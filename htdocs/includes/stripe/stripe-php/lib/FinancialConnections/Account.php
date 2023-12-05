<?php

// File generated from our OpenAPI spec

namespace Stripe\FinancialConnections;

/**
 * A Financial Connections Account represents an account that exists outside of
 * Stripe, to which you have been granted some degree of access.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\Stripe\StripeObject $account_holder The account holder that this account belongs to.
 * @property null|\Stripe\StripeObject $balance The most recent information about the account's balance.
 * @property null|\Stripe\StripeObject $balance_refresh The state of the most recent attempt to refresh the account balance.
 * @property string $category The type of the account. Account category is further divided in <code>subcategory</code>.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|string $display_name A human-readable name that has been assigned to this account, either by the account holder or by the institution.
 * @property string $institution_name The name of the institution that holds this account.
 * @property null|string $last4 The last 4 digits of the account number. If present, this will be 4 numeric characters.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property null|string|\Stripe\FinancialConnections\AccountOwnership $ownership The most recent information about the account's owners.
 * @property null|\Stripe\StripeObject $ownership_refresh The state of the most recent attempt to refresh the account owners.
 * @property null|string[] $permissions The list of permissions granted by this account.
 * @property string $status The status of the link to the account.
 * @property string $subcategory <p>If <code>category</code> is <code>cash</code>, one of:</p><p>- <code>checking</code> - <code>savings</code> - <code>other</code></p><p>If <code>category</code> is <code>credit</code>, one of:</p><p>- <code>mortgage</code> - <code>line_of_credit</code> - <code>credit_card</code> - <code>other</code></p><p>If <code>category</code> is <code>investment</code> or <code>other</code>, this will be <code>other</code>.</p>
 * @property string[] $supported_payment_method_types The <a href="https://stripe.com/docs/api/payment_methods/object#payment_method_object-type">PaymentMethod type</a>(s) that can be created from this account.
 */
class Account extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'financial_connections.account';

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Retrieve;

    const CATEGORY_CASH = 'cash';
    const CATEGORY_CREDIT = 'credit';
    const CATEGORY_INVESTMENT = 'investment';
    const CATEGORY_OTHER = 'other';

    const STATUS_ACTIVE = 'active';
    const STATUS_DISCONNECTED = 'disconnected';
    const STATUS_INACTIVE = 'inactive';

    const SUBCATEGORY_CHECKING = 'checking';
    const SUBCATEGORY_CREDIT_CARD = 'credit_card';
    const SUBCATEGORY_LINE_OF_CREDIT = 'line_of_credit';
    const SUBCATEGORY_MORTGAGE = 'mortgage';
    const SUBCATEGORY_OTHER = 'other';
    const SUBCATEGORY_SAVINGS = 'savings';

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\FinancialConnections\Account the disconnected account
     */
    public function disconnect($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/disconnect';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }

    /**
     * @param string $id
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection<\Stripe\FinancialConnections\AccountOwner> list of BankConnectionsResourceOwners
     */
    public static function allOwners($id, $params = null, $opts = null)
    {
        $url = static::resourceUrl($id) . '/owners';
        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = \Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\FinancialConnections\Account the refreshed account
     */
    public function refreshAccount($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/refresh';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
