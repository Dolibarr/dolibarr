<?php

namespace Sabre\HTTP;

/**
 * HTTP utility methods
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @author Paul Voegler
 * @deprecated All these functions moved to functions.php
 * @license http://sabre.io/license/ Modified BSD License
 */
class Util {

    /**
     * Content negotiation
     *
     * @deprecated Use \Sabre\HTTP\negotiateContentType
     * @param string|null $acceptHeaderValue
     * @param array $availableOptions
     * @return string|null
     */
    static function negotiateContentType($acceptHeaderValue, array $availableOptions) {

        return negotiateContentType($acceptHeaderValue, $availableOptions);

    }

    /**
     * Deprecated! Use negotiateContentType.
     *
     * @deprecated Use \Sabre\HTTP\NegotiateContentType
     * @param string|null $acceptHeaderValue
     * @param array $availableOptions
     * @return string|null
     */
    static function negotiate($acceptHeaderValue, array $availableOptions) {

        return negotiateContentType($acceptHeaderValue, $availableOptions);

    }

    /**
     * Parses a RFC2616-compatible date string
     *
     * This method returns false if the date is invalid
     *
     * @deprecated Use parseDate
     * @param string $dateHeader
     * @return bool|DateTime
     */
    static function parseHTTPDate($dateHeader) {

        return parseDate($dateHeader);

    }

    /**
     * Transforms a DateTime object to HTTP's most common date format.
     *
     * We're serializing it as the RFC 1123 date, which, for HTTP must be
     * specified as GMT.
     *
     * @deprecated Use toDate
     * @param \DateTime $dateTime
     * @return string
     */
    static function toHTTPDate(\DateTime $dateTime) {

        return toDate($dateTime);

    }
}
