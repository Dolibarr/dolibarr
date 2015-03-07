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
 * Class for handling templating segments with odt files
 * You need PHP 5.2 at least
 * You need Zip Extension or PclZip library
 * Encoding : ISO-8859-1
 *
 * @copyright  GPL License 2008 - Julien Pauli - Cyril PIERRE de GEYER - Anaska (http://www.anaska.com)
 * @copyright  GPL License 2012 - Stephen Larroque - lrq3000@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version 1.4.5 (last update 2013-04-07)
 */

class DolibarrSegment extends Segment
{
	/**
	 * @var DolibarrOdf
	 */
	protected $odf;

	/**
	 * @var ZipInterface
	 */
	protected $file;

	/**
	 * Replace variables of the template in the XML code
	 * All the children are also called
	 *
	 * @return string
	 */
	public function merge()
	{
		$this->xmlParsed = $this->xml;

		foreach ($this->vars as $key => $val) {
			$regex_search = '/'.$this->odf->getConfig('DELIMITER_LEFT').'(<\/text:(.*)>)?'.preg_quote($key).'(<text:(.*)>)?'.$this->odf->getConfig('DELIMITER_RIGHT').'/';
			$regex_substitution = '${1}'.$val.'${3}';

			$this->xmlParsed = preg_replace($regex_search, $regex_substitution, $this->xmlParsed);
		}

		if ($this->hasChildren()) {
			foreach ($this->children as $child) {
				$this->xmlParsed = str_replace($child->xml, ($child->xmlParsed=="")?$child->merge():$child->xmlParsed, $this->xmlParsed);
				$child->xmlParsed = '';
			}
		}
		$reg = "/\[!--\sBEGIN\s$this->name\s--\](.*)\[!--\sEND\s$this->name\s--\]/sm";
		$this->xmlParsed = preg_replace($reg, '$1', $this->xmlParsed);
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
	 * Assign a template variable to replace
	 *
	 * @param string $key
	 * @param string $value
	 * @throws OdfException
	 * @return Segment
	 */
	public function setVars($key, $value, $encode = true, $charset = 'ISO-8859')
	{
		$regex_search = '/'.$this->odf->getConfig('DELIMITER_LEFT').'(<\/text:(.*)>)?'.preg_quote($key, '/').'(<text:(.*)>)?'.$this->odf->getConfig('DELIMITER_RIGHT').'/';

		if (!preg_match($regex_search, $this->xml)) {
			throw new OdfException("var $key not found in the document");
		}

		$value=$this->odf->htmlToUTFAndPreOdf($value);

		$value = $encode ? htmlspecialchars($value) : $value;
		$value = ($charset == 'ISO-8859') ? utf8_encode($value) : $value;

		$value=$this->odf->preOdfToOdf($value);

		$this->vars[$key] = $value;
		return $this;
	}

}