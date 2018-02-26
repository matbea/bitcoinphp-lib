<?php

namespace BTCBridge\Api;

use BTCBridge\Exception\BEInvalidArgumentException;

/**
 * Class SendMoneyOptions
 * Special Output exclusively for SendManyMethod
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
     * @throws BEInvalidArgumentException in case of error of this type
     */
    public function setConfirmations($confirmations)
    {
        if ((!is_int($confirmations)) || ($confirmations < 0)) {
            if (!is_null($confirmations)) {
                throw new BEInvalidArgumentException(
                    "confirmation variable must be non negative integer or null."
                );
            }
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
     * @throws BEInvalidArgumentException in case of error of this type
     */
    public function setComment($comment)
    {
        if (!is_string($comment)) {
            throw new BEInvalidArgumentException("comment variable must be a string variable.");
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
     * @throws BEInvalidArgumentException in case of error of this type
     */
    public function setCommentTo($commentTo)
    {
        if (!is_string($commentTo)) {
            throw new BEInvalidArgumentException("commentTo variable must be a string variable.");
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
     * @throws BEInvalidArgumentException in case of error of this type
     */
    public function setAddressForChange($addressForChange)
    {
        if (!is_string($addressForChange)) {
            if (!is_null($addressForChange)) {
                throw new BEInvalidArgumentException(
                    "commentTo variable must be a string variable or null."
                );
            }
        }
        $this->addressForChange = $addressForChange;
        return $this;
    }
}
