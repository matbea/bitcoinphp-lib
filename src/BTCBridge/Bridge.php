<?php

namespace BTCBridge;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use BTCBridge\Api\TransactionReference;
use BTCBridge\Api\BTCValue;
use BTCBridge\Handler\AbstractHandler;
use BTCBridge\ResultHandler\AbstractResultHandler;
use BTCBridge\ConflictHandler\ConflictHandlerInterface;
use BTCBridge\Exception\ResultHandlerException;
use BTCBridge\Exception\HandlerErrorException;
use BTCBridge\Exception\BEInvalidArgumentException;
use BTCBridge\Exception\BERuntimeException;
use BTCBridge\Exception\BELogicException;
use BTCBridge\ConflictHandler\DefaultConflictHandler;
use BTCBridge\ResultHandler\DefaultResultHandler;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;
use BTCBridge\Api\Wallet;
use BTCBridge\Api\SMOutput;
use BTCBridge\Api\SendMoneyOptions;
use BTCBridge\Api\ListTransactionsOptions;
use BTCBridge\Api\WalletActionOptions;
use BTCBridge\Api\CurrencyTypeEnum;
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
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkFactory;

/**
 * Describes a Bridge instance
 *
 * Bridge takes commands from the users and retranslate them to different bitcoin services.
 *
 */
class Bridge
{
    /** This group constants describe time points of measurement */
    const PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS        = 0;
    const PRIV_TIME_MEASUREMENT_AFTER_HANDLERS         = 1;
    const PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER = 2;
    const PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER   = 3;
    const PRIV_TIME_MEASUREMENT_METHOD_NAME            = 4;

    /** This group of constants are bridge options */
    const OPT_LOCAL_PATH_OF_WALLET_DATA = 1;
    const OPT_MINIMAL_AMOUNT_FOR_SENT   = 2;
    const OPT_MINIMAL_FEE_PER_KB        = 3;

    /** This group of constants are bridge options for restrictions for methods */
    const MAX_COUNT_OF_TRANSACTIONS_FOR__GETTRANSACTIONS__METHOD = 20;

    /** @var CurrencyTypeEnum currency */
    protected $currency;

    /** @var Network network */
    protected $network;

    /** @var array options */
    protected $options = [];

