<?php

namespace Stripe;

/**
 * Class Balance
 *
 * @property string $object
 * @property array $available
 * @property bool $livemode
 * @property array $pending
 *
 * @package Stripe
 */
class Balance extends SingletonApiResource
{
    /**
     * @param array|string|null $opts
     *
     * @return Balance
     */
    public static function retrieve($opts = null)
    {
        return self::_singletonRetrieve($opts);
    }
}
