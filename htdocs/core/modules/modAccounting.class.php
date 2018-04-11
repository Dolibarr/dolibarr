<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2014      Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2014 	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2016-2017 Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 * \file		htdocs/core/modules/modAccounting.class.php
 * \ingroup		Advanced accountancy
 * \brief		Module to activate Accounting Expert module
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

/**
 * Description and activation class for module accounting expert
 */
class modAccounting extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 50400;

		$this->family = "financial";
		$this->module_position = 610;
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Advanced accounting management";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or 'dolibarr_deprecated' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_' . strtoupper($this->name);
		$this->picto = 'accounting';

		// Data directories to create when module is enabled
		$this->dirs = array('/accounting/temp');

		// Config pages
		$this->config_page_url = array();

		// Dependencies
		$this->depends = array("modFacture","modBanque","modTax"); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->conflictwith = array("modComptabilite"); // List of modules are in conflict with this module
		$this->phpmin = array(5, 3); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 9); // Minimum version of Dolibarr required by module
		$this->langfiles = array("accountancy","compta");

		// Constants
		// List of particular constants to add when module is enabled (key, 'chaine', value, desc, visible, 'current' or 'allentities', deleteonunactive)
		// Example: $this->const=array(0=>array('MYMODULE_MYNEWCONST1','chaine','myvalue','This is a constant to add',1),
		//                             1=>array('MYMODULE_MYNEWCONST2','chaine','myvalue','This is another constant to add',0, 'current', 1)
		// );
		$this->const = array();
		$this->const[1] = array(
				"MAIN_COMPANY_CODE_ALWAYS_REQUIRED",
				"chaine",
				"1",
				"With this constants on, third party code is always required whatever is numbering module behaviour", 0, 'current', 0
		);
		$this->const[2] = array(
				"MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED",
				"chaine",
				"1",
				"With this constants on, bank account number is always required", 0, 'current', 1
		);
		$this->const[3] = array(
				"ACCOUNTING_ACCOUNT_SUSPENSE",
				"chaine",
				"471",
				"", 0, 'current', 0
		);
		$this->const[4] = array(
				"ACCOUNTING_ACCOUNT_TRANSFER_CASH",
				"chaine",
				"58",
				"", 0, 'current', 0
		);
		$this->const[5] = array(
				"CHARTOFACCOUNTS",
				"chaine",
				"2",
				"", 0, 'current', 0
		);
		$this->const[6] = array(
				"ACCOUNTING_EXPORT_MODELCSV",
				"chaine",
				"1",
				"", 0, 'current', 0
		);
		$this->const[7] = array(
				"ACCOUNTING_LENGTH_GACCOUNT",
				"chaine",
				"",
				"", 0, 'current', 0
		);
		$this->const[8] = array(
				"ACCOUNTING_LENGTH_AACCOUNT",
				"chaine",
				"",
				"", 0, 'current', 0
		);
		$this->const[9] = array(
				"ACCOUNTING_LIST_SORT_VENTILATION_TODO",
				"yesno",
				"1",
				"", 0, 'current', 0
		);
		$this->const[10] = array(
				"ACCOUNTING_LIST_SORT_VENTILATION_DONE",
				"yesno",
				"1",
				"", 0, 'current', 0
		);
		$this->const[11] = array (
				"ACCOUNTING_EXPORT_DATE",
				"chaine",
				"%d%m%Y",
				"", 0, 'current', 0
		);
		$this->const[12] = array(
				"ACCOUNTING_EXPORT_SEPARATORCSV",
				"string",
				",",
				"", 0, 'current', 0
		);
		$this->const[13] = array(
				"ACCOUNTING_EXPORT_FORMAT",
				"chaine",
				"csv",
				"", 0, 'current', 0
		);

		// Tabs
		$this->tabs = array();

		// Css
		$this->module_parts = array();

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights_class = 'accounting';

		$this->rights = array(); // Permission array used by this module
		$r = 0;

		$this->rights[$r][0] = 50440;
		$this->rights[$r][1] = 'Manage chart of accounts, setup of accountancy';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'chartofaccount';
		$this->rights[$r][5] = '';
		$r++;

		$this->rights[$r][0] = 50401;
		$this->rights[$r][1] = 'Bind products and invoices with accounting accounts';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'bind';
		$this->rights[$r][5] = 'write';
		$r++;

		/*
		$this->rights[$r][0] = 50402;
		$this->rights[$r][1] = 'Make binding with products and invoices';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'ventilation';
		$this->rights[$r][5] = 'dispatch_advanced';
		$r++;
        */

		$this->rights[$r][0] = 50411;
		$this->rights[$r][1] = 'Read operations in Ledger';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mouvements';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 50412;
		$this->rights[$r][1] = 'Write/Edit operations in Ledger';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mouvements';
		$this->rights[$r][5] = 'creer';
		$r++;

		$this->rights[$r][0] = 50420;
		$this->rights[$r][1] = 'Report and export reports (turnover, balance, journals, ledger)';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'comptarapport';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 50430;
		$this->rights[$r][1] = 'Define and close a fiscal year';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'fiscalyear';
		$this->rights[$r][5] = '';
		$r++;


		// Menus
		//-------
		$this->menu = 1;        // This module add menu entries. They are coded into menu manager.

		// Exports
        //--------
        $r=0;

        $r++;
        $this->export_code[$r]=$this->rights_class.'_'.$r;
        $this->export_label[$r]='Chartofaccounts';
        $this->export_icon[$r]='accounting';
		$this->export_permission[$r]=array(array("accounting","chartofaccount"));
        $this->export_fields_array[$r]=array('ac.rowid'=>'ChartofaccountsId','ac.pcg_version'=>'Chartofaccounts','aa.rowid'=>'Id','aa.account_number'=>"AccountAccounting",'aa.label'=>"Label",'aa.account_parent'=>"Accountparent",'aa.pcg_type'=>"Pcgtype",'aa.pcg_subtype'=>'Pcgsubtype','aa.active'=>'Status');
        $this->export_TypeFields_array[$r]=array('ac.rowid'=>'List:accounting_system:pcg_version','aa.account_number'=>"Text",'aa.label'=>"Text",'aa.pcg_type'=>'Text','aa.pcg_subtype'=>'Text','aa.active'=>'Status');
        $this->export_entities_array[$r]=array('ac.rowid'=>"accounting",'ac.pcg_version'=>"accounting",'aa.rowid'=>'accounting','aa.account_number'=>"accounting",'aa.label'=>"accounting",'aa.accountparent'=>"accounting",'aa.pcg_type'=>"accounting",'aa.pcgsubtype'=>"accounting",'aa_active'=>"accounting");

        $this->export_sql_start[$r]='SELECT DISTINCT ';
        $this->export_sql_end[$r]  =' FROM '.MAIN_DB_PREFIX.'accounting_account as aa, '.MAIN_DB_PREFIX.'accounting_system as ac';
        $this->export_sql_end[$r] .=' WHERE ac.pcg_version = aa.fk_pcg_version AND aa.entity IN ('.getEntity('accounting').') ';


        // Imports
        //--------
        $r=0;

        $r++;
        $this->import_code[$r]=$this->rights_class.'_'.$r;
        $this->import_label[$r]="Chartofaccounts"; // Translation key
        $this->import_icon[$r]=$this->picto;
        $this->import_entities_array[$r]=array();		// We define here only fields that use another icon that the one defined into import_icon
        $this->import_tables_array[$r]=array('aa'=>MAIN_DB_PREFIX.'accounting_account');
        $this->import_tables_creator_array[$r]=array('aa'=>'fk_user_author');    // Fields to store import user id
        $this->import_fields_array[$r]=array('aa.fk_pcg_version'=>"Chartofaccounts*",'aa.account_number'=>"AccountAccounting*",'aa.label'=>"Label*",'aa.account_parent'=>"Accountparent","aa.fk_accounting_category"=>"AccountingCategory","aa.pcg_type"=>"Pcgtype*",'aa.pcg_subtype'=>'Pcgsubtype*','aa.active'=>'Status*','aa.datec'=>"DateCreation");
        $this->import_regex_array[$r]=array('aa.fk_pcg_version'=>'pcg_version@'.MAIN_DB_PREFIX.'accounting_system','aa.account_number'=>'^\d{1,32}$','aa.label'=>'^.{1,255}$','aa.account_parent'=>'^\d{0,32}$','aa.fk_accounting_category'=>'rowid@'.MAIN_DB_PREFIX.'c_accounting_category','aa.pcg_type'=>'^.{1,20}$','aa.pcg_subtype'=>'^.{1,20}$','aa.active'=>'^0|1$','aa.datec'=>'^\d{4}-\d{2}-\d{2}$');
        $this->import_convertvalue_array[$r]=array(
	        'aa.fk_accounting_category'=>array('rule'=>'fetchidfromcodeorlabel','classfile'=>'/accountancy/class/accountancycategory.class.php','class'=>'AccountancyCategory','method'=>'fetch','dict'=>'DictionaryAccountancyCategory'),
	        'aa.account_parent'=>array('rule'=>'zeroifnull'),
        );
        $this->import_examplevalues_array[$r]=array('aa.fk_pcg_version'=>"PCG99-ABREGE",'aa.account_number'=>"707",'aa.label'=>"Product sales",'aa.account_parent'=>"1407","aa.fk_accounting_category"=>"","aa.pcg_type"=>"PROD",'aa.pcg_subtype'=>'PRODUCT','aa.active'=>'1','aa.datec'=>"2017-04-28");

	}
}
