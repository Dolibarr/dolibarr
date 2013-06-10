<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/core/modules/export/export_excel.modules.php
 *	\ingroup    export
 *	\brief      File of class to generate export file with Excel format
 *	\author	    Laurent Destailleur
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/export/export_excel.modules.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to build export files with Excel format
 */
class ExportExcel2007 extends ExportExcel
{
	var $id;
	var $label;
	var $extension;
	var $version;

	var $label_lib;
	var $version_lib;

	var $workbook;      // Handle fichier
	var $worksheet;     // Handle onglet
	var $row;
	var $col;
    var $file;          // To save filename


	/**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs;
		$this->db = $db;

		$this->id='excel2007';                  // Same value then xxx in file name export_xxx.modules.php
		$this->label='Excel 2007';               // Label of driver
		$this->desc = $langs->trans('Excel2007FormatDesc');
		$this->extension='xlsx';             // Extension for generated file by this driver
        $this->picto='mime/xls';			// Picto
		$this->version='1.30';             // Driver version

		// If driver use an external library, put its name here
		$this->label_lib='PhpExcel';
		$this->version_lib='1.7.2';

		$this->row=0;
	}


	/**
     *	Close Excel file
     *
	 * 	@return		int							<0 if KO, >0 if OK
     */
	function close_file()
	{
		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
    	{
	        $this->workbook->close();
    	}
    	else
    	{
            require_once PHPEXCEL_PATH.'PHPExcel/Writer/Excel5.php';
    	    $objWriter = new PHPExcel_Writer_Excel2007($this->workbook);
            $objWriter->save($this->file);
            $this->workbook->disconnectWorksheets();
            unset($this->workbook);
    	}
		return 0;
	}

}

?>
