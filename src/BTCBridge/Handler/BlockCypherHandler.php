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
use BTCBridge\Api\Transaction;
use BTCBridge\Api\TransactionInput;
use BTCBridge\Api\TransactionOutput;
use BTCBridge\Api\Wallet;
use BTCBridge\Api\WalletActionOptions;
use BTCBridge\Api\BTCValue;
use BTCBridge\Api\CurrencyTypeEnum;
use BTCBridge\Exception\BEInvalidArgumentException;
use BTCBridge\Exception\BERuntimeException;

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
    public function __construct(CurrencyTypeEnum $currency)
    {
        parent::__construct($currency);
        $this->setOption(self::OPT_BASE_URL, "https://api.blockcypher.com/v1/btc/main/");
    }

    /**
     * Setting token to the handler
     *
     * @param string $token An token for accessing to the blockcypher data
     *
     * @throws BEInvalidArgumentException in case of error of this type
     *
     * @return $this
     */
    public function setToken($token)
    {
        if ((!is_string($token)) || empty($token)) {
            throw new BEInvalidArgumentException("Bad type of token (must be non empty string)");
        }
        $this->token = $token;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function listtransactions($walletName, ListTransactionsOptions $options = null)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }


    /**
     * {@inheritdoc}
     */
    public function gettransactions(array $txHashes)
    {
        if (empty($txHashes)) {
            throw new BEInvalidArgumentException("txHashes variable must be non empty array of non empty strings.");
        }
        $maxCountOfTransactions = 100;
        if (count($txHashes) > $maxCountOfTransactions) {
            throw new BEInvalidArgumentException(
                "txHashes variable size could not be bigger than " . $maxCountOfTransactions . "."
            );
        }

        foreach ($txHashes as $txHash) {
            if ((!is_string($txHash)) && ("" == $txHash)) {
                throw new BEInvalidArgumentException(
                    "All hashes is \$txHashes array must be non empty strings."
                );
            }
        }

        $url = $this->getOption(self::OPT_BASE_URL) . "txs/" . implode(";", $txHashes);


        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $fullContent = curl_exec($ch);
        if (false === $fullContent) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $fullContent = json_decode($fullContent, true);
        if ((false === $fullContent) || (null === $fullContent)) {
            throw new BERuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($fullContent['error'])) {
            throw new BERuntimeException("Error \"" . $fullContent['error'] . "\" returned (url:\"" . $url . "\").");
        }

        $txs = array_fill(0, count($txHashes), null);
        if (1 == count($txHashes)) {
            $fullContent = [$fullContent];
        }
        //$key2 = array_search($txHashes[1], array_column($fullContent, 'hash'));

        foreach ($fullContent as $content) {
            if (isset($content['error'])) {
                throw new BERuntimeException("Error \"" . $content['error'] . "\" returned (url:\"" . $url . "\").");
            }
            $tx = new Transaction;
            if (isset($content["block_hash"])) {
                $tx->setBlockHash($content["block_hash"]);
            }
            $tx->setBlockHeight($content["block_height"]);
            $tx->setTxId($content["hash"]);
            //$tx->setAddresses($content["addresses"]);
            if (isset($content["confirmed"])) {
                $tx->setConfirmationTime(strtotime($content["confirmed"]));
            }
            //$tx->setLockTime($content["lock_time"]);
            $tx->setDoubleSpend($content["double_spend"]);
            //$tx->setVoutSz($content["vout_sz"]);
            //$tx->setVinSz($content["vin_sz"]);
            $tx->setConfirmations($content["confirmations"]);
            foreach ($content["inputs"] as $inp) { //20 штук по дефолту выдаётся, надо, чтобы все
                $input = new TransactionInput();
                $input->setAddresses(
                    (isset($inp["addresses"]) && (null !== $inp["addresses"])) ? $inp["addresses"] : []
                );
                //if (isset($inp["prev_hash"])) {
                    //$input->setPrevHash($inp["prev_hash"]);
                //}
                if (isset($inp["output_index"])) {
                    $input->setOutputIndex($inp["output_index"]);
                }
                $val = gmp_init(strval($inp["output_value"]));
                $input->setOutputValue(new BTCValue($val));
                //////////////////////////////////////////
                /*
                if ("pay-to-multi-pubkey-hash" == $inp["script_type"]) {
                    $multisigAddresses = $this->extractAddressesFromMultisigScript($inp["script"]);
                    $previousInputAddresses = $input->getAddresses();
                    $input->setAddresses($multisigAddresses);
                    $txAddresses = $tx->getAddresses();
                    if (!empty($previousInputAddresses)) {
                        $tx->setAddresses(array_diff($txAddresses, $previousInputAddresses));
                        $txAddresses = $tx->getAddresses();
                    }
                    if (!empty($multisigAddresses)) {
                        foreach ( $multisigAddresses as $addr ) {
                            if ( !in_array($addr,$txAddresses) ) {
                                $txAddresses [] = $addr;
                            }
                        }
                        $tx->setAddresses($txAddresses);
                    }
                }*/
                //////////////////////////////////////////
                $options = [];
                if ($input->getOutputIndex() == -1) {
                    $options["newlyminted"] = true;
                }
                $input->setScriptType($this->getTransformedTypeOfSignature($inp["script_type"], $options));
                $tx->addInput($input);
            }
            foreach ($content["outputs"] as $outp) {
                $output = new TransactionOutput();
                $output->setAddresses(
                    (isset($outp["addresses"]) && (null !== $outp["addresses"])) ? $outp["addresses"] : []
                );
                $v = gmp_init(strval($outp["value"]));
                $output->setValue(new BTCValue($v));
                /*if ("pay-to-multi-pubkey-hash" == $outp["script_type"]) {
                    $multisigAddresses = $this->extractAddressesFromMultisigScript($outp["script"]);
                    $previousOutputAddresses = $output->getAddresses();
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
                    }
                }*/
                $options = [];
                $script_type = $this->getTransformedTypeOfSignature($outp["script_type"], $options);
                $output->setScriptType($script_type);
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
    public function getbalance($walletName, $Confirmations = 1)
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
    public function sendrawtransaction($transaction)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function createWallet($walletName, array $addresses, WalletActionOptions $options = null)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function addAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null)
    {
        return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null)
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
    public function getWallets(WalletActionOptions $options = null)
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
        return "blockcypher.com";
    }
}
