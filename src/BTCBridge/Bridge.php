<?php

namespace BTCBridge;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use BTCBridge\Api\TransactionReference;
use BTCBridge\Handler\AbstractHandler;
use BTCBridge\ResultHandler\AbstractResultHandler;
use BTCBridge\ConflictHandler\ConflictHandlerInterface;
use BTCBridge\Exception\ConflictHandlerException;
use BTCBridge\Exception\ResultHandlerException;
use BTCBridge\Exception\HandlerErrorException;
use BTCBridge\ConflictHandler\DefaultConflictHandler;
use BTCBridge\ResultHandler\DefaultResultHandler;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;
use BTCBridge\Api\Wallet;
use BTCBridge\Api\SMOutput;
use BTCBridge\Api\SendMoneyOptions;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;

/**
 * Describes a Bridge instance
 *
 * Bridge takes commands from the users and retranslate them to different bitcoin services.
 *
 */
class Bridge
{

    /** This group of constants are bridge options */
    const OPT_LOCAL_PATH_OF_WALLET_DATA = 1;
    const OPT_MINIMAL_AMOUNT_FOR_SENT = 2;
    const OPT_MINIMAL_FEE_PER_KB = 3;

    /** @var array options */
    protected $options = [];

    /**
     * The handler stack
     *
     * @var AbstractHandler[]
     */
    protected $handlers;

    /**
     * The conflictHandler
     *
     * @var ConflictHandlerInterface
     */
    protected $conflictHandler;

    /**
     * The result handler
     *
     * @var AbstractResultHandler
     */
    protected $resultHandler;


    /**
     * The conflictHandler
     *
     * @var ConflictHandlerInterface
     */
    protected $loggerHandler;

