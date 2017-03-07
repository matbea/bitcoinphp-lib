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

/**
 * Returns data to user's btc-requests using Matbea-API
 * @author Matbea <mail@matbea.com>
 */
class MatbeaHandler extends AbstractHandler {

    protected $url;

    /**
     * @param string  $url Url to matbea API resource
     * @param Boolean $ordered Whether the order of processor will saved or not
     *
     * @throws InvalidArgumentException If passed url is not a valid url
     */
    public function __construct($ordered = false) {
        parent::__construct($ordered);
    }

    /**
     * {@inheritdoc}
     */
	public function listtransactions($address,array $options=array()) {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function gettransaction($TXID,$IncludeWatchOnly=false) {
        $item = [
            "confirmations"=> 2040,
            "walletconflicts" => [],
            "hex" => "", //HUERAGA - poka ne reshili
            "fee" => 0.00030303,
            "details" => [
				[
	                             "account" => "",
				     "address" => "1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7",
                                     "category" => "send",
                                     "amount" => -0.00033894,
                                     "label" => "",
                                     "vout" => 0,
                                     "fee" => -0.00030303
                                ],
                                [
                                    "account" => "",
                                    "address" => "1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7",
                                    "category" => "receive",
                                    "amount" => 0.00033894,
                                    "label" => "",
                                    "vout" => 0
                                ]
                        ]
        ];
        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($Account="",$Confirmations=1,$IncludeWatchOnly=false) {
        return 0.1;
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance() {
        return 0.2;
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($MinimumConfirmations=1,$MaximumConfirmations=1,array $Addresses=array()) {
        return [
            [
                "txid" => "721dca6852f828af1057d5bf5f324a6d2b27328a27882229048cf340c1e3ec10",
                "vout" => 0,
                "address" => "1MN3cT9Ro927h4kgpSZ5V7SfYjrwTysXv7",
                "account" => "",
                "scriptPubKey" => "76a914df5d6e3c76eb3f38744fbe3b4a4f32aaaf7d607088ac",
                "amount" => 0.00033894,
                "confirmations" => 950,
                "spendable" => true
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($Transaction,$AllowHighFees=false) {
        return "721dca6852f828af1057d5bf5f324a6d2b27328a27882229048cf340c1e3ec10";
    }
}
