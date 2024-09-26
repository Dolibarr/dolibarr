<?php
/* Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2019  Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2019       Destailleur Laurent     <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Charlene Benke     		<charlene@patas-monkey.com>
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
 *  \defgroup   mrp     Module Mrp
 *  \brief      Module to manage Manufacturing Orders (MO)
 *
 *  \file       htdocs/core/modules/modMrp.class.php
 *  \ingroup    mrp
 *  \brief      Description and activation file for the module Mrp
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

/**
 *  Description and activation class for module Mrp
 */
class modMrp extends DolibarrModules
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
		$this->numero = 660;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'mrp';
		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "products";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '66';
		// Gives the possibility for the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuleMrpName' not found (Mrp is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleMrpDesc' not found (Mrp is name of module).
		$this->description = "Module to Manage Manufacturing Orders (MO)";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Module to Manage Manufacturing Orders (MO)";
		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'dolibarr';
		// Url to the file with your last numberversion of this module
		//$this->url_last_version = 'http://www.example.com/versionmodule.txt';

		// Key used in llx_const table to save module status enabled/disabled (where MRP is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'mrp';
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
			'models' => 0,
			// Set this to 1 if module has its own theme directory (theme)
			'theme' => 0,
			// Set this to relative path of css file if module has its own css file
			'css' => array(
				//    '/mrp/css/mrp.css.php',
			),
			// Set this to relative path of js file if module must load a js on all pages
			'js' => array(
				//   '/mrp/js/mrp.js.php',
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
		// Example: this->dirs = array("/mrp/temp","/mrp/subdir");
		$this->dirs = array("/mrp/temp");
		// Config pages. Put here list of php page, stored into mrp/admin directory, to use to setup module.
		$this->config_page_url = array("mrp.php");
		// Dependencies
		// A condition to hide module
		$this->hidden = false;
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR'...))
		$this->depends = array('modBom');
		$this->requiredby = array('modWorkstation'); // List of module class names as string to disable if this one is disabled. Example: array('modModuleToDisable1', ...)
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with. Example: array('modModuleToDisable1', ...)
		$this->langfiles = array("mrp");
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(8, 0); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'MrpWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(1 => array('MRP_MYNEWCONST1', 'chaine', 'myvalue', 'This is a constant to add', 1),
		//                             2 => array('MRP_MYNEWCONST2', 'chaine', 'myvalue', 'This is another constant to add', 0, 'current', 1)
		// );
		$this->const = array(
			//1=>array('MRP_MO_ADDON_PDF', 'chaine', 'vinci', 'Name of default PDF model of MO', 0),
			2=>array('MRP_MO_ADDON', 'chaine', 'mod_mo_standard', 'Name of numbering rules of MO', 0),
			3=>array('MRP_MO_ADDON_PDF_ODT_PATH', 'chaine', 'DOL_DATA_ROOT/doctemplates/mrps', '', 0)
		);

		// Some keys to add into the overwriting translation tables
		/*$this->overwrite_translation = array(
			'en_US:ParentCompany'=>'Parent company or reseller',
			'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		)*/

		if (!isset($conf->mrp) || !isset($conf->mrp->enabled)) {
			$conf->mrp = new stdClass();
			$conf->mrp->enabled = 0;
		}

		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@mrp:$user->rights->mrp->read:/mrp/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@mrp:$user->rights->othermodule->read:/mrp/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		// 'member'           to add a tab in foundation member view
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

		// Boxes/Widgets
		// Add here list of php file(s) stored in mrp/core/boxes that contains a class to show a widget.
		$this->boxes = array(
			0 => array('file' => 'box_mos.php', 'note' => '', 'enabledbydefaulton' => 'Home')
		);

		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//  0 => array(
			//      'label' => 'MyJob label',
			//      'jobtype' => 'method',
			//      'class' => '/mrp/class/mo.class.php',
			//      'objectname' => 'Mo',
			//      'method' => 'doScheduledJob',
			//      'parameters' => '',
			//      'comment' => 'Comment',
			//      'frequency' => 2,
			//      'unitfrequency' => 3600,
			//      'status' => 0,
			//      'test' => '$conf->mrp->enabled',
			//      'priority' => 50,
			//  ),
		);
		// Example: $this->cronjobs=array(
		//    0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>'$conf->mrp->enabled', 'priority'=>50),
		//    1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>'$conf->mrp->enabled', 'priority'=>50)
		// );

		// Permissions provided by this module
		$this->rights = array();
		$r = 1;
		// Add here entries to declare new permissions
		/* BEGIN MODULEBUILDER PERMISSIONS */
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read Manufacturing Order'; // Permission label
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->mrp->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->mrp->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update Manufacturing Order'; // Permission label
		$this->rights[$r][4] = 'write'; // In php code, permission will be checked by test if ($user->rights->mrp->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->mrp->level1->level2)
		$r++;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete Manufacturing Order'; // Permission label
		$this->rights[$r][4] = 'delete'; // In php code, permission will be checked by test if ($user->rights->mrp->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->mrp->level1->level2)
		$r++;
		/* END MODULEBUILDER PERMISSIONS */

		// Main menu entries to add
		$this->menu = array();
		$r = 0;
		// Add here entries to declare new menus
		/* BEGIN MODULEBUILDER TOPMENU */
		/* END MODULEBUILDER LEFTMENU MO */

		$langs->loadLangs(array("mrp", "stocks"));

		// Exports profiles provided by this module
		$r = 1;

		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='MOs';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->export_icon[$r]='mrp';
		$this->export_fields_array[$r] = array(
			'm.rowid'=>"Id",
			'm.ref'=>"Ref",
			'm.label'=>"Label",
			'm.fk_project'=>'Project',
			'm.fk_bom'=>"Bom",
			'm.date_start_planned'=>"DateStartPlanned",
			'm.date_end_planned'=>"DateEndPlanned",
			'm.fk_product'=>"Product",
			'm.status'=>'Status',
			'm.model_pdf'=>'Model',
			'm.fk_user_valid'=>'ValidatedById',
			'm.fk_user_modif'=>'ModifiedById',
			'm.fk_user_creat'=>'CreatedById',
			'm.date_valid'=>'DateValidation',
			'm.note_private'=>'NotePrivate',
			'm.note_public'=>'Note',
			'm.fk_soc'=>'Tiers',
			'e.rowid'=>'WarehouseId',
			'e.ref'=>'WarehouseRef',
			'm.qty'=>'Qty',
			'm.date_creation'=>'DateCreation',
			'm.tms'=>'DateModification'
		);
		$keyforselect = 'mrp_mo';
		$keyforelement = 'mrp_mo';
		$keyforaliasextra = 'extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		$this->export_TypeFields_array[$r] = array(
			'm.ref'=>"Text",
			'm.label'=>"Text",
			'm.fk_project'=>'Numeric',
			'm.fk_bom'=>"Numeric",
			'm.date_end_planned'=>"Date",
			'm.date_start_planned'=>"Date",
			'm.fk_product'=>"Numeric",
			'm.status'=>'Numeric',
			'm.model_pdf'=>'Text',
			'm.fk_user_valid'=>'Numeric',
			'm.fk_user_modif'=>'Numeric',
			'm.fk_user_creat'=>'Numeric',
			'm.date_valid'=>'Date',
			'm.note_private'=>'Text',
			'm.note_public'=>'Text',
			'm.fk_soc'=>'Numeric',
			'e.fk_warehouse'=>'Numeric',
			'e.ref'=>'Text',
			'm.qty'=>'Numeric',
			'm.date_creation'=>'Date',
			'm.tms'=>'Date'

		);
		$this->export_entities_array[$r] = array(); // We define here only fields that use another icon that the one defined into import_icon
		$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		$this->export_sql_end[$r]  = ' FROM '.MAIN_DB_PREFIX.'mrp_mo as m';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'mrp_mo_extrafields as extra ON m.rowid = extra.fk_object';
		$this->export_sql_end[$r] .= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot as e ON e.rowid = m.fk_warehouse';
		$this->export_sql_end[$r] .= ' WHERE m.entity IN ('.getEntity('mrp_mo').')'; // For product and service profile

		// Imports profiles provided by this module
		$r = 0;
		$langs->load("mrp");
		/* BEGIN MODULEBUILDER IMPORT MO */
		/*
		 $this->export_code[$r]=$this->rights_class.'_'.$r;
		 $this->export_label[$r]='MoLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		 $this->export_icon[$r]='mo@mrp';
		 $keyforclass = 'Mo'; $keyforclassfile='/mymobule/class/mo.class.php'; $keyforelement='mo';
		 include DOL_DOCUMENT_ROOT.'/core/commonfieldsinexport.inc.php';
		 $keyforselect='mo'; $keyforaliasextra='extra'; $keyforelement='mo';
		 include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		 //$this->export_dependencies_array[$r]=array('mysubobject'=>'ts.rowid', 't.myfield'=>array('t.myfield2','t.myfield3')); // To force to activate one or several fields if we select some fields that need same (like to select a unique key if we ask a field of a child to avoid the DISTINCT to discard them, or for computed field than need several other fields)
		 $this->export_sql_start[$r]='SELECT DISTINCT ';
		 $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'mo as t';
		 $this->export_sql_end[$r] .=' WHERE 1 = 1';
		 $this->export_sql_end[$r] .=' AND t.entity IN ('.getEntity('mo').')';
		 $r++; */
		/* END MODULEBUILDER IMPORT MO */
		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]='MOs';	// Translation key (used only if key ExportDataset_xxx_z not found)
		$this->import_icon[$r]='mrp';
		$this->import_entities_array[$r] = array(); // We define here only fields that use a different icon from the one defined in import_icon
		$this->import_tables_array[$r] = array('m'=>MAIN_DB_PREFIX.'mrp_mo', 'extra'=>MAIN_DB_PREFIX.'mrp_mo_extrafields');
		$this->import_tables_creator_array[$r] = array('m'=>'fk_user_creat'); // Fields to store import user id
		$this->import_fields_array[$r] = array(
			'm.ref' => "Ref*",
			'm.label' => "Label*",
			'm.fk_project'=>'Project',
			'm.fk_bom'=>"Bom",
			'm.date_start_planned'=>"DateStartPlanned",
			'm.date_end_planned'=>"DateEndPlanned",
			'm.fk_product'=>"Product*",
			'm.status'=>'Status',
			'm.model_pdf'=>'Model',
			'm.fk_user_valid'=>'ValidatedById',
			'm.fk_user_modif'=>'ModifiedById',
			'm.fk_user_creat'=>'CreatedById',
			'm.date_valid'=>'DateValidation',
			'm.note_private'=>'NotePrivate',
			'm.note_public'=>'Note',
			'm.fk_soc'=>'Tiers',
			'm.fk_warehouse'=>'Warehouse',
			'm.qty'=>'Qty*',
			'm.date_creation'=>'DateCreation',
			'm.tms'=>'DateModification',
		);
		$import_sample = array();

		// Add extra fields
		$import_extrafield_sample = array();
		$sql = "SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'mrp_mo' AND entity IN (0, ".$conf->entity.")";
		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$fieldname = 'extra.'.$obj->name;
				$fieldlabel = ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname] = $fieldlabel.($obj->fieldrequired ? '*' : '');
				$import_extrafield_sample[$fieldname] = $fieldlabel;
			}
		}
		// End add extra fields

		$this->import_fieldshidden_array[$r] = array('extra.fk_object' => 'lastrowid-'.MAIN_DB_PREFIX.'mrp_mo');
		/*$this->import_regex_array[$r] = array(
			'm.ref' => ''
		);*/

		$this->import_examplevalues_array[$r] = array_merge($import_sample, $import_extrafield_sample);
		$this->import_updatekeys_array[$r] = array('m.ref' => 'Ref');
		$this->import_convertvalue_array[$r] = array(
			'm.fk_product' => array(
				'rule'    => 'fetchidfromref',
				'file'    => '/product/class/product.class.php',
				'class'   => 'Product',
				'method'  => 'fetch',
				'element' => 'Product'
			),
			'm.fk_warehouse' => array(
				'rule'    => 'fetchidfromref',
				'file'    => '/product/stock/class/entrepot.class.php',
				'class'   => 'Entrepot',
				'method'  => 'fetch',
				'element' => 'Warehouse'
			),
			'm.fk_user_valid' => array(
				'rule'    => 'fetchidfromref',
				'file'    => '/user/class/user.class.php',
				'class'   => 'User',
				'method'  => 'fetch',
				'element' => 'user'
			),
			'm.fk_user_modif' => array(
				'rule'    => 'fetchidfromref',
				'file'    => '/user/class/user.class.php',
				'class'   => 'User',
				'method'  => 'fetch',
				'element' => 'user'
			),
		);
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

		// Create tables of module at module activation
		$result = $this->_load_tables('/install/mysql/', 'mrp');

		// Permissions
		$this->remove($options);

		$sql = array();

		// ODT template
		$src = DOL_DOCUMENT_ROOT.'/install/doctemplates/mrps/template_mo.odt';
		$dirodt = DOL_DATA_ROOT.'/doctemplates/mrps';
		$dest = $dirodt.'/template_mo.odt';

		if (file_exists($src) && !file_exists($dest)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_mkdir($dirodt);
			$result = dol_copy($src, $dest, '0', 0);
			if ($result < 0) {
				$langs->load("errors");
				$this->error = $langs->trans('ErrorFailToCopyFile', $src, $dest);
				return 0;
			}
		}

		$sql = array(
			"DELETE FROM ".MAIN_DB_PREFIX."document_model WHERE nom = '".$this->db->escape('vinci')."' AND type = 'mrp' AND entity = ".((int) $conf->entity),
			"INSERT INTO ".MAIN_DB_PREFIX."document_model (nom, type, entity) VALUES('".$this->db->escape('vinci')."','mrp',".((int) $conf->entity).")"
		);

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
