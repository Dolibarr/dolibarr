<?php

// File generated from our OpenAPI spec

namespace Stripe\Treasury;

/**
 * Use OutboundPayments to send funds to another party's external bank account or
 * <a href="https://stripe.com/docs/api#financial_accounts">FinancialAccount</a>.
 * To send money to an account belonging to the same user, use an <a
 * href="https://stripe.com/docs/api#outbound_transfers">OutboundTransfer</a>.
 *
 * Simulate OutboundPayment state changes with the
 * <code>/v1/test_helpers/treasury/outbound_payments</code> endpoints. These
 * methods can only be called on test mode objects.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount (in cents) transferred.
 * @property bool $cancelable Returns <code>true</code> if the object can be canceled, and <code>false</code> otherwise.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $customer ID of the <a href="https://stripe.com/docs/api/customers">customer</a> to whom an OutboundPayment is sent.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|string $destination_payment_method The PaymentMethod via which an OutboundPayment is sent. This field can be empty if the OutboundPayment was created using <code>destination_payment_method_data</code>.
 * @property null|\Stripe\StripeObject $destination_payment_method_details Details about the PaymentMethod for an OutboundPayment.
 * @property null|\Stripe\StripeObject $end_user_details Details about the end user.
 * @property int $expected_arrival_date The date when funds are expected to arrive in the destination account.
 * @property string $financial_account The FinancialAccount that funds were pulled from.
 * @property null|string $hosted_regulatory_receipt_url A <a href="https://stripe.com/docs/treasury/moving-money/regulatory-receipts">hosted transaction receipt</a> URL that is provided when money movement is considered regulated under Stripe's money transmission licenses.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|\Stripe\StripeObject $returned_details Details about a returned OutboundPayment. Only set when the status is <code>returned</code>.
 * @property string $statement_descriptor The description that appears on the receiving end for an OutboundPayment (for example, bank statement for external bank transfer).
 * @property string $status Current status of the OutboundPayment: <code>processing</code>, <code>failed</code>, <code>posted</code>, <code>returned</code>, <code>canceled</code>. An OutboundPayment is <code>processing</code> if it has been created and is pending. The status changes to <code>posted</code> once the OutboundPayment has been &quot;confirmed&quot; and funds have left the account, or to <code>failed</code> or <code>canceled</code>. If an OutboundPayment fails to arrive at its destination, its status will change to <code>returned</code>.
 * @property \Stripe\StripeObject $status_transitions
 * @property string|\Stripe\Treasury\Transaction $transaction The Transaction associated with this object.
 */
class OutboundPayment extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'treasury.outbound_payment';

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;
    use \Stripe\ApiOperations\Retrieve;

    const STATUS_CANCELED = 'canceled';
    const STATUS_FAILED = 'failed';
    const STATUS_POSTED = 'posted';
    const STATUS_PROCESSING = 'processing';
    const STATUS_RETURNED = 'returned';

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Treasury\OutboundPayment the canceled outbound payment
     */
    public function cancel($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
