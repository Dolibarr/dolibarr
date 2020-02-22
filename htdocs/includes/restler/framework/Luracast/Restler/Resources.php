<?php
namespace Luracast\Restler;

use Luracast\Restler\Data\Text;
use Luracast\Restler\Scope;
use stdClass;

/**
 * API Class to create Swagger Spec 1.1 compatible id and operation
 * listing
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 */
class Resources implements iUseAuthentication, iProvideMultiVersionApi
{
    /**
     * @var bool should protected resources be shown to unauthenticated users?
     */
    public static $hideProtected = true;
    /**
     * @var bool should we use format as extension?
     */
    public static $useFormatAsExtension = true;
    /**
     * @var bool should we include newer apis in the list? works only when
     * Defaults::$useUrlBasedVersioning is set to true;
     */
    public static $listHigherVersions = true;
    /**
     * @var array all http methods specified here will be excluded from
     * documentation
     */
    public static $excludedHttpMethods = array('OPTIONS');
    /**
     * @var array all paths beginning with any of the following will be excluded
     * from documentation
     */
    public static $excludedPaths = array();
    /**
     * @var bool
     */
    public static $placeFormatExtensionBeforeDynamicParts = true;
    /**
     * @var bool should we group all the operations with the same url or not
     */
    public static $groupOperations = false;
    /**
     * @var null|callable if the api methods are under access control mechanism
     * you can attach a function here that returns true or false to determine
     * visibility of a protected api method. this function will receive method
     * info as the only parameter.
     */
    public static $accessControlFunction = null;
    /**
     * @var array type mapping for converting data types to javascript / swagger
     */
    public static $dataTypeAlias = array(
        'string' => 'string',
        'int' => 'int',
        'number' => 'float',
        'float' => 'float',
        'bool' => 'boolean',
        'boolean' => 'boolean',
        'NULL' => 'null',
        'array' => 'Array',
        'object' => 'Object',
        'stdClass' => 'Object',
        'mixed' => 'string',
        'DateTime' => 'Date'
    );
    /**
     * @var array configurable symbols to differentiate public, hybrid and
     * protected api
     */
    public static $apiDescriptionSuffixSymbols = array(
        0 => '&nbsp; <i class="icon-unlock-alt icon-large"></i>', //public api
        1 => '&nbsp; <i class="icon-adjust icon-large"></i>', //hybrid api
        2 => '&nbsp; <i class="icon-lock icon-large"></i>', //protected api
    );

    /**
     * Injected at runtime
     *
     * @var Restler instance of restler
     */
    public $restler;
    /**
     * @var string when format is not used as the extension this property is
     * used to set the extension manually
     */
    public $formatString = '';
    protected $_models;
    protected $_bodyParam;
    /**
     * @var bool|stdClass
     */
    protected $_fullDataRequested = false;
    protected $crud = array(
        'POST' => 'create',
        'GET' => 'retrieve',
        'PUT' => 'update',
        'DELETE' => 'delete',
        'PATCH' => 'partial update'
    );
    protected static $prefixes = array(
        'get' => 'retrieve',
        'index' => 'list',
        'post' => 'create',
        'put' => 'update',
        'patch' => 'modify',
        'delete' => 'remove',
    );
    protected $_authenticated = false;
    protected $cacheName = '';

    public function __construct()
    {
        if (static::$useFormatAsExtension) {
            $this->formatString = '.{format}';
        }
    }

    /**
     * This method will be called first for filter classes and api classes so
     * that they can respond accordingly for filer method call and api method
     * calls
     *
     *
     * @param bool $isAuthenticated passes true when the authentication is
     *                              done, false otherwise
     *
     * @return mixed
     */
    public function __setAuthenticationStatus($isAuthenticated = false)
    {
        $this->_authenticated = $isAuthenticated;
    }

    /**
     * pre call for get($id)
     *
     * if cache is present, use cache
     */
    public function _pre_get_json($id)
    {
        $userClass = Defaults::$userIdentifierClass;
        $this->cacheName = $userClass::getCacheIdentifier() . '_resources_' . $id;
        if ($this->restler->getProductionMode()
            && !$this->restler->refreshCache
            && $this->restler->cache->isCached($this->cacheName)
        ) {
            //by pass call, compose, postCall stages and directly send response
            $this->restler->composeHeaders();
            die($this->restler->cache->get($this->cacheName));
        }
    }

