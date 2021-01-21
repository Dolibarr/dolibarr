<?php

require 'Segment.php';

class OdfException extends Exception
{
}

/**
 * Templating class for odt file
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 *
 * @copyright  2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @copyright  2010-2015 - Laurent Destailleur - eldy@users.sourceforge.net
 * @copyright  2010 - Vikas Mahajan - http://vikasmahajan.wordpress.com
 * @copyright  2012 - Stephen Larroque - lrq3000@gmail.com
 * @license    https://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.5.0
 */
class Odf
{
	protected $config = array(
	'ZIP_PROXY' => 'PclZipProxy',	// PclZipProxy, PhpZipProxy
	'DELIMITER_LEFT' => '{',
	'DELIMITER_RIGHT' => '}',
	'PATH_TO_TMP' => '/tmp'
	);
	protected $file;
	protected $contentXml;			// To store content of content.xml file
	protected $metaXml;			    // To store content of meta.xml file
	protected $stylesXml;			// To store content of styles.xml file
	protected $manifestXml;			// To store content of META-INF/manifest.xml file
	protected $tmpfile;
	protected $tmpdir='';
	protected $images = array();
	protected $vars = array();
	protected $segments = array();

	public $creator;
	public $title;
	public $subject;
	public $userdefined=array();

	const PIXEL_TO_CM = 0.026458333;

	/**
	 * Class constructor
	 *
	 * @param string $filename     The name of the odt file
	 * @param string $config       Array of config data
	 * @throws OdfException
	 */
	public function __construct($filename, $config = array())
	{
		clearstatcache();

		if (! is_array($config)) {
			throw new OdfException('Configuration data must be provided as array');
		}
		foreach ($config as $configKey => $configValue) {
			if (array_key_exists($configKey, $this->config)) {
				$this->config[$configKey] = $configValue;
			}
		}

		$md5uniqid = md5(uniqid());
		if ($this->config['PATH_TO_TMP']) $this->tmpdir = preg_replace('|[\/]$|','',$this->config['PATH_TO_TMP']);	// Remove last \ or /
		$this->tmpdir .= ($this->tmpdir?'/':'').$md5uniqid;
		$this->tmpfile = $this->tmpdir.'/'.$md5uniqid.'.odt';	// We keep .odt extension to allow OpenOffice usage during debug.

		// A working directory is required for some zip proxy like PclZipProxy
		if (in_array($this->config['ZIP_PROXY'],array('PclZipProxy')) && ! is_dir($this->config['PATH_TO_TMP'])) {
			throw new OdfException('Temporary directory '.$this->config['PATH_TO_TMP'].' must exists');
		}

		// Create tmp direcoty (will be deleted in destructor)
		if (!file_exists($this->tmpdir)) {
			$result=mkdir($this->tmpdir);
		}

		// Load zip proxy
		$zipHandler = $this->config['ZIP_PROXY'];
		if (!defined('PCLZIP_TEMPORARY_DIR')) define('PCLZIP_TEMPORARY_DIR',$this->tmpdir);
		include_once('zip/'.$zipHandler.'.php');
		if (! class_exists($this->config['ZIP_PROXY'])) {
			throw new OdfException($this->config['ZIP_PROXY'] . ' class not found - check your php settings');
		}
		$this->file = new $zipHandler($this->tmpdir);


		if ($this->file->open($filename) !== true) {	// This also create the tmpdir directory
			throw new OdfException("Error while Opening the file '$filename' - Check your odt filename");
		}
		if (($this->contentXml = $this->file->getFromName('content.xml')) === false) {
			throw new OdfException("Nothing to parse - Check that the content.xml file is correctly formed in source file '$filename'");
		}
		if (($this->manifestXml = $this->file->getFromName('META-INF/manifest.xml')) === false) {
			throw new OdfException("Something is wrong with META-INF/manifest.xml in source file '$filename'");
		}
		if (($this->metaXml = $this->file->getFromName('meta.xml')) === false) {
			throw new OdfException("Nothing to parse - Check that the meta.xml file is correctly formed in source file '$filename'");
		}
		if (($this->stylesXml = $this->file->getFromName('styles.xml')) === false) {
			throw new OdfException("Nothing to parse - Check that the styles.xml file is correctly formed in source file '$filename'");
		}
		$this->file->close();


		//print "tmpdir=".$tmpdir;
		//print "filename=".$filename;
		//print "tmpfile=".$tmpfile;

		copy($filename, $this->tmpfile);

		// Now file has been loaded, we must move the [!-- BEGIN and [!-- END tags outside the
		// <table:table-row tag and clean bad lines tags.
		$this->_moveRowSegments();
	}

	/**
	 * Assing a template variable
	 *
	 * @param string   $key        Name of the variable within the template
	 * @param string   $value      Replacement value
	 * @param bool     $encode     If true, special XML characters are encoded
	 * @param string   $charset    Charset
	 * @throws OdfException
	 * @return odf
	 */
	public function setVars($key, $value, $encode = true, $charset = 'ISO-8859')
	{
		$tag = $this->config['DELIMITER_LEFT'] . $key . $this->config['DELIMITER_RIGHT'];
		// TODO Warning string may be:
		// <text:span text:style-name="T13">{</text:span><text:span text:style-name="T12">aaa</text:span><text:span text:style-name="T13">}</text:span>
		// instead of {aaa} so we should enhance this function.
		//print $key.'-'.$value.'-'.strpos($this->contentXml, $this->config['DELIMITER_LEFT'] . $key . $this->config['DELIMITER_RIGHT']).'<br>';
		if (strpos($this->contentXml, $tag) === false && strpos($this->stylesXml, $tag) === false) {
			//if (strpos($this->contentXml, '">'. $key . '</text;span>') === false) {
			throw new OdfException("var $key not found in the document");
			//}
		}

		$this->vars[$tag] = $this->convertVarToOdf($value, $encode, $charset);

		return $this;
	}

