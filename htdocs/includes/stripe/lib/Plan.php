<?php

namespace Stripe;

/**
 * Class Plan
 *
 * @package Stripe
 *
 * @property string $id
 * @property string $object
<<<<<<< HEAD
 * @property int $amount
=======
 * @property bool $active
 * @property string $aggregate_usage
 * @property int $amount
 * @property string $billing_scheme
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property int $created
 * @property string $currency
 * @property string $interval
 * @property int $interval_count
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $nickname
 * @property string $product
<<<<<<< HEAD
 * @property int $trial_period_days
 */
class Plan extends ApiResource
{
=======
 * @property mixed $tiers
 * @property string $tiers_mode
 * @property mixed $transform_usage
 * @property int $trial_period_days
 * @property string $usage_type
 */
class Plan extends ApiResource
{

    const OBJECT_NAME = "plan";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
}
