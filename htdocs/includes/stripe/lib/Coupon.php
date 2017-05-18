<?php

namespace Stripe;

/**
 * Class Coupon
 *
 * @package Stripe
 */
class Coupon extends ApiResource
{
    /**
     * @param string $id The ID of the coupon to retrieve.
     * @param array|string|null $opts
     *
     * @return Coupon
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Coupon The created coupon.
     */
    public static function create($params = null, $opts = null)
    {
        return self::_create($params, $opts);
    }

    /**
     * @param string $id The ID of the coupon to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Coupon The updated coupon.
     */
    public static function update($id, $params = null, $options = null)
    {
        return self::_update($id, $params, $options);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Coupon The deleted coupon.
     */
    public function delete($params = null, $opts = null)
    {
        return $this->_delete($params, $opts);
    }

    /**
     * @param array|string|null $opts
     *
     * @return Coupon The saved coupon.
     */
    public function save($opts = null)
    {
        return $this->_save($opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of Coupons
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }
}
