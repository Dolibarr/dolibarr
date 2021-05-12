<?php

namespace Stripe;

/**
 * Class TaxRate
 *
 * @property string $id
 * @property string $object
 * @property bool $active
 * @property int $created
 * @property string $description
 * @property string $display_name
 * @property bool $inclusive
 * @property string $jurisdiction
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property float $percentage
 *
 * @package Stripe
 */
class TaxRate extends ApiResource
{

    const OBJECT_NAME = "tax_rate";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
