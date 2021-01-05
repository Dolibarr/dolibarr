<?php
namespace Luracast\Restler;

use Luracast\Restler\Data\ValidationInfo;
use Luracast\Restler\Data\Validator;

/**
 * Static class to hold all restler defaults, change the values to suit your
 * needs in the gateway file (index.php), you may also allow the api users to
 * change them per request by adding the properties to Defaults::$overridables
 *
 * @category   Framework
 * @package    Restler
 * @author     R.Arul Kumaran <arul@luracast.com>
 * @copyright  2010 Luracast
 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link       http://luracast.com/products/restler/
 *
 */
class Defaults
{
    // ==================================================================
    //
    // Class Mappings
    //
    // ------------------------------------------------------------------

    /**
     * @var string of name of the class that implements
     * \Luracast\Restler\iCache the cache class to be used
     */
    public static $cacheClass = 'Luracast\\Restler\\HumanReadableCache';

    /**
     * @var string full path of the directory where all the generated files will
     * be kept. When set to null (default) it will use the cache folder that is
     * in the same folder as index.php (gateway)
     */
    public static $cacheDirectory;

    /**
     * @var string of name of the class that implements
     * \Luracast\Restler\Data\iValidate the validator class to be used
     */
    public static $validatorClass = 'Luracast\\Restler\\Data\\Validator';

    /**
     * @var string name of the class that implements \Luracast\Restler\iCompose
     * the class to be used to compose the response
     */
    public static $composeClass = 'Luracast\\Restler\\Compose';

    // ==================================================================
    //
    // Routing
    //
    // ------------------------------------------------------------------

    /**
     * @var bool should auto routing for public and protected api methods
     * should be enabled by default or not. Set this to false to get
     * Restler 1.0 style behavior
     */
    public static $autoRoutingEnabled = true;

    /**
     * @var boolean avoids creating multiple routes that can increase the
     * ambiguity when set to true. when a method parameter is optional it is
     * not mapped to the url and should only be used in request body or as
     * query string `/resource?id=value`. When a parameter is required and is
     * scalar, it will be mapped as part of the url `/resource/{id}`
     */
    public static $smartAutoRouting = true;

    /**
     * @var boolean enables more ways of finding the parameter data in the request.
     * If you need backward compatibility with Restler 2 or below turn this off
     */
    public static $smartParameterParsing = true;

    // ==================================================================
    //
    // API Version Management
    //
    // ------------------------------------------------------------------

    /**
     * @var null|string name that is used for vendor specific media type and
     * api version using the Accept Header for example
     * application/vnd.{vendor}-v1+json
     *
     * Keep this null if you do not want to use vendor MIME for specifying api version
     */
    public static $apiVendor = null;

    /**
     * @var bool set it to true to force vendor specific MIME for versioning.
     * It will be automatically set to true when Defaults::$vendor is not
     * null and client is requesting for the custom MIME type
     */
    public static $useVendorMIMEVersioning = false;

    /**
     * @var bool set it to true to use enableUrl based versioning
     */
    public static $useUrlBasedVersioning = false;


    // ==================================================================
    //
    // Request
    //
    // ------------------------------------------------------------------

    /**
     * @var string name to be used for the method parameter to capture the
     *             entire request data
     */
    public static $fullRequestDataName = 'request_data';

    /**
     * @var string name of the property that can sent through $_GET or $_POST to
     *             override the http method of the request. Set it to null or
     *             blank string to disable http method override through request
     *             parameters.
     */
    public static $httpMethodOverrideProperty = 'http_method';

    /**
     * @var bool should auto validating api parameters should be enabled by
     *           default or not. Set this to false to avoid validation.
     */
    public static $autoValidationEnabled = true;

    /**
     * @var string name of the class that implements iUser interface to identify
     *             the user for caching purposes
     */
    public static $userIdentifierClass = 'Luracast\\Restler\\User';

    // ==================================================================
    //
    // Response
    //
    // ------------------------------------------------------------------

    /**
     * @var bool HTTP status codes are set on all responses by default.
     * Some clients (like flash, mobile) have trouble dealing with non-200
     * status codes on error responses.
     *
     * You can set it to true to force a HTTP 200 status code on all responses,
     * even when errors occur. If you suppress status codes, look for an error
     * response to determine if an error occurred.
     */
    public static $suppressResponseCode = false;

    public static $supportedCharsets = array('utf-8', 'iso-8859-1');
    public static $supportedLanguages = array('en', 'en-US');

    public static $charset = 'utf-8';
    public static $language = 'en';

    /**
     * @var bool when set to true, it will exclude the response body
     */
    public static $emptyBodyForNullResponse = true;

    /**
     * @var bool when set to true, the response will not be outputted directly into the buffer.
     * If set, Restler::handle() will return the response as a string.
     */
    public static $returnResponse = false;

