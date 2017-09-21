<?php

namespace Stripe;

class ThreeDSecure extends ApiResource
{
    /**
     * @return string The endpoint URL for the given class.
     */
    public static function classUrl()
    {
        return "/v1/3d_secure";
    }

    /**
     * @param string $id The ID of the 3DS auth to retrieve.
     * @param array|string|null $options
     *
     * @return ThreeDSecure
     */
    public static function retrieve($id, $options = null)
    {
        return self::_retrieve($id, $options);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return ThreeDSecure The created 3D Secure object.
     */
    public static function create($params = null, $opts = null)
    {
        return self::_create($params, $opts);
    }
}
