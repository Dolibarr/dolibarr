<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class ApplicationFeeService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of application fees you’ve previously collected. The application
     * fees are returned in sorted order, with the most recent fees appearing first.
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
        return $this->requestCollection('get', '/v1/application_fees', $params, $opts);
    }

    /**
     * You can see a list of the refunds belonging to a specific application fee. Note
     * that the 10 most recent refunds are always available by default on the
     * application fee object. If you need more than those 10, you can use this API
     * method and the <code>limit</code> and <code>starting_after</code> parameters to
     * page through additional refunds.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection
     */
    public function allRefunds($parentId, $params = null, $opts = null)
    {
        return $this->requestCollection('get', $this->buildPath('/v1/application_fees/%s/refunds', $parentId), $params, $opts);
    }

    /**
     * Refunds an application fee that has previously been collected but not yet
     * refunded. Funds will be refunded to the Stripe account from which the fee was
     * originally collected.
     *
     * You can optionally refund only part of an application fee. You can do so
     * multiple times, until the entire fee has been refunded.
     *
     * Once entirely refunded, an application fee can’t be refunded again. This method
     * will raise an error when called on an already-refunded application fee, or when
     * trying to refund more money than is left on an application fee.
     *
     * @param string $parentId
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\ApplicationFeeRefund
     */
    public function createRefund($parentId, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/application_fees/%s/refunds', $parentId), $params, $opts);
    }

    /**
     * Retrieves the details of an application fee that your account has collected. The
     * same information is returned when refunding the application fee.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\ApplicationFee
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/application_fees/%s', $id), $params, $opts);
    }

    /**
     * By default, you can see the 10 most recent refunds stored directly on the
     * application fee object, but you can also retrieve details about a specific
     * refund stored on the application fee.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\ApplicationFeeRefund
     */
    public function retrieveRefund($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/application_fees/%s/refunds/%s', $parentId, $id), $params, $opts);
    }

    /**
     * Updates the specified application fee refund by setting the values of the
     * parameters passed. Any parameters not provided will be left unchanged.
     *
     * This request only accepts metadata as an argument.
     *
     * @param string $parentId
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\ApplicationFeeRefund
     */
    public function updateRefund($parentId, $id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/application_fees/%s/refunds/%s', $parentId, $id), $params, $opts);
    }
}
