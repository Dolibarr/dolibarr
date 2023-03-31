<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * Each customer has a <a
 * href="https://stripe.com/docs/api/customers/object#customer_object-balance"><code>balance</code></a>
 * value, which denotes a debit or credit that's automatically applied to their
 * next invoice upon finalization. You may modify the value directly by using the
 * <a href="https://stripe.com/docs/api/customers/update">update customer API</a>,
 * or by creating a Customer Balance Transaction, which increments or decrements
 * the customer's <code>balance</code> by the specified <code>amount</code>.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/billing/customer/balance">Customer Balance</a> to
 * learn more.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $amount The amount of the transaction. A negative value is a credit for the customer's balance, and a positive value is a debit to the customer's <code>balance</code>.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string|\Stripe\CreditNote $credit_note The ID of the credit note (if any) related to the transaction.
 * @property string $currency Three-letter <a href="https://www.iso.org/iso-4217-currency-codes.html">ISO currency code</a>, in lowercase. Must be a <a href="https://stripe.com/docs/currencies">supported currency</a>.
 * @property string|\Stripe\Customer $customer The ID of the customer the transaction belongs to.
 * @property null|string $description An arbitrary string attached to the object. Often useful for displaying to users.
 * @property int $ending_balance The customer's <code>balance</code> after the transaction was applied. A negative value decreases the amount due on the customer's next invoice. A positive value increases the amount due on the customer's next invoice.
 * @property null|string|\Stripe\Invoice $invoice The ID of the invoice (if any) related to the transaction.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property string $type Transaction type: <code>adjustment</code>, <code>applied_to_invoice</code>, <code>credit_note</code>, <code>initial</code>, <code>invoice_too_large</code>, <code>invoice_too_small</code>, <code>unspent_receiver_credit</code>, or <code>unapplied_from_invoice</code>. See the <a href="https://stripe.com/docs/billing/customer/balance#types">Customer Balance page</a> to learn more about transaction types.
 */
class CustomerBalanceTransaction extends ApiResource
{
    const OBJECT_NAME = 'customer_balance_transaction';

    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_APPLIED_TO_INVOICE = 'applied_to_invoice';
    const TYPE_CREDIT_NOTE = 'credit_note';
    const TYPE_INITIAL = 'initial';
    const TYPE_INVOICE_TOO_LARGE = 'invoice_too_large';
    const TYPE_INVOICE_TOO_SMALL = 'invoice_too_small';
    const TYPE_UNSPENT_RECEIVER_CREDIT = 'unspent_receiver_credit';

    const TYPE_ADJUSTEMENT = 'adjustment';

    /**
     * @return string the API URL for this balance transaction
     */
    public function instanceUrl()
    {
        $id = $this['id'];
        $customer = $this['customer'];
        if (!$id) {
            throw new Exception\UnexpectedValueException(
                "Could not determine which URL to request: class instance has invalid ID: {$id}",
                null
            );
        }
        $id = Util\Util::utf8($id);
        $customer = Util\Util::utf8($customer);

        $base = Customer::classUrl();
        $customerExtn = \urlencode($customer);
        $extn = \urlencode($id);

        return "{$base}/{$customerExtn}/balance_transactions/{$extn}";
    }

    /**
     * @param array|string $_id
     * @param null|array|string $_opts
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = 'Customer Balance Transactions cannot be retrieved without a ' .
               'customer ID. Retrieve a Customer Balance Transaction using ' .
               "`Customer::retrieveBalanceTransaction('customer_id', " .
               "'balance_transaction_id')`.";

        throw new Exception\BadMethodCallException($msg);
    }

    /**
     * @param string $_id
     * @param null|array $_params
     * @param null|array|string $_options
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function update($_id, $_params = null, $_options = null)
    {
        $msg = 'Customer Balance Transactions cannot be updated without a ' .
               'customer ID. Update a Customer Balance Transaction using ' .
               "`Customer::updateBalanceTransaction('customer_id', " .
               "'balance_transaction_id', \$updateParams)`.";

        throw new Exception\BadMethodCallException($msg);
    }
}
