<?php

namespace Sabre\HTTP;

/**
 * This class represents a single HTTP response.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Response extends Message implements ResponseInterface {

    /**
     * This is the list of currently registered HTTP status codes.
     *
     * @var array
     */
    static $statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authorative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status', // RFC 4918
        208 => 'Already Reported', // RFC 5842
        226 => 'IM Used', // RFC 3229
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot', // RFC 2324
        421 => 'Misdirected Request', // RFC7540 (HTTP/2)
        422 => 'Unprocessable Entity', // RFC 4918
        423 => 'Locked', // RFC 4918
        424 => 'Failed Dependency', // RFC 4918
        426 => 'Upgrade Required',
        428 => 'Precondition Required', // RFC 6585
        429 => 'Too Many Requests', // RFC 6585
        431 => 'Request Header Fields Too Large', // RFC 6585
        451 => 'Unavailable For Legal Reasons', // draft-tbray-http-legally-restricted-status
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage', // RFC 4918
        508 => 'Loop Detected', // RFC 5842
        509 => 'Bandwidth Limit Exceeded', // non-standard
        510 => 'Not extended',
        511 => 'Network Authentication Required', // RFC 6585
    ];

    /**
     * HTTP status code
     *
     * @var int
     */
    protected $status;

    /**
     * HTTP status text
     *
     * @var string
     */
    protected $statusText;

    /**
     * Creates the response object
     *
     * @param string|int $status
     * @param array $headers
     * @param resource $body
     */
    function __construct($status = null, array $headers = null, $body = null) {

        if (!is_null($status)) $this->setStatus($status);
        if (!is_null($headers)) $this->setHeaders($headers);
        if (!is_null($body)) $this->setBody($body);

    }


    /**
     * Returns the current HTTP status code.
     *
     * @return int
     */
    function getStatus() {

        return $this->status;

    }

    /**
     * Returns the human-readable status string.
     *
     * In the case of a 200, this may for example be 'OK'.
     *
     * @return string
     */
    function getStatusText() {

        return $this->statusText;

    }

    /**
     * Sets the HTTP status code.
     *
     * This can be either the full HTTP status code with human readable string,
     * for example: "403 I can't let you do that, Dave".
     *
     * Or just the code, in which case the appropriate default message will be
     * added.
     *
     * @param string|int $status
     * @throws \InvalidArgumentException
     * @return void
     */
    function setStatus($status) {

        if (ctype_digit($status) || is_int($status)) {

            $statusCode = $status;
            $statusText = isset(self::$statusCodes[$status]) ? self::$statusCodes[$status] : 'Unknown';

        } else {
            list(
                $statusCode,
                $statusText
            ) = explode(' ', $status, 2);
        }
        if ($statusCode < 100 || $statusCode > 999) {
            throw new \InvalidArgumentException('The HTTP status code must be exactly 3 digits');
        }

        $this->status = $statusCode;
        $this->statusText = $statusText;

    }

    /**
     * Serializes the response object as a string.
     *
     * This is useful for debugging purposes.
     *
     * @return string
     */
    function __toString() {

        $str = 'HTTP/' . $this->httpVersion . ' ' . $this->getStatus() . ' ' . $this->getStatusText() . "\r\n";
        foreach ($this->getHeaders() as $key => $value) {
            foreach ($value as $v) {
                $str .= $key . ": " . $v . "\r\n";
            }
        }
        $str .= "\r\n";
        $str .= $this->getBodyAsString();
        return $str;

    }

}
