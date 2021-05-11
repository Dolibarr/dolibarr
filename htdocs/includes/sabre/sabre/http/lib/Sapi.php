<?php

namespace Sabre\HTTP;

/**
 * PHP SAPI
 *
 * This object is responsible for:
 * 1. Constructing a Request object based on the current HTTP request sent to
 *    the PHP process.
 * 2. Sending the Response object back to the client.
 *
 * It could be said that this class provides a mapping between the Request and
 * Response objects, and php's:
 *
 * * $_SERVER
 * * $_POST
 * * $_FILES
 * * php://input
 * * echo()
 * * header()
 * * php://output
 *
 * You can choose to either call all these methods statically, but you can also
 * instantiate this as an object to allow for polymorhpism.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Sapi {

    /**
     * This static method will create a new Request object, based on the
     * current PHP request.
     *
     * @return Request
     */
    static function getRequest() {

        $r = self::createFromServerArray($_SERVER);
        $r->setBody(fopen('php://input', 'r'));
        $r->setPostData($_POST);
        return $r;

    }

    /**
     * Sends the HTTP response back to a HTTP client.
     *
     * This calls php's header() function and streams the body to php://output.
     *
     * @param ResponseInterface $response
     * @return void
     */
    static function sendResponse(ResponseInterface $response) {

        header('HTTP/' . $response->getHttpVersion() . ' ' . $response->getStatus() . ' ' . $response->getStatusText());
        foreach ($response->getHeaders() as $key => $value) {

            foreach ($value as $k => $v) {
                if ($k === 0) {
                    header($key . ': ' . $v);
                } else {
                    header($key . ': ' . $v, false);
                }
            }

        }

        $body = $response->getBody();
        if (is_null($body)) return;

        $contentLength = $response->getHeader('Content-Length');
        if ($contentLength !== null) {
            $output = fopen('php://output', 'wb');
            if (is_resource($body) && get_resource_type($body) == 'stream') {
                stream_copy_to_stream($body, $output, $contentLength);
            } else {
                fwrite($output, $body, $contentLength);
            }
        } else {
            file_put_contents('php://output', $body);
        }

        if (is_resource($body)) {
            fclose($body);
        }

    }

    /**
     * This static method will create a new Request object, based on a PHP
     * $_SERVER array.
     *
     * @param array $serverArray
     * @return Request
     */
    static function createFromServerArray(array $serverArray) {

        $headers = [];
        $method = null;
        $url = null;
        $httpVersion = '1.1';

        $protocol = 'http';
        $hostName = 'localhost';

        foreach ($serverArray as $key => $value) {

            switch ($key) {

                case 'SERVER_PROTOCOL' :
                    if ($value === 'HTTP/1.0') {
                        $httpVersion = '1.0';
                    }
                    break;
                case 'REQUEST_METHOD' :
                    $method = $value;
                    break;
                case 'REQUEST_URI' :
                    $url = $value;
                    break;

                // These sometimes show up without a HTTP_ prefix
                case 'CONTENT_TYPE' :
                    $headers['Content-Type'] = $value;
                    break;
                case 'CONTENT_LENGTH' :
                    $headers['Content-Length'] = $value;
                    break;

                // mod_php on apache will put credentials in these variables.
                // (fast)cgi does not usually do this, however.
                case 'PHP_AUTH_USER' :
                    if (isset($serverArray['PHP_AUTH_PW'])) {
                        $headers['Authorization'] = 'Basic ' . base64_encode($value . ':' . $serverArray['PHP_AUTH_PW']);
                    }
                    break;

                // Similarly, mod_php may also screw around with digest auth.
                case 'PHP_AUTH_DIGEST' :
                    $headers['Authorization'] = 'Digest ' . $value;
                    break;

                // Apache may prefix the HTTP_AUTHORIZATION header with
                // REDIRECT_, if mod_rewrite was used.
                case 'REDIRECT_HTTP_AUTHORIZATION' :
                    $headers['Authorization'] = $value;
                    break;

                case 'HTTP_HOST' :
                    $hostName = $value;
                    $headers['Host'] = $value;
                    break;

                case 'HTTPS' :
                    if (!empty($value) && $value !== 'off') {
                        $protocol = 'https';
                    }
                    break;

                default :
                    if (substr($key, 0, 5) === 'HTTP_') {
                        // It's a HTTP header

                        // Normalizing it to be prettier
                        $header = strtolower(substr($key, 5));

                        // Transforming dashes into spaces, and uppercasing
                        // every first letter.
                        $header = ucwords(str_replace('_', ' ', $header));

                        // Turning spaces into dashes.
                        $header = str_replace(' ', '-', $header);
                        $headers[$header] = $value;

                    }
                    break;


            }

        }

        $r = new Request($method, $url, $headers);
        $r->setHttpVersion($httpVersion);
        $r->setRawServerData($serverArray);
        $r->setAbsoluteUrl($protocol . '://' . $hostName . $url);
        return $r;

    }

}
