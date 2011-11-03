<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Xavier Dutoit        <doli@sydesy.com>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin      	<regis@dolibarr.fr>
 * Copyright (C) 2006 	   Jean Heimburger    	<jean@tiaris.info>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \class      Conf
 *  \brief      Class to stock current configuration
 */
class Conf
{
	/** \public */
	//! Object with database handler
	var $db;
	//! To store properties found in conf file
	var $file;
	//! To store properties found into database
	var $global;

	//! To store if javascript/ajax is enabked
	var $use_javascript_ajax;

	//! Used to store current currency
	var $monnaie;
	//! Used to store current css (from theme)
	var $theme;        // Contains current theme ("eldy", "auguria", ...)
	var $css;          // Contains full path of css page ("/theme/eldy/style.css.php", ...)
    //! Used to store current menu handlers
	var $top_menu;
	var $smart_menu;

	//! Used to store instance for multi-company (default 1)
	var $entity=1;

	var $css_modules			= array();
	var $tabs_modules			= array();
	var $triggers_modules		= array();
	var $hooks_modules			= array();
	public $login_method_modules	= array();
	var $modules				= array();
	var $entities				= array();

	var $logbuffer				= array();

	var $filesystem_forbidden_chars = array('<','>',':','/','\\','?','*','|','"');


	/**
	 * Constructor
	 *
	 * @return Conf
	 */
	function Conf()
	{
	    // Avoid warnings when filling this->xxx
	    $this->file=(object) array();
        $this->db=(object) array();
        $this->global=(object) array();
        $this->mycompany=(object) array();
        $this->admin=(object) array();
        $this->user=(object) array();
	    //! Charset for HTML output and for storing data in memory
	    $this->file->character_set_client='UTF-8';   // UTF-8, ISO-8859-1
	}


	/**
	 *      Load setup values into conf object (read llx_const)
	 *      @param      $db			    Handler d'acces base
	 *      @return     int         	< 0 if KO, >= 0 if OK
	 */
	function setValues($db)
	{
		dol_syslog("Conf::setValues");

		// Directory of core triggers
		$this->triggers_modules[] = "/core/triggers";	// Default relative path to triggers file

		// Avoid warning if not defined
		if (empty($this->db->dolibarr_main_db_encryption)) $this->db->dolibarr_main_db_encryption=0;
		if (empty($this->db->dolibarr_main_db_cryptkey))   $this->db->dolibarr_main_db_cryptkey='';

		/*
		 * Definition de toutes les constantes globales d'environnement
		 * - En constante php (TODO a virer)
		 * - En $this->global->key=value
		 */
		$sql = "SELECT ".$db->decrypt('name')." as name,";
		$sql.= " ".$db->decrypt('value')." as value, entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."const";
		$sql.= " WHERE entity IN (0,".$this->entity.")";
		$sql.= " ORDER BY entity";	// This is to have entity 0 first, then entity 1 that overwrite.

		$result = $db->query($sql);
		if ($result)
		{
			$numr = $db->num_rows($result);
			$multicompany_sharing=array();
			$i = 0;

			while ($i < $numr)
			{
				$objp = $db->fetch_object($result);
				$key=$objp->name;
				$value=$objp->value;
				if ($key)
				{
					if (! defined("$key")) define ("$key", $value);	// In some cases, the constant might be already forced (Example: SYSLOG_FILE_ON and SYSLOG_FILE during install)
					$this->global->$key=$value;

					if ($value && preg_match('/^MAIN_MODULE_/',$key))
					{
						// If this is constant for a css file activated by a module
						if (preg_match('/^MAIN_MODULE_([A-Z_]+)_CSS$/i',$key))
						{
							$this->css_modules[]=$value;
						}
						// If this is constant for a new tab page activated by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_TABS_/i',$key))
						{
							$params=explode(':',$value,2);
							$this->tabs_modules[$params[0]][]=$value;
							//print 'xxx'.$params[0].'-'.$value;
						}
						// If this is constant for triggers activated by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_TRIGGERS$/i',$key,$reg))
						{
							$modulename = strtolower($reg[1]);
							$this->triggers_modules[] = '/'.$modulename.'/core/triggers/';
						}
						// If this is constant for login method activated by a module
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_LOGIN_METHOD$/i',$key,$reg))
						{
							$modulename = strtolower($reg[1]);
							$this->login_method_modules[] = dol_buildpath('/'.$modulename.'/core/login/');
						}
						// If this is constant for hook activated by a module. Value is list of hooked tabs separated with :
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_HOOKS$/i',$key,$reg))
						{
							$modulename = strtolower($reg[1]);
							$params=explode(':',$value);
							foreach($params as $value)
							{
								$this->hooks_modules[$modulename][]=$value;
							}
						}
					    // If this is constant for a sms engine
                        elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)_SMS$/i',$key,$reg))
                        {
                            $module=strtolower($reg[1]);
                            // Add this module in list of modules that provide SMS
                            $this->sms_engine[$module]=$module;
                        }
						// If this is a module constant
						elseif (preg_match('/^MAIN_MODULE_([A-Z_]+)$/i',$key,$reg))
						{
							$module=strtolower($reg[1]);
							//print "Module ".$module." is enabled<br>\n";
							$this->$module=(object) array();
							$this->$module->enabled=true;
							// Add this module in list of enabled modules
							$this->modules[]=$module;
						}
					}
					// Sharings between entities
					else if ($value && preg_match('/^MULTICOMPANY_([A-Z_]+)_SHARING$/',$key,$reg))
					{
						$module=strtolower($reg[1]);
						$multicompany_sharing[$module]=$value;
					}
				}
				$i++;
			}

			// Sharings between entities
			if (isset($this->multicompany->enabled) && $this->multicompany->enabled && ! empty($multicompany_sharing))
			{
				$ret = @dol_include_once('/multicompany/class/actions_multicompany.class.php');
				if ($ret)
				{
					$mc = new ActionsMulticompany($db);

					foreach($multicompany_sharing as $key => $value)
					{
						$this->entities[$key]=$mc->check_entity($value);
					}
				}
			}
		}
		$db->free($result);
		//var_dump($this->modules);

