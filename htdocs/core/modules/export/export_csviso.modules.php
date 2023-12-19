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
 *		\file       htdocs/core/modules/export/export_csv.modules.php
 *		\ingroup    export
 *		\brief      File of class to build exports with CSV format
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/exportcsv.class.php';

// avoid timeout for big export
set_time_limit(0);

/**
 *	Class to build export files with format CSV iso
 */
class ExportCsvIso extends ExportCsv
{
	/**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;
		$this->db = $db;

		$this->separator = ',';
		if (getDolGlobalString('EXPORT_CSV_SEPARATOR_TO_USE')) {
			$this->separator = $conf->global->EXPORT_CSV_SEPARATOR_TO_USE;
		}

		$conf->global->EXPORT_CSV_FORCE_CHARSET = 'ISO-8859-1';

		$this->escape = '"';
		$this->enclosure = '"';
		$this->id = 'csviso'; // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'CSV ISO-8859-1'; // Label of driver
		$this->desc = $langs->trans("CSVFormatDesc", $this->separator, $this->enclosure, $this->escape);
		$this->extension = 'csv'; // Extension for generated file by this driver
		$this->picto = 'mime/other'; // Picto
		$this->version = '1.32'; // Driver version

		// If driver use an external library, put its name here
		$this->label_lib = 'Dolibarr';
		$this->version_lib = DOL_VERSION;
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
		global $conf;
		$conf->global->EXPORT_CSV_FORCE_CHARSET = 'ISO-8859-1';

		return parent::write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Output record line into file
	 *
	 *  @param     	array		$array_selected_sorted      Array with list of field to export
	 *  @param     	resource	$objp                       A record from a fetch with all fields from select
	 *  @param     	Translate	$outputlangs    			Object lang to translate values
	 *  @param		array		$array_types				Array with types of fields
	 * 	@return		int										Return integer <0 if KO, >0 if OK
	 */
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
		global $conf;
		$conf->global->EXPORT_CSV_FORCE_CHARSET = 'ISO-8859-1';

		return parent::write_record($array_selected_sorted, $objp, $outputlangs, $array_types);
	}
}
