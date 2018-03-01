<?php

namespace BTCBridge\Api;

/**
 * Class WalletActionOptions
 * This class contains options for Handlers methods, which works with wallets
 *
 * @package BTCBridge\Api
 */
class WalletActionOptions
{
    /** @var $omitaddresses boolean */
    protected $omitaddresses = true;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * @return boolean
     */
    public function getOmitAddresses()
    {
        return $this->omitaddresses;
    }

    /**
     * @param boolean $omitaddresses
     *
     * @return $this
     */
    public function setOmitAddresses($omitaddresses)
    {
        $this->omitaddresses = $omitaddresses;
        return $this;
    }
}
