<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * A customer's <code>Cash balance</code> represents real funds. Customers can add
 * funds to their cash balance by sending a bank transfer. These funds can be used
 * for payment and can eventually be paid out to your bank account.
 *
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\StripeObject $available A hash of all cash balances available to this customer. You cannot delete a customer with any cash balances, even if the balance is 0. Amounts are represented in the <a href="https://stripe.com/docs/currencies#zero-decimal">smallest currency unit</a>.
 * @property string $customer The ID of the customer whose cash balance this object represents.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $settings
 */
class CashBalance extends ApiResource
{
    const OBJECT_NAME = 'cash_balance';

    /**
     * @return string the API URL for this balance transaction
     */
    public function instanceUrl()
    {
        $customer = $this['customer'];
        $customer = Util\Util::utf8($customer);

        $base = Customer::classUrl();
        $customerExtn = \urlencode($customer);

        return "{$base}/{$customerExtn}/cash_balance";
    }

    /**
     * @param array|string $_id
     * @param null|array|string $_opts
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = 'Customer Cash Balance cannot be retrieved without a ' .
               'customer ID. Retrieve a Customer Cash Balance using ' .
               "`Customer::retrieveCashBalance('customer_id')`.";

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
        $msg = 'Customer Cash Balance cannot be updated without a ' .
        'customer ID. Retrieve a Customer Cash Balance using ' .
        "`Customer::updateCashBalance('customer_id')`.";

        throw new Exception\BadMethodCallException($msg);
    }
}
