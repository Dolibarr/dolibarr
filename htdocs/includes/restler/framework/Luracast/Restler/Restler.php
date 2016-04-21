<?php
namespace Luracast\Restler;

use Exception;
use InvalidArgumentException;
use Luracast\Restler\Data\ApiMethodInfo;
use Luracast\Restler\Data\ValidationInfo;
use Luracast\Restler\Data\Validator;
use Luracast\Restler\Format\iFormat;
use Luracast\Restler\Format\iDecodeStream;
use Luracast\Restler\Format\UrlEncodedFormat;

/**
 * REST API Server. It is the server part of the Restler framework.
 * inspired by the RestServer code from
 * <http://jacwright.com/blog/resources/RestServer.txt>
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 * @version    3.0.0rc6
 *
 * @method static void onGet() onGet(Callable $function) fired before reading the request details
 * @method static void onRoute() onRoute(Callable $function) fired before finding the api method
 * @method static void onNegotiate() onNegotiate(Callable $function) fired before content negotiation
 * @method static void onPreAuthFilter() onPreAuthFilter(Callable $function) fired before pre auth filtering
 * @method static void onAuthenticate() onAuthenticate(Callable $function) fired before auth
 * @method static void onPostAuthFilter() onPostAuthFilter(Callable $function) fired before post auth filtering
 * @method static void onValidate() onValidate(Callable $function) fired before validation
 * @method static void onCall() onCall(Callable $function) fired before api method call
 * @method static void onCompose() onCompose(Callable $function) fired before composing response
 * @method static void onRespond() onRespond(Callable $function) fired before sending response
 * @method static void onComplete() onComplete(Callable $function) fired after sending response
 * @method static void onMessage() onMessage(Callable $function) fired before composing error response
 *
 * @method void onGet() onGet(Callable $function) fired before reading the request details
 * @method void onRoute() onRoute(Callable $function) fired before finding the api method
 * @method void onNegotiate() onNegotiate(Callable $function) fired before content negotiation
 * @method void onPreAuthFilter() onPreAuthFilter(Callable $function) fired before pre auth filtering
 * @method void onAuthenticate() onAuthenticate(Callable $function) fired before auth
 * @method void onPostAuthFilter() onPostAuthFilter(Callable $function) fired before post auth filtering
 * @method void onValidate() onValidate(Callable $function) fired before validation
 * @method void onCall() onCall(Callable $function) fired before api method call
 * @method void onCompose() onCompose(Callable $function) fired before composing response
 * @method void onRespond() onRespond(Callable $function) fired before sending response
 * @method void onComplete() onComplete(Callable $function) fired after sending response
 * @method void onMessage() onMessage(Callable $function) fired before composing error response
 */
class Restler extends EventDispatcher
{
    const VERSION = '3.0.0rc6';

    // ==================================================================
    //
    // Public variables
    //
    // ------------------------------------------------------------------
    /**
     * Reference to the last exception thrown
     * @var RestException
     */
    public $exception = null;
    /**
     * Used in production mode to store the routes and more
     *
     * @var iCache
     */
    public $cache;
    /**
     * URL of the currently mapped service
     *
     * @var string
     */
    public $url;
    /**
     * Http request method of the current request.
     * Any value between [GET, PUT, POST, DELETE]
     *
     * @var string
     */
    public $requestMethod;
    /**
     * Requested data format.
     * Instance of the current format class
     * which implements the iFormat interface
     *
     * @var iFormat
     * @example jsonFormat, xmlFormat, yamlFormat etc
     */
    public $requestFormat;
    /**
     * Response data format.
     *
     * Instance of the current format class
     * which implements the iFormat interface
     *
     * @var iFormat
     * @example jsonFormat, xmlFormat, yamlFormat etc
     */
    public $responseFormat;
    /**
     * Http status code
     *
     * @var int|null when specified it will override @status comment
     */
    public $responseCode=null;
    /**
     * @var string base url of the api service
     */
    protected $baseUrl;
    /**
     * @var bool Used for waiting till verifying @format
     *           before throwing content negotiation failed
     */
    protected $requestFormatDiffered = false;
    /**
     * method information including metadata
     *
     * @var ApiMethodInfo
     */
    public $apiMethodInfo;
    /**
     * @var int for calculating execution time
     */
    protected $startTime;
    /**
     * When set to false, it will run in debug mode and parse the
     * class files every time to map it to the URL
     *
     * @var boolean
     */
    protected $productionMode = false;
    public $refreshCache = false;
    /**
     * Caching of url map is enabled or not
     *
     * @var boolean
     */
    protected $cached;
    /**
     * @var int
     */
    protected $apiVersion = 1;
    /**
     * @var int
     */
    protected $requestedApiVersion = 1;
    /**
     * @var int
     */
    protected $apiMinimumVersion = 1;
    /**
     * @var array
     */
    protected $apiVersionMap = array();
    /**
     * Associated array that maps formats to their respective format class name
     *
     * @var array
     */
    protected $formatMap = array();
    /**
     * List of the Mime Types that can be produced as a response by this API
     *
     * @var array
     */
    protected $writableMimeTypes = array();
    /**
     * List of the Mime Types that are supported for incoming requests by this API
     *
     * @var array
     */
    protected $readableMimeTypes = array();
    /**
     * Associated array that maps formats to their respective format class name
     *
     * @var array
     */
    protected $formatOverridesMap = array('extensions' => array());
    /**
     * list of filter classes
     *
     * @var array
     */
    protected $filterClasses = array();
    /**
     * instances of filter classes that are executed after authentication
     *
     * @var array
     */
    protected $postAuthFilterClasses = array();


