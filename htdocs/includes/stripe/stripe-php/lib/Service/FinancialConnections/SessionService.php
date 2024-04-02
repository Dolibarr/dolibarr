<?php

// File generated from our OpenAPI spec

namespace Stripe\Service\FinancialConnections;

class SessionService extends \Stripe\Service\AbstractService
{
    /**
     * To launch the Financial Connections authorization flow, create a
     * <code>Session</code>. The session’s <code>client_secret</code> can be used to
     * launch the flow using Stripe.js.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\FinancialConnections\Session
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/financial_connections/sessions', $params, $opts);
    }

    /**
     * Retrieves the details of a Financial Connections <code>Session</code>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\FinancialConnections\Session
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/financial_connections/sessions/%s', $id), $params, $opts);
    }
}
