<?php
namespace Luracast\Restler;

use stdClass;
use Luracast\Restler\Data\Text;
use Luracast\Restler\Data\ValidationInfo;
use Luracast\Restler\Scope;

/**
 * Class Explorer
 *
 * @package Luracast\Restler
 *
 * @access  hybrid
 * @version 3.0.0rc6
 */
class Explorer implements iProvideMultiVersionApi
{
    const SWAGGER = '2.0';

    /**
     * @var array http schemes supported. http or https or both http and https
     */
    public static $schemes = array();
    /**
     * @var bool should protected resources be shown to unauthenticated users?
     */
    public static $hideProtected = true;
    /**
     * @var bool should we use format as extension?
     */
    public static $useFormatAsExtension = true;
    /*
     * @var bool can we accept scalar values (string, int, float etc) as the request body?
     */
    public static $allowScalarValueOnRequestBody = false;
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
     * @var string class that holds metadata as static properties
     */
    public static $infoClass = 'Luracast\Restler\ExplorerInfo';
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

    /**
     * @var array type mapping for converting data types to JSON-Schema Draft 4
     * Which is followed by swagger 1.2 spec
     */
    public static $dataTypeAlias = array(
        //'string' => 'string',
        'int'      => 'integer',
        'number'   => 'number',
        'float'    => array('number', 'float'),
        'bool'     => 'boolean',
        //'boolean' => 'boolean',
        //'NULL' => 'null',
        'array'    => 'array',
        //'object' => 'object',
        'stdClass' => 'object',
        'mixed'    => 'string',
        'date'     => array('string', 'date'),
        'datetime' => array('string', 'date-time'),
    );

    /**
     * @var array configurable symbols to differentiate public, hybrid and
     * protected api
     */
    public static $apiDescriptionSuffixSymbols = array(
        0 => ' 🔓', //'&nbsp; <i class="fa fa-lg fa-unlock-alt"></i>', //public api
        1 => ' ◑', //'&nbsp; <i class="fa fa-lg fa-adjust"></i>', //hybrid api
        2 => ' 🔐', //'&nbsp; <i class="fa fa-lg fa-lock"></i>', //protected api
    );

    protected $models = array();
    /**
     * @var bool|stdClass
     */
    protected $_fullDataRequested = false;
    protected $crud = array(
        'POST'   => 'create',
        'GET'    => 'retrieve',
        'PUT'    => 'update',
        'DELETE' => 'delete',
        'PATCH'  => 'partial update'
    );
    protected static $prefixes = array(
        'get'    => 'retrieve',
        'index'  => 'list',
        'post'   => 'create',
        'put'    => 'update',
        'patch'  => 'modify',
        'delete' => 'remove',
    );
    protected $_authenticated = false;
    protected $cacheName = '';


    /**
     * Serve static files for exploring
     *
     * Serves explorer html, css, and js files
     *
     * @url GET *
     */
    public function get()
    {
        if (func_num_args() > 1 && func_get_arg(0) == 'swagger') {
            /**
             * BUGFIX:
             * If we use common resourcePath (e.g. $r->addAPIClass([api-class], 'api/shop')), than we must determine resource-ID of e.g. 'api/shop'!
             */
            $arguments = func_get_args();
            // remove first entry
            array_shift($arguments);
            // create ID
            $id = implode('/', $arguments);

            return $this->getResources($id);
        }
        $filename = implode('/', func_get_args());
        $redirect = false;
        if (
            (empty($filename) && substr($_SERVER['REQUEST_URI'], -1, 1) != '/') ||
            $filename == 'index.html'
        ) {
            $status = 302;
            $url = $this->restler->getBaseUrl() . '/' . $this->base() . '/';
            header("{$_SERVER['SERVER_PROTOCOL']} $status " . RestException::$codes[$status]);
            header("Location: $url");
            exit;
        }
        if (
            isset($this->restler->responseFormat) &&
            $this->restler->responseFormat->getExtension() == 'js'
        ) {
            $filename .= '.js';
        }
        PassThrough::file(__DIR__ . '/explorer/' . (empty($filename) ? 'index.html' : $filename), false,
            0); //60 * 60 * 24);
    }

