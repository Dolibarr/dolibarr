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

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/module_export_csv.php';

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
		if (!empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)) {
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
}
