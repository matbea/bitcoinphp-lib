<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\ConflictHandler;

use BTCBridge\Exception\ConflictHandlerException;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;
use BTCBridge\Api\TransactionReference;

/**
 * Interface that all BTCBridge ConflictHandlers must implement.
 * Every method can throw Exception in case of any error occured.
 *
 * @author Matbea <mail@matbea.com>
 */
interface ConflictHandlerInterface
{
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
     * @throws ConflictHandlerException in case of any error
     */
    public function listtransactions($data);

    /**
     * The gettransactions RPC gets detailed information about an in-wallet transaction.
     *
     * @param Transaction[][] $data  Result from method gettransactions (from all handlers)
     *
     * @throws ConflictHandlerException in case of any error
     */
    public function gettransactions($data);

    /**
     * The getbalance RPC gets the balance in decimal bitcoins across all accounts or for a particular account.
     * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of
     * information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getbalance Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param array $data  Result from method getbalance (from all handlers)
     *
     * @throws ConflictHandlerException in case of any error
     */
    public function getbalance($data);

    /**
     * Returns the wallet’s total unconfirmed balance.
     * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of
     * information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
     *
     * @param array $data  Result from method getunconfirmedbalance (from all handlers)
     *
     * @throws ConflictHandlerException in case of any error
     */
    public function getunconfirmedbalance($data);

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
     * @throws ConflictHandlerException in case of any error
     */
    public function listunspent($data);

    /**
     * The sendrawtransaction RPC validates a transaction and broadcasts it to the peer-to-peer network.
     * @link https://bitcoin.org/en/developer-reference#sendrawtransaction Official bitcoin documentation.
     *
     * @param array $data  Result from method sendrawtransaction (from all handlers)
     *
     * @throws ConflictHandlerException in case of any error
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
     * @return boolean  If ok then true
     *
     * @throws ConflictHandlerException in case of any error
     *
     */
    public function createWallet($data);

    /**
     * This Method removes address from the passed wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#remove-addresses-from-wallet-endpoint
     *
     * @param array $data  Result from method removeAddresses (from all handlers)
     *
     * @throws ConflictHandlerException in case of any error
     *
     */
    public function removeAddresses($data);


    /**
     * This Method adds new addresses to the passed wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#add-addresses-to-wallet-endpoint
     *
     * @param array $data  Result from method addAddresses (from all handlers)
     *
     * @throws ConflictHandlerException in case of any error
     *
     */
    public function addAddresses($data);

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
    public function getAddresses($data);
}
