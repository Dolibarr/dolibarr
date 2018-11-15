<?php

namespace Stripe;

/**
 * Class Subscription
 *
 * @property string $id
 * @property string $object
 * @property float $application_fee_percent
 * @property string $billing
 * @property int $billing_cycle_anchor
 * @property bool $cancel_at_period_end
 * @property int $canceled_at
 * @property int $created
 * @property int $current_period_end
 * @property int $current_period_start
 * @property string $customer
 * @property int $days_until_due
 * @property Discount $discount
 * @property int $ended_at
 * @property Collection $items
 * @property boolean $livemode
 * @property StripeObject $metadata
 * @property Plan $plan
 * @property int $quantity
 * @property int $start
 * @property string $status
 * @property float $tax_percent
 * @property int $trial_end
 * @property int $trial_start
 *
 * @package Stripe
 */
class Subscription extends ApiResource
{

    const OBJECT_NAME = "subscription";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete {
        delete as protected _delete;
    }
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * These constants are possible representations of the status field.
     *
     * @link https://stripe.com/docs/api#subscription_object-status
     */
    const STATUS_ACTIVE   = 'active';
    const STATUS_CANCELED = 'canceled';
    const STATUS_PAST_DUE = 'past_due';
    const STATUS_TRIALING = 'trialing';
    const STATUS_UNPAID   = 'unpaid';

    public static function getSavedNestedResources()
    {
        static $savedNestedResources = null;
        if ($savedNestedResources === null) {
            $savedNestedResources = new Util\Set([
                'source',
            ]);
        }
        return $savedNestedResources;
    }

    /**
     * @param array|null $params
     *
     * @return Subscription The deleted subscription.
     */
    public function cancel($params = null, $opts = null)
    {
        return $this->_delete($params, $opts);
    }

    /**
     * @return Subscription The updated subscription.
     */
    public function deleteDiscount()
    {
        $url = $this->instanceUrl() . '/discount';
        list($response, $opts) = $this->_request('delete', $url);
        $this->refreshFrom(['discount' => null], $opts, true);
    }
}
