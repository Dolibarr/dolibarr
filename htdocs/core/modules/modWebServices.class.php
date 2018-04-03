<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \defgroup   webservices     Module webservices
 *      \brief      Module to enable the Dolibarr server of web services
 *       \file       htdocs/core/modules/modWebServices.class.php
 *       \ingroup    webservices
 *       \brief      File to describe webservices module
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe a WebServices module
 */
class modWebServices extends DolibarrModules
{

    /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        $this->numero = 2600;

        $this->family = "interface";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Enable the Dolibarr web services server";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        $this->picto='technic';

        // Data directories to create when module is enabled
        $this->dirs = array();

        // Config pages
        //-------------
        $this->config_page_url = array("index.php@webservices");

        // Dependancies
        //-------------
        $this->depends = array();
        $this->requiredby = array();
        //$this->phpmax = array(7,1);					// Maximum version of PHP required by module
        $this->langfiles = array("other");

        // Constants
        //-----------
        $this->const = array();

        // New pages on tabs
        // -----------------
        $this->tabs = array();

        // Boxes
        //------
        $this->boxes = array();

        // Permissions
        //------------
        $this->rights = array();
        $this->rights_class = 'webservices';
        $r=0;
    }
}
