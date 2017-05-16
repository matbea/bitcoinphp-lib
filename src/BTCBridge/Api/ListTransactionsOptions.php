<?php

namespace BTCBridge\Api;

/**
 * Class ListTransactionsOptions
 * This class contains options for method Handler::listtransactions
 *
 * @property int before
 * @property int after
 * @property int limit
 * @property int confirmations
 * @property boolean nobalance
 *
 * @package BTCBridge\Api
 */
class ListTransactionsOptions
{
    /** @var $before integer */
    protected $before;

    /** @var $after integer */
    protected $after;

    /** @var $limit integer */
    protected $limit;

    /** @var $confirmations integer */
    protected $confirmations;

    /** @var $nobalance boolean */
    protected $nobalance;


    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->confirmations = 1;
        $this->limit = 1000;
        $this->nobalance = false;
    }

    /**
     * @return integer
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @param integer $before
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setBefore($before)
    {
        if ("integer" != gettype($before) || ($before <= 0)) {
            throw new \InvalidArgumentException("before variable must be positive integer.");
        }
        $this->before = $before;
        return $this;
    }

    /**
     * @return integer
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @param integer $after
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setAfter($after)
    {
        if ("integer" != gettype($after) || ($after <= 0)) {
            throw new \InvalidArgumentException("after variable must be positive integer.");
        }
        $this->after = $after;
        return $this;
    }

    /**
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param integer $limit
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setLimit($limit)
    {
        if ("integer" != gettype($limit) || ($limit <= 0) || ($limit > 1000)) {
            throw new \InvalidArgumentException("limit variable must be positive integer (less than 1000).");
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return integer
     */
    public function getConfirmations()
    {
        return $this->confirmations;
    }

    /**
     * @param integer $confirmations
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setConfirmations($confirmations)
    {
        if ("integer" != gettype($confirmations) || ($confirmations <= 0)) {
            throw new \InvalidArgumentException("confirmation variable must be positive integer.");
        }
        $this->confirmations = $confirmations;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getNobalance()
    {
        return $this->nobalance;
    }

    /**
     * @param boolean $nobalance
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setNobalance($nobalance)
    {
        $this->nobalance = $nobalance;
        return $this;
    }
}