    /**
     * @return stdClass
     */
    public function swagger()
    {
        $r = new stdClass();
        $version = (string)$this->restler->getRequestedApiVersion();
        $r->swagger = static::SWAGGER;

        $info = parse_url($this->restler->getBaseUrl());
        $r->host = $info['host'];
        if (isset($info['port'])) {
            $r->host .= ':' . $info['port'];
        }
        $r->basePath = isset($info['path']) ?  $info['path'] : '';
        if (!empty(static::$schemes)) {
            $r->schemes = static::$schemes;
        }

        $r->produces = $this->restler->getWritableMimeTypes();
        $r->consumes = $this->restler->getReadableMimeTypes();

        $r->paths = $this->paths($version);
        $r->definitions = (object)$this->models;
        $r->securityDefinitions = $this->securityDefinitions();
        $r->info = compact('version') + array_filter(get_class_vars(static::$infoClass));

        return $r;
    }

    private function paths($version = 1)
    {
        $map = Routes::findAll(static::$excludedPaths + array($this->base()), static::$excludedHttpMethods, $version);
        $paths = array();
        foreach ($map as $path => $data) {
            $access = $data[0]['access'];
            if (static::$hideProtected && !$access) {
                continue;
            }
            foreach ($data as $item) {
                $route = $item['route'];
                $access = $item['access'];
                if (static::$hideProtected && !$access) {
                    continue;
                }
                $url = $route['url'];
                $paths["/$url"][strtolower($route['httpMethod'])] = $this->operation($route);
            }
        }

        return $paths;
    }

    private function operation($route)
    {
        $r = new stdClass();
        $m = $route['metadata'];
        $r->operationId = $this->operationId($route);
        $base = strtok($route['url'], '/');
        if (empty($base)) {
            $base = 'root';
        }
        $r->tags = array($base);
        $r->parameters = $this->parameters($route);


        $r->summary = isset($m['description'])
            ? $m['description']
            : '';
        $r->summary .= $route['accessLevel'] > 2
            ? static::$apiDescriptionSuffixSymbols[2]
            : static::$apiDescriptionSuffixSymbols[$route['accessLevel']];
        $r->description = isset($m['longDescription'])
            ? $m['longDescription']
            : '';
        $r->responses = $this->responses($route);
        //TODO: avoid hard coding. Properly detect security
        if ($route['accessLevel']) {
            $r->security = array(array('api_key' => array()));
        }
        /*
        $this->setType(
            $r,
            new ValidationInfo(Util::nestedValue($m, 'return') ?: array())
        );
        if (is_null($r->type) || 'mixed' == $r->type) {
            $r->type = 'array';
        } elseif ($r->type == 'null') {
            $r->type = 'void';
        } elseif (Text::contains($r->type, '|')) {
            $r->type = 'array';
        }
        */
        //TODO: add $r->authorizations
        //A list of authorizations required to execute this operation. While not mandatory, if used, it overrides
        //the value given at the API Declaration's authorizations. In order to completely remove API Declaration's
        //authorizations completely, an empty object ({}) may be applied.
        //TODO: add $r->produces
        //TODO: add $r->consumes
        //A list of MIME types this operation can produce/consume. This is overrides the global produces definition at the root of the API Declaration. Each string value SHOULD represent a MIME type.
        //TODO: add $r->deprecated
        //Declares this operation to be deprecated. Usage of the declared operation should be refrained. Valid value MUST be either "true" or "false". Note: This field will change to type boolean in the future.
        return $r;
    }

