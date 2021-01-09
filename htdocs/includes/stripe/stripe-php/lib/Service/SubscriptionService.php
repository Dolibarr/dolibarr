<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class SubscriptionService extends \Stripe\Service\AbstractService
{
    /**
     * By default, returns a list of subscriptions that have not been canceled. In
     * order to list canceled subscriptions, specify <code>status=canceled</code>.
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
        return $this->requestCollection('get', '/v1/subscriptions', $params, $opts);
    }

    /**
     * Cancels a customer’s subscription immediately. The customer will not be charged
     * again for the subscription.
     *
     * Note, however, that any pending invoice items that you’ve created will still be
     * charged for at the end of the period, unless manually <a
     * href="#delete_invoiceitem">deleted</a>. If you’ve set the subscription to cancel
     * at the end of the period, any pending prorations will also be left in place and
     * collected at the end of the period. But if the subscription is set to cancel
     * immediately, pending prorations will be removed.
     *
     * By default, upon subscription cancellation, Stripe will stop automatic
     * collection of all finalized invoices for the customer. This is intended to
     * prevent unexpected payment attempts after the customer has canceled a
     * subscription. However, you can resume automatic collection of the invoices
     * manually after subscription cancellation to have us proceed. Or, you could check
     * for unpaid invoices before allowing the customer to cancel the subscription at
     * all.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Subscription
     */
    public function cancel($id, $params = null, $opts = null)
    {
        return $this->request('delete', $this->buildPath('/v1/subscriptions/%s', $id), $params, $opts);
    }

    /**
     * Creates a new subscription on an existing customer. Each customer can have up to
     * 500 active or scheduled subscriptions.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Subscription
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/subscriptions', $params, $opts);
    }

    /**
     * Removes the currently applied discount on a subscription.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Subscription
     */
    public function deleteDiscount($id, $params = null, $opts = null)
    {
        return $this->request('delete', $this->buildPath('/v1/subscriptions/%s/discount', $id), $params, $opts);
    }

    /**
     * Retrieves the subscription with the given ID.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Subscription
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/subscriptions/%s', $id), $params, $opts);
    }

    /**
     * Updates an existing subscription on a customer to match the specified
     * parameters. When changing plans or quantities, we will optionally prorate the
     * price we charge next month to make up for any price changes. To preview how the
     * proration will be calculated, use the <a href="#upcoming_invoice">upcoming
     * invoice</a> endpoint.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Subscription
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/subscriptions/%s', $id), $params, $opts);
    }
}
