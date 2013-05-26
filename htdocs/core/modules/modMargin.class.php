<?php
/* Copyright (C) 2012	Christophe Battarel	<christophe.battarel@altairis.fr>
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

/**     \defgroup   margin     Module margin
 *      \brief      Module to manage margins
 *      \file       htdocs/includes/modules/modMargin.class.php
 *      \ingroup    margin
 *      \brief      Description and activation file for module Margin
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 * 	Class to describe module Margin
 */
class modMargin extends DolibarrModules
{
	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 59000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'margin';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Margin management";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 2;
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png.
		$this->picto='margin';

		// Data directories to create when module is enabled.
		$this->dirs = array('/margin/temp');

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("margin.php@margin");

		// Dependencies
		$this->depends = array("modPropale", "modProduct");		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(5,1);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,2);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("margins");

		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled

		// New pages on tabs
		$this->tabs = array(
				'product:+margin:Margins:margins:$conf->margin->enabled:/margin/tabs/productMargins.php?id=__ID__',
				'thirdparty:+margin:Margins:margins:$conf->margin->enabled && empty($user->societe_id):/margin/tabs/thirdpartyMargins.php?socid=__ID__'
		);


		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r = 0;

		// left menu entry
		$this->menu[$r]=array(
				'fk_menu'=>'fk_mainmenu=accountancy',			// Put 0 if this is a top menu
    			'type'=>'left',			// This is a Top menu entry
    			'titre'=>'Margins',
    			'mainmenu'=>'accountancy',
    			'leftmenu'=>'margins',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
    			'url'=>'/margin/index.php',
    			'langs'=>'margins',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
    			'position'=>100,
    			'enabled'=>'$conf->margin->enabled',			// Define condition to show or hide menu entry. Use '$conf->monmodule->enabled' if entry must be visible if module is enabled.
    			'perms'=>'1',			// Use 'perms'=>'$user->rights->monmodule->level1->level2' if you want your menu with a permission rules
    			'target'=>'',
    			'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
	}

	/**
     *	Function called when module is enabled.
     *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *	It also creates data directories.
     *
     *	@return     int             1 if OK, 0 if KO
     */
	function init()
  	{
    	$sql = array();

		$result=$this->load_tables();

    	return $this->_init($sql);
  	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted.
	 *
	 *	@return     int             1 if OK, 0 if KO
 	 */
	function remove()
	{
    	$sql = array();

    	return $this->_remove($sql);
  	}


	/**
	 * 	Create tables and keys required by module
	 * 	Files mymodule.sql and mymodule.key.sql with create table and create keys
	 * 	commands must be stored in directory /mymodule/sql/
	 * 	This function is called by this->init.
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
  	function load_tables()
	{
		//return $this->_load_tables();
	}
}

?>
