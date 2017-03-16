<?php

namespace BTCBridge;

use BTCBridge\Handler\AbstractHandler;
use BTCBridge\ConflictHandler\ConflictHandlerInterface;
use BTCBridge\ConflictHandler\DefaultConflictHandler;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;
use BTCBridge\Api\Wallet;
use Psr\Log\LoggerInterface;
use Monolog\Logger;

/**
 * Describes a Bridge instance
 *
 * Bridge takes commands from the users and retranslate them to different bitcoin services.
 *
 */
class Bridge
{
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
     * @param string $Account            An account name to get balance from
     * @param int $Confirmations         The minimum number of confirmations an externally-generated transaction
     * must have before it is counted towards the balance.
     * @param boolean $IncludeWatchOnly  Whether to include watch-only addresses in details and calculations
     *
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
     *
     * @return integer                   The balance in bitcoins (in satoshi)
     */
    public function getbalance($Account, $Confirmations = 1, $IncludeWatchOnly = false)
    {
        if ("string" != gettype($Account) || ("" == $Account)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->getbalance($Account, $Confirmations, $IncludeWatchOnly);
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
     * @param string $Account An account name to get unconfirmed balance from
     *
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
     *
     * @return integer The total number of bitcoins paid to the passed wallet in unconfirmed transactions (in satoshi)
     */
    public function getunconfirmedbalance($Account)
    {
        if ("string" != gettype($Account) || ("" == $Account)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            //$result = call_user_func_array([$handle, "getunconfirmedbalance"], [$Account]);
            $result = $handle->getunconfirmedbalance($Account);
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
     * @param string $Account An account name to get unconfirmed balance from
     * @param int $MinimumConfirmations  The minimum number of confirmations the transaction containing an output
     * must have in order to be returned.
     *
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
     *
     * @return array The list of unspent outputs
     */
    public function listunspent($Account, $MinimumConfirmations = 1)
    {
        if ("string" != gettype($Account) || ("" == $Account)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->listunspent($Account, $MinimumConfirmations);
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
     * @param integer $handlerIndex  Index of handler which will call this method
     *
     * @return string If the transaction was accepted by the node for broadcast, this will be the TXID
     * of the transaction encoded as hex in RPC byte order.
     *
     * @throws \RuntimeException in case of any error
     * @throws \InvalidArgumentException if error of this type
     *
     */
    public function sendrawtransaction($Transaction, $handlerIndex)
    {
        if ("string" != gettype($Transaction) || ("" == $Transaction)) {
            throw new \InvalidArgumentException("Transaction variable must be non empty string.");
        }
        if ("integer" != gettype($handlerIndex) || ($handlerIndex < 0 )  || ($handlerIndex>=count($this->handlers))) {
            throw new \InvalidArgumentException(
                "handlerIndex variable must non-negative integer (and less then size of handlers collection)."
            );
        }
        $result = $this->handlers[$handlerIndex]->sendrawtransaction($Transaction);
        return $result;
    }

    /**
     * This Method allows you to create a new wallet, by POSTing a partially filled out Wallet or HDWallet object,
     * depending on the endpoint.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#create-wallet-endpoint
     *
     * @param string $name Name of wallet
     * @param string[] $addresses
     *
     * @return Wallet
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     *
     */
    public function createwallet($name, $addresses)
    {
        if ("string" != gettype($name) || ("" == $name)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!is_array($addresses)) {
            throw new \InvalidArgumentException("addresses variable must be the array.");
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->createwallet($name, $addresses);
            $results [] = $result;
        }
        return $this->conflictHandler->createwallet($results);
    }

    /**
     * This Method adds new addresses into a wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#add-addresses-to-wallet-endpoint
     *
     * @param string $name Name of wallet
     * @param string[] $addresses
     *
     * @return Wallet
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     *
     */
    public function addaddresses($name, $addresses)
    {
        if ("string" != gettype($name) || ("" == $name)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if ((!is_array($addresses)) || (count($addresses) == 0)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array.");
        }
        $results = [];
        foreach ($this->handlers as $handle) {
            $result = $handle->addaddresses($name, $addresses);
            $results [] = $result;
        }
        return $this->conflictHandler->addaddresses($results);
    }
}
