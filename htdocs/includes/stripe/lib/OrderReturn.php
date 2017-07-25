<?php

namespace Stripe;

/**
 * Class OrderReturn
 *
 * @package Stripe
 */
class OrderReturn extends ApiResource
{
    /**
     * @param string $id The ID of the OrderReturn to retrieve.
     * @param array|string|null $opts
     *
     * @return Order
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of OrderReturns
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }
}
