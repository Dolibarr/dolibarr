<?php

namespace Stripe;

/**
 * Class SubscriptionItem
 *
 * @property string $id
 * @property string $object
<<<<<<< HEAD
=======
 * @property mixed $billing_thresholds
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * @property int $created
 * @property StripeObject $metadata
 * @property Plan $plan
 * @property int $quantity
 * @property string $subscription
<<<<<<< HEAD
=======
 * @property array $tax_rates
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *
 * @package Stripe
 */
class SubscriptionItem extends ApiResource
{
<<<<<<< HEAD
=======

    const OBJECT_NAME = "subscription_item";

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Delete;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
<<<<<<< HEAD
     * This is a special case because the subscription items endpoint has an
     *    underscore in it. The parent `className` function strips underscores.
     *
     * @return string The name of the class.
     */
    public static function className()
    {
        return 'subscription_item';
=======
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }
}
