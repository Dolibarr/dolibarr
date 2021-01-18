<?php
namespace Luracast\Restler\Data;

/**
 * Convenience class that converts the given object
 * in to associative array
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
class Obj
{
    /**
     * @var bool|string|callable
     */
    public static $stringEncoderFunction = false;
    /**
     * @var bool|string|callable
     */
    public static $numberEncoderFunction = false;
    /**
     * @var array key value pairs for fixing value types using functions.
     * For example
     *
     *      'id'=>'intval'      will make sure all values of the id properties
     *                          will be converted to integers intval function
     *      'password'=> null   will remove all the password entries
     */
    public static $fix = array();
    /**
     * @var string character that is used to identify sub objects
     *
     * For example
     *
     * when Object::$separatorChar = '.';
     *
     * array('my.object'=>true) will result in
     *
     * array(
     *    'my'=>array('object'=>true)
     * );
     */
    public static $separatorChar = null;
    /**
     * @var bool set it to true when empty arrays, blank strings, null values
     * to be automatically removed from response
     */
    public static $removeEmpty = false;
    /**
     * @var bool set it to true to remove all null values from the result
     */
    public static $removeNull = false;

    /**
     * Convenience function that converts the given object
     * in to associative array
     *
     * @static
     *
     * @param mixed $object                          that needs to be converted
     *
     * @param bool  $forceObjectTypeWhenEmpty        when set to true outputs
     *                                               actual type  (array or
     *                                               object) rather than
     *                                               always an array when the
     *                                               array/object is empty
     *
     * @return array
     */
    public static function toArray($object,
                                   $forceObjectTypeWhenEmpty = false)
    {
        //if ($object instanceof JsonSerializable) { //wont work on PHP < 5.4
        if (is_object($object)) {
            if (method_exists($object, 'jsonSerialize')) {
                $object = $object->jsonSerialize();
            } elseif (method_exists($object, '__sleep')) {
                $properties = $object->__sleep();
                $array = array();
                foreach ($properties as $key) {
                    $value = self::toArray($object->{$key},
                        $forceObjectTypeWhenEmpty);
                    if (self::$stringEncoderFunction && is_string($value)) {
                        $value = self::$stringEncoderFunction ($value);
                    } elseif (self::$numberEncoderFunction && is_numeric($value)) {
                        $value = self::$numberEncoderFunction ($value);
                    }
                    $array [$key] = $value;
                }
                return $array;
            }
        }
        if (is_array($object) || is_object($object)) {
            $count = 0;
            $array = array();
            foreach ($object as $key => $value) {
                if (
                    is_string(self::$separatorChar) &&
                    false !== strpos($key, self::$separatorChar)
                ) {
                    list($key, $obj) = explode(self::$separatorChar, $key, 2);
                    $object[$key][$obj] = $value;
                    $value = $object[$key];
                }
                if (self::$removeEmpty && empty($value) && !is_numeric($value) && !is_bool($value)) {
                    continue;
                } elseif (self::$removeNull && is_null($value)) {
                    continue;
                }
                if (array_key_exists($key, self::$fix)) {
                    if (isset(self::$fix[$key])) {
                        $value = call_user_func(self::$fix[$key], $value);
                    } else {
                        continue;
                    }
                }
                $value = self::toArray($value, $forceObjectTypeWhenEmpty);
                if (self::$stringEncoderFunction && is_string($value)) {
                    $value = self::$encoderFunctionName ($value);
                } elseif (self::$numberEncoderFunction && is_numeric($value)) {
                    $value = self::$numberEncoderFunction ($value);
                }
                $array [$key] = $value;
                $count++;
            }
            return $forceObjectTypeWhenEmpty && $count == 0 ? $object : $array;
        }

        return $object;
    }

    public function __get($name)
    {
        isset(self::$fix[$name]) ? self::$fix[$name] : null;
    }

    public function __set($name, $function)
    {
        self::$fix[$name] = $function;
    }

    public function __isset($name)
    {
        return isset(self::$fix[$name]);
    }

    public function __unset($name)
    {
        unset(self::$fix[$name]);
    }
}

