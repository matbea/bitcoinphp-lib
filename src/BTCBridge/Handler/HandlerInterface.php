<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\Handler;

use Psr\Log\LoggerInterface;

/**
 * Interface that all BTCBridge Handlers must implement
 *
 * @author Matbea <mail@matbea.com>
 */
interface HandlerInterface {
	/**
	 * Sets the logger handler
	 *
	 * @param string  $TXID a transaction identifier (TXID)
	 * @param boolean $IncludeWatchOnly whether to include watch-only addresses in details and calculations
	 *
	 * @throws RuntimeException in case of any error
	 *
	 * @return array
	 */
	public function setLogger(LoggerInterface $loggerHandle);

	/**
	 * The listtransactions RPC returns the most recent transactions that affect the wallet.
	 * The default Address Endpoint strikes a balance between speed of response and data on Addresses. It returns more information about an address’ transactions than the Address Balance Endpoint but doesn’t return full transaction information (like the Address Full Endpoint).
	 * @link https://bitcoin.org/en/developer-reference#listtransactions Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint Official blockcypher documentation
	 *
	 * @param string $address  An account name (or address) to get transactions from
	 * @param array  $options  Array containing the optional params
	 * $options = [
	 *   ['unspentOnly']           bool      If unspentOnly is true, filters response to only include unspent transaction outputs (UTXOs).
	 *   ['includeScript']         bool      If includeScript is true, includes raw script of input or output within returned TXRefs.
	 *   ['includeConfidence']     bool      If true, includes the confidence attribute (useful for unconfirmed transactions) within returned TXRefs. For more info about this figure, check the Confidence Factor documentation.
	 *   ['before']                integer   Filters response to only include transactions below before height in the blockchain.
	 *   ['after']                 integer   Filters response to only include transactions above after height in the blockchain.
	 *   ['limit']                 integer   Limit sets the minimum number of returned TXRefs; there can be less if there are less than limit TXRefs associated with this address, but there can be more in the rare case of more TXRefs in the block at the bottom of your call. This ensures paging by block height never misses TXRefs. Defaults to 200, maximum is 2000.
	 *   ['confirmations']         integer   If set, only returns the balance and TXRefs that have at least this number of confirmations.
	 *   ['confidence']            integer   Filters response to only include TXRefs above confidence in percent; e.g., if this is set to 99, will only return TXRefs with 99% confidence or above (including all confirmed TXRefs). For more detail on confidence, check the Confidence Factor documentation.
	 *   ['omitWalletAddresses']   bool      If omitWalletAddresses is true and you’re querying a Wallet or HDWallet, the response will omit address information (useful to speed up the API call for larger wallets).
	 * ]
	 *
	 * @throws RuntimeException in case of any error
	 *
	 * @return array
	 */
	public function listtransactions($address,array $options = array());

	/**
	 * The gettransaction RPC gets detailed information about an in-wallet transaction.
	 * The Transaction Hash Endpoint returns detailed information about a given transaction based on its hash.
	 * @link https://bitcoin.org/en/developer-reference#gettransaction Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?php#transaction-hash-endpoint
	 *
	 * @param string  $TXHASH a transaction identifier
	 * @param array  $options  Array containing the optional params
	 * $options = [
	 *   ['limit'] 	           integer 	Filters TXInputs/TXOutputs, if unset, default is 20.
	 *   ['instart'] 	       integer 	Filters TX to only include TXInputs from this input index and above.
	 *   ['outstart'] 	       integer 	Filters TX to only include TXOutputs from this output index and above.
	 *   ['includeHex']        bool 	If true, includes hex-encoded raw transaction; false by default.
	 *   ['includeConfidence'] bool 	If true, includes the confidence attribute (useful for unconfirmed transactions). For more info about this figure, check the Confidence Factor documentation.
	 * ]
	 *
	 * @throws RuntimeException in case of any error
	 * @throws InvalidArgumentException if error of this type
	 *
	 * @return array
	 */
	public function gettransaction($TXHASH,array $options = array());

	/**
	 * The getbalance RPC gets the balance in decimal bitcoins across all accounts or for a particular account.
	 * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of information on a public address.
	 * @link https://bitcoin.org/en/developer-reference#getbalance Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
	 *
	 * @param string  $Account                  An account name to get balance from
	 * @param int     $Confirmations            The minimum number of confirmations an externally-generated transaction must have before it is counted towards the balance.
	 * @param boolean $IncludeWatchOnly         Whether to include watch-only addresses in details and calculations
	 *
	 * @throws RuntimeException in case of any error
	 * @throws InvalidArgumentException if error of this type
	 *
	 * @return float                            The balance in bitcoins
	 */
	public function getbalance($Account,$Confirmations=1,$IncludeWatchOnly=false);

	/**
	 * Returns the wallet’s total unconfirmed balance.
	 * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of information on a public address.
	 * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
	 *
	 * @param string  $Account An account name to get unconfirmed balance from
	 *
	 * @throws RuntimeException in case of any error
	 * @throws InvalidArgumentException if error of this type
	 *
	 * @return float The total number of bitcoins paid to the passed wallet in unconfirmed transactions
	 */
	public function getunconfirmedbalance($Account);

	/**
	 * Returns an array of unspent transaction outputs belonging to this wallet.
	 * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of information on a public address.
	 * @link https://bitcoin.org/en/developer-reference#listunspent Official bitcoin documentation.
	 * @link https://www.blockcypher.com/dev/bitcoin/?shell#address-endpoint
	 *
	 * @param string  $Account An account name to get unconfirmed balance from
	 * @param int     $MinimumConfirmations  The minimum number of confirmations the transaction containing an output must have in order to be returned.
	 *
	 * @throws RuntimeException in case of any error
	 * @throws InvalidArgumentException if error of this type
	 *
	 * @return array                         The list of unspent outputs
	 */
	public function listunspent($Account,$MinimumConfirmations=1);

	/**
	 * The sendrawtransaction RPC validates a transaction and broadcasts it to the peer-to-peer network.
	 * @link https://bitcoin.org/en/developer-reference#sendrawtransaction Official bitcoin documentation.
	 *
	 * @param string  $Transaction  The minimum number of confirmations the transaction containing an output must have in order to be returned.
	 * @return string If the transaction was accepted by the node for broadcast, this will be the TXID of the transaction encoded as hex in RPC byte order.
	 *
	 * @throws RuntimeException in case of any error
	 * @throws InvalidArgumentException if error of this type
	 *
	 */
	public function sendrawtransaction($Transaction);
};
