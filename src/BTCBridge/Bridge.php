<?php

namespace BTCBridge;

use BTCBridge\Handler\AbstractHandler;
use BTCBridge\ConflictHandler\ConflictHandlerInterface;
use BTCBridge\Exception\HandlerErrorException;
use BTCBridge\ConflictHandler\DefaultConflictHandler;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;
use BTCBridge\Api\Wallet;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Address\AddressFactory;
use BitWasp\Bitcoin\Exceptions\Base58ChecksumFailure;

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
     * The conflictHandler
     *
     * @var ConflictHandlerInterface
     */
    protected $loggerHandler;

    /**
     * @param AbstractHandler[] $handlers         Stack of handlers for calling BTC-methods, $handlers must not be empty
     * @param ConflictHandlerInterface $conflictHandler  Methods of this objects will be raised for validating results.
     * Parameter is optional, by default DefaultConflictHandler instance will be used
     * @param LoggerInterface $loggerHandler    Methods of this objects will be raised for validating results.
     * Parameter is optional, by default DefaultConflictHandler instance will be used
     *
     * @throws \InvalidArgumentException if the provided argument $handlers is empty
     * @throws \RuntimeException if the provided argument $conflictHandler is not instance of HandlerInterface
     */
    public function __construct(
        array $handlers,
        ConflictHandlerInterface $conflictHandler = null,
        LoggerInterface $loggerHandler = null
    ) {
        if ([] == $handlers) {
            throw new \InvalidArgumentException("Handlers array can not be empty.");
        }
        foreach ($handlers as $handler) {
            if (!$handler instanceof AbstractHandler) {
                throw new \RuntimeException("The given handler is not a AbstractHandler");
            }
        }

        $this->handlers = $handlers;
        $this->conflictHandler = (null !== $conflictHandler) ? $conflictHandler : new DefaultConflictHandler();
        if ($loggerHandler) {
            $this->loggerHandler = $loggerHandler;
            foreach ($this->handlers as $handler) {
                $handler->setLogger($loggerHandler);
            }
        } else {
            $this->loggerHandler = new Logger('BTCBridge');
        }
        $this->setOption(self::OPT_LOCAL_PATH_OF_WALLET_DATA, __DIR__."/wallet.dat");
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
        if ((gettype($optionName) != "integer") || (!in_array($optionName, [self::OPT_LOCAL_PATH_OF_WALLET_DATA]))) {
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
        if ((gettype($optionName) != "integer") || (!in_array($optionName, [self::OPT_LOCAL_PATH_OF_WALLET_DATA]))) {
            throw new \InvalidArgumentException("Bad type of option (".$optionName.")");
        }
        if (!isset($this->options[$optionName])) {
            throw new \RuntimeException("No option with name \"" . $optionName . "\" exists in the class)");
        }
        return $this->options[$optionName];
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
     * @throws \RuntimeException in case of any error of this type
     * @throws \InvalidArgumentException in case of any error of this type
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
        return $this->conflictHandler->listtransactions($results);
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
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
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
        return $this->conflictHandler->gettransaction($results);
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
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
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
        return $this->conflictHandler->getbalance($results);
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
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
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
        return $this->conflictHandler->getunconfirmedbalance($results);
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
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
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
        return $this->conflictHandler->listunspent($results);
    }

    /**
     * The sendrawtransaction RPC validates a transaction and broadcasts it to the peer-to-peer network.
     * @link https://bitcoin.org/en/developer-reference#sendrawtransaction Official bitcoin documentation.
     *
     * @param string $Transaction  The minimum number of confirmations the transaction containing an output
     * must have in order to be returned.
     *
     * @return string If the transaction was accepted by the node for broadcast, this will be the TXID
     * of the transaction encoded as hex in RPC byte order.
     *
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
     *
     */
    public function sendrawtransaction($Transaction)
    {
        if ("string" != gettype($Transaction) || ("" == $Transaction)) {
            throw new \InvalidArgumentException("Transaction variable must be non empty string.");
        }
        $result = null;
        for ($i = 0; $i < count($this->handlers); ++$i) {
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
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     * @throws HandlerErrorException in case of any error with Handler occured
     *
     */
    public function createwallet($walletName, $addresses)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
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
        $results = [];

        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandlers AbstractHandler[] */
        $errorHandlers = [];

        for ($i = 0; $i < count($this->handlers); ++$i) {
            try {
                $result = $this->handlers[$i]->createwallet($walletName, $addresses);
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandlers [] = $this->handlers[$i];
                continue;
            }
            $successHandlers [] = $this->handlers[$i];
            $results [] = $result;
        }

        if ([] != $errorHandlers) {
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandlers,
                "Some handler(s) raised error (method createwallet)"
            );
        }
        return $this->conflictHandler->createwallet($results);
    }

    /**
     * This Method adds new addresses into a wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#add-addresses-to-wallet-endpoint
     *
     * @param string $walletName Name of wallet
     * @param string[] $addresses
     *
     * @return Wallet
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     * @throws HandlerErrorException in case of any error with Handler occured
     *
     */
    public function addaddresses($walletName, $addresses)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if ((!is_array($addresses)) || (count($addresses) == 0)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array.");
        }

        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandlers AbstractHandler[] */
        $errorHandlers = [];
        $results = [];

        for ($i = 0; $i < count($this->handlers); ++$i) {
            try {
                $result = $this->handlers[$i]->addaddresses($walletName, $addresses);
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandlers [] = $this->handlers[$i];
                continue;
            }
            $successHandlers [] = $this->handlers[$i];
            $results [] = $result;
        }

        if ([] != $errorHandlers) {
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandlers,
                "Some handler(s) raised error (method addaddresses)"
            );
        }
        return $this->conflictHandler->addaddresses($results);
    }

    /**
     * This Method adds new addresses into a wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#remove-addresses-from-wallet-endpoint
     *
     * @param string $walletName Name of wallet
     * @param string $address
     *
     * @return Wallet object
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     * @throws HandlerErrorException in case of any error with Handler occured
     *
     */
    public function removeAddress($walletName, $address)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }

        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandlers AbstractHandler[] */
        $errorHandlers = [];
        $results = [];

        for ($i = 0; $i < count($this->handlers); ++$i) {
            try {
                $result = $this->handlers[$i]->removeaddress($walletName, $address);
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandlers [] = $this->handlers[$i];
                continue;
            }
            $successHandlers [] = $this->handlers[$i];
            $results [] = $result;
        }

        if ([] != $errorHandlers) {
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandlers,
                "Some handler(s) raised error (method removeaddress)"
            );
        }
        return $this->conflictHandler->removeaddress($results);
    }


    /**
     * This Method deletes a passed wallet
     * https://www.blockcypher.com/dev/bitcoin/?shell#delete-wallet-endpoint
     *
     * @param string $walletName Name of wallet
     *
     * @return boolean result
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     * @throws HandlerErrorException in case of any error with Handler occured
     *
     */
    public function deletewallet($walletName)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Wallet name variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }

        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandlers AbstractHandler[] */
        $errorHandlers = [];

        for ($i = 0; $i < count($this->handlers); ++$i) {
            try {
                $this->handlers[$i]->deletewallet($walletName);
            } catch (\RuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandlers [] = $this->handlers[$i];
                continue;
            }
            $successHandlers [] = $this->handlers[$i];
        }
        if ([] != $errorHandlers) {
            throw new HandlerErrorException(
                $successHandlers,
                $errorHandlers,
                "Some handler(s) raised error (method deleteWallet)"
            );
        }
        return true;
    }

    /**
     * This method returns addresses from the passed wallet
     * @link https://bitcoin.org/en/developer-reference#getaddressesbyaccount
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#get-wallet-addresses-endpoint
     *
     * @param string $walletName
     *
     * @throws \RuntimeException in case of any error of this type
     * @throws \InvalidArgumentException in case of any error of this type
     *
     * @return \string[] addesses
     */
    public function getAddresses($walletName)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->getAddresses($walletName);
            $results [] = $result;
        }
        return $this->conflictHandler->getAddresses($results);
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
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("wallet Name must be non empty string.");
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
}
