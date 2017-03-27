<?php

namespace BTCBridge\Api;

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
    /**
     * Address which received the BTC from this output
     * @var int
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
     * Index of the input in the transaction. It's a negative number for an output.
     * @var int
     */
    protected $txInputN = null;

    /**
     * Index of the output in the transaction. It's a negative number for an input.
     * @var int
     */
    protected $txOutputN = null;

    /**
     * The value transferred by the particular input or output.
     * @var int
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
     * Whether the transaction is a double spend (see Zero Confirmations).
     * @var bool
     */
    protected $doubleSpend = null;

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
     * Index of the input in the transaction. It's a negative number for an output.
     *
     * @return int
     */
    public function getTxInputN()
    {
        return $this->txInputN;
    }

    /**
     * Index of the input in the transaction. It's a negative number for an output.
     *
     * @param int $txInputN
     * @return $this
     */
    public function setTxInputN($txInputN)
    {
        $this->txInputN = $txInputN;
        return $this;
    }

    /**
     * Index of the output in the transaction. It's a negative number for an input.
     *
     * @return int
     */
    public function getTxOutputN()
    {
        return $this->txOutputN;
    }

    /**
     * Index of the output in the transaction. It's a negative number for an input.
     *
     * @param int $txOutputN
     * @return $this
     */
    public function setTxOutputN($txOutputN)
    {
        $this->txOutputN = $txOutputN;
        return $this;
    }

    /**
     * The value transferred by the particular input or output.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * The value transferred by the particular input or output.
     *
     * @param int $value
     * @return $this
     */
    public function setValue($value)
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
     Is $o equal current object
     * @param TransactionReference $o
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
            ($this->getTxInputN() == $o->getTxInputN())
                &&
            ($this->getTxOutputN() == $o->getTxOutputN())
                &&
            ($this->getValue() == $o->getValue())
                &&
            ($this->getSpent() == $o->getSpent())
                &&
            ($this->getConfirmations() == $o->getConfirmations())
                &&
            ($this->getAddress() == $o->getAddress())
        ;
    }
}
