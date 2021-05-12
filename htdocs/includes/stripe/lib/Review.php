<?php

namespace Stripe;

/**
 * Class Review
 *
 * @property string $id
 * @property string $object
 * @property string $billing_zip
 * @property string $charge
 * @property string $closed_reason
 * @property int $created
 * @property string $ip_address
 * @property mixed $ip_address_location
 * @property bool $livemode
 * @property bool $open
 * @property string $opened_reason
 * @property string $payment_intent
 * @property string $reason
 * @property mixed $session
 *
 * @package Stripe
 */
class Review extends ApiResource
{
    const OBJECT_NAME = "review";

    use ApiOperations\All;
    use ApiOperations\Retrieve;

    /**
     * Possible string representations of the current, the opening or the closure reason of the review.
     * Not all of these enumeration apply to all of the ´reason´ fields. Please consult the Review object to
     * determine where these are apply.
     * @link https://stripe.com/docs/api/radar/reviews/object
     */
    const REASON_APPROVED          = 'approved';
    const REASON_DISPUTED          = 'disputed';
    const REASON_MANUAL            = 'manual';
    const REASON_REFUNDED          = 'refunded';
    const REASON_REFUNDED_AS_FRAUD = 'refunded_as_fraud';
    const REASON_RULE              = 'rule';

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
