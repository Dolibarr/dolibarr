<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017 Regis Houssin      	<regis.houssin@inodbox.com>
 * Copyright (C) 2006 	   Jean Heimburger    	<jean@tiaris.info>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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

require_once DOL_DOCUMENT_ROOT.'/core/class/doldeprecationhandler.class.php';

/**
 *	\file       	htdocs/core/class/conf.class.php
 *	\ingroup		core
 *  \brief      	File of class to manage storage of current setup
 *  				Config is stored into file conf.php
 */

/**
 *  Class to stock current configuration
 *
 */
class Conf extends stdClass
{
	use DolDeprecationHandler;
	/**
	 * When true, indicates to the DeprecationHandler that this
	 * class supports dynamic properties.
	 */
	protected $enableDynamicProperties = true;

	/**
	 * @var Object 	Associative array with properties found in conf file
	 */
	public $file;

	/**
	 * @var Object 	Associative array with some properties ->type, ->db, ...
	 */
	public $db;

	/**
	 * @var Object To store global setup found into database
	 */
	public $global;

	/**
	 * @var Object To store browser info (->name, ->os, ->version, ->ua, ->layout, ...)
	 */
	public $browser;

	//! To store some setup of generic modules
	public $mycompany;
	public $admin;
	public $medias;
	//! To store properties of multi-company
	public $multicompany;

	//! To store module status of special module names
	public $expedition_bon;
	public $delivery_note;


	//! To store if javascript/ajax is enabked
	public $use_javascript_ajax;
	//! To store if javascript/ajax is enabked
	public $disable_compute;
	//! Used to store current currency (ISO code like 'USD', 'EUR', ...). To get the currency symbol: $langs->getCurrencySymbol($this->currency)
	public $currency;

	/**
	 * @var string
	 */
	public $theme; // Contains current theme ("eldy", "auguria", ...)
	//! Used to store current css (from theme)
	/**
	 * @var string
	 */
	public $css; // Contains full path of css page ("/theme/eldy/style.css.php", ...)

	public $email_from;

	//! Used to store current menu handler
	public $standard_menu;
	/**
	 * @var array<string,string>  List of activated modules
	 */
	public $modules;
	/**
	 * @var array<string,array<string,string|array>>  List of activated modules
	 */
	public $modules_parts;

	/**
	 * @var array<string,mixed> An array to store cache results ->cache['nameofcache']=...
	 */
	public $cache;

	/**
	 * @var int To tell header was output
	 */
	public $headerdone;

	/**
	 * @var string[]
	 */
	public $logbuffer = array();

	/**
	 * @var LogHandler[]
	 */
	public $loghandlers = array();

	/**
	 * @var int Used to store running instance for multi-company (default 1)
	 */
	public $entity = 1;
	/**
	 * @var int[] Used to store list of entities to use for each element
	 */
	public $entities = array();

	public $dol_hide_topmenu; // Set if we force param dol_hide_topmenu into login url
	public $dol_hide_leftmenu; // Set if we force param dol_hide_leftmenu into login url
	public $dol_optimize_smallscreen; // Set if we force param dol_optimize_smallscreen into login url or if browser is smartphone
	public $dol_no_mouse_hover; // Set if we force param dol_no_mouse_hover into login url or if browser is smartphone
	public $dol_use_jmobile; // Set if we force param dol_use_jmobile into login url. 0=default, 1=to say we use app from a webview app, 2=to say we use app from a webview app and keep ajax

	public $format_date_short; // Format of day with PHP/C tags (strftime functions)
	public $format_date_short_java; // Format of day with Java tags
	public $format_hour_short;
	public $format_hour_short_duration;
	public $format_date_text_short;
	public $format_date_text;
	public $format_date_hour_short;
	public $format_date_hour_sec_short;
	public $format_date_hour_text_short;
	public $format_date_hour_text;

	public $liste_limit;
	public $main_checkbox_left_column;

	public $tzuserinputkey = 'tzserver';		// Use 'tzuserrel' to always store date in GMT and show date in time zone of user.


	// TODO Remove this part.

	/**
	 * @var stdClass  	Supplier
	 */
	public $fournisseur;

	/**
	 * @var stdClass
	 */
	public $product;

	/**
	 * @var stdClass
	 * @deprecated      Use $product
	 * @see $product
	 */
	private $produit;

	/**
	 * @var stdClass
	 */
	public $service;

	/**
	 * @var stdClass
	 * @deprecated      Use $contract
	 * @see $contract
	 */
	private $contrat;

	/**
	 * @var stdClass
	 */
	public $contract;

	/**
	 * @var stdClass
	 */
	public $actions;

	/**
	 * @var stdClass
	 */
	public $agenda;

	/**
	 * @var stdClass
	 * @deprecated      Use $order
	 * @see $order
	 */
	private $commande;

	/**
	 * @var stdClass
	 */
	public $propal;

	/**
	 * @var stdClass
	 */
	public $order;

	/**
	 * @var stdClass
	 * @deprecated      Use $invoice
	 * @see $invoice
	 */
	private $facture;


	/**
	 * @var stdClass
	 */
	public $invoice;

	/**
	 * @var stdClass
	 */
	public $user;

	/**
	 * @var stdClass
	 * @deprecated      Use $member
	 * @see $member
	 */
	private $adherent;


	/**
	 * @var stdClass
	 */
	public $member;

	/**
	 * @var stdClass
	 */
	public $bank;

	/**
	 * @var stdClass
	 */
	public $notification;

	/**
	 * @var stdClass
	 */
	public $expensereport;

	/**
	 * @var stdClass
	 */
	public $productbatch;

	/**
	 * @var stdClass
	 * @deprecated      Use $project
	 * @see $project
	 */
	private $projet;


	/**
	 * @var stdClass
	 */
	public $project;

	/**
	 * @var stdClass
	 */
	public $supplier_proposal;

	/**
	 * @var stdClass
	 */
	public $supplier_order;

	/**
	 * @var stdClass
	 */
	public $supplier_invoice;

	/**
	 * @var stdClass
	 */
	public $category;

	/**
	 * @var stdClass
	 * @deprecated      Use $category
	 * @see $category
	 */
	private $categorie;

	/**
	 * @var stdClass
	 * @deprecated      Use $supplier_proposal
	 * @see $supplier_proposal
	 */
	private $supplierproposal;

	/**
	 * @var stdClass
	 * @deprecated      Use $delivery_note
	 * @see $delivery_note
	 */
	private $expedition;

	/**
	 * @var stdClass
	 * @deprecated      Use $bank
	 * @see $bank
	 */
	private $banque;


	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Properly declare multi-modules objects.
		$this->file = new stdClass();
		$this->db = new stdClass();
		//! Charset for HTML output and for storing data in memory
		$this->file->character_set_client = 'UTF-8'; // UTF-8, ISO-8859-1

