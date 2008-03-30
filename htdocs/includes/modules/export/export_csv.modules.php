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
       	\file       htdocs/includes/modules/export/export_csv.modules.php
		\ingroup    export
		\brief      Fichier de la classe permettant de générer les export au format CSV
		\author	    Laurent Destailleur
		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/export/modules_export.php");


/**
	    \class      ExportCsv
		\brief      Classe permettant de générer les factures au modèle Crabe
*/

class ExportCsv extends ModeleExports
{
    var $id;
    var $label;
    var $extension;
    var $version;

    var $label_lib;
    var $version_lib;

    var $handle;    // Handle fichier

    
    /**
    		\brief      Constructeur
    		\param	    db      Handler accès base de donnée
    */
    function ExportCsv($db)
    {
        global $conf,$langs;
        $this->db = $db;

        $this->id='csv';                // Same value then xxx in file name export_xxx.modules.php
        $this->label='Csv';             // Label of driver
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
	function open_file($file)
    {
        global $langs;
        dolibarr_syslog("ExportCsv::open_file file=".$file);

		$ret=1;
		
        $langs->load("exports");
		$this->handle = fopen($file, "wt");
        if (! $this->handle)
		{
			$langs->load("errors");
			$this->error=$langs->trans("ErrorFailToCreateFile",$file);
			$ret=-1;
		}
		
		return $ret;
    }


    function write_header($langs)
    {
        return 0;
    }


    function write_title($array_export_fields_label,$array_selected_sorted,$langs)
    {
        foreach($array_selected_sorted as $code => $value)
        {
            fwrite($this->handle,$langs->transnoentities($array_export_fields_label[$code]).";");
        }
        fwrite($this->handle,"\n");
        return 0;
    }


    function write_record($array_alias,$array_selected_sorted,$objp)
    {
        global $langs;
		
		$this->col=0;
 		foreach($array_selected_sorted as $code => $value)
        {
            $alias=$array_alias[$code];
			$newvalue=$objp->$alias;
            // Nettoyage newvalue
			$newvalue=ereg_replace(';',',',clean_html($newvalue));
            $newvalue=ereg_replace("\r",'',$newvalue);
            $newvalue=ereg_replace("\n",'\n',$newvalue);
			// Traduction newvalue
			if (eregi('^\((.*)\)$',$newvalue,$reg))
			{
				$newvalue=$langs->transnoentities($reg[1]);
			}
			
			fwrite($this->handle,$newvalue.";");
            $this->col++;
		}
        fwrite($this->handle,"\n");
        return 0;
    }


    function write_footer($langs)
    {
        return 0;
    }
    

    function close_file()
    {
        fclose($this->handle);
        return 0;
    }

}

?>
