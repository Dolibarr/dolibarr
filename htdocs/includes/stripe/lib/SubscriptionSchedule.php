<?php

namespace Stripe;

/**
 * Class SubscriptionSchedule
 *
 * @property string $id
 * @property string $object
 * @property string $billing
 * @property mixed $billing_thresholds
 * @property int $canceled_at
 * @property int $completed_at
 * @property int $created
 * @property mixed $current_phase
 * @property string $customer
 * @property mixed $invoice_settings
 * @property boolean $livemode
 * @property StripeObject $metadata
 * @property mixed $phases
 * @property int $released_at
 * @property string $released_subscription
 * @property string $renewal_behavior
 * @property mixed $renewal_interval
 * @property string $revision
 * @property string $status
 * @property string $subscription
 *
 * @package Stripe
 */
class SubscriptionSchedule extends ApiResource
{

    const OBJECT_NAME = "subscription_schedule";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;
    use ApiOperations\NestedResource;

    const PATH_REVISIONS = '/revisions';

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return SubscriptionSchedule The canceled subscription schedule.
     */
    public function cancel($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return SubscriptionSchedule The released subscription schedule.
     */
    public function release($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/release';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Collection The list of subscription schedule revisions.
     */
    public function revisions($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/revisions';
        list($response, $opts) = $this->_request('get', $url, $params, $options);
        $obj = Util\Util::convertToStripeObject($response, $opts);
        $obj->setLastResponse($response);
        return $obj;
    }

    /**
     * @param array|null $id The ID of the subscription schedule to which the person belongs.
     * @param array|null $personId The ID of the person to retrieve.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Revision
     */
    public static function retrieveRevision($id, $personId, $params = null, $opts = null)
    {
        return self::_retrieveNestedResource($id, static::PATH_REVISIONS, $personId, $params, $opts);
    }

    /**
     * @param array|null $id The ID of the subscription schedule on which to retrieve the persons.
     * @param array|null $params
     * @param array|string|null $opts
     *
     * @return Revision
     */
    public static function allRevisions($id, $params = null, $opts = null)
    {
        return self::_allNestedResources($id, static::PATH_REVISIONS, $params, $opts);
    }
}
