<?php

/**
* 
* Provides an object-oriented template system for PHP5.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* 
* @version $Id: Savant3.php,v 1.42 2006/01/01 18:31:00 pmjones Exp $
* 
*/


/**
* Always have these classes available.
*/
include_once dirname(__FILE__) . '/Savant3/Filter.php';
include_once dirname(__FILE__) . '/Savant3/Plugin.php';


/**
* 
* Provides an object-oriented template system for PHP5.
* 
* Savant3 helps you separate business logic from presentation logic
* using PHP as the template language. By default, Savant3 does not
* compile templates. However, you may pass an optional compiler object
* to compile template source to include-able PHP code.  It is E_STRICT
* compliant for PHP5.
* 
* Please see the documentation at {@link http://phpsavant.com/}, and be
* sure to donate! :-)
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @package Savant3
* 
* @version @package_version@
* 
*/

class Savant3 {
	
	
	/**
	* 
	* Array of configuration parameters.
	* 
	* @access protected
	* 
	* @var array
	* 
	*/
	
	protected $__config = array(
		'template_path' => array(),
		'resource_path' => array(),
		'error_text'    => "\n\ntemplate error, examine fetch() result\n\n",
		'exceptions'    => false,
		'autoload'      => false,
		'compiler'      => null,
		'filters'       => array(),
		'plugins'       => array(),
		'template'      => null,
		'plugin_conf'   => array(),
		'extract'       => false,
		'fetch'         => null,
		'escape'        => array('htmlspecialchars'),
	);
	
	
	// -----------------------------------------------------------------
	//
	// Constructor and magic methods
	//
	// -----------------------------------------------------------------
	
	
	/**
	* 
	* Constructor.
	* 
	* @access public
	* 
	* @param array $config An associative array of configuration keys for
	* the Savant3 object.  Any, or none, of the keys may be set.
	* 
	* @return object Savant3 A Savant3 instance.
	* 
	*/
	
	public function __construct($config = null)
	{
		// force the config to an array
		settype($config, 'array');
		
		// set the default template search path
		if (isset($config['template_path'])) {
			// user-defined dirs
			$this->setPath('template', $config['template_path']);
		} else {
			// no directories set, use the
			// default directory only
			$this->setPath('template', null);
		}
		
		// set the default resource search path
		if (isset($config['resource_path'])) {
			// user-defined dirs
			$this->setPath('resource', $config['resource_path']);
		} else {
			// no directories set, use the
			// default directory only
			$this->setPath('resource', null);
		}
		
		// set the error reporting text
		if (isset($config['error_text'])) {
			$this->setErrorText($config['error_text']);
		}
		
		// set the autoload flag
		if (isset($config['autoload'])) {
			$this->setAutoload($config['autoload']);
		}
		
		// set the extraction flag
		if (isset($config['extract'])) {
			$this->setExtract($config['extract']);
		}
		
		// set the exceptions flag
		if (isset($config['exceptions'])) {
			$this->setExceptions($config['exceptions']);
		}
		
		// set the template to use for output
		if (isset($config['template'])) {
			$this->setTemplate($config['template']);
		}
		
		// set the output escaping callbacks
		if (isset($config['escape'])) {
			$this->setEscape($config['escape']);
		}
		
		// set the default plugin configs
		if (isset($config['plugin_conf']) && is_array($config['plugin_conf'])) {
			foreach ($config['plugin_conf'] as $name => $opts) {
				$this->setPluginConf($name, $opts);
			}
		}
		
		// set the default filter callbacks
		if (isset($config['filters'])) {
			$this->addFilters($config['filters']);
		}
	}
	
	
	/**
	*
	* Executes a main plugin method with arbitrary parameters.
	* 
	* @access public
	* 
	* @param string $func The plugin method name.
	*
	* @param array $args The parameters passed to the method.
	*
	* @return mixed The plugin output, or a Savant3_Error with an
	* ERR_PLUGIN code if it can't find the plugin.
	* 
	*/
	
