<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Sometimes you want to add a charge or credit to a customer, but actually charge
 * or credit the customer's card only at the end of a regular billing cycle. This
 * is useful for combining several charges (to minimize per-transaction fees), or
 * for having Stripe tabulate your usage-based billing totals.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/billing/invoices/subscription#adding-upcoming-invoice-items">Subscription
 * Invoices</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount (in the <code>currency</code> specified) of the invoice item. This should always be equal to <code>unit_amount * quantity</code>.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property string|\Stripe\Customer $customer The ID of the customer who will be billed when this invoice item is billed.
 * @property int $date Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property bool $discountable If true, discounts will apply to this invoice item. Always false for prorations.
 * @property null|(string|\Stripe\Discount)[] $discounts The discounts which apply to the invoice item. Item discounts are applied before invoice discounts. Use <code>expand[]=discounts</code> to expand each discount.
 * @property null|string|\Stripe\Invoice $invoice The ID of the invoice this invoice item belongs to.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property \Stripe\StripeObject $period
 * @property null|\Stripe\Plan $plan If the invoice item is a proration, the plan of the subscription that the proration was computed for.
 * @property null|\Stripe\Price $price The price of the invoice item.
 * @property bool $proration Whether the invoice item was created automatically as a proration adjustment when the customer switched plans.
 * @property int $quantity Quantity of units for the invoice item. If the invoice item is a proration, the quantity of the subscription that the proration was computed for.
 * @property null|string|\Stripe\Subscription $subscription The subscription that this invoice item has been created for, if any.
 * @property string $subscription_item The subscription item that this invoice item has been created for, if any.
 * @property null|\Stripe\TaxRate[] $tax_rates The tax rates which apply to the invoice item. When set, the <code>default_tax_rates</code> on the invoice do not apply to this invoice item.
 * @property null|int $unit_amount Unit amount (in the <code>currency</code> specified) of the invoice item.
 * @property null|string $unit_amount_decimal Same as <code>unit_amount</code>, but contains a decimal value with at most 12 decimal places.
 */
class InvoiceItem extends ApiResource
{
    const OBJECT_NAME = 'invoiceitem';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
