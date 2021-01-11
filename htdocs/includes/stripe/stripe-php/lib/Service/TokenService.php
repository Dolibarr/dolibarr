<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class TokenService extends \Stripe\Service\AbstractService
{
    /**
     * Creates a single-use token that represents a bank account’s details. This token
     * can be used with any API method in place of a bank account dictionary. This
     * token can be used only once, by attaching it to a <a href="#accounts">Custom
     * account</a>.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Token
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/tokens', $params, $opts);
    }

    /**
     * Retrieves the token with the given ID.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Token
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/tokens/%s', $id), $params, $opts);
    }
}
