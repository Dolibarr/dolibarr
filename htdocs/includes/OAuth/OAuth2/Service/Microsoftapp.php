<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Token\TokenInterface;

/**
 * Microsoft Exchange Online OAuth2 service, app-only mode (client credentials grant flow)
 *
 * Used for SMTP/IMAP XOAUTH2 protocol authentication with application permissions:
 * the token is requested server to server with the client id/secret of the Azure
 * application (no user interaction, no authorization code, no admin browser consent dance).
 * There is no refresh token with this flow: when the access token expires, a new one
 * is simply requested again with the same credentials (see refreshAccessToken()).
 *
 * Azure application requirements:
 *   - API permission of type "Application": Office 365 Exchange Online > IMAP.AccessAsApp
 *     (and/or POP.AccessAsApp, SMTP.SendAsApp), with admin consent granted on the tenant.
 *   - The application service principal must be registered into Exchange Online and given
 *     access to the mailboxes (New-ServicePrincipal + Add-MailboxPermission).
 * See: https://learn.microsoft.com/en-us/exchange/client-developer/legacy-protocols/how-to-authenticate-an-imap-pop-smtp-application-by-using-oauth#use-client-credentials-grant-flow-to-authenticate-imap-and-pop-connections
 */
class Microsoftapp extends AbstractService
{
    // Exchange Online resource, ".default" scope. With the client credentials grant flow, the scope
    // must always be the ".default" scope of the resource to access. For SMTP/IMAP protocol
    // authentication, the resource must be Exchange Online (a Microsoft Graph token is rejected).
    const SCOPE_EXCHANGE_DEFAULT = 'https://outlook.office365.com/.default';

    protected $storage;

    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
        // Remove empty values (when constant OAUTH_MICROSOFTAPP_SCOPE is empty, callers may
        // provide array('')) and fallback to the Exchange Online ".default" scope.
        $scopes = array_values(array_filter((array) $scopes));
        if (empty($scopes)) {
            $scopes = array(self::SCOPE_EXCHANGE_DEFAULT);
        }

        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        $this->storage = $storage;

        if (null === $baseApiUri) {
            // baseApiUri is not used for SMTP/IMAP auth, but keep a sensible default for potential API calls.
            $this->baseApiUri = new Uri('https://graph.microsoft.com/v1.0/');
        }
    }

    /**
     * {@inheritdoc}
     *
     * Not used by the client credentials grant flow (there is no interactive authorization step),
     * kept to expose a valid endpoint for the AbstractService contract.
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
        // Note: the client credentials grant flow requires a real tenant ID ('common' is not supported by Microsoft for this flow)
        $tenant = $this->storage->getTenant();

        return new Uri('https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0/token');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_BEARER;
    }

    /**
     * Accept any non empty scope. This allows national cloud variants of the ".default" scope
     * (for example https://outlook.office365.us/.default) entered manually into the setup,
     * without triggering an InvalidScopeException from the constructor.
     *
     * @param string $scope
     *
     * @return bool
     */
    public function isValidScope($scope)
    {
        return is_string($scope) && $scope !== '';
    }

    /**
     * Request a new access token to the Microsoft identity platform using the
     * client credentials grant flow (grant_type=client_credentials) and store it.
     *
     * @return TokenInterface
     *
     * @throws TokenResponseException
     */
    public function requestNewAccessToken()
    {
        $bodyParams = array(
            'client_id'     => $this->credentials->getConsumerId(),
            'client_secret' => $this->credentials->getConsumerSecret(),
            'scope'         => implode(' ', $this->scopes),
            'grant_type'    => 'client_credentials',
        );

        $responseBody = $this->httpClient->retrieveResponse(
            $this->getAccessTokenEndpoint(),
            $bodyParams,
            $this->getExtraOAuthHeaders()
        );

        $token = $this->parseAccessTokenResponse($responseBody);
        $this->storage->storeAccessToken($this->service(), $token);

        return $token;
    }

    /**
     * {@inheritdoc}
     *
     * The authorization code is not used by the client credentials grant flow, so $code is ignored.
     */
    public function requestAccessToken($code, $state = null)
    {
        return $this->requestNewAccessToken();
    }

    /**
     * {@inheritdoc}
     *
     * App-only access tokens come without a refresh token: renewing an expired token
     * simply means requesting a new one with the same application credentials.
     * This keeps callers (emailcollector, CMailFile, token manager) working unchanged
     * when the token has expired.
     */
    public function refreshAccessToken(TokenInterface $token)
    {
        return $this->requestNewAccessToken();
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
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . (isset($data['error_description']) ? ' - ' . $data['error_description'] : '') . '"');
        } elseif (empty($data['access_token'])) {
            throw new TokenResponseException('Error in retrieving token: access_token is missing from response.');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        $token->setLifetime($data['expires_in']);

        // No refresh_token is returned by the client credentials grant flow, but keep this for safety
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
