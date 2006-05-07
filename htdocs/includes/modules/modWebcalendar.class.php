<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**     \defgroup   webcalendar     Module Webcalendar
        \brief      Module to include Webcalendar into Dolibarr and
                    add Dolibarr events directly inside a Webcalendar database.
*/

/**
        \file       htdocs/includes/modules/modWebcalendar.class.php
        \ingroup    webcalendar
        \brief      Description and activation file for module Webcalendar
*/

include_once "DolibarrModules.class.php";

/**     \class      modWebcalendar
        \brief      Description and activation class for module Webcalendar
*/

class modWebcalendar extends DolibarrModules
{

   /**
    *   \brief      Constructor. Define names, constants, directories, boxes, permissions
    *   \param      DB      Database handler
    */
	function modWebcalendar($DB)
	{
		$this->db = $DB;
		
		// Id of module (must be unique for all modules)
		// Use same value here than in file modXxx.class.php
		$this->id = 'webcalendar';   	
		// Another id for module (must be unique).
		// Use here a free id.
		$this->numero = 410;
		
		// Family can be 'crm','financial','hr','projects','product','technic','other'
		// It is used to sort modules in module setup page 
		$this->family = "projects";		
		// Module title used if translation string 'ModuleXXXName' not found (XXX is id value)
		$this->name = "Webcalendar";	
		// Module descriptoin used translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Interfaçage avec le calendrier Webcalendar";
		// Possible values for version are: 'experimental' or 'dolibarr' or version
		$this->version = 'dolibarr';    
		// Id used in llx_const table to manage module status (enabled/disabled)	
		$this->const_name = 'MAIN_MODULE_WEBCALENDAR';
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 1;
		// Name of png file (without png) used for this module
		$this->picto='calendar';
		
		// Data directories to create when module is enabled
		$this->dirs = array();
		
		// Config pages
		$this->config_page_url = array("webcalendar.php");
		
		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		
		// Constants
		$this->const = array();			// List of parameters
		
		// Boxes
		$this->boxes = array();			// List of boxes 
		
		// Permissions
		$this->rights_class = 'webcal';	// Permission key
		$this->rights = array();		// Permission array used by this module
		// Example
		// $r++;
		// $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
	}

	/**
     *		\brief      Function called when module is enabled.
     *					Add constants, boxes and permissions into Dolibarr database.
     *					It also creates data directories.
     */
	function init()
  	{
    	$sql = array();
    
    	return $this->_init($sql);
  	}

	/**
	 *		\brief		Function called when module is disabled.
 	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
 	 *					Data directories are not deleted.
 	 */
	function remove()
	{
    	$sql = array();

    	return $this->_remove($sql);
  	}

}

?>
