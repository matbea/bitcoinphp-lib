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
use BTCBridge\Exception\BEInvalidArgumentException;
use BTCBridge\Exception\BERuntimeException;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;
use BTCBridge\Api\BTCValue;

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
     * @throws BEInvalidArgumentException if the provided argument $handlers is empty
     * or any item of this array has incorrect type
     */
    public function setHandlers(array $handlers)
    {
        foreach ($handlers as $handler) {
            if (!$handler instanceof AbstractHandler) {
                throw new BEInvalidArgumentException("The given handler is not an AbstractHandler");
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
     *
     * @param Address[] $data  Result from method listtransactions (from all handlers)
     *
     * @throws BEInvalidArgumentException in case of any error of this type
     * @throws ResultHandlerException in case of any error
     *
     * @return Address object
     */
    abstract public function listtransactions($data);

    /**
     * The gettransactions RPC gets detailed information about an in-wallet transaction.
     *
     * @param Transaction[][] $data  Result from method gettransactions (from all handlers)
     *
     * @throws BEInvalidArgumentException in case of any error of this type
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
     *
     * @param array $data  Result from method getbalance (from all handlers)
     *
     * @throws BEInvalidArgumentException in case of any error of this type
     * @throws ResultHandlerException in case of any error
     *
     * @return BTCValue The balance
     */
    abstract public function getbalance($data);

    /**
     * Returns the wallet’s total unconfirmed balance.
     * The Address Balance Endpoint is the simplest—and fastest—method to get a subset of
     * information on a public address.
     * @link https://bitcoin.org/en/developer-reference#getunconfirmedbalance Official bitcoin documentation.
     *
     * @param array $data  Result from method getunconfirmedbalance (from all handlers)
     *
     * @throws BEInvalidArgumentException in case of any error of this type
     * @throws ResultHandlerException in case of any error
     *
     * @return BTCValue The total number of bitcoins paid to this wallet in unconfirmed transactions
     */
    abstract public function getunconfirmedbalance($data);

    /**
     * Returns an array of unspent transaction outputs belonging to this wallet.
     * The Address Balance Endpoint is the simplest—and fastest—method to get a subset
     * of information on a public address.
     * @link https://bitcoin.org/en/developer-reference#listunspent Official bitcoin documentation.
     *
     * @param TransactionReference[][] $data
     * Result from method listunspent (from all handlers)
     *
     * @throws BEInvalidArgumentException in case of any error of this type
     * @throws ResultHandlerException in case of any error
     *
     * @return TransactionReference[] The list of unspent outputs
     */
    abstract public function listunspent($data);

    /**
     * This Method Creates a new wallet
     *
     * @param array $data  Result from method createWallet (from all handlers)
     *
     * @return Wallet
     *
     * @throws ResultHandlerException in case of any error
     * @throws BEInvalidArgumentException in case of any error of this type
     *
     */
    abstract public function createWallet($data);

    /**
     * This Method removes address from the passed wallet
     *
     * @param array $data  Result from method removeAddresses (from all handlers)
     *
     * @return Wallet
     *
     * @throws BEInvalidArgumentException in case of any error of this type
     * @throws ResultHandlerException in case of any error
     *
     */
    abstract public function removeAddresses($data);


    /**
     * This Method adds new addresses into a wallet
     *
     * @param array $data  Result from method addAddresses (from all handlers)
     *
     * @return Wallet
     *
     * @throws BEInvalidArgumentException in case of any error of this type
     * @throws ResultHandlerException in case of any error
     *
     */
    abstract public function addAddresses($data);

    /**
     * This method returns addresses from the passed wallet
     * @link https://bitcoin.org/en/developer-reference#getaddressesbyaccount
     *
     * @param array $data  Result from method getAddresses (from all handlers)
     *
     * @throws BERuntimeException in case of any error of this type
     * @throws BEInvalidArgumentException in case of any error of this type
     *
     * @return \string[] addresses
     */
    abstract public function getAddresses($data);

    /**
     * This method returns wallets  and addresses optionally by token
     *
     * @param array $data  Result from method getWallets (from all handlers)
     *
     * @throws BERuntimeException in case of any error of this type
     * @throws BEInvalidArgumentException in case of any error of this type
     *
     * @return Wallet[] wallets
     */
    abstract public function getWallets($data);
}