    // ==================================================================
    //
    // Protected variables
    //
    // ------------------------------------------------------------------

    /**
     * Data sent to the service
     *
     * @var array
     */
    protected $requestData = array();
    /**
     * list of authentication classes
     *
     * @var array
     */
    protected $authClasses = array();
    /**
     * list of error handling classes
     *
     * @var array
     */
    protected $errorClasses = array();
    protected $authenticated = false;
    protected $authVerified = false;
    /**
     * @var mixed
     */
    protected $responseData;

    /**
     * Constructor
     *
     * @param boolean $productionMode    When set to false, it will run in
     *                                   debug mode and parse the class files
     *                                   every time to map it to the URL
     *
     * @param bool    $refreshCache      will update the cache when set to true
     */
    public function __construct($productionMode = false, $refreshCache = false)
    {
        parent::__construct();
        $this->startTime = time();
        Util::$restler = $this;
        Scope::set('Restler', $this);
        $this->productionMode = $productionMode;
        if (is_null(Defaults::$cacheDirectory)) {
            Defaults::$cacheDirectory = dirname($_SERVER['SCRIPT_FILENAME']) .
                DIRECTORY_SEPARATOR . 'cache';
        }
        $this->cache = new Defaults::$cacheClass();
        $this->refreshCache = $refreshCache;
        // use this to rebuild cache every time in production mode
        if ($productionMode && $refreshCache) {
            $this->cached = false;
        }
    }

    /**
     * Main function for processing the api request
     * and return the response
     *
     * @throws Exception     when the api service class is missing
     * @throws RestException to send error response
     */
    public function handle()
    {
        try {
            try {
                try {
                    $this->get();
                } catch (Exception $e) {
                    $this->requestData
                        = array(Defaults::$fullRequestDataName => array());
                    if (!$e instanceof RestException) {
                        $e = new RestException(
                            500,
                            $this->productionMode ? null : $e->getMessage(),
                            array(),
                            $e
                        );
                    }
                    $this->route();
                    throw $e;
                }
                if (Defaults::$useVendorMIMEVersioning)
                    $this->responseFormat = $this->negotiateResponseFormat();
                $this->route();
            } catch (Exception $e) {
                $this->negotiate();
                if (!$e instanceof RestException) {
                    $e = new RestException(
                        500,
                        $this->productionMode ? null : $e->getMessage(),
                        array(),
                        $e
                    );
                }
                throw $e;
            }
            $this->negotiate();
            $this->preAuthFilter();
            $this->authenticate();
            $this->postAuthFilter();
            $this->validate();
            $this->preCall();
            $this->call();
            $this->compose();
            $this->postCall();
            if (Defaults::$returnResponse) {
                return $this->respond();
            }
            $this->respond();
        } catch (Exception $e) {
            try{
                if (Defaults::$returnResponse) {
                    return $this->message($e);
                }
                $this->message($e);
            } catch (Exception $e2) {
                if (Defaults::$returnResponse) {
                    return $this->message($e2);
                }
                $this->message($e2);
            }
        }
    }

    /**
     * read the request details
     *
     * Find out the following
     *  - baseUrl
     *  - url requested
     *  - version requested (if url based versioning)
     *  - http verb/method
     *  - negotiate content type
     *  - request data
     *  - set defaults
     */
    protected function get()
    {
        $this->dispatch('get');
        if (empty($this->formatMap)) {
            $this->setSupportedFormats('JsonFormat');
        }
        $this->url = $this->getPath();
        $this->requestMethod = Util::getRequestMethod();
        $this->requestFormat = $this->getRequestFormat();
        $this->requestData = $this->getRequestData(false);

        //parse defaults
        foreach ($_GET as $key => $value) {
            if (isset(Defaults::$aliases[$key])) {
                $_GET[Defaults::$aliases[$key]] = $value;
                unset($_GET[$key]);
                $key = Defaults::$aliases[$key];
            }
            if (in_array($key, Defaults::$overridables)) {
                Defaults::setProperty($key, $value);
            }
        }
    }

    /**
     * Returns a list of the mime types (e.g.  ["application/json","application/xml"]) that the API can respond with
     * @return array
     */
    public function getWritableMimeTypes()
    {
        return $this->writableMimeTypes;
    }

    /**
     * Returns the list of Mime Types for the request that the API can understand
     * @return array
     */
    public function getReadableMimeTypes()
    {
        return $this->readableMimeTypes;
    }

    /**
     * Call this method and pass all the formats that should be  supported by
     * the API Server. Accepts multiple parameters
     *
     * @param string ,... $formatName   class name of the format class that
     *                                  implements iFormat
     *
     * @example $restler->setSupportedFormats('JsonFormat', 'XmlFormat'...);
     * @throws Exception
     */
    public function setSupportedFormats($format = null /*[, $format2...$farmatN]*/)
    {
        $args = func_get_args();
        $extensions = array();
        $throwException = $this->requestFormatDiffered;
        $this->writableMimeTypes = $this->readableMimeTypes = array();
        foreach ($args as $className) {

            $obj = Scope::get($className);

            if (!$obj instanceof iFormat)
                throw new Exception('Invalid format class; must implement ' .
                    'iFormat interface');
            if ($throwException && get_class($obj) == get_class($this->requestFormat)) {
                $throwException = false;
            }

            foreach ($obj->getMIMEMap() as $mime => $extension) {
                if($obj->isWritable()){
                    $this->writableMimeTypes[]=$mime;
                    $extensions[".$extension"] = true;
                }
                if($obj->isReadable())
                    $this->readableMimeTypes[]=$mime;
                if (!isset($this->formatMap[$extension]))
                    $this->formatMap[$extension] = $className;
                if (!isset($this->formatMap[$mime]))
                    $this->formatMap[$mime] = $className;
            }
        }
        if ($throwException) {
            throw new RestException(
                403,
                'Content type `' . $this->requestFormat->getMIME() . '` is not supported.'
            );
        }
        $this->formatMap['default'] = $args[0];
        $this->formatMap['extensions'] = array_keys($extensions);
    }

