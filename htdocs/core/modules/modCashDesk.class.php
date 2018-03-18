<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \defgroup   pos       Module points of sale
 *      \brief      Module to manage points of sale
 *      \file       htdocs/core/modules/modCashDesk.class.php
 *      \ingroup    pos
 *      \brief      File to enable/disable module Point Of Sales
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Point Of Sales
 */
class modCashDesk extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used module id).
		$this->numero = 50100;
		// Key text used to identify module (for permission, menus, etc...)
		$this->rights_class = 'cashdesk';

		$this->family = "portal";
		$this->module_position = 10;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "CashDesk module";

		$this->revision = '1.27';
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'list';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("cashdesk.php@cashdesk");

		// Dependencies
		$this->depends = array('always'=>"modBanque", 'always'=>"modFacture", 'always'=>"modProduct", 'FR'=>'modBlockedLog');	// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();			    // List of modules id to disable if this one is disabled
		$this->phpmin = array(4,1);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(2,4);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("cashdesk");
		$this->warnings_activation = array('FR'=>'WarningNoteModulePOSForFrenchLaw');                     // Warning to show when we activate module. array('always'='text') or array('FR'='text')
		//$this->warnings_activation_ext = array('FR'=>'WarningInstallationMayBecomeNotCompliantWithLaw');  // Warning to show when we activate an external module. array('always'='text') or array('FR'='text')

		// Constants
		$this->const = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'cashdesk';
		$r=0;

		$r++;
		$this->rights[$r][0] = 50101;
		$this->rights[$r][1] = 'Use point of sale';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'use';

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

		// This is to declare the Top Menu entry:
		$this->menu[$r]=array(	    'fk_menu'=>0,			// Put 0 if this is a top menu
									'type'=>'top',			// This is a Top menu entry
									'titre'=>'CashDeskMenu',
									'mainmenu'=>'cashdesk',
									'url'=>'/cashdesk/index.php?user=__LOGIN__',
									'langs'=>'cashdesk',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>900,
                                    'enabled'=>'$conf->cashdesk->enabled',
		                            'perms'=>'$user->rights->cashdesk->use',		// Use 'perms'=>'1' if you want your menu with no permission rules
									'target'=>'pointofsale',
									'user'=>0);				// 0=Menu for internal users, 1=external users, 2=both

		$r++;

		// This is to declare a Left Menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the top menu entry
		//							'type'=>'left',			// This is a Left menu entry
		//							'titre'=>'Title left menu',
		//							'mainmenu'=>'mymodule',
		//							'url'=>'/comm/action/index2.php',
		//							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'perms'=>'$user->rights->mymodule->level1->level2',		// Use 'perms'=>'1' if you want your menu with no permission rules
		//							'target'=>'',
		//							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		// $r++;
	}


    /**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
     */
	function init($options='')
  	{
    	$sql = array();

		// Remove permissions and default values
		$this->remove($options);

    	return $this->_init($sql,$options);
  	}
}
