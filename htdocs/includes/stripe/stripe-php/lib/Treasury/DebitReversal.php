<?php

// File generated from our OpenAPI spec

namespace Stripe\Treasury;

/**
 * You can reverse some <a
 * href="https://stripe.com/docs/api#received_debits">ReceivedDebits</a> depending
 * on their network and source flow. Reversing a ReceivedDebit leads to the
 * creation of a new object known as a DebitReversal.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount Amount (in cents) transferred.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property null|string $financial_account The FinancialAccount to reverse funds from.
 * @property null|string $hosted_regulatory_receipt_url A <a href="https://stripe.com/docs/treasury/moving-money/regulatory-receipts">hosted transaction receipt</a> URL that is provided when money movement is considered regulated under Stripe's money transmission licenses.
 * @property null|\Stripe\StripeObject $linked_flows Other flows linked to a DebitReversal.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property string $network The rails used to reverse the funds.
 * @property string $received_debit The ReceivedDebit being reversed.
 * @property string $status Status of the DebitReversal
 * @property \Stripe\StripeObject $status_transitions
 * @property null|string|\Stripe\Treasury\Transaction $transaction The Transaction associated with this object.
 */
class DebitReversal extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'treasury.debit_reversal';

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;
    use \Stripe\ApiOperations\Retrieve;

    const NETWORK_ACH = 'ach';
    const NETWORK_CARD = 'card';

    const STATUS_FAILED = 'failed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCEEDED = 'succeeded';
}
