<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Stores representations of <a
 * href="http://en.wikipedia.org/wiki/Stock_keeping_unit">stock keeping units</a>.
 * SKUs describe specific product variations, taking into account any combination
 * of: attributes, currency, and cost. For example, a product may be a T-shirt,
 * whereas a specific SKU represents the <code>size: large</code>, <code>color:
 * red</code> version of that shirt.
 *
 * Can also be used to manage inventory.
 *
 * Related guide: <a href="https://stripe.com/docs/orders">Tax, Shipping, and
 * Inventory</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property bool $active Whether the SKU is available for purchase.
 * @property \Stripe\StripeObject $attributes A dictionary of attributes and values for the attributes defined by the product. If, for example, a product's attributes are <code>[&quot;size&quot;, &quot;gender&quot;]</code>, a valid SKU has the following dictionary of attributes: <code>{&quot;size&quot;: &quot;Medium&quot;, &quot;gender&quot;: &quot;Unisex&quot;}</code>.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $image The URL of an image for this SKU, meant to be displayable to the customer.
 * @property \Stripe\StripeObject $inventory
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|\Stripe\StripeObject $package_dimensions The dimensions of this SKU for shipping purposes.
 * @property int $price The cost of the item as a positive integer in the smallest currency unit (that is, 100 cents to charge $1.00, or 100 to charge ¥100, Japanese Yen being a zero-decimal currency).
 * @property string|\Stripe\Product $product The ID of the product this SKU is associated with. The product must be currently active.
 * @property int $updated Time at which the object was last updated. Measured in seconds since the Unix epoch.
 */
class SKU extends ApiResource
{
    const OBJECT_NAME = 'sku';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
