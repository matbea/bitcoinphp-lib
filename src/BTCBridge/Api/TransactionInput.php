<?php

namespace BTCBridge\Api;

/**
 * Class TransactionInput
 *
 * A TransactionInput represents an input consumed within a transaction. Typically found within an array in a Transaction.
 *
 * @package BTCBridge\Api
 *
 * @property string prev_hash
 * @property int output_index
 * @property int output_value
 * @property string[] addresses
 * @property string script_type
 */
class TransactionInput
{

    /**
     * Hash of the transaction for which an output is being spent by this input. Does not exist for coinbase transactions.
     *
     * @return string
     */
    public function getPrevHash()
    {
        return $this->prev_hash;
    }

    /**
     * Hash of the transaction for which an output is being spent by this input. Does not exist for coinbase transactions.
     *
     * @param string $prev_hash
     * @return $this
     */
    public function setPrevHash($prev_hash)
    {
        $this->prev_hash = $prev_hash;
        return $this;
    }

    /**
     * Index in the previous transaction of the output being spent. Does not exist for coinbase transactions.
     *
     * @return int
     */
    public function getOutputIndex()
    {
        return $this->output_index;
    }

    /**
     * Index in the previous transaction of the output being spent. Does not exist for coinbase transactions.
     *
     * @param int $output_index
     * @return $this
     */
    public function setOutputIndex($output_index)
    {
        $this->output_index = $output_index;
        return $this;
    }

    /**
     * Value of the output being spent. Does not exist for coinbase transactions.
     *
     * @return int
     */
    public function getOutputValue()
    {
        return $this->output_value;
    }

    /**
     * Value of the output being spent. Does not exist for coinbase transactions.
     *
     * @param int $output_value
     * @return $this
     */
    public function setOutputValue($output_value)
    {
        $this->output_value = $output_value;
        return $this;
    }

    /**
     * Append Address to the list.
     *
     * @param string $address
     * @return $this
     */
    public function addAddress($address)
    {
        if (!$this->getAddresses()) {
            return $this->setAddresses(array($address));
        } else {
            return $this->setAddresses(
                array_merge($this->getAddresses(), array($address))
            );
        }
    }

    /**
     * Addresses referenced in the transaction output being spent.
     *
     * @return \string[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * Addresses referenced in the transaction output being spent.
     *
     * @param \string[] $addresses
     * @return $this
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
        return $this;
    }

    /**
     * Remove Address from the list.
     *
     * @param string $address
     * @return $this
     */
    public function removeAddress($address)
    {
        return $this->setAddresses(
            array_diff($this->getAddresses(), array($address))
        );
    }

    /**
     * Script type in the transaction output being spent.
     *
     * @return string
     */
    public function getScriptType()
    {
        return $this->script_type;
    }

    /**
     * Script type in the transaction output being spent.
     *
     * @param string $script_type
     * @return $this
     */
    public function setScriptType($script_type)
    {
        $this->script_type = $script_type;
        return $this;
    }

}