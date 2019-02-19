<?php

namespace Stripe;

/**
 * Class PaymentIntent
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $amount_capturable
 * @property int $amount_received
 * @property string $application
 * @property int $application_fee
 * @property int $canceled_at
 * @property string $cancellation_reason
 * @property string $capture_method
 * @property Collection $charges
 * @property string $client_secret
 * @property string $confirmation_method
 * @property int $created
 * @property string $currency
 * @property string $customer
 * @property string $description
 * @property mixed $last_payment_error
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property mixed $next_action
 * @property string $on_behalf_of
 * @property string[] $payment_method_types
 * @property string $receipt_email
 * @property string $return_url
 * @property string $review
 * @property mixed $shipping
 * @property string $source
 * @property string $statement_descriptor
 * @property string $status
 * @property mixed $transfer_data
 * @property string $transfer_group
 *
 * @package Stripe
 */
class PaymentIntent extends ApiResource
{

    const OBJECT_NAME = "payment_intent";

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return PaymentIntent The canceled payment intent.
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
     * @return PaymentIntent The captured payment intent.
     */
    public function capture($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/capture';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return PaymentIntent The confirmed payment intent.
     */
    public function confirm($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/confirm';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
