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
use BTCBridge\Api\TransactionInput;
use BTCBridge\Api\TransactionOutput;
use BTCBridge\Api\Address;
use BTCBridge\Api\Wallet;
use \BTCBridge\Api\TransactionReference;

/**
 * Returns data to user's btc-requests using BlockCypher-API
 * @author Matbea <mail@matbea.com>
 */
class BlockCypherHandler extends AbstractHandler
{

    protected $token = "";

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->setOption("base_url", "https://api.blockcypher.com/v1/btc/main/");
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
     * Prepare curl descriptor for querying
     *
     * @param resource $curl A reference to the curl onjet
     * @param string $url An url address for connecting
     *
     * @throws \RuntimeException in case of any curl error
     */
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
        //$unspentOnly=false,$includeScript=false,$includeConfidence=false,$before=NULL,$after=NULL,$limit=200,$confirmations=NULL,$confidence=NULL,$omitWalletAddresses=false
        if ("string" != gettype($address) || ("" == $address)) {
            throw new \InvalidArgumentException("address variable must be non empty string.");
        }
        $url = $this->getOption("base_url") . "addrs/" . $address;
        $sep = "?";
        if ($this->token) {
            $url .= "?token=" . $this->token;
            $sep = "&";
        }

        if (array_key_exists('unspentOnly', $options) && (true === $options['unspentOnly'])) {
            $url .= $sep . "unspentOnly=true";
            $sep = "&";
        }
        if (array_key_exists('includeScript', $options) && (true === $options['includeScript'])) {
            $url .= $sep . "includeScript=true";
            $sep = "&";
        }
        if (array_key_exists('includeConfidence', $options) && (true === $options['includeConfidence'])) {
            $url .= $sep . "includeConfidence=true";
            $sep = "&";
        }
        if (array_key_exists('before', $options) && (null !== $options['before'])) {
            $url .= $sep . "before=" . $options['before'];
            $sep = "&";
        }
        if (array_key_exists('after', $options) && (null !== $options['after'])) {
            $url .= $sep . "after=" . $options['after'];
            $sep = "&";
        }
        if (array_key_exists('limit', $options) && (200 !== $options['limit'])) {
            $url .= $sep . "limit=" . $options['limit'];
            $sep = "&";
        }
        if (array_key_exists('confirmations', $options) && (null !== $options['confirmations'])) {
            $url .= $sep . "confirmations=" . $options['confirmations'];
            $sep = "&";
        }
        if (array_key_exists('confidence', $options) && (null !== $options['confidence'])) {
            $url .= $sep . "confidence=" . $options['$confidence'];
            $sep = "&";
        }
        if (array_key_exists('omitWalletAddresses', $options) && (true !== $options['omitWalletAddresses'])) {
            $url .= $sep . "omitWalletAddresses=true";
        }

