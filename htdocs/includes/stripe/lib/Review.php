<?php

namespace Stripe;

/**
 * Class Review
 *
 * @property string $id
 * @property string $object
 * @property string $charge
 * @property int $created
 * @property bool $livemode
 * @property bool $open
 * @property sring $payment_intent
 * @property string $reason
 *
 * @package Stripe
 */
class Review extends \Stripe\ApiResource
{
    const OBJECT_NAME = "review";

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Retrieve;

    /**
     * @param array|string|null $options
     *
     * @return Review The approved review.
     */
    public function approve($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/approve';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