    /** @var array timeMeasurementStatistics */
    protected $timeMeasurementStatistics = [];

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
     * @param CurrencyTypeEnum $currency Name of currency
     * @param AbstractHandler[] $handlers Stack of handlers for calling BTC-methods, $handlers must not be empty
     * @param ConflictHandlerInterface $conflictHandler Methods of this objects will be raised for validating results.
     * Parameter is optional, by default DefaultConflictHandler instance will be used
     * @param AbstractResultHandler $resultHandler Methods of this objects will be raised for processing results
     * @param LoggerInterface $loggerHandler Methods of this objects will be raised for validating results
     * Parameter is optional, by default DefaultConflictHandler instance will be used
     *
     * @throws BEInvalidArgumentException if the provided argument $handlers is empty
     * @throws BELogicException if the provided argument $conflictHandler is not instance of HandlerInterface
     * @throws BERuntimeException
     */
    public function __construct(
        CurrencyTypeEnum $currency,
        array $handlers,
        ConflictHandlerInterface $conflictHandler = null,
        AbstractResultHandler $resultHandler = null,
        LoggerInterface $loggerHandler = null
    ) {
        if (empty($handlers)) {
            throw new BEInvalidArgumentException("Handlers array can not be empty.");
        }
        foreach ($handlers as $handler) {
            if (!$handler instanceof AbstractHandler) {
                throw new BEInvalidArgumentException("The given handler is not a AbstractHandler");
            }
            if ((string)$currency != (string)$handler->getCurrency()) {
                throw new BELogicException("Handler contains different currency than bridge instance");
            }
        }

        try {
            $this->currency = $currency;
            if (CurrencyTypeEnum::BTC == $this->currency) {
                $this->network = NetworkFactory::bitcoin();
            } elseif (CurrencyTypeEnum::TBTC == $this->currency) {
                $this->network = NetworkFactory::bitcoinTestnet();
            }
        } catch (\Exception $ex) {
            throw new BERuntimeException($ex->getMessage());
        }

        $this->handlers = $handlers;
        $this->conflictHandler = (null !== $conflictHandler) ? $conflictHandler : new DefaultConflictHandler();
        $this->resultHandler = (null !== $resultHandler) ? $resultHandler : new DefaultResultHandler();
        $this->resultHandler->setHandlers($handlers);

        if ($loggerHandler) {
            $this->loggerHandler = $loggerHandler;
        } else {
            $this->loggerHandler = new Logger('BTCBridge');
        }
        foreach ($this->handlers as $handler) {
            $handler->setLogger($this->loggerHandler);
        }

        $this->setOption(self::OPT_LOCAL_PATH_OF_WALLET_DATA, __DIR__."/wallet.dat");
        $this->setOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT, "5500");
        $this->setOption(self::OPT_MINIMAL_FEE_PER_KB, "10000");

        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS]        = null;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]         = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = null;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER]   = null;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME]            = null;
    }

    /**
     * Sets the option
     *
     * @param int $optionName a constant describying name of the option
     * @param string $optionValue a value of the option
     *
     * @throws BEInvalidArgumentException if error of this type
     *
     */
    public function setOption($optionName, $optionValue)
    {
        if (!is_int($optionName) ||
            (!in_array(
                $optionName,
                [
                    self::OPT_LOCAL_PATH_OF_WALLET_DATA,
                    self::OPT_MINIMAL_AMOUNT_FOR_SENT,
                    self::OPT_MINIMAL_FEE_PER_KB
                ]
            ))) {
            throw new BEInvalidArgumentException("Bad type of option (".$optionName.")");
        }
        if ((!is_string($optionValue)) || (empty($optionValue))) {
            $msg = "Bad type (" . gettype($optionValue) . ") of option value (must be non empty string)";
            throw new BEInvalidArgumentException($msg);
        }
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Gets the option
     *
     * @param int $optionName a constant, which describes the name of the option
     *
     * @throws BEInvalidArgumentException if error of this type
     * @throws BERuntimeException in case if this option is not exists
     *
     * @return string Option
     */
    protected function getOption($optionName)
    {
        if (!is_int($optionName) ||
            (!in_array(
                $optionName,
                [
                    self::OPT_LOCAL_PATH_OF_WALLET_DATA,
                    self::OPT_MINIMAL_AMOUNT_FOR_SENT,
                    self::OPT_MINIMAL_FEE_PER_KB
                ]
            ))) {
            throw new BEInvalidArgumentException("Bad type of option (".$optionName.")");
        }
        if (!isset($this->options[$optionName])) {
            $msg = "No option with name \"" . $optionName . "\" exists in the class)";
            throw new BERuntimeException($msg);
        }
        return $this->options[$optionName];
    }

    /** Returns time measurement statistics
     *
     * @return array
     */
    public function getTimeMeasurementStatistics()
    {
        return $this->timeMeasurementStatistics;
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
     * @throws BERuntimeException
     */
    private function binarySearch(array $outputs, $first, $last, $searchedValue)
    {
        if ($outputs[$last - 1]->getValue()->getSatoshiValue() < $searchedValue) {
            return -1;
        }
        $count = $last - $first;
        while ($count > 0) {
            $it = $first;
            $step = intval(floor($count / 2));
            $it += $step;
            if ($outputs[$it]->getValue()->getSatoshiValue() < $searchedValue) {
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
     * @throws BEInvalidArgumentException case of any error of this type
     *
     * @return TransactionReference[] If not enouth BTC on passed outputs then ampty array will be returned
     * @throws BERuntimeException
     */
    public function selectOutputsForSpent($outputs, $amount)
    {
        if ((!is_int($amount)) || ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)))) {
            throw new BEInvalidArgumentException(
                "amount variable must be integer bigger or equal " .
                $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT) . "."
            );
        }
        if (!is_array($outputs) || empty($outputs)) {
            throw new BEInvalidArgumentException(
                "outputs variable must be non empty array of TransactionReference type."
            );
        }
        /** @var $outputs TransactionReference[] */

        //Now we'll check sufficiency of total balance of passed subset of outputs -
        //and will try to find the output with the same value as needed
        $sum = 0;
        for ($i = 0, $ic = count($outputs); $i < $ic; ++$i) {
            if ($outputs[$i]->getValue()->getSatoshiValue() == $amount) {
                return [$outputs[$i]];
            }
            $sum += $outputs[$i]->getValue()->getSatoshiValue();
        }
        if ($sum < $amount) {
            return []; //Not enough BTC
        }

        //We'll sort $outputs array in ascending order
        usort(
            $outputs,
            function (TransactionReference $a, TransactionReference $b) {
                if (0 != gmp_cmp($a->getValue()->getGMPValue(), $b->getValue()->getGMPValue())) {
                    return 0;
                }
                return ($a->getValue()->getSatoshiValue() < $b->getValue()->getSatoshiValue()) ? -1 : 1;
            }
        );

        //No output with the value which is equal to the necessary, so will approximate

        /** @var $result TransactionReference[] */
        $result = [];

        while (true) {
            //Firstly we'll try to find 1 output which has enough money
            $outputIndex = $this->binarySearch($outputs, 0, count($outputs), $amount);
            if (-1 != $outputIndex) {
                $result [] = $outputs[$outputIndex];
                return $result;
            }
            $amountOfBigOutput = $outputs[count($outputs) - 1]->getValue()->getSatoshiValue();
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
     *
     * @param string $walletName  A wallet name (or address) to get transactions from
     * @param ListTransactionsOptions $options contains the optional params
     *
     * @throws BERuntimeException in case of any runtime error
     * @throws BEInvalidArgumentException in case of error of this type
     *
     * @return Address
     */
    public function listtransactions($walletName, ListTransactionsOptions $options = null)
    {
        if ((!is_string($walletName)) || empty($walletName)) {
            throw new BEInvalidArgumentException("address variable must be non empty string.");
        }
        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);
        $results = [];
        foreach ($this->handlers as $handle_num => $handle) {
            $result = $handle->listtransactions($walletName, $options);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]
                [$handle_num] = microtime(true);
                $results [] = $result;
            }
        }
        $this->conflictHandler->listtransactions($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->listtransactions($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * The gettransactions RPC gets detailed information about an in-wallet transaction.
     *
     * @param string[] $txHashes transaction identifiers
     *
     * @throws BERuntimeException in case of any runtime error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return Transaction[]
     */
    public function gettransactions(array $txHashes)
    {
        if (empty($txHashes)) {
            throw new BEInvalidArgumentException("\$txHashes variable must be non empty array of valid btc tx-hashes.");
        }
        if (count($txHashes) > Bridge::MAX_COUNT_OF_TRANSACTIONS_FOR__GETTRANSACTIONS__METHOD) {
            throw new BEInvalidArgumentException(
                "txHashes variable size must be non bigger than " .
                Bridge::MAX_COUNT_OF_TRANSACTIONS_FOR__GETTRANSACTIONS__METHOD . "."
            );
        }
        foreach ($txHashes as $txHash) {
            if ((!is_string($txHash)) || !preg_match('/^[a-z0-9]+$/i', $txHash) || (strlen($txHash) != 64)) {
                throw new BEInvalidArgumentException(
                    "Hashes in \$txHashes must be valid bitcoin transaction hashes (\"" . $txHash . "\" not valid)."
                );
            }
        }

        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);
        $results = [];
        foreach ($this->handlers as $handle_num => $handle) {
            $result = $handle->gettransactions($txHashes);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]
                [$handle_num] = microtime(true);
                $results [] = $result;
            }
        }
        $this->conflictHandler->gettransactions($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->gettransactions($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }


    /**
     * The getbalance RPC gets the balance in decimal bitcoins across all accounts or for a particular account.
     * The Address Balance Endpoint is the simplest—and fastest—method
     * to get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getbalance Official bitcoin documentation.
     *
     * @param string $walletName A wallet name to get balance from
     * @param int $Confirmations The minimum number of confirmations an externally-generated transaction
     * must have before it is counted towards the balance.
     *
     * @throws BERuntimeException in case of any runtime error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return BTCValue The balance
     * @throws BELogicException
     */
    public function getbalance($walletName, $Confirmations = 1)
    {
        if ((!is_string($walletName)) || empty($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if ((!is_int($Confirmations)) || ($Confirmations < 0)) {
            throw new BEInvalidArgumentException(
                "Confirmations variable must ne non negative integer, (" .
                $Confirmations . " passed)."
            );
        }
        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);
        $results = [];
        foreach ($this->handlers as $handle_num => $handle) {
            $result = $handle->getbalance($walletName, $Confirmations);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]
                [$handle_num] = microtime(true);
                $results [] = $result;
            }
        }
        $this->conflictHandler->getbalance($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->getbalance($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * Returns the wallet’s total unconfirmed balance.
     * The Address Balance Endpoint is the simplest—and fastest—method
     * to get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
     *
     * @param string $walletName A wallet name to get unconfirmed balance from
     *
     * @throws BERuntimeException in case of any runtime error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return BTCValue The unconfirmed balance
     */
    public function getunconfirmedbalance($walletName)
    {
        if ((!is_string($walletName)) || empty($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);
        $results = [];
        foreach ($this->handlers as $handle_num => $handle) {
            //$result = call_user_func_array([$handle, "getunconfirmedbalance"], [$walletName]);
            $result = $handle->getunconfirmedbalance($walletName);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]
                [$handle_num] = microtime(true);
                $results [] = $result;
            }
        }
        $this->conflictHandler->getunconfirmedbalance($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->getunconfirmedbalance($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * Returns an array of unspent transaction outputs belonging to this wallet.
     * The Address Balance Endpoint is the simplest—and fastest—method to
     * get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#listunspent Official bitcoin documentation.
     *
     * @param string $walletName A wallet name to get unconfirmed balance from
     * @param int $MinimumConfirmations  The minimum number of confirmations the transaction containing an output
     * must have in order to be returned.
     * If $MinimumConfirmations = 0, then only unconfirmed transactions will be returned.
     *
     * @throws BERuntimeException in case of any runtime error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return array The list of unspent outputs
     */
    public function listunspent($walletName, $MinimumConfirmations = 1)
    {
        if ((!is_string($walletName)) || empty($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (!is_int($MinimumConfirmations) || $MinimumConfirmations < 0) {
            throw new BEInvalidArgumentException(
                "\$MinumumConfirmations variable must be nonnegative integerymbols ( " .
                $MinimumConfirmations . " passed)."
            );
        }
        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);
        $results = [];
        foreach ($this->handlers as $handle_num => $handle) {
            $result = $handle->listunspent($walletName, $MinimumConfirmations);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]
                [$handle_num] = microtime(true);
                $results [] = $result;
            }
        }
        $this->conflictHandler->listunspent($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->listunspent($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * The sendrawtransaction RPC validates a transaction and broadcasts it to the peer-to-peer network.
     * @link https://bitcoin.org/en/developer-reference#sendrawtransaction Official bitcoin documentation.
     *
     * @param string $transaction
     *
     * @return string If the transaction was accepted by the node for broadcast, this will be the TXID
     * of the transaction encoded as hex in RPC byte order.
     *
     * @throws BERuntimeException in case of any runtime error
     * @throws BEInvalidArgumentException if error of this type
     *
     */
    public function sendrawtransaction($transaction)
    {
        if ((!is_string($transaction)) || empty($transaction)) {
            throw new BEInvalidArgumentException("Transaction variable must be non empty string.");
        }
        $result = null;
        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $result = $this->handlers[$i]->sendrawtransaction($transaction);
                if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD === $result) {
                    $result = null;
                    continue;
                }
            } catch (\Exception $ex) {
                $this->loggerHandler->addError($ex->getMessage());
                continue;
            }
            return $result;
        }
        throw new BERuntimeException(
            "Transaction \"" . $transaction . "\" was not sent (" . count($this->handlers) . " handlers)."
        );
    }

    /**
     * This Method allows you to create a new wallet, by POSTing a partially filled out Wallet or HDWallet object,
     * depending on the endpoint.
     *
     * @param string $walletName
     * @param string[] $addresses
     * @param WalletActionOptions $options
     *
     * @throws HandlerErrorException
     * @throws BEInvalidArgumentException if error of this type
     * @throws ResultHandlerException|\Exception
     * @return Wallet
     *
     */
    public function createWallet($walletName, array $addresses, WalletActionOptions $options = null)
    {
        if ((!is_string($walletName)) || empty($walletName)) {
            throw new BEInvalidArgumentException("Wallet name variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (count($addresses) > 0) {
            foreach ($addresses as $address) {
                if (!AddressFactory::isValidAddress($address, $this->network)) {
                    throw new BEInvalidArgumentException("No valid address (\"" . $address . "\" passed).");
                }
            }
        }

        /** @var $resultWallets Wallet[] */
        $resultWallets = [];
        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandler AbstractHandler */
        $errorHandler = null;
        /** @var $unusedHandlers AbstractHandler[] */
        $unusedHandlers = [];
        $errMsg = "";

        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $resultWallet = $this->handlers[$i]->createWallet($walletName, $addresses, $options);
            } catch (BERuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                $errMsg = $ex->getMessage();
                break;
            }
            $successHandlers [] = $this->handlers[$i];
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $resultWallet) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS][$i] = microtime(true);
                $resultWallets [] = $resultWallet;
            }
        }

        if ($errorHandler) {
            $resultWallet = null;
            if (!empty($resultWallets)) {
                $this->resultHandler->setHandlers($successHandlers);
                try {
                    $resultWallet = $this->resultHandler->createWallet($resultWallets);
                } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ResultHandlerException $ex) {
                    $this->resultHandler->setHandlers($this->handlers);
                    throw $ex;
                }
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
                '"' . $errorHandler->getHandlerName() . '" handler raised error (method createWallet) ' . $errMsg . '.'
            );
        }
        $this->conflictHandler->createWallet($resultWallets); //In case of error throw will be raised
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->createWallet($resultWallets);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * This Method adds new addresses into a wallet
     *
     * @param Wallet $wallet Object to which addresses will be added
     * @param string[] $addresses
     * @param WalletActionOptions $options
     *
     * @throws BEInvalidArgumentException
     * @throws HandlerErrorException
     * @throws ResultHandlerException
     * @return Wallet
     *
     * @throws Base58ChecksumFailure
     */
    public function addAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null)
    {
        if (empty($addresses)) {
            throw new BEInvalidArgumentException("addresses variable must be non empty array.");
        }
        foreach ($addresses as $address) {
            if (!AddressFactory::isValidAddress($address, $this->network)) {
                throw new BEInvalidArgumentException("No valid address (\"" . $address . "\" passed).");
            }
        }

        /** @var $resultWallets Wallet[] */
        $resultWallets = [];
        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandler AbstractHandler */
        $errorHandler = null;
        /** @var $unusedHandlers AbstractHandler[] */
        $unusedHandlers = [];

        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $resultWallet = $this->handlers[$i]->addAddresses($wallet, $addresses, $options);
            } catch (BERuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                break;
            }
            $successHandlers [] = $this->handlers[$i];
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $resultWallet) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS][$i] = microtime(true);
                $resultWallets [] = $resultWallet;
            }
        }

        if ($errorHandler) {
            $resultWallet = null;
            if (!empty($resultWallets)) {
                $this->resultHandler->setHandlers($successHandlers);
                try {
                    $resultWallet = $this->resultHandler->addAddresses($resultWallets);
                } /** @noinspection PhpRedundantCatchClauseInspection */ catch (ResultHandlerException $ex) {
                    $this->resultHandler->setHandlers($this->handlers);
                    throw $ex;
                }
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
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->addAddresses($resultWallets);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * This Method adds new addresses into a wallet
     *
     * @param Wallet $wallet
     * @param string[] $addresses
     * @param WalletActionOptions $options
     *
     * @throws BEInvalidArgumentException
     * @throws HandlerErrorException
     * @throws ResultHandlerException
     * @return Wallet
     *
     * @throws Base58ChecksumFailure
     */
    public function removeAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null)
    {
        if (empty($addresses)) {
            throw new BEInvalidArgumentException("addresses variable must be non empty array of strings.");
        }
        //TODO - catch * @throws Base58ChecksumFailure in try ... isValidAddress .. catch
        foreach ($addresses as $address) {
            if (!AddressFactory::isValidAddress($address, $this->network)) {
                throw new BEInvalidArgumentException("No valid address (\"" . $address . "\" passed).");
            }
        }

        /** @var $resultWallets Wallet[] */
        $resultWallets = [];
        /** @var $successHandlers AbstractHandler[] */
        $successHandlers = [];
        /** @var $errorHandler AbstractHandler */
        $errorHandler = null;
        /** @var $unusedHandlers AbstractHandler[] */
        $unusedHandlers = [];

        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $resultWallet = $this->handlers[$i]->removeAddresses($wallet, $addresses, $options);
            } catch (BERuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                break;
            }
            $successHandlers [] = $this->handlers[$i];
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $resultWallet) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS][$i] = microtime(true);
                $resultWallets [] = $resultWallet;
            }
        }

        if ($errorHandler) {
            $resultWallet = null;
            if (!empty($resultWallets)) {
                $this->resultHandler->setHandlers($successHandlers);
                try {
                    $resultWallet = $this->resultHandler->removeAddresses($resultWallets);
                } /** @noinspection PhpRedundantCatchClauseInspection */ /** @noinspection PhpRedundantCatchClauseInspection */ catch (ResultHandlerException $ex) {
                    $this->resultHandler->setHandlers($this->handlers);
                    throw $ex;
                }
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
                '"' . $errorHandler->getHandlerName() . '" handler raised error (method removeAddresses).'
            );
        }
        $this->conflictHandler->removeAddresses($resultWallets);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->removeAddresses($resultWallets);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }


    /**
     * This Method deletes a passed wallet
     *
     * @param Wallet $wallet wallet for deleting
     *
     * @throws HandlerErrorException
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

        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);

        for ($i = 0, $ic = count($this->handlers); $i < $ic; ++$i) {
            try {
                $this->handlers[$i]->deleteWallet($wallet);
            } catch (BERuntimeException $ex) {
                $this->loggerHandler->error($ex->getMessage());
                $errorHandler = $this->handlers[$i];
                break;
            }
            $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS][$i] = microtime(true);
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
                null, /*This method does not return wallet, so we can pass null only*/
                '"' . $errorHandler->getHandlerName() . '" handler raised error (method deleteWallet).'
            );
        }
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
    }

    /**
     * This method returns addresses from the passed wallet
     * @link https://bitcoin.org/en/developer-reference#getaddressesbyaccount
     *
     * @param Wallet $wallet
     *
     * @throws BERuntimeException in case of any runtime error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return \string[] addresses
     */
    public function getAddresses(Wallet $wallet)
    {
        $results = [];

        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);

        foreach ($this->handlers as $handle_num => $handle) {
            $result = $handle->getAddresses($wallet);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]
                [$handle_num] = microtime(true);
                $results [] = $result;
            }
        }
        $this->conflictHandler->getAddresses($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->getAddresses($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * This method returns wallets  and addresses optionally by token
     *
     * @param WalletActionOptions $options
     *
     * @throws BERuntimeException in case of any error of this type
     * @throws BEInvalidArgumentException in case of any error of this type
     *
     * @return Wallet[] wallets
     */
    public function getWallets(WalletActionOptions $options = null)
    {
        $results = [];

        $this->timeMeasurementStatistics = [];
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_METHOD_NAME] = __METHOD__;
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_BEFORE_HANDLERS] = microtime(true);

        foreach ($this->handlers as $handle_num => $handle) {
            $result = $handle->getWallets($options);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_HANDLERS]
                [$handle_num] = microtime(true);
                $results [] = $result;
            }
        }
        $this->conflictHandler->getWallets($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_CONFLICT_HANDLER] = microtime(true);
        $ret = $this->resultHandler->getWallets($results);
        $this->timeMeasurementStatistics[Bridge::PRIV_TIME_MEASUREMENT_AFTER_RESULT_HANDLER] = microtime(true);
        return $ret;
    }

    /**
     * The getnewaddress RPC returns a new Bitcoin address for receiving payments.
     * @link https://bitcoin.org/en/developer-reference#getnewaddress
     *
     * @throws BERuntimeException in case of error of this type
     *
     * @throws BEInvalidArgumentException
     *
     * @return \string
     */
    public function getnewaddress()
    {
        try {
            $privateKey = PrivateKeyFactory::create();
            $address = $privateKey->getPublicKey()->getAddress();
            $address = $address->getAddress();
            $network = Bitcoin::getNetwork();
            $wif = $privateKey->toWif($network);
        } catch (\Exception $ex) {
            throw new BERuntimeException($ex->getMessage());
        } //May be \RuntimeException will raised in the BitWASP library - we'll not change this
        if (!file_put_contents(
            $this->getOption(Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA),
            ($wif.";".$address.PHP_EOL),
            FILE_APPEND
        ) ) {
            throw new BERuntimeException(
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
     * @param string $address
     *
     * @throws BERuntimeException in case of error of this type
     * @throws BEInvalidArgumentException in case of error of this type
     * @internal param string $walletName Name of wallet
     * @return \string|null WIF or null (if didn't found)
     *
     */
    public function dumpprivkey($address)
    {
        if ((!is_string($address)) || empty($address)) {
            throw new BEInvalidArgumentException("address variable must be non empty string.");
        }

        $path = $this->getOption(Bridge::OPT_LOCAL_PATH_OF_WALLET_DATA);

        $handle = fopen($path, "r");
        if (false === $handle) {
            throw new BERuntimeException("Read data from the file " . $path . " failed.");
        }
        $i = 0;
        while (($data = fgetcsv($handle, 1000, ";")) !== false) {
            ++$i;
            $num = count($data);
            if (2 != $num) {
                throw new BERuntimeException(
                    "Line #" . $i . " in the file " . $path . " contains " .
                    $num . " fields, must contain 3 fields only."
                );
            }
            if ($address == $data[1]) {
                fclose($handle);
                return $data[0];
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
     * @throws BERuntimeException in case of any error
     * @throws BEInvalidArgumentException if error of this type
     *
     */
    public function settxfee($fee)
    {
        if ((!is_int($fee)) || ($fee <= intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB)))) {
            throw new BEInvalidArgumentException(
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
     * @param string $walletName The wallet, which is source for money
     * @param string $address The address to which the bitcoins should be sent
     * @param integer $amount The amount to spend in satoshis.
     * @param int $confirmations The minimum number of confirmations the transaction containing an output
     * @param string $comment A locally-stored (not broadcast) comment assigned to this
     * transaction. Default is no comment
     * must have in order to be returned.
     * @param string $commentTo A locally-stored (not broadcast) comment assigned to this transaction.
     * Meant to be used for describing who the payment was sent to. Default is no comment.
     *
     * @return string $transactionId
     *
     * @throws BEInvalidArgumentException if error of this type
     * @throws BERuntimeException in case of any runtime error
     * @throws Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @throws \Exception
     */
    public function sendfrom($walletName, $address, $amount, $confirmations = 1, $comment = "", $commentTo = "")
    {
        if ((!is_string($walletName)) || empty($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if ((!is_string($address)) || empty($address)) {
            throw new BEInvalidArgumentException("address variable must be non empty string.");
        }
        if ((!is_int($amount)) || ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
            throw new BEInvalidArgumentException(
                "amount variable must be integer bigger or equal " . self::OPT_MINIMAL_AMOUNT_FOR_SENT . "."
            );
        }
        if ((!is_int($confirmations)) || ($confirmations < 0)) {
            throw new BEInvalidArgumentException("confirmation variable must be non negative integer.");
        }

        if (!is_string($comment)) {
            throw new BEInvalidArgumentException("comment variable must be a string variable.");
        }
        if (!is_string($commentTo)) {
            throw new BEInvalidArgumentException("commentTo variable must be a string variable.");
        }

        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $confirmations);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $results [] = $result;
            }
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
        $mimimumRequiredFee = intval(ceil((1 * 181 + 2 * 34 + 10) * $feePerKb / 1024));
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
                $sumFromOutputs += $outputsForSpent[$i]->getValue()->getSatoshiValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(ceil((count($outputsForSpent) * 181 + 34 * 2 + 10) * $feePerKb / 1024));
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount -= ( intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change );
                    if ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                        throw new BEInvalidArgumentException(
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
            //throw new BERuntimeException(
                //"Logic error (\$amount (" . $amount . ") less than \$sumAmount" . $sumAmount . ")."
            //);
        } while (true);
        //$sumAmount;
        //$requiredFee;

        $transactionSources = [];
        foreach ($outputsForSpent as $output) {
            $txSource = new \stdClass();
            $txSource->address = AddressFactory::fromString($output->getAddress(), $this->network);
            $txSource->privateKey = $this->dumpprivkey($output->getAddress());
            if (!$txSource->privateKey) {
                throw new BERuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getVout();
            $txSource->amount = $output->getValue()->getSatoshiValue();
            $txSource->outpoint = new OutPoint(Buffer::hex($txSource->sourceTxId), $txSource->sourceVout);
            $txSource->transactionOutput = new TransactionOutput($txSource->amount, $txSource->outputScript);
            $transactionSources [] = clone $txSource;
        }

        $transaction = TransactionFactory::build();
        foreach ($transactionSources as $source) {
            $transaction = $transaction->spendOutPoint($source->outpoint);
        }
        $transaction = $transaction->payToAddress($amount, AddressFactory::fromString($address, $this->network));
        if ($change >= $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = $outputsForSpent[0]->getAddress();
            $objAddressForChange = AddressFactory::fromString($addressForChange, $this->network);
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, $objAddressForChange);
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
     * @param string $walletName The wallet, which is source for money
     * @param string $address The address to which the bitcoins should be sent
     * @param integer $amount The amount to spend in satoshis.
     * @param SendMoneyOptions $sendMoneyOptions (comment,confirmations,commentTo etc)
     *
     * @return string $transactionId
     *
     * @throws BEInvalidArgumentException if error of this type
     * @throws BERuntimeException in case of any runtime error
     * @throws Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @throws \Exception
     */
    public function sendfromEX($walletName, $address, $amount, SendMoneyOptions $sendMoneyOptions)
    {
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException("Wallet name variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if ((!is_string($address)) || empty($address)) {
            throw new BEInvalidArgumentException("address variable must be non empty string.");
        }
        if ((!is_int($amount)) || ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
            throw new BEInvalidArgumentException(
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
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $results [] = $result;
            }
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
        //Ideal case - one input for spend, two outputs - one is for destination addreses, one - for change
        $mimimumRequiredFee = intval(ceil((1 * 181 + 2 * 34 + 10) * $feePerKb / 1024));
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
                $sumFromOutputs += $outputsForSpent[$i]->getValue()->getSatoshiValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(ceil((count($outputsForSpent) * 181 + 34 * 2 + 10) * $feePerKb / 1024));
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount -= ( intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change );
                    if ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                        throw new BEInvalidArgumentException(
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
            $txSource->address = AddressFactory::fromString($output->getAddress(), $this->network);
            $txSource->privateKey = $this->dumpprivkey($output->getAddress());
            if (!$txSource->privateKey) {
                throw new BERuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getVout();
            $txSource->amount = $output->getValue()->getSatoshiValue();
            $txSource->outpoint = new OutPoint(Buffer::hex($txSource->sourceTxId), $txSource->sourceVout);
            $txSource->transactionOutput = new TransactionOutput($txSource->amount, $txSource->outputScript);
            $transactionSources [] = clone $txSource;
        }

        $transaction = TransactionFactory::build();
        foreach ($transactionSources as $source) {
            $transaction = $transaction->spendOutPoint($source->outpoint);
        }
        $transaction = $transaction->payToAddress($amount, AddressFactory::fromString($address, $this->network));
        if ($change >= $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = null;
            if (!empty($sendMoneyOptions)) {
                $addressForChange = $sendMoneyOptions->getAddressForChange();
            } else {
                $addressForChange = $outputsForSpent[0]->getAddress();
            }
            $objAddressForChange = AddressFactory::fromString($addressForChange, $this->network);
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, $objAddressForChange);
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
     * @param string $walletName The wallet, which is source for money
     * @param SMOutput[] $smoutputs Object containing key/value pairs corresponding to the addresses and amounts to pay
     * @param int $confirmations The minimum number of confirmations the transaction containing an output
     * @param string $comment
     * A locally-stored (not broadcast) comment assigned to this transaction. Default is no comment
     *
     * @return string $transactionId
     *
     * @throws BEInvalidArgumentException if error of this type
     * @throws BERuntimeException in case of any runtime error
     * @throws Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @throws \Exception
     */
    public function sendmany($walletName, array $smoutputs, $confirmations = 1, $comment = "")
    {
        /** @var $smoutputs SMOutput[] */
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (empty($smoutputs)) {
            throw new BEInvalidArgumentException("\$smoutputs variable must be non empty string.");
        }
        $amount = 0;
        for ($i = 0, $ic = count($smoutputs); $i < $ic; ++$i) {
            if (!$smoutputs[$i] instanceof SMOutput) {
                throw new BEInvalidArgumentException(
                    "items of smoutputs variable must be instances of SMOutput class"
                );
            }
            $amount += $smoutputs[$i]->getAmount();
        }
        if ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            throw new BEInvalidArgumentException(
                "total amount from outputs must bigger or equal " . self::OPT_MINIMAL_AMOUNT_FOR_SENT . "."
            );
        }
        if ((!is_int($confirmations)) || ($confirmations < 0)) {
            throw new BEInvalidArgumentException("confirmation variable must be non negative integer.");
        }
        if (!is_string($comment)) {
            throw new BEInvalidArgumentException("comment variable must be a string variable.");
        }

        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $confirmations);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $results [] = $result;
            }
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        $mimimumRequiredFee = intval(ceil((1 * 181 + (count($smoutputs) + 1) * 34 + 10) * $feePerKb / 1024));
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
                $sumFromOutputs += $outputsForSpent[$i]->getValue()->getSatoshiValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(
                ceil((count($outputsForSpent) * 181 + 34 * (count($smoutputs) + 1) + 10) * $feePerKb / 1024)
            );
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount -= ( intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change );
                    if ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                        throw new BEInvalidArgumentException(
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
            $txSource->address = AddressFactory::fromString($output->getAddress(), $this->network);
            $txSource->privateKey = $this->dumpprivkey($output->getAddress());
            if (!$txSource->privateKey) {
                throw new BERuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getVout();
            $txSource->amount = $output->getValue()->getSatoshiValue();
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
                AddressFactory::fromString($sendmanyoutput->getAddress(), $this->network)
            );
        }
        if ($change > $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = $outputsForSpent[0]->getAddress();
            $objAddressForChange = AddressFactory::fromString($addressForChange, $this->network);
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, $objAddressForChange);
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
     * @param string $walletName The wallet, which is source for money
     * @param SMOutput[] $smoutputs Object containing key/value pairs corresponding to the addresses and amounts to pay
     * @param SendMoneyOptions $sendMoneyOptions (comment,confirmations,commentTo etc)
     *
     * @return string $transactionId
     *
     * @throws BEInvalidArgumentException if error of this type
     * @throws BERuntimeException in case of any runtime error
     * @throws Base58ChecksumFailure
     * @throws \BitWasp\Bitcoin\Exceptions\InvalidPrivateKey
     * @throws \Exception
     */
    public function sendmanyEX($walletName, array $smoutputs, SendMoneyOptions $sendMoneyOptions)
    {
        /** @var $smoutputs SMOutput[] */
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException("Wallet name variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (empty($smoutputs)) {
            throw new BEInvalidArgumentException("\$smoutputs variable must be non empty string.");
        }
        $amount = 0;
        for ($i = 0, $ic = count($smoutputs); $i < $ic; ++$i) {
            if (!$smoutputs[$i] instanceof SMOutput) {
                throw new BEInvalidArgumentException(
                    "items of smoutputs variable must be instances of SMOutput class"
                );
            }
            $amount += $smoutputs[$i]->getAmount();
        }
        if ($amount < intval(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            throw new BEInvalidArgumentException(
                "total amount from outputs must bigger or equal " . self::OPT_MINIMAL_AMOUNT_FOR_SENT . "."
            );
        }
        $confirmations = $sendMoneyOptions->getConfirmations();
        /** @noinspection PhpUnusedLocalVariableInspection */
        $comment = $sendMoneyOptions->getComment();

        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($walletName, $confirmations);
            if (AbstractHandler::HANDLER_UNSUPPORTED_METHOD !== $result) {
                $results [] = $result;
            }
        }
        $this->conflictHandler->listunspent($results);
        $unspents = $this->resultHandler->listunspent($results);

        $feePerKb = intval($this->getOption(self::OPT_MINIMAL_FEE_PER_KB));
        $mimimumRequiredFee = intval(ceil((1 * 181 + (count($smoutputs) + 1) * 34 + 10) * $feePerKb / 1024));
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
                $sumFromOutputs += $outputsForSpent[$i]->getValue()->getSatoshiValue();
            }
            //http://bitzuma.com/posts/making-sense-of-bitcoin-transaction-fees/     size = 181 * in + 34 * out + 10
            $requiredFeeWithChange = intval(
                ceil((count($outputsForSpent) * 181 + 34 * (count($smoutputs) + 1) + 10) * $feePerKb / 1024)
            );
            $change = $sumFromOutputs - $amount - $requiredFeeWithChange;
            if ($change < 0) {
                $requiredCoins = $amount + $requiredFeeWithChange;
            } elseif ($change > 0) {
                if ($change < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                    $amount -= (intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) - $change);
                    if ($amount < intval($this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT))) {
                        throw new BEInvalidArgumentException(
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
            $txSource->address = AddressFactory::fromString($output->getAddress(), $this->network);
            $txSource->privateKey = $this->dumpprivkey($output->getAddress());
            if (!$txSource->privateKey) {
                throw new BERuntimeException(
                    "dumpprivkey did not return object on address \"" . $output->getAddress() . "\"."
                );
            }
            $txSource->privateKey = PrivateKeyFactory::fromWif($txSource->privateKey);
            $txSource->pubKeyHash = $txSource->privateKey->getPubKeyHash(); //Very slow method
            $txSource->outputScript = ScriptFactory::scriptPubKey()->payToPubKeyHash($txSource->pubKeyHash);
            $txSource->sourceTxId = $output->getTxHash();
            $txSource->sourceVout = $output->getVout();
            $txSource->amount = $output->getValue()->getSatoshiValue();
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
                AddressFactory::fromString($sendmanyoutput->getAddress(), $this->network)
            );
        }
        if ($change > $this->getOption(self::OPT_MINIMAL_AMOUNT_FOR_SENT)) {
            $addressForChange = null;
            if (!empty($sendMoneyOptions)) {
                $addressForChange = $sendMoneyOptions->getAddressForChange();
            } else {
                $addressForChange = $outputsForSpent[0]->getAddress();
            }
            $objAddressForChange = AddressFactory::fromString($addressForChange, $this->network);
            /** @noinspection PhpUndefinedMethodInspection */
            $transaction = $transaction->payToAddress($change, $objAddressForChange);
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
