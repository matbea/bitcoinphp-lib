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
     * @param \GMP $value
     *
     * @throws \InvalidArgumentException in case of any error of this logical type
     */
    public function __construct($value)
    {
        if ( !$value instanceof \GMP) {
            throw new \InvalidArgumentException("The given value is not a GMP value");
        }
        $this->value = $value;
    }

    /**
     * Set GMP value
     *
     * @param \GMP $value
     * @return $this
     *
     * @throws \InvalidArgumentException in case of any error of this logical type
     */
    public function setGMPValue($value)
    {
        if ( !$value instanceof \GMP) {
            throw new \InvalidArgumentException("The given value is not a GMP value");
        }
        $this->value = $value;
        return $this;
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
     * @return double
     */
    public function getBTCValue()
    {
        return doubleval(bcdiv(gmp_strval($this->value), "100000000", 8));
    }

    /**
     * Get value in Satoshi
     *
     * @return string
     *
     * @throws \RuntimeException if case of any error of this type
     */
    public function getSatoshiValue()
    {
        $intValue = gmp_intval(gmp_strval($this->value));
        if ( strval($intValue) !== gmp_strval($this->value) )
        {
            throw new \RuntimeException("Integer value is not equal string value (" . gmp_strval($this->value) . ").");
        }
        return $intValue;
    }
}
