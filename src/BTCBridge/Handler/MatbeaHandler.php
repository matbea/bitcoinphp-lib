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
use BTCBridge\Api\ListTransactionsOptions;
use BTCBridge\Api\WalletActionOptions;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\TransactionReference;
use BTCBridge\Api\Wallet;
use BTCBridge\Api\TransactionInput;
use BTCBridge\Api\TransactionOutput;
use BTCBridge\Api\Address;
use BTCBridge\Api\BTCValue;
use BitWasp\Bitcoin\Address\AddressFactory;
use BTCBridge\Api\CurrencyTypeEnum;
use BTCBridge\Exception\BEInvalidArgumentException;
use BTCBridge\Exception\BERuntimeException;
use BTCBridge\Exception\BELogicException;

/**
 * Returns data to user's btc-requests using Matbea-API
 * @author Matbea <mail@matbea.com>
 */
class MatbeaHandler extends AbstractHandler
{

    protected $token = '';

    /**
     * {@inheritdoc}
     */
    public function __construct(CurrencyTypeEnum $currency)
    {
        parent::__construct($currency);
        $this->setOption(self::OPT_BASE_URL, 'https://api.matbea.net/btcbridge');
    }

    /**
     * Setting token to the handler
     *
     * @param string $token An token for accessing to the data
     *
     * @throws BEInvalidArgumentException in case of error of this type
     *
     * @return $this
     */
    public function setToken($token)
    {
        if ((!is_string($token)) || empty($token)) {
            $msg = 'Bad type (' . gettype($token) . ') of token or empty token';
            throw new BEInvalidArgumentException($msg);
        }
        $this->token = $token;
        return $this;
    }

