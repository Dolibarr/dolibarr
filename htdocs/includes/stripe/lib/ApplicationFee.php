<?php

namespace Stripe;

/**
 * Class ApplicationFee
 *
 * @property string $id
 * @property string $object
 * @property string $account
 * @property int $amount
 * @property int $amount_refunded
 * @property string $application
 * @property string $balance_transaction
 * @property string $charge
 * @property int $created
 * @property string $currency
 * @property bool $livemode
 * @property string $originating_transaction
 * @property bool $refunded
 * @property Collection $refunds
 *
 * @package Stripe
 */
class ApplicationFee extends ApiResource
{

    const OBJECT_NAME = "application_fee";

    use ApiOperations\All;
    use ApiOperations\NestedResource;
    use ApiOperations\Retrieve;

    const PATH_REFUNDS = '/refunds';

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

    /**
     * @param string|null $id The ID of the application fee on which to create the refund.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return ApplicationFeeRefund
     */
    public static function createRefund($id, $params = null, $opts = null)
    {
        return self::_createNestedResource($id, static::PATH_REFUNDS, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the application fee to which the refund belongs.
     * @param array|null $refundId The ID of the refund to retrieve.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return ApplicationFeeRefund
     */
    public static function retrieveRefund($id, $refundId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_REFUNDS, $refundId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the application fee to which the refund belongs.
     * @param array|null $refundId The ID of the refund to update.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return ApplicationFeeRefund
     */
    public static function updateRefund($id, $refundId, $params = null, $opts = null)
    {
        return self::_updateNestedResource($id, static::PATH_REFUNDS, $refundId, $params, $opts);
    }

    /**
     * @param string|null $id The ID of the application fee on which to retrieve the refunds.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return ApplicationFeeRefund
     */
    public static function allRefunds($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_REFUNDS, $params, $opts);
    }
}
