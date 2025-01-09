<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A dispute occurs when a customer questions your charge with their card issuer.
 * When this happens, you're given the opportunity to respond to the dispute with
 * evidence that shows that the charge is legitimate. You can find more information
 * about the dispute process in our <a href="/docs/disputes">Disputes and Fraud</a>
 * documentation.
 *
 * Related guide: <a href="https://stripe.com/docs/disputes">Disputes and
 * Fraud</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Disputed amount. Usually the amount of the charge, but can differ (usually because of currency fluctuation or because only part of the order is disputed).
 * @property \Stripe\BalanceTransaction[] $balance_transactions List of zero, one, or two balance transactions that show funds withdrawn and reinstated to your Stripe account as a result of this dispute.
 * @property string|\Stripe\Charge $charge ID of the charge that was disputed.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property \Stripe\StripeObject $evidence
 * @property \Stripe\StripeObject $evidence_details
 * @property bool $is_charge_refundable If true, it is still possible to refund the disputed payment. Once the payment has been fully refunded, no further funds will be withdrawn from your Stripe account as a result of this dispute.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $network_reason_code Network-dependent reason code for the dispute.
 * @property null|string|\Stripe\PaymentIntent $payment_intent ID of the PaymentIntent that was disputed.
 * @property string $reason Reason given by cardholder for dispute. Possible values are <code>bank_cannot_process</code>, <code>check_returned</code>, <code>credit_not_processed</code>, <code>customer_initiated</code>, <code>debit_not_authorized</code>, <code>duplicate</code>, <code>fraudulent</code>, <code>general</code>, <code>incorrect_account_details</code>, <code>insufficient_funds</code>, <code>product_not_received</code>, <code>product_unacceptable</code>, <code>subscription_canceled</code>, or <code>unrecognized</code>. Read more about <a href="https://stripe.com/docs/disputes/categories">dispute reasons</a>.
 * @property string $status Current status of dispute. Possible values are <code>warning_needs_response</code>, <code>warning_under_review</code>, <code>warning_closed</code>, <code>needs_response</code>, <code>under_review</code>, <code>charge_refunded</code>, <code>won</code>, or <code>lost</code>.
 */
class Dispute extends ApiResource
{
    const OBJECT_NAME = 'dispute';

    use ApiOperations\All;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    const REASON_BANK_CANNOT_PROCESS = 'bank_cannot_process';
    const REASON_CHECK_RETURNED = 'check_returned';
    const REASON_CREDIT_NOT_PROCESSED = 'credit_not_processed';
    const REASON_CUSTOMER_INITIATED = 'customer_initiated';
    const REASON_DEBIT_NOT_AUTHORIZED = 'debit_not_authorized';
    const REASON_DUPLICATE = 'duplicate';
    const REASON_FRAUDULENT = 'fraudulent';
    const REASON_GENERAL = 'general';
    const REASON_INCORRECT_ACCOUNT_DETAILS = 'incorrect_account_details';
    const REASON_INSUFFICIENT_FUNDS = 'insufficient_funds';
    const REASON_PRODUCT_NOT_RECEIVED = 'product_not_received';
    const REASON_PRODUCT_UNACCEPTABLE = 'product_unacceptable';
    const REASON_SUBSCRIPTION_CANCELED = 'subscription_canceled';
    const REASON_UNRECOGNIZED = 'unrecognized';

    const STATUS_CHARGE_REFUNDED = 'charge_refunded';
    const STATUS_LOST = 'lost';
    const STATUS_NEEDS_RESPONSE = 'needs_response';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_WARNING_CLOSED = 'warning_closed';
    const STATUS_WARNING_NEEDS_RESPONSE = 'warning_needs_response';
    const STATUS_WARNING_UNDER_REVIEW = 'warning_under_review';
    const STATUS_WON = 'won';

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Dispute the closed dispute
     */
    public function close($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/close';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
