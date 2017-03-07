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

/**
 * Interface that all BTCBridge ConflictHandlers must implement. Every method can throw Exception in case of any error occured.
 *
 * @author Matbea <mail@matbea.com>
 */
interface ConflictHandlerInterface {
	/**
	 * The listtransactions RPC returns the most recent transactions that affect the wallet.
	 * The default Address Endpoint strikes a balance between speed of response and data on Addresses. It returns more information about an address’ transactions than the Address Balance Endpoint but doesn’t return full transaction information (like the Address Full Endpoint).
	 * @link https://bitcoin.org/en/developer-reference#listtransactions Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint Official blockcypher documentation
	 *
	 * @param array  $data  Result from method listtransactions (from all handlers)
	 *
	 * @throws ConflictHandlerException in case of any error
	 *
	 * @return array
	 */
	public function listtransactions($data);

	/**
	 * The gettransaction RPC gets detailed information about an in-wallet transaction.
	 * The Transaction Hash Endpoint returns detailed information about a given transaction based on its hash.
	 * @link https://bitcoin.org/en/developer-reference#gettransaction Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?php#transaction-hash-endpoint
	 *
	 * @param array  $data  Result from method gettransaction (from all handlers)
	 *
	 * @throws ConflictHandlerException in case of any error
	 *
	 * @return array
	 */
	public function gettransaction($data);

	/**
	 * The getbalance RPC gets the balance in decimal bitcoins across all accounts or for a particular account.
	 * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of information on a public address.
	 * @link https://bitcoin.org/en/developer-reference#getbalance Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
	 *
	 * @param array  $data  Result from method getbalance (from all handlers)
	 *
	 * @throws ConflictHandlerException in case of any error
	 *
	 * @return float        The balance in bitcoins
	 */
	public function getbalance($data);

	/**
	 * Returns the wallet’s total unconfirmed balance.
	 * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of information on a public address.
	 * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
	 *
	 * @param array  $data  Result from method getunconfirmedbalance (from all handlers)
	 *
	 * @throws ConflictHandlerException in case of any error
	 *
	 * @return float        The total number of bitcoins paid to this wallet in unconfirmed transactions
	 */
	public function getunconfirmedbalance($data);

	/**
	 * Returns an array of unspent transaction outputs belonging to this wallet.
	 * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of information on a public address.
	 * @link https://bitcoin.org/en/developer-reference#listunspent Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
	 *
	 * @param array  $data  Result from method listunspent (from all handlers)
	 *
	 * @throws ConflictHandlerException in case of any error
	 *
	 * @return array        The list of unspent outputs
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
	 * @return string If the transaction was accepted by the node for broadcast, this will be the TXID of the transaction encoded as hex in RPC byte order.
	 */
	public function sendrawtransaction($data);

};