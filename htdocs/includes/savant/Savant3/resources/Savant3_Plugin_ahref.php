<?php

/**
* 
* Generates an <a href="">...</a> tag.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* 
* @version $Id: Savant3_Plugin_ahref.php,v 1.4 2005/08/09 12:56:14 pmjones Exp $
*
*/

/**
* 
* Generates an <a href="">...</a> tag.
*
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
*/

class Savant3_Plugin_ahref extends Savant3_Plugin {

	/**
	* 
	* Generate an HTML <a href="">...</a> tag.
	* 
	* @access public
	* 
	* @param string|array $href A string URL for the resulting tag.  May
	* also be an array with any combination of the keys 'scheme',
	* 'host', 'path', 'query', and 'fragment' (c.f. PHP's native
	* parse_url() function).
	* 
	* @param string $text The displayed text of the link.
	* 
	* @param string|array $attr Any extra attributes for the <a> tag.
	* 
	* @return string The <a href="">...</a> tag.
	* 
	*/
	
	public function ahref($href, $text, $attr = null)
	{
		$html = '<a href="';
		
		if (is_array($href)) {
			
			// add the HREF from an array
			$tmp = '';
			
			if (isset($href['scheme'])) {
				$tmp .= $href['scheme'] . ':';
				if (strtolower($href['scheme']) != 'mailto') {
					$tmp .= '//';
				}
			}
			
			if (isset($href['host'])) {
				$tmp .= $href['host'];
			}
			
			if (isset($href['path'])) {
				$tmp .= $href['path'];
			}
			
			if (isset($href['query'])) {
				$tmp .= '?' . $href['query'];
			}
			
			if (isset($href['fragment'])) {
				$tmp .= '#' . $href['fragment'];
			}
		
			$html .= htmlspecialchars($tmp);
			
		} else {
		
			// add the HREF from a scalar
			$html .= htmlspecialchars($href);
			
		}
		
		$html .= '"';
		
		// add attributes
		if (is_array($attr)) {
			// from array
			foreach ($attr as $key => $val) {
				$key = htmlspecialchars($key);
				$val = htmlspecialchars($val);
				$html .= " $key=\"$val\"";
			}
		} elseif (! is_null($attr)) {
			// from scalar
			$html .= htmlspecialchars(" $attr");
		}
		
		// set the link text, close the tag, and return
		$html .= '>' . $text . '</a>';
		return $html;
	}
}
?>