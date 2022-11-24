<?php

namespace MathPHP\Functions;

use MathPHP\Exception;
use MathPHP\Number\ArbitraryInteger;

/**
 * Utility functions to manipulate numerical strings with non-standard bases and alphabets
 */
class BaseEncoderDecoder
{
    /** string alphabet of base 64 numbers */
    private const RFC3548_BASE64 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

    /** string alphabet of file safe base 64 numbers */
    private const RFC3548_BASE64_FILE_SAFE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_';

    /** string alphabet of base 32 numbers */
    private const RFC3548_BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Get the default alphabet for a given number base
     *
     * @param int $base
     *
     * @return string offset
     */
    protected static function getDefaultAlphabet(int $base): string
    {
        switch ($base) {
            case 2:
            case 8:
            case 10:
                return '0';

            case 16:
                return '0123456789abcdef';

            default:
                return \chr(0);
        }
    }

    /**
     * Convert to an arbitrary base and alphabet
     *
     * @param ArbitraryInteger $number
     * @param int $base
     * @param string $alphabet
     *
     * @return string
     *
     * @throws Exception\BadParameterException if the base is greater than 256
     */
    public static function toBase(ArbitraryInteger $number, int $base, $alphabet = null): string
    {
        if ($base > 256) {
            throw new Exception\BadParameterException("Number base cannot be greater than 256.");
        }
        if ($alphabet === null) {
            $alphabet = self::getDefaultAlphabet($base);
        }

        $base_256 = $number->toBinary();
        $result   = '';

        while ($base_256 !== '') {
            $carry    = 0;
            $next_int = $base_256;
            $len      = \strlen($base_256);
            $base_256 = '';

            for ($i = 0; $i < $len; $i++) {
                $chr   = \ord($next_int[$i]);
                $int   = \intdiv($chr + 256 * $carry, $base);
                $carry = ($chr + 256 * $carry) % $base;
                // or just trim off all leading chr(0)s
                if ($base_256 !== '' || $int > 0) {
                    $base_256 .= \chr($int);
                }
            }
            if (\strlen($alphabet) == 1) {
                $result = \chr(\ord($alphabet) + $carry) . $result;
            } else {
                $result = $alphabet[$carry] . $result;
            }
        }
        return $result;
    }

    /**
     * Create an ArbitraryInteger from a number string in novel number bases and alphabets
     *
     * @param string $number
     * @param int    $base
     * @param string $offset
     *
     * @return ArbitraryInteger
     *
     * @throws Exception\BadParameterException if the string is empty or base is greater than 256
     */
    public static function createArbitraryInteger(string $number, int $base, string $offset = null): ArbitraryInteger
    {
        if ($number == '') {
            throw new Exception\BadParameterException("String cannot be empty.");
        }
        if ($base > 256) {
            throw new Exception\BadParameterException("Number base cannot be greater than 256");
        }
        // Set to default offset and ascii alphabet
        if ($offset === null) {
            $offset = self::getDefaultAlphabet($base);
        }

        $length = \strlen($number);

        // Remove the offset.
        if ($offset !== \chr(0)) {
            // I'm duplicating the for loop instead of placing the if within the for
            // to prevent calling the if/else on every pass.
            if (\strlen($offset) ==  1) {
                // Subtract a constant offset from each character.
                $offset_num = \ord($offset);
                for ($i = 0; $i < $length; $i++) {
                    $chr   = $number[$i];
                    $digit = \ord($chr) - $offset_num;
                    // Check that all elements are greater than the offset, and are members of the alphabet.
                    if ($digit < 0 || $digit >= $base) {
                        throw new Exception\BadParameterException("Invalid character in string.");
                    }
                    $number[$i] = \chr(\ord($chr) - $offset_num);
                }
            } else {
                // Lookup the offset from the string position
                for ($i = 0; $i < $length; $i++) {
                    $chr = $number[$i];
                    $pos = \strpos($offset, $chr);
                    if ($pos === false) {
                        throw new Exception\BadParameterException("Invalid character in string.");
                    }
                    $number[$i] = \chr($pos);
                }
            }
        }
        // Convert to base 256
        $base256 = new ArbitraryInteger(0);
        $length  = \strlen($number);
        for ($i = 0; $i < $length; $i++) {
            $chr = \ord($number[$i]);
            $base256 = $base256->multiply($base)->add($chr);
        }

        return $base256;
    }
}
