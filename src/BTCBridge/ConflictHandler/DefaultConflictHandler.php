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

//use BitWasp\Bitcoin\Transaction\Transaction;
use BTCBridge\Api\Transaction;
use BTCBridge\Api\Address;
use BTCBridge\Api\Wallet;
use BTCBridge\Exception\ConflictHandlerException;

/**
 * Default Conflict Handler class providing the default behaviour
 *
 * @author Matbea <mail@matbea.com>
 */
class DefaultConflictHandler implements ConflictHandlerInterface
{

    /**
     * {@inheritdoc}
     */
    public function listtransactions($data)
    {
        //$uniq_results = array_map('unserialize',array_unique(array_map('serialize', $data)) );
        //$uniq_results = array_unique($results);
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $address1 = &$data[0];
        $address2 = &$data[1];
        if ((!$address1 instanceof Address) || (!$address2 instanceof Address)) {
            throw new \InvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        $addr1 = $address1->getAddress();
        $addr2 =  $address2->getAddress();
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
            sort($addressesArr1);
            sort($addressesArr2);
            if ($addressesArr1 != $addressesArr2) {
                throw new ConflictHandlerException(
                    "Different addresses ( " . implode(",", $addressesArr1)
                    . " and " . implode(",", $addressesArr2) . " )."
                );
            }
        }

        $balance1 =  $address2->getBalance();
        $balance2 =  $address2->getBalance();
        if ($balance1 !== $balance2) {
            throw new ConflictHandlerException(
                "Different values of balances ( " . $balance1 . " and " . $balance2 . " )."
            );
        }
        $unconfirmedBalance1 =  $address1->getUnconfirmedBalance();
        $unconfirmedBalance2 =  $address2->getUnconfirmedBalance();
        if ($unconfirmedBalance1 !== $unconfirmedBalance2) {
            throw new ConflictHandlerException(
                "Different values of unconfirmed balances ( " .
                $unconfirmedBalance1 . " and " . $unconfirmedBalance2 . " )."
            );
        }
        $finalBalance1 =  $address1->getFinalBalance();
        $finalBalance2 =  $address2->getFinalBalance();
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

        for ($i = 0; $i < count($txrefs1); ++$i) {
            $tx = &$txrefs1[$i];
            $found = false;
            for ($j = 0; $j < count($txrefs2); ++$j) {
                $txc = &$txrefs2[$j];
                if (($tx->getBlockHeight() != $txc->getBlockHeight()) ||
                    ($tx->getConfirmations()  != $txc->getConfirmations()) ||
                    ($tx->getDoubleSpend() != $txc->getDoubleSpend()) ||
                    ($tx->getSpent() != $txc->getSpent()) ||
                    ($tx->getTxHash() != $txc->getTxHash()) ||
                    ($tx->getTxInputN() != $txc->getTxInputN()) ||
                    ($tx->getTxOutputN()  != $txc->getTxOutputN()) ||
                    ($tx->getValue()  != $txc->getValue())
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

        for ($i = 0; $i < count($unconfirmedtxrefs1); ++$i) {
            $tx = &$unconfirmedtxrefs1[$i];
            $found = false;
            for ($j = 0; $j < count($unconfirmedtxrefs2); ++$j) {
                $txc = &$txrefs2[$j];
                if (($tx->getBlockHeight() != $txc->getBlockHeight()) ||
                    ($tx->getConfirmations()  != $txc->getConfirmations()) ||
                    ($tx->getDoubleSpend() != $txc->getDoubleSpend()) ||
                    ($tx->getSpent() != $txc->getSpent()) ||
                    ($tx->getTxHash() != $txc->getTxHash()) ||
                    ($tx->getTxInputN() != $txc->getTxInputN()) ||
                    ($tx->getTxOutputN()  != $txc->getTxOutputN()) ||
                    ($tx->getValue()  != $txc->getValue())
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
        return $address1;
    }

    /**
     * {@inheritdoc}
     */
    public function gettransaction($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $tx1 = &$data[0];
        $tx2 = &$data[1];
        if ((!$tx1 instanceof Transaction) || (!$tx2 instanceof Transaction)) {
            throw new \InvalidArgumentException("Elements of Data array must be instances of Transaction class.");
        }
        if (($tx1->getBlockHash() !== $tx2->getBlockHash()) ||
            ($tx1->getConfirmed() !== $tx2->getConfirmed()) ||
            ($tx1->getLockTime() !== $tx2->getLockTime()) ||
            ($tx1->getDoubleSpend() !== $tx2->getDoubleSpend()) ||
            ($tx1->getVinSz() !== $tx2->getVinSz()) ||
            ($tx1->getVoutSz() !== $tx2->getVoutSz()) ||
            ($tx1->getConfirmations() !== $tx2->getConfirmations()) ||
            ($tx1->getBlockHeight() !== $tx2->getBlockHeight()) ||
            ($tx1->getHash() !== $tx2->getHash())
        ) {
            throw new ConflictHandlerException(
                "Different values of transactions ( " . serialize($tx1) . " and " . serialize($tx2) . " )."
            );
        }
        $addressesArr1 = $tx1->getAddresses();
        $addressesArr2 = $tx2->getAddresses();
        sort($addressesArr1);
        sort($addressesArr2);
        if ($addressesArr1 != $addressesArr2) {
            throw new ConflictHandlerException(
                "Different addresses ( " . implode(",", $addressesArr1)
                . " and " . implode(",", $addressesArr2) . " )."
            );
        }
        $outputs1 = $tx1->getOutputs() ? $tx1->getOutputs() : [];
        $outputs2 = $tx2->getOutputs() ? $tx2->getOutputs() : [];
        if (count($outputs1) != count($outputs2)) {
            throw new ConflictHandlerException(
                "Different sizes of outputs ( " . serialize($outputs1) . " and " . serialize($outputs2) . " )."
            );
        }
        for ($i = 0; $i < count($outputs1); ++$i) {
            $output = &$outputs1[$i];
            $found = false;
            for ($j = 0; $j < count($outputs2); ++$j) {
                $outputc = &$outputs2[$j];
                if (($output->getValue() != $outputc->getValue()) ||
                    ($output->getScriptType()  != $outputc->getScripttype()) ||
                    ($output->getSpentBy()  != $outputc->getSpentBy()) ||
                    ($output->getAddresses()  != $outputc->getAdresses())
                ) {
                    continue;
                }
                $found = true;
                break;
            }
            if (!$found) {
                throw new ConflictHandlerException(
                    "No found output in second array ( " . serialize($output) . " )."
                );
            }
        }
        $inputs1 = $tx1->getInputs() ? $tx1->getInputs() : [];
        $inputs2 = $tx2->getInputs() ? $tx2->getInputs() : [];
        if (count($inputs1) != count($inputs2)) {
            throw new ConflictHandlerException(
                "Different sizes of inputs ( " . serialize($inputs1) . " and " . serialize($inputs2) . " )."
            );
        }
        for ($i = 0; $i < count($inputs1); ++$i) {
            $input = &$inputs1[$i];
            $found = false;
            for ($j = 0; $j < count($inputs2); ++$j) {
                $inputc = &$inputs2[$j];
                if (($input->getPrevHash() != $inputc->getPrevHash()) ||
                    ($input->getOutputIndex()  != $inputc->getOutputIndex()) ||
                    ($input->getAddresses()  != $inputc->getAddresses()) ||
                    ($input->getScriptType()  != $inputc->getScriptType())
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
        }
        return $tx1;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $balance1 = &$data[0];
        $balance2 = &$data[1];
        if ((gettype($balance1)!='integer') && (gettype($balance2)!='integer')) {
            throw new \InvalidArgumentException("Elements of Data array must be integer.");
        }
        if ($balance1 != $balance2) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $balance1;
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $unconfirmedbalance1 = &$data[0];
        $unconfirmedbalance2 = &$data[1];
        if ((gettype($unconfirmedbalance1)!='integer') && (gettype($unconfirmedbalance2)!='integer')) {
            throw new \InvalidArgumentException("Elements of Data array must be integer.");
        }
        if ($unconfirmedbalance1 != $unconfirmedbalance2) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $unconfirmedbalance1;
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new \InvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $txrefs1 = &$data[0];
        $txrefs2 = &$data[1];
        if (count($txrefs1) != count($txrefs2)) {
            throw new ConflictHandlerException(
                "No equal results from different services (different size of result arrays)."
            );
        }

        for ($i = 0; $i < count($txrefs1); ++$i) {
            $tx = &$txrefs1[$i];
            $found = false;
            for ($j = 0; $j < count($txrefs2); ++$j) {
                $txc = &$txrefs2[$j];
                if (($tx->getBlockHeight() != $txc->getBlockHeight()) ||
                    ($tx->getConfirmations()  != $txc->getConfirmations()) ||
                    ($tx->getDoubleSpend() != $txc->getDoubleSpend()) ||
                    ($tx->getSpent() != $txc->getSpent()) ||
                    ($tx->getTxHash() != $txc->getTxHash()) ||
                    ($tx->getTxInputN() != $txc->getTxInputN()) ||
                    ($tx->getTxOutputN()  != $txc->getTxOutputN()) ||
                    ($tx->getValue()  != $txc->getValue())
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
        return $txrefs1;
    }

    /**
     * {@inheritdoc}
     */
    /*public function sendrawtransaction($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }*/

    /**
     * {@inheritdoc}
     */
    public function createwallet($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function addaddresses($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }
}
