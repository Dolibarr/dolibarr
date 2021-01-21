<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A return represents the full or partial return of a number of <a
 * href="https://stripe.com/docs/api#order_items">order items</a>. Returns always
 * belong to an order, and may optionally contain a refund.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/orders/guide#handling-returns">Handling
 * Returns</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount A positive integer in the smallest currency unit (that is, 100 cents for $1.00, or 1 for ¥1, Japanese Yen being a zero-decimal currency) representing the total amount for the returned line item.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property \Stripe\OrderItem[] $items The items included in this order return.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string|\Stripe\Order $order The order that this return includes items from.
 * @property null|string|\Stripe\Refund $refund The ID of the refund issued for this return.
 */
class OrderReturn extends ApiResource
{
    const OBJECT_NAME = 'order_return';

    use ApiOperations\All;
    use ApiOperations\Retrieve;
}