	public function __call($func, $args)
	{
		$plugin = $this->plugin($func);
		
		if ($this->isError($plugin)) {
			return $plugin;
		}
		
		// try to avoid the very-slow call_user_func_array()
		// for plugins with very few parameters.  thanks to
		// Andreas Korthaus for profiling the code to find
		// the slowdown.
		switch (count($args)) {
		
		case 0:
			return $plugin->$func();
		
		case 1:
			return $plugin->$func($args[0]);
			break;
			
		case 2:
			return $plugin->$func($args[0], $args[1]);
			break;
			
		case 3:
			return $plugin->$func($args[0], $args[1], $args[2]);
			break;
		
		default:
			return call_user_func_array(array($plugin, $func), $args);
			break;
		}
	}
	
	
	/**
	* 
	* Magic method to echo this object as template output.
	* 
	* Note that if there is an error, this will output a simple
	* error text string and will not return an error object.  Use
	* fetch() to get an error object when errors occur.
	* 
	* @access public
	*  
	* @return string The template output.
	* 
	*/
	
	public function __toString()
	{
		return $this->getOutput();
	}
	
	
	/**
	* 
	* Reports the API version for this class.
	* 
	* @access public
	* 
	* @return string A PHP-standard version number.
	* 
	*/
	
	public function apiVersion()
	{
		return '@package_version@';
	}
	
	
	/**
	* 
	* Returns an internal plugin object; creates it as needed.
	* 
	* @access public
	* 
	* @param string $name The plugin name.  If this plugin has not
	* been created yet, this method creates it automatically.
	*
	* @return mixed The plugin object, or a Savant3_Error with an
	* ERR_PLUGIN code if it can't find the plugin.
	* 
	*/
	
	public function plugin($name)
	{
		// shorthand reference
		$plugins =& $this->__config['plugins'];
		$autoload = $this->__config['autoload'];
		
		// is the plugin method object already instantiated?
		if (! array_key_exists($name, $plugins)) {
			
			// not already instantiated, so load it up.
			// set up the class name.
			$class = "Savant3_Plugin_$name";
			
			// has the class been loaded?
			if (! class_exists($class, $autoload)) {
			
				// class is not loaded, set up the file name.
				$file = "$class.php";
				
				// make sure the class file is available from the resource path.
				$result = $this->findFile('resource', $file);
				if (! $result) {
					// not available, this is an error
					return $this->error(
						'ERR_PLUGIN',
						array('method' => $name)
					);
				} else {
					// available, load the class file
					include_once $result;
				}
			}
			
			// get the default configuration for the plugin.
			$plugin_conf =& $this->__config['plugin_conf'];
			if (! empty($plugin_conf[$name])) {
				$opts = $plugin_conf[$name];
			} else {
				$opts = array();
			}
			
			// add the Savant reference
			$opts['Savant'] = $this;
			
			// instantiate the plugin with its options.
			$plugins[$name] = new $class($opts);
		}
	
		// return the plugin object
		return $plugins[$name];
	}
	
	
	// -----------------------------------------------------------------
	//
	// Public configuration management (getters and setters).
	// 
	// -----------------------------------------------------------------
	
	
	/**
	*
	* Returns a copy of the Savant3 configuration parameters.
	*
	* @access public
	* 
	* @param string $key The specific configuration key to return.  If null,
	* returns the entire configuration array.
	* 
	* @return mixed A copy of the $this->__config array.
	* 
	*/
	
	public function getConfig($key = null)
	{
		if (is_null($key)) {
			// no key requested, return the entire config array
			return $this->__config;
		} elseif (empty($this->__config[$key])) {
			// no such key
			return null;
		} else {
			// return the requested key
			return $this->__config[$key];
		}
	}
	
	
	/**
	* 
	* Sets __autoload() usage on or off.
	* 
	* @access public
	* 
	* @param bool $flag True to use __autoload(), false to not use it.
	* 
	* @return void
	* 
	*/
	
	public function setAutoload($flag)
	{
		$this->__config['autoload'] = (bool) $flag;
	}
	
	
	/**
	* 
	* Sets a custom compiler/pre-processor callback for template sources.
	* 
	* By default, Savant3 does not use a compiler; use this to set your
	* own custom compiler (pre-processor) for template sources.
	* 
	* @access public
	* 
	* @param mixed $compiler A compiler callback value suitable for the
	* first parameter of call_user_func().  Set to null/false/empty to
	* use PHP itself as the template markup (i.e., no compiling).
	* 
	* @return void
	* 
	*/
	
