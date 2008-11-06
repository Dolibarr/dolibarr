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
 *		\file       htdocs/includes/modules/export/export_csv.modules.php
 *		\ingroup    export
 *		\brief      Fichier de la classe permettant de g�n�rer les export au format CSV
 *		\author	    Laurent Destailleur
 *		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/export/modules_export.php");


/**
 *	    \class      ExportCsv
 *		\brief      Classe permettant de g�n�rer les factures au mod�le Crabe
 */
class ExportCsv extends ModeleExports
{
    var $id;
    var $label;
    var $extension;
    var $version;

    var $label_lib;
    var $version_lib;

    var $separator=',';
    
    var $handle;    // Handle fichier

    
    /**
    		\brief      Constructeur
    		\param	    db      Handler acc�s base de donn�e
    */
    function ExportCsv($db)
    {
        global $conf;
        $this->db = $db;

        $this->id='csv';                // Same value then xxx in file name export_xxx.modules.php
        $this->label='Csv (Comma Separated Value)';             // Label of driver
        $this->extension='csv';         // Extension for generated file by this driver
        $ver=split(' ','$Revision$');
        $this->version=$ver[2];         // Driver version

        // If driver use an external library, put its name here
        $this->label_lib='Dolibarr';            
        $this->version_lib=DOL_VERSION;
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
    	
        dolibarr_syslog("ExportCsv::open_file file=".$file);

		$ret=1;
		
        $outputlangs->load("exports");
		$this->handle = fopen($file, "wt");
        if (! $this->handle)
		{
			$langs->load("errors");
			$this->error=$langs->trans("ErrorFailToCreateFile",$file);
			$ret=-1;
		}
		
		return $ret;
    }

	/**
	 * 	\brief		Output header into file
	 * 	\param		langs		Output language
	 */
    function write_header($outputlangs)
    {
		return 0;
    }


	/**
	 * 	\brief		Output title line into file
	 * 	\param		langs		Output language
	 */
    function write_title($array_export_fields_label,$array_selected_sorted,$outputlangs)
    {
        foreach($array_selected_sorted as $code => $value)
        {
            $newvalue=$outputlangs->transnoentities($array_export_fields_label[$code]);
			$newvalue=$this->csv_clean($newvalue);
            
			fwrite($this->handle,$newvalue.$this->separator);
        }
        fwrite($this->handle,"\n");
        return 0;
    }


	/**
	 * 	\brief		Output record line into file
	 */
    function write_record($array_alias,$array_selected_sorted,$objp,$outputlangs)
    {
		$this->col=0;
 		foreach($array_selected_sorted as $code => $value)
        {
            $alias=$array_alias[$code];
            if (empty($alias)) dolibarr_print_error('','Bad value for field with code='.$code.'. Try to redefine export.');
			$newvalue=$objp->$alias;

            // Translation newvalue
			if (eregi('^\((.*)\)$',$newvalue,$reg))
			{
				$newvalue=$outputlangs->transnoentities($reg[1]);
			}
			
			$newvalue=$this->csv_clean($newvalue);
			
			fwrite($this->handle,$newvalue.$this->separator);
            $this->col++;
		}
        fwrite($this->handle,"\n");
        return 0;
    }

	/**
	 * 	\brief		Output footer into file
	 * 	\param		outputlangs		Output language
	 */
    function write_footer($outputlangs)
    {
		return 0;
    }
    
	/**
	 * 	\brief		Close file handle
	 */
    function close_file()
    {
        fclose($this->handle);
        return 0;
    }

    /**
     * Clean a cell to respect rules of CSV file cells
     * @param 	newvalue	String to clean
     * @return 	string		Value cleaned
     */
    function csv_clean($newvalue)
    {
    	$addquote=0;
    	
		// Rule Dolibarr: No HTML
		$newvalue=dol_string_nohtmltag($newvalue);

		// Rule 1 CSV: No CR, LF in cells
    	$newvalue=ereg_replace("\r",'',$newvalue);
        $newvalue=ereg_replace("\n",'\n',$newvalue);
    	
        // Rule 2 CSV: If value contains ", we must duplicate ", and add "
		if (ereg('"',$newvalue))
		{
			$addquote=1;
			$newvalue=ereg_replace('"','""',$newvalue);
		}

		// Rule 3 CSV: If value contains separator, we must add "
    	if (ereg($this->separator,$newvalue))
    	{
    		$addquote=1;
    	}
    	
    	return ($addquote?'"':'').$newvalue.($addquote?'"':'');
    }
    
}

?>