    /**
     * post call for get($id)
     *
     * create cache if in production mode
     *
     * @param $responseData
     *
     * @internal param string $data composed json output
     *
     * @return string
     */
    public function _post_get_json($responseData)
    {
        if ($this->restler->getProductionMode()) {
            $this->restler->cache->set($this->cacheName, $responseData);
        }
        return $responseData;
    }

    /**
     * @access hybrid
     *
     * @param string $id
     *
     * @throws RestException
     * @return null|stdClass
     *
     * @url    GET {id}
     */
    public function get($id = '')
    {
        $version = $this->restler->getRequestedApiVersion();
        if (empty($id)) {
            //do nothing
        } elseif (false !== ($pos = strpos($id, '-v'))) {
            //$version = intval(substr($id, $pos + 2));
            $id = substr($id, 0, $pos);
        } elseif ($id[0] == 'v' && is_numeric($v = substr($id, 1))) {
            $id = '';
            //$version = $v;
        } elseif ($id == 'root' || $id == 'index') {
            $id = '';
        }
        $this->_models = new stdClass();
        $r = null;
        $count = 0;

        $tSlash = !empty($id);
        $target = empty($id) ? '' : $id;
        $tLen = strlen($target);

        $filter = array();

        $routes
            = Util::nestedValue(Routes::toArray(), "v$version")
            ? : array();

        $prefix = Defaults::$useUrlBasedVersioning ? "/v$version" : '';

        foreach ($routes as $value) {
            foreach ($value as $httpMethod => $route) {
                if (in_array($httpMethod, static::$excludedHttpMethods)) {
                    continue;
                }
                $fullPath = $route['url'];
                if ($fullPath !== $target && !Text::beginsWith($fullPath, $target)) {
                    continue;
                }
                $fLen = strlen($fullPath);
                if ($tSlash) {
                    if ($fLen != $tLen && !Text::beginsWith($fullPath, $target . '/'))
                        continue;
                } elseif ($fLen > $tLen + 1 && $fullPath[$tLen + 1] != '{' && !Text::beginsWith($fullPath, '{')) {
                    //when mapped to root exclude paths that have static parts
                    //they are listed else where under that static part name
                    continue;
                }

                if (!static::verifyAccess($route)) {
                    continue;
                }
                foreach (static::$excludedPaths as $exclude) {
                    if (empty($exclude)) {
                        if ($fullPath == $exclude)
                            continue 2;
                    } elseif (Text::beginsWith($fullPath, $exclude)) {
                        continue 2;
                    }
                }
                $m = $route['metadata'];
                if ($id == '' && $m['resourcePath'] != '') {
                    continue;
                }
                if (isset($filter[$httpMethod][$fullPath])) {
                    continue;
                }
                $filter[$httpMethod][$fullPath] = true;
                // reset body params
                $this->_bodyParam = array(
                    'required' => false,
                    'description' => array()
                );
                $count++;
                $className = $this->_noNamespace($route['className']);
                if (!$r) {
                    $resourcePath = '/'
                        . trim($m['resourcePath'], '/');
                    $r = $this->_operationListing($resourcePath);
                }
                $parts = explode('/', $fullPath);
                $pos = count($parts) - 1;
                if (count($parts) == 1 && $httpMethod == 'GET') {
                } else {
                    for ($i = 0; $i < count($parts); $i++) {
                        if (strlen($parts[$i]) && $parts[$i][0] == '{') {
                            $pos = $i - 1;
                            break;
                        }
                    }
                }
                $nickname = $this->_nickname($route);
                $index = static::$placeFormatExtensionBeforeDynamicParts && $pos > 0 ? $pos : 0;
                if (!empty($parts[$index]))
                    $parts[$index] .= $this->formatString;

                $fullPath = implode('/', $parts);
                $description = isset(
                $m['classDescription'])
                    ? $m['classDescription']
                    : $className . ' API';
                if (empty($m['description'])) {
                    $m['description'] = $this->restler->getProductionMode()
                        ? ''
                        : 'routes to <mark>'
                        . $route['className']
                        . '::'
                        . $route['methodName'] . '();</mark>';
                }
                if (empty($m['longDescription'])) {
                    $m['longDescription'] = $this->restler->getProductionMode()
                        ? ''
                        : 'Add PHPDoc long description to '
                        . "<mark>$className::"
                        . $route['methodName'] . '();</mark>'
                        . '  (the api method) to write here';
                }
                $operation = $this->_operation(
                    $route,
                    $nickname,
                    $httpMethod,
                    $m['description'],
                    $m['longDescription']
                );
                if (isset($m['throws'])) {
                    foreach ($m['throws'] as $exception) {
                        $operation->errorResponses[] = array(
                            'reason' => $exception['message'],
                            'code' => $exception['code']);
                    }
                }
                if (isset($m['param'])) {
                    foreach ($m['param'] as $param) {
                        //combine body params as one
                        $p = $this->_parameter($param);
                        if ($p->paramType == 'body') {
                            $this->_appendToBody($p);
                        } else {
                            $operation->parameters[] = $p;
                        }
                    }
                }
                if (
                    count($this->_bodyParam['description']) ||
                    (
                        $this->_fullDataRequested &&
                        $httpMethod != 'GET' &&
                        $httpMethod != 'DELETE'
                    )
                ) {
                    $operation->parameters[] = $this->_getBody();
                }
                if (isset($m['return']['type'])) {
                    $responseClass = $m['return']['type'];
                    if (is_string($responseClass)) {
                        if (class_exists($responseClass)) {
                            $this->_model($responseClass);
                            $operation->responseClass
                                = $this->_noNamespace($responseClass);
                        } elseif (strtolower($responseClass) == 'array') {
                            $operation->responseClass = 'Array';
                            $rt = $m['return'];
                            if (isset(
                            $rt[CommentParser::$embeddedDataName]['type'])
                            ) {
                                $rt = $rt[CommentParser::$embeddedDataName]
                                ['type'];
                                if (class_exists($rt)) {
                                    $this->_model($rt);
                                    $operation->responseClass .= '[' .
                                        $this->_noNamespace($rt) . ']';
                                }
                            }
                        }
                    }
                }
                $api = false;

                if (static::$groupOperations) {
                    foreach ($r->apis as $a) {
                        if ($a->path == "$prefix/$fullPath") {
                            $api = $a;
                            break;
                        }
                    }
                }

                if (!$api) {
                    $api = $this->_api("$prefix/$fullPath", $description);
                    $r->apis[] = $api;
                }

                $api->operations[] = $operation;
            }
        }
        if (!$count) {
            throw new RestException(404);
        }
        if (!is_null($r))
            $r->models = $this->_models;
        usort(
            $r->apis,
            function ($a, $b) {
                $order = array(
                    'GET' => 1,
                    'POST' => 2,
                    'PUT' => 3,
                    'PATCH' => 4,
                    'DELETE' => 5
                );
                return
                    $a->operations[0]->httpMethod ==
                    $b->operations[0]->httpMethod
                        ? $a->path > $b->path
                        : $order[$a->operations[0]->httpMethod] >
                        $order[$b->operations[0]->httpMethod];

            }
        );
        return $r;
    }

