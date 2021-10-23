<?php

namespace Sabre\HTTP;

/**
 * This trait contains a bunch of methods, shared by both the RequestDecorator
 * and the ResponseDecorator.
 *
 * Didn't seem needed to create a full class for this, so we're just
 * implementing it as a trait.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
trait MessageDecoratorTrait {

    /**
     * The inner request object.
     *
     * All method calls will be forwarded here.
     *
     * @var MessageInterface
     */
    protected $inner;

    /**
     * Returns the body as a readable stream resource.
     *
     * Note that the stream may not be rewindable, and therefore may only be
     * read once.
     *
     * @return resource
     */
    function getBodyAsStream() {

        return $this->inner->getBodyAsStream();

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

        return $this->inner->getBodyAsString();

    }

    /**
     * Returns the message body, as it's internal representation.
     *
     * This could be either a string or a stream.
     *
     * @return resource|string
     */
    function getBody() {

        return $this->inner->getBody();

    }

    /**
     * Updates the body resource with a new stream.
     *
     * @param resource $body
     * @return void
     */
    function setBody($body) {

        $this->inner->setBody($body);

    }

    /**
     * Returns all the HTTP headers as an array.
     *
     * Every header is returned as an array, with one or more values.
     *
     * @return array
     */
    function getHeaders() {

        return $this->inner->getHeaders();

    }

    /**
     * Will return true or false, depending on if a HTTP header exists.
     *
     * @param string $name
     * @return bool
     */
    function hasHeader($name) {

        return $this->inner->hasHeader($name);

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

        return $this->inner->getHeader($name);

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

        return $this->inner->getHeaderAsArray($name);

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

        $this->inner->setHeader($name, $value);

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

        $this->inner->setHeaders($headers);

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

        $this->inner->addHeader($name, $value);

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

        $this->inner->addHeaders($headers);

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

        return $this->inner->removeHeader($name);

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

        $this->inner->setHttpVersion($version);

    }

    /**
     * Returns the HTTP version.
     *
     * @return string
     */
    function getHttpVersion() {

        return $this->inner->getHttpVersion();

    }

}