		// Common objects that are not modules
		$this->mycompany = new stdClass();
		$this->admin = new stdClass();
		$this->medias = new stdClass();
		$this->global = new stdClass();

		// Common objects that are not modules and set by the main and not into the this->setValues()
		$this->browser = new stdClass();

		// Common arrays
		$this->cache = array();
		$this->modules = array();
		$this->modules_parts = array(
			'css' => array(),
			'js' => array(),
			'tabs' => array(),
			'triggers' => array(),
			'login' => array(),
			'substitutions' => array(),
			'menus' => array(),
			'theme' => array(),
			'sms' => array(),
			'tpl' => array(),
			'barcode' => array(),
			'models' => array(),
			'societe' => array(),
			'member' => array(),
			'hooks' => array(),
			'dir' => array(),
			'syslog' => array(),
			'websitetemplates' => array()
		);

		// First level object that are modules.
		// TODO Remove this part.
		$this->multicompany = new stdClass();
		$this->fournisseur = new stdClass();
		$this->product = new stdClass();
		$this->service = new stdClass();
		$this->contract = new stdClass();
		$this->actions = new stdClass();
		$this->agenda = new stdClass();
		$this->order = new stdClass();
		$this->propal = new stdClass();
		$this->invoice = new stdClass();
		$this->user	= new stdClass();
		$this->member = new stdClass();
		$this->bank = new stdClass();
		$this->mailing = new stdClass();
		$this->notification = new stdClass();
		$this->expensereport = new stdClass();
		$this->productbatch = new stdClass();
		$this->api = new stdClass();
	}

	/**
	 * Provide list of deprecated properties and replacements
	 *
	 * @return array<string,string>
	 */
	protected function deprecatedProperties()
	{
		return MODULE_MAPPING
		+ array(
			// Previously detected module names, already in mapping
			//'adherent' => 'member',
			//'banque' => 'bank',
			//'categorie' => 'category',
			//'commande' => 'order',
			//'contrat' => 'contract',
			//'expedition' => 'delivery_note',
			//'facture' => 'invoice',
			//'projet' => 'project',

			// Other, not deprecated module names
			'produit' => 'product',
			'supplierproposal' => 'supplier_proposal',
		);
	}


	/**
	 * Load setup values into conf object (read llx_const) for a specified entity
	 * Note that this->db->xxx, this->file->xxx and this->multicompany have been already loaded when setEntityValues is called.
	 *
	 * @param	DoliDB	$db			Database handler
	 * @param	int		$entity		Entity to get
	 * @return	int					Return integer < 0 if KO, >= 0 if OK
	 */
	public function setEntityValues($db, $entity)
	{
		if ($this->entity != $entity) {
			// If we ask to reload setup for a new entity
			$this->entity = $entity;
			return $this->setValues($db);
		}

		return 0;
	}

	/**
	 *  Load setup values into conf object (read llx_const)
	 *  Note that this->db->xxx, this->file->xxx have been already set when setValues is called.
	 *
	 *  @param      DoliDB      $db     Database handler
	 *  @return     int                 Return integer < 0 if KO, >= 0 if OK
	 */
	public function setValues($db)
	{
		dol_syslog(get_class($this)."::setValues");

		// Unset all old modules values
		if (!empty($this->modules)) {
			foreach ($this->modules as $m) {
				if (isset($this->$m)) {
					unset($this->$m);
				}
			}
		}

		// Common objects that are not modules
		$this->mycompany = new stdClass();
		$this->admin = new stdClass();
		$this->medias = new stdClass();
		$this->global = new stdClass();

		// Common objects that are not modules and set by the main and not into the this->setValues()
		//$this->browser = new stdClass();	// This is set by main and not into this setValues(), so we keep it intact.

		// First level object
		// TODO Remove this part.
		$this->fournisseur = new stdClass();
		$this->compta = new stdClass();
		$this->product = new stdClass();
		$this->service = new stdClass();
		$this->contract = new stdClass();
		$this->actions = new stdClass();
		$this->agenda = new stdClass();
		$this->order = new stdClass();
		$this->propal = new stdClass();
		$this->invoice = new stdClass();
		$this->user	= new stdClass();
		$this->member = new stdClass();
		$this->bank = new stdClass();
		$this->notification = new stdClass();
		$this->expensereport = new stdClass();
		$this->productbatch = new stdClass();

		// Common arrays
		$this->cache = array();
		$this->modules = array();
		$this->modules_parts = array(
			'css' => array(),
			'js' => array(),
			'tabs' => array(),
			'triggers' => array(),
			'login' => array(),
			'substitutions' => array(),
			'menus' => array(),
			'theme' => array(),
			'sms' => array(),
			'tpl' => array(),
			'barcode' => array(),
			'models' => array(),
			'societe' => array(),
			'member' => array(),
			'hooks' => array(),
			'dir' => array(),
			'syslog' => array(),
			'websitetemplates' => array(),
		);

		if (!is_null($db) && is_object($db)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

			// Define all global constants into $this->global->key=value
			$sql = "SELECT ".$db->decrypt('name')." as name,";
			$sql .= " ".$db->decrypt('value')." as value, entity";
			$sql .= " FROM ".$db->prefix()."const";
			$sql .= " WHERE entity IN (0,".$this->entity.")";
			$sql .= " ORDER BY entity"; // This is to have entity 0 first, then entity 1 that overwrite.

			$resql = $db->query($sql);
			if ($resql) {
				$i = 0;
				$numr = $db->num_rows($resql);
				while ($i < $numr) {
					$objp = $db->fetch_object($resql);
					$key = $objp->name;
					$value = $objp->value;
					if ($key) {
						// Allow constants values to be overridden by environment variables
						if (isset($_SERVER['DOLIBARR_'.$key])) {
							$value = $_SERVER['DOLIBARR_'.$key];
						} elseif (isset($_ENV['DOLIBARR_'.$key])) {
							$value = $_ENV['DOLIBARR_'.$key];
						}

						$this->global->$key = dolDecrypt($value);	// decrypt data excrypted with dolibarr_set_const($db, $name, $value)

						if ($value && strpos($key, 'MAIN_MODULE_') === 0) {
							$reg = array();
							// If this is constant for a new tab page activated by a module. It initializes modules_parts['tabs'].
							if (preg_match('/^MAIN_MODULE_([0-9A-Z_]+)_TABS_/i', $key)) {
								$partname = 'tabs';
								$params = explode(':', $value, 2);
								if (!is_array($this->modules_parts[$partname])) {
									$this->modules_parts[$partname] = array();
								}
								$this->modules_parts[$partname][$params[0]][] = $value; // $value may be a string or an array
							} elseif (preg_match('/^MAIN_MODULE_([0-9A-Z_]+)_([A-Z]+)$/i', $key, $reg)) {
								// If this is a constant for all generic part activated by a module. It initializes
								// modules_parts['login'], modules_parts['menus'], modules_parts['substitutions'], modules_parts['triggers'], modules_parts['tpl'],
								// modules_parts['models'], modules_parts['theme']
								// modules_parts['sms'],
								// modules_parts['css'], modules_parts['js'],...

								$modulename = strtolower($reg[1]);
								$partname = strtolower($reg[2]);
								if (!isset($this->modules_parts[$partname]) || !is_array($this->modules_parts[$partname])) {
									$this->modules_parts[$partname] = array();
								}

								//$arrValue = json_decode($value, true, null, JSON_BIGINT_AS_STRING|JSON_THROW_ON_ERROR);
								$arrValue = json_decode($value, true);
								//var_dump($key); var_dump($value); var_dump($arrValue);

								if (is_array($arrValue)) {
									$newvalue = $arrValue;
								} elseif (in_array($partname, array('login', 'menus', 'substitutions', 'triggers', 'tpl'))) {
									$newvalue = '/'.$modulename.'/core/'.$partname.'/';
								} elseif (in_array($partname, array('models', 'theme', 'websitetemplates'))) {
									$newvalue = '/'.$modulename.'/';
								} elseif ($value == 1) {
									$newvalue = '/'.$modulename.'/core/modules/'.$partname.'/'; // ex: partname = societe
								} else {	// $partname can be any other value like 'sms', ...
									$newvalue = $value;
								}

								if (!empty($newvalue)) {
									$this->modules_parts[$partname] = array_merge($this->modules_parts[$partname], array($modulename => $newvalue)); // $value may be a string or an array
								}
							} elseif (preg_match('/^MAIN_MODULE_([0-9A-Z_]+)$/i', $key, $reg)) {
								// If this is a module constant (must be at end)
								$modulename = strtolower($reg[1]);
								$this->modules[$modulename] = $modulename; // Add this module in list of enabled modules

								// deprecated in php 8.2
								//if (version_compare(phpversion(), '8.2') < 0) {
								if (!isset($this->$modulename) || !is_object($this->$modulename)) {
									$this->$modulename = new stdClass();	// We need this to use the ->enabled and the ->multidir, ->dir...
								}
								$this->$modulename->enabled = true;	// TODO Remove this

								// Duplicate entry with the new name
								$mapping = $this->deprecatedProperties();
								if (array_key_exists($modulename, $mapping)) {
									$newmodulename = $mapping[$modulename];
									$this->modules[$newmodulename] = $newmodulename;

									if (!isset($this->$newmodulename) || !is_object($this->$newmodulename)) {
										$this->$newmodulename = new stdClass();	// We need this to use the ->enabled and the ->multidir, ->dir...
									}
									$this->$newmodulename->enabled = true;	// TODO Remove this
								}
							}
						}
					}
					$i++;
				}

				$db->free($resql);
			}

			// Include other local file xxx/zzz_consts.php to overwrite some variables
			if (!empty($this->global->LOCAL_CONSTS_FILES)) {
				$filesList = explode(":", $this->global->LOCAL_CONSTS_FILES);
				foreach ($filesList as $file) {
					$file = dol_sanitizeFileName($file);
					dol_include_once($file."/".$file."_consts.php"); // This file can run code like setting $this->global->XXX vars.
				}
			}

			//var_dump($this->modules);
			//var_dump($this->modules_parts['theme']);

			// If you can't set timezone of your PHP, set this constant. Better is to set it to UTC.
			// In future, this constant will be forced to 'UTC' so PHP server timezone will not have effect anymore.
			//$this->global->MAIN_SERVER_TZ='Europe/Paris';
			if (!empty($this->global->MAIN_SERVER_TZ) && $this->global->MAIN_SERVER_TZ != 'auto') {
				try {
					date_default_timezone_set($this->global->MAIN_SERVER_TZ);
				} catch (Exception $e) {
					dol_syslog("Error: Bad value for parameter MAIN_SERVER_TZ=".$this->global->MAIN_SERVER_TZ, LOG_ERR);
				}
			}

			// Object $mc
			if (!defined('NOREQUIREMC') && isModEnabled('multicompany')) {
				global $mc;
				$ret = @dol_include_once('/multicompany/class/actions_multicompany.class.php');
				if ($ret && class_exists('ActionsMulticompany')) {
					$mc = new ActionsMulticompany($db);
				}
			}

			// Clean some variables
			if (empty($this->global->MAIN_MENU_STANDARD)) {
				$this->global->MAIN_MENU_STANDARD = "eldy_menu.php";
			}
			if (empty($this->global->MAIN_MENUFRONT_STANDARD)) {
				$this->global->MAIN_MENUFRONT_STANDARD = "eldy_menu.php";
			}
			if (empty($this->global->MAIN_MENU_SMARTPHONE)) {
				$this->global->MAIN_MENU_SMARTPHONE = "eldy_menu.php"; // Use eldy by default because smartphone does not work on all phones
			}
			if (empty($this->global->MAIN_MENUFRONT_SMARTPHONE)) {
				$this->global->MAIN_MENUFRONT_SMARTPHONE = "eldy_menu.php"; // Use eldy by default because smartphone does not work on all phones
			}
			if (!isset($this->global->FACTURE_TVAOPTION)) {
				$this->global->FACTURE_TVAOPTION = 1;
			}

			// Variable globales LDAP
			if (empty($this->global->LDAP_FIELD_FULLNAME)) {
				$this->global->LDAP_FIELD_FULLNAME = '';
			}
			if (!isset($this->global->LDAP_KEY_USERS)) {
				$this->global->LDAP_KEY_USERS = $this->global->LDAP_FIELD_FULLNAME;
			}
			if (!isset($this->global->LDAP_KEY_GROUPS)) {
				$this->global->LDAP_KEY_GROUPS = $this->global->LDAP_FIELD_FULLNAME;
			}
			if (!isset($this->global->LDAP_KEY_CONTACTS)) {
				$this->global->LDAP_KEY_CONTACTS = $this->global->LDAP_FIELD_FULLNAME;
			}
			if (!isset($this->global->LDAP_KEY_MEMBERS)) {
				$this->global->LDAP_KEY_MEMBERS = $this->global->LDAP_FIELD_FULLNAME;
			}
			if (!isset($this->global->LDAP_KEY_MEMBERS_TYPES)) {
				$this->global->LDAP_KEY_MEMBERS_TYPES = $this->global->LDAP_FIELD_FULLNAME;
			}

			// Load translation object with current language
			if (empty($this->global->MAIN_LANG_DEFAULT)) {
				$this->global->MAIN_LANG_DEFAULT = "en_US";
			}

			$rootfordata = DOL_DATA_ROOT;
			$rootforuser = DOL_DATA_ROOT;
			// If multicompany module is enabled, we redefine the root of data
			if (isModEnabled('multicompany') && !empty($this->entity) && $this->entity > 1) {
				$rootfordata .= '/'.$this->entity;
			}
			// Set standard temporary folder name or global override
			$rootfortemp = empty($this->global->MAIN_TEMP_DIR) ? $rootfordata : $this->global->MAIN_TEMP_DIR;

			// Define default dir_output and dir_temp for directories of modules
			foreach ($this->modules as $module) {
				//var_dump($module);
				// For multicompany sharings
				$this->$module->multidir_output = array($this->entity => $rootfordata."/".$module);
				$this->$module->multidir_temp = array($this->entity => $rootfortemp."/".$module."/temp");
				// For backward compatibility
				$this->$module->dir_output = $rootfordata."/".$module;
				$this->$module->dir_temp = $rootfortemp."/".$module."/temp";
			}

			// External modules storage
			if (!empty($this->modules_parts['dir'])) {
				foreach ($this->modules_parts['dir'] as $module => $dirs) {
					if (!empty($this->$module->enabled)) {
						foreach ($dirs as $type => $name) {  // $type is 'output' or 'temp'
							$multidirname = 'multidir_'.$type;
							$dirname = 'dir_'.$type;

							if ($type != 'temp') {
								// For multicompany sharings
								$this->$module->$multidirname = array($this->entity => $rootfordata."/".$name);

								// For backward compatibility
								$this->$module->$dirname = $rootfordata."/".$name;
							} else {
								// For multicompany sharings
								$this->$module->$multidirname = array($this->entity => $rootfortemp."/".$name."/temp");

								// For backward compatibility
								$this->$module->$dirname = $rootfortemp."/".$name."/temp";
							}
						}
					}
				}
			}

			// For mycompany storage
			$this->mycompany->multidir_output = array($this->entity => $rootfordata."/mycompany");
			$this->mycompany->multidir_temp = array($this->entity => $rootfortemp."/mycompany/temp");
			// For backward compatibility
			$this->mycompany->dir_output = $rootfordata."/mycompany";
			$this->mycompany->dir_temp = $rootfortemp."/mycompany/temp";

			// For admin storage
			$this->admin->dir_output = $rootfordata.'/admin';
			$this->admin->dir_temp = $rootfortemp.'/admin/temp';

			// For user storage
			$this->user->multidir_output = array($this->entity => $rootfordata."/users");
			$this->user->multidir_temp = array($this->entity => $rootfortemp."/users/temp");
			// For backward compatibility
			$this->user->dir_output = $rootforuser."/users";
			$this->user->dir_temp = $rootfortemp."/users/temp";

			// For proposal storage
			$this->propal->multidir_output = array($this->entity => $rootfordata."/propale");
			$this->propal->multidir_temp = array($this->entity => $rootfortemp."/propale/temp");
			// For backward compatibility
			$this->propal->dir_output = $rootfordata."/propale";
			$this->propal->dir_temp = $rootfortemp."/propale/temp";

			// For medias storage
			$this->medias->multidir_output = array($this->entity => $rootfordata."/medias");
			$this->medias->multidir_temp = array($this->entity => $rootfortemp."/medias/temp");

			// Exception: Some dir are not the name of module. So we keep exception here for backward compatibility.

			// Module supplier is on
			if (isModEnabled('fournisseur')) {
				$this->fournisseur->commande = new stdClass();
				$this->fournisseur->commande->multidir_output = array($this->entity => $rootfordata."/fournisseur/commande");
				$this->fournisseur->commande->multidir_temp = array($this->entity => $rootfortemp."/fournisseur/commande/temp");
				$this->fournisseur->commande->dir_output = $rootfordata."/fournisseur/commande"; // For backward compatibility
				$this->fournisseur->commande->dir_temp = $rootfortemp."/fournisseur/commande/temp"; // For backward compatibility

				$this->fournisseur->facture = new stdClass();
				$this->fournisseur->facture->multidir_output = array($this->entity => $rootfordata."/fournisseur/facture");
				$this->fournisseur->facture->multidir_temp = array($this->entity => $rootfortemp."/fournisseur/facture/temp");
				$this->fournisseur->facture->dir_output = $rootfordata."/fournisseur/facture"; // For backward compatibility
				$this->fournisseur->facture->dir_temp = $rootfortemp."/fournisseur/facture/temp"; // For backward compatibility

				$this->supplier_proposal = new stdClass();
				$this->supplier_proposal->multidir_output = array($this->entity => $rootfordata."/supplier_proposal");
				$this->supplier_proposal->multidir_temp = array($this->entity => $rootfortemp."/supplier_proposal/temp");
				$this->supplier_proposal->dir_output = $rootfordata."/supplier_proposal"; // For backward compatibility
				$this->supplier_proposal->dir_temp = $rootfortemp."/supplier_proposal/temp"; // For backward compatibility

				$this->fournisseur->payment = new stdClass();
				$this->fournisseur->payment->multidir_output = array($this->entity => $rootfordata."/fournisseur/payment");
				$this->fournisseur->payment->multidir_temp = array($this->entity => $rootfortemp."/fournisseur/payment/temp");
				$this->fournisseur->payment->dir_output = $rootfordata."/fournisseur/payment"; // For backward compatibility
				$this->fournisseur->payment->dir_temp = $rootfortemp."/fournisseur/payment/temp"; // For backward compatibility

				// To prepare split of module supplier into module 'supplier' + 'supplier_order' + 'supplier_invoice'
				if (!getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) {  // By default, if module supplier is on, and we don't use yet the new modules, we set artificially the module properties
					$this->supplier_order = new stdClass();
					$this->supplier_order->enabled = 1;
					$this->supplier_order->multidir_output = array($this->entity => $rootfordata."/fournisseur/commande");
					$this->supplier_order->multidir_temp = array($this->entity => $rootfortemp."/fournisseur/commande/temp");
					$this->supplier_order->dir_output = $rootfordata."/fournisseur/commande"; // For backward compatibility
					$this->supplier_order->dir_temp = $rootfortemp."/fournisseur/commande/temp"; // For backward compatibility

					$this->supplier_invoice = new stdClass();
					$this->supplier_invoice->enabled = 1;
					$this->supplier_invoice->multidir_output = array($this->entity => $rootfordata."/fournisseur/facture");
					$this->supplier_invoice->multidir_temp = array($this->entity => $rootfortemp."/fournisseur/facture/temp");
					$this->supplier_invoice->dir_output = $rootfordata."/fournisseur/facture"; // For backward compatibility
					$this->supplier_invoice->dir_temp = $rootfortemp."/fournisseur/facture/temp"; // For backward compatibility
				}
			}

			// Module compta
			$this->compta->payment = new stdClass();
			$this->compta->payment->dir_output				= $rootfordata."/compta/payment";
			$this->compta->payment->dir_temp					= $rootfortemp."/compta/payment/temp";

			// Module product/service
			$this->product->multidir_output 		= array($this->entity => $rootfordata."/produit");
			$this->product->multidir_temp			= array($this->entity => $rootfortemp."/produit/temp");
			$this->service->multidir_output			= array($this->entity => $rootfordata."/produit");
			$this->service->multidir_temp			= array($this->entity => $rootfortemp."/produit/temp");
			// For backward compatibility
			$this->product->dir_output				= $rootfordata."/produit";
			$this->product->dir_temp				= $rootfortemp."/produit/temp";
			$this->service->dir_output				= $rootfordata."/produit";
			$this->service->dir_temp				= $rootfortemp."/produit/temp";

			// Module productbatch
			$this->productbatch->multidir_output = array($this->entity => $rootfordata."/productlot");
			$this->productbatch->multidir_temp = array($this->entity => $rootfortemp."/productlot/temp");

			// Module contract
			$this->contract->multidir_output = array($this->entity => $rootfordata."/contract");
			$this->contract->multidir_temp = array($this->entity => $rootfortemp."/contract/temp");
			// For backward compatibility
			$this->contract->dir_output = $rootfordata."/contract";
			$this->contract->dir_temp = $rootfortemp."/contract/temp";

			// Module bank
			$this->bank->multidir_output = array($this->entity => $rootfordata."/bank");
			$this->bank->multidir_temp = array($this->entity => $rootfortemp."/bank/temp");
			// For backward compatibility
			$this->bank->dir_output = $rootfordata."/bank";
			$this->bank->dir_temp = $rootfortemp."/bank/temp";

			// Set some default values
			//$this->global->MAIN_LIST_FILTER_ON_DAY=1;		// On filter that show date, we must show input field for day before or after month
			$this->global->MAIN_MAIL_USE_MULTI_PART = 1;

			// societe
			if (empty($this->global->SOCIETE_CODECLIENT_ADDON)) {
				$this->global->SOCIETE_CODECLIENT_ADDON = "mod_codeclient_leopard";
			}
			if (empty($this->global->SOCIETE_CODECOMPTA_ADDON)) {
				$this->global->SOCIETE_CODECOMPTA_ADDON = "mod_codecompta_panicum";
			}

			if (empty($this->global->CHEQUERECEIPTS_ADDON)) {
				$this->global->CHEQUERECEIPTS_ADDON = 'mod_chequereceipt_mint';
			}
			if (empty($this->global->TICKET_ADDON)) {
				$this->global->TICKET_ADDON = 'mod_ticket_simple';
			}

			// Security
			if (empty($this->global->USER_PASSWORD_GENERATED)) {
				$this->global->USER_PASSWORD_GENERATED = 'standard'; // Default password generator
			}
			if (empty($this->global->MAIN_UMASK)) {
				$this->global->MAIN_UMASK = '0660'; // Default mask
			} else {
				// We remove the execute bits on the file umask
				$tmpumask = (octdec(getDolGlobalString('MAIN_UMASK')) & 0666);
				$tmpumask = decoct($tmpumask);
				if (!preg_match('/^0/', $tmpumask)) {	// Convert string '123' into octal representation '0123'
					$tmpumask = '0'.$tmpumask;
				}
				if (empty($tmpumask)) {		// when $tmpmask is null, '', or '0'
					$tmpumask = '0664';
				}
				$this->global->MAIN_UMASK = $tmpumask;
			}

			// conf->use_javascript_ajax
			$this->use_javascript_ajax = 1;
			if (isset($this->global->MAIN_DISABLE_JAVASCRIPT)) {
				$this->use_javascript_ajax = !$this->global->MAIN_DISABLE_JAVASCRIPT;
			}
			// If no javascript_ajax, Ajax features are disabled.
			if (empty($this->use_javascript_ajax)) {
				unset($this->global->PRODUIT_USE_SEARCH_TO_SELECT);
				unset($this->global->COMPANY_USE_SEARCH_TO_SELECT);
				unset($this->global->CONTACT_USE_SEARCH_TO_SELECT);
				unset($this->global->PROJECT_USE_SEARCH_TO_SELECT);
			}

			if (isModEnabled('productbatch')) {
				// If module lot/serial enabled, we force the inc/dec mode to STOCK_CALCULATE_ON_SHIPMENT_CLOSE and STOCK_CALCULATE_ON_RECEPTION_CLOSE
				$this->global->STOCK_CALCULATE_ON_BILL = 0;
				$this->global->STOCK_CALCULATE_ON_VALIDATE_ORDER = 0;
				if (empty($this->global->STOCK_CALCULATE_ON_SHIPMENT)) {
					$this->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE = 1;
				}
				if (empty($this->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE)) {
					$this->global->STOCK_CALCULATE_ON_SHIPMENT = 1;
				}
				$this->global->STOCK_CALCULATE_ON_SUPPLIER_BILL = 0;
				$this->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER = 0;
				if (!isModEnabled('reception')) {
					$this->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER = 1;
				} else {
					if (empty($this->global->STOCK_CALCULATE_ON_RECEPTION)) {
						$this->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE = 1;
					}
					if (empty($this->global->STOCK_CALCULATE_ON_RECEPTION_CLOSE)) {
						$this->global->STOCK_CALCULATE_ON_RECEPTION = 1;
					}
				}
			}

			if (!isset($this->global->STOCK_SHOW_ALL_BATCH_BY_DEFAULT)) {
				$this->global->STOCK_SHOW_ALL_BATCH_BY_DEFAULT = 1;
			}

			// conf->currency
			if (empty($this->global->MAIN_MONNAIE)) {
				$this->global->MAIN_MONNAIE = 'EUR';
			}
			$this->currency = $this->global->MAIN_MONNAIE;

			if (empty($this->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY)) {
				$this->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY = 30; // Less than 1 minutes to be sure
			}

			// conf->global->ACCOUNTING_MODE = Option des modules Comptabilites (simple ou expert). Defini le mode de calcul des etats comptables (CA,...)
			if (empty($this->global->ACCOUNTING_MODE)) {
				$this->global->ACCOUNTING_MODE = 'RECETTES-DEPENSES'; // By default. Can be 'RECETTES-DEPENSES' ou 'CREANCES-DETTES'
			}

			if (!isset($this->global->MAIN_ENABLE_AJAX_TOOLTIP)) {
				$this->global->MAIN_ENABLE_AJAX_TOOLTIP = 0;	// Not enabled by default (still trouble of persistent tooltip)
			}

			// By default, suppliers objects can be linked to all projects
			if (!isset($this->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS)) {
				$this->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS = 1;
			}

			// By default we enable feature to bill time spent
			if (!isset($this->global->PROJECT_BILL_TIME_SPENT)) {
				$this->global->PROJECT_BILL_TIME_SPENT = 1;
			}

			// MAIN_HTML_TITLE
			if (!isset($this->global->MAIN_HTML_TITLE)) {
				$this->global->MAIN_HTML_TITLE = 'thirdpartynameonly,contactnameonly,projectnameonly';
			}

			// conf->liste_limit = constant to limit size of lists
			$this->liste_limit = getDolGlobalInt('MAIN_SIZE_LISTE_LIMIT', 15);
			if ((int) $this->liste_limit <= 0) {
				// Mode automatic.
				$this->liste_limit = 15;
				if (!empty($_SESSION['dol_screenheight']) && $_SESSION['dol_screenheight'] < 910) {
					$this->liste_limit = 10;
				} elseif (!empty($_SESSION['dol_screenheight']) && $_SESSION['dol_screenheight'] > 1130) {
					$this->liste_limit = 20;
				}
			}

			// conf->main_checkbox_left_column = constant to set checkbox list to left
			if (!isset($this->main_checkbox_left_column)) {
				$this->main_checkbox_left_column = getDolGlobalInt("MAIN_CHECKBOX_LEFT_COLUMN");
			}

			// Set PRODUIT_LIMIT_SIZE if never defined
			if (!isset($this->global->PRODUIT_LIMIT_SIZE)) {
				$this->global->PRODUIT_LIMIT_SIZE = 1000;
			}

			// Set PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE, may be modified later according to browser
			$this->global->PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE = getDolGlobalInt('PRODUIT_DESC_IN_FORM');

			// conf->theme et $this->css
			if (empty($this->global->MAIN_THEME)) {
				$this->global->MAIN_THEME = "eldy";
			}
			if (!empty($this->global->MAIN_FORCETHEME)) {
				$this->global->MAIN_THEME = $this->global->MAIN_FORCETHEME;
			}
			$this->theme = $this->global->MAIN_THEME;
			$this->css = "/theme/".$this->theme."/style.css.php";

			// conf->email_from = email by default to send Dolibarr automatic emails
			$this->email_from = "robot@example.com";
			if (!empty($this->global->MAIN_MAIL_EMAIL_FROM)) {
				$this->email_from = $this->global->MAIN_MAIL_EMAIL_FROM;
			}

			// conf->notification->email_from = email by default to send Dolibarr notifications
			if (isModEnabled('notification')) {
				$this->notification->email_from = $this->email_from;
				if (!empty($this->global->NOTIFICATION_EMAIL_FROM)) {
					$this->notification->email_from = $this->global->NOTIFICATION_EMAIL_FROM;
				}
			}

			if (!isset($this->global->MAIN_HIDE_WARNING_TO_ENCOURAGE_SMTP_SETUP)) {
				$this->global->MAIN_HIDE_WARNING_TO_ENCOURAGE_SMTP_SETUP = 1;
			}

			if (!isset($this->global->MAIN_FIX_FOR_BUGGED_MTA)) {
				$this->global->MAIN_FIX_FOR_BUGGED_MTA = 1;
			}

			// Format for date (used by default when not found or not searched in lang)
			$this->format_date_short = "%d/%m/%Y"; // Format of day with PHP/C tags (strftime functions)
			$this->format_date_short_java = "dd/MM/yyyy"; // Format of day with Java tags
			$this->format_hour_short = "%H:%M";
			$this->format_hour_short_duration = "%H:%M";
			$this->format_date_text_short = "%d %b %Y";
			$this->format_date_text = "%d %B %Y";
			$this->format_date_hour_short = "%d/%m/%Y %H:%M";
			$this->format_date_hour_sec_short = "%d/%m/%Y %H:%M:%S";
			$this->format_date_hour_text_short = "%d %b %Y %H:%M";
			$this->format_date_hour_text = "%d %B %Y %H:%M";

			// Duration of workday
			if (!isset($this->global->MAIN_DURATION_OF_WORKDAY)) {
				$this->global->MAIN_DURATION_OF_WORKDAY = 86400;
			}

			// Limites decimales si non definie (peuvent etre egale a 0)
			if (!isset($this->global->MAIN_MAX_DECIMALS_UNIT)) {
				$this->global->MAIN_MAX_DECIMALS_UNIT = 5;
			}
			if (!isset($this->global->MAIN_MAX_DECIMALS_TOT)) {
				$this->global->MAIN_MAX_DECIMALS_TOT = 2;
			}
			if (!isset($this->global->MAIN_MAX_DECIMALS_SHOWN)) {
				$this->global->MAIN_MAX_DECIMALS_SHOWN = 8;
			}

			// Default pdf option
			if (!isset($this->global->MAIN_PDF_DASH_BETWEEN_LINES)) {
				$this->global->MAIN_PDF_DASH_BETWEEN_LINES = 1; // use dash between lines
			}
			if (!isset($this->global->PDF_ALLOW_HTML_FOR_FREE_TEXT)) {
				$this->global->PDF_ALLOW_HTML_FOR_FREE_TEXT = 1; // allow html content into free footer text
			}
			if (!isset($this->global->MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING)) {
				$this->global->MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING = 1;
			}

			// Default max file size for upload (deprecated)
			//$this->maxfilesize = (empty($this->global->MAIN_UPLOAD_DOC) ? 0 : (int) $this->global->MAIN_UPLOAD_DOC * 1024);

			// By default, we propagate contacts
			if (!isset($this->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN)) {
				$this->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN = '*'; // Can be also '*' or '^(BILLING|SHIPPING|CUSTOMER|.*)$' (regex not yet implemented)
			}

			// By default, we do not use the zip town table but the table of third parties
			if (!isset($this->global->MAIN_USE_ZIPTOWN_DICTIONNARY)) {
				$this->global->MAIN_USE_ZIPTOWN_DICTIONNARY = 0;
			}

			// By default, we open card if one found
			if (!isset($this->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE)) {
				$this->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE = 1;
			}

			// By default, we show state code in combo list
			if (!isset($this->global->MAIN_SHOW_STATE_CODE)) {
				$this->global->MAIN_SHOW_STATE_CODE = 1;
			}

			// By default, we show state code in combo list
			if (!isset($this->global->MULTICURRENCY_USE_ORIGIN_TX)) {
				$this->global->MULTICURRENCY_USE_ORIGIN_TX = 1;
			}

			// By default, use an enclosure " for field with CRL or LF into content, + we also remove also CRL/LF chars.
			if (!isset($this->global->USE_STRICT_CSV_RULES)) {
				$this->global->USE_STRICT_CSV_RULES = 2;
			}

			// Use a SCA ready workflow with Stripe module (STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION by default if nothing defined)
			if (!isset($this->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION) && empty($this->global->STRIPE_USE_NEW_CHECKOUT)) {
				$this->global->STRIPE_USE_INTENT_WITH_AUTOMATIC_CONFIRMATION = 1;
			}

			// Define list of limited modules (value must be key found for "name" property of module, so for example 'supplierproposal' for Module "Supplier Proposal"
			if (!isset($this->global->MAIN_MODULES_FOR_EXTERNAL)) {
				$this->global->MAIN_MODULES_FOR_EXTERNAL = 'user,societe,propal,commande,facture,categorie,supplierproposal,fournisseur,contact,projet,contrat,ficheinter,expedition,reception,agenda,resource,adherent,blockedlog,ticket'; // '' means 'all'. Note that contact is added here as it should be a module later.
			}
			if (!empty($this->modules_parts['moduleforexternal'])) {		// Module part to include an external module into the MAIN_MODULES_FOR_EXTERNAL list
				foreach ($this->modules_parts['moduleforexternal'] as $key => $value) {
					$this->global->MAIN_MODULES_FOR_EXTERNAL .= ",".$key;
				}
			}

			// Enable select2
			if (empty($this->global->MAIN_USE_JQUERY_MULTISELECT) || $this->global->MAIN_USE_JQUERY_MULTISELECT == '1') {
				$this->global->MAIN_USE_JQUERY_MULTISELECT = 'select2';
			}

			// Timeouts
			if (empty($this->global->MAIN_USE_CONNECT_TIMEOUT)) {
				$this->global->MAIN_USE_CONNECT_TIMEOUT = 10;
			}
			if (empty($this->global->MAIN_USE_RESPONSE_TIMEOUT)) {
				$this->global->MAIN_USE_RESPONSE_TIMEOUT = 30;
			}

			// Set default variable to calculate VAT as if option tax_mode was 0 (standard)
			if (empty($this->global->TAX_MODE_SELL_PRODUCT)) {
				$this->global->TAX_MODE_SELL_PRODUCT = 'invoice';
			}
			if (empty($this->global->TAX_MODE_BUY_PRODUCT)) {
				$this->global->TAX_MODE_BUY_PRODUCT = 'invoice';
			}
			if (empty($this->global->TAX_MODE_SELL_SERVICE)) {
				$this->global->TAX_MODE_SELL_SERVICE = 'payment';
			}
			if (empty($this->global->TAX_MODE_BUY_SERVICE)) {
				$this->global->TAX_MODE_BUY_SERVICE = 'payment';
			}

			// Delay before warnings
			// Avoid strict errors. TODO: Replace xxx->warning_delay with a property ->warning_delay_xxx
			if (isset($this->agenda)) {
				$this->member->subscription = new stdClass();
				$this->member->subscription->warning_delay = (isset($this->global->MAIN_DELAY_MEMBERS) ? (int) $this->global->MAIN_DELAY_MEMBERS : 0) * 86400;
			}
			if (isset($this->agenda)) {
				$this->agenda->warning_delay = (isset($this->global->MAIN_DELAY_ACTIONS_TODO) ? (int) $this->global->MAIN_DELAY_ACTIONS_TODO : 7) * 86400;
			}
			if (isset($this->project)) {
				$this->project->warning_delay = (getDolGlobalInt('MAIN_DELAY_PROJECT_TO_CLOSE', 7) * 86400);
				$this->project->task = new StdClass();
				$this->project->task->warning_delay = (getDolGlobalInt('MAIN_DELAY_TASKS_TODO', 7) * 86400);
			}

			if (isset($this->order)) {
				$this->order->client = new stdClass();
				$this->order->fournisseur = new stdClass();
				$this->order->client->warning_delay = (isset($this->global->MAIN_DELAY_ORDERS_TO_PROCESS) ? (int) $this->global->MAIN_DELAY_ORDERS_TO_PROCESS : 2) * 86400;
				$this->order->fournisseur->warning_delay = (isset($this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS) ? (int) $this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS : 7) * 86400;
			}
			if (isset($this->propal)) {
				$this->propal->cloture = new stdClass();
				$this->propal->facturation = new stdClass();
				$this->propal->cloture->warning_delay = (isset($this->global->MAIN_DELAY_PROPALS_TO_CLOSE) ? (int) $this->global->MAIN_DELAY_PROPALS_TO_CLOSE : 0) * 86400;
				$this->propal->facturation->warning_delay = (isset($this->global->MAIN_DELAY_PROPALS_TO_BILL) ? (int) $this->global->MAIN_DELAY_PROPALS_TO_BILL : 0) * 86400;
			}
			if (isset($this->invoice)) {
				$this->invoice->client = new stdClass();
				$this->invoice->fournisseur = new stdClass();
				$this->invoice->client->warning_delay = (isset($this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED) ? (int) $this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED : 0) * 86400;
				$this->invoice->fournisseur->warning_delay = (isset($this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY) ? (int) $this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY : 0) * 86400;
			}
			if (isset($this->contract)) {
				$this->contract->services = new stdClass();
				$this->contract->services->inactifs = new stdClass();
				$this->contract->services->expires = new stdClass();
				$this->contract->services->inactifs->warning_delay = (isset($this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES) ? (int) $this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES : 0) * 86400;
				$this->contract->services->expires->warning_delay = (isset($this->global->MAIN_DELAY_RUNNING_SERVICES) ? (int) $this->global->MAIN_DELAY_RUNNING_SERVICES : 0) * 86400;
			}
			if (isset($this->order)) {
				$this->bank->rappro	= new stdClass();
				$this->bank->cheque	= new stdClass();
				$this->bank->rappro->warning_delay = (isset($this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE) ? (int) $this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE : 0) * 86400;
				$this->bank->cheque->warning_delay = (isset($this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT) ? (int) $this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT : 0) * 86400;
			}
			if (isset($this->expensereport)) {
				$this->expensereport->approve = new stdClass();
				$this->expensereport->approve->warning_delay = (isset($this->global->MAIN_DELAY_EXPENSEREPORTS) ? (int) $this->global->MAIN_DELAY_EXPENSEREPORTS : 0) * 86400;
				$this->expensereport->payment = new stdClass();
				$this->expensereport->payment->warning_delay = (isset($this->global->MAIN_DELAY_EXPENSEREPORTS_TO_PAY) ? (int) $this->global->MAIN_DELAY_EXPENSEREPORTS_TO_PAY : 0) * 86400;
			}
			if (isset($this->holiday)) {
				$this->holiday->approve = new stdClass();
				$this->holiday->approve->warning_delay = (isset($this->global->MAIN_DELAY_HOLIDAYS) ? (int) $this->global->MAIN_DELAY_HOLIDAYS : 0) * 86400;
			}

			if (!empty($this->global->PRODUIT_MULTIPRICES) && empty($this->global->PRODUIT_MULTIPRICES_LIMIT)) {
				$this->global->PRODUIT_MULTIPRICES_LIMIT = 5;
			}

			if (!isset($this->global->MAIN_CHECKBOX_LEFT_COLUMN)) {
				$this->global->MAIN_CHECKBOX_LEFT_COLUMN = 1;
			}

			// For modules that want to disable top or left menu
			if (!empty($this->global->MAIN_HIDE_TOP_MENU)) {
				$this->dol_hide_topmenu = $this->global->MAIN_HIDE_TOP_MENU;
			}
			if (!empty($this->global->MAIN_HIDE_LEFT_MENU)) {
				$this->dol_hide_leftmenu = $this->global->MAIN_HIDE_LEFT_MENU;
			}

			if (empty($this->global->MAIN_SIZE_SHORTLIST_LIMIT)) {
				$this->global->MAIN_SIZE_SHORTLIST_LIMIT = 3;
			}

			// Save inconsistent option
			if (empty($this->global->AGENDA_USE_EVENT_TYPE) && (!isset($this->global->AGENDA_DEFAULT_FILTER_TYPE) || $this->global->AGENDA_DEFAULT_FILTER_TYPE == 'AC_NON_AUTO')) {
				$this->global->AGENDA_DEFAULT_FILTER_TYPE = '0'; // 'AC_NON_AUTO' does not exists when AGENDA_DEFAULT_FILTER_TYPE is not on.
			}

			if (!isset($this->global->MAIN_JS_GRAPH)) {
				$this->global->MAIN_JS_GRAPH = 'chart'; // Use chart.js library
			}

			if (empty($this->global->MAIN_MODULE_DOLISTORE_API_SRV)) {
				$this->global->MAIN_MODULE_DOLISTORE_API_SRV = 'https://www.dolistore.com';
			}
			if (empty($this->global->MAIN_MODULE_DOLISTORE_API_KEY)) {
				$this->global->MAIN_MODULE_DOLISTORE_API_KEY = 'dolistorecatalogpublickey1234567';
			}

			// Enable by default the CSRF protection by token.
			if (!isset($this->global->MAIN_SECURITY_CSRF_WITH_TOKEN)) {
				// Value 1 makes CSRF check for all POST parameters only
				// Value 2 makes also CSRF check for GET requests with action = a sensitive requests like action=del, action=remove...
				// Value 3 makes also CSRF check for all GET requests with a param action or massaction (except some non sensitive values)
				$this->global->MAIN_SECURITY_CSRF_WITH_TOKEN = 2; // TODO Switch value to 3
				// Note: Set MAIN_SECURITY_CSRF_TOKEN_RENEWAL_ON_EACH_CALL=1 to have a renewal of token at each page call instead of each session (not recommended)
			}

			if (!isset($this->global->MAIN_MAIL_ADD_INLINE_IMAGES_IF_DATA)) {
				$this->global->MAIN_MAIL_ADD_INLINE_IMAGES_IF_DATA = 1;
			}

			if (!isset($this->global->MAIL_SMTP_USE_FROM_FOR_HELO)) {
				$this->global->MAIL_SMTP_USE_FROM_FOR_HELO = 2;	// Use the domain in $dolibarr_main_url_root (mydomain.com)
			}

			if (!defined('MAIN_ANTIVIRUS_BYPASS_COMMAND_AND_PARAM')) {
				if (defined('MAIN_ANTIVIRUS_COMMAND')) {
					$this->global->MAIN_ANTIVIRUS_COMMAND = constant('MAIN_ANTIVIRUS_COMMAND');
				}
				if (defined('MAIN_ANTIVIRUS_PARAM')) {
					$this->global->MAIN_ANTIVIRUS_PARAM = constant('MAIN_ANTIVIRUS_PARAM');
				}
			}

			// For backward compatibility
			if (!empty($this->global->LDAP_SYNCHRO_ACTIVE)) {
				if ($this->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap') {
					$this->global->LDAP_SYNCHRO_ACTIVE = 1;
				} elseif ($this->global->LDAP_SYNCHRO_ACTIVE == 'ldap2dolibarr') {
					$this->global->LDAP_SYNCHRO_ACTIVE = 2;
				}
			}
			// For backward compatibility
			if (!empty($this->global->LDAP_MEMBER_ACTIVE) && $this->global->LDAP_MEMBER_ACTIVE == 'ldap2dolibarr') {
				$this->global->LDAP_MEMBER_ACTIVE = 2;
			}
			// For backward compatibility
			if (!empty($this->global->LDAP_MEMBER_TYPE_ACTIVE) && $this->global->LDAP_MEMBER_TYPE_ACTIVE == 'ldap2dolibarr') {
				$this->global->LDAP_MEMBER_TYPE_ACTIVE = 2;
			}

			if (!empty($this->global->MAIN_TZUSERINPUTKEY)) {
				$this->tzuserinputkey = $this->global->MAIN_TZUSERINPUTKEY;	// 'tzserver' or 'tzuserrel'
			}

			if (!empty($this->global->PRODUIT_AUTOFILL_DESC)) {
				$this->global->MAIN_NO_CONCAT_DESCRIPTION = 1;
			} else {
				unset($this->global->MAIN_NO_CONCAT_DESCRIPTION);
			}

			// Object $mc
			if (!defined('NOREQUIREMC') && isModEnabled('multicompany')) {
				if (is_object($mc)) {
					$mc->setValues($this);
				}
			}

			if (isModEnabled('syslog')) {
				// We init log handlers
				if (!empty($this->global->SYSLOG_HANDLERS)) {
					$handlers = json_decode($this->global->SYSLOG_HANDLERS);
				} else {
					$handlers = array();
				}
				foreach ($handlers as $handler) {
					$handler_file_found = '';
					$dirsyslogs = array('/core/modules/syslog/');
					if (!empty($this->modules_parts['syslog']) && is_array($this->modules_parts['syslog'])) {
						$dirsyslogs = array_merge($dirsyslogs, $this->modules_parts['syslog']);
					}
					foreach ($dirsyslogs as $reldir) {
						$dir = dol_buildpath($reldir, 0);
						$newdir = dol_osencode($dir);
						if (is_dir($newdir)) {
							$file = $newdir.$handler.'.php';
							if (file_exists($file)) {
								$handler_file_found = $file;
								break;
							}
						}
					}

					if (empty($handler_file_found)) {
						// If log handler has been removed of is badly setup, we must be able to continue code.
						//throw new Exception('Missing log handler file '.$handler.'.php');
						continue;
					}

					require_once $handler_file_found;
					$loghandlerinstance = new $handler();
					if (!$loghandlerinstance instanceof LogHandler) {
						throw new Exception('Log handler does not extend LogHandler');
					}

					if (empty($this->loghandlers[$handler])) {
						$this->loghandlers[$handler] = $loghandlerinstance;
					}
				}
			}
		}

		// Overwrite database values from conf into the conf.php file.
		if (!empty($this->file->mailing_limit_sendbyweb)) {
			$this->global->MAILING_LIMIT_SENDBYWEB = $this->file->mailing_limit_sendbyweb;
		}
		if (empty($this->global->MAILING_LIMIT_SENDBYWEB)) {	// Limit by web can't be 0
			$this->global->MAILING_LIMIT_SENDBYWEB = 25;
		}
		if (!empty($this->file->mailing_limit_sendbycli)) {
			$this->global->MAILING_LIMIT_SENDBYCLI = $this->file->mailing_limit_sendbycli;
		}
		if (!empty($this->file->mailing_limit_sendbyday)) {
			$this->global->MAILING_LIMIT_SENDBYDAY = $this->file->mailing_limit_sendbyday;
		}

		return 0;
	}
}
