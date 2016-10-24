<?php
/* Copyright (C) 2013-2014 Jean-François Ferry <jfefe@aternatik.fr>
 * Copyright (C) 2015      Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Module to manage resources into Dolibarr ERP/CRM
 */

/**
 * 	\defgroup	resource	Module resource
 * 	\brief		Resource module descriptor.
 * 	\file		core/modules/modResource.class.php
 * 	\ingroup	resource
 * 	\brief		Description and activation file for module Resource
 */
include_once DOL_DOCUMENT_ROOT . "/core/modules/DolibarrModules.class.php";

/**
 * Description and activation class for module Resource
 */
class modResource extends DolibarrModules
{

	/**
	 * 	Constructor. Define names, constants, directories, boxes, permissions
	 *
	 * 	@param	DoliDB		$db	Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;

		// Id for module (must be unique).
		// Use a free id here
		// (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 63000;

		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'resource';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "projects";
		$this->module_position = 20;
		// Module label (no space allowed)
		// used if translation string 'ModuleXXXName' not found
		// (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description
		// used if translation string 'ModuleXXXDesc' not found
		// (where XXX is value of numeric property 'numero' of module)
		$this->description = "Manage resources (printers, cars, room, ...) you can then share into events";
		// Possible values for version are: 'development', 'experimental' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled
		// (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		// Where to store the module in setup page
		// (0=common,1=interface,2=others,3=very specific)
		$this->special = 2;
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png
		// use this->picto='pictovalue'
		// If file is in module/img directory under name object_pictovalue.png
		// use this->picto='pictovalue@module'
		$this->picto = 'resource'; // mypicto@resource
		// Defined all module parts (triggers, login, substitutions, menus, css, etc...)
		// for default path (eg: /resource/core/xxxxx) (0=disable, 1=enable)
		// for specific path of parts (eg: /resource/core/modules/barcode)
		// for specific css file (eg: /resource/css/resource.css.php)
		$this->module_parts = array(
			// Set this to 1 if module has its own trigger directory
			//'triggers' => 1,
			// Set this to 1 if module has its own login method directory
			//'login' => 0,
			// Set this to 1 if module has its own substitution function file
			//'substitutions' => 0,
			// Set this to 1 if module has its own menus handler directory
			//'menus' => 0,
			// Set this to 1 if module has its own barcode directory
			//'barcode' => 0,
			// Set this to 1 if module has its own models directory
			//'models' => 0,
			// Set this to relative path of css if module has its own css file
			//'css' => '/resource/css/resource.css.php',
			// Set here all hooks context managed by module
			// 'hooks' => array('actioncard','actioncommdao','resource_card','element_resource')
			// Set here all workflow context managed by module
			//'workflow' => array('order' => array('WORKFLOW_ORDER_AUTOCREATE_INVOICE'))
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/resource/temp");
		//$this->dirs = array("/resource");

		// Config pages. Put here list of php pages
		// stored into resource/admin directory, used to setup module.
		$this->config_page_url = array("resource.php");

		// Dependencies
		// List of modules id that must be enabled if this module is enabled
		$this->depends = array();
		// List of modules id to disable if this one is disabled
		$this->requiredby = array('modPlace');
		// Minimum version of PHP required by module
		$this->phpmin = array(5, 3);

		$this->langfiles = array("resource"); // langfiles@resource
		// Constants
		// List of particular constants to add when module is enabled
		// (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example:
		$this->const = array();

		// Array to add new pages in new tabs
		// Example:
		$this->tabs = array(
			//	// To add a new tab identified by code tabname1
			//	'objecttype:+tabname1:Title1:langfile@resource:$user->rights->resource->read:/resource/mynewtab1.php?id=__ID__',
			//	// To add another new tab identified by code tabname2
			//	'objecttype:+tabname2:Title2:langfile@resource:$user->rights->othermodule->read:/resource/mynewtab2.php?id=__ID__',
			//	// To remove an existing tab identified by code tabname
			//	'objecttype:-tabname'
		);
		// where objecttype can be
		// 'thirdparty'			to add a tab in third party view
		// 'intervention'		to add a tab in intervention view
		// 'order_supplier'		to add a tab in supplier order view
		// 'invoice_supplier'	to add a tab in supplier invoice view
		// 'invoice'			to add a tab in customer invoice view
		// 'order'				to add a tab in customer order view
		// 'product'			to add a tab in product view
		// 'stock'				to add a tab in stock view
		// 'propal'				to add a tab in propal view
		// 'member'				to add a tab in fundation member view
		// 'contract'			to add a tab in contract view
		// 'user'				to add a tab in user view
		// 'group'				to add a tab in group view
		// 'contact'			to add a tab in contact view
		// 'categories_x'		to add a tab in category view
		// (reresource 'x' by type of category (0=product, 1=supplier, 2=customer, 3=member)

		// Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
		$this->boxes = array(); // Boxes list
		$r = 0;
		// Example:

		//$this->boxes[$r][1] = "MyBox@resource";
		//$r ++;
		/*
		  $this->boxes[$r][1] = "myboxb.php";
		  $r++;
		 */

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = 63001;
		$this->rights[$r][1] = 'Read resources';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$r++;

