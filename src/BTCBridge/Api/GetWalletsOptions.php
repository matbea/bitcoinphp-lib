<?php

namespace BTCBridge\Api;

/**
 * Class GetWalletsOptions
 * This class contains options for method Handler::getWallets
 *
 * @package BTCBridge\Api
 */
class GetWalletsOptions
{
    /** @var $noaddresses boolean */
    protected $noaddresses;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->noaddresses = false;
    }

    /**
     * @return boolean
     */
    public function getNoaddresses()
    {
        return $this->noaddresses;
    }

    /**
     * @param boolean $noaddresses
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setNoaddresses($noaddresses)
    {
        $this->noaddresses = $noaddresses;
        return $this;
    }
}
