<?php

namespace BTCBridge\Api;

/**
 * Class SendMoneyOptions
 * Special Output exclusively for SendManyMethod
 *
 * @property int confirmations
 * @property string comment
 * @property string commentTo
 * @property string addressForChange
 *
 * @package BTCBridge\Api
 */
class SendMoneyOptions
{
    /** @var $confirmations integer */
    protected $confirmations;

    /** @var $comment string */
    protected $comment;

    /** @var $commentTo string */
    protected $commentTo;

    /** @var $addressForChange string */
    protected $addressForChange;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->confirmations = 1;
        $this->comment = "";
        $this->commentTo = "";
        $this->addressForChange = "";
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
        if ("integer" != gettype($confirmations) || ($confirmations < 0)) {
            throw new \InvalidArgumentException("confirmation variable must be non negative integer.");
        }
        $this->confirmations = $confirmations;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setComment($comment)
    {
        if ("string" != gettype($comment)) {
            throw new \InvalidArgumentException("comment variable must be a string variable.");
        }
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommentTo()
    {
        return $this->commentTo;
    }

    /**
     * @param string $commentTo
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setCommentTo($commentTo)
    {
        if ("string" != gettype($commentTo)) {
            throw new \InvalidArgumentException("commentTo variable must be a string variable.");
        }
        $this->commentTo = $commentTo;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddressForChange()
    {
        return $this->addressForChange;
    }

    /**
     * @param string $addressForChange
     * @return $this
     * @throws \InvalidArgumentException in case of error of this type
     */
    public function setAddressForChange($addressForChange)
    {
        if ("string" != gettype($addressForChange)) {
            throw new \InvalidArgumentException("commentTo variable must be a string variable.");
        }
        $this->addressForChange = $addressForChange;
        return $this;
    }
}
