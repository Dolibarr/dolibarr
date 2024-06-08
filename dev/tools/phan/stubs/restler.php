<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 */
// phpcs:disable PEAR.Commenting,Generic.NamingConventions,PEAR.NamingConventions,Squiz.Scope.MethodScope.Missing

namespace Luracast\Restler {
	/**
	 * Interface for the cache system that manages caching of given data
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iCache
	{
		/**
		 * store data in the cache
		 *
		 * @abstract
		 *
		 * @param string $name
		 * @param mixed  $data
		 *
		 * @return boolean true if successful
		 */
		public function set($name, $data);
		/**
		 * retrieve data from the cache
		 *
		 * @abstract
		 *
		 * @param string     $name
		 * @param bool       $ignoreErrors
		 *
		 * @return mixed
		 */
		public function get($name, $ignoreErrors = false);
		/**
		 * delete data from the cache
		 *
		 * @abstract
		 *
		 * @param string     $name
		 * @param bool       $ignoreErrors
		 *
		 * @return boolean true if successful
		 */
		public function clear($name, $ignoreErrors = false);
		/**
		 * check if the given name is cached
		 *
		 * @abstract
		 *
		 * @param string $name
		 *
		 * @return boolean true if cached
		 */
		public function isCached($name);
	}
	/**
	 * Class ApcCache provides an APC based cache for Restler
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     Joel R. Simpson <joel.simpson@gmail.com>
	 * @copyright  2013 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class ApcCache implements \Luracast\Restler\iCache
	{
		/**
		 * The namespace that all of the cached entries will be stored under.  This allows multiple APIs to run concurrently.
		 *
		 * @var string
		 */
		public static $namespace = 'restler';
		/**
		 * store data in the cache
		 *
		 *
		 * @param string $name
		 * @param mixed $data
		 *
		 * @return boolean true if successful
		 */
		public function set($name, $data)
		{
		}
		/**
		 * retrieve data from the cache
		 *
		 *
		 * @param string $name
		 * @param bool $ignoreErrors
		 *
		 * @throws \Exception
		 * @return mixed
		 */
		public function get($name, $ignoreErrors = false)
		{
		}
		/**
		 * delete data from the cache
		 *
		 *
		 * @param string $name
		 * @param bool $ignoreErrors
		 *
		 * @throws \Exception
		 * @return boolean true if successful
		 */
		public function clear($name, $ignoreErrors = false)
		{
		}
		/**
		 * check if the given name is cached
		 *
		 *
		 * @param string $name
		 *
		 * @return boolean true if cached
		 */
		public function isCached($name)
		{
		}
	}
	/**
	 * Class that implements spl_autoload facilities and multiple
	 * conventions support.
	 * Supports composer libraries and 100% PSR-0 compliant.
	 * In addition we enable namespace prefixing and class aliases.
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage Helper
	 * @author     Nick Lombard <github@jigsoft.co.za>
	 * @copyright  2012 Luracast
	 *
	 */
	class AutoLoader
	{
		protected static $instance;
		protected static $perfectLoaders;
		protected static $rogueLoaders = array();
		protected static $classMap = array();
		protected static $aliases = array(
			// aliases and prefixes instead of null list aliases
			'Luracast\\Restler' => null,
			'Luracast\\Restler\\Format' => null,
			'Luracast\\Restler\\Data' => null,
			'Luracast\\Restler\\Filter' => null,
		);
		/**
		 * Singleton instance facility.
		 *
		 * @static
		 * @return AutoLoader the current instance or new instance if none exists.
		 */
		public static function instance()
		{
		}
		/**
		 * Helper function to add a path to the include path.
		 * AutoLoader uses the include path to discover classes.
		 *
		 * @static
		 *
		 * @param $path string absolute or relative path.
		 *
		 * @return bool false if the path cannot be resolved
		 *              or the resolved absolute path.
		 */
		public static function addPath($path)
		{
		}
		/**
		 * Other autoLoaders interfere and cause duplicate class loading.
		 * AutoLoader is capable enough to handle all standards so no need
		 * for others stumbling about.
		 *
		 * @return callable the one true auto loader.
		 */
		public static function thereCanBeOnlyOne()
		{
		}
		/**
		 * Seen this before cache handler.
		 * Facilitates both lookup and persist operations as well as convenience,
		 * load complete map functionality. The key can only be given a non falsy
		 * value once, this will be truthy for life.
		 *
		 * @param $key   mixed class name considered or a collection of
		 *               classMap entries
		 * @param $value mixed optional not required when doing a query on
		 *               key. Default is false we haven't seen this
		 *               class. Most of the time it will be the filename
		 *               for include and is set to true if we are unable
		 *               to load this class iow true == it does not exist.
		 *               value may also be a callable auto loader function.
		 *
		 * @return mixed The known value for the key or false if key has no value
		 */
		public static function seen($key, $value = false)
		{
		}
		/**
		 * Protected constructor to enforce singleton pattern.
		 * Populate a default include path.
		 * All possible includes cant possibly be catered for and if you
		 * require another path then simply add it calling set_include_path.
		 */
		protected function __construct()
		{
		}
		/**
		 * Auto loader callback through __invoke object as function.
		 *
		 * @param $className string class/interface name to auto load
		 *
		 * @return mixed|null the reference from the include or null
		 */
		public function __invoke($className)
		{
		}
	}
	/**
	 * Parses the PHPDoc comments for metadata. Inspired by `Documentor` code base.
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage Helper
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class CommentParser
	{
		/**
		 * name for the embedded data
		 *
		 * @var string
		 */
		public static $embeddedDataName = 'properties';
		/**
		 * Regular Expression pattern for finding the embedded data and extract
		 * the inner information. It is used with preg_match.
		 *
		 * @var string
		 */
		public static $embeddedDataPattern = '/```(\\w*)[\\s]*(([^`]*`{0,2}[^`]+)*)```/ms';
		/**
		 * Pattern will have groups for the inner details of embedded data
		 * this index is used to locate the data portion.
		 *
		 * @var int
		 */
		public static $embeddedDataIndex = 2;
		/**
		 * Delimiter used to split the array data.
		 *
		 * When the name portion is of the embedded data is blank auto detection
		 * will be used and if URLEncodedFormat is detected as the data format
		 * the character specified will be used as the delimiter to find split
		 * array data.
		 *
		 * @var string
		 */
		public static $arrayDelimiter = ',';
		/**
		 * @var array annotations that support array value
		 */
		public static $allowsArrayValue = array('choice' => true, 'select' => true, 'properties' => true);
		/**
		 * character sequence used to escape \@
		 */
		const escapedAtChar = '\\@';
		/**
		 * character sequence used to escape end of comment
		 */
		const escapedCommendEnd = '{@*}';
		/**
		 * Instance of Restler class injected at runtime.
		 *
		 * @var Restler
		 */
		public $restler;
		/**
		 * Parse the comment and extract the data.
		 *
		 * @static
		 *
		 * @param      $comment
		 * @param bool $isPhpDoc
		 *
		 * @return array associative array with the extracted values
		 */
		public static function parse($comment, $isPhpDoc = true)
		{
		}
		/**
		 * Removes the comment tags from each line of the comment.
		 *
		 * @static
		 *
		 * @param string $comment PhpDoc style comment
		 *
		 * @return string comments with out the tags
		 */
		public static function removeCommentTags($comment)
		{
		}
	}
}

namespace {
	/**
	 * Interface iAuthenticate only exists for compatibility mode for Restler 2 and below, it should
	 * not be used otherwise.
	 */
	interface iAuthenticate
	{
		public function __isAuthenticated();
	}
}

namespace Luracast\Restler {
	/**
	 * Interface for composing response
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage result
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iCompose
	{
		/**
		 * Result of an api call is passed to this method
		 * to create a standard structure for the data
		 *
		 * @param mixed $result can be a primitive or array or object
		 */
		public function response($result);
		/**
		 * When the api call results in RestException this method
		 * will be called to return the error message
		 *
		 * @param RestException $exception exception that has reasons for failure
		 *
		 * @return
		 */
		public function message(\Luracast\Restler\RestException $exception);
	}
	/**
	 * Default Composer to provide standard structure for all HTTP responses
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage result
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Compose implements \Luracast\Restler\iCompose
	{
		/**
		 * @var bool When restler is not running in production mode, this value will
		 * be checked to include the debug information on error response
		 */
		public static $includeDebugInfo = true;
		/**
		 * Current Restler instance
		 * Injected at runtime
		 *
		 * @var Restler
		 */
		public $restler;
		/**
		 * Result of an api call is passed to this method
		 * to create a standard structure for the data
		 *
		 * @param mixed $result can be a primitive or array or object
		 *
		 * @return mixed
		 */
		public function response($result)
		{
		}
		/**
		 * When the api call results in RestException this method
		 * will be called to return the error message
		 *
		 * @param RestException $exception exception that has reasons for failure
		 *
		 * @return array
		 */
		public function message(\Luracast\Restler\RestException $exception)
		{
		}
	}
}

