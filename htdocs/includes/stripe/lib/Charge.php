<?php

namespace Stripe;

/**
 * Class Charge
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property int $amount_refunded
 * @property mixed $application_fee
 * @property string $balance_transaction
 * @property bool $captured
 * @property int $created
 * @property string $currency
 * @property string $customer
 * @property mixed $description
 * @property mixed $destination
 * @property string|null $dispute
 * @property mixed $failure_code
 * @property mixed $failure_message
 * @property mixed $fraud_details
 * @property mixed $invoice
 * @property bool $livemode
 * @property mixed $metadata
 * @property mixed $order
 * @property bool $paid
 * @property mixed $receipt_email
 * @property mixed $receipt_number
 * @property bool $refunded
 * @property mixed $refunds
 * @property mixed $shipping
 * @property mixed $source
 * @property mixed $source_transfer
 * @property mixed $statement_descriptor
 * @property string $status
 *
 * @package Stripe
 */
class Charge extends ApiResource
{
    /**
     * @param string $id The ID of the charge to retrieve.
     * @param array|string|null $options
     *
     * @return Charge
     */
    public static function retrieve($id, $options = null)
    {
        return self::_retrieve($id, $options);
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Collection of Charges
     */
    public static function all($params = null, $options = null)
    {
        return self::_all($params, $options);
    }

    /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Charge The created charge.
     */
    public static function create($params = null, $options = null)
    {
        return self::_create($params, $options);
    }

    /**
     * @param string $id The ID of the charge to update.
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return Charge The updated charge.
     */
    public static function update($id, $params = null, $options = null)
    {
        return self::_update($id, $params, $options);
    }

    /**
     * @param array|string|null $options
     *
     * @return Charge The saved charge.
     */
    public function save($options = null)
    {
        return $this->_save($options);
    }

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
        $this->refreshFrom(array('dispute' => $response), $opts, true);
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
        $params = array('fraud_details' => array('user_report' => 'fraudulent'));
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
        $params = array('fraud_details' => array('user_report' => 'safe'));
        $url = $this->instanceUrl();
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
