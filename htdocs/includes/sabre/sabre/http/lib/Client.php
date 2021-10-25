<?php

namespace Sabre\HTTP;

use Sabre\Event\EventEmitter;
use Sabre\Uri;

/**
 * A rudimentary HTTP client.
 *
 * This object wraps PHP's curl extension and provides an easy way to send it a
 * Request object, and return a Response object.
 *
 * This is by no means intended as the next best HTTP client, but it does the
 * job and provides a simple integration with the rest of sabre/http.
 *
 * This client emits the following events:
 *   beforeRequest(RequestInterface $request)
 *   afterRequest(RequestInterface $request, ResponseInterface $response)
 *   error(RequestInterface $request, ResponseInterface $response, bool &$retry, int $retryCount)
 *   exception(RequestInterface $request, ClientException $e, bool &$retry, int $retryCount)
 *
 * The beforeRequest event allows you to do some last minute changes to the
 * request before it's done, such as adding authentication headers.
 *
 * The afterRequest event will be emitted after the request is completed
 * succesfully.
 *
 * If a HTTP error is returned (status code higher than 399) the error event is
 * triggered. It's possible using this event to retry the request, by setting
 * retry to true.
 *
 * The amount of times a request has retried is passed as $retryCount, which
 * can be used to avoid retrying indefinitely. The first time the event is
 * called, this will be 0.
 *
 * It's also possible to intercept specific http errors, by subscribing to for
 * example 'error:401'.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Client extends EventEmitter {

    /**
     * List of curl settings
     *
     * @var array
     */
    protected $curlSettings = [];

    /**
     * Wether or not exceptions should be thrown when a HTTP error is returned.
     *
     * @var bool
     */
    protected $throwExceptions = false;

    /**
     * The maximum number of times we'll follow a redirect.
     *
     * @var int
     */
    protected $maxRedirects = 5;

    /**
     * Initializes the client.
     *
     * @return void
     */
    function __construct() {

        $this->curlSettings = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_NOBODY         => false,
            CURLOPT_USERAGENT      => 'sabre-http/' . Version::VERSION . ' (http://sabre.io/)',
        ];

    }

    /**
     * Sends a request to a HTTP server, and returns a response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    function send(RequestInterface $request) {

        $this->emit('beforeRequest', [$request]);

        $retryCount = 0;
        $redirects = 0;

        do {

            $doRedirect = false;
            $retry = false;

            try {

                $response = $this->doRequest($request);

                $code = (int)$response->getStatus();

                // We are doing in-PHP redirects, because curl's
                // FOLLOW_LOCATION throws errors when PHP is configured with
                // open_basedir.
                //
                // https://github.com/fruux/sabre-http/issues/12
                if (in_array($code, [301, 302, 307, 308]) && $redirects < $this->maxRedirects) {

                    $oldLocation = $request->getUrl();

                    // Creating a new instance of the request object.
                    $request = clone $request;

                    // Setting the new location
                    $request->setUrl(Uri\resolve(
                        $oldLocation,
                        $response->getHeader('Location')
                    ));

                    $doRedirect = true;
                    $redirects++;

                }

                // This was a HTTP error
                if ($code >= 400) {

                    $this->emit('error', [$request, $response, &$retry, $retryCount]);
                    $this->emit('error:' . $code, [$request, $response, &$retry, $retryCount]);

                }

            } catch (ClientException $e) {

                $this->emit('exception', [$request, $e, &$retry, $retryCount]);

                // If retry was still set to false, it means no event handler
                // dealt with the problem. In this case we just re-throw the
                // exception.
                if (!$retry) {
                    throw $e;
                }

            }

            if ($retry) {
                $retryCount++;
            }

        } while ($retry || $doRedirect);

        $this->emit('afterRequest', [$request, $response]);

        if ($this->throwExceptions && $code >= 400) {
            throw new ClientHttpException($response);
        }

        return $response;

    }

    /**
     * Sends a HTTP request asynchronously.
     *
     * Due to the nature of PHP, you must from time to time poll to see if any
     * new responses came in.
     *
     * After calling sendAsync, you must therefore occasionally call the poll()
     * method, or wait().
     *
     * @param RequestInterface $request
     * @param callable $success
     * @param callable $error
     * @return void
     */
    function sendAsync(RequestInterface $request, callable $success = null, callable $error = null) {

        $this->emit('beforeRequest', [$request]);
        $this->sendAsyncInternal($request, $success, $error);
        $this->poll();

    }


    /**
     * This method checks if any http requests have gotten results, and if so,
     * call the appropriate success or error handlers.
     *
     * This method will return true if there are still requests waiting to
     * return, and false if all the work is done.
     *
     * @return bool
     */
    function poll() {

        // nothing to do?
        if (!$this->curlMultiMap) {
            return false;
        }

        do {
            $r = curl_multi_exec(
                $this->curlMultiHandle,
                $stillRunning
            );
        } while ($r === CURLM_CALL_MULTI_PERFORM);

        do {

            messageQueue:

            $status = curl_multi_info_read(
                $this->curlMultiHandle,
                $messagesInQueue
            );

            if ($status && $status['msg'] === CURLMSG_DONE) {

                $resourceId = intval($status['handle']);
                list(
                    $request,
                    $successCallback,
                    $errorCallback,
                    $retryCount,
                ) = $this->curlMultiMap[$resourceId];
                unset($this->curlMultiMap[$resourceId]);
                $curlResult = $this->parseCurlResult(curl_multi_getcontent($status['handle']), $status['handle']);
                $retry = false;

                if ($curlResult['status'] === self::STATUS_CURLERROR) {

                    $e = new ClientException($curlResult['curl_errmsg'], $curlResult['curl_errno']);
                    $this->emit('exception', [$request, $e, &$retry, $retryCount]);

                    if ($retry) {
                        $retryCount++;
                        $this->sendAsyncInternal($request, $successCallback, $errorCallback, $retryCount);
                        goto messageQueue;
                    }

                    $curlResult['request'] = $request;

                    if ($errorCallback) {
                        $errorCallback($curlResult);
                    }

                } elseif ($curlResult['status'] === self::STATUS_HTTPERROR) {

                    $this->emit('error', [$request, $curlResult['response'], &$retry, $retryCount]);
                    $this->emit('error:' . $curlResult['http_code'], [$request, $curlResult['response'], &$retry, $retryCount]);

                    if ($retry) {

                        $retryCount++;
                        $this->sendAsyncInternal($request, $successCallback, $errorCallback, $retryCount);
                        goto messageQueue;

                    }

                    $curlResult['request'] = $request;

                    if ($errorCallback) {
                        $errorCallback($curlResult);
                    }

                } else {

                    $this->emit('afterRequest', [$request, $curlResult['response']]);

                    if ($successCallback) {
                        $successCallback($curlResult['response']);
                    }

                }
            }

        } while ($messagesInQueue > 0);

        return count($this->curlMultiMap) > 0;

    }

    /**
     * Processes every HTTP request in the queue, and waits till they are all
     * completed.
     *
     * @return void
     */
    function wait() {

        do {
            curl_multi_select($this->curlMultiHandle);
            $stillRunning = $this->poll();
        } while ($stillRunning);

    }

    /**
     * If this is set to true, the Client will automatically throw exceptions
     * upon HTTP errors.
     *
     * This means that if a response came back with a status code greater than
     * or equal to 400, we will throw a ClientHttpException.
     *
     * This only works for the send() method. Throwing exceptions for
     * sendAsync() is not supported.
     *
     * @param bool $throwExceptions
     * @return void
     */
    function setThrowExceptions($throwExceptions) {

        $this->throwExceptions = $throwExceptions;

    }

    /**
     * Adds a CURL setting.
     *
     * These settings will be included in every HTTP request.
     *
     * @param int $name
     * @param mixed $value
     * @return void
     */
    function addCurlSetting($name, $value) {

        $this->curlSettings[$name] = $value;

    }

    /**
     * This method is responsible for performing a single request.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    protected function doRequest(RequestInterface $request) {

        $settings = $this->createCurlSettingsArray($request);

        if (!$this->curlHandle) {
            $this->curlHandle = curl_init();
        }

        curl_setopt_array($this->curlHandle, $settings);
        $response = $this->curlExec($this->curlHandle);
        $response = $this->parseCurlResult($response, $this->curlHandle);

        if ($response['status'] === self::STATUS_CURLERROR) {
            throw new ClientException($response['curl_errmsg'], $response['curl_errno']);
        }

        return $response['response'];

    }

    /**
     * Cached curl handle.
     *
     * By keeping this resource around for the lifetime of this object, things
     * like persistent connections are possible.
     *
     * @var resource
     */
    private $curlHandle;

    /**
     * Handler for curl_multi requests.
     *
     * The first time sendAsync is used, this will be created.
     *
     * @var resource
     */
    private $curlMultiHandle;

    /**
     * Has a list of curl handles, as well as their associated success and
     * error callbacks.
     *
     * @var array
     */
    private $curlMultiMap = [];

    /**
     * Turns a RequestInterface object into an array with settings that can be
     * fed to curl_setopt
     *
     * @param RequestInterface $request
     * @return array
     */
    protected function createCurlSettingsArray(RequestInterface $request) {

        $settings = $this->curlSettings;

        switch ($request->getMethod()) {
            case 'HEAD' :
                $settings[CURLOPT_NOBODY] = true;
                $settings[CURLOPT_CUSTOMREQUEST] = 'HEAD';
                $settings[CURLOPT_POSTFIELDS] = '';
                $settings[CURLOPT_PUT] = false;
                break;
            case 'GET' :
                $settings[CURLOPT_CUSTOMREQUEST] = 'GET';
                $settings[CURLOPT_POSTFIELDS] = '';
                $settings[CURLOPT_PUT] = false;
                break;
            default :
                $body = $request->getBody();
                if (is_resource($body)) {
                    // This needs to be set to PUT, regardless of the actual
                    // method used. Without it, INFILE will be ignored for some
                    // reason.
                    $settings[CURLOPT_PUT] = true;
                    $settings[CURLOPT_INFILE] = $request->getBody();
                } else {
                    // For security we cast this to a string. If somehow an array could
                    // be passed here, it would be possible for an attacker to use @ to
                    // post local files.
                    $settings[CURLOPT_POSTFIELDS] = (string)$body;
                }
                $settings[CURLOPT_CUSTOMREQUEST] = $request->getMethod();
                break;

        }

        $nHeaders = [];
        foreach ($request->getHeaders() as $key => $values) {

            foreach ($values as $value) {
                $nHeaders[] = $key . ': ' . $value;
            }

        }
        $settings[CURLOPT_HTTPHEADER] = $nHeaders;
        $settings[CURLOPT_URL] = $request->getUrl();
        // FIXME: CURLOPT_PROTOCOLS is currently unsupported by HHVM
        if (defined('CURLOPT_PROTOCOLS')) {
            $settings[CURLOPT_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }
        // FIXME: CURLOPT_REDIR_PROTOCOLS is currently unsupported by HHVM
        if (defined('CURLOPT_REDIR_PROTOCOLS')) {
            $settings[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP | CURLPROTO_HTTPS;
        }

        return $settings;

    }

    const STATUS_SUCCESS = 0;
    const STATUS_CURLERROR = 1;
    const STATUS_HTTPERROR = 2;

    /**
     * Parses the result of a curl call in a format that's a bit more
     * convenient to work with.
     *
     * The method returns an array with the following elements:
     *   * status - one of the 3 STATUS constants.
     *   * curl_errno - A curl error number. Only set if status is
     *                  STATUS_CURLERROR.
     *   * curl_errmsg - A current error message. Only set if status is
     *                   STATUS_CURLERROR.
     *   * response - Response object. Only set if status is STATUS_SUCCESS, or
     *                STATUS_HTTPERROR.
     *   * http_code - HTTP status code, as an int. Only set if Only set if
     *                 status is STATUS_SUCCESS, or STATUS_HTTPERROR
     *
     * @param string $response
     * @param resource $curlHandle
     * @return Response
     */
    protected function parseCurlResult($response, $curlHandle) {

        list(
            $curlInfo,
            $curlErrNo,
            $curlErrMsg
        ) = $this->curlStuff($curlHandle);

        if ($curlErrNo) {
            return [
                'status'      => self::STATUS_CURLERROR,
                'curl_errno'  => $curlErrNo,
                'curl_errmsg' => $curlErrMsg,
            ];
        }

        $headerBlob = substr($response, 0, $curlInfo['header_size']);
        // In the case of 204 No Content, strlen($response) == $curlInfo['header_size].
        // This will cause substr($response, $curlInfo['header_size']) return FALSE instead of NULL
        // An exception will be thrown when calling getBodyAsString then
        $responseBody = substr($response, $curlInfo['header_size']) ?: null;

        unset($response);

        // In the case of 100 Continue, or redirects we'll have multiple lists
        // of headers for each separate HTTP response. We can easily split this
        // because they are separated by \r\n\r\n
        $headerBlob = explode("\r\n\r\n", trim($headerBlob, "\r\n"));

        // We only care about the last set of headers
        $headerBlob = $headerBlob[count($headerBlob) - 1];

        // Splitting headers
        $headerBlob = explode("\r\n", $headerBlob);

        $response = new Response();
        $response->setStatus($curlInfo['http_code']);

        foreach ($headerBlob as $header) {
            $parts = explode(':', $header, 2);
            if (count($parts) == 2) {
                $response->addHeader(trim($parts[0]), trim($parts[1]));
            }
        }

        $response->setBody($responseBody);

        $httpCode = intval($response->getStatus());

        return [
            'status'    => $httpCode >= 400 ? self::STATUS_HTTPERROR : self::STATUS_SUCCESS,
            'response'  => $response,
            'http_code' => $httpCode,
        ];

    }

    /**
     * Sends an asynchronous HTTP request.
     *
     * We keep this in a separate method, so we can call it without triggering
     * the beforeRequest event and don't do the poll().
     *
     * @param RequestInterface $request
     * @param callable $success
     * @param callable $error
     * @param int $retryCount
     */
    protected function sendAsyncInternal(RequestInterface $request, callable $success, callable $error, $retryCount = 0) {

        if (!$this->curlMultiHandle) {
            $this->curlMultiHandle = curl_multi_init();
        }
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            $this->createCurlSettingsArray($request)
        );
        curl_multi_add_handle($this->curlMultiHandle, $curl);
        $this->curlMultiMap[intval($curl)] = [
            $request,
            $success,
            $error,
            $retryCount
        ];

    }

    // @codeCoverageIgnoreStart

    /**
     * Calls curl_exec
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     * @return string
     */
    protected function curlExec($curlHandle) {

        return curl_exec($curlHandle);

    }

    /**
     * Returns a bunch of information about a curl request.
     *
     * This method exists so it can easily be overridden and mocked.
     *
     * @param resource $curlHandle
     * @return array
     */
    protected function curlStuff($curlHandle) {

        return [
            curl_getinfo($curlHandle),
            curl_errno($curlHandle),
            curl_error($curlHandle),
        ];

    }
    // @codeCoverageIgnoreEnd

}
