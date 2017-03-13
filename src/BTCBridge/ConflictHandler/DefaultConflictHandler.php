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
        $uniq_results = array_map(
            'unserialize',
            array_unique(array_map('serialize', $data))
        ); //$uniq_results = array_unique($results);
        if (1 != count($uniq_results)) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function gettransaction($data)
    {
        $uniq_results = array_map('unserialize', array_unique(array_map('serialize', $data)));
        if (1 != count($uniq_results)) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getbalance($data)
    {
        $uniq_results = array_map('unserialize', array_unique(array_map('serialize', $data)));
        if (1 != count($uniq_results)) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function getunconfirmedbalance($data)
    {
        $uniq_results = array_map('unserialize', array_unique(array_map('serialize', $data)));
        if (1 != count($uniq_results)) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function listunspent($data)
    {
        $uniq_results = array_map('unserialize', array_unique(array_map('serialize', $data)));
        if (1 != count($uniq_results)) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $data[0];
    }

    /**
     * {@inheritdoc}
     */
    public function sendrawtransaction($data)
    {
        $uniq_results = array_map('unserialize', array_unique(array_map('serialize', $data)));
        if (1 != count($uniq_results)) {
            throw new ConflictHandlerException("No equal results from different services.");
        }
        return $data[0];
    }

}