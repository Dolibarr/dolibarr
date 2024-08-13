<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Uri\UriInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\ClientInterface;

class Generic extends AbstractService
{
	/**
	 * Defined scopes
	 */
	const SCOPE_READ = 'read';
	const SCOPE_WRITE = 'write';


    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);
        if ($baseApiUri === null) {
        	$url = getDolGlobalString('OAUTH_GENERIC-'.$storage->getKeyForProvider().'_URL');
        	//$url = 'https://aaaaa.com';
        	if (!empty($url)) {
				$this->baseApiUri = new Uri($url);
        	}
        }
    }

    /**
     * Return the private property $this->baseApiUri
     */
    public function getBaseApiUri()
    {
    	return $this->baseApiUri;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTokenEndpoint()
    {
    	return new Uri($this->baseApiUri.'/oauth/request');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
    	return new Uri($this->baseApiUri.'/oauth/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
    	return new Uri($this->baseApiUri.'/oauth/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUri(array $additionalParameters = array())
    {
        $parameters = array_merge(
            $additionalParameters,
            array(
                'redirect_uri' => $this->credentials->getCallbackUrl(),
            )
        );

        // Build the url
        $url = clone $this->getAuthorizationEndpoint();
        foreach ($parameters as $key => $val) {
            $url->addToQuery($key, $val);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function requestRequestToken()
    {
        $responseBody = $this->httpClient->retrieveResponse(
            $this->getRequestTokenEndpoint(),
            array(
                'consumer_key' => $this->credentials->getConsumerId(),
                'redirect_uri' => $this->credentials->getCallbackUrl(),
            )
        );

        $code = $this->parseRequestTokenResponse($responseBody);

        return $code;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseRequestTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (!isset($data['code'])) {
            throw new TokenResponseException('Error in retrieving code.');
        }
        return $data['code'];
    }

    /**
     * {@inheritdoc}
     */
    public function requestAccessToken($code, $state = null)
    {
        $bodyParams = array(
            'consumer_key'     => $this->credentials->getConsumerId(),
            'code'             => $code,
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
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        parse_str($responseBody, $data);

        if ($data === null || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        #$token->setRequestToken($data['access_token']);
        $token->setAccessToken($data['access_token']);
        $token->setEndOfLife(StdOAuth2Token::EOL_NEVER_EXPIRES);
        unset($data['access_token']);
        $token->setExtraParams($data);

        return $token;
    }
}
