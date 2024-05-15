<?php

namespace Stripe\HttpClient;

use Stripe\Exception;
use Stripe\Stripe;
use Stripe\Util;

// @codingStandardsIgnoreStart
// PSR2 requires all constants be upper case. Sadly, the CURL_SSLVERSION
// constants do not abide by those rules.

// Note the values come from their position in the enums that
// defines them in cURL's source code.

// Available since PHP 5.5.19 and 5.6.3
if (!\defined('CURL_SSLVERSION_TLSv1_2')) {
    \define('CURL_SSLVERSION_TLSv1_2', 6);
}
// @codingStandardsIgnoreEnd

// Available since PHP 7.0.7 and cURL 7.47.0
if (!\defined('CURL_HTTP_VERSION_2TLS')) {
    \define('CURL_HTTP_VERSION_2TLS', 4);
}

class CurlClient implements ClientInterface, StreamingClientInterface
{
    protected static $instance;

    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    protected $defaultOptions;

    /** @var \Stripe\Util\RandomGenerator */
    protected $randomGenerator;

    protected $userAgentInfo;

    protected $enablePersistentConnections = true;

    protected $enableHttp2;

    protected $curlHandle;

    protected $requestStatusCallback;

    /**
     * CurlClient constructor.
     *
     * Pass in a callable to $defaultOptions that returns an array of CURLOPT_* values to start
     * off a request with, or an flat array with the same format used by curl_setopt_array() to
     * provide a static set of options. Note that many options are overridden later in the request
     * call, including timeouts, which can be set via setTimeout() and setConnectTimeout().
     *
     * Note that request() will silently ignore a non-callable, non-array $defaultOptions, and will
     * throw an exception if $defaultOptions returns a non-array value.
     *
     * @param null|array|callable $defaultOptions
     * @param null|\Stripe\Util\RandomGenerator $randomGenerator
     */
    public function __construct($defaultOptions = null, $randomGenerator = null)
    {
        $this->defaultOptions = $defaultOptions;
        $this->randomGenerator = $randomGenerator ?: new Util\RandomGenerator();
        $this->initUserAgentInfo();

        $this->enableHttp2 = $this->canSafelyUseHttp2();
    }

    public function __destruct()
    {
        $this->closeCurlHandle();
    }

    public function initUserAgentInfo()
    {
        $curlVersion = \curl_version();
        $this->userAgentInfo = [
            'httplib' => 'curl ' . $curlVersion['version'],
            'ssllib' => $curlVersion['ssl_version'],
        ];
    }

    public function getDefaultOptions()
    {
        return $this->defaultOptions;
    }

    public function getUserAgentInfo()
    {
        return $this->userAgentInfo;
    }

    /**
     * @return bool
     */
    public function getEnablePersistentConnections()
    {
        return $this->enablePersistentConnections;
    }

    /**
     * @param bool $enable
     */
    public function setEnablePersistentConnections($enable)
    {
        $this->enablePersistentConnections = $enable;
    }

    /**
     * @return bool
     */
    public function getEnableHttp2()
    {
        return $this->enableHttp2;
    }

    /**
     * @param bool $enable
     */
    public function setEnableHttp2($enable)
    {
        $this->enableHttp2 = $enable;
    }

    /**
     * @return null|callable
     */
    public function getRequestStatusCallback()
    {
        return $this->requestStatusCallback;
    }

    /**
     * Sets a callback that is called after each request. The callback will
     * receive the following parameters:
     * <ol>
     *   <li>string $rbody The response body</li>
     *   <li>integer $rcode The response status code</li>
     *   <li>\Stripe\Util\CaseInsensitiveArray $rheaders The response headers</li>
     *   <li>integer $errno The curl error number</li>
     *   <li>string|null $message The curl error message</li>
     *   <li>boolean $shouldRetry Whether the request will be retried</li>
     *   <li>integer $numRetries The number of the retry attempt</li>
     * </ol>.
     *
     * @param null|callable $requestStatusCallback
     */
    public function setRequestStatusCallback($requestStatusCallback)
    {
        $this->requestStatusCallback = $requestStatusCallback;
    }

