<?php

namespace Stripe\ApiOperations;

/**
 * Trait for creatable resources. Adds a `create()` static method to the class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait Create
{
    /**
     * @param array|null $params
     * @param array|string|null $options
     *
<<<<<<< HEAD
     * @return \Stripe\ApiResource The created resource.
=======
     * @return static The created resource.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     */
    public static function create($params = null, $options = null)
    {
        self::_validateParams($params);
        $url = static::classUrl();

        list($response, $opts) = static::_staticRequest('post', $url, $params, $options);
        $obj = \Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);
        return $obj;
    }
}