namespace Luracast\Restler\Data {
	/**
	 * Restler is using many ValueObjects across to make it easy for the developers
	 * to use them with the help of code hinting etc.,
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iValueObject
	{
		/**
		 * This static method is called for creating an instance of the class by
		 * passing the initiation values as an array.
		 *
		 * @static
		 * @abstract
		 *
		 * @param array $properties
		 *
		 * @return iValueObject
		 */
		public static function __set_state(array $properties);
		/**
		 * This method provides a string representation for the instance
		 *
		 * @return string
		 */
		public function __toString();
	}
	/**
	 * ValueObject base class, you may use this class to create your
	 * iValueObjects quickly
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class ValueObject implements \Luracast\Restler\Data\iValueObject
	{
		public function __toString()
		{
		}
		public static function __set_state(array $properties)
		{
		}
		public function __toArray()
		{
		}
	}
	/**
	 * ValueObject for api method info. All needed information about a api method
	 * is stored here
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class ApiMethodInfo extends \Luracast\Restler\Data\ValueObject
	{
		/**
		 * @var string target url
		 */
		public $url;
		/**
		 * @var string
		 */
		public $className;
		/**
		 * @var string
		 */
		public $methodName;
		/**
		 * @var array parameters to be passed to the api method
		 */
		public $parameters = array();
		/**
		 * @var array information on parameters in the form of array(name => index)
		 */
		public $arguments = array();
		/**
		 * @var array default values for parameters if any
		 * in the form of array(index => value)
		 */
		public $defaults = array();
		/**
		 * @var array key => value pair of method meta information
		 */
		public $metadata = array();
		/**
		 * @var int access level
		 * 0 - @public - available for all
		 * 1 - @hybrid - both public and protected (enhanced info for authorized)
		 * 2 - @protected comment - only for authenticated users
		 * 3 - protected method - only for authenticated users
		 */
		public $accessLevel = 0;
	}
	/**
	 * Convenience class for Array manipulation
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Arr
	{
		/**
		 * Deep copy given array
		 *
		 * @param array $arr
		 *
		 * @return array
		 */
		public static function copy(array $arr)
		{
		}
	}
	/**
	 * Invalid Exception
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Invalid extends \Exception
	{
	}
	/**
	 * Validation classes should implement this interface
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iValidate
	{
		/**
		 * method used for validation.
		 *
		 * @param mixed $input
		 *            data that needs to be validated
		 * @param ValidationInfo $info
		 *            information to be used for validation
		 * @return boolean false in case of failure or fixed value in the expected
		 *         type
		 * @throws \Luracast\Restler\RestException 400 with information about the
		 * failed
		 * validation
		 */
		public static function validate($input, \Luracast\Restler\Data\ValidationInfo $info);
	}
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
		public static function toArray($object, $forceObjectTypeWhenEmpty = false)
		{
		}
		public function __get($name)
		{
		}
		public function __set($name, $function)
		{
		}
		public function __isset($name)
		{
		}
		public function __unset($name)
		{
		}
	}
	/**
	 * Convenience class for String manipulation
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Text
	{
		/**
		 * Given haystack contains the needle or not?
		 *
		 * @param string $haystack
		 * @param string $needle
		 * @param bool $caseSensitive
		 *
		 * @return bool
		 */
		public static function contains($haystack, $needle, $caseSensitive = true)
		{
		}
		/**
		 * Given haystack begins with the needle or not?
		 *
		 * @param string $haystack
		 * @param string $needle
		 *
		 * @return bool
		 */
		public static function beginsWith($haystack, $needle)
		{
		}
		/**
		 * Given haystack ends with the needle or not?
		 *
		 * @param string $haystack
		 * @param string $needle
		 *
		 * @return bool
		 */
		public static function endsWith($haystack, $needle)
		{
		}
		/**
		 * Convert camelCased or underscored string in to a title
		 *
		 * @param string $name
		 *
		 * @return string
		 */
		public static function title($name)
		{
		}
		/**
		 * Convert given string to be used as a slug or css class
		 *
		 * @param string $name
		 * @return string
		 */
		public static function slug($name)
		{
		}
	}
	/**
	 * ValueObject for validation information. An instance is created and
	 * populated by Restler to pass it to iValidate implementing classes for
	 * validation
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class ValidationInfo implements \Luracast\Restler\Data\iValueObject
	{
		/**
		 * @var mixed given value for the parameter
		 */
		public $value;
		/**
		 * @var string proper name for given parameter
		 */
		public $label;
		/**
		 * @var string html element that can be used to represent the parameter for
		 *             input
		 */
		public $field;
		/**
		 * @var mixed default value for the parameter
		 */
		public $default;
		/**
		 * Name of the variable being validated
		 *
		 * @var string variable name
		 */
		public $name;
		/**
		 * @var bool is it required or not
		 */
		public $required;
		/**
		 * @var string body or header or query where this parameter is coming from
		 * in the http request
		 */
		public $from;
		/**
		 * Data type of the variable being validated.
		 * It will be mostly string
		 *
		 * @var string|array multiple types are specified it will be of
		 *      type array otherwise it will be a string
		 */
		public $type;
		/**
		 * When the type is array, this field is used to define the type of the
		 * contents of the array
		 *
		 * @var string|null when all the items in an array are of certain type, we
		 * can set this property. It will be null if the items can be of any type
		 */
		public $contentType;
		/**
		 * Should we attempt to fix the value?
		 * When set to false validation class should throw
		 * an exception or return false for the validate call.
		 * When set to true it will attempt to fix the value if possible
		 * or throw an exception or return false when it cant be fixed.
		 *
		 * @var boolean true or false
		 */
		public $fix = false;
		/**
		 * @var array of children to be validated
		 */
		public $children = null;
		// ==================================================================
		//
		// VALUE RANGE
		//
		// ------------------------------------------------------------------
		/**
		 * Given value should match one of the values in the array
		 *
		 * @var array of choices to match to
		 */
		public $choice;
		/**
		 * If the type is string it will set the lower limit for length
		 * else will specify the lower limit for the value
		 *
		 * @var number minimum value
		 */
		public $min;
		/**
		 * If the type is string it will set the upper limit limit for length
		 * else will specify the upper limit for the value
		 *
		 * @var number maximum value
		 */
		public $max;
		// ==================================================================
		//
		// REGEX VALIDATION
		//
		// ------------------------------------------------------------------
		/**
		 * RegEx pattern to match the value
		 *
		 * @var string regular expression
		 */
		public $pattern;
		// ==================================================================
		//
		// CUSTOM VALIDATION
		//
		// ------------------------------------------------------------------
		/**
		 * Rules specified for the parameter in the php doc comment.
		 * It is passed to the validation method as the second parameter
		 *
		 * @var array custom rule set
		 */
		public $rules;
		/**
		 * Specifying a custom error message will override the standard error
		 * message return by the validator class
		 *
		 * @var string custom error response
		 */
		public $message;
		// ==================================================================
		//
		// METHODS
		//
		// ------------------------------------------------------------------
		/**
		 * Name of the method to be used for validation.
		 * It will be receiving two parameters $input, $rules (array)
		 *
		 * @var string validation method name
		 */
		public $method;
		/**
		 * Instance of the API class currently being called. It will be null most of
		 * the time. Only when method is defined it will contain an instance.
		 * This behavior is for lazy loading of the API class
		 *
		 * @var null|object will be null or api class instance
		 */
		public $apiClassInstance = null;
		public static function numericValue($value)
		{
		}
		public static function arrayValue($value)
		{
		}
		public static function stringValue($value, $glue = ',')
		{
		}
		public static function booleanValue($value)
		{
		}
		public static function filterArray(array $data, $keepNumericKeys)
		{
		}
		public function __toString()
		{
		}
		public function __construct(array $info)
		{
		}
		/**
		 * Magic Method used for creating instance at run time
		 */
		public static function __set_state(array $info)
		{
		}
	}
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
	class Validator implements \Luracast\Restler\Data\iValidate
	{
		public static $holdException = false;
		public static $exceptions = array();
		public static $preFilters = array(
			//'*'            => 'some_global_filter', //applied to all parameters
			'string' => 'trim',
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
		public static function alpha($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function uuid($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function alphanumeric($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function printable($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function hex($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function color($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function tel($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function email($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function ip($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function url($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function date($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function datetime($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function time24($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function time($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function time12($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function timestamp($input, \Luracast\Restler\Data\ValidationInfo $info = null)
		{
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
		public static function validate($input, \Luracast\Restler\Data\ValidationInfo $info, $full = null)
		{
		}
	}
}

namespace Luracast\Restler {
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
		public static $accessControlAllowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD';
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
		public static $overridables = array('suppressResponseCode');
		/**
		 * @var array contains validation details for defaults to be used when
		 * set through URL parameters
		 */
		public static $validation = array('suppressResponseCode' => array('type' => 'bool'), 'headerExpires' => array('type' => 'int', 'min' => 0));
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
		}
	}
	class EventDispatcher
	{
		protected static $_waitList = array();
		public static $self;
		protected $events = array();
		public function __construct()
		{
		}
		public static function __callStatic($eventName, $params)
		{
		}
		public function __call($eventName, $params)
		{
		}
		public static function addListener($eventName, \Closure $callback)
		{
		}
		public function on(array $eventHandlers)
		{
		}
		/**
		 * Fire an event to notify all listeners
		 *
		 * @param string $eventName name of the event
		 * @param array  $params    event related data
		 */
		protected function dispatch($eventName, array $params = array())
		{
		}
	}
	/**
	 * Interface iProvideMultiVersionApi
	 * @package Luracast\Restler
	 *
	 *
	 */
	interface iProvideMultiVersionApi
	{
		/**
		 * Maximum api version supported by the api class
		 * @return int
		 */
		public static function __getMaximumSupportedVersion();
	}
	/**
	 * Class Explorer
	 *
	 * @package Luracast\Restler
	 *
	 * @access  hybrid
	 * @version 3.0.0rc6
	 */
	class Explorer implements \Luracast\Restler\iProvideMultiVersionApi
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
		public static $infoClass = 'Luracast\\Restler\\ExplorerInfo';
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
			'int' => 'integer',
			'number' => 'number',
			'float' => array('number', 'float'),
			'bool' => 'boolean',
			//'boolean' => 'boolean',
			//'NULL' => 'null',
			'array' => 'array',
			//'object' => 'object',
			'stdClass' => 'object',
			'mixed' => 'string',
			'date' => array('string', 'date'),
			'datetime' => array('string', 'date-time'),
		);
		/**
		 * @var array configurable symbols to differentiate public, hybrid and
		 * protected api
		 */
		public static $apiDescriptionSuffixSymbols = array(
			0 => ' 🔓',
			//'&nbsp; <i class="fa fa-lg fa-unlock-alt"></i>', //public api
			1 => ' ◑',
			//'&nbsp; <i class="fa fa-lg fa-adjust"></i>', //hybrid api
			2 => ' 🔐',
		);
		protected $models = array();
		/**
		 * @var bool|stdClass
		 */
		protected $_fullDataRequested = false;
		protected $crud = array('POST' => 'create', 'GET' => 'retrieve', 'PUT' => 'update', 'DELETE' => 'delete', 'PATCH' => 'partial update');
		protected static $prefixes = array('get' => 'retrieve', 'index' => 'list', 'post' => 'create', 'put' => 'update', 'patch' => 'modify', 'delete' => 'remove');
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
		}
		/**
		 * @return stdClass
		 */
		public function swagger()
		{
		}
		/**
		 * Maximum api version supported by the api class
		 * @return int
		 */
		public static function __getMaximumSupportedVersion()
		{
		}
	}
	/**
	 * Class ExplorerInfo
	 * @package    Luracast\Restler
	 *
	 * @version    3.0.0rc6
	 */
	class ExplorerInfo
	{
		public static $title = 'Restler API Explorer';
		public static $description = 'Live API Documentation';
		public static $termsOfService = null;
		public static $contact = array('name' => 'Restler Support', 'url' => 'luracast.com/products/restler', 'email' => 'arul@luracast.com');
		public static $license = array('name' => 'LGPL-2.1', 'url' => 'https://www.gnu.org/licenses/old-licenses/lgpl-2.1.html');
	}
	/**
	 * Interface for creating classes that perform authentication/access
	 * verification
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage auth
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iFilter
	{
		/**
		 * Access verification method.
		 *
		 * API access will be denied when this method returns false
		 *
		 * @abstract
		 * @return boolean true when api access is allowed false otherwise
		 */
		public function __isAllowed();
	}
	/**
	 * Api classes or filter classes can implement this interface to know about
	 * authentication status
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iUseAuthentication
	{
		/**
		 * This method will be called first for filter classes and api classes so
		 * that they can respond accordingly for filer method call and api method
		 * calls
		 *
		 * @abstract
		 *
		 * @param bool $isAuthenticated passes true when the authentication is
		 * done false otherwise
		 *
		 * @return mixed
		 */
		public function __setAuthenticationStatus($isAuthenticated = false);
	}
}

namespace Luracast\Restler\Filter {
	/**
	 * Describe the purpose of this class/interface/trait
	 *
	 * @category   Framework
	 * @package    restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class RateLimit implements \Luracast\Restler\iFilter, \Luracast\Restler\iUseAuthentication
	{
		/**
		 * @var \Luracast\Restler\Restler;
		 */
		public $restler;
		/**
		 * @var int
		 */
		public static $usagePerUnit = 1200;
		/**
		 * @var int
		 */
		public static $authenticatedUsagePerUnit = 5000;
		/**
		 * @var string
		 */
		public static $unit = 'hour';
		/**
		 * @var string group the current api belongs to
		 */
		public static $group = 'common';
		protected static $units = array(
			'second' => 1,
			'minute' => 60,
			'hour' => 3600,
			// 60*60 seconds
			'day' => 86400,
			// 60*60*24 seconds
			'week' => 604800,
			// 60*60*24*7 seconds
			'month' => 2592000,
		);
		/**
		 * @var array all paths beginning with any of the following will be excluded
		 * from documentation
		 */
		public static $excludedPaths = array('explorer');
		/**
		 * @param string $unit
		 * @param int    $usagePerUnit
		 * @param int    $authenticatedUsagePerUnit set it to false to give unlimited access
		 *
		 * @throws \InvalidArgumentException
		 * @return void
		 */
		public static function setLimit($unit, $usagePerUnit, $authenticatedUsagePerUnit = null)
		{
		}
		public function __isAllowed()
		{
		}
		public function __setAuthenticationStatus($isAuthenticated = false)
		{
		}
	}
}

namespace Luracast\Restler {
	/**
	 * Storing and retrieving a message or array of key value pairs for one time use using $_SESSION
	 *
	 * They are typically used in view templates when using HtmlFormat
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Flash implements \ArrayAccess
	{
		const SUCCESS = 'success';
		const INFO = 'info';
		const WARNING = 'warning';
		const DANGER = 'danger';
		/**
		 * Flash a success message to user
		 *
		 * @param string $message
		 * @param string $header
		 *
		 * @return Flash
		 */
		public static function success($message, $header = '')
		{
		}
		/**
		 * Flash a info message to user
		 *
		 * @param string $message
		 * @param string $header
		 *
		 * @return Flash
		 */
		public static function info($message, $header = '')
		{
		}
		/**
		 * Flash a warning message to user
		 *
		 * @param string $message
		 * @param string $header
		 *
		 * @return Flash
		 */
		public static function warning($message, $header = '')
		{
		}
		/**
		 * Flash a error message to user
		 *
		 * @param string $message
		 * @param string $header
		 *
		 * @return Flash
		 */
		public static function danger($message, $header = '')
		{
		}
		/**
		 * Flash a message to user
		 *
		 * @param string $text message text
		 * @param string $header
		 * @param string $type
		 *
		 * @return Flash
		 */
		public static function message($text, $header = '', $type = \Luracast\Restler\Flash::WARNING)
		{
		}
		/**
		 * Set some data for one time use
		 *
		 * @param array $data array of key value pairs {@type associative}
		 *
		 * @return Flash
		 */
		public static function set(array $data)
		{
		}
		public function __get($name)
		{
		}
		public function __isset($name)
		{
		}
		public function __destruct()
		{
		}
		/**
		 * Specify data which should be serialized to JSON
		 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
		 * @return mixed data which can be serialized by <b>json_encode</b>,
		 * which is a value of any type other than a resource.
		 */
		public function jsonSerialize()
		{
		}
		public function offsetExists($offset)
		{
		}
		public function offsetGet($offset)
		{
		}
		public function offsetSet($offset, $value)
		{
		}
		public function offsetUnset($offset)
		{
		}
	}
}

namespace Luracast\Restler\Format {
	/**
	 * Interface for creating custom data formats
	 * like xml, json, yaml, amf etc
	 * @category   Framework
	 * @package    Restler
	 * @subpackage format
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iFormat
	{
		/**
		 * Get MIME type => Extension mappings as an associative array
		 *
		 * @return array list of mime strings for the format
		 * @example array('application/json'=>'json');
		 */
		public function getMIMEMap();
		/**
		 * Set the selected MIME type
		 *
		 * @param string $mime
		 *            MIME type
		 */
		public function setMIME($mime);
		/**
		 * Content-Type field of the HTTP header can send a charset
		 * parameter in the HTTP header to specify the character
		 * encoding of the document.
		 * This information is passed
		 * here so that Format class can encode data accordingly
		 * Format class may choose to ignore this and use its
		 * default character set.
		 *
		 * @param string $charset
		 *            Example utf-8
		 */
		public function setCharset($charset);
		/**
		 * Content-Type accepted by the Format class
		 *
		 * @return string $charset Example utf-8
		 */
		public function getCharset();
		/**
		 * Get selected MIME type
		 */
		public function getMIME();
		/**
		 * Set the selected file extension
		 *
		 * @param string $extension
		 *            file extension
		 */
		public function setExtension($extension);
		/**
		 * Get the selected file extension
		 *
		 * @return string file extension
		 */
		public function getExtension();
		/**
		 * Encode the given data in the format
		 *
		 * @param array $data
		 *            resulting data that needs to
		 *            be encoded in the given format
		 * @param boolean $humanReadable
		 *            set to TRUE when restler
		 *            is not running in production mode. Formatter has to
		 *            make the encoded output more human readable
		 * @return string encoded string
		 */
		public function encode($data, $humanReadable = false);
		/**
		 * Decode the given data from the format
		 *
		 * @param string $data
		 *            data sent from client to
		 *            the api in the given format.
		 * @return array associative array of the parsed data
		 */
		public function decode($data);
		/**
		 * @return boolean is parsing the request supported?
		 */
		public function isReadable();
		/**
		 * @return boolean is composing response supported?
		 */
		public function isWritable();
	}
	/**
	 * Abstract class to implement common methods of iFormat
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	abstract class Format implements \Luracast\Restler\Format\iFormat
	{
		/**
		 * override in the extending class
		 */
		const MIME = 'text/plain';
		/**
		 * override in the extending class
		 */
		const EXTENSION = 'txt';
		/**
		 * @var string charset encoding defaults to UTF8
		 */
		protected $charset = 'utf-8';
		/**
		 * Injected at runtime
		 *
		 * @var \Luracast\Restler\Restler
		 */
		public $restler;
		/**
		 * Get MIME type => Extension mappings as an associative array
		 *
		 * @return array list of mime strings for the format
		 * @example array('application/json'=>'json');
		 */
		public function getMIMEMap()
		{
		}
		/**
		 * Set the selected MIME type
		 *
		 * @param string $mime
		 *            MIME type
		 */
		public function setMIME($mime)
		{
		}
		/**
		 * Content-Type field of the HTTP header can send a charset
		 * parameter in the HTTP header to specify the character
		 * encoding of the document.
		 * This information is passed
		 * here so that Format class can encode data accordingly
		 * Format class may choose to ignore this and use its
		 * default character set.
		 *
		 * @param string $charset
		 *            Example utf-8
		 */
		public function setCharset($charset)
		{
		}
		/**
		 * Content-Type accepted by the Format class
		 *
		 * @return string $charset Example utf-8
		 */
		public function getCharset()
		{
		}
		/**
		 * Get selected MIME type
		 */
		public function getMIME()
		{
		}
		/**
		 * Set the selected file extension
		 *
		 * @param string $extension
		 *            file extension
		 */
		public function setExtension($extension)
		{
		}
		/**
		 * Get the selected file extension
		 *
		 * @return string file extension
		 */
		public function getExtension()
		{
		}
		/**
		 * @return boolean is parsing the request supported?
		 */
		public function isReadable()
		{
		}
		/**
		 * @return boolean is composing response supported?
		 */
		public function isWritable()
		{
		}
		public function __toString()
		{
		}
	}
	abstract class DependentFormat extends \Luracast\Restler\Format\Format
	{
		/**
		 * override in the extending class
		 *
		 * @example symfony/yaml:*
		 */
		const PACKAGE_NAME = 'vendor/project:version';
		/**
		 * override in the extending class
		 *
		 * fully qualified class name
		 */
		const EXTERNAL_CLASS = 'Namespace\\ClassName';
		/**
		 * Get external class => packagist package name as an associative array
		 *
		 * @return array list of dependencies for the format
		 */
		public function getDependencyMap()
		{
		}
		protected function checkDependency($class = null)
		{
		}
		public function __construct()
		{
		}
	}
	/**
	 * AMF Binary Format for Restler Framework.
	 * Native format supported by Adobe Flash and Adobe AIR
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
	class AmfFormat extends \Luracast\Restler\Format\DependentFormat
	{
		const MIME = 'application/x-amf';
		const EXTENSION = 'amf';
		const PACKAGE_NAME = 'zendframework/zendamf:dev-master';
		const EXTERNAL_CLASS = 'ZendAmf\\Parser\\Amf3\\Deserializer';
		public function encode($data, $humanReadable = false)
		{
		}
		public function decode($data)
		{
		}
	}
	/**
	 * Interface for creating formats that accept steams for decoding
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
	interface iDecodeStream
	{
		/**
		 * Decode the given data stream
		 *
		 * @param string $stream A stream resource with data
		 *                       sent from client to the api
		 *                       in the given format.
		 *
		 * @return array associative array of the parsed data
		 */
		public function decodeStream($stream);
	}
	/**
	 * Comma Separated Value Format
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
	class CsvFormat extends \Luracast\Restler\Format\Format implements \Luracast\Restler\Format\iDecodeStream
	{
		const MIME = 'text/csv';
		const EXTENSION = 'csv';
		public static $delimiter = ',';
		public static $enclosure = '"';
		public static $escape = '\\';
		public static $haveHeaders = null;
		/**
		 * Encode the given data in the csv format
		 *
		 * @param array   $data
		 *            resulting data that needs to
		 *            be encoded in the given format
		 * @param boolean $humanReadable
		 *            set to TRUE when restler
		 *            is not running in production mode. Formatter has to
		 *            make the encoded output more human readable
		 *
		 * @return string encoded string
		 *
		 * @throws RestException 500 on unsupported data
		 */
		public function encode($data, $humanReadable = false)
		{
		}
		protected static function putRow($data)
		{
		}
		/**
		 * Decode the given data from the csv format
		 *
		 * @param string $data
		 *            data sent from client to
		 *            the api in the given format.
		 *
		 * @return array associative array of the parsed data
		 */
		public function decode($data)
		{
		}
		protected static function getRow($data, $keys = false)
		{
		}
		/**
		 * Decode the given data stream
		 *
		 * @param string $stream A stream resource with data
		 *                       sent from client to the api
		 *                       in the given format.
		 *
		 * @return array associative array of the parsed data
		 */
		public function decodeStream($stream)
		{
		}
	}
	/**
	 * Describe the purpose of this class/interface/trait
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	abstract class MultiFormat implements \Luracast\Restler\Format\iFormat
	{
		/**
		 * override in the extending class
		 */
		const MIME = 'text/plain,text/html';
		/**
		 * override in the extending class
		 */
		const EXTENSION = 'txt,html';
		/**
		 * @var string charset encoding defaults to UTF8
		 */
		protected $charset = 'utf-8';
		public static $mime;
		public static $extension;
		/**
		 * Injected at runtime
		 *
		 * @var \Luracast\Restler\Restler
		 */
		public $restler;
		/**
		 * Get MIME type => Extension mappings as an associative array
		 *
		 * @return array list of mime strings for the format
		 * @example array('application/json'=>'json');
		 */
		public function getMIMEMap()
		{
		}
		/**
		 * Set the selected MIME type
		 *
		 * @param string $mime
		 *            MIME type
		 */
		public function setMIME($mime)
		{
		}
		/**
		 * Content-Type field of the HTTP header can send a charset
		 * parameter in the HTTP header to specify the character
		 * encoding of the document.
		 * This information is passed
		 * here so that Format class can encode data accordingly
		 * Format class may choose to ignore this and use its
		 * default character set.
		 *
		 * @param string $charset
		 *            Example utf-8
		 */
		public function setCharset($charset)
		{
		}
		/**
		 * Content-Type accepted by the Format class
		 *
		 * @return string $charset Example utf-8
		 */
		public function getCharset()
		{
		}
		/**
		 * Get selected MIME type
		 */
		public function getMIME()
		{
		}
		/**
		 * Set the selected file extension
		 *
		 * @param string $extension
		 *            file extension
		 */
		public function setExtension($extension)
		{
		}
		/**
		 * Get the selected file extension
		 *
		 * @return string file extension
		 */
		public function getExtension()
		{
		}
		/**
		 * @return boolean is parsing the request supported?
		 */
		public function isReadable()
		{
		}
		/**
		 * @return boolean is composing response supported?
		 */
		public function isWritable()
		{
		}
		public function __toString()
		{
		}
	}
	abstract class DependentMultiFormat extends \Luracast\Restler\Format\MultiFormat
	{
		/**
		 * Get external class => packagist package name as an associative array
		 *
		 * @return array list of dependencies for the format
		 *
		 * @example return ['Illuminate\\View\\View' => 'illuminate/view:4.2.*']
		 */
		abstract public function getDependencyMap();
		protected function checkDependency($class = null)
		{
		}
		public function __construct()
		{
		}
	}
	/**
	 * Html template format
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
	class HtmlFormat extends \Luracast\Restler\Format\DependentFormat
	{
		const BLADE = 'Illuminate\\View\\View';
		const TWIG = 'Twig\\Environment';
		const MUSTACHE = 'Mustache_Engine';
		public static $mime = 'text/html';
		public static $extension = 'html';
		public static $view;
		public static $errorView = 'debug.php';
		public static $template = 'php';
		public static $handleSession = true;
		public static $convertResponseToArray = false;
		public static $useSmartViews = true;
		/**
		 * @var null|string defaults to template named folder in Defaults::$cacheDirectory
		 */
		public static $cacheDirectory = null;
		/**
		 * @var array global key value pair to be supplied to the templates. All
		 * keys added here will be available as a variable inside the template
		 */
		public static $data = array();
		/**
		 * @var string set it to the location of your the view files. Defaults to
		 * views folder which is same level as vendor directory.
		 */
		public static $viewPath;
		/**
		 * @var array template and its custom extension key value pair
		 */
		public static $customTemplateExtensions = array('blade' => 'blade.php');
		/**
		 * @var bool used internally for error handling
		 */
		protected static $parseViewMetadata = true;
		/**
		 * @var Restler;
		 */
		public $restler;
		public function __construct()
		{
		}
		public function getDependencyMap()
		{
		}
		public static function blade(array $data, $debug = true)
		{
		}
		/**
		 * @param array|object $data
		 * @param bool         $debug
		 *
		 * @return string
		 * @throws \Twig\Error\LoaderError
		 * @throws \Twig\Error\RuntimeError
		 * @throws \Twig\Error\SyntaxError
		 */
		public static function twig($data, $debug = true)
		{
		}
		/**
		 * @param array|object $data
		 * @param bool         $debug
		 *
		 * @return string
		 */
		public static function handlebar($data, $debug = true)
		{
		}
		/**
		 * @param array|object $data
		 * @param bool         $debug
		 *
		 * @return string
		 */
		public static function mustache($data, $debug = true)
		{
		}
		/**
		 * @param array|object $data
		 * @param bool         $debug
		 *
		 * @return string
		 * @throws RestException
		 */
		public static function php($data, $debug = true)
		{
		}
		/**
		 * Encode the given data in the format
		 *
		 * @param array   $data                resulting data that needs to
		 *                                     be encoded in the given format
		 * @param boolean $humanReadable       set to TRUE when restler
		 *                                     is not running in production mode.
		 *                                     Formatter has to make the encoded
		 *                                     output more human readable
		 *
		 * @return string encoded string
		 * @throws \Exception
		 */
		public function encode($data, $humanReadable = false)
		{
		}
		public static function guessViewName($path)
		{
		}
		public static function getViewExtension()
		{
		}
		public static function getViewFile($fullPath = false, $includeExtension = true)
		{
		}
		/**
		 * Decode the given data from the format
		 *
		 * @param string $data
		 *            data sent from client to
		 *            the api in the given format.
		 *
		 * @return array associative array of the parsed data
		 *
		 * @throws RestException
		 */
		public function decode($data)
		{
		}
		/**
		 * @return bool false as HTML format is write only
		 */
		public function isReadable()
		{
		}
		/**
		 * Get MIME type => Extension mappings as an associative array
		 *
		 * @return array list of mime strings for the format
		 * @example array('application/json'=>'json');
		 */
		public function getMIMEMap()
		{
		}
		/**
		 * Set the selected MIME type
		 *
		 * @param string $mime MIME type
		 */
		public function setMIME($mime)
		{
		}
		/**
		 * Get selected MIME type
		 */
		public function getMIME()
		{
		}
		/**
		 * Get the selected file extension
		 *
		 * @return string file extension
		 */
		public function getExtension()
		{
		}
		/**
		 * Set the selected file extension
		 *
		 * @param string $extension file extension
		 */
		public function setExtension($extension)
		{
		}
	}
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
	class JsonFormat extends \Luracast\Restler\Format\Format
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
		}
		public function decode($data)
		{
		}
		/**
		 * Throws an exception if an error occurred during the last JSON encoding/decoding
		 *
		 * @return void
		 * @throws \RuntimeException
		 */
		protected function handleJsonError()
		{
		}
	}
	/**
	 * Javascript Object Notation Packaged in a method (JSONP)
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
	class JsFormat extends \Luracast\Restler\Format\JsonFormat
	{
		const MIME = 'text/javascript';
		const EXTENSION = 'js';
		public static $callbackMethodName = 'parseResponse';
		public static $callbackOverrideQueryString = 'callback';
		public static $includeHeaders = true;
		public function encode($data, $human_readable = false)
		{
		}
		public function isReadable()
		{
		}
	}
	/**
	 * Plist Format for Restler Framework.
	 * Plist is the native data exchange format for Apple iOS and Mac platform.
	 * Use this format to talk to mac applications and iOS devices.
	 * This class is capable of serving both xml plist and binary plist.
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
	class PlistFormat extends \Luracast\Restler\Format\DependentMultiFormat
	{
		/**
		 * @var boolean set it to true binary plist is preferred
		 */
		public static $compact = null;
		const MIME = 'application/xml,application/x-plist';
		const EXTENSION = 'plist';
		public function setMIME($mime)
		{
		}
		/**
		 * Encode the given data in plist format
		 *
		 * @param array   $data
		 *            resulting data that needs to
		 *            be encoded in plist format
		 * @param boolean $humanReadable
		 *            set to true when restler
		 *            is not running in production mode. Formatter has to
		 *            make the encoded output more human readable
		 *
		 * @return string encoded string
		 */
		public function encode($data, $humanReadable = false)
		{
		}
		/**
		 * Decode the given data from plist format
		 *
		 * @param string $data
		 *            data sent from client to
		 *            the api in the given format.
		 *
		 * @return array associative array of the parsed data
		 */
		public function decode($data)
		{
		}
		/**
		 * Get external class => packagist package name as an associative array
		 *
		 * @return array list of dependencies for the format
		 *
		 * @example return ['Illuminate\\View\\View' => 'illuminate/view:4.2.*']
		 */
		public function getDependencyMap()
		{
		}
	}
	/**
	 * Tab Separated Value Format
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
	class TsvFormat extends \Luracast\Restler\Format\CsvFormat
	{
		const MIME = 'text/csv';
		const EXTENSION = 'csv';
		public static $delimiter = "\t";
		public static $enclosure = '"';
		public static $escape = '\\';
		public static $haveHeaders = null;
	}
	/**
	 * Support for Multi Part Form Data and File Uploads
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
	class UploadFormat extends \Luracast\Restler\Format\Format
	{
		const MIME = 'multipart/form-data';
		const EXTENSION = 'post';
		public static $errors = array(0 => false, 1 => "The uploaded file exceeds the maximum allowed size", 2 => "The uploaded file exceeds the maximum allowed size", 3 => "The uploaded file was only partially uploaded", 4 => "No file was uploaded", 6 => "Missing a temporary folder", 7 => "Failed to write file to disk", 8 => "A PHP extension stopped the file upload");
		/**
		 * use it if you need to restrict uploads based on file type
		 * setting it as an empty array allows all file types
		 * default is to allow only png and jpeg images
		 *
		 * @var array
		 */
		public static $allowedMimeTypes = array('image/jpeg', 'image/png');
		/**
		 * use it to restrict uploads based on file size
		 * set it to 0 to allow all sizes
		 * please note that it upload restrictions in the server
		 * takes precedence so it has to be lower than or equal to that
		 * default value is 1MB (1024x1024)bytes
		 * usual value for the server is 8388608
		 *
		 * @var int
		 */
		public static $maximumFileSize = 1048576;
		/**
		 * Your own validation function for validating each uploaded file
		 * it can return false or throw an exception for invalid file
		 * use anonymous function / closure in PHP 5.3 and above
		 * use function name in other cases
		 *
		 * @var Callable
		 */
		public static $customValidationFunction;
		/**
		 * Since exceptions are triggered way before at the `get` stage
		 *
		 * @var bool
		 */
		public static $suppressExceptionsAsError = false;
		protected static function checkFile(&$file, $doMimeCheck = false, $doSizeCheck = false)
		{
		}
		public function encode($data, $humanReadable = false)
		{
		}
		public function decode($data)
		{
		}
		public function isWritable()
		{
		}
	}
	/**
	 * URL Encoded String Format
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
	class UrlEncodedFormat extends \Luracast\Restler\Format\Format
	{
		const MIME = 'application/x-www-form-urlencoded';
		const EXTENSION = 'post';
		public function encode($data, $humanReadable = false)
		{
		}
		public function decode($data)
		{
		}
		public static function encoderTypeFix(array $data)
		{
		}
		public static function decoderTypeFix(array $data)
		{
		}
	}
	/**
	 * XML Markup Format for Restler Framework
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
	class XmlFormat extends \Luracast\Restler\Format\Format
	{
		const MIME = 'application/xml';
		const EXTENSION = 'xml';
		// ==================================================================
		//
		// Properties related to reading/parsing/decoding xml
		//
		// ------------------------------------------------------------------
		public static $importSettingsFromXml = false;
		public static $parseAttributes = true;
		public static $parseNamespaces = true;
		public static $parseTextNodeAsProperty = true;
		// ==================================================================
		//
		// Properties related to writing/encoding xml
		//
		// ------------------------------------------------------------------
		public static $useTextNodeProperty = true;
		public static $useNamespaces = true;
		public static $cdataNames = array();
		// ==================================================================
		//
		// Common Properties
		//
		// ------------------------------------------------------------------
		public static $attributeNames = array();
		public static $textNodeName = 'text';
		public static $namespaces = array();
		public static $namespacedProperties = array();
		/**
		 * Default name for the root node.
		 *
		 * @var string $rootNodeName
		 */
		public static $rootName = 'response';
		public static $defaultTagName = 'item';
		/**
		 * When you decode an XML its structure is copied to the static vars
		 * we can use this function to echo them out and then copy paste inside
		 * our service methods
		 *
		 * @return string PHP source code to reproduce the configuration
		 */
		public static function exportCurrentSettings()
		{
		}
		public function encode($data, $humanReadable = false)
		{
		}
		public function write(\XMLWriter $xml, $data, $parent)
		{
		}
		public function decode($data)
		{
		}
		public function read(\SimpleXMLElement $xml, $namespaces = null)
		{
		}
		public static function setType($value)
		{
		}
	}
	/**
	 * YAML Format for Restler Framework
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
	class YamlFormat extends \Luracast\Restler\Format\DependentFormat
	{
		const MIME = 'text/plain';
		const EXTENSION = 'yaml';
		const PACKAGE_NAME = 'symfony/yaml:*';
		const EXTERNAL_CLASS = 'Symfony\\Component\\Yaml\\Yaml';
		public function encode($data, $humanReadable = false)
		{
		}
		public function decode($data)
		{
		}
	}
}

namespace Luracast\Restler {
	/**
	 * Default Cache that writes/reads human readable files for caching purpose
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class HumanReadableCache implements \Luracast\Restler\iCache
	{
		/**
		 * @var string path of the folder to hold cache files
		 */
		public static $cacheDir;
		public function __construct()
		{
		}
		/**
		 * store data in the cache
		 *
		 * @param string $name
		 * @param mixed  $data
		 *
		 * @throws \Exception
		 * @return boolean true if successful
		 */
		public function set($name, $data)
		{
		}
		/**
		 * retrieve data from the cache
		 *
		 * @param string $name
		 * @param bool   $ignoreErrors
		 *
		 * @return mixed
		 */
		public function get($name, $ignoreErrors = false)
		{
		}
		/**
		 * delete data from the cache
		 *
		 * @param string $name
		 * @param bool   $ignoreErrors
		 *
		 * @return boolean true if successful
		 */
		public function clear($name, $ignoreErrors = false)
		{
		}
		/**
		 * check if the given name is cached
		 *
		 * @param string $name
		 *
		 * @return boolean true if cached
		 */
		public function isCached($name)
		{
		}
	}
	/**
	 * Interface for creating authentication classes
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage auth
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iAuthenticate extends \Luracast\Restler\iFilter
	{
		/**
		 * @return string string to be used with WWW-Authenticate header
		 * @example Basic
		 * @example Digest
		 * @example OAuth
		 */
		public function __getWWWAuthenticateString();
	}
	/**
	 * Interface to identify the user
	 *
	 * When the user is known we will be able to monitor, rate limit and do more
	 *
	 * @category   Framework
	 * @package    restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	interface iIdentifyUser
	{
		/**
		 * A way to uniquely identify the current api consumer
		 *
		 * When his user id is known it should be used otherwise ip address
		 * can be used
		 *
		 * @param bool $includePlatform Should we consider user alone or should
		 *                              consider the application/platform/device
		 *                              as well for generating unique id
		 *
		 * @return string
		 */
		public static function getUniqueIdentifier($includePlatform = false);
		/**
		 * User identity to be used for caching purpose
		 *
		 * When the dynamic cache service places an object in the cache, it needs to
		 * label it with a unique identifying string known as a cache ID. This
		 * method gives that identifier
		 *
		 * @return string
		 */
		public static function getCacheIdentifier();
		/**
		 * Authentication classes should call this method
		 *
		 * @param string $id user id as identified by the authentication classes
		 *
		 * @return void
		 */
		public static function setUniqueIdentifier($id);
		/**
		 * User identity for caching purpose
		 *
		 * In a role based access control system this will be based on role
		 *
		 * @param $id
		 *
		 * @return void
		 */
		public static function setCacheIdentifier($id);
	}
	/**
	 * Special Exception for raising API errors
	 * that can be used in API methods
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage exception
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class RestException extends \Exception
	{
		/**
		 * HTTP status codes
		 *
		 * @var array
		 */
		public static $codes = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			429 => 'Too Many Requests',
			//still in draft but used for rate limiting
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
		);
		/**
		 * @param string      $httpStatusCode http status code
		 * @param string|null $errorMessage   error message
		 * @param array       $details        any extra detail about the exception
		 * @param Exception   $previous       previous exception if any
		 */
		public function __construct($httpStatusCode, $errorMessage = null, array $details = array(), \Exception $previous = null)
		{
		}
		/**
		 * Get extra details about the exception
		 *
		 * @return array details array
		 */
		public function getDetails()
		{
		}
		public function getStage()
		{
		}
		public function getStages()
		{
		}
		public function getErrorMessage()
		{
		}
		public function getSource()
		{
		}
	}
	/**
	 * Special RestException for forcing the exception even when
	 * in hybrid method
	 *
	 * @category   Framework
	 * @package    Restler
	 * @subpackage exception
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class InvalidAuthCredentials extends \Luracast\Restler\RestException
	{
	}
	/**
	 * Class MemcacheCache provides a memcache based cache for Restler
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     Dave Drager <ddrager@gmail.com>
	 * @copyright  2014 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 * @version    3.0.0rc5
	 */
	class MemcacheCache implements \Luracast\Restler\iCache
	{
		/**
		 * The namespace that all of the cached entries will be stored under.  This allows multiple APIs to run concurrently.
		 *
		 * @var string
		 */
		public static $namespace;
		/**
		 * @var string the memcache server hostname / IP address. For the memcache
		 * cache method.
		 */
		public static $memcacheServer = '127.0.0.1';
		/**
		 * @var int the memcache server port. For the memcache cache method.
		 */
		public static $memcachePort = 11211;
		/**
		 * @param string $namespace
		 */
		public function __construct($namespace = 'restler')
		{
		}
		/**
		 * store data in the cache
		 *
		 *
		 * @param string $name
		 * @param mixed $data
		 *
		 * @return boolean true if successful
		 */
		public function set($name, $data)
		{
		}
		/**
		 * retrieve data from the cache
		 *
		 *
		 * @param string $name
		 * @param bool $ignoreErrors
		 *
		 * @throws \Exception
		 * @return mixed
		 */
		public function get($name, $ignoreErrors = false)
		{
		}
		/**
		 * delete data from the cache
		 *
		 *
		 * @param string $name
		 * @param bool $ignoreErrors
		 *
		 * @throws \Exception
		 * @return boolean true if successful
		 */
		public function clear($name, $ignoreErrors = false)
		{
		}
		/**
		 * check if the given name is cached
		 *
		 *
		 * @param string $name
		 *
		 * @return boolean true if cached
		 */
		public function isCached($name)
		{
		}
	}
	/**
	 * Static Class to pass through content outside of web root
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class PassThrough
	{
		public static $mimeTypes = array('js' => 'text/javascript', 'css' => 'text/css', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'gif' => 'image/gif', 'html' => 'text/html');
		/**
		 * Serve a file outside web root
		 *
		 * Respond with a file stored outside web accessible path
		 *
		 * @param string $filename      full path for the file to be served
		 * @param bool   $forceDownload should the we download instead of viewing
		 * @param int    $expires       cache expiry in number of seconds
		 * @param bool   $isPublic      cache control, is it public or private
		 *
		 * @throws RestException
		 *
		 */
		public static function file($filename, $forceDownload = false, $expires = 0, $isPublic = true)
		{
		}
	}
	/**
	 * Static class for handling redirection
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Redirect
	{
		/**
		 * Redirect to given url
		 *
		 * @param string $url       relative path or full url
		 * @param array  $params    associative array of query parameters
		 * @param array  $flashData associative array of properties to be set in $_SESSION for one time use
		 * @param int    $status    http status code to send the response with ideally 301 or 302
		 *
		 * @return array
		 */
		public static function to($url, array $params = array(), array $flashData = array(), $status = 302)
		{
		}
		/**
		 * Redirect back to the previous page
		 *
		 * Makes use of http referrer for redirection
		 *
		 * @return array
		 */
		public static function back()
		{
		}
	}
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
	 *
	 */
	class Resources implements \Luracast\Restler\iUseAuthentication, \Luracast\Restler\iProvideMultiVersionApi
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
		public static $dataTypeAlias = array('string' => 'string', 'int' => 'int', 'number' => 'float', 'float' => 'float', 'bool' => 'boolean', 'boolean' => 'boolean', 'NULL' => 'null', 'array' => 'Array', 'object' => 'Object', 'stdClass' => 'Object', 'mixed' => 'string', 'DateTime' => 'Date');
		/**
		 * @var array configurable symbols to differentiate public, hybrid and
		 * protected api
		 */
		public static $apiDescriptionSuffixSymbols = array(
			0 => '&nbsp; <i class="icon-unlock-alt icon-large"></i>',
			//public api
			1 => '&nbsp; <i class="icon-adjust icon-large"></i>',
			//hybrid api
			2 => '&nbsp; <i class="icon-lock icon-large"></i>',
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
		protected $crud = array('POST' => 'create', 'GET' => 'retrieve', 'PUT' => 'update', 'DELETE' => 'delete', 'PATCH' => 'partial update');
		protected static $prefixes = array('get' => 'retrieve', 'index' => 'list', 'post' => 'create', 'put' => 'update', 'patch' => 'modify', 'delete' => 'remove');
		protected $_authenticated = false;
		protected $cacheName = '';
		public function __construct()
		{
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
		}
		/**
		 * pre call for get($id)
		 *
		 * if cache is present, use cache
		 */
		public function _pre_get_json($id)
		{
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
		}
		protected function _nickname(array $route)
		{
		}
		protected function _noNamespace($className)
		{
		}
		protected function _operationListing($resourcePath = '/')
		{
		}
		protected function _resourceListing()
		{
		}
		protected function _api($path, $description = '')
		{
		}
		protected function _operation($route, $nickname, $httpMethod = 'GET', $summary = 'description', $notes = 'long description', $responseClass = 'void')
		{
		}
		protected function _parameter($param)
		{
		}
		protected function _appendToBody($p)
		{
		}
		protected function _getBody()
		{
		}
		protected function _model($className, $instance = null)
		{
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
		}
		/**
		 * pre call for index()
		 *
		 * if cache is present, use cache
		 */
		public function _pre_index_json()
		{
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
		}
		/**
		 * @access hybrid
		 * @return \stdClass
		 */
		public function index()
		{
		}
		protected function _loadResource($url)
		{
		}
		protected function _mapResources(array $allRoutes, array &$map, $version = 1)
		{
		}
		/**
		 * Maximum api version supported by the api class
		 * @return int
		 */
		public static function __getMaximumSupportedVersion()
		{
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
		}
	}
	/**
	 * REST API Server. It is the server part of the Restler framework.
	 * inspired by the RestServer code from
	 * <http://jacwright.com/blog/resources/RestServer.txt>
	 *
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 *
	 * @method static void onGet(Callable $function) fired before reading the request details
	 * @method static void onRoute(Callable $function) fired before finding the api method
	 * @method static void onNegotiate(Callable $function) fired before content negotiation
	 * @method static void onPreAuthFilter(Callable $function) fired before pre auth filtering
	 * @method static void onAuthenticate(Callable $function) fired before auth
	 * @method static void onPostAuthFilter(Callable $function) fired before post auth filtering
	 * @method static void onValidate(Callable $function) fired before validation
	 * @method static void onCall(Callable $function) fired before api method call
	 * @method static void onCompose(Callable $function) fired before composing response
	 * @method static void onRespond(Callable $function) fired before sending response
	 * @method static void onComplete(Callable $function) fired after sending response
	 * @method static void onMessage(Callable $function) fired before composing error response
	 *
	 * @method void onGet(Callable $function) fired before reading the request details
	 * @method void onRoute(Callable $function) fired before finding the api method
	 * @method void onNegotiate(Callable $function) fired before content negotiation
	 * @method void onPreAuthFilter(Callable $function) fired before pre auth filtering
	 * @method void onAuthenticate(Callable $function) fired before auth
	 * @method void onPostAuthFilter(Callable $function) fired before post auth filtering
	 * @method void onValidate(Callable $function) fired before validation
	 * @method void onCall(Callable $function) fired before api method call
	 * @method void onCompose(Callable $function) fired before composing response
	 * @method void onRespond(Callable $function) fired before sending response
	 * @method void onComplete(Callable $function) fired after sending response
	 * @method void onMessage(Callable $function) fired before composing error response
	 *
	 * @property bool|null  _authenticated
	 * @property bool _authVerified
	 */
	class Restler extends \Luracast\Restler\EventDispatcher
	{
		const VERSION = '3.1.0';
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
		 * @var Format\iFormat
		 * @example jsonFormat, xmlFormat, yamlFormat etc
		 */
		public $requestFormat;
		/**
		 * Response data format.
		 *
		 * Instance of the current format class
		 * which implements the iFormat interface
		 *
		 * @var Format\iFormat
		 * @example jsonFormat, xmlFormat, yamlFormat etc
		 */
		public $responseFormat;
		/**
		 * Http status code
		 *
		 * @var int|null when specified it will override @status comment
		 */
		public $responseCode = null;
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
		 * @var Data\ApiMethodInfo
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
		}
		/**
		 * Returns a list of the mime types (e.g.  ["application/json","application/xml"]) that the API can respond with
		 * @return array
		 */
		public function getWritableMimeTypes()
		{
		}
		/**
		 * Returns the list of Mime Types for the request that the API can understand
		 * @return array
		 */
		public function getReadableMimeTypes()
		{
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
		public function setSupportedFormats($format = null, ...$formatName)
		{
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
		public function setOverridingFormats($format = null, ...$formatName)
		{
		}
		/**
		 * Set one or more string to be considered as the base url
		 *
		 * When more than one base url is provided, restler will make
		 * use of $_SERVER['HTTP_HOST'] to find the right one
		 *
		 * @param string ,... $url
		 */
		public function setBaseUrls($url, ...$otherurls)
		{
		}
		/**
		 * Parses the request url and get the api path
		 *
		 * @return string api path
		 */
		protected function getPath()
		{
		}
		/**
		 * Parses the request to figure out format of the request data
		 *
		 * @throws RestException
		 * @return Format\iFormat any class that implements iFormat
		 * @example JsonFormat
		 */
		protected function getRequestFormat()
		{
		}
		public function getRequestStream()
		{
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
		}
		/**
		 * Find the api method to execute for the requested Url
		 */
		protected function route()
		{
		}
		/**
		 * Negotiate the response details such as
		 *  - cross origin resource sharing
		 *  - media type
		 *  - charset
		 *  - language
		 *
		 * @throws RestException
		 */
		protected function negotiate()
		{
		}
		protected function negotiateCORS()
		{
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
		 * @return Format\iFormat
		 * @example JsonFormat
		 */
		protected function negotiateResponseFormat()
		{
		}
		protected function negotiateCharset()
		{
		}
		protected function negotiateLanguage()
		{
		}
		/**
		 * Filer api calls before authentication
		 */
		protected function preAuthFilter()
		{
		}
		protected function authenticate()
		{
		}
		/**
		 * Filer api calls after authentication
		 */
		protected function postAuthFilter()
		{
		}
		protected function validate()
		{
		}
		protected function call()
		{
		}
		protected function compose()
		{
		}
		public function composeHeaders(\Luracast\Restler\RestException $e = null)
		{
		}
		protected function respond()
		{
		}
		protected function message(\Exception $exception)
		{
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
		}
		/**
		 * Add class for custom error handling
		 *
		 * @param string $className   of the error handling class
		 */
		public function addErrorClass($className)
		{
		}
		/**
		 * protected methods will need at least one authentication class to be set
		 * in order to allow that method to be executed.  When multiple authentication
		 * classes are in use, this function provides better performance by setting
		 * all auth classes through a single function call.
		 *
		 * @param array $classNames     array of associative arrays containing
		 *                              the authentication class name & optional
		 *                              url prefix for mapping.
		 */
		public function setAuthClasses(array $classNames)
		{
		}
		/**
		 * Add multiple api classes through this method.
		 *
		 * This method provides better performance when large number
		 * of API classes are in use as it processes them all at once,
		 * as opposed to hundreds (or more) addAPIClass calls.
		 *
		 *
		 * All the public methods that do not start with _ (underscore)
		 * will be will be exposed as the public api by default.
		 *
		 * All the protected methods that do not start with _ (underscore)
		 * will exposed as protected api which will require authentication
		 *
		 * @param array $map        array of associative arrays containing
		 *                          the class name & optional url prefix
		 *                          for mapping.
		 *
		 * @return null
		 *
		 * @throws Exception when supplied with invalid class name
		 */
		public function mapAPIClasses(array $map)
		{
		}
		/**
		 * Associated array that maps formats to their respective format class name
		 *
		 * @return array
		 */
		public function getFormatMap()
		{
		}
		/**
		 * API version requested by the client
		 * @return int
		 */
		public function getRequestedApiVersion()
		{
		}
		/**
		 * When false, restler will run in debug mode and parse the class files
		 * every time to map it to the URL
		 *
		 * @return bool
		 */
		public function getProductionMode()
		{
		}
		/**
		 * Chosen API version
		 *
		 * @return int
		 */
		public function getApiVersion()
		{
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
		}
		/**
		 * List of events that fired already
		 *
		 * @return array
		 */
		public function getEvents()
		{
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
		}
		/**
		 * Store the url map cache if needed
		 */
		public function __destruct()
		{
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
		}
	}
	/**
	 * Router class that routes the urls to api methods along with parameters
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Routes
	{
		public static $prefixingParameterNames = array('id');
		public static $fieldTypesByName = array('email' => 'email', 'password' => 'password', 'phone' => 'tel', 'mobile' => 'tel', 'tel' => 'tel', 'search' => 'search', 'date' => 'date', 'created_at' => 'datetime', 'modified_at' => 'datetime', 'url' => 'url', 'link' => 'url', 'href' => 'url', 'website' => 'url', 'color' => 'color', 'colour' => 'color');
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
		}
		/**
		 * @access private
		 */
		public static function typeChar($type = null)
		{
		}
		protected static function addPath($path, array $call, $httpMethod = 'GET', $version = 1)
		{
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
		 * @return Data\ApiMethodInfo
		 */
		public static function find($path, $httpMethod, $version = 1, array $data = array())
		{
		}
		public static function findAll(array $excludedPaths = array(), array $excludedHttpMethods = array(), $version = 1)
		{
		}
		public static function verifyAccess($route)
		{
		}
		/**
		 * Populates the parameter values
		 *
		 * @param array $call
		 * @param       $data
		 *
		 * @return Data\ApiMethodInfo
		 *
		 * @access private
		 */
		protected static function populate(array $call, $data)
		{
		}
		/**
		 * @access private
		 */
		protected static function pathVarTypeOf($var)
		{
		}
		protected static function typeMatch($type, $var)
		{
		}
		protected static function parseMagic(\ReflectionClass $class, $forResponse = true)
		{
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
		protected static function getTypeAndModel(\ReflectionClass $class, array $scope, $prefix = '', array $rules = array())
		{
		}
		/**
		 * Import previously created routes from cache
		 *
		 * @param array $routes
		 */
		public static function fromArray(array $routes)
		{
		}
		/**
		 * Export current routes for cache
		 *
		 * @return array
		 */
		public static function toArray()
		{
		}
		public static function type($var)
		{
		}
		public static function scope(\ReflectionClass $class)
		{
		}
	}
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
	 *
	 */
	class Scope
	{
		public static $classAliases = array(
			//Core
			'Restler' => 'Luracast\\Restler\\Restler',
			//Format classes
			'AmfFormat' => 'Luracast\\Restler\\Format\\AmfFormat',
			'JsFormat' => 'Luracast\\Restler\\Format\\JsFormat',
			'JsonFormat' => 'Luracast\\Restler\\Format\\JsonFormat',
			'HtmlFormat' => 'Luracast\\Restler\\Format\\HtmlFormat',
			'PlistFormat' => 'Luracast\\Restler\\Format\\PlistFormat',
			'UploadFormat' => 'Luracast\\Restler\\Format\\UploadFormat',
			'UrlEncodedFormat' => 'Luracast\\Restler\\Format\\UrlEncodedFormat',
			'XmlFormat' => 'Luracast\\Restler\\Format\\XmlFormat',
			'YamlFormat' => 'Luracast\\Restler\\Format\\YamlFormat',
			'CsvFormat' => 'Luracast\\Restler\\Format\\CsvFormat',
			'TsvFormat' => 'Luracast\\Restler\\Format\\TsvFormat',
			//Filter classes
			'RateLimit' => 'Luracast\\Restler\\Filter\\RateLimit',
			//UI classes
			'Forms' => 'Luracast\\Restler\\UI\\Forms',
			'Nav' => 'Luracast\\Restler\\UI\\Nav',
			'Emmet' => 'Luracast\\Restler\\UI\\Emmet',
			'T' => 'Luracast\\Restler\\UI\\Tags',
			//API classes
			'Resources' => 'Luracast\\Restler\\Resources',
			'Explorer' => 'Luracast\\Restler\\Explorer\\v2\\Explorer',
			'Explorer1' => 'Luracast\\Restler\\Explorer\\v1\\Explorer',
			'Explorer2' => 'Luracast\\Restler\\Explorer\\v2\\Explorer',
			//Cache classes
			'HumanReadableCache' => 'Luracast\\Restler\\HumanReadableCache',
			'ApcCache' => 'Luracast\\Restler\\ApcCache',
			'MemcacheCache' => 'Luracast\\Restler\\MemcacheCache',
			//Utility classes
			'Object' => 'Luracast\\Restler\\Data\\Obj',
			'Text' => 'Luracast\\Restler\\Data\\Text',
			'Arr' => 'Luracast\\Restler\\Data\\Arr',
			//Exception
			'RestException' => 'Luracast\\Restler\\RestException',
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
		}
		public static function set($name, $instance)
		{
		}
		public static function get($name)
		{
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
		}
	}
}

namespace Luracast\Restler\UI {
	/**
	 * Class Emmet
	 * @package Luracast\Restler\UI
	 *
	 * @version 3.1.0
	 */
	class Emmet
	{
		const DELIMITERS = '.#*>+^[=" ]{$@-#}';
		/**
		 * Create the needed tag hierarchy from emmet string
		 *
		 * @param string       $string
		 *
		 * @param array|string $data
		 *
		 * @return array|T
		 */
		public static function make($string, $data = null)
		{
		}
		public static function tokenize($string)
		{
		}
	}
	/**
	 * Utility class for automatically generating forms for the given http method
	 * and api url
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Forms implements \Luracast\Restler\iFilter
	{
		const FORM_KEY = 'form_key';
		public static $filterFormRequestsOnly = false;
		public static $excludedPaths = array();
		/**
		 * @var bool should we fill up the form using given data?
		 */
		public static $preFill = true;
		/**
		 * @var ValidationInfo
		 */
		public static $validationInfo = null;
		protected static $inputTypes = array('hidden', 'password', 'button', 'image', 'file', 'reset', 'submit', 'search', 'checkbox', 'radio', 'email', 'text', 'color', 'date', 'datetime', 'datetime-local', 'email', 'month', 'number', 'range', 'search', 'tel', 'time', 'url', 'week');
		protected static $fileUpload = false;
		public static function setStyles(\Luracast\Restler\UI\HtmlForm $style)
		{
		}
		/**
		 * Get the form
		 *
		 * @param string $method   http method to submit the form
		 * @param string $action   relative path from the web root. When set to null
		 *                         it uses the current api method's path
		 * @param bool   $dataOnly if you want to render the form yourself use this
		 *                         option
		 * @param string $prefix   used for adjusting the spacing in front of
		 *                         form elements
		 * @param string $indent   used for adjusting indentation
		 *
		 * @return array|T
		 *
		 * @throws RestException
		 */
		public static function get($method = 'POST', $action = null, $dataOnly = false, $prefix = '', $indent = '    ')
		{
		}
		public static function style($name, array $metadata, $type = '')
		{
		}
		public static function fields($dataOnly = false)
		{
		}
		/**
		 * @param ValidationInfo $p
		 *
		 * @param bool           $dataOnly
		 *
		 * @return array|T
		 */
		public static function field(\Luracast\Restler\Data\ValidationInfo $p, $dataOnly = false)
		{
		}
		protected static function guessFieldType(\Luracast\Restler\Data\ValidationInfo $p, $type = 'type')
		{
		}
		/**
		 * Get the form key
		 *
		 * @param string $method   http method for form key
		 * @param string $action   relative path from the web root. When set to null
		 *                         it uses the current api method's path
		 *
		 * @return string generated form key
		 */
		public static function key($method = 'POST', $action = null)
		{
		}
		/**
		 * Access verification method.
		 *
		 * API access will be denied when this method returns false
		 *
		 * @return boolean true when api access is allowed false otherwise
		 *
		 * @throws RestException 403 security violation
		 */
		public function __isAllowed()
		{
		}
	}
	/**
	 * Utility class for providing preset styles for html forms
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 * @version    3.0.0rc6
	 */
	class FormStyles
	{
		public static $html = array(
			'form' => 'form[role=form id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
			'input' => '.row>section>label{$label#}^input[id=$id# name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept# disabled=$disabled#]',
			'textarea' => '.row>label{$label#}^textarea[id=$id# name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3 disabled=$disabled#]{$value#}',
			'radio' => '.row>section>label{$label#}^span>label*options>input[id=$id# name=$name# value=$value# type=radio checked=$selected# required=$required# disabled=$disabled#]+{ $text#}',
			'select' => '.row>label{$label#}^select[id=$id# name=$name# required=$required#]>option[value]+option[value=$value# selected=$selected# disabled=$disabled#]{$text#}*options',
			'submit' => '.row>label{ &nbsp; }^button[id=$id# type=submit disabled=$disabled#]{$label#}',
			'fieldset' => 'fieldset>legend{$label#}',
			'checkbox' => '.row>label>input[id=$id# name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept# disabled=$disabled#]+{$label#}',
			//------------- TYPE BASED STYLES ---------------------//
			'checkbox-array' => 'fieldset>legend{$label#}+section*options>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# accept=$accept#]+{ $text#}',
			'select-array' => 'label{$label#}+select[name=$name# required=$required# multiple style="height: auto;background-image: none; outline: inherit;"]>option[value=$value# selected=$selected#]{$text#}*options',
		);
		public static $bootstrap3 = array(
			'form' => 'form[role=form id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
			'input' => '.form-group.$error#>label{$label#}+input.form-control[id=$id# name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept# disabled=$disabled#]+small.help-block>{$message#}',
			'textarea' => '.form-group>label{$label#}+textarea.form-control[id=$id# name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3 disabled=$disabled#]{$value#}+small.help-block>{$message#}',
			'radio' => 'fieldset>legend{$label#}>.radio*options>label>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required# disabled=$disabled#]{$text#}+p.help-block>{$message#}',
			'select' => '.form-group>label{$label#}+select.form-control[id=$id# name=$name# multiple=$multiple# required=$required#]>option[value]+option[value=$value# selected=$selected# disabled=$disabled#]{$text#}*options',
			'submit' => 'button.btn.btn-primary[id=$id# type=submit]{$label#} disabled=$disabled#',
			'fieldset' => 'fieldset>legend{$label#}',
			'checkbox' => '.checkbox>label>input[id=$id# name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# disabled=$disabled#]+{$label#}^p.help-block>{$error#}',
			//------------- TYPE BASED STYLES ---------------------//
			'checkbox-array' => 'fieldset>legend{$label#}>.checkbox*options>label>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required#]{$text#}',
			'select-array' => '.form-group>label{$label#}+select.form-control[name=$name# multiple=$multiple# required=$required#] size=$options#>option[value=$value# selected=$selected#]{$text#}*options',
			//------------- CUSTOM STYLES ---------------------//
			'radio-inline' => '.form-group>label{$label# : &nbsp;}+label.radio-inline*options>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required#]+{$text#}',
		);
		public static $foundation5 = array(
			'form' => 'form[id=$id# name=$name# method=$method# action=$action# enctype=$enctype#]',
			'input' => 'label{$label#}+input[id=$id# name=$name# value=$value# type=$type# required=$required# autofocus=$autofocus# placeholder=$default# accept=$accept# disabled=$disabled#]',
			'textarea' => 'label{$label#}+textarea[id=$id# name=$name# required=$required# autofocus=$autofocus# placeholder=$default# rows=3 disabled=$disabled#]{$value#}',
			'radio' => 'label{$label# : &nbsp;}+label.radio-inline*options>input.radio[name=$name# value=$value# type=radio checked=$selected# required=$required# disabled=$disabled#]+{$text#}',
			'select' => 'label{$label#}+select[id=$id# name=$name# required=$required#]>option[value]+option[value=$value# selected=$selected# disabled=$disabled#]{$text#}*options',
			'submit' => 'button.button[id=$id# type=submit disabled=$disabled#]{$label#}',
			'fieldset' => 'fieldset>legend{$label#}',
			'checkbox' => 'label>input[id=$id# name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus# disabled=$disabled#]+{ $label#}',
			//------------- TYPE BASED STYLES ---------------------//
			'checkbox-array' => 'fieldset>legend{$label#}+label*options>input[name=$name# value=$value# type=checkbox checked=$selected# required=$required# autofocus=$autofocus#]+{ $text#}',
			'select-array' => 'label{$label#}+select[name=$name# required=$required# multiple style="height: auto;background-image: none; outline: inherit;"]>option[value=$value# selected=$selected#]{$text#}*options',
		);
	}
	/**
	 * Utility class for automatically creating data to build an navigation interface
	 * based on available routes that are accessible by the current user
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class Nav
	{
		protected static $tree = array();
		public static $root = 'home';
		/**
		 * @var array all paths beginning with any of the following will be excluded
		 * from documentation. if an empty string is given it will exclude the root
		 */
		public static $excludedPaths = array('');
		/**
		 * @var array prefix additional menu items with one of the following syntax
		 *            [$path => $text]
		 *            [$path]
		 *            [$path => ['text' => $text, 'url' => $url, 'trail'=> $trail]]
		 */
		public static $prepends = array();
		/**
		 * @var array suffix additional menu items with one of the following syntax
		 *            [$path => $text]
		 *            [$path]
		 *            [$path => ['text' => $text, 'url' => $url, 'trail'=> $trail]]
		 */
		public static $appends = array();
		public static $addExtension = true;
		protected static $extension = '';
		protected static $activeTrail = '';
		protected static $url;
		public static function get($for = '', $activeTrail = null)
		{
		}
		protected static function &nested(array &$tree, array $parts)
		{
		}
		public static function addUrls(array $urls)
		{
		}
		public static function add($url, $label = null, $trail = null)
		{
		}
		public static function build(array $r)
		{
		}
	}
	/**
	 * Utility class for generating html tags in an object oriented way
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 *
	 * ============================ magic  properties ==============================
	 * @property Tags parent parent tag
	 * ============================== magic  methods ===============================
	 * @method Tags name(string $value) name attribute
	 * @method Tags action(string $value) action attribute
	 * @method Tags placeholder(string $value) placeholder attribute
	 * @method Tags value(string $value) value attribute
	 * @method Tags required(boolean $value) required attribute
	 * @method Tags class(string $value) required attribute
	 *
	 * =========================== static magic methods ============================
	 * @method static Tags form() creates a html form
	 * @method static Tags input() creates a html input element
	 * @method static Tags button() creates a html button element
	 *
	 */
	class Tags implements \ArrayAccess, \Countable
	{
		public static $humanReadable = true;
		public static $initializer = null;
		protected static $instances = array();
		public $prefix = '';
		public $indent = '    ';
		public $tag;
		protected $attributes = array();
		protected $children = array();
		protected $_parent;
		public function __construct($name = null, array $children = array())
		{
		}
		/**
		 * Get Tag by id
		 *
		 * Retrieve a tag by its id attribute
		 *
		 * @param string $id
		 *
		 * @return Tags|null
		 */
		public static function byId($id)
		{
		}
		/**
		 * @param       $name
		 * @param array $children
		 *
		 * @return Tags
		 */
		public static function __callStatic($name, array $children)
		{
		}
		public function toString($prefix = '', $indent = '    ')
		{
		}
		public function __toString()
		{
		}
		public function toArray()
		{
		}
		/**
		 * Set the id attribute of the current tag
		 *
		 * @param string $value
		 *
		 * @return string
		 */
		public function id($value)
		{
		}
		public function __get($name)
		{
		}
		public function __set($name, $value)
		{
		}
		public function __isset($name)
		{
		}
		/**
		 * @param $attribute
		 * @param $value
		 *
		 * @return Tags
		 */
		public function __call($attribute, $value)
		{
		}
		public function offsetGet($index)
		{
		}
		public function offsetExists($index)
		{
		}
		public function offsetSet($index, $value)
		{
		}
		public function offsetUnset($index)
		{
		}
		public function getContents()
		{
		}
		public function count()
		{
		}
	}
}

