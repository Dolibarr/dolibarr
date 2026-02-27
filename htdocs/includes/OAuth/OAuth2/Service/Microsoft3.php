<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

/**
 * Microsoft Exchange Online OAuth2 service (SMTP/IMAP)
 *
 * Uses Exchange Online OAuth2 scopes for legacy protocols (SMTP/IMAP):
 *   - offline_access (required for refresh token)
 *   - https://outlook.office.com/SMTP.Send
 *   - https://outlook.office.com/IMAP.AccessAsUser.All
 */
class Microsoft3 extends AbstractService
{
    // offline_access is resource-neutral, allowed with any resource scope
    const SCOPE_OFFLINE_ACCESS = 'offline_access';

    // Exchange Online scopes for SMTP/IMAP XOAUTH2 protocol authentication.
    // MUST NOT be mixed with Microsoft Graph scopes (openid/profile/email/User.Read)
    // in the same token request — doing so causes error AADSTS28000.
    // Azure app registration requires: Microsoft Graph > Delegated > SMTP.Send and IMAP.AccessAsUser.All
    // See: https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth
    const SCOPE_SMTP_SEND              = 'https://outlook.office.com/SMTP.Send';
    const SCOPE_IMAP_ACCESSASUSERALL   = 'https://outlook.office.com/IMAP.AccessAsUser.All';

    protected $storage;

    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        $this->storage = $storage;

        if (null === $baseApiUri) {
            // baseApiUri is not used for SMTP/IMAP auth, but keep a sensible default for potential API calls.
            $this->baseApiUri = new Uri('https://graph.microsoft.com/v1.0/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
        $tenant = $this->storage->getTenant();

        return new Uri('https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
        $tenant = $this->storage->getTenant();

        return new Uri('https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_QUERY_STRING;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        $token->setLifetime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);

        $token->setExtraParams($data);

        return $token;
    }
}
