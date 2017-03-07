<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin      	<regis.houssin@capnetworks.com>
 * Copyright (C) 2006 	   Jean Heimburger    	<jean@tiaris.info>
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
 *	\file       	htdocs/core/class/conf.class.php
 *	\ingroup		core
 *  \brief      	File of class to manage storage of current setup
 *  				Config is stored into file conf.php
 */


/**
 *  Class to stock current configuration
 */
class Conf
{
	/** \public */
	//! To store properties found in conf file
	var $file;
	//! Object with database handler
	var $db;
	//! To store properties found into database
	var $global;

	//! To store if javascript/ajax is enabked
	public $use_javascript_ajax;
	//! Used to store current currency
	public $currency;
	//! Used to store current css (from theme)
	public $theme;        // Contains current theme ("eldy", "auguria", ...)
	public $css;          // Contains full path of css page ("/theme/eldy/style.css.php", ...)
    //! Used to store current menu handler
	public $standard_menu;

	public $modules					= array();	// List of activated modules
	public $modules_parts			= array('css'=>array(),'js'=>array(),'tabs'=>array(),'triggers'=>array(),'login'=>array(),'substitutions'=>array(),'menus'=>array(),'theme'=>array(),'sms'=>array(),'tpl'=>array(),'barcode'=>array(),'models'=>array(),'societe'=>array(),'hooks'=>array(),'dir'=>array(), 'syslog' =>array());

	var $logbuffer					= array();

	/**
	 * @var LogHandlerInterface[]
	 */
	var $loghandlers                = array();

	//! To store properties of multi-company
	public $multicompany;
	//! Used to store running instance for multi-company (default 1)
	public $entity					= 1;
	//! Used to store list of entities to use for each element
	public $entities				= array();

	public $dol_hide_topmenu;			// Set if we force param dol_hide_topmenu into login url
	public $dol_hide_leftmenu;			// Set if we force param dol_hide_leftmenu into login url
	public $dol_optimize_smallscreen;	// Set if we force param dol_optimize_smallscreen into login url or if browser is smartphone
	public $dol_no_mouse_hover;			// Set if we force param dol_no_mouse_hover into login url or if browser is smartphone
	public $dol_use_jmobile;			// Set if we force param dol_use_jmobile into login url


	/**
	 * Constructor
	 */
	function __construct()
	{
		// Properly declare multi-modules objects.
		$this->file				= new stdClass();
		$this->db				= new stdClass();
		$this->global			= new stdClass();
		$this->mycompany		= new stdClass();
		$this->admin			= new stdClass();
		$this->user				= new stdClass();
		$this->syslog			= new stdClass();
		$this->browser			= new stdClass();
		$this->multicompany		= new stdClass();

		//! Charset for HTML output and for storing data in memory
		$this->file->character_set_client='UTF-8';   // UTF-8, ISO-8859-1

		// First level object
		// TODO Remove this part.
		$this->expedition_bon	= new stdClass();
		$this->livraison_bon	= new stdClass();
		$this->fournisseur		= new stdClass();
		$this->product			= new stdClass();
		$this->service			= new stdClass();
		$this->contrat			= new stdClass();
		$this->actions			= new stdClass();
		$this->commande			= new stdClass();
		$this->propal			= new stdClass();
		$this->facture			= new stdClass();
		$this->contrat			= new stdClass();
		$this->adherent			= new stdClass();
		$this->bank				= new stdClass();
		$this->notification		= new stdClass();
		$this->mailing			= new stdClass();
		$this->expensereport    = new stdClass();
	}


