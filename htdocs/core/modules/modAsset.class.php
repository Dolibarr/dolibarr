<?php
/* Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018      Alexandre Spangaro   <aspangaro@zendsi.com>
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
 * 	\defgroup   asset     Module Assets
 *  \brief      Asset module descriptor.
 *
 *  \file       htdocs/core/modules/modAsset.class.php
 *  \ingroup    asset
 *  \brief      Description and activation file for module Assets
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


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
		global $langs,$conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 51000;		// TODO Go on page https://wiki.dolibarr.org/index.php/List_of_modules_id to reserve id number for your module
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'asset';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','interface','other'
		// It is used to group modules by family in module setup page
		$this->family = "financial";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '70';
		// Gives the possibility to the module, to provide his own family info and position of this family (Overwrite $this->family and $this->module_position. Avoid this)
		//$this->familyinfo = array('myownfamily' => array('position' => '01', 'label' => $langs->trans("MyOwnFamily")));

		// Module label (no space allowed), used if translation string 'ModuleAssetsName' not found (MyModue is name of module).
		$this->name = preg_replace('/^mod/i','',get_class($this));
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
		$this->picto='generic';

		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /asset/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /asset/core/modules/barcode)
		// for specific css file (eg: /asset/css/assets.css.php)
		$this->module_parts = array();

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/asset/temp","/asset/subdir");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into assets/admin directory, to use to setup module.
		$this->config_page_url = array("setup.php@asset");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array();		// List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array();	// List of module ids to disable if this one is disabled
		$this->conflictwith = array();	// List of module class names as string this module is in conflict with
		$this->langfiles = array("assets");
		$this->phpmin = array(5,4);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(7,0);	// Minimum version of Dolibarr required by module
		$this->warnings_activation = array();                     // Warning to show when we activate module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		$this->warnings_activation_ext = array();                 // Warning to show when we activate an external module. array('always'='text') or array('FR'='textfr','ES'='textes'...)
		//$this->automatic_activation = array('FR'=>'AssetsWasAutomaticallyActivatedBecauseOfYourCountryChoice');
		//$this->always_enabled = true;								// If true, can't be disabled

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('ASSETS_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('ASSETS_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array(
			1=>array('ASSET_MYCONSTANT', 'chaine', 'avalue', 'This is a constant to add', 1, 'allentities', 1)
		);


		if (! isset($conf->asset) || ! isset($conf->asset->enabled))
		{
			$conf->asset=new stdClass();
			$conf->asset->enabled=0;
		}


		// Array to add new pages in new tabs
		$this->tabs = array();
		// Example:
		// $this->tabs[] = array('data'=>'objecttype:+tabname1:Title1:mylangfile@assets:$user->rights->assets->read:/assets/mynewtab1.php?id=__ID__');  					// To add a new tab identified by code tabname1
		// $this->tabs[] = array('data'=>'objecttype:+tabname2:SUBSTITUTION_Title2:mylangfile@assets:$user->rights->othermodule->read:/assets/mynewtab2.php?id=__ID__',  	// To add another new tab identified by code tabname2. Label will be result of calling all substitution functions on 'Title2' key.
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
		$this->dictionaries=array();
		/* Example:
		$this->dictionaries=array(
			'langs'=>'mylangfile@assets',
			'tabname'=>array(MAIN_DB_PREFIX."table1",MAIN_DB_PREFIX."table2",MAIN_DB_PREFIX."table3"),		// List of tables we want to see into dictonnary editor
			'tablib'=>array("Table1","Table2","Table3"),													// Label of tables
			'tabsql'=>array('SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table1 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table2 as f','SELECT f.rowid as rowid, f.code, f.label, f.active FROM '.MAIN_DB_PREFIX.'table3 as f'),	// Request to select fields
			'tabsqlsort'=>array("label ASC","label ASC","label ASC"),																					// Sort order
			'tabfield'=>array("code,label","code,label","code,label"),																					// List of fields (result of select to show dictionary)
			'tabfieldvalue'=>array("code,label","code,label","code,label"),																				// List of fields (list of fields to edit a record)
			'tabfieldinsert'=>array("code,label","code,label","code,label"),																			// List of fields (list of fields for insert)
			'tabrowid'=>array("rowid","rowid","rowid"),																									// Name of columns with primary key (try to always name it 'rowid')
			'tabcond'=>array($conf->assets->enabled,$conf->assets->enabled,$conf->assets->enabled)												// Condition to show each dictionary
		);
		*/


		// Boxes/Widgets
		// Add here list of php file(s) stored in assets/core/boxes that contains class to show a widget.
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
		$this->rights = array();		// Permission array used by this module

		$r=0;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Read assets';		// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read';				// In php code, permission will be checked by test if ($user->rights->asset->level1->level2)
		$this->rights[$r][5] = '';					// In php code, permission will be checked by test if ($user->rights->asset->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Create/Update assets';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->asset->level1->level2)
		$this->rights[$r][5] = '';					// In php code, permission will be checked by test if ($user->rights->asset->level1->level2)

		$r++;
		$this->rights[$r][0] = $this->numero + $r;	// Permission id (must not be already used)
		$this->rights[$r][1] = 'Delete assets';		// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';			// In php code, permission will be checked by test if ($user->rights->asset->level1->level2)
		$this->rights[$r][5] = '';					// In php code, permission will be checked by test if ($user->rights->asset->level1->level2)


		// Main menu entries
		$this->menu = array();			// List of menus to add
		$r=0;

		// Add here entries to declare new menus
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.

		// Exports
		//--------
		$r=1;

		// $this->export_code[$r]          Code unique identifiant l'export (tous modules confondus)
		// $this->export_label[$r]         Libelle par defaut si traduction de cle "ExportXXX" non trouvee (XXX = Code)
		// $this->export_permission[$r]    Liste des codes permissions requis pour faire l'export
		// $this->export_fields_sql[$r]    Liste des champs exportables en codif sql
		// $this->export_fields_name[$r]   Liste des champs exportables en codif traduction
		// $this->export_sql[$r]           Requete sql qui offre les donnees a l'export

		/*
		$r++;
		$this->export_code[$r]=$this->rights_class.'_'.$r;
		$this->export_label[$r]='AssetsLines';
		$this->export_permission[$r]=array(array("asset","export"));
		$this->export_fields_array[$r]=array(
			'a.rowid'=>'Id','a.civility'=>"UserTitle",'a.lastname'=>"Lastname",'a.firstname'=>"Firstname",'a.login'=>"Login",'a.morphy'=>'Nature',
			'a.societe'=>'Company','a.address'=>"Address",'a.zip'=>"Zip",'a.town'=>"Town",'d.nom'=>"State",'co.code'=>"CountryCode",'co.label'=>"Country",
			'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",'a.email'=>"Email",'a.birth'=>"Birthday",'a.statut'=>"Status",
			'a.photo'=>"Photo",'a.note_public'=>"NotePublic",'a.note_private'=>"NotePrivate",'a.datec'=>'DateCreation','a.datevalid'=>'DateValidation',
			'a.tms'=>'DateLastModification','a.datefin'=>'DateEndSubscription','ta.rowid'=>'AssetTypeId','ta.label'=>'AssetTypeLabel',
			'ta.accountancy_code_asset'=>'AccountancyCodeAsset','ta.accountancy_code_depreciation_asset'=>'AccountancyCodeDepreciationAsset',
			'ta.accountancy_code_depreciation_expense'=>'AccountancyCodeDepreciationExpense'
		);
		$this->export_TypeFields_array[$r]=array(
			'a.civility'=>"Text",'a.lastname'=>"Text",'a.firstname'=>"Text",'a.login'=>"Text",'a.morphy'=>'Text','a.societe'=>'Text','a.address'=>"Text",
			'a.zip'=>"Text",'a.town'=>"Text",'d.nom'=>"Text",'co.code'=>'Text','co.label'=>"Text",'a.phone'=>"Text",'a.phone_perso'=>"Text",'a.phone_mobile'=>"Text",
			'a.email'=>"Text",'a.birth'=>"Date",'a.statut'=>"Status",'a.note_public'=>"Text",'a.note_private'=>"Text",'a.datec'=>'Date','a.datevalid'=>'Date',
			'a.tms'=>'Date','a.datefin'=>'Date','ta.rowid'=>'List:asset_type:label','ta.label'=>'Text','ta.accountancy_code_asset'=>'Text','ta.accountancy_code_depreciation_asset'=>'Text',
			'ta.accountancy_code_depreciation_expense'=>'Text'
		);
		$this->export_entities_array[$r]=array(
			'a.rowid'=>'member','a.civility'=>"member",'a.lastname'=>"member",'a.firstname'=>"member",'a.login'=>"member",'a.morphy'=>'member',
			'a.societe'=>'member','a.address'=>"member",'a.zip'=>"member",'a.town'=>"member",'d.nom'=>"member",'co.code'=>"member",'co.label'=>"member",
			'a.phone'=>"member",'a.phone_perso'=>"member",'a.phone_mobile'=>"member",'a.email'=>"member",'a.birth'=>"member",'a.statut'=>"member",
			'a.photo'=>"member",'a.note_public'=>"member",'a.note_private'=>"member",'a.datec'=>'member','a.datevalid'=>'member','a.tms'=>'member',
			'a.datefin'=>'member','ta.rowid'=>'asset_type','ta.label'=>'asset_type','ta.accountancy_code_asset'=>'asset_type','ta.accountancy_code_depreciation_asset'=>'asset_type',
			'ta.accountancy_code_depreciation_expense'=>'asset_type'
		);
		// Add extra fields
		$keyforselect='asset'; $keyforelement='asset'; $keyforaliasextra='extra';
		include DOL_DOCUMENT_ROOT.'/core/extrafieldsinexport.inc.php';
		// End add axtra fields
		$this->export_sql_start[$r]='SELECT DISTINCT ';
		$this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'asset_type as ta, '.MAIN_DB_PREFIX.'asset as a)';
		$this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'asset_extrafields as extra ON a.rowid = extra.fk_object';
		$this->export_sql_end[$r] .=' WHERE a.fk_asset_type = ta.rowid AND ta.entity IN ('.getEntity('asset_type').') ';

		// Imports
		//--------
		$r=0;

		$now=dol_now();
		require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		$r++;
		$this->import_code[$r]=$this->rights_class.'_'.$r;
		$this->import_label[$r]="Assets"; // Translation key
		$this->import_icon[$r]=$this->picto;
		$this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
		$this->import_tables_array[$r]=array('a'=>MAIN_DB_PREFIX.'asset','extra'=>MAIN_DB_PREFIX.'asset_extrafields');
		$this->import_tables_creator_array[$r]=array('a'=>'fk_user_author');    // Fields to store import user id
		$this->import_fields_array[$r]=array(
			'a.civility'=>"UserTitle",'a.lastname'=>"Lastname*",'a.firstname'=>"Firstname",'a.login'=>"Login*","a.pass"=>"Password",
			"a.fk_adherent_type"=>"MemberType*",'a.morphy'=>'Nature*','a.societe'=>'Company','a.address'=>"Address",'a.zip'=>"Zip",'a.town'=>"Town",
			'a.state_id'=>'StateId','a.country'=>"CountryId",'a.phone'=>"PhonePro",'a.phone_perso'=>"PhonePerso",'a.phone_mobile'=>"PhoneMobile",
			'a.email'=>"Email",'a.birth'=>"Birthday",'a.statut'=>"Status*",'a.photo'=>"Photo",'a.note_public'=>"NotePublic",'a.note_private'=>"NotePrivate",
			'a.datec'=>'DateCreation','a.datefin'=>'DateEndSubscription'
		);
		// Add extra fields
		$sql="SELECT name, label, fieldrequired FROM ".MAIN_DB_PREFIX."extrafields WHERE elementtype = 'asset' AND entity = ".$conf->entity;
		$resql=$this->db->query($sql);
		if ($resql)    // This can fail when class is used on old database (during migration for example)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$fieldname='extra.'.$obj->name;
				$fieldlabel=ucfirst($obj->label);
				$this->import_fields_array[$r][$fieldname]=$fieldlabel.($obj->fieldrequired?'*':'');
			}
		}
		// End add extra fields
		$this->import_fieldshidden_array[$r]=array('extra.fk_object'=>'lastrowid-'.MAIN_DB_PREFIX.'asset');    // aliastable.field => ('user->id' or 'lastrowid-'.tableparent)
		$this->import_regex_array[$r]=array(
			'a.civility'=>'code@'.MAIN_DB_PREFIX.'c_civility','a.fk_adherent_type'=>'rowid@'.MAIN_DB_PREFIX.'adherent_type',
			'a.morphy'=>'(phy|mor)','a.statut'=>'^[0|1]','a.datec'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$',
			'a.datefin'=>'^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$'
		);
		$this->import_examplevalues_array[$r]=array(
			'a.civility'=>"MR",'a.lastname'=>'Smith','a.firstname'=>'John','a.login'=>'jsmith','a.pass'=>'passofjsmith','a.fk_adherent_type'=>'1',
			'a.morphy'=>'"mor" or "phy"','a.societe'=>'JS company','a.address'=>'21 jump street','a.zip'=>'55000','a.town'=>'New York','a.country'=>'1',
			'a.email'=>'jsmith@example.com','a.birth'=>'1972-10-10','a.statut'=>"0 or 1",'a.note_public'=>"This is a public comment on member",
			'a.note_private'=>"This is private comment on member",'a.datec'=>dol_print_date($now,'%Y-%m__%d'),'a.datefin'=>dol_print_date(dol_time_plus_duree($now, 1, 'y'),'%Y-%m-%d')
		);
		*/
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories
	 *
	 *	@param      string	$options    Options when enabling module ('', 'noboxes')
	 *	@return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql,$options);
	}
}
