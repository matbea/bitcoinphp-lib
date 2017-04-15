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
 */
class Transaction
{
    /**
     * BlockHash of the block the transaction is in. Only exists for confirmed transactions.
     * @var string
     */
    protected $blockHash = null;

    /**
     * Height of the block the transaction is in. Only exists for confirmed transactions.
     * @var int
     */
    protected $blockHeight = null;

    /**
     * Hash of the transaction.
     * @var string
     */
    protected $hash = null;

    /**
     * Array of Bitcoin addresses involved in the transaction.
     * @var string[]
     */
    protected $addresses = null;

    /**
     * Time at which transaction was included in a block; only present for confirmed transactions.
     * @var int
     */
    protected $confirmed = null;

    /**
     * Lock time of transaction
     * @var int
     */
    protected $lockTime = null;

    /**
     * Whether the transaction is a double spend.
     * @var bool
     */
    protected $doubleSpend = null;

    /**
     * Total number of inputs
     * @var int
     */
    protected $vinSz = null;

    /**
     * Total number of outputs
     * @var int
     */
    protected $voutSz = null;

    /**
     * Number of subsequent blocks, including the block the transaction is in.
     * Unconfirmed transactions have 0 for confirmation.
     * @var int
     */
    protected $confirmations = null;

    /**
     * Array of inputs, limited to 20. Use paging to get more inputs (see section on blocks) with
     * instart and limit URL parameters.
     * @var \BTCBridge\Api\TransactionInput[]
     */
    protected $inputs = null;

    /**
     * Array of outputs, limited to 20. Use paging to get more outputs (see section on blocks) with
     * outstart and limit URL parameters.
     * @var \BTCBridge\Api\TransactionOutput[]
     */
    protected $outputs = null;


    /**
     * BlockHash of the block the transaction is in. Only exists for confirmed transactions.
     *
     * @param $blockHash
     * @return $this
     */
    public function setBlockHash($blockHash)
    {
        $this->blockHash = $blockHash;
        return $this;
    }

    /**
     * BlockHash of the block the transaction is in. Only exists for confirmed transactions.
     *
     * @return string
     */
    public function getBlockHash()
    {
        return $this->blockHash;
    }


    /**
     * Array of Bitcoin addresses involved in the transaction.
     *
     * @return \string[]
     */
    public function getAddresses()
    {
        return isset($this->addresses) ? $this->addresses : [];
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
        sort($this->addresses);
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
     * @return int
     */
    public function getLockTime()
    {
        return $this->lockTime;
    }

    /**
     * @param int $lockTime
     * @return $this
     */
    public function setLockTime($lockTime)
    {
        $this->lockTime = $lockTime;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isDoubleSpend()
    {
        return $this->doubleSpend;
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
     * Total number of inputs
     *
     * @return int
     */
    public function getVinSz()
    {
        return $this->vinSz;
    }

    /**
     * Total number of inputs
     *
     * @param int $vinSz
     * @return $this
     */
    public function setVinSz($vinSz)
    {
        $this->vinSz = $vinSz;
        return $this;
    }

    /**
     * Total number of outputs
     *
     * @return int
     */
    public function getVoutSz()
    {
        return $this->voutSz;
    }

    /**
     * Total number of outputs
     *
     * @param int $voutSz
     * @return $this
     */
    public function setVoutSz($voutSz)
    {
        $this->voutSz = $voutSz;
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
            $this->setInputs(array($input));
        } else {
            $this->setInputs(array_merge($this->getInputs(), array($input)));
        }
        return $this->getInputs();
    }

    /**
     * Array of inputs, limited to 20. Use paging to get more inputs (see section on blocks) with
     * instart and limit URL parameters.
     *
     * @return \BTCBridge\Api\TransactionInput[]
     */
    public function getInputs()
    {
        return isset($this->inputs) ? $this->inputs : [];
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
            $this->setOutputs(array($output));
        } else {
            $this->setOutputs(array_merge($this->getOutputs(), array($output)));
        }
        return $this->getOutputs();
    }

    /**
     * Array of outputs, limited to 20. Use paging to get more outputs (see section on blocks) with
     * outstart and limit URL parameters.
     *
     * @return \BTCBridge\Api\TransactionOutput[]
     */
    public function getOutputs()
    {
        return isset($this->outputs) ? $this->outputs : []; //property_exists may be help too
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
     * @param $blockHeight
     * @return $this
     */
    public function setBlockHeight($blockHeight)
    {
        $this->blockHeight = $blockHeight;
        return $this;
    }

    /**
     * Height of the block the transaction is in. Only exists for confirmed transactions.
     *
     * @return string
     */
    public function getBlockHeight()
    {
        return $this->blockHeight;
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
