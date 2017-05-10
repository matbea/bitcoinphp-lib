<?php

namespace BTCBridge\Api;

//use BTCBridge\Api\BTCValue;

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
     * Index in the previous transaction of the output being spent. Does not exist for coinbase transactions.
     * @var int
     */
    protected $outputIndex;

    /**
     * Value of the output being spent. Does not exist for coinbase transactions.
     * @var BTCValue
     */
    protected $outputValue;

    /**
     * Addresses referenced in the transaction output being spent.
     * @var string[]
     */
    protected $addresses = [];

    /**
     * Script type in the transaction output being spent.
     * @var string
     */
    protected $scriptType;

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
     * Sets index in the previous transaction of the output being spent.
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
     * @return BTCValue
     */
    public function getOutputValue()
    {
        return $this->outputValue;
    }

    /**
     * Value of the output being spent. Does not exist for coinbase transactions.
     *
     * @param BTCValue $outputValue
     * @return $this
     */
    public function setOutputValue(BTCValue $outputValue)
    {
        $this->outputValue = $outputValue;
        return $this;
    }

    /**
     * Append Address to the list.
     *
     * @param string $address
     * @return \string[]
     */
    public function addAddress($address)
    {
        if (!$this->getAddresses()) {
            $this->setAddresses(array($address));
        } else {
            $this->setAddresses(array_merge($this->getAddresses(), array($address)));
        }
        return $this->getAddresses();
    }

    /**
     * Addresses referenced in the transaction output being spent.
     *
     * @return \string[]
     */
    public function getAddresses()
    {
        return isset($this->addresses) ? $this->addresses : [];
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
