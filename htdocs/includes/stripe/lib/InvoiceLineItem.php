<?php

namespace Stripe;

/**
 * Class InvoiceLineItem
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property string $currency
 * @property string $description
 * @property bool $discountable
 * @property string $invoice_item
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $period
 * @property Plan $plan
 * @property bool $proration
 * @property int $quantity
 * @property string $subscription
 * @property string $subscription_item
 * @property string $type
 *
 * @package Stripe
 */
class InvoiceLineItem extends ApiResource
{
    const OBJECT_NAME = "line_item";
}