	/**
     * Replaces html tags in odt tags and returns a compatible string
     * @param string   $key        Name of the variable within the template
	 * @param string   $value      Replacement value
	 * @param bool     $encode     If true, special XML characters are encoded
	 * @param string   $charset    Charset
     * @return string
     */
	public function convertVarToOdf($value, $encode = true, $charset = 'ISO-8859')
	{
		$value = $encode ? htmlspecialchars($value) : $value;
		$value = ($charset == 'ISO-8859') ? utf8_encode($value) : $value;
		$convertedValue = $value;

		// Check if the value includes html tags
		if ($this->_hasHtmlTag($value) === true) {
			// Default styles for strong/b, i/em, u, s, sub & sup
			$automaticStyles = array(
				'<style:style style:name="boldText" style:family="text"><style:text-properties fo:font-weight="bold" style:font-weight-asian="bold" style:font-weight-complex="bold" /></style:style>',
				'<style:style style:name="italicText" style:family="text"><style:text-properties fo:font-style="italic" style:font-style-asian="italic" style:font-style-complex="italic" /></style:style>',
				'<style:style style:name="underlineText" style:family="text"><style:text-properties style:text-underline-style="solid" style:text-underline-width="auto" style:text-underline-color="font-color" /></style:style>',
				'<style:style style:name="strikethroughText" style:family="text"><style:text-properties style:text-line-through-style="solid" style:text-line-through-type="single" /></style:style>',
				'<style:style style:name="subText" style:family="text"><style:text-properties style:text-position="sub 58%" /></style:style>',
				'<style:style style:name="supText" style:family="text"><style:text-properties style:text-position="super 58%" /></style:style>'
			);

			$convertedValue = $this->_replaceHtmlWithOdtTag($this->_getDataFromHtml($value), $customStyles, $fontDeclarations);

			foreach ($customStyles as $key => $val) {
				array_push($automaticStyles, '<style:style style:name="customStyle' . $key . '" style:family="text">' . $val . '</style:style>');
			}

			// Join the styles and add them to the content xml
			$styles = '';
			foreach ($automaticStyles as $style) {
				if (strpos($this->contentXml, $style) === false) {
					$styles .= $style;
				}
			}
			$this->contentXml = str_replace('</office:automatic-styles>', $styles . '</office:automatic-styles>', $this->contentXml);

			// Join the font declarations and add them to the content xml
			$fonts = '';
			foreach ($fontDeclarations as $font) {
				if (strpos($this->contentXml, 'style:name="' . $font . '"') === false) {
					$fonts .= '<style:font-face style:name="' . $font . '" svg:font-family="\'' . $font . '\'" />';
				}
			}
			$this->contentXml = str_replace('</office:font-face-decls>', $fonts . '</office:font-face-decls>', $this->contentXml);
		}
		else $convertedValue = preg_replace('/(\r\n|\r|\n)/i', "<text:line-break/>", $value);

		return $convertedValue;
	}

