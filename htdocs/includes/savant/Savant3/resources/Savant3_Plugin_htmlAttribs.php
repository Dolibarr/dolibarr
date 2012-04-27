<?php

/**
* 
* Plugin to convert an associative array to a string of tag attributes.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* 
* @version $Id: Savant3_Plugin_htmlAttribs.php,v 1.3 2005/09/12 17:49:27 pmjones Exp $
* 
*/

/**
* 
* Plugin to convert an associative array to a string of tag attributes.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
*/

class Savant3_Plugin_htmlAttribs extends Savant3_Plugin {

	/**
	* 
	* Converts an associative array to a string of tag attributes.
	* 
	* @access public
	* 
	* @param array $attribs From this array, each key-value pair is 
	* converted to an attribute name and value.
	* 
	* @return string The XHTML for the attributes.
	* 
	*/
	
	public function htmlAttribs($attribs)
	{
		$xhtml = '';
		foreach ((array) $attribs as $key => $val) {
		
			if ($val === null) {
				continue;
			}
			
			if (is_array($val)) {
				$val = implode(' ', $val);
			}
			
			$key = htmlspecialchars($key);
			$val = htmlspecialchars($val);
			
			$xhtml .= " $key=\"$val\"";
		}
		return $xhtml;
	}
}
?>