	public function setCompiler($compiler)
	{
		$this->__config['compiler'] = $compiler;
	}
	
	
	/**
	* 
	* Sets the custom error text for __toString().
	* 
	* @access public
	* 
	* @param string $text The error text when a template is echoed.
	* 
	* @return void
	* 
	*/
	
	public function setErrorText($text)
	{
		$this->__config['error_text'] = $text;
	}
	
	
	/**
	* 
	* Sets whether or not exceptions will be thrown.
	* 
	* @access public
	* 
	* @param bool $flag True to turn on exception throwing, false
	* to turn it off.
	* 
	* @return void
	* 
	*/
	
	public function setExceptions($flag)
	{
		$this->__config['exceptions'] = (bool) $flag;
	}
	
	
	/**
	* 
	* Sets whether or not variables will be extracted.
	* 
	* @access public
	* 
	* @param bool $flag True to turn on variable extraction, false
	* to turn it off.
	* 
	* @return void
	* 
	*/
	
	public function setExtract($flag)
	{
		$this->__config['extract'] = (bool) $flag;
	}
	
	
	/**
	*
	* Sets config array for a plugin.
	* 
	* @access public
	* 
	* @param string $plugin The plugin to configure.
	* 
	* @param array $config The configuration array for the plugin.
	* 
	* @return void
	*
	*/
	
	public function setPluginConf($plugin, $config = null)
	{
		$this->__config['plugin_conf'][$plugin] = $config;
	}
	
	
	/**
	*
	* Sets the template name to use.
	*
	* @access public
	*
	* @param string $template The template name.
	*
	* @return void
	*
	*/
	
	public function setTemplate($template)
	{
		$this->__config['template'] = $template;
	}
	
	
	// -----------------------------------------------------------------
	//
	// Output escaping and management.
	//
	// -----------------------------------------------------------------
	
	
	/**
	* 
	* Clears then sets the callbacks to use when calling $this->escape().
	* 
	* Each parameter passed to this function is treated as a separate
	* callback.  For example:
	* 
	* <code>
	* $savant->setEscape(
	*     'stripslashes',
	*     'htmlspecialchars',
	*     array('StaticClass', 'method'),
	*     array($object, $method)
	* );
	* </code>
	* 
	* @access public
	*
	* @return void
	*
	*/
	
	public function setEscape()
	{
		$this->__config['escape'] = (array) @func_get_args();
	}
	
	
	/**
	* 
	* Adds to the callbacks used when calling $this->escape().
	* 
	* Each parameter passed to this function is treated as a separate
	* callback.  For example:
	* 
	* <code>
	* $savant->addEscape(
	*     'stripslashes',
	*     'htmlspecialchars',
	*     array('StaticClass', 'method'),
	*     array($object, $method)
	* );
	* </code>
	* 
	* @access public
	*
	* @return void
	*
	*/
	
	public function addEscape()
	{
		$args = (array) @func_get_args();
		$this->__config['escape'] = array_merge(
			$this->__config['escape'], $args
		);
	}
	
	
	/**
	*
	* Gets the array of output-escaping callbacks.
	*
	* @access public
	*
	* @return array The array of output-escaping callbacks.
	*
	*/
	
	public function getEscape()
	{
		return $this->__config['escape'];
	}
	
	
	/**
	*
	* Applies escaping to a value.
	* 
	* You can override the predefined escaping callbacks by passing
	* added parameters as replacement callbacks.
	* 
	* <code>
	* // use predefined callbacks
	* $result = $savant->escape($value);
	* 
	* // use replacement callbacks
	* $result = $savant->escape(
	*     $value,
	*     'stripslashes',
	*     'htmlspecialchars',
	*     array('StaticClass', 'method'),
	*     array($object, $method)
	* );
	* </code>
	*
	* 
	* Unfortunately, a call to "echo htmlspecialchars()" is twice
	* as fast as a call to "echo $this->escape()" under the default
	* escaping (which is htmlspecialchars).  The benchmark showed
	* 0.007 seconds for htmlspecialchars(), and 0.014 seconds for
	* $this->escape(), on 300 calls each.
	* 
	* @access public
	* 
	* @param mixed $value The value to be escaped.
	* 
	* @return mixed
	*
	*/
	
