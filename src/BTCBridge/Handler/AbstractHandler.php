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
use \BTCBridge\Api\BTCValue;
use \BTCBridge\Api\ListTransactionsOptions;
use \BTCBridge\Api\WalletActionOptions;
use BTCBridge\Api\CurrencyTypeEnum;
use BTCBridge\Exception\BERuntimeException;
use BTCBridge\Exception\BEInvalidArgumentException;
use BTCBridge\Exception\BELogicException;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkFactory;

/**
 * Base Handler class providing the Handler structure, must be extended
 *
 * @author Matbea <mail@matbea.com>
 */
abstract class AbstractHandler
{
    /** @const HANDLER_UNSUPPORTED_METHOD this value returned if method is unsupported */
    const HANDLER_UNSUPPORTED_METHOD = "HANDLER_UNSUPPORTED_METHOD";

    /** This group of constants are options */
    const OPT_BASE_URL     = 1;
    const OPT_BASE_BROWSER = 2;

    /** @var LoggerInterface logger handler */
    protected $logger;

    /** @var array options */
    protected $options = [];

    /** @var CurrencyTypeEnum currency */
    protected $currency;

    /** @var Network network */
    protected $network;

    /**
     * Constructor
     *
     * @param CurrencyTypeEnum $currency          Name of currency
     *
     * @throws BERuntimeException in case of error of this type
     * @throws BEInvalidArgumentException in case of error of this type
     */
    public function __construct(CurrencyTypeEnum $currency)
    {
        $this->currency = $currency;
        if (CurrencyTypeEnum::BTC == $this->currency) {
            $this->network = NetworkFactory::bitcoin();
        } elseif (CurrencyTypeEnum::TBTC == $this->currency) {
            $this->network = NetworkFactory::bitcoinTestnet();
        }
        $this->setLogger(new Logger('BTCBridge'));
        $this->setOption(
            self::OPT_BASE_BROWSER,
            'Mozilla/5.0 (Windows NT 6.1; WOW64) '
            . 'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36'
        );
    }

    /**
     * Get currency
     *
     * @return CurrencyTypeEnum
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Sets the logger handler
     *
     * @param LoggerInterface $loggerHandle a handler to logging Interface
     *
     * @throws BERuntimeException in case of error of this type
     * @throws BEInvalidArgumentException in case of error of this type
     *
     */
    public function setLogger(LoggerInterface $loggerHandle)
    {
        $this->logger = $loggerHandle;
    }

    /**
     * Sets the option
     *
     * @param int $optionName a const which describes name of the option
     * @param string $optionValue a value of the followed option
     *
     * @throws BEInvalidArgumentException if error of this type
     *
     */
    public function setOption($optionName, $optionValue)
    {
        if (!is_int($optionName)) {
            $msg = "Bad type (" . gettype($optionName) . ") of option name (must be integer)";
            throw new BEInvalidArgumentException($msg);
        }
        if (!is_string($optionValue) || empty($optionValue)) {
            $msg = "Bad type (" . gettype($optionValue) . ") of option value (must be non empty string)";
            throw new BEInvalidArgumentException($msg);
        }
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Gets the option
     *
     * @param int $optionName a const which describes name of the option
     *
     * @throws BEInvalidArgumentException if error of this type
     * @throws BERuntimeException in case if this option is not exists
     *
     * @return string Option
     */
    public function getOption($optionName)
    {
        if (!is_int($optionName)) {
            throw new BEInvalidArgumentException(
                "Bad type (" . gettype($optionName) . ") of option name (must be integer)"
            );
        }
        if (!isset($this->options[$optionName])) {
            throw new BERuntimeException("No option with name \"" . $optionName . "\" exists in the class)");
        }
        return $this->options[$optionName];
    }

    /**
     * Prepare curl descriptor for querying
     *
     * @param resource $curl A reference to the curl object
     * @param string $url An url address for connecting
     *
     * @throws BERuntimeException in case of any curl error
     */
    protected function prepareCurl(&$curl, $url)
    {
        $curl_options = [
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => $this->getOption(self::OPT_BASE_BROWSER),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER         => 0,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new BERuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: " . serialize($curl_options) . ")."
            );
        }
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
     * @throws BERuntimeException in case of any error of this type
     * @throws BEInvalidArgumentException in case of any error of this type
     *
     * @return Address object
     */
    abstract public function listtransactions($walletName, ListTransactionsOptions $options = null);

    /**
     * The gettransactions RPC gets detailed information about an in-wallet transaction.
     * @param string[] $txHashes transaction identifiers
     *
     * @throws BERuntimeException in case of any error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return Transaction[]
     */
    abstract public function gettransactions(array $txHashes);


