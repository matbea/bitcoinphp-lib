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

use BTCBridge\Api\ListTransactionsOptions;
use BTCBridge\Api\GetWalletsOptions;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\TransactionReference;
use BTCBridge\Api\Wallet;
use BTCBridge\Api\TransactionInput;
use BTCBridge\Api\TransactionOutput;
use BTCBridge\Api\Address;
use BTCBridge\Api\BTCValue;

//use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
        //$this->setOption(self::OPT_BASE_URL, "http://136.243.32.19:8080/btcbridge");
        $this->setOption(self::OPT_BASE_URL, "http://api.matbea.net:8080/btcbridge");
    }

    /**
     * Setting token to the handler
     *
     * @param string $token An token for accessing to the blockcypher data
     *
     * @throws \InvalidArgumentException in case of error of this type
     *
     * @return $this
     */
    public function setToken($token)
    {
        if ((gettype($token) != "string") || ("" == $token)) {
            throw new \InvalidArgumentException("Bad type of token (must be non empty string)");
        }
        $this->token = $token;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function listtransactions($walletName, ListTransactionsOptions $options = null)
    {
        if ("string" != gettype($walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . "listtransactions" . "?accountId=" . $walletName;
        $sep = "?";
        if ($this->token) {
            $url .= "&token=" . $this->token;
            $sep = "&";
        }
        if ($options) {
            if (null !== $options->getBefore()) {
                $url .= $sep . "before=" . $options->getBefore();
                $sep = "&";
            }
            if (null !== $options->getAfter()) {
                $url .= $sep . "after=" . $options->getAfter();
                $sep = "&";
            }
            if (null !== $options->getLimit()) {
                $url .= $sep . "limit=" . $options->getLimit();
                $sep = "&";
            }
            if (null !== $options->getOffset()) {
                $url .= $sep . "offset=" . $options->getOffset();
                $sep = "&";
            }
            if (null !== $options->getConfirmations()) {
                $url .= $sep . "confirmations=" . $options->getConfirmations();
            } else {
                $url .= $sep . "confirmations=1";
            }
            $sep = "&";
            if (null !== $options->getNobalance()) {
                $url .= $sep . "nobalance=" . ($options->getNobalance() ? "true" : "false");
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
                "Error (code: " . $content["error"]["code"] . ") \""
                . $content['error']["message"] . "\" returned (url:\"" . $url . "\")."
            );
        }

        /** @var $result Address */
        $addrObject = new Address();

        if (isset($content['address'])) {
            $addrObject->setAddress($content['address']);
        } else {
            if (isset($content['wallet'])) {
                $wallet = new Wallet();
/////////////////////////////////////////////////////////////////////////////////
                if (!isset($content['wallet']['name'])) {
                    throw new \RuntimeException(
                        "Answer does not contain \"wallet.name\" field (url:\"" . $url . "\")."
                    );
                }
                if (!isset($content['wallet']['id'])) {
                    throw new \RuntimeException(
                        "Answer does not contain \"wallet.id\" field (url:\"" . $url . "\").");
                }
                if (!isset($content['wallet']['addresses'])) {
                    throw new \RuntimeException(
                        "Answer does not contain \"wallet.addresses\" field (url:\"" . $url . "\").");
                }
                $wallet->setName($content['wallet']['name']);
                $wallet->setAddresses($content['wallet']['addresses']);
                $wallet->setSystemDataByHandler(
                    $this->getHandlerName(),
                    [
                        "name" => $content['wallet']['name']
                        ,
                        "id" => intval($content['wallet']['id'])
                    ]
                );
/////////////////////////////////////////////////////////////////////////////////
                $addrObject->setWallet($wallet);
            }
        }

        /** @var $txrefs TransactionReference[] */
        $txrefs = [];
        $txRefHashes = [];

        foreach ($content["transactions"] as $txref) {
            $txr = new TransactionReference();
            $txr->setBlockHeight($txref["block_height"]);
            $txr->setConfirmations($txref["confirmations"]);
            $txr->setDoubleSpend($txref["double_spend"]);
            $txr->setTxHash($txref["txid"]);
            $txr->setVout($txref["vout"]);
            $txr->setConfirmed(strtotime($txref["time"]));
            $txr->setCategory($txref["category"]);
            if (false !== strpos($txref["amount"], "E")) {
                $txref["amount"] = sprintf('%f', $txref["amount"]); //Exponential form
            }
            $v = gmp_init(strval($txref["amount"] * 100 * 1000 * 1000));
            $txr->setValue(new BTCValue($v));
            $txr->setAddress($txref['address']);

            $txRefHash = (string)($txr->getVout()) . "_" . $txr->getTxHash() . "_"
                . $txr->getConfirmed() . "_" . $txr->getCategory() . "_"
                . (string)($txr->getBlockHeight()) . "_" . ((string) $txr->getConfirmations())
                . "_" . $txr->getAddress();
            if (!isset($txRefHashes[$txRefHash])) {
                $txRefHashes[$txRefHash] = 1;
                $txrefs [] = $txr;
            } else {
                throw new \RuntimeException(
                    "Logic error in ListTransactions method, response contains same txref 
                    (server side, url:\"" . $url . "\", txrefhash: \"" . $txRefHash . "\", 
                    txref: ( " . serialize($txr) . " ) )."
                );
            }

            /*$filteredTxs = array_filter(
                $txrefs,
                function (TransactionReference $tx) use ($txr) {
                    return $tx->isEqual($txr);
                }
            );
            if (empty($filteredTxs)) {
                $txrefs [] = $txr;
            }*/
            //$txrefs [] = $txr;
        }
        $addrObject->setTxrefs($txrefs);
        if ($options && true !== $options->getNobalance()) {
            $v1 = gmp_init(strval($content["balance"] * 100 * 1000 * 1000));
            $addrObject->setBalance(new BTCValue($v1));
            $v2 = gmp_init(strval($content["unconfirmed_balance"] * 100 * 1000 * 1000));
            $addrObject->setUnconfirmedBalance(new BTCValue($v2));
            $addrObject->setFinalBalance(new BTCValue(gmp_add($v1, $v2)));
        }
        return $addrObject;
    }

    /**
     * {@inheritdoc}
     */
    public function gettransactions(array $txHashes)
    {
        if (empty($txHashes)) {
            throw new \InvalidArgumentException("txHashes variable must be non empty array of non empty strings.");
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "/gettransactions";
        $sep = "?";
        if ($this->token) {
            $url .= "?token=" . $this->token;
            $sep = "&";
        }
        foreach ($txHashes as $txHash) {
            if ((!is_string($txHash)) && ("" == $txHash)) {
                throw new \InvalidArgumentException("All hashes is \$txHashes array must be non empty strings.");
            }
            $url .= $sep . "txid[]=" . $txHash;
            $sep = "&";
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
        if (!isset($content["transactions"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"transactions\" property.",
                ["data" => $content]
            );
            throw new \RuntimeException(
                "Answer of url: \"" . $url . "\")  does not contain a \"transactions\" property."
            );
        }

        $txs = [];

        foreach ($content["transactions"] as $tr) {
            $tx = new Transaction;
            if (-1 != $tr["block_height"]) {
                $tx->setConfirmed(strtotime($tr["confirmed"]));
                $tx->setBlockHash($tr["block_hash"]);
                $tx->setBlockHeight($tr["block_height"]);
            } else {
                $tx->setBlockHeight(-1);
            }
            $tx->setHash($tr["hash"]);
            $tx->setDoubleSpend($tr["double_spend"]);
            $tx->setConfirmations($tr["confirmations"]);
            foreach ($tr["inputs"] as $inp) {
                $input = new TransactionInput();
                if (isset($inp["addresses"])) {
                    $input->setAddresses($inp["addresses"]);
                }
                if (isset($inp["output_index"])) {
                    $input->setOutputIndex($inp["output_index"]);
                }
                $v = gmp_init(strval($inp["value"] * 100 * 1000 * 1000));
                $input->setOutputValue(new BTCValue($v));
                $input->setScriptType($this->getTransformedTypeOfSignature($inp["script_type"]));
                $tx->addInput($input);
            }
            if (empty($tr["inputs"])) {
                //coinbase transaction - will create system input
                $input = new TransactionInput();
                $input->setOutputIndex(0);
                $input->setScriptType("coinbase");
                $divresult = floor($tr["block_height"] / 210000);
                $value = (50 * (pow(0.5, $divresult))) * 100 * 1000 * 1000;
                $input->setOutputValue(new BTCValue(gmp_init(strval($value))));
                $tx->addInput($input);
            }
            foreach ($tr["outputs"] as $outp) {
                $output = new TransactionOutput();
                $output->setAddresses($outp["addresses"]);
                $v = gmp_init(strval($outp["value"] * 100 * 1000 * 1000));
                $output->setValue(new BTCValue($v));
                $output->setScriptType($this->getTransformedTypeOfSignature($outp["script_type"]));
                $tx->addOutput($output);
            }
            $txs [] = $tx;
        }
        return $txs;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($walletName, $Confirmations = 1)
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
        $url = $this->getOption(self::OPT_BASE_URL) . "/getconfirmedbalance?accountId=" . $walletName
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
        $v = gmp_init(strval($content["balance"] * 100 * 1000 * 1000));
        return new BTCValue($v);
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
        $v = gmp_init(strval($content["balance"] * 100 * 1000 * 1000));
        return new BTCValue($v);
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($walletName, $MinimumConfirmations = 1)
    {
        if ("string" != gettype($walletName)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $action = ( 0 == $MinimumConfirmations ) ? "listunspentunconfirmed" : "listunspentconfirmed";
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $action . "?accountId=" . $walletName;
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        if ($MinimumConfirmations > 0) {
            $url .= "&confirmations=" . intval($MinimumConfirmations);
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
                "Error (code: " . $content["error"]["code"] . ") \""
                . $content['error']["message"] . "\" returned (url:\"" . $url . "\")."
            );
        }
        if (empty($content["unspents"])) {
            return [];
        }

        /** @var $result TransactionReference[] */
        $result = [];

        foreach ($content["unspents"] as $txref) {
            $txr = new TransactionReference();
            if (isset($txref["block_height"])) {
                $txr->setBlockHeight($txref["block_height"]);
            }
            $txr->setConfirmations($txref["confirmations"]);
            if (isset($txref["double_spend"])) {
                $txr->setDoubleSpend($txref["double_spend"]);
            }
            $txr->setSpent(false);
            $txr->setTxHash($txref["tx_hash"]);
            $txr->setVout($txref["tx_output_n"]);
            $txr->setCategory(TransactionReference::CATEGORY_RECEIVE);
            $v = gmp_init(strval($txref["value"] * 100 * 1000 * 1000));
            $txr->setValue(new BTCValue($v));
            if (isset($txref["address"])) {
                $txr->setAddress($txref['address']);
            }
            if (isset($txref["confirmed"])) {
                $txr->setConfirmed(strtotime($txref["confirmed"]));
            }
            $filteredTxs = array_filter(
                $result,
                function (TransactionReference $tx) use ($txr) {
                    return $tx->isEqual($txr);
                }
            );
            if (empty($filteredTxs)) {
                $result [] = $txr;
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($Transaction)
    {
        if ((!is_string($Transaction)) && (empty($Transaction))) {
            throw new \InvalidArgumentException("\$Transaction variable array must be non empty strings.");
        }

        //HUERAGA - может быть стоит проверять средствами bitwasp'а корректность транзы перед отправкой?
        $url = $this->getOption(self::OPT_BASE_URL) . "/sendrawtransaction?data=" . $Transaction;
        //$url = str_replace("/btcbridge","",$url); //HUERAGA
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }

        $curl = curl_init();
        $curl_options = [
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => $this->getOption(self::OPT_BASE_BROWSER),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new \RuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: " . serialize($curl_options) . ")."
            );
        }

        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        if (isset($content['error']) && (null != $content['error'])) {
            throw new \RuntimeException(
                "Error (code " . $content["error"]["code"] . ") \"" .
                $content['error']["message"] . "\" returned (url: \"" . $url . "\")."
            );
        }
        if (!isset($content['result'])) {
            throw new \RuntimeException(
                "Answer does not contain \"result\" field (url: \"" . $url . "\")."
            );
        }
        if (empty($content['result'])) {
            throw new \RuntimeException(
                "Answer contains empty \"result\" field (url: \"" . $url . "\")."
            );
        }
        return $content['result'];
    }

    /**
     * {@inheritdoc}
     */
    public function createWallet($walletName, array $addresses)
    {
        if ("string" != gettype($walletName)) {
            throw new \InvalidArgumentException("name variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name can't be empty and have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "/wallet/create?name=" . $walletName;
        $url = str_replace("/btcbridge", "", $url); //HUERAGA
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $post_data = [];
        if (count($addresses) > 0) {
            $post_data["addresses"] = [];
            foreach ($addresses as $address) {
                $post_data["addresses"] [] = $address;
                //$post_data["addresses"] [] = $address;
            }
        }
        $curl = curl_init();
        if (!curl_setopt($curl, CURLOPT_URL, $url)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_USERAGENT, $this->getOption(self::OPT_BASE_BROWSER))) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_POST, 1)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json'])) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($post_data))) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        $content = curl_exec($curl);
        if (false === $content) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\", post: \"" . $post_data . "\").");
        }
        $content = json_decode($content, true);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error (code " . $content["error"]["code"] . ") \""
                . $content['error']["message"] . "\" returned (url: \"" . $url . "\", post: \""
                . serialize($post_data) . "\")."
            );
        }
        if (!isset($content['name'])) {
            throw new \RuntimeException(
                "Answer does not contain \"name\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if (!isset($content['id'])) {
            throw new \RuntimeException(
                "Answer does not contain \"id\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        $wallet = new Wallet;
        $wallet->setName($content['name']);
        if (!isset($content['addresses'])) {
            $content["addresses"] = [];
        }
        $wallet->setAddresses($content["addresses"]);
        $wallet->setSystemDataByHandler($this->getHandlerName(), ["name" => $walletName, "id" => $content['id']]);
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function addAddresses(Wallet $wallet, array $addresses)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "System data of passed wallet is empty (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        $walletId = $walletSystemData["id"]; //HUERAGA - надо бы проверять все же, есть ли проперти id
        if (empty($addresses)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array.");
        }

        $url = $this->getOption(self::OPT_BASE_URL) . "/wallet/addaddresses?id=" . $walletId;
        $url = str_replace("/btcbridge", "", $url); //HUERAGA
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $post_data["addresses"] = [];
        foreach ($addresses as $address) {
            $post_data["addresses"] [] = $address;
        }

        $curl = curl_init();
        $curl_options = [
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => $this->getOption(self::OPT_BASE_BROWSER),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST           => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type:application/json'],
            CURLOPT_POSTFIELDS     => json_encode($post_data)
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new \RuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: "
                . serialize($curl_options) . ")."
            );
        }

        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error (code " . $content["error"]["code"] . ") \"" .
                $content['error']["message"] . "\" returned (url: \"" . $url . "\")."
            );
        }
        if (!isset($content['addresses'])) {
            $content['addresses'] = [];
        }
        $wallet->setAddresses($content['addresses']);
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAddresses(Wallet $wallet, array $addresses)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "System data of passed wallet is empty (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        $walletId = $walletSystemData["id"]; //HUERAGA - надо бы проверять все же, есть ли проперти id
        if (empty($addresses)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array.");
        }

        $url = $this->getOption(self::OPT_BASE_URL) . "/wallet/removeaddresses?id=" . $walletId;
        $url = str_replace("/btcbridge", "", $url); //HUERAGA
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $post_data["addresses"] = [];
        foreach ($addresses as $address) {
            if (!is_string($address) || empty($address)) {
                throw new \InvalidArgumentException("address variable must be non empty string.");
            }
            $post_data["addresses"] [] = $address;
        }

        $curl = curl_init();
        $curl_options = [
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => $this->getOption(self::OPT_BASE_BROWSER),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST           => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type:application/json'],
            CURLOPT_POSTFIELDS     => json_encode($post_data)
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new \RuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: " . serialize($curl_options) . ")."
            );
        }

        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error (code " . $content["error"]["code"] . ") \"" .
                $content['error']["message"] . "\" returned (url: \"" . $url . "\")."
            );
        }
        if (!isset($content['addresses'])) {
            $content['addresses'] = [];
        }
        $wallet->setAddresses($content['addresses']);
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWallet(Wallet $wallet)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "System data of passed wallet is empty (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        $walletId = $walletSystemData["id"];
        $url = $this->getOption(self::OPT_BASE_URL) . "/wallet/delete?id=" . intval($walletId);
        $url = str_replace("/btcbridge", "", $url); //HUERAGA
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $curl = curl_init();
        if (!curl_setopt($curl, CURLOPT_URL, $url)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_USERAGENT, $this->getOption(self::OPT_BASE_BROWSER))) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_POST, 1)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json'])) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        $content = curl_exec($curl);
        if (false === $content) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\".");
        }
        $content = json_decode($content, true);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error (code " . $content["error"]["code"] . ") \"" .
                $content['error']["message"] . "\" returned (url: \"" . $url . "\")."
            );
        }
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
        $walletId = $walletSystemData["id"];
        $url = $this->getOption(self::OPT_BASE_URL) . "/wallet/getaddresses?id=" . intval($walletId);
        $url = str_replace("/btcbridge", "", $url); //HUERAGA
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error (code " . $content["error"]["code"] . ") \"" .
                $content['error']["message"] . "\" returned (url: \"" . $url . "\")."
            );
        }
        if (!isset($content['addresses'])) {
            $content['addresses'] = [];
        }
        $wallet->setAddresses($content['addresses']);
        return $wallet->getAddresses();
    }

    /**
     * {@inheritdoc}
     */
    public function getWallets(GetWalletsOptions $options = null)
    {
        $url = $this->getOption(self::OPT_BASE_URL) . "/wallet/getwallets";
        $url = str_replace("/btcbridge", "", $url); //HUERAGA
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        if ($options) {
            if ($options->getNoaddresses()) {
                $url .= "nobalace=true";
            }
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        if (isset($content['error'])) {
            throw new \RuntimeException(
                "Error (code " . $content["error"]["code"] . ") \"" .
                $content['error']["message"] . "\" returned (url: \"" . $url . "\")."
            );
        }
        $ret = [];
        foreach ($content['wallets'] as $wallet) {
            $w = new Wallet();
            $w->setName($wallet["name"]);
            $w->setAddresses($wallet["addresses"]);
            $w->setSystemDataByHandler(
                $this->getHandlerName(),
                [
                    "name" => $wallet['name']
                    ,
                    "id" => intval($wallet['id'])
                ]
            );
            $ret [] = $w;
        }
        return $ret;
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
