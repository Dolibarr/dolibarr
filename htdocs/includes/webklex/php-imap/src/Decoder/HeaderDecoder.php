<?php
/*
* File: Decoder.php
* Category: -
* Author: M.Goldenbaum
* Created: 12.04.24 20:14
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Decoder;

use Webklex\PHPIMAP\EncodingAliases;

/**
 * Class HeaderDecoder
 *
 * @package Webklex\PHPIMAP
 */
class HeaderDecoder extends Decoder {

    public function decode(array|string|null $value, ?string $encoding = null): mixed {
        if (is_array($value)) {
            return $this->decodeHeaderArray($value);
        }
        $original_value = $value;
        $decoder = $this->options['header'];

        if ($value !== null) {
            if ($decoder === 'utf-8') {
                $decoded_values = $this->mimeHeaderDecode($value);
                $tempValue = "";
                foreach ($decoded_values as $decoded_value) {
                    $tempValue .= $this->convertEncoding($decoded_value->text, $decoded_value->charset);
                }
                if ($tempValue) {
                    $value = $tempValue;
                } else if (extension_loaded('imap')) {
                    $value = \imap_utf8($value);
                } else if (function_exists('iconv_mime_decode')) {
                    $value = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8");
                } else {
                    $value = mb_decode_mimeheader($value);
                }
            } elseif ($decoder === 'iconv') {
                $value = iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, "UTF-8");
            } else if (self::isUTF8($value)) {
                $value = mb_decode_mimeheader($value);
            }

            if (self::notDecoded($original_value, $value)) {
                $value = $this->convertEncoding($original_value, $this->getEncoding($original_value));
            }
        }

        return $value;
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
                    return EncodingAliases::get($parameter->value == "default" ? EncodingAliases::detectEncoding($parameter->value) : $parameter->value, $this->fallback_encoding);
                }
            }
        } elseif (property_exists($structure, 'charset')) {
            return EncodingAliases::get($structure->charset == "default" ? EncodingAliases::detectEncoding($structure->charset) : $structure->charset, $this->fallback_encoding);
        } elseif (is_string($structure) === true) {
            $result = mb_detect_encoding($structure);
            return $result === false ? $this->fallback_encoding : $result;
        }

        return $this->fallback_encoding;
    }


    /**
     * Decode a given array
     * @param array $values
     *
     * @return array
     */
    private function decodeHeaderArray(array $values): array {
        foreach ($values as $key => $value) {
            $values[$key] = $this->decode($value);
        }
        return $values;
    }

}