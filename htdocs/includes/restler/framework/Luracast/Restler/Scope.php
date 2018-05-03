<?php
namespace Luracast\Restler;

/**
 * Scope resolution class, manages instantiation and acts as a dependency
 * injection container
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class Scope
{
    public static $classAliases = array(

        //Core
        'Restler'            => 'Luracast\Restler\Restler',

        //Format classes
        'AmfFormat'          => 'Luracast\Restler\Format\AmfFormat',
        'JsFormat'           => 'Luracast\Restler\Format\JsFormat',
        'JsonFormat'         => 'Luracast\Restler\Format\JsonFormat',
        'HtmlFormat'         => 'Luracast\Restler\Format\HtmlFormat',
        'PlistFormat'        => 'Luracast\Restler\Format\PlistFormat',
        'UploadFormat'       => 'Luracast\Restler\Format\UploadFormat',
        'UrlEncodedFormat'   => 'Luracast\Restler\Format\UrlEncodedFormat',
        'XmlFormat'          => 'Luracast\Restler\Format\XmlFormat',
        'YamlFormat'         => 'Luracast\Restler\Format\YamlFormat',
        'CsvFormat'          => 'Luracast\Restler\Format\CsvFormat',
        'TsvFormat'          => 'Luracast\Restler\Format\TsvFormat',

        //Filter classes
        'RateLimit'          => 'Luracast\Restler\Filter\RateLimit',

        //UI classes
        'Forms'              => 'Luracast\Restler\UI\Forms',
        'Nav'                => 'Luracast\Restler\UI\Nav',
        'Emmet'              => 'Luracast\Restler\UI\Emmet',
        'T'                  => 'Luracast\Restler\UI\Tags',

        //API classes
        'Resources'          => 'Luracast\Restler\Resources',
        'Explorer'           => 'Luracast\Restler\Explorer',

        //Cache classes
        'HumanReadableCache' => 'Luracast\Restler\HumanReadableCache',
        'ApcCache'           => 'Luracast\Restler\ApcCache',
        'MemcacheCache'      => 'Luracast\Restler\MemcacheCache',

        //Utility classes
        'Obj'                => 'Luracast\Restler\Data\Obj',
        'Text'               => 'Luracast\Restler\Data\Text',
        'Arr'                => 'Luracast\Restler\Data\Arr',

        //Exception
        'RestException'      => 'Luracast\Restler\RestException'
    );
    /**
     * @var null|Callable adding a resolver function that accepts
     * the class name as the parameter and returns an instance of the class
     * as a singleton. Allows the use of your favourite DI container
     */
    public static $resolver = null;
    public static $properties = array();
    protected static $instances = array();
    protected static $registry = array();

    /**
     * @param string   $name
     * @param callable $function
     * @param bool     $singleton
     */
    public static function register($name, $function, $singleton = true)
    {
        static::$registry[$name] = (object)compact('function', 'singleton');
    }

    public static function set($name, $instance)
    {
        static::$instances[$name] = (object)array('instance' => $instance);
    }

    public static function get($name)
    {
        $r = null;
        $initialized = false;
        $properties = array();
        if (array_key_exists($name, static::$instances)) {
            $initialized = true;
            $r = static::$instances[$name]->instance;
        } elseif (!empty(static::$registry[$name])) {
            $function = static::$registry[$name]->function;
            $r = $function();
            if (static::$registry[$name]->singleton) {
                static::$instances[$name] = (object)array('instance' => $r);
            }
        } elseif (is_callable(static::$resolver) && false === stristr($name, 'Luracast\Restler')) {
            $fullName = $name;
            if (isset(static::$classAliases[$name])) {
                $fullName = static::$classAliases[$name];
            }
            /** @var Callable $function */
            $function = static::$resolver;
            $r = $function($fullName);
            static::$instances[$name] = (object)array('instance' => $r);
            static::$instances[$name]->initPending = true;
        } else {
            $fullName = $name;
            if (isset(static::$classAliases[$name])) {
                $fullName = static::$classAliases[$name];
            }
            if (class_exists($fullName)) {
                $shortName = Util::getShortName($name);
                $r = new $fullName();
                static::$instances[$name] = (object)array('instance' => $r);
                if ($name != 'Restler') {
                    $r->restler = static::get('Restler');
                    $m = Util::nestedValue($r->restler, 'apiMethodInfo', 'metadata');
                    if ($m) {
                        $properties = Util::nestedValue(
                            $m, 'class', $fullName,
                            CommentParser::$embeddedDataName
                        ) ?: (Util::nestedValue(
                            $m, 'class', $shortName,
                            CommentParser::$embeddedDataName
                        ) ?: array());
                    } else {
                        static::$instances[$name]->initPending = true;
                    }
                }
            }
        }
        if (
            $r instanceof iUseAuthentication &&
            static::get('Restler')->_authVerified &&
            !isset(static::$instances[$name]->authVerified)
        ) {
            static::$instances[$name]->authVerified = true;
            $r->__setAuthenticationStatus
            (static::get('Restler')->_authenticated);
        }
        if (isset(static::$instances[$name]->initPending)) {
            $m = Util::nestedValue(static::get('Restler'), 'apiMethodInfo', 'metadata');
            $fullName = $name;
            if (class_exists($name)) {
                $shortName = Util::getShortName($name);
            } else {
                $shortName = $name;
                if (isset(static::$classAliases[$name])) {
                    $fullName = static::$classAliases[$name];
                }
            }
            if ($m) {
                $properties = Util::nestedValue(
                    $m, 'class', $fullName,
                    CommentParser::$embeddedDataName
                ) ?: (Util::nestedValue(
                    $m, 'class', $shortName,
                    CommentParser::$embeddedDataName
                ) ?: array());
                unset(static::$instances[$name]->initPending);
                $initialized = false;
            }
        }
        if (!$initialized && is_object($r)) {
            $properties += static::$properties;
            $objectVars = get_object_vars($r);
            $className = get_class($r);
            foreach ($properties as $property => $value) {
                if (property_exists($className, $property)) {
                    //if not a static property
                    array_key_exists($property, $objectVars)
                        ? $r->{$property} = $value
                        : $r::$$property = $value;
                }
            }
        }
        return $r;
    }

    /**
     * Get fully qualified class name for the given scope
     *
     * @param string $className
     * @param array  $scope local scope
     *
     * @return string|boolean returns the class name or false
     */
    public static function resolve($className, array $scope)
    {
        if (empty($className) || !is_string($className))
            return false;

        if (self::isPrimitiveDataType($className)) {
            return false;
        }

        $divider = '\\';
        $qualified = false;
        if ($className{0} == $divider) {
            $qualified = trim($className, $divider);
        } elseif (array_key_exists($className, $scope)) {
            $qualified = $scope[$className];
        } else {
            $qualified = $scope['*'] . $className;
        }
        if (class_exists($qualified))
            return $qualified;
        if (isset(static::$classAliases[$className])) {
            $qualified = static::$classAliases[$className];
            if (class_exists($qualified))
                return $qualified;
        }
        return false;
    }

    /**
     * @param string $stringName
     * @return boolean
     */
    private static function isPrimitiveDataType($stringName)
    {
        $primitiveDataTypes = array('Array', 'array', 'bool', 'boolean', 'float', 'int', 'integer', 'string');
        return in_array($stringName, $primitiveDataTypes);
    }
}
