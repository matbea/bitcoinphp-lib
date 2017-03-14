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
 * @property int block_height
 * @property string tx_hash
 * @property int tx_input_n
 * @property int tx_output_n
 * @property int value
 * @property bool spent
 * @property int confirmations
 * @property string confirmed
 * @property bool double_spend
 */
class TransactionReference
{
    /**
     * Whether the transaction is a double spend (see Zero Confirmations).
     *
     * @return boolean
     */
    public function getDoubleSpend()
    {
        return $this->double_spend;
    }

    /**
     * Whether the transaction is a double spend (see Zero Confirmations).
     *
     * @param boolean $double_spend
     * @return $this
     */
    public function setDoubleSpend($double_spend)
    {
        $this->double_spend = $double_spend;
        return $this;
    }

    /**
     * One of the transaction hashes for the specified address.
     *
     * @return string
     */
    public function getTxHash()
    {
        return $this->tx_hash;
    }

    /**
     * One of the transaction hashes for the specified address.
     *
     * @param string $tx_hash
     * @return $this
     */
    public function setTxHash($tx_hash)
    {
        $this->tx_hash = $tx_hash;
        return $this;
    }

    /**
     * Height of the block for the transaction.
     *
     * @return int
     */
    public function getBlockHeight()
    {
        return $this->block_height;
    }

    /**
     * Height of the block for the transaction.
     *
     * @param int $block_height
     * @return $this
     */
    public function setBlockHeight($block_height)
    {
        $this->block_height = $block_height;
        return $this;
    }

    /**
     * Index of the input in the transaction. It's a negative number for an output.
     *
     * @return int
     */
    public function getTxInputN()
    {
        return $this->tx_input_n;
    }

    /**
     * Index of the input in the transaction. It's a negative number for an output.
     *
     * @param int $tx_input_n
     * @return $this
     */
    public function setTxInputN($tx_input_n)
    {
        $this->tx_input_n = $tx_input_n;
        return $this;
    }

    /**
     * Index of the output in the transaction. It's a negative number for an input.
     *
     * @return int
     */
    public function getTxOutputN()
    {
        return $this->tx_output_n;
    }

    /**
     * Index of the output in the transaction. It's a negative number for an input.
     *
     * @param int $tx_output_n
     * @return $this
     */
    public function setTxOutputN($tx_output_n)
    {
        $this->tx_output_n = $tx_output_n;
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
}
