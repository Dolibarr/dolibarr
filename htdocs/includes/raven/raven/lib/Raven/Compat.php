<?php

/*
 * This file is part of Raven.
 *
 * (c) Sentry Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Raven_Compat
{

    public static function gethostname()
    {
        if (function_exists('gethostname')) {
            return gethostname();
        }

        return self::_gethostname();
    }

    public static function _gethostname()
    {
        return php_uname('n');
    }

    public static function hash_hmac($algo, $data, $key, $raw_output=false)
    {
        if (function_exists('hash_hmac')) {
            return hash_hmac($algo, $data, $key, $raw_output);
        }

        return self::_hash_hmac($algo, $data, $key, $raw_output);
    }

    /**
     * Implementation from 'KC Cloyd'.
     * See http://nl2.php.net/manual/en/function.hash-hmac.php
     */
    public static function _hash_hmac($algo, $data, $key, $raw_output=false)
    {
        $algo = strtolower($algo);
        $pack = 'H'.strlen($algo('test'));
        $size = 64;
        $opad = str_repeat(chr(0x5C), $size);
        $ipad = str_repeat(chr(0x36), $size);

        if (strlen($key) > $size) {
            $key = str_pad(pack($pack, $algo($key)), $size, chr(0x00));
        } else {
            $key = str_pad($key, $size, chr(0x00));
        }

        $keyLastPos = strlen($key) - 1;
        for ($i = 0; $i < $keyLastPos; $i++) {
            $opad[$i] = $opad[$i] ^ $key[$i];
            $ipad[$i] = $ipad[$i] ^ $key[$i];
        }

        $output = $algo($opad.pack($pack, $algo($ipad.$data)));

        return ($raw_output) ? pack($pack, $output) : $output;
    }

    /**
     * Note that we discard the options given to be compatible
     * with PHP < 5.3
     */
    public static function json_encode($value, $options=0)
    {
        if (function_exists('json_encode')) {
            return json_encode($value);
        }

        return self::_json_encode($value);
    }

    /**
     * Implementation taken from
     * http://www.mike-griffiths.co.uk/php-json_encode-alternative/
     */
    public static function _json_encode($value)
    {
        static $jsonReplaces = array(
            array('\\', '/', "\n", "\t", "\r", "\b", "\f", '"'),
            array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));

        if (is_null($value)) {
            return 'null';
        }
        if ($value === false) {
            return 'false';
        }
        if ($value === true) {
            return 'true';
        }

        if (is_scalar($value)) {

            // Always use '.' for floats.
            if (is_float($value)) {
                return floatval(str_replace(',', '.', strval($value)));
            }
            if (is_string($value)) {
                return sprintf('"%s"',
                    str_replace($jsonReplaces[0], $jsonReplaces[1], $value));
            } else {
                return $value;
            }
        }

        $isList = true;
        for ($i = 0, reset($value); $i<count($value); $i++, next($value)) {
            if (key($value) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($value as $v) {
                $result[] = self::_json_encode($v);
            }

            return '[' . join(',', $result) . ']';
        } else {
            foreach ($value as $k => $v) {
                $result[] = self::_json_encode($k) . ':' . self::_json_encode($v);
            }

            return '{' . join(',', $result) . '}';
        }
    }
}