    // USER DEFINED TIMEOUTS

    const DEFAULT_TIMEOUT = 80;
    const DEFAULT_CONNECT_TIMEOUT = 30;

    private $timeout = self::DEFAULT_TIMEOUT;
    private $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;

    public function setTimeout($seconds)
    {
        $this->timeout = (int) \max($seconds, 0);

        return $this;
    }

    public function setConnectTimeout($seconds)
    {
        $this->connectTimeout = (int) \max($seconds, 0);

        return $this;
    }

    public function getTimeout()
    {
        return $this->timeout;
    }

    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    // END OF USER DEFINED TIMEOUTS

    private function constructRequest($method, $absUrl, $headers, $params, $hasFile)
    {
        $method = \strtolower($method);

        $opts = [];
        if (\is_callable($this->defaultOptions)) { // call defaultOptions callback, set options to return value
            $opts = \call_user_func_array($this->defaultOptions, \func_get_args());
            if (!\is_array($opts)) {
                throw new Exception\UnexpectedValueException('Non-array value returned by defaultOptions CurlClient callback');
            }
        } elseif (\is_array($this->defaultOptions)) { // set default curlopts from array
            $opts = $this->defaultOptions;
        }

        $params = Util\Util::objectsToIds($params);

        if ('get' === $method) {
            if ($hasFile) {
                throw new Exception\UnexpectedValueException(
                    'Issuing a GET request with a file parameter'
                );
            }
            $opts[\CURLOPT_HTTPGET] = 1;
            if (\count($params) > 0) {
                $encoded = Util\Util::encodeParameters($params);
                $absUrl = "{$absUrl}?{$encoded}";
            }
        } elseif ('post' === $method) {
            $opts[\CURLOPT_POST] = 1;
            $opts[\CURLOPT_POSTFIELDS] = $hasFile ? $params : Util\Util::encodeParameters($params);
        } elseif ('delete' === $method) {
            $opts[\CURLOPT_CUSTOMREQUEST] = 'DELETE';
            if (\count($params) > 0) {
                $encoded = Util\Util::encodeParameters($params);
                $absUrl = "{$absUrl}?{$encoded}";
            }
        } else {
            throw new Exception\UnexpectedValueException("Unrecognized method {$method}");
        }

        // It is only safe to retry network failures on POST requests if we
        // add an Idempotency-Key header
        if (('post' === $method) && (Stripe::$maxNetworkRetries > 0)) {
            if (!$this->hasHeader($headers, 'Idempotency-Key')) {
                $headers[] = 'Idempotency-Key: ' . $this->randomGenerator->uuid();
            }
        }

        // By default for large request body sizes (> 1024 bytes), cURL will
        // send a request without a body and with a `Expect: 100-continue`
        // header, which gives the server a chance to respond with an error
        // status code in cases where one can be determined right away (say
        // on an authentication problem for example), and saves the "large"
        // request body from being ever sent.
        //
        // Unfortunately, the bindings don't currently correctly handle the
        // success case (in which the server sends back a 100 CONTINUE), so
        // we'll error under that condition. To compensate for that problem
        // for the time being, override cURL's behavior by simply always
        // sending an empty `Expect:` header.
        $headers[] = 'Expect: ';

        $absUrl = Util\Util::utf8($absUrl);
        $opts[\CURLOPT_URL] = $absUrl;
        $opts[\CURLOPT_RETURNTRANSFER] = true;
        $opts[\CURLOPT_CONNECTTIMEOUT] = $this->connectTimeout;
        $opts[\CURLOPT_TIMEOUT] = $this->timeout;
        $opts[\CURLOPT_HTTPHEADER] = $headers;
        $opts[\CURLOPT_CAINFO] = Stripe::getCABundlePath();
        if (!Stripe::getVerifySslCerts()) {
            $opts[\CURLOPT_SSL_VERIFYPEER] = false;
        }

        if (!isset($opts[\CURLOPT_HTTP_VERSION]) && $this->getEnableHttp2()) {
            // For HTTPS requests, enable HTTP/2, if supported
            $opts[\CURLOPT_HTTP_VERSION] = \CURL_HTTP_VERSION_2TLS;
        }

        // If the user didn't explicitly specify a CURLOPT_IPRESOLVE option, we
        // force IPv4 resolving as Stripe's API servers are only accessible over
        // IPv4 (see. https://github.com/stripe/stripe-php/issues/1045).
        // We let users specify a custom option in case they need to say proxy
        // through an IPv6 proxy.
        if (!isset($opts[\CURLOPT_IPRESOLVE])) {
            $opts[\CURLOPT_IPRESOLVE] = \CURL_IPRESOLVE_V4;
        }

        return [$opts, $absUrl];
    }

