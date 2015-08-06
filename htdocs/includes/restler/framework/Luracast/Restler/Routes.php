<?php
namespace Luracast\Restler;

use Luracast\Restler\Data\ApiMethodInfo;
use Luracast\Restler\Data\String;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Exception;

/**
 * Router class that routes the urls to api methods along with parameters
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc5
 */
class Routes
{
    public static $prefixingParameterNames = array(
        'id'
    );
    protected static $routes = array();

    protected static $models = array();

    /**
     * Route the public and protected methods of an Api class
     *
     * @param string $className
     * @param string $resourcePath
     * @param int    $version
     *
     * @throws RestException
     */
    public static function addAPIClass($className, $resourcePath = '', $version = 1)
    {

        /*
         * Mapping Rules
         * =============
         *
         * - Optional parameters should not be mapped to URL
         * - If a required parameter is of primitive type
         *      - If one of the self::$prefixingParameterNames
         *              - Map it to URL
         *      - Else If request method is POST/PUT/PATCH
         *              - Map it to body
         *      - Else If request method is GET/DELETE
         *              - Map it to body
         * - If a required parameter is not primitive type
         *      - Do not include it in URL
         */
        $class = new ReflectionClass($className);
        try {
            $classMetadata = CommentParser::parse($class->getDocComment());
        } catch (Exception $e) {
            throw new RestException(500, "Error while parsing comments of `$className` class. " . $e->getMessage());
        }
        $classMetadata['scope'] = $scope = static::scope($class);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC +
            ReflectionMethod::IS_PROTECTED);
        foreach ($methods as $method) {
            $methodUrl = strtolower($method->getName());
            //method name should not begin with _
            if ($methodUrl{0} == '_') {
                continue;
            }
            $doc = $method->getDocComment();

            try {
                $metadata = CommentParser::parse($doc) + $classMetadata;
            } catch (Exception $e) {
                throw new RestException(500, "Error while parsing comments of `{$className}::{$method->getName()}` method. " . $e->getMessage());
            }
            //@access should not be private
            if (isset($metadata['access'])
                && $metadata['access'] == 'private'
            ) {
                continue;
            }
            $arguments = array();
            $defaults = array();
            $params = $method->getParameters();
            $position = 0;
            $pathParams = array();
            $allowAmbiguity
                = (isset($metadata['smart-auto-routing'])
                    && $metadata['smart-auto-routing'] != 'true')
                || !Defaults::$smartAutoRouting;
            $metadata['resourcePath'] = $resourcePath;
            if (isset($classMetadata['description'])) {
                $metadata['classDescription'] = $classMetadata['description'];
            }
            if (isset($classMetadata['classLongDescription'])) {
                $metadata['classLongDescription']
                    = $classMetadata['longDescription'];
            }
            if (!isset($metadata['param'])) {
                $metadata['param'] = array();
            }
            if (isset($metadata['return']['type'])) {
                if ($qualified = Scope::resolve($metadata['return']['type'], $scope))
                    list($metadata['return']['type'], $metadata['return']['children']) =
                        static::getTypeAndModel(new ReflectionClass($qualified), $scope);
            } else {
                //assume return type is array
                $metadata['return']['type'] = 'array';
            }
            foreach ($params as $param) {
                $children = array();
                $type =
                    $param->isArray() ? 'array' : $param->getClass();
                $arguments[$param->getName()] = $position;
                $defaults[$position] = $param->isDefaultValueAvailable() ?
                    $param->getDefaultValue() : null;
                if (!isset($metadata['param'][$position])) {
                    $metadata['param'][$position] = array();
                }
                $m = & $metadata ['param'] [$position];
                $m ['name'] = $param->getName();
                if (empty($m['label']))
                    $m['label'] = static::label($m['name']);
                if (is_null($type) && isset($m['type'])) {
                    $type = $m['type'];
                }
                if ($m['name'] == 'email' && empty($m[CommentParser::$embeddedDataName]['type']) && $type == 'string')
                    $m[CommentParser::$embeddedDataName]['type'] = 'email';
                $m ['default'] = $defaults [$position];
                $m ['required'] = !$param->isOptional();
                $contentType = Util::nestedValue(
                    $m,
                    CommentParser::$embeddedDataName,
                    'type'
                );
                if ($contentType && $qualified = Scope::resolve($contentType, $scope)) {
                    list($m[CommentParser::$embeddedDataName]['type'], $children) = static::getTypeAndModel(
                        new ReflectionClass($qualified), $scope
                    );
                }
                if ($type instanceof ReflectionClass) {
                    list($type, $children) = static::getTypeAndModel($type, $scope);
                } elseif ($type && is_string($type) && $qualified = Scope::resolve($type, $scope)) {
                    list($type, $children)
                        = static::getTypeAndModel(new ReflectionClass($qualified), $scope);
                }
                if (isset($type)) {
                    $m['type'] = $type;
                }
                $m['children'] = $children;

                if ($m['name'] == Defaults::$fullRequestDataName) {
                    $from = 'body';
                    if (!isset($m['type'])) {
                        $type = $m['type'] = 'array';
                    }

                } elseif (isset($m[CommentParser::$embeddedDataName]['from'])) {
                    $from = $m[CommentParser::$embeddedDataName]['from'];
                } else {
                    if ((isset($type) && Util::isObjectOrArray($type))
                    ) {
                        $from = 'body';
                        if (!isset($type)) {
                            $type = $m['type'] = 'array';
                        }
                    } elseif ($m['required'] && in_array($m['name'], static::$prefixingParameterNames)) {
                        $from = 'path';
                    } else {
                        $from = 'body';
                    }
                }
                $m[CommentParser::$embeddedDataName]['from'] = $from;
                if (!isset($m['type'])) {
                    $type = $m['type'] = static::type($defaults[$position]);
                }

                if ($allowAmbiguity || $from == 'path') {
                    $pathParams [] = $position;
                }
                $position++;
            }
            $accessLevel = 0;
            if ($method->isProtected()) {
                $accessLevel = 3;
            } elseif (isset($metadata['access'])) {
                if ($metadata['access'] == 'protected') {
                    $accessLevel = 2;
                } elseif ($metadata['access'] == 'hybrid') {
                    $accessLevel = 1;
                }
            } elseif (isset($metadata['protected'])) {
                $accessLevel = 2;
            }
            /*
            echo " access level $accessLevel for $className::"
            .$method->getName().$method->isProtected().PHP_EOL;
            */

            // take note of the order
            $call = array(
                'url' => null,
                'className' => $className,
                'path' => rtrim($resourcePath, '/'),
                'methodName' => $method->getName(),
                'arguments' => $arguments,
                'defaults' => $defaults,
                'metadata' => $metadata,
                'accessLevel' => $accessLevel,
            );
            // if manual route
            if (preg_match_all(
                '/@url\s+(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)'
                . '[ \t]*\/?(\S*)/s',
                $doc, $matches, PREG_SET_ORDER
            )
            ) {
                foreach ($matches as $match) {
                    $httpMethod = $match[1];
                    $url = rtrim($resourcePath . $match[2], '/');
                    //deep copy the call, as it may change for each @url
                    $copy = unserialize(serialize($call));
                    foreach ($copy['metadata']['param'] as $i => $p) {
                        $inPath =
                            strpos($url, '{' . $p['name'] . '}') ||
                            strpos($url, ':' . $p['name']);
                        if ($inPath) {
                            $copy['metadata']['param'][$i][CommentParser::$embeddedDataName]['from'] = 'path';
                        } elseif ($httpMethod == 'GET' || $httpMethod == 'DELETE') {
                            $copy['metadata']['param'][$i][CommentParser::$embeddedDataName]['from'] = 'query';
                        } elseif ($p[CommentParser::$embeddedDataName]['from'] == 'path') {
                            $copy['metadata']['param'][$i][CommentParser::$embeddedDataName]['from'] = 'body';
                        }
                    }
                    $url = preg_replace_callback('/{[^}]+}|:[^\/]+/',
                        function ($matches) use ($call) {
                            $match = trim($matches[0], '{}:');
                            $index = $call['arguments'][$match];
                            return '{' .
                            Routes::typeChar(isset(
                            $call['metadata']['param'][$index]['type'])
                                ? $call['metadata']['param'][$index]['type']
                                : null)
                            . $index . '}';
                        }, $url);
                    static::addPath($url, $copy, $httpMethod, $version);
                }
                //if auto route enabled, do so
            } elseif (Defaults::$autoRoutingEnabled) {
                // no configuration found so use convention
                if (preg_match_all(
                    '/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)/i',
                    $methodUrl, $matches)
                ) {
                    $httpMethod = strtoupper($matches[0][0]);
                    $methodUrl = substr($methodUrl, strlen($httpMethod));
                } else {
                    $httpMethod = 'GET';
                }
                if ($methodUrl == 'index') {
                    $methodUrl = '';
                }
                $url = empty($methodUrl) ? rtrim($resourcePath, '/')
                    : $resourcePath . $methodUrl;
                $lastPathParam = array_keys($pathParams);
                $lastPathParam = end($lastPathParam);
                for ($position = 0; $position < count($params); $position++) {
                    $from = $metadata['param'][$position][CommentParser::$embeddedDataName]['from'];
                    if ($from == 'body' && ($httpMethod == 'GET' ||
                            $httpMethod == 'DELETE')
                    ) {
                        $call['metadata']['param'][$position][CommentParser::$embeddedDataName]['from']
                            = 'query';
                    }
                }
                if (empty($pathParams) || $allowAmbiguity) {
                    static::addPath($url, $call, $httpMethod, $version);
                }
                foreach ($pathParams as $position) {
                    if (!empty($url))
                        $url .= '/';
                    $url .= '{' .
                        static::typeChar(isset($call['metadata']['param'][$position]['type'])
                            ? $call['metadata']['param'][$position]['type']
                            : null)
                        . $position . '}';
                    if ($allowAmbiguity || $position == $lastPathParam) {
                        static::addPath($url, $call, $httpMethod, $version);
                    }
                }
            }
        }
    }

    /**
     * @access private
     */
    public static function typeChar($type = null)
    {
        if (!$type) {
            return 's';
        }
        switch ($type{0}) {
            case 'i':
            case 'f':
                return 'n';
        }
        return 's';
    }

    protected static function addPath($path, array $call,
                                      $httpMethod = 'GET', $version = 1)
    {
        $call['url'] = preg_replace_callback(
            "/\{\S(\d+)\}/",
            function ($matches) use ($call) {
                return '{' .
                $call['metadata']['param'][$matches[1]]['name'] . '}';
            },
            $path
        );
        //check for wildcard routes
        if (substr($path, -1, 1) == '*') {
            $path = rtrim($path, '/*');
            static::$routes["v$version"]['*'][$path][$httpMethod] = $call;
        } else {
            static::$routes["v$version"][$path][$httpMethod] = $call;
            //create an alias with index if the method name is index
            if ($call['methodName'] == 'index')
                static::$routes["v$version"][ltrim("$path/index", '/')][$httpMethod] = $call;
        }
    }

    /**
     * Find the api method for the given url and http method
     *
     * @param string $path       Requested url path
     * @param string $httpMethod GET|POST|PUT|PATCH|DELETE etc
     * @param int    $version    Api Version number
     * @param array  $data       Data collected from the request
     *
     * @throws RestException
     * @return ApiMethodInfo
     */
    public static function find($path, $httpMethod,
                                $version = 1, array $data = array())
    {
        $p = Util::nestedValue(static::$routes, "v$version");
        if (!$p) {
            throw new RestException(
                404,
                $version == 1 ? '' : "Version $version is not supported"
            );
        }
        $status = 404;
        $message = null;
        $methods = array();
        if (isset($p[$path][$httpMethod])) {
            //================== static routes ==========================
            return static::populate($p[$path][$httpMethod], $data);
        } elseif (isset($p['*'])) {
            //================== wildcard routes ========================
            uksort($p['*'], function ($a, $b) {
                return strlen($b) - strlen($a);
            });
            foreach ($p['*'] as $key => $value) {
                if (strpos($path, $key) === 0 && isset($value[$httpMethod])) {
                    //path found, convert rest of the path to parameters
                    $path = substr($path, strlen($key) + 1);
                    $call = ApiMethodInfo::__set_state($value[$httpMethod]);
                    $call->parameters = empty($path)
                        ? array()
                        : explode('/', $path);
                    return $call;
                }
            }
        }
        //================== dynamic routes =============================
        //add newline char if trailing slash is found
        if (substr($path, -1) == '/')
            $path .= PHP_EOL;
        //if double slash is found fill in newline char;
        $path = str_replace('//', '/' . PHP_EOL . '/', $path);
        ksort($p);
        foreach ($p as $key => $value) {
            if (!isset($value[$httpMethod])) {
                continue;
            }
            $regex = str_replace(array('{', '}'),
                array('(?P<', '>[^/]+)'), $key);
            if (preg_match_all(":^$regex$:i", $path, $matches, PREG_SET_ORDER)) {
                $matches = $matches[0];
                $found = true;
                foreach ($matches as $k => $v) {
                    if (is_numeric($k)) {
                        unset($matches[$k]);
                        continue;
                    }
                    $index = intval(substr($k, 1));
                    $details = $value[$httpMethod]['metadata']['param'][$index];
                    if ($k{0} == 's' || strpos($k, static::pathVarTypeOf($v)) === 0) {
                        //remove the newlines
                        $data[$details['name']] = trim($v, PHP_EOL);
                    } else {
                        $status = 400;
                        $message = 'invalid value specified for `'
                            . $details['name'] . '`';
                        $found = false;
                        break;
                    }
                }
                if ($found) {
                    return static::populate($value[$httpMethod], $data);
                }
            }
        }
        if ($status == 404) {
            //check if other methods are allowed
            if (isset($p[$path])) {
                $status = 405;
                $methods = array_keys($p[$path]);
            }
        }
        if ($status == 405) {
            header('Allow: ' . implode(', ', $methods));
        }
        throw new RestException($status, $message);
    }

    /**
     * Populates the parameter values
     *
     * @param array $call
     * @param       $data
     *
     * @return ApiMethodInfo
     *
     * @access private
     */
    protected static function populate(array $call, $data)
    {
        $call['parameters'] = $call['defaults'];
        $p = & $call['parameters'];
        foreach ($data as $key => $value) {
            if (isset($call['arguments'][$key])) {
                $p[$call['arguments'][$key]] = $value;
            }
        }
        if (Defaults::$smartParameterParsing && 'post' != (string)Util::$restler->requestFormat) {
            if (
                count($p) == 1 &&
                ($m = Util::nestedValue($call, 'metadata', 'param', 0)) &&
                !array_key_exists($m['name'], $data) &&
                array_key_exists(Defaults::$fullRequestDataName, $data) &&
                !is_null($d = $data[Defaults::$fullRequestDataName]) &&
                isset($m['type']) &&
                static::typeMatch($m['type'], $d)
            ) {
                $p[0] = $d;
            } else {
                $bodyParamCount = 0;
                $lastBodyParamIndex = -1;
                $lastM = null;
                foreach ($call['metadata']['param'] as $k => $m) {
                    if ($m[CommentParser::$embeddedDataName]['from'] == 'body') {
                        $bodyParamCount++;
                        $lastBodyParamIndex = $k;
                        $lastM = $m;
                    }
                }
                if (
                    $bodyParamCount == 1 &&
                    !array_key_exists($lastM['name'], $data) &&
                    array_key_exists(Defaults::$fullRequestDataName, $data) &&
                    !is_null($d = $data[Defaults::$fullRequestDataName])
                ) {
                    $p[$lastBodyParamIndex] = $d;
                }
            }
        }
        $r = ApiMethodInfo::__set_state($call);
        $modifier = "_modify_{$r->methodName}_api";
        if (method_exists($r->className, $modifier)) {
            $stage = end(Scope::get('Restler')->getEvents());
            if (empty($stage))
                $stage = 'setup';
            $r = Scope::get($r->className)->$modifier($r, $stage) ? : $r;
        }
        return $r;
    }

    /**
     * @access private
     */
    protected static function pathVarTypeOf($var)
    {
        if (is_numeric($var)) {
            return 'n';
        }
        if ($var === 'true' || $var === 'false') {
            return 'b';
        }
        return 's';
    }

    protected static function typeMatch($type, $var)
    {
        switch ($type) {
            case 'boolean':
            case 'bool':
                return is_bool($var);
            case 'array':
            case 'object':
                return is_array($var);
            case 'string':
            case 'int':
            case 'integer':
            case 'float':
            case 'number':
                return is_scalar($var);
        }
        return true;
    }

    /**
     * Get the type and associated model
     *
     * @param ReflectionClass $class
     * @param array           $scope
     *
     * @throws RestException
     * @throws \Exception
     * @return array
     *
     * @access protected
     */
    protected static function getTypeAndModel(ReflectionClass $class, array $scope)
    {
        $className = $class->getName();
        if (isset(static::$models[$className])) {
            return static::$models[$className];
        }
        $children = array();
        try {
            $props = $class->getProperties(ReflectionProperty::IS_PUBLIC);
            foreach ($props as $prop) {
                $name = $prop->getName();
                $child = array('name' => $name);
                if ($c = $prop->getDocComment()) {
                    $child += Util::nestedValue(CommentParser::parse($c), 'var') ?: array();
                } else {
                    $o = $class->newInstance();
                    $p = $prop->getValue($o);
                    if (is_object($p)) {
                        $child['type'] = get_class($p);
                    } elseif (is_array($p)) {
                        $child['type'] = 'array';
                        if (count($p)) {
                            $pc = reset($p);
                            if (is_object($pc)) {
                                $child['contentType'] = get_class($pc);
                            }
                        }
                    }
                }
                $child += array(
                    'type' => $child['name'] == 'email' ? 'email' : 'string',
                    'label' => static::label($child['name'])
                );
                isset($child[CommentParser::$embeddedDataName])
                    ? $child[CommentParser::$embeddedDataName] += array('required' => true)
                    : $child[CommentParser::$embeddedDataName]['required'] = true;
                if ($qualified = Scope::resolve($child['type'], $scope)) {
                    list($child['type'], $child['children'])
                        = static::getTypeAndModel(new ReflectionClass($qualified), $scope);
                } elseif (
                    ($contentType = Util::nestedValue($child, CommentParser::$embeddedDataName, 'type')) &&
                    ($qualified = Scope::resolve($contentType, $scope))
                ) {
                    list($child['contentType'], $child['children'])
                        = static::getTypeAndModel(new ReflectionClass($qualified), $scope);
                }
                $children[$name] = $child;
            }
        } catch (Exception $e) {
            if (String::endsWith($e->getFile(), 'CommentParser.php')) {
                throw new RestException(500, "Error while parsing comments of `$className` class. " . $e->getMessage());
            }
            throw $e;
        }
        static::$models[$className] = array($className, $children);
        return static::$models[$className];
    }

    /**
     * Import previously created routes from cache
     *
     * @param array $routes
     */
    public static function fromArray(array $routes)
    {
        static::$routes = $routes;
    }

    /**
     * Export current routes for cache
     *
     * @return array
     */
    public static function toArray()
    {
        return static::$routes;
    }

    public static function type($var)
    {
        if (is_object($var)) return get_class($var);
        if (is_array($var)) return 'array';
        if (is_bool($var)) return 'boolean';
        if (is_numeric($var)) return is_float($var) ? 'float' : 'int';
        return 'string';
    }

    /**
     * Create a label from name of the parameter or property
     *
     * Convert `camelCase` style names into proper `Title Case` names
     *
     * @param string $name
     *
     * @return string
     */
    public static function label($name)
    {
        return ucfirst(preg_replace(array('/(?<=[^A-Z])([A-Z])/', '/(?<=[^0-9])([0-9])/'), ' $0', $name));
    }

    public static function scope(ReflectionClass $class)
    {
        $namespace = $class->getNamespaceName();
        $imports = array(
            '*' => empty($namespace) ? '' : $namespace . '\\'
        );
        $file = file_get_contents($class->getFileName());
        $tokens = token_get_all($file);
        $namespace = '';
        $alias = '';
        $reading = false;
        $last = 0;
        foreach ($tokens as $token) {
            if (is_string($token)) {
                if ($reading && ',' == $token) {
                    //===== STOP =====//
                    $reading = false;
                    if (!empty($namespace))
                        $imports[$alias] = trim($namespace, '\\');
                    //===== START =====//
                    $reading = true;
                    $namespace = '';
                    $alias = '';
                } else {
                    //===== STOP =====//
                    $reading = false;
                    if (!empty($namespace))
                        $imports[$alias] = trim($namespace, '\\');
                }
            } elseif (T_USE == $token[0]) {
                //===== START =====//
                $reading = true;
                $namespace = '';
                $alias = '';
            } elseif ($reading) {
                //echo token_name($token[0]) . ' ' . $token[1] . PHP_EOL;
                switch ($token[0]) {
                    case T_WHITESPACE:
                        continue 2;
                    case T_STRING:
                        $alias = $token[1];
                        if (T_AS == $last) {
                            break;
                        }
                    //don't break;
                    case T_NS_SEPARATOR:
                        $namespace .= $token[1];
                        break;
                }
                $last = $token[0];
            }
        }
        return $imports;
    }
}