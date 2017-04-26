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
use BTCBridge\Api\TransactionReference;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\Script;

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
        $this->setOption(self::OPT_BASE_URL, "https://api.blockcypher.com/v1/btc/main/");
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
     * Extract destination bitcoin-addresses from the multisig output
     *
     * @param string $script hex of ScriptPubKey of output, must be multisig
     *
     * @throws \InvalidArgumentException in case of error of this type
     * @throws \RuntimeException in case of error of this type
     *
     * @return \string addresses
     */

    private function extractAddressesFromMultisigScript($script)
    {
        if (("string" != gettype($script)) || ("" == $script)) {
            throw new \InvalidArgumentException("script variable must be non empty string.");
        }
        try {
            $objScript = ScriptFactory::fromHex($script);
        } catch (\InvalidArgumentException $ex) {
            throw new \InvalidArgumentException($ex->getMessage());
        }
        if (!$objScript instanceof Script) {
            throw new \InvalidArgumentException("Passed string is not valid hex of scriptPubkey hex (" . $script . ").");
        }
        //$decode = (new OutputClassifier())->decode($script);
        $solutions = null;
        $type = (new OutputClassifier())->classify($objScript,$solutions);
        if ( in_array($type,["multisig","nonstandard"]) ) {
            return []; //This moment we'll not elaborate this case
        } else {
            throw new \InvalidArgumentException(
                "Type of passed scriptPubKey is not multisig or nonstandard (" . $script . ")."
            );
        }

        /*
        if (("multisig" !== $type) && ("nonstandard" !== $type)) {
            throw new \InvalidArgumentException("Type of signature of passed scriptPubkey (" . $script . ") is not multisig.");
        }
        if (!is_array($solutions) || empty($solutions)) {
            throw new \InvalidArgumentException("Incorrect solutions of passed scriptPubkey (" . $script . ").");
        }
        $addresses = [];
        foreach ($solutions as $solution) {
            try {
                $addr =  PublicKeyFactory::fromHex($solution)->getAddress();
                $addresses [] = $addr->getAddress();
            } catch (\Exception $ex) {
                throw new \InvalidArgumentException("Passed scriptPubkey does not ontain valid addresses (" . $script . ").");
            }
        }
        return $addresses;
        */
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
        $url = $this->getOption(self::OPT_BASE_URL) . "addrs/" . $address;
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
                $txr->setConfirmed($txref["confirmed"]);
                $txr->setTxHash($txref["tx_hash"]);
                $txr->setTxInputN($txref["tx_input_n"]);
                $txr->setTxOutputN($txref["tx_output_n"]);
                $txr->setValue($txref["value"]);
                if (isset($txref['address'])) {
                    $txr->setAddress($content['address']);
                } else {
                    $txr->setAddress($address);
                }
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
                if (isset($txref['address'])) {
                    $txr->setAddress($content['address']);
                } else {
                    $txr->setAddress($address);
                }
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

        $url = $this->getOption(self::OPT_BASE_URL) . "txs/" . $TXHASH;

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
        if (isset($content["block_hash"])) {
            $tx->setBlockHash($content["block_hash"]);
        }
        $tx->setBlockHeight($content["block_height"]);
        $tx->setHash($content["hash"]);
        $tx->setAddresses($content["addresses"]);
        if (isset($content["confirmed"])) {
            $tx->setConfirmed(strtotime($content["confirmed"]));
        }
        $tx->setLockTime($content["lock_time"]);
        $tx->setDoubleSpend($content["double_spend"]);
        $tx->setVoutSz($content["vout_sz"]);
        $tx->setVinSz($content["vin_sz"]);
        $tx->setConfirmations($content["confirmations"]);
        foreach ($content["inputs"] as $inp) { //20 штук по дефолту выдаётся, надо, чтобы все
            $input = new TransactionInput();
            $input->setAddresses((isset($inp["addresses"]) && (null!==$inp["addresses"]))?$inp["addresses"]:[]);
            if (isset($inp["prev_hash"])) {
                $input->setPrevHash($inp["prev_hash"]);
            }
            if (isset($inp["output_index"])) {
                $input->setOutputIndex($inp["output_index"]);
            }
            $val = gmp_init(strval($inp["output_value"]));
            $input->setOutputValue($val);
            $options = [];
            if ( $input->getOutputIndex() == -1 ) {
                $options["newlyminted"] = true;
            }
            $input->setScriptType($this->getTransformedTypeOfSignature($inp["script_type"], $options));
            $tx->addInput($input);
        }
        foreach ($content["outputs"] as $outp) {
            $output = new TransactionOutput();
            $output->setAddresses((isset($outp["addresses"]) && (null!==$outp["addresses"]))?$outp["addresses"]:[]);
            $output->setValue(gmp_init(strval($outp["value"])));
            if ("pay-to-multi-pubkey-hash" == $outp["script_type"]) {
                //TODO after fixing bug in bit-wasp with the multisig/nonstandard
                /** @noinspection PhpUnusedLocalVariableInspection */
                $multisigAddresses = $this->extractAddressesFromMultisigScript($outp["script"]);
                /*$previousOutputAddresses = $output->getAddresses();
                $output->setAddresses($multisigAddresses);
                $txAddresses = $tx->getAddresses();
                if (!empty($previousOutputAddresses)) {
                    $tx->setAddresses(array_diff($txAddresses, $previousOutputAddresses));
                    $txAddresses = $tx->getAddresses();
                }
                if ( !empty($multisigAddresses) ) {
                    foreach ( $multisigAddresses as $addr ) {
                        if ( !in_array($addr,$txAddresses) ) {
                            $txAddresses [] = $addr;
                        }
                    }
                    $tx->setAddresses($txAddresses);
                }*/
            }
            $options = [];
            $script_type = $this->getTransformedTypeOfSignature($outp["script_type"], $options);
            $output->setScriptType($script_type);
            $tx->addOutput($output);
        }
        return $tx;
    }

    /**
     * {@inheritdoc}
     */
    public function gettransactions(array $txHashes, array $options = array())
    {
        if (empty($txHashes)) {
            throw new \InvalidArgumentException("txHashes variable must be non empty array of non empty strings.");
        }

        $urls = [];

        foreach ( $txHashes as $txHash ) {
            if ((!is_string($txHash)) && (""==$txHash)) {
                throw new \InvalidArgumentException("All hashes is \$txHashes array must be non empty strings.");
            }

            $url = $this->getOption(self::OPT_BASE_URL) . "txs/" . $txHash;

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
            $urls[$url] = [];
            $urls[$url]["ch"] = "";
        }

        $multi = curl_multi_init();
        $allResults = [];
        foreach ($urls as $url => $urlData) {
            $ch = curl_init();
            $this->prepareCurl($ch, $url);
            //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_multi_add_handle($multi, $ch);
            $urls[$url]["ch"] = $ch;
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            # for php 7+
//            if (curl_multi_select($multi) == -1) {
//                continue;
//            }
            # For php 5.6
            if (curl_multi_select($multi) == -1) {
                usleep(500);
            }
            do {
                $mrc = curl_multi_exec($multi, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }


        //Пока все соединения не отработают
        //do { curl_multi_exec($multi,$active); } while ($active);

        foreach ($urls as $url => $urlData) {
            $content = curl_multi_getcontent($urlData['ch']);
            if ( (!$content) || (""==$content) ) {
                curl_multi_close($multi);
                throw new \RuntimeException("curl did not return content (url:\"" . $url . "\")");
            }
            $content = json_decode($content, true);
            if ((false === $content) || (null === $content)) {
                curl_multi_close($multi); //???
                throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
            }
            $allResults [] = ["url" => $url, "content" => $content];
            curl_multi_remove_handle($multi, $urlData['ch']);
        }
        curl_multi_close($multi);

        $txs = [];

        foreach ($allResults as $result) {
            $url = &$result["url"];
            $content = &$result["content"];
            if (isset($content['error'])) {
                throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
            }
            $tx = new Transaction;
            if (isset($content["block_hash"])) {
                $tx->setBlockHash($content["block_hash"]);
            }
            $tx->setBlockHeight($content["block_height"]);
            $tx->setHash($content["hash"]);
            $tx->setAddresses($content["addresses"]);
            if (isset($content["confirmed"])) {
                $tx->setConfirmed(strtotime($content["confirmed"]));
            }
            $tx->setLockTime($content["lock_time"]);
            $tx->setDoubleSpend($content["double_spend"]);
            $tx->setVoutSz($content["vout_sz"]);
            $tx->setVinSz($content["vin_sz"]);
            $tx->setConfirmations($content["confirmations"]);
            foreach ($content["inputs"] as $inp) { //20 штук по дефолту выдаётся, надо, чтобы все
                $input = new TransactionInput();
                $input->setAddresses((isset($inp["addresses"]) && (null!==$inp["addresses"]))?$inp["addresses"]:[]);
                if (isset($inp["prev_hash"])) {
                    $input->setPrevHash($inp["prev_hash"]);
                }
                if (isset($inp["output_index"])) {
                    $input->setOutputIndex($inp["output_index"]);
                }
                $val = gmp_init(strval($inp["output_value"]));
                $input->setOutputValue($val);
                $options = [];
                if ( $input->getOutputIndex() == -1 ) {
                    $options["newlyminted"] = true;
                }
                $input->setScriptType($this->getTransformedTypeOfSignature($inp["script_type"], $options));
                $tx->addInput($input);
            }
            foreach ($content["outputs"] as $outp) {
                $output = new TransactionOutput();
                $output->setAddresses((isset($outp["addresses"]) && (null!==$outp["addresses"]))?$outp["addresses"]:[]);
                $output->setValue(gmp_init(strval($outp["value"])));
                if ("pay-to-multi-pubkey-hash" == $outp["script_type"]) {
                    //TODO after fixing bug in bit-wasp with the multisig/nonstandard
                    /** @noinspection PhpUnusedLocalVariableInspection */
                    $multisigAddresses = $this->extractAddressesFromMultisigScript($outp["script"]);
                    /*$previousOutputAddresses = $output->getAddresses();
                    $output->setAddresses($multisigAddresses);
                    $txAddresses = $tx->getAddresses();
                    if (!empty($previousOutputAddresses)) {
                        $tx->setAddresses(array_diff($txAddresses, $previousOutputAddresses));
                        $txAddresses = $tx->getAddresses();
                    }
                    if ( !empty($multisigAddresses) ) {
                        foreach ( $multisigAddresses as $addr ) {
                            if ( !in_array($addr,$txAddresses) ) {
                                $txAddresses [] = $addr;
                            }
                        }
                        $tx->setAddresses($txAddresses);
                    }*/
                }
                $options = [];
                $script_type = $this->getTransformedTypeOfSignature($outp["script_type"], $options);
                $output->setScriptType($script_type);
                $tx->addOutput($output);
            }
            $txs [] = $tx;
        }
        return $txs;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($walletName, $Confirmations = 1, $IncludeWatchOnly = false)
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
        $url = $this->getOption(self::OPT_BASE_URL) . "addrs/" . $walletName;
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
        $url = $this->getOption(self::OPT_BASE_URL) . "addrs/" . $walletName;
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
        $url = $this->getOption(self::OPT_BASE_URL) . "addrs/" . $walletName;
        if ($this->token) {
            $url .= "?token=" . $this->token . "&";
        } else {
            $url .= "?";
        }
        $url .= "&unspentOnly=true&confirmations=" . intval($MinimumConfirmations);
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

        /** @var $result TransactionReference[] */
        $result = [];

        if (0 == $MinimumConfirmations) {
            if (isset($content["unconfirmed_txrefs"])) {
                foreach ($content["unconfirmed_txrefs"] as $rec) {
                    if (intval($rec['tx_output_n']) < 0) {
                        //according to https://www.blockcypher.com/dev/bitcoin/?shell#txref
                        //if tx_output_n is negative then this is input, we look for outputs only
                        continue;
                    }
                    $txr = new TransactionReference();
                    $txr->setBlockHeight($rec["block_height"]);
                    $txr->setConfirmations($rec["confirmations"]);
                    $txr->setDoubleSpend($rec["double_spend"]);
                    $txr->setSpent($rec["spent"]);
                    $txr->setTxHash($rec["tx_hash"]);
                    $txr->setTxInputN($rec["tx_input_n"]);
                    $txr->setTxOutputN($rec["tx_output_n"]);
                    $txr->setValue($rec["value"]);
                    $txr->setAddress($rec['address']);
                    $filteredTxs = array_filter($result, function (TransactionReference $tx) use ($txr) {
                            return $tx->isEqual($txr);
                    });
                    if (empty($filteredTxs)) {
                        $result [] = $txr;
                    }
                }
            }
            return $result;
        }

        if (isset($content["txrefs"])) {
            foreach ($content["txrefs"] as $txref) {
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
                if ( isset($txref["address"]) ) {
                    $txr->setAddress($txref['address']);
                }
                $txr->setConfirmed(strtotime($txref["confirmed"]));
                $filteredTxs = array_filter($result, function (TransactionReference $tx) use ($txr) {
                        return $tx->isEqual($txr);
                });
                if (empty($filteredTxs)) {
                    $result [] = $txr;
                }
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($Transaction)
    {
        $url = $this->getOption(self::OPT_BASE_URL) . "txs/push";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $post_data = '{"tx":"' . $Transaction . '"}';
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
        $url = $this->getOption(self::OPT_BASE_URL) . "wallets";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $post_data = ["name" => $walletName];
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
        if (!isset($content['addresses'])) {
            $content["addresses"] = [];
        }
        $wallet->setAddresses($content["addresses"]);
        $wallet->setSystemDataByHandler($this->getHandlerName(), ["name"=>$walletName]);
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
                "No handlers data (\"" . $this->getHandlerName()
                . "\") in the passed wallet ( " . serialize($wallet) . ")."
            );
        }
        $walletName = $walletSystemData["name"];
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name cant't be empty and have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if (empty($addresses)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array.");
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "wallets/" . $walletName . "/addresses";
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
        if ((false === $content) || (null === $content)) {
            throw new \RuntimeException(
                "curl error occured (url:\"" . $url . "\", post: \"" . serialize($post_data) . "\")."
            );
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
                "Answer does not contain \"tx\" field (url:\"" . $url . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if (!isset($content['addresses'])) {
            throw new \RuntimeException(
                "Answer does not contain \"addresses\" field (url:\"" . $url . "\", post: \"" . $post_data . "\")."
            );
        }
        $wallet->setAddresses($content["addresses"]);
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAddresses(Wallet $wallet, array $addresses)
    {
        if (empty($addresses)) {
            throw new \InvalidArgumentException("addresses variable must be non empty array of strings.");
        }
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if (!$walletSystemData) {
            throw new \InvalidArgumentException(
                "No handlers data (\"" . $this->getHandlerName()
                . "\") in the passed wallet ( " . serialize($wallet) . ")."
            );
        }
        $walletName = $walletSystemData["name"];
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name cant't be empty and have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }

        $post_data = [];
        $post_data["name"] = $walletName;
        $post_data["addresses"] = [];
        foreach ($addresses as $address) {
            if (!is_string($address) || empty($address)) {
                throw new \InvalidArgumentException("address variable must be non empty string.");
            }
            $post_data["addresses"] [] = $address;

        }

        $url = $this->getOption(self::OPT_BASE_URL) . "wallets/" . $walletName . "/addresses";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }

        $curl = curl_init();
        $curl_options = [
            CURLOPT_URL            => $url,
            CURLOPT_USERAGENT      => $this->getOption(self::OPT_BASE_BROWSER),
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_CUSTOMREQUEST  => "DELETE",
            CURLOPT_POST           => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type:application/json'],
            CURLOPT_POSTFIELDS     => json_encode($post_data)
        ];
        if ( FALSE === curl_setopt_array($curl, $curl_options)) {
            throw new \RuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: " . serialize($curl_options) . ")."
            );
        }

        $content = curl_exec($curl);
        if (false === $content) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\".");
        }
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if (204 !== $httpCode) {
            throw new \RuntimeException(
                "curl query does not return error occured (url:\"" . $url . "\", httpcode =  " . $httpCode . ".)"
            );
        }
        $wallet->setAddresses(array_diff($wallet->getAddresses(), $addresses));
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
        $walletName = $walletSystemData["name"];
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name can't be empty and have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "wallets/" . $walletName;
        if ($this->token) {
            $url .= "?token=" . $this->token;
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
        if (!curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE")) {
            throw new \RuntimeException("curl_setopt failed url:\"" . $url . "\").");
        }
        $content = curl_exec($curl);
        if (false === $content) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\".");
        }
        $content = json_decode($content, true);
        if (/*(null === $content) || */(false === $content)) {
            throw new \RuntimeException("curl does not return an awaiting value (url:\"" . $url . "\").");
        }
        if (isset($content['error'])) {
            if ("Error: wallet not found" != $content['error']) {
                throw new \RuntimeException(
                    "Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\"."
                );
            }
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
        $walletName = $walletSystemData["name"];
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new \InvalidArgumentException(
                "Wallet name can't be empty and have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $url = $this->getOption(self::OPT_BASE_URL) . "wallets/" . $walletName;
        /** @noinspection PhpUnusedLocalVariableInspection */
        $sep = "?";
        if ($this->token) {
            $url .= "?token=" . $this->token;
            /** @noinspection PhpUnusedLocalVariableInspection */
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
            throw new \RuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
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
    public function getTransformedTypeOfSignature($type, array $options = [])
    {
        switch ($type) {
            case "pay-to-multi-pubkey-hash":
                return "multisig"; break;
            case "pay-to-pubkey-hash":
                return "pubkeyhash"; break;
            case "pay-to-pubkey":
                return "pubkey"; break;
            case "empty": {
                if ( isset($options["newlyminted"]) ) {
                    return "pubkey"; break;
                }
                return "nonstandard"; break;
            }
            case "null-data":
                return "nulldata"; break;
            case "pay-to-script-hash":
                return "scripthash"; break;
            default:
                return $type;
                break;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHandlerName()
    {
        return "blockcypher.com";
    }
}
