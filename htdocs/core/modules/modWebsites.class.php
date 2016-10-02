<?php
/* Copyright (C) 2015      Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 	\defgroup   websites     Module websites
 *  \brief      Websites module descriptor.
 *  \file       htdocs/core/modules/modWebsites.class.php
 *  \ingroup    websites
 *  \brief      Description and activation file for module Websites
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe Websites module
 */
class modWebsites extends DolibarrModules
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
        $this->numero = 10000;
        
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
        $this->family = "portal";
        $this->module_position = 50;
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        $this->description = "Enable the public website with CMS features";
        $this->version = 'development';                        // 'experimental' or 'dolibarr' or version
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 0;
        // Name of image file used for this module.
        $this->picto='globe';

		// Data directories to create when module is enabled
		$this->dirs = array("/websites/temp");

        // Config pages
        //-------------
        $this->config_page_url = array('websites.php');

        // Dependancies
        //-------------
		$this->hidden = ! empty($conf->global->WEBSITE_MODULE_DISABLED);	// A condition to disable module
		$this->depends = array('modFckeditor');		// List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->conflictwith = array();	// List of modules id this module is in conflict with
        $this->langfiles = array("websites");

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
		$this->rights = array();		// Permission array used by this module
		$this->rights_class = 'websites';
		$r=0;

		$this->rights[$r][0] = 10001;
		$this->rights[$r][1] = 'Read website content';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = 10002;
		$this->rights[$r][1] = 'Create/modify website content';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = 10003;
		$this->rights[$r][1] = 'Delete website content';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$r++;

        // Main menu entries
        $r=0;
        $this->menu[$r]=array(	'fk_menu'=>'0',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
						        'type'=>'top',			                // This is a Left menu entry
						        'titre'=>'Websites',
                                'mainmenu'=>'websites',
						        'url'=>'/websites/index.php',
						        'langs'=>'websites',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
						        'position'=>100,
						        'enabled'=>'$conf->websites->enabled',  		// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
						        'perms'=>'$user->rights->websites->read',	// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
						        'target'=>'',
						        'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;
    }
}
