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
 * @property string[] $images
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $name
 * @property mixed $package_dimensions
 * @property bool $shippable
 * @property string $statement_descriptor
 * @property string $type
 * @property string $unit_label
 * @property int $updated
 * @property string $url
 *
 * @package Stripe
 */
class Product extends ApiResource
{

    const OBJECT_NAME = "product";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
