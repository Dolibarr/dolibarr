<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class ChargeService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of charges you’ve previously created. The charges are returned in
     * sorted order, with the most recent charges appearing first.
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
        return $this->requestCollection('get', '/v1/charges', $params, $opts);
    }

    /**
     * Capture the payment of an existing, uncaptured, charge. This is the second half
     * of the two-step payment flow, where first you <a href="#create_charge">created a
     * charge</a> with the capture option set to false.
     *
     * Uncaptured payments expire exactly seven days after they are created. If they
     * are not captured by that point in time, they will be marked as refunded and will
     * no longer be capturable.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Charge
     */
    public function capture($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/charges/%s/capture', $id), $params, $opts);
    }

    /**
     * To charge a credit card or other payment source, you create a
     * <code>Charge</code> object. If your API key is in test mode, the supplied
     * payment source (e.g., card) won’t actually be charged, although everything else
     * will occur as if in live mode. (Stripe assumes that the charge would have
     * completed successfully).
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Charge
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/charges', $params, $opts);
    }

    /**
     * Retrieves the details of a charge that has previously been created. Supply the
     * unique charge ID that was returned from your previous request, and Stripe will
     * return the corresponding charge information. The same information is returned
     * when creating or refunding the charge.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Charge
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/charges/%s', $id), $params, $opts);
    }

    /**
     * Updates the specified charge by setting the values of the parameters passed. Any
     * parameters not provided will be left unchanged.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Charge
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/charges/%s', $id), $params, $opts);
    }
}
