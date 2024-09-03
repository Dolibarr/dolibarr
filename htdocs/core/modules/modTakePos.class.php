<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018 SuperAdmin
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * 	\defgroup   takepos     Module TakePos
 *  \brief      TakePos module descriptor.
 *
 *  \file       htdocs/core/modules/modTakePos.class.php
 *  \ingroup    takepos
 *  \brief      Description and activation file for the module TakePos
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


/**
 *  Class to describe and enable module TakePos
 */
class modTakePos extends DolibarrModules
{
	/**
	 * Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 50150;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'takepos';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "portal";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '45';
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModuleTakePosName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleTakePosDesc' not found (MyModue is name of module).
		$this->description = "Point of sales module (Touch Screen POS)";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "Point Of Sales (compliant with touch screen)";

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where TAKEPOS is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'cash-register';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /takepos/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /takepos/core/modules/barcode)
		// for specific css file (eg: /takepos/css/takepos.css.php)
		$this->module_parts = array(
									'triggers' => 0, // Set this to 1 if module has its own trigger directory (core/triggers)
									'login' => 0, // Set this to 1 if module has its own login method file (core/login)
									'substitutions' => 1, // Set this to 1 if module has its own substitution function file (core/substitutions)
									'menus' => 0, // Set this to 1 if module has its own menus handler directory (core/menus)
									'theme' => 0, // Set this to 1 if module has its own theme directory (theme)
									'tpl' => 0, // Set this to 1 if module overwrite template dir (core/tpl)
									'barcode' => 0, // Set this to 1 if module has its own barcode directory (core/modules/barcode)
									'models' => 0, // Set this to 1 if module has its own models directory (core/modules/xxx)
									'hooks' => array() 	                                // Set here all hooks context managed by module. To find available hook context, make a "grep -r '>initHooks(' *" on source code. You can also set hook context 'all'
								);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/takepos/temp","/takepos/subdir");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into takepos/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@takepos");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		// List of module class names as string that must be enabled if this module is enabled. Example: array('always'=>array('modModuleToEnable1','modModuleToEnable2'), 'FR'=>array('modModuleToEnableFR'...))
		$this->depends = array('always'=>array("modBanque", "modFacture", "modProduct", "modCategorie"), 'FR'=>array('modBlockedLog'));
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->langfiles = array("cashdesk");
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(4, 0); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array('FR'=>'WarningNoteModulePOSForFrenchLaw'); // Warning to show when we activate module. array('always'='text') or array('FR'='text')
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'TakePosWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('TAKEPOS_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('TAKEPOS_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			//1=>array('TAKEPOS_MYCONSTANT', 'chaine', 'avalue', 'This is a constant to add', 1, 'allentities', 1)
		);

		// To avoid warning
		if (!isModEnabled('takepos')) {
			$conf->takepos = new stdClass();
			$conf->takepos->enabled = 0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@takepos:$user->rights->takepos->read:/takepos/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@takepos:$user->rights->othermodule->read:/takepos/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		// Add here list of php file(s) stored in takepos/core/boxes that contains class to show a widget.
		$this->boxes = array(
			//0=>array('file'=>'takeposwidget1.php@takepos','note'=>'Widget provided by TakePos','enabledbydefaulton'=>'Home'),
			//1=>array('file'=>'takeposwidget2.php@takepos','note'=>'Widget provided by TakePos'),
			//2=>array('file'=>'takeposwidget3.php@takepos','note'=>'Widget provided by TakePos')
		);


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			//0=>array('label'=>'MyJob label', 'jobtype'=>'method', 'class'=>'/takepos/class/myobject.class.php', 'objectname'=>'MyObject', 'method'=>'doScheduledJob', 'parameters'=>'', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true)
		);
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );


		// Permissions
		$this->rights = array(); // Permission array used by this module

		$r = 0;

		$r++;
		$this->rights[$r][0] = 50151;
		$this->rights[$r][1] = 'Use Point Of Sale (record a sale, add products, record payment)';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'run';

		$r++;
		$this->rights[$r][0] = 50152;
		$this->rights[$r][1] = 'Can modify added sales lines (prices, discount)';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'editlines';

		$r++;
		$this->rights[$r][0] = 50153;
		$this->rights[$r][1] = 'Edit ordered sales lines (useful only when option "Order printers" has been enabled). Allow to edit sales lines even after the order has been printed';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'editorderedlines';


		// Main menu entries
		$this->menu = array(); // List of menus to add
		$r = 0;

		// Add here entries to declare new menus

		/* BEGIN MODULEBUILDER TOPMENU */
		$this->menu[$r++] = array('fk_menu'=>'', // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'top', // This is a Top menu entry
								'titre'=>'PointOfSaleShort',
								'mainmenu'=>'takepos',
								'leftmenu'=>'',
								'prefix' => img_picto('', $this->picto, 'class="pictofixedwidth"'),
								'url'=>'/takepos/index.php',
								'langs'=>'cashdesk', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000 + $r,
								'enabled'=>'isModEnabled("takepos")', // Define condition to show or hide menu entry. Use '$conf->takepos->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->hasRight("takepos", "run")', // Use 'perms'=>'$user->rights->takepos->level1->level2' if you want your menu with a permission rules
								'target'=>'takepos',
								'user'=>2); // 0=Menu for internal users, 1=external users, 2=both

