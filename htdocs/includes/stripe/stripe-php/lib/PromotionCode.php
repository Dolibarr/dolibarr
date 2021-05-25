<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A Promotion Code represents a customer-redeemable code for a coupon. It can be
 * used to create multiple codes for a single coupon.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $active Whether the promotion code is currently active. A promotion code is only active if the coupon is also valid.
 * @property string $code The customer-facing code. Regardless of case, this code must be unique across all active promotion codes for each customer.
 * @property \Stripe\Coupon $coupon A coupon contains information about a percent-off or amount-off discount you might want to apply to a customer. Coupons may be applied to <a href="https://stripe.com/docs/api#invoices">invoices</a> or <a href="https://stripe.com/docs/api#create_order-coupon">orders</a>. Coupons do not work with conventional one-off <a href="https://stripe.com/docs/api#create_charge">charges</a>.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string|\Stripe\Customer $customer The customer that this promotion code can be used by.
 * @property null|int $expires_at Date at which the promotion code can no longer be redeemed.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|int $max_redemptions Maximum number of times this promotion code can be redeemed.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property \Stripe\StripeObject $restrictions
 * @property int $times_redeemed Number of times this promotion code has been used.
 */
class PromotionCode extends ApiResource
{
    const OBJECT_NAME = 'promotion_code';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
