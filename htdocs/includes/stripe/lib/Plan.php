<?php

namespace Stripe;

/**
 * Class Plan
 *
 * @package Stripe
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $created
 * @property string $currency
 * @property string $interval
 * @property int $interval_count
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $nickname
 * @property string $product
 * @property int $trial_period_days
 */
class Plan extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
