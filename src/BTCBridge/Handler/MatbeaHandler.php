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

use BTCBridge\Api\TransactionInput;
use BTCBridge\Api\TransactionOutput;
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
        //$this->setOption(self::OPT_BASE_URL, "https://api.matbea.net");
        $this->setOption(self::OPT_BASE_URL, "http://136.243.32.19:8080/btcbridge");
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
        if (("string" != gettype($TXHASH)) || ("" == $TXHASH)) {
            throw new \InvalidArgumentException("TXHASH variable must be non empty string.");
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "/gettransaction?txhash=" . $TXHASH;

        $sep = "&";
        if (array_key_exists('limit', $options) && (20 !== $options['limit'])) {
            $url .= $sep . "limit=" . $options['limit'];
            $sep = "&";
        }
        if (array_key_exists('instart', $options) && (null !== $options['instart'])) {
            $url .= $sep . "instart=" . $options['instart'];
            $sep = "&";
        }
        if (array_key_exists('outstart', $options) && (null !== $options['outstart'])) {
            $url .= $sep . "outstart=" . $options['outstart'];
            $sep = "&";
        }
        if (array_key_exists('includeHex', $options) && (true === $options['includeHex'])) {
            $url .= $sep . "includeHex=true";
            $sep = "&";
        }
        if (array_key_exists('includeConfidence', $options) && (true === $options['includeConfidence'])) {
            $url .= $sep . "includeConfidence=true";
        }

        $awaiting_params = ['limit', 'instart', 'outstart', 'includeHex', 'includeConfidence'];

        foreach ($options as $opt_name => $opt_val) {
            if (!in_array($opt_name, $awaiting_params)) {
                $this->logger->warning("Method \"" . __METHOD__ . "\" does not accept option \"" . $opt_name . "\".");
            }
        }

        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if (false === $content) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error \"" . $content['error']['message'] . "\" (code: "
                . $content['error']['code'] . ") returned (url:\"" . $url . "\")."
            );
        }
        if (!isset($content["transaction"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"transaction\" property.",
                ["data" => $content]
            );
            throw new \RuntimeException(
                "Answer of url: \"" . $url . "\")  does not contain a \"transaction\" property."
            );
        }
        $tx = new Transaction;
        $tx->setBlockHash($content["transaction"]["block_hash"]);
        $tx->setBlockHeight($content["transaction"]["block_height"]);
        $tx->setHash($content["transaction"]["hash"]);
        $tx->setAddresses($content["transaction"]["addresses"]);
        $tx->setConfirmed($content["transaction"]["confirmed"]);
        $tx->setLockTime($content["transaction"]["lock_time"]);
        $tx->setDoubleSpend($content["transaction"]["double_spend"]);
        $tx->setVoutSz($content["transaction"]["vout_sz"]);
        $tx->setVinSz($content["transaction"]["vin_sz"]);
        $tx->setConfirmations($content["transaction"]["confirmations"]);
        foreach ($content["transaction"]["inputs"] as $inp) {
            $input = new TransactionInput();
            if (isset($inp["addresses"])) {
                $input->setAddresses($inp["addresses"]);
            }
            if (isset($inp["prev_hash"])) {
                $input->setPrevHash($inp["prev_hash"]);
            }
            if (isset($inp["output_index"])) {
                $input->setOutputIndex($inp["output_index"]);
            }
            $v = gmp_init(strval($inp["value"]*100*1000*1000));
            $input->setOutputValue($v);
            $input->setScriptType($this->getTransformedTypeOfSignature($inp["script_type"]));
            $tx->addInput($input);
        }
        if (empty($content["transaction"]["inputs"])) {
            //coinbase transaction - will create system input
            $input = new TransactionInput();
            $input->setOutputIndex(-1);
            $input->setScriptType("pubkey");
            $divresult = floor($content["transaction"]["block_height"]/210000);
            $value = (50*(pow(0.5, $divresult)))*100*1000*1000;
            $input->setOutputValue(gmp_init(strval($value)));
            $tx->addInput($input);
            $tx->setVinSz(1);
        }
        foreach ($content["transaction"]["outputs"] as $outp) {
            $output = new TransactionOutput();
            $output->setAddresses($outp["addresses"]);
            $v = gmp_init(strval($outp["value"]*100*1000*1000));
            $output->setValue($v);
            $output->setScriptType($this->getTransformedTypeOfSignature($outp["script_type"]));
            $tx->addOutput($output);
        }
        return $tx;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($walletName, $Confirmations = 1, $IncludeWatchOnly = false)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "/getbalance?accountId=" . $walletName
            . "&confirmations=" . $Confirmations;
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if (false === $content) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error \"" . $content['error']['message'] . "\" (code: "
                . $content['error']['code'] . ") returned (url:\"" . $url . "\")."
            );
        }
        if (!isset($content["balance"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.",
                ["data" => $content]
            );
            throw new \RuntimeException("Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.");
        }
        return intval($content["balance"]*100*1000*1000);
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($walletName)
    {
        if ("string" != gettype($walletName) || ("" == $walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "/getunconfirmedbalance?accountId=" . $walletName;
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if (false === $content) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error \"" . $content['error']['message'] . "\" (code: "
                . $content['error']['code'] . ") returned (url:\"" . $url . "\")."
            );
        }
        if (!isset($content["balance"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.",
                ["data" => $content]
            );
            throw new \RuntimeException("Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.");
        }
        return intval($content["balance"]*100*1000*1000);
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
    public function addAddresses(Wallet $wallet, $addresses)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "System data of passed wallet is empty (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $walletId = $walletSystemData["id"];
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function removeaddress(Wallet $wallet, $address)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "System data of passed wallet is empty (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $walletId = $walletSystemData["id"];
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function deletewallet(Wallet $wallet)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "System data of passed wallet is empty (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $walletId = $walletSystemData["id"];
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses(Wallet $wallet)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "System data of passed wallet is empty (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        /** @noinspection PhpUnusedLocalVariableInspection */
        $walletId = $walletSystemData["id"];
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformedTypeOfSignature($type, array $options = [])
    {
        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlerName()
    {
        return "matbea.net";
    }
}