    public function request($method, $absUrl, $headers, $params, $hasFile)
    {
        list($opts, $absUrl) = $this->constructRequest($method, $absUrl, $headers, $params, $hasFile);

        list($rbody, $rcode, $rheaders) = $this->executeRequestWithRetries($opts, $absUrl);

        return [$rbody, $rcode, $rheaders];
    }

    public function requestStream($method, $absUrl, $headers, $params, $hasFile, $readBodyChunk)
    {
        list($opts, $absUrl) = $this->constructRequest($method, $absUrl, $headers, $params, $hasFile);

        $opts[\CURLOPT_RETURNTRANSFER] = false;
        list($rbody, $rcode, $rheaders) = $this->executeStreamingRequestWithRetries($opts, $absUrl, $readBodyChunk);

        return [$rbody, $rcode, $rheaders];
    }

    /**
     * Curl permits sending \CURLOPT_HEADERFUNCTION, which is called with lines
     * from the header and \CURLOPT_WRITEFUNCTION, which is called with bytes
     * from the body. You usually want to handle the body differently depending
     * on what was in the header.
     *
     * This function makes it easier to specify different callbacks depending
     * on the contents of the heeder. After the header has been completely read
     * and the body begins to stream, it will call $determineWriteCallback with
     * the array of headers. $determineWriteCallback should, based on the
     * headers it receives, return a "writeCallback" that describes what to do
     * with the incoming HTTP response body.
     *
     * @param array $opts
     * @param callable $determineWriteCallback
     *
     * @return array
     */
    private function useHeadersToDetermineWriteCallback($opts, $determineWriteCallback)
    {
        $rheaders = new Util\CaseInsensitiveArray();
        $headerCallback = function ($curl, $header_line) use (&$rheaders) {
            return self::parseLineIntoHeaderArray($header_line, $rheaders);
        };

        $writeCallback = null;
        $writeCallbackWrapper = function ($curl, $data) use (&$writeCallback, &$rheaders, &$determineWriteCallback) {
            if (null === $writeCallback) {
                $writeCallback = \call_user_func_array($determineWriteCallback, [$rheaders]);
            }

            return \call_user_func_array($writeCallback, [$curl, $data]);
        };

        return [$headerCallback, $writeCallbackWrapper];
    }

    private static function parseLineIntoHeaderArray($line, &$headers)
    {
        if (false === \strpos($line, ':')) {
            return \strlen($line);
        }
        list($key, $value) = \explode(':', \trim($line), 2);
        $headers[\trim($key)] = \trim($value);

        return \strlen($line);
    }

