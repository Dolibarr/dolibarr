<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A line item.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|int $amount_subtotal Total before any discounts or taxes is applied.
 * @property null|int $amount_total Total after discounts and taxes.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property string $description An arbitrary string attached to the object. Often useful for displaying to users. Defaults to product name.
 * @property \Stripe\StripeObject[] $discounts The discounts applied to the line item.
 * @property \Stripe\Price $price <p>Prices define the unit cost, currency, and (optional) billing cycle for both recurring and one-time purchases of products. <a href="https://stripe.com/docs/api#products">Products</a> help you track inventory or provisioning, and prices help you track payment terms. Different physical goods or levels of service should be represented by products, and pricing options should be represented by prices. This approach lets you change prices without having to change your provisioning scheme.</p><p>For example, you might have a single &quot;gold&quot; product that has prices for $10/month, $100/year, and €9 once.</p><p>Related guides: <a href="https://stripe.com/docs/billing/subscriptions/set-up-subscription">Set up a subscription</a>, <a href="https://stripe.com/docs/billing/invoices/create">create an invoice</a>, and more about <a href="https://stripe.com/docs/billing/prices-guide">products and prices</a>.</p>
 * @property null|int $quantity The quantity of products being purchased.
 * @property \Stripe\StripeObject[] $taxes The taxes applied to the line item.
 */
class LineItem extends ApiResource
{
    const OBJECT_NAME = 'item';

    use ApiOperations\All;
}
