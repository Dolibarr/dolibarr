<?php
/* Copyright (C) 2006-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/core/modules/export/exportcsv.class.php
 *		\ingroup    export
 *		\brief      File of class to build exports with CSV format
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';

// avoid timeout for big export
set_time_limit(0);

/**
 *	Class to build export files with format CSV
 */
class ExportCsv extends ModeleExports
{
	/**
	 * @var string ID ex: csv, tsv, excel...
	 */
	public $id;

	/**
	 * @var string export files label
	 */
	public $label;

	public $extension;

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'dolibarr';

	public $label_lib;

	public $version_lib;

	public $separator;

	public $handle; // Handle fichier

	/**
	 * getDriverId
	 *
	 * @return string
	 */
	public function getDriverId()
	{
		return $this->id;
	}

	/**
	 * getDriverLabel
	 *
	 * @return 	string			Return driver label
	 */
	public function getDriverLabel()
	{
		return $this->label;
	}

	/**
	 * getDriverDesc
	 *
	 * @return string
	 */
	public function getDriverDesc()
	{
		return $this->desc;
	}

	/**
	 * getDriverExtension
	 *
	 * @return string
	 */
	public function getDriverExtension()
	{
		return $this->extension;
	}

	/**
	 * getDriverVersion
	 *
	 * @return string
	 */
	public function getDriverVersion()
	{
		return $this->version;
	}

	/**
	 * getLabelLabel
	 *
	 * @return string
	 */
	public function getLibLabel()
	{
		return $this->label_lib;
	}

