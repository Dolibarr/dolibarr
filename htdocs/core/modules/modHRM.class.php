<?php
/* Copyright (C) 2015-2021  Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * \defgroup   HRM 	Module hrm
 * \file       htdocs/core/modules/modHRM.class.php
 * \ingroup    HRM
 * \brief      Description and activation file for the module HRM
 */
include_once DOL_DOCUMENT_ROOT."/core/modules/DolibarrModules.class.php";


/**
 *		Description and activation class for module HRM
 */
class modHRM extends DolibarrModules
{
	/**
	 *  Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 4000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'hrm';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "hr";
		$this->module_position = '50';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "HRM";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'experimental';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'hrm';

		// Define some features supported by module (triggers, login, substitutions, menus, css, etc...)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory (core/triggers)
			'triggers' => 0,
			// Set this to 1 if module has its own login method file (core/login)
			'login' => 0,
			// Set this to 1 if module has its own substitution function file (core/substitutions)
			'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory (core/menus)
			'menus' => 0,
			// Set this to 1 if module overwrite template dir (core/tpl)
			'tpl' => 0,
			// Set this to 1 if module has its own barcode directory (core/modules/barcode)
			'barcode' => 0,
			// Set this to 1 if module has its own models directory (core/modules/xxx)
			'models' => 1,
			// Set this to 1 if module has its own printing directory (core/modules/printing)
			'printing' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(),

			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/hrm/temp","/hrm/subdir");
		$this->dirs = array("/hrm/temp");

		// Config pages. Put here list of php page, stored into hrm/admin directory, to use to setup module.
		$this->config_page_url = array("hrm.php");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, 0); // Minimum version of Dolibarr required by module
		$this->langfiles = array("hrm");

		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		//                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',0)
		$this->const = array(); // List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')
		$r = 0;


		if (!isset($conf->hrm) || !isset($conf->hrm->enabled)) {
			$conf->hrm = new stdClass();
			$conf->hrm->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		$this->tabs[] = array('data'=>'user:+skill_tab:Skills:hrm:1:/hrm/skill_tab.php?id=__ID__&objecttype=user');  					// To add a new tab identified by code tabname1
		//$this->tabs[] = array('data'=>'job:+tabname1:Poste:mylangfile@hrm:1:/hrm/poste_list.php?fk_job=__ID__');  					// To add a new tab identified by code tabname1
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@hrm:$user->rights->hrm->read:/hrm/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@hrm:$user->rights->othermodule->read:/hrm/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
		// $this->tabs[] = array('data'=>'objecttype:-tabname:NU:conditiontoremove');                                                     										// To remove an existing tab identified by code tabname
		//
		// Where objecttype can be
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		// 'contact'          to add a tab in contact view
		// 'contract'         to add a tab in contract view
		// 'group'            to add a tab in group view
		// 'intervention'     to add a tab in intervention view
		// 'invoice'          to add a tab in customer invoice view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'member'           to add a tab in fundation member view
		// 'opensurveypoll'	  to add a tab in opensurvey poll view
		// 'order'            to add a tab in customer order view
		// 'order_supplier'   to add a tab in supplier order view
		// 'payment'		  to add a tab in payment view
		// 'payment_supplier' to add a tab in supplier payment view
		// 'product'          to add a tab in product view
		// 'propal'           to add a tab in propal view
		// 'project'          to add a tab in project view
		// 'stock'            to add a tab in stock view
		// 'thirdparty'       to add a tab in third party view
		// 'user'             to add a tab in user view


		// Dictionaries
		$this->dictionaries = array();

		// Boxes/Widgets
		// Add here list of php file(s) stored in hrm/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'hrmwidget1.php@hrm',
			//      'note' => 'Widget provided by HrmTest',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/hrm/class/poste.class.php',
			//      'objectname' => 'Poste',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->hrm->enabled',
			//      'priority' => 50,
			//  ),
		);


		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		// Skill / Job / Position
		$this->rights[$r][0] = 4001; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read skill/job/position'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->hrm->all->read)
		$r++;

		$this->rights[$r][0] = 4002; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/modify skill/job/position'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->hrm->all->write)
		$r++;

		$this->rights[$r][0] = 4003; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete skill/job/position'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'all';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->hrm->all->delete)
		$r++;

		// Evaluation
		$this->rights[$r][0] = 4021; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read evaluations'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'evaluation';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->hrm->evaluation->read)
		$r++;

		$this->rights[$r][0] = 4022; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/modify your evaluation'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'evaluation';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->hrm->evaluation->write)
		$r++;

		$this->rights[$r][0] = 4023; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Validate evaluation'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'evaluation_advance';
		$this->rights[$r][5] = 'validate'; // In php code, permission will be checked by test if ($user->rights->hrm->evaluation->validate)
		$r++;

		$this->rights[$r][0] = 4025; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete evaluations'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'evaluation';
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->hrm->evaluation->delete)
		$r++;

		// Comparison
		$this->rights[$r][0] = 4028; // Permission id (must not be already used)
		$this->rights[$r][1] = 'See comparison menu'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'compare_advance';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->hrm->compare_advance->read)
		$r++;

		// Read employee
		$this->rights[$r][0] = 4031; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read personal information'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read_personal_information';
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->hrm->read_personal_information->read)
		$r++;

		// Write employee
		$this->rights[$r][0] = 4032; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Write personal information'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write_personal_information';
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->hrm->write_personal_information->write)
		$r++;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'newboxdefonly', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		// Permissions
		$this->remove($options);

		$result = $this->_load_tables('/install/mysql/', 'hrm');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		$sql = array();

		return $this->_init($sql, $options);
	}
}
