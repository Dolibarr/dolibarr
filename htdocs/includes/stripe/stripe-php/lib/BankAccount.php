<?php

// File generated from our OpenAPI spec

namespace Stripe;

/**
 * These bank accounts are payment methods on <code>Customer</code> objects.
 *
 * On the other hand <a
 * href="https://stripe.com/docs/api#external_accounts">External Accounts</a> are
 * transfer destinations on <code>Account</code> objects for <a
 * href="https://stripe.com/docs/connect/custom-accounts">Custom accounts</a>. They
 * can be bank accounts or debit cards as well, and are documented in the links
 * above.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/payments/bank-debits-transfers">Bank Debits and
 * Transfers</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string|\Stripe\Account $account The ID of the account that the bank account is associated with.
 * @property null|string $account_holder_name The name of the person or business that owns the bank account.
 * @property null|string $account_holder_type The type of entity that holds the account. This can be either <code>individual</code> or <code>company</code>.
 * @property null|string[] $available_payout_methods A set of available payout methods for this bank account. Only values from this set should be passed as the <code>method</code> when creating a payout.
 * @property null|string $bank_name Name of the bank associated with the routing number (e.g., <code>WELLS FARGO</code>).
 * @property string $country Two-letter ISO code representing the country the bank account is located in.
 * @property string $currency Three-letter <a href="https://stripe.com/docs/payouts">ISO code for the currency</a> paid out to the bank account.
 * @property null|string|\Stripe\Customer $customer The ID of the customer that the bank account is associated with.
 * @property null|bool $default_for_currency Whether this bank account is the default external account for its currency.
 * @property null|string $fingerprint Uniquely identifies this particular bank account. You can use this attribute to check whether two bank accounts are the same.
 * @property string $last4 The last four digits of the bank account number.
 * @property null|\Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property null|string $routing_number The routing transit number for the bank account.
 * @property string $status <p>For bank accounts, possible values are <code>new</code>, <code>validated</code>, <code>verified</code>, <code>verification_failed</code>, or <code>errored</code>. A bank account that hasn't had any activity or validation performed is <code>new</code>. If Stripe can determine that the bank account exists, its status will be <code>validated</code>. Note that there often isn’t enough information to know (e.g., for smaller credit unions), and the validation is not always run. If customer bank account verification has succeeded, the bank account status will be <code>verified</code>. If the verification failed for any reason, such as microdeposit failure, the status will be <code>verification_failed</code>. If a transfer sent to this bank account fails, we'll set the status to <code>errored</code> and will not continue to send transfers until the bank details are updated.</p><p>For external accounts, possible values are <code>new</code> and <code>errored</code>. Validations aren't run against external accounts because they're only used for payouts. This means the other statuses don't apply. If a transfer fails, the status is set to <code>errored</code> and transfers are stopped until account details are updated.</p>
 */
class BankAccount extends ApiResource
{
    const OBJECT_NAME = 'bank_account';

    use ApiOperations\Delete;
    use ApiOperations\Update;

    /**
     * Possible string representations of the bank verification status.
     *
     * @see https://stripe.com/docs/api/external_account_bank_accounts/object#account_bank_account_object-status
     */
    const STATUS_NEW = 'new';
    const STATUS_VALIDATED = 'validated';
    const STATUS_VERIFIED = 'verified';
    const STATUS_VERIFICATION_FAILED = 'verification_failed';
    const STATUS_ERRORED = 'errored';

    /**
     * @return string The instance URL for this resource. It needs to be special
     *    cased because it doesn't fit into the standard resource pattern.
     */
    public function instanceUrl()
    {
        if ($this['customer']) {
            $base = Customer::classUrl();
            $parent = $this['customer'];
            $path = 'sources';
        } elseif ($this['account']) {
            $base = Account::classUrl();
            $parent = $this['account'];
            $path = 'external_accounts';
        } else {
            $msg = 'Bank accounts cannot be accessed without a customer ID or account ID.';

            throw new Exception\UnexpectedValueException($msg, null);
        }
        $parentExtn = \urlencode(Util\Util::utf8($parent));
        $extn = \urlencode(Util\Util::utf8($this['id']));

        return "{$base}/{$parentExtn}/{$path}/{$extn}";
    }

    /**
     * @param array|string $_id
     * @param null|array|string $_opts
     *
     * @throws \Stripe\Exception\BadMethodCallException
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = 'Bank accounts cannot be retrieved without a customer ID or ' .
               'an account ID. Retrieve a bank account using ' .
               "`Customer::retrieveSource('customer_id', " .
               "'bank_account_id')` or `Account::retrieveExternalAccount(" .
               "'account_id', 'bank_account_id')`.";

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
        $msg = 'Bank accounts cannot be updated without a customer ID or an ' .
               'account ID. Update a bank account using ' .
               "`Customer::updateSource('customer_id', 'bank_account_id', " .
               '$updateParams)` or `Account::updateExternalAccount(' .
               "'account_id', 'bank_account_id', \$updateParams)`.";

        throw new Exception\BadMethodCallException($msg);
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return BankAccount the verified bank account
     */
    public function verify($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/verify';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
