<?php

// File generated from our OpenAPI spec

namespace Stripe\Service\Issuing;

class DisputeService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of Issuing <code>Dispute</code> objects. The objects are sorted
     * in descending order by creation date, with the most recently created object
     * appearing first.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection<\Stripe\Issuing\Dispute>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/issuing/disputes', $params, $opts);
    }

    /**
     * Creates an Issuing <code>Dispute</code> object. Individual pieces of evidence
     * within the <code>evidence</code> object are optional at this point. Stripe only
     * validates that required evidence is present during submission. Refer to <a
     * href="/docs/issuing/purchases/disputes#dispute-reasons-and-evidence">Dispute
     * reasons and evidence</a> for more details about evidence requirements.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Issuing\Dispute
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/issuing/disputes', $params, $opts);
    }

    /**
     * Retrieves an Issuing <code>Dispute</code> object.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Issuing\Dispute
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/issuing/disputes/%s', $id), $params, $opts);
    }

    /**
     * Submits an Issuing <code>Dispute</code> to the card network. Stripe validates
     * that all evidence fields required for the dispute’s reason are present. For more
     * details, see <a
     * href="/docs/issuing/purchases/disputes#dispute-reasons-and-evidence">Dispute
     * reasons and evidence</a>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Issuing\Dispute
     */
    public function submit($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/issuing/disputes/%s/submit', $id), $params, $opts);
    }

    /**
     * Updates the specified Issuing <code>Dispute</code> object by setting the values
     * of the parameters passed. Any parameters not provided will be left unchanged.
     * Properties on the <code>evidence</code> object can be unset by passing in an
     * empty string.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Issuing\Dispute
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/issuing/disputes/%s', $id), $params, $opts);
    }
}
