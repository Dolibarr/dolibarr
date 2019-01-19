<?php

namespace Stripe;

/**
 * Class ExchangeRate
 *
 * @package Stripe
 */
class ExchangeRate extends ApiResource
{
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
}
