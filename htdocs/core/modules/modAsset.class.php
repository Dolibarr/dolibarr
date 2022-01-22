<?php
/* Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
 * 	\defgroup   asset     Module Assets
 *  \brief      Asset module descriptor.
 *
 *  \file       htdocs/core/modules/modAsset.class.php
 *  \ingroup    asset
 *  \brief      Description and activation file for module Assets
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module FixedAssets
 */
class modAsset extends DolibarrModules
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
		$this->numero = 51000; // TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "financial";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '70';
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModuleAssetsName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleAssetsDesc' not found (MyModue is name of module).
		$this->description = "Assets module";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Assets module to manage assets module and depreciation charge on Dolibarr";

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'development';
		// Key used in llx_const table to save module status enabled/disabled (where ASSETS is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'accounting';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /asset/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /asset/core/modules/barcode)
		// for specific css file (eg: /asset/css/assets.css.php)
		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/asset/temp","/asset/subdir");
		$this->dirs = array("/asset/temp");

		// Config pages. Put here list of php page, stored into asset/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@asset");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->langfiles = array("assets");
		$this->phpmin = array(5, 4); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(7, 0); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'AssetsWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('ASSETS_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('ASSETS_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();


		if (!isset($conf->asset) || !isset($conf->asset->enabled))
		{
			$conf->asset = new stdClass();
			$conf->asset->enabled = 0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@assets:$user->rights->assets->read:/asset/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@assets:$user->rights->othermodule->read:/asset/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		// Add here list of php file(s) stored in asset/core/boxes that contains class to show a widget.
		$this->boxes = array(
			//0=>array('file'=>'assetswidget1.php@asset','note'=>'Widget provided by Assets','enabledbydefaulton'=>'Home'),
			//1=>array('file'=>'assetswidget2.php@asset','note'=>'Widget provided by Assets'),
			//2=>array('file'=>'assetswidget3.php@asset','note'=>'Widget provided by Assets')
		);


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		//$this->cronjobs = array(
		//	0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/asset/class/asset.class.php', 'objectname'=>'Asset', 'method'=>'doScheduledJob', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true)
		//);
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );


		// Permissions
		$this->rights = array(); // Permission array used by this module
		$this->rights_class = 'asset';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 51001; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read assets'; // Permission label
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)

		$r++;
		$this->rights[$r][0] = 51002; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update assets'; // Permission label
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write'; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)

		$r++;
		$this->rights[$r][0] = 51003; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete assets'; // Permission label
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete'; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)

		$r++;
		$this->rights[$r][0] = 51005; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Setup types of asset'; // Permission label
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'setup_advance'; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)
		$this->rights[$r][5] = ''; // In php code, permission will be checked by test if ($user->rights->asset->level1->level2)

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
