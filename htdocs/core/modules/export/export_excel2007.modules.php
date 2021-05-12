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
<<<<<<< HEAD
 *	\file       htdocs/core/modules/export/export_excel.modules.php
=======
 *	\file       htdocs/core/modules/export/export_excel2007.modules.php
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
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
=======
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

	public $workbook;      // Handle fichier

	public $worksheet;     // Handle onglet

	public $row;

	public $col;

    public $file;          // To save filename
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
	 */
<<<<<<< HEAD
	function __construct($db)
=======
	public function __construct($db)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $conf, $langs;
		$this->db = $db;

		$this->id='excel2007';                  // Same value then xxx in file name export_xxx.modules.php
<<<<<<< HEAD
		$this->label='Excel 2007';               // Label of driver
=======
		$this->label='Excel 2007 (old library)';               // Label of driver
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->desc = $langs->trans('Excel2007FormatDesc');
		$this->extension='xlsx';             // Extension for generated file by this driver
        $this->picto='mime/xls';			// Picto
		$this->version='1.30';             // Driver version

<<<<<<< HEAD
		$this->disabled = (in_array(constant('PHPEXCEL_PATH'),array('disabled','disabled/'))?1:0);	// A condition to disable module (used for native debian packages)
		
=======
		$this->disabled = (in_array(constant('PHPEXCEL_PATH'), array('disabled','disabled/'))?1:0);	// A condition to disable module (used for native debian packages)

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if (empty($this->disabled))
		{
    		// If driver use an external library, put its name here
    		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
    		{
    			require_once PHP_WRITEEXCEL_PATH.'class.writeexcel_workbookbig.inc.php';
                require_once PHP_WRITEEXCEL_PATH.'class.writeexcel_worksheet.inc.php';
                require_once PHP_WRITEEXCEL_PATH.'functions.writeexcel_utility.inc.php';
    			$this->label_lib='PhpWriteExcel';
                $this->version_lib='unknown';
    		}
    		else
    		{
                require_once PHPEXCEL_PATH.'PHPExcel.php';
                require_once PHPEXCEL_PATH.'PHPExcel/Style/Alignment.php';
    			$this->label_lib='PhpExcel';
                $this->version_lib='1.8.0';		// No way to get info from library
    		}
		}
<<<<<<< HEAD
		
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->row=0;
	}


<<<<<<< HEAD
	/**
     *	Close Excel file
     *
	 * 	@return		int							<0 if KO, >0 if OK
     */
	function close_file()
	{
		global $conf;

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
		return 1;
	}

}

=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Close Excel file
     *
	 *  @return		int							<0 if KO, >0 if OK
     */
    public function close_file()
    {
        // phpcs:enable
        global $conf;

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
        return 1;
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
