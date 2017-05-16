<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\ConflictHandler;

use BTCBridge\Api\BTCValue;
use BTCBridge\Api\Transaction;
//use BTCBridge\Api\TransactionInput;
//use BTCBridge\Api\TransactionOutput;
use BTCBridge\Api\Address;
use \BTCBridge\Api\TransactionReference;
use BTCBridge\Api\Wallet;
use BTCBridge\Exception\ConflictHandlerException;

/**
 * Default Conflict Handler class providing the default behaviour
 *
 * @author Matbea <mail@matbea.com>
 */
class DefaultConflictHandler implements ConflictHandlerInterface
{
    /** This group of constants are DefaultConflictHandler options */
    const OPT_CONFIRMATIONS_MAXIMUM_DIFERENCE_ALLOWED = 1;

    /** @var array options */
    protected $options = [];

    /**
     * Constructor of class
     */
    public function __construct()
    {
        $this->setOption(self::OPT_CONFIRMATIONS_MAXIMUM_DIFERENCE_ALLOWED, "10");
    }

    /**
     * Sets the option
     *
     * @param int $optionName a constant describying name of the option
     * @param string $optionValue a value of the option
     *
     * @throws \InvalidArgumentException if error of this type
     *
     */
    public function setOption($optionName, $optionValue)
    {
        if ((gettype($optionName) != "integer") ||
            (!in_array(
                $optionName,
                [
                    self::OPT_CONFIRMATIONS_MAXIMUM_DIFERENCE_ALLOWED
                ]
            ))) {
            throw new \InvalidArgumentException("Bad type of option (".$optionName.")");
        }
        if (gettype($optionValue) != "string" || "" == $optionValue) {
            throw new \InvalidArgumentException("Bad type of option value (must be non empty string)");
        }
        $this->options[$optionName] = $optionValue;
    }

    /**
     * Gets the option
     *
     * @param int $optionName a constant, which describes the name of the option
     *
     * @throws \InvalidArgumentException if error of this type
     * @throws \RuntimeException in case if this option is not exists
     *
     * @return string Option
     */
    protected function getOption($optionName)
    {
        if ((gettype($optionName) != "integer") ||
            (!in_array(
                $optionName,
                [
                    self::OPT_CONFIRMATIONS_MAXIMUM_DIFERENCE_ALLOWED
                ]
            ))) {
            throw new \InvalidArgumentException("Bad type of option (".$optionName.")");
        }
        if (!isset($this->options[$optionName])) {
            throw new \RuntimeException("No option with name \"" . $optionName . "\" exists in the class)");
        }
        return $this->options[$optionName];
    }

