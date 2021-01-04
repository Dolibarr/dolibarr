<?php

namespace Stripe;

/**
 * Class Dispute
 *
 * @property string $id
 * @property string $object
 * @property int $amount
 * @property BalanceTransaction[] $balance_transactions
 * @property string $charge
 * @property int $created
 * @property string $currency
 * @property mixed $evidence
 * @property mixed $evidence_details
 * @property bool $is_charge_refundable
 * @property bool $livemode
 * @property StripeObject $metadata
 * @property string $reason
 * @property string $status
 *
 * @package Stripe
 */
class Dispute extends ApiResource
{
    const OBJECT_NAME = "dispute";

    use ApiOperations\All;
    use ApiOperations\Retrieve;
    use ApiOperations\Update;

    /**
     * Possible string representations of dispute reasons.
     * @link https://stripe.com/docs/api#dispute_object
     */
    const REASON_BANK_CANNOT_PROCESS       = 'bank_cannot_process';
    const REASON_CHECK_RETURNED            = 'check_returned';
    const REASON_CREDIT_NOT_PROCESSED      = 'credit_not_processed';
    const REASON_CUSTOMER_INITIATED        = 'customer_initiated';
    const REASON_DEBIT_NOT_AUTHORIZED      = 'debit_not_authorized';
    const REASON_DUPLICATE                 = 'duplicate';
    const REASON_FRAUDULENT                = 'fraudulent';
    const REASON_GENERAL                   = 'general';
    const REASON_INCORRECT_ACCOUNT_DETAILS = 'incorrect_account_details';
    const REASON_INSUFFICIENT_FUNDS        = 'insufficient_funds';
    const REASON_PRODUCT_NOT_RECEIVED      = 'product_not_received';
    const REASON_PRODUCT_UNACCEPTABLE      = 'product_unacceptable';
    const REASON_SUBSCRIPTION_CANCELED     = 'subscription_canceled';
    const REASON_UNRECOGNIZED              = 'unrecognized';

    /**
     * Possible string representations of dispute statuses.
     * @link https://stripe.com/docs/api#dispute_object
     */
    const STATUS_CHARGE_REFUNDED        = 'charge_refunded';
    const STATUS_LOST                   = 'lost';
    const STATUS_NEEDS_RESPONSE         = 'needs_response';
    const STATUS_UNDER_REVIEW           = 'under_review';
    const STATUS_WARNING_CLOSED         = 'warning_closed';
    const STATUS_WARNING_NEEDS_RESPONSE = 'warning_needs_response';
    const STATUS_WARNING_UNDER_REVIEW   = 'warning_under_review';
    const STATUS_WON                    = 'won';

    /**
     * @param array|string|null $options
     *
     * @return Dispute The closed dispute.
     */
    public function close($options = null)
    {
        $url = $this->instanceUrl() . '/close';
        list($response, $opts) = $this->_request('post', $url, null, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
