<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Usage records allow you to report customer usage and metrics to Stripe for
 * metered billing of subscription prices.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/billing/subscriptions/metered-billing">Metered
 * Billing</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property int $quantity The usage quantity for the specified date.
 * @property string $subscription_item The ID of the subscription item this usage record contains data for.
 * @property int $timestamp The timestamp when this usage occurred.
 */
class UsageRecord extends ApiResource
{
    const OBJECT_NAME = 'usage_record';
}
