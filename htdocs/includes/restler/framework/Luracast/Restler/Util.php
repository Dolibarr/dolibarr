<?php
namespace Luracast\Restler;
/**
 * Describe the purpose of this class/interface/trait
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class Util
{
    /**
     * @var Restler instance injected at runtime
     */
    public static $restler;

    /**
     * verify if the given data type string is scalar or not
     *
     * @static
     *
     * @param string $type data type as string
     *
     * @return bool true or false
     */
    public static function isObjectOrArray($type)
    {
        if (is_array($type)) {
            foreach ($type as $t) {
                if (static::isObjectOrArray($t)) {
                    return true;
                }
            }
            return false;
        }
        return !(boolean)strpos('|bool|boolean|int|float|string|', $type);
    }

    /**
     * Get the value deeply nested inside an array / object
     *
     * Using isset() to test the presence of nested value can give a false positive
     *
     * This method serves that need
     *
     * When the deeply nested property is found its value is returned, otherwise
     * false is returned.
     *
     * @param array $from array to extract the value from
     * @param string|array $key ... pass more to go deeply inside the array
     *                              alternatively you can pass a single array
     *
     * @return null|mixed null when not found, value otherwise
     */
    public static function nestedValue($from, $key/**, $key2 ... $key`n` */)
    {
        if (is_array($key)) {
            $keys = $key;
        } else {
            $keys = func_get_args();
            array_shift($keys);
        }
        foreach ($keys as $key) {
            if (is_array($from) && isset($from[$key])) {
                $from = $from[$key];
                continue;
            } elseif (is_object($from) && isset($from->{$key})) {
                $from = $from->{$key};
                continue;
            }
            return null;
        }
        return $from;
    }

    public static function getResourcePath($className,
                                           $resourcePath = null,
                                           $prefix = '')
    {
        if (is_null($resourcePath)) {
            if (Defaults::$autoRoutingEnabled) {
                $resourcePath = strtolower($className);
                if (false !== ($index = strrpos($className, '\\')))
                    $resourcePath = substr($resourcePath, $index + 1);
                if (false !== ($index = strrpos($resourcePath, '_')))
                    $resourcePath = substr($resourcePath, $index + 1);
            } else {
                $resourcePath = '';
            }
        } else
            $resourcePath = trim($resourcePath, '/');
        if (strlen($resourcePath) > 0)
            $resourcePath .= '/';
        return $prefix . $resourcePath;
    }

    /**
     * Compare two strings and remove the common
     * sub string from the first string and return it
     *
     * @static
     *
     * @param string $fromPath
     * @param string $usingPath
     * @param string $char
     *            optional, set it as
     *            blank string for char by char comparison
     *
     * @return string
     */
    public static function removeCommonPath($fromPath, $usingPath, $char = '/')
    {
        if (empty($fromPath))
            return '';
        $fromPath = explode($char, $fromPath);
        $usingPath = explode($char, $usingPath);
        while (count($usingPath)) {
            if (count($fromPath) && $fromPath[0] == $usingPath[0]) {
                array_shift($fromPath);
            } else {
                break;
            }
            array_shift($usingPath);
        }
        return implode($char, $fromPath);
    }

    /**
     * Compare two strings and split the common
     * sub string from the first string and return it as array
     *
     * @static
     *
     * @param string $fromPath
     * @param string $usingPath
     * @param string $char
     *            optional, set it as
     *            blank string for char by char comparison
     *
     * @return array with 2 strings first is the common string and second is the remaining in $fromPath
     */
    public static function splitCommonPath($fromPath, $usingPath, $char = '/')
    {
        if (empty($fromPath))
            return array('', '');
        $fromPath = explode($char, $fromPath);
        $usingPath = explode($char, $usingPath);
        $commonPath = array();
        while (count($usingPath)) {
            if (count($fromPath) && $fromPath[0] == $usingPath[0]) {
                $commonPath [] = array_shift($fromPath);
            } else {
                break;
            }
            array_shift($usingPath);
        }
        return array(implode($char, $commonPath), implode($char, $fromPath));
    }

    /**
     * Parses the request to figure out the http request type
     *
     * @static
     *
     * @return string which will be one of the following
     *        [GET, POST, PUT, PATCH, DELETE]
     * @example GET
     */
    public static function getRequestMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        if (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        } elseif (
            !empty(Defaults::$httpMethodOverrideProperty)
            && isset($_REQUEST[Defaults::$httpMethodOverrideProperty])
        ) {
            // support for exceptional clients who can't set the header
            $m = strtoupper($_REQUEST[Defaults::$httpMethodOverrideProperty]);
            if ($m == 'PUT' || $m == 'DELETE' ||
                $m == 'POST' || $m == 'PATCH'
            ) {
                $method = $m;
            }
        }
        // support for HEAD request
        if ($method == 'HEAD') {
            $method = 'GET';
        }
        return $method;
    }

    /**
     * Pass any content negotiation header such as Accept,
     * Accept-Language to break it up and sort the resulting array by
     * the order of negotiation.
     *
     * @static
     *
     * @param string $accept header value
     *
     * @return array sorted by the priority
     */
    public static function sortByPriority($accept)
    {
        $acceptList = array();
        $accepts = explode(',', strtolower($accept));
        if (!is_array($accepts)) {
            $accepts = array($accepts);
        }
        foreach ($accepts as $pos => $accept) {
            $parts = explode(';q=', trim($accept));
            $type = array_shift($parts);
            $quality = count($parts) ?
                floatval(array_shift($parts)) :
                (1000 - $pos) / 1000;
            $acceptList[$type] = $quality;
        }
        arsort($acceptList);
        return $acceptList;
    }

    public static function getShortName($className)
    {
    	// @CHANGE LDR
    	if (! is_string($className)) return '';
    	//var_dump($className);
    	
    	$className = explode('\\', $className);
        return end($className);
    }
}

