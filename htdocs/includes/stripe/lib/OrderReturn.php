<?php

namespace Stripe;

/**
 * Class OrderReturn
 *
<<<<<<< HEAD
=======
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $created
 * @property string $currency
 * @property OrderItem[] $items
 * @property bool $livemode
 * @property string $order
 * @property string $refund
 *
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @package Stripe
 */
class OrderReturn extends ApiResource
{
<<<<<<< HEAD
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
=======

    const OBJECT_NAME = "order_return";

    use ApiOperations\All;
    use ApiOperations\Retrieve;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
