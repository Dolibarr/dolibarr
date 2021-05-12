<?php

namespace Stripe;

/**
 * Class ApplePayDomain
 *
 * @package Stripe
 */
class ApplePayDomain extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "apple_pay_domain";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;

    /**
     * @return string The class URL for this resource. It needs to be special
     *    cased because it doesn't fit into the standard resource pattern.
     */
    public static function classUrl()
    {
        return '/v1/apple_pay/domains';
    }
}
