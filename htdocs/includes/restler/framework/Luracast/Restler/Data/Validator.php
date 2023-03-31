<?php

namespace Luracast\Restler\Data;

use Luracast\Restler\CommentParser;
use Luracast\Restler\Format\HtmlFormat;
use Luracast\Restler\RestException;
use Luracast\Restler\Scope;
use Luracast\Restler\Util;

/**
 * Default Validator class used by Restler. It can be replaced by any
 * iValidate implementing class by setting Defaults::$validatorClass
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
class Validator implements iValidate
{
    public static $holdException = false;
    public static $exceptions = array();

    public static $preFilters = array(
        //'*'            => 'some_global_filter', //applied to all parameters
        'string' => 'trim', //apply filter function by type (string)
        //'string'       => 'strip_tags',
        //'string'       => 'htmlspecialchars',
        //'int'          => 'abs',
        //'float'        => 'abs',
        //'CustomClass'  => 'MyFilterClass::custom',
        //                  please note that you wont get an instance
        //                  of CustomClass. you will get an array instead
    );

    /**
     * Validate alphabetic characters.
     *
     * Check that given value contains only alphabetic characters.
     *
     * @param                $input
     * @param ValidationInfo $info
     *
     * @return string
     *
     * @throws Invalid
     */
    public static function alpha($input, ValidationInfo $info = null)
    {
        if (ctype_alpha($input)) {
            return $input;
        }
        if ($info && $info->fix) {
            //remove non alpha characters
            return preg_replace("/[^a-z]/i", "", $input);
        }
        throw new Invalid('Expecting only alphabetic characters.');
    }

    /**
     * Validate UUID strings.
     *
     * Check that given value contains only alpha numeric characters and the length is 36 chars.
     *
     * @param                $input
     * @param ValidationInfo $info
     *
     * @return string
     *
     * @throws Invalid
     */
    public static function uuid($input, ValidationInfo $info = null)
    {
        if (is_string($input) && preg_match(
                '/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i',
                $input
            )) {
            return strtolower($input);
        }
        throw new Invalid('Expecting a Universally Unique IDentifier (UUID) string.');
    }

    /**
     * Validate alpha numeric characters.
     *
     * Check that given value contains only alpha numeric characters.
     *
     * @param                $input
     * @param ValidationInfo $info
     *
     * @return string
     *
     * @throws Invalid
     */
    public static function alphanumeric($input, ValidationInfo $info = null)
    {
        if (ctype_alnum($input)) {
            return $input;
        }
        if ($info && $info->fix) {
            //remove non alpha numeric and space characters
            return preg_replace("/[^a-z0-9 ]/i", "", $input);
        }
        throw new Invalid('Expecting only alpha numeric characters.');
    }

    /**
     * Validate printable characters.
     *
     * Check that given value contains only printable characters.
     *
     * @param                $input
     * @param ValidationInfo $info
     *
     * @return string
     *
     * @throws Invalid
     */
    public static function printable($input, ValidationInfo $info = null)
    {
        if (ctype_print($input)) {
            return $input;
        }
        if ($info && $info->fix) {
            //remove non printable characters
            return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $input);
        }
        throw new Invalid('Expecting only printable characters.');
    }

    /**
     * Validate hexadecimal digits.
     *
     * Check that given value contains only hexadecimal digits.
     *
     * @param                $input
     * @param ValidationInfo $info
     *
     * @return string
     *
     * @throws Invalid
     */
    public static function hex($input, ValidationInfo $info = null)
    {
        if (ctype_xdigit($input)) {
            return $input;
        }
        throw new Invalid('Expecting only hexadecimal digits.');
    }

    /**
     * Color specified as hexadecimals
     *
     * Check that given value contains only color.
     *
     * @param                     $input
     * @param ValidationInfo|null $info
     *
     * @return string
     * @throws Invalid
     */
    public static function color($input, ValidationInfo $info = null)
    {
        if (preg_match('/^#[a-f0-9]{6}$/i', $input)) {
            return $input;
        }
        throw new Invalid('Expecting color as hexadecimal digits.');
    }

    /**
     * Validate Telephone number
     *
     * Check if the given value is numeric with or without a `+` prefix
     *
     * @param                $input
     * @param ValidationInfo $info
     *
     * @return string
     *
     * @throws Invalid
     */
    public static function tel($input, ValidationInfo $info = null)
    {
        if (is_numeric($input) && '-' != substr($input, 0, 1)) {
            return $input;
        }
        throw new Invalid('Expecting phone number, a numeric value ' .
            'with optional `+` prefix');
    }

    /**
     * Validate Email
     *
     * Check if the given string is a valid email
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function email($input, ValidationInfo $info = null)
    {
        $r = filter_var($input, FILTER_VALIDATE_EMAIL);
        if ($r) {
            return $r;
        } elseif ($info && $info->fix) {
            $r = filter_var($input, FILTER_SANITIZE_EMAIL);
            return static::email($r);
        }
        throw new Invalid('Expecting email in `name@example.com` format');
    }

    /**
     * Validate IP Address
     *
     * Check if the given string is a valid ip address
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function ip($input, ValidationInfo $info = null)
    {
        $r = filter_var($input, FILTER_VALIDATE_IP);
        if ($r) {
            return $r;
        }

        throw new Invalid('Expecting IP address in IPV6 or IPV4 format');
    }

    /**
     * Validate Url
     *
     * Check if the given string is a valid url
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function url($input, ValidationInfo $info = null)
    {
        $r = filter_var($input, FILTER_VALIDATE_URL);
        if ($r) {
            return $r;
        } elseif ($info && $info->fix) {
            $r = filter_var($input, FILTER_SANITIZE_URL);
            return static::url($r);
        }
        throw new Invalid('Expecting url in `http://example.com` format');
    }

    /**
     * MySQL Date
     *
     * Check if the given string is a valid date in YYYY-MM-DD format
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function date($input, ValidationInfo $info = null)
    {
        if (
            preg_match(
                '#^(?P<year>\d{2}|\d{4})-(?P<month>\d{1,2})-(?P<day>\d{1,2})$#',
                $input,
                $date
            )
            && checkdate($date['month'], $date['day'], $date['year'])
        ) {
            return $input;
        }
        throw new Invalid(
            'Expecting date in `YYYY-MM-DD` format, such as `'
            . date("Y-m-d") . '`'
        );
    }

    /**
     * MySQL DateTime
     *
     * Check if the given string is a valid date and time in YYY-MM-DD HH:MM:SS format
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function datetime($input, ValidationInfo $info = null)
    {
        if (
            preg_match('/^(?P<year>19\d\d|20\d\d)\-(?P<month>0[1-9]|1[0-2])\-' .
                '(?P<day>0\d|[1-2]\d|3[0-1]) (?P<h>0\d|1\d|2[0-3]' .
                ')\:(?P<i>[0-5][0-9])\:(?P<s>[0-5][0-9])$/',
                $input, $date)
            && checkdate($date['month'], $date['day'], $date['year'])
        ) {
            return $input;
        }
        throw new Invalid(
            'Expecting date and time in `YYYY-MM-DD HH:MM:SS` format, such as `'
            . date("Y-m-d H:i:s") . '`'
        );
    }

    /**
     * Alias for Time
     *
     * Check if the given string is a valid time in HH:MM:SS format
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function time24($input, ValidationInfo $info = null)
    {
        return static::time($input, $info);
    }

    /**
     * Time
     *
     * Check if the given string is a valid time in HH:MM:SS format
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function time($input, ValidationInfo $info = null)
    {
        if (preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]$/', $input)) {
            return $input;
        }
        throw new Invalid(
            'Expecting time in `HH:MM:SS` format, such as `'
            . date("H:i:s") . '`'
        );
    }

    /**
     * Time in 12 hour format
     *
     * Check if the given string is a valid time 12 hour format
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return string
     * @throws Invalid
     */
    public static function time12($input, ValidationInfo $info = null)
    {
        if (preg_match(
            '/^([1-9]|1[0-2]|0[1-9]){1}(:[0-5][0-9])?\s?([aApP][mM]{1})?$/',
            $input)
        ) {
            return $input;
        }
        throw new Invalid(
            'Expecting time in 12 hour format, such as `08:00AM` and `10:05:11`'
        );
    }

    /**
     * Unix Timestamp
     *
     * Check if the given value is a valid timestamp
     *
     * @param String         $input
     * @param ValidationInfo $info
     *
     * @return int
     * @throws Invalid
     */
    public static function timestamp($input, ValidationInfo $info = null)
    {
        if ((string)(int)$input == $input
            && ($input <= PHP_INT_MAX)
            && ($input >= ~PHP_INT_MAX)
        ) {
            return (int)$input;
        }
        throw new Invalid('Expecting unix timestamp, such as ' . time());
    }

    /**
     * Validate the given input
     *
     * Validates the input and attempts to fix it when fix is requested
     *
     * @param mixed          $input
     * @param ValidationInfo $info
     * @param null           $full
     *
     * @throws \Exception
     * @return array|bool|float|int|mixed|null|number|string
     */
    public static function validate($input, ValidationInfo $info, $full = null)
    {
        $html = Scope::get('Restler')->responseFormat instanceof HtmlFormat;
        $name = $html ? "<strong>$info->label</strong>" : "`$info->name`";
        if (
            isset(static::$preFilters['*']) &&
            is_scalar($input) &&
            is_callable($func = static::$preFilters['*'])
        ) {
            $input = $func($input);
        }
        if (
            isset(static::$preFilters[$info->type]) &&
            (is_scalar($input) || !empty($info->children)) &&
            is_callable($func = static::$preFilters[$info->type])
        ) {
            $input = $func($input);
        }
        try {
            if (is_null($input)) {
                if ($info->required) {
                    throw new RestException (400,
                        "$name is required.");
                }
                return null;
            }
            $error = isset ($info->message)
                ? $info->message
                : "Invalid value specified for $name";

            //if a validation method is specified
            if (!empty($info->method)) {
                $method = $info->method;
                $info->method = '';
                $r = self::validate($input, $info);
                return $info->apiClassInstance->{$method} ($r);
            }

            // when type is an array check if it passes for any type
            if (is_array($info->type)) {
                //trace("types are ".print_r($info->type, true));
                $types = $info->type;
                foreach ($types as $type) {
                    $info->type = $type;
                    try {
                        $r = self::validate($input, $info);
                        if ($r !== false) {
                            return $r;
                        }
                    } catch (RestException $e) {
                        // just continue
                    }
                }
                throw new RestException (400, $error);
            }

            //patterns are supported only for non numeric types
            if (isset ($info->pattern)
                && $info->type != 'int'
                && $info->type != 'float'
                && $info->type != 'number'
            ) {
                if (!preg_match($info->pattern, $input)) {
                    throw new RestException (400, $error);
                }
            }

            if (isset ($info->choice)) {
                if (!$info->required && empty($input)) {
                    //since its optional, and empty let it pass.
                    $input = null;
                } elseif (is_array($input)) {
                    foreach ($input as $i) {
                        if (!in_array($i, $info->choice)) {
                            $error .= ". Expected one of (" . implode(',', $info->choice) . ").";
                            throw new RestException (400, $error);
                        }
                    }
                } elseif (!in_array($input, $info->choice)) {
                    $error .= ". Expected one of (" . implode(',', $info->choice) . ").";
                    throw new RestException (400, $error);
                }
            }

            if (method_exists($class = get_called_class(), $info->type) && $info->type != 'validate') {
                if (!$info->required && empty($input)) {
                    //optional parameter with a empty value assume null
                    return null;
                }
                try {
                    return call_user_func("$class::$info->type", $input, $info);
                } catch (Invalid $e) {
                    throw new RestException(400, $error . '. ' . $e->getMessage());
                }
            }

            switch ($info->type) {
                case 'int' :
                case 'float' :
                case 'number' :
                    if (!is_numeric($input)) {
                        $error .= '. Expecting '
                            . ($info->type == 'int' ? 'integer' : 'numeric')
                            . ' value';
                        break;
                    }
                    if ($info->type == 'int' && (int)$input != $input) {
                        if ($info->fix) {
                            $r = (int)$input;
                        } else {
                            $error .= '. Expecting integer value';
                            break;
                        }
                    } else {
                        $r = $info->numericValue($input);
                    }
                    if (isset ($info->min) && $r < $info->min) {
                        if ($info->fix) {
                            $r = $info->min;
                        } else {
                            $error .= ". Minimum required value is $info->min.";
                            break;
                        }
                    }
                    if (isset ($info->max) && $r > $info->max) {
                        if ($info->fix) {
                            $r = $info->max;
                        } else {
                            $error .= ". Maximum allowed value is $info->max.";
                            break;
                        }
                    }
                    return $r;

                case 'string' :
                case 'password' : //password fields with string
                case 'search' : //search field with string
                    if (is_bool($input)) $input = $input ? 'true' : 'false';
                    if (!is_string($input)) {
                        $error .= '. Expecting alpha numeric value';
                        break;
                    }
                    if ($info->required && $input === '') {
                        $error = "$name is required.";
                        break;
                    }
                    $r = strlen($input);
                    if (isset ($info->min) && $r < $info->min) {
                        if ($info->fix) {
                            $input = str_pad($input, $info->min, $input);
                        } else {
                            $char = $info->min > 1 ? 'characters' : 'character';
                            $error .= ". Minimum $info->min $char required.";
                            break;
                        }
                    }
                    if (isset ($info->max) && $r > $info->max) {
                        if ($info->fix) {
                            $input = substr($input, 0, $info->max);
                        } else {
                            $char = $info->max > 1 ? 'characters' : 'character';
                            $error .= ". Maximum $info->max $char allowed.";
                            break;
                        }
                    }
                    return $input;

                case 'bool':
                case 'boolean':
                    if (is_bool($input)) {
                        return $input;
                    }
                    if (is_numeric($input)) {
                        if ($input == 1) {
                            return true;
                        }
                        if ($input == 0) {
                            return false;
                        }
                    } elseif (is_string($input)) {
                        switch (strtolower($input)) {
                            case 'true':
                                return true;
                            case 'false':
                                return false;
                        }
                    }
                    if ($info->fix) {
                        return $input ? true : false;
                    }
                    $error .= '. Expecting boolean value';
                    break;
                case 'array':
                    if ($info->fix && is_string($input)) {
                        $input = explode(CommentParser::$arrayDelimiter, $input);
                    }
                    if (is_array($input)) {
                        $contentType =
                            Util::nestedValue($info, 'contentType') ?: null;
                        if ($info->fix) {
                            if ($contentType == 'indexed') {
                                $input = $info->filterArray($input, true);
                            } elseif ($contentType == 'associative') {
                                $input = $info->filterArray($input, false);
                            }
                        } elseif (
                            $contentType == 'indexed' &&
                            array_values($input) != $input
                        ) {
                            $error .= '. Expecting a list of items but an item is given';
                            break;
                        } elseif (
                            $contentType == 'associative' &&
                            array_values($input) == $input &&
                            count($input)
                        ) {
                            $error .= '. Expecting an item but a list is given';
                            break;
                        }
                        $r = count($input);
                        if (isset ($info->min) && $r < $info->min) {
                            $item = $info->max > 1 ? 'items' : 'item';
                            $error .= ". Minimum $info->min $item required.";
                            break;
                        }
                        if (isset ($info->max) && $r > $info->max) {
                            if ($info->fix) {
                                $input = array_slice($input, 0, $info->max);
                            } else {
                                $item = $info->max > 1 ? 'items' : 'item';
                                $error .= ". Maximum $info->max $item allowed.";
                                break;
                            }
                        }
                        if (
                            isset($contentType) &&
                            $contentType != 'associative' &&
                            $contentType != 'indexed'
                        ) {
                            $name = $info->name;
                            $info->type = $contentType;
                            unset($info->contentType);
                            unset($info->min);
                            unset($info->max);
                            foreach ($input as $key => $chinput) {
                                $info->name = "{$name}[$key]";
                                $input[$key] = static::validate($chinput, $info);
                            }
                        }
                        return $input;
                    } elseif (isset($contentType)) {
                        $error .= '. Expecting items of type ' .
                            ($html ? "<strong>$contentType</strong>" : "`$contentType`");
                        break;
                    }
                    break;
                case 'mixed':
                case 'unknown_type':
                case 'unknown':
                case null: //treat as unknown
                    return $input;
                default :
                    if (!is_array($input)) {
                        break;
                    }
                    //do type conversion
                    if (class_exists($info->type)) {
                        $input = $info->filterArray($input, false);
                        $implements = class_implements($info->type);
                        if (
                            is_array($implements) &&
                            in_array('Luracast\\Restler\\Data\\iValueObject', $implements)
                        ) {
                            return call_user_func(
                                "{$info->type}::__set_state", $input
                            );
                        }
                        $class = $info->type;
                        $instance = new $class();
                        if (is_array($info->children)) {
                            if (
                                empty($input) ||
                                !is_array($input) ||
                                $input === array_values($input)
                            ) {
                                $error .= '. Expecting an item of type ' .
                                    ($html ? "<strong>$info->type</strong>" : "`$info->type`");
                                break;
                            }
                            foreach ($info->children as $key => $value) {
                                $cv = new ValidationInfo($value);
                                $cv->name = "{$info->name}[$key]";
                                if (array_key_exists($key, $input) || $cv->required) {
                                    $instance->{$key} = static::validate(
                                        Util::nestedValue($input, $key),
                                        $cv
                                    );
                                }
                            }
                        }
                        return $instance;
                    }
            }
            throw new RestException (400, $error);
        } catch (\Exception $e) {
            static::$exceptions[$info->name] = $e;
            if (static::$holdException) {
                return null;
            }
            throw $e;
        }
    }
}
