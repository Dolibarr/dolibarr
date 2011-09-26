<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**     \defgroup   ecm		Module ecm
 *      \brief      Module for ECM (Electronic Content Management)
 *      \file       htdocs/includes/modules/modECM.class.php
 *      \ingroup    ecm
 *      \brief      Description and activation file for module ECM
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modECM
 *      \brief      Description and activation class for module ECM
 */
class modECM extends DolibarrModules
{

   /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$DB      Database handler
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
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Electronic Content Management";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (XXX is id value)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 0;
		// Name of png file (without png) used for this module
		$this->picto='dir';

		// Data directories to create when module is enabled
		$this->dirs = array("/ecm/temp");

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
		$this->rights[$r][0] = 2501;
		$this->rights[$r][1] = 'Consulter/Télécharger les documents';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 2503;
		$this->rights[$r][1] = 'Soumettre ou supprimer des documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'upload';

		$r++;
		$this->rights[$r][0] = 2515;
		$this->rights[$r][1] = 'Administrer les rubriques de documents';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 1;
		$this->rights[$r][4] = 'setup';


        // Menus
		//------
		$this->menus = array();			// List of menus to add
		$r=0;

		// Top menu
		$this->menu[$r]=array('fk_menu'=>0,
							  'type'=>'top',
							  'titre'=>'MenuECM',
							  'mainmenu'=>'ecm',
							  'leftmenu'=>'1',		// To say if we can overwrite leftmenu
							  'url'=>'/ecm/index.php',
							  'langs'=>'ecm',
							  'position'=>100,
							  'perms'=>'$user->rights->ecm->read || $user->rights->ecm->upload || $user->rights->ecm->setup',
							  'enabled'=>'$conf->ecm->enabled',
							  'target'=>'',
							  'user'=>2);			// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		// Left menu linked to top menu
		$this->menu[$r]=array('fk_menu'=>'r=0',
							  'type'=>'left',
							  'titre'=>'ECMArea',
							  'mainmenu'=>'ecm',
							  'url'=>'/ecm/index.php',
							  'langs'=>'ecm',
							  'position'=>101,
							  'perms'=>'$user->rights->ecm->read || $user->rights->ecm->upload',
							  'enabled'=>'$user->rights->ecm->read || $user->rights->ecm->upload',
							  'target'=>'',
							  'user'=>2);			// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array('fk_menu'=>'r=1',
							  'type'=>'left',
							  'titre'=>'ECMNewSection',
							  'mainmenu'=>'ecm',
							  'url'=>'/ecm/docdir.php?action=create',
							  'langs'=>'ecm',
							  'position'=>100,
							  'perms'=>'$user->rights->ecm->setup',
							  'enabled'=>'$user->rights->ecm->setup',
							  'target'=>'',
							  'user'=>2);			// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array('fk_menu'=>'r=1',
							  'type'=>'left',
							  'titre'=>'ECMFileManager',
							  'mainmenu'=>'ecm',
							  'url'=>'/ecm/index.php?action=file_manager',
							  'langs'=>'ecm',
							  'position'=>102,
							  'perms'=>'$user->rights->ecm->read || $user->rights->ecm->upload',
							  'enabled'=>'$user->rights->ecm->read || $user->rights->ecm->upload',
							  'target'=>'',
							  'user'=>2);			// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array('fk_menu'=>'r=1',
							  'type'=>'left',
							  'titre'=>'Search',
							  'mainmenu'=>'ecm',
							  'url'=>'/ecm/search.php',
							  'langs'=>'ecm',
							  'position'=>103,
							  'perms'=>'$user->rights->ecm->read',
							  'enabled'=>'$user->rights->ecm->read',
							  'target'=>'',
							  'user'=>2);			// 0=Menu for internal users, 1=external users, 2=both
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
