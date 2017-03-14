<?php

namespace BTCBridge\Api;

/**
 * Class Transaction
 *
 * A Transaction represents the current state of a particular transaction from either a Block within a Blockchain,
 * or an unconfirmed transaction that has yet to be included in a Block.
 *
 * @package BTCBridge\Api
 *
 * @property string block_hash
 * @property int block_height
 * @property string hash
 * @property string[] addresses
 * @property string confirmed
 * @property int lock_time
 * @property bool double_spend
 * @property integer vin_sz
 * @property integer vout_sz
 * @property int confirmations
 * @property \BTCBridge\Api\TransactionInput[] inputs
 * @property \BTCBridge\Api\TransactionOutput[] outputs
 */
class Transaction
{
    /**
     * BlockHash of the block the transaction is in. Only exists for confirmed transactions.
     *
     * @param $block_hash
     * @return $this
     */
    public function setBlockHash($block_hash)
    {
        $this->block_hash = $block_hash;
        return $this;
    }

    /**
     * BlockHash of the block the transaction is in. Only exists for confirmed transactions.
     *
     * @return string
     */
    public function getBlockHash()
    {
        return $this->block_hash;
    }


    /**
     * Array of Bitcoin addresses involved in the transaction.
     *
     * @return \string[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * Array of Bitcoin addresses involved in the transaction.
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
     * Time at which transaction was included in a block; only present for confirmed transactions.
     *
     * @return string
     */
    public function getConfirmed()
    {
        return $this->confirmed;
    }

    /**
     * Time at which transaction was included in a block; only present for confirmed transactions.
     *
     * @param string $confirmed
     * @return $this
     */
    public function setConfirmed($confirmed)
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    /**
     * @return int
     */
    public function getLockTime()
    {
        return $this->lock_time;
    }

    /**
     * @param int $lock_time
     * @return $this
     */
    public function setLockTime($lock_time)
    {
        $this->lock_time = $lock_time;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDoubleSpend()
    {
        return $this->double_spend;
    }

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
     * Total number of inputs
     *
     * @return int
     */
    public function getVinSz()
    {
        return $this->vin_sz;
    }

    /**
     * Total number of inputs
     *
     * @param int $vin_sz
     * @return $this
     */
    public function setVinSz($vin_sz)
    {
        $this->vin_sz = $vin_sz;
        return $this;
    }

    /**
     * Total number of outputs
     *
     * @return int
     */
    public function getVoutSz()
    {
        return $this->vout_sz;
    }

    /**
     * Total number of outputs
     *
     * @param int $vout_sz
     * @return $this
     */
    public function setVoutSz($vout_sz)
    {
        $this->vout_sz = $vout_sz;
        return $this;
    }

    /**
     * Number of subsequent blocks, including the block the transaction is in.
     * Unconfirmed transactions have 0 for confirmation.
     *
     * @return int
     */
    public function getConfirmations()
    {
        return $this->confirmations;
    }

    /**
     * Number of subsequent blocks, including the block the transaction is in.
     * Unconfirmed transactions have 0 for confirmation.
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
     * Append TransactionInput to the list.
     *
     * @param \BTCBridge\Api\TransactionInput $input
     * @return \BTCBridge\Api\TransactionInput[]
     */
    public function addInput($input)
    {
        if (!$this->getInputs()) {
            return $this->setInputs(array($input));
        } else {
            return $this->setInputs(
                array_merge($this->getInputs(), array($input))
            );
        }
    }

    /**
     * Array of inputs, limited to 20. Use paging to get more inputs (see section on blocks) with
     * instart and limit URL parameters.
     *
     * @return \BTCBridge\Api\TransactionInput[]
     */
    public function getInputs()
    {
        return isset($this->inputs) ? $this->inputs : false;
    }

    /**
     * Array of inputs, limited to 20. Use paging to get more inputs (see section on blocks) with
     * instart and limit URL parameters.
     *
     * @param \BTCBridge\Api\TransactionInput[] $inputs
     * @return $this
     */
    public function setInputs($inputs)
    {
        $this->inputs = $inputs;
        return $this;
    }

    /**
     * Append TransactionOutput to the list.
     *
     * @param \BTCBridge\Api\TransactionOutput $output
     * @return \BTCBridge\Api\TransactionOutput[]
     */
    public function addOutput($output)
    {
        if (!$this->getOutputs()) {
            return $this->setOutputs(array($output));
        } else {
            return $this->setOutputs(
                array_merge($this->getOutputs(), array($output))
            );
        }
    }

    /**
     * Array of outputs, limited to 20. Use paging to get more outputs (see section on blocks) with
     * outstart and limit URL parameters.
     *
     * @return \BTCBridge\Api\TransactionOutput[]
     */
    public function getOutputs()
    {
        return isset($this->outputs) ? $this->outputs : false; //property_exists may be help too
    }

    /**
     * Array of outputs, limited to 20. Use paging to get more outputs (see section on blocks) with
     * outstart and limit URL parameters.
     *
     * @param \BTCBridge\Api\TransactionOutput[] $outputs
     * @return $this
     */
    public function setOutputs($outputs)
    {
        $this->outputs = $outputs;
        return $this;
    }

    /**
     * Height of the block the transaction is in. Only exists for confirmed transactions.
     *
     * @param $block_height
     * @return $this
     */
    public function setBlockHeight($block_height)
    {
        $this->block_height = $block_height;
        return $this;
    }

    /**
     * Height of the block the transaction is in. Only exists for confirmed transactions.
     *
     * @return string
     */
    public function getBlockHeight()
    {
        return $this->block_height;
    }

    /**
     * Hash of the transaction. While hashes are reasonably unique, using them as identifiers may be unsafe.
     * https://en.bitcoin.it/wiki/Transaction_Malleability
     *
     * @param $hash
     * @return $this
     */
    public function setHash($hash)
    {
        $this->hash = $hash;
        return $this;
    }

    /**
     * Hash of the transaction. While hashes are reasonably unique, using them as identifiers may be unsafe.
     * https://en.bitcoin.it/wiki/Transaction_Malleability
     *
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }
}
