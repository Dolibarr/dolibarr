<?php

namespace Stripe;

/**
 * Class ApplicationFee
 *
 * @package Stripe
 */
class ApplicationFee extends ApiResource
{
    /**
     * This is a special case because the application fee endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'application_fee';
    }

    /**
     * @param string $id The ID of the application fee to retrieve.
     * @param array|string|null $opts
     *
     * @return ApplicationFee
     */
    public static function retrieve($id, $opts = null)
    {
        return self::_retrieve($id, $opts);
    }

    /**
     * @param string $id The ID of the application fee to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return ApplicationFee The updated application fee.
     */
    public static function update($id, $params = null, $options = null)
    {
        return self::_update($id, $params, $options);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection of ApplicationFees
     */
    public static function all($params = null, $opts = null)
    {
        return self::_all($params, $opts);
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return ApplicationFee The refunded application fee.
     */
    public function refund($params = null, $opts = null)
    {
        $this->refunds->create($params, $opts);
        $this->refresh();
        return $this;
    }
}
