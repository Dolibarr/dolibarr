<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**     \defgroup   ecm		Electronic Content Management
        \brief      Module for ECM.
		\version	$Id$
*/

/**
        \file       htdocs/includes/modules/modECM.class.php
        \ingroup    ecm
        \brief      Description and activation file for module ECM
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modECM
        \brief      Description and activation class for module ECM
*/

class modECM extends DolibarrModules
{

   /**
    *   \brief      Constructor. Define names, constants, directories, boxes, permissions
    *   \param      DB      Database handler
    */
	function modECM($DB)
	{
		$this->db = $DB;
		
		// Id for module (must be unique).
		// Use here a free id.
		$this->numero = 2500;
		
		// Family can be 'crm','financial','hr','projects','product','ecm','technic','other'
		// It is used to sort modules in module setup page 
		$this->family = "ecm";		
		// Module title used if translation string 'ModuleXXXName' not found (XXX is id value)
		$this->name = "ECM";	
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Electronic Content Management";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'experimental';    
		// Key used in llx_const table to save module status enabled/disabled (XXX is id value)
		$this->const_name = 'MAIN_MODULE_ECM';
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 2;
		// Name of png file (without png) used for this module
		$this->picto='dir';
		
		// Data directories to create when module is enabled
		$this->dirs = array();
		$this->dirs[0] = DOL_DATA_ROOT."/ecm";
		
		// Config pages. Put here list of php page names stored in admmin directory used to setup module
		$this->config_page_url = array();
		
		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		
		// Constants
		$this->const = array();			// List of parameters
		
		// Boxes
		$this->boxes = array();			// List of boxes 
		$r=0;
		
		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		// Example:
        //$this->boxes[$r][1] = "myboxa.php";
    	//$r++;
        //$this->boxes[$r][1] = "myboxb.php";
    	//$r++;

		// Permissions
		$this->rights_class = 'ecm';	// Permission key
		$this->rights = array();		// Permission array used by this module

		$r++;
		$this->rights[$r][0] = 2500;
		$this->rights[$r][1] = 'Consulter les documents';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 2501;
		$this->rights[$r][1] = 'Soumettre des documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'create';

		$r++;
		$this->rights[$r][0] = 2515;
		$this->rights[$r][1] = 'Administrer les rubriques de documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'setup';

		
        // Menus
		//------
		$r=0;
		
		$this->menu[$r]=array('fk_menu'=>0,
							  'type'=>'top',
							  'titre'=>'MenuECM(dotnoloadlang)',
							  'mainmenu'=>'ecm',
							  'leftmenu'=>'',
							  'url'=>'/ecm/index.php',
							  'langs'=>'ecm',
							  'position'=>100,
							  'perms'=>'',
							  'target'=>'',
							  'user'=>0);
		$r++;
		
	}

	/**
     *		\brief      Function called when module is enabled.
     *					The init function add previous constants, boxes and permissions into Dolibarr database.
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