	/**
	 *	Load setup values into conf object (read llx_const)
	 *  Note that this->db->xxx, this->file->xxx and this->multicompany have been already loaded when setValues is called.
	 *
	 *	@param      DoliDB		$db		Database handler
	 *	@return     int					< 0 if KO, >= 0 if OK
	 */
	function setValues($db)
	{
		global $conf;

		dol_syslog(get_class($this)."::setValues");

		/*
		 * Definition de toutes les constantes globales d'environnement
		 * - En constante php (TODO a virer)
		 * - En $this->global->key=value
		 */
		$sql = "SELECT ".$db->decrypt('name')." as name,";
		$sql.= " ".$db->decrypt('value')." as value, entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		if (! empty($this->multicompany->transverse_mode))
		{
			$sql.= " WHERE entity IN (0,1,".$this->entity.")";
		}
		else
		{
			$sql.= " WHERE entity IN (0,".$this->entity.")";
		}
		$sql.= " ORDER BY entity";	// This is to have entity 0 first, then entity 1 that overwrite.

		$resql = $db->query($sql);
		if ($resql)
		{
			$i = 0;
			$numr = $db->num_rows($resql);
			while ($i < $numr)
			{
				$objp = $db->fetch_object($resql);
				$key=$objp->name;
				$value=$objp->value;
				if ($key)
				{
					if (! defined("$key")) define("$key", $value);	// In some cases, the constant might be already forced (Example: SYSLOG_HANDLERS during install)
					$this->global->$key=$value;

					if ($value && preg_match('/^MAIN_MODULE_/',$key))
					{
						// If this is constant for a new tab page activated by a module. It initializes modules_parts['tabs'].
						if (preg_match('/^MAIN_MODULE_([0-9A-Z_]+)_TABS_/i',$key))
						{
							$partname = 'tabs';
							$params=explode(':',$value,2);
							if (! isset($this->modules_parts[$partname]) || ! is_array($this->modules_parts[$partname])) { $this->modules_parts[$partname] = array(); }
							$this->modules_parts[$partname][$params[0]][]=$value;	// $value may be a string or an array
						}
						// If this is constant for all generic part activated by a module. It initializes
						// modules_parts['login'], modules_parts['menus'], modules_parts['substitutions'], modules_parts['triggers'], modules_parts['tpl'],
						// modules_parts['models'], modules_parts['theme']
						// modules_parts['sms'],
						// modules_parts['css'], ...
						elseif (preg_match('/^MAIN_MODULE_([0-9A-Z_]+)_([A-Z]+)$/i',$key,$reg))
						{
							$modulename = strtolower($reg[1]);
							$partname = strtolower($reg[2]);
							if (! isset($this->modules_parts[$partname]) || ! is_array($this->modules_parts[$partname])) { $this->modules_parts[$partname] = array(); }
							$arrValue = json_decode($value,true);
							if (is_array($arrValue) && ! empty($arrValue)) $value = $arrValue;
							else if (in_array($partname,array('login','menus','substitutions','triggers','tpl'))) $value = '/'.$modulename.'/core/'.$partname.'/';
							else if (in_array($partname,array('models','theme'))) $value = '/'.$modulename.'/';
							else if (in_array($partname,array('sms'))) $value = $modulename;
							else if ($value == 1) $value = '/'.$modulename.'/core/modules/'.$partname.'/';	// ex: partname = societe
							$this->modules_parts[$partname] = array_merge($this->modules_parts[$partname], array($modulename => $value));	// $value may be a string or an array
						}
                        // If this is a module constant (must be at end)
						elseif (preg_match('/^MAIN_MODULE_([0-9A-Z_]+)$/i',$key,$reg))
						{
							$modulename=strtolower($reg[1]);
							if ($modulename == 'propale') $modulename='propal';
							if ($modulename == 'supplierproposal') $modulename='supplier_proposal';
							if (! isset($this->$modulename) || ! is_object($this->$modulename)) $this->$modulename=new stdClass();
							$this->$modulename->enabled=true;
							$this->modules[]=$modulename;              // Add this module in list of enabled modules
						}
					}
				}
				$i++;
			}

		    $db->free($resql);
		}

        // Include other local consts.php files and fetch their values to the corresponding database constants
        if (! empty($this->global->LOCAL_CONSTS_FILES)) {
            $filesList = explode(":", $this->global->LOCAL_CONSTS_FILES);
            foreach ($filesList as $file) {
                $file=dol_sanitizeFileName($file);
                include_once DOL_DOCUMENT_ROOT . "/".$file."/".$file."_consts.php";
                foreach ($file2bddconsts as $key=>$value) {
                    $this->global->$key=constant($value);
                }
            }
        }

		//var_dump($this->modules);
		//var_dump($this->modules_parts['theme']);

		// If you can't set timezone of your PHP, set this constant. Better is to set it to UTC.
		// In future, this constant will be forced to 'UTC' so PHP server timezone will not have effect anymore.
		//$this->global->MAIN_SERVER_TZ='Europe/Paris';
		if (! empty($this->global->MAIN_SERVER_TZ) && $this->global->MAIN_SERVER_TZ != 'auto')
		{
			try {
				date_default_timezone_set($this->global->MAIN_SERVER_TZ);
			}
			catch(Exception $e)
			{
				dol_syslog("Error: Bad value for parameter MAIN_SERVER_TZ=".$this->global->MAIN_SERVER_TZ, LOG_ERR);
			}
		}

		// Object $mc
		if (! defined('NOREQUIREMC') && ! empty($this->multicompany->enabled))
		{
			global $mc;
			$ret = @dol_include_once('/multicompany/class/actions_multicompany.class.php');
			if ($ret) $mc = new ActionsMulticompany($db);
		}

		// Clean some variables
		if (empty($this->global->MAIN_MENU_STANDARD)) $this->global->MAIN_MENU_STANDARD="eldy_menu.php";
		if (empty($this->global->MAIN_MENUFRONT_STANDARD)) $this->global->MAIN_MENUFRONT_STANDARD="eldy_menu.php";
		if (empty($this->global->MAIN_MENU_SMARTPHONE)) $this->global->MAIN_MENU_SMARTPHONE="eldy_menu.php";	// Use eldy by default because smartphone does not work on all phones
		if (empty($this->global->MAIN_MENUFRONT_SMARTPHONE)) $this->global->MAIN_MENUFRONT_SMARTPHONE="eldy_menu.php";	// Use eldy by default because smartphone does not work on all phones
		// Clean var use vat for company
		if (! isset($this->global->FACTURE_TVAOPTION)) $this->global->FACTURE_TVAOPTION=1;
		else if (! empty($this->global->FACTURE_TVAOPTION) && ! is_numeric($this->global->FACTURE_TVAOPTION))
		{
			// Old value of option, we clean to use new value (0 or 1)
			if ($this->global->FACTURE_TVAOPTION != "franchise") $this->global->FACTURE_TVAOPTION=1;
			else $this->global->FACTURE_TVAOPTION=0;
		}

		// Variable globales LDAP
		if (empty($this->global->LDAP_FIELD_FULLNAME)) $this->global->LDAP_FIELD_FULLNAME='';
		if (! isset($this->global->LDAP_KEY_USERS)) $this->global->LDAP_KEY_USERS=$this->global->LDAP_FIELD_FULLNAME;
		if (! isset($this->global->LDAP_KEY_GROUPS)) $this->global->LDAP_KEY_GROUPS=$this->global->LDAP_FIELD_FULLNAME;
		if (! isset($this->global->LDAP_KEY_CONTACTS)) $this->global->LDAP_KEY_CONTACTS=$this->global->LDAP_FIELD_FULLNAME;
		if (! isset($this->global->LDAP_KEY_MEMBERS)) $this->global->LDAP_KEY_MEMBERS=$this->global->LDAP_FIELD_FULLNAME;

		// Load translation object with current language
		if (empty($this->global->MAIN_LANG_DEFAULT)) $this->global->MAIN_LANG_DEFAULT="en_US";

        // By default, we repeat info on all tabs
		if (! isset($this->global->MAIN_REPEATCONTACTONEACHTAB)) $this->global->MAIN_REPEATCONTACTONEACHTAB=1;
		if (! isset($this->global->MAIN_REPEATADDRESSONEACHTAB)) $this->global->MAIN_REPEATADDRESSONEACHTAB=1;

		$rootfordata = DOL_DATA_ROOT;
		$rootforuser = DOL_DATA_ROOT;
		// If multicompany module is enabled, we redefine the root of data
		if (! empty($this->multicompany->enabled) && ! empty($this->entity) && $this->entity > 1)
		{
			$rootfordata.='/'.$this->entity;
		}

		// Define default dir_output and dir_temp for directories of modules
		foreach($this->modules as $module)
		{
		    //var_dump($module);
			// For multicompany sharings
			$this->$module->multidir_output	= array($this->entity => $rootfordata."/".$module);
			$this->$module->multidir_temp	= array($this->entity => $rootfordata."/".$module."/temp");
			// For backward compatibility
			$this->$module->dir_output	= $rootfordata."/".$module;
			$this->$module->dir_temp	= $rootfordata."/".$module."/temp";
		}

		// External modules storage
		if (! empty($this->modules_parts['dir']))
		{
			foreach($this->modules_parts['dir'] as $module => $dirs)
			{
				foreach($dirs as $type => $name)
				{
					$subdir=($type=='temp'?'/temp':'');
					// For multicompany sharings
					$varname = 'multidir_'.$type;
					$this->$module->$varname = array($this->entity => $rootfordata."/".$name.$subdir);
					// For backward compatibility
					$varname = 'dir_'.$type;
					$this->$module->$varname = $rootfordata."/".$name.$subdir;
				}
			}
		}

		// For mycompany storage
		$this->mycompany->dir_output=$rootfordata."/mycompany";
		$this->mycompany->dir_temp=$rootfordata."/mycompany/temp";

		// For admin storage
		$this->admin->dir_output=$rootfordata.'/admin';
		$this->admin->dir_temp=$rootfordata.'/admin/temp';

		// For user storage
		$this->user->multidir_output	= array($this->entity => $rootfordata."/users");
		$this->user->multidir_temp		= array($this->entity => $rootfordata."/users/temp");
		// For backward compatibility
		$this->user->dir_output=$rootforuser."/users";
		$this->user->dir_temp=$rootforuser."/users/temp";

		// For propal storage
		$this->propal->dir_output=$rootfordata."/propale";
		$this->propal->dir_temp=$rootfordata."/propale/temp";

		// Exception: Some dir are not the name of module. So we keep exception here
		// for backward compatibility.

		// Sous module bons d'expedition
		$this->expedition_bon->enabled= defined("MAIN_SUBMODULE_EXPEDITION")?MAIN_SUBMODULE_EXPEDITION:0;
		// Sous module bons de livraison
		$this->livraison_bon->enabled=defined("MAIN_SUBMODULE_LIVRAISON")?MAIN_SUBMODULE_LIVRAISON:0;

		// Module fournisseur
		if (! empty($this->fournisseur))
		{
			$this->fournisseur->commande=new stdClass();
			$this->fournisseur->commande->dir_output=$rootfordata."/fournisseur/commande";
			$this->fournisseur->commande->dir_temp  =$rootfordata."/fournisseur/commande/temp";
			$this->fournisseur->facture=new stdClass();
			$this->fournisseur->facture->dir_output =$rootfordata."/fournisseur/facture";
			$this->fournisseur->facture->dir_temp   =$rootfordata."/fournisseur/facture/temp";

			// To prepare split of module fournisseur into fournisseur + supplier_order + supplier_invoice
			if (! empty($this->fournisseur->enabled) && empty($this->global->MAIN_USE_NEW_SUPPLIERMOD))  // By default, if module supplier is on, we set new properties
			{
    			$this->supplier_order=new stdClass();
    			$this->supplier_order->enabled=1;
    			$this->supplier_order->dir_output=$rootfordata."/fournisseur/commande";
    			$this->supplier_order->dir_temp=$rootfordata."/fournisseur/commande/temp";
    			$this->supplier_invoice=new stdClass();
    			$this->supplier_invoice->enabled=1;
    			$this->supplier_order->dir_output=$rootfordata."/fournisseur/facture";
    			$this->supplier_order->dir_temp=$rootfordata."/fournisseur/facture/temp";
			}
		}

		// Module product/service
		$this->product->multidir_output=array($this->entity => $rootfordata."/produit");
		$this->product->multidir_temp  =array($this->entity => $rootfordata."/produit/temp");
		$this->service->multidir_output=array($this->entity => $rootfordata."/produit");
		$this->service->multidir_temp  =array($this->entity => $rootfordata."/produit/temp");
		// For backward compatibility
		$this->product->dir_output=$rootfordata."/produit";
		$this->product->dir_temp  =$rootfordata."/produit/temp";
		$this->service->dir_output=$rootfordata."/produit";
		$this->service->dir_temp  =$rootfordata."/produit/temp";

		// Module contrat
		$this->contrat->dir_output=$rootfordata."/contracts";
		$this->contrat->dir_temp  =$rootfordata."/contracts/temp";
		// Module bank
		$this->bank->dir_output=$rootfordata."/bank";
		$this->bank->dir_temp  =$rootfordata."/bank/temp";


		// Set some default values

		$this->global->MAIN_ACTIVATE_HTML5=1;

		// societe
		if (empty($this->global->SOCIETE_CODECLIENT_ADDON))       $this->global->SOCIETE_CODECLIENT_ADDON="mod_codeclient_leopard";
		if (empty($this->global->SOCIETE_CODECOMPTA_ADDON))       $this->global->SOCIETE_CODECOMPTA_ADDON="mod_codecompta_panicum";

		if (empty($this->global->CHEQUERECEIPTS_ADDON))           $this->global->CHEQUERECEIPTS_ADDON='mod_chequereceipt_mint';
		
        // Security
		if (empty($this->global->USER_PASSWORD_GENERATED)) $this->global->USER_PASSWORD_GENERATED='standard'; // Default password generator
        if (empty($this->global->MAIN_UMASK)) $this->global->MAIN_UMASK='0664';         // Default mask

		// conf->use_javascript_ajax
		$this->use_javascript_ajax=1;
		if (isset($this->global->MAIN_DISABLE_JAVASCRIPT)) $this->use_javascript_ajax=! $this->global->MAIN_DISABLE_JAVASCRIPT;
		// If no javascript_ajax, Ajax features are disabled.
		if (empty($this->use_javascript_ajax))
		{
			unset($this->global->PRODUIT_USE_SEARCH_TO_SELECT);
			unset($this->global->COMPANY_USE_SEARCH_TO_SELECT);
			unset($this->global->CONTACT_USE_SEARCH_TO_SELECT);
			unset($this->global->PROJECT_USE_SEARCH_TO_SELECT);
		}

		if (! empty($this->productbatch->enabled))
		{
			$this->global->STOCK_CALCULATE_ON_BILL=0;
			$this->global->STOCK_CALCULATE_ON_VALIDATE_ORDER=0;
			$this->global->STOCK_CALCULATE_ON_SHIPMENT=1;
			$this->global->STOCK_CALCULATE_ON_SHIPMENT_CLOSE=0;
			$this->global->STOCK_CALCULATE_ON_SUPPLIER_BILL=0;
			$this->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER=0;
			$this->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER=1;
		}

		// conf->currency
		if (empty($this->global->MAIN_MONNAIE)) $this->global->MAIN_MONNAIE='EUR';
		$this->currency=$this->global->MAIN_MONNAIE;

		// conf->global->ACCOUNTING_MODE = Option des modules Comptabilites (simple ou expert). Defini le mode de calcul des etats comptables (CA,...)
        if (empty($this->global->ACCOUNTING_MODE)) $this->global->ACCOUNTING_MODE='RECETTES-DEPENSES';  // By default. Can be 'RECETTES-DEPENSES' ou 'CREANCES-DETTES'

        // By default, suppliers objects can be linked to all projects
        $this->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS = 1;

        // MAIN_HTML_TITLE
        if (! isset($this->global->MAIN_HTML_TITLE)) $this->global->MAIN_HTML_TITLE='noapp,thirdpartynameonly,contactnameonly,projectnameonly';
        
		// conf->liste_limit = constante de taille maximale des listes
		if (empty($this->global->MAIN_SIZE_LISTE_LIMIT)) $this->global->MAIN_SIZE_LISTE_LIMIT=25;
		$this->liste_limit=$this->global->MAIN_SIZE_LISTE_LIMIT;

		// conf->product->limit_size = constante de taille maximale des select de produit
		if (! isset($this->global->PRODUIT_LIMIT_SIZE)) $this->global->PRODUIT_LIMIT_SIZE=1000;
		$this->product->limit_size=$this->global->PRODUIT_LIMIT_SIZE;

		// conf->theme et $this->css
		if (empty($this->global->MAIN_THEME)) $this->global->MAIN_THEME="eldy";
        if (! empty($this->global->MAIN_FORCETHEME)) $this->global->MAIN_THEME=$this->global->MAIN_FORCETHEME;
		$this->theme=$this->global->MAIN_THEME;
		$this->css  = "/theme/".$this->theme."/style.css.php";

		// conf->email_from = email pour envoi par dolibarr des mails automatiques
		$this->email_from = "robot@example.com";
		if (! empty($this->global->MAIN_MAIL_EMAIL_FROM)) $this->email_from = $this->global->MAIN_MAIL_EMAIL_FROM;

		// conf->notification->email_from = email pour envoi par Dolibarr des notifications
		$this->notification->email_from=$this->email_from;
		if (! empty($this->global->NOTIFICATION_EMAIL_FROM)) $this->notification->email_from=$this->global->NOTIFICATION_EMAIL_FROM;

		// conf->mailing->email_from = email pour envoi par Dolibarr des mailings
		$this->mailing->email_from=$this->email_from;
		if (! empty($this->global->MAILING_EMAIL_FROM))	$this->mailing->email_from=$this->global->MAILING_EMAIL_FROM;
		if (! isset($this->global->MAIN_EMAIL_ADD_TRACK_ID)) $this->global->MAIN_EMAIL_ADD_TRACK_ID=1;
		
        // Format for date (used by default when not found or not searched in lang)
        $this->format_date_short="%d/%m/%Y";            // Format of day with PHP/C tags (strftime functions)
        $this->format_date_short_java="dd/MM/yyyy";     // Format of day with Java tags
        $this->format_hour_short="%H:%M";
        $this->format_hour_short_duration="%H:%M";
        $this->format_date_text_short="%d %b %Y";
        $this->format_date_text="%d %B %Y";
        $this->format_date_hour_short="%d/%m/%Y %H:%M";
        $this->format_date_hour_sec_short="%d/%m/%Y %H:%M:%S";
        $this->format_date_hour_text_short="%d %b %Y %H:%M";
        $this->format_date_hour_text="%d %B %Y %H:%M";

        // Duration of workday
        if (! isset($this->global->MAIN_DURATION_OF_WORKDAY)) $this->global->MAIN_DURATION_OF_WORKDAY=86400;

		// Limites decimales si non definie (peuvent etre egale a 0)
		if (! isset($this->global->MAIN_MAX_DECIMALS_UNIT))  $this->global->MAIN_MAX_DECIMALS_UNIT=5;
		if (! isset($this->global->MAIN_MAX_DECIMALS_TOT))   $this->global->MAIN_MAX_DECIMALS_TOT=2;
		if (! isset($this->global->MAIN_MAX_DECIMALS_SHOWN)) $this->global->MAIN_MAX_DECIMALS_SHOWN=8;

		// Default pdf option
		if (! isset($this->global->MAIN_PDF_DASH_BETWEEN_LINES)) $this->global->MAIN_PDF_DASH_BETWEEN_LINES=1;    // use dash between lines
        if (! isset($this->global->PDF_ALLOW_HTML_FOR_FREE_TEXT)) $this->global->PDF_ALLOW_HTML_FOR_FREE_TEXT=1;  // allow html content into free footer text
		
		// Set default value to MAIN_SHOW_LOGO
		if (! isset($this->global->MAIN_SHOW_LOGO)) $this->global->MAIN_SHOW_LOGO=1;

		// Default max file size for upload
		$this->maxfilesize = (empty($this->global->MAIN_UPLOAD_DOC) ? 0 : $this->global->MAIN_UPLOAD_DOC * 1024);

		// By default, we propagate contacts
		if (! isset($this->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN)) $this->global->MAIN_PROPAGATE_CONTACTS_FROM_ORIGIN='*';  // Can be also '*' or '^(BILLING|SHIPPING|CUSTOMER|.*)$' (regex not yet implemented)

		// By default, we use the zip town autofill
		if (! isset($this->global->MAIN_USE_ZIPTOWN_DICTIONNARY)) $this->global->MAIN_USE_ZIPTOWN_DICTIONNARY=1;

		// Define list of limited modules (value must be key found for "name" property of module, so for example 'supplierproposal' for Module "Supplier Proposal"
		if (! isset($this->global->MAIN_MODULES_FOR_EXTERNAL)) $this->global->MAIN_MODULES_FOR_EXTERNAL='user,societe,propal,commande,facture,categorie,supplierproposal,fournisseur,contact,projet,contrat,ficheinter,expedition,agenda,resource,adherent';	// '' means 'all'. Note that contact is added here as it should be a module later.

		// Enable select2
		if (empty($this->global->MAIN_USE_JQUERY_MULTISELECT) || $this->global->MAIN_USE_JQUERY_MULTISELECT == '1') $this->global->MAIN_USE_JQUERY_MULTISELECT='select2';

		// Timeouts
        if (empty($this->global->MAIN_USE_CONNECT_TIMEOUT)) $this->global->MAIN_USE_CONNECT_TIMEOUT=10;
        if (empty($this->global->MAIN_USE_RESPONSE_TIMEOUT)) $this->global->MAIN_USE_RESPONSE_TIMEOUT=30;

		// Set default variable to calculate VAT as if option tax_mode was 0 (standard)
        if (empty($this->global->TAX_MODE_SELL_PRODUCT)) $this->global->TAX_MODE_SELL_PRODUCT='invoice';
        if (empty($this->global->TAX_MODE_BUY_PRODUCT))  $this->global->TAX_MODE_BUY_PRODUCT='invoice';
        if (empty($this->global->TAX_MODE_SELL_SERVICE)) $this->global->TAX_MODE_SELL_SERVICE='payment';
        if (empty($this->global->TAX_MODE_BUY_SERVICE))  $this->global->TAX_MODE_BUY_SERVICE='payment';

		// Delay before warnings
		// Avoid strict errors. TODO: Replace xxx->warning_delay with a property ->warning_delay_xxx
		if (isset($this->agenda)) {
		    $this->adherent->subscription = new stdClass();
            $this->adherent->subscription->warning_delay=(isset($this->global->MAIN_DELAY_MEMBERS)?$this->global->MAIN_DELAY_MEMBERS:0)*24*60*60;
		}
		if (isset($this->agenda)) $this->agenda->warning_delay=(isset($this->global->MAIN_DELAY_ACTIONS_TODO)?$this->global->MAIN_DELAY_ACTIONS_TODO:7)*24*60*60;
		if (isset($this->projet))
		{
		    $this->projet->warning_delay=(isset($this->global->MAIN_DELAY_PROJECT_TO_CLOSE)?$this->global->MAIN_DELAY_PROJECT_TO_CLOSE:7)*24*60*60;
		    $this->projet->task                 = new StdClass();
		    $this->projet->task->warning_delay=(isset($this->global->MAIN_DELAY_TASKS_TODO)?$this->global->MAIN_DELAY_TASKS_TODO:7)*24*60*60;
		}

        if (isset($this->commande)) {
            $this->commande->client				= new stdClass();
    		$this->commande->fournisseur		= new stdClass();
    		$this->commande->client->warning_delay=(isset($this->global->MAIN_DELAY_ORDERS_TO_PROCESS)?$this->global->MAIN_DELAY_ORDERS_TO_PROCESS:2)*24*60*60;
    		$this->commande->fournisseur->warning_delay=(isset($this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS)?$this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS:7)*24*60*60;
		}
		if (isset($this->propal)) {
		    $this->propal->cloture				= new stdClass();
    		$this->propal->facturation			= new stdClass();
	        $this->propal->cloture->warning_delay=(isset($this->global->MAIN_DELAY_PROPALS_TO_CLOSE)?$this->global->MAIN_DELAY_PROPALS_TO_CLOSE:0)*24*60*60;
            $this->propal->facturation->warning_delay=(isset($this->global->MAIN_DELAY_PROPALS_TO_BILL)?$this->global->MAIN_DELAY_PROPALS_TO_BILL:0)*24*60*60;
		}
		if (isset($this->facture)) {
		    $this->facture->client				= new stdClass();
    		$this->facture->fournisseur			= new stdClass();
            $this->facture->client->warning_delay=(isset($this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED)?$this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED:0)*24*60*60;
		    $this->facture->fournisseur->warning_delay=(isset($this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY)?$this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY:0)*24*60*60;
		}
		if (isset($this->contrat)) {
		    $this->contrat->services			= new stdClass();
    		$this->contrat->services->inactifs	= new stdClass();
	   	    $this->contrat->services->expires	= new stdClass();
		    $this->contrat->services->inactifs->warning_delay=(isset($this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES)?$this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES:0)*24*60*60;
            $this->contrat->services->expires->warning_delay=(isset($this->global->MAIN_DELAY_RUNNING_SERVICES)?$this->global->MAIN_DELAY_RUNNING_SERVICES:0)*24*60*60;
		}
		if (isset($this->commande)) {
		    $this->bank->rappro					= new stdClass();
    		$this->bank->cheque					= new stdClass();
            $this->bank->rappro->warning_delay=(isset($this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE)?$this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE:0)*24*60*60;
		    $this->bank->cheque->warning_delay=(isset($this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT)?$this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT:0)*24*60*60;
		}
		if (isset($this->expensereport)) {
		    $this->expensereport->approve		= new stdClass();
		    $this->expensereport->approve->warning_delay=(isset($this->global->MAIN_DELAY_EXPENSEREPORTS)?$this->global->MAIN_DELAY_EXPENSEREPORTS:0)*24*60*60;
		    $this->expensereport->payment		= new stdClass();
		    $this->expensereport->payment->warning_delay=(isset($this->global->MAIN_DELAY_EXPENSEREPORTS_TO_PAY)?$this->global->MAIN_DELAY_EXPENSEREPORTS_TO_PAY:0)*24*60*60;
		}
		
		if (! empty($this->global->PRODUIT_MULTIPRICES) && empty($this->global->PRODUIT_MULTIPRICES_LIMIT))
		{
		    $this->global->PRODUIT_MULTIPRICES_LIMIT = 5;
		}
		
		// For modules that want to disable top or left menu
		if (! empty($this->global->MAIN_HIDE_TOP_MENU)) $this->dol_hide_topmenu=$this->global->MAIN_HIDE_TOP_MENU;
		if (! empty($this->global->MAIN_HIDE_LEFT_MENU)) $this->dol_hide_leftmenu=$this->global->MAIN_HIDE_LEFT_MENU;

		if (empty($this->global->MAIN_SIZE_SHORTLIST_LIMIT)) $this->global->MAIN_SIZE_SHORTLIST_LIMIT=3;

		// For backward compatibility
		if (isset($this->product))   $this->produit=$this->product;
		if (isset($this->facture))   $this->invoice=$this->facture;
		if (isset($this->commande))  $this->order=$this->commande;
		if (isset($this->contrat))   $this->contract=$this->contrat;
		if (isset($this->categorie)) $this->category=$this->categorie;

        // Object $mc
        if (! defined('NOREQUIREMC') && ! empty($this->multicompany->enabled))
        {
        	if (is_object($mc)) $mc->setValues($this);
        }

		// We init log handlers
		if (defined('SYSLOG_HANDLERS')) {
			$handlers = json_decode(constant('SYSLOG_HANDLERS'));
		} else {
			$handlers = array();
		}
		foreach ($handlers as $handler) {
			$handler_files = array();
			$dirsyslogs = array_merge(array('/core/modules/syslog/'), $this->modules_parts['syslog']);
			foreach ($dirsyslogs as $reldir) {
				$dir = dol_buildpath($reldir, 0);
				$newdir = dol_osencode($dir);
				if (is_dir($newdir)) {
					$file = $newdir . $handler . '.php';
					if (file_exists($file)) {
						$handler_files[] = $file;
					}
				}
			}

			if (empty($handler_files)) {
				throw new Exception('Missing log handler file ' . $handler . '.php');
			}

			require_once $handler_files[0];
			$loghandlerinstance = new $handler();
			if (!$loghandlerinstance instanceof LogHandlerInterface) {
				throw new Exception('Log handler does not extend LogHandlerInterface');
			}

			if (empty($this->loghandlers[$handler])) {
				$this->loghandlers[$handler] = $loghandlerinstance;
			}
		}
	}
}

