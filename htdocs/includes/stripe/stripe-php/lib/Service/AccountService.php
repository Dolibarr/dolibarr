<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class AccountService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of accounts connected to your platform via <a
     * href="/docs/connect">Connect</a>. If you’re not a platform, the list is empty.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/accounts', $params, $opts);
    }

    /**
     * Returns a list of capabilities associated with the account. The capabilities are
     * returned sorted by creation date, with the most recent capability appearing
     * first.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection
     */
    public function allCapabilities($parentId, $params = null, $opts = null)
    {
        return $this->requestCollection('get', $this->buildPath('/v1/accounts/%s/capabilities', $parentId), $params, $opts);
    }

    /**
     * List external accounts for an account.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection
     */
    public function allExternalAccounts($parentId, $params = null, $opts = null)
    {
        return $this->requestCollection('get', $this->buildPath('/v1/accounts/%s/external_accounts', $parentId), $params, $opts);
    }

    /**
     * Returns a list of people associated with the account’s legal entity. The people
     * are returned sorted by creation date, with the most recent people appearing
     * first.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection
     */
    public function allPersons($parentId, $params = null, $opts = null)
    {
        return $this->requestCollection('get', $this->buildPath('/v1/accounts/%s/persons', $parentId), $params, $opts);
    }

    /**
     * With <a href="/docs/connect">Connect</a>, you can create Stripe accounts for
     * your users. To do this, you’ll first need to <a
     * href="https://dashboard.stripe.com/account/applications/settings">register your
     * platform</a>.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Account
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/accounts', $params, $opts);
    }

    /**
     * Create an external account for a given account.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\BankAccount|\Stripe\Card
     */
    public function createExternalAccount($parentId, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s/external_accounts', $parentId), $params, $opts);
    }

    /**
     * Creates a single-use login link for an Express account to access their Stripe
     * dashboard.
     *
     * <strong>You may only create login links for <a
     * href="/docs/connect/express-accounts">Express accounts</a> connected to your
     * platform</strong>.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\LoginLink
     */
    public function createLoginLink($parentId, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s/login_links', $parentId), $params, $opts);
    }

    /**
     * Creates a new person.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Person
     */
    public function createPerson($parentId, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s/persons', $parentId), $params, $opts);
    }

    /**
     * With <a href="/docs/connect">Connect</a>, you can delete Custom or Express
     * accounts you manage.
     *
     * Accounts created using test-mode keys can be deleted at any time. Accounts
     * created using live-mode keys can only be deleted once all balances are zero.
     *
     * If you want to delete your own account, use the <a
     * href="https://dashboard.stripe.com/account">account information tab in your
     * account settings</a> instead.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Account
     */
    public function delete($id, $params = null, $opts = null)
    {
        return $this->request('delete', $this->buildPath('/v1/accounts/%s', $id), $params, $opts);
    }

    /**
     * Delete a specified external account for a given account.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\BankAccount|\Stripe\Card
     */
    public function deleteExternalAccount($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('delete', $this->buildPath('/v1/accounts/%s/external_accounts/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Deletes an existing person’s relationship to the account’s legal entity. Any
     * person with a relationship for an account can be deleted through the API, except
     * if the person is the <code>account_opener</code>. If your integration is using
     * the <code>executive</code> parameter, you cannot delete the only verified
     * <code>executive</code> on file.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Person
     */
    public function deletePerson($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('delete', $this->buildPath('/v1/accounts/%s/persons/%s', $parentId, $id), $params, $opts);
    }

    /**
     * With <a href="/docs/connect">Connect</a>, you may flag accounts as suspicious.
     *
     * Test-mode Custom and Express accounts can be rejected at any time. Accounts
     * created using live-mode keys may only be rejected once all balances are zero.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Account
     */
    public function reject($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s/reject', $id), $params, $opts);
    }

    /**
     * Retrieves information about the specified Account Capability.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Capability
     */
    public function retrieveCapability($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/accounts/%s/capabilities/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Retrieve a specified external account for a given account.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\BankAccount|\Stripe\Card
     */
    public function retrieveExternalAccount($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/accounts/%s/external_accounts/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Retrieves an existing person.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Person
     */
    public function retrievePerson($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/accounts/%s/persons/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Updates a connected <a href="/docs/connect/accounts">Express or Custom
     * account</a> by setting the values of the parameters passed. Any parameters not
     * provided are left unchanged. Most parameters can be changed only for Custom
     * accounts. (These are marked <strong>Custom Only</strong> below.) Parameters
     * marked <strong>Custom and Express</strong> are supported by both account types.
     *
     * To update your own account, use the <a
     * href="https://dashboard.stripe.com/account">Dashboard</a>. Refer to our <a
     * href="/docs/connect/updating-accounts">Connect</a> documentation to learn more
     * about updating accounts.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Account
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s', $id), $params, $opts);
    }

    /**
     * Updates an existing Account Capability.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Capability
     */
    public function updateCapability($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s/capabilities/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Updates the metadata, account holder name, and account holder type of a bank
     * account belonging to a <a href="/docs/connect/custom-accounts">Custom
     * account</a>, and optionally sets it as the default for its currency. Other bank
     * account details are not editable by design.
     *
     * You can re-enable a disabled bank account by performing an update call without
     * providing any arguments or changes.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\BankAccount|\Stripe\Card
     */
    public function updateExternalAccount($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s/external_accounts/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Updates an existing person.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Person
     */
    public function updatePerson($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/accounts/%s/persons/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Retrieves the details of an account.
     *
     * @param null|string $id
     * @param null|array $params
     * @param null|array|StripeUtilRequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Account
     */
    public function retrieve($id = null, $params = null, $opts = null)
    {
        if (null === $id) {
            return $this->request('get', '/v1/account', $params, $opts);
        }

        return $this->request('get', $this->buildPath('/v1/accounts/%s', $id), $params, $opts);
    }
}
