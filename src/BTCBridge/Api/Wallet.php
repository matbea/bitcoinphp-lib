<?php

namespace BTCBridge\Api;

/**
 * Class Wallet
 *
 * @package BTCBridge\Api
 *
 * @property string token
 * @property string name
 * @property string[] addresses
 */
class Wallet
{

    /**
     * Token of the wallet
     * @var string
     */
    protected $token;

    /**
     * Name of the wallet
     * @var string
     */
    protected $name;

    /**
     * Addresses of the wallet
     * @var string[]
     */
    protected $addresses;


    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
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
            $this->setAddresses(array($address));
        } else {
            $this->setAddresses(array_merge($this->getAddresses(), array($address)));
        }
        return $this->getAddresses();
    }

    /**
     * @return \string[]
     */
    public function getAddresses()
    {
        return isset($this->addresses) ? $this->addresses : [];
    }

    /**
     * @param \string[] $addresses
     * @return $this
     */
    public function setAddresses($addresses)
    {
        $this->addresses = $addresses;
        sort($this->addresses);
        return $this;
    }
}
