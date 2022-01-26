<?php
require 'SegmentIterator.php';
class SegmentException extends Exception
{
}

/**
 * Class for handling templating segments with odt files
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 *
 * @copyright  2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @copyright  2012 - Stephen Larroque - lrq3000@gmail.com
 * @license    https://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.4.5 (last update 2013-04-07)
 */
class Segment implements IteratorAggregate, Countable
{
	protected $xml;
	protected $xmlParsed = '';
	protected $name;
	protected $children = array();
	protected $vars = array();
	protected $images = array();
	protected $odf;
	protected $file;

	/**
	 * Constructor
	 *
	 * @param string $name  name of the segment to construct
	 * @param string $xml   XML tree of the segment
	 * @param string $odf   odf
	 */
	public function __construct($name, $xml, $odf)
	{
		$this->name = (string) $name;
		$this->xml = (string) $xml;
		$this->odf = $odf;
		$zipHandler = $this->odf->getConfig('ZIP_PROXY');
		$this->file = new $zipHandler($this->odf->getConfig('PATH_TO_TMP'));
		$this->_analyseChildren($this->xml);
	}
	/**
	 * Returns the name of the segment
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	/**
	 * Does the segment have children ?
	 *
	 * @return bool
	 */
	public function hasChildren()
	{
		return $this->getIterator()->hasChildren();
	}
	/**
	 * Countable interface
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->children);
	}
	/**
	 * IteratorAggregate interface
	 *
	 * @return Iterator
	 */
	public function getIterator()
	{
		return new RecursiveIteratorIterator(new SegmentIterator($this->children), 1);
	}
	/**
	 * Replace variables of the template in the XML code
	 * All the children are also called
	 * Complete the current segment with new line
	 *
	 * @return string
	 */
	public function merge()
	{
		// To provide debug information on line number processed
		global $count;
		if (empty($count)) $count=1;
		else $count++;

		if (empty($this->savxml)) $this->savxml = $this->xml;       // Sav content of line at first line merged, so we will reuse original for next steps
		$this->xml = $this->savxml;
		$tmpvars = $this->vars;                                     // Store into $tmpvars so we won't modify this->vars when completing data with empty values

		// Search all tags fou into condition to complete $tmpvars, so we will proceed all tests even if not defined
		$reg='@\[!--\sIF\s([{}a-zA-Z0-9\.\,_]+)\s--\]@smU';
		$matches = array();
		preg_match_all($reg, $this->xml, $matches, PREG_SET_ORDER);
		//var_dump($tmpvars);exit;
		foreach ($matches as $match) {   // For each match, if there is no entry into this->vars, we add it
			if (! empty($match[1]) && ! isset($tmpvars[$match[1]])) {
				$tmpvars[$match[1]] = '';     // Not defined, so we set it to '', we just need entry into this->vars for next loop
			}
		}

		// Conditionals substitution
		// Note: must be done before static substitution, else the variable will be replaced by its value and the conditional won't work anymore
		foreach ($tmpvars as $key => $value) {
			// If value is true (not 0 nor false nor null nor empty string)
			if ($value) {
				// Remove the IF tag
				$this->xml = str_replace('[!-- IF '.$key.' --]', '', $this->xml);
				// Remove everything between the ELSE tag (if it exists) and the ENDIF tag
				$reg = '@(\[!--\sELSE\s' . $key . '\s--\](.*))?\[!--\sENDIF\s' . $key . '\s--\]@smU'; // U modifier = all quantifiers are non-greedy
				$this->xml = preg_replace($reg, '', $this->xml);
			}
			// Else the value is false, then two cases: no ELSE and we're done, or there is at least one place where there is an ELSE clause, then we replace it
			else {
				// Find all conditional blocks for this variable: from IF to ELSE and to ENDIF
				$reg = '@\[!--\sIF\s' . $key . '\s--\](.*)(\[!--\sELSE\s' . $key . '\s--\](.*))?\[!--\sENDIF\s' . $key . '\s--\]@smU'; // U modifier = all quantifiers are non-greedy
				preg_match_all($reg, $this->xml, $matches, PREG_SET_ORDER);
				foreach ($matches as $match) { // For each match, if there is an ELSE clause, we replace the whole block by the value in the ELSE clause
					if (!empty($match[3])) $this->xml = str_replace($match[0], $match[3], $this->xml);
				}
				// Cleanup the other conditional blocks (all the others where there were no ELSE clause, we can just remove them altogether)
				$this->xml = preg_replace($reg, '', $this->xml);
			}
		}

		$this->xmlParsed .= str_replace(array_keys($tmpvars), array_values($tmpvars), $this->xml);
		if ($this->hasChildren()) {
			foreach ($this->children as $child) {
				$this->xmlParsed = str_replace($child->xml, ($child->xmlParsed=="")?$child->merge():$child->xmlParsed, $this->xmlParsed);
				$child->xmlParsed = '';
			}
		}
		$reg = "/\[!--\sBEGIN\s$this->name\s--\](.*)\[!--\sEND\s$this->name\s--\]/sm";
		$this->xmlParsed = preg_replace($reg, '$1', $this->xmlParsed);
		// Miguel Erill 09704/2017 - Add macro replacement to invoice lines
		$this->xmlParsed = $this->macroReplace($this->xmlParsed);
		$this->file->open($this->odf->getTmpfile());
		foreach ($this->images as $imageKey => $imageValue) {
			if ($this->file->getFromName('Pictures/' . $imageValue) === false) {
				// Add the image inside the ODT document
				$this->file->addFile($imageKey, 'Pictures/' . $imageValue);
				// Add the image to the Manifest (which maintains a list of images, necessary to avoid "Corrupt ODT file. Repair?" when opening the file with LibreOffice)
				$this->odf->addImageToManifest($imageValue);
			}
		}
		$this->file->close();

		return $this->xmlParsed;
	}