    /**
     * {@inheritdoc}
     */
    public function listtransactions($data)
    {
        //$uniq_results = array_map('unserialize',array_unique(array_map('serialize', $data)) );
        //$uniq_results = array_unique($results);
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $address1 = & $data[0];
        $address2 = & $data[1];
        if ((!$address1 instanceof Address) || (!$address2 instanceof Address)) {
            throw new \InvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        $addr1 = $address1->getAddress();
        $addr2 = $address2->getAddress();
        if ($addr1 !== $addr2) {
            throw new ConflictHandlerException("Different values of addresses ( " . $addr1 . " and " . $addr2 . " ).");
        }
        $wallet1 = $address1->getWallet();
        $wallet2 = $address2->getWallet();
        if (gettype($wallet1) != gettype($wallet2)) {
            throw new ConflictHandlerException("Different types of wallets ( first is null, another - not).");
        }
        if ($wallet1 && $wallet2) {
            if ($wallet1->getName() != $wallet2->getName()) {
                throw new ConflictHandlerException(
                    "Different names of wallets ( " . $wallet1->getName() . " and " . $wallet2->getName() . " )."
                );
            }
            $addressesArr1 = $wallet1->getAddresses();
            $addressesArr2 = $wallet2->getAddresses();
            if ($addressesArr1 != $addressesArr2) {
                throw new ConflictHandlerException(
                    "Different addresses ( " . implode(",", $addressesArr1)
                    . " and " . implode(",", $addressesArr2) . " )."
                );
            }
        }

        $balance1 = $address2->getBalance();
        $balance2 = $address2->getBalance();
        if ($balance1 !== $balance2) {
            throw new ConflictHandlerException(
                "Different values of balances ( " . $balance1 . " and " . $balance2 . " )."
            );
        }
        $unconfirmedBalance1 = $address1->getUnconfirmedBalance();
        $unconfirmedBalance2 = $address2->getUnconfirmedBalance();
        if ($unconfirmedBalance1 !== $unconfirmedBalance2) {
            throw new ConflictHandlerException(
                "Different values of unconfirmed balances ( " .
                $unconfirmedBalance1 . " and " . $unconfirmedBalance2 . " )."
            );
        }
        $finalBalance1 = $address1->getFinalBalance();
        $finalBalance2 = $address2->getFinalBalance();
        if ($finalBalance1 !== $finalBalance2) {
            throw new ConflictHandlerException(
                "Different values of final balances ( " . $finalBalance1 . " and " . $finalBalance2 . " )."
            );
        }

        $txrefs1 = $address1->getTxrefs();
        $txrefs2 = $address2->getTxrefs();
        if (count($txrefs1) != count($txrefs2)) {
            throw new ConflictHandlerException(
                "Different count of  ( " . $finalBalance1 . " and " . $finalBalance2 . " )."
            );
        }
        $unconfirmedtxrefs1 = $address1->getUnconfirmedTxrefs();
        $unconfirmedtxrefs2 = $address2->getUnconfirmedTxrefs();
        if (count($unconfirmedtxrefs1) != count($unconfirmedtxrefs2)) {
            throw new ConflictHandlerException(
                "Different values of final balances ( " . $finalBalance1 . " and " . $finalBalance2 . " )."
            );
        }

        for ($i = 0, $ic = count($txrefs1); $i < $ic; ++$i) {
            $tx = & $txrefs1[$i];
            $found = false;
            for ($j = 0, $jc = count($txrefs2); $j < $jc; ++$j) {
                $txc = & $txrefs2[$j];
                $gmpCmp = gmp_cmp($tx->getValue()->getGMPValue(), $txc->getValue()->getGMPValue());
                if (($tx->getBlockHeight() != $txc->getBlockHeight()) ||
                    ($tx->getConfirmations() != $txc->getConfirmations()) ||
                    ($tx->getDoubleSpend() != $txc->getDoubleSpend()) ||
                    //($tx->getSpent() != $txc->getSpent()) ||
                    ($tx->getTxHash() != $txc->getTxHash()) ||
                    ($tx->getVout() != $txc->getVout()) ||
                    ($gmpCmp != 0)
                ) {
                    continue;
                }
                $found = true;
                break;
            }
            if (!$found) {
                throw new ConflictHandlerException(
                    "No found transaction in second array ( " . serialize($tx) . " )."
                );
            }
        }

        for ($i = 0, $ic = count($unconfirmedtxrefs1); $i < $ic; ++$i) {
            $tx = & $unconfirmedtxrefs1[$i];
            $found = false;
            for ($j = 0, $jc = count($unconfirmedtxrefs2); $j < $jc; ++$j) {
                $txc = & $txrefs2[$j];
                $gmpCmp = gmp_cmp($tx->getValue()->getGMPValue(), $txc->getValue()->getGMPValue());
                if (($tx->getBlockHeight() != $txc->getBlockHeight()) ||
                    ($tx->getConfirmations() != $txc->getConfirmations()) ||
                    ($tx->getDoubleSpend() != $txc->getDoubleSpend()) ||
                    //($tx->getSpent() != $txc->getSpent()) ||
                    ($tx->getTxHash() != $txc->getTxHash()) ||
                    ($tx->getVout() != $txc->getVout()) ||
                    ($gmpCmp != 0)
                ) {
                    continue;
                }
                $found = true;
                break;
            }
            if (!$found) {
                throw new ConflictHandlerException(
                    "No found transaction in second array ( " . serialize($tx) . " )."
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function gettransactions($data)
    {
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $txs1 = & $data[0];
        $txs2 = & $data[1];
        if ((!is_array($txs1)) || (!is_array($txs2))) {
            throw new \InvalidArgumentException(
                "Elements of Data array must be array of instances of Transaction class."
            );
        }

        $i_outc1 = count($txs1);
        $i_outc2 = count($txs2);
        if ($i_outc1 != $i_outc2) {
            throw new \InvalidArgumentException("Elements of Data array must have same dimensions.");
        }
        for ($i_out = 0; $i_out < $i_outc1; ++$i_out) {
            /** @var $tx1 Transaction */
            $tx1 = &$txs1[$i_out];
            /** @var $tx2 Transaction */
            $tx2 = &$txs2[$i_out];


            if (($tx1->getBlockHash() !== $tx2->getBlockHash()) ||
                // because of txhash=
                //000010ab9378a649fe2d57387afeb4b066a6fa396cefcc6b91328badd49f319f
                // (different time)
                // because of txhash=
                //0000297bd516c501aa9b143a5eac8adaf457fa78431e844092a7112815411d03
                // (different confirmed)
                //($tx1->getConfirmed() !== $tx2->getConfirmed()) ||
                //($tx1->getDoubleSpend() !== $tx2->getDoubleSpend()) || HUERAGA - have to develop it
                ($tx1->getBlockHeight() !== $tx2->getBlockHeight()) ||
                ($tx1->getHash() !== $tx2->getHash())
            ) {
                throw new ConflictHandlerException(
                    "Different values of transactions ( " . serialize($tx1) . " and " . serialize($tx2) . " )."
                );
            }

            $cnf1 = $tx1->getConfirmations();
            $cnf2 = $tx2->getConfirmations();
            if (((0 == $cnf1) && ($cnf2 > 0)) || ((0 == $cnf1) && ($cnf2 > 0))) {
                throw new ConflictHandlerException(
                    "Different values of confirmations of transactions, one is confirmed, 
                    another is unconfirmed ( " . serialize($tx1) . " and " . serialize($tx2) . " )."
                );
            }

            $maxAllowed = intval($this->getOption(self::OPT_CONFIRMATIONS_MAXIMUM_DIFERENCE_ALLOWED));
            if (abs($cnf1 - $cnf2) > $maxAllowed) {
                throw new ConflictHandlerException(
                    " Too big Difference (" . abs($cnf1 - $cnf2) . ") between the 
                    confirmations of transactions, maxim difference allowed is " . $maxAllowed . "
                    ( " . serialize($tx1) . " and " . serialize($tx2) . " )."
                );
            }

            $outputs1 = $tx1->getOutputs();
            $outputs2 = $tx2->getOutputs();
            if (count($outputs1) != count($outputs2)) {
                throw new ConflictHandlerException(
                    "Different sizes of outputs ( " . serialize($outputs1) . " and " . serialize($outputs2) . " )."
                );
            }
            $error = false;
            for ($i = 0, $ic = count($outputs1); $i < $ic; ++$i) {
                $output1 = & $outputs1[$i];
                $output2 = & $outputs2[$i];
                $valCmp = gmp_cmp($output1->getValue()->getGMPValue(), $output2->getValue()->getGMPValue());
                if ((0 != $valCmp) || ($output1->getScriptType() != $output2->getScriptType())) {
                    $error = true;
                    break;
                } else {
                    if (!in_array($output1->getScriptType(), ["multisig","nonstandard"])) {
                        if ($output1->getAddresses() != $output2->getAddresses()) {
                            $error = true;
                            break;
                        }
                    }
                }
            }
            if ($error) {
                throw new ConflictHandlerException(
                    "Outputs are not equal ( " . serialize($outputs1) . " ), ( " . serialize($outputs2) . " )."
                );
            }
            $inputs1 = $tx1->getInputs() ? $tx1->getInputs() : [];
            $inputs2 = $tx2->getInputs() ? $tx2->getInputs() : [];
            if (count($inputs1) != count($inputs2)) {
                throw new ConflictHandlerException(
                    "Different sizes of inputs ( " . serialize($inputs1) . " and " . serialize($inputs2) . " )."
                );
            }
            $error = false;
            for ($i = 0, $ic = count($inputs1); $i < $ic; ++$i) {
                $input1 = & $inputs1[$i];
                $input2 = & $inputs2[$i];
                $gmpCmp = gmp_cmp(
                    $input1->getOutputValue()->getGMPValue(),
                    $input2->getOutputValue()->getGMPValue()
                );
                if (($input1->getOutputIndex() != $input2->getOutputIndex()) ||
                    ( $gmpCmp != 0 ) ||
                    ($input1->getScriptType() != $input2->getScriptType())
                ) {
                    /** @noinspection PhpUnusedLocalVariableInspection */
                    $error = true;
                    break;
                } else {
                    if (!in_array($input1->getScriptType(), ["multisig","nonstandard"])) {
                        if ($input1->getAddresses() != $input2->getAddresses()) {
                            $error = true;
                            break;
                        }
                    }
                }
            }
            if ($error) {
                throw new ConflictHandlerException(
                    "Inputs are not equal ( " . serialize($inputs1) . " ), ( " . serialize($inputs2) . " )."
                );
            }
            /*for ($i = 0, $ic = count($inputs1); $i < $ic; ++$i) {
                $input = & $inputs1[$i];
                $found = false;
                for ($j = 0, $jc = count($inputs2); $j < $jc; ++$j) {
                    $inputc = & $inputs2[$j];
                    if (($input->getPrevHash() != $inputc->getPrevHash()) ||
                        ($input->getOutputIndex() != $inputc->getOutputIndex()) ||
                        ($input->getAddresses() != $inputc->getAddresses()) ||
                        //($input->getOutputValue() != $inputc->getOutputValue()
                        ( gmp_cmp($input->getOutputValue(), $inputc->getOutputValue()) != 0 ) ||
                        ($input->getScriptType() != $inputc->getScriptType())
                    ) {
                        continue;
                    }
                    $found = true;
                    break;
                }
                if (!$found) {
                    throw new ConflictHandlerException(
                        "No found output in second array ( " . serialize($input) . " )."
                    );
                }
            }*/
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($data)
    {
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $balance1 BTCValue */
        $balance1 = & $data[0];
        /** @var $balance2 BTCValue */
        $balance2 = & $data[1];
        if ((!$balance1 instanceof BTCValue) || (!$balance2 instanceof BTCValue)) {
            throw new \InvalidArgumentException("Elements of Data array must be BTCValue.");
        }
        if (gmp_cmp($balance1->getGMPValue(), $balance2->getGMPValue()) != 0) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($data)
    {
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $uncbal1 BTCValue */
        $uncbal1 = & $data[0];
        /** @var $uncbal2 BTCValue */
        $uncbal2 = & $data[1];
        if ((!$uncbal1 instanceof BTCValue) || (!$uncbal2 instanceof BTCValue)) {
            throw new \InvalidArgumentException("Elements of Data array must be BTCValue.");
        }
        if (gmp_cmp($uncbal1->getGMPValue(), $uncbal2->getGMPValue()) != 0) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($data)
    {
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $txrefs1 = & $data[0];
        $txrefs2 = & $data[1];
        if (count($txrefs1) != count($txrefs2)) {
            throw new ConflictHandlerException(
                "No equal results from different services (different size of result arrays)."
            );
        }

        for ($i = 0, $ic = count($txrefs1); $i < $ic; ++$i) {
            /** @var $tx TransactionReference */
            $tx = & $txrefs1[$i];
            $found = false;
            for ($j = 0, $jc = count($txrefs2); $j < $jc; ++$j) {
                /** @var $txc TransactionReference */
                $txc = & $txrefs2[$j];
                $gmpCmp = gmp_cmp($tx->getValue()->getGMPValue(), $txc->getValue()->getGMPValue());
                if (($tx->getBlockHeight() != $txc->getBlockHeight()) ||
                    ($tx->getConfirmations() != $txc->getConfirmations()) ||
                    ($tx->getDoubleSpend() != $txc->getDoubleSpend()) ||
                    //($tx->getSpent() != $txc->getSpent()) ||
                    ($tx->getTxHash() != $txc->getTxHash()) ||
                    ($tx->getVout() != $txc->getVout()) ||
                    ($gmpCmp != 0) ||
                    ($tx->getAddress() != $txc->getAddress())
                ) {
                    continue;
                }
                $found = true;
                break;
            }
            if (!$found) {
                throw new ConflictHandlerException(
                    "No found transaction in second array ( " . serialize($tx) . " )."
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createWallet($data)
    {
        if (!is_array($data)) {
            throw new \InvalidArgumentException("\$data variable must be the array of instances of Wallet class.");
        }

        if (1 == count($data)) {
            if (!$data[0] instanceof Wallet) {
                throw new \InvalidArgumentException("Elements of Data array must be instances of Wallet class.");
            }
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $wallet1 Wallet */
        $wallet1 = & $data[0];
        /** @var $wallet2 Wallet */
        $wallet2 = & $data[1];

        if ((!$wallet1 instanceof Wallet) || (!$wallet2 instanceof Wallet)) {
            throw new \InvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        if ($wallet1->getName() != $wallet2->getName()) {
            throw new ConflictHandlerException(
                "Different names of wallets ( \"" . $wallet1->getName() . "\" and \"" . $wallet2->getName() . "\" )."
            );
        }
        $addressesArr1 = $wallet1->getAddresses();
        $addressesArr2 = $wallet2->getAddresses();
        if ($addressesArr1 != $addressesArr2) {
            throw new ConflictHandlerException(
                "Different addresses ( " . implode(",", $addressesArr1)
                . " and " . implode(",", $addressesArr2) . " )."
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function removeAddresses($data)
    {
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $wallet1 Wallet */
        $wallet1 = & $data[0];
        /** @var $wallet2 Wallet */
        $wallet2 = & $data[1];

        if ((!$wallet1 instanceof Wallet) || (!$wallet2 instanceof Wallet)) {
            throw new \InvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        $addressesArr1 = $wallet1->getAddresses();
        $addressesArr2 = $wallet2->getAddresses();
        if ($addressesArr1 != $addressesArr2) {
            throw new ConflictHandlerException(
                "Different addresses ( \"" . implode(",", $addressesArr1)
                . "\" and \"" . implode(",", $addressesArr2) . "\" )."
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addAddresses($data)
    {
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $wallet1 Wallet */
        $wallet1 = & $data[0];
        /** @var $wallet2 Wallet */
        $wallet2 = & $data[1];

        if ((!$wallet1 instanceof Wallet) || (!$wallet2 instanceof Wallet)) {
            throw new \InvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        $addressesArr1 = $wallet1->getAddresses();
        $addressesArr2 = $wallet2->getAddresses();
        if ($addressesArr1 != $addressesArr2) {
            throw new ConflictHandlerException(
                "Different addresses ( \"" . implode(",", $addressesArr1)
                . "\" and \"" . implode(",", $addressesArr2) . "\" )."
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses($data)
    {
        if (1 == count($data)) {
            return;
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $result1 string[] */
        $result1 = & $data[0];
        /** @var $result2 string[] */
        $result2 = & $data[1];

        if ((gettype($result1) != 'array') || (gettype($result2) != 'array')) {
            throw new \InvalidArgumentException("Elements of Data array must be arrays of string.");
        }
        if ($result1 != $result2) {
            throw new ConflictHandlerException(
                "Different results from method \"getAddresses\"."
            );
        }
    }
}
