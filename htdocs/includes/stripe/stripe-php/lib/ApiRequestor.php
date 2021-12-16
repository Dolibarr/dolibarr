<?php

namespace Stripe;

/**
 * Class ApiRequestor.
 */
class ApiRequestor
{
    /**
     * @var null|string
     */
    private $_apiKey;

    /**
     * @var string
     */
    private $_apiBase;

    /**
     * @var HttpClient\ClientInterface
     */
    private static $_httpClient;

    /**
     * @var RequestTelemetry
     */
    private static $requestTelemetry;

    private static $OPTIONS_KEYS = ['api_key', 'idempotency_key', 'stripe_account', 'stripe_version', 'api_base'];

    /**
     * ApiRequestor constructor.
     *
     * @param null|string $apiKey
     * @param null|string $apiBase
     */
    public function __construct($apiKey = null, $apiBase = null)
    {
        $this->_apiKey = $apiKey;
        if (!$apiBase) {
            $apiBase = Stripe::$apiBase;
        }
        $this->_apiBase = $apiBase;
    }

    /**
     * Creates a telemetry json blob for use in 'X-Stripe-Client-Telemetry' headers.
     *
     * @static
     *
     * @param RequestTelemetry $requestTelemetry
     *
     * @return string
     */
    private static function _telemetryJson($requestTelemetry)
    {
        $payload = [
            'last_request_metrics' => [
                'request_id' => $requestTelemetry->requestId,
                'request_duration_ms' => $requestTelemetry->requestDuration,
            ],
        ];

        $result = \json_encode($payload);
        if (false !== $result) {
            return $result;
        }
        Stripe::getLogger()->error('Serializing telemetry payload failed!');

        return '{}';
    }

    /**
     * @static
     *
     * @param ApiResource|array|bool|mixed $d
     *
     * @return ApiResource|array|mixed|string
     */
    private static function _encodeObjects($d)
    {
        if ($d instanceof ApiResource) {
            return Util\Util::utf8($d->id);
        }
        if (true === $d) {
            return 'true';
        }
        if (false === $d) {
            return 'false';
        }
        if (\is_array($d)) {
            $res = [];
            foreach ($d as $k => $v) {
                $res[$k] = self::_encodeObjects($v);
            }

            return $res;
        }

        return Util\Util::utf8($d);
    }

    /**
     * @param string     $method
     * @param string     $url
     * @param null|array $params
     * @param null|array $headers
     *
     * @throws Exception\ApiErrorException
     *
     * @return array tuple containing (ApiReponse, API key)
     */
    public function request($method, $url, $params = null, $headers = null)
    {
        $params = $params ?: [];
        $headers = $headers ?: [];
        list($rbody, $rcode, $rheaders, $myApiKey) =
        $this->_requestRaw($method, $url, $params, $headers);
        $json = $this->_interpretResponse($rbody, $rcode, $rheaders);
        $resp = new ApiResponse($rbody, $rcode, $rheaders, $json);

        return [$resp, $myApiKey];
    }

    /**
     * @param string $rbody a JSON string
     * @param int $rcode
     * @param array $rheaders
     * @param array $resp
     *
     * @throws Exception\UnexpectedValueException
     * @throws Exception\ApiErrorException
     */
    public function handleErrorResponse($rbody, $rcode, $rheaders, $resp)
    {
        if (!\is_array($resp) || !isset($resp['error'])) {
            $msg = "Invalid response object from API: {$rbody} "
              . "(HTTP response code was {$rcode})";

            throw new Exception\UnexpectedValueException($msg);
        }

        $errorData = $resp['error'];

        $error = null;
        if (\is_string($errorData)) {
            $error = self::_specificOAuthError($rbody, $rcode, $rheaders, $resp, $errorData);
        }
        if (!$error) {
            $error = self::_specificAPIError($rbody, $rcode, $rheaders, $resp, $errorData);
        }

        throw $error;
    }

    /**
     * @static
     *
     * @param string $rbody
     * @param int    $rcode
     * @param array  $rheaders
     * @param array  $resp
     * @param array  $errorData
     *
     * @return Exception\ApiErrorException
     */
    private static function _specificAPIError($rbody, $rcode, $rheaders, $resp, $errorData)
    {
        $msg = isset($errorData['message']) ? $errorData['message'] : null;
        $param = isset($errorData['param']) ? $errorData['param'] : null;
        $code = isset($errorData['code']) ? $errorData['code'] : null;
        $type = isset($errorData['type']) ? $errorData['type'] : null;
        $declineCode = isset($errorData['decline_code']) ? $errorData['decline_code'] : null;

        switch ($rcode) {
            case 400:
                // 'rate_limit' code is deprecated, but left here for backwards compatibility
                // for API versions earlier than 2015-09-08
                if ('rate_limit' === $code) {
                    return Exception\RateLimitException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code, $param);
                }
                if ('idempotency_error' === $type) {
                    return Exception\IdempotencyException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code);
                }