    /**
     * Like `executeRequestWithRetries` except:
     *   1. Does not buffer the body of a successful (status code < 300)
     *      response into memory -- instead, calls the caller-provided
     *      $readBodyChunk with each chunk of incoming data.
     *   2. Does not retry if a network error occurs while streaming the
     *      body of a successful response.
     *
     * @param array $opts cURL options
     * @param string $absUrl
     * @param callable $readBodyChunk
     *
     * @return array
     */
    public function executeStreamingRequestWithRetries($opts, $absUrl, $readBodyChunk)
    {
        /** @var bool */
        $shouldRetry = false;
        /** @var int */
        $numRetries = 0;

        // Will contain the bytes of the body of the last request
        // if it was not successful and should not be retries
        /** @var null|string */
        $rbody = null;

        // Status code of the last request
        /** @var null|bool */
        $rcode = null;

        // Array of headers from the last request
        /** @var null|array */
        $lastRHeaders = null;

        $errno = null;
        $message = null;

        $determineWriteCallback = function ($rheaders) use (
            &$readBodyChunk,
            &$shouldRetry,
            &$rbody,
            &$numRetries,
            &$rcode,
            &$lastRHeaders,
            &$errno
        ) {
            $lastRHeaders = $rheaders;
            $errno = \curl_errno($this->curlHandle);

            $rcode = \curl_getinfo($this->curlHandle, \CURLINFO_HTTP_CODE);

            // Send the bytes from the body of a successful request to the caller-provided $readBodyChunk.
            if ($rcode < 300) {
                $rbody = null;

                return function ($curl, $data) use (&$readBodyChunk) {
                    // Don't expose the $curl handle to the user, and don't require them to
                    // return the length of $data.
                    \call_user_func_array($readBodyChunk, [$data]);

                    return \strlen($data);
                };
            }

            $shouldRetry = $this->shouldRetry($errno, $rcode, $rheaders, $numRetries);

            // Discard the body from an unsuccessful request that should be retried.
            if ($shouldRetry) {
                return function ($curl, $data) {
                    return \strlen($data);
                };
            } else {
                // Otherwise, buffer the body into $rbody. It will need to be parsed to determine
                // which exception to throw to the user.
                $rbody = '';

                return function ($curl, $data) use (&$rbody) {
                    $rbody .= $data;

                    return \strlen($data);
                };
            }
        };

        while (true) {
            list($headerCallback, $writeCallback) = $this->useHeadersToDetermineWriteCallback($opts, $determineWriteCallback);
            $opts[\CURLOPT_HEADERFUNCTION] = $headerCallback;
            $opts[\CURLOPT_WRITEFUNCTION] = $writeCallback;

            $shouldRetry = false;
            $rbody = null;
            $this->resetCurlHandle();
            \curl_setopt_array($this->curlHandle, $opts);
            $result = \curl_exec($this->curlHandle);
            $errno = \curl_errno($this->curlHandle);
            if (0 !== $errno) {
                $message = \curl_error($this->curlHandle);
            }
            if (!$this->getEnablePersistentConnections()) {
                $this->closeCurlHandle();
            }

            if (\is_callable($this->getRequestStatusCallback())) {
                \call_user_func_array(
                    $this->getRequestStatusCallback(),
                    [$rbody, $rcode, $lastRHeaders, $errno, $message, $shouldRetry, $numRetries]
                );
            }

            if ($shouldRetry) {
                ++$numRetries;
                $sleepSeconds = $this->sleepTime($numRetries, $lastRHeaders);
                \usleep((int) ($sleepSeconds * 1000000));
            } else {
                break;
            }
        }

        if (0 !== $errno) {
            $this->handleCurlError($absUrl, $errno, $message, $numRetries);
        }

        return [$rbody, $rcode, $lastRHeaders];
    }

