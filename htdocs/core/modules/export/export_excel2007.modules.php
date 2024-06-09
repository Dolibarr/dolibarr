<?php
/* Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
 */

/**
 *	\file       htdocs/core/modules/export/export_excel2007.modules.php
 *	\ingroup    export
 *	\brief      File of class to generate export file with Excel format
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 *	Class to build export files with Excel format
 */
class ExportExcel2007 extends ModeleExports
{
	/**
	 * @var string ID
	 */
	public $id;

	/**
	 * @var string Export Excel label
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

	/** @var Spreadsheet */
	public $workbook; // Handle file

	public $worksheet; // Handle sheet

	public $styleArray;

	public $row;

	public $col;

	public $file; // To save filename


	/**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs;
		$this->db = $db;

		$this->id = 'excel2007'; // Same value then xxx in file name export_xxx.modules.php
		$this->label = 'Excel 2007'; // Label of driver
		$this->desc = $langs->trans('Excel2007FormatDesc');
		$this->extension = 'xlsx'; // Extension for generated file by this driver
		$this->picto = 'mime/xls'; // Picto
		$this->version = '1.30'; // Driver version
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module

		$this->disabled = 0;

		if (empty($this->disabled)) {
			require_once PHPEXCELNEW_PATH.'Spreadsheet.php';
			$this->label_lib = 'PhpSpreadSheet';
			$this->version_lib = '1.12.0'; // No way to get info from library
		}

		$this->row = 0;
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
	 * getDriverLabel
	 *
	 * @return 	string			Return driver label
	 */
	public function getDriverLabelBis()
	{
		global $langs;
		$langs->load("errors");
		return $langs->trans("NumberOfLinesLimited");
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
	 *	Open output file
	 *
	 * 	@param		string		$file			File name to generate
	 *  @param		Translate	$outputlangs	Output language object
	 *	@return		int							Return integer <0 if KO, >=0 if OK
	 */
	public function open_file($file, $outputlangs)
	{
		// phpcs:enable
		global $user, $langs;

		dol_syslog(get_class($this)."::open_file file=".$file);
		$this->file = $file;

		$ret = 1;

		$outputlangs->load("exports");

		require_once DOL_DOCUMENT_ROOT.'/includes/phpoffice/phpspreadsheet/src/autoloader.php';
		require_once DOL_DOCUMENT_ROOT.'/includes/Psr/autoloader.php';
		require_once PHPEXCELNEW_PATH.'Spreadsheet.php';

		if ($this->id == 'excel2007') {
			if (!class_exists('ZipArchive')) {	// For Excel2007, PHPSpreadSheet may need ZipArchive
				$langs->load("errors");
				$this->error = $langs->trans('ErrorPHPNeedModule', 'zip');
				return -1;
			}
		}

		$this->workbook = new Spreadsheet();
		$this->workbook->getProperties()->setCreator($user->getFullName($outputlangs).' - '.DOL_APPLICATION_TITLE.' '.DOL_VERSION);
		//$this->workbook->getProperties()->setLastModifiedBy('Dolibarr '.DOL_VERSION);
		$this->workbook->getProperties()->setTitle(basename($file));
		$this->workbook->getProperties()->setSubject(basename($file));
		$this->workbook->getProperties()->setDescription(DOL_APPLICATION_TITLE.' '.DOL_VERSION);

		$this->workbook->setActiveSheetIndex(0);
		$this->workbook->getActiveSheet()->setTitle($outputlangs->trans("Sheet"));
		$this->workbook->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);

		return $ret;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write header
	 *
	 *  @param      Translate	$outputlangs        Object lang to translate values
	 * 	@return		int								Return integer <0 if KO, >0 if OK
	 */
	public function write_header($outputlangs)
	{
		// phpcs:enable
		//$outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO

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
	 * 	@return		int											Return integer <0 if KO, >0 if OK
	 */
	public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
	{
		// phpcs:enable

		// Create a format for the column headings
		$this->workbook->getActiveSheet()->getStyle('1')->getFont()->setBold(true);
		$this->workbook->getActiveSheet()->getStyle('1')->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
		$selectlabel = array();

		$this->col = 1;

		foreach ($array_selected_sorted as $code => $value) {
			$alias = $array_export_fields_label[$code];
			//print "dd".$alias;
			if (empty($alias)) {
				dol_print_error(null, 'Bad value for field with code='.$code.'. Try to redefine export.');
			}
			$typefield = isset($array_types[$code]) ? $array_types[$code] : '';

			if (preg_match('/^Select:/i', $typefield) && $typefield = substr($typefield, 7)) {
				$selectlabel[$code."_label"] = $alias."_label";
			}
			$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, $outputlangs->transnoentities($alias));
			if (!empty($array_types[$code]) && in_array($array_types[$code], array('Date', 'Numeric', 'TextAuto'))) {		// Set autowidth for some types
				$this->workbook->getActiveSheet()->getColumnDimension($this->column2Letter($this->col + 1))->setAutoSize(true);
			}
			$this->col++;
		}

		// Complete with some columns to add columns with the labels of columns of type Select, so we have more then the ID
		foreach ($selectlabel as $key => $value) {
			$code = preg_replace('/_label$/', '', $key);
			$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, $outputlangs->transnoentities($value));
			if (!empty($array_types[$code]) && in_array($array_types[$code], array('Date', 'Numeric', 'TextAuto'))) {		// Set autowidth for some types
				$this->workbook->getActiveSheet()->getColumnDimension($this->column2Letter($this->col + 1))->setAutoSize(true);
			}
			$this->col++;
		}

		$this->row++;
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Output record line into file
	 *
	 *  @param      array		$array_selected_sorted      Array with list of field to export
	 *  @param      Resource	$objp                       A record from a fetch with all fields from select
	 *  @param      Translate	$outputlangs                Object lang to translate values
	 *  @param		array		$array_types				Array with types of fields
	 * 	@return		int										Return integer <0 if KO, >0 if OK
	 */
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
		// phpcs:enable

		// Define first row
		$this->col = 1;

		$reg = array();
		$selectlabelvalues = array();
		foreach ($array_selected_sorted as $code => $value) {
			if (strpos($code, ' as ') == 0) {
				$alias = str_replace(array('.', '-', '(', ')'), '_', $code);
			} else {
				$alias = substr($code, strpos($code, ' as ') + 4);
			}
			if (empty($alias)) {
				dol_print_error(null, 'Bad value for field with code='.$code.'. Try to redefine export.');
			}

			$newvalue = !empty($objp->$alias) ? $objp->$alias : '';

			$newvalue = $this->excel_clean($newvalue);
			$typefield = isset($array_types[$code]) ? $array_types[$code] : '';

			if (preg_match('/^Select:/i', $typefield) && $typefield = substr($typefield, 7)) {
				$array = jsonOrUnserialize($typefield);
				if (is_array($array) && !empty($newvalue)) {
					$array = $array['options'];
					$selectlabelvalues[$code."_label"] = $array[$newvalue];
				} else {
					$selectlabelvalues[$code."_label"] = "";
				}
			}

			// Traduction newvalue
			if (preg_match('/^\((.*)\)$/i', $newvalue, $reg)) {
				$newvalue = $outputlangs->transnoentities($reg[1]);
			} else {
				$newvalue = $outputlangs->convToOutputCharset($newvalue);
			}

			if (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/i', $newvalue)) {
				$newvalue = dol_stringtotime($newvalue);
				$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($newvalue));
				$coord = $this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
				$this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
			} elseif (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]$/i', $newvalue)) {
				$newvalue = dol_stringtotime($newvalue);
				$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($newvalue));
				$coord = $this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
				$this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('yyyy-mm-dd h:mm:ss');
			} else {
				if ($typefield == 'Text' || $typefield == 'TextAuto') {
					// If $newvalue start with an equal sign we don't want it to be interpreted as a formula, so we add a '. Such transformation should be
					// done by SetCellValueByColumnAndRow but it is not, so we do it ourself.
					$newvalue = (dol_substr($newvalue, 0, 1) === '=' ? '\'' : '') . $newvalue;
					$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, $newvalue);
					$coord = $this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
					$this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
					$this->workbook->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
				} else {
					$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, $newvalue);
				}
			}
			$this->col++;
		}

		// Complete with some columns to add columns with the labels of columns of type Select, so we have more then the ID
		foreach ($selectlabelvalues as $key => $newvalue) {
			$code = preg_replace('/_label$/', '', $key);
			$typefield = isset($array_types[$code]) ? $array_types[$code] : '';

			if (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/i', $newvalue)) {
				$newvalue = dol_stringtotime($newvalue);
				$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($newvalue));
				$coord = $this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
				$this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
			} elseif (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]$/i', $newvalue)) {
				$newvalue = dol_stringtotime($newvalue);
				$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($newvalue));
				$coord = $this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
				$this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('yyyy-mm-dd h:mm:ss');
			} else {
				if ($typefield == 'Text' || $typefield == 'TextAuto') {
					$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, (string) $newvalue);
					$coord = $this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row + 1)->getCoordinate();
					$this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
					$this->workbook->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
				} else {
					$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row + 1, $newvalue);
				}
			}
			$this->col++;
		}

		$this->row++;
		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Write footer
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
	 *	Close Excel file
	 *
	 * 	@return		int							Return integer <0 if KO, >0 if OK
	 */
	public function close_file()
	{
		// phpcs:enable

		$objWriter = new Xlsx($this->workbook);
		$objWriter->save($this->file);
		$this->workbook->disconnectWorksheets();
		unset($this->workbook);

		return 1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Clean a cell to respect rules of Excel file cells
	 *
	 * @param 	string	$newvalue	String to clean
	 * @return 	string				Value cleaned
	 */
	public function excel_clean($newvalue)
	{
		// phpcs:enable
		// Rule Dolibarr: No HTML
		$newvalue = dol_string_nohtmltag($newvalue);

		return $newvalue;
	}


	/**
	 * Convert a column to letter (1->A, 0->B, 27->AA, ...)
	 *
	 * @param 	int		$c		Column position
	 * @return 	string			Letter
	 */
	public function column2Letter($c)
	{
		$c = intval($c);
		if ($c <= 0) {
			return '';
		}

		$letter = '';
		while ($c != 0) {
			$p = ($c - 1) % 26;
			$c = intval(($c - $p) / 26);
			$letter = chr(65 + $p).$letter;
		}

		return $letter;
	}

	/**
	 * Set cell value and automatically merge if we give an endcell
	 *
	 * @param string $val cell value
	 * @param string $startCell starting cell
	 * @param string $endCell  ending cell
	 * @return int 1 if success -1 if failed
	 */
	public function setCellValue($val, $startCell, $endCell = '')
	{
		try {
			$this->workbook->getActiveSheet()->setCellValue($startCell, $val);

			if (!empty($endCell)) {
				$cellRange = $startCell.':'.$endCell;
				$this->workbook->getActiveSheet()->mergeCells($startCell.':'.$endCell);
			} else {
				$cellRange = $startCell;
			}
			if (!empty($this->styleArray)) {
				$this->workbook->getActiveSheet()->getStyle($cellRange)->applyFromArray($this->styleArray);
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			return -1;
		}
		return 1;
	}

	/**
	 * Set border style
	 *
	 * @param string $thickness style \PhpOffice\PhpSpreadsheet\Style\Border
	 * @param string $color     color \PhpOffice\PhpSpreadsheet\Style\Color
	 * @return int 1 if ok
	 */
	public function setBorderStyle($thickness, $color)
	{
		$this->styleArray['borders'] = array(
			'outline' => array(
				'borderStyle' => $thickness,
				'color' => array('argb' => $color)
			)
		);
		return 1;
	}

	/**
	 * Set font style
	 *
	 * @param bool   $bold  true if bold
	 * @param string $color color \PhpOffice\PhpSpreadsheet\Style\Color
	 * @return int 1
	 */
	public function setFontStyle($bold, $color)
	{
		$this->styleArray['font'] = array(
			'color' => array('argb' => $color),
			'bold' => $bold
		);
		return 1;
	}

	/**
	 * Set alignment style (horizontal, left, right, ...)
	 *
	 * @param string $horizontal PhpOffice\PhpSpreadsheet\Style\Alignment
	 * @return int 1
	 */
	public function setAlignmentStyle($horizontal)
	{
		$this->styleArray['alignment'] = array('horizontal' => $horizontal);
		return 1;
	}

	/**
	 * Reset Style
	 * @return int 1
	 */
	public function resetStyle()
	{
		$this->styleArray = array();
		return 1;
	}

	/**
	 * Make a NxN Block in sheet
	 *
	 * @param string $startCell starting cell
	 * @param array  $TDatas array(ColumnName=>array(Row value 1, row value 2, etc ...))
	 * @param bool   $boldTitle true if bold headers
	 * @return int 1 if OK, -1 if KO
	 */
	public function setBlock($startCell, $TDatas = array(), $boldTitle = false)
	{
		try {
			if (!empty($TDatas)) {
				$startCell = $this->workbook->getActiveSheet()->getCell($startCell);
				$startColumn = Coordinate::columnIndexFromString($startCell->getColumn());
				$startRow = $startCell->getRow();
				foreach ($TDatas as $column => $TRows) {
					if ($boldTitle) {
						$this->setFontStyle(true, $this->styleArray['font']['color']['argb']);
					}
					$cell = $this->workbook->getActiveSheet()->getCellByColumnAndRow($startColumn, $startRow);
					$this->setCellValue($column, $cell->getCoordinate());
					$rowPos = $startRow;
					if ($boldTitle) {
						$this->setFontStyle(false, $this->styleArray['font']['color']['argb']);
					}
					foreach ($TRows as $row) {
						$rowPos++;
						$cell = $this->workbook->getActiveSheet()->getCellByColumnAndRow($startColumn, $rowPos);
						$this->setCellValue($row, $cell->getCoordinate());
					}
					$startColumn++;
				}
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			return -1;
		}
		return 1;
	}

	/**
	 * Make a 2xN Tab in Sheet
	 *
	 * @param string $startCell A1
	 * @param array  $TDatas    array(Title=>val)
	 * @param bool   $boldTitle true if bold titles
	 * @return int 1 if OK, -1 if KO
	 */
	public function setBlock2Columns($startCell, $TDatas = array(), $boldTitle = false)
	{
		try {
			if (!empty($TDatas)) {
				$startCell = $this->workbook->getActiveSheet()->getCell($startCell);
				$startColumn = Coordinate::columnIndexFromString($startCell->getColumn());
				$startRow = $startCell->getRow();
				foreach ($TDatas as $title => $val) {
					$cell = $this->workbook->getActiveSheet()->getCellByColumnAndRow($startColumn, $startRow);
					if ($boldTitle) {
						$this->setFontStyle(true, $this->styleArray['font']['color']['argb']);
					}
					$this->setCellValue($title, $cell->getCoordinate());
					if ($boldTitle) {
						$this->setFontStyle(false, $this->styleArray['font']['color']['argb']);
					}
					$cell2 = $this->workbook->getActiveSheet()->getCellByColumnAndRow($startColumn + 1, $startRow);
					$this->setCellValue($val, $cell2->getCoordinate());
					$startRow++;
				}
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			return -1;
		}
		return 1;
	}

	/**
	 * Enable auto sizing for column range
	 *
	 * @param string $firstColumn first column to autosize
	 * @param string $lastColumn  to last column to autosize
	 * @return int 1
	 */
	public function enableAutosize($firstColumn, $lastColumn)
	{
		foreach (range($firstColumn, $lastColumn) as $columnID) {
			$this->workbook->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
		}
		return 1;
	}

	/**
	 * Set a value cell and merging it by giving a starting cell and a length
	 *
	 * @param	string		$val		Cell value
	 * @param	string		$startCell	Starting cell
	 * @param	int			$length		Length
	 * @param	int			$offset		Starting offset
	 * @return	int|string				Coordinate or if KO: -1
	 */
	public function setMergeCellValueByLength($val, $startCell, $length, $offset = 0)
	{
		try {
			$startCell = $this->workbook->getActiveSheet()->getCell($startCell);
			$startColumn = Coordinate::columnIndexFromString($startCell->getColumn());
			if (!empty($offset)) {
				$startColumn += $offset;
			}

			$startRow = $startCell->getRow();
			$startCell = $this->workbook->getActiveSheet()->getCellByColumnAndRow($startColumn, $startRow);
			$startCoordinate = $startCell->getCoordinate();
			$this->setCellValue($val, $startCell->getCoordinate());

			$endCell = $this->workbook->getActiveSheet()->getCellByColumnAndRow($startColumn + ($length - 1), $startRow);
			$endCoordinate = $endCell->getCoordinate();
			$this->workbook->getActiveSheet()->mergeCells($startCoordinate.':'.$endCoordinate);
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			return -1;
		}
		return $endCoordinate;
	}
}