	public function escape($value)
	{
		// were custom callbacks passed?
		if (func_num_args() == 1) {
		
			// no, only a value was passed.
			// loop through the predefined callbacks.
			foreach ($this->__config['escape'] as $func) {
				// this if() shaves 0.001sec off of 300 calls.
				if (is_string($func)) {
					$value = $func($value);
				} else {
					$value = call_user_func($func, $value);
				}
			}
			
		} else {
		
			// yes, use the custom callbacks
			$callbacks = func_get_args();
			
			// drop $value
			array_shift($callbacks);
			
			// loop through custom callbacks.
			foreach ($callbacks as $func) {
				// this if() shaves 0.001sec off of 300 calls.
				if (is_string($func)) {
					$value = $func($value);
				} else {
					$value = call_user_func($func, $value);
				}
			}
			
		}
		
		return $value;
	}
	
	
	/**
	*
	* Prints a value after escaping it for output.
	* 
	* You can override the predefined escaping callbacks by passing
	* added parameters as replacement callbacks.
	* 
	* <code>
	* // use predefined callbacks
	* $this->eprint($value);
	* 
	* // use replacement callbacks
	* $this->eprint(
	*     $value,
	*     'stripslashes',
	*     'htmlspecialchars',
	*     array('StaticClass', 'method'),
	*     array($object, $method)
	* );
	* </code>
	* 
	* @access public
	* 
	* @param mixed $value The value to be escaped and printed.
	* 
	* @return void
	*
	*/
	
	public function eprint($value)
	{
		// avoid the very slow call_user_func_array() when there
		// are no custom escaping callbacks.  thanks to
		// Andreas Korthaus for profiling the code to find
		// the slowdown.
		$num = func_num_args();
		if ($num == 1) {
			echo $this->escape($value);
		} else {
			$args = func_get_args();
			echo call_user_func_array(
				array($this, 'escape'),
				$args
			);
		}
	}
	
	
	// -----------------------------------------------------------------
	//
	// File management
	//
	// -----------------------------------------------------------------
	
	
	/**
	*
	* Sets an entire array of search paths for templates or resources.
	*
	* @access public
	*
	* @param string $type The type of path to set, typically 'template'
	* or 'resource'.
	* 
	* @param string|array $path The new set of search paths.  If null or
	* false, resets to the current directory only.
	*
	* @return void
	*
	*/
	
	public function setPath($type, $path)
	{
		// clear out the prior search dirs
		$this->__config[$type . '_path'] = array();
		
		// always add the fallback directories as last resort
		switch (strtolower($type)) {
		case 'template':
			// the current directory
			$this->addPath($type, '.');
			break;
		case 'resource':
			// the Savant3 distribution resources
			$this->addPath($type, dirname(__FILE__) . '/Savant3/resources/');
			break;
		}
		
		// actually add the user-specified directories
		$this->addPath($type, $path);
	}
	
	
	/**
	*
	* Adds to the search path for templates and resources.
	*
	* @access public
	*
	* @param string|array $path The directory or stream to search.
	*
	* @return void
	*
	*/
	
	public function addPath($type, $path)
	{
		// convert from path string to array of directories
		if (is_string($path) && ! strpos($path, '://')) {
		
			// the path config is a string, and it's not a stream
			// identifier (the "://" piece). add it as a path string.
			$path = explode(PATH_SEPARATOR, $path);
			
			// typically in path strings, the first one is expected
			// to be searched first. however, Savant3 uses a stack,
			// so the first would be last.  reverse the path string
			// so that it behaves as expected with path strings.
			$path = array_reverse($path);
			
		} else {
		
			// just force to array
			settype($path, 'array');
			
		}
		
		// loop through the path directories
		foreach ($path as $dir) {
		
			// no surrounding spaces allowed!
			$dir = trim($dir);
			
			// add trailing separators as needed
			if (strpos($dir, '://') && substr($dir, -1) != '/') {
				// stream
				$dir .= '/';
			} elseif (substr($dir, -1) != DIRECTORY_SEPARATOR) {
				// directory
				$dir .= DIRECTORY_SEPARATOR;
			}
			
			// add to the top of the search dirs
			array_unshift(
				$this->__config[$type . '_path'],
				$dir
			);
		}
	}
	
	
	/**
	* 
	* Searches the directory paths for a given file.
	* 
	* @param array $type The type of path to search (template or resource).
	* 
	* @param string $file The file name to look for.
	* 
	* @return string|bool The full path and file name for the target file,
	* or boolean false if the file is not found in any of the paths.
	*
	*/
	
