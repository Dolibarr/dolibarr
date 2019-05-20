<?php
/* Copyright (C) 2003       Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2019       Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 *  \file       htdocs/core/modules/modIntracommreport.class.php
 * 	\ingroup    Intracomm report
 *	\brief      Module to activate intracomm report double entry accounting module
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module intracommreport
 */
class modIntracommreport extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
        global $conf, $langs;

        $this->db = $db;
        $this->numero = 68000;

        $this->family = "financial";
        $this->module_position = '100';
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        $this->description = "Intracomm report management (Support for French DEB/DES format)";

        // Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
        $this->version = 'dolibarr';

        $this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
        $this->picto = 'intracommreport';

        // Data directories to create when module is enabled
        $this->dirs = array('/intracommreport/temp');

        // Config pages
        $this->config_page_url = array("intracommreport.php@intracommreport");

		// Dependencies
		$this->depends = array("modFacture","modTax");  // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();                    // List of modules id to disable if this one is disabled
		$this->conflictwith = array();                  // List of modules id this module is in conflict with
		$this->phpmin = array(5,5);                     // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(9,0);      // Minimum version of Dolibarr required by module
		$this->langfiles = array("intracommreport");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();

        // Tabs
        $this->tabs = array();

        // Css
        $this->module_parts = array();

        // Boxes
        $this->boxes = array();

        // Dictionaries
	    if (! isset($conf->intracommreport->enabled))
        {
        	$conf->intracommreport=new stdClass();
        	$conf->intracommreport->enabled=0;
        }
		$this->dictionaries=array();

        // Permissions
        $this->rights_class = 'intracommreport';

        $this->rights = array(); // Permission array used by this module
        $r = 0;

		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;
		
		$langs->load('intracommreport');
		
		$this->menu[$r]=array('fk_menu'=>0,			// Put 0 if this is a top menu
				'type'=>'top',			// This is a Top menu entry
				'titre'=>$langs->trans('intracommreportDouane'),
				'mainmenu'=>'intracommreport',
				'leftmenu'=>'',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
				'url'=>'/intracommreport/export.php',
				'langs'=>'intracommreport@intracommreport',
				'position'=>100,
				'enabled'=>'$conf->intracommreport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
				'perms'=>1,			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
				'target'=>'',
				'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=intracommreport',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('intracommreportDEB'),
					'mainmenu'=>'intracommreport',
					'leftmenu'=>'intracommreport',
					'url'=>'/intracommreport/export.php',
					'position'=>100+$r,
					'enabled'=>'$conf->intracommreport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>1,			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
	   
        $this->menu[$r]=array('fk_menu'=>'fk_mainmenu=intracommreport,fk_leftmenu=intracommreport',         // Put 0 if this is a top menu
                    'type'=>'left',         // This is a Top menu entry
                    'titre'=>$langs->trans('intracommreportNew'),
                    'mainmenu'=>'intracommreport',
                    'leftmenu'=>'intracommreportNew',
                 	'url'=>'/intracommreport/export.php',
                    'position'=>100+$r,
                    'enabled'=>'$conf->intracommreport->enabled',           // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                    'perms'=>1,          // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                    'target'=>'',
                    'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
        $r++;
        
        $this->menu[$r]=array('fk_menu'=>'fk_mainmenu=intracommreport,fk_leftmenu=intracommreport',         // Put 0 if this is a top menu
                    'type'=>'left',         // This is a Top menu entry
                    'titre'=>$langs->trans('intracommreportList'),
                    'mainmenu'=>'intracommreport',
                    'leftmenu'=>'intracommreportList',
                    'url'=>'/intracommreport/export.php?action=list',
                    'position'=>100+$r,
                    'enabled'=>'$conf->intracommreport->enabled',            // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                    'perms'=>1,          // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                    'target'=>'',
                    'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
        $r++;
		
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=intracommreport',			// Put 0 if this is a top menu
					'type'=>'left',			// This is a Top menu entry
					'titre'=>$langs->trans('intracommreportDES'),
					'mainmenu'=>'intracommreport',
					'leftmenu'=>'exportprodes',
					'url'=>'/intracommreport/export.php?exporttype=des',
					'position'=>100+$r,
					'enabled'=>'$conf->intracommreport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
					'perms'=>1,			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
					'target'=>'',
					'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		
		$this->menu[$r]=array('fk_menu'=>'fk_mainmenu=intracommreport,fk_leftmenu=exportprodes',         // Put 0 if this is a top menu
                    'type'=>'left',         // This is a Top menu entry
                    'titre'=>$langs->trans('exportprodesNew'),
                    'mainmenu'=>'intracommreport',
                    'leftmenu'=>'exportprodes_new',
                 	'url'=>'/intracommreport/export.php?exporttype=des',
                    'position'=>100+$r,
                    'enabled'=>'$conf->intracommreport->enabled',           // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                    'perms'=>1,          // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                    'target'=>'',
                    'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
        $r++;
        
        $this->menu[$r]=array('fk_menu'=>'fk_mainmenu=intracommreport,fk_leftmenu=exportprodes',         // Put 0 if this is a top menu
                    'type'=>'left',         // This is a Top menu entry
                    'titre'=>$langs->trans('exportprodesList'),
                    'mainmenu'=>'intracommreport',
                    'leftmenu'=>'exportprodes_list',
                    'url'=>'/intracommreport/export.php?exporttype=des&action=list',
                    'position'=>100+$r,
                    'enabled'=>'$conf->intracommreport->enabled',            // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
                    'perms'=>1,          // Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
                    'target'=>'',
                    'user'=>2);             // 0=Menu for internal users, 1=external users, 2=both
        $r++;
		
		// Exports
		$r=1;

	}
}
