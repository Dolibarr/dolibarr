<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2021  Frédéric France         <frederic.france@netlogic.fr>
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
 * 	\defgroup   recruitment     Module Recruitment
 *  \brief      Recruitment module descriptor.
 *
 *  \file       htdocs/core/modules/modRecruitment.class.php
 *  \ingroup    recruitment
 *  \brief      Description and activation file for the module Recruitment
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module Recruitment
 */
class modRecruitment extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 750;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'recruitment';
		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "hr";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '44';
		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleRecruitmentName' not found (Recruitment is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleRecruitmentDesc' not found (Recruitment is name of module).
		$this->description = "Manage and follow recruitment campaign for new job positions";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Manage and follow recruitment campaign for new job positions";
		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'dolibarr';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where RECRUITMENT is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'recruitmentjobposition';
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
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/recruitment/css/recruitment.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/recruitment/js/recruitment.js.php',
			),
			// Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context to 'all'
			'hooks' => array(
				//   'data' => array(
				//       'hookcontext1',
				//       'hookcontext2',
				//   ),
				//   'entity' => '0',
			),
			// Set this to 1 if features of module are opened to external users
			'moduleforexternal' => 0,
		);
		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/recruitment/temp","/recruitment/subdir");
		$this->dirs = array("/recruitment/temp");
		// Config pages. Put here list of php page, stored into recruitment/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@recruitment");
		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = array("recruitment");
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'RecruitmentWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('RECRUITMENT_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('RECRUITMENT_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$r = 0;
		$this->const[$r][0] = "RECRUITMENT_RECRUITMENTJOBPOSITION_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_recruitmentjobposition_standard";
		$this->const[$r][3] = 'Name of manager to generate recruitment job position ref number';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "RECRUITMENT_RECRUITMENTCANDIDATURE_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_recruitmentcandidature_standard";
		$this->const[$r][3] = 'Name of manager to generate recruitment candidature ref number';
		$this->const[$r][4] = 0;
		$r++;

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->recruitment) || !isset($conf->recruitment->enabled)) {
			$conf->recruitment = new stdClass();
			$conf->recruitment->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@recruitment:$user->rights->recruitment->read:/recruitment/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@recruitment:$user->rights->othermodule->read:/recruitment/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		// Add here list of php file(s) stored in recruitment/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'recruitmentwidget1.php@recruitment',
			//      'note' => 'Widget provided by Recruitment',
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
			//      'class' => '/recruitment/class/recruitmentjobposition.class.php',
			//      'objectname' => 'RecruitmentJobPosition',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->recruitment->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->recruitment->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->recruitment->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read job positions to fill and candidatures'; // Permission label
		$this->rights[$r][4] = 'recruitmentjobposition'; // In php code, permission will be checked by test if ($user->rights->recruitment->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->recruitment->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update job positions to fill and candidatures'; // Permission label
		$this->rights[$r][4] = 'recruitmentjobposition'; // In php code, permission will be checked by test if ($user->rights->recruitment->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->recruitment->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete Job positions to fill and candidatures'; // Permission label
		$this->rights[$r][4] = 'recruitmentjobposition'; // In php code, permission will be checked by test if ($user->rights->recruitment->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->recruitment->level1->level2)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU RECRUITMENTJOBPOSITION */
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu=hrm', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Top menu entry
			'titre'=>'Recruitment',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'mainmenu'=>'hrm',
			'leftmenu'=>'recruitmentjobposition',
			'url'=>'/recruitment/recruitmentindex.php',
			'langs'=>'recruitment', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->recruitment->enabled', // Define condition to show or hide menu entry. Use '$conf->recruitment->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->recruitment->recruitmentjobposition->read', // Use 'perms'=>'$user->rights->recruitment->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=recruitmentjobposition', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'NewPositionToBeFilled',
			'mainmenu'=>'hrm',
			'leftmenu'=>'recruitment_recruitmentjobposition_new',
			'url'=>'/recruitment/recruitmentjobposition_card.php?action=create',
			'langs'=>'recruitment', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->recruitment->enabled', // Define condition to show or hide menu entry. Use '$conf->recruitment->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->recruitment->recruitmentjobposition->write', // Use 'perms'=>'$user->rights->recruitment->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=recruitmentjobposition', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'ListOfPositionsToBeFilled',
			'mainmenu'=>'hrm',
			'leftmenu'=>'recruitment_recruitmentjobposition_list',
			'url'=>'/recruitment/recruitmentjobposition_list.php',
			'langs'=>'recruitment', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->recruitment->enabled', // Define condition to show or hide menu entry. Use '$conf->recruitment->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->recruitment->recruitmentjobposition->read', // Use 'perms'=>'$user->rights->recruitment->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=recruitmentjobposition', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'NewCandidature',
			'mainmenu'=>'hrm',
			'leftmenu'=>'recruitment_recruitmentcandidature_new',
			'url'=>'/recruitment/recruitmentcandidature_card.php?action=create',
			'langs'=>'recruitment', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->recruitment->enabled', // Define condition to show or hide menu entry. Use '$conf->recruitment->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->recruitment->recruitmentjobposition->write', // Use 'perms'=>'$user->rights->recruitment->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu=hrm,fk_leftmenu=recruitmentjobposition', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'ListOfCandidatures',
			'mainmenu'=>'hrm',
			'leftmenu'=>'recruitment_recruitmentcandidature_list',
			'url'=>'/recruitment/recruitmentcandidature_list.php',
			'langs'=>'recruitment', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->recruitment->enabled', // Define condition to show or hide menu entry. Use '$conf->recruitment->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->recruitment->recruitmentjobposition->read', // Use 'perms'=>'$user->rights->recruitment->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER LEFTMENU RECRUITMENTJOBPOSITION */

		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT RECRUITMENTJOBPOSITION */
		/*
		$langs->load("recruitment");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='RecruitmentJobPositionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='recruitmentjobposition';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'RecruitmentJobPosition'; $keyforclassfile='/mymobule/class/recruitmentjobposition.class.php'; $keyforelement='recruitmentjobposition';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'RecruitmentJobPositionLine'; $keyforclassfile='/recruitment/class/recruitmentjobposition.class.php'; $keyforelement='recruitmentjobpositionline'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='recruitmentjobposition'; $keyforaliasextra='extra'; $keyforelement='recruitmentjobposition';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='recruitmentjobpositionline'; $keyforaliasextra='extraline'; $keyforelement='recruitmentjobpositionline';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('recruitmentjobpositionline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'recruitmentjobposition as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'recruitmentjobposition_line as tl ON tl.fk_recruitmentjobposition = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('recruitmentjobposition').')';
		$r++; */
		/* END MODULEBUILDER EXPORT RECRUITMENTJOBPOSITION */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT RECRUITMENTJOBPOSITION */
		/*
		 $langs->load("recruitment");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='RecruitmentJobPositionLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='recruitmentjobposition';
		 $keyforclass = 'RecruitmentJobPosition'; $keyforclassfile='/mymobule/class/recruitmentjobposition.class.php'; $keyforelement='recruitmentjobposition';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='recruitmentjobposition'; $keyforaliasextra='extra'; $keyforelement='recruitmentjobposition';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'recruitmentjobposition as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('recruitmentjobposition').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT RECRUITMENTJOBPOSITION */
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string  $options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs;

		$result = $this->_load_tables('/install/mysql/', 'recruitment');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('recruitment_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'recruitment', '$conf->recruitment->enabled');
		//$result2=$extrafields->addExtraField('recruitment_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'recruitment', '$conf->recruitment->enabled');
		//$result3=$extrafields->addExtraField('recruitment_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'recruitment', '$conf->recruitment->enabled');
		//$result4=$extrafields->addExtraField('recruitment_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'recruitment', '$conf->recruitment->enabled');
		//$result5=$extrafields->addExtraField('recruitment_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'recruitment', '$conf->recruitment->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document template
		$moduledir = 'recruitment';
		$myTmpObjects = array();
		$myTmpObjects['RecruitmentJobPosition'] = array('includerefgeneration'=>1, 'includedocgeneration'=>1);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'MyObject') {
				continue;
			}
			if ($myTmpObjectArray['includedocgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_recruitmentjobposition.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_recruitmentjobposition.odt';

				if (file_exists($src) && !file_exists($dest)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					dol_mkdir($dirodt);
					$result = dol_copy($src, $dest, 0, 0);
					if ($result < 0) {
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
						return 0;
					}
				}

				$sql = array_merge($sql, array(
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."','".$this->db->escape(strtolower($myTmpObjectKey))."',".((int) $conf->entity).")",

					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".$this->db->escape(strtolower($myTmpObjectKey))."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".$this->db->escape(strtolower($myTmpObjectKey))."', ".((int) $conf->entity).")"
				));
			}
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *  Data directories are not deleted
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
