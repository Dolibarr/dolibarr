<?php
/* Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010		Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \defgroup   workflow     Module workflow
 *      \brief		Workflow management
 *      \file       htdocs/core/modules/modWorkflow.class.php
 *      \ingroup    workflow
 *      \brief      File to describe and activate module Workflow
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module Workflow
 */
class modWorkflow extends DolibarrModules
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
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 6000 ;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'workflow';

        $this->family = "technic";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Workflow management";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
        $this->special = 2;
        // Name of png file (without png) used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto='technic';

        // Data directories to create when module is enabled
        $this->dirs = array("/workflow/temp");

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array('workflow.php');

        // Dependencies
        $this->depends = array();       // List of modules id that must be enabled if this module is enabled
        $this->requiredby = array();    // List of modules id to disable if this one is disabled
        $this->phpmin = array(5,2);                 // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(2,8);  // Minimum version of Dolibarr required by module
        $this->langfiles = array("@workflow");

        // Constants
        // List of particular constants to add when module is enabled
        //Example: $this->const=array(0=>array('MODULE_MY_NEW_CONST1','chaine','myvalue','This is a constant to add',0),
        //                            1=>array('MODULE_MY_NEW_CONST2','chaine','myvalue','This is another constant to add',0) );
        $this->const=array();

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r=0;

        /*
        $r++;
        $this->rights[$r][0] = 6001; // id de la permission
        $this->rights[$r][1] = "Lire les workflow"; // libelle de la permission
        $this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'read';
        */

        // Main menu entries
        $this->menus = array();         // List of menus to add
        $r=0;
        /*
        $this->menu[$r]=array('fk_menu'=>0,
                                'type'=>'top',
                                'titre'=>'Workflow',
                                'mainmenu'=>'workflow',
                                'url'=>'/workflow/index.php',
                                'langs'=>'@workflow',
                                'position'=>100,
                                'perms'=>'$user->rights->workflow->read',
                                'enabled'=>'$conf->workflow->enabled',
                                'target'=>'',
                                'user'=>0);
        $r++;

        $this->menu[$r]=array(  'fk_menu'=>'r=0',
                                'type'=>'left',
                                'titre'=>'Workflow',
                                'mainmenu'=>'workflow',
                                'url'=>'/workflow/index.php',
                                'langs'=>'@workflow',
                                'position'=>101,
                                'enabled'=>1,
                                'perms'=>'$user->rights->workflow->read',
                                'target'=>'',
                                'user'=>0);
        $r++;
        */
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
		// Permissions
		$this->remove($options);

		$sql = array();

        return $this->_init($sql,$options);
    }
}
