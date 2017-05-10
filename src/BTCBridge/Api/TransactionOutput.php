<?php

namespace BTCBridge\Api;

//use BTCBridge\Api\BTCValue;

/**
 * Class TransactionOutput
 *
 * A TransactionOutput represents an output created by a transaction. Typically found within an array in a Transaction.
 *
 * scriptType values:
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
 */
class TransactionOutput
{

    /**
     * Value transferred by the transaction output, in satoshi.
     * @var BTCValue
     */
    protected $value = null;

    /**
     * Addresses that correspond to this output
     * @var string[]
     */
    protected $addresses = null;

    /**
     * The type of encumbrance script used for this output.
     * @var string
     */
    protected $scriptType = null;

    /**
     * The transaction hash that spent this output. Only returned for outputs that have been spent.
     * @var string
     */
    //protected $spentBy = null;


    /**
     * Value transferred by the transaction output
     *
     * @return BTCValue
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Value transferred by the transaction output
     *
     * @param BTCValue $value
     * @return $this
     */
    public function setValue(BTCValue $value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Append Address to the list.
     *
     * @param string $address
     * @return \string[]
     */
    public function addAddress($address)
    {
        if (!$this->getAddresses()) {
            $this->setAddresses([$address]);
        } else {
            $this->setAddresses(array_merge($this->getAddresses(), [$address]));
        }
        return $this->getAddresses();
    }

    /**
     * Addresses that correspond to this output; typically this will only have a single address,
     * and you can think of this output as having �sent� value to the address contained herein.
     *
     * @return \string[]
     */
    public function getAddresses()
    {
        return isset($this->addresses) ? $this->addresses : [];
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
        sort($this->addresses);
        return $this;
    }

    /**
     * The type of encumbrance script used for this output.
     *
     * @return string
     */
    public function getScriptType()
    {
        return $this->scriptType;
    }

    /**
     * The type of encumbrance script used for this output.
     *
     * @param string $scriptType
     * @return $this
     */
    public function setScriptType($scriptType)
    {
        $this->scriptType = $scriptType;
        return $this;
    }
}
