<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@capnetworks.com>
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
 * 	\defgroup   paypal     Module paypal
 * 	\brief      Add integration with Paypal online payment system.
 *  \file       htdocs/core/modules/modPaypal.class.php
 *  \ingroup    paypal
 *  \brief      Description and activation file for module Paypal
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';


/**
 * 	Description and activation class for module Paypal
 */
class modPaypal extends DolibarrModules
{
    /**
     *   Constructor. Define names, constants, directories, boxes, permissions
     *
     *   @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        // Id for module (must be unique).
        // Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
        $this->numero = 50200;
        // Key text used to identify module (for permissions, menus, etc...)
        $this->rights_class = 'paypal';

        // Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
        // It is used to group modules in module setup page
        $this->family = "interface";
        // Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
        $this->name = preg_replace('/^mod/i','',get_class($this));
        // Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
        $this->description = "Module to offer an online payment page with PayPal";
        // Possible values for version are: 'development', 'experimental', 'dolibarr' or version
        $this->version = 'dolibarr';
        // Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
        $this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
        // Name of image file used for this module.
        // If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
        // If file is in module/img directory, use this->picto=DOL_URL_ROOT.'/module/img/file.png'
        $this->picto='paypal@paypal';

        // Data directories to create when module is enabled.
        $this->dirs = array('/paypal/temp');

        // Config pages. Put here list of php page names stored in admmin directory used to setup module.
        $this->config_page_url = array("paypal.php@paypal");

        // Dependencies
        $this->depends = array();						// List of modules id that must be enabled if this module is enabled
        $this->requiredby = array('modPaypalPlus');		// List of modules id to disable if this one is disabled
        $this->phpmin = array(5,2);						// Minimum version of PHP required by module
        $this->need_dolibarr_version = array(3,0);		// Minimum version of Dolibarr required by module
        $this->langfiles = array("paypal");

        // Constants
        $this->const = array();			// List of particular constants to add when module is enabled
        //Example: $this->const=array(0=>array('MODULE_MY_NEW_CONST1','chaine','myvalue','This is a constant to add',0),
        //                            1=>array('MODULE_MY_NEW_CONST2','chaine','myvalue','This is another constant to add',0) );

        // New pages on tabs
        $this->tabs = array();


        // Boxes
        $this->boxes = array();			// List of boxes
        $r=0;

        // Add here list of php file(s) stored in core/boxes that contains class to show a box.
        // Example:
        //$this->boxes[$r][1] = "myboxa.php";
        //$r++;
        //$this->boxes[$r][1] = "myboxb.php";
        //$r++;


        // Permissions
        $this->rights = array();		// Permission array used by this module
        $r=0;


        // Main menu entries
        $this->menus = array();			// List of menus to add
        $r=0;
        $this->menu[$r]=array(
        'fk_menu'=>'fk_mainmenu=billing,fk_leftmenu=customers_bills_payment',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
        'mainmenu'=>'billing',
        'leftmenu'=>'customers_bills_payment_paypal',
        'type'=>'left',			                // This is a Left menu entry
        'titre'=>'PaypalImportPayment',
        'url'=>'/paypal/importpayments.php',
        'langs'=>'paypal',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
        'position'=>501,
        'enabled'=>'$conf->paypal->enabled && $conf->banque->enabled && $conf->global->MAIN_FEATURES_LEVEL >= 2',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
        'perms'=>'$user->rights->banque->consolidate',	// Use 'perms'=>'$user->rights->mymodule->level1->level2' if you want your menu with a permission rules
        'target'=>'',
        'user'=>2
        );				                // 0=Menu for internal users, 1=external users, 2=both
        $r++;

        // Add here entries to declare new menus
        // Example to declare the Top Menu entry:
        // $this->menu[$r]=array(	'fk_menu'=>0,			// Put 0 if this is a top menu
        //							'type'=>'top',			// This is a Top menu entry
        //							'titre'=>'MyModule top menu',
        //							'mainmenu'=>'mymodule',
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
        // $this->export_fields_array[$r]=array(
        //    's.rowid'=>"IdCompany",'s.nom'=>'CompanyName','s.address'=>'Address','s.zip'=>'Zip','s.town'=>'Town','s.fk_pays'=>'Country','s.phone'=>'Phone',
        //    's.siren'=>'ProfId1','s.siret'=>'ProfId2','s.ape'=>'ProfId3','s.idprof4'=>'ProfId4','s.code_compta'=>'CustomerAccountancyCode',
        //    's.code_compta_fournisseur'=>'SupplierAccountancyCode','f.rowid'=>"InvoiceId",'f.facnumber'=>"InvoiceRef",'f.datec'=>"InvoiceDateCreation",
        //    'f.datef'=>"DateInvoice",'f.total'=>"TotalHT",'f.total_ttc'=>"TotalTTC",'f.tva'=>"TotalVAT",'f.paye'=>"InvoicePaid",'f.fk_statut'=>'InvoiceStatus',
        //    'f.note'=>"InvoiceNote",'fd.rowid'=>'LineId','fd.description'=>"LineDescription",'fd.price'=>"LineUnitPrice",'fd.tva_tx'=>"LineVATRate",
        //    'fd.qty'=>"LineQty",'fd.total_ht'=>"LineTotalHT",'fd.total_tva'=>"LineTotalTVA",'fd.total_ttc'=>"LineTotalTTC",'fd.date_start'=>"DateStart",
        //    'fd.date_end'=>"DateEnd",'fd.fk_product'=>'ProductId','p.ref'=>'ProductRef'
        // );
        // $this->export_entities_array[$r]=array(
        //    's.rowid'=>"company",'s.nom'=>'company','s.address'=>'company','s.zip'=>'company','s.town'=>'company','s.fk_pays'=>'company','s.phone'=>'company',
        //    's.siren'=>'company','s.siret'=>'company','s.ape'=>'company','s.idprof4'=>'company','s.code_compta'=>'company','s.code_compta_fournisseur'=>'company',
        //    'f.rowid'=>"invoice",'f.facnumber'=>"invoice",'f.datec'=>"invoice",'f.datef'=>"invoice",'f.total'=>"invoice",'f.total_ttc'=>"invoice",
        //    'f.tva'=>"invoice",'f.paye'=>"invoice",'f.fk_statut'=>'invoice','f.note'=>"invoice",'fd.rowid'=>'invoice_line','fd.description'=>"invoice_line",
        //    'fd.price'=>"invoice_line",'fd.total_ht'=>"invoice_line",'fd.total_tva'=>"invoice_line",'fd.total_ttc'=>"invoice_line",'fd.tva_tx'=>"invoice_line",
        //    'fd.qty'=>"invoice_line",'fd.date_start'=>"invoice_line",'fd.date_end'=>"invoice_line",'fd.fk_product'=>'product','p.ref'=>'product'
        // );
        // $this->export_sql_start[$r]='SELECT DISTINCT ';
        // $this->export_sql_end[$r]  =' FROM ('.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'facturedet as fd, '.MAIN_DB_PREFIX.'societe as s)';
        // $this->export_sql_end[$r] .=' LEFT JOIN '.MAIN_DB_PREFIX.'product as p on (fd.fk_product = p.rowid)';
        // $this->export_sql_end[$r] .=' WHERE f.fk_soc = s.rowid AND f.rowid = fd.fk_facture';
        // $r++;
    }
}

