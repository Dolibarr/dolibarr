<?php

// File generated from our OpenAPI spec

namespace Stripe\Identity;

/**
 * A VerificationSession guides you through the process of collecting and verifying
 * the identities of your users. It contains details about the type of
 * verification, such as what <a
 * href="/docs/identity/verification-checks">verification check</a> to perform.
 * Only create one VerificationSession for each verification in your system.
 *
 * A VerificationSession transitions through <a
 * href="/docs/identity/how-sessions-work">multiple statuses</a> throughout its
 * lifetime as it progresses through the verification flow. The VerificationSession
 * contains the user's verified data after verification checks are complete.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/identity/verification-sessions">The Verification
 * Sessions API</a>
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|string $client_secret The short-lived client secret used by Stripe.js to <a href="https://stripe.com/docs/js/identity/modal">show a verification modal</a> inside your app. This client secret expires after 24 hours and can only be used once. Don’t store it, log it, embed it in a URL, or expose it to anyone other than the user. Make sure that you have TLS enabled on any page that includes the client secret. Refer to our docs on <a href="https://stripe.com/docs/identity/verification-sessions#client-secret">passing the client secret to the frontend</a> to learn more.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|\Stripe\StripeObject $last_error If present, this property tells you the last error encountered when processing the verification.
 * @property null|string|\Stripe\Identity\VerificationReport $last_verification_report ID of the most recent VerificationReport. <a href="https://stripe.com/docs/identity/verification-sessions#results">Learn more about accessing detailed verification results.</a>
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property \Stripe\StripeObject $metadata Set of <a href="https://stripe.com/docs/api/metadata">key-value pairs</a> that you can attach to an object. This can be useful for storing additional information about the object in a structured format.
 * @property \Stripe\StripeObject $options
 * @property null|\Stripe\StripeObject $redaction Redaction status of this VerificationSession. If the VerificationSession is not redacted, this field will be null.
 * @property string $status Status of this VerificationSession. <a href="https://stripe.com/docs/identity/how-sessions-work">Learn more about the lifecycle of sessions</a>.
 * @property string $type The type of <a href="https://stripe.com/docs/identity/verification-checks">verification check</a> to be performed.
 * @property null|string $url The short-lived URL that you use to redirect a user to Stripe to submit their identity information. This URL expires after 48 hours and can only be used once. Don’t store it, log it, send it in emails or expose it to anyone other than the user. Refer to our docs on <a href="https://stripe.com/docs/identity/verify-identity-documents?platform=web&amp;type=redirect">verifying identity documents</a> to learn how to redirect users to Stripe.
 * @property null|\Stripe\StripeObject $verified_outputs The user’s verified data.
 */
class VerificationSession extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'identity.verification_session';

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;
    use \Stripe\ApiOperations\Retrieve;
    use \Stripe\ApiOperations\Update;

    const STATUS_CANCELED = 'canceled';
    const STATUS_PROCESSING = 'processing';
    const STATUS_REQUIRES_INPUT = 'requires_input';
    const STATUS_VERIFIED = 'verified';

    const TYPE_DOCUMENT = 'document';
    const TYPE_ID_NUMBER = 'id_number';

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Identity\VerificationSession the canceled verification session
     */
    public function cancel($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/cancel';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Identity\VerificationSession the redacted verification session
     */
    public function redact($params = null, $opts = null)
    {
        $url = $this->instanceUrl() . '/redact';
        list($response, $opts) = $this->_request('post', $url, $params, $opts);
        $this->refreshFrom($response, $opts);

        return $this;
    }
}