namespace Luracast\Restler {
	/**
	 * Information gathered about the api user is kept here using static methods
	 * and properties for other classes to make use of them.
	 * Typically Authentication classes populate them
	 *
	 * @category   Framework
	 * @package    restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
	 */
	class User implements \Luracast\Restler\iIdentifyUser
	{
		public static $id = null;
		public static $cacheId = null;
		public static $ip;
		public static $browser = '';
		public static $platform = '';
		public static function init()
		{
		}
		public static function getUniqueIdentifier($includePlatform = false)
		{
		}
		public static function getIpAddress($ignoreProxies = false)
		{
		}
		/**
		 * Authentication classes should call this method
		 *
		 * @param string $id user id as identified by the authentication classes
		 *
		 * @return void
		 */
		public static function setUniqueIdentifier($id)
		{
		}
		/**
		 * User identity to be used for caching purpose
		 *
		 * When the dynamic cache service places an object in the cache, it needs to
		 * label it with a unique identifying string known as a cache ID. This
		 * method gives that identifier
		 *
		 * @return string
		 */
		public static function getCacheIdentifier()
		{
		}
		/**
		 * User identity for caching purpose
		 *
		 * In a role based access control system this will be based on role
		 *
		 * @param $id
		 *
		 * @return void
		 */
		public static function setCacheIdentifier($id)
		{
		}
	}
	/**
	 * Describe the purpose of this class/interface/trait
	 *
	 * @category   Framework
	 * @package    Restler
	 * @author     R.Arul Kumaran <arul@luracast.com>
	 * @copyright  2010 Luracast
	 * @license    http://www.opensource.org/licenses/lgpl-license.php LGPL
	 * @link       http://luracast.com/products/restler/
	 *
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
		 * @param array|object $from    array to extract the value from
		 * @param string|array $key     ... pass more to go deeply inside the array
		 *                              alternatively you can pass a single array
		 *
		 * @return null|mixed null when not found, value otherwise
		 */
		public static function nestedValue($from, $key)
		{
		}
		public static function getResourcePath($className, $resourcePath = null, $prefix = '')
		{
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
		}
		public static function getShortName($className)
		{
		}
	}
}

namespace {
	/**
	 * Include function in the root namespace to include files optimized
	 * for the global context.
	 *
	 * @param $path string path of php file to include into the global context.
	 *
	 * @return mixed|bool false if the file could not be included.
	 */
	function Luracast_Restler_autoloaderInclude($path)
	{
	}
}

namespace {
	function exceptions()
	{
	}
	function parse_backtrace($raw, $skip = 1)
	{
	}
	function render($data, $shadow = \true)
	{
	}
}
