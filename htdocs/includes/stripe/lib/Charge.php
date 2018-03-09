<?php

namespace Stripe;

/**
 * Class Charge
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $amount_refunded
 * @property string $application
 * @property string $application_fee
 * @property string $balance_transaction
 * @property bool $captured
 * @property int $created
 * @property string $currency
 * @property string $customer
 * @property string $description
 * @property string $destination
 * @property string $dispute
 * @property string $failure_code
 * @property string $failure_message
 * @property mixed $fraud_details
 * @property string $invoice
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $on_behalf_of
 * @property string $order
 * @property mixed $outcome
 * @property bool $paid
 * @property string $receipt_email
 * @property string $receipt_number
 * @property bool $refunded
 * @property Collection $refunds
 * @property string $review
 * @property mixed $shipping
 * @property mixed $source
 * @property string $source_transfer
 * @property string $statement_descriptor
 * @property string $status
 * @property string $transfer
 * @property string $transfer_group
 *
 * @package Stripe
 */
class Charge extends ApiResource
{
    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Charge The refunded charge.
     */
    public function refund($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/refund';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Charge The captured charge.
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
     * @deprecated Use the `save` method on the Dispute object
     *
     * @return array The updated dispute.
     */
    public function updateDispute($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/dispute';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom(['dispute' => $response], $opts, true);
        return $this->dispute;
    }

    /**
     * @param array|string|null $options
     *
     * @deprecated Use the `close` method on the Dispute object
     *
     * @return Charge The updated charge.
     */
    public function closeDispute($options = null)
    {
        $url = $this->instanceUrl() . '/dispute/close';
        list($response, $opts) = $this->_request('post', $url, null, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|string|null $opts
     *
     * @return Charge The updated charge.
     */
    public function markAsFraudulent($opts = null)
    {
        $params = ['fraud_details' => ['user_report' => 'fraudulent']];
        $url = $this->instanceUrl();
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }

    /**
     * @param array|string|null $opts
     *
     * @return Charge The updated charge.
     */
    public function markAsSafe($opts = null)
    {
        $params = ['fraud_details' => ['user_report' => 'safe']];
        $url = $this->instanceUrl();
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
