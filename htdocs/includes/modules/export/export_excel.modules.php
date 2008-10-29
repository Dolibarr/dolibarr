<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 \file       htdocs/includes/modules/export/export_excel.modules.php
 \ingroup    export
 \brief      Fichier de la classe permettant de g�n�rer les export au format Excel
 \author	    Laurent Destailleur
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/export/modules_export.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_workbookbig.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/class.writeexcel_worksheet.inc.php");
require_once(PHP_WRITEEXCEL_PATH."/functions.writeexcel_utility.inc.php");


/**
 \class      ExportExcel
 \brief      Classe permettant de g�n�rer les export au format Excel
 */

class ExportExcel extends ModeleExports
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


	/**
	 \brief      Constructeur
	 \param	    db      Handler acc�s base de donn�e
	 */
	function ExportExcel($db)
	{
		global $conf;
		$this->db = $db;

		$this->id='excel';                  // Same value then xxx in file name export_xxx.modules.php
		$this->label='Excel';               // Label of driver
		$this->extension='xls';             // Extension for generated file by this driver
		$ver=split(' ','$Revision$');
		$this->version=$ver[2];             // Driver version

		// If driver use an external library, put its name here
		$this->label_lib='Php_WriteExcel';
		$this->version_lib='0.3.0';

		$this->row=0;
	}

	function getDriverId()
	{
		return $this->id;
	}

	function getDriverLabel()
	{
		return $this->label;
	}

	function getDriverExtension()
	{
		return $this->extension;
	}

	function getDriverVersion()
	{
		return $this->version;
	}

	function getLibLabel()
	{
		return $this->label_lib;
	}

	function getLibVersion()
	{
		return $this->version_lib;
	}


	/**
	 *	\brief		Open output file
	 *	\param		file		Path of filename
	 *	\return		int			<0 if KO, >=0 if OK
	 */
	function open_file($file,$outputlangs)
	{
		global $langs;
   
		$outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO
   
		dolibarr_syslog("ExportExcel::open_file file=".$file);

		$ret=1;

		$outputlangs->load("exports");
		$this->workbook = &new writeexcel_workbookbig($file);
		$this->workbook->set_sheetname($outputlangs->trans("Sheet"));
		$this->worksheet = &$this->workbook->addworksheet();

		// $this->worksheet->set_column(0, 50, 18);

		return $ret;
	}

	/**
	 *
	 */
	function write_header($outputlangs)
	{
		$outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO
   
		return 0;
	}


	/**
	 *
	 */
	function write_title($array_export_fields_label,$array_selected_sorted,$outputlangs)
	{
		$outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO
   
		// Create a format for the column headings
		$formatheader =$this->workbook->addformat();
		$formatheader->set_bold();
		$formatheader->set_color('blue');
		//$formatheader->set_size(12);
		//$formatheader->set_font("Courier New");
		//$formatheader->set_align('center');
   
		//$this->worksheet->insert_bitmap('A1', 'php.bmp', 16, 8);

		$this->col=0;
		foreach($array_selected_sorted as $code => $value)
		{
			$alias=$array_export_fields_label[$code];
			//print "dd".$alias;
			if (empty($alias)) dolibarr_print_error('','Bad value for field with code='.$code.'. Try to redefine export.');
			$this->worksheet->write($this->row, $this->col, $outputlangs->transnoentities($alias), $formatheader);
			$this->col++;
		}
		$this->row++;
		return 0;
	}

	/**
	 *
	 */
	function write_record($array_alias,$array_selected_sorted,$objp,$outputlangs)
	{
		$outputlangs->charset_output='ISO-8859-1';	// Because Excel 5 format is ISO
   
		$formatdate=$this->workbook->addformat();
		$formatdate->set_num_format('yyyy-mm-dd');
		//$formatdate->set_num_format(0x0f);

		$formatdatehour=$this->workbook->addformat();
		$formatdatehour->set_num_format('yyyy-mm-dd hh:mm:ss');
		//$formatdatehour->set_num_format(0x0f);
   
   
		$this->col=0;
		foreach($array_selected_sorted as $code => $value)
		{
			$alias=$array_alias[$code];
			$newvalue=$objp->$alias;
			// Nettoyage newvalue
			$newvalue=clean_html($newvalue);
			// Traduction newvalue
			if (eregi('^\((.*)\)$',$newvalue,$reg))
			{
				$newvalue=$outputlangs->transnoentities($reg[1]);
			}
			else
			{
				$newvalue=$outputlangs->convToOutputCharset($newvalue);
			}

			if (eregi('^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',$newvalue))
			{
				$arrayvalue=split('[\.,]',xl_parse_date($newvalue));
				//print "x".$arrayvalue[0].'.'.strval($arrayvalue[1]).'<br>';
				$newvalue=strval($arrayvalue[0]).'.'.strval($arrayvalue[1]);	// $newvalue=strval(36892.521); directly does not work because . will be convert into , later
				$this->worksheet->write($this->row, $this->col, $newvalue, $formatdate);
			}
			elseif (eregi('^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9]$',$newvalue))
			{
				$arrayvalue=split('[\.,]',xl_parse_date($newvalue));
				//print "x".$arrayvalue[0].'.'.strval($arrayvalue[1]).'<br>';
				$newvalue=strval($arrayvalue[0]).'.'.strval($arrayvalue[1]);	// $newvalue=strval(36892.521); directly does not work because . will be convert into , later
				$this->worksheet->write($this->row, $this->col, $newvalue, $formatdatehour);
			}
			else
			{
				$this->worksheet->write($this->row, $this->col, $newvalue);
			}
			$this->col++;
		}
		$this->row++;
		return 0;
	}


	function write_footer($outputlangs)
	{
		return 0;
	}


	function close_file()
	{
		$this->workbook->close();
		return 0;
	}

}

?>
