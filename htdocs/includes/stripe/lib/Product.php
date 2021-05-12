<?php

namespace Stripe;

/**
 * Class Product
 *
 * @property string $id
 * @property string $object
 * @property bool $active
 * @property string[] $attributes
 * @property string $caption
 * @property int $created
 * @property string[] $deactivate_on
 * @property string $description
<<<<<<< HEAD
 * @property array $images
=======
 * @property string[] $images
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $name
 * @property mixed $package_dimensions
 * @property bool $shippable
<<<<<<< HEAD
 * @property Collection $skus
 * @property string $statement_descriptor
 * @property string $type
=======
 * @property string $statement_descriptor
 * @property string $type
 * @property string $unit_label
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property int $updated
 * @property string $url
 *
 * @package Stripe
 */
class Product extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "product";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
<<<<<<< HEAD
=======

    /**
     * Possible string representations of the type of product.
     * @link https://stripe.com/docs/api/service_products/object#service_product_object-type
     */
    const TYPE_GOOD    = 'good';
    const TYPE_SERVICE = 'service';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