    protected function _nickname(array $route)
    {
        static $hash = array();
        $method = $route['methodName'];
        if (isset(static::$prefixes[$method])) {
            $method = static::$prefixes[$method];
        } else {
            $method = str_replace(
                array_keys(static::$prefixes),
                array_values(static::$prefixes),
                $method
            );
        }
        while (isset($hash[$method]) && $route['url'] != $hash[$method]) {
            //create another one
            $method .= '_';
        }
        $hash[$method] = $route['url'];
        return $method;
    }

    protected function _noNamespace($className)
    {
        $className = explode('\\', $className);
        return end($className);
    }

    protected function _operationListing($resourcePath = '/')
    {
        $r = $this->_resourceListing();
        $r->resourcePath = $resourcePath;
        $r->models = new stdClass();
        return $r;
    }

    protected function _resourceListing()
    {
        $r = new stdClass();
        $r->apiVersion = (string)$this->restler->_requestedApiVersion;
        $r->swaggerVersion = "1.1";
        $r->basePath = $this->restler->getBaseUrl();
        $r->produces = $this->restler->getWritableMimeTypes();
        $r->consumes = $this->restler->getReadableMimeTypes();
        $r->apis = array();
        return $r;
    }

    protected function _api($path, $description = '')
    {
        $r = new stdClass();
        $r->path = $path;
        $r->description =
            empty($description) && $this->restler->getProductionMode()
                ? 'Use PHPDoc comment to describe here'
                : $description;
        $r->operations = array();
        return $r;
    }

