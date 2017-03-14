<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\ConflictHandler;

use BTCBridge\Exception\ConflictHandlerException;

/**
 * Default Conflict Handler class providing the default behaviour
 *
 * @author Matbea <mail@matbea.com>
 */
class DefaultConflictHandler implements ConflictHandlerInterface
{

    /**
     * {@inheritdoc}
     */
    public function listtransactions($data)
    {
        //$uniq_results = array_map('unserialize',array_unique(array_map('serialize', $data)) ); //$uniq_results = array_unique($results);
        for ($i = 0; $i < count($data) - 1; ++$i) {
            $collection1 = & $data[$i];
            $collection2 = & $data[$i + 1];
            if (count($collection1) != count($collection2)) {
                throw new ConflictHandlerException("No equal results from different services (different size of result arrays).");
            }
            for ($j = 0; $j < count($collection1); ++$j) {
                if ($collection1[$j] != $collection2[$j]) {
                    throw new ConflictHandlerException("No equal results from different services (different items in the result arrays).");
                }
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function gettransaction($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            $collection1 = & $data[$i];
            $collection2 = & $data[$i + 1];
            if (count($collection1) != count($collection2)) {
                throw new ConflictHandlerException("No equal results from different services (different size of result arrays).");
            }
            for ($j = 0; $j < count($collection1); ++$j) {
                if ($collection1[$j] != $collection2[$j]) {
                    throw new ConflictHandlerException("No equal results from different services (different items in the result arrays).");
                }
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function createwallet($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function addaddresses($data)
    {
        for ($i = 0; $i < count($data) - 1; ++$i) {
            if ($data[$i] != $data[$i + 1]) {
                throw new ConflictHandlerException("No equal results from different services.");
            }
        }
        return $data[0];
    }


}