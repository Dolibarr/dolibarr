<?php

// File generated from our OpenAPI spec

namespace Stripe\Apps;

/**
 * Secret Store is an API that allows Stripe Apps developers to securely persist
 * secrets for use by UI Extensions and app backends.
 *
 * The primary resource in Secret Store is a <code>secret</code>. Other apps can't
 * view secrets created by an app. Additionally, secrets are scoped to provide
 * further permission control.
 *
 * All Dashboard users and the app backend share <code>account</code> scoped
 * secrets. Use the <code>account</code> scope for secrets that don't change
 * per-user, like a third-party API key.
 *
 * A <code>user</code> scoped secret is accessible by the app backend and one
 * specific Dashboard user. Use the <code>user</code> scope for per-user secrets
 * like per-user OAuth tokens, where different users might have different
 * permissions.
 *
 * Related guide: <a
 * href="https://stripe.com/docs/stripe-apps/store-auth-data-custom-objects">Store
 * data between page reloads</a>.
 *
 * @property string $id Unique identifier for the object.
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property int $created Time at which the object was created. Measured in seconds since the Unix epoch.
 * @property null|int $expires_at The Unix timestamp for the expiry time of the secret, after which the secret deletes.
 * @property bool $livemode Has the value <code>true</code> if the object exists in live mode or the value <code>false</code> if the object exists in test mode.
 * @property string $name A name for the secret that's unique within the scope.
 * @property null|string $payload The plaintext secret value to be stored.
 * @property \Stripe\StripeObject $scope
 */
class Secret extends \Stripe\ApiResource
{
    const OBJECT_NAME = 'apps.secret';

    use \Stripe\ApiOperations\All;
    use \Stripe\ApiOperations\Create;

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Apps\Secret the deleted secret
     */
    public static function deleteWhere($params = null, $opts = null)
    {
        $url = static::classUrl() . '/delete';
        list($response, $opts) = static::_staticRequest('post', $url, $params, $opts);
        $obj = \Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }

    /**
     * @param null|array $params
     * @param null|array|string $opts
     *
     * @throws \Stripe\Exception\ApiErrorException if the request fails
     *
     * @return \Stripe\Apps\Secret the finded secret
     */
    public static function find($params = null, $opts = null)
    {
        $url = static::classUrl() . '/find';
        list($response, $opts) = static::_staticRequest('get', $url, $params, $opts);
        $obj = \Stripe\Util\Util::convertToStripeObject($response->json, $opts);
        $obj->setLastResponse($response);

        return $obj;
    }
}