	protected function findFile($type, $file)
	{
		// get the set of paths
		$set = $this->__config[$type . '_path'];
		
		// start looping through the path set
		foreach ($set as $path) {
			
			// get the path to the file
			$fullname = $path . $file;
			
			// is the path based on a stream?
			if (strpos($path, '://') === false) {
				// not a stream, so do a realpath() to avoid
				// directory traversal attempts on the local file
				// system. Suggested by Ian Eure, initially
				// rejected, but then adopted when the secure
				// compiler was added.
				$path = realpath($path); // needed for substr() later
				$fullname = realpath($fullname);
			}
			
			// the substr() check added by Ian Eure to make sure
			// that the realpath() results in a directory registered
			// with Savant so that non-registered directores are not
			// accessible via directory traversal attempts.
			if (file_exists($fullname) && is_readable($fullname) &&
				substr($fullname, 0, strlen($path)) == $path) {
				return $fullname;
			}
		}
		
		// could not find the file in the set of paths
		return false;
	}
	
	
	// -----------------------------------------------------------------
	//
	// Variable and reference assignment
	//
	// -----------------------------------------------------------------
	
	
	/**
	* 
	* Sets variables for the template (by copy).
	* 
	* This method is overloaded; you can assign all the properties of
	* an object, an associative array, or a single value by name.
	* 
	* You are not allowed to assign any variable named '__config' as
	* it would conflict with internal configuration tracking.
	* 
	* In the following examples, the template will have two variables
	* assigned to it; the variables will be known inside the template as
	* "$this->var1" and "$this->var2".
	* 
	* <code>
	* $Savant3 = new Savant3();
	* 
	* // assign by object
	* $obj = new stdClass;
	* $obj->var1 = 'something';
	* $obj->var2 = 'else';
	* $Savant3->assign($obj);
	* 
	* // assign by associative array
	* $ary = array('var1' => 'something', 'var2' => 'else');
	* $Savant3->assign($ary);
	* 
	* // assign by name and value
	* $Savant3->assign('var1', 'something');
	* $Savant3->assign('var2', 'else');
	* 
	* // assign directly
	* $Savant3->var1 = 'something';
	* $Savant3->var2 = 'else';
	* </code>
	* 
	* @access public
	* 
	* @return bool True on success, false on failure.
	* 
	*/
	
	public function assign()
	{
		// get the arguments; there may be 1 or 2.
		$arg0 = @func_get_arg(0);
		$arg1 = @func_get_arg(1);
		
		// assign from object
		if (is_object($arg0)) {
			// assign public properties
			foreach (get_object_vars($arg0) as $key => $val) {
				// can't assign to __config
				if ($key != '__config') {
					$this->$key = $val;
				}
			}
			return true;
		}
		
		// assign from associative array
		if (is_array($arg0)) {
			foreach ($arg0 as $key => $val) {
				// can't assign to __config
				if ($key != '__config') {
					$this->$key = $val;
				}
			}
			return true;
		}
		
		// assign by name and value (can't assign to __config).
		if (is_string($arg0) && func_num_args() > 1 && $arg0 != '__config') {
			$this->$arg0 = $arg1;
			return true;
		}
		
		// $arg0 was not object, array, or string.
		return false;
	}
	
	
	/**
	* 
	* Sets variables for the template (by reference).
	* 
	* You are not allowed to assign any variable named '__config' as
	* it would conflict with internal configuration tracking.
	* 
	* <code>
	* $Savant3 = new Savant3();
	* 
	* // assign by name and value
	* $Savant3->assignRef('var1', $ref);
	* 
	* // assign directly
	* $Savant3->ref =& $var1;
	* </code>
	* 
	* @access public
	* 
	* @return bool True on success, false on failure.
	* 
	*/
	
