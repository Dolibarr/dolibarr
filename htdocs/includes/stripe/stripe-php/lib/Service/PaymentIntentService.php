<?php

// File generated from our OpenAPI spec

namespace Stripe\Service;

class PaymentIntentService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of PaymentIntents.
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
        return $this->requestCollection('get', '/v1/payment_intents', $params, $opts);
    }

    /**
     * A PaymentIntent object can be canceled when it is in one of these statuses:
     * <code>requires_payment_method</code>, <code>requires_capture</code>,
     * <code>requires_confirmation</code>, or <code>requires_action</code>.
     *
     * Once canceled, no additional charges will be made by the PaymentIntent and any
     * operations on the PaymentIntent will fail with an error. For PaymentIntents with
     * <code>status=’requires_capture’</code>, the remaining
     * <code>amount_capturable</code> will automatically be refunded.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\PaymentIntent
     */
    public function cancel($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/payment_intents/%s/cancel', $id), $params, $opts);
    }

    /**
     * Capture the funds of an existing uncaptured PaymentIntent when its status is
     * <code>requires_capture</code>.
     *
     * Uncaptured PaymentIntents will be canceled exactly seven days after they are
     * created.
     *
     * Learn more about <a href="/docs/payments/capture-later">separate authorization
     * and capture</a>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\PaymentIntent
     */
    public function capture($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/payment_intents/%s/capture', $id), $params, $opts);
    }

    /**
     * Confirm that your customer intends to pay with current or provided payment
     * method. Upon confirmation, the PaymentIntent will attempt to initiate a payment.
     *
     * If the selected payment method requires additional authentication steps, the
     * PaymentIntent will transition to the <code>requires_action</code> status and
     * suggest additional actions via <code>next_action</code>. If payment fails, the
     * PaymentIntent will transition to the <code>requires_payment_method</code>
     * status. If payment succeeds, the PaymentIntent will transition to the
     * <code>succeeded</code> status (or <code>requires_capture</code>, if
     * <code>capture_method</code> is set to <code>manual</code>).
     *
     * If the <code>confirmation_method</code> is <code>automatic</code>, payment may
     * be attempted using our <a
     * href="/docs/stripe-js/reference#stripe-handle-card-payment">client SDKs</a> and
     * the PaymentIntent’s <a
     * href="#payment_intent_object-client_secret">client_secret</a>. After
     * <code>next_action</code>s are handled by the client, no additional confirmation
     * is required to complete the payment.
     *
     * If the <code>confirmation_method</code> is <code>manual</code>, all payment
     * attempts must be initiated using a secret key. If any actions are required for
     * the payment, the PaymentIntent will return to the
     * <code>requires_confirmation</code> state after those actions are completed. Your
     * server needs to then explicitly re-confirm the PaymentIntent to initiate the
     * next payment attempt. Read the <a
     * href="/docs/payments/payment-intents/web-manual">expanded documentation</a> to
     * learn more about manual confirmation.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\PaymentIntent
     */
    public function confirm($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/payment_intents/%s/confirm', $id), $params, $opts);
    }

    /**
     * Creates a PaymentIntent object.
     *
     * After the PaymentIntent is created, attach a payment method and <a
     * href="/docs/api/payment_intents/confirm">confirm</a> to continue the payment.
     * You can read more about the different payment flows available via the Payment
     * Intents API <a href="/docs/payments/payment-intents">here</a>.
     *
     * When <code>confirm=true</code> is used during creation, it is equivalent to
     * creating and confirming the PaymentIntent in the same call. You may use any
     * parameters available in the <a href="/docs/api/payment_intents/confirm">confirm
     * API</a> when <code>confirm=true</code> is supplied.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\PaymentIntent
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/payment_intents', $params, $opts);
    }

    /**
     * Retrieves the details of a PaymentIntent that has previously been created.
     *
     * Client-side retrieval using a publishable key is allowed when the
     * <code>client_secret</code> is provided in the query string.
     *
     * When retrieved with a publishable key, only a subset of properties will be
     * returned. Please refer to the <a href="#payment_intent_object">payment
     * intent</a> object reference for more details.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\PaymentIntent
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/payment_intents/%s', $id), $params, $opts);
    }

    /**
     * Updates properties on a PaymentIntent object without confirming.
     *
     * Depending on which properties you update, you may need to confirm the
     * PaymentIntent again. For example, updating the <code>payment_method</code> will
     * always require you to confirm the PaymentIntent again. If you prefer to update
     * and confirm at the same time, we recommend updating properties via the <a
     * href="/docs/api/payment_intents/confirm">confirm API</a> instead.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\PaymentIntent
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/payment_intents/%s', $id), $params, $opts);
    }
}
