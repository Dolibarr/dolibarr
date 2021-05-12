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
<<<<<<< HEAD
=======

    const OBJECT_NAME = "application_fee";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\NestedResource;
    use ApiOperations\Retrieve;

    const PATH_REFUNDS = '/refunds';

    /**
<<<<<<< HEAD
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
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
     * @param array|null $id The ID of the application fee on which to create the refund.
=======
     * @param string|null $id The ID of the application fee on which to create the refund.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
     * @param array|null $id The ID of the application fee to which the refund belongs.
=======
     * @param string|null $id The ID of the application fee to which the refund belongs.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
     * @param array|null $id The ID of the application fee to which the refund belongs.
=======
     * @param string|null $id The ID of the application fee to which the refund belongs.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
     * @param array|null $id The ID of the application fee on which to retrieve the refunds.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return ApplicationFeeRefund
=======
     * @param string|null $id The ID of the application fee on which to retrieve the refunds.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Collection The list of refunds.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     */
    public static function allRefunds($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_REFUNDS, $params, $opts);
    }
}