    protected function _operation(
        $route,
        $nickname,
        $httpMethod = 'GET',
        $summary = 'description',
        $notes = 'long description',
        $responseClass = 'void'
    )
    {
        //reset body params
        $this->_bodyParam = array(
            'required' => false,
            'description' => array()
        );

        $r = new stdClass();
        $r->httpMethod = $httpMethod;
        $r->nickname = $nickname;
        $r->responseClass = $responseClass;

        $r->parameters = array();

        $r->summary = $summary . ($route['accessLevel'] > 2
                ? static::$apiDescriptionSuffixSymbols[2]
                : static::$apiDescriptionSuffixSymbols[$route['accessLevel']]
            );
        $r->notes = $notes;

        $r->errorResponses = array();
        return $r;
    }

    protected function _parameter($param)
    {
        $r = new stdClass();
        $r->name = $param['name'];
        $r->description = !empty($param['description'])
            ? $param['description'] . '.'
            : ($this->restler->getProductionMode()
                ? ''
                : 'add <mark>@param {type} $' . $r->name
                . ' {comment}</mark> to describe here');
        //paramType can be path or query or body or header
        $r->paramType = Util::nestedValue($param, CommentParser::$embeddedDataName, 'from') ? : 'query';
        $r->required = isset($param['required']) && $param['required'];
        if (isset($param['default'])) {
            $r->defaultValue = $param['default'];
        } elseif (isset($param[CommentParser::$embeddedDataName]['example'])) {
            $r->defaultValue
                = $param[CommentParser::$embeddedDataName]['example'];
        }
        $r->allowMultiple = false;
        $type = 'string';
        if (isset($param['type'])) {
            $type = $param['type'];
            if (is_array($type)) {
                $type = array_shift($type);
            }
            if ($type == 'array') {
                $contentType = Util::nestedValue(
                    $param,
                    CommentParser::$embeddedDataName,
                    'type'
                );
                if ($contentType) {
                    if ($contentType == 'indexed') {
                        $type = 'Array';
                    } elseif ($contentType == 'associative') {
                        $type = 'Object';
                    } else {
                        $type = "Array[$contentType]";
                    }
                    if (Util::isObjectOrArray($contentType)) {
                        $this->_model($contentType);
                    }
                } elseif (isset(static::$dataTypeAlias[$type])) {
                    $type = static::$dataTypeAlias[$type];
                }
            } elseif (Util::isObjectOrArray($type)) {
                $this->_model($type);
            } elseif (isset(static::$dataTypeAlias[$type])) {
                $type = static::$dataTypeAlias[$type];
            }
        }
        $r->dataType = $type;
        if (isset($param[CommentParser::$embeddedDataName])) {
            $p = $param[CommentParser::$embeddedDataName];
            if (isset($p['min']) && isset($p['max'])) {
                $r->allowableValues = array(
                    'valueType' => 'RANGE',
                    'min' => $p['min'],
                    'max' => $p['max'],
                );
            } elseif (isset($p['choice'])) {
                $r->allowableValues = array(
                    'valueType' => 'LIST',
                    'values' => $p['choice']
                );
            }
        }
        return $r;
    }

    protected function _appendToBody($p)
    {
        if ($p->name === Defaults::$fullRequestDataName) {
            $this->_fullDataRequested = $p;
            unset($this->_bodyParam['names'][Defaults::$fullRequestDataName]);
            return;
        }
        $this->_bodyParam['description'][$p->name]
            = "$p->name"
            . ' : <tag>' . $p->dataType . '</tag> '
            . ($p->required ? ' <i>(required)</i> - ' : ' - ')
            . $p->description;
        $this->_bodyParam['required'] = $p->required
            || $this->_bodyParam['required'];
        $this->_bodyParam['names'][$p->name] = $p;
    }

