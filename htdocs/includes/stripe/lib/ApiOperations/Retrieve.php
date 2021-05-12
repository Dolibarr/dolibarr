<?php

namespace Stripe\ApiOperations;

/**
 * Trait for retrievable resources. Adds a `retrieve()` static method to the
 * class.
 *
 * This trait should only be applied to classes that derive from StripeObject.
 */
trait Retrieve
{
    /**
     * @param array|string $id The ID of the API resource to retrieve,
     *     or an options array containing an `id` key.
     * @param array|string|null $opts
     *
<<<<<<< HEAD
     * @return \Stripe\StripeObject
=======
     * @return static
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     */
    public static function retrieve($id, $opts = null)
    {
        $opts = \Stripe\Util\RequestOptions::parse($opts);
        $instance = new static($id, $opts);
        $instance->refresh();
        return $instance;
    }
}
