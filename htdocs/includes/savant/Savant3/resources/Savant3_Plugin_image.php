<?php

/**
* 
* Plugin to generate an <img ... /> tag.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
* @license http://www.gnu.org/copyleft/lesser.html LGPL
* 
* @version $Id: Savant3_Plugin_image.php,v 1.7 2005/08/12 14:34:09 pmjones Exp $
*
*/

/**
* 
* Plugin to generate an <img ... /> tag.
*
* Support for alpha transparency of PNG files in Microsoft IE added by
* Edward Ritter; thanks, Edward.
* 
* @package Savant3
* 
* @author Paul M. Jones <pmjones@ciaweb.net>
* 
*/

class Savant3_Plugin_image extends Savant3_Plugin {
	
	
	/**
	* 
	* The document root.
	* 
	* @access public
	* 
	* @var string
	* 
	*/
	
	protected $documentRoot = null;
	
	
	/**
	* 
	* The base directory for images within the document root.
	* 
	* @access public
	* 
	* @var string
	* 
	*/
	
	protected $imageDir = null;
	
	
	/**
	* 
	* Outputs an <img ... /> tag.
	* 
	* Microsoft IE alpha PNG support added by Edward Ritter.
	* 
	* @access public
	* 
	* @param string $file The path to the image on the local file system
	* relative to $this->imageDir.
	* 
	* @param string $alt Alternative descriptive text for the image;
	* defaults to the filename of the image.
	* 
	* @param int $border The border width for the image; defaults to zero.
	* 
	* @param int $width The displayed image width in pixels; defaults to
	* the width of the image.
	* 
	* @param int $height The displayed image height in pixels; defaults to
	* the height of the image.
	* 
	* @return string An <img ... /> tag.
	* 
	*/
	
	public function image($file, $alt = null, $height = null, $width = null,
		$attr = null)
	{
		// is the document root set?
		if (is_null($this->documentRoot) && isset($_SERVER['DOCUMENT_ROOT'])) {
			// no, so set it
			$this->documentRoot = $_SERVER['DOCUMENT_ROOT'];
		}
		
		// make sure there's a DIRECTORY_SEPARATOR between the docroot
		// and the image dir
		if (substr($this->documentRoot, -1) != DIRECTORY_SEPARATOR &&
			substr($this->imageDir, 0, 1) != DIRECTORY_SEPARATOR) {
			$this->documentRoot .= DIRECTORY_SEPARATOR;
		}
		
		// make sure there's a separator between the imageDir and the
		// file name
		if (substr($this->imageDir, -1) != DIRECTORY_SEPARATOR &&
			substr($file, 0, 1) != DIRECTORY_SEPARATOR) {
			$this->imageDir .= DIRECTORY_SEPARATOR;
		}
		
		// the image file type code (PNG = 3)
		$type = null;
		
		// get the file information
		$info = false;
		
		if (strpos($file, '://') === false) {
			// no "://" in the file, so it's local
			$file = $this->imageDir . $file;
			$tmp = $this->documentRoot . $file;
			$info = @getimagesize($tmp);
		} else {
			// don't attempt to get file info from streams, it takes
			// way too long.
			$info = false;
		}
		
		// did we find the file info?
		if (is_array($info)) {
		
			// capture type info regardless
			$type = $info[2];
			
			// capture size info where both not specified
			if (is_null($width) && is_null($height)) {
				$width = $info[0];
				$height = $info[1];
			}
		}
		
		// clean up
		unset($info);
		
		// is the file a PNG? if so, check user agent, we will need to
		// make special allowances for Microsoft IE.
		if (stristr($_SERVER['HTTP_USER_AGENT'], 'MSIE') && $type === 3) {
			
			// support alpha transparency for PNG files in MSIE
			$html = '<span style="position: relative;';
			
			if ($height) {
				$html .= ' height: ' . $height . 'px;';
			}
			
			if ($width) {
				$html .= ' width: ' . $width . 'px;';
			}
			
			$html .= ' filter:progid:DXImageTransform.Microsoft.AlphaImageLoader';
			$html .= "(src='" . htmlspecialchars($file) . "',sizingMethod='scale');\"";
			$html .= ' title="' . htmlspecialchars($alt) . '"';
			
			$html .= $this->Savant->htmlAttribs($attr);

			// done
			$html .= '></span>';
			
		} else {
			
			// not IE, so build a normal image tag.
			$html = '<img';
			$html .= ' src="' . htmlspecialchars($file) . '"';
			
			// add the alt attribute
			if (is_null($alt)) {
				$alt = basename($file);
			}
			$html .= ' alt="' . htmlspecialchars($alt) . '"';
			
			// add the height attribute
			if ($height) {
				$html .= ' height="' . htmlspecialchars($height) . '"';
			}
			
			// add the width attribute
			if ($width) {
				$html .= ' width="' . htmlspecialchars($width) . '"';
			}
			
			$html .= $this->Savant->htmlAttribs($attr);
			
			// done
			$html .= ' />';
			
		}
		
		// done!
		return $html;
	}
}

?>