<?php
/* Copyright (C) 2005-2007 Regis Houssin  <regis@dolibarr.fr>
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

/**     \defgroup   phenix     Module Phenix
        \brief      Module to include Phenix into Dolibarr and
                    add Dolibarr events directly inside a Phenix database.
		\version	$Id$
*/

/**
        \file       htdocs/includes/modules/modPhenix.class.php
        \ingroup    phenix
        \brief      Description and activation file for module Phenix
*/

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modPhenix
        \brief      Description and activation class for module Phenix
*/

class modPhenix extends DolibarrModules
{

   /**
    *   \brief      Constructor. Define names, constants, directories, boxes, permissions
    *   \param      DB      Database handler
    */
	function modPhenix($DB)
	{
		$this->db = $DB;
		
		// Id for module (must be unique).
		// Use here a free id.
		$this->numero = 420;
		
		// Family can be 'crm','financial','hr','projects','product','technic','other'
		// It is used to sort modules in module setup page 
		$this->family = "projects";		
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		// Module descriptoin used translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Interface avec le calendrier Phenix";
		// Possible values for version are: 'experimental' or 'dolibarr' or version
		$this->version = 'development';    
		// Id used in llx_const table to manage module status (enabled/disabled)	
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 1;
		// Name of png file (without png) used for this module
		$this->picto='calendar';
		
		// Data directories to create when module is enabled
		$this->dirs = array();
		
		// Config pages. Put here list of php page names stored in admmin directory used to setup module
		$this->config_page_url = array("phenix.php");
		
		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		
		// Constants
		$this->const = array();			// List of parameters
		
		// Boxes
		$this->boxes = array();			// List of boxes 
		$r=0;
		
		// Add here list of default box name and php file stored in includes/boxes that
		// contains class to show a box.
		// Example:
		//$this->boxes[$r][0] = "My box";
        //$this->boxes[$r][1] = "mybox.php";
    	//$r++;

		// Permissions
		$this->rights_class = 'phenix';	// Permission key
		$this->rights = array();		// Permission array used by this module

        // Menus
		//------
		$r=0;
		
		$this->menu[$r]=array('fk_menu'=>0,
													'type'=>'top',
													'titre'=>'Calendar',
													'mainmenu'=>'phenix',
													'leftmenu'=>'1',
													'url'=>'/phenix/phenix.php',
													'langs'=>'other',
													'position'=>100,
													'perms'=>'',
													'target'=>'',
													'user'=>0,
													'constraint'=>'$conf->phenix->enabled');
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