                // no break
            case 404:
                return Exception\InvalidRequestException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code, $param);

            case 401:
                return Exception\AuthenticationException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code);

            case 402:
                return Exception\CardException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code, $declineCode, $param);

            case 403:
                return Exception\PermissionException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code);

            case 429:
                return Exception\RateLimitException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code, $param);

            default:
                return Exception\UnknownApiErrorException::factory($msg, $rcode, $rbody, $resp, $rheaders, $code);
        }
    }

    /**
     * @static
     *
     * @param bool|string $rbody
     * @param int         $rcode
     * @param array       $rheaders
     * @param array       $resp
     * @param string      $errorCode
     *
     * @return Exception\OAuth\OAuthErrorException
     */
    private static function _specificOAuthError($rbody, $rcode, $rheaders, $resp, $errorCode)
    {
        $description = isset($resp['error_description']) ? $resp['error_description'] : $errorCode;

        switch ($errorCode) {
            case 'invalid_client':
                return Exception\OAuth\InvalidClientException::factory($description, $rcode, $rbody, $resp, $rheaders, $errorCode);

            case 'invalid_grant':
                return Exception\OAuth\InvalidGrantException::factory($description, $rcode, $rbody, $resp, $rheaders, $errorCode);

            case 'invalid_request':
                return Exception\OAuth\InvalidRequestException::factory($description, $rcode, $rbody, $resp, $rheaders, $errorCode);

            case 'invalid_scope':
                return Exception\OAuth\InvalidScopeException::factory($description, $rcode, $rbody, $resp, $rheaders, $errorCode);

            case 'unsupported_grant_type':
                return Exception\OAuth\UnsupportedGrantTypeException::factory($description, $rcode, $rbody, $resp, $rheaders, $errorCode);

            case 'unsupported_response_type':
                return Exception\OAuth\UnsupportedResponseTypeException::factory($description, $rcode, $rbody, $resp, $rheaders, $errorCode);

            default:
                return Exception\OAuth\UnknownOAuthErrorException::factory($description, $rcode, $rbody, $resp, $rheaders, $errorCode);
        }
    }

    /**
     * @static
     *
     * @param null|array $appInfo
     *
     * @return null|string
     */
    private static function _formatAppInfo($appInfo)
    {
        if (null !== $appInfo) {
            $string = $appInfo['name'];
            if (null !== $appInfo['version']) {
                $string .= '/' . $appInfo['version'];
            }
            if (null !== $appInfo['url']) {
                $string .= ' (' . $appInfo['url'] . ')';
            }

            return $string;
        }

        return null;
    }

    /**
     * @static
     *
     * @param string $disabledFunctionsOutput - String value of the 'disable_function' setting, as output by \ini_get('disable_functions')
     * @param string $functionName - Name of the function we are interesting in seeing whether or not it is disabled
     * @param mixed $disableFunctionsOutput
     *
     * @return bool
     */
    private static function _isDisabled($disableFunctionsOutput, $functionName)
    {
        $disabledFunctions = \explode(',', $disableFunctionsOutput);
        foreach ($disabledFunctions as $disabledFunction) {
            if (\trim($disabledFunction) === $functionName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @static
     *
     * @param string $apiKey
     * @param null   $clientInfo
     *
     * @return array
     */
    private static function _defaultHeaders($apiKey, $clientInfo = null)
    {
        $uaString = 'Stripe/v1 PhpBindings/' . Stripe::VERSION;

        $langVersion = \PHP_VERSION;
        $uname_disabled = static::_isDisabled(\ini_get('disable_functions'), 'php_uname');
        $uname = $uname_disabled ? '(disabled)' : \php_uname();

        $appInfo = Stripe::getAppInfo();
        $ua = [
            'bindings_version' => Stripe::VERSION,
            'lang' => 'php',
            'lang_version' => $langVersion,
            'publisher' => 'stripe',
            'uname' => $uname,
        ];
        if ($clientInfo) {
            $ua = \array_merge($clientInfo, $ua);
        }
        if (null !== $appInfo) {
            $uaString .= ' ' . self::_formatAppInfo($appInfo);
            $ua['application'] = $appInfo;
        }

        return [
            'X-Stripe-Client-User-Agent' => \json_encode($ua),
            'User-Agent' => $uaString,
            'Authorization' => 'Bearer ' . $apiKey,
        ];
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     *
     * @throws Exception\AuthenticationException
     * @throws Exception\ApiConnectionException
     *
     * @return array
     */
    private function _requestRaw($method, $url, $params, $headers)
    {
        $myApiKey = $this->_apiKey;
        if (!$myApiKey) {
            $myApiKey = Stripe::$apiKey;
        }

        if (!$myApiKey) {
            $msg = 'No API key provided.  (HINT: set your API key using '
              . '"Stripe::setApiKey(<API-KEY>)".  You can generate API keys from '
              . 'the Stripe web interface.  See https://stripe.com/api for '
              . 'details, or email support@stripe.com if you have any questions.';

            throw new Exception\AuthenticationException($msg);
        }

        // Clients can supply arbitrary additional keys to be included in the
        // X-Stripe-Client-User-Agent header via the optional getUserAgentInfo()
        // method
        $clientUAInfo = null;
        if (\method_exists($this->httpClient(), 'getUserAgentInfo')) {
            $clientUAInfo = $this->httpClient()->getUserAgentInfo();
        }

        if ($params && \is_array($params)) {
            $optionKeysInParams = \array_filter(
                static::$OPTIONS_KEYS,
                function ($key) use ($params) {
                    return \array_key_exists($key, $params);
                }
            );
            if (\count($optionKeysInParams) > 0) {
                $message = \sprintf('Options found in $params: %s. Options should '
                  . 'be passed in their own array after $params. (HINT: pass an '
                  . 'empty array to $params if you do not have any.)', \implode(', ', $optionKeysInParams));
                \trigger_error($message, \E_USER_WARNING);
            }
        }

        $absUrl = $this->_apiBase . $url;
        $params = self::_encodeObjects($params);
        $defaultHeaders = $this->_defaultHeaders($myApiKey, $clientUAInfo);
        if (Stripe::$apiVersion) {
            $defaultHeaders['Stripe-Version'] = Stripe::$apiVersion;
        }

        if (Stripe::$accountId) {
            $defaultHeaders['Stripe-Account'] = Stripe::$accountId;
        }

        if (Stripe::$enableTelemetry && null !== self::$requestTelemetry) {
            $defaultHeaders['X-Stripe-Client-Telemetry'] = self::_telemetryJson(self::$requestTelemetry);
        }

        $hasFile = false;
        foreach ($params as $k => $v) {
            if (\is_resource($v)) {
                $hasFile = true;
                $params[$k] = self::_processResourceParam($v);
            } elseif ($v instanceof \CURLFile) {
                $hasFile = true;
            }
        }

        if ($hasFile) {
            $defaultHeaders['Content-Type'] = 'multipart/form-data';
        } else {
            $defaultHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
        }

        $combinedHeaders = \array_merge($defaultHeaders, $headers);
        $rawHeaders = [];

        foreach ($combinedHeaders as $header => $value) {
            $rawHeaders[] = $header . ': ' . $value;
        }

        $requestStartMs = Util\Util::currentTimeMillis();

        list($rbody, $rcode, $rheaders) = $this->httpClient()->request(
            $method,
            $absUrl,
            $rawHeaders,
            $params,
            $hasFile
        );

        if (isset($rheaders['request-id'])
        && \is_string($rheaders['request-id'])
        && \strlen($rheaders['request-id']) > 0) {
            self::$requestTelemetry = new RequestTelemetry(
                $rheaders['request-id'],
                Util\Util::currentTimeMillis() - $requestStartMs
            );
        }

        return [$rbody, $rcode, $rheaders, $myApiKey];
    }

    /**
     * @param resource $resource
     *
     * @throws Exception\InvalidArgumentException
     *
     * @return \CURLFile|string
     */
    private function _processResourceParam($resource)
    {
        if ('stream' !== \get_resource_type($resource)) {
            throw new Exception\InvalidArgumentException(
                'Attempted to upload a resource that is not a stream'
            );
        }

        $metaData = \stream_get_meta_data($resource);
        if ('plainfile' !== $metaData['wrapper_type']) {
            throw new Exception\InvalidArgumentException(
                'Only plainfile resource streams are supported'
            );
        }

        // We don't have the filename or mimetype, but the API doesn't care
        return new \CURLFile($metaData['uri']);
    }

    /**
     * @param string $rbody
     * @param int    $rcode
     * @param array  $rheaders
     *
     * @throws Exception\UnexpectedValueException
     * @throws Exception\ApiErrorException
     *
     * @return array
     */
    private function _interpretResponse($rbody, $rcode, $rheaders)
    {
        $resp = \json_decode($rbody, true);
        $jsonError = \json_last_error();
        if (null === $resp && \JSON_ERROR_NONE !== $jsonError) {
            $msg = "Invalid response body from API: {$rbody} "
              . "(HTTP response code was {$rcode}, json_last_error() was {$jsonError})";

            throw new Exception\UnexpectedValueException($msg, $rcode);
        }

        if ($rcode < 200 || $rcode >= 300) {
            $this->handleErrorResponse($rbody, $rcode, $rheaders, $resp);
        }

        return $resp;
    }

    /**
     * @static
     *
     * @param HttpClient\ClientInterface $client
     */
    public static function setHttpClient($client)
    {
        self::$_httpClient = $client;
    }

    /**
     * @static
     *
     * Resets any stateful telemetry data
     */
    public static function resetTelemetry()
    {
        self::$requestTelemetry = null;
    }

    /**
     * @return HttpClient\ClientInterface
     */
    private function httpClient()
    {
        if (!self::$_httpClient) {
            self::$_httpClient = HttpClient\CurlClient::instance();
        }

        return self::$_httpClient;
    }
}
