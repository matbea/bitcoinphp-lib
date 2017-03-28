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

use BTCBridge\Api\Transaction;
use BTCBridge\Api\Wallet;

//use BTCBridge\Api\TransactionInput;
//use BTCBridge\Api\TransactionOutput;
use BTCBridge\Api\Address;

//use BTCBridge\Api\Wallet;
//use \BTCBridge\Api\TransactionReference;

/**
 * Returns data to user's btc-requests using Matbea-API
 * @author Matbea <mail@matbea.com>
 */
class MatbeaHandler extends AbstractHandler
{

    protected $token = "";

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->setOption(self::OPT_BASE_URL, "https://api.matbea.net");
    }

    /**
     * Setting token to the handler
     *
     * @param string $token An token for accessing to the blockcypher data
     *
     * @throws \InvalidArgumentException in case of error of this type
     *
     * @return void
     */
    public function setToken($token)
    {
        if ((gettype($token) != "string") || ("" == $token)) {
            throw new \InvalidArgumentException("Bad type of token (must be non empty string)");
        }
        $this->token = $token;
    }

    /**
     * Prepare curl descriptor for querying
     *
     * @param resource $curl A reference to the curl onjet
     * @param string $url An url address for connecting
     *
     * @throws \RuntimeException in case of any curl error
     */
    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function prepareCurl(&$curl, $url)
    {
        if (!curl_setopt($curl, CURLOPT_URL, $url)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_USERAGENT, $this->getOption("browser"))) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_HEADER, 0)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
    }


    /**
     * {@inheritdoc}
     */
    public function listtransactions($address, array $options = array())
    {
        return new Address();
    }

    /**
     * {@inheritdoc}
     */
    public function gettransaction($TXHASH, array $options = array())
    {
        return new Transaction();
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($Account = "", $Confirmations = 1, $IncludeWatchOnly = false)
    {
        return 1000000;
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($Account)
    {
        return 200000;
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($Account, $MinimumConfirmations = 1)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($Transaction)
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        return "721dca6852f828af1057d5bf5f324a6d2b27328a27882229048cf340c1e3ec10" ;
    }

    /**
     * {@inheritdoc}
     */
    public function createwallet($walletName, $addresses)
    {
        if ("todo" == $walletName) {
            $wallet = new Wallet();
            $wallet->setAddresses($addresses);
            $wallet->setName($walletName);
            $wallet->setSystemDataByHandler($this->getHandlerName(), ["name"=>$walletName, "id"=>123456789]);
            return $wallet;
        }
        return new Wallet();
    }

    /**
     * {@inheritdoc}
     */
    public function addaddresses(Wallet $wallet, $addresses)
    {
        return new Wallet();
    }

    /**
     * {@inheritdoc}
     */
    public function removeaddress($walletName, $address)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deletewallet($walletName)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses($walletName)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlerName()
    {
        return "matbea.net";
    }

    /**
     * {@inheritdoc}
     */
    public function getSystemDataForWallet(Wallet $wallet)
    {
        if (!$wallet->getName()) {
            throw new \InvalidArgumentException("No name property in the passed wallet ( " . serialize($wallet) . ")");
        }
        return $wallet->getSystemDataByHandler($this->getHandlerName());
    }
}
