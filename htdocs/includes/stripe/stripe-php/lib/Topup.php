<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * To top up your Stripe balance, you create a top-up object. You can retrieve
 * individual top-ups, as well as list all top-ups. Top-ups are identified by a
 * unique, random ID.
 *
 * Related guide: <a href="https://stripe.com/docs/connect/top-ups">Topping Up your
 * Platform Account</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount transferred.
 * @property null|string|\Stripe\BalanceTransaction $balance_transaction ID of the balance transaction that describes the impact of this top-up on your account balance. May not be specified depending on status of top-up.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|int $expected_availability_date Date the funds are expected to arrive in your Stripe account for payouts. This factors in delays like weekends or bank holidays. May not be specified depending on status of top-up.
 * @property null|string $failure_code Error code explaining reason for top-up failure if available (see <a href="https://stripe.com/docs/api#errors">the errors section</a> for a list of codes).
 * @property null|string $failure_message Message to user further explaining reason for top-up failure if available.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property \Stripe\Source $source <p><code>Source</code> objects allow you to accept a variety of payment methods. They represent a customer's payment instrument, and can be used with the Stripe API just like a <code>Card</code> object: once chargeable, they can be charged, or can be attached to customers.</p><p>Related guides: <a href="https://stripe.com/docs/sources">Sources API</a> and <a href="https://stripe.com/docs/sources/customers">Sources &amp; Customers</a>.</p>
 * @property null|string $statement_descriptor Extra information about a top-up. This will appear on your source's bank statement. It must contain at least one letter.
 * @property string $status The status of the top-up is either <code>canceled</code>, <code>failed</code>, <code>pending</code>, <code>reversed</code>, or <code>succeeded</code>.
 * @property null|string $transfer_group A string that identifies this top-up as part of a group.
 */
class Topup extends ApiResource
{
    const OBJECT_NAME = 'topup';

    use ApiOperations\All;
    use ApiOperations\Create;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const STATUS_CANCELED = 'canceled';
    const STATUS_FAILED = 'failed';
    const STATUS_PENDING = 'pending';
    const STATUS_REVERSED = 'reversed';
    const STATUS_SUCCEEDED = 'succeeded';

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return Topup the canceled topup
     */
    public function cancel($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
