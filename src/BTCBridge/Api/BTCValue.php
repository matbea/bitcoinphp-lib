<?php

namespace BTCBridge\Api;

/**
 * Class BTCValue
 *
 * @package BTCBridge\Api
 *
 */
class BTCValue
{
    /**
     * Amount of money
     * @var \GMP
     */
    protected $value;

    /**
     * Create a new BTCValue object with the value passed from parameter
     *
     * @param $value
     * @return \BTCBridge\Api\BTCValue
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * Set GMP value
     *
     * @param \GMP $value
     * @return $this
     */
    public function setGMPValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get GMP value
     *
     * @return \GMP
     */
    public function getGMPValue()
    {
        return $this->value;
    }

    /**
     * Get value in BTC
     *
     * @return string
     */
    public function getBTCValue()
    {
        return bcdiv(gmp_strval($this->value), "100000000", 8);
    }

    /**
     * Get value in Satoshi
     *
     * @return string
     */
    public function getSatoshiValue()
    {
        return gmp_strval($this->value);
    }
}
