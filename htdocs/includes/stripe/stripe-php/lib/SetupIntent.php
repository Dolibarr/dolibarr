<?php

namespace Stripe;

/**
 * Class SetupIntent
 *
 * @property string $id
 * @property string $object
 * @property string $application
 * @property string $client_secret
 * @property int $created
 * @property string $customer
 * @property string $description
 * @property mixed $last_setup_error
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $next_action
 * @property string $on_behalf_of
 * @property string $payment_method
 * @property string[] $payment_method_types
 * @property string $status
 *
 * @package Stripe
 */
class SetupIntent extends ApiResource
{
    const OBJECT_NAME = "setup_intent";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * These constants are possible representations of the status field.
     *
     * @link https://stripe.com/docs/api/setup_intents/object#setup_intent_object-status
     */
    const STATUS_CANCELED                = 'canceled';
    const STATUS_PROCESSING              = 'processing';
    const STATUS_REQUIRES_ACTION         = 'requires_action';
    const STATUS_REQUIRES_CONFIRMATION   = 'requires_confirmation';
    const STATUS_REQUIRES_PAYMENT_METHOD = 'requires_payment_method';
    const STATUS_SUCCEEDED               = 'succeeded';

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return SetupIntent The canceled setup intent.
     */
    public function cancel($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return SetupIntent The confirmed setup intent.
     */
    public function confirm($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/confirm';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