    protected function _getBody()
    {
        $r = new stdClass();
        $n = isset($this->_bodyParam['names'])
            ? array_values($this->_bodyParam['names'])
            : array();
        if (count($n) == 1) {
            if (isset($this->_models->{$n[0]->dataType})) {
                // ============ custom class ===================
                $r = $n[0];
                $c = $this->_models->{$r->dataType};
                $a = $c->properties;
                $r->description = "Paste JSON data here";
                if (count($a)) {
                    $r->description .= " with the following"
                        . (count($a) > 1 ? ' properties.' : ' property.');
                    foreach ($a as $k => $v) {
                        $r->description .= "<hr/>$k : <tag>"
                            . $v['type'] . '</tag> '
                            . (isset($v['required']) ? '(required)' : '')
                            . ' - ' . $v['description'];
                    }
                }
                $r->defaultValue = "{\n    \""
                    . implode("\": \"\",\n    \"",
                        array_keys($c->properties))
                    . "\": \"\"\n}";
                return $r;
            } elseif (false !== ($p = strpos($n[0]->dataType, '['))) {
                // ============ array of custom class ===============
                $r = $n[0];
                $t = substr($r->dataType, $p + 1, -1);
                if ($c = Util::nestedValue($this->_models, $t)) {
                    $a = $c->properties;
                    $r->description = "Paste JSON data here";
                    if (count($a)) {
                        $r->description .= " with an array of objects with the following"
                            . (count($a) > 1 ? ' properties.' : ' property.');
                        foreach ($a as $k => $v) {
                            $r->description .= "<hr/>$k : <tag>"
                                . $v['type'] . '</tag> '
                                . (isset($v['required']) ? '(required)' : '')
                                . ' - ' . $v['description'];
                        }
                    }
                    $r->defaultValue = "[\n    {\n        \""
                        . implode("\": \"\",\n        \"",
                            array_keys($c->properties))
                        . "\": \"\"\n    }\n]";
                    return $r;
                } else {
                    $r->description = "Paste JSON data here with an array of $t values.";
                    $r->defaultValue = "[ ]";
                    return $r;
                }
            } elseif ($n[0]->dataType == 'Array') {
                // ============ array ===============================
                $r = $n[0];
                $r->description = "Paste JSON array data here"
                    . ($r->required ? ' (required) . ' : '. ')
                    . "<br/>$r->description";
                $r->defaultValue = "[\n    {\n        \""
                    . "property\" : \"\"\n    }\n]";
                return $r;
            } elseif ($n[0]->dataType == 'Object') {
                // ============ object ==============================
                $r = $n[0];
                $r->description = "Paste JSON object data here"
                    . ($r->required ? ' (required) . ' : '. ')
                    . "<br/>$r->description";
                $r->defaultValue = "{\n    \""
                    . "property\" : \"\"\n}";
                return $r;
            }
        }
        $p = array_values($this->_bodyParam['description']);
        $r->name = 'REQUEST_BODY';
        $r->description = "Paste JSON data here";
        if (count($p) == 0 && $this->_fullDataRequested) {
            $r->required = $this->_fullDataRequested->required;
            $r->defaultValue = "{\n    \"property\" : \"\"\n}";
        } else {
            $r->description .= " with the following"
                . (count($p) > 1 ? ' properties.' : ' property.')
                . '<hr/>'
                . implode("<hr/>", $p);
            $r->required = $this->_bodyParam['required'];
            // Create default object that includes parameters to be submitted
            $defaultObject = new \StdClass();
            foreach ($this->_bodyParam['names'] as $name => $values) {
                if (!$values->required)
                    continue;
                if (class_exists($values->dataType)) {
                    $myClassName = $values->dataType;
                    $defaultObject->$name = new $myClassName();
                } else {
                    $defaultObject->$name = '';
                }
            }
            $r->defaultValue = Scope::get('JsonFormat')->encode($defaultObject, true);
        }
        $r->paramType = 'body';
        $r->allowMultiple = false;
        $r->dataType = 'Object';
        return $r;
    }

