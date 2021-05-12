<?php

namespace Stripe;

/**
 * Class Balance
 *
 * @property string $object
 * @property array $available
<<<<<<< HEAD
=======
 * @property array $connect_reserved
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property bool $livemode
 * @property array $pending
 *
 * @package Stripe
 */
class Balance extends SingletonApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "balance";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