		// Clean some variables
		if (empty($this->global->MAIN_MENU_STANDARD)) $this->global->MAIN_MENU_STANDARD="eldy_backoffice.php";
		if (empty($this->global->MAIN_MENUFRONT_STANDARD)) $this->global->MAIN_MENUFRONT_STANDARD="eldy_frontoffice.php";
		if (empty($this->global->MAIN_MENU_SMARTPHONE)) $this->global->MAIN_MENU_SMARTPHONE="eldy_backoffice.php";	// Use eldy by default because smartphone does not work on all phones
		if (empty($this->global->MAIN_MENUFRONT_SMARTPHONE)) $this->global->MAIN_MENUFRONT_SMARTPHONE="eldy_frontoffice.php";	// Ue eldy by default because smartphone does not work on all phones

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

		$rootfordata = DOL_DATA_ROOT;
		$rootforuser = DOL_DATA_ROOT;
		// If multicompany module is enabled, we redefine the root of data
		if (! empty($this->global->MAIN_MODULE_MULTICOMPANY) && ! empty($this->entity) && $this->entity > 1) $rootfordata.='/'.$this->entity;

		// For backward compatibility
		// TODO Replace this->xxx->enabled by this->modulename->enabled to remove this code
		if (isset($this->propale->enabled)) $this->propal->enabled=$this->propale->enabled;

		// Define default dir_output and dir_temp for directories of modules
		foreach($this->modules as $module)
		{
			$this->$module->dir_output=$rootfordata."/".$module;
			$this->$module->dir_temp=$rootfordata."/".$module."/temp";
		}

		// For mycompany setup
		$this->mycompany->dir_output=$rootfordata."/mycompany";
		$this->mycompany->dir_temp=$rootfordata."/mycompany/temp";

		// For admin features
		$this->admin->dir_output=$rootfordata.'/admin';
		$this->admin->dir_temp=$rootfordata.'/admin/temp';

		// Module user
		$this->user->dir_output=$rootforuser."/users";
		$this->user->dir_temp=$rootforuser."/users/temp";