    /**
     * @param array $opts cURL options
     * @param string $absUrl
     */
    public function executeRequestWithRetries($opts, $absUrl)
    {
        $numRetries = 0;

        while (true) {
            $rcode = 0;
            $errno = 0;
            $message = null;

            // Create a callback to capture HTTP headers for the response
            $rheaders = new Util\CaseInsensitiveArray();
            $headerCallback = function ($curl, $header_line) use (&$rheaders) {
                return CurlClient::parseLineIntoHeaderArray($header_line, $rheaders);
            };
            $opts[\CURLOPT_HEADERFUNCTION] = $headerCallback;

            $this->resetCurlHandle();
            \curl_setopt_array($this->curlHandle, $opts);
            $rbody = \curl_exec($this->curlHandle);

            if (false === $rbody) {
                $errno = \curl_errno($this->curlHandle);
                $message = \curl_error($this->curlHandle);
            } else {
                $rcode = \curl_getinfo($this->curlHandle, \CURLINFO_HTTP_CODE);
            }
            if (!$this->getEnablePersistentConnections()) {
                $this->closeCurlHandle();
            }

            $shouldRetry = $this->shouldRetry($errno, $rcode, $rheaders, $numRetries);

            if (\is_callable($this->getRequestStatusCallback())) {
                \call_user_func_array(
                    $this->getRequestStatusCallback(),
                    [$rbody, $rcode, $rheaders, $errno, $message, $shouldRetry, $numRetries]
                );
            }

            if ($shouldRetry) {
                ++$numRetries;
                $sleepSeconds = $this->sleepTime($numRetries, $rheaders);
                \usleep((int) ($sleepSeconds * 1000000));
            } else {
                break;
            }
        }

        if (false === $rbody) {
            $this->handleCurlError($absUrl, $errno, $message, $numRetries);
        }

        return [$rbody, $rcode, $rheaders];
    }

    /**
     * @param string $url
     * @param int $errno
     * @param string $message
     * @param int $numRetries
     *
     * @throws Exception\ApiConnectionException
     */
    private function handleCurlError($url, $errno, $message, $numRetries)
    {
        switch ($errno) {
            case \CURLE_COULDNT_CONNECT:
            case \CURLE_COULDNT_RESOLVE_HOST:
            case \CURLE_OPERATION_TIMEOUTED:
                $msg = "Could not connect to Stripe ({$url}).  Please check your "
                 . 'internet connection and try again.  If this problem persists, '
                 . "you should check Stripe's service status at "
                 . 'https://twitter.com/stripestatus, or';

                break;

            case \CURLE_SSL_CACERT:
            case \CURLE_SSL_PEER_CERTIFICATE:
                $msg = "Could not verify Stripe's SSL certificate.  Please make sure "
                 . 'that your network is not intercepting certificates.  '
                 . "(Try going to {$url} in your browser.)  "
                 . 'If this problem persists,';

                break;

            default:
                $msg = 'Unexpected error communicating with Stripe.  '
                 . 'If this problem persists,';
        }
        $msg .= ' let us know at support@stripe.com.';

        $msg .= "\n\n(Network error [errno {$errno}]: {$message})";

        if ($numRetries > 0) {
            $msg .= "\n\nRequest was retried {$numRetries} times.";
        }

        throw new Exception\ApiConnectionException($msg);
    }

    /**
     * Checks if an error is a problem that we should retry on. This includes both
     * socket errors that may represent an intermittent problem and some special
     * HTTP statuses.
     *
     * @param int $errno
     * @param int $rcode
     * @param array|\Stripe\Util\CaseInsensitiveArray $rheaders
     * @param int $numRetries
     *
     * @return bool
     */
    private function shouldRetry($errno, $rcode, $rheaders, $numRetries)
    {
        if ($numRetries >= Stripe::getMaxNetworkRetries()) {
            return false;
        }

        // Retry on timeout-related problems (either on open or read).
        if (\CURLE_OPERATION_TIMEOUTED === $errno) {
            return true;
        }

        // Destination refused the connection, the connection was reset, or a
        // variety of other connection failures. This could occur from a single
        // saturated server, so retry in case it's intermittent.
        if (\CURLE_COULDNT_CONNECT === $errno) {
            return true;
        }

        // The API may ask us not to retry (eg; if doing so would be a no-op)
        // or advise us to retry (eg; in cases of lock timeouts); we defer to that.
        if (isset($rheaders['stripe-should-retry'])) {
            if ('false' === $rheaders['stripe-should-retry']) {
                return false;
            }
            if ('true' === $rheaders['stripe-should-retry']) {
                return true;
            }
        }

        // 409 Conflict
        if (409 === $rcode) {
            return true;
        }

        // Retry on 500, 503, and other internal errors.
        //
        // Note that we expect the stripe-should-retry header to be false
        // in most cases when a 500 is returned, since our idempotency framework
        // would typically replay it anyway.
        if ($rcode >= 500) {
            return true;
        }

        return false;
    }

