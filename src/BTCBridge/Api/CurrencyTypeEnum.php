<?php

namespace BTCBridge\Api;

use Garoevans\PhpEnum\Enum;

/**
 * Class CurrencyTypeEnum
 *
 * A CurrencyTypeEnum represents a type of the cryptocurrency (btc, tbtc, litecoin etc).
 *
 * @package BTCBridge\Api
 *
 */
class CurrencyTypeEnum extends Enum
{
    // If no value is given during object construction this value is used
    const __default = 1;
    // Our enum values
    const BTC     = 'btc';
    const TBTC    = 'tbtc';
}