		// Exception: Some dir are not the name of module. So we keep exception here
		// for backward compatibility.

		// Sous module bons d'expedition
		$this->expedition_bon->enabled=defined("MAIN_SUBMODULE_EXPEDITION")?MAIN_SUBMODULE_EXPEDITION:0;
		// Sous module bons de livraison
		$this->livraison_bon->enabled=defined("MAIN_SUBMODULE_LIVRAISON")?MAIN_SUBMODULE_LIVRAISON:0;

		// Module fournisseur
		$this->fournisseur->commande->dir_output=$rootfordata."/fournisseur/commande";
		$this->fournisseur->commande->dir_temp  =$rootfordata."/fournisseur/commande/temp";
		$this->fournisseur->facture->dir_output =$rootfordata."/fournisseur/facture";
		$this->fournisseur->facture->dir_temp   =$rootfordata."/fournisseur/facture/temp";
		// Module product/service
		$this->product->dir_output=$rootfordata."/produit";
		$this->product->dir_temp  =$rootfordata."/produit/temp";
		$this->service->dir_output=$rootfordata."/produit";
		$this->service->dir_temp  =$rootfordata."/produit/temp";
		// Module contrat
		$this->contrat->dir_output=$rootfordata."/contracts";
		$this->contrat->dir_temp=$rootfordata."/contracts/temp";


		/*
		 * Set some default values
		 */

		// societe
		if (empty($this->global->SOCIETE_CODECLIENT_ADDON))      $this->global->SOCIETE_CODECLIENT_ADDON="mod_codeclient_leopard";
		if (empty($this->global->SOCIETE_CODEFOURNISSEUR_ADDON)) $this->global->SOCIETE_CODEFOURNISSEUR_ADDON=$this->global->SOCIETE_CODECLIENT_ADDON;
		if (empty($this->global->SOCIETE_CODECOMPTA_ADDON))      $this->global->SOCIETE_CODECOMPTA_ADDON="mod_codecompta_panicum";
        if (empty($this->global->COMPANY_AQUARIUM_MASK_SUPPLIER)) $this->global->COMPANY_AQUARIUM_MASK_SUPPLIER='401';
		if (empty($this->global->COMPANY_AQUARIUM_MASK_CUSTOMER)) $this->global->COMPANY_AQUARIUM_MASK_CUSTOMER='411';

        // Security
		if (empty($this->global->USER_PASSWORD_GENERATED)) $this->global->USER_PASSWORD_GENERATED='standard'; // Default password generator
        if (empty($this->global->MAIN_UMASK)) $this->global->MAIN_UMASK='0664';         // Default mask

		// conf->box_max_lines
		$this->box_max_lines=5;
		if (isset($this->global->MAIN_BOXES_MAXLINES)) $this->box_max_lines=$this->global->MAIN_BOXES_MAXLINES;

		// conf->use_preview_tabs
		$this->use_preview_tabs=0;
		if (isset($this->global->MAIN_USE_PREVIEW_TABS)) $this->use_preview_tabs=$this->global->MAIN_USE_PREVIEW_TABS;

		// conf->use_javascript_ajax
		$this->use_javascript_ajax=1;
		if (isset($this->global->MAIN_DISABLE_JAVASCRIPT)) $this->use_javascript_ajax=! $this->global->MAIN_DISABLE_JAVASCRIPT;
		// If no javascript_ajax, Ajax features are disabled.
		if (! $this->use_javascript_ajax)
		{
			$this->global->PRODUIT_USE_SEARCH_TO_SELECT=0;
		}

		// conf->monnaie
		if (empty($this->global->MAIN_MONNAIE)) $this->global->MAIN_MONNAIE='EUR';
		$this->monnaie=$this->global->MAIN_MONNAIE;	// TODO deprecated
		$this->currency=$this->global->MAIN_MONNAIE;

		// $this->global->COMPTA_MODE = Option des modules Comptabilites (simple ou expert). Defini le mode de calcul des etats comptables (CA,...)
        if (empty($this->global->COMPTA_MODE)) $this->global->COMPTA_MODE='RECETTES-DEPENSES';  // By default. Can be 'RECETTES-DEPENSES' ou 'CREANCES-DETTES'

