<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2022  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2021 		Gauthier VERDOL 		<gauthier.verdol@atm-consulting.fr>
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
 * 	\defgroup   stocktransfer     Module StockTransfer
 *  \brief      StockTransfer module descriptor.
 *
 *  \file       htdocs/stocktransfer/core/modules/modStockTransfer.class.php
 *  \ingroup    stocktransfer
 *  \brief      Description and activation file for module StockTransfer
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module StockTransfer
 */
class modStockTransfer extends DolibarrModules
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

		$langs->load('stocks');
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 701; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve an id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'stocktransfer';
		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "products";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '90';
		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleStockTransferName' not found (StockTransfer is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleStockTransferDesc' not found (StockTransfer is name of module).
		$this->description = $langs->trans("ModuleStockTransferDesc");
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Advanced management of stock transfer orders with generation of stock transfer sheets";
		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'experimental';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where STOCKTRANSFER is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'stock';
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
				//    '/stocktransfer/css/stocktransfer.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/stocktransfer/js/stocktransfer.js.php',
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
			'contactelement'=>1
		);
		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/stocktransfer/temp","/stocktransfer/subdir");
		$this->dirs = array("/stocktransfer/temp");
		// Config pages. Put here list of php page, stored into stocktransfer/admin directory, to use to setup module.
		$this->config_page_url = array("stocktransfer.php");
		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR'...))
		$this->depends = array('modStock', 'modProduct');
		$this->requiredby = array(); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = array("stocktransfer@stocktransfer");
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'StockTransferWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('STOCKTRANSFER_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('STOCKTRANSFER_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array();

		if (!isset($conf->stocktransfer) || !isset($conf->stocktransfer->enabled)) {
			$conf->stocktransfer = new stdClass();
			$conf->stocktransfer->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@stocktransfer:$user->rights->stocktransfer->read:/stocktransfer/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@stocktransfer:$user->rights->othermodule->read:/stocktransfer/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		// 'order'            to add a tab in sales order view
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
		/* Example:
		$this->dictionaries=array(
			'langs'=>'stocktransfer@stocktransfer',
			// List of tables we want to see into dictonnary editor
			'tabname'=>array(MAIN_DB_PREFIX."table1", MAIN_DB_PREFIX."table2", MAIN_DB_PREFIX."table3"),
			// Label of tables
			'tablib'=>array("Table1", "Table2", "Table3"),
			// Request to select fields
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f', 'SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),
			// Sort order
			'tabsqlsort'=>array("label ASC", "label ASC", "label ASC"),
			// List of fields (result of select to show dictionary)
			'tabfield'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields to edit a record)
			'tabfieldvalue'=>array("code,label", "code,label", "code,label"),
			// List of fields (list of fields for insert)
			'tabfieldinsert'=>array("code,label", "code,label", "code,label"),
			// Name of columns with primary key (try to always name it 'rowid')
			'tabrowid'=>array("rowid", "rowid", "rowid"),
			// Condition to show each dictionary
			'tabcond'=>array($conf->stocktransfer->enabled, $conf->stocktransfer->enabled, $conf->stocktransfer->enabled)
		);
		*/

		// Boxes/Widgets
		// Add here list of php file(s) stored in stocktransfer/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			//  0 => array(
			//      'file' => 'stocktransferwidget1.php@stocktransfer',
			//      'note' => 'Widget provided by StockTransfer',
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
			//      'class' => '/stocktransfer/class/stocktransfer.class.php',
			//      'objectname' => 'StockTransfer',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->stocktransfer->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->stocktransfer->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->stocktransfer->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 10;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('StockTransferRightRead'); // Permission label
		$this->rights[$r][4] = 'stocktransfer'; // In php code, permission will be checked by test if ($user->rights->stocktransfer->level1->level2)
		$this->rights[$r][5] = 'read'; // In php code, permission will be checked by test if ($user->rights->stocktransfer->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('StockTransferRightCreateUpdate'); // Permission label
		$this->rights[$r][4] = 'stocktransfer'; // In php code, permission will be checked by test if ($user->rights->stocktransfer->level1->level2)
		$this->rights[$r][5] = 'write'; // In php code, permission will be checked by test if ($user->rights->stocktransfer->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = $langs->trans('StockTransferRightDelete'); // Permission label
		$this->rights[$r][4] = 'stocktransfer'; // In php code, permission will be checked by test if ($user->rights->stocktransfer->level1->level2)
		$this->rights[$r][5] = 'delete'; // In php code, permission will be checked by test if ($user->rights->stocktransfer->level1->level2)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$langs->load('stocktransfer@stocktransfer');
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		/*$this->menu[$r++] = array(
			'fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'top', // This is a Top menu entry
			'titre'=>'ModuleStockTransferName',
			'mainmenu'=>'stocktransfer',
			'leftmenu'=>'',
			'url'=>'/stocktransfer/stocktransferindex.php',
			'langs'=>'stocktransfer@stocktransfer', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000 + $r,
			'enabled'=>'$conf->stocktransfer->enabled', // Define condition to show or hide menu entry. Use '$conf->stocktransfer->enabled' if entry must be visible if module is enabled.
			'perms'=>'1', // Use 'perms'=>'$user->rights->stocktransfer->stocktransfer->read' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2, // 0=Menu for internal users, 1=external users, 2=both
		);*/
		/* END MODULEBUILDER TOPMENU */
		/* BEGIN MODULEBUILDER LEFTMENU STOCKTRANSFER
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=stocktransfer',      // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',                          // This is a Top menu entry
			'titre'=>'StockTransfer',
			'mainmenu'=>'stocktransfer',
			'leftmenu'=>'stocktransfer',
			'url'=>'/stocktransfer/stocktransferindex.php',
			'langs'=>'stocktransfer@stocktransfer',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->stocktransfer->enabled',  // Define condition to show or hide menu entry. Use '$conf->stocktransfer->enabled' if entry must be visible if module is enabled.
			'perms'=>'$user->rights->stocktransfer->stocktransfer->read',			                // Use 'perms'=>'$user->rights->stocktransfer->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=stocktransfer,fk_leftmenu=stocktransfer',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'List StockTransfer',
			'mainmenu'=>'stocktransfer',
			'leftmenu'=>'stocktransfer_stocktransfer_list',
			'url'=>'/stocktransfer/stocktransfer_list.php',
			'langs'=>'stocktransfer@stocktransfer',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->stocktransfer->enabled',  // Define condition to show or hide menu entry. Use '$conf->stocktransfer->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->stocktransfer->stocktransfer->read',			                // Use 'perms'=>'$user->rights->stocktransfer->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=stocktransfer,fk_leftmenu=stocktransfer',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'New StockTransfer',
			'mainmenu'=>'stocktransfer',
			'leftmenu'=>'stocktransfer_stocktransfer_new',
			'url'=>'/stocktransfer/stocktransfer_card.php?action=create',
			'langs'=>'stocktransfer@stocktransfer',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>1000+$r,
			'enabled'=>'$conf->stocktransfer->enabled',  // Define condition to show or hide menu entry. Use '$conf->stocktransfer->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->stocktransfer->stocktransfer->write',			                // Use 'perms'=>'$user->rights->stocktransfer->level1->level2' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2,				                // 0=Menu for internal users, 1=external users, 2=both
		);
		*/

		/*$this->menu[$r++]=array(
			// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=stock',
			// This is a Left menu entry
			'type'=>'left',
			'titre'=>$langs->trans('StockTransferNew'),
			'mainmenu'=>'products',
			'leftmenu'=>'stocktransfer_stocktransfer',
			'url'=>'/stocktransfer/stocktransfer_card.php?action=create',
			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'langs'=>'stocktransfer@stocktransfer',
			'position'=>1100+$r,
			// Define condition to show or hide menu entry. Use '$conf->stocktransfer->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'enabled'=>'$conf->stocktransfer->enabled',
			// Use 'perms'=>'$user->rights->stocktransfer->level1->level2' if you want your menu with a permission rules
			'perms'=>'1',
			'target'=>'',
			// 0=Menu for internal users, 1=external users, 2=both
			'user'=>2
		);
		$this->menu[$r++]=array(
			// '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'fk_menu'=>'fk_mainmenu=products,fk_leftmenu=stock',
			// This is a Left menu entry
			'type'=>'left',
			'titre'=>$langs->trans('StockTransferList'),
			'mainmenu'=>'products',
			'leftmenu'=>'stocktransfer_stocktransferlist',
			'url'=>'/stocktransfer/stocktransfer_list.php',
			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'langs'=>'stocktransfer@stocktransfer',
			'position'=>1100+$r,
			// Define condition to show or hide menu entry. Use '$conf->stocktransfer->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'enabled'=>'$conf->stocktransfer->enabled',
			// Use 'perms'=>'$user->rights->stocktransfer->level1->level2' if you want your menu with a permission rules
			'perms'=>'1',
			'target'=>'',
			// 0=Menu for internal users, 1=external users, 2=both
			'user'=>2,
		);*/

		/* END MODULEBUILDER LEFTMENU STOCKTRANSFER */

		// Exports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER EXPORT STOCKTRANSFER */
		/*
		$langs->load("stocktransfer@stocktransfer");
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='StockTransferLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='stocktransfer@stocktransfer';
		// Define $this->export_fields_array, $this->export_TypeFields_array and $this->export_entities_array
		$keyforclass = 'StockTransfer'; $keyforclassfile='/stocktransfer/class/stocktransfer.class.php'; $keyforelement='stocktransfer@stocktransfer';
		include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		//$this->export_fields_array[$r]['t.fieldtoadd']='FieldToAdd'; $this->export_TypeFields_array[$r]['t.fieldtoadd']='Text';
		//unset($this->export_fields_array[$r]['t.fieldtoremove']);
		//$keyforclass = 'StockTransferLine'; $keyforclassfile='/stocktransfer/class/stocktransfer.class.php'; $keyforelement='stocktransferline@stocktransfer'; $keyforalias='tl';
		//include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		$keyforselect='stocktransfer'; $keyforaliasextra='extra'; $keyforelement='stocktransfer@stocktransfer';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$keyforselect='stocktransferline'; $keyforaliasextra='extraline'; $keyforelement='stocktransferline@stocktransfer';
		//include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		//$this->export_dependencies_array[$r] = array('stocktransferline'=>array('tl.rowid','tl.ref')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		//$this->export_special_array[$r] = array('t.field'=>'...');
		//$this->export_examplevalues_array[$r] = array('t.field'=>'Example');
		//$this->export_help_array[$r] = array('t.field'=>'FieldDescHelp');
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'stocktransfer as t';
		//$this->export_sql_end[$r]  =' LEFT JOIN '.MAIN_DB_PREFIX.'stocktransfer_line as tl ON tl.fk_stocktransfer = t.rowid';
		$this->export_sql_end[$r] .=' WHERE 1 = 1';
		$this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('stocktransfer').')';
		$r++; */
		/* END MODULEBUILDER EXPORT STOCKTRANSFER */

		// Imports profiles provided by this module
		$r = 1;
		/* BEGIN MODULEBUILDER IMPORT STOCKTRANSFER */
		/*
		 $langs->load("stocktransfer@stocktransfer");
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='StockTransferLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='stocktransfer@stocktransfer';
		 $keyforclass = 'StockTransfer'; $keyforclassfile='/stocktransfer/class/stocktransfer.class.php'; $keyforelement='stocktransfer@stocktransfer';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='stocktransfer'; $keyforaliasextra='extra'; $keyforelement='stocktransfer@stocktransfer';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'stocktransfer as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('stocktransfer').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT STOCKTRANSFER */
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
		global  $conf, $langs;

		$result = $this->_load_tables('/install/mysql/tables/', 'stocktransfer');
		if ($result < 0) {
			return -1;
		} // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')

		// Permissions
		$this->remove($options);

		$sql = array();

		// Roles
		$resql = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE code = 'STDEST' AND element = 'stocktransfer' AND source = 'internal'");
		$res = $this->db->fetch_object($resql);
		$nextid=$this->getNextId();
		if (empty($res)) {
			$this->db->query("INSERT INTO ".MAIN_DB_PREFIX."c_type_contact(rowid, element, source, code, libelle, active, module, position) VALUES(".((int) $nextid).", 'stocktransfer', 'internal', 'STRESP', 'Responsible for stock transfers', 1, NULL, 0)");
		}

		$resql = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE code = 'STFROM' AND element = 'stocktransfer' AND source = 'external'");
		$res = $this->db->fetch_object($resql);
		$nextid=$this->getNextId();
		if (empty($res)) {
			$this->db->query("INSERT INTO ".MAIN_DB_PREFIX."c_type_contact(rowid, element, source, code, libelle, active, module, position) VALUES(".((int) $nextid).", 'stocktransfer', 'external', 'STFROM', 'Contact sending the stock transfer', 1, NULL, 0)");
		}

		$resql = $this->db->query("SELECT rowid FROM ".MAIN_DB_PREFIX."c_type_contact WHERE code = 'STDEST' AND element = 'stocktransfer' AND source = 'external'");
		$res = $this->db->fetch_object($resql);
		$nextid=$this->getNextId();
		if (empty($res)) {
			$this->db->query("INSERT INTO ".MAIN_DB_PREFIX."c_type_contact(rowid, element, source, code, libelle, active, module, position) VALUES(".((int) $nextid).", 'stocktransfer', 'external', 'STDEST', 'Contact receiving the stock transfer', 1, NULL, 0)");
		}

		return $this->_init($sql, $options);
	}

	/**
	 *  Returns next available id to insert new roles in llx_c_type_contact
	 * 	@return     int		> 0 if OK, < 0 if KO
	 */
	public function getNextId()
	{
		// Get free id for insert
		$newid = 0;
		$sql = "SELECT MAX(rowid) newid from ".MAIN_DB_PREFIX."c_type_contact";
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			$newid = ($obj->newid + 1);
		} else {
			dol_print_error($this->db);
			return -1;
		}
		return $newid;
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
