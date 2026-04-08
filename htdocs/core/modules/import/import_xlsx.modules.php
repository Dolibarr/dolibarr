<?php
/* Copyright (C) 2006-2012 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012-2016 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *		\file       htdocs/core/modules/import/import_xlsx.modules.php
 *		\ingroup    import
 *		\brief      File to load import files with Excel format
 */

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Shared\Date;

require_once DOL_DOCUMENT_ROOT . '/core/modules/import/modules_import.class.php';


/**
 *	Class to import Excel files
 */
class ImportXlsx extends ModeleImports
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
	 * @var string
	 */
	public $label_lib; // Label of external lib used by driver

	/**
	 * @var string
	 */
	public $version_lib; // Version of external lib used by driver

	/**
	 * @var string
	 */
	public $separator;

	/**
	 * @var string
	 */
	public $file; // Path of file

	/**
	 * @var resource
	 */
	public $handle; // Handle fichier

	public $cacheconvert = array(); // Array to cache list of value found after a conversion

	public $cachefieldtable = array(); // Array to cache list of value found into fields@tables

	public $nbinsert = 0; // # of insert done during the import

	public $nbupdate = 0; // # of update done during the import

	/**
	 * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	public $workbook; // temporary import file

	/**
	 * @var int
	 */
	public $record; // current record

	/**
	 * @var array<int,mixed>
	 */
	public $headers;


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

		// this is used as an extension from the example file code, so we have to put xlsx here !!!
		$this->id = 'xlsx'; // Same value as xxx in file name export_xxx.modules.php
		$this->label = 'Excel 2007'; // Label of driver
		$this->desc = $langs->trans("Excel2007FormatDesc");
		$this->extension = 'xlsx'; // Extension for generated file by this driver
		$this->picto = 'mime/xls'; // Picto (This is not used by the example file code as Mime type, too bad ...)
		$this->version = '1.0'; // Driver version
		$this->phpmin = array(7, 1); // Minimum version of PHP required by module

		require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
		if (versioncompare($this->phpmin, versionphparray()) > 0) {
			dol_syslog("Module need a higher PHP version");
			$this->error = "Module need a higher PHP version";
			return;
		}

		// If driver use an external library, put its name here
		require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
		require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';
		require_once PHPEXCELNEW_PATH.'Spreadsheet.php';
		$this->workbook = new Spreadsheet();

		// If driver use an external library, put its name here
		if (!class_exists('ZipArchive')) {	// For Excel2007
			$langs->load("errors");
			$this->error = $langs->trans('ErrorPHPNeedModule', 'zip');
			return;
		}
		$this->label_lib = 'PhpSpreadSheet';
		$this->version_lib = '1.8.0';

		$arrayofstreams = stream_get_wrappers();
		if (!in_array('zip', $arrayofstreams)) {
			$langs->load("errors");
			$this->error = $langs->trans('ErrorStreamMustBeEnabled', 'zip');
			return;
		}

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
		global $user, $conf, $langs, $file;
		// create a temporary object, the final output will be generated in footer
		$this->workbook->getProperties()->setCreator($user->getFullName($outputlangs) . ' - Dolibarr ' . DOL_VERSION);
		$this->workbook->getProperties()->setTitle($outputlangs->trans("Import") . ' - ' . $file);
		$this->workbook->getProperties()->setSubject($outputlangs->trans("Import") . ' - ' . $file);
		$this->workbook->getProperties()->setDescription($outputlangs->trans("Import") . ' - ' . $file);

		$this->workbook->setActiveSheetIndex(0);
		$this->workbook->getActiveSheet()->setTitle($outputlangs->trans("Sheet"));
		$this->workbook->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);

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
		global $conf;
		$this->workbook->getActiveSheet()->getStyle('1')->getFont()->setBold(true);
		$this->workbook->getActiveSheet()->getStyle('1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

		$col = 1;
		foreach ($headerlinefields as $field) {
			$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, 1, $outputlangs->transnoentities($field));
			// set autowidth
			//$this->workbook->getActiveSheet()->getColumnDimension($this->column2Letter($col + 1))->setAutoSize(true);
			$col++;
		}

		return ''; // final output will be generated in footer
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output record of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 * 	@param	mixed[]		$contentlinevalues	Array of lines
	 * 	@return	string							Empty string
	 */
	public function write_record_example($outputlangs, $contentlinevalues)
	{
		// phpcs:enable
		$col = 1;
		$row = 2;
		foreach ($contentlinevalues as $cell) {
			$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($col, $row, $cell);
			$col++;
		}

		return ''; // final output will be generated in footer
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output footer of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string							String output
	 */
	public function write_footer_example($outputlangs)
	{
		// phpcs:enable
		// return the file content as a string
		$tempfile = tempnam(sys_get_temp_dir(), 'dol');
		$objWriter = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($this->workbook);
		$objWriter->save($tempfile);
		$this->workbook->disconnectWorksheets();
		unset($this->workbook);

		$content = file_get_contents($tempfile);
		unlink($tempfile);
		return $content;
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
		$ret = 1;

		dol_syslog(get_class($this) . "::open_file file=" . $file);

		$reader = new Xlsx();
		$this->workbook = $reader->load($file);
		$this->record = 1;
		$this->file = $file;

		return $ret;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Return nb of records. File must be closed.
	 *
	 *	@param	string	$file		Path of filename
	 * 	@return	int					Return integer <0 if KO, >=0 if OK
	 */
	public function import_get_nb_of_lines($file)
	{
		// phpcs:enable
		$reader = new Xlsx();
		$this->workbook = $reader->load($file);

		$rowcount = $this->workbook->getActiveSheet()->getHighestDataRow();

		$this->workbook->disconnectWorksheets();
		unset($this->workbook);

		return $rowcount;
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
		// This is not called by the import code !!!
		$this->headers = array();
		$xlsx = new Xlsx();
		$info = $xlsx->listWorksheetinfo($this->file);
		$countcolumns = $info[0]['totalColumns'];
		for ($col = 1; $col <= $countcolumns; $col++) {
			$this->headers[$col] = $this->workbook->getActiveSheet()->getCellByColumnAndRow($col, 1)->getValue();
		}
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
		$rowcount = $this->workbook->getActiveSheet()->getHighestDataRow();
		if ($this->record > $rowcount) {
			return false;
		}
		$array = array();

		$xlsx = new Xlsx();
		$info = $xlsx->listWorksheetinfo($this->file);
		$countcolumns = $info[0]['totalColumns'];

		for ($col = 1; $col <= $countcolumns; $col++) {
			$tmpcell = $this->workbook->getActiveSheet()->getCellByColumnAndRow($col, $this->record);

			$val = $tmpcell->getValue();

			if (Date::isDateTime($tmpcell)) {
				// For date field, we use the standard date format string.
				$dateValue = Date::excelToDateTimeObject($val);
				$val = $dateValue->format('Y-m-d H:i:s');
			}

			$array[$col]['val'] = $val;
			$array[$col]['type'] = (dol_strlen($val) ? 1 : -1); // If empty we consider it null
		}
		$this->record++;

		unset($xlsx);

		return $array;
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
		$this->workbook->disconnectWorksheets();
		unset($this->workbook);
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
		return $this->commonImportInsert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys, 1);
	}
}
