<?php

namespace Sabre\HTTP;

/**
 * This is the abstract base class for both the Request and Response objects.
 *
 * This object contains a few simple methods that are shared by both.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
abstract class Message implements MessageInterface {

    /**
     * Request body
     *
     * This should be a stream resource
     *
     * @var resource
     */
    protected $body;

    /**
     * Contains the list of HTTP headers
     *
     * @var array
     */
    protected $headers = [];

    /**
     * HTTP message version (1.0 or 1.1)
     *
     * @var string
     */
    protected $httpVersion = '1.1';

    /**
     * Returns the body as a readable stream resource.
     *
     * Note that the stream may not be rewindable, and therefore may only be
     * read once.
     *
     * @return resource
     */
    function getBodyAsStream() {

        $body = $this->getBody();
        if (is_string($body) || is_null($body)) {
            $stream = fopen('php://temp', 'r+');
            fwrite($stream, $body);
            rewind($stream);
            return $stream;
        }
        return $body;

    }

    /**
     * Returns the body as a string.
     *
     * Note that because the underlying data may be based on a stream, this
     * method could only work correctly the first time.
     *
     * @return string
     */
    function getBodyAsString() {

        $body = $this->getBody();
        if (is_string($body)) {
            return $body;
        }
        if (is_null($body)) {
            return '';
        }
        $contentLength = $this->getHeader('Content-Length');
        if (is_int($contentLength) || ctype_digit($contentLength)) {
            return stream_get_contents($body, $contentLength);
        } else {
            return stream_get_contents($body);
        }
    }

    /**
     * Returns the message body, as it's internal representation.
     *
     * This could be either a string or a stream.
     *
     * @return resource|string
     */
    function getBody() {

        return $this->body;

    }

    /**
     * Replaces the body resource with a new stream or string.
     *
     * @param resource|string $body
     */
    function setBody($body) {

        $this->body = $body;

    }

    /**
     * Returns all the HTTP headers as an array.
     *
     * Every header is returned as an array, with one or more values.
     *
     * @return array
     */
    function getHeaders() {

        $result = [];
        foreach ($this->headers as $headerInfo) {
            $result[$headerInfo[0]] = $headerInfo[1];
        }
        return $result;

    }

    /**
     * Will return true or false, depending on if a HTTP header exists.
     *
     * @param string $name
     * @return bool
     */
    function hasHeader($name) {

        return isset($this->headers[strtolower($name)]);

    }

    /**
     * Returns a specific HTTP header, based on it's name.
     *
     * The name must be treated as case-insensitive.
     * If the header does not exist, this method must return null.
     *
     * If a header appeared more than once in a HTTP request, this method will
     * concatenate all the values with a comma.
     *
     * Note that this not make sense for all headers. Some, such as
     * `Set-Cookie` cannot be logically combined with a comma. In those cases
     * you *should* use getHeaderAsArray().
     *
     * @param string $name
     * @return string|null
     */
    function getHeader($name) {

        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return implode(',', $this->headers[$name][1]);
        }
        return null;

    }

    /**
     * Returns a HTTP header as an array.
     *
     * For every time the HTTP header appeared in the request or response, an
     * item will appear in the array.
     *
     * If the header did not exists, this method will return an empty array.
     *
     * @param string $name
     * @return string[]
     */
    function getHeaderAsArray($name) {

        $name = strtolower($name);

        if (isset($this->headers[$name])) {
            return $this->headers[$name][1];
        }

        return [];

    }

    /**
     * Updates a HTTP header.
     *
     * The case-sensitivity of the name value must be retained as-is.
     *
     * If the header already existed, it will be overwritten.
     *
     * @param string $name
     * @param string|string[] $value
     * @return void
     */
    function setHeader($name, $value) {

        $this->headers[strtolower($name)] = [$name, (array)$value];

    }

    /**
     * Sets a new set of HTTP headers.
     *
     * The headers array should contain headernames for keys, and their value
     * should be specified as either a string or an array.
     *
     * Any header that already existed will be overwritten.
     *
     * @param array $headers
     * @return void
     */
    function setHeaders(array $headers) {

        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }

    }

    /**
     * Adds a HTTP header.
     *
     * This method will not overwrite any existing HTTP header, but instead add
     * another value. Individual values can be retrieved with
     * getHeadersAsArray.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    function addHeader($name, $value) {

        $lName = strtolower($name);
        if (isset($this->headers[$lName])) {
            $this->headers[$lName][1] = array_merge(
                $this->headers[$lName][1],
                (array)$value
            );
        } else {
            $this->headers[$lName] = [
                $name,
                (array)$value
            ];
        }

    }

    /**
     * Adds a new set of HTTP headers.
     *
     * Any existing headers will not be overwritten.
     *
     * @param array $headers
     * @return void
     */
    function addHeaders(array $headers) {

        foreach ($headers as $name => $value) {
            $this->addHeader($name, $value);
        }

    }


    /**
     * Removes a HTTP header.
     *
     * The specified header name must be treated as case-insensitive.
     * This method should return true if the header was successfully deleted,
     * and false if the header did not exist.
     *
     * @param string $name
     * @return bool
     */
    function removeHeader($name) {

        $name = strtolower($name);
        if (!isset($this->headers[$name])) {
            return false;
        }
        unset($this->headers[$name]);
        return true;

    }

    /**
     * Sets the HTTP version.
     *
     * Should be 1.0 or 1.1.
     *
     * @param string $version
     * @return void
     */
    function setHttpVersion($version) {

        $this->httpVersion = $version;

    }

    /**
     * Returns the HTTP version.
     *
     * @return string
     */
    function getHttpVersion() {

        return $this->httpVersion;

    }
}
