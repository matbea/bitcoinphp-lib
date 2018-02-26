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

use BTCBridge\Bridge;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\TransactionInput;
use BTCBridge\Api\TransactionOutput;
use BTCBridge\Api\Wallet;
use BTCBridge\Api\BTCValue;
use BTCBridge\Api\ListTransactionsOptions;
use BTCBridge\Api\WalletActionOptions;
use BTCBridge\Api\CurrencyTypeEnum;
use BTCBridge\Exception\BEInvalidArgumentException;
use BTCBridge\Exception\BERuntimeException;

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
    public function __construct(CurrencyTypeEnum $currency)
    {
        parent::__construct($currency);
        $this->setOption(self::OPT_BASE_URL, "https://api.blocktrail.com/v1/btc/");
    }

    /**
     * Setting token to the handler
     *
     * @param string $token An token for accessing to the blocktrail data
     *
     * @throws BEInvalidArgumentException in case of error of this type
     *
     * @return $this
     */
    public function setToken($token)
    {
        if ((!is_string($token)) || empty($token)) {
            $msg = "Bad type (" . gettype($token) . ") of token or empty token";
            throw new BEInvalidArgumentException($msg);
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

        if (CurrencyTypeEnum::BTC != $this->currency) {
            //blocktrail api doen't work correctly with testnet data, another are not supported now
            return AbstractHandler::HANDLER_UNSUPPORTED_METHOD;
        }

        if (empty($txHashes)) {
            throw new BEInvalidArgumentException("txHashes variable must be non empty array of non empty strings.");
        }
        if (count($txHashes) > Bridge::MAX_COUNT_OF_TRANSACTIONS_FOR__GETTRANSACTIONS__METHOD) {
            throw new BEInvalidArgumentException(
                "txHashes variable size must be non bigger than " .
                Bridge::MAX_COUNT_OF_TRANSACTIONS_FOR__GETTRANSACTIONS__METHOD . "."
            );
        }
        foreach ($txHashes as $txHash) {
            if ((!is_string($txHash)) || !preg_match('/^[a-z0-9]+$/i', $txHash) || (strlen($txHash) != 64)) {
                throw new BEInvalidArgumentException(
                    "Hashes in \$txHashes must be valid bitcoin transaction hashes (\"" . $txHash . "\" not valid)."
                );
            }
        }

        $url = $this->getOption(self::OPT_BASE_URL) . "transactions/" . implode(",", $txHashes);
        if ($this->token) {
            $url .= "?api_key=" . $this->token;
        }

        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $fullContent = curl_exec($ch);
        if ((false === $fullContent) || (null === $fullContent)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $fullContent = json_decode($fullContent, true);
        if ((false === $fullContent) || (null === $fullContent)) {
            throw new BERuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        if (isset($fullContent['msg'])) {
            throw new BERuntimeException(
                "Error \"" . $fullContent['msg'] . "\" (code: " . $fullContent["code"] .
                ") returned (url:\"" . $url . "\")."
            );
        }
        if (!isset($fullContent['data']) || !is_array($fullContent['data'])) {
            throw new BERuntimeException(
                "Returned array from url \"" . $url .
                "\" does not contain \"data\" field or \"data\" field is not array."
            );
        }

        $txs = array_fill(0, count($txHashes), null);
        foreach ($fullContent['data'] as $content) {
            if (isset($content['msg'])) {
                throw new BERuntimeException(
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
            if (null !== $content["block_time"]) {
                $tx->setConfirmed(strtotime($content["block_time"]));
            }
            $tx->setDoubleSpend($content["is_double_spend"]);
            $tx->setConfirmations($content["confirmations"]);
            foreach ($content["inputs"] as $inp) {
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
                            $input->setAddresses([]);
                        } else {
                            $input->setAddresses([$inp["address"]]);
                        }
                        break;
                };

                if (isset($inp["output_index"])) {
                    $input->setOutputIndex($inp["output_index"]);
                }
                $val = gmp_init(strval($inp["value"]));
                $input->setOutputValue(new BTCValue($val));
                $input->setScriptType($this->getTransformedTypeOfSignature($inp["type"]));
                $tx->addInput($input);
            }
            foreach ($content["outputs"] as $outp) {
                $output = new TransactionOutput();
                $outp["type"] = $this->getTransformedTypeOfSignature($outp["type"]);
                if ("multisig" == $outp["type"]) {
                    $output->setAddresses($outp["multisig_addresses"]);
                } elseif ("op_return" == $outp["type"]) {
                    $output->setAddresses([]);
                } else {
                    $output->setAddresses([$outp["address"]]);
                }
                $v = gmp_init(strval($outp["value"]));
                $output->setValue(new BTCValue($v));
                $output->setScriptType($outp["type"]);
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
            //case "op_return":
                //return "nulldata";
                //break;
            case "unknown":
                //return "nonstandard";
                //return "nulldata";
                return "op_return";
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
