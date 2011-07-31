<?php
/* Copyright (C) 2010-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**     \defgroup   memcached		Module Memcached
 *      \brief      Module for memcached server
 *		\version	$Id: modMemcached.class.php,v 1.8 2011/01/22 10:18:00 eldy Exp $
 */

/**
 *       \file       htdocs/includes/modules/modMemcached.class.php
 *       \ingroup    ftp
 *       \brief      Description and activation file for module Memcached
 */

include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modMemcached
 *      \brief      Description and activation class for module Memcached
 */
class modMemcached extends DolibarrModules
{

   /**
    *   \brief      Constructor. Define names, constants, directories, boxes, permissions
    *   \param      DB      Database handler
    */
	function modMemcached($DB)
	{
		$this->db = $DB;

		// Id for module (must be unique).
		// Use here a free id.
		$this->numero = 20100;

		// Family can be 'crm','financial','hr','projects','product','ecm','technic','other'
		// It is used to sort modules in module setup page
		$this->family = "other";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (XXX is id value)
		$this->description = "Use a memcached server to increase Dolibarr speed (need PHP functions Memcached or Memcache)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '3.0';
		// Key used in llx_const table to save module status enabled/disabled (XXX is id value)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 1;
		// Name of png file (without png) used for this module
		$this->picto='technic';
                $this->moddir="memcache";

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admin directory used to setup module
		$this->config_page_url = array('memcached.php@memcached');

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
        $this->phpmin = array(4,3);                 // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,0,-2);  // Minimum version of Dolibarr required by module
        $this->langfiles = array("memcached@memcached");

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
		$this->rights_class = 'memcached';	// Permission key
		$this->rights = array();		// Permission array used by this module


		// Menus
		//------
		$this->menus = array();			// List of menus to add
		$r=0;

		// Top menu
		/*$this->menu[$r]=array('fk_menu'=>0,
							  'type'=>'top',
							  'titre'=>'FTP',
							  'mainmenu'=>'ftp',
							  'leftmenu'=>'0',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
							  'url'=>'/ftp/index.php',
							  'langs'=>'ftp',
							  'position'=>100,
							  'perms'=>'$user->rights->ftp->read || $user->rights->ftp->write || $user->rights->ftp->setup',
							  'enabled'=>1,
							  'target'=>'',
							  'user'=>2);			// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		*/
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
