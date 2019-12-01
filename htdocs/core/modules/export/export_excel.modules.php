<?php
/* Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/core/modules/export/export_excel.modules.php
 *	\ingroup    export
 *	\brief      File of class to generate export file with Excel format
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


/**
 *	Class to build export files with Excel format
 */
class ExportExcel extends ModeleExports
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

	public $workbook;      // Handle file

	public $worksheet;     // Handle sheet

	public $row;

	public $col;

    public $file;          // To save filename


	/**
	 *	Constructor
	 *
	 *	@param	    DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs;
		$this->db = $db;

		$this->id='excel';                  // Same value then xxx in file name export_xxx.modules.php
		$this->label='Excel 95 (old library)';            // Label of driver
		$this->desc = $langs->trans('Excel95FormatDesc');
		$this->extension='xls';             // Extension for generated file by this driver
        $this->picto='mime/xls';            // Picto
		$this->version='1.30';              // Driver version

		$this->disabled = (in_array(constant('PHPEXCEL_PATH'), array('disabled','disabled/'))?1:0);	// A condition to disable module (used for native debian packages)

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

		$this->row=0;
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
	 *	Open output file
	 *
	 * 	@param		string		$file			File name to generate
	 *  @param		Translate	$outputlangs	Output language object
	 *	@return		int							<0 if KO, >=0 if OK
	 */
	public function open_file($file, $outputlangs)
	{
        // phpcs:enable
		global $user,$conf,$langs;

		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
		{
		    $outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO
		}

		dol_syslog(get_class($this)."::open_file file=".$file);
        $this->file=$file;

		$ret=1;

    	$outputlangs->load("exports");
		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL)) {
            require_once PHP_WRITEEXCEL_PATH.'class.writeexcel_workbookbig.inc.php';
            require_once PHP_WRITEEXCEL_PATH.'class.writeexcel_worksheet.inc.php';
            require_once PHP_WRITEEXCEL_PATH.'functions.writeexcel_utility.inc.php';
		    $this->workbook = new writeexcel_workbookbig($file);
    		$this->workbook->set_tempdir($conf->export->dir_temp);			// Set temporary directory
    		$this->workbook->set_sheetname($outputlangs->trans("Sheet"));
    		$this->worksheet = &$this->workbook->addworksheet();
		}
		else
		{
            require_once PHPEXCEL_PATH.'PHPExcel.php';
            require_once PHPEXCEL_PATH.'PHPExcel/Style/Alignment.php';

		    if ($this->id == 'excel2007')
		    {
	            if (! class_exists('ZipArchive'))	// For Excel2007, PHPExcel need ZipArchive
	            {
	            	$langs->load("errors");
	            	$this->error=$langs->trans('ErrorPHPNeedModule', 'zip');
	            	return -1;
	            }
		    }

		    if (!empty($conf->global->MAIN_USE_FILECACHE_EXPORT_EXCEL_DIR)) {
			    $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_discISAM;
			    $cacheSettings = array (
			    		'dir' => $conf->global->MAIN_USE_FILECACHE_EXPORT_EXCEL_DIR
			    );
			    PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
		    }

            $this->workbook = new PHPExcel();
            $this->workbook->getProperties()->setCreator($user->getFullName($outputlangs).' - '.DOL_APPLICATION_TITLE.' '.DOL_VERSION);
            //$this->workbook->getProperties()->setLastModifiedBy('Dolibarr '.DOL_VERSION);
            $this->workbook->getProperties()->setTitle(basename($file));
            $this->workbook->getProperties()->setSubject(basename($file));
            $this->workbook->getProperties()->setDescription(DOL_APPLICATION_TITLE.' '.DOL_VERSION);

            $this->workbook->setActiveSheetIndex(0);
            $this->workbook->getActiveSheet()->setTitle($outputlangs->trans("Sheet"));
            $this->workbook->getActiveSheet()->getDefaultRowDimension()->setRowHeight(16);
		}
		return $ret;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write header
	 *
     *  @param      Translate	$outputlangs        Object lang to translate values
	 * 	@return		int								<0 if KO, >0 if OK
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
	 * 	@return		int											<0 if KO, >0 if OK
	 */
	public function write_title($array_export_fields_label, $array_selected_sorted, $outputlangs, $array_types)
	{
        // phpcs:enable
		global $conf;

		// Create a format for the column headings
		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
		{
		    $outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO

		    $formatheader =$this->workbook->addformat();
    		$formatheader->set_bold();
    		$formatheader->set_color('blue');
    		//$formatheader->set_size(12);
    		//$formatheader->set_font("Courier New");
    		//$formatheader->set_align('center');
		}
		else
		{
            $this->workbook->getActiveSheet()->getStyle('1')->getFont()->setBold(true);
		    $this->workbook->getActiveSheet()->getStyle('1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
		}

		$this->col=0;
		foreach($array_selected_sorted as $code => $value)
		{
            $alias=$array_export_fields_label[$code];
			//print "dd".$alias;
			if (empty($alias)) dol_print_error('', 'Bad value for field with code='.$code.'. Try to redefine export.');
    		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
    		{
    			$this->worksheet->write($this->row, $this->col, $outputlangs->transnoentities($alias), $formatheader);
    		}
    		else
    		{
                $this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row+1, $outputlangs->transnoentities($alias));
    		    if (! empty($array_types[$code]) && in_array($array_types[$code], array('Date','Numeric','TextAuto')))		// Set autowidth for some types
                {
                	$this->workbook->getActiveSheet()->getColumnDimension($this->column2Letter($this->col + 1))->setAutoSize(true);
                }
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
     *  @param      resource	$objp                       A record from a fetch with all fields from select
     *  @param      Translate	$outputlangs                Object lang to translate values
     *  @param		array		$array_types				Array with types of fields
	 * 	@return		int										<0 if KO, >0 if OK
	 */
	public function write_record($array_selected_sorted, $objp, $outputlangs, $array_types)
	{
        // phpcs:enable
		global $conf;

		// Create a format for the column headings
		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
		{
		    $outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO
		}

		// Define first row
		$this->col=0;

		$reg=array();

		foreach($array_selected_sorted as $code => $value)
		{
			if (strpos($code, ' as ') == 0) $alias=str_replace(array('.','-','(',')'), '_', $code);
			else $alias=substr($code, strpos($code, ' as ') + 4);
            if (empty($alias)) dol_print_error('', 'Bad value for field with code='.$code.'. Try to redefine export.');
            $newvalue=$objp->$alias;

			$newvalue=$this->excel_clean($newvalue);
			$typefield=isset($array_types[$code])?$array_types[$code]:'';

			if (preg_match('/^Select:/i', $typefield, $reg) && $typefield = substr($typefield, 7))
			{
				$array = unserialize($typefield);
				$array = $array['options'];
				$newvalue = $array[$newvalue];
			}

			// Traduction newvalue
			if (preg_match('/^\((.*)\)$/i', $newvalue, $reg))
			{
				$newvalue=$outputlangs->transnoentities($reg[1]);
			}
			else
			{
				$newvalue=$outputlangs->convToOutputCharset($newvalue);
			}

			if (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/i', $newvalue))
			{
        		if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
        		{
            		$formatdate=$this->workbook->addformat();
            		$formatdate->set_num_format('yyyy-mm-dd');
            		//$formatdate->set_num_format(0x0f);
        		    $arrayvalue=preg_split('/[.,]/', xl_parse_date($newvalue));
    				//print "x".$arrayvalue[0].'.'.strval($arrayvalue[1]).'<br>';
    				$newvalue=strval($arrayvalue[0]).'.'.strval($arrayvalue[1]);	// $newvalue=strval(36892.521); directly does not work because . will be convert into , later
        		    $this->worksheet->write($this->row, $this->col, $newvalue, PHPExcel_Shared_Date::PHPToExcel($formatdate));
        		}
        		else
        		{
        		    $newvalue=dol_stringtotime($newvalue);
        		    $this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row+1, PHPExcel_Shared_Date::PHPToExcel($newvalue));
        		    $coord=$this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row+1)->getCoordinate();
        		    $this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        		}
			}
			elseif (preg_match('/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]$/i', $newvalue))
			{
				if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
    		    {
            		$formatdatehour=$this->workbook->addformat();
            		$formatdatehour->set_num_format('yyyy-mm-dd hh:mm:ss');
            		//$formatdatehour->set_num_format(0x0f);
            		$arrayvalue=preg_split('/[.,]/', xl_parse_date($newvalue));
    				//print "x".$arrayvalue[0].'.'.strval($arrayvalue[1]).'<br>';
    				$newvalue=strval($arrayvalue[0]).'.'.strval($arrayvalue[1]);	// $newvalue=strval(36892.521); directly does not work because . will be convert into , later
    		        $this->worksheet->write($this->row, $this->col, $newvalue, $formatdatehour);
    		    }
    		    else
    		    {
        		    $newvalue=dol_stringtotime($newvalue);
    		        $this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row+1, PHPExcel_Shared_Date::PHPToExcel($newvalue));
        		    $coord=$this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row+1)->getCoordinate();
        		    $this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('yyyy-mm-dd h:mm:ss');
    		    }
			}
			else
			{
				if (! empty($conf->global->MAIN_USE_PHP_WRITEEXCEL))
    		    {
			        $this->worksheet->write($this->row, $this->col, $newvalue);
    		    }
    		    else
				{
    		    	if ($typefield == 'Text' || $typefield == 'TextAuto')
    		    	{
    		    		//$this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row+1)->setValueExplicit($newvalue, PHPExcel_Cell_DataType::TYPE_STRING);
						$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row+1, (string) $newvalue);
    		    		$coord=$this->workbook->getActiveSheet()->getCellByColumnAndRow($this->col, $this->row+1)->getCoordinate();
    		    		$this->workbook->getActiveSheet()->getStyle($coord)->getNumberFormat()->setFormatCode('@');
    		    		$this->workbook->getActiveSheet()->getStyle($coord)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
    		    	}
    		    	else
    		    	{
    		    		$this->workbook->getActiveSheet()->SetCellValueByColumnAndRow($this->col, $this->row+1, $newvalue);
    		    	}
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
	 * 	@return		int							<0 if KO, >0 if OK
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
	 * 	@return		int							<0 if KO, >0 if OK
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
    	    $objWriter = new PHPExcel_Writer_Excel5($this->workbook);
            $objWriter->save($this->file);
            $this->workbook->disconnectWorksheets();
            unset($this->workbook);
    	}
		return 1;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Clean a cell to respect rules of Excel file cells
     *
     *  @param      string  $newvalue   String to clean
     *  @return     string              Value cleaned
     */
    public function excel_clean($newvalue)
    {
        // phpcs:enable
        // Rule Dolibarr: No HTML
        $newvalue=dol_string_nohtmltag($newvalue);

        return $newvalue;
    }


    /**
     *  Convert a column to letter (1->A, 0->B, 27->AA, ...)
     *
     *  @param      int     $c      Column position
     *  @return     string          Letter
     */
    public function column2Letter($c)
    {

        $c = intval($c);
        $letter = '';
        if ($c <= 0) {
            return '';
        }

        while ($c != 0) {
            $p = ($c - 1) % 26;
            $c = intval(($c - $p) / 26);
            $letter = chr(65 + $p) . $letter;
        }

        return $letter;
    }
}