    private function parameters(array $route)
    {
        $r = array();
        $children = array();
        $required = false;
        foreach ($route['metadata']['param'] as $param) {
            $info = new ValidationInfo($param);
            $description = isset($param['description']) ? $param['description'] : '';
            if ('body' == $info->from) {
                if ($info->required) {
                    $required = true;
                }
                $param['description'] = $description;
                $children[] = $param;
            } else {
                $r[] = $this->parameter($info, $description);
            }
        }
        if (!empty($children)) {
            if (
                1 == count($children) &&
                (static::$allowScalarValueOnRequestBody || !empty($children[0]['children']))
            ) {
                $firstChild = $children[0];
                if (empty($firstChild['children'])) {
                    $description = $firstChild['description'];
                } else {
                    $description = ''; //'<section class="body-param">';
                    foreach ($firstChild['children'] as $child) {
                        $description .= isset($child['required']) && $child['required']
                            ? '**' . $child['name'] . '** (required)  '.PHP_EOL
                            : $child['name'] . '  '.PHP_EOL;
                    }
                    //$description .= '</section>';
                }
                $r[] = $this->parameter(new ValidationInfo($firstChild), $description);
            } else {
                $description = ''; //'<section class="body-param">';
                foreach ($children as $child) {
                    $description .= isset($child['required']) && $child['required']
                        ? '**' . $child['name'] . '** (required)  '.PHP_EOL
                        : $child['name'] . '  '.PHP_EOL;
                }
                //$description .= '</section>';

                //lets group all body parameters under a generated model name
                $name = $this->modelName($route);
                $r[] = $this->parameter(
                    new ValidationInfo(array(
                        'name'     => $name,
                        'type'     => $name,
                        'from'     => 'body',
                        'required' => $required,
                        'children' => $children
                    )),
                    $description
                );
            }
        }

        return $r;
    }

    private function parameter(ValidationInfo $info, $description = '')
    {
        $p = new stdClass();
        if (isset($info->rules['model'])) {
            //$info->type = $info->rules['model'];
        }
        $p->name = $info->name;
        $this->setType($p, $info);
        if (empty($info->children) || $info->type != 'array') {
            //primitives
            if ($info->default) {
                $p->defaultValue = $info->default;
            }
            if ($info->choice) {
                $p->enum = $info->choice;
            }
            if ($info->min) {
                $p->minimum = $info->min;
            }
            if ($info->max) {
                $p->maximum = $info->max;
            }
            //TODO: $p->items and $p->uniqueItems boolean
        }
        $p->description = $description;
        $p->in = $info->from; //$info->from == 'body' ? 'form' : $info->from;
        $p->required = $info->required;

        //$p->allowMultiple = false;

        if (isset($p->{'$ref'})) {
            $p->schema = (object)array('$ref' => ($p->{'$ref'}));
            unset($p->{'$ref'});
        }

        return $p;
    }

    private function responses(array $route)
    {
        $code = '200';
        $r = array(
            $code => (object)array(
                'description' => 'Success',
                'schema'      => new stdClass()
            )
        );
        $return = Util::nestedValue($route, 'metadata', 'return');
        if (!empty($return)) {
            $this->setType($r[$code]->schema, new ValidationInfo($return));
        }

        if (is_array($throws = Util::nestedValue($route, 'metadata', 'throws'))) {
            foreach ($throws as $message) {
                $r[$message['code']] = array('description' => $message['message']);
            }
        }

        return $r;
    }

    private function model($type, array $children)
    {
        if (isset($this->models[$type])) {
            return $this->models[$type];
        }
        $r = new stdClass();
        $r->properties = array();
        $required = array();
        foreach ($children as $child) {
            $info = new ValidationInfo($child);
            $p = new stdClass();
            $this->setType($p, $info);
            $p->description = isset($child['description']) ? $child['description'] : '';
            if ($info->default) {
                $p->defaultValue = $info->default;
            }
            if ($info->choice) {
                $p->enum = $info->choice;
            }
            if ($info->min) {
                $p->minimum = $info->min;
            }
            if ($info->max) {
                $p->maximum = $info->max;
            }
            if ($info->required) {
                $required[] = $info->name;
            }
            $r->properties[$info->name] = $p;
        }
        if (!empty($required)) {
            $r->required = $required;
        }
        //TODO: add $r->subTypes https://github.com/wordnik/swagger-spec/blob/master/versions/1.2.md#527-model-object
        //TODO: add $r->discriminator https://github.com/wordnik/swagger-spec/blob/master/versions/1.2.md#527-model-object
        $this->models[$type] = $r;

        return $r;
    }