		// $this->liste_limit = constante de taille maximale des listes
		if (empty($this->global->MAIN_SIZE_LISTE_LIMIT)) $this->global->MAIN_SIZE_LISTE_LIMIT=25;
		$this->liste_limit=$this->global->MAIN_SIZE_LISTE_LIMIT;

		// $this->product->limit_size = constante de taille maximale des select de produit
		if (! isset($this->global->PRODUIT_LIMIT_SIZE)) $this->global->PRODUIT_LIMIT_SIZE=100;
		$this->product->limit_size=$this->global->PRODUIT_LIMIT_SIZE;

		// $this->theme et $this->css
		if (empty($this->global->MAIN_THEME)) $this->global->MAIN_THEME="eldy";
		$this->theme=$this->global->MAIN_THEME;
		$this->css  = "/theme/".$this->theme."/style.css.php";

		// $this->email_from = email pour envoi par dolibarr des mails automatiques
		$this->email_from = "dolibarr-robot@domain.com";
		if (! empty($this->global->MAIN_MAIL_EMAIL_FROM)) $this->email_from = $this->global->MAIN_MAIL_EMAIL_FROM;

		// $this->notification->email_from = email pour envoi par Dolibarr des notifications
		$this->notification->email_from=$this->email_from;
		if (! empty($this->global->NOTIFICATION_EMAIL_FROM)) $this->notification->email_from=$this->global->NOTIFICATION_EMAIL_FROM;

		// $this->mailing->email_from = email pour envoi par Dolibarr des mailings
		$this->mailing->email_from=$this->email_from;
		if (! empty($this->global->MAILING_EMAIL_FROM))	$this->mailing->email_from=$this->global->MAILING_EMAIL_FROM;

		// Defini MAIN_GRAPH_LIBRARY
		if (empty($this->global->MAIN_GRAPH_LIBRARY)) $this->global->MAIN_GRAPH_LIBRARY = 'artichow';

        if (! isset($this->global->FCKEDITOR_EDITORNAME)) $this->global->FCKEDITOR_EDITORNAME='ckeditor';  // fckeditor to switch

        // Format for date (used by default when not found or searched in lang)
        $this->format_date_short="%d/%m/%Y";            // Format of day with PHP/C tags (strftime functions)
        $this->format_date_short_java="dd/MM/yyyy";     // Format of day with Java tags
        $this->format_hour_short="%H:%M";
        $this->format_hour_short_duration="%H:%M";
        $this->format_date_text_short="%d %b %Y";
        $this->format_date_text="%d %B %Y";
        $this->format_date_hour_short="%d/%m/%Y %H:%M";
        $this->format_date_hour_text_short="%d %b %Y %H:%M";
        $this->format_date_hour_text="%d %B %Y %H:%M";

		// Limites decimales si non definie (peuvent etre egale a 0)
		if (! isset($this->global->MAIN_MAX_DECIMALS_UNIT))  $this->global->MAIN_MAX_DECIMALS_UNIT=5;
		if (! isset($this->global->MAIN_MAX_DECIMALS_TOT))   $this->global->MAIN_MAX_DECIMALS_TOT=2;
		if (! isset($this->global->MAIN_MAX_DECIMALS_SHOWN)) $this->global->MAIN_MAX_DECIMALS_SHOWN=8;

		// Timeouts
        if (empty($this->global->MAIN_USE_CONNECT_TIMEOUT)) $this->global->MAIN_USE_CONNECT_TIMEOUT=10;
        if (empty($this->global->MAIN_USE_RESPONSE_TIMEOUT)) $this->global->MAIN_USE_RESPONSE_TIMEOUT=30;

		// Set default variable to calculate VAT as if option tax_mode was 0 (standard)
        if (empty($this->global->TAX_MODE_SELL_PRODUCT)) $this->global->TAX_MODE_SELL_PRODUCT='invoice';
        if (empty($this->global->TAX_MODE_BUY_PRODUCT))  $this->global->TAX_MODE_BUY_PRODUCT='invoice';
        if (empty($this->global->TAX_MODE_SELL_SERVICE)) $this->global->TAX_MODE_SELL_SERVICE='payment';
        if (empty($this->global->TAX_MODE_BUY_SERVICE))  $this->global->TAX_MODE_BUY_SERVICE='payment';

