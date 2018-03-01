<?php

namespace BTCBridge\Api;

use BTCBridge\Exception\BERuntimeException;

/**
 * Class TransactionReference
 *
 * A TXRef object represents summarized data about a transaction input or output. Typically found in an array
 * within an Address object, which is usually returned from the standard Address Endpoint.
 *
 * @package BTCBridge\Api
 *
 */
class TransactionReference
{

    /** This group constants describe the categories of Transaction references */
    const CATEGORY_RECEIVE  = 'receive';
    const CATEGORY_SEND     = 'send';

    /**
     * Address which received the BTC from this output
     * @var string
     */
    protected $address = null;


    /**
     * Height of the block for the transaction.
     * @var int
     */
    protected $blockHeight = null;

    /**
     * One of the transaction hashes for the specified address.
     * @var string
     */
    protected $txHash = null;

    /**
     * For an output, the output index (vout) for this output in this transaction.
     * For an input, the output index for the output being spent in its transaction.
     * @var int
     */
    protected $vout = null;

    /**
     * The value transferred by the particular input or output.
     * @var BTCValue
     */
    protected $value = null;

    /**
     * Is 'true' if the output was spent.
     * @var bool
     */
    protected $spent = null;

    /**
     * Number of confirmations for the transaction.
     * @var int
     */
    protected $confirmations = null;

    /**
     * Time at which transaction was included in a block; only present for confirmed transactions.
     * @var int
     */
    protected $confirmed = null;


    /**
     * Whether the transaction is a double spend (see Zero Confirmations).
     * @var bool
     */
    protected $doubleSpend = null;

    /**
     * Category Set to one of the following values: send if sending payment
     * receive if this wallet received payment in a regular transaction
     * @var string
     */
    protected $category = null;


    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Whether the transaction is a double spend (see Zero Confirmations).
     *
     * @return boolean
     */
    public function getDoubleSpend()
    {
        return $this->doubleSpend;
    }

    /**
     * Whether the transaction is a double spend (see Zero Confirmations).
     *
     * @param boolean $doubleSpend
     * @return $this
     */
    public function setDoubleSpend($doubleSpend)
    {
        $this->doubleSpend = $doubleSpend;
        return $this;
    }

    /**
     * One of the transaction hashes for the specified address.
     *
     * @return string
     */
    public function getTxHash()
    {
        return $this->txHash;
    }

    /**
     * One of the transaction hashes for the specified address.
     *
     * @param string $txHash
     * @return $this
     */
    public function setTxHash($txHash)
    {
        $this->txHash = $txHash;
        return $this;
    }

    /**
     * Height of the block for the transaction.
     *
     * @return int
     */
    public function getBlockHeight()
    {
        return $this->blockHeight;
    }

    /**
     * Height of the block for the transaction.
     *
     * @param int $blockHeight
     * @return $this
     */
    public function setBlockHeight($blockHeight)
    {
        $this->blockHeight = $blockHeight;
        return $this;
    }

    /**
     * Index of the output in the transaction.
     *
     * @return int
     */
    public function getVout()
    {
        return $this->vout;
    }

    /**
     * Index of the output in the transaction.
     *
     * @param int $vout
     * @return $this
     */
    public function setVout($vout)
    {
        $this->vout = $vout;
        return $this;
    }

    /**
     * The value transferred by the particular input or output.
     *
     * @return BTCValue
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * The value transferred by the particular input or output.
     *
     * @param BTCValue $value
     * @return $this
     */
    public function setValue(BTCValue $value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Is 'true' if the output was spent.
     *
     * @return boolean
     */
    public function getSpent()
    {
        return $this->spent;
    }

    /**
     * Is 'true' if the output was spent.
     *
     * @param boolean $spent
     * @return $this
     */
    public function setSpent($spent)
    {
        $this->spent = $spent;
        return $this;
    }

    /**
     * Returns category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Sets category
     *
     * @param string $category
     * @return $this
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Number of confirmations for the transaction.
     *
     * @return int
     */
    public function getConfirmations()
    {
        return $this->confirmations;
    }

    /**
     * Number of confirmations for the transaction.
     *
     * @param int $confirmations
     * @return $this
     */
    public function setConfirmations($confirmations)
    {
        $this->confirmations = $confirmations;
        return $this;
    }

    /**
     * Time at which transaction was included in a block; only present for confirmed transactions.
     *
     * @return int
     */
    public function getConfirmed()
    {
        return $this->confirmed;
    }

    /**
     * Time at which transaction was included in a block; only present for confirmed transactions.
     *
     * @param int $confirmed
     * @return $this
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    /**
     Is $o equal current object
     * @param TransactionReference $o
     *
     * @throws BERuntimeException if case of any error of this type
     *
     * @return boolean
     */
    public function isEqual(TransactionReference $o)
    {
        return
            ($this->getDoubleSpend() == $o->getDoubleSpend())
                &&
            ($this->getTxHash() == $o->getTxHash())
                &&
            ($this->getBlockHeight() == $o->getBlockHeight())
                &&
            ($this->getVout() == $o->getVout())
                &&
            ($this->getValue()->getSatoshiValue() == $o->getValue()->getSatoshiValue())
                &&
            ($this->getSpent() == $o->getSpent())
                &&
            ($this->getConfirmations() == $o->getConfirmations())
                &&
            ($this->getCategory() == $o->getCategory())
                &&
            ($this->getAddress() == $o->getAddress())
            &&
            ($this->getConfirmed() == $o->getConfirmed())
        ;
    }
}