    /**
     * Make standart checks for the result from the matbea API
     *
     * @param string $url An url address for connecting
     * @param array $content Data from matbea API
     *
     * @throws BERuntimeException in case of this type of error
     * @throws BEInvalidArgumentException in case of this type of error
     */
    protected function checkMatbeaResult($url, $content)
    {
        if (!is_string($url) || empty($url)) {
            throw new BEInvalidArgumentException('Bad type of \$url (must be valid url-string)');
        }
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException('curl does not return a json object (url:"' . $url . '").');
        }
        if (!isset($content['error']) || (!isset($content['result']))) {
            throw new BERuntimeException(
                'Incorrect format of returning data ("' . json_encode($content) . '"), url="' . $url . '".'
            );
        }
        if (!empty($content['error'])) {
            throw new BERuntimeException(
                'Error "' . $content['error']['message'] . '" (code: '
                . $content['error']['code'] . ') returned (url:"' . $url . '").'
            );
        }
    }


    /**
     * {@inheritdoc}
     */
    public function listtransactions($walletName, ListTransactionsOptions $options = null)
    {
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException('Walletname must be non empty string.');
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                'Wallet name must contain only alphanumeric, underline and dash symbols ("' .
                $walletName . '" passed).'
            );
        }
        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . '/' . $curr . '/' . $walletName . '/' . 'listtransactions';
        $sep = '?';
        if ($this->token) {
            $url .= $sep . 'token=' . $this->token;
            $sep = '&';
        }
        if ($options) {
            if (null !== $options->getLimit()) {
                $url .= $sep . 'limit=' . $options->getLimit();
                $sep = '&';
            }
            if (null !== $options->getConfirmations()) {
                $url .= $sep . 'confirmations=' . $options->getConfirmations();
            } else {
                $url .= $sep . 'confirmations=1';
            }
            $sep = '&';
            if (null !== $options->getStarttxid()) {
                $url .= $sep . 'starttxid=' . $options->getStarttxid();
                $sep = '&';
            }
            if (null !== $options->getOmitAddresses()) {
                $url .= $sep . 'omit_addresses=' . (int)$options->getOmitAddresses();
            }
        }

        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException('curl error occured (url:"' . $url . '")');
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content['result']['transactions'])) {
            $msg = 'Answer of url: "' . $url . '")  does not contain a "transactions" property.';
            $this->logger->error($msg, ['data' => $content]);
            throw new BERuntimeException($msg);
        }

        /** @var $result Address */
        $addrObject = new Address();

        if (isset($content['result']['address'])) {
            $addrObject->setAddress($content['result']['address']);
        } else {
            if (isset($content['result']['wallet'])) {
                $wallet = new Wallet();
                if (!isset($content['result']['wallet']['name'])) {
                    throw new BERuntimeException(
                        'Answer does not contain "wallet.name" field (url:"' . $url . '").'
                    );
                }
                if (!isset($content['result']['wallet']['id'])) {
                    throw new BERuntimeException(
                        'Answer does not contain "wallet.id" field (url:"' . $url . '").'
                    );
                }
                if ($options && (false === $options->getOmitAddresses())) {
                    if (!isset($content['result']['wallet']['addresses'])) {
                        throw new BERuntimeException(
                            'Answer does not contain "wallet.addresses" field (url:"' . $url . '").'
                        );
                    }
                }
                $wallet->setName($content['result']['wallet']['name']);
                $wallet->setAddresses($content['result']['wallet']['addresses']);
                $wallet->setSystemDataByHandler(
                    $this->getHandlerName(),
                    [
                        'name' => $content['result']['wallet']['name']
                        ,
                        'id' => (int)$content['result']['wallet']['id']
                    ]
                );
                $addrObject->setWallet($wallet);
            }
        }

        /** @var $txrefs TransactionReference[] */
        $txRefs = [];
        /** @var $txUnconfirmedRefs TransactionReference[] */
        $txUnconfirmedRefs = [];
        $txRefHashes = [];

        foreach ($content['result']['transactions'] as $txref) {
            $txr = new TransactionReference();
            if (isset($txref['block_height'])) {
                $txr->setBlockHeight($txref['block_height']);
            }
            $txr->setConfirmations($txref['confirmations']);
            $txr->setDoubleSpend($txref['double_spend']);
            $txr->setTxHash($txref['txid']);
            $txr->setVout($txref['vout']);
            $txr->setConfirmed($txref['time']);
            $txr->setCategory($txref['category']);
            $v = gmp_init((string)$txref['amount']);
            $txr->setValue(new BTCValue($v));
            $txr->setAddress($txref['address']);

            $txRefHash = (string)$txr->getVout() . '_' . $txr->getTxHash() . '_'
                . $txr->getConfirmed() . '_' . $txr->getCategory() . '_'
                . (string)$txr->getBlockHeight() . '_' . (string)$txr->getConfirmations()
                . '_' . $txr->getAddress();
            if (!isset($txRefHashes[$txRefHash])) {
                $txRefHashes[$txRefHash] = 1;
                if (isset($txref['block_height'])) {
                    $txRefs [] = $txr;
                } else {
                    $txUnconfirmedRefs [] = $txr;
                }
            }
        }
        $addrObject->setTxrefs($txRefs);
        $addrObject->setUnconfirmedTxrefs($txUnconfirmedRefs);
        return $addrObject;
    }

    /**
     * {@inheritdoc}
     */
    public function gettransactions(array $txHashes)
    {
        if (empty($txHashes)) {
            throw new BEInvalidArgumentException("\$txHashes must be non empty array of valid btc-transction hashes.");
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

        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/gettransactions";
        $sep = "?";
        if ($this->token) {
            $url .= "?token=" . $this->token;
            $sep = "&";
        }
        foreach ($txHashes as $txHash) {
            $url .= $sep . "txid[]=" . $txHash;
            $sep = "&";
        }

        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content["result"]["transactions"])) {
            $msg = "Answer of url: \"" . $url . "\")  does not contain a \"transactions\" property.";
            $this->logger->error($msg, ["data" => $content]);
            throw new BERuntimeException($msg);
        }

        $txs = [];

        foreach ($content['result']['transactions'] as $tr) {
            $tx = new Transaction;
            if (-1 != $tr['"block_height']) {
                $tx->setConfirmed($tr['block_time']);
                $tx->setBlockHash($tr['block_hash']);
                $tx->setBlockHeight($tr['block_height']);
            } else {
                $tx->setBlockHeight(-1);
            }
            $tx->setHash($tr['hash']);
            $tx->setDoubleSpend($tr['double_spend']);
            $tx->setConfirmations($tr['confirmations']);
            foreach ($tr['inputs'] as $inp) {
                $input = new TransactionInput();
                if (isset($inp["addresses"])) {
                    $input->setAddresses($inp["addresses"]);
                }
                if (isset($inp["vout"])) {
                    $input->setOutputIndex($inp["vout"]);
                }
                $v = gmp_init(strval($inp["value"]));
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
                $v = gmp_init(strval($outp["value"]));
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
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        if ((!is_int($Confirmations)) || ($Confirmations < 0)) {
            throw new BEInvalidArgumentException(
                "Confirmations variable must ne non negative integer, (" .
                $Confirmations . " passed)."
            );
        }
        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/" . $walletName . "/getbalance"
            . "?confirmations=" . $Confirmations;
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content["result"]["balance"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.",
                ["data" => $content]
            );
            throw new BERuntimeException("Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.");
        }
        $balance = $content["result"]["balance"];
        if (!is_int($balance) || ($balance < 0)) {
            $msg = "Answer of url: \"" . $url . "\")  contains bad value (" . $content["result"]["balance"]
                . ") of field \"balance\" (type=" . gettype($content["result"]["balance"]) . ").";
            $this->logger->error($msg, ["data" => $content]);
            throw new BELogicException($msg);
        }
        $v = gmp_init(strval($balance));
        return new BTCValue($v);
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($walletName)
    {
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/" . $walletName . "/getunconfirmedbalance";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content["result"]["balance"])) {
            $this->logger->error(
                "Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.",
                ["data" => $content]
            );
            throw new BERuntimeException("Answer of url: \"" . $url . "\")  does not contain a \"balance\" field.");
        }
        $balance = $content["result"]["balance"];
        if (!is_int($balance) || ($balance < 0)) {
            $msg = "Answer of url: \"" . $url . "\")  contains bad value (" . $content["result"]["balance"]
                . ") of field \"balance\" (type=" . gettype($content["result"]["balance"]) . ").";
            $this->logger->error($msg, ["data" => $content]);
            throw new BELogicException($msg);
        }
        $v = gmp_init(strval($balance));
        return new BTCValue($v);
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($walletName, $MinimumConfirmations = 1)
    {
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException("Wallet name must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $action = "listunspent";
        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/" . $walletName . "/" . $action;
        $sep = "?";
        if ($this->token) {
            $url .= $sep . "token=" . $this->token;
            $sep = "&";
        }
        $url .= $sep . "confirmations=" . intval($MinimumConfirmations);

        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content["result"]['unspents'])) {
            $msg = "Answer of url: \"" . $url . "\")  does not contain an \"unspents\" array.";
            $this->logger->error($msg, ["data" => $content]);
            throw new BERuntimeException($msg);
        }
        if (empty($content["result"]["unspents"])) {
            return [];
        }

        /** @var $result TransactionReference[] */
        $result = [];

        foreach ($content["result"]["unspents"] as $i => $txref) {
            $txr = new TransactionReference();
            if (isset($txref["block_height"])) {
                $txr->setBlockHeight($txref["block_height"]);
            }
            if (isset($txref["confirmed"])) {
                $txr->setConfirmed($txref["confirmed"]);
            }
            $txr->setConfirmations($txref["confirmations"]);
            $txr->setDoubleSpend($txref["double_spend"]);
            $txr->setTxHash($txref["tx_hash"]);
            $txr->setVout($txref["vout"]);
            $txr->setAddress($txref['address']);
            $txr->setCategory(TransactionReference::CATEGORY_RECEIVE);
            $v = gmp_init(strval($txref["amount"]));
            if (false === $v) {
                $msg = "Answer of url: \"" . $url . "\")  contains an incorrect \"amount\" value ("
                    . $txref["amount"] . ") in \"unspents\" result array (item #" . $i . ").";
                $this->logger->error($msg, ["txref" => $txref]);
                throw new BERuntimeException($msg);
            }
            $txr->setValue(new BTCValue($v));
            $txr->setSpent(false);

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
    public function sendrawtransaction($transaction)
    {
        if ((!is_string($transaction)) && (empty($transaction))) {
            throw new BEInvalidArgumentException("\$transaction variable array must be non empty strings.");
        }

        //TODO make sure that format of transaction is correct
        $url = $this->getOption(self::OPT_BASE_URL) . "/sendrawtransaction?data=" . $transaction;
        if ($this->token) {
            $url .= "&token=" . $this->token;
        }

        $curl = curl_init();
        $this->prepareCurl($curl, $url);
        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        if (isset($content['error']) && (null != $content['error'])) {
            throw new BERuntimeException(
                "Error (code " . $content["error"]["code"] . ") \"" .
                $content['error']["message"] . "\" returned (url: \"" . $url . "\")."
            );
        }
        if (!isset($content['result'])) {
            throw new BERuntimeException(
                "Answer does not contain \"result\" field (url: \"" . $url . "\")."
            );
        }
        if (empty($content['result'])) {
            throw new BERuntimeException(
                "Answer contains empty \"result\" field (url: \"" . $url . "\")."
            );
        }
        return $content['result'];
    }

    /**
     * {@inheritdoc}
     */
    public function createWallet($walletName, array $addresses, WalletActionOptions $options = null)
    {
        if (!is_string($walletName)) {
            throw new BEInvalidArgumentException("name variable must be non empty string.");
        }
        if (!preg_match('/^[A-Z0-9_-]+$/i', $walletName)) {
            throw new BEInvalidArgumentException(
                "Wallet name can't be empty and have to contain only alphanumeric, underline and dash symbols (\"" .
                $walletName . "\" passed)."
            );
        }
        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/wallet/" . $walletName . "/create";
        $sep = "?";
        if ($this->token) {
            $url .= "?token=" . $this->token;
            $sep = "&";
        }
        if ($options) {
            if (null !== $options->getOmitAddresses()) {
                $url .= $sep . "omit_addresses=" . (int)$options->getOmitAddresses();
            }
        }
        $post_data = [];
        if (count($addresses) > 0) {
            $post_data["addresses"] = [];
            foreach ($addresses as $address) {
                if (!AddressFactory::isValidAddress($address, $this->network)) {
                    throw new BEInvalidArgumentException("No valid address (\"" . $address . "\" passed).");
                }
                $post_data["addresses"] [] = $address;
            }
        }
        $curl = curl_init();
        $this->prepareCurl($curl, $url);
        $curl_options = [
            CURLOPT_POST            => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type:application/json'],
            CURLOPT_POSTFIELDS      => json_encode($post_data)
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new BERuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: " . serialize($curl_options) . ")."
            );
        }
        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content['result']['wallet_name'])) {
            throw new BERuntimeException(
                "Answer does not contain \"wallet_name\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if (!isset($content['result']['wallet_id'])) {
            throw new BERuntimeException(
                "Answer does not contain \"wallet_id\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if ($options) {
            if (false === $options->getOmitAddresses()) {
                if (!isset($content['result']['addresses'])) {
                    throw new BERuntimeException(
                        "Answer does not contain \"addreses\" field (url:\"" . $url
                        . "\", post: \"" . serialize($post_data) . "\")."
                    );
                }
            }
        }
        $wallet = new Wallet;
        $wallet->setName($content['result']['wallet_name']);
        if ($options) {
            if (false === $options->getOmitAddresses()) {
                $wallet->setAddresses($content["result"]["addresses"]);
            }
        }
        $wallet->setSystemDataByHandler(
            $this->getHandlerName(),
            ["name" => $content['result']['wallet_name'], "id" => $content['result']['wallet_id']]
        );
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function addAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if ((!$walletSystemData) || (!isset($walletSystemData["id"]))) {
            throw new BEInvalidArgumentException(
                "System data of passed wallet is empty or invalid (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        $walletId = $walletSystemData["id"];
        if (empty($addresses)) {
            throw new BEInvalidArgumentException("addresses variable must be non empty array.");
        }
        foreach ($addresses as $address) {
            if (!AddressFactory::isValidAddress($address, $this->network)) {
                throw new BEInvalidArgumentException("No valid address (\"" . $address . "\" passed).");
            }
        }

        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/wallet/" . $walletId . "/addaddresses";
        $sep = "?";
        if ($this->token) {
            $url .= "?token=" . $this->token;
            $sep = "&";
        }
        if ($options) {
            if (null !== $options->getOmitAddresses()) {
                $url .= $sep . "omit_addresses=" . (int)$options->getOmitAddresses();
            }
        }
        $post_data["addresses"] = [];
        foreach ($addresses as $address) {
            $post_data["addresses"] [] = $address;
        }

        $curl = curl_init();
        $this->prepareCurl($curl, $url);
        $curl_options = [
            CURLOPT_POST            => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type:application/json'],
            CURLOPT_POSTFIELDS      => json_encode($post_data)
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new BERuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: "
                . serialize($curl_options) . ")."
            );
        }

        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content['result']['wallet_name'])) {
            throw new BERuntimeException(
                "Answer does not contain \"wallet_name\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if (!isset($content['result']['wallet_id'])) {
            throw new BERuntimeException(
                "Answer does not contain \"wallet_id\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if ($options) {
            if (false === $options->getOmitAddresses()) {
                if (!isset($content['result']['addresses'])) {
                    throw new BERuntimeException(
                        "Answer does not contain \"addreses\" field (url:\"" . $url
                        . "\", post: \"" . serialize($post_data) . "\")."
                    );
                }
            }
        }
        if ($options) {
            if (false === $options->getOmitAddresses()) {
                $wallet->setAddresses($content['result']['addresses']);
            }
        }
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAddresses(Wallet $wallet, array $addresses, WalletActionOptions $options = null)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if ((!$walletSystemData) || (!isset($walletSystemData["id"]))) {
            throw new BEInvalidArgumentException(
                "System data of passed wallet is empty or invalid (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        $walletId = $walletSystemData["id"];
        if (empty($addresses)) {
            throw new BEInvalidArgumentException("addresses variable must be non empty array.");
        }
        foreach ($addresses as $address) {
            if (!AddressFactory::isValidAddress($address, $this->network)) {
                throw new BEInvalidArgumentException("No valid address (\"" . $address . "\" passed).");
            }
        }

        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/wallet/" . $walletId . "/removeaddresses";
        $sep = "?";
        if ($this->token) {
            $url .= "?token=" . $this->token;
            $sep = "&";
        }
        if ($options) {
            if (null !== $options->getOmitAddresses()) {
                $url .= $sep . "omit_addresses=" . (int)$options->getOmitAddresses();
            }
        }
        $post_data["addresses"] = [];
        foreach ($addresses as $address) {
            $post_data["addresses"] [] = $address;
        }

        $curl = curl_init();
        $this->prepareCurl($curl, $url);
        $curl_options = [
            CURLOPT_POST            => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type:application/json'],
            CURLOPT_POSTFIELDS      => json_encode($post_data)
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new BERuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: " . serialize($curl_options) . ")."
            );
        }

        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content['result']['wallet_name'])) {
            throw new BERuntimeException(
                "Answer does not contain \"wallet_name\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if (!isset($content['result']['wallet_id'])) {
            throw new BERuntimeException(
                "Answer does not contain \"wallet_id\" field (url:\"" . $url
                . "\", post: \"" . serialize($post_data) . "\")."
            );
        }
        if ($options) {
            if (false === $options->getOmitAddresses()) {
                if (!isset($content['result']['addresses'])) {
                    throw new BERuntimeException(
                        "Answer does not contain \"addreses\" field (url:\"" . $url
                        . "\", post: \"" . serialize($post_data) . "\")."
                    );
                }
            }
        }
        if ($options) {
            if (false === $options->getOmitAddresses()) {
                $wallet->setAddresses($content['result']['addresses']);
            }
        }
        return $wallet;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWallet(Wallet $wallet)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if ((!$walletSystemData) || (!isset($walletSystemData["id"]))) {
            throw new BEInvalidArgumentException(
                "System data of passed wallet is empty or invalid (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        $walletId = $walletSystemData["id"];
        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/wallet/" . $walletId . "/delete";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $curl = curl_init();
        $this->prepareCurl($curl, $url);
        $curl_options = [
            CURLOPT_POST            => 1,
            CURLOPT_HTTPHEADER     => ['Content-Type:application/json']
        ];
        if (false === curl_setopt_array($curl, $curl_options)) {
            throw new BERuntimeException(
                "curl_setopt_array failed url:\"" . $url . "\", parameters: "
                . serialize($curl_options) . ")."
            );
        }
        $content = curl_exec($curl);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl error occured (url:\"" . $url . "\")");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses(Wallet $wallet)
    {
        $walletSystemData = $this->getSystemDataForWallet($wallet);
        if ((!$walletSystemData) || (!isset($walletSystemData["id"]))) {
            throw new BEInvalidArgumentException(
                "System data of passed wallet is empty or invalid (for handler \"" . $this->getHandlerName() . "\")."
            );
        }
        $walletId = $walletSystemData["id"];

        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/wallet/" . $walletId . "/addresses";
        if ($this->token) {
            $url .= "?token=" . $this->token;
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content["result"]["addresses"])) {
            $msg = "Answer of url: \"" . $url . "\")  does not contain an \"addresses\" array.";
            $this->logger->error($msg, ["data" => $content]);
            throw new BERuntimeException($msg);
        }
        $wallet->setAddresses($content['result']['addresses']);
        return $wallet->getAddresses();
    }

    /**
     * {@inheritdoc}
     */
    public function getWallets(WalletActionOptions $options = null)
    {
        $curr = $this->currency;
        $url = $this->getOption(self::OPT_BASE_URL) . "/" . $curr . "/wallet/getwallets";
        $sep = "?";
        if ($this->token) {
            $url .= $sep . "token=" . $this->token;
            $sep = "&";
        }
        if ($options) {
            if (null !== $options->getOmitAddresses()) {
                $url .= $sep . "omit_addresses=" . (int)$options->getOmitAddresses();
            }
        }
        $ch = curl_init();
        $this->prepareCurl($ch, $url);
        $content = curl_exec($ch);
        if ((false === $content) || (null === $content)) {
            throw new BERuntimeException("curl does not return a json object (url:\"" . $url . "\").");
        }
        $content = json_decode($content, true);
        $this->checkMatbeaResult($url, $content);
        if (!isset($content["result"]["wallets"])) {
            $msg = "Answer of url: \"" . $url . "\")  does not contain an \"addresses\" array.";
            $this->logger->error($msg, ["data" => $content]);
            throw new BERuntimeException($msg);
        }

        $ret = [];
        foreach ($content["result"]["wallets"] as $wallet) {
            $w = new Wallet();
            $w->setName($wallet["wallet_name"]);

            if ($options && (false === $options->getOmitAddresses())) {
                if (!isset($wallet['addresses'])) {
                    throw new BERuntimeException(
                        "Answer does not contain \"wallet.addresses\" field (url:\"" . $url . "\")."
                    );
                }
                $w->setAddresses($wallet["addresses"]);
            }
            $w->setSystemDataByHandler(
                $this->getHandlerName(),
                ["name" => $wallet["wallet_name"], "id" => $wallet["wallet_id"]]
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
        switch ($type) {
            case "nulldata":
            case "nonstandard":
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
        return "matbea.net";
    }
}
