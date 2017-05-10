<?php

namespace BTCBridge\Api;

/**
 * Class Wallet
 *
 * @package BTCBridge\Api
 *
 */
class Wallet
{

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

    /**
     * SystemData - something like name, id or guid - it depends of type of BTC-data provider
     * @var array
     */
    protected $systemData;

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
            $this->setAddresses([$address]);
        } else {
            $this->setAddresses(array_merge($this->getAddresses(), [$address]));
        }
        return $this;
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

    /**
     * @param \string $handlerId Name of BTC data provider
     *
     * @return \array
     */
    public function getSystemDataByHandler($handlerId)
    {
        if (!isset($this->systemData[$handlerId])) {
            return [];
        }
        return $this->systemData[$handlerId];
    }

    /**
     * @param \string $handlerId Name of BTC data provider
     * @param \array $data
     *
     * @return $this
     */
    public function setSystemDataByHandler($handlerId, $data)
    {
        $this->systemData[$handlerId] = $data;
        return $this;
    }

    /**
     * @return \array
     */
    public function getSystemData()
    {
        return $this->systemData;
    }

    /**
     * @param \array $data
     *
     * @return $this
     */
    public function setSystemData($data)
    {
        $this->systemData = $data;
        return $this;
    }
}