	public function assignRef($key, &$val)
	{
		// assign by name and reference (can't assign to __config).
		if ($key != '__config') {
			$this->$key =& $val;
			return true;
		} else {
			return false;
		}
	}
	
	
	// -----------------------------------------------------------------
	//
	// Template processing
	//
	// -----------------------------------------------------------------
	
	
	/**
	* 
	* Displays a template directly (equivalent to <code>echo $tpl</code>).
	* 
	* @access public
	* 
	* @param string $tpl The template source to compile and display.
	* 
	* @return void
	* 
	*/
	
	public function display($tpl = null)
	{
		echo $this->getOutput($tpl);
	}
	
	/**
	 * Returns output, including error_text if an error occurs.
	 * 
	 * @param $tpl The template to process; if null, uses the
	 * default template set with setTemplate().
	 * 
	 * @return string The template output.
	 */
	public function getOutput($tpl = null)
	{
	    $output = $this->fetch($tpl);
        if ($this->isError($output)) {
            $text = $this->__config['error_text'];
            return $this->escape($text);
        } else {
            return $output;
        }
	}
	
	
	/**
	* 
	* Compiles, executes, and filters a template source.
	* 
	* @access public
	* 
	* @param string $tpl The template to process; if null, uses the
	* default template set with setTemplate().
	* 
	* @return mixed The template output string, or a Savant3_Error.
	* 
	*/
	
	public function fetch($tpl = null)
	{
		// make sure we have a template source to work with
		if (is_null($tpl)) {
			$tpl = $this->__config['template'];
		}
		
		// get a path to the compiled template script
		$result = $this->template($tpl);
		
		// did we get a path?
		if (! $result || $this->isError($result)) {
		
			// no. return the error result.
			return $result;
			
		} else {
		
			// yes.  execute the template script.  move the script-path
			// out of the local scope, then clean up the local scope to
			// avoid variable name conflicts.
			$this->__config['fetch'] = $result;
			unset($result);
			unset($tpl);
			
			// are we doing extraction?
			if ($this->__config['extract']) {
				// pull variables into the local scope.
				extract(get_object_vars($this), EXTR_REFS);
			}
			
			// buffer output so we can return it instead of displaying.
			ob_start();
			
			// are we using filters?
			if ($this->__config['filters']) {
				// use a second buffer to apply filters. we used to set
				// the ob_start() filter callback, but that would
				// silence errors in the filters. Hendy Irawan provided
				// the next three lines as a "verbose" fix.
				ob_start();
				include $this->__config['fetch'];
				echo $this->applyFilters(ob_get_clean());
			} else {
				// no filters being used.
				include $this->__config['fetch'];
			}
			
			// reset the fetch script value, get the buffer, and return.
			$this->__config['fetch'] = null;
			return ob_get_clean();
		}
	}
	
	
	/**
	*
	* Compiles a template and returns path to compiled script.
	* 
	* By default, Savant does not compile templates, it uses PHP as the
	* markup language, so the "compiled" template is the same as the source
	* template.
	* 
	* Used inside a template script like so:
	* 
	* <code>
	* include $this->template($tpl);
	* </code>
	* 
	* @access protected
	*
	* @param string $tpl The template source name to look for.
	* 
	* @return string The full path to the compiled template script.
	* 
	* @throws object An error object with a 'ERR_TEMPLATE' code.
	* 
	*/
	
	protected function template($tpl = null)
	{
		// set to default template if none specified.
		if (is_null($tpl)) {
			$tpl = $this->__config['template'];
		}
		
		// find the template source.
		$file = $this->findFile('template', $tpl);
		if (! $file) {
			return $this->error(
				'ERR_TEMPLATE',
				array('template' => $tpl)
			);
		}
		
		// are we compiling source into a script?
		if ($this->__config['compiler']) {
			// compile the template source and get the path to the
			// compiled script (will be returned instead of the
			// source path)
			$result = call_user_func(
				array($this->__config['compiler'], 'compile'),
				$file
			);
		} else {
			// no compiling requested, use the source path
			$result = $file;
		}
		
		// is there a script from the compiler?
		if (! $result || $this->isError($result)) {
			// return an error, along with any error info
			// generated by the compiler.
			return $this->error(
				'ERR_COMPILER',
				array(
					'template' => $tpl,
					'compiler' => $result
				)
			);
		} else {
			// no errors, the result is a path to a script
			return $result;
		}
	}
	
	
	// -----------------------------------------------------------------
	//
	// Filter management and processing
	//
	// -----------------------------------------------------------------
	
	
	/**
	* 
	* Resets the filter stack to the provided list of callbacks.
	* 
	* @access protected
	* 
	* @param array An array of filter callbacks.
	* 
	* @return void
	* 
	*/
	
