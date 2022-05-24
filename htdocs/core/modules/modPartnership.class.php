<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2021  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021 Dorian Laurent <i.merraha@sofimedmaroc.com>
 * Copyright (C) 2021 NextGestion <contact@nextgestion.com>
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
 * 	\defgroup   partnership     Module Partnership
 *  \brief      Partnership module descriptor.
 *
 *  \file       htdocs/core/modules/modPartnership.class.php
 *  \ingroup    partnership
 *  \brief      Description and activation file for module Partnership
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Partnership
 *  This module is base on this specification :
 *  https://wiki.dolibarr.org/index.php?title=Draft:Module_Partnership_management#Note
 */
class modPartnership extends DolibarrModules
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
		$this->numero = 58000;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'partnership';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "crm";

		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';

		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModulePartnershipName' not found (Partnership is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));

		// Module description, used if translation string 'ModulePartnershipDesc' not found (Partnership is name of module).
		$this->description = "PartnershipDescription";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "PartnershipDescriptionLong";

		// // Author
		// $this->editor_name = 'Editor name';
		// $this->editor_url = 'https://www.example.com';

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'dolibarr';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where PARTNERSHIP is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		// To use a supported fa-xxx css style of font awesome, use this->picto='xxx'
		$this->picto = 'partnership';

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
			'css' => array(
				//    '/partnership/css/partnership.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/partnership/js/partnership.js.php',
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
		// Example: this->dirs = array("/partnership/temp","/partnership/subdir");
		$this->dirs = array("/partnership/temp");

		// Config pages. Put here list of php page, stored into partnership/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@partnership");

		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always1'=>'modModuleToEnable1','always2'=>'modModuleToEnable2', 'FR1'=>'modModuleToEnableFR'...)
		$this->depends = array();
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)

		// The language file dedicated to your module
		$this->langfiles = array("partnership");

		// Prerequisites
		$this->phpmin = array(5, 6); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(11, -3); // Minimum version of Dolibarr required by module

		// Messages at activation
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'PartnershipWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('PARTNERSHIP_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('PARTNERSHIP_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->partnership) || !isset($conf->partnership->enabled)) {
			$conf->partnership = new stdClass();
			$conf->partnership->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();

		$tabtoadd = getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty');
		if ($tabtoadd == 'member') {
			$fk_mainmenu = "members";
		} else {
			$fk_mainmenu = "companies";
		}

		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@partnership:$user->rights->partnership->read:/partnership/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@partnership:$user->rights->othermodule->read:/partnership/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		$this->dictionaries=array(
			'langs'=>'partnership@partnership',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array("c_partnership_type"),
			// Label of tables
			'tablib'=>array("PartnershipType"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.keyword, f.active FROM '.MAIN_DB_PREFIX.'c_partnership_type as f WHERE f.entity = '.((int) $conf->entity)),
			// Sort order
			'tabsqlsort'=>array("label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,label,keyword"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,label,keyword"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,label,keyword"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->partnership->enabled),
			// Help tooltip for each fields of the dictionary
			'tabhelp'=>array(array('keyword'=>$langs->trans('KeywordToCheckInWebsite')))
		);

		// Boxes/Widgets
		// Add here list of php file(s) stored in partnership/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'partnershipwidget1.php@partnership',
			//      'note' => 'Widget provided by Partnership',
			//      'enabledbydefaulton' => 'Home',
			//  ),
			//  ...
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week

		$arraydate=dol_getdate(dol_now());
		$datestart=dol_mktime(21, 15, 0, $arraydate['mon'], $arraydate['mday'], $arraydate['year']);

		$this->cronjobs = array(
			0	=> array('priority'=>60, 'label'=>'CancelPartnershipForExpiredMembers', 'jobtype'=>'method', 'class'=>'/partnership/class/partnershiputils.class.php', 'objectname'=>'PartnershipUtils', 'method'=>'doCancelStatusOfMemberPartnership', 'parameters'=>'',      'comment'=>'Cancel status of partnership when subscription is expired + x days.', 'frequency'=>1, 'unitfrequency'=>86400, 'status'=>1, 'test'=>'$conf->partnership->enabled', 'datestart'=>$datestart),
			1	=> array('priority'=>61, 'label'=>'PartnershipCheckBacklink', 'jobtype'=>'method', 'class'=>'/partnership/class/partnershiputils.class.php', 'objectname'=>'PartnershipUtils', 'method'=>'doWarningOfPartnershipIfDolibarrBacklinkNotfound', 'parameters'=>'',      'comment'=>'Warning of partnership if Dolibarr backlink not found on partner website.', 'frequency'=>1, 'unitfrequency'=>86400, 'status'=>0, 'test'=>'$conf->partnership->enabled', 'datestart'=>$datestart),
		);

		// Permissions provided by this module
		$this->rights = array();
		$r = 0;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read objects of Partnership'; // Permission label
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->partnership->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update objects of Partnership'; // Permission label
		$this->rights[$r][4] = 'write'; // In php code, permission will be checked by test if ($user->rights->partnership->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete objects of Partnership'; // Permission label
		$this->rights[$r][4] = 'delete'; // In php code, permission will be checked by test if ($user->rights->partnership->level1->level2)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		// $this->menu[$r++]=array(
		//     // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
		//     'fk_menu'=>'fk_mainmenu=partnership',
		//     // This is a Left menu entry
		//     'type'=>'left',
		//     'titre'=>'List Partnership',
		//     'mainmenu'=>'partnership',
		//     'leftmenu'=>'partnership',
		//     'url'=>'/partnership/partnership_list.php',
		//     // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//     'langs'=>'partnership@partnership',
		//     'position'=>1100+$r,
		//     // Define condition to show or hide menu entry. Use '$conf->partnership->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
		//     'enabled'=>'$conf->partnership->enabled',
		//     // Use 'perms'=>'$user->rights->partnership->level1->level2' if you want your menu with a permission rules
		//     'perms'=>'1',
		//     'target'=>'',
		//     // 0=Menu for internal users, 1=external users, 2=both
		//     'user'=>2,
		// );
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu='.$fk_mainmenu, // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Top menu entry
			'titre'=>'Partnership',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'mainmenu'=>$fk_mainmenu,
			'leftmenu'=>'partnership',
			'url'=>'/partnership/partnership_list.php',
			'langs'=>'partnership', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100 + $r,
			'enabled'=>'$conf->partnership->enabled', // Define condition to show or hide menu entry. Use '$conf->partnership->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->partnership->read', // Use 'perms'=>'$user->rights->partnership->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu='.$fk_mainmenu.',fk_leftmenu=partnership', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'NewPartnership',
			'mainmenu'=>$fk_mainmenu,
			'leftmenu'=>'partnership_new',
			'url'=>'/partnership/partnership_card.php?action=create',
			'langs'=>'partnership', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100 + $r,
			'enabled'=>'$conf->partnership->enabled', // Define condition to show or hide menu entry. Use '$conf->partnership->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->partnership->write', // Use 'perms'=>'$user->rights->partnership->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++] = array(
			'fk_menu'=>'fk_mainmenu='.$fk_mainmenu.',fk_leftmenu=partnership', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left', // This is a Left menu entry
			'titre'=>'ListOfPartnerships',
			'mainmenu'=>$fk_mainmenu,
			'leftmenu'=>'partnership_list',
			'url'=>'/partnership/partnership_list.php',
			'langs'=>'partnership', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1100 + $r,
			'enabled'=>'$conf->partnership->enabled', // Define condition to show or hide menu entry. Use '$conf->partnership->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->partnership->read', // Use 'perms'=>'$user->rights->partnership->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);
		/* END MODULEBUILDER LEFTMENU PARTNERSHIP */
		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT PARTNERSHIP */
		/*
		$langs->load("partnership@partnership");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='PartnershipLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='partnership@partnership';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'Partnership'; $keyforclassfile='/partnership/class/partnership.class.php'; $keyforelement='partnership@partnership';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'PartnershipLine'; $keyforclassfile='/partnership/class/partnership.class.php'; $keyforelement='partnershipline@partnership'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='partnership'; $keyforaliasextra='extra'; $keyforelement='partnership@partnership';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='partnershipline'; $keyforaliasextra='extraline'; $keyforelement='partnershipline@partnership';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('partnershipline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'partnership as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'partnership_line as tl ON tl.fk_partnership = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('partnership').')';
		$r++; */
		/* END MODULEBUILDER EXPORT PARTNERSHIP */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT PARTNERSHIP */
		/*
		 $langs->load("partnership@partnership");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='PartnershipLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='partnership@partnership';
		 $keyforclass = 'Partnership'; $keyforclassfile='/partnership/class/partnership.class.php'; $keyforelement='partnership@partnership';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='partnership'; $keyforaliasextra='extra'; $keyforelement='partnership@partnership';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'partnership as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('partnership').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT PARTNERSHIP */
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

		$result = $this->_load_tables('/install/mysql/', 'partnership');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Create extrafields during init
		//include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		//$extrafields = new ExtraFields($this->db);
		//$result1=$extrafields->addExtraField('partnership_myattr1', "New Attr 1 label", 'boolean', 1,  3, 'thirdparty',   0, 0, '', '', 1, '', 0, 0, '', '', 'partnership@partnership', '$conf->partnership->enabled');
		//$result2=$extrafields->addExtraField('partnership_myattr2', "New Attr 2 label", 'varchar', 1, 10, 'project',      0, 0, '', '', 1, '', 0, 0, '', '', 'partnership@partnership', '$conf->partnership->enabled');
		//$result3=$extrafields->addExtraField('partnership_myattr3', "New Attr 3 label", 'varchar', 1, 10, 'bank_account', 0, 0, '', '', 1, '', 0, 0, '', '', 'partnership@partnership', '$conf->partnership->enabled');
		//$result4=$extrafields->addExtraField('partnership_myattr4', "New Attr 4 label", 'select',  1,  3, 'thirdparty',   0, 1, '', array('options'=>array('code1'=>'Val1','code2'=>'Val2','code3'=>'Val3')), 1,'', 0, 0, '', '', 'partnership@partnership', '$conf->partnership->enabled');
		//$result5=$extrafields->addExtraField('partnership_myattr5', "New Attr 5 label", 'text',    1, 10, 'user',         0, 0, '', '', 1, '', 0, 0, '', '', 'partnership@partnership', '$conf->partnership->enabled');

		// Permissions
		$this->remove($options);

		$sql = array();

		// Document templates
		$moduledir = dol_sanitizeFileName('partnership');
		$myTmpObjects = array();
		$myTmpObjects['Partnership'] = array('includerefgeneration'=>0, 'includedocgeneration'=>0);

		foreach ($myTmpObjects as $myTmpObjectKey => $myTmpObjectArray) {
			if ($myTmpObjectKey == 'Partnership') {
				continue;
			}
			if ($myTmpObjectArray['includerefgeneration']) {
				$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/'.$moduledir.'/template_partnerships.odt';
				$dirodt = DOL_DATA_ROOT.'/doctemplates/'.$moduledir;
				$dest = $dirodt.'/template_partnerships.odt';

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
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'standard_".strtolower($myTmpObjectKey)."' AND type = '".strtolower($myTmpObjectKey)."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('standard_".strtolower($myTmpObjectKey)."','".strtolower($myTmpObjectKey)."',".((int) $conf->entity).")",
					"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = 'generic_".strtolower($myTmpObjectKey)."_odt' AND type = '".strtolower($myTmpObjectKey)."' AND entity = ".((int) $conf->entity),
					"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('generic_".strtolower($myTmpObjectKey)."_odt', '".strtolower($myTmpObjectKey)."', ".((int) $conf->entity).")"
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
