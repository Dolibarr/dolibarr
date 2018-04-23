<?php

namespace Stripe;

/**
 * Class BankAccount
 *
 * @property string $id
 * @property string $object
 * @property string $account
 * @property string $account_holder_name
 * @property string $account_holder_type
 * @property string $bank_name
 * @property string $country
 * @property string $currency
 * @property string $customer
 * @property bool $default_for_currency
 * @property string $fingerprint
 * @property string $last4
 * @property StripeObject $metadata
 * @property string $routing_number
 * @property string $status
 *
 * @package Stripe
 */
class BankAccount extends ApiResource
{
    use ApiOperations\Delete;
    use ApiOperations\Update;

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
            $msg = "Bank accounts cannot be accessed without a customer ID or account ID.";
            throw new Error\InvalidRequest($msg, null);
        }
        $parentExtn = urlencode(Util\Util::utf8($parent));
        $extn = urlencode(Util\Util::utf8($this['id']));
        return "$base/$parentExtn/$path/$extn";
    }

    /**
     * @param array|string $_id
     * @param array|string|null $_opts
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function retrieve($_id, $_opts = null)
    {
        $msg = "Bank accounts cannot be accessed without a customer ID or account ID. " .
               "Retrieve a bank account using \$customer->sources->retrieve('bank_account_id') or " .
               "\$account->external_accounts->retrieve('bank_account_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }

    /**
     * @param string $_id
     * @param array|null $_params
     * @param array|string|null $_options
     *
     * @throws \Stripe\Error\InvalidRequest
     */
    public static function update($_id, $_params = null, $_options = null)
    {
        $msg = "Bank accounts cannot be accessed without a customer ID or account ID. " .
               "Call save() on \$customer->sources->retrieve('bank_account_id') or " .
               "\$account->external_accounts->retrieve('bank_account_id') instead.";
        throw new Error\InvalidRequest($msg, null);
    }

   /**
     * @param array|null $params
     * @param array|string|null $options
     *
     * @return BankAccount The verified bank account.
     */
    public function verify($params = null, $options = null)
    {
        $url = $this->instanceUrl() . '/verify';
        list($response, $opts) = $this->_request('post', $url, $params, $options);
        $this->refreshFrom($response, $opts);
        return $this;
    }
}
