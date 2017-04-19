<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\ResultHandler;

use BTCBridge\Handler\AbstractHandler;
use BTCBridge\Api\TransactionReference;
use BTCBridge\Api\Wallet;
use BTCBridge\Exception\ResultHandlerException;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;

/**
 * Abstract class that all BTCBridge ResultHandlers must extend and implement.
 *
 * @author Matbea <mail@matbea.com>
 */
abstract class AbstractResultHandler
{

    /** @var AbstractHandler[] $handlers */
    protected $handlers;

    /**
     * Set handlers to the instanse
     * @param AbstractHandler[] $handlers
     *
     * @throws \InvalidArgumentException if the provided argument $handlers is empty
     * or any item of this array has incorrect type
     */
    public function setHandlers(array $handlers)
    {
        /*if (empty($handlers)) {
            throw new \InvalidArgumentException("Handlers array can not be empty.");
        }*/
        foreach ($handlers as $handler) {
            if (!$handler instanceof AbstractHandler) {
                throw new \InvalidArgumentException("The given handler is not an AbstractHandler");
            }
        }
        $this->handlers = $handlers;
    }

    /**
     * The listtransactions RPC returns the most recent transactions that affect the wallet.
     * The default Address Endpoint strikes a balance between speed of response and data on Addresses.
     * It returns more information about an address’ transactions than the Address Balance Endpoint but
     * doesn’t return full transaction information (like the Address Full Endpoint).
     * @link https://bitcoin.org/en/developer-reference#listtransactions Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint Official blockcypher documentation
     *
     * @param Address[] $data  Result from method listtransactions (from all handlers)
     *
     * @throws ResultHandlerException in case of any error
     *
     * @return Address object
     */
    abstract public function listtransactions($data);

    /**
     * The gettransaction RPC gets detailed information about an in-wallet transaction.
     * The Transaction Hash Endpoint returns detailed information about a given transaction based on its hash.
     * @link https://bitcoin.org/en/developer-reference#gettransaction Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?php#transaction-hash-endpoint
     *
     * @param Transaction[] $data  Result from method gettransaction (from all handlers)
     *
     * @throws ResultHandlerException in case of any error
     *
     * @return Transaction
     */
    abstract public function gettransaction($data);

    /**
     * The gettransactions RPC gets detailed information about an in-wallet transaction.
     *
     * @param Transaction[][] $data  Result from method gettransactions (from all handlers)
     *
     * @throws ResultHandlerException in case of any error
     *
     * @return Transaction[]
     */
    abstract public function gettransactions($data);


    /**
     * The getbalance RPC gets the balance in decimal bitcoins across all accounts or for a particular account.
     * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of
     * information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getbalance Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param array $data  Result from method getbalance (from all handlers)
     *
     * @throws ResultHandlerException in case of any error
     *
     * @return integer        The balance in satoshi
     */
    abstract public function getbalance($data);

    /**
     * Returns the wallet’s total unconfirmed balance.
     * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of
     * information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param array $data  Result from method getunconfirmedbalance (from all handlers)
     *
     * @throws ResultHandlerException in case of any error
     *
     * @return integer        The total number of bitcoins paid to this wallet in unconfirmed transactions (in satoshi)
     */
    abstract public function getunconfirmedbalance($data);

    /**
     * Returns an array of unspent transaction outputs belonging to this wallet.
     * The Address Balance Endpoint is the simplest—and fastest—method to get a subset
     * of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#listunspent Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param TransactionReference[][] $data
     * Result from method listunspent (from all handlers)
     *
     * @throws ResultHandlerException in case of any error
     *
     * @return TransactionReference[] The list of unspent outputs
     */
    abstract public function listunspent($data);

    /**
     * The sendrawtransaction RPC validates a transaction and broadcasts it to the peer-to-peer network.
     * @link https://bitcoin.org/en/developer-reference#sendrawtransaction Official bitcoin documentation.
     *
     * @param array $data  Result from method sendrawtransaction (from all handlers)
     *
     * @throws ResultHandlerException in case of any error
     *
     * @return string If the transaction was accepted by the node for broadcast, this will be the TXID of the
     * transaction encoded as hex in RPC byte order.
     */
    //public function sendrawtransaction($data);

    /**
     * This Method Creates a new wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#create-wallet-endpoint
     *
     * @param array $data  Result from method createWallet (from all handlers)
     *
     * @return Wallet
     *
     * @throws ResultHandlerException in case of any error
     *
     */
    abstract public function createWallet($data);

    /**
     * This Method removes address from the passed wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#remove-addresses-from-wallet-endpoint
     *
     * @param array $data  Result from method removeAddress (from all handlers)
     *
     * @return Wallet
     *
     * @throws ResultHandlerException in case of any error
     *
     */
    abstract public function removeAddress($data);


    /**
     * This Method adds new addresses into a wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#add-addresses-to-wallet-endpoint
     *
     * @param array $data  Result from method addAddresses (from all handlers)
     *
     * @return Wallet
     *
     * @throws ResultHandlerException in case of any error
     *
     */
    abstract public function addAddresses($data);

    /**
     * This method returns addresses from the passed wallet
     * @link https://bitcoin.org/en/developer-reference#getaddressesbyaccount
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#get-wallet-addresses-endpoint
     *
     * @param array $data  Result from method getAddresses (from all handlers)
     *
     * @throws \RuntimeException in case of any error of this type
     * @throws \InvalidArgumentException in case of any error of this type
     *
     * @return \string[] addresses
     */
    abstract public function getAddresses($data);
}
