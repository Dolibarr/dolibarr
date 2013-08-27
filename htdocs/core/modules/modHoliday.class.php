<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Dimitri Mouillard 	<dmouillard@teclib.com>
 * Copyright (C) 2013      Juanjo Menent		<jmenent@2byte.es>
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
 * or see http://www.gnu.org/
 */

/**
 * 	  \defgroup   holiday 	Module holiday
 *    \brief      Module de gestion des congés
 *    \file       htdocs/core/modules/modHoliday.class.php
 *    \ingroup    holiday
 *    \brief      Description and activation file for module holiday
 */
include_once(DOL_DOCUMENT_ROOT ."/core/modules/DolibarrModules.class.php");


/**
 *		Description and activation class for module holiday
 */
class modHoliday extends DolibarrModules
{
	/**
	 *  Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 20000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'holiday';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "hr";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Leave management";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=others,3=very specific)
		$this->special = 0;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png, use this->picto='pictovalue@module'
		$this->picto='holiday';

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
		$this->triggers = 0;

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/mymodule/temp");
		$this->dirs = array();
		$r=0;

		// Relative path to module style sheet if exists. Example: '/mymodule/css/mycss.css'.
		//$this->style_sheet = '/mymodule/mymodule.css.php';

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("holiday.php?leftmenu=setup@holiday");

		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,3);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,0);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("holiday");

		// Constants
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',0),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0) );
		//                             2=>array('MAIN_MODULE_MYMODULE_NEEDSMARTY','chaine',1,'Constant to say module need smarty',0)
		$this->const = array();			// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 0 or 'allentities')

		// Array to add new pages in new tabs
		// Example: $this->tabs = array('objecttype:+tabname1:Title1:@mymodule:$user->rights->mymodule->read:/mymodule/mynewtab1.php?id=__ID__',  // To add a new tab identified by code tabname1
        //                              'objecttype:+tabname2:Title2:@mymodule:$user->rights->othermodule->read:/mymodule/mynewtab2.php?id=__ID__',  // To add another new tab identified by code tabname2
        //                              'objecttype:-tabname');                                                     // To remove an existing tab identified by code tabname
		// where objecttype can be
		// 'thirdparty'       to add a tab in third party view
		// 'intervention'     to add a tab in intervention view
		// 'order_supplier'   to add a tab in supplier order view
		// 'invoice_supplier' to add a tab in supplier invoice view
		// 'invoice'          to add a tab in customer invoice view
		// 'order'            to add a tab in customer order view
		// 'product'          to add a tab in product view
		// 'stock'            to add a tab in stock view
		// 'propal'           to add a tab in propal view
		// 'member'           to add a tab in fundation member view
		// 'contract'         to add a tab in contract view
		// 'user'             to add a tab in user view
		// 'group'            to add a tab in group view
		// 'contact'          to add a tab in contact view
		// 'categories_x'	  to add a tab in category view (replace 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)
		$this->tabs = array('user:+paidholidays:CPTitreMenu:holiday:$user->rights->holiday->write:/holiday/index.php?mainmenu=holiday&id=__ID__');

		// Boxes
		$this->boxes = array();			// List of boxes
		$r=0;

		// Add here list of php file(s) stored in includes/boxes that contains class to show a box.
		// Example:
		//$this->boxes[$r][1] = "myboxa.php";
		//$r++;
		//$this->boxes[$r][1] = "myboxb.php";
		//$r++;


		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		$this->rights[$r][0] = 20001; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire/créer/modifier ses demandes de congés payés';	// Permission label
		$this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'write';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20002; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Lire/créer/modifier toutes les demandes de congés payés';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'lire_tous';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20003; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Supprimer des demandes de congés payés';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'delete';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20004; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Définir les congés payés des utilisateurs';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'define_holiday';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20005; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Voir les logs de modification des congés payés';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'view_log';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		$this->rights[$r][0] = 20006; 				// Permission id (must not be already used)
		$this->rights[$r][1] = 'Accéder au rapport mensuel des congés payés';	// Permission label
		$this->rights[$r][3] = 0; 					// Permission by default for new user (0/1)
		$this->rights[$r][4] = 'month_report';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$this->rights[$r][5] = '';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		$r++;

		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;

		
		/* Move to HRM menu
		// Add here entries to declare new menus
		$this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
								'type'=>'top',			// This is a Top menu entry
								'titre'=>'CPTitreMenu',
								'mainmenu'=>'holiday',
								'leftmenu'=>'holiday',
								'url'=>'/holiday/index.php',
								'langs'=>'holiday',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>100,
								'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->holiday->write',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=holiday',			// Put 0 if this is a top menu
								'type'=>'left',			// This is a Top menu entry
								'titre'=>'CPTitreMenu',
								'mainmenu'=>'holiday',
								'leftmenu'=>'holiday',
								'url'=>'/holiday/index.php?mainmenu=holiday&leftmenu=holiday',
								'langs'=>'holiday',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>100,
								'enabled'=>'1',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->holiday->write',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=holiday,fk_leftmenu=holiday',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
								'type'=>'left',			// This is a Left menu entry
								'titre'=>'MenuAddCP',
								'mainmenu'=>'holiday',
								'leftmenu'=>'holiday_add',
								'url'=>'/holiday/fiche.php?mainmenu=holiday&action=request',
								'langs'=>'holiday',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>101,
								'enabled'=>'$conf->holiday->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->holiday->write',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=holiday,fk_leftmenu=holiday',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
								'type'=>'left',			// This is a Left menu entry
								'titre'=>'MenuConfCP',
								'mainmenu'=>'holiday',
								'leftmenu'=>'holiday_conf',
								'url'=>'/holiday/define_holiday.php?mainmenu=holiday&action=request',
								'langs'=>'holiday',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>102,
								'enabled'=>'$conf->holiday->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->holiday->define_holiday',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=holiday,fk_leftmenu=holiday',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
								'type'=>'left',			// This is a Left menu entry
								'titre'=>'MenuLogCP',
								'mainmenu'=>'holiday_def',
								'url'=>'/holiday/view_log.php?mainmenu=holiday&action=request',
								'leftmenu'=>'holiday',
								'langs'=>'holiday',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>103,
								'enabled'=>'$conf->holiday->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->holiday->view_log',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
		$this->menu[$r]=array(	'fk_menu'=>'fk_mainmenu=holiday,fk_leftmenu=holiday',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
								'type'=>'left',			// This is a Left menu entry
								'titre'=>'MenuReportMonth',
								'mainmenu'=>'holiday',
								'leftmenu'=>'holiday_report',
								'url'=>'/holiday/month_report.php?mainmenu=holiday&action=request',
								'langs'=>'holiday',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>104,
								'enabled'=>'$conf->holiday->enabled',			// Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->holiday->view_log',		// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		$r++;
*/
		
