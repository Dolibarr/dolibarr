<?php

namespace OAuth\OAuth2\Service;

use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\OAuth2\Service\Exception\InvalidAccessTypeException;
use OAuth\Common\Http\Uri\Uri;

/**
 * Class For WordPress OAuth
 */
class WordPress extends AbstractService
{
	/**
	 * @var string
	 */
	protected $accessType = 'online';

	/**
	 * Construct
	 *
	 * @param CredentialsInterface $credentials credentials
	 * @param ClientInterface $httpClient httpClient
	 * @param TokenStorageInterface $storage storage
	 * @param $scopes scope
	 * @param UriInterface|null $baseApiUri baseApiUri
	 * @throws Exception\InvalidScopeException
	 */
	public function __construct(CredentialsInterface $credentials, ClientInterface $httpClient, TokenStorageInterface $storage, $scopes = array(), UriInterface $baseApiUri = null)
	{
		parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri, true);

		if (null === $baseApiUri) {
			$this->baseApiUri = new Uri('https://addresse_de_votre_site_wordpress');
		}
	}
	/*
	// LDR CHANGE Add approval_prompt to force the prompt if value is set to 'force' so it force return of a "refresh token" in addition to "standard token"
	public $approvalPrompt='auto';
	public function setApprouvalPrompt($prompt)
	{
		if (!in_array($prompt, array('auto', 'force'), true)) {
			// @todo Maybe could we rename this exception
			throw new InvalidAccessTypeException('Invalid approuvalPrompt, expected either auto or force.');
		}
		$this->approvalPrompt = $prompt;
	}*/

	/**
	 * @return Uri
	 */
	public function getAuthorizationEndpoint()
	{
		return new Uri(sprintf('%s/oauth/authorize', $this->baseApiUri));
	}

	/**
	 * @return Uri
	 */
	public function getAccessTokenEndpoint()
	{
		return new Uri(sprintf('%s/oauth/token', $this->baseApiUri));
	}

	/**
	 * @return int
	 */
	protected function getAuthorizationMethod()
	{
		global $conf;
		return empty($conf->global->OAUTH_WORDPRESS_AUTHORIZATION_METHOD_QUERY_STRING) ? static::AUTHORIZATION_METHOD_HEADER_BEARER : static::AUTHORIZATION_METHOD_QUERY_STRING;
	}

	/**
	 * @param $responseBody responseBody
	 * @return StdOAuth2Token
	 * @throws TokenResponseException
	 */
	protected function parseAccessTokenResponse($responseBody)
	{
		$data = json_decode($responseBody, true);

		if (null === $data || !is_array($data)) {
			throw new TokenResponseException('Unable to parse response: "'.(isset($responseBody)?$responseBody:'NULL').'"');
		} elseif (isset($data['error'])) {
			throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '" : "'.$data['error_description'].'"');
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
