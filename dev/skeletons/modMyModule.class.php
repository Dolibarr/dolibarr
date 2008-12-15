<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**     \defgroup   mymodule     Module MyModule
 *      \brief      Example of a module descriptor.
 *					Such a file must be copied into htdocs/includes/module directory.
 */

/**
 *      \file       htdocs/includes/modules/modMyModule.class.php
 *      \ingroup    mymodule
 *      \brief      Description and activation file for module MyModule
 *		\version	$Id$
 */
include_once(DOL_DOCUMENT_ROOT ."/includes/modules/DolibarrModules.class.php");


/**     \class      modMyModule
 *      \brief      Description and activation class for module MyModule
 */
class modMyModule extends DolibarrModules
{
    /**
    *   \brief      Constructor. Define names, constants, directories, boxes, permissions
    *   \param      DB      Database handler
    */
	function modMyModule($DB)
	{
		$this->db = $DB;
		
		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 10000;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'mymodule';
		
		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page 
		$this->family = "other";		
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = eregi_replace('^mod','',get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Description of module MyModule";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0';    
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Where to store the module in setup page (0=common,1=interface,2=other)
		$this->special = 0;
		// Name of png file (without png) used for this module.
		// Png file must be in theme/yourtheme/img directory under name object_pictovalue.png. 
		$this->picto='generic';
		
		// Data directories to create when module is enabled.
		$this->dirs = array();
		//$this->dirs[0] = DOL_DATA_ROOT.'/mymodule;
        //$this->dirs[1] = DOL_DATA_ROOT.'/mymodule/temp;
 		
		// Relative path to module style sheet if exists. Example: '/mymodule/mycss.css'.
		$this->style_sheet = '';

		// Config pages. Put here list of php page names stored in admmin directory used to setup module.
		$this->config_page_url = array("mymodulesetuppage.php");
		
		// Dependencies
		$this->depends = array();		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->phpmin = array(4,1);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(2,4);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("mymodule");
		
		// Constants
		$this->const = array();			// List of particular constants to add when module is enabled
		
		
		// New pages on tabs
		$this->tabs = array('entity:Title:@mymodule:/mymodule/mynewtab.php?id=__ID__');
		
		
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
		
		// Add here list of permission defined by an id, a label, a boolean and two constant strings.
		// Example:
		// $this->rights[$r][0] = 2000; 				// Permission id (must not be already used)
		// $this->rights[$r][1] = 'Permision label';	// Permission label
		// $this->rights[$r][3] = 1; 					// Permission by default for new user (0/1)
		// $this->rights[$r][4] = 'level1';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $this->rights[$r][5] = 'level2';				// In php code, permission will be checked by test if ($user->rights->permkey->level1->level2)
		// $r++;

		
		// Main menu entries
		$this->menus = array();			// List of menus to add
		$r=0;
		
		// Add here entries to declare new menus
		// Example to declare the Top Menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
		//							'type'=>'top',			// This is a Top menu entry
		//							'titre'=>'MyModule top menu',
		//							'mainmenu'=>'mymodule',
		//							'leftmenu'=>'1',		// Use 1 if you also want to add left menu entries using this descriptor. Use 0 if left menu entries are defined in a file pre.inc.php (old school).
		//							'url'=>'/mymodule/pagetop.php',
		//							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		//
		// Example to declare a Left Menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'r=0',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
		//							'type'=>'left',			// This is a Left menu entry
		//							'titre'=>'MyModule left menu 1',
		//							'mainmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagelevel1.php',
		//							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		//
		// Example to declare another Left Menu entry:
		// $this->menu[$r]=array(	'fk_menu'=>'r=1',		// Use r=value where r is index key used for the parent menu entry (higher parent must be a top menu entry)
		//							'type'=>'left',			// This is a Left menu entry
		//							'titre'=>'MyModule left menu 2',
		//							'mainmenu'=>'mymodule',
		//							'url'=>'/mymodule/pagelevel2.php',
		//							'langs'=>'mylangfile',	// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
		//							'position'=>100,
		//							'perms'=>'1',			// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
		//							'target'=>'',
		//							'user'=>2);				// 0=Menu for internal users, 1=external users, 2=both
		// $r++;
		
		
		// Exports
        $r=1;
    	
        // Example:
        // $this->export_code[$r]=$this->rights_class.'_'.$r;
        // $this->export_label[$r]='CustomersInvoicesAndInvoiceLines';	// Translation key (used only if key ExportDataset_xxx_z not found)
        // $this->export_permission[$r]=array(array("facture","facture","export"));
        // $this->export_fields_array[$r]=array('s.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.cp'=>'Zip','s.ville'=>'Town','s.fk_pays'=>'Country','s.tel'=>'Phone','s.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode','s.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePayed",'f.fk_statut'=>'InvoiceStatus','f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_taux'=>"LineVATRate",'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef');
		// $this->export_entities_array[$r]=array('s.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.cp'=>'company','s.ville'=>'company','s.fk_pays'=>'company','s.tel'=>'company','s.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company','f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_taux'=>"invoice_line",'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product');
        // $this->export_alias_array[$r]=array('s.rowid'=>"socid",'s.nom'=>'soc_name','s.address'=>'soc_adres','s.cp'=>'soc_zip','s.ville'=>'soc_ville','s.fk_pays'=>'soc_pays','s.tel'=>'soc_tel','s.siren'=>'soc_siren','s.siret'=>'soc_siret','s.ape'=>'soc_ape','s.idprof4'=>'soc_idprof4','s.code_compta'=>'soc_customer_accountancy','s.code_compta_fournisseur'=>'soc_supplier_accountancy','f.rowid'=>"invoiceid",'f.facnumber'=>"ref",'f.datec'=>"datecreation",'f.datef'=>"dateinvoice",'f.total'=>"totalht",'f.total_ttc'=>"totalttc",'f.tva'=>"totalvat",'f.paye'=>"paid",'f.fk_statut'=>'status','f.note'=>"note",'fd.rowid'=>'lineid','fd.description'=>"linedescription",'fd.price'=>"lineprice",'fd.total_ht'=>"linetotalht",'fd.total_tva'=>"linetotaltva",'fd.total_ttc'=>"linetotalttc",'fd.tva_taux'=>"linevatrate",'fd.qty'=>"lineqty",'fd.date_start'=>"linedatestart",'fd.date_end'=>"linedateend",'fd.fk_product'=>'productid','p.ref'=>'productref');
        // $this->export_sql_start[$r]='SELECT DISTINCT ';
        // $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
		// $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
		// $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
        // $r++;
  	}

	/**
     *		\brief      Function called when module is enabled.
     *					The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
     *					It also creates data directories.
	 *      \return     int             1 if OK, 0 if KO
     */
	function init()
  	{
    	$sql = array();
    
		$result=$this->load_tables();
	
    	return $this->_init($sql);
  	}

	/**
	 *		\brief		Function called when module is disabled.
 	 *              	Remove from database constants, boxes and permissions from Dolibarr database.
 	 *					Data directories are not deleted.
	 *      \return     int             1 if OK, 0 if KO
 	 */
	function remove()
	{
    	$sql = array();

    	return $this->_remove($sql);
  	}

	
	/**
	*		\brief		Create tables and keys required by module
	* 					Files mymodule.sql and mymodule.key.sql with create table and create keys
	* 					commands must be stored in directory /mymodule/sql/
	*					This function is called by this->init.
	* 		\return		int		<=0 if KO, >0 if OK
	*/
	function load_tables()
	{
		return $this->_load_tables('/mymodule/sql/');
	}
}

?>
