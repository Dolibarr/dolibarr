<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Shipping rates describe the price of shipping presented to your customers and
 * can be applied to <a
 * href="https://stripe.com/docs/payments/checkout/shipping">Checkout Sessions</a>
 * and <a href="https://stripe.com/docs/orders/shipping">Orders</a> to collect
 * shipping costs.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $active Whether the shipping rate can be used for new purchases. Defaults to <code>true</code>.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|\Stripe\StripeObject $delivery_estimate The estimated range for how long shipping will take, meant to be displayable to the customer. This will appear on CheckoutSessions.
 * @property null|string $display_name The name of the shipping rate, meant to be displayable to the customer. This will appear on CheckoutSessions.
 * @property null|\Stripe\StripeObject $fixed_amount
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $tax_behavior Specifies whether the rate is considered inclusive of taxes or exclusive of taxes. One of <code>inclusive</code>, <code>exclusive</code>, or <code>unspecified</code>.
 * @property null|string|\Stripe\TaxCode $tax_code A <a href="https://stripe.com/docs/tax/tax-categories">tax code</a> ID. The Shipping tax code is <code>txcd_92010001</code>.
 * @property string $type The type of calculation to use on the shipping rate. Can only be <code>fixed_amount</code> for now.
 */
class ShippingRate extends ApiResource
{
    const OBJECT_NAME = 'shipping_rate';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const TAX_BEHAVIOR_EXCLUSIVE = 'exclusive';
    const TAX_BEHAVIOR_INCLUSIVE = 'inclusive';
    const TAX_BEHAVIOR_UNSPECIFIED = 'unspecified';

    const TYPE_FIXED_AMOUNT = 'fixed_amount';
}