    protected function _model($className, $instance = null)
    {
        $id = $this->_noNamespace($className);
        if (isset($this->_models->{$id})) {
            return;
        }
        $properties = array();
        if (!$instance) {
            if (!class_exists($className))
                return;
            $instance = new $className();
        }
        $data = get_object_vars($instance);
        $reflectionClass = new \ReflectionClass($className);
        foreach ($data as $key => $value) {

            $propertyMetaData = null;

            try {
                $property = $reflectionClass->getProperty($key);
                if ($c = $property->getDocComment()) {
                    $propertyMetaData = Util::nestedValue(
                        CommentParser::parse($c),
                        'var'
                    );
                }
            } catch (\ReflectionException $e) {
            }

            if (is_null($propertyMetaData)) {
                $type = $this->getType($value, true);
                $description = '';
            } else {
                $type = Util::nestedValue(
                    $propertyMetaData,
                    'type'
                ) ? : $this->getType($value, true);
                $description = Util::nestedValue(
                    $propertyMetaData,
                    'description'
                ) ? : '';

                if (class_exists($type)) {
                    $this->_model($type);
                    $type = $this->_noNamespace($type);
                }
            }

            if (isset(static::$dataTypeAlias[$type])) {
                $type = static::$dataTypeAlias[$type];
            }
            $properties[$key] = array(
                'type' => $type,
                'description' => $description
            );
            if (Util::nestedValue(
                $propertyMetaData,
                CommentParser::$embeddedDataName,
                'required'
            )
            ) {
                $properties[$key]['required'] = true;
            }
            if ($type == 'Array') {
                $itemType = Util::nestedValue(
                    $propertyMetaData,
                    CommentParser::$embeddedDataName,
                    'type'
                ) ? :
                    (count($value)
                        ? $this->getType(end($value), true)
                        : 'string');
                if (class_exists($itemType)) {
                    $this->_model($itemType);
                    $itemType = $this->_noNamespace($itemType);
                }
                $properties[$key]['items'] = array(
                    'type' => $itemType,
                    /*'description' => '' */ //TODO: add description
                );
            } else if (preg_match('/^Array\[(.+)\]$/', $type, $matches)) {
                $itemType = $matches[1];
                $properties[$key]['type'] = 'Array';
                $properties[$key]['items']['type'] = $this->_noNamespace($itemType);

                if (class_exists($itemType)) {
                    $this->_model($itemType);
                }
            }
        }
        if (!empty($properties)) {
            $model = new stdClass();
            $model->id = $id;
            $model->properties = $properties;
            $this->_models->{$id} = $model;
        }
    }

    /**
     * Find the data type of the given value.
     *
     *
     * @param mixed $o given value for finding type
     *
     * @param bool $appendToModels if an object is found should we append to
     *                              our models list?
     *
     * @return string
     *
     * @access private
     */
    public function getType($o, $appendToModels = false)
    {
        if (is_object($o)) {
            $oc = get_class($o);
            if ($appendToModels) {
                $this->_model($oc, $o);
            }
            return $this->_noNamespace($oc);
        }
        if (is_array($o)) {
            if (count($o)) {
                $child = end($o);
                if (Util::isObjectOrArray($child)) {
                    $childType = $this->getType($child, $appendToModels);
                    return "Array[$childType]";
                }
            }
            return 'array';
        }
        if (is_bool($o)) return 'boolean';
        if (is_numeric($o)) return is_float($o) ? 'float' : 'int';
        return 'string';
    }

    /**
     * pre call for index()
     *
     * if cache is present, use cache
     */
    public function _pre_index_json()
    {
        $userClass = Defaults::$userIdentifierClass;
        $this->cacheName = $userClass::getCacheIdentifier()
            . '_resources-v'
            . $this->restler->_requestedApiVersion;
        if ($this->restler->getProductionMode()
            && !$this->restler->refreshCache
            && $this->restler->cache->isCached($this->cacheName)
        ) {
            //by pass call, compose, postCall stages and directly send response
            $this->restler->composeHeaders();
            die($this->restler->cache->get($this->cacheName));
        }
    }

