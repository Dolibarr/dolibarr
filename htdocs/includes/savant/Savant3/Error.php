<?php

/**
* 
* Provides a simple error class for Savant.
*
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* 
* @version $Id: Error.php,v 1.5 2005/05/27 14:03:50 pmjones Exp $
* 
*/

/**
* 
* Provides a simple error class for Savant.
*
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
*/

class Savant3_Error {
	
	
	/**
	* 
	* The error code, typically a Savant 'ERR_*' string.
	* 
	* @access public
	*
	* @var string
	*
	*/
	
	public $code = null;
	
	
	/**
	* 
	* An array of error-specific information.
	* 
	* @access public
	*
	* @var array
	*
	*/
	
	public $info = array();
	
	
	/**
	* 
	* The error severity level.
	*
	* @access public
	*
	* @var int
	*
	*/
	
	public $level = E_USER_ERROR;
	
	
	/**
	* 
	* A debug backtrace for the error, if any.
	*
	* @access public
	*
	* @var array
	*
	*/
	
	public $trace = null;
	
	
	/**
	* 
	* Constructor.
	*
	* @access public
	*
	* @param array $conf An associative array where the key is a
	* Savant3_Error property and the value is the value for that
	* property.
	*
	*/
	
	public function __construct($conf = array())
	{
		// set public properties
		foreach ($conf as $key => $val) {
			$this->$key = $val;
		}
		
		// add a backtrace
		if ($conf['trace'] === true) {
			$this->trace = debug_backtrace();
		}
	}
	
	
	/**
	* 
	* Magic method for output dump.
	*
	* @access public
	*
	* @return void
	*/
	
	public function __toString()
	{
		ob_start();
		echo get_class($this) . ': ';
		print_r(get_object_vars($this));
		return ob_get_clean();
	}
}
?>