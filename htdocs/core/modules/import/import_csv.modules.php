<?php
/* Copyright (C) 2006-2012	Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Christophe Battarel         <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016  Juanjo Menent               <jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024       MDW                         <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026       Alexandre Spangaro          <alexandre@inovea-conseil.com>
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
 * or see https://www.gnu.org/
 */

/**
 *		\file       htdocs/core/modules/import/import_csv.modules.php
 *		\ingroup    import
 *		\brief      File to load import files with CSV format
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.class.php';


/**
 *	Class to import CSV files
 */
class ImportCsv extends ModeleImports
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Code of driver
	 */
	public $id;

	/**
	 * Dolibarr version of driver
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * @var string Label of external lib used by driver
	 */
	public $label_lib;

	/**
	 * @var string Version of external lib used by driver
	 */
	public $version_lib;

	/**
	 * @var string|string[]
	 */
	public $separator;

	/**
	 * @var string
	 */
	public $file; // Path of file

	/**
	 * @var resource
	 */
	public $handle; // File handle

	public $cacheconvert = array(); // Array to cache list of value found after a conversion

	public $cachefieldtable = array(); // Array to cache list of value found into fields@tables

	public $nbinsert = 0; // # of insert done during the import

	public $nbupdate = 0; // # of update done during the import

	public $charset = '';

	/**
	 * @var int
	 */
	public $col;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB		$db				Database handler
	 *	@param	string		$datatoimport	String code describing import set (ex: 'societe_1')
	 */
	public function __construct($db, $datatoimport)
	{
		global $langs;

		parent::__construct();
		$this->db = $db;

		$this->separator = (GETPOST('separator') ? GETPOST('separator') : getDolGlobalString('IMPORT_CSV_SEPARATOR_TO_USE', ','));
		$this->enclosure = '"';
		$this->escape = '"';

		$this->id = 'csv'; // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'Csv'; // Label of driver
		$this->desc = $langs->trans("CSVFormatDesc", $this->separator, $this->enclosure, $this->escape);
		$this->extension = 'csv'; // Extension for generated file by this driver
		$this->picto = 'mime/other'; // Picto
		$this->version = '1.34'; // Driver version
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module

		require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
		if (versioncompare($this->phpmin, versionphparray()) > 0) {
			dol_syslog("Module need a higher PHP version");
			$this->error = "Module need a higher PHP version";
			return;
		}

		// If driver use an external library, put its name here
		$this->label_lib = 'Dolibarr';
		$this->version_lib = DOL_VERSION;

		$this->datatoimport = $datatoimport;
		if (preg_match('/^societe_/', $datatoimport)) {
			$this->thirdpartyobject = new Societe($this->db);
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output header of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string							Empty string
	 */
	public function write_header_example($outputlangs)
	{
		// phpcs:enable
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output title line of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @param	string[]	$headerlinefields	Array of fields name
	 * 	@return	string							String output
	 */
	public function write_title_example($outputlangs, $headerlinefields)
	{
		// phpcs:enable
		$s = implode($this->separator, array_map('cleansep', $headerlinefields));
		return $s."\n";
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output record of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 * 	@param	string[]	$contentlinevalues	Array of lines
	 * 	@return	string							String output
	 */
	public function write_record_example($outputlangs, $contentlinevalues)
	{
		// phpcs:enable
		$s = implode($this->separator, array_map('cleansep', $contentlinevalues));
		return $s."\n";
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output footer of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string							Empty string
	 */
	public function write_footer_example($outputlangs)
	{
		// phpcs:enable
		return '';
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Open input file
	 *
	 *	@param	string	$file		Path of filename
	 *	@return	int					Return integer <0 if KO, >=0 if OK
	 */
	public function import_open_file($file)
	{
		// phpcs:enable
		global $langs;
		$ret = 1;

		dol_syslog(get_class($this)."::open_file file=".$file);

		ini_set('auto_detect_line_endings', 1); // For MAC compatibility

		$handle = fopen(dol_osencode($file), "r");
		if (!$handle) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFailToOpenFile", $file);
			$ret = -1;
		} else {
			$this->handle = $handle;
			$this->file = $file;
		}

		return $ret;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Return nb of records. File must be closed.
	 *
	 *	@param	string	$file		Path of filename
	 * 	@return		int		Return integer <0 if KO, >=0 if OK
	 */
	public function import_get_nb_of_lines($file)
	{
		// phpcs:enable
		return dol_count_nb_of_line($file);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Input header line from file
	 *
	 * 	@return		int		Return integer <0 if KO, >=0 if OK
	 */
	public function import_read_header()
	{
		// phpcs:enable
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Return array of next record in input file.
	 *
	 * 	@return		array|boolean		Array of field values. Data are UTF8 encoded. [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=not empty string)
	 */
	public function import_read_record()
	{
		// phpcs:enable
		global $conf;

		$arrayres = fgetcsv($this->handle, 100000, $this->separator, $this->enclosure, $this->escape);

		// End of file
		if ($arrayres === false) {
			return false;
		}

		//var_dump($this->handle);
		//var_dump($arrayres);exit;
		$newarrayres = array();
		$key = 1; // Default value to ensure $key is declared

		if ($arrayres && is_array($arrayres)) {
			foreach ($arrayres as $key => $val) {
				if (getDolGlobalString('IMPORT_CSV_FORCE_CHARSET')) {	// Forced charset
					if (strtolower($conf->global->IMPORT_CSV_FORCE_CHARSET) == 'utf8') {
						$newarrayres[$key]['val'] = $val;
						$newarrayres[$key]['type'] = (dol_strlen($val) ? 1 : -1); // If empty we consider it's null
					} else {
						$newarrayres[$key]['val'] = mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1');
						$newarrayres[$key]['type'] = (dol_strlen($val) ? 1 : -1); // If empty we consider it's null
					}
				} else { // Autodetect format (UTF8 or ISO)
					if (utf8_check($val)) {
						$newarrayres[$key]['val'] = $val;
						$newarrayres[$key]['type'] = (dol_strlen($val) ? 1 : -1); // If empty we consider it's null
					} else {
						$newarrayres[$key]['val'] = mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1');
						$newarrayres[$key]['type'] = (dol_strlen($val) ? 1 : -1); // If empty we consider it's null
					}
				}
			}

			$this->col = count($newarrayres);
		}

		return $newarrayres;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Close file handle
	 *
	 *  @return	integer
	 */
	public function import_close_file()
	{
		// phpcs:enable
		fclose($this->handle);
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Insert a record into database
	 *
	 * @param	array<int,array{val:mixed,type:int}>|bool	$arrayrecord			Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param	array<int|string,string>	$array_match_file_to_database	Array of target fields where to insert data: [fieldpos] => 's.fieldname', [fieldpos+1]...
	 * @param 	Object		$objimport						Object import (contains objimport->array_import_tables, objimport->array_import_fields, objimport->array_import_convertvalue, ...)
	 * @param	int			$maxfields						Max number of fields to use
	 * @param	string		$importid						Import key
	 * @param	string[]	$updatekeys						Array of keys to use to try to do an update first before insert. This field are defined into the module descriptor.
	 * @return	int										Return integer <0 if KO, >0 if OK
	 */
	public function import_insert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys)
	{
		// phpcs:enable
		return $this->commonImportInsert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys, 0);
	}
}

/**
 *	Clean a string from separator
 *
 *	@param	string	$value	Remove standard separators
 *	@return	string			String without separators
 */
function cleansep($value)
{
	return str_replace(array(',', ';'), '/', $value);
}
