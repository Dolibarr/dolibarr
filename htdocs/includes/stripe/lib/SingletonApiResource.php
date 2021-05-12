<?php

namespace Stripe;

/**
 * Class SingletonApiResource
 *
 * @package Stripe
 */
abstract class SingletonApiResource extends ApiResource
{
    protected static function _singletonRetrieve($options = null)
    {
        $opts = Util\RequestOptions::parse($options);
        $instance = new static(null, $opts);
        $instance->refresh();
        return $instance;
    }

    /**
     * @return string The endpoint associated with this singleton class.
     */
    public static function classUrl()
    {
<<<<<<< HEAD
        $base = static::className();
=======
        // Replace dots with slashes for namespaced resources, e.g. if the object's name is
        // "foo.bar", then its URL will be "/v1/foo/bar".
        $base = str_replace('.', '/', static::OBJECT_NAME);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        return "/v1/${base}";
    }

    /**
     * @return string The endpoint associated with this singleton API resource.
     */
    public function instanceUrl()
    {
        return static::classUrl();
    }
}
