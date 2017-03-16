<?php

namespace BTCBridge\Api;

/**
 * Class TransactionInput
 *
 * A TransactionInput represents an input consumed within a transaction.
 * Typically found within an array in a Transaction.
 *
 * @package BTCBridge\Api
 *
 */
class TransactionInput
{
    /**
     * Hash of the transaction for which an output is being spent by this input.
     * Does not exist for coinbase transactions.
     * @var string
     */
    protected $prevHash = null;

    /**
     * Index in the previous transaction of the output being spent. Does not exist for coinbase transactions.
     * @var int
     */
    protected $outputIndex = null;

    /**
     * Value of the output being spent. Does not exist for coinbase transactions.
     * @var int
     */
    protected $outputValue = null;

    /**
     * Addresses referenced in the transaction output being spent.
     * @var string[]
     */
    protected $addresses = [];

    /**
     * Script type in the transaction output being spent.
     * @var string
     */
    protected $scriptType = null;


    /**
     * Hash of the transaction for which an output is being spent by this input.
     * Does not exist for coinbase transactions.
     *
     * @return string
     */
    public function getPrevHash()
    {
        return $this->prevHash;
    }

    /**
     * Hash of the transaction for which an output is being spent by this input.
     * Does not exist for coinbase transactions.
     *
     * @param string $prevHash
     * @return $this
     */
    public function setPrevHash($prevHash)
    {
        $this->prevHash = $prevHash;
        return $this;
    }

    /**
     * Index in the previous transaction of the output being spent. Does not exist for coinbase transactions.
     *
     * @return int
     */
    public function getOutputIndex()
    {
        return $this->outputIndex;
    }

    /**
     * Index in the previous transaction of the output being spent. Does not exist for coinbase transactions.
     *
     * @param int $outputIndex
     * @return $this
     */
    public function setOutputIndex($outputIndex)
    {
        $this->outputIndex = $outputIndex;
        return $this;
    }

    /**
     * Value of the output being spent. Does not exist for coinbase transactions.
     *
     * @return int
     */
    public function getOutputValue()
    {
        return $this->outputValue;
    }

    /**
     * Value of the output being spent. Does not exist for coinbase transactions.
     *
     * @param int $outputValue
     * @return $this
     */
    public function setOutputValue($outputValue)
    {
        $this->outputValue = $outputValue;
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
        sort($this->addresses);
        return $this;
    }

    /**
     * Script type in the transaction output being spent.
     *
     * @return string
     */
    public function getScriptType()
    {
        return $this->scriptType;
    }

    /**
     * Script type in the transaction output being spent.
     *
     * @param string $scriptType
     * @return $this
     */
    public function setScriptType($scriptType)
    {
        $this->scriptType = $scriptType;
        return $this;
    }
}
