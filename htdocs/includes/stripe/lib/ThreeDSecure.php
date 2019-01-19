<?php

namespace Stripe;

class ThreeDSecure extends ApiResource
{
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
