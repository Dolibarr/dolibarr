<?php

// File generated from our OpenAPI spec

namespace Stripe\Treasury;

/**
 * Use OutboundTransfers to transfer funds from a <a
 * href="https://stripe.com/docs/api#financial_accounts">FinancialAccount</a> to a
 * PaymentMethod belonging to the same entity. To send funds to a different party,
 * use <a href="https://stripe.com/docs/api#outbound_payments">OutboundPayments</a>
 * instead. You can send funds over ACH rails or through a domestic wire transfer
 * to a user's own external bank account.
 *
 * Simulate OutboundTransfer state changes with the
 * <code>/v1/test_helpers/treasury/outbound_transfers</code> endpoints. These
 * methods can only be called on test mode objects.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount (in cents) transferred.
 * @property bool $cancelable Returns <code>true</code> if the object can be canceled, and <code>false</code> otherwise.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property null|string $destination_payment_method The PaymentMethod used as the payment instrument for an OutboundTransfer.
 * @property \Stripe\StripeObject $destination_payment_method_details
 * @property int $expected_arrival_date The date when funds are expected to arrive in the destination account.
 * @property string $financial_account The FinancialAccount that funds were pulled from.
 * @property null|string $hosted_regulatory_receipt_url A <a href="https://stripe.com/docs/treasury/moving-money/regulatory-receipts">hosted transaction receipt</a> URL that is provided when money movement is considered regulated under Stripe's money transmission licenses.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|\Stripe\StripeObject $returned_details Details about a returned OutboundTransfer. Only set when the status is <code>returned</code>.
 * @property string $statement_descriptor Information about the OutboundTransfer to be sent to the recipient account.
 * @property string $status Current status of the OutboundTransfer: <code>processing</code>, <code>failed</code>, <code>canceled</code>, <code>posted</code>, <code>returned</code>. An OutboundTransfer is <code>processing</code> if it has been created and is pending. The status changes to <code>posted</code> once the OutboundTransfer has been &quot;confirmed&quot; and funds have left the account, or to <code>failed</code> or <code>canceled</code>. If an OutboundTransfer fails to arrive at its destination, its status will change to <code>returned</code>.
 * @property \Stripe\StripeObject $status_transitions
 * @property string|\Stripe\Treasury\Transaction $transaction The Transaction associated with this object.
 */
class OutboundTransfer extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'treasury.outbound_transfer';

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
     * @return \Stripe\Treasury\OutboundTransfer the canceled outbound transfer
     */
    public function cancel($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
