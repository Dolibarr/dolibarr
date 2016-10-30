
TODO:

  * Fix code indenting in PHP 4 code
  * Port maxDepth option to PHP 4 code

2013-04-23 - Release Version: 0.4.0

  * No changes

2011-06-22 - Release Version: 0.4.0rc3

  * Build fixes

2011-06-20 - Release Version: 0.4.0rc1

  * (Issue 163) PHP5 class_exists() throws Exception without second parameter
  * (Issue 166) Non-utf8 array values replaced with null
  * Cleaned up code formatting [sokolov.innokenty@gmail.com]
  * Ensure JSON keys are never NULL (due to NULL key in some arrays)
  * Better UTF-8 encoding detection
  * Code style cleanup (qbbr)
  * Changed license to MIT
  * Refactored project

2010-10-26 - Release Version: 0.3.2

2010-10-12 - Release Version: 0.3.2rc6

  * (Issue 154) getRequestHeader uses "getallheaders" even though it doesn't always exist. [25m]

2010-10-09 - Release Version: 0.3.2rc5

  * (Issue 153) FirePHP incorrectly double-encodes UTF8 when mbstring.func_overload is enabled

2010-10-08 - Release Version: 0.3.2rc4

  * Trigger upgrade message if part of FirePHP 1.0
  * Removed FirePHP/Init.php inclusion logic and only load FirePHP.class.php if not already loaded

2010-07-19 - Release Version: 0.3.2rc3

  * Fixed FirePHP/Init.php inclusion logic

2010-07-19 - Release Version: 0.3.2rc2

  * (Issue 145) maxDepth option
  * Changed maxObjectDepth and maxArrayDepth option defaults to 5
  * Fixed code indentation

2010-03-05 - Release Version: 0.3.2rc1

  * (Issue 114) Allow options to be passed on to basic logging wrappers
  * (Issue 122) Filter objectStack property of FirePHP class
  * (Issue 123) registerErrorHandler(false) by default
  * Added setOption() and getOption() methods
  * (Issue 117) dump() method argument validation
  * Started adding PHPUnit tests
  * Some refactoring to support unit testing
  * Deprecated setProcessorUrl() and setRendererUrl()
  * Check User-Agent and X-FirePHP-Version header to detect FirePHP on client
  * (Issue 135) FirePHP 0.4.3 with Firebug 1.5 changes user agent on the fly
  * (Issue 112) Error Predefined Constants Not available for PHP 5.x versions

2008-06-14 - Release Version: 0.3.1

  * (Issue 108) ignore class name case in object filter

2009-05-11 - Release Version: 0.3
2009-05-01 - Release Version: 0.3.rc.1

  * (Issue 90) PHP4 compatible version of FirePHPCore
  * (Issue 98) Thrown exceptions don't send an HTTP 500 if the FirePHP exception handler is enabled
  * (Issue 85) Support associative arrays in encodeTable method in FirePHP.class.php
  * (Issue 66) Add a new getOptions() public method in API
  * (Issue 82) Define $this->options outside of __construct
  * (Issue 72) Message error if group name is null
  * (Issue 68) registerErrorHandler() and registerExceptionHandler() should returns previous handlers defined
  * (Issue 69) Add the missing register handler in the triumvirate (error, exception, assert)
  * (Issue 75) [Error & Exception Handling] Option to not exit script execution
  * (Issue 83) Exception handler can't throw exceptions
  * (Issue 80) Auto/Pre collapsing groups AND Custom group row colors

2008-11-09 - Release Version: 0.2.1

  * (Issue 70) Problem when logging resources

2008-10-21 - Release Version: 0.2.0

  * Updated version to 0.2.0
  * Switched to using __sleep instead of __wakeup
  * Added support to exclude object members when encoding
  * Add support to enable/disable logging

2008-10-17 - Release Version: 0.2.b.8
  
  * New implementation for is_utf8()
  * (Issue 55) maxObjectDepth Option not working correctly when using TABLE and EXCEPTION Type
  * Bugfix for max[Object|Array]Depth when encoding nested array/object graphs
  * Bugfix for FB::setOptions()

2008-10-16 - Release Version: 0.2.b.7

  * (Issue 45) Truncate dump when string have non utf8 cars
  * (Issue 52) logging will not work when firephp object gets stored in the session.

2008-10-16 - Release Version: 0.2.b.6

  * (Issue 37) Display file and line information for each log message
  * (Issue 51) Limit output of object graphs
  * Bugfix for encoding object members set to NULL|false|''

2008-10-14 - Release Version: 0.2.b.5

  * Updated JsonStream wildfire protocol to be more robust
  * (Issue 33) PHP error notices running demos
  * (Issue 48) Warning: ReflectionProperty::getValue() expects exactly 1 parameter, 0 given

2008-10-08 - Release Version: 0.2.b.4

  * Bugfix for logging objects with recursion

2008-10-08 - Release Version: 0.2.b.3

  * (Issue 43) Notice message in 0.2b2
  * Added support for PHP's native json_encode() if available
  * Revised object encoder to detect object recursion

2008-10-07 - Release Version: 0.2.b.2

  * (Issue 28) Need solution for logging private and protected object variables
  * Added trace() and table() aliases in FirePHP class
  * (Issue 41) Use PHP doc in FirePHP
  * (Issue 39) Static logging method for object oriented API

2008-10-01 - Release Version: 0.2.b.1

  * Added support for error and exception handling
  * Updated min PHP version for PEAR package to 5.2
  * Added version constant for library
  * Gave server library it's own wildfire plugin namespace
  * Migrated communication protocol to Wildfire JsonStream
  * Added support for console groups using "group" and "groupEnd"
  * Added support for log, info, warn and error logging aliases
  * (Issue 29) problem with TRACE when using with error_handler
  * (Issue 33) PHP error notices running demos
  * (Issue 12) undefined index php notice
  * Removed closing ?> php tags
  * (Issue 13) the code in the fb() function has a second return statement that will never be reached

2008-07-30 - Release Version: 0.1.1.3

  * Include __className property in JSON string if variable was an object
  * Bugfix - Mis-spelt "Exception" in JSON encoding code

2008-06-13 - Release Version: 0.1.1.1

  * Bugfix - Standardize windows paths in stack traces
  * Bugfix - Display correct stack trace info in windows environments
  * Bugfix - Check $_SERVER['HTTP_USER_AGENT'] before returning

2008-06-13 - Release Version: 0.1.1

  * Added support for FirePHP::TRACE log style
  * Changed license to New BSD License

2008-06-06 - Release Version: 0.0.2

  * Bugfix - Added usleep() to header writing loop to ensure unique index
  * Bugfix - Ensure chunk_split does not generate trailing "\n" with empty data header
  * Added support for FirePHP::TABLE log style
