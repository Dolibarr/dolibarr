<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\brief      File to build exports with CSV format
 *		\author	    Laurent Destailleur
 *		\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/import/modules_import.php");


/**
 *	    \class      ImportCsv
 *		\brief      Classe permettant de lire les fichiers imports CSV
 */
class ImportCsv extends ModeleImports
{
    var $id;
    var $label;
    var $extension;
    var $version;

    var $label_lib;
    var $version_lib;

    var $separator;
    
    var $handle;    // Handle fichier

    
    /**
     *		\brief      Constructeur
     *		\param	    db      Handler acces base de donnee
     */
    function ImportCsv($db)
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
        
        $this->separator=',';
        if (! empty($conf->global->EXPORT_CSV_SEPARATOR_TO_USE)) $this->separator=$conf->global->EXPORT_CSV_SEPARATOR_TO_USE;
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
    	
        dol_syslog("ImportCsv::open_file file=".$file);

		$ret=1;
		
        $outputlangs->load("exports");
		$this->handle = fread($file, "wt");
        if (! $this->handle)
		{
			$langs->load("errors");
			$this->error=$langs->trans("ErrorFailToOpenFile",$file);
			$ret=-1;
		}
		
		return $ret;
    }

	/**
	 * 	\brief		Output header into file
	 * 	\param		langs		Output language
	 */
    function read_header($outputlangs)
    {
		return 0;
    }


	/**
	 * 	\brief		Output record line into file
	 */
    function read_record($array_alias,$array_selected_sorted,$objp,$outputlangs)
    {
    	global $conf;
    	if (! empty($conf->global->EXPORT_CSV_FORCE_CHARSET)) $outputlangs->charset_output=$conf->global->EXPORT_CSV_FORCE_CHARSET;

    	$this->col=0;
 		foreach($array_selected_sorted as $code => $value)
        {
            $alias=$array_alias[$code];
            if (empty($alias)) dol_print_error('','Bad value for field with code='.$code.'. Try to redefine export.');
			$newvalue=$outputlangs->convToOutputCharset($objp->$alias);

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