	/**
     * Replaces html tags in with odt tags and returns an odt string
     * @param array $tags   An array with html tags generated by the getDataFromHtml() function
     * @param array $customStyles   An array of style defenitions that should be included inside the odt file
     * @param array $fontDeclarations   An array of font declarations that should be included inside the odt file
     * @return string
     */
    private function _replaceHtmlWithOdtTag($tags, &$customStyles, &$fontDeclarations)
	{
        if ($customStyles == null) $customStyles = array();
        if ($fontDeclarations == null) $fontDeclarations = array();

        $odtResult = '';

        foreach ((array) $tags as $tag) {
            // Check if the current item is a tag or just plain text
            if (isset($tag['text'])) {
                $odtResult .= $tag['text'];
            } elseif (isset($tag['name'])) {
                switch ($tag['name']) {
                    case 'br':
                        $odtResult .= '<text:line-break/>';
                        break;
                    case 'strong':
                    case 'b':
                        $odtResult .= '<text:span text:style-name="boldText">' . ($tag['children'] != null ? $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations) : $tag['innerText']) . '</text:span>';
                        break;
                    case 'i':
                    case 'em':
                        $odtResult .= '<text:span text:style-name="italicText">' . ($tag['children'] != null ? $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations) : $tag['innerText']) . '</text:span>';
                        break;
                    case 'u':
                        $odtResult .= '<text:span text:style-name="underlineText">' . ($tag['children'] != null ? $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations) : $tag['innerText']) . '</text:span>';
                        break;
                    case 's':
                        $odtResult .= '<text:span text:style-name="strikethroughText">' . ($tag['children'] != null ? $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations) : $tag['innerText']) . '</text:span>';
                        break;
                    case 'sub':
                        $odtResult .= '<text:span text:style-name="subText">' . ($tag['children'] != null ? $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations) : $tag['innerText']) . '</text:span>';
                        break;
                    case 'sup':
                        $odtResult .= '<text:span text:style-name="supText">' . ($tag['children'] != null ? $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations) : $tag['innerText']) . '</text:span>';
                        break;
                    case 'span':
                        if (isset($tag['attributes']['style'])) {
                            $odtStyles = '';
                            foreach ($tag['attributes']['style'] as $styleName => $styleValue) {
                                switch ($styleName) {
                                    case 'font-family':
                                        $fontName = $styleValue;
                                        if (strpos($fontName, ',') !== false) {
                                            $fontName = explode(',', $fontName)[0];
                                        }
                                        if (!in_array($fontName, $fontDeclarations)) {
                                            array_push($fontDeclarations, $fontName);
                                        }
                                        $odtStyles .= '<style:text-properties style:font-name="' . $fontName . '" />';
                                        break;
                                    case 'font-size':
                                        if (preg_match('/([0-9]+)\s?(px|pt)/', $styleValue, $matches)) {
                                            $fontSize = intval($matches[1]);
                                            if ($matches[2] == 'px') {
                                                $fontSize = round($fontSize * 0.75);
                                            }
                                            $odtStyles .= '<style:text-properties fo:font-size="' . $fontSize . 'pt" style:font-size-asian="' . $fontSize . 'pt" style:font-size-complex="' . $fontSize . 'pt" />';
                                        }
                                        break;
                                    case 'color':
                                        if (preg_match('/#[0-9A-Fa-f]{3}(?:[0-9A-Fa-f]{3})?/', $styleValue)) {
                                            $odtStyles .= '<style:text-properties fo:color="' . $styleValue . '" />';
                                        }
                                        break;
                                }
                            }
                            if (strlen($odtStyles) > 0) {
								// Generate a unique id for the style (using microtime and random because some CPUs are really fast...)
                                $key = floatval(str_replace('.', '', microtime(true)))+rand(0, 10);
                                $customStyles[$key] = $odtStyles;
                                $odtResult .= '<text:span text:style-name="customStyle' . $key . '">' . ($tag['children'] != null ? $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations) : $tag['innerText']) . '</text:span>';
                            }
                        }
                        break;
                    default:
                        $odtResult .= $this->_replaceHtmlWithOdtTag($tag['children'], $customStyles, $fontDeclarations);
                        break;
                }
            }
        }
        return $odtResult;
    }

    /**
     * Checks if the given text is a html string
     * @param string    $text   The text to check
     * @return bool
     */
    private function _isHtmlTag($text)
	{
        return preg_match('/<([A-Za-z]+)(?:\s([A-Za-z]+(?:\-[A-Za-z]+)?(?:=(?:".*?")|(?:[0-9]+))))*(?:(?:\s\/>)|(?:>(.*)<\/\1>))/', $text);
    }

    /**
     * Checks if the given text includes a html string
     * @param string    $text   The text to check
     * @return bool
     */
    private function _hasHtmlTag($text)
	{
        $result = preg_match_all('/<([A-Za-z]+)(?:\s([A-Za-z]+(?:\-[A-Za-z]+)?(?:=(?:".*?")|(?:[0-9]+))))*(?:(?:\s\/>)|(?:>(.*)<\/\1>))/', $text);
        return is_numeric($result) && $result > 0;
    }

    /**
     * Returns an array of html elements
     * @param string    $html   A string with html tags
     * @return array
     */
    private function _getDataFromHtml($html)
	{
        $tags = array();
        $tempHtml = $html;

        while (strlen($tempHtml) > 0) {
            // Check if the string includes a html tag
            if (preg_match_all('/<([A-Za-z]+)(?:\s([A-Za-z]+(?:\-[A-Za-z]+)?(?:=(?:".*?")|(?:[0-9]+))))*(?:(?:\s\/>)|(?:>(.*)<\/\1>))/', $tempHtml, $matches)) {
                $tagOffset = strpos($tempHtml, $matches[0][0]);
                // Check if the string starts with the html tag
                if ($tagOffset > 0) {
                    // Push the text infront of the html tag to the result array
                    array_push($tags, array(
                        'text' => substr($tempHtml, 0, $tagOffset)
                    ));
                    // Remove the text from the string
                    $tempHtml = substr($tempHtml, $tagOffset);
                }
                // Extract the attribute data from the html tag
                preg_match_all('/([0-9A-Za-z]+(?:="[0-9A-Za-z\:\-\s\,\;\#]*")?)+/', $matches[2][0], $explodedAttributes);
                $explodedAttributes = array_filter($explodedAttributes[0]);
                $attributes = array();
                // Store each attribute with its name in the $attributes array
                $explodedAttributesCount = count($explodedAttributes);
                for ($i=0; $i<$explodedAttributesCount; $i++) {
                    $attribute = trim($explodedAttributes[$i]);
                    // Check if the attribute has a value (like style="") or has no value (like required)
                    if (strpos($attribute, '=') !== false) {
                        $splitAttribute = explode('=', $attribute);
                        $attrName = trim($splitAttribute[0]);
                        $attrValue = trim(str_replace('"', '', $splitAttribute[1]));
                        // check if the current attribute is a style attribute
                        if (strtolower($attrName) == 'style') {
                            $attributes[$attrName] = array();
                            if (strpos($attrValue, ';') !== false) {
                                // Split the style properties and store them in an array
                                $explodedStyles = explode(';', $attrValue);
                                $explodedStylesCount = count($explodedStyles);
                                for ($n=0; $n<$explodedStylesCount; $n++) {
                                    $splitStyle = explode(':', $explodedStyles[$n]);
                                    $attributes[$attrName][trim($splitStyle[0])] = trim($splitStyle[1]);
                                }
                            } else {
                                $splitStyle = explode(':', $attrValue);
                                $attributes[$attrName][trim($splitStyle[0])] = trim($splitStyle[1]);
                            }
                        } else {
                            // Store the value directly in the $attributes array if this is not the style attribute
                            $attributes[$attrName] = $attrValue;
                        }
                    } else {
                        $attributes[trim($attribute)] = true;
                    }
                }
                // Push the html tag data to the result array
                array_push($tags, array(
                    'name' => $matches[1][0],
                    'attributes' => $attributes,
                    'innerText' => strip_tags($matches[3][0]),
                    'children' => $this->_hasHtmlTag($matches[3][0]) ? $this->_getDataFromHtml($matches[3][0]) : null
                ));
                // Remove the processed html tag from the html string
                $tempHtml = substr($tempHtml, strlen($matches[0][0]));
            } else {
                array_push($tags, array(
                    'text' => $tempHtml
                ));
                $tempHtml = '';
            }
        }
        return $tags;
    }


	/**
	 * Function to convert a HTML string into an ODT string
	 *
	 * @param	string	$value	String to convert
	 */
	public function htmlToUTFAndPreOdf($value)
	{
		// We decode into utf8, entities
		$value=dol_html_entity_decode($value, ENT_QUOTES|ENT_HTML5);

		// We convert html tags
		$ishtml=dol_textishtml($value);
		if ($ishtml)
		{
	        // If string is "MYPODUCT - Desc <strong>bold</strong> with &eacute; accent<br />\n<br />\nUn texto en espa&ntilde;ol ?"
    	    // Result after clean must be "MYPODUCT - Desc bold with é accent\n\nUn texto en espa&ntilde;ol ?"

			// We want to ignore \n and we want all <br> to be \n
			$value=preg_replace('/(\r\n|\r|\n)/i','',$value);
			$value=preg_replace('/<br>/i',"\n",$value);
			$value=preg_replace('/<br\s+[^<>\/]*>/i',"\n",$value);
			$value=preg_replace('/<br\s+[^<>\/]*\/>/i',"\n",$value);

			//$value=preg_replace('/<strong>/','__lt__text:p text:style-name=__quot__bold__quot____gt__',$value);
			//$value=preg_replace('/<\/strong>/','__lt__/text:p__gt__',$value);

			$value=dol_string_nohtmltag($value, 0);
		}

		return $value;
	}


	/**
	 * Function to convert a HTML string into an ODT string
	 *
	 * @param	string	$value	String to convert
	 */
	public function preOdfToOdf($value)
	{
		$value = str_replace("\n", "<text:line-break/>", $value);

		//$value = str_replace("__lt__", "<", $value);
		//$value = str_replace("__gt__", ">", $value);
		//$value = str_replace("__quot__", '"', $value);

		return $value;
	}

	/**
	 * Evaluating php codes inside the ODT and output the buffer (print, echo) inplace of the code
	 *
	 * @return int             0
	 */
	public function phpEval()
	{
		preg_match_all('/[\{\<]\?(php)?\s+(?P<content>.+)\?[\}\>]/iU',$this->contentXml, $matches); // detecting all {?php code ?} or <?php code ? >
		$nbfound=count($matches['content']);
		for ($i=0; $i < $nbfound; $i++)
		{
			try {
				$ob_output = ''; // flush the output for each code. This var will be filled in by the eval($code) and output buffering : any print or echo or output will be redirected into this variable
				$code = $matches['content'][$i];
				ob_start();
				eval ($code);
				$ob_output = ob_get_contents(); // send the content of the buffer into $ob_output
				$this->contentXml = str_replace($matches[0][$i], $ob_output, $this->contentXml);
				ob_end_clean();
			} catch (Exception $e) {
				ob_end_clean();
				$this->contentXml = str_replace($matches[0][$i], 'ERROR: there was a problem while evaluating this portion of code, please fix it: '.$e, $this->contentXml);
			}
		}
		return 0;
	}

	/**
	 * Assign a template variable as a picture
	 *
	 * @param string $key name of the variable within the template
	 * @param string $value path to the picture
	 * @throws OdfException
	 * @return odf
	 */
	public function setImage($key, $value)
	{
		$filename = strtok(strrchr($value, '/'), '/.');
		$file = substr(strrchr($value, '/'), 1);
		$size = @getimagesize($value);
		if ($size === false) {
			throw new OdfException("Invalid image");
		}
		list ($width, $height) = $size;
		$width *= self::PIXEL_TO_CM;
		$height *= self::PIXEL_TO_CM;
		$xml = <<<IMG
			<draw:frame draw:style-name="fr1" draw:name="$filename" text:anchor-type="aschar" svg:width="{$width}cm" svg:height="{$height}cm" draw:z-index="3"><draw:image xlink:href="Pictures/$file" xlink:type="simple" xlink:show="embed" xlink:actuate="onLoad"/></draw:frame>
IMG;
		$this->images[$value] = $file;
		$this->setVars($key, $xml, false);
		return $this;
	}

	/**
	 * Move segment tags for lines of tables
	 * This function is called automatically within the constructor, so this->contentXml is clean before any other thing
	 *
	 * @return void
	 */
	private function _moveRowSegments()
	{
	    // Replace BEGIN<text:s/>xxx into BEGIN xxx
	    $this->contentXml = preg_replace('/\[!--\sBEGIN<text:s[^>]>(row.[\S]*)\s--\]/sm', '[!-- BEGIN \\1 --]', $this->contentXml);
	    // Replace END<text:s/>xxx into END xxx
	    $this->contentXml = preg_replace('/\[!--\sEND<text:s[^>]>(row.[\S]*)\s--\]/sm', '[!-- END \\1 --]', $this->contentXml);

	    // Search all possible rows in the document
		$reg1 = "#<table:table-row[^>]*>(.*)</table:table-row>#smU";
		preg_match_all($reg1, $this->contentXml, $matches);
		for ($i = 0, $size = count($matches[0]); $i < $size; $i++) {
			// Check if the current row contains a segment row.*
			$reg2 = '#\[!--\sBEGIN\s(row.[\S]*)\s--\](.*)\[!--\sEND\s\\1\s--\]#sm';
			if (preg_match($reg2, $matches[0][$i], $matches2)) {
				$balise = str_replace('row.', '', $matches2[1]);
				// Move segment tags around the row
				$replace = array(
				'[!-- BEGIN ' . $matches2[1] . ' --]'	=> '',
				'[!-- END ' . $matches2[1] . ' --]'		=> '',
				'<table:table-row'							=> '[!-- BEGIN ' . $balise . ' --]<table:table-row',
				'</table:table-row>'						=> '</table:table-row>[!-- END ' . $balise . ' --]'
				);
				$replacedXML = str_replace(array_keys($replace), array_values($replace), $matches[0][$i]);
				$this->contentXml = str_replace($matches[0][$i], $replacedXML, $this->contentXml);
			}
		}
	}

	/**
	 * Merge template variables
	 * Called at the beginning of the _save function
	 *
	 * @param  string	$type		'content', 'styles' or 'meta'
	 * @return void
	 */
	private function _parse($type='content')
	{
	    // Search all tags fou into condition to complete $this->vars, so we will proceed all tests even if not defined
	    $reg='@\[!--\sIF\s([{}a-zA-Z0-9\.\,_]+)\s--\]@smU';
	    preg_match_all($reg, $this->contentXml, $matches, PREG_SET_ORDER);

	    //var_dump($this->vars);exit;
	    foreach($matches as $match)   // For each match, if there is no entry into this->vars, we add it
		{
		    if (! empty($match[1]) && ! isset($this->vars[$match[1]]))
			{
			    $this->vars[$match[1]] = '';     // Not defined, so we set it to '', we just need entry into this->vars for next loop
			}
	    }
	    //var_dump($this->vars);exit;

		// Conditionals substitution
		// Note: must be done before static substitution, else the variable will be replaced by its value and the conditional won't work anymore
	    foreach($this->vars as $key => $value)
		{
			// If value is true (not 0 nor false nor null nor empty string)
			if ($value)
			{
			    //dol_syslog("Var ".$key." is defined, we remove the IF, ELSE and ENDIF ");
			    //$sav=$this->contentXml;
				// Remove the IF tag
				$this->contentXml = str_replace('[!-- IF '.$key.' --]', '', $this->contentXml);
				// Remove everything between the ELSE tag (if it exists) and the ENDIF tag
				$reg = '@(\[!--\sELSE\s' . $key . '\s--\](.*))?\[!--\sENDIF\s' . $key . '\s--\]@smU'; // U modifier = all quantifiers are non-greedy
				$this->contentXml = preg_replace($reg, '', $this->contentXml);
				/*if ($sav != $this->contentXml)
				{
				    dol_syslog("We found a IF and it was processed");
				    //var_dump($sav);exit;
				}*/
			}
			// Else the value is false, then two cases: no ELSE and we're done, or there is at least one place where there is an ELSE clause, then we replace it
			else
			{
			    //dol_syslog("Var ".$key." is not defined, we remove the IF, ELSE and ENDIF ");
			    //$sav=$this->contentXml;
				// Find all conditional blocks for this variable: from IF to ELSE and to ENDIF
				$reg = '@\[!--\sIF\s' . $key . '\s--\](.*)(\[!--\sELSE\s' . $key . '\s--\](.*))?\[!--\sENDIF\s' . $key . '\s--\]@smU'; // U modifier = all quantifiers are non-greedy
				preg_match_all($reg, $this->contentXml, $matches, PREG_SET_ORDER);
				foreach($matches as $match) { // For each match, if there is an ELSE clause, we replace the whole block by the value in the ELSE clause
					if (!empty($match[3])) $this->contentXml = str_replace($match[0], $match[3], $this->contentXml);
				}
				// Cleanup the other conditional blocks (all the others where there were no ELSE clause, we can just remove them altogether)
				$this->contentXml = preg_replace($reg, '', $this->contentXml);
				/*if ($sav != $this->contentXml)
				{
				    dol_syslog("We found a IF and it was processed");
				    //var_dump($sav);exit;
				}*/
			}
		}

		// Static substitution
		if ($type == 'content')	$this->contentXml = str_replace(array_keys($this->vars), array_values($this->vars), $this->contentXml);
		if ($type == 'styles')	$this->stylesXml = str_replace(array_keys($this->vars), array_values($this->vars), $this->stylesXml);
		if ($type == 'meta')	$this->metaXml = str_replace(array_keys($this->vars), array_values($this->vars), $this->metaXml);

	}

	/**
	 * Add the merged segment to the document
	 *
	 * @param Segment $segment     Segment
	 * @throws OdfException
	 * @return odf
	 */
	public function mergeSegment(Segment $segment)
	{
		if (! array_key_exists($segment->getName(), $this->segments)) {
			throw new OdfException($segment->getName() . 'cannot be parsed, has it been set yet ?');
		}
		$string = $segment->getName();
		// $reg = '@<text:p[^>]*>\[!--\sBEGIN\s' . $string . '\s--\](.*)\[!--.+END\s' . $string . '\s--\]<\/text:p>@smU';
		$reg = '@\[!--\sBEGIN\s' . $string . '\s--\](.*)\[!--.+END\s' . $string . '\s--\]@smU';
		$this->contentXml = preg_replace($reg, $segment->getXmlParsed(), $this->contentXml);
		return $this;
	}

	/**
	 * Display all the current template variables
	 *
	 * @return string
	 */
	public function printVars()
	{
		return print_r('<pre>' . print_r($this->vars, true) . '</pre>', true);
	}

	/**
	 * Display the XML content of the file from odt document
	 * as it is at the moment
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->contentXml;
	}

	/**
	 * Display loop segments declared with setSegment()
	 *
	 * @return string
	 */
	public function printDeclaredSegments()
	{
		return '<pre>' . print_r(implode(' ', array_keys($this->segments)), true) . '</pre>';
	}

	/**
	 * Declare a segment in order to use it in a loop.
	 * Extract the segment and store it into $this->segments[]. Return it for next call.
	 *
	 * @param  string      $segment        Segment
	 * @throws OdfException
	 * @return Segment
	 */
	public function setSegment($segment)
	{
		if (array_key_exists($segment, $this->segments)) {
			return $this->segments[$segment];
		}
		// $reg = "#\[!--\sBEGIN\s$segment\s--\]<\/text:p>(.*)<text:p\s.*>\[!--\sEND\s$segment\s--\]#sm";
		$reg = "#\[!--\sBEGIN\s$segment\s--\](.*)\[!--\sEND\s$segment\s--\]#sm";
		if (preg_match($reg, html_entity_decode($this->contentXml), $m) == 0) {
			throw new OdfException("'".$segment."' segment not found in the document. The tag [!-- BEGIN xxx --] or [!-- END xxx --] is not present into content file.");
		}
		$this->segments[$segment] = new Segment($segment, $m[1], $this);
		return $this->segments[$segment];
	}
	/**
	 * Save the odt file on the disk
	 *
	 * @param string $file name of the desired file
	 * @throws OdfException
	 * @return void
	 */
	public function saveToDisk($file = null)
	{
		if ($file !== null && is_string($file)) {
			if (file_exists($file) && !(is_file($file) && is_writable($file))) {
				throw new OdfException('Permission denied : can\'t create ' . $file);
			}
			$this->_save();
			copy($this->tmpfile, $file);
		} else {
			$this->_save();
		}
	}

	/**
	 * Write output file onto disk
	 *
	 * @throws OdfException
	 * @return void
	 */
	private function _save()
	{
		$res=$this->file->open($this->tmpfile);    // tmpfile is odt template
		$this->_parse('content');
		$this->_parse('styles');
		$this->_parse('meta');

		$this->setMetaData();
		//print $this->metaXml;exit;

		if (! $this->file->addFromString('content.xml', $this->contentXml)) {
			throw new OdfException('Error during file export addFromString content');
		}
		if (! $this->file->addFromString('meta.xml', $this->metaXml)) {
			throw new OdfException('Error during file export addFromString meta');
		}
		if (! $this->file->addFromString('styles.xml', $this->stylesXml)) {
			throw new OdfException('Error during file export addFromString styles');
		}

		foreach ($this->images as $imageKey => $imageValue) {
			// Add the image inside the ODT document
			$this->file->addFile($imageKey, 'Pictures/' . $imageValue);
			// Add the image to the Manifest (which maintains a list of images, necessary to avoid "Corrupt ODT file. Repair?" when opening the file with LibreOffice)
			$this->addImageToManifest($imageValue);
		}
		if (! $this->file->addFromString('./META-INF/manifest.xml', $this->manifestXml)) {
			throw new OdfException('Error during file export: manifest.xml');
		}
		$this->file->close();
	}

	/**
	 * Update Meta information
	 * <dc:date>2013-03-16T14:06:25</dc:date>
	 *
	 * @return void
	 */
	public function setMetaData()
	{
	    if (empty($this->creator)) $this->creator='';

		$this->metaXml = preg_replace('/<dc:date>.*<\/dc:date>/', '<dc:date>'.gmdate("Y-m-d\TH:i:s").'</dc:date>', $this->metaXml);
		$this->metaXml = preg_replace('/<dc:creator>.*<\/dc:creator>/', '<dc:creator>'.htmlspecialchars($this->creator).'</dc:creator>', $this->metaXml);
		$this->metaXml = preg_replace('/<dc:title>.*<\/dc:title>/', '<dc:title>'.htmlspecialchars($this->title).'</dc:title>', $this->metaXml);
		$this->metaXml = preg_replace('/<dc:subject>.*<\/dc:subject>/', '<dc:subject>'.htmlspecialchars($this->subject).'</dc:subject>', $this->metaXml);

		if (count($this->userdefined))
		{
		    foreach($this->userdefined as $key => $val)
		    {
		      $this->metaXml = preg_replace('<meta:user-defined meta:name="'.$key.'"/>', '', $this->metaXml);
		      $this->metaXml = preg_replace('/<meta:user-defined meta:name="'.$key.'">.*<\/meta:user-defined>/', '', $this->metaXml);
		      $this->metaXml = str_replace('</office:meta>', '<meta:user-defined meta:name="'.$key.'">'.htmlspecialchars($val).'</meta:user-defined></office:meta>', $this->metaXml);
		    }
		}
	}

	/**
	 * Update Manifest file according to added image files
	 *
	 * @param string	$file		Image file to add into manifest content
	 * @return void
	 */
	public function addImageToManifest($file)
	{
		// Get the file extension
		$ext = substr(strrchr($file, '.'), 1);
		// Create the correct image XML entry to add to the manifest (this is necessary because ODT format requires that we keep a list of the images in the manifest.xml)
		$add = ' <manifest:file-entry manifest:media-type="image/'.$ext.'" manifest:full-path="Pictures/'.$file.'"/>'."\n";
		// Append the image to the manifest
		$this->manifestXml = str_replace('</manifest:manifest>', $add.'</manifest:manifest>', $this->manifestXml); // we replace the manifest closing tag by the image XML entry + manifest closing tag (this results in appending the data, we do not overwrite anything)
	}

	/**
	 * Export the file as attached file by HTTP
	 *
	 * @param string $name (optional)
	 * @throws OdfException
	 * @return void
	 */
	public function exportAsAttachedFile($name = "")
	{
		$this->_save();
		if (headers_sent($filename, $linenum)) {
			throw new OdfException("headers already sent ($filename at $linenum)");
		}

		if( $name == "" )
		{
			$name = md5(uniqid()) . ".odt";
		}

		header('Content-type: application/vnd.oasis.opendocument.text');
		header('Content-Disposition: attachment; filename="'.$name.'"');
		header('Content-Length: '.filesize($this->tmpfile));
		readfile($this->tmpfile);
	}

	/**
	 * Convert the ODT file to PDF and export the file as attached file by HTTP
	 * Note: you need to have JODConverter and OpenOffice or LibreOffice installed and executable on the same system as where this php script will be executed. You also need to chmod +x odt2pdf.sh
	 *
	 * @param 	string 	$name 	Name of ODT file to generate before generating PDF
	 * @throws OdfException
	 * @return void
	 */
	public function exportAsAttachedPDF($name="")
	{
		global $conf;

		if( $name == "" ) $name = "temp".md5(uniqid());

		dol_syslog(get_class($this).'::exportAsAttachedPDF $name='.$name, LOG_DEBUG);
		$this->saveToDisk($name);

		$execmethod=(empty($conf->global->MAIN_EXEC_USE_POPEN)?1:2);	// 1 or 2
		// Method 1 sometimes hang the server.


		// Export to PDF using LibreOffice
		if ($conf->global->MAIN_ODT_AS_PDF == 'libreoffice')
		{
			// Install prerequisites: apt install soffice libreoffice-common libreoffice-writer
			// using windows libreoffice that must be in path
			// using linux/mac libreoffice that must be in path
			// Note PHP Config "fastcgi.impersonate=0" must set to 0 - Default is 1
			$command ='soffice --headless -env:UserInstallation=file:"//'.$conf->user->dir_temp.'" --convert-to pdf --outdir '. escapeshellarg(dirname($name)). " ".escapeshellarg($name);
		}
		elseif (preg_match('/unoconv/', $conf->global->MAIN_ODT_AS_PDF))
		{
			// If issue with unoconv, see https://github.com/dagwieers/unoconv/issues/87

			// MAIN_ODT_AS_PDF should be   "sudo -u unoconv /usr/bin/unoconv" and userunoconv must have sudo to be root by adding file /etc/sudoers.d/unoconv with content  www-data ALL=(unoconv) NOPASSWD: /usr/bin/unoconv .

			// Try this with www-data user:    /usr/bin/unoconv -vvvv -f pdf /tmp/document-example.odt
			// It must return:
			//Verbosity set to level 4
			//Using office base path: /usr/lib/libreoffice
			//Using office binary path: /usr/lib/libreoffice/program
			//DEBUG: Connection type: socket,host=127.0.0.1,port=2002;urp;StarOffice.ComponentContext
			//DEBUG: Existing listener not found.
			//DEBUG: Launching our own listener using /usr/lib/libreoffice/program/soffice.bin.
			//LibreOffice listener successfully started. (pid=9287)
			//Input file: /tmp/document-example.odt
			//unoconv: file `/tmp/document-example.odt' does not exist.
			//unoconv: RuntimeException during import phase:
			//Office probably died. Unsupported URL <file:///tmp/document-example.odt>: "type detection failed"
			//DEBUG: Terminating LibreOffice instance.
			//DEBUG: Waiting for LibreOffice instance to exit

			// If it fails:
			// - set shell of user to bash instead of nologin.
			// - set permission to read/write to user on home directory /var/www so user can create the libreoffice , dconf and .cache dir and files then set permission back

			$command = $conf->global->MAIN_ODT_AS_PDF.' '.escapeshellcmd($name);
			//$command = '/usr/bin/unoconv -vvv '.escapeshellcmd($name);
		}
		else
		{
			// deprecated old method using odt2pdf.sh (native, jodconverter, ...)
			$tmpname=preg_replace('/\.odt/i', '', $name);

			if (!empty($conf->global->MAIN_DOL_SCRIPTS_ROOT))
			{
				$command = $conf->global->MAIN_DOL_SCRIPTS_ROOT.'/scripts/odt2pdf/odt2pdf.sh '.escapeshellcmd($tmpname).' '.(is_numeric($conf->global->MAIN_ODT_AS_PDF)?'jodconverter':$conf->global->MAIN_ODT_AS_PDF);
			}
			else
			{
			    dol_syslog(get_class($this).'::exportAsAttachedPDF is used but the constant MAIN_DOL_SCRIPTS_ROOT with path to script directory was not defined.', LOG_WARNING);
				$command = '../../scripts/odt2pdf/odt2pdf.sh '.escapeshellcmd($tmpname).' '.(is_numeric($conf->global->MAIN_ODT_AS_PDF)?'jodconverter':$conf->global->MAIN_ODT_AS_PDF);
			}
		}

		//$dirname=dirname($name);
		//$command = DOL_DOCUMENT_ROOT.'/includes/odtphp/odt2pdf.sh '.$name.' '.$dirname;

		dol_syslog(get_class($this).'::exportAsAttachedPDF $execmethod='.$execmethod.' Run command='.$command,LOG_DEBUG);
		$retval=0; $output_arr=array();
		if ($execmethod == 1)
		{
			exec($command, $output_arr, $retval);
		}
		if ($execmethod == 2)
		{
			$outputfile = DOL_DATA_ROOT.'/odt2pdf.log';

			$ok=0;
			$handle = fopen($outputfile, 'w');
			if ($handle)
			{
				dol_syslog(get_class($this)."Run command ".$command,LOG_DEBUG);
				fwrite($handle, $command."\n");
				$handlein = popen($command, 'r');
				while (!feof($handlein))
				{
					$read = fgets($handlein);
					fwrite($handle, $read);
					$output_arr[]=$read;
				}
				pclose($handlein);
				fclose($handle);
			}
			if (! empty($conf->global->MAIN_UMASK)) @chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		}

		if ($retval == 0)
		{
			dol_syslog(get_class($this).'::exportAsAttachedPDF $ret_val='.$retval, LOG_DEBUG);
			$filename=''; $linenum=0;
			if (headers_sent($filename, $linenum)) {
				throw new OdfException("headers already sent ($filename at $linenum)");
			}

			if (!empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$name=preg_replace('/\.od(x|t)/i', '', $name);
				header('Content-type: application/pdf');
				header('Content-Disposition: attachment; filename="'.$name.'.pdf"');
				readfile($name.".pdf");
			}
			if (!empty($conf->global->MAIN_ODT_AS_PDF_DEL_SOURCE))
			{
				unlink($name);
			}
		} else {
			dol_syslog(get_class($this).'::exportAsAttachedPDF $ret_val='.$retval, LOG_DEBUG);
			dol_syslog(get_class($this).'::exportAsAttachedPDF $output_arr='.var_export($output_arr, true), LOG_DEBUG);

			if ($retval==126) {
				throw new OdfException('Permission execute convert script : ' . $command);
			}
			else {
			    $errorstring='';
				foreach($output_arr as $line) {
				    $errorstring.= $line."<br>";
				}
				throw new OdfException('ODT to PDF convert fail (option MAIN_ODT_AS_PDF is '.$conf->global->MAIN_ODT_AS_PDF.', command was '.$command.', retval='.$retval.') : ' . $errorstring);
			}
		}
	}

	/**
	 * Returns a variable of configuration
	 *
	 * @param  string  $configKey  Config key
	 * @return string              The requested variable of configuration
	 */
	public function getConfig($configKey)
	{
		if (array_key_exists($configKey, $this->config)) {
			return $this->config[$configKey];
		}
		return false;
	}
	/**
	 * Returns the temporary working file
	 *
	 * @return string le chemin vers le fichier temporaire de travail
	 */
	public function getTmpfile()
	{
		return $this->tmpfile;
	}

	/**
	 * Delete the temporary file when the object is destroyed
	 */
	public function __destruct()
	{
		if (file_exists($this->tmpfile)) {
			unlink($this->tmpfile);
		}

		if (file_exists($this->tmpdir)) {
			$this->_rrmdir($this->tmpdir);
			rmdir($this->tmpdir);
		}
	}

	/**
	 * Empty the temporary working directory recursively
	 *
	 * @param  string  $dir    The temporary working directory
	 * @return void
	 */
	private function _rrmdir($dir)
	{
		if ($handle = opendir($dir)) {
			while (($file = readdir($handle)) !== false) {
				if ($file != '.' && $file != '..') {
					if (is_dir($dir . '/' . $file)) {
						$this->_rrmdir($dir . '/' . $file);
						rmdir($dir . '/' . $file);
					} else {
						unlink($dir . '/' . $file);
					}
				}
			}
			closedir($handle);
		}
	}

	/**
	 * return the value present on odt in [valuename][/valuename]
	 *
	 * @param  string $valuename   Balise in the template
	 * @return string              The value inside the balise
	 */
	public function getvalue($valuename)
	{
		$searchreg="/\\[".$valuename."\\](.*)\\[\\/".$valuename."\\]/";
		preg_match($searchreg, $this->contentXml, $matches);
		$this->contentXml = preg_replace($searchreg, "", $this->contentXml);
		return  $matches[1];
	}
}
