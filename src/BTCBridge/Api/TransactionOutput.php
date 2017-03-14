<?php

namespace BTCBridge\Api;

/**
 * Class TransactionOutput
 *
 * A TransactionOutput represents an output created by a transaction. Typically found within an array in a Transaction.
 *
 * script_type values:
 * pay-to-pubkey-hash (most common transaction transferring to a public key hash,
 * and the default behavior if no out)
 * pay-to-multi-pubkey-hash (multi-signatures transaction, now actually less used than
 * pay-to-script-hash for this purpose)
 * pay-to-pubkey (used for mining transactions)
 * pay-to-script-hash (used for transactions relying on arbitrary scripts,
 * now used primarily for multi-sig transactions)
 * null-data (sometimes called op-return; used to embed small chunks of data in the blockchain)
 * empty (no script present, mostly used for mining transaction inputs)
 * unknown (non-standard script)
 *
 * @package BTCBridge\Api
 *
 * @property int value
 * @property string address
 * @property string[] addresses
 * @property string script_type
 * @property string spent_by
 */
class TransactionOutput
{
    /**
     * Value transferred by the transaction output, in satoshi.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Value transferred by the transaction output, in satoshi.
     *
     * @param int $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }


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
     * Append Address to the list.
     *
     * @param string $address
     * @return $this
     */
    public function addAddress($address)
    {
        if (!$this->getAddresses()) {
            return $this->setAddresses(array($address));
        } else {
            return $this->setAddresses(
                array_merge($this->getAddresses(), array($address))
            );
        }
    }

    /**
     * Addresses that correspond to this output; typically this will only have a single address,
     * and you can think of this output as having �sent� value to the address contained herein.
     *
     * @return \string[]
     */
    public function getAddresses()
    {
        return $this->addresses;
    }

    /**
     * Addresses that correspond to this output; typically this will only have a single address,
     * and you can think of this output as having �sent� value to the address contained herein.
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
     * The type of encumbrance script used for this output.
     *
     * @return string
     */
    public function getScriptType()
    {
        return $this->script_type;
    }

    /**
     * The type of encumbrance script used for this output.
     *
     * @param string $script_type
     * @return $this
     */
    public function setScriptType($script_type)
    {
        $this->script_type = $script_type;
        return $this;
    }
}
