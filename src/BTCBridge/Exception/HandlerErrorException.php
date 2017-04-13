<?php
namespace BTCBridge\Exception;

use BTCBridge\Handler\AbstractHandler;

class HandlerErrorException extends BridgeException
{
    /** @var AbstractHandler[] */
    protected $successHandlers;

    /** @var AbstractHandler */
    protected $errorHandler;

    /** @var AbstractHandler[] */
    protected $unusedHandlers;

    /** @var mixed */
    protected $result;


    /**
     * Constructor
     *
     * @param AbstractHandler[] $successHandlers
     * @param AbstractHandler $errorHandler
     * @param AbstractHandler[] $unusedHandlers
     * @param mixed $result
     * @param string $message
     * @param int $code
     * @param BridgeException $previous
     */
    public function __construct(array $successHandlers, AbstractHandler $errorHandler, array $unusedHandlers, $result, $message, $code = 0, BridgeException $previous = null)
    {
        $this->successHandlers = $successHandlers;
        $this->errorHandler = $errorHandler;
        $this->unusedHandlers = $unusedHandlers;
        $this->result = $result;
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
     * @return AbstractHandler
     */
    public function getErrorHandler()
    {
        return $this->errorHandler;
    }

    /**
     * Gets the unused Handlers
     *
     * @return AbstractHandler[]
     */
    public function getUnusedHandlers()
    {
        return $this->unusedHandlers;
    }

    /**
     * Gets the result
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}
