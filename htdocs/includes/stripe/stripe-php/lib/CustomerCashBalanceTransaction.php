<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Customers with certain payments enabled have a cash balance, representing funds
 * that were paid by the customer to a merchant, but have not yet been allocated to
 * a payment. Cash Balance Transactions represent when funds are moved into or out
 * of this balance. This includes funding by the customer, allocation to payments,
 * and refunds to the customer.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\StripeObject $applied_to_payment
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property string|\Stripe\Customer $customer The customer whose available cash balance changed as a result of this transaction.
 * @property int $ending_balance The total available cash balance for the specified currency after this transaction was applied. Represented in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>.
 * @property null|\Stripe\StripeObject $funded
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property int $net_amount The amount by which the cash balance changed, represented in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>. A positive value represents funds being added to the cash balance, a negative value represents funds being removed from the cash balance.
 * @property null|\Stripe\StripeObject $refunded_from_payment
 * @property string $type The type of the cash balance transaction. One of <code>applied_to_payment</code>, <code>unapplied_from_payment</code>, <code>refunded_from_payment</code>, <code>funded</code>, <code>return_initiated</code>, or <code>return_canceled</code>. New types may be added in future. See <a href="https://stripe.com/docs/payments/customer-balance#types">Customer Balance</a> to learn more about these types.
 * @property null|\Stripe\StripeObject $unapplied_from_payment
 */
class CustomerCashBalanceTransaction extends ApiResource
{
    const OBJECT_NAME = 'customer_cash_balance_transaction';

    use ApiOperations\All;
    use ApiOperations\Retrieve;

    const TYPE_APPLIED_TO_PAYMENT = 'applied_to_payment';
    const TYPE_FUNDED = 'funded';
    const TYPE_FUNDING_REVERSED = 'funding_reversed';
    const TYPE_REFUNDED_FROM_PAYMENT = 'refunded_from_payment';
    const TYPE_RETURN_CANCELED = 'return_canceled';
    const TYPE_RETURN_INITIATED = 'return_initiated';
    const TYPE_UNAPPLIED_FROM_PAYMENT = 'unapplied_from_payment';
}
