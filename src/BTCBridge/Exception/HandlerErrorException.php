<?php
namespace BTCBridge\Exception;

use BTCBridge\Handler\AbstractHandler;

class HandlerErrorException extends BridgeException
{
    /** @var AbstractHandler[] */
    protected $successHandlers;

    /** @var AbstractHandler[] */
    protected $errorHandlers;

    /**
     * Constructor
     *
     * @param AbstractHandler[] $successHandlers
     * @param AbstractHandler[] $errorHandlers
     * @param string $message
     * @param int $code
     * @param BridgeException $previous
     * @param BridgeException $previous
     */
    public function __construct($successHandlers, $errorHandlers, $message, $code = 0, BridgeException $previous = null)
    {
        $this->successHandlers = $successHandlers;
        $this->errorHandlers = $errorHandlers;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the success Handlers
     *
     * @return AbstractHandler[]
     */
    public function getSuccessHandlers()
    {
        return $this->successHandlers;
    }

    /**
     * Gets the error Handlers
     *
     * @return AbstractHandler[]
     */
    public function getErrorHandlers()
    {
        return $this->errorHandlers;
    }
}
