<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
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
 *		\file       htdocs/core/modules/export/export_csv.modules.php
 *		\ingroup    export
 *		\brief      File of class to build export files with format TSV
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';


/**
 *	Class to build export files with format TSV
 */
class ExportTsv extends ModeleExports
{
	/**
	 * @var string ID
	 */
	public $id;

	/**
	 * @var string label
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

	public $separator = "\t";

	public $handle; // Handle fichier


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;
		$this->db = $db;

		$this->id = 'tsv'; // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'TSV'; // Label of driver
		$this->desc = $langs->trans('TsvFormatDesc');
		$this->extension = 'tsv'; // Extension for generated file by this driver
		$this->picto = 'mime/other'; // Picto
		$this->version = '1.15'; // Driver version

		// If driver use an external library, put its name here
		$this->label_lib = 'Dolibarr';
		$this->version_lib = DOL_VERSION;
	}

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
	 * getLibLabel
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
	 *   Open output file
	 *
	 *  @param      string		$file			Path of filename to generate
	 *  @param      Translate	$outputlangs	Output language object
	 *  @return     int							<0 if KO, >=0 if OK
	 */
	public function open_file($file, $outputlangs)
	{
		// phpcs:enable
		global $langs;

		dol_syslog("ExportTsv::open_file file=".$file);

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
	 * 	@param		Translate	$outputlangs		Output language object
	 * 	@return		int								<0 if KO, >0 if OK
	 */
	public function write_header($outputlangs)
	{
		// phpcs:enable
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output title line into file
	 *
	 *  @param      array		$array_export_fields_label   	Array with list of label of fields
	 *  @param      array		$array_selected_sorted       	Array with list of field to export
	 *  @param      Translate	$outputlangs    				Object lang to translate values
	 *  @param		array		$array_types					Array with types of fields
	 * 	@return		int											<0 if KO, >0 if OK
	 */
	public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
	{
		// phpcs:enable
		foreach ($array_selected_sorted as $code => $value) {
			$newvalue = $outputlangs->transnoentities($array_export_fields_label[$code]); // newvalue is now $outputlangs->charset_output encoded
			$newvalue = $this->tsv_clean($newvalue, $outputlangs->charset_output);

			fwrite($this->handle, $newvalue.$this->separator);
		}
		fwrite($this->handle, "\n");
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output record line into file
	 *
	 *  @param      array		$array_selected_sorted      Array with list of field to export
	 *  @param      resource	$objp                       A record from a fetch with all fields from select
	 *  @param      Translate	$outputlangs                Object lang to translate values
	 *  @param		array		$array_types				Array with types of fields
	 * 	@return		int										<0 if KO, >0 if OK
	 */
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
		// phpcs:enable
		global $conf;

		$this->col = 0;
		foreach ($array_selected_sorted as $code => $value) {
			if (strpos($code, ' as ') == 0) {
				$alias = str_replace(array('.', '-', '(', ')'), '_', $code);
			} else {
				$alias = substr($code, strpos($code, ' as ') + 4);
			}
			if (empty($alias)) {
				dol_print_error('', 'Bad value for field with code='.$code.'. Try to redefine export.');
			}

			$newvalue = $outputlangs->convToOutputCharset($objp->$alias); // objp->$alias must be utf8 encoded as any var in memory // newvalue is now $outputlangs->charset_output encoded
			$typefield = isset($array_types[$code]) ? $array_types[$code] : '';

			// Translation newvalue
			if (preg_match('/^\((.*)\)$/i', $newvalue, $reg)) {
				$newvalue = $outputlangs->transnoentities($reg[1]);
			}

			$newvalue = $this->tsv_clean($newvalue, $outputlangs->charset_output);

			if (preg_match('/^Select:/i', $typefield) && $typefield = substr($typefield, 7)) {
				$array = json_decode($typefield, true);
				$array = $array['options'];
				$newvalue = $array[$newvalue];
			}

			fwrite($this->handle, $newvalue.$this->separator);
			$this->col++;
		}
		fwrite($this->handle, "\n");
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output footer into file
	 *
	 * 	@param		Translate	$outputlangs		Output language object
	 * 	@return		int								<0 if KO, >0 if OK
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
	 * 	@return		int							<0 if KO, >0 if OK
	 */
	public function close_file()
	{
		// phpcs:enable
		fclose($this->handle);
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Clean a cell to respect rules of TSV file cells
	 *
	 * @param 	string	$newvalue	String to clean
	 * @param	string	$charset	Input AND Output character set
	 * @return 	string				Value cleaned
	 */
	public function tsv_clean($newvalue, $charset)
	{
		// phpcs:enable
		// Rule Dolibarr: No HTML
		$newvalue = dol_string_nohtmltag($newvalue, 1, $charset);

		// Rule 1 TSV: No CR, LF in cells
		$newvalue = str_replace("\r", '', $newvalue);
		$newvalue = str_replace("\n", '\n', $newvalue);

		// Rule 2 TSV: If value contains tab, we must replace by space
		if (preg_match('/'.$this->separator.'/', $newvalue)) {
			$newvalue = str_replace("\t", " ", $newvalue);
		}

		return $newvalue;
	}
}
