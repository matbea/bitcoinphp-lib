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
//use BTCBridge\Api\TransactionInput;
//use BTCBridge\Api\TransactionOutput;
//use BTCBridge\Api\Address;
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
        $this->setOption("base_url", "https://api.matbea.net");
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
    private function prepare_curl(&$curl, $url)
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
        return [];
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
        return "721dca6852f828af1057d5bf5f324a6d2b27328a27882229048cf340c1e3ec10";
    }
}
