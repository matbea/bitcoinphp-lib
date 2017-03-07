<?php

/*
 * This file is part of the BTCBridge package.
 *
 * (c) Matbea <mail@matbea.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BTCBridge\Handler;

use Psr\Log\LoggerInterface;

/**
 * Base Handler class providing the Handler structure
 *
 * @author Matbea <mail@matbea.com>
 */
abstract class AbstractHandler implements HandlerInterface {

	protected $ordered;
	protected $logger;

    /**
     * @param Boolean $ordered Whether the order of processor will saved or not
     */
    public function __construct($ordered = false) {
        $this->ordered = $ordered;
		$this->logger = new \Monolog\Logger('BTCBridge');
    }

	/**
	 * @param Boolean $ordered Whether the order of processor will saved or not
	 */
	public function setLogger(LoggerInterface $loggerHandle) {
		$this->logger = $loggerHandle;
	}

}
