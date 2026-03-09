<?php

namespace Luracast\Restler\Format;

use Luracast\Restler\Data\Obj;
use Luracast\Restler\RestException;

/**
 * Javascript Object Notation Format
 *
 * @category   Framework
 * @package    Restler
 * @subpackage format
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
class JsonFormat extends Format
{
    /**
     * @var boolean|null  shim for json_encode option JSON_PRETTY_PRINT set
     * it to null to use smart defaults
     */
    public static $prettyPrint = null;

    /**
     * @var boolean|null  shim for json_encode option JSON_UNESCAPED_SLASHES
     * set it to null to use smart defaults
     */
    public static $unEscapedSlashes = null;

    /**
     * @var boolean|null  shim for json_encode JSON_UNESCAPED_UNICODE set it
     * to null to use smart defaults
     */
    public static $unEscapedUnicode = null;

    /**
     * @var boolean|null  shim for json_decode JSON_BIGINT_AS_STRING set it to
     * null to
     * use smart defaults
     */
    public static $bigIntAsString = null;

    /**
     * @var boolean|null  shim for json_decode JSON_NUMERIC_CHECK set it to
     * null to
     * use smart defaults
     */
    public static $numbersAsNumbers = null;

    const MIME = 'application/json';
    const EXTENSION = 'json';

    public function encode($data, $humanReadable = false)
    {
        if (!is_null(self::$prettyPrint)) {
            $humanReadable = self::$prettyPrint;
        }
        if (is_null(self::$unEscapedSlashes)) {
            self::$unEscapedSlashes = $humanReadable;
        }
        if (is_null(self::$unEscapedUnicode)) {
            self::$unEscapedUnicode = $this->charset == 'utf-8';
        }

        $options = 0;

        if ((PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION >= 4) // PHP >= 5.4
            || PHP_MAJOR_VERSION > 5 // PHP >= 6.0
        ) {

            if ($humanReadable) {
                $options |= JSON_PRETTY_PRINT;
            }

            if (self::$unEscapedSlashes) {
                $options |= JSON_UNESCAPED_SLASHES;
            }

            if (self::$bigIntAsString) {
                $options |= JSON_BIGINT_AS_STRING;
            }

            if (self::$unEscapedUnicode) {
                $options |= JSON_UNESCAPED_UNICODE;
            }

            if (self::$numbersAsNumbers) {
                $options |= JSON_NUMERIC_CHECK;
            }

            $result = json_encode(Obj::toArray($data, true), $options);
            $this->handleJsonError();

            return $result;
        }

        $result = json_encode(Obj::toArray($data, true));
        $this->handleJsonError();

        if ($humanReadable) {
            $result = $this->formatJson($result);
        }

        if (self::$unEscapedUnicode) {
            $result = preg_replace_callback(
                '/\\\u(\w\w\w\w)/',
                function ($matches) {
                    if (function_exists('mb_convert_encoding')) {
                        return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UTF-16BE');
                    } else {
                        return iconv('UTF-16BE', 'UTF-8', pack('H*', $matches[1]));
                    }
                },
                $result
            );
        }

        if (self::$unEscapedSlashes) {
            $result = str_replace('\/', '/', $result);
        }

        return $result;
    }

    public function decode($data)
    {
        if (empty($data)) {
            return null;
        }

        $options = 0;
        if (self::$bigIntAsString) {
            if ((PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION >= 4) // PHP >= 5.4
                || PHP_MAJOR_VERSION > 5 // PHP >= 6.0
            ) {
                $options |= JSON_BIGINT_AS_STRING;
            } else {
                $data = preg_replace(
                    '/:\s*(\-?\d+(\.\d+)?([e|E][\-|\+]\d+)?)/',
                    ': "$1"',
                    $data
                );
            }
        }

        try {
            $decoded = json_decode($data, true, 512, $options);
            $this->handleJsonError();
        } catch (\RuntimeException $e) {
            throw new RestException(400, $e->getMessage());
        }

        if (strlen($data) && $decoded === null || $decoded === $data) {
            throw new RestException(400, 'Error parsing JSON');
        }

        return $decoded; //Obj::toArray($decoded);
    }

    /**
     * Pretty print JSON string
     *
     * @param string $json
     *
     * @return string formatted json
     */
    private function formatJson($json)
    {
        $tab = '  ';
        $newJson = '';
        $indentLevel = 0;
        $inString = false;
        $len = strlen($json);
        for ($c = 0; $c < $len; $c++) {
            $char = $json [$c];
            switch ($char) {
                case '{' :
                case '[' :
                    if (!$inString) {
                        $newJson .= $char . "\n" .
                            str_repeat($tab, $indentLevel + 1);
                        $indentLevel++;
                    } else {
                        $newJson .= $char;
                    }
                    break;
                case '}' :
                case ']' :
                    if (!$inString) {
                        $indentLevel--;
                        $newJson .= "\n" .
                            str_repeat($tab, $indentLevel) . $char;
                    } else {
                        $newJson .= $char;
                    }
                    break;
                case ',' :
                    if (!$inString) {
                        $newJson .= ",\n" .
                            str_repeat($tab, $indentLevel);
                    } else {
                        $newJson .= $char;
                    }
                    break;
                case ':' :
                    if (!$inString) {
                        $newJson .= ': ';
                    } else {
                        $newJson .= $char;
                    }
                    break;
                case '"' :
                    if ($c == 0) {
                        $inString = true;
                    } elseif ($c > 0 && $json [$c - 1] != '\\') {
                        $inString = !$inString;
                    }
                default :
                    $newJson .= $char;
                    break;
            }
        }

        return $newJson;
    }

    /**
     * Throws an exception if an error occurred during the last JSON encoding/decoding
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function handleJsonError()
    {
        if (function_exists('json_last_error_msg') && json_last_error() !== JSON_ERROR_NONE) {

            // PHP >= 5.5.0

            $message = json_last_error_msg();

        } elseif (function_exists('json_last_error')) {

            // PHP >= 5.3.0

            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    break;
                case JSON_ERROR_DEPTH:
                    $message = 'maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $message = 'underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $message = 'unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $message = 'malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $message = 'malformed UTF-8 characters, possibly ' .
                        'incorrectly encoded';
                    break;
                default:
                    $message = 'unknown error';
                    break;
            }
        }

        if (isset($message)) {
            throw new \RuntimeException('Error encoding/decoding JSON: ' . $message);
        }
    }
}
