<?php

namespace Stripe;

/**
 * Class ExchangeRate
 *
 * @package Stripe
 */
class ExchangeRate extends ApiResource
{
<<<<<<< HEAD
    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * This is a special case because the exchange rates endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'exchange_rate';
    }
=======

    const OBJECT_NAME = "exchange_rate";

    use ApiOperations\All;
    use ApiOperations\Retrieve;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