		$this->rights[$r][0] = 63002;
		$this->rights[$r][1] = 'Create/Modify resources';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$r++;

		$this->rights[$r][0] = 63003;
		$this->rights[$r][1] = 'Delete resources';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$r++;

		$this->rights[$r][0] = 63004;
		$this->rights[$r][1] = 'Link resources to agenda events';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'link';
		$r++;


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.


		// Add here list of permission defined by
		// an id, a label, a boolean and two constant strings.
		// Example:
		//// Permission id (must not be already used)
		//$this->rights[$r][0] = 2000;
		//// Permission label
		//$this->rights[$r][1] = 'Permision label';
		//// Permission by default for new user (0/1)
		//$this->rights[$r][3] = 0;
		//// In php code, permission will be checked by test
		//// if ($user->rights->permkey->level1->level2)
		//$this->rights[$r][4] = 'level1';
		//// In php code, permission will be checked by test
		//// if ($user->rights->permkey->level1->level2)
		//$this->rights[$r][5] = 'level2';
		//$r++;
		// Main menu entries
		$this->menu = array(); // List of menus to add
		$r = 0;

		// Menus declaration
		$this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=tools',
			'type'=>'left',
			'titre'=> 'MenuResourceIndex',
			'mainmenu'=>'tools',
			'leftmenu'=> 'resource',
			'url'=> '/resource/list.php',
			'langs'=> 'resource',
			'position'=> 100,
			'enabled'=> '1',
			'perms'=> '$user->rights->resource->read',
			'user'=> 0
		);
		$r++;

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=resource', //On utilise les ancres définis dans le menu parent déclaré au dessus
			'type'=> 'left', // Toujours un menu gauche
			'titre'=> 'MenuResourceAdd',
			'mainmenu'=> 'tools',
			'leftmenu'=> '', // On n'indique rien ici car on ne souhaite pas intégrer de sous-menus à ce menu
			'url'=> '/resource/add.php',
			'langs'=> 'resource',
			'position'=> 101,
			'enabled'=> '1',
			'perms'=> '$user->rights->resource->read',
			'target'=> '',
			'user'=> 0
		);

		$this->menu[$r++]=array(
			'fk_menu'=>'fk_mainmenu=tools,fk_leftmenu=resource', //On utilise les ancres définis dans le menu parent déclaré au dessus
			'type'=> 'left', // Toujours un menu gauche
			'titre'=> 'List',
			'mainmenu'=> 'tools',
			'leftmenu'=> '', // On n'indique rien ici car on ne souhaite pas intégrer de sous-menus à ce menu
			'url'=> '/resource/list.php',
			'langs'=> 'resource',
			'position'=> 102,
			'enabled'=> '1',
			'perms'=> '$user->rights->resource->read',
			'target'=> '',
			'user'=> 0
		);

		// Exports
		$r = 1;

		// Example:
		//$this->export_code[$r]=$this->rights_class.'_'.$r;
		//// Translation key (used only if key ExportDataset_xxx_z not found)
		//$this->export_label[$r]='CustomersInvoicesAndInvoiceLines';
		//// Condition to show export in list (ie: '$user->id==3').
		//// Set to 1 to always show when module is enabled.
		//$this->export_enabled[$r]='1';
		//$this->export_permission[$r]=array(array("facture","facture","export"));
		//$this->export_fields_array[$r]=array(
		//	's.rowid'=>"IdCompany",
		//	's.nom'=>'CompanyName',
		//	's.address'=>'Address',
		//	's.cp'=>'Zip',
		//	's.ville'=>'Town',
		//	's.fk_pays'=>'Country',
		//	's.tel'=>'Phone',
		//	's.siren'=>'ProfId1',
		//	's.siret'=>'ProfId2',
		//	's.ape'=>'ProfId3',
		//	's.idprof4'=>'ProfId4',
		//	's.code_compta'=>'CustomerAccountancyCode',
		//	's.code_compta_fournisseur'=>'SupplierAccountancyCode',
		//	'f.rowid'=>"InvoiceId",
		//	'f.facnumber'=>"InvoiceRef",
		//	'f.datec'=>"InvoiceDateCreation",
		//	'f.datef'=>"DateInvoice",
		//	'f.total'=>"TotalHT",
		//	'f.total_ttc'=>"TotalTTC",
		//	'f.tva'=>"TotalVAT",
		//	'f.paye'=>"InvoicePaid",
		//	'f.fk_statut'=>'InvoiceStatus',
		//	'f.note'=>"InvoiceNote",
		//	'fd.rowid'=>'LineId',
		//	'fd.description'=>"LineDescription",
		//	'fd.price'=>"LineUnitPrice",
		//	'fd.tva_tx'=>"LineVATRate",
		//	'fd.qty'=>"LineQty",
		//	'fd.total_ht'=>"LineTotalHT",
		//	'fd.total_tva'=>"LineTotalTVA",
		//	'fd.total_ttc'=>"LineTotalTTC",
		//	'fd.date_start'=>"DateStart",
		//	'fd.date_end'=>"DateEnd",
		//	'fd.fk_product'=>'ProductId',
		//	'p.ref'=>'ProductRef'
		//);
		//$this->export_entities_array[$r]=array('s.rowid'=>"company",
		//	's.nom'=>'company',
		//	's.address'=>'company',
		//	's.cp'=>'company',
		//	's.ville'=>'company',
		//	's.fk_pays'=>'company',
		//	's.tel'=>'company',
		//	's.siren'=>'company',
		//	's.siret'=>'company',
		//	's.ape'=>'company',
		//	's.idprof4'=>'company',
		//	's.code_compta'=>'company',
		//	's.code_compta_fournisseur'=>'company',
		//	'f.rowid'=>"invoice",
		//	'f.facnumber'=>"invoice",
		//	'f.datec'=>"invoice",
		//	'f.datef'=>"invoice",
		//	'f.total'=>"invoice",
		//	'f.total_ttc'=>"invoice",
		//	'f.tva'=>"invoice",
		//	'f.paye'=>"invoice",
		//	'f.fk_statut'=>'invoice',
		//	'f.note'=>"invoice",
		//	'fd.rowid'=>'invoice_line',
		//	'fd.description'=>"invoice_line",
		//	'fd.price'=>"invoice_line",
		//	'fd.total_ht'=>"invoice_line",
		//	'fd.total_tva'=>"invoice_line",
		//	'fd.total_ttc'=>"invoice_line",
		//	'fd.tva_tx'=>"invoice_line",
		//	'fd.qty'=>"invoice_line",
		//	'fd.date_start'=>"invoice_line",
		//	'fd.date_end'=>"invoice_line",
		//	'fd.fk_product'=>'product',
		//	'p.ref'=>'product'
		//);
		//$this->export_sql_start[$r] = 'SELECT DISTINCT ';
		//$this->export_sql_end[$r] = ' FROM (' . MAIN_DB_PREFIX . 'facture as f, '
		//	. MAIN_DB_PREFIX . 'facturedet as fd, ' . MAIN_DB_PREFIX . 'societe as s)';
		//$this->export_sql_end[$r] .= ' LEFT JOIN ' . MAIN_DB_PREFIX
		//	. 'product as p on (fd.fk_product = p.rowid)';
		//$this->export_sql_end[$r] .= ' WHERE f.fk_soc = s.rowid '
		//	. 'AND f.rowid = fd.fk_facture';
		//$r++;
	}

	/**
	 * Function called when module is enabled.
	 * The init function add constants, boxes, permissions and menus
	 * (defined in constructor) into Dolibarr database.
	 * It also creates data directories
	 *
	 * 	@param		string	$options	Options when enabling module ('', 'noboxes')
	 * 	@return		int					1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$sql = array();

		$result = $this->loadTables();

		return $this->_init($sql, $options);
	}

	/**
	 * Create tables, keys and data required by module
	 * Files llx_table1.sql, llx_table1.key.sql llx_data.sql with create table, create keys
	 * and create data commands must be stored in directory /resource/sql/
	 * This function is called by this->init
	 *
	 * 	@return		int		<=0 if KO, >0 if OK
	 */
	private function loadTables()
	{
		return $this->_load_tables('/resource/sql/');
	}
}