	/**
	* Function to replace macros for invoice short and long month, invoice year
	*
	* Substitution occur when the invoice is generated, not considering the invoice date
	* so do not (re)generate in a diferent date than the one that the invoice belongs to
	* Perhaps it would be better to use the invoice issued date but I still do not know
	* how to get it here
	*
	* Miguel Erill 09/04/2017
	*
	* @param	string	$value	String to convert
	*/
	public function macroReplace($text)
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
		global $langs;

		$hoy = dol_getdate(dol_now('tzuser'));
		$dateinonemontharray = dol_get_next_month($hoy['mon'], $hoy['year']);
		$nextMonth = $dateinonemontharray['month'];

		$patterns=array( '/__CURRENTDAY__/u','/__CURENTWEEKDAY__/u',
						 '/__CURRENTMONTH__/u','/__CURRENTMONTHLONG__/u',
						 '/__NEXTMONTH__/u','/__NEXTMONTHLONG__/u',
						 '/__CURRENTYEAR__/u','/__NEXTYEAR__/u' );
		$values=array( $hoy['mday'], $langs->transnoentitiesnoconv($hoy['weekday']),
					   $hoy['mon'], $langs->transnoentitiesnoconv($hoy['month']),
					   $nextMonth, monthArray($langs)[$nextMonth],
					   $hoy['year'], $hoy['year']+1 );

		$text=preg_replace($patterns, $values, $text);

		return $text;
	}

	/**
	 * Analyse the XML code in order to find children
	 *
	 * @param string $xml   Xml
	 * @return Segment
	 */
	protected function _analyseChildren($xml)
	{
		// $reg2 = "#\[!--\sBEGIN\s([\S]*)\s--\](?:<\/text:p>)?(.*)(?:<text:p\s.*>)?\[!--\sEND\s(\\1)\s--\]#sm";
		$reg2 = "#\[!--\sBEGIN\s([\S]*)\s--\](.*)\[!--\sEND\s(\\1)\s--\]#sm";
		preg_match_all($reg2, $xml, $matches);
		for ($i = 0, $size = count($matches[0]); $i < $size; $i++) {
			if ($matches[1][$i] != $this->name) {
				$this->children[$matches[1][$i]] = new self($matches[1][$i], $matches[0][$i], $this->odf);
			} else {
				$this->_analyseChildren($matches[2][$i]);
			}
		}
		return $this;
	}

	/**
	 * Assign a template variable to replace
	 *
	 * @param string $key       Key
	 * @param string $value     Value
	 * @param string $encode    Encode
	 * @param string $charset   Charset
	 * @throws SegmentException
	 * @return Segment
	 */
	public function setVars($key, $value, $encode = true, $charset = 'ISO-8859')
	{
		$tag = $this->odf->getConfig('DELIMITER_LEFT') . $key . $this->odf->getConfig('DELIMITER_RIGHT');

		if (strpos($this->xml, $tag) === false) {
			//throw new SegmentException("var $key not found in {$this->getName()}");
		}

		$this->vars[$tag] = $this->odf->convertVarToOdf($value, $encode, $charset);

		return $this;
	}

	/**
	 * Assign a template variable as a picture
	 *
	 * @param string $key name of the variable within the template
	 * @param string $value path to the picture
	 * @throws OdfException
	 * @return Segment
	 */
	public function setImage($key, $value)
	{
		$filename = strtok(strrchr($value, '/'), '/.');
		$file = substr(strrchr($value, '/'), 1);
		$size = @getimagesize($value);
		if ($size === false) {
			throw new OdfException("Invalid image");
		}
		// Set the width and height of the page
		list ($width, $height) = $size;
		$width *= Odf::PIXEL_TO_CM;
		$height *= Odf::PIXEL_TO_CM;
		// Fix local-aware issues (eg: 12,10 -> 12.10)
		$width = sprintf("%F", $width);
		$height = sprintf("%F", $height);

		$xml = <<<IMG
<draw:frame draw:style-name="fr1" draw:name="$filename" text:anchor-type="aschar" svg:width="{$width}cm" svg:height="{$height}cm" draw:z-index="3"><draw:image xlink:href="Pictures/$file" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/></draw:frame>
IMG;
		$this->images[$value] = $file;
		$this->setVars($key, $xml, false);
		return $this;
	}
	/**
	 * Shortcut to retrieve a child
	 *
	 * @param string $prop      Prop
	 * @return Segment
	 * @throws SegmentException
	 */
	public function __get($prop)
	{
		if (array_key_exists($prop, $this->children)) {
			return $this->children[$prop];
		} else {
			throw new SegmentException('child ' . $prop . ' does not exist');
		}
	}
	/**
	 * Proxy for setVars
	 *
	 * @param string $meth      Meth
	 * @param array $args       Args
	 * @return Segment
	 */
	public function __call($meth, $args)
	{
		try {
			return $this->setVars($meth, $args[0]);
		} catch (SegmentException $e) {
			throw new SegmentException("method $meth nor var $meth exist");
		}
	}
	/**
	 * Returns the parsed XML
	 *
	 * @return string
	 */
	public function getXmlParsed()
	{
		return $this->xmlParsed;
	}
}
