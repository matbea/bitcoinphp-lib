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

use BTCBridge\Api\Wallet;
use \Psr\Log\LoggerInterface;
use \Monolog\Logger;
use \BTCBridge\Api\Transaction;
use \BTCBridge\Api\Address;
use \BTCBridge\Api\TransactionReference;

/**
 * Base Handler class providing the Handler structure, must be extended
 *
 * @author Matbea <mail@matbea.com>
 */
abstract class AbstractHandler
{

    /** @var LoggerInterface logger handler */
    protected $logger;

    /** @var array options */
    protected $options = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setLogger(new Logger('BTCBridge'));
        $this->setOption(
            "browser",
            'Mozilla/5.0 (Windows NT 6.1; WOW64) '
            . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36'
        );
    }

    /**
     * Sets the logger handler
     *
     * @param LoggerInterface $loggerHandle a handler to logging Interface
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     *
     */
    public function setLogger(LoggerInterface $loggerHandle)
    {
        $this->logger = $loggerHandle;
    }

    /**
     * Sets the option
     *
     * @param string $optionname a name of the option
     * @param string $optionvalue a value of the option
     *
     * @throws \InvalidArgumentException if error of this type
     *
     */
    protected function setOption($optionname, $optionvalue)
    {
        if (gettype($optionname) != "string" || "" == $optionname) {
            throw new \InvalidArgumentException("Bad type of option name (must be non empty string)");
        }
        if (gettype($optionvalue) != "string" || "" == $optionvalue) {
            throw new \InvalidArgumentException("Bad type of option value (must be non empty string)");
        }
        $this->options[$optionname] = $optionvalue;
    }

    /**
     * Gets the option
     *
     * @param string $optionname a name of the option
     *
     * @throws \InvalidArgumentException if error of this type
     * @throws \RuntimeException in case if this option is not exists
     *
     * @return string Option
     */
    protected function getOption($optionname)
    {
        if (gettype($optionname) != "string" || "" == $optionname) {
            throw new \InvalidArgumentException("Bad type of option name (must be non empty string)");
        }
        if (!isset($this->options[$optionname])) {
            throw new \RuntimeException("No option with name \"" . $optionname . "\" exists in the class)");
        }
        return $this->options[$optionname];
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
     * @return Address object
     */
    abstract public function listtransactions($address, array $options = array());

    /**
     * The gettransaction RPC gets detailed information about an in-wallet transaction.
     * The Transaction Hash Endpoint returns detailed information about a given transaction based on its hash.
     * @link https://bitcoin.org/en/developer-reference#gettransaction Official bitcoin documentation.
     * @link https://www.blockcypher.com/dev/bitcoin/?php#transaction-hash-endpoint
     *
     * @param string $TXHASH a transaction identifier
     * @param array $options  Array containing the optional params
     * $options = [
     *   ['limit']               integer    Filters TXInputs/TXOutputs, if unset, default is 20.
     *   ['instart']           integer    Filters TX to only include TXInputs from this input index and above.
     *   ['outstart']           integer    Filters TX to only include TXOutputs from this output index and above.
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
    abstract public function gettransaction($TXHASH, array $options = array());

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
     * @return integer                            The balance in satoshi
     */
    abstract public function getbalance($Account, $Confirmations = 1, $IncludeWatchOnly = false);

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
    abstract public function getunconfirmedbalance($Account);

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
     * @return  TransactionReference[] The list of unspent outputs
     */
    abstract public function listunspent($Account, $MinimumConfirmations = 1);

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
    abstract public function sendrawtransaction($Transaction);

    /**
     * This Method Creates a new wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#create-wallet-endpoint
     *
     * @param string $walletName Name of wallet
     * @param string[] $addresses
     *
     * @return Wallet object
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     *
     */
    abstract public function createwallet($walletName, $addresses);

    /**
     * This Method adds new addresses into a wallet
     * @link https://www.blockcypher.com/dev/bitcoin/?shell#add-addresses-to-wallet-endpoint
     *
     * @param string $walletName Name of wallet
     * @param string[] $addresses
     *
     * @return Wallet object
     *
     * @throws \RuntimeException in case of error of this type
     * @throws \InvalidArgumentException in case of error of this type
     *
     */
    abstract public function addaddresses($walletName, $addresses);
}