    /**
     * Call this method and pass all the formats that can be used to override
     * the supported formats using `@format` comment. Accepts multiple parameters
     *
     * @param string ,... $formatName   class name of the format class that
     *                                  implements iFormat
     *
     * @example $restler->setOverridingFormats('JsonFormat', 'XmlFormat'...);
     * @throws Exception
     */
    public function setOverridingFormats($format = null /*[, $format2...$farmatN]*/)
    {
        $args = func_get_args();
        $extensions = array();
        foreach ($args as $className) {

            $obj = Scope::get($className);

            if (!$obj instanceof iFormat)
                throw new Exception('Invalid format class; must implement ' .
                    'iFormat interface');

            foreach ($obj->getMIMEMap() as $mime => $extension) {
                if (!isset($this->formatOverridesMap[$extension]))
                    $this->formatOverridesMap[$extension] = $className;
                if (!isset($this->formatOverridesMap[$mime]))
                    $this->formatOverridesMap[$mime] = $className;
                if($obj->isWritable())
                    $extensions[".$extension"] = true;
            }
        }
        $this->formatOverridesMap['extensions'] = array_keys($extensions);
    }

    /**
     * Set one or more string to be considered as the base url
     *
     * When more than one base url is provided, restler will make
     * use of $_SERVER['HTTP_HOST'] to find the right one
     *
     * @param string ,... $url
     */
    public function setBaseUrls($url /*[, $url2...$urlN]*/)
    {
        if (func_num_args() > 1) {
            $urls = func_get_args();
            usort($urls, function ($a, $b) {
                return strlen($a) - strlen($b);
            });
            foreach ($urls as $u) {
                if (0 === strpos($_SERVER['HTTP_HOST'], parse_url($u, PHP_URL_HOST))) {
                    $this->baseUrl = $u;
                    return;
                }
            }
        }
        $this->baseUrl = $url;
    }