        $awaiting_params = [
            'unspentOnly',
            'includeScript',
            'includeConfidence',
            'before',
            'after',
            'limit',
            'confirmations',
            'confidence',
            'omitWalletAddresses'
        ];

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
            throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
        }
        $addrObject = new Address();
        if (isset($content['address'])) {
            $addrObject->setAddress($content['address']);
        } else {
            if (isset($content['wallet'])) {
                $wallet = new Wallet();
                $wallet->setAddresses($content['wallet']['addresses']);
                $wallet->setName($content['wallet']['name']);
                $addrObject->setWallet($wallet);
            }
        }
        $addrObject->setBalance($content["balance"]);
        $addrObject->setUnconfirmedBalance($content["unconfirmed_balance"]);
        $addrObject->setFinalBalance($content["final_balance"]);
        if (isset($content["txrefs"])) {
            foreach ($content["txrefs"] as $txref) {
                $txr = new TransactionReference();
                $txr->setBlockHeight($txref["block_height"]);
                $txr->setConfirmations($txref["confirmations"]);
                $txr->setDoubleSpend($txref["double_spend"]);
                $txr->setSpent($txref["spent"]);
                $txr->setTxHash($txref["tx_hash"]);
                $txr->setTxInputN($txref["tx_input_n"]);
                $txr->setTxOutputN($txref["tx_output_n"]);
                $txr->setValue($txref["value"]);
                $addrObject->addTxref($txr);
            }
        }
        if (isset($content["unconfirmed_txrefs"])) {
            foreach ($content["unconfirmed_txrefs"] as $txref) {
                $txr = new TransactionReference();
                $txr->setBlockHeight($txref["block_height"]);
                $txr->setConfirmations($txref["confirmations"]);
                $txr->setDoubleSpend($txref["double_spend"]);
                $txr->setSpent($txref["spent"]);
                $txr->setTxHash($txref["tx_hash"]);
                $txr->setTxInputN($txref["tx_input_n"]);
                $txr->setTxOutputN($txref["tx_output_n"]);
                $txr->setValue($txref["value"]);
                $addrObject->addUnconfirmedTxref($txr);
            }
        }
        return $addrObject;
    }

    /**
     * {@inheritdoc}
     */
    public function gettransaction($TXHASH, array $options = array())
    {
        if (("string" != gettype($TXHASH)) || ("" == $TXHASH)) {
            throw new \InvalidArgumentException("TXHASH variable must be non empty string.");
        }

        $url = $this->getOption("base_url") . "txs/" . $TXHASH;

        $sep = "?";
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
            throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
        }
        $tx = new Transaction;
        $tx->setBlockHash($content["block_hash"]);
        $tx->setBlockHeight($content["block_height"]);
        $tx->setHash($content["hash"]);
        $tx->setAddresses($content["addresses"]);
        $tx->setConfirmed($content["confirmed"]);
        $tx->setLockTime($content["lock_time"]);
        $tx->setDoubleSpend($content["double_spend"]);
        $tx->setVoutSz($content["vout_sz"]);
        $tx->setVinSz($content["vin_sz"]);
        $tx->setConfirmations($content["confirmations"]);
        foreach ($content["inputs"] as $inp) { //HUERAGA - вроде как 20 штук по дефолту выдаётся, надо, чтобы все
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
            $input->setOutputValue($inp["output_value"]);
            $input->setScriptType($inp["script_type"]);
            $tx->addInput($input);
        }
        foreach ($content["outputs"] as $outp) {
            $output = new TransactionOutput();
            $output->setAddresses($outp["addresses"]);
            $output->setValue($outp["value"]);
            $output->setScriptType($outp["script_type"]);
            $tx->addOutput($output);
        }
        return $tx;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($Account, $Confirmations = 1, $IncludeWatchOnly = false)
    {
        if ("string" != gettype($Account) || ("" == $Account)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        $url = $this->getOption("base_url") . "addrs/" . $Account;
        if ($this->token) {
            $url .= "?token=" . $this->token . "&confirmations=" . $Confirmations;
        } else {
            $url .= "?confirmations=" . $Confirmations;
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
            throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
        }
        if (!isset($content["balance"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.",
                ["data" => $content]
            );
            throw new \RuntimeException("Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.");
        }
        return intval($content["balance"]);
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($Account)
    {
        if ("string" != gettype($Account) || ("" == $Account)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }

        $url = $this->getOption("base_url") . "addrs/" . $Account;
        if ($this->token) {
            $url .= "?token=" . $this->token;
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
            throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
        }
        if (!isset($content["unconfirmed_balance"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"unconfirmed_balance\" field.",
                ["data" => $content]
            );
            throw new \RuntimeException(
                "Answer of url: \"" . $url . "\")  does not contain a \"unconfirmed_balance\" field."
            );
        }
        return intval($content["unconfirmed_balance"]);
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($Account, $MinimumConfirmations = 1)
    {
        if ("string" != gettype($Account) || ("" == $Account)) {
            throw new \InvalidArgumentException("Account variable must be non empty string.");
        }
        $url = $this->getOption("base_url") . "addrs/" . $Account;
        if ($this->token) {
            $url .= "?token=" . $this->token . "&";
        } else {
            $url .= "?";
        }
        $url .= "&unspentOnly=true&confirmations=" . $MinimumConfirmations;
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
            throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
        }
        if ((!isset($content["txrefs"])) && (!isset($content["unconfirmed_txrefs"]))) {
            return [];
        }

        $necessary_fields = ['tx_output_n', 'tx_hash', 'value', 'confirmations'];

        $result = [];

        if ((0 == $MinimumConfirmations) && isset($content["unconfirmed_txrefs"])) {
            foreach ($content["unconfirmed_txrefs"] as $rec) {
                foreach ($necessary_fields as $f) {
                    if (!isset($rec[$f])) {
                        throw new \RuntimeException(
                            "Item of unconfirmed_txrefs array does not contain \""
                            . $f . "\" field (url:\"" . $url . "\")."
                        );
                    }
                }
                if (intval($rec['tx_output_n']) < 0) {
                    //according to https://www.blockcypher.com/dev/bitcoin/?shell#txref
                    //if tx_output_n is negative then this is input, we look for outputs only
                    continue;
                }
                $item = [];
                $item["txid"] = $rec['tx_hash'];
                $item["vout"] = $rec['tx_output_n'];
                $item["address"] = $Account;
                $item["scriptPubey"] = "demo"; //HUERAGA
                $item["amount"] = floatval($rec['value']) / 100000000;
                $item["confirmations"] = $rec['confirmations'];
                $item["spendable"] = false;
                $result [] = $item;
            }
        }

        if (isset($content["txrefs"])) {
            foreach ($content["txrefs"] as $txref) {
                foreach ($necessary_fields as $f) {
                    if (!isset($txref[$f])) {
                        throw new \RuntimeException(
                            "Item of txrefs array does not contain \"" . $f . "\" field (url:\"" . $url . "\")."
                        );
                    }
                }
                if (intval($txref['tx_output_n']) < 0) {
                    //according to https://www.blockcypher.com/dev/bitcoin/?shell#txref
                    //if tx_output_n is negative then this is input, we look for outputs only
                    continue;
                }


                $txr = new TransactionReference();
                $txr->setBlockHeight($txref["block_height"]);
                $txr->setConfirmations($txref["confirmations"]);
                $txr->setDoubleSpend($txref["double_spend"]);
                $txr->setSpent($txref["spent"]);
                $txr->setTxHash($txref["tx_hash"]);
                $txr->setTxInputN($txref["tx_input_n"]);
                $txr->setTxOutputN($txref["tx_output_n"]);
                $txr->setValue($txref["value"]);
                $result [] = $txr;

                /*$item = [];
                $item["txid"] = $rec['tx_hash'];
                $item["vout"] = $rec['tx_output_n'];
                $item["address"] = $Account;
                $item["scriptPubkey"] = "demo"; //HUERAGA
                $item["amount"] = floatval($rec['value']) / 100000000;
                $item["confirmations"] = intval($rec['confirmations']);
                $item["spendable"] = false;
                $result [] = $item;*/
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($Transaction)
    {
        $url = $this->getOption("base_url") . "txs/push";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $post_data = '{"tx":"' . $Transaction . '"}';
        $curl = curl_init();
        if (!curl_setopt($curl, CURLOPT_URL, $url)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_USERAGENT, $this->getOption("browser"))) {
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
        if (!curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data)) {
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
                "Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\", post: \"" . $post_data . "\")."
            );
        }
        if (!isset($content['tx'])) {
            throw new \RuntimeException(
                "Answer does not contain \"tx\" field (url:\"" . $url . "\", post: \"" . $post_data . "\")."
            );
        }
        if (!isset($content['tx']['hash'])) {
            throw new \RuntimeException(
                "Answer does not contain \"hash\" field in \"tx\" array (url:\""
                . $url . "\", post: \"" . $post_data . "\")."
            );
        }
        return $content['tx']['hash'];
    }

    /**
     * {@inheritdoc}
     */
    public function createwallet($name, $addresses)
    {
        if ("string" != gettype($name) || ("" == $name)) {
            throw new \InvalidArgumentException("name variable must be non empty string.");
        }
        if (!is_array($addresses)) {
            throw new \InvalidArgumentException("addresses variable must be the array.");
        }
        $url = $this->getOption("base_url") . "wallets";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $post_data = ["name" => $name];
        if (count($addresses) > 0) {
            $post_data["addresses"] = [];
            foreach ($addresses as $address) {
                $post_data["addresses"] [] = $address;
            }
        }
        $curl = curl_init();
        if (!curl_setopt($curl, CURLOPT_URL, $url)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_USERAGENT, $this->getOption("browser"))) {
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
                "Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\", post: \""
                . json_encode($post_data) . "\")."
            );
        }
        if (!isset($content['name'])) {
            throw new \RuntimeException(
                "Answer does not contain \"tx\" field (url:\"" . $url
                . "\", post: \"" . $post_data . "\")."
            );
        }
        $wallet = new Wallet;
        $wallet->setName($content['name']);
        $wallet->setAddresses($content["addresses"]);
        if (isset($content["token"])) {
            $wallet->setToken($content["token"]);
        }
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function addaddresses($name, $addresses)
    {
        if ("string" != gettype($name) || ("" == $name)) {
            throw new \InvalidArgumentException("name variable must be non empty string.");
        }
        if ((!is_array($addresses)) || (count($addresses) == 0)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array.");
        }
        $url = $this->getOption("base_url") . "wallets/" . $name . "/addresses";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $post_data["addresses"] = [];
        foreach ($addresses as $address) {
            $post_data["addresses"] [] = $address;
        }
        $curl = curl_init();
        if (!curl_setopt($curl, CURLOPT_URL, $url)) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        if (!curl_setopt($curl, CURLOPT_USERAGENT, $this->getOption("browser"))) {
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
                "Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\", post: \""
                . json_encode($post_data) . "\")."
            );
        }
        if (!isset($content['name'])) {
            throw new \RuntimeException(
                "Answer does not contain \"tx\" field (url:\"" . $url . "\", post: \"" . $post_data . "\")."
            );
        }
        $wallet = new Wallet;
        $wallet->setName($content['name']);
        $wallet->setAddresses($content["addresses"]);
        if (isset($content["token"])) {
            $wallet->setToken($content["token"]);
        }
        return $wallet;
    }
}
