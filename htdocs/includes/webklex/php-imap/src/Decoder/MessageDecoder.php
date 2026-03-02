<?php
/*
* File: MessageDecoder.php
* Category: -
* Author: M.Goldenbaum
* Created: 12.04.24 20:14
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Decoder;

use Exception;
use Webklex\PHPIMAP\EncodingAliases;
use Webklex\PHPIMAP\IMAP;

/**
 * Class MessageDecoder
 *
 * @package Webklex\PHPIMAP
 */
class MessageDecoder extends Decoder {

    public function decode(array|string|null $value, ?string $encoding = null): mixed {
        if(is_array($value)) {
            return array_map(function($item){
                return $this->decode($item);
            }, $value);
        }

        switch ($encoding) {
            case IMAP::MESSAGE_ENC_BINARY:
                if (extension_loaded('imap')) {
                    return base64_decode(\imap_binary($value));
                }
                return base64_decode($value);
            case IMAP::MESSAGE_ENC_BASE64:
                return base64_decode($value);
            case IMAP::MESSAGE_ENC_QUOTED_PRINTABLE:
                return quoted_printable_decode($value);
            case IMAP::MESSAGE_ENC_8BIT:
            case IMAP::MESSAGE_ENC_7BIT:
            case IMAP::MESSAGE_ENC_OTHER:
            default:
                return $value;
        }
    }

    /**
     * Get the encoding of a given abject
     * @param object|string $structure
     *
     * @return string
     */
    public function getEncoding(object|string $structure): string {
        if (property_exists($structure, 'parameters')) {
            foreach ($structure->parameters as $parameter) {
                if (strtolower($parameter->attribute) == "charset") {
                    return EncodingAliases::get($parameter->value, "ISO-8859-2");
                }
            }
        } elseif (property_exists($structure, 'charset')) {
            return EncodingAliases::get($structure->charset, "ISO-8859-2");
        } elseif (is_string($structure) === true) {
            return EncodingAliases::detectEncoding($structure);
        }

        return $this->fallback_encoding;
    }


    /**
     * Convert the encoding
     * @param $str
     * @param string $from
     * @param string $to
     *
     * @return mixed|string
     */
    public function convertEncoding($str, string $from = "ISO-8859-2", string $to = "UTF-8"): mixed {
        $from = EncodingAliases::get($from);
        $to = EncodingAliases::get($to);

        if ($from === $to) {
            return $str;
        }

        // We don't need to do convertEncoding() if charset is ASCII (us-ascii):
        //     ASCII is a subset of UTF-8, so all ASCII files are already UTF-8 encoded
        //     https://stackoverflow.com/a/11303410
        //
        // us-ascii is the same as ASCII:
        //     ASCII is the traditional name for the encoding system; the Internet Assigned Numbers Authority (IANA)
        //     prefers the updated name US-ASCII, which clarifies that this system was developed in the US and
        //     based on the typographical symbols predominantly in use there.
        //     https://en.wikipedia.org/wiki/ASCII
        //
        // convertEncoding() function basically means convertToUtf8(), so when we convert ASCII string into UTF-8 it gets broken.
        if (strtolower($from ?? '') == 'us-ascii' && $to == 'UTF-8') {
            return $str;
        }

        if (function_exists('iconv') && !EncodingAliases::isUtf7($from) && !EncodingAliases::isUtf7($to)) {
            try {
                return iconv($from, $to.'//IGNORE', $str);
            } catch (Exception) {
                return @iconv($from, $to, $str);
            }
        } else {
            if (!$from) {
                return mb_convert_encoding($str, $to);
            }
            return mb_convert_encoding($str, $to, $from);
        }
    }

}