    /**
     * @param AbstractHandler[] $handlers         Stack of handlers for calling BTC-methods, $handlers must not be empty
     * @param ConflictHandlerInterface $conflictHandler  Methods of this objects will be raised for validating results.
     * Parameter is optional, by default DefaultConflictHandler instance will be used
     * @param AbstractResultHandler $resultHandler
     * @param LoggerInterface $loggerHandler    Methods of this objects will be raised for validating results.
     * Parameter is optional, by default DefaultConflictHandler instance will be used
     *
     * @throws \InvalidArgumentException if the provided argument $handlers is empty
     * @throws \RuntimeException if the provided argument $conflictHandler is not instance of HandlerInterface
     */
    public function __construct(
        array $handlers,
        ConflictHandlerInterface $conflictHandler = null,
        AbstractResultHandler $resultHandler = null,
        LoggerInterface $loggerHandler = null
    ) {
        if (empty($handlers)) {
            throw new \InvalidArgumentException("Handlers array can not be empty.");
        }
        foreach ($handlers as $handler) {
            if (!$handler instanceof AbstractHandler) {
                throw new \InvalidArgumentException("The given handler is not a AbstractHandler");
            }
        }

        $this->handlers = $handlers;
        $this->conflictHandler = (null !== $conflictHandler) ? $conflictHandler : new DefaultConflictHandler();
        $this->resultHandler = (null !== $resultHandler) ? $resultHandler : new DefaultResultHandler();
        $this->resultHandler->setHandlers($handlers);
        if ($loggerHandler) {
            $this->loggerHandler = $loggerHandler;
            foreach ($this->handlers as $handler) {
                $handler->setLogger($loggerHandler);
            }
        } else {
            $this->loggerHandler = new Logger('BTCBridge');
        }
        $this->setOption(self::OPT_LOCAL_PATH_OF_WALLET_DATA, __DIR__."/wallet.dat");
        $this->setOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT, "5500");
        $this->setOption(self::OPT_MINIMAL_FEE_PER_KB, "10000");
    }

    /**
     * Sets the option
     *
     * @param int $optionName a constant describying name of the option
     * @param string $optionValue a value of the option
     *
     * @throws \InvalidArgumentException if error of this type
     *
     */
    public function setOption($optionName, $optionValue)
    {
        if ((gettype($optionName) != "integer") ||
            (!in_array(
                $optionName,
                [
                    self::OPT_LOCAL_PATH_OF_WALLET_DATA,
                    self::OPT_MINIMAL_AMOUNT_FOR_SENT,
                    self::OPT_MINIMAL_FEE_PER_KB
                ]
            ))) {
            throw new \InvalidArgumentException("Bad type of option (".$optionName.")");
        }
        if (gettype($optionValue) != "string" || "" == $optionValue) {
            throw new \InvalidArgumentException("Bad type of option value (must be non empty string)");
        }
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Gets the option
     *
     * @param int $optionName a constant, which describes the name of the option
     *
     * @throws \InvalidArgumentException if error of this type
     * @throws \RuntimeException in case if this option is not exists
     *
     * @return string Option
     */
    protected function getOption($optionName)
    {
        if ((gettype($optionName) != "integer") ||
            (!in_array(
                $optionName,
                [
                    self::OPT_LOCAL_PATH_OF_WALLET_DATA,
                    self::OPT_MINIMAL_AMOUNT_FOR_SENT,
                    self::OPT_MINIMAL_FEE_PER_KB
                ]
            ))) {
            throw new \InvalidArgumentException("Bad type of option (".$optionName.")");
        }
        if (!isset($this->options[$optionName])) {
            throw new \RuntimeException("No option with name \"" . $optionName . "\" exists in the class)");
        }
        return $this->options[$optionName];
    }

    /**
     * The binarySearch method choose the most suitable (equal or more than passed amount)
     * output from the set of passed outputs.
     * outputs must be sorted ascending!
     *
     * @link http://en.cppreference.com/w/cpp/algorithm/lower_bound
     *
     * @param TransactionReference[] $outputs
     * @param integer $first
     * @param integer $last
     * @param integer $searchedValue
     *
     * @return integer (index in array or -1 if item was not found)
     */
    private function binarySearch(array $outputs, $first, $last, $searchedValue)
    {
        if ($outputs[$last-1]->getValue() < $searchedValue) {
            return -1;
        }
        $count = $last - $first;
        while ($count > 0) {
            $it = $first;
            $step = intval(floor($count/2));
            $it += $step;
            if ($outputs[$it]->getValue() < $searchedValue) {
                ++$it;
                $first = $it;
                $count -= $step + 1;
            } else {
                $count = $step;
            }
        }
        return $first;
    }

    /**
     * The selectOutputsForSpent method choose optimal outputs for spent from the set of unspented outputs of wallet.
     *
     * @link http://bitcoin.stackexchange.com/questions/1077/what-is-the-coin-selection-algorithm
     *
     * @param TransactionReference[] $outputs
     * @param integer $amount string
     *
     * @throws \RuntimeException in case of any error of this type
     * @throws \InvalidArgumentException in case of any error of this type
     *
     * @return TransactionReference[] If not enouth BTC on passed outputs then ampty array will be returned
     */
    public function selectOutputsForSpent($outputs, $amount)
    {
        if ("integer" != gettype($amount) || ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)))) {
            throw new \InvalidArgumentException(
                "amount variable must be integer bigger or equal " .
                $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT) . "."
            );
        }
        if (!is_array($outputs) || empty($outputs)) {
            throw new \InvalidArgumentException(
                "outputs variable must be non empty array of TransactionReference type."
            );
        }
        /** @var $outputs TransactionReference[] */

        //Now we'll check sufficiency of total balance of passed subset of outputs -
        //and will try to find the output with the same value as needed
        $sum = 0;
        for ($i = 0, $ic = count($outputs); $i < $ic; ++$i) {
            if ($outputs[$i]->getValue() == $amount) {
                return [$outputs[$i]];
            }
            $sum += $outputs[$i]->getValue();
        }
        if ($sum < $amount) {
            return []; //Not enough BTC
        }

        //We'll sort $outputs array in ascending order
        usort(
            $outputs,
            function (TransactionReference $a, TransactionReference $b) {
                if ($a->getValue() == $b->getValue()) {
                    return 0;
                }
                return ($a->getValue() < $b->getValue()) ? -1 : 1;
            }
        );

        //No output with the value which is equal to the necessary, so will approximate

        /** @var $result TransactionReference[] */
        $result = [];

        while (true) {
            //Firstly we'll try to find 1 output which has enough money
            $outputIndex =  $this->binarySearch($outputs, 0, count($outputs), $amount);
            if (-1 != $outputIndex) {
                $result [] = $outputs[$outputIndex];
                return $result;
            }
            $amountOfBigOutput = $outputs[count($outputs)-1]->getValue();
            $amount -= $amountOfBigOutput;
            $result [] = array_pop($outputs);
        }
        return $result;
    }


    /**
     * The listtransactions RPC returns the most recent transactions that affect the wallet.
     * The default Address Endpoint strikes a balance between speed of response and data on Addresses.
     * It returns more information about an address’ transactions than the Address Balance Endpoint
     * but doesn’t return full transaction information (like the Address Full Endpoint).
     * @link https://bitcoin.org/en/developer-reference#listtransactions Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint Official blockcypher documentation
     *
     * @param string $address  An account name (or address) to get transactions from
     * @param array $options  Array containing the optional params
     * $options = [
     *   ['unspentOnly']           bool      If unspentOnly is true, filters response to only include unspent
     *   transaction outputs (UTXOs).
     *   ['includeScript']         bool      If includeScript is true, includes raw script of input or output
     *   within returned TXRefs.
     *   ['includeConfidence']     bool      If true, includes the confidence attribute (useful for unconfirmed
     *   transactions) within returned TXRefs. For more info about this figure,
     *   check the Confidence Factor documentation.
     *   ['before']                integer   Filters response to only include transactions below before height
     *   in the blockchain.
     *   ['after']                 integer   Filters response to only include transactions above after height
     *   in the blockchain.
     *   ['limit']                 integer   Limit sets the minimum number of returned TXRefs; there can be less
     *   if there are less than limit TXRefs associated with this address, but there can be more in the rare case
     *   of more TXRefs in the block at the bottom of your call.
     *   This ensures paging by block height never misses TXRefs.
     *   Defaults to 200, maximum is 2000.
     *   ['confirmations']         integer   If set, only returns the balance and TXRefs that have at least this number
     *   of confirmations.
     *   ['confidence']            integer   Filters response to only include TXRefs above confidence in percent; e.g.,
     *   if this is set to 99, will only return TXRefs with 99% confidence or above (including all confirmed TXRefs).
     *   For more detail on confidence, check the Confidence Factor documentation.
     *   ['omitWalletAddresses']   bool      If omitWalletAddresses is true and you’re querying a Wallet or HDWallet,
     *   the response will omit address information (useful to speed up the API call for larger wallets).
     * ]
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException in case of error of this type
     * @throws ConflictHandlerException in case of any error of this type
     * @throws ResultHandlerException in case of any error of this type
     *
     * @return Address
     */
    public function listtransactions($address, array $options = array())
    {
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listtransactions($address, $options);
            $results [] = $result;
        }
        $this->conflictHandler->listtransactions($results);
        return $this->resultHandler->listtransactions($results);
    }

    /**
     * The gettransaction RPC gets detailed information about an in-wallet transaction.
     * The Transaction Hash Endpoint returns detailed information about a given transaction based on its hash.
     * @link https://bitcoin.org/en/developer-reference#gettransaction Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?php#transaction-hash-endpoint
     *
     * @param string $TXHASH   a transaction identifier
     * @param array $options   Array containing the optional params
     * $options = [
     *   ['limit']             integer    Filters TXInputs/TXOutputs, if unset, default is 20.
     *   ['instart']           integer    Filters TX to only include TXInputs from this input index and above.
     *   ['outstart']          integer    Filters TX to only include TXOutputs from this output index and above.
     *   ['includeHex']        bool    If true, includes hex-encoded raw transaction; false by default.
     *   ['includeConfidence'] bool    If true, includes the confidence attribute (useful for unconfirmed transactions).
     *   For more info about this figure, check the Confidence Factor documentation.
     * ]
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     *
     * @return Transaction
     */
    public function gettransaction($TXHASH, array $options = array())
    {
        if ("string" != gettype($TXHASH) || ("" == $TXHASH)) {
            throw new \InvalidArgumentException("TXHASH variable must be non empty string.");
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->gettransaction($TXHASH, $options);
            $results [] = $result;
        }
        $this->conflictHandler->gettransaction($results);
        return $this->resultHandler->gettransaction($results);
    }

    /**
     * The getbalance RPC gets the balance in decimal bitcoins across all accounts or for a particular account.
     * The Address Balance Endpoint is the simplest—and fastest—method
     * to get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getbalance Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param string $walletName            An account name to get balance from
     * @param int $Confirmations         The minimum number of confirmations an externally-generated transaction
     * must have before it is counted towards the balance.
     * @param boolean $IncludeWatchOnly  Whether to include watch-only addresses in details and calculations
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     *
     * @return integer                   The balance in bitcoins (in satoshi)
     */
    public function getbalance($walletName, $Confirmations = 1, $IncludeWatchOnly = false)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->getbalance($walletName, $Confirmations, $IncludeWatchOnly);
            $results [] = $result;
        }
        $this->conflictHandler->getbalance($results);
        return $this->resultHandler->getbalance($results);
    }

    /**
     * Returns the wallet’s total unconfirmed balance.
     * The Address Balance Endpoint is the simplest—and fastest—method
     * to get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param string $walletName An account name to get unconfirmed balance from
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     *
     * @return integer The total number of bitcoins paid to the passed wallet in unconfirmed transactions (in satoshi)
     */
    public function getunconfirmedbalance($walletName)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            //$result = call_user_func_array([$handle, "getunconfirmedbalance"], [$Account]);
            $result = $handle->getunconfirmedbalance($walletName);
            $results [] = $result;
        }
        $this->conflictHandler->getunconfirmedbalance($results);
        return $this->resultHandler->getunconfirmedbalance($results);
    }

    /**
     * Returns an array of unspent transaction outputs belonging to this wallet.
     * The Address Balance Endpoint is the simplest—and fastest—method to
     * get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#listunspent Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param string $walletName An account name to get unconfirmed balance from
     * @param int $MinimumConfirmations  The minimum number of confirmations the transaction containing an output
     * must have in order to be returned.
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     *
     * @return array The list of unspent outputs
     */
    public function listunspent($walletName, $MinimumConfirmations = 1)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $MinimumConfirmations);
            $results [] = $result;
        }
        $this->conflictHandler->listunspent($results);
        return $this->resultHandler->listunspent($results);
    }

    /**
     * The sendrawtransaction RPC validates a transaction and broadcasts it to the peer-to-peer network.
     * @link https://bitcoin.org/en/developer-reference#sendrawtransaction Official bitcoin documentation.
     *
     * @param string $Transaction
     *
     * @return string If the transaction was accepted by the node for broadcast, this will be the TXID
     * of the transaction encoded as hex in RPC byte order.
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     *
     */
    public function sendrawtransaction($Transaction)
    {
        if ("string" != gettype($Transaction) || ("" == $Transaction)) {
            throw new \InvalidArgumentException("Transaction variable must be non empty string.");
        }
        $result = null;
        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $result = $this->handlers[$i]->sendrawtransaction($Transaction);
            } catch (\InvalidArgumentException $ex) {
                $this->loggerHandler->addError($ex->getMessage());
                continue;
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->addError($ex->getMessage());
                continue;
            }
            return $result;
        }
        throw new \RuntimeException(
            "Transaction \"" . $Transaction . "\" was not sent (" . count($this->handlers) . " handlers)."
        );
    }

    /**
     * This Method allows you to create a new wallet, by POSTing a partially filled out Wallet or HDWallet object,
     * depending on the endpoint.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#create-wallet-endpoint
     *
     * @param string $walletName
     * @param string[] $addresses
     *
     * @return Wallet
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     * @throws HandlerErrorException in case of any error with Handler occured
     *
     */
    public function createWallet($walletName, $addresses)
    {
        if ("string" != gettype($walletName)) {
            throw new \InvalidArgumentException("Wallet name variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (!is_array($addresses)) {
            throw new \InvalidArgumentException("addresses variable must be the array.");
        }
        /** @var $resultWallets Wallet[] */
        $resultWallets = [];
        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandler AbstractHandler */
        $errorHandler = null;
        /** @var $unusedHandlers AbstractHandler[] */
        $unusedHandlers = [];

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $resultWallet = $this->handlers[$i]->createWallet($walletName, $addresses);
                /*$resultWallet->setSystemDataByHandler(
                    $this->handlers[$i]->getHandlerName(),
                    $this->handlers[$i]->getSystemDataForWallet($resultWallet)
                );*/
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                break;
            }
            $successHandlers [] = $this->handlers[$i];
            $resultWallets [] = $resultWallet;
        }

        if ($errorHandler) {
            $resultWallet = new Wallet();
            if (!empty($successHandlers)) {
                $this->resultHandler->setHandlers($successHandlers);
                $resultWallet = $this->resultHandler->createWallet($resultWallets);
                $this->resultHandler->setHandlers($this->handlers);
            }
            for ($j = $i + 1; $j < $ic; ++$j) {
                $unusedHandlers [] = $this->handlers[$j];
            }
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandler,
                $unusedHandlers,
                $resultWallet,
                '"' . $errorHandler->getHandlerName() . '" handler raised error (method createWallet).'
            );
        }
        $this->conflictHandler->createWallet($resultWallets); //In case of error throw will be raised
        return $this->resultHandler->createWallet($resultWallets);
    }

    /**
     * This Method adds new addresses into a wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#add-addresses-to-wallet-endpoint
     *
     * @param Wallet $wallet Object to which addresses will be added
     * @param string[] $addresses
     *
     * @return Wallet
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     * @throws HandlerErrorException in case of any error with Handler occured
     *
     */
    public function addAddresses(Wallet $wallet, $addresses)
    {
        if ((!is_array($addresses)) || (count($addresses) == 0)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array.");
        }

        /** @var $resultWallets Wallet[] */
        $resultWallets = [];
        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandler AbstractHandler */
        $errorHandler = null;
        /** @var $unusedHandlers AbstractHandler[] */
        $unusedHandlers = [];

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $wallet = $this->handlers[$i]->addAddresses($wallet, $addresses);
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                break;
            }
            $successHandlers [] = $this->handlers[$i];
            $resultWallets [] = $wallet;
        }

        if ($errorHandler) {
            $resultWallet = new Wallet();
            if (!empty($successHandlers)) {
                $this->resultHandler->setHandlers($successHandlers);
                $resultWallet = $this->resultHandler->addAddresses($resultWallets);
                $this->resultHandler->setHandlers($this->handlers);
            }
            for ($j = $i + 1; $j < $ic; ++$j) {
                $unusedHandlers [] = $this->handlers[$j];
            }
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandler,
                $unusedHandlers,
                $resultWallet,
                '"' . $errorHandler->getHandlerName() . '" handler raised error (method addAddresses).'
            );
        }
        $this->conflictHandler->addAddresses($resultWallets);
        return $this->resultHandler->addAddresses($resultWallets);
    }

    /**
     * This Method adds new addresses into a wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#remove-addresses-from-wallet-endpoint
     *
     * @param Wallet $wallet
     * @param string $address
     *
     * @return \BTCBridge\Api\Wallet
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     * @throws HandlerErrorException in case of any error with Handler occured
     *
     */
    public function removeAddress(Wallet $wallet, $address)
    {
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }

        /** @var $resultWallets Wallet[] */
        $resultWallets = [];
        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandler AbstractHandler */
        $errorHandler = null;
        /** @var $unusedHandlers AbstractHandler[] */
        $unusedHandlers = [];

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $wallet = $this->handlers[$i]->removeAddress($wallet, $address);
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                break;
            }
            $successHandlers [] = $this->handlers[$i];
            $resultWallets [] = $wallet;
        }

        if ($errorHandler) {
            $resultWallet = new Wallet();
            if (!empty($successHandlers)) {
                $this->resultHandler->setHandlers($successHandlers);
                $resultWallet = $this->resultHandler->removeAddress($resultWallets);
                $this->resultHandler->setHandlers($this->handlers);
            }
            for ($j = $i + 1; $j < $ic; ++$j) {
                $unusedHandlers [] = $this->handlers[$j];
            }
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandler,
                $unusedHandlers,
                $resultWallet,
                '"' . $errorHandler->getHandlerName() . '" handler raised error (method removeAddress).'
            );
        }
        $this->conflictHandler->removeAddress($resultWallets);
        return $this->resultHandler->removeAddress($resultWallets);
    }


    /**
     * This Method deletes a passed wallet
     * https://www.blockcypher.com/dev/bitcoin/?shell#delete-wallet-endpoint
     *
     * @param Wallet $wallet wallet for deleting
     *
     * @throws Exception\HandlerErrorException
     * @throws \InvalidArgumentException if error of this type
     *
     */
    public function deleteWallet(Wallet $wallet)
    {
        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandler AbstractHandler */
        $errorHandler = null;
        /** @var $unusedHandlers AbstractHandler[] */
        $unusedHandlers = [];

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $this->handlers[$i]->deleteWallet($wallet);
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                break;
            }
            $successHandlers [] = $this->handlers[$i];
        }
        if ($errorHandler) {
            for ($j = $i + 1; $j < $ic; ++$j) {
                $unusedHandlers [] = $this->handlers[$j];
            }
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandler,
                $unusedHandlers,
                null,/*This method does not return wallet, so we can pass null only*/
                '"' . $errorHandler->getHandlerName() . '" handler raised error (method deleteWallet).'
            );
        }
    }

    /**
     * This method returns addresses from the passed wallet
     * @link https://bitcoin.org/en/developer-reference#getaddressesbyaccount
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#get-wallet-addresses-endpoint
     *
     * @param Wallet $wallet
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     *
     * @return \string[] addesses
     */
    public function getAddresses(Wallet $wallet)
    {
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->getAddresses($wallet);
            $results [] = $result;
        }
        $this->conflictHandler->getAddresses($results);
        return $this->resultHandler->getAddresses($results);
    }

    /**
     * The getnewaddress RPC returns a new Bitcoin address for receiving payments.
     * If an account is specified, payments received with the address will be credited to that account.
     * @link https://bitcoin.org/en/developer-reference#getnewaddress
     *
     * @param string $walletName Name of wallet
     *
     * @return \string
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     *
     */
    public function getnewaddress($walletName)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("wallet Name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        try {
            $privateKey = PrivateKeyFactory::create();
            $address = $privateKey->getPublicKey()->getAddress();
            $address = $address->getAddress();
            $network = Bitcoin::getNetwork();
            $wif = $privateKey->toWif($network);
        } catch (Base58ChecksumFailure $ex) {
            throw new \RuntimeException($ex->getMessage());
        } //May be \RuntimeException will raised in the BitWASP library - we'll not change this
        if (!file_put_contents(
            $this->getOption(Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA),
            ($walletName.";".$wif.";".$address.PHP_EOL),
            FILE_APPEND
        ) ) {
            throw new \RuntimeException(
                "Write data into the file " . $this->getOption(Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA) . " failed."
            );
        }
        return $address;
    }

    /**
     * The dumpprivkey RPC returns the wallet-import-format (WIP) private key corresponding to an address.
     * (But does not remove it from the wallet.)
     * @link https://bitcoin.org/en/developer-reference#dumpprivkey
     *
     * @param string $walletName Name of wallet
     * @param string $address
     *
     * @return \string|null WIF or null (if didn't found)
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     *
     */
    public function dumpprivkey($walletName, $address)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("wallet Name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }

        $path = $this->getOption(Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA);

        $handle = fopen($path, "r");
        if (false === $handle) {
            throw new \RuntimeException(
                "Read data from the file " . $path . " failed."
            );
        }
        $i = 0;
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            ++$i;
            $num = count($data);
            if (3 != $num) {
                throw new \RuntimeException(
                    "Line #" . $i . " in the file " . $path . " contains " .
                    $num . " fields, must contain 3 fields only."
                );
            }
            if (( $address == $data[2] ) && ( $walletName == $data[0] )) {
                fclose($handle);
                return $data[1];
            }
        }
        fclose($handle);
        return null;
    }

    /**
     * The settxfee RPC sets the transaction fee per kilobyte paid by transactions created by this wallet.
     * @link https://bitcoin.org/en/developer-reference#settxfee
     *
     * @param integer $fee The transaction fee to pay, in satoshis, for each kilobyte of transaction data.
     *
     * @return boolean true on success
     *
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
     *
     */
    public function settxfee($fee)
    {
        if ((gettype($fee) != "integer") || ($fee <= intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB)))) {
            throw new \InvalidArgumentException(
                "fee variable must be integer and more or  equal than " .
                $this->getOption(self::OPT_MINIMAL_FEE_PER_KB) . ")."
            );
        }
        $this->setOption(self::OPT_MINIMAL_FEE_PER_KB, strval($fee));
        return true;
    }

    /**
     * The sendfrom RPC spends an amount from a local account to a bitcoin address.
     * @link https://bitcoin.org/en/developer-reference#sendfrom
     *
     * @param string $walletName  The wallet, which is source for money
     * @param string $address     The address to which the bitcoins should be sent
     * @param integer $amount The amount to spend in satoshis.
     * @param int $confirmations  The minimum number of confirmations the transaction containing an output
     * @param string $comment  A locally-stored (not broadcast) comment assigned to this
     * transaction. Default is no comment
     * must have in order to be returned.
     * @param string $commentTo A locally-stored (not broadcast) comment assigned to this transaction.
     * Meant to be used for describing who the payment was sent to. Default is no comment.
     *
     * @return string $transactionId
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     * @throws ResultHandlerException in case of any error of this type
     *
     */
    public function sendfrom($walletName, $address, $amount, $confirmations = 1, $comment = "", $commentTo = "")
    {
        if ("string" != gettype($walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }
        if ("integer" != gettype($amount) || ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
            throw new \InvalidArgumentException(
                "amount variable must be integer bigger or equal " . self::OPT_MINIMAL_AMOUNT_FOR_SENT . "."
            );
        }
        if ("integer" != gettype($confirmations) || ($confirmations < 0)) {
            throw new \InvalidArgumentException("confirmation variable must be non negative integer.");
        }
        if ("string" != gettype($comment)) {
            throw new \InvalidArgumentException("comment variable must be a string variable.");
        }
        if ("string" != gettype($commentTo)) {
            throw new \InvalidArgumentException("commentTo variable must be a string variable.");
        }

        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $confirmations);
            $results [] = $result;
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
        $mimimumRequiredFee = intval(ceil((1*181+2*34+10) * $feePerKb / 1024));
        $requiredCoins = $amount + $mimimumRequiredFee;
        $sumAmount = null;
        $requiredFee = null;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $change = 0;
        do {
            /** @var $outputsForSpent TransactionReference[] */
            $outputsForSpent = $this->selectOutputsForSpent($unspents, $requiredCoins);
            if (empty($outputsForSpent)) {
                return ""; //No possible to create transaction, not enough money on unspent outputs of this wallet
            }
            $sumFromOutputs = 0;
            for ($i = 0, $ic = count($outputsForSpent); $i < $ic; ++$i) {
                $sumFromOutputs += $outputsForSpent[$i]->getValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(ceil((count($outputsForSpent)*181+34*2+10) * $feePerKb / 1024));
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount = $amount - ( intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change );
                    if ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                        throw new \InvalidArgumentException(
                            "The transaction amount is too small to send after the fee has been deducted."
                        );
                    }
                    $change = intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT));
                }
                break;
            } else {
                //no change
                break;
            }
            //throw new \RuntimeException(
                //"Logic error (\$amount (" . $amount . ") less than \$sumAmount" . $sumAmount . ")."
            //);
        } while (true);
        //$sumAmount;
        //$requiredFee;

        $transactionSources = [];
        foreach ($outputsForSpent as $output) {
            $txSource = new \stdClass();
            $txSource->address = AddressFactory::fromString($output->getAddress());
            $txSource->privateKey = $this->dumpprivkey($walletName, $output->getAddress());
            if (!$txSource->privateKey) {
                throw new \RuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getTxOutputN();
            $txSource->amount = $output->getValue();
            $txSource->outpoint = new OutPoint(Buffer::hex($txSource->sourceTxId), $txSource->sourceVout);
            $txSource->transactionOutput = new TransactionOutput($txSource->amount, $txSource->outputScript);
            $transactionSources [] = clone $txSource;
        }

        $transaction = TransactionFactory::build();
        foreach ($transactionSources as $source) {
            $transaction = $transaction->spendOutPoint($source->outpoint);
        }
        $transaction = $transaction->payToAddress($amount, AddressFactory::fromString($address));
        if ($change >= $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = $outputsForSpent[0]->getAddress();
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, AddressFactory::fromString($addressForChange));
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $transaction = $transaction->get();

        $ec = Bitcoin::getEcAdapter();
        $signer = new Signer($transaction, $ec);
        for ($i = 0, $ic = count($transactionSources); $i < $ic; ++$i) {
            $signer->sign($i, $transactionSources[$i]->privateKey, $transactionSources[$i]->transactionOutput);
        }
        $signedTransaction = $signer->get();
        $raw = $signedTransaction->getHex();
        return $this->sendrawtransaction($raw);
        //print $raw . PHP_EOL;
        /** @noinspection PhpUnusedLocalVariableInspection */
        //$size = $signedTransaction->getBuffer()->getSize();
    }

    /**
     * The sendfrom RPC spends an amount from a local account to a bitcoin address.
     * @link https://bitcoin.org/en/developer-reference#sendfrom
     *
     * @param string $walletName  The wallet, which is source for money
     * @param string $address     The address to which the bitcoins should be sent
     * @param integer $amount The amount to spend in satoshis.
     * @param SendMoneyOptions $sendMoneyOptions (comment,confirmations,commentTo etc)
     *
     * @return string $transactionId
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     * @throws ResultHandlerException in case of any error of this type
     *
     */
    public function sendfromEX($walletName, $address, $amount, SendMoneyOptions $sendMoneyOptions)
    {
        if ("string" != gettype($walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }
        if ("integer" != gettype($amount) || ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
            throw new \InvalidArgumentException(
                "amount variable must be integer bigger or equal " . self::OPT_MINIMAL_AMOUNT_FOR_SENT . "."
            );
        }
        $confirmations = $sendMoneyOptions->getConfirmations();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $comment = $sendMoneyOptions->getComment();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $commentTo = $sendMoneyOptions->getCommentTo();

        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $confirmations);
            $results [] = $result;
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
        //Ideal case - one input for spend, two outputs - one is for destination addreses, one - for change
        $mimimumRequiredFee = intval(ceil((1*181+2*34+10) * $feePerKb / 1024));
        $requiredCoins = $amount + $mimimumRequiredFee;
        $sumAmount = null;
        $requiredFee = null;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $change = 0;
        do {
            /** @var $outputsForSpent TransactionReference[] */
            $outputsForSpent = $this->selectOutputsForSpent($unspents, $requiredCoins);
            if (empty($outputsForSpent)) {
                return ""; //No possible to create transaction, not enough money on unspent outputs of this wallet
            }
            $sumFromOutputs = 0;
            for ($i = 0, $ic = count($outputsForSpent); $i < $ic; ++$i) {
                $sumFromOutputs += $outputsForSpent[$i]->getValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(ceil((count($outputsForSpent)*181+34*2+10) * $feePerKb / 1024));
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount = $amount - ( intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change );
                    if ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                        throw new \InvalidArgumentException(
                            "The transaction amount is too small to send after the fee has been deducted."
                        );
                    }
                    $change = intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT));
                }
                break;
            } else {
                //no change
                break;
            }
        } while (true);

        $transactionSources = [];
        foreach ($outputsForSpent as $output) {
            $txSource = new \stdClass();
            $txSource->address = AddressFactory::fromString($output->getAddress());
            $txSource->privateKey = $this->dumpprivkey($walletName, $output->getAddress());
            if (!$txSource->privateKey) {
                throw new \RuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getTxOutputN();
            $txSource->amount = $output->getValue();
            $txSource->outpoint = new OutPoint(Buffer::hex($txSource->sourceTxId), $txSource->sourceVout);
            $txSource->transactionOutput = new TransactionOutput($txSource->amount, $txSource->outputScript);
            $transactionSources [] = clone $txSource;
        }

        $transaction = TransactionFactory::build();
        foreach ($transactionSources as $source) {
            $transaction = $transaction->spendOutPoint($source->outpoint);
        }
        $transaction = $transaction->payToAddress($amount, AddressFactory::fromString($address));
        if ($change >= $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = ("" != $sendMoneyOptions) ? $sendMoneyOptions->getAddressForChange() : $outputsForSpent[0]->getAddress();
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, AddressFactory::fromString($addressForChange));
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $transaction = $transaction->get();

        $ec = Bitcoin::getEcAdapter();
        $signer = new Signer($transaction, $ec);
        for ($i = 0, $ic = count($transactionSources); $i < $ic; ++$i) {
            $signer->sign($i, $transactionSources[$i]->privateKey, $transactionSources[$i]->transactionOutput);
        }
        $signedTransaction = $signer->get();
        $raw = $signedTransaction->getHex();
        return $this->sendrawtransaction($raw);
        //print $raw . PHP_EOL;
        /** @noinspection PhpUnusedLocalVariableInspection */
        //$size = $signedTransaction->getBuffer()->getSize();
    }

    /**
     * The sendmany RPC creates and broadcasts a transaction which sends outputs to multiple addresses.
     * @link https://bitcoin.org/en/developer-reference#sendmany
     *
     * @param string $walletName  The wallet, which is source for money
     * @param SMOutput[] $smoutputs Object containing key/value pairs corresponding to the addresses and amounts to pay
     * @param int $confirmations  The minimum number of confirmations the transaction containing an output
     * @param string $comment
     * A locally-stored (not broadcast) comment assigned to this transaction. Default is no comment
     *
     * @return string $transactionId
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     *
     */
    public function sendmany($walletName, array $smoutputs, $confirmations = 1, $comment = "")
    {
        /** @var $smoutputs SMOutput[] */
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (empty($smoutputs)) {
            throw new \InvalidArgumentException("\$smoutputs variable must be non empty string.");
        }
        $amount = 0;
        for ($i = 0, $ic = count($smoutputs); $i < $ic; ++$i) {
            if (!$smoutputs[$i] instanceof SMOutput) {
                throw new \InvalidArgumentException(
                    "items of smoutputs variable must be instances of SMOutput class"
                );
            }
            $amount += $smoutputs[$i]->getAmount();
        }
        if ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            throw new \InvalidArgumentException(
                "total amount from outputs must bigger or equal " . self::OPT_MINIMAL_AMOUNT_FOR_SENT . "."
            );
        }
        if ("integer" != gettype($confirmations) || ($confirmations < 0)) {
            throw new \InvalidArgumentException("confirmation variable must be non negative integer.");
        }
        if ("string" != gettype($comment)) {
            throw new \InvalidArgumentException("comment variable must be a string variable.");
        }

        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $confirmations);
            $results [] = $result;
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        $mimimumRequiredFee = intval(ceil((1*181+(count($smoutputs)+1)*34+10) * $feePerKb / 1024));
        $requiredCoins = $amount + $mimimumRequiredFee;
        $sumAmount = null;
        $requiredFee = null;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $change = 0;
        do {
            /** @var $outputsForSpent TransactionReference[] */
            $outputsForSpent = $this->selectOutputsForSpent($unspents, $requiredCoins);
            if (empty($outputsForSpent)) {
                return ""; //No possible to create transaction, not enough money on unspent outputs of this wallet
            }
            $sumFromOutputs = 0;
            for ($i = 0, $ic = count($outputsForSpent); $i < $ic; ++$i) {
                $sumFromOutputs += $outputsForSpent[$i]->getValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(
                ceil((count($outputsForSpent)*181+34*(count($smoutputs)+1)+10) * $feePerKb / 1024)
            );
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount = $amount - ( intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change );
                    if ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                        throw new \InvalidArgumentException(
                            "The transaction amount is too small to send after the fee has been deducted."
                        );
                    }
                    $change = intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT));
                }
                break;
            } else {
                //no change
                break;
            }
        } while (true);

        $transactionSources = [];
        foreach ($outputsForSpent as $output) {
            $txSource = new \stdClass();
            $txSource->address = AddressFactory::fromString($output->getAddress());
            $txSource->privateKey = $this->dumpprivkey($walletName, $output->getAddress());
            if (!$txSource->privateKey) {
                throw new \RuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getTxOutputN();
            $txSource->amount = $output->getValue();
            $txSource->outpoint = new OutPoint(Buffer::hex($txSource->sourceTxId), $txSource->sourceVout);
            $txSource->transactionOutput = new TransactionOutput($txSource->amount, $txSource->outputScript);
            $transactionSources [] = clone $txSource;
        }

        $transaction = TransactionFactory::build();
        foreach ($transactionSources as $source) {
            $transaction = $transaction->spendOutPoint($source->outpoint);
        }
        foreach ($smoutputs as $sendmanyoutput) {
            $transaction = $transaction->payToAddress(
                $sendmanyoutput->getAmount(),
                AddressFactory::fromString($sendmanyoutput->getAddress())
            );
        }
        if ($change > $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = $outputsForSpent[0]->getAddress();
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, AddressFactory::fromString($addressForChange));
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $transaction = $transaction->get();

        $ec = Bitcoin::getEcAdapter();
        $signer = new Signer($transaction, $ec);
        for ($i = 0, $ic = count($transactionSources); $i < $ic; ++$i) {
            $signer->sign($i, $transactionSources[$i]->privateKey, $transactionSources[$i]->transactionOutput);
        }
        $signedTransaction = $signer->get();
        $raw = $signedTransaction->getHex();
        return $this->sendrawtransaction($raw);
    }

    /**
     * The sendmany RPC creates and broadcasts a transaction which sends outputs to multiple addresses.
     * @link https://bitcoin.org/en/developer-reference#sendmany
     *
     * @param string $walletName  The wallet, which is source for money
     * @param SMOutput[] $smoutputs Object containing key/value pairs corresponding to the addresses and amounts to pay
     * @param SendMoneyOptions $sendMoneyOptions (comment,confirmations,commentTo etc)
     *
     * @return string $transactionId
     *
     * @throws \RuntimeException in case of any runtime error
     * @throws \InvalidArgumentException if error of this type
     * @throws ConflictHandlerException in case of any error of this type
     * @throws ResultHandlerException in case of any error of this type
     *
     */
    public function sendmanyEX($walletName, array $smoutputs, SendMoneyOptions $sendMoneyOptions)
    {
        /** @var $smoutputs SMOutput[] */
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (empty($smoutputs)) {
            throw new \InvalidArgumentException("\$smoutputs variable must be non empty string.");
        }
        $amount = 0;
        for ($i = 0, $ic = count($smoutputs); $i < $ic; ++$i) {
            if (!$smoutputs[$i] instanceof SMOutput) {
                throw new \InvalidArgumentException(
                    "items of smoutputs variable must be instances of SMOutput class"
                );
            }
            $amount += $smoutputs[$i]->getAmount();
        }
        if ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            throw new \InvalidArgumentException(
                "total amount from outputs must bigger or equal " . self::OPT_MINIMAL_AMOUNT_FOR_SENT . "."
            );
        }
        $confirmations = $sendMoneyOptions->getConfirmations();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $comment = $sendMoneyOptions->getComment();

        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $confirmations);
            $results [] = $result;
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        $mimimumRequiredFee = intval(ceil((1*181+(count($smoutputs)+1)*34+10) * $feePerKb / 1024));
        $requiredCoins = $amount + $mimimumRequiredFee;
        $sumAmount = null;
        $requiredFee = null;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $change = 0;
        do {
            /** @var $outputsForSpent TransactionReference[] */
            $outputsForSpent = $this->selectOutputsForSpent($unspents, $requiredCoins);
            if (empty($outputsForSpent)) {
                return ""; //No possible to create transaction, not enough money on unspent outputs of this wallet
            }
            $sumFromOutputs = 0;
            for ($i = 0, $ic = count($outputsForSpent); $i < $ic; ++$i) {
                $sumFromOutputs += $outputsForSpent[$i]->getValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(
                ceil((count($outputsForSpent)*181+34*(count($smoutputs)+1)+10) * $feePerKb / 1024)
            );
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount = $amount - (intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change);
                    if ( $amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) ) {
                        throw new \InvalidArgumentException(
                            "The transaction amount is too small to send after the fee has been deducted."
                        );
                    }
                    $change = intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT));
                }
                break;
            } else {
                //no change
                break;
            }
        } while (true);

        $transactionSources = [];
        foreach ($outputsForSpent as $output) {
            $txSource = new \stdClass();
            $txSource->address = AddressFactory::fromString($output->getAddress());
            $txSource->privateKey = $this->dumpprivkey($walletName, $output->getAddress());
            if (!$txSource->privateKey) {
                throw new \RuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getTxOutputN();
            $txSource->amount = $output->getValue();
            $txSource->outpoint = new OutPoint(Buffer::hex($txSource->sourceTxId), $txSource->sourceVout);
            $txSource->transactionOutput = new TransactionOutput($txSource->amount, $txSource->outputScript);
            $transactionSources [] = clone $txSource;
        }

        $transaction = TransactionFactory::build();
        foreach ($transactionSources as $source) {
            $transaction = $transaction->spendOutPoint($source->outpoint);
        }
        foreach ($smoutputs as $sendmanyoutput) {
            $transaction = $transaction->payToAddress(
                $sendmanyoutput->getAmount(),
                AddressFactory::fromString($sendmanyoutput->getAddress())
            );
        }
        if ($change > $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = ("" != $sendMoneyOptions)
                ? $sendMoneyOptions->getAddressForChange()
                : $outputsForSpent[0]->getAddress();
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, AddressFactory::fromString($addressForChange));
        }
        /** @noinspection PhpUndefinedMethodInspection */
        $transaction = $transaction->get();

        $ec = Bitcoin::getEcAdapter();
        $signer = new Signer($transaction, $ec);
        for ($i = 0, $ic = count($transactionSources); $i < $ic; ++$i) {
            $signer->sign($i, $transactionSources[$i]->privateKey, $transactionSources[$i]->transactionOutput);
        }
        $signedTransaction = $signer->get();
        $raw = $signedTransaction->getHex();
        return $this->sendrawtransaction($raw);
    }
}