		// Exports
		$r=1;

		// Example:
		// $this->export_code[$r]=$this->rights_class.'_'.$r;
		// $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
		// $this->export_permission[$r]=array(array("facture","facture","export"));
		// $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
		// $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.zip'=>'soc_zip','s.town'=>'soc_town','s.fk_pays'=>'soc_pays','s.phone'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','s.code_compta'=>'soc_customer_accountancy','s.code_compta_fournisseur'=>'soc_supplier_accountancy','f.rowid'=>"invoiceid",'f.facnumber'=>"ref",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",'f.total'=>"totalht",'f.total_ttc'=>"totalttc",'f.tva'=>"totalvat",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'fd.rowid'=>'lineid','fd.description'=>"linedescription",'fd.price'=>"lineprice",'fd.total_ht'=>"linetotalht",'fd.total_tva'=>"linetotaltva",'fd.total_ttc'=>"linetotalttc",'fd.tva_tx'=>"linevatrate",'fd.qty'=>"lineqty",'fd.date_start'=>"linedatestart",'fd.date_end'=>"linedateend",'fd.fk_product'=>'productid','p.ref'=>'productref');
		// $this->export_sql_start[$r]='SELECT DISTINCT ';
		// $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
		// $r++;
	}

	/**
	 *	Function called when module is enabled.
	 *	The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *	It also creates data directories.
	 *
	 *	@return     int             1 if OK, 0 if KO
	 */
	function init()
	{
		$sql = array();

		//$result=$this->_load_tables('');

		return $this->_init($sql);
	}

	/**
	 *	Function called when module is disabled.
	 *  Remove from database constants, boxes and permissions from Dolibarr database.
	 *	Data directories are not deleted.
	 *
	 *  @return     int             1 if OK, 0 if KO
	 */
	function remove()
	{
		$sql = array();

		return $this->_remove($sql);
	}

}

?>