<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\ResultHandler;

use BTCBridge\Api\Address;
use BTCBridge\Api\Wallet;
use BTCBridge\Api\BTCValue;
use BTCBridge\Exception\ResultHandlerException;
use BTCBridge\Exception\BEInvalidArgumentException;

/**
 * Default Conflict Handler class providing the default behaviour
 *
 * @author Matbea <mail@matbea.com>
 */
class DefaultResultHandler extends AbstractResultHandler
{

    /**
     * {@inheritdoc}
     */
    public function listtransactions($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $address1 = & $data[0];
        $address2 = & $data[1];
        if ((!$address1 instanceof Address) || (!$address2 instanceof Address)) {
            throw new BEInvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        return $address1;
    }



    /**
     * {@inheritdoc}
     */
    public function gettransactions($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        $txs1 = & $data[0];
        $txs2 = & $data[1];
        if ((!is_array($txs1)) || (!is_array($txs2))) {
            throw new BEInvalidArgumentException(
                "Elements of Data array must be array of instances of Transaction class."
            );
        }
        return $txs1;
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $balance1 BTCValue */
        $balance1 = & $data[0];
        /** @var $balance2 BTCValue */
        $balance2 = & $data[1];
        if ((!$balance1 instanceof BTCValue) || (!$balance2 instanceof BTCValue)) {
            throw new BEInvalidArgumentException("Elements of data array must be BTCValue.");
        }
        return $balance1;
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("data array for verification must have size 1 or 2.");
        }
        /** @var $uncbal1 BTCValue */
        $uncbal1 = & $data[0];
        /** @var $uncbal2 BTCValue */
        $uncbal2 = & $data[1];
        if ((!$uncbal1 instanceof BTCValue) || (!$uncbal2 instanceof BTCValue)) {
            throw new BEInvalidArgumentException("Elements of data array must be BTCValue.");
        }
        return $uncbal1;
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function createWallet($data)
    {
        if (!is_array($data)) {
            throw new BEInvalidArgumentException("\$data variable must be the array of instances of Wallet class.");
        }

        if (1 == count($data)) {
            if (!$data[0] instanceof Wallet) {
                throw new BEInvalidArgumentException("Elements of Data array must be instances of Wallet class.");
            }
            $handlerIndex = 0;
            $systemData = $this->handlers[$handlerIndex]->getSystemDataForWallet($data[0]);
            if (!$systemData) {
                $handlerIndex = 1;
                $systemData = $this->handlers[$handlerIndex]->getSystemDataForWallet($data[0]);
            }
            if (!$systemData) {
                throw new ResultHandlerException(
                    "No handlers data (\"" . $this->handlers[1]->getHandlerName() .
                    "\") in the passed wallet ( " . serialize($data[0]) . ")"
                );
            }
            $data[0]->setSystemDataByHandler(
                $this->handlers[$handlerIndex]->getHandlerName(),
                $systemData
            );
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $wallet1 Wallet */
        $wallet1 = & $data[0];
        /** @var $wallet2 Wallet */
        $wallet2 = & $data[1];

        if ((!$wallet1 instanceof Wallet) || (!$wallet2 instanceof Wallet)) {
            throw new BEInvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }

        /** @var $resultWallet Wallet */
        $resultWallet = $data[0];
        for ($i = 0, $ic = count($data); $i < $ic; ++$i) {
            $systemData = $this->handlers[$i]->getSystemDataForWallet($data[$i]);
            if (!$systemData) {
                throw new ResultHandlerException(
                    "No handlers data (\"" . $this->handlers[$i]->getHandlerName() .
                    "\") in the passed wallet ( " . serialize($data[$i]) . ")"
                );
            }
            $resultWallet->setSystemDataByHandler(
                $this->handlers[$i]->getHandlerName(),
                $systemData
            );
        }
        return $resultWallet;
    }

    /**
     * {@inheritdoc}
     */
    public function removeAddresses($data)
    {
        if (!is_array($data)) {
            throw new BEInvalidArgumentException("\$data variable must be the array of instances of Wallet class.");
        }
        if (1 == count($data)) {
            if (!$data[0] instanceof Wallet) {
                throw new BEInvalidArgumentException("Elements of Data array must be instances of Wallet class.");
            }
            $handlerIndex = 0;
            $systemData = $this->handlers[$handlerIndex]->getSystemDataForWallet($data[0]);
            if (!$systemData) {
                $handlerIndex = 1;
                $systemData = $this->handlers[$handlerIndex]->getSystemDataForWallet($data[0]);
            }
            if (!$systemData) {
                throw new ResultHandlerException(
                    "No handlers data (\"" . $this->handlers[1]->getHandlerName() .
                    "\") in the passed wallet ( " . serialize($data[0]) . ")"
                );
            }
            /** @var $data Wallet[] */
            $data[0]->setSystemDataByHandler(
                $this->handlers[$handlerIndex]->getHandlerName(),
                $systemData
            );
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $wallet1 Wallet */
        $wallet1 = & $data[0];
        /** @var $wallet2 Wallet */
        $wallet2 = & $data[1];

        if ((!$wallet1 instanceof Wallet) || (!$wallet2 instanceof Wallet)) {
            throw new BEInvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        return $wallet1;
    }

    /**
     * {@inheritdoc}
     */
    public function addAddresses($data)
    {
        if (!is_array($data)) {
            throw new BEInvalidArgumentException("\$data variable must be the array of instances of Wallet class.");
        }
        if (1 == count($data)) {
            if (!$data[0] instanceof Wallet) {
                throw new BEInvalidArgumentException("Elements of Data array must be instances of Wallet class.");
            }
            $handlerIndex = 0;
            $systemData = $this->handlers[$handlerIndex]->getSystemDataForWallet($data[0]);
            if (!$systemData) {
                $handlerIndex = 1;
                $systemData = $this->handlers[$handlerIndex]->getSystemDataForWallet($data[0]);
            }
            if (!$systemData) {
                throw new ResultHandlerException(
                    "No handlers data (\"" . $this->handlers[1]->getHandlerName() .
                    "\") in the passed wallet ( " . serialize($data[0]) . ")"
                );
            }
            /** @var $data Wallet[] */
            $data[0]->setSystemDataByHandler(
                $this->handlers[$handlerIndex]->getHandlerName(),
                $systemData
            );
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $wallet1 Wallet */
        $wallet1 = & $data[0];
        /** @var $wallet2 Wallet */
        $wallet2 = & $data[1];

        if ((!$wallet1 instanceof Wallet) || (!$wallet2 instanceof Wallet)) {
            throw new BEInvalidArgumentException("Elements of Data array must be instances of Wallet class.");
        }
        return $wallet1;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddresses($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $result1 string[] */
        $result1 = & $data[0];
        /** @var $result2 string[] */
        $result2 = & $data[1];

        if ((!is_array($result1)) || (!is_array($result2))) {
            throw new BEInvalidArgumentException("Elements of Data array must be arrays of string.");
        }
        return $result1;
    }

    /**
     * {@inheritdoc}
     */
    public function getWallets($data)
    {
        if (1 == count($data)) {
            return $data[0];
        }
        if (2 != count($data)) {
            throw new BEInvalidArgumentException("Data array for verification must have size 1 or 2.");
        }
        /** @var $result1 string[] */
        $result1 = & $data[0];
        /** @var $result2 string[] */
        $result2 = & $data[1];

        if ((!is_array($result1)) || (!is_array($result2))) {
            throw new BEInvalidArgumentException("Elements of Data array must be arrays of string.");
        }
        return $result1;
    }
}