    private function setType(&$object, ValidationInfo $info)
    {
        //TODO: proper type management
        $type = Util::getShortName($info->type);
        if ($info->type == 'array') {
            $object->type = 'array';
            if ($info->children) {
                $contentType = Util::getShortName($info->contentType);
                $model = $this->model($contentType, $info->children);
                $object->items = (object)array(
                    '$ref' => "#/definitions/$contentType"
                );
            } elseif ($info->contentType && $info->contentType == 'associative') {
                unset($info->contentType);
                $this->model($info->type = 'Object', array(
                    array(
                        'name'        => 'property',
                        'type'        => 'string',
                        'default'     => '',
                        'required'    => false,
                        'description' => ''
                    )
                ));
            } elseif ($info->contentType && $info->contentType != 'indexed') {
                if (is_string($info->contentType) && $t = Util::nestedValue(static::$dataTypeAlias,
                        strtolower($info->contentType))) {
                    if (is_array($t)) {
                        $object->items = (object)array(
                            'type'   => $t[0],
                            'format' => $t[1],
                        );
                    } else {
                        $object->items = (object)array(
                            'type' => $t,
                        );
                    }
                } else {
                    $contentType = Util::getShortName($info->contentType);
                    $object->items = (object)array(
                        '$ref' => "#/definitions/$contentType"
                    );
                }
            } else {
                $object->items = (object)array(
                    'type' => 'string'
                );
            }
        } elseif ($info->children) {
            $this->model($type, $info->children);
            $object->{'$ref'} = "#/definitions/$type";
        } elseif (is_string($info->type) && $t = Util::nestedValue(static::$dataTypeAlias, strtolower($info->type))) {
            if (is_array($t)) {
                $object->type = $t[0];
                $object->format = $t[1];
            } else {
                $object->type = $t;
            }
        } else {
            $object->type = 'string';
        }
        $has64bit = PHP_INT_MAX > 2147483647;
        if (isset($object->type)) {
            if ($object->type == 'integer') {
                $object->format = $has64bit
                    ? 'int64'
                    : 'int32';
            } elseif ($object->type == 'number') {
                $object->format = $has64bit
                    ? 'double'
                    : 'float';
            }
        }
    }

    private function operationId(array $route)
    {
        static $hash = array();
        $id = $route['httpMethod'] . ' ' . $route['url'];
        if (isset($hash[$id])) {
            return $hash[$id];
        }
        $class = Util::getShortName($route['className']);
        $method = $route['methodName'];

        if (isset(static::$prefixes[$method])) {
            $method = static::$prefixes[$method] . $class;
        } else {
            $method = str_replace(
                array_keys(static::$prefixes),
                array_values(static::$prefixes),
                $method
            );
            $method = lcfirst($class) . ucfirst($method);
        }
        $hash[$id] = $method;

        return $method;
    }

    private function modelName(array $route)
    {
        return $this->operationId($route) . 'Model';
    }

    private function securityDefinitions()
    {
        $r = new stdClass();
        $r->api_key = (object)array(
            'type' => 'apiKey',
            'name' => 'api_key',
            'in'   => 'query',
        );

        return $r;
    }

    private function base()
    {
        return strtok($this->restler->url, '/');
    }

    /**
     * Maximum api version supported by the api class
     * @return int
     */
    public static function __getMaximumSupportedVersion()
    {
        return Scope::get('Restler')->getApiVersion();
    }
}