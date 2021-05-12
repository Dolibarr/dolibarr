<?php

namespace Stripe;

class ThreeDSecure extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "three_d_secure";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\Create;
    use ApiOperations\Retrieve;

    /**
     * @return string The endpoint URL for the given class.
     */
    public static function classUrl()
    {
        return "/v1/3d_secure";
    }
}
