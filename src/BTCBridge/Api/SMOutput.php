<?php

namespace BTCBridge\Api;

/**
 * Class SMOutput
 * Specieal Output exclusively for SendManyMethod
 *
 * @property string address
 * @property int amount
 *
 * @package BTCBridge\Api
 */
class SMOutput
{
    /** @var $address string */
    protected $address;

    /** @var $amount integer */
    protected $amount;

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param integer $amount
     * @return $this
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

}