<?php

// File generated from our OpenAPI spec

namespace Stripe\Service\Identity;

class VerificationSessionService extends \Stripe\Service\AbstractService
{
    /**
     * Returns a list of VerificationSessions.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Collection<\Stripe\Identity\VerificationSession>
     */
    public function all($params = null, $opts = null)
    {
        return $this->requestCollection('get', '/v1/identity/verification_sessions', $params, $opts);
    }

    /**
     * A VerificationSession object can be canceled when it is in
     * <code>requires_input</code> <a
     * href="/docs/identity/how-sessions-work">status</a>.
     *
     * Once canceled, future submission attempts are disabled. This cannot be undone.
     * <a href="/docs/identity/verification-sessions#cancel">Learn more</a>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Identity\VerificationSession
     */
    public function cancel($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/identity/verification_sessions/%s/cancel', $id), $params, $opts);
    }

    /**
     * Creates a VerificationSession object.
     *
     * After the VerificationSession is created, display a verification modal using the
     * session <code>client_secret</code> or send your users to the session’s
     * <code>url</code>.
     *
     * If your API key is in test mode, verification checks won’t actually process,
     * though everything else will occur as if in live mode.
     *
     * Related guide: <a href="/docs/identity/verify-identity-documents">Verify your
     * users’ identity documents</a>.
     *
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Identity\VerificationSession
     */
    public function create($params = null, $opts = null)
    {
        return $this->request('post', '/v1/identity/verification_sessions', $params, $opts);
    }

    /**
     * Redact a VerificationSession to remove all collected information from Stripe.
     * This will redact the VerificationSession and all objects related to it,
     * including VerificationReports, Events, request logs, etc.
     *
     * A VerificationSession object can be redacted when it is in
     * <code>requires_input</code> or <code>verified</code> <a
     * href="/docs/identity/how-sessions-work">status</a>. Redacting a
     * VerificationSession in <code>requires_action</code> state will automatically
     * cancel it.
     *
     * The redaction process may take up to four days. When the redaction process is in
     * progress, the VerificationSession’s <code>redaction.status</code> field will be
     * set to <code>processing</code>; when the process is finished, it will change to
     * <code>redacted</code> and an <code>identity.verification_session.redacted</code>
     * event will be emitted.
     *
     * Redaction is irreversible. Redacted objects are still accessible in the Stripe
     * API, but all the fields that contain personal data will be replaced by the
     * string <code>[redacted]</code> or a similar placeholder. The
     * <code>metadata</code> field will also be erased. Redacted objects cannot be
     * updated or used for any purpose.
     *
     * <a href="/docs/identity/verification-sessions#redact">Learn more</a>.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Identity\VerificationSession
     */
    public function redact($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/identity/verification_sessions/%s/redact', $id), $params, $opts);
    }

    /**
     * Retrieves the details of a VerificationSession that was previously created.
     *
     * When the session status is <code>requires_input</code>, you can use this method
     * to retrieve a valid <code>client_secret</code> or <code>url</code> to allow
     * re-submission.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Identity\VerificationSession
     */
    public function retrieve($id, $params = null, $opts = null)
    {
        return $this->request('get', $this->buildPath('/v1/identity/verification_sessions/%s', $id), $params, $opts);
    }

    /**
     * Updates a VerificationSession object.
     *
     * When the session status is <code>requires_input</code>, you can use this method
     * to update the verification check and options.
     *
     * @param string $id
     * @param null|array $params
     * @param null|array|\Stripe\Util\RequestOptions $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Identity\VerificationSession
     */
    public function update($id, $params = null, $opts = null)
    {
        return $this->request('post', $this->buildPath('/v1/identity/verification_sessions/%s', $id), $params, $opts);
    }
}
