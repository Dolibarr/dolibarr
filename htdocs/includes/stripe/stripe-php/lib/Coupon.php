<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A coupon contains information about a percent-off or amount-off discount you
 * might want to apply to a customer. Coupons may be applied to <a
 * href="https://stripe.com/docs/api#subscriptions">subscriptions</a>, <a
 * href="https://stripe.com/docs/api#invoices">invoices</a>, <a
 * href="https://stripe.com/docs/api/checkout/sessions">checkout sessions</a>, <a
 * href="https://stripe.com/docs/api#quotes">quotes</a>, and more. Coupons do not
 * work with conventional one-off <a
 * href="https://stripe.com/docs/api#create_charge">charges</a> or <a
 * href="https://stripe.com/docs/api/payment_intents">payment intents</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|int $amount_off Amount (in the <code>currency</code> specified) that will be taken off the subtotal of any invoices for this customer.
 * @property null|\Stripe\StripeObject $applies_to
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string $currency If <code>amount_off</code> has been set, the three-letter <a href="https://stripe.com/docs/currencies">ISO code for the currency</a> of the amount to take off.
 * @property null|\Stripe\StripeObject $currency_options Coupons defined in each available currency option. Each key must be a three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a> and a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property string $duration One of <code>forever</code>, <code>once</code>, and <code>repeating</code>. Describes how long a customer who applies this coupon will get the discount.
 * @property null|int $duration_in_months If <code>duration</code> is <code>repeating</code>, the number of months the coupon applies. Null if coupon <code>duration</code> is <code>forever</code> or <code>once</code>.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|int $max_redemptions Maximum number of times this coupon can be redeemed, in total, across all customers, before it is no longer valid.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $name Name of the coupon displayed to customers on for instance invoices or receipts.
 * @property null|float $percent_off Percent that will be taken off the subtotal of any invoices for this customer for the duration of the coupon. For example, a coupon with percent_off of 50 will make a %s100 invoice %s50 instead.
 * @property null|int $redeem_by Date after which the coupon can no longer be redeemed.
 * @property int $times_redeemed Number of times this coupon has been applied to a customer.
 * @property bool $valid Taking account of the above properties, whether this coupon can still be applied to a customer.
 */
class Coupon extends ApiResource
{
    const OBJECT_NAME = 'coupon';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
