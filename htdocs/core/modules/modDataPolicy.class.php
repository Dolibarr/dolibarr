<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Nicolas ZABOURI      <info@inovea-conseil.com>
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
 * 	\defgroup   datapolicy     Module data policy
 *  \brief      Data policy module descriptor.
 *
 *  \file       htdocs/core/modules/modDataPolicy.class.php
 *  \ingroup    datapolicy
 *  \brief      Description and activation file for the module datapolicy
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';



// The class name should start with a lower case mod for Dolibarr to pick it up
// so we ignore the Squiz.Class.ValidClassName.NotCamelCaps rule.
// @codingStandardsIgnoreStart
/**
 *  Description and activation class for module datapolicy
 */
class modDataPolicy extends DolibarrModules
{
	// @codingStandardsIgnoreEnd
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
		$this->numero = 4100;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'datapolicy';

		// Family can be 'base' (core modules),'crm','financial','hr','projects','products','ecm','technic' (transverse modules),'interface' (link with external tools),'other','...'
		// It is used to group modules by family in module setup page
		$this->family = "technic";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '78';
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));
		// Module label (no space allowed), used if translation string 'ModuledatapolicyName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuledatapolicyDesc' not found (MyModue is name of module).
		$this->description = "Module to manage Data policy (for compliance with GDPR in Europe or other Data policy rules)";
		// Used only if file README.md and README-LL.md not found.
		$this->descriptionlong = "";

		// Possible values for version are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'
		$this->version = 'experimental';
		// Key used in llx_const table to save module status enabled/disabled (where datapolicy is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto = 'generic';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /datapolicy/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /datapolicy/core/modules/barcode)
		// for specific css file (eg: /datapolicy/css/datapolicy.css.php)
		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/datapolicy/temp","/datapolicy/subdir");
		$this->dirs = array("/datapolicy/temp");

		// Config pages. Put here list of php page, stored into datapolicy/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@datapolicy");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array('always'=>'modCron'); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->langfiles = array("datapolicy");
		$this->phpmin = array(5, 3); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(7, 0); // Minimum version of Dolibarr required by module
		$this->warnings_activation = array(); // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array(); // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'datapolicyWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled
		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('datapolicy_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('datapolicy_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			array('DATAPOLICY_TIERS_CLIENT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_TIERS_PROSPECT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_TIERS_PROSPECT_CLIENT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_TIERS_NIPROSPECT_NICLIENT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_TIERS_FOURNISSEUR', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_CONTACT_CLIENT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_CONTACT_PROSPECT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_CONTACT_PROSPECT_CLIENT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_CONTACT_NIPROSPECT_NICLIENT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_CONTACT_FOURNISSEUR', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
			array('DATAPOLICY_ADHERENT', 'chaine', '', $langs->trans('NUMBER_MONTH_BEFORE_DELETION'), 0),
		);

		$country = explode(":", !getDolGlobalString('MAIN_INFO_SOCIETE_COUNTRY') ? '' : $conf->global->MAIN_INFO_SOCIETE_COUNTRY);

		// Some keys to add into the overwriting translation tables
		/* $this->overwrite_translation = array(
		  'en_US:ParentCompany'=>'Parent company or reseller',
		  'fr_FR:ParentCompany'=>'Maison mère ou revendeur'
		  ) */

		if (!isset($conf->datapolicy) || !isset($conf->datapolicy->enabled)) {
			$conf->datapolicy = new stdClass();
			$conf->datapolicy->enabled = 0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@datapolicy:$user->rights->datapolicy->read:/datapolicy/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@datapolicy:$user->rights->othermodule->read:/datapolicy/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		// Add here list of php file(s) stored in datapolicy/core/boxes that contains class to show a widget.
		$this->boxes = array();


		// Cronjobs (List of cron jobs entries to add when module is enabled)
		// unit_frequency must be 60 for minute, 3600 for hour, 86400 for day, 604800 for week
		$this->cronjobs = array(
			0 => array('label' => 'DATAPOLICYJob', 'jobtype' => 'method', 'class' => 'datapolicy/class/datapolicycron.class.php', 'objectname' => 'DataPolicyCron', 'method' => 'cleanDataForDataPolicy', 'parameters' => '', 'comment' => 'Clean data', 'frequency' => 1, 'unitfrequency' => 86400, 'status' => 1, 'test' => '$conf->datapolicy->enabled'),
		);
		// Example: $this->cronjobs=array(0=>array('label'=>'My label', 'jobtype'=>'method', 'class'=>'/dir/class/file.class.php', 'objectname'=>'MyClass', 'method'=>'myMethod', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>2, 'unitfrequency'=>3600, 'status'=>0, 'test'=>true),
		//                                1=>array('label'=>'My label', 'jobtype'=>'command', 'command'=>'', 'parameters'=>'param1, param2', 'comment'=>'Comment', 'frequency'=>1, 'unitfrequency'=>3600*24, 'status'=>0, 'test'=>true)
		// );
		// Permissions
		$this->rights = array(); // Permission array used by this module
		// Main menu entries
		$this->menu = array(); // List of menus to add
		$r = 0;
	}

	/**
	 * 	Function called when module is enabled.
	 * 	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 * 	It also creates data directories
	 *
	 * 	@param      string	$options    Options when enabling module ('', 'noboxes')
	 * 	@return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $langs;

		// Create extrafields
		include_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		/*
		// Extrafield contact
		$result1 = $extrafields->addExtraField('datapolicy_consentement', $langs->trans("DATAPOLICY_consentement"), 'boolean', 101, 3, 'thirdparty', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_opposition_traitement', $langs->trans("DATAPOLICY_opposition_traitement"), 'boolean', 102, 3, 'thirdparty', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_opposition_prospection', $langs->trans("DATAPOLICY_opposition_prospection"), 'boolean', 103, 3, 'thirdparty', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_date', $langs->trans("DATAPOLICY_date"), 'date', 104, 3, 'thirdparty', 0, 0, '', '', 1, '', '3', 0);
		$result1 = $extrafields->addExtraField('datapolicy_send', $langs->trans("DATAPOLICY_send"), 'date', 105, 3, 'thirdparty', 0, 0, '', '', 0, '', '0', 0);

		// Extrafield Tiers
		$result1 = $extrafields->addExtraField('datapolicy_consentement', $langs->trans("DATAPOLICY_consentement"), 'boolean', 101, 3, 'contact', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_opposition_traitement', $langs->trans("DATAPOLICY_opposition_traitement"), 'boolean', 102, 3, 'contact', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_opposition_prospection', $langs->trans("DATAPOLICY_opposition_prospection"), 'boolean', 103, 3, 'contact', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_date', $langs->trans("DATAPOLICY_date"), 'date', 104, 3, 'contact', 0, 0, '', '', 1, '', '3', 0);
		$result1 = $extrafields->addExtraField('datapolicy_send', $langs->trans("DATAPOLICY_send"), 'date', 105, 3, 'contact', 0, 0, '', '', 0, '', '0', 0);

		// Extrafield Adherent
		$result1 = $extrafields->addExtraField('datapolicy_consentement', $langs->trans("DATAPOLICY_consentement"), 'boolean', 101, 3, 'adherent', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_opposition_traitement', $langs->trans("DATAPOLICY_opposition_traitement"), 'boolean', 102, 3, 'adherent', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_opposition_prospection', $langs->trans("DATAPOLICY_opposition_prospection"), 'boolean', 103, 3, 'adherent', 0, 0, '', '', 1, '', '3', 0, '', '', 'datapolicy', '$conf->datapolicy->enabled');
		$result1 = $extrafields->addExtraField('datapolicy_date', $langs->trans("DATAPOLICY_date"), 'date', 104, 3, 'adherent', 0, 0, '', '', 1, '', '3', 0);
		$result1 = $extrafields->addExtraField('datapolicy_send', $langs->trans("DATAPOLICY_send"), 'date', 105, 3, 'adherent', 0, 0, '', '', 0, '', '0', 0);
		*/

		$sql = array();

		return $this->_init($sql, $options);
	}

	/**
	 * 	Function called when module is disabled.
	 * 	Remove from database constants, boxes and permissions from Dolibarr database.
	 * 	Data directories are not deleted
	 *
	 * 	@param      string	$options    Options when enabling module ('', 'noboxes')
	 * 	@return     int             	1 if OK, 0 if KO
	 */
	public function remove($options = '')
	{
		$sql = array();

		return $this->_remove($sql, $options);
	}
}
