<?php

namespace Stripe;

/**
 * Class OrderReturn
 *
 * @package Stripe
 */
class OrderReturn extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * This is a special case because the order returns endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'order_return';
    }
}