    /**
     * The getbalance RPC gets the balance in decimal bitcoins across all accounts or for a particular account.
     * The Address Balance Endpoint is the simplest—and fastest—method
     * to get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getbalance Official bitcoin documentation.
     *
     * @param string $walletName (or address)     A wallet name to get balance from
     * @param int $Confirmations         The minimum number of confirmations an externally-generated transaction
     * must have before it is counted towards the balance.
     *
     * @throws BERuntimeException in case of any error
     * @throws BELogicException in case of any error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return BTCValue The total number of bitcoins paid to the passed wallet in unconfirmed transactions
     */
    abstract public function getbalance($walletName, $Confirmations = 1);

    /**
     * Returns the wallet’s total unconfirmed balance.
     * The Address Balance Endpoint is the simplest—and fastest—method
     * to get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
     *
     * @param string $walletName A wallet name (or address) to get unconfirmed balance from
     *
     * @throws BERuntimeException in case of any error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return BTCValue The total number of bitcoins paid to the passed wallet in unconfirmed transactions
     */
    abstract public function getunconfirmedbalance($walletName);

    /**
     * Returns an array of unspent transaction outputs belonging to this wallet.
     * The Address Balance Endpoint is the simplest—and fastest—method to
     * get a subset of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#listunspent Official bitcoin documentation.
     *
     * @param string $walletName A wallet name (or address) to get unconfirmed balance from
     * @param int $MinimumConfirmations  The minimum number of confirmations the transaction containing an output
     * must have in order to be returned.
     * If $MinimumConfirmations = 0, then only unconfirmed transactions will be returned.
     *
     * @throws BERuntimeException in case of any error
     * @throws BEInvalidArgumentException if error of this type
     *
     * @return  TransactionReference[] The list of unspent outputs
     */
    abstract public function listunspent($walletName, $MinimumConfirmations = 1);

    /**
     * The sendrawtransaction RPC validates a transaction and broadcasts it to the peer-to-peer network.
     * @link https://bitcoin.org/en/developer-reference#sendrawtransaction Official bitcoin documentation.
     *
     * @param string $transaction Raw transaction hex-encoded
     *
     * @return string If the transaction was accepted by the node for broadcast, this will be the TXID
     * of the transaction encoded as hex in RPC byte order.
     *
     * @throws BERuntimeException in case of any error
     * @throws BEInvalidArgumentException if error of this type
     *
     */
    abstract public function sendrawtransaction($transaction);

    /**
     * This Method Creates a new wallet
     *
     * @param string $walletName Name of wallet
     * @param string[] $addresses
     * @param WalletActionOptions $options
     *
     * @return Wallet object
     *
     * @throws BERuntimeException in case of error of this type
     * @throws BEInvalidArgumentException in case of error of this type
     *
     */
    abstract public function createWallet($walletName, array $addresses, WalletActionOptions $options = null);

    /**
     * This Method removes address from the wallet
     *
     * @param Wallet $wallet
     * @param string[] $addresses
     * @param WalletActionOptions $options
     *
     * @return Wallet result object
     *
     * @throws BERuntimeException in case of error of this type
     * @throws BEInvalidArgumentException in case of error of this type
     *
     */
    abstract public function removeAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null);

    /**
     * This Method adds new addresses into a wallet
     *
     * @param Wallet $wallet Object to which addresses will be added
     * @param string[] $addresses
     * @param WalletActionOptions $options
     *
     * @return Wallet result object
     *
     * @throws BERuntimeException in case of error of this type
     * @throws BEInvalidArgumentException in case of error of this type
     *
     */
    abstract public function addAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null);


    /**
     * This Method deletes a passed wallet
     *
     * @param Wallet $wallet
     *
     * @throws BERuntimeException in case of error of this type
     */
    abstract public function deleteWallet(Wallet $wallet);

    /**
     * This method returns addresses from the passed wallet
     * @link https://bitcoin.org/en/developer-reference#getaddressesbyaccount
     *
     * @param Wallet $wallet
     *
     * @throws BERuntimeException in case of any error of this type
     * @throws BEInvalidArgumentException in case of any error of this type
     *
     * @return \string[] addresses
     */
    abstract public function getAddresses(Wallet $wallet);

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
    abstract public function getWallets(WalletActionOptions $options = null);

    /**
     * This method transforms name of signature type to common
     *
     * @param string $type
     * @param array $options
     *
     * @return \string transformed type
     */
    abstract public function getTransformedTypeOfSignature($type, array $options = []);

    /**
     * This method returns name of current handler
     *
     *
     * @return \string Name of the handler
     */
    abstract public function getHandlerName();


    /**
     * This method returns system Id from the passed wallet
     *
     * @param Wallet $wallet
     *
     * @throws BERuntimeException in case of any error of this type
     * @throws BEInvalidArgumentException in case of any error of this type
     *
     * @return \array systemdata
     */
    public function getSystemDataForWallet(Wallet $wallet)
    {
        return $wallet->getSystemDataByHandler($this->getHandlerName());
    }
}
