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

    const OBJECT_NAME = "subscription_item";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Collection The list of source transactions.
     */
    public function usageRecordSummaries($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/usage_record_summaries';
        list($response, $opts) = $this->_request('get', $url, $params, $options);
        $obj = Util\Util::convertToStripeObject($response, $opts);
        $obj->setLastResponse($response);
        return $obj;
    }
}
