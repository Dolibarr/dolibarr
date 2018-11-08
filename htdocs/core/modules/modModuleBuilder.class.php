<?php
/* Copyright (C) 2017   Laurent Destailleur  <eldy@users.sourcefore.net>
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
 * 	\defgroup   modulebuilder   Module ModuleBuilder
 *  \brief      Add a log into a block chain for some actions.
 *  \file       htdocs/core/modules/modBlockedLog.class.php
 *  \ingroup    blockedlog
 *  \brief      Description and activation file for module ModuleBuilder
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 *	Class to describe a ModuleBuilder module
 */
class modModuleBuilder extends DolibarrModules
{
    /**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
    	global $langs,$conf;

        $this->db = $db;
        $this->numero = 3300;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
        $this->family = "technic";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "A RAD (Rapid Application Development) tool to help developers to build their own module.";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'experimental';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        $this->picto='technic';

        // Data directories to create when module is enabled
        $this->dirs = array();

        // Config pages
        //-------------
        $this->config_page_url = array();

        // Dependancies
        //-------------
	    $this->hidden = false;	// A condition to disable module
	    $this->depends = array();		// List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();	// List of modules id to disable if this one is disabled
	    $this->conflictwith = array();	// List of modules id this module is in conflict with
        $this->langfiles = array();

        // Constants
        //-----------


        // New pages on tabs
        // -----------------
        $this->tabs = array();

        // Boxes
        //------
        $this->boxes = array();

        // Main menu entries
        //------------------
        $this->menu = array();

        $this->menu[$r]=array('fk_menu'=>'fk_mainmenu=home,fk_leftmenu=admintools',
            'type'=>'left',
            'titre'=>'ModuleBuilder',
            'mainmenu'=>'home',
            'leftmenu'=>'admintools_modulebuilder',
            'url'=>'/modulebuilder/index.php?mainmenu=home&amp;leftmenu=admintools',
            'langs'=>'modulebuilder',
            'position'=>100,
            'perms'=>'1',
            'enabled'=>'$conf->modulebuilder->enabled && preg_match(\'/^(admintools|all)/\',$leftmenu) && ($user->admin || $conf->global->MODULEBUILDER_FOREVERYONE)',
            'target'=>'_modulebuilder',
            'user'=>0);

    }
}