    /**
     * post call for index()
     *
     * create cache if in production mode
     *
     * @param $responseData
     *
     * @internal param string $data composed json output
     *
     * @return string
     */
    public function _post_index_json($responseData)
    {
        if ($this->restler->getProductionMode()) {
            $this->restler->cache->set($this->cacheName, $responseData);
        }
        return $responseData;
    }

    /**
     * @access hybrid
     * @return \stdClass
     */
    public function index()
    {
        if (!static::$accessControlFunction && Defaults::$accessControlFunction)
            static::$accessControlFunction = Defaults::$accessControlFunction;
        $version = $this->restler->getRequestedApiVersion();
        $allRoutes = Util::nestedValue(Routes::toArray(), "v$version");
        $r = $this->_resourceListing();
        $map = array();
        if (isset($allRoutes['*'])) {
            $this->_mapResources($allRoutes['*'], $map, $version);
            unset($allRoutes['*']);
        }
        $this->_mapResources($allRoutes, $map, $version);
        foreach ($map as $path => $description) {
            if (!Text::contains($path, '{')) {
                //add id
                $r->apis[] = array(
                    'path' => $path . $this->formatString,
                    'description' => $description
                );
            }
        }
        if (Defaults::$useUrlBasedVersioning && static::$listHigherVersions) {
            $nextVersion = $version + 1;
            if ($nextVersion <= $this->restler->getApiVersion()) {
                list($status, $data) = $this->_loadResource("/v$nextVersion/resources.json");
                if ($status == 200) {
                    $r->apis = array_merge($r->apis, $data->apis);
                    $r->apiVersion = $data->apiVersion;
                }
            }

        }
        return $r;
    }

    protected function _loadResource($url)
    {
        $ch = curl_init($this->restler->getBaseUrl() . $url
            . (empty($_GET) ? '' : '?' . http_build_query($_GET)));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept:application/json',
        ));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);        
        $result = json_decode(curl_exec($ch));
        $http_status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        return array($http_status, $result);
    }

    protected function _mapResources(array $allRoutes, array &$map, $version = 1)
    {
        foreach ($allRoutes as $fullPath => $routes) {
            $path = explode('/', $fullPath);
            $resource = isset($path[0]) ? $path[0] : '';
            if ($resource == 'resources' || Text::endsWith($resource, 'index'))
                continue;
            foreach ($routes as $httpMethod => $route) {
                if (in_array($httpMethod, static::$excludedHttpMethods)) {
                    continue;
                }
                if (!static::verifyAccess($route)) {
                    continue;
                }

                foreach (static::$excludedPaths as $exclude) {
                    if (empty($exclude)) {
                        if ($fullPath == $exclude)
                            continue 2;
                    } elseif (Text::beginsWith($fullPath, $exclude)) {
                        continue 2;
                    }
                }

                $res = $resource
                    ? ($version == 1 ? "/resources/$resource" : "/v$version/resources/$resource-v$version")
                    : ($version == 1 ? "/resources/root" : "/v$version/resources/root-v$version");

                if (empty($map[$res])) {
                    $map[$res] = isset(
                    $route['metadata']['classDescription'])
                        ? $route['metadata']['classDescription'] : '';
                }
            }
        }
    }

    /**
     * Maximum api version supported by the api class
     * @return int
     */
    public static function __getMaximumSupportedVersion()
    {
        return Scope::get('Restler')->getApiVersion();
    }

    /**
     * Verifies that the requesting user is allowed to view the docs for this API
     *
     * @param $route
     *
     * @return boolean True if the user should be able to view this API's docs
     */
    protected function verifyAccess($route)
    {
        if ($route['accessLevel'] < 2) {
            return true;
        }
        if (
            static::$hideProtected
            && !$this->_authenticated
            && $route['accessLevel'] > 1
        ) {
            return false;
        }
        if ($this->_authenticated
            && static::$accessControlFunction
            && (!call_user_func(
                static::$accessControlFunction, $route['metadata']))
        ) {
            return false;
        }
        return true;
    }
}