    /**
     * Parses the request url and get the api path
     *
     * @return string api path
     */
    protected function getPath()
    {
        // fix SCRIPT_NAME for PHP 5.4 built-in web server
        if (false === strpos($_SERVER['SCRIPT_NAME'], '.php'))
            $_SERVER['SCRIPT_NAME']
                = '/' . substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']) + 1);

        list($base, $path) = Util::splitCommonPath(
            strtok(urldecode($_SERVER['REQUEST_URI']), '?'), //remove query string
            $_SERVER['SCRIPT_NAME']
        );

        if (!$this->baseUrl) {
            // Fix port number retrieval if port is specified in HOST header.
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $portPos = strpos($host,":");
            if ($portPos){
               $port = substr($host,$portPos+1);
            } else {
               $port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : '80';
               $port = isset($_SERVER['HTTP_X_FORWARDED_PORT']) ? $_SERVER['HTTP_X_FORWARDED_PORT'] : $port; // Amazon ELB
            }
            $https = $port == '443' ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || // Amazon ELB
                (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
            $baseUrl = ($https ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'];
            if (!$https && $port != '80' || $https && $port != '443')
                $baseUrl .= ':' . $port;
            $this->baseUrl = $baseUrl . $base;
        } elseif (!empty($base) && false === strpos($this->baseUrl, $base)) {
            $this->baseUrl .= $base;
        }

        $path = str_replace(
            array_merge(
                $this->formatMap['extensions'],
                $this->formatOverridesMap['extensions']
            ),
            '',
            rtrim($path, '/') //remove trailing slash if found
        );

        if (Defaults::$useUrlBasedVersioning && strlen($path) && $path{0} == 'v') {
            $version = intval(substr($path, 1));
            if ($version && $version <= $this->apiVersion) {
                $this->requestedApiVersion = $version;
                $path = explode('/', $path, 2);
                $path = $path[1];
            }
        } else {
            $this->requestedApiVersion = $this->apiMinimumVersion;
        }
        return $path;
    }

    /**
     * Parses the request to figure out format of the request data
     *
     * @throws RestException
     * @return iFormat any class that implements iFormat
     * @example JsonFormat
     */
    protected function getRequestFormat()
    {
        $format = null ;
        // check if client has sent any information on request format
        if (
            !empty($_SERVER['CONTENT_TYPE']) ||
            (
                !empty($_SERVER['HTTP_CONTENT_TYPE']) &&
                $_SERVER['CONTENT_TYPE'] = $_SERVER['HTTP_CONTENT_TYPE']
            )
        ) {
            $mime = $_SERVER['CONTENT_TYPE'];
            if (false !== $pos = strpos($mime, ';')) {
                $mime = substr($mime, 0, $pos);
            }
            if ($mime == UrlEncodedFormat::MIME)
                $format = Scope::get('UrlEncodedFormat');
            elseif (isset($this->formatMap[$mime])) {
                $format = Scope::get($this->formatMap[$mime]);
                $format->setMIME($mime);
            } elseif (!$this->requestFormatDiffered && isset($this->formatOverridesMap[$mime])) {
                //if our api method is not using an @format comment
                //to point to this $mime, we need to throw 403 as in below
                //but since we don't know that yet, we need to defer that here
                $format = Scope::get($this->formatOverridesMap[$mime]);
                $format->setMIME($mime);
                $this->requestFormatDiffered = true;
            } else {
                throw new RestException(
                    403,
                    "Content type `$mime` is not supported."
                );
            }
        }
        if(!$format){
            $format = Scope::get($this->formatMap['default']);
        }
        return $format;
    }

    public function getRequestStream()
    {
        static $tempStream = false;
        if (!$tempStream) {
            $tempStream = fopen('php://temp', 'r+');
            $rawInput = fopen('php://input', 'r');
            stream_copy_to_stream($rawInput, $tempStream);
        }
        rewind($tempStream);
        return $tempStream;
    }

    /**
     * Parses the request data and returns it
     *
     * @param bool $includeQueryParameters
     *
     * @return array php data
     */
    public function getRequestData($includeQueryParameters = true)
    {
        $get = UrlEncodedFormat::decoderTypeFix($_GET);
        if ($this->requestMethod == 'PUT'
            || $this->requestMethod == 'PATCH'
            || $this->requestMethod == 'POST'
        ) {
            if (!empty($this->requestData)) {
                return $includeQueryParameters
                    ? $this->requestData + $get
                    : $this->requestData;
            }

            $stream = $this->getRequestStream();
            if($stream === FALSE)
                return array();
            $r = $this->requestFormat instanceof iDecodeStream
                ? $this->requestFormat->decodeStream($stream)
                : $this->requestFormat->decode(stream_get_contents($stream));

            $r = is_array($r)
                ? array_merge($r, array(Defaults::$fullRequestDataName => $r))
                : array(Defaults::$fullRequestDataName => $r);
            return $includeQueryParameters
                ? $r + $get
                : $r;
        }
        return $includeQueryParameters ? $get : array(); //no body
    }

    /**
     * Find the api method to execute for the requested Url
     */
    protected function route()
    {
        $this->dispatch('route');

        $params = $this->getRequestData();

        //backward compatibility for restler 2 and below
        if (!Defaults::$smartParameterParsing) {
            $params = $params + array(Defaults::$fullRequestDataName => $params);
        }

        $this->apiMethodInfo = $o = Routes::find(
            $this->url, $this->requestMethod,
            $this->requestedApiVersion, $params
        );
        //set defaults based on api method comments
        if (isset($o->metadata)) {
            foreach (Defaults::$fromComments as $key => $defaultsKey) {
                if (array_key_exists($key, $o->metadata)) {
                    $value = $o->metadata[$key];
                    Defaults::setProperty($defaultsKey, $value);
                }
            }
        }
        if (!isset($o->className))
            throw new RestException(404);

        if(isset($this->apiVersionMap[$o->className])){
            Scope::$classAliases[Util::getShortName($o->className)]
                = $this->apiVersionMap[$o->className][$this->requestedApiVersion];
        }

        foreach ($this->authClasses as $auth) {
            if (isset($this->apiVersionMap[$auth])) {
                Scope::$classAliases[$auth] = $this->apiVersionMap[$auth][$this->requestedApiVersion];
            } elseif (isset($this->apiVersionMap[Scope::$classAliases[$auth]])) {
                Scope::$classAliases[$auth]
                    = $this->apiVersionMap[Scope::$classAliases[$auth]][$this->requestedApiVersion];
            }
        }
    }

    /**
     * Negotiate the response details such as
     *  - cross origin resource sharing
     *  - media type
     *  - charset
     *  - language
     */
    protected function negotiate()
    {
        $this->dispatch('negotiate');
        $this->negotiateCORS();
        $this->responseFormat = $this->negotiateResponseFormat();
        $this->negotiateCharset();
        $this->negotiateLanguage();
    }

    protected function negotiateCORS()
    {
        if (
            $this->requestMethod == 'OPTIONS'
            && Defaults::$crossOriginResourceSharing
        ) {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header('Access-Control-Allow-Methods: '
                    . Defaults::$accessControlAllowMethods);

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header('Access-Control-Allow-Headers: '
                    . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);

            header('Access-Control-Allow-Origin: ' .
                (Defaults::$accessControlAllowOrigin == '*' ? $_SERVER['HTTP_ORIGIN'] : Defaults::$accessControlAllowOrigin));
            header('Access-Control-Allow-Credentials: true');

            exit(0);
        }
    }

    // ==================================================================
    //
    // Protected functions
    //
    // ------------------------------------------------------------------

    /**
     * Parses the request to figure out the best format for response.
     * Extension, if present, overrides the Accept header
     *
     * @throws RestException
     * @return iFormat
     * @example JsonFormat
     */
    protected function negotiateResponseFormat()
    {
        $metadata = Util::nestedValue($this, 'apiMethodInfo', 'metadata');
        //check if the api method insists on response format using @format comment

        if ($metadata && isset($metadata['format'])) {
            $formats = explode(',', (string)$metadata['format']);
            foreach ($formats as $i => $f) {
                $f = trim($f);
                if (!in_array($f, $this->formatOverridesMap))
                    throw new RestException(
                        500,
                        "Given @format is not present in overriding formats. Please call `\$r->setOverridingFormats('$f');` first."
                    );
                $formats[$i] = $f;
            }
            call_user_func_array(array($this, 'setSupportedFormats'), $formats);
        }

        // check if client has specified an extension
        /** @var $format iFormat*/
        $format = null;
        $extensions = explode(
            '.',
            parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
        );
        while ($extensions) {
            $extension = array_pop($extensions);
            $extension = explode('/', $extension);
            $extension = array_shift($extension);
            if ($extension && isset($this->formatMap[$extension])) {
                $format = Scope::get($this->formatMap[$extension]);
                $format->setExtension($extension);
                // echo "Extension $extension";
                return $format;
            }
        }
        // check if client has sent list of accepted data formats
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $acceptList = Util::sortByPriority($_SERVER['HTTP_ACCEPT']);
            foreach ($acceptList as $accept => $quality) {
                if (isset($this->formatMap[$accept])) {
                    $format = Scope::get($this->formatMap[$accept]);
                    $format->setMIME($accept);
                    //echo "MIME $accept";
                    // Tell cache content is based on Accept header
                    @header('Vary: Accept');

                    return $format;
                } elseif (false !== ($index = strrpos($accept, '+'))) {
                    $mime = substr($accept, 0, $index);
                    if (is_string(Defaults::$apiVendor)
                        && 0 === stripos($mime,
                            'application/vnd.'
                            . Defaults::$apiVendor . '-v')
                    ) {
                        $extension = substr($accept, $index + 1);
                        if (isset($this->formatMap[$extension])) {
                            //check the MIME and extract version
                            $version = intval(substr($mime,
                                18 + strlen(Defaults::$apiVendor)));
                            if ($version > 0 && $version <= $this->apiVersion) {
                                $this->requestedApiVersion = $version;
                                $format = Scope::get($this->formatMap[$extension]);
                                $format->setExtension($extension);
                                // echo "Extension $extension";
                                Defaults::$useVendorMIMEVersioning = true;
                                @header('Vary: Accept');

                                return $format;
                            }
                        }
                    }

                }
            }
        } else {
            // RFC 2616: If no Accept header field is
            // present, then it is assumed that the
            // client accepts all media types.
            $_SERVER['HTTP_ACCEPT'] = '*/*';
        }
        if (strpos($_SERVER['HTTP_ACCEPT'], '*') !== false) {
            if (false !== strpos($_SERVER['HTTP_ACCEPT'], 'application/*')) {
                $format = Scope::get('JsonFormat');
            } elseif (false !== strpos($_SERVER['HTTP_ACCEPT'], 'text/*')) {
                $format = Scope::get('XmlFormat');
            } elseif (false !== strpos($_SERVER['HTTP_ACCEPT'], '*/*')) {
                $format = Scope::get($this->formatMap['default']);
            }
        }
        if (empty($format)) {
            // RFC 2616: If an Accept header field is present, and if the
            // server cannot send a response which is acceptable according to
            // the combined Accept field value, then the server SHOULD send
            // a 406 (not acceptable) response.
            $format = Scope::get($this->formatMap['default']);
            $this->responseFormat = $format;
            throw new RestException(
                406,
                'Content negotiation failed. ' .
                'Try `' . $format->getMIME() . '` instead.'
            );
        } else {
            // Tell cache content is based at Accept header
            @header("Vary: Accept");
            return $format;
        }
    }

    protected function negotiateCharset()
    {
        if (isset($_SERVER['HTTP_ACCEPT_CHARSET'])) {
            $found = false;
            $charList = Util::sortByPriority($_SERVER['HTTP_ACCEPT_CHARSET']);
            foreach ($charList as $charset => $quality) {
                if (in_array($charset, Defaults::$supportedCharsets)) {
                    $found = true;
                    Defaults::$charset = $charset;
                    break;
                }
            }
            if (!$found) {
                if (strpos($_SERVER['HTTP_ACCEPT_CHARSET'], '*') !== false) {
                    //use default charset
                } else {
                    throw new RestException(
                        406,
                        'Content negotiation failed. ' .
                        'Requested charset is not supported'
                    );
                }
            }
        }
    }

    protected function negotiateLanguage()
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $found = false;
            $langList = Util::sortByPriority($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($langList as $lang => $quality) {
                foreach (Defaults::$supportedLanguages as $supported) {
                    if (strcasecmp($supported, $lang) == 0) {
                        $found = true;
                        Defaults::$language = $supported;
                        break 2;
                    }
                }
            }
            if (!$found) {
                if (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], '*') !== false) {
                    //use default language
                } else {
                    //ignore
                }
            }
        }
    }

    /**
     * Filer api calls before authentication
     */
    protected function preAuthFilter()
    {
        if (empty($this->filterClasses)) {
            return;
        }
        $this->dispatch('preAuthFilter');
        foreach ($this->filterClasses as $filterClass) {
            /**
             * @var iFilter
             */
            $filterObj = Scope::get($filterClass);

            if (!$filterObj instanceof iFilter) {
                throw new RestException (
                    500, 'Filter Class ' .
                    'should implement iFilter');
            } else if (!($ok = $filterObj->__isAllowed())) {
                if (is_null($ok)
                    && $filterObj instanceof iUseAuthentication
                ) {
                    //handle at authentication stage
                    $this->postAuthFilterClasses[] = $filterClass;
                    continue;
                }
                throw new RestException(403); //Forbidden
            }
        }
    }

    protected function authenticate()
    {
        $o = &$this->apiMethodInfo;
        $accessLevel = max(Defaults::$apiAccessLevel, $o->accessLevel);
        if ($accessLevel || count($this->postAuthFilterClasses)) {
            $this->dispatch('authenticate');
            if (!count($this->authClasses) && $accessLevel > 1) {
                throw new RestException(
                    403,
                    'at least one Authentication Class is required'
                );
            }
            $unauthorized = false;
            foreach ($this->authClasses as $authClass) {
                try {
                    $authObj = Scope::get($authClass);
                    if (!method_exists($authObj, Defaults::$authenticationMethod)) {
                        throw new RestException (
                            500, 'Authentication Class ' .
                            'should implement iAuthenticate');
                    } elseif (
                    !$authObj->{Defaults::$authenticationMethod}()
                    ) {
                        throw new RestException(401);
                    }
                    $unauthorized = false;
                    break;
                } catch (InvalidAuthCredentials $e) {
                    $this->authenticated = false;
                    throw $e;
                } catch (RestException $e) {
                    if (!$unauthorized) {
                        $unauthorized = $e;
                    }
                }
            }
            $this->authVerified = true;
            if ($unauthorized) {
                if ($accessLevel > 1) { //when it is not a hybrid api
                    throw $unauthorized;
                } else {
                    $this->authenticated = false;
                }
            } else {
                $this->authenticated = true;
            }
        }
    }

    /**
     * Filer api calls after authentication
     */
    protected function postAuthFilter()
    {
        if(empty($this->postAuthFilterClasses)) {
            return;
        }
        $this->dispatch('postAuthFilter');
        foreach ($this->postAuthFilterClasses as $filterClass) {
            Scope::get($filterClass);
        }
    }

    protected function validate()
    {
        if (!Defaults::$autoValidationEnabled) {
            return;
        }
        $this->dispatch('validate');

        $o = & $this->apiMethodInfo;
        foreach ($o->metadata['param'] as $index => $param) {
            $info = & $param [CommentParser::$embeddedDataName];
            if (!isset ($info['validate'])
                || $info['validate'] != false
            ) {
                if (isset($info['method'])) {
                    $info ['apiClassInstance'] = Scope::get($o->className);
                }
                //convert to instance of ValidationInfo
                $info = new ValidationInfo($param);
                //initialize validator
                Scope::get(Defaults::$validatorClass);
                $validator = Defaults::$validatorClass;
                //if(!is_subclass_of($validator, 'Luracast\\Restler\\Data\\iValidate')) {
                //changed the above test to below for addressing this php bug
                //https://bugs.php.net/bug.php?id=53727
                if (function_exists("$validator::validate")) {
                    throw new \UnexpectedValueException(
                        '`Defaults::$validatorClass` must implement `iValidate` interface'
                    );
                }
                $valid = $o->parameters[$index];
                $o->parameters[$index] = null;
                if (empty(Validator::$exceptions))
                    $o->metadata['param'][$index]['autofocus'] = true;
                $valid = $validator::validate(
                    $valid, $info
                );
                $o->parameters[$index] = $valid;
                unset($o->metadata['param'][$index]['autofocus']);
            }
        }
    }

    protected function call()
    {
        $this->dispatch('call');
        $o = & $this->apiMethodInfo;
        $accessLevel = max(Defaults::$apiAccessLevel,
            $o->accessLevel);
        if (function_exists('newrelic_name_transaction'))
            newrelic_name_transaction("{$o->className}/{$o->methodName}");
        $object =  Scope::get($o->className);
        switch ($accessLevel) {
            case 3 : //protected method
                $reflectionMethod = new \ReflectionMethod(
                    $object,
                    $o->methodName
                );
                $reflectionMethod->setAccessible(true);
                $result = $reflectionMethod->invokeArgs(
                    $object,
                    $o->parameters
                );
                break;
            default :
                $result = call_user_func_array(array(
                    $object,
                    $o->methodName
                ), $o->parameters);
        }
        $this->responseData = $result;
    }

    protected function compose()
    {
        $this->dispatch('compose');
        $this->composeHeaders();
        /**
         * @var iCompose Default Composer
         */
        $compose = Scope::get(Defaults::$composeClass);
        $this->responseData = is_null($this->responseData) &&
        Defaults::$emptyBodyForNullResponse
            ? ''
            : $this->responseFormat->encode(
                $compose->response($this->responseData),
                !$this->productionMode
            );
    }

    public function composeHeaders(RestException $e = null)
    {
        //only GET method should be cached if allowed by API developer
        $expires = $this->requestMethod == 'GET' ? Defaults::$headerExpires : 0;
        if(!is_array(Defaults::$headerCacheControl))
            Defaults::$headerCacheControl = array(Defaults::$headerCacheControl);
        $cacheControl = Defaults::$headerCacheControl[0];
        if ($expires > 0) {
            $cacheControl = $this->apiMethodInfo->accessLevel
                ? 'private, ' : 'public, ';
            $cacheControl .= end(Defaults::$headerCacheControl);
            $cacheControl = str_replace('{expires}', $expires, $cacheControl);
            $expires = gmdate('D, d M Y H:i:s \G\M\T', time() + $expires);
        }
        @header('Cache-Control: ' . $cacheControl);
        @header('Expires: ' . $expires);
        @header('X-Powered-By: Luracast Restler v' . Restler::VERSION);

        if (Defaults::$crossOriginResourceSharing
            && isset($_SERVER['HTTP_ORIGIN'])
        ) {
            header('Access-Control-Allow-Origin: ' .
                (Defaults::$accessControlAllowOrigin == '*'
                    ? $_SERVER['HTTP_ORIGIN']
                    : Defaults::$accessControlAllowOrigin)
            );
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        $this->responseFormat->setCharset(Defaults::$charset);
        $charset = $this->responseFormat->getCharset()
            ? : Defaults::$charset;

        @header('Content-Type: ' . (
            Defaults::$useVendorMIMEVersioning
                ? 'application/vnd.'
                . Defaults::$apiVendor
                . "-v{$this->requestedApiVersion}"
                . '+' . $this->responseFormat->getExtension()
                : $this->responseFormat->getMIME())
            . '; charset=' . $charset
        );

        @header('Content-Language: ' . Defaults::$language);

        if (isset($this->apiMethodInfo->metadata['header'])) {
            foreach ($this->apiMethodInfo->metadata['header'] as $header)
                @header($header, true);
        }
        $code = 200;
        if (!Defaults::$suppressResponseCode) {
            if ($e) {
                $code = $e->getCode();
            } elseif ($this->responseCode) {
                $code = $this->responseCode;
            } elseif (isset($this->apiMethodInfo->metadata['status'])) {
                $code = $this->apiMethodInfo->metadata['status'];
            }
        }
        $this->responseCode = $code;
        @header(
            "{$_SERVER['SERVER_PROTOCOL']} $code " .
            (isset(RestException::$codes[$code]) ? RestException::$codes[$code] : '')
        );
    }

    protected function respond()
    {
        $this->dispatch('respond');
        //handle throttling
        if (Defaults::$throttle) {
            $elapsed = time() - $this->startTime;
            if (Defaults::$throttle / 1e3 > $elapsed) {
                usleep(1e6 * (Defaults::$throttle / 1e3 - $elapsed));
            }
        }
        if ($this->responseCode == 401) {
            $authString = count($this->authClasses)
                ? Scope::get($this->authClasses[0])->__getWWWAuthenticateString()
                : 'Unknown';
            @header('WWW-Authenticate: ' . $authString, false);
        }
        $this->dispatch('complete');
        if (Defaults::$returnResponse) {
            return $this->responseData;
        } else {
            echo $this->responseData;
            exit;
        }
    }

    protected function message(Exception $exception)
    {
        $this->dispatch('message');

        if (!$exception instanceof RestException) {
            $exception = new RestException(
                500,
                $this->productionMode ? null : $exception->getMessage(),
                array(),
                $exception
            );
        }

        $this->exception = $exception;

        $method = 'handle' . $exception->getCode();
        $handled = false;
        foreach ($this->errorClasses as $className) {
            if (method_exists($className, $method)) {
                $obj = Scope::get($className);
                if ($obj->$method())
                    $handled = true;
            }
        }
        if ($handled) {
            return;
        }
        if (!isset($this->responseFormat)) {
            $this->responseFormat = Scope::get('JsonFormat');
        }
        $this->composeHeaders($exception);
        /**
         * @var iCompose Default Composer
         */
        $compose = Scope::get(Defaults::$composeClass);
        $this->responseData = $this->responseFormat->encode(
            $compose->message($exception),
            !$this->productionMode
        );
        if (Defaults::$returnResponse) {
            return $this->respond();
        }
        $this->respond();
    }

    /**
     * Provides backward compatibility with older versions of Restler
     *
     * @param int $version restler version
     *
     * @throws \OutOfRangeException
     */
    public function setCompatibilityMode($version = 2)
    {
        if ($version <= intval(self::VERSION) && $version > 0) {
            require __DIR__."/compatibility/restler{$version}.php";
            return;
        }
        throw new \OutOfRangeException();
    }

    /**
     * @param int $version                 maximum version number supported
     *                                     by  the api
     * @param int $minimum                 minimum version number supported
     * (optional)
     *
     * @throws InvalidArgumentException
     * @return void
     */
    public function setAPIVersion($version = 1, $minimum = 1)
    {
        if (!is_int($version) && $version < 1) {
            throw new InvalidArgumentException
            ('version should be an integer greater than 0');
        }
        $this->apiVersion = $version;
        if (is_int($minimum)) {
            $this->apiMinimumVersion = $minimum;
        }
    }

    /**
     * Classes implementing iFilter interface can be added for filtering out
     * the api consumers.
     *
     * It can be used for rate limiting based on usage from a specific ip
     * address or filter by country, device etc.
     *
     * @param $className
     */
    public function addFilterClass($className)
    {
        $this->filterClasses[] = $className;
    }

    /**
     * protected methods will need at least one authentication class to be set
     * in order to allow that method to be executed
     *
     * @param string $className     of the authentication class
     * @param string $resourcePath  optional url prefix for mapping
     */
    public function addAuthenticationClass($className, $resourcePath = null)
    {
        $this->authClasses[] = $className;
        $this->addAPIClass($className, $resourcePath);
    }

    /**
     * Add api classes through this method.
     *
     * All the public methods that do not start with _ (underscore)
     * will be will be exposed as the public api by default.
     *
     * All the protected methods that do not start with _ (underscore)
     * will exposed as protected api which will require authentication
     *
     * @param string $className       name of the service class
     * @param string $resourcePath    optional url prefix for mapping, uses
     *                                lowercase version of the class name when
     *                                not specified
     *
     * @return null
     *
     * @throws Exception when supplied with invalid class name
     */
    public function addAPIClass($className, $resourcePath = null)
    {
        try{
            if ($this->productionMode && is_null($this->cached)) {
                $routes = $this->cache->get('routes');
                if (isset($routes) && is_array($routes)) {
                    $this->apiVersionMap = $routes['apiVersionMap'];
                    unset($routes['apiVersionMap']);
                    Routes::fromArray($routes);
                    $this->cached = true;
                } else {
                    $this->cached = false;
                }
            }
            if (isset(Scope::$classAliases[$className])) {
                $className = Scope::$classAliases[$className];
            }
            if (!$this->cached) {
                $maxVersionMethod = '__getMaximumSupportedVersion';
                if (class_exists($className)) {
                    if (method_exists($className, $maxVersionMethod)) {
                        $max = $className::$maxVersionMethod();
                        for ($i = 1; $i <= $max; $i++) {
                            $this->apiVersionMap[$className][$i] = $className;
                        }
                    } else {
                        $this->apiVersionMap[$className][1] = $className;
                    }
                }
                //versioned api
                if (false !== ($index = strrpos($className, '\\'))) {
                    $name = substr($className, 0, $index)
                        . '\\v{$version}' . substr($className, $index);
                } else if (false !== ($index = strrpos($className, '_'))) {
                    $name = substr($className, 0, $index)
                        . '_v{$version}' . substr($className, $index);
                } else {
                    $name = 'v{$version}\\' . $className;
                }

                for ($version = $this->apiMinimumVersion;
                     $version <= $this->apiVersion;
                     $version++) {

                    $versionedClassName = str_replace('{$version}', $version,
                        $name);
                    if (class_exists($versionedClassName)) {
                        Routes::addAPIClass($versionedClassName,
                            Util::getResourcePath(
                                $className,
                                $resourcePath
                            ),
                            $version
                        );
                        if (method_exists($versionedClassName, $maxVersionMethod)) {
                            $max = $versionedClassName::$maxVersionMethod();
                            for ($i = $version; $i <= $max; $i++) {
                                $this->apiVersionMap[$className][$i] = $versionedClassName;
                            }
                        } else {
                            $this->apiVersionMap[$className][$version] = $versionedClassName;
                        }
                    } elseif (isset($this->apiVersionMap[$className][$version])) {
                        Routes::addAPIClass($this->apiVersionMap[$className][$version],
                            Util::getResourcePath(
                                $className,
                                $resourcePath
                            ),
                            $version
                        );
                    }
                }

            }
        } catch (Exception $e) {
            $e = new Exception(
                "addAPIClass('$className') failed. ".$e->getMessage(),
                $e->getCode(),
                $e
            );
            $this->setSupportedFormats('JsonFormat');
            $this->message($e);
        }
    }

    /**
     * Add class for custom error handling
     *
     * @param string $className   of the error handling class
     */
    public function addErrorClass($className)
    {
        $this->errorClasses[] = $className;
    }

    /**
     * Associated array that maps formats to their respective format class name
     *
     * @return array
     */
    public function getFormatMap()
    {
        return $this->formatMap;
    }

    /**
     * API version requested by the client
     * @return int
     */
    public function getRequestedApiVersion()
    {
        return $this->requestedApiVersion;
    }

    /**
     * When false, restler will run in debug mode and parse the class files
     * every time to map it to the URL
     *
     * @return bool
     */
    public function getProductionMode()
    {
        return $this->productionMode;
    }

    /**
     * Chosen API version
     *
     * @return int
     */
    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * Base Url of the API Service
     *
     * @return string
     *
     * @example http://localhost/restler3
     * @example http://restler3.com
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * List of events that fired already
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Magic method to expose some protected variables
     *
     * @param string $name name of the hidden property
     *
     * @return null|mixed
     */
    public function __get($name)
    {
        if ($name{0} == '_') {
            $hiddenProperty = substr($name, 1);
            if (isset($this->$hiddenProperty)) {
                return $this->$hiddenProperty;
            }
        }
        return null;
    }

    /**
     * Store the url map cache if needed
     */
    public function __destruct()
    {
        if ($this->productionMode && !$this->cached) {
            $this->cache->set(
                'routes',
                Routes::toArray() +
                array('apiVersionMap' => $this->apiVersionMap)
            );
        }
    }

    /**
     * pre call
     *
     * call _pre_{methodName)_{extension} if exists with the same parameters as
     * the api method
     *
     * @example _pre_get_json
     *
     */
    protected function preCall()
    {
        $o = & $this->apiMethodInfo;
        $preCall = '_pre_' . $o->methodName . '_'
            . $this->requestFormat->getExtension();

        if (method_exists($o->className, $preCall)) {
            $this->dispatch('preCall');
            call_user_func_array(array(
                Scope::get($o->className),
                $preCall
            ), $o->parameters);
        }
    }

    /**
     * post call
     *
     * call _post_{methodName}_{extension} if exists with the composed and
     * serialized (applying the repose format) response data
     *
     * @example _post_get_json
     */
    protected function postCall()
    {
        $o = & $this->apiMethodInfo;
        $postCall = '_post_' . $o->methodName . '_' .
            $this->responseFormat->getExtension();
        if (method_exists($o->className, $postCall)) {
            $this->dispatch('postCall');
            $this->responseData = call_user_func(array(
                Scope::get($o->className),
                $postCall
            ), $this->responseData);
        }
    }
}
