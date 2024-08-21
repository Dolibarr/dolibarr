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

require_once DOL_DOCUMENT_ROOT . '/core/modules/import/modules_import.php';


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

	public $label_lib; // Label of external lib used by driver

	public $version_lib; // Version of external lib used by driver

	public $separator;

	public $file; // Path of file

	public $handle; // Handle fichier

	public $cacheconvert = array(); // Array to cache list of value found after a conversion

	public $cachefieldtable = array(); // Array to cache list of value found into fields@tables

	public $nbinsert = 0; // # of insert done during the import

	public $nbupdate = 0; // # of update done during the import

	public $workbook; // temporary import file

	public $record; // current record

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
	 *  @param	array		$headerlinefields	Array of fields name
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
	 * 	@param	array		$contentlinevalues	Array of lines
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
		global $langs;
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


	// What is this doing here ? it is common to all imports, is should be in the parent class
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Insert a record into database
	 *
	 * @param	array	$arrayrecord					Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 * @param	array	$array_match_file_to_database	Array of target fields where to insert data: [fieldpos] => 's.fieldname', [fieldpos+1]...
	 * @param 	Object	$objimport						Object import (contains objimport->array_import_tables, objimport->array_import_fields, objimport->array_import_convertvalue, ...)
	 * @param	int		$maxfields						Max number of fields to use
	 * @param	string	$importid						Import key
	 * @param	array	$updatekeys						Array of keys to use to try to do an update first before insert. This field are defined into the module descriptor.
	 * @return	int										Return integer <0 if KO, >0 if OK
	 */
	public function import_insert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys)
	{
		// phpcs:enable
		global $langs, $conf, $user;
		global $thirdparty_static; // Specific to thirdparty import
		global $tablewithentity_cache; // Cache to avoid to call  desc at each rows on tables

		$error = 0;
		$warning = 0;
		$this->errors = array();
		$this->warnings = array();

		//dol_syslog("import_csv.modules maxfields=".$maxfields." importid=".$importid);

		//var_dump($array_match_file_to_database);
		//var_dump($arrayrecord); exit;

		$array_match_database_to_file = array_flip($array_match_file_to_database);
		$sort_array_match_file_to_database = $array_match_file_to_database;
		ksort($sort_array_match_file_to_database);

		//var_dump($sort_array_match_file_to_database);

		if (count($arrayrecord) == 0 || (count($arrayrecord) == 1 && empty($arrayrecord[1]['val']))) {
			//print 'W';
			$this->warnings[$warning]['lib'] = $langs->trans('EmptyLine');
			$this->warnings[$warning]['type'] = 'EMPTY';
			$warning++;
		} else {
			$last_insert_id_array = array(); // store the last inserted auto_increment id for each table, so that dependent tables can be inserted with the appropriate id (eg: extrafields fk_object will be set with the last inserted object's id)
			$updatedone = false;
			$insertdone = false;
			// For each table to insert, me make a separate insert
			foreach ($objimport->array_import_tables[0] as $alias => $tablename) {
				// Build sql request
				$sql = '';
				$listfields = array();
				$listvalues = array();
				$i = 0;
				$errorforthistable = 0;

				// Define $tablewithentity_cache[$tablename] if not already defined
				if (!isset($tablewithentity_cache[$tablename])) {	// keep this test with "isset"
					dol_syslog("Check if table " . $tablename . " has an entity field");
					$resql = $this->db->DDLDescTable($tablename, 'entity');
					if ($resql) {
						$obj = $this->db->fetch_object($resql);
						if ($obj) {
							$tablewithentity_cache[$tablename] = 1; // table contains entity field
						} else {
							$tablewithentity_cache[$tablename] = 0; // table does not contain entity field
						}
					} else {
						dol_print_error($this->db);
					}
				} else {
					//dol_syslog("Table ".$tablename." check for entity into cache is ".$tablewithentity_cache[$tablename]);
				}

				// Define an array to convert fields ('c.ref', ...) into column index (1, ...)
				$arrayfield = array();
				foreach ($sort_array_match_file_to_database as $key => $val) {
					$arrayfield[$val] = ($key);
				}

				// $arrayrecord start at key 1
				// $sort_array_match_file_to_database start at key 1

				// Loop on each fields in the match array: $key = 1..n, $val=alias of field (s.nom)
				foreach ($sort_array_match_file_to_database as $key => $val) {
					$fieldalias = preg_replace('/\..*$/i', '', $val);
					$fieldname = preg_replace('/^.*\./i', '', $val);

					if ($alias != $fieldalias) {
						continue; // Not a field of current table
					}

					if ($key <= $maxfields) {
						// Set $newval with value to insert and set $listvalues with sql request part for insert
						$newval = '';
						if ($arrayrecord[($key)]['type'] > 0) {
							$newval = $arrayrecord[($key)]['val']; // If type of field into input file is not empty string (so defined into input file), we get value
						}

						//var_dump($newval);var_dump($val);
						//var_dump($objimport->array_import_convertvalue[0][$val]);

						// Make some tests on $newval

						// Is it a required field ?
						if (preg_match('/\*/', $objimport->array_import_fields[0][$val]) && ((string) $newval == '')) {
							$this->errors[$error]['lib'] = $langs->trans('ErrorMissingMandatoryValue', num2Alpha($key - 1));
							$this->errors[$error]['type'] = 'NOTNULL';
							$errorforthistable++;
							$error++;
						} else {
							// Test format only if field is not a missing mandatory field (field may be a value or empty but not mandatory)
							// We convert field if required
							if (!empty($objimport->array_import_convertvalue[0][$val])) {
								//print 'Must convert '.$newval.' with rule '.join(',',$objimport->array_import_convertvalue[0][$val]).'. ';
								if ($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeid'
									|| $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromref'
									|| $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeorlabel'
								) {
									// New val can be an id or ref. If it start with id: it is forced to id, if it start with ref: it is forced to ref. It not, we try to guess.
									$isidorref = 'id';
									if (!is_numeric($newval) && $newval != '' && !preg_match('/^id:/i', $newval)) {
										$isidorref = 'ref';
									}
									$newval = preg_replace('/^(id|ref):/i', '', $newval); // Remove id: or ref: that was used to force if field is id or ref
									//print 'Newval is now "'.$newval.'" and is type '.$isidorref."<br>\n";

									if ($isidorref == 'ref') {    // If value into input import file is a ref, we apply the function defined into descriptor
										$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
										$class = $objimport->array_import_convertvalue[0][$val]['class'];
										$method = $objimport->array_import_convertvalue[0][$val]['method'];
										if ($this->cacheconvert[$file . '_' . $class . '_' . $method . '_'][$newval] != '') {
											$newval = $this->cacheconvert[$file . '_' . $class . '_' . $method . '_'][$newval];
										} else {
											$resultload = dol_include_once($file);
											if (empty($resultload)) {
												dol_print_error(null, 'Error trying to call file=' . $file . ', class=' . $class . ', method=' . $method);
												break;
											}
											$classinstance = new $class($this->db);
											if ($class == 'CGenericDic') {
												$classinstance->element = $objimport->array_import_convertvalue[0][$val]['element'];
												$classinstance->table_element = $objimport->array_import_convertvalue[0][$val]['table_element'];
											}

											// Try the fetch from code or ref
											$param_array = array('', $newval);
											if ($class == 'AccountingAccount') {
												//var_dump($arrayrecord[0]['val']);
												/*include_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancysystem.class.php';
												$tmpchartofaccount = new AccountancySystem($this->db);
												$tmpchartofaccount->fetch(getDolGlobalInt('CHARTOFACCOUNTS'));
												//var_dump($tmpchartofaccount->ref.' - '.$arrayrecord[0]['val']);
												if ((! (getDolGlobalInt('CHARTOFACCOUNTS') > 0)) || $tmpchartofaccount->ref != $arrayrecord[0]['val'])
												{
													$this->errors[$error]['lib']=$langs->trans('ErrorImportOfChartLimitedToCurrentChart', $tmpchartofaccount->ref);
													$this->errors[$error]['type']='RESTRICTONCURRENCTCHART';
													$errorforthistable++;
													$error++;
												}*/
												$param_array = array('', $newval, 0, $arrayrecord[0]['val']); // Param to fetch parent from account, in chart.
											}

											$result = call_user_func_array(array($classinstance, $method), $param_array);

											// If duplicate record found
											if (!($classinstance->id != '') && $result == -2) {
												$this->errors[$error]['lib'] = $langs->trans('ErrorMultipleRecordFoundFromRef', $newval);
												$this->errors[$error]['type'] = 'FOREIGNKEY';
												$errorforthistable++;
												$error++;
											}

											// If not found, try the fetch from label
											if (!($classinstance->id != '') && $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeorlabel') {
												$param_array = array('', '', $newval);
												call_user_func_array(array($classinstance, $method), $param_array);
											}
											$this->cacheconvert[$file . '_' . $class . '_' . $method . '_'][$newval] = $classinstance->id;

											//print 'We have made a '.$class.'->'.$method.' to get id from code '.$newval.'. ';
											if ($classinstance->id != '') {	// id may be 0, it is a found value
												$newval = $classinstance->id;
											} elseif (! $error) {
												if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) {
													// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
													$this->errors[$error]['lib'] = $langs->trans('ErrorFieldValueNotIn', $key, $newval, 'code', $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
												} elseif (!empty($objimport->array_import_convertvalue[0][$val]['element'])) {
													// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
													$this->errors[$error]['lib'] = $langs->trans('ErrorFieldRefNotIn', $key, $newval, $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['element']));
												} else {
													$this->errors[$error]['lib'] = 'ErrorBadDefinitionOfImportProfile';
												}
												$this->errors[$error]['type'] = 'FOREIGNKEY';
												$errorforthistable++;
												$error++;
											}
										}
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeandlabel') {
									$isidorref = 'id';
									if (!is_numeric($newval) && $newval != '' && !preg_match('/^id:/i', $newval)) {
										$isidorref = 'ref';
									}
									$newval = preg_replace('/^(id|ref):/i', '', $newval);

									if ($isidorref == 'ref') {
										$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
										$class = $objimport->array_import_convertvalue[0][$val]['class'];
										$method = $objimport->array_import_convertvalue[0][$val]['method'];
										$codefromfield = $objimport->array_import_convertvalue[0][$val]['codefromfield'];
										$code = $arrayrecord[$arrayfield[$codefromfield]]['val'];
										if ($this->cacheconvert[$file . '_' . $class . '_' . $method . '_' . $code][$newval] != '') {
											$newval = $this->cacheconvert[$file . '_' . $class . '_' . $method . '_' . $code][$newval];
										} else {
											$resultload = dol_include_once($file);
											if (empty($resultload)) {
												dol_print_error(null, 'Error trying to call file=' . $file . ', class=' . $class . ', method=' . $method . ', code=' . $code);
												break;
											}
											$classinstance = new $class($this->db);
											// Try the fetch from code and ref
											$param_array = array('', $newval, $code);
											call_user_func_array(array($classinstance, $method), $param_array);
											$this->cacheconvert[$file . '_' . $class . '_' . $method . '_' . $code][$newval] = $classinstance->id;
											if ($classinstance->id > 0) {    // we found record
												$newval = $classinstance->id;
											} else {
												if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) {
													// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
													$this->errors[$error]['lib'] = $langs->trans('ErrorFieldValueNotIn', $key, $newval, 'scale', $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
												} else {
													$this->errors[$error]['lib'] = 'ErrorFieldValueNotIn';
												}
												$this->errors[$error]['type'] = 'FOREIGNKEY';
												$errorforthistable++;
												$error++;
											}
										}
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'zeroifnull') {
									if (empty($newval)) {
										$newval = '0';
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeunits' || $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchscalefromcodeunits') {
									$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
									$class = $objimport->array_import_convertvalue[0][$val]['class'];
									$method = $objimport->array_import_convertvalue[0][$val]['method'];
									$units = $objimport->array_import_convertvalue[0][$val]['units'];
									if ($this->cacheconvert[$file . '_' . $class . '_' . $method . '_' . $units][$newval] != '') {
										$newval = $this->cacheconvert[$file . '_' . $class . '_' . $method . '_' . $units][$newval];
									} else {
										$resultload = dol_include_once($file);
										if (empty($resultload)) {
											dol_print_error(null, 'Error trying to call file=' . $file . ', class=' . $class . ', method=' . $method . ', units=' . $units);
											break;
										}
										$classinstance = new $class($this->db);
										// Try the fetch from code or ref
										call_user_func_array(array($classinstance, $method), array('', '', $newval, $units));
										$scaleorid = (($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeunits') ? $classinstance->id : $classinstance->scale);
										$this->cacheconvert[$file . '_' . $class . '_' . $method . '_' . $units][$newval] = $scaleorid;
										//print 'We have made a '.$class.'->'.$method." to get a value from key '".$newval."' and we got '".$scaleorid."'.";exit;
										if ($classinstance->id > 0) {	// we found record
											$newval = $scaleorid ? $scaleorid : 0;
										} else {
											if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) {
												// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
												$this->errors[$error]['lib'] = $langs->trans('ErrorFieldValueNotIn', $key, $newval, 'scale', $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
											} else {
												$this->errors[$error]['lib'] = 'ErrorFieldValueNotIn';
											}
											$this->errors[$error]['type'] = 'FOREIGNKEY';
											$errorforthistable++;
											$error++;
										}
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getcustomercodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codeclient(0, 0);
										$newval = $this->thirdpartyobject->code_client;
										//print 'code_client='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getsuppliercodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codefournisseur(0, 1);
										$newval = $this->thirdpartyobject->code_fournisseur;
										//print 'code_fournisseur='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getcustomeraccountancycodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codecompta('customer');
										$newval = $this->thirdpartyobject->code_compta_client;
										//print 'code_compta='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getsupplieraccountancycodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codecompta('supplier');
										$newval = $this->thirdpartyobject->code_compta_fournisseur;
										if (empty($newval)) {
											$arrayrecord[($key)]['type'] = -1; // If we get empty value, we will use "null"
										}
										//print 'code_compta_fournisseur='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getrefifauto') {
									if (strtolower($newval) == 'auto') {
										$defaultref = '';

										$classModForNumber = $objimport->array_import_convertvalue[0][$val]['class'];
										$pathModForNumber = $objimport->array_import_convertvalue[0][$val]['path'];

										if (!empty($classModForNumber) && !empty($pathModForNumber) && is_readable(DOL_DOCUMENT_ROOT.$pathModForNumber)) {
											require_once DOL_DOCUMENT_ROOT.$pathModForNumber;
											$modForNumber = new $classModForNumber();

											$tmpobject = null;
											// Set the object with the date property when we can
											if (!empty($objimport->array_import_convertvalue[0][$val]['classobject'])) {
												$pathForObject = $objimport->array_import_convertvalue[0][$val]['pathobject'];
												require_once DOL_DOCUMENT_ROOT.$pathForObject;
												$tmpclassobject = $objimport->array_import_convertvalue[0][$val]['classobject'];
												$tmpobject = new $tmpclassobject($this->db);
												foreach ($arrayfield as $tmpkey => $tmpval) {	// $arrayfield is array('c.ref'=>1, ...)
													if (in_array($tmpkey, array('t.date', 'c.date_commande'))) {
														$tmpobject->date = dol_stringtotime($arrayrecord[$arrayfield[$tmpkey]]['val'], 1);
													}
												}
											}

											$defaultref = $modForNumber->getNextValue(null, $tmpobject);
										}
										if (is_numeric($defaultref) && $defaultref <= 0) {	// If error
											$defaultref = '';
										}
										$newval = $defaultref;
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'compute') {
									$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
									$class = $objimport->array_import_convertvalue[0][$val]['class'];
									$method = $objimport->array_import_convertvalue[0][$val]['method'];
									$resultload = dol_include_once($file);
									if (empty($resultload)) {
										dol_print_error(null, 'Error trying to call file=' . $file . ', class=' . $class . ', method=' . $method);
										break;
									}
									$classinstance = new $class($this->db);
									$res = call_user_func_array(array($classinstance, $method), array(&$arrayrecord, $arrayfield, $key));
									if (empty($classinstance->error) && empty($classinstance->errors)) {
										$newval = $res; 	// We get new value computed.
									} else {
										$this->errors[$error]['type'] = 'CLASSERROR';
										$this->errors[$error]['lib'] = implode(
											"\n",
											array_merge([$classinstance->error], $classinstance->errors)
										);
										$errorforthistable++;
										$error++;
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'numeric') {
									$newval = price2num($newval);
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'accountingaccount') {
									if (!getDolGlobalString('ACCOUNTING_MANAGE_ZERO')) {
										$newval = rtrim(trim($newval), "0");
									} else {
										$newval = trim($newval);
									}
								}

								//print 'Val to use as insert is '.$newval.'<br>';
							}

							// Test regexp
							if (!empty($objimport->array_import_regex[0][$val]) && ($newval != '')) {
								// If test is "Must exist in a field@table or field@table:..."
								$reg = array();
								if (preg_match('/^(.+)@([^:]+)(:.+)?$/', $objimport->array_import_regex[0][$val], $reg)) {
									$field = $reg[1];
									$table = $reg[2];
									$filter = !empty($reg[3]) ? substr($reg[3], 1) : '';

									$cachekey = $field . '@' . $table;
									if (!empty($filter)) {
										$cachekey .= ':' . $filter;
									}

									// Load content of field@table into cache array
									if (!is_array($this->cachefieldtable[$cachekey])) { // If content of field@table not already loaded into cache
										$sql = "SELECT " . $field . " as aliasfield FROM " . $table;
										if (!empty($filter)) {
											$sql .= ' WHERE ' . $filter;
										}

										$resql = $this->db->query($sql);
										if ($resql) {
											$num = $this->db->num_rows($resql);
											$i = 0;
											while ($i < $num) {
												$obj = $this->db->fetch_object($resql);
												if ($obj) {
													$this->cachefieldtable[$cachekey][] = $obj->aliasfield;
												}
												$i++;
											}
										} else {
											dol_print_error($this->db);
										}
									}

									// Now we check cache is not empty (should not) and key is into cache
									if (!is_array($this->cachefieldtable[$cachekey]) || !in_array($newval, $this->cachefieldtable[$cachekey])) {
										$tableforerror = $table;
										if (!empty($filter)) {
											$tableforerror .= ':' . $filter;
										}
										// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
										$this->errors[$error]['lib'] = $langs->transnoentitiesnoconv('ErrorFieldValueNotIn', $key, $newval, $field, $tableforerror);
										$this->errors[$error]['type'] = 'FOREIGNKEY';
										$errorforthistable++;
										$error++;
									}
								} elseif (!preg_match('/' . $objimport->array_import_regex[0][$val] . '/i', $newval)) {
									// If test is just a static regex
									//if ($key == 19) print "xxx".$newval."zzz".$objimport->array_import_regex[0][$val]."<br>";
									// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
									$this->errors[$error]['lib'] = $langs->transnoentitiesnoconv('ErrorWrongValueForField', $key, $newval, $objimport->array_import_regex[0][$val]);
									$this->errors[$error]['type'] = 'REGEX';
									$errorforthistable++;
									$error++;
								}
							}

							// Check HTML injection
							$inj = testSqlAndScriptInject($newval, 0);
							if ($inj) {
								// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
								$this->errors[$error]['lib'] = $langs->transnoentitiesnoconv('ErrorHtmlInjectionForField', $key, dol_trunc($newval, 100));
								$this->errors[$error]['type'] = 'HTMLINJECTION';
								$errorforthistable++;
								$error++;
							}

							// Other tests
							// ...
						}

						// Define $listfields and $listvalues to build the SQL request
						if (isModEnabled("socialnetworks") && strpos($fieldname, "socialnetworks") !== false) {
							if (!in_array("socialnetworks", $listfields)) {
								$listfields[] = "socialnetworks";
								$socialkey = array_search("socialnetworks", $listfields);	// Return position of 'socialnetworks' key in array. Example socialkey=19
								$listvalues[$socialkey] = '';
							}
							if (!empty($newval) && $arrayrecord[($key)]['type'] > 0) {
								$socialkey = array_search("socialnetworks", $listfields);	// Return position of 'socialnetworks' key in array. Example socialkey=19
								$socialnetwork = explode("_", $fieldname)[1];
								if (empty($listvalues[$socialkey]) || $listvalues[$socialkey] == "null") {
									$json = new stdClass();
									$json->$socialnetwork = $newval;
									$listvalues[$socialkey] = json_encode($json);
								} else {
									$jsondata = $listvalues[$socialkey];
									$json = json_decode($jsondata);
									$json->$socialnetwork = $newval;
									$listvalues[$socialkey] = json_encode($json);
								}
							}
						} else {
							$listfields[] = $fieldname;

							// Note: arrayrecord (and 'type') is filled with ->import_read_record called by import.php page before calling import_insert
							if (empty($newval) && $arrayrecord[($key)]['type'] < 0) {
								$listvalues[] = ($newval == '0' ? (int) $newval : "null");
							} elseif (empty($newval) && $arrayrecord[($key)]['type'] == 0) {
								$listvalues[] = "''";
							} else {
								$listvalues[] = "'".$this->db->escape($newval)."'";
							}
						}
					}
					$i++;
				}

				// We add hidden fields (but only if there is at least one field to add into table)
				// We process here all the fields that were declared into the array $this->import_fieldshidden_array of the descriptor file.
				// Previously we processed the ->import_fields_array.
				if (!empty($listfields) && is_array($objimport->array_import_fieldshidden[0])) {
					// Loop on each hidden fields to add them into listfields/listvalues
					foreach ($objimport->array_import_fieldshidden[0] as $key => $val) {
						if (!preg_match('/^' . preg_quote($alias, '/') . '\./', $key)) {
							continue; // Not a field of current table
						}
						$keyfield = preg_replace('/^' . preg_quote($alias, '/') . '\./', '', $key);

						if (in_array($keyfield, $listfields)) {	// avoid duplicates in insert
							continue;
						} elseif ($val == 'user->id') {
							$listfields[] = $keyfield;
							$listvalues[] = ((int) $user->id);
						} elseif (preg_match('/^lastrowid-/', $val)) {
							$tmp = explode('-', $val);
							$lastinsertid = (isset($last_insert_id_array[$tmp[1]])) ? $last_insert_id_array[$tmp[1]] : 0;
							$listfields[] = $keyfield;
							$listvalues[] = (int) $lastinsertid;
							//print $key."-".$val."-".$listfields."-".$listvalues."<br>";exit;
						} elseif (preg_match('/^const-/', $val)) {
							$tmp = explode('-', $val, 2);
							$listfields[] = $keyfield;
							$listvalues[] = "'".$this->db->escape($tmp[1])."'";
						} elseif (preg_match('/^rule-/', $val)) {
							$fieldname = $key;
							if (!empty($objimport->array_import_convertvalue[0][$fieldname])) {
								if ($objimport->array_import_convertvalue[0][$fieldname]['rule'] == 'compute') {
									$file = (empty($objimport->array_import_convertvalue[0][$fieldname]['classfile']) ? $objimport->array_import_convertvalue[0][$fieldname]['file'] : $objimport->array_import_convertvalue[0][$fieldname]['classfile']);
									$class = $objimport->array_import_convertvalue[0][$fieldname]['class'];
									$method = $objimport->array_import_convertvalue[0][$fieldname]['method'];
									$type = $objimport->array_import_convertvalue[0][$fieldname]['type'];
									$resultload = dol_include_once($file);
									if (empty($resultload)) {
										dol_print_error(null, 'Error trying to call file=' . $file . ', class=' . $class . ', method=' . $method);
										break;
									}
									$classinstance = new $class($this->db);
									$res = call_user_func_array(array($classinstance, $method), array(&$arrayrecord, $arrayfield, $key));
									$fieldArr = explode('.', $fieldname);
									if (count($fieldArr) > 0) {
										$fieldname = $fieldArr[1];
									}

									// Set $listfields and $listvalues
									$listfields[] = $fieldname;
									if ($type == 'int') {
										$listvalues[] = (int) $res;
									} elseif ($type == 'double') {
										$listvalues[] = (float) $res;
									} else {
										$listvalues[] = "'".$this->db->escape($res)."'";
									}
								} else {
									$this->errors[$error]['type'] = 'CLASSERROR';
									$this->errors[$error]['lib'] = implode(
										"\n",
										array_merge([$classinstance->error], $classinstance->errors)
									);
									$errorforthistable++;
									$error++;
								}
							}
						} else {
							$this->errors[$error]['lib'] = 'Bad value of profile setup ' . $val . ' for array_import_fieldshidden';
							$this->errors[$error]['type'] = 'Import profile setup';
							$error++;
						}
					}
				}
				//print 'listfields='.$listfields.'<br>listvalues='.$listvalues.'<br>';

				// If no error for this $alias/$tablename, we have a complete $listfields and $listvalues that are defined
				// so we can try to make the insert or update now.
				if (!$errorforthistable) {
					//print "$alias/$tablename/$listfields/$listvalues<br>";
					if (!empty($listfields)) {
						$updatedone = false;
						$insertdone = false;

						$is_table_category_link = false;
						$fname = 'rowid';
						if (strpos($tablename, '_categorie_') !== false) {
							$is_table_category_link = true;
							$fname = '*';
						}

						if (!empty($updatekeys)) {
							// We do SELECT to get the rowid, if we already have the rowid, it's to be used below for related tables (extrafields)

							if (empty($lastinsertid)) {	// No insert done yet for a parent table
								$sqlSelect = "SELECT ".$fname." FROM " . $tablename;

								$data = array_combine($listfields, $listvalues);

								$where = array();	// filters to forge SQL request
								// @phpstan-ignore-next-line
								'@phan-var string[] $where';
								$filters = array();	// filters to forge output error message
								foreach ($updatekeys as $key) {
									$col = $objimport->array_import_updatekeys[0][$key];
									$key = preg_replace('/^.*\./i', '', $key);
									if (isModEnabled("socialnetworks") && strpos($key, "socialnetworks") !== false) {
										$tmp = explode("_", $key);
										$key = $tmp[0];
										$socialnetwork = $tmp[1];
										$jsondata = $data[$key];
										$json = json_decode($jsondata);
										$stringtosearch = json_encode($socialnetwork).':'.json_encode($json->$socialnetwork);
										//var_dump($stringtosearch);
										//var_dump($this->db->escape($stringtosearch));	// This provide a value for sql string (but not for a like)
										$where[] = $key." LIKE '%".$this->db->escape($this->db->escapeforlike($stringtosearch))."%'";
										$filters[] = $col." LIKE '%".$this->db->escape($this->db->escapeforlike($stringtosearch))."%'";
										//var_dump($where[1]); // This provide a value for sql string inside a like
									} else {
										$where[] = $key.' = '.$data[$key];
										$filters[] = $col.' = '.$data[$key];
									}
								}
								if (!empty($tablewithentity_cache[$tablename])) {
									$where[] = "entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
									$filters[] = "entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
								}
								$sqlSelect .= " WHERE " . implode(' AND ', $where);

								$resql = $this->db->query($sqlSelect);
								if ($resql) {
									$num_rows = $this->db->num_rows($resql);
									if ($num_rows == 1) {
										$res = $this->db->fetch_object($resql);
										$lastinsertid = $res->rowid;
										if ($is_table_category_link) {
											$lastinsertid = 'linktable';
										} // used to apply update on tables like llx_categorie_product and avoid being blocked for all file content if at least one entry already exists
										$last_insert_id_array[$tablename] = $lastinsertid;
									} elseif ($num_rows > 1) {
										$this->errors[$error]['lib'] = $langs->trans('MultipleRecordFoundWithTheseFilters', implode(', ', $filters));
										$this->errors[$error]['type'] = 'SQL';
										$error++;
									} else {
										// No record found with filters, insert will be tried below
									}
								} else {
									//print 'E';
									$this->errors[$error]['lib'] = $this->db->lasterror();
									$this->errors[$error]['type'] = 'SQL';
									$error++;
								}
							} else {
								// We have a last INSERT ID (got by previous pass), so we check if we have a row referencing this foreign key.
								// This is required when updating table with some extrafields. When inserting a record in parent table, we can make
								// a direct insert into subtable extrafields, but when me wake an update, the insertid is defined and the child record
								// may already exists. So we rescan the extrafield table to know if record exists or not for the rowid.
								// Note: For extrafield tablename, we have in importfieldshidden_array an entry 'extra.fk_object'=>'lastrowid-tableparent' so $keyfield is 'fk_object'
								$sqlSelect = "SELECT rowid FROM " . $tablename;


								if (empty($keyfield)) {
									$keyfield = 'rowid';
								}
								$sqlSelect .= " WHERE ".$keyfield." = ".((int) $lastinsertid);

								if (!empty($tablewithentity_cache[$tablename])) {
									$sqlSelect .= " AND entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
								}

								$resql = $this->db->query($sqlSelect);
								if ($resql) {
									$res = $this->db->fetch_object($resql);
									if ($this->db->num_rows($resql) == 1) {
										// We have a row referencing this last foreign key, continue with UPDATE.
									} else {
										// No record found referencing this last foreign key,
										// force $lastinsertid to 0 so we INSERT below.
										$lastinsertid = 0;
									}
								} else {
									//print 'E';
									$this->errors[$error]['lib'] = $this->db->lasterror();
									$this->errors[$error]['type'] = 'SQL';
									$error++;
								}
							}

							if (!empty($lastinsertid)) {
								// We db escape social network field because he isn't in field creation
								if (in_array("socialnetworks", $listfields)) {
									$socialkey = array_search("socialnetworks", $listfields);
									$tmpsql =  $listvalues[$socialkey];
									$listvalues[$socialkey] = "'".$this->db->escape($tmpsql)."'";
								}

								// Build SQL UPDATE request
								$sqlstart = "UPDATE " . $tablename;

								$data = array_combine($listfields, $listvalues);
								$set = array();
								foreach ($data as $key => $val) {
									$set[] = $key." = ".$val;	// $val was escaped/sanitized previously
								}
								$sqlstart .= " SET " . implode(', ', $set) . ", import_key = '" . $this->db->escape($importid) . "'";

								if (empty($keyfield)) {
									$keyfield = 'rowid';
								}
								$sqlend = " WHERE " . $keyfield . " = ".((int) $lastinsertid);

								if ($is_table_category_link) {
									'@phan-var-force string[] $where';
									$sqlend = " WHERE " . implode(' AND ', $where);
								}

								if (!empty($tablewithentity_cache[$tablename])) {
									$sqlend .= " AND entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
								}

								$sql = $sqlstart . $sqlend;

								// Run update request
								$resql = $this->db->query($sql);
								if ($resql) {
									// No error, update has been done. $this->db->db->affected_rows can be 0 if data hasn't changed
									$updatedone = true;
								} else {
									//print 'E';
									$this->errors[$error]['lib'] = $this->db->lasterror();
									$this->errors[$error]['type'] = 'SQL';
									$error++;
								}
							}
						}

						// Update not done, we do insert
						if (!$error && !$updatedone) {
							// We db escape social network field because he isn't in field creation
							if (in_array("socialnetworks", $listfields)) {
								$socialkey = array_search("socialnetworks", $listfields);
								$tmpsql =  $listvalues[$socialkey];
								$listvalues[$socialkey] = "'".$this->db->escape($tmpsql)."'";
							}

							// Build SQL INSERT request
							$sqlstart = "INSERT INTO " . $tablename . "(" . implode(", ", $listfields) . ", import_key";
							$sqlend = ") VALUES(" . implode(', ', $listvalues) . ", '" . $this->db->escape($importid) . "'";
							if (!empty($tablewithentity_cache[$tablename])) {
								$sqlstart .= ", entity";
								$sqlend .= ", " . $conf->entity;
							}
							if (!empty($objimport->array_import_tables_creator[0][$alias])) {
								$sqlstart .= ", " . $objimport->array_import_tables_creator[0][$alias];
								$sqlend .= ", " . $user->id;
							}
							$sql = $sqlstart . $sqlend . ")";
							//dol_syslog("import_xlsx.modules", LOG_DEBUG);

							// Run insert request
							if ($sql) {
								$resql = $this->db->query($sql);
								if ($resql) {
									if (!$is_table_category_link) {
										$last_insert_id_array[$tablename] = $this->db->last_insert_id($tablename); // store the last inserted auto_increment id for each table, so that child tables can be inserted with the appropriate id. This must be done just after the INSERT request, else we risk losing the id (because another sql query will be issued somewhere in Dolibarr).
									}
									$insertdone = true;
								} else {
									//print 'E';
									$this->errors[$error]['lib'] = $this->db->lasterror();
									$this->errors[$error]['type'] = 'SQL';
									$error++;
								}
							}
						}
					}
					/*else
					{
						dol_print_error(null,'ErrorFieldListEmptyFor '.$alias."/".$tablename);
					}*/
				}

				if ($error) {
					break;
				}
			}

			if ($updatedone) {
				$this->nbupdate++;
			}
			if ($insertdone) {
				$this->nbinsert++;
			}
		}

		return 1;
	}
}
