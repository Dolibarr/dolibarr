<?php

namespace Stripe;

/**
 * Class SubscriptionItem
 *
 * @property string $id
 * @property string $object
 * @property int $created
 * @property StripeObject $metadata
 * @property Plan $plan
 * @property int $quantity
 * @property string $subscription
 *
 * @package Stripe
 */
class SubscriptionItem extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * This is a special case because the subscription items endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'subscription_item';
    }
}
