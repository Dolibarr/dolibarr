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
 * Class Decoder
 *
 * @package Webklex\PHPIMAP
 */
abstract class Decoder implements DecoderInterface {

    /**
     * Decoder constructor.
     * @param array $options Decoder options
     * @param string $fallback_encoding Fallback encoding
     */
    public function __construct(
        /**
         * Options
         *
         * @var array
         */
        protected array  $options = [],

        /**
         * Fallback Encoding
         *
         * @var string
         */
        protected string $fallback_encoding = 'UTF-8',
    ) {
        $this->options = array_merge([
            'header' => 'utf-8',
            'message' => 'utf-8',
            'attachment' => 'utf-8',
        ], $this->options);
    }

    /**
     * Decode a given value
     * @param array|string|null $value
     * @param string|null $encoding
     * @return mixed
     */
    public function decode(array|string|null $value, ?string $encoding = null): mixed {
        return $value;
    }

    /**
     * Convert the encoding
     * @param string $str The string to convert
     * @param string $from The source encoding
     * @param string $to The target encoding
     *
     * @return mixed|string
     */
    public function convertEncoding(string $str, string $from = "ISO-8859-2", string $to = "UTF-8"): mixed {
        $from = EncodingAliases::get($from, $this->fallback_encoding);
        $to = EncodingAliases::get($to, $this->fallback_encoding);

        if ($from === $to) {
            return $str;
        }

        return EncodingAliases::convert($str, $from, $to);
    }

    /**
     * Decode MIME header elements
     * @link https://php.net/manual/en/function.imap-mime-header-decode.php
     * @param string $text The MIME text
     *
     * @return array<int,object> Returns an array of objects. Each *object has two properties, charset and text.
     */
    public function mimeHeaderDecode(string $text): array {
        if (extension_loaded('imap')) {
            $result = \imap_mime_header_decode($text);
            return is_array($result) ? $result : [];
        }
        $charset = $this->getEncoding($text);
        return [(object)[
            "charset" => $charset,
            "text"    => $this->convertEncoding($text, $charset)
        ]];
    }

    /**
     * Test if a given value is utf-8 encoded
     * @param $value
     *
     * @return bool
     */
    public static function isUTF8($value): bool {
        return str_starts_with(strtolower($value), '=?utf-8?');
    }

    /**
     * Check if a given pair of strings has been decoded
     * @param $encoded
     * @param $decoded
     *
     * @return bool
     */
    public static function notDecoded($encoded, $decoded): bool {
        return str_starts_with($decoded, '=?')
            && strlen($decoded) - 2 === strpos($decoded, '?=')
            && str_contains($encoded, $decoded);
    }

    /**
     * Set the configuration used for decoding
     * @param array $config
     *
     * @return Decoder
     */
    public function setOptions(array $config): static {
        $this->options = $config;
        return $this;
    }

    /**
     * Get the configuration used for decoding
     *
     * @return array
     */
    public function getOptions(): array {
        return $this->options;
    }

    /**
     * Get the fallback encoding
     *
     * @return string
     */
    public function getFallbackEncoding(): string {
        return $this->fallback_encoding;
    }

    /**
     * Set the fallback encoding
     *
     * @param string $fallback_encoding
     * @return Decoder
     */
    public function setFallbackEncoding(string $fallback_encoding): static {
        $this->fallback_encoding = $fallback_encoding;
        return $this;
    }
}