    /**
     * @var bool enables CORS support
     */
    public static $crossOriginResourceSharing = false;
    public static $accessControlAllowOrigin = '*';
    public static $accessControlAllowMethods =
        'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD';

    // ==================================================================
    //
    // Header
    //
    // ------------------------------------------------------------------

    /**
     * @var array default Cache-Control template that used to set the
     * Cache-Control header and has two values, first one is used when
     * Defaults::$headerExpires is 0 and second one when it has some time
     * value specified. When only one value is specified it will be used for
     * both cases
     */
    public static $headerCacheControl = array(
        'no-cache, must-revalidate',

        /* "public, " or "private, " will be prepended based on api method
         * called (public or protected)
         */
        'max-age={expires}, must-revalidate',

    );


    /**
     * @var int sets the content to expire immediately when set to zero
     * alternatively you can specify the number of seconds the content will
     * expire. This setting can be altered at api level using php doc comment
     * with @expires numOfSeconds
     */
    public static $headerExpires = 0;

    // ==================================================================
    //
    // Access Control
    //
    // ------------------------------------------------------------------

    /**
     * @var null|callable if the api methods are under access control mechanism
     * you can attach a function here that returns true or false to determine
     * visibility of a protected api method. this function will receive method
     * info as the only parameter.
     */
    public static $accessControlFunction = null;

    /**
     * @var int set the default api access mode
     *      value of 0 = public api
     *      value of 1 = hybrid api using `@access hybrid` comment
     *      value of 2 = protected api using `@access protected` comment
     *      value of 3 = protected api using `protected function` method
     */
    public static $apiAccessLevel = 0;

    /**
     * @var string authentication method to be called in iAuthenticate
     * Interface
     */
    public static $authenticationMethod = '__isAllowed';

    /**
     * @var int time in milliseconds for bandwidth throttling,
     * which is the minimum response time for each api request. You can
     * change it per api method by setting `@throttle 3000` in php doc
     * comment either at the method level or class level
     */
    public static $throttle = 0;

    // ==================================================================
    //
    // Overrides for API User
    //
    // ------------------------------------------------------------------

    /**
     * @var array use 'alternativeName'=> 'actualName' to set alternative
     * names that can be used to represent the api method parameters and/or
     * static properties of Defaults
     */
    public static $aliases = array(
        /**
         * suppress_response_codes=true as an URL parameter to force
         * a HTTP 200 status code on all responses
         */
        'suppress_response_codes' => 'suppressResponseCode',
    );

    /**
     * @var array determines the defaults that can be overridden by the api
     * user by passing them as URL parameters
     */
    public static $overridables = array(
        'suppressResponseCode',
    );

    /**
     * @var array contains validation details for defaults to be used when
     * set through URL parameters
     */
    public static $validation = array(
        'suppressResponseCode' => array('type' => 'bool'),
        'headerExpires' => array('type' => 'int', 'min' => 0),
    );

    // ==================================================================
    //
    // Overrides API Developer
    //
    // ------------------------------------------------------------------

    /**
     * @var array determines what are the phpdoc comment tags that will
     * override the Defaults here with their values
     */
    public static $fromComments = array(

        /**
         * use PHPDoc comments such as the following
         * `
         *
         * @cache no-cache, must-revalidate` to set the Cache-Control header
         *        for a specific api method
         */
        'cache' => 'headerCacheControl',

        /**
         * use PHPDoc comments such as the following
         * `
         *
         * @expires 50` to set the Expires header
         *          for a specific api method
         */
        'expires' => 'headerExpires',

        /**
         * use PHPDoc comments such as the following
         * `
         *
         * @throttle 300`
         *           to set the bandwidth throttling for 300 milliseconds
         *           for a specific api method
         */
        'throttle' => 'throttle',

        /**
         * enable or disable smart auto routing from method comments
         * this one is hardwired so cant be turned off
         * it is placed here just for documentation purpose
         */
        'smart-auto-routing' => 'smartAutoRouting',
    );

    // ==================================================================
    //
    // Util
    //
    // ------------------------------------------------------------------

    /**
     * Use this method to set value to a static properly of Defaults when
     * you want to make sure only proper values are taken in with the help of
     * validation
     *
     * @static
     *
     * @param string $name  name of the static property
     * @param mixed  $value value to set the property to
     *
     * @return bool
     */
    public static function setProperty($name, $value)
    {
        if (!property_exists(__CLASS__, $name)) return false;
        if (@is_array(Defaults::$validation[$name])) {
            $info = new ValidationInfo(Defaults::$validation[$name]);
            $value = Validator::validate($value, $info);
        }
        Defaults::$$name = $value;
        return true;
    }

}