    /**
     * Provides the number of seconds to wait before retrying a request.
     *
     * @param int $numRetries
     * @param array|\Stripe\Util\CaseInsensitiveArray $rheaders
     *
     * @return int
     */
    private function sleepTime($numRetries, $rheaders)
    {
        // Apply exponential backoff with $initialNetworkRetryDelay on the
        // number of $numRetries so far as inputs. Do not allow the number to exceed
        // $maxNetworkRetryDelay.
        $sleepSeconds = \min(
            Stripe::getInitialNetworkRetryDelay() * 1.0 * 2 ** ($numRetries - 1),
            Stripe::getMaxNetworkRetryDelay()
        );

        // Apply some jitter by randomizing the value in the range of
        // ($sleepSeconds / 2) to ($sleepSeconds).
        $sleepSeconds *= 0.5 * (1 + $this->randomGenerator->randFloat());

        // But never sleep less than the base sleep seconds.
        $sleepSeconds = \max(Stripe::getInitialNetworkRetryDelay(), $sleepSeconds);

        // And never sleep less than the time the API asks us to wait, assuming it's a reasonable ask.
        $retryAfter = isset($rheaders['retry-after']) ? (float) ($rheaders['retry-after']) : 0.0;
        if (\floor($retryAfter) === $retryAfter && $retryAfter <= Stripe::getMaxRetryAfter()) {
            $sleepSeconds = \max($sleepSeconds, $retryAfter);
        }

        return $sleepSeconds;
    }

    /**
     * Initializes the curl handle. If already initialized, the handle is closed first.
     */
    private function initCurlHandle()
    {
        $this->closeCurlHandle();
        $this->curlHandle = \curl_init();
    }

    /**
     * Closes the curl handle if initialized. Do nothing if already closed.
     */
    private function closeCurlHandle()
    {
        if (null !== $this->curlHandle) {
            \curl_close($this->curlHandle);
            $this->curlHandle = null;
        }
    }

    /**
     * Resets the curl handle. If the handle is not already initialized, or if persistent
     * connections are disabled, the handle is reinitialized instead.
     */
    private function resetCurlHandle()
    {
        if (null !== $this->curlHandle && $this->getEnablePersistentConnections()) {
            \curl_reset($this->curlHandle);
        } else {
            $this->initCurlHandle();
        }
    }

    /**
     * Indicates whether it is safe to use HTTP/2 or not.
     *
     * @return bool
     */
    private function canSafelyUseHttp2()
    {
        // Versions of curl older than 7.60.0 don't respect GOAWAY frames
        // (cf. https://github.com/curl/curl/issues/2416), which Stripe use.
        $curlVersion = \curl_version()['version'];

        return \version_compare($curlVersion, '7.60.0') >= 0;
    }

    /**
     * Checks if a list of headers contains a specific header name.
     *
     * @param string[] $headers
     * @param string $name
     *
     * @return bool
     */
    private function hasHeader($headers, $name)
    {
        foreach ($headers as $header) {
            if (0 === \strncasecmp($header, "{$name}: ", \strlen($name) + 2)) {
                return true;
            }
        }

        return false;
    }
}
