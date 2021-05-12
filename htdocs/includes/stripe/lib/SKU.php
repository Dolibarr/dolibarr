<?php

namespace Stripe;

/**
 * Class SKU
 *
 * @property string $id
 * @property string $object
 * @property bool $active
 * @property mixed $attributes
 * @property int $created
 * @property string $currency
 * @property string $image
 * @property mixed $inventory
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $package_dimensions
 * @property int $price
 * @property string $product
 * @property int $updated
 *
 * @package Stripe
 */
class SKU extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "sku";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
