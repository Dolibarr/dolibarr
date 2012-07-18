<?php
/* Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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

/**     \defgroup   mymodule     Module MyModule
 *      \brief      Example of a module descriptor.
 *					Such a file must be copied into htdocs/includes/module directory.
 */

/**
 *      \file       htdocs/includes/modules/modMyModule.class.php
 *      \ingroup    mymodule
 *      \brief      Description and activation file for module MyModule
 *		\version	$Id: modMyModule.class.php,v 1.26 2008/12/15 18:27:00 eldy Exp $
 */
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modMyModule
 *      \brief      Description and activation class for module MyModule
 */
class modMarges extends DolibarrModules
{
    /**
    *   \brief      Constructor. Define names, constants, directories, boxes, permissions
    *   \param      DB      Database handler
    */
	function modMarges($DB)
	{
		$this->db = $DB;
		
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 59000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'Marges';
		
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page 
		$this->family = "financial";		
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = mb_ereg_replace('^mod','',get_class($this), "i");
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Gestion des marges";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '2.0';    
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 0;
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png. 
		$this->picto='marges@marges';
		
		// Data directories to create when module is enabled.
		$this->dirs = array();
		//$this->dirs[0] = DOL_DATA_ROOT.'/Marges';
        //$this->dirs[1] = DOL_DATA_ROOT.'/mymodule/temp;
 		
		// Relative path to module style sheet if exists. Example: '/mymodule/mycss.css'.
		$this->style_sheet = '/custom/marges/css/marges.css';

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("marges.php@marges");
		
		// Dependencies
		$this->depends = array("modPropale", "modProduct");		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,1);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,1);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("marges@marges");
		
		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled
    //$this->const = array(    0=>array('MAIN_MODULE_MARGES_HOOKS', 'chaine', 'propalcard',    'Hooks list for displaying Marges data on entity lists', 0, 'current', 1)    );		
		
		// New pages on tabs
		$this->tabs = array(
			'product:+marges:Marges:marges@marges:/marges/tabs/productMargins.php?id=__ID__',
			'thirdparty:+marges:Marges:marges@marges:/marges/tabs/thirdpartyMargins.php?socid=__ID__',
		); 
		
		
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
		$this->rights = array();		// Permission array used by this module
		$r=0;
		
		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		
		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r = 0;

    // left menu entry
    $this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
    			'type'=>'top',			// This is a Top menu entry
    			'titre'=>'Margins',
    			'mainmenu'=>'margins',
    			'leftmenu'=>'1',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
    			'url'=>'/marges/index.php',
    			'langs'=>'marges@marges',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
    			'position'=>100,
    			'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->monmodule->enabled' if entry must be visible if module is enabled.
    			'perms'=>'1',			// Use 'perms'=>'$user->rights->monmodule->level1->level2' if you want your menu with a permission rules
    			'target'=>'',
    			'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
    $r++;

    // top menu entry
    $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
    			'type'=>'left',			// This is a Left menu entry
    			'titre'=>'ProductMargins',
    			'mainmenu'=>'margins',
    			'url'=>'/marges/productMargins.php',
    			'langs'=>'marges@marges',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
    			'position'=>100,
    			'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->monmodule->enabled' if entry must be visible if module is enabled.
    			'perms'=>'1',			// Use 'perms'=>'$user->rights->monmodule->level1->level2' if you want your menu with a permission rules
    			'target'=>'',
    			'user'=>2);				// 0=Menu for internal users,1=external users, 2=both
    $r++;

    $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
    			'type'=>'left',			// This is a Left menu entry
    			'titre'=>'CustomerMargins',
    			'mainmenu'=>'margins',
    			'url'=>'/marges/customerMargins.php',
    			'langs'=>'marges@marges',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
    			'position'=>200,
    			'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->monmodule->enabled' if entry must be visible if module is enabled.
    			'perms'=>'1',			// Use 'perms'=>'$user->rights->monmodule->level1->level2' if you want your menu with a permission rules
    			'target'=>'',
    			'user'=>2);				// 0=Menu for internal users,1=external users, 2=both
    $r++;

    $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
    			'type'=>'left',			// This is a Left menu entry
    			'titre'=>'AgentMargins',
    			'mainmenu'=>'margins',
    			'url'=>'/marges/agentMargins.php',
    			'langs'=>'marges@marges',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
    			'position'=>300,
    			'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->monmodule->enabled' if entry must be visible if module is enabled.
    			'perms'=>'1',			// Use 'perms'=>'$user->rights->monmodule->level1->level2' if you want your menu with a permission rules
    			'target'=>'',
    			'user'=>2);				// 0=Menu for internal users,1=external users, 2=both
    $r++;

 	}

	/**
     *		\brief      Function called when module is enabled.
     *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *					It also creates data directories.
	 *      \return     int             1 if OK, 0 if KO
     */
	function init()
  	{
    	$sql = array();
    
		$result=$this->load_tables();
	
    	return $this->_init($sql);
  	}

	/**
	 *		\brief		Function called when module is disabled.
 	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
 	 *					Data directories are not deleted.
	 *      \return     int             1 if OK, 0 if KO
 	 */
	function remove()
	{
    	$sql = array();

    	return $this->_remove($sql);
  	}

	
	/**
	*		\brief		Create tables and keys required by module
	* 					Files mymodule.sql and mymodule.key.sql with create table and create keys
	* 					commands must be stored in directory /mymodule/sql/
	*					This function is called by this->init.
	* 		\return		int		<=0 if KO, >0 if OK
	*/
	function load_tables()
	{
		return $this->_load_tables('/marges/sql/');
	}
}

?>
