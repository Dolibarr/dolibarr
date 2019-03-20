<?php

namespace Sabre\HTTP;

use Sabre\URI;

/**
 * URL utility class
 *
 * Note: this class is deprecated. All its functionality moved to functions.php
 * or sabre\uri.
 *
 * @deprecated
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class URLUtil {

    /**
     * Encodes the path of a url.
     *
     * slashes (/) are treated as path-separators.
     *
     * @deprecated use \Sabre\HTTP\encodePath()
     * @param string $path
     * @return string
     */
    static function encodePath($path) {

        return encodePath($path);

    }

    /**
     * Encodes a 1 segment of a path
     *
     * Slashes are considered part of the name, and are encoded as %2f
     *
     * @deprecated use \Sabre\HTTP\encodePathSegment()
     * @param string $pathSegment
     * @return string
     */
    static function encodePathSegment($pathSegment) {

        return encodePathSegment($pathSegment);

    }

    /**
     * Decodes a url-encoded path
     *
     * @deprecated use \Sabre\HTTP\decodePath
     * @param string $path
     * @return string
     */
    static function decodePath($path) {

        return decodePath($path);

    }

    /**
     * Decodes a url-encoded path segment
     *
     * @deprecated use \Sabre\HTTP\decodePathSegment()
     * @param string $path
     * @return string
     */
    static function decodePathSegment($path) {

        return decodePathSegment($path);

    }

    /**
     * Returns the 'dirname' and 'basename' for a path.
     *
     * @deprecated Use Sabre\Uri\split().
     * @param string $path
     * @return array
     */
    static function splitPath($path) {

        return Uri\split($path);

    }

    /**
     * Resolves relative urls, like a browser would.
     *
     * @deprecated Use Sabre\Uri\resolve().
     * @param string $basePath
     * @param string $newPath
     * @return string
     */
    static function resolve($basePath, $newPath) {

        return Uri\resolve($basePath, $newPath);

    }

}
