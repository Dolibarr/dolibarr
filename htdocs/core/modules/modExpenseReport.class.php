<?php
/* Copyright (C) 2011 Dimitri Mouillard   <dmouillard@teclib.com>
 * Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * 		\defgroup   expensereport	Module expensereport
 *      \brief      Module to manage expense report. Replace old module Deplacement.
 */

/**
 *      \file       htdocs/core/modules/modExpenseReport.class.php
 *      \ingroup    expensereport
 *      \brief      Description and activation file for module ExpenseReport
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *	Description and activation class for module ExpenseReport
 */
class modExpenseReport extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param		Database	$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 770;

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "hr";
		$this->module_position = 40;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Manage and claim expense reports (transportation, meal, ...)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='trip';

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
		$this->triggers = 0;

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();
		$r=0;

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array('expensereport.php');

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		// $this->conflictwith = array("modDeplacement"); // Deactivate for access on old information
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,7);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("companies","trips");

		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		//                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',0)
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r=0;

		$this->const[$r][0] = "EXPENSEREPORT_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "standard";
		$this->const[$r][3] = 'Name of manager to build PDF expense reports documents';
		$this->const[$r][4] = 0;
		$r++;

		// Array to add new pages in new tabs
		$this->tabs = array();
		// where entity can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view


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
		$this->rights_class = 'expensereport';

		$this->rights[1][0] = 771;
		$this->rights[1][1] = 'Read expense reports (yours and your subordinates)';
		$this->rights[1][2] = 'r';
		$this->rights[1][3] = 1;
		$this->rights[1][4] = 'lire';

		$this->rights[3][0] = 772;
		$this->rights[3][1] = 'Create/modify expense reports';
		$this->rights[3][2] = 'w';
		$this->rights[3][3] = 0;
		$this->rights[3][4] = 'creer';

		$this->rights[4][0] = 773;
		$this->rights[4][1] = 'Delete expense reports';
		$this->rights[4][2] = 'd';
		$this->rights[4][3] = 0;
		$this->rights[4][4] = 'supprimer';

		$this->rights[2][0] = 774;
		$this->rights[2][1] = 'Read all expense reports';
		$this->rights[2][2] = 'r';
		$this->rights[2][3] = 1;
		$this->rights[2][4] = 'readall';

		$this->rights[6][0] = 775;
		$this->rights[6][1] = 'Approve expense reports';
		$this->rights[6][2] = 'w';
		$this->rights[6][3] = 0;
		$this->rights[6][4] = 'approve';

		$this->rights[7][0] = 776;
		$this->rights[7][1] = 'Pay expense reports';
		$this->rights[7][2] = 'w';
		$this->rights[7][3] = 0;
		$this->rights[7][4] = 'to_paid';

		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))
		{
			$this->rights[8][0] = 777;
			$this->rights[8][1] = 'Synchroniser les NDF avec un compte courant';
			$this->rights[8][2] = 'w';
			$this->rights[8][3] = 0;
			$this->rights[8][4] = 'synchro';

			$this->rights[9][0] = 778;
			$this->rights[9][1] = 'Exporter les NDF au format CSV';
			$this->rights[9][2] = 'r';
			$this->rights[9][3] = 0;
			$this->rights[9][4] = 'export_csv';
		}

		$this->rights[5][0] = 779;
		$this->rights[5][1] = 'Export expense reports';
		$this->rights[5][2] = 'r';
		$this->rights[5][3] = 0;
		$this->rights[5][4] = 'export';

		// Exports
		$r=0;

		$r++;
		$this->export_code[$r]='expensereport_'.$r;
		$this->export_label[$r]='ListTripsAndExpenses';
		$this->export_icon[$r]='trip';
		$this->export_permission[$r]=array(array("expensereport","export"));
        $this->export_fields_array[$r]=array('d.rowid'=>"TripId",'d.ref'=>'Ref','d.date_debut'=>'DateStart','d.date_fin'=>'DateEnd','d.date_create'=>'DateCreation','d.date_approve'=>'DateApprove','d.total_ht'=>"TotalHT",'d.total_tva'=>'TotalVAT','d.total_ttc'=>'TotalTTC','d.note_private'=>'NotePrivate','d.note_public'=>'NotePublic','u.lastname'=>'Lastname','u.firstname'=>'Firstname','u.login'=>"Login",'ed.rowid'=>'LineId','tf.code'=>'Type','ed.date'=>'Date','ed.tva_tx'=>'VATRate','ed.total_ht'=>'TotalHT','ed.total_tva'=>'TotalVAT','ed.total_ttc'=>'TotalTTC','ed.comments'=>'Comment','p.rowid'=>'ProjectId','p.ref'=>'Ref');
        $this->export_entities_array[$r]=array('u.lastname'=>'user','u.firstname'=>'user','u.login'=>'user','ed.rowid'=>'expensereport_line','ed.date'=>'expensereport_line','ed.tva_tx'=>'expensereport_line','ed.total_ht'=>'expensereport_line','ed.total_tva'=>'expensereport_line','ed.total_ttc'=>'expensereport_line','ed.comments'=>'expensereport_line','tf.code'=>'expensereport_line','p.project_ref'=>'expensereport_line','p.rowid'=>'project','p.ref'=>'project');
        $this->export_alias_array[$r]=array('d.rowid'=>"idtrip",'d.type'=>"type",'d.note_private'=>'note_private','d.note_public'=>'note_public','u.lastname'=>'name','u.firstname'=>'firstname','u.login'=>'login');
		$this->export_dependencies_array[$r]=array('expensereport_line'=>'ed.rowid','type_fees'=>'tf.rowid'); // To add unique key if we ask a field of a child to avoid the DISTINCT to discard them

		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'expensereport as d, '.MAIN_DB_PREFIX.'user as u,';
		$this->export_sql_end[$r] .=' '.MAIN_DB_PREFIX.'expensereport_det as ed LEFT JOIN '.MAIN_DB_PREFIX.'c_type_fees as tf ON ed.fk_c_type_fees = tf.id';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'projet as p ON ed.fk_projet = p.rowid';
		$this->export_sql_end[$r] .=' WHERE ed.fk_expensereport = d.rowid AND d.fk_user_author = u.rowid';
		$this->export_sql_end[$r] .=' AND d.entity IN ('.getEntity('expensereport',1).')';



		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Example to declare a Left Menu entry: fk_mainmenu=home,fk_leftmenu=modulesadmintools
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=hrm',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'TripsAndExpenses',
									'mainmenu'=>'hrm',
									'leftmenu'=>'expensereport',
									'url'=>'/expensereport/index.php',
									'langs'=>'trips',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->expensereport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->expensereport->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=expensereport',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'New',
									'mainmenu'=>'hrm',
									'leftmenu'=>'expensereport_detailnew',
									'url'=>'/expensereport/card.php?action=create',
									'langs'=>'trips',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->expensereport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->expensereport->creer',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=expensereport',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'List',
									'mainmenu'=>'hrm',
									'leftmenu'=>'expensereport_detaillist',
									'url'=>'/expensereport/list.php',
									'langs'=>'trips',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->expensereport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->expensereport->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=expensereport_detaillist',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'ListToApprove',
									'mainmenu'=>'hrm',
									'leftmenu'=>'expensereport_detaillist_approve',
									'url'=>'/expensereport/list.php?search_status=2',
									'langs'=>'trips',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->expensereport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->expensereport->approve',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=expensereport',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Statistics',
									'mainmenu'=>'hrm',
									'leftmenu'=>'expensereport_detail',
									'url'=>'/expensereport/stats/index.php',
									'langs'=>'trips',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->expensereport->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->expensereport->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;


		// Disabled, not yet stable
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=expensereport',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'ExportTripCSV',
									'mainmenu'=>'hrm',
									'leftmenu'=>'expensereport_detail',
									'url'=>'/expensereport/export_csv.php',
									'langs'=>'expensereport',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->DEPLACEMENT_TO_CLEAN',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->expensereport->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;

		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=expensereport',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
									'type'=>'left',			// This is a Left menu entry
									'titre'=>'Synchro_Compta',
									'mainmenu'=>'hrm',
									'leftmenu'=>'expensereport_detail',
									'url'=>'/expensereport/synchro_compta.php',
									'langs'=>'expensereport',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
									'position'=>100,
									'enabled'=>'$conf->global->DEPLACEMENT_TO_CLEAN',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
									'perms'=>'$user->rights->expensereport->lire',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
									'target'=>'',
									'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories.
	 *
	 *	@param		string	$options	Options
	 *	@return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Remove permissions and default values
		$this->remove($options);

		$sql = array(
				"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard' AND entity = ".$conf->entity,
				"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard','deplacement',".$conf->entity.")"
		);

		return $this->_init($sql,$options);
	}
}
