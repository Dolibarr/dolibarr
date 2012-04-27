<?php

/**
* 
* Plugin to generate a formatted date using strftime() conventions.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* 
* @version $Id: Savant3_Plugin_date.php,v 1.3 2005/03/07 14:40:16 pmjones Exp $
*
*/

/**
* 
* Plugin to generate a formatted date using strftime() conventions.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
*/

class Savant3_Plugin_date extends Savant3_Plugin {
	
	/**
	* 
	* The default strftime() format string.
	* 
	* @access public
	* 
	* @var array
	* 
	*/
	
	public $default = '%c';
	
	
	/**
	* 
	* Custom strftime() format strings to use for dates.
	* 
	* You can preset the format strings via Savant3::setPluginConf().
	* 
	* <code>
	* $conf = array(
	*     'custom' => array(
	*         'mydate' => '%Y-%m-%d',
	*         'mytime' => '%R'
	*     )
	* );
	* 
	* $Savant->setPluginConf('date', $conf);
	* </code>
	* 
	* ... and in your template, to use a preset custom string by name:
	* 
	* <code>
	* echo $this->date($value, 'mydate');
	* </code>
	* 
	* @access public
	* 
	* @var array
	* 
	*/
	
	public $custom = array(
		'date'    => '%Y-%m-%d',
		'time'    => '%H:%M:%S'
	);
	
	
	/**
	* 
	* Outputs a formatted date using strftime() conventions.
	* 
	* @access public
	* 
	* @param string $datestring Any date-time string suitable for
	* strtotime().
	* 
	* @param string $format The strftime() formatting string, or a named
	* custom string key from $this->custom.
	* 
	* @return string The formatted date string.
	* 
	*/
	
	function date($datestring, $format = null)
	{
		settype($format, 'string');
		
		if (is_null($format)) {
			$format = $this->default;
		}
		
		// does the format string have a % sign in it?
		if (strpos($format, '%') === false) {
			// no, look for a custom format string
			if (! empty($this->custom[$format])) {
				// found a custom format string
				$format = $this->custom[$format];
			} else {
				// did not find the custom format, revert to default
				$format = $this->default;
			}
		}
		
		// convert the date string to the specified format
		if (trim($datestring != '')) {
			return strftime($format, strtotime($datestring));
		} else {
			// no datestring, return VOID
			return;
		}
	}

}
?>