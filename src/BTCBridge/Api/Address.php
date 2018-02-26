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
 */
class Address
{

    /**
     * Only present when object represents an address
     * @var string
     */
    protected $address = null;

    /**
     * Only present when object represents a wallet
     * @var \BTCBridge\Api\Wallet
     */
    protected $wallet = null;


    /**
     * All transaction inputs and outputs for the specified address.
     * @var \BTCBridge\Api\TransactionReference[]
     */
    protected $txrefs = []; //

    /**
     * All unconfirmed transaction inputs and outputs for the specified address.
     * @var \BTCBridge\Api\TransactionReference[]
     */
    protected $unconfirmedTxrefs = [];


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
     * Append TXRef to the list.
     *
     * @param \BTCBridge\Api\TransactionReference $txref
     * @return $this
     */
    public function addTxref($txref)
    {
        if (!$this->getTxrefs()) {
            return $this->setTxrefs([$txref]);
        } else {
            return $this->setTxrefs(
                array_merge($this->getTxrefs(), [$txref])
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
        return (isset($this->txrefs)) ? $this->txrefs : [];
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
            return $this->setUnconfirmedTxrefs([$unconfirmedTxref]);
        } else {
            return $this->setUnconfirmedTxrefs(
                array_merge($this->getUnconfirmedTxrefs(), [$unconfirmedTxref])
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
        return $this->unconfirmedTxrefs;
    }

    /**
     * All unconfirmed transaction inputs and outputs for the specified address.
     *
     * @param \BTCBridge\Api\TransactionReference[] $unconfirmedTxrefs
     * @return $this
     */
    public function setUnconfirmedTxrefs($unconfirmedTxrefs)
    {
        $this->unconfirmedTxrefs = $unconfirmedTxrefs;
        return $this;
    }

    /**
     * All transactions refs for confirmed and unconfirmed transactions.
     *
     * @return \BTCBridge\Api\TransactionReference[]
     */
    public function getAllTxrefs()
    {
        $allTxrefs = [];
        if (is_array($this->txrefs)) {
            $allTxrefs = array_merge($allTxrefs, $this->txrefs);
        }
        if (is_array($this->unconfirmedTxrefs)) {
            $allTxrefs = array_merge($allTxrefs, $this->unconfirmedTxrefs);
        }

        if (count($allTxrefs) == 0) {
            return null;
        } else {
            return $allTxrefs;
        }
    }
}