		/* END MODULEBUILDER TOPMENU */

		/* BEGIN MODULEBUILDER LEFTMENU MYOBJECT
		$this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=takepos',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'List MyObject',
								'mainmenu'=>'takepos',
								'leftmenu'=>'takepos_myobject_list',
								'url'=>'/takepos/myobject_list.php',
								'langs'=>'cashdesk',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->takepos->enabled',  // Define condition to show or hide menu entry. Use '$conf->takepos->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->takepos->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		$this->menu[$r++]=array(	'fk_menu'=>'fk_mainmenu=takepos,fk_leftmenu=takepos',	    // '' if this is a top menu. For left menu, use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
								'type'=>'left',			                // This is a Left menu entry
								'titre'=>'New MyObject',
								'mainmenu'=>'takepos',
								'leftmenu'=>'takepos_myobject_new',
								'url'=>'/takepos/myobject_page.php?action=create',
								'langs'=>'cashdesk',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>1000+$r,
								'enabled'=>'$conf->takepos->enabled',  // Define condition to show or hide menu entry. Use '$conf->takepos->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
								'perms'=>'1',			                // Use 'perms'=>'$user->rights->takepos->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				                // 0=Menu for internal users, 1=external users, 2=both
		END MODULEBUILDER LEFTMENU MYOBJECT */
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $user, $mysoc;
		$langs->load("cashdesk");

		dolibarr_set_const($this->db, "TAKEPOS_PRINT_METHOD", "browser", 'chaine', 0, '', $conf->entity);

		// Default customer for Point of sale
		if (!getDolGlobalInt('CASHDESK_ID_THIRDPARTY1')) {	// If a customer has already ben set into the TakePos setup page
			$societe = new Societe($this->db);
			$nametouse = $langs->trans("DefaultPOSThirdLabel");

			$searchcompanyid = $societe->fetch(0, $nametouse);
			if ($searchcompanyid == 0) {
				$societe->name = $nametouse;
				$societe->client = 1;
				$societe->code_client = '-1';
				$societe->code_fournisseur = '-1';
				$societe->note_private = "Default customer automatically created by Point Of Sale module activation. Can be used as the default generic customer in the Point Of Sale setup. Can also be edited or removed if you don't need a generic customer.";

				$searchcompanyid = $societe->create($user);
			}
			if ($searchcompanyid > 0) {
				// We already have or we have create a thirdparty with id = $searchcompanyid, so we link use it into setup
				dolibarr_set_const($this->db, "CASHDESK_ID_THIRDPARTY1", $searchcompanyid, 'chaine', 0, '', $conf->entity);
			} else {
				setEventMessages($societe->error, $societe->errors, 'errors');
			}
		}

		// Create product category DefaultPOSCatLabel if not exists
		$categories = new Categorie($this->db);
		$cate_arbo = $categories->get_full_arbo('product', 0, 1);
		if (is_array($cate_arbo)) {
			if (!count($cate_arbo) || !getDolGlobalString('TAKEPOS_ROOT_CATEGORY_ID')) {
				$category = new Categorie($this->db);

				$category->label = $langs->trans("DefaultPOSCatLabel");
				$category->type = Categorie::TYPE_PRODUCT;

				$result = $category->create($user);

				if ($result > 0) {
					dolibarr_set_const($this->db, 'TAKEPOS_ROOT_CATEGORY_ID', $result, 'chaine', 0, 'Id of category for products visible in TakePOS', $conf->entity);

					/* TODO Create a generic product only if there is no product yet. If 0 product,  we create 1. If there is already product, it is better to show a message to ask to add product in the category */
					/*
					$product = new Product($this->db);
					$product->status = 1;
					$product->ref = "takepos";
					$product->label = $langs->trans("DefaultPOSProductLabel");
					$product->create($user);
					$product->setCategories($result);
					 */
				} else {
					setEventMessages($category->error, $category->errors, 'errors');
				}
			}
		}

		// Create cash account CASH-POS / DefaultCashPOSLabel if not exists
		if (!getDolGlobalInt('CASHDESK_ID_BANKACCOUNT_CASH1')) {
			require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
			$cashaccount = new Account($this->db);
			$searchaccountid = $cashaccount->fetch(0, "CASH-POS");
			if ($searchaccountid == 0) {
				$cashaccount->ref = "CASH-POS";
				$cashaccount->label = $langs->trans("DefaultCashPOSLabel");
				$cashaccount->courant = Account::TYPE_CASH; // deprecated
				$cashaccount->type = Account::TYPE_CASH;
				$cashaccount->country_id = $mysoc->country_id ? $mysoc->country_id : 1;
				$cashaccount->date_solde = dol_now();
				$searchaccountid = $cashaccount->create($user);
			}
			if ($searchaccountid > 0) {
				dolibarr_set_const($this->db, "CASHDESK_ID_BANKACCOUNT_CASH1", $searchaccountid, 'chaine', 0, '', $conf->entity);
			} else {
				setEventMessages($cashaccount->error, $cashaccount->errors, 'errors');
			}
		}

		$result = $this->_load_tables('/install/mysql/', 'takepos');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Clean before activation
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 *	Function called when module is disabled.
	 *	Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