	public function setFilters()
	{
		$this->__config['filters'] = (array) @func_get_args();
	}
	
	
	/**
	* 
	* Adds filter callbacks to the stack of filters.
	* 
	* @access protected
	* 
	* @param array An array of filter callbacks.
	* 
	* @return void
	* 
	*/
	
	public function addFilters()
	{
		// add the new filters to the static config variable
		// via the reference
		foreach ((array) @func_get_args() as $callback) {
			$this->__config['filters'][] = $callback;
		}
	}
	
	
	/**
	* 
	* Runs all filter callbacks on buffered output.
	* 
	* @access protected
	* 
	* @param string The template output.
	* 
	* @return void
	* 
	*/
	
	protected function applyFilters($buffer)
	{
	   $autoload = $this->__config['autoload'];
		foreach ($this->__config['filters'] as $callback) {
		
			// if the callback is a static Savant3_Filter method,
			// and not already loaded, try to auto-load it.
			if (is_array($callback) &&
				is_string($callback[0]) &&
				substr($callback[0], 0, 15) == 'Savant3_Filter_' &&
				! class_exists($callback[0], $autoload)) {
				
				// load the Savant3_Filter_*.php resource
				$file = $callback[0] . '.php';
				$result = $this->findFile('resource', $file);
				if ($result) {
					include_once $result;
				}
			}
			
			// can't pass a third $this param, it chokes the OB system.
			$buffer = call_user_func($callback, $buffer);
		}
		
		return $buffer;
	}
	
	
	// -----------------------------------------------------------------
	//
	// Error handling
	//
	// -----------------------------------------------------------------
	
	
	/**
	*
	* Returns an error object or throws an exception.
	* 
	* @access public
	* 
	* @param string $code A Savant3 'ERR_*' string.
	* 
	* @param array $info An array of error-specific information.
	* 
	* @param int $level The error severity level, default is
	* E_USER_ERROR (the most severe possible).
	* 
	* @param bool $trace Whether or not to include a backtrace, default
	* true.
	* 
	* @return object Savant3_Error
	* 
	*/
	
	public function error($code, $info = array(), $level = E_USER_ERROR,
		$trace = true)
	{
		$autoload = $this->__config['autoload'];
		
		// are we throwing exceptions?
		if ($this->__config['exceptions']) {
			if (! class_exists('Savant3_Exception', $autoload)) {
				include_once dirname(__FILE__) . '/Savant3/Exception.php';
			}
			throw new Savant3_Exception($code);
		}
		
		
		// the error config array
		$config = array(
			'code'  => $code,
			'info'  => (array) $info,
			'level' => $level,
			'trace' => $trace
		);
		
		// make sure the Savant3 error class is available
		if (! class_exists('Savant3_Error', $autoload)) {
			include_once dirname(__FILE__) . '/Savant3/Error.php';
		}
		
		// return it
		$err = new Savant3_Error($config);
		return $err;
	}
	
	
	/**
	*
	* Tests if an object is of the Savant3_Error class.
	* 
	* @access public
	* 
	* @param object $obj The object to be tested.
	* 
	* @return boolean True if $obj is an error object of the type
	* Savant3_Error, or is a subclass that Savant3_Error. False if not.
	*
	*/
	
	public function isError($obj)
	{
		$autoload = $this->__config['autoload'];
		
		// is it even an object?
		if (! is_object($obj)) {
			// not an object, so can't be a Savant3_Error
			return false;
		} else {
			// make sure the Savant3 error class is available for
			// comparison
			if (! class_exists('Savant3_Error', $autoload)) {
				include_once dirname(__FILE__) . '/Savant3/Error.php';
			}
			// now compare the parentage
			$is = $obj instanceof Savant3_Error;
			$sub = is_subclass_of($obj, 'Savant3_Error');
			return ($is || $sub);
		}
	}
}
?>