<?php
/* Copyright (C) 2010-2012	Regis Houssin        <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \defgroup   workflow     Module workflow
 *      \brief		Workflow management
 *      \file       htdocs/core/modules/modWorkflow.class.php
 *      \ingroup    workflow
 *      \brief      File to describe and activate module Workflow
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


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
    public function __construct($db)
    {
        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 6000;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'workflow';

        $this->family = "technic";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i', '', get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Workflow management";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of png file (without png) used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
        $this->picto = 'technic';

        // Data directories to create when module is enabled
        $this->dirs = array("/workflow/temp");

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array('workflow.php');

        // Dependencies
        $this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 4); // Minimum version of PHP required by module
        $this->need_dolibarr_version = array(2, 8); // Minimum version of Dolibarr required by module
        $this->langfiles = array("@workflow");

        // Constants
        // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
        // Example: $this->const=array(1 => array('MYMODULE_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
        //                             2 => array('MYMODULE_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
        // );
        $this->const = array(
            //0=>array('WORKFLOW_PROPAL_AUTOCREATE_ORDER', 'chaine', '1', 'WORKFLOW_PROPAL_AUTOCREATE_ORDER', 0, 'current', 0),
            //0=>array('WORKFLOW_ORDER_AUTOCREATE_INVOICE', 'chaine', '1', 'WORKFLOW_ORDER_AUTOCREATE_INVOICE', 0, 'current', 0),
            0=>array('WORKFLOW_ORDER_CLASSIFY_BILLED_PROPAL', 'chaine', '1', 'WORKFLOW_ORDER_CLASSIFY_BILLED_PROPAL', 0, 'current', 0),
            1=>array('WORKFLOW_INVOICE_CLASSIFY_BILLED_PROPAL', 'chaine', '1', 'WORKFLOW_INVOICE_CLASSIFY_BILLED_PROPAL', 0, 'current', 0),
            2=>array('WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING', 'chaine', '1', 'WORKFLOW_ORDER_CLASSIFY_SHIPPED_SHIPPING', 0, 'current', 0),
            4=>array('WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER', 'chaine', '1', 'WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_ORDER', 0, 'current', 0),
            5=>array('WORKFLOW_ORDER_CLASSIFY_BILLED_SUPPLIER_PROPOSAL', 'chaine', '1', 'WORKFLOW_ORDER_CLASSIFY_BILLED_SUPPLIER_PROPOSAL', 0, 'current', 0),
            6=>array('WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_SUPPLIER_ORDER', 'chaine', '1', 'WORKFLOW_INVOICE_AMOUNT_CLASSIFY_BILLED_SUPPLIER_ORDER', 0, 'current', 0),
            7=>array('WORKFLOW_BILL_ON_RECEPTION', 'chaine', '1', 'WORKFLOW_BILL_ON_RECEPTION', 0, 'current', 0)
        );

        // Boxes
        $this->boxes = array();

        // Permissions
        $this->rights = array();
        $r = 0;

        /*
        $r++;
        $this->rights[$r][0] = 6001; // id de la permission
        $this->rights[$r][1] = "Lire les workflow"; // libelle de la permission
        $this->rights[$r][2] = 'r'; // type de la permission (deprecie a ce jour)
        $this->rights[$r][3] = 0; // La permission est-elle une permission par defaut
        $this->rights[$r][4] = 'read';
        */

        // Main menu entries
        $this->menus = array(); // List of menus to add
        $r = 0;
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
	 *      @return     int                 1 if OK, 0 if KO
     */
    public function init($options = '')
    {
		// Permissions
		$this->remove($options);

		$sql = array();

        return $this->_init($sql, $options);
    }
}
