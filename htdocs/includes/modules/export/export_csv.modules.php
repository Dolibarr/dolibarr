<?php
/* Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
*
* $Id$
* $Source$
*/

/**
       	\file       htdocs/includes/modules/export/export_csv.modules.php
		\ingroup    export
		\brief      Fichier de la classe permettant de générer les export au format CSV
		\author	    Laurent Destailleur
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/export/modules_export.php");


/**
	    \class      ExportCsv
		\brief      Classe permettant de générer les factures au modèle Crabe
*/

class ExportCsv extends ModeleExports
{
    var $extension;
    var $handle;    // Handle fichier
    
    
    /**
    		\brief      Constructeur
    		\param	    db      Handler accès base de donnée
    */
    function ExportCsv($db)
    {
        global $conf,$langs;
        $this->db = $db;

        $this->extension='csv';
    }


    function get_extension()
    {
        return $this->extension;
    }


    function open_file($file)
    {
        dolibarr_syslog("ExportCsv::open_file file=$file");
        $this->handle = fopen($file, "wt");
    }


    function write_header()
    {

    }


    function write_title()
    {

    }


    function write_record()
    {

    }


    function write_footer()
    {

    }
    

    function close_file()
    {
        fclose($this->handle);
    }

}

?>