	/**
	 * getLibVersion
	 *
	 * @return string
	 */
	public function getLibVersion()
	{
		return $this->version_lib;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Open output file
	 *
	 *	@param		string		$file			Path of filename to generate
	 * 	@param		Translate	$outputlangs	Output language object
	 *	@return		int							Return integer <0 if KO, >=0 if OK
	 */
	public function open_file($file, $outputlangs)
	{
		// phpcs:enable
		global $langs;

		dol_syslog("ExportCsv::open_file file=".$file);

		$ret = 1;

		$outputlangs->load("exports");
		$this->handle = fopen($file, "wt");
		if (!$this->handle) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFailToCreateFile", $file);
			$ret = -1;
		}

		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output header into file
	 *
	 * 	@param		Translate	$outputlangs	Output language object
	 * 	@return		int							Return integer <0 if KO, >0 if OK
	 */
	public function write_header($outputlangs)
	{
		// phpcs:enable
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output title line into file
	 *
	 *  @param      array		$array_export_fields_label   	Array with list of label of fields
	 *  @param      array		$array_selected_sorted       	Array with list of field to export
	 *  @param      Translate	$outputlangs    				Object lang to translate values
	 *  @param		array		$array_types					Array with types of fields
	 * 	@return		int											Return integer <0 if KO, >0 if OK
	 */
	public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
	{
		// phpcs:enable
		$outputlangs->charset_output = getDolGlobalString('EXPORT_CSV_FORCE_CHARSET');

		$selectlabel = array();
		foreach ($array_selected_sorted as $code => $value) {
			$newvalue = $outputlangs->transnoentities($array_export_fields_label[$code]); // newvalue is now $outputlangs->charset_output encoded
			$newvalue = $this->csvClean($newvalue, $outputlangs->charset_output);

			fwrite($this->handle, $newvalue.$this->separator);
			$typefield = isset($array_types[$code]) ? $array_types[$code] : '';

			if (preg_match('/^Select:/i', $typefield) && $typefield = substr($typefield, 7)) {
				$selectlabel[$code."_label"] = $newvalue."_label";
			}
		}

		foreach ($selectlabel as $key => $value) {
			fwrite($this->handle, $value.$this->separator);
		}
		fwrite($this->handle, "\n");
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Output record line into file
	 *
	 *  @param     	array		$array_selected_sorted      Array with list of field to export
	 *  @param     	Resource	$objp                       A record from a fetch with all fields from select
	 *  @param     	Translate	$outputlangs    			Object lang to translate values
	 *  @param		array		$array_types				Array with types of fields
	 * 	@return		int										Return integer <0 if KO, >0 if OK
	 */
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
		// phpcs:enable
		global $conf;

		$outputlangs->charset_output = getDolGlobalString('EXPORT_CSV_FORCE_CHARSET');

		$this->col = 0;

		$reg = array();
		$selectlabelvalues = array();
		foreach ($array_selected_sorted as $code => $value) {
			if (strpos($code, ' as ') == 0) {
				$alias = str_replace(array('.', '-', '(', ')'), '_', $code);
			} else {
				$alias = substr($code, strpos($code, ' as ') + 4);
			}
			if (empty($alias)) {
				dol_print_error(null, 'Bad value for field with key='.$code.'. Try to redefine export.');
			}

			$newvalue = $outputlangs->convToOutputCharset($objp->$alias); // objp->$alias must be utf8 encoded as any var in memory	// newvalue is now $outputlangs->charset_output encoded
			$typefield = isset($array_types[$code]) ? $array_types[$code] : '';

			// Translation newvalue
			if (preg_match('/^\((.*)\)$/i', $newvalue, $reg)) {
				$newvalue = $outputlangs->transnoentities($reg[1]);
			}

			// Clean data and add encloser if required (depending on value of USE_STRICT_CSV_RULES)
			$newvalue = $this->csvClean($newvalue, $outputlangs->charset_output);

			if (preg_match('/^Select:/i', $typefield) && $typefield = substr($typefield, 7)) {
				$array = jsonOrUnserialize($typefield);
				if (is_array($array) && !empty($newvalue)) {
					$array = $array['options'];
					$selectlabelvalues[$code."_label"] = $array[$newvalue];
				} else {
					$selectlabelvalues[$code."_label"] = "";
				}
			}

			fwrite($this->handle, $newvalue.$this->separator);
			$this->col++;
		}
		foreach ($selectlabelvalues as $key => $value) {
			fwrite($this->handle, $value.$this->separator);
			$this->col++;
		}

		fwrite($this->handle, "\n");
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output footer into file
	 *
	 * 	@param		Translate	$outputlangs	Output language object
	 * 	@return		int							Return integer <0 if KO, >0 if OK
	 */
	public function write_footer($outputlangs)
	{
		// phpcs:enable
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Close file handle
	 *
	 * 	@return		int							Return integer <0 if KO, >0 if OK
	 */
	public function close_file()
	{
		// phpcs:enable
		fclose($this->handle);
		return 0;
	}


	/**
	 * Clean a cell to respect rules of CSV file cells
	 * Note: It uses $this->separator
	 * Note: We keep this function public to be able to test
	 *
	 * @param 	string	$newvalue	String to clean
	 * @param	string	$charset	Input AND Output character set
	 * @return 	string				Value cleaned
	 */
	public function csvClean($newvalue, $charset)
	{
		global $conf;
		$addquote = 0;

		// Rule Dolibarr: No HTML
		//print $charset.' '.$newvalue."\n";
		//$newvalue=dol_string_nohtmltag($newvalue,0,$charset);
		$newvalue = dol_htmlcleanlastbr($newvalue);
		//print $charset.' '.$newvalue."\n";

		// Rule 1 CSV: No CR, LF in cells (except if USE_STRICT_CSV_RULES is 1, we can keep record as it is but we must add quotes)
		$oldvalue = $newvalue;
		$newvalue = str_replace("\r", '', $newvalue);
		$newvalue = str_replace("\n", '\n', $newvalue);
		if (getDolGlobalString('USE_STRICT_CSV_RULES') && $oldvalue != $newvalue) {
			// If we must use enclusure on text with CR/LF)
			if (getDolGlobalInt('USE_STRICT_CSV_RULES') == 1) {
				// If we use strict CSV rules (original value must remain but we add quote)
				$newvalue = $oldvalue;
			}
			$addquote = 1;
		}

		// Rule 2 CSV: If value contains ", we must escape with ", and add "
		if (preg_match('/"/', $newvalue)) {
			$addquote = 1;
			$newvalue = str_replace('"', '""', $newvalue);
		}

		// Rule 3 CSV: If value contains separator, we must add "
		if (preg_match('/'.$this->separator.'/', $newvalue)) {
			$addquote = 1;
		}

		return ($addquote ? '"' : '').$newvalue.($addquote ? '"' : '');
	}
}
