<?php

/* Copyright (C) 2015	Marcos García   <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * Templating class for odt file
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @copyright  GPL License 2010 - Laurent Destailleur - eldy@users.sourceforge.net
 * @copyright  GPL License 2010 -  Vikas Mahajan - http://vikasmahajan.wordpress.com
 * @copyright  GPL License 2012 - Stephen Larroque - lrq3000@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.4.6 (last update 2013-04-07)
 */

require_once ODTPHP_PATH.'odf.php';
require_once 'DolibarrSegment.php';

class DolibarrOdf extends Odf {

	/**
	 * Declare a segment in order to use it in a loop
	 *
	 * @param string $segment
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
			throw new OdfException("'$segment' segment not found in the document");
		}
		$this->segments[$segment] = new DolibarrSegment($segment, $m[1], $this);
		return $this->segments[$segment];
	}

	/**
	 * Assing a template variable
	 *
	 * @param string $key name of the variable within the template
	 * @param string $value replacement value
	 * @param bool $encode if true, special XML characters are encoded
	 * @throws OdfException
	 * @return odf
	 */
	public function setVars($key, $value, $encode = true, $charset = 'ISO-8859')
	{
		$regex_search = '/'.$this->getConfig('DELIMITER_LEFT').'(<\/text:(.*)>)?'.preg_quote($key, '/').'(<text:(.*)>)?'.$this->getConfig('DELIMITER_RIGHT').'/';

		if (!preg_match($regex_search, $this->contentXml) && !preg_match($regex_search, $this->stylesXml)) {
			throw new OdfException("var $key not found in the document");
		}

		$value=$this->htmlToUTFAndPreOdf($value);

		$value = $encode ? htmlspecialchars($value) : $value;
		$value = ($charset == 'ISO-8859') ? utf8_encode($value) : $value;

		$value=$this->preOdfToOdf($value);

		$this->vars[$key] = $value;
		return $this;
	}

	/**
	 * Merge template variables
	 * Called automatically for a save
	 *
	 * @param  string	$type		'content' or 'styles'
	 * @return void
	 */
	protected function _parse($type='content')
	{
		// Conditionals substitution
		// Note: must be done before content substitution, else the variable will be replaced by its value and the conditional won't work anymore
		foreach($this->vars as $key => $value)
		{
			// If value is true (not 0 nor false nor null nor empty string)
			if($value)
			{
				// Remove the IF tag
				$this->contentXml = str_replace('[!-- IF '.$key.' --]', '', $this->contentXml);
				// Remove everything between the ELSE tag (if it exists) and the ENDIF tag
				$reg = '@(\[!--\sELSE\s' . $key . '\s--\](.*))?\[!--\sENDIF\s' . $key . '\s--\]@smU'; // U modifier = all quantifiers are non-greedy
				$this->contentXml = preg_replace($reg, '', $this->contentXml);
			}
			// Else the value is false, then two cases: no ELSE and we're done, or there is at least one place where there is an ELSE clause, then we replace it
			else
			{
				// Find all conditional blocks for this variable: from IF to ELSE and to ENDIF
				$reg = '@\[!--\sIF\s' . $key . '\s--\](.*)(\[!--\sELSE\s' . $key . '\s--\](.*))?\[!--\sENDIF\s' . $key . '\s--\]@smU'; // U modifier = all quantifiers are non-greedy
				preg_match_all($reg, $this->contentXml, $matches, PREG_SET_ORDER);
				foreach($matches as $match) { // For each match, if there is an ELSE clause, we replace the whole block by the value in the ELSE clause
					if (!empty($match[3])) $this->contentXml = str_replace($match[0], $match[3], $this->contentXml);
				}
				// Cleanup the other conditional blocks (all the others where there were no ELSE clause, we can just remove them altogether)
				$this->contentXml = preg_replace($reg, '', $this->contentXml);
			}
		}

		foreach ($this->vars as $key => $val) {
			$regex_search = '/'.$this->getConfig('DELIMITER_LEFT').'(<\/text:(.*)>)?'.preg_quote($key).'(<text:(.*)>)?'.$this->getConfig('DELIMITER_RIGHT').'/';
			$regex_substitution = '${1}'.$val.'${3}';

			if ($type == 'content') {
				// Content (variable) substitution
				$this->contentXml = preg_replace($regex_search, $regex_substitution, $this->contentXml);
			} elseif ($type == 'styles') {
				// Styles substitution
				$this->stylesXml = preg_replace($regex_search, $regex_substitution, $this->stylesXml);
			}

		}

	}

}