		// Delay before warnings
		$this->actions->warning_delay=(isset($this->global->MAIN_DELAY_ACTIONS_TODO)?$this->global->MAIN_DELAY_ACTIONS_TODO:7)*24*60*60;
		$this->commande->client->warning_delay=(isset($this->global->MAIN_DELAY_ORDERS_TO_PROCESS)?$this->global->MAIN_DELAY_ORDERS_TO_PROCESS:2)*24*60*60;
        $this->commande->fournisseur->warning_delay=(isset($this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS)?$this->global->MAIN_DELAY_SUPPLIER_ORDERS_TO_PROCESS:7)*24*60*60;
		$this->propal->cloture->warning_delay=(isset($this->global->MAIN_DELAY_PROPALS_TO_CLOSE)?$this->global->MAIN_DELAY_PROPALS_TO_CLOSE:0)*24*60*60;
		$this->propal->facturation->warning_delay=(isset($this->global->MAIN_DELAY_PROPALS_TO_BILL)?$this->global->MAIN_DELAY_PROPALS_TO_BILL:0)*24*60*60;
		$this->facture->client->warning_delay=(isset($this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED)?$this->global->MAIN_DELAY_CUSTOMER_BILLS_UNPAYED:0)*24*60*60;
        $this->facture->fournisseur->warning_delay=(isset($this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY)?$this->global->MAIN_DELAY_SUPPLIER_BILLS_TO_PAY:0)*24*60*60;
		$this->contrat->services->inactifs->warning_delay=(isset($this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES)?$this->global->MAIN_DELAY_NOT_ACTIVATED_SERVICES:0)*24*60*60;
		$this->contrat->services->expires->warning_delay=(isset($this->global->MAIN_DELAY_RUNNING_SERVICES)?$this->global->MAIN_DELAY_RUNNING_SERVICES:0)*24*60*60;
		$this->adherent->cotisation->warning_delay=(isset($this->global->MAIN_DELAY_MEMBERS)?$this->global->MAIN_DELAY_MEMBERS:0)*24*60*60;
		$this->bank->rappro->warning_delay=(isset($this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE)?$this->global->MAIN_DELAY_TRANSACTIONS_TO_CONCILIATE:0)*24*60*60;
		$this->bank->cheque->warning_delay=(isset($this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT)?$this->global->MAIN_DELAY_CHEQUES_TO_DEPOSIT:0)*24*60*60;

		// For backward compatibility
		$this->produit=$this->product;


        // Define menu manager in setup
        if (empty($user->societe_id))    // If internal user or not defined
        {
            $this->top_menu=(empty($this->global->MAIN_MENU_STANDARD_FORCED)?$this->global->MAIN_MENU_STANDARD:$this->global->MAIN_MENU_STANDARD_FORCED);
            $this->smart_menu=(empty($this->global->MAIN_MENU_SMARTPHONE_FORCED)?$this->global->MAIN_MENU_SMARTPHONE:$this->global->MAIN_MENU_SMARTPHONE_FORCED);
        }
        else                        // If external user
        {
            $this->top_menu=(empty($this->global->MAIN_MENUFRONT_STANDARD_FORCED)?$this->global->MAIN_MENUFRONT_STANDARD:$this->global->MAIN_MENUFRONT_STANDARD_FORCED);
            $this->smart_menu=(empty($this->global->MAIN_MENUFRONT_SMARTPHONE_FORCED)?$this->global->MAIN_MENUFRONT_SMARTPHONE:$this->global->MAIN_MENUFRONT_SMARTPHONE_FORCED);
        }
        // For backward compatibility
        if ($this->top_menu == 'eldy.php') $this->top_menu='eldy_backoffice.php';
        elseif ($this->top_menu == 'rodolphe.php') $this->top_menu='eldy_backoffice.php';
	}
}

?>
