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
use BTCBridge\Api\Wallet;
use BTCBridge\Api\BTCValue;

//use BitWasp\Bitcoin\Script\ScriptFactory;
//use BitWasp\Bitcoin\Script\Classifier\OutputClassifier;
//use BitWasp\Bitcoin\Key\PublicKeyFactory;
//use BitWasp\Bitcoin\Script\Script;
//use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key\PublicKey;
//use BitWasp\Bitcoin\Script\Opcodes;

/**
 * Returns data to user's btc-requests using BlockTrail-API
 * @author Matbea <mail@matbea.com>
 */
class BlockTrailHandler extends AbstractHandler
{
    protected $token = "";

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->setOption(self::OPT_BASE_URL, "https://api.blocktrail.com/v1/btc/");
    }

    /**
     * Setting token to the handler
     *
     * @param string $token An token for accessing to the blocktrail data
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
    public function listtransactions($walletName, array $options = [])
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function gettransactions(array $txHashes, array $options = [])
    {
        if (empty($txHashes)) {
            throw new \InvalidArgumentException("txHashes variable must be non empty array of non empty strings.");
        }
        $maxCountOfTransactions = 20;
        if (count($txHashes) > $maxCountOfTransactions) {
            throw new \InvalidArgumentException(
                "txHashes variable size must be non bigger than " . $maxCountOfTransactions . "."
            );
        }

        foreach ($txHashes as $txHash) {
            if ((!is_string($txHash)) && (""==$txHash)) {
                throw new \InvalidArgumentException(
                    "All hashes is \$txHashes array must be non empty strings."
                );
            }
        }

        $url = $this->getOption(self::OPT_BASE_URL) . "transactions/" . implode(",", $txHashes);
        if ($this->token) {
            $url .= "?api_key=" . $this->token;
        }

        /*$sep = "?";
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
        }*/

        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $fullContent = curl_exec($ch);
        if (false === $fullContent) {
            throw new \RuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $fullContent = json_decode($fullContent, true);
        if ((false === $fullContent) || (null === $fullContent)) {
            throw new \RuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($fullContent['msg'])) {
            throw new \RuntimeException(
                "Error \"" . $fullContent['msg'] . "\" (code: " . $fullContent["code"] .
                ") returned (url:\"" . $url . "\")."
            );
        }
        if (!isset($fullContent['data']) || !is_array($fullContent['data'])) {
            throw new \RuntimeException(
                "Returned array from url \"" . $url .
                "\" does not contain \"data\" field or \"data\" field is not array."
            );
        }

        $txs = array_fill(0, count($txHashes), null);
        foreach ($fullContent['data'] as $content) {
            if (isset($content['msg'])) {
                throw new \RuntimeException(
                    "Error \"" . $content['msg'] . "\" (code: " .
                    $content["code"] . ") returned (url:\"" . $url . "\")."
                );
            }
            $tx = new Transaction;
            if (null === $content["block_height"]) {
                $tx->setBlockHeight(-1);
            } else {
                $tx->setBlockHeight($content["block_height"]);
            }
            if (null !== $content["block_hash"]) {
                $tx->setBlockHash($content["block_hash"]);
            }
            $tx->setHash($content["hash"]);
            //$tx->setAddresses($content["addresses"]);
            if (null !== $content["block_time"]) {
                $tx->setConfirmed(strtotime($content["block_time"]));
            }
            //$tx->setLockTime($content["lock_time"]);
            $tx->setDoubleSpend($content["is_double_spend"]);
            //$tx->setVoutSz($content["vout_sz"]);
            //$tx->setVinSz($content["vin_sz"]);
            $tx->setConfirmations($content["confirmations"]);
            foreach ($content["inputs"] as $inp) { //20 штук по дефолту выдаётся, надо, чтобы все
                $input = new TransactionInput();
                switch ($inp["type"]) {
                    case "coinbase":
                        $input->setAddresses([]);
                        break;
                    case "multisig":
                        $input->setAddresses($inp["multisig_addresses"]);
                        break;
                    default:
                        if (null === $inp["address"]) {
                            throw new \RuntimeException(
                                "Error: type of input: \"" . $inp["type"] .
                                "\", address is null (url:\"" . $url . "\")."
                            );
                        }
                        $input->setAddresses([$inp["address"]]);
                        break;
                };

                /*$input->setAddresses((isset($inp["addresses"]) && (null!==$inp["addresses"]))?$inp["addresses"]:[]);
                if (isset($inp["prev_hash"])) {
                    $input->setPrevHash($inp["prev_hash"]);
                }*/
                if (isset($inp["output_index"])) {
                    $input->setOutputIndex($inp["output_index"]);
                }
                $val = gmp_init(strval($inp["value"]));
                $input->setOutputValue(new BTCValue($val));
                //$options = [];
                /*if ($input->getOutputIndex() == -1) {
                    $options["newlyminted"] = true;
                }
                $input->setScriptType($this->getTransformedTypeOfSignature($inp["script_type"], $options));*/
                $input->setScriptType($inp["type"]);
                $tx->addInput($input);
            }
            foreach ($content["outputs"] as $outp) {
                $output = new TransactionOutput();
                if ("multisig" == $outp["type"]) {
                    $output->setAddresses($outp["multisig_addresses"]);
                } elseif ("op_return" == $outp["type"]) {
                    $output->setAddresses([]);
                } else {
                    $output->setAddresses([$outp["address"]]);
                }
                $v = gmp_init(strval($outp["value"]));
                $output->setValue(new BTCValue($v));
                //$options = [];
                //$script_type = $this->getTransformedTypeOfSignature($outp["script_type"], $options);
                $output->setScriptType($outp["type"]);
                if ("op_return" == $outp["type"]) {
                    $output->setScriptType("nulldata");
                }
                $tx->addOutput($output);
            }
            $index = array_search($content["hash"], $txHashes);
            $txs[$index] = $tx;
        }
        return $txs;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($walletName, $Confirmations = 1, $IncludeWatchOnly = false)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($walletName)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($walletName, $MinimumConfirmations = 1)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($Transaction)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function createWallet($walletName, array $addresses)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function addAddresses(Wallet $wallet, array $addresses)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAddresses(Wallet $wallet, array $addresses)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWallet(Wallet $wallet)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses(Wallet $wallet)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function getTransformedTypeOfSignature($type, array $options = [])
    {
        switch ($type) {
            case "pay-to-multi-pubkey-hash":
                return "multisig";
                break;
            case "pay-to-pubkey-hash":
                return "pubkeyhash";
                break;
            case "pay-to-pubkey":
                return "pubkey";
                break;
            case "empty":
                if (isset($options["newlyminted"])) {
                    return "pubkey";
                    break;
                }
                return "nonstandard";
                break;
            case "null-data":
                return "nulldata";
                break;
            case "pay-to-script-hash":
                return "scripthash";
                break;
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
        return "blocktrail.com";
    }
}
