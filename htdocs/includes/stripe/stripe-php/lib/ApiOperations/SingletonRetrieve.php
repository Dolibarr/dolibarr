<?php

namespace Stripe\ApiOperations;

/**
 * Trait for retrievable singleton resources. Adds a `retrieve()` static method to the
 * class.
 *
 * This trait should only be applied to classes that derive from SingletonApiResource.
 */
trait SingletonRetrieve
{
    /**
     * @param array|string $id the ID of the API resource to retrieve,
     *     or an options array containing an `id` key
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return static
     */
    public static function retrieve($opts = null)
    {
        $opts = \Stripe\Util\RequestOptions::parse($opts);
        $instance = new static(null, $opts);
        $instance->refresh();

        return $instance;
    }
}
