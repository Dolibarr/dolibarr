<?php
/*
* File: DecoderInterface.php
* Category: -
* Author: M.Goldenbaum
* Created: 12.04.24 20:25
* Updated: -
*
* Description:
*  -
*/

namespace Webklex\PHPIMAP\Decoder;

/**
 * Interface DecoderInterface
 *
 * @package Webklex\PHPIMAP\Decoder
 */
interface DecoderInterface {

    /**
     * DecoderInterface constructor.
     * @param array $options
     * @param string $fallback_encoding
     */
    public function __construct(array $options = [], string $fallback_encoding = 'UTF-8');

    /**
     * Decode a given value
     *
     * @param array|string|null $value
     * @param string|null $encoding
     * @return string|array|null
     */
    public function decode(array|string|null $value, ?string $encoding = null): mixed;

    public function mimeHeaderDecode(string $text): array;

    public function convertEncoding(string $str, string $from = "ISO-8859-2", string $to = "UTF-8"): mixed;

    public function getEncoding(object|string $structure): string;

    public function getOptions(): array;

    public function setOptions(array $config): static;

    public function getFallbackEncoding(): string;

    public function setFallbackEncoding(string $fallback_encoding): static;

}