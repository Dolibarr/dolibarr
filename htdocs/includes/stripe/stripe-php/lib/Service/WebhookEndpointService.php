<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class WebhookEndpointService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of your webhook endpoints.
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
        return $this->requestCollection('get', '/v1/webhook_endpoints', $params, $opts);
    }

    /**
     * A webhook endpoint must have a <code>url</code> and a list of
     * <code>enabled_events</code>. You may optionally specify the Boolean
     * <code>connect</code> parameter. If set to true, then a Connect webhook endpoint
     * that notifies the specified <code>url</code> about events from all connected
     * accounts is created; otherwise an account webhook endpoint that notifies the
     * specified <code>url</code> only about events from your account is created. You
     * can also create webhook endpoints in the <a
     * href="https://dashboard.stripe.com/account/webhooks">webhooks settings</a>
     * section of the Dashboard.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\WebhookEndpoint
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/webhook_endpoints', $params, $opts);
    }

    /**
     * You can also delete webhook endpoints via the <a
     * href="https://dashboard.stripe.com/account/webhooks">webhook endpoint
     * management</a> page of the Stripe dashboard.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\WebhookEndpoint
     */
    public function delete($id, $params = null, $opts = null)
    {
        return $this->request('delete', $this->buildPath('/v1/webhook_endpoints/%s', $id), $params, $opts);
    }

    /**
     * Retrieves the webhook endpoint with the given ID.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\WebhookEndpoint
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/webhook_endpoints/%s', $id), $params, $opts);
    }

    /**
     * Updates the webhook endpoint. You may edit the <code>url</code>, the list of
     * <code>enabled_events</code>, and the status of your endpoint.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\WebhookEndpoint
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/webhook_endpoints/%s', $id), $params, $opts);
    }
}
