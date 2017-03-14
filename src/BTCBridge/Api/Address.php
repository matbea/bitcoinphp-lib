<?php

namespace BTCBridge\Api;

/**
 * Class Address
 *
 * An Address represents a public address on a blockchain, and contains information about the state of balances and
 * transactions related to this address.
 *
 * @package BTCBridge\Api
 *
 * @property string address Only present when object represents an address
 * @property \BTCBridge\Api\Wallet wallet Only present when object represents a wallet
 * @property int balance
 * @property int unconfirmed_balance
 * @property int final_balance
 * @property \BTCBridge\Api\TransactionReference[] txrefs
 * @property \BTCBridge\Api\TransactionReference[] unconfirmed_txrefs
 */
class Address
{

    /**
     * The requested address.
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * The requested address.
     *
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return \BTCBridge\Api\Wallet
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * @param \BTCBridge\Api\Wallet
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;
    }


    /**
     * Balance on the specified address, in satoshi. This is the difference between outputs and inputs on this address,
     * for transactions that have been included into a block (confirmations > 0)
     *
     * @return int
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Balance on the specified address, in satoshi. This is the difference between outputs and inputs on this address,
     * for transactions that have been included into a block (confirmations > 0)
     *
     * @param int $balance
     * @return $this
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
        return $this;
    }

    /**
     * Balance of unconfirmed transactions for this address, in satoshi. Can be negative
     * (if unconfirmed transactions are just spending.). Only unconfirmed transactions (haven't made it into a block)
     * are included.
     *
     * @return int
     */
    public function getUnconfirmedBalance()
    {
        return $this->unconfirmed_balance;
    }

    /**
     * Balance of unconfirmed transactions for this address, in satoshi. Can be negative
     * (if unconfirmed transactions are just spending.). Only unconfirmed transactions (haven't made it into a block)
     * are included.
     *
     * @param int $unconfirmed_balance
     * @return $this
     */
    public function setUnconfirmedBalance($unconfirmed_balance)
    {
        $this->unconfirmed_balance = $unconfirmed_balance;
        return $this;
    }

    /**
     * Balance including confirmed and unconfirmed transactions for this address, in satoshi.
     *
     * @return int
     */
    public function getFinalBalance()
    {
        return $this->final_balance;
    }

    /**
     * Balance including confirmed and unconfirmed transactions for this address, in satoshi.
     *
     * @param int $final_balance
     * @return $this
     */
    public function setFinalBalance($final_balance)
    {
        $this->final_balance = $final_balance;
        return $this;
    }


    /**
     * Append TXRef to the list.
     *
     * @param \BTCBridge\Api\TransactionReference $txref
     * @return $this
     */
    public function addTxref($txref)
    {
        if (!$this->getTxrefs()) {
            return $this->setTxrefs(array($txref));
        } else {
            return $this->setTxrefs(
                array_merge($this->getTxrefs(), array($txref))
            );
        }
    }

    /**
     * All transaction inputs and outputs for the specified address.
     *
     * @return \BTCBridge\Api\TransactionReference[]
     */
    public function getTxrefs()
    {
        return (isset($this->txrefs)) ? $this->txrefs : false;
    }

    /**
     * All transaction inputs and outputs for the specified address.
     *
     * @param \BTCBridge\Api\TransactionReference[] $txrefs
     *
     * @return $this
     */
    public function setTxrefs($txrefs)
    {
        $this->txrefs = $txrefs;
        return $this;
    }

    /**
     * Append Unconfirmed TXRef to the list.
     *
     * @param \BTCBridge\Api\TransactionReference $unconfirmedTxref
     * @return $this
     */
    public function addUnconfirmedTxref($unconfirmedTxref)
    {
        if (!$this->getUnconfirmedTxrefs()) {
            return $this->setUnconfirmedTxrefs(array($unconfirmedTxref));
        } else {
            return $this->setUnconfirmedTxrefs(
                array_merge($this->getUnconfirmedTxrefs(), array($unconfirmedTxref))
            );
        }
    }

    /**
     * All unconfirmed transaction inputs and outputs for the specified address.
     *
     * @return \BTCBridge\Api\TransactionReference[]
     */
    public function getUnconfirmedTxrefs()
    {
        return $this->unconfirmed_txrefs;
    }

    /**
     * All unconfirmed transaction inputs and outputs for the specified address.
     *
     * @param \BTCBridge\Api\TransactionReference[] $unconfirmed_txrefs
     * @return $this
     */
    public function setUnconfirmedTxrefs($unconfirmed_txrefs)
    {
        $this->unconfirmed_txrefs = $unconfirmed_txrefs;
        return $this;
    }

    /**
     * All transactions refs for confirmed and unconfirmed transactions.
     *
     * @return \BTCBridge\Api\TransactionReference[]
     */
    public function getAllTxrefs()
    {
        $allTxrefs = array();
        if (is_array($this->txrefs)) {
            $allTxrefs = array_merge($allTxrefs, $this->txrefs);
        }
        if (is_array($this->unconfirmed_txrefs)) {
            $allTxrefs = array_merge($allTxrefs, $this->unconfirmed_txrefs);
        }

        if (count($allTxrefs) == 0) {
            return null;
        } else {
            return $allTxrefs;
        }
    }
}
