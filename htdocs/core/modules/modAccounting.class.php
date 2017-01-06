<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2016 Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014      Ari Elbaz (elarifr)	<github@accedinfo.com>
 * Copyright (C) 2014 	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2016      Laurent Destailleur 	<eldy@users.sourceforge.net>
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
 * \class	modAccounting
 * \brief	Description and activation class for module accounting expert
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
		$this->special = 0;
		$this->picto = 'accounting';

		// Defined if the directory /mymodule/inc/triggers/ contains triggers or not
		// $this->triggers = 1;

		// Data directories to create when module is enabled
		$this->dirs = array('/accounting/temp');

		// Config pages
		$this->config_page_url = array('index.php@accountancy');

		// Dependencies
		$this->depends = array("modFacture","modBanque","modTax"); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->conflictwith = array("modComptabilite"); // List of modules are in conflict with this module
		$this->phpmin = array(5, 3); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 9); // Minimum version of Dolibarr required by module
		$this->langfiles = array("accountancy");

		// Constants
		$this->const = array();
		$this->const[1] = array(
				"MAIN_COMPANY_CODE_ALWAYS_REQUIRED",
				"chaine",
				"1",
				"With this constants on, third party code is always required whatever is numbering module behaviour"
		);
		$this->const[2] = array(
				"MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED",
				"chaine",
				"1",
				"With this constants on, bank account number is always required"
		);
		$this->const[1] = array(
				"ACCOUNTING_EXPORT_SEPARATORCSV",
				"string",
				","
		);
		$this->const[2] = array(
				"ACCOUNTING_ACCOUNT_SUSPENSE",
				"chaine",
				"471"
		);
		$this->const[3] = array(
				"ACCOUNTING_SELL_JOURNAL",
				"chaine",
				"VTE"
		);
		$this->const[4] = array(
				"ACCOUNTING_PURCHASE_JOURNAL",
				"chaine",
				"ACH"
		);
		$this->const[5] = array(
				"ACCOUNTING_SOCIAL_JOURNAL",
				"chaine",
				"SOC"
		);
		$this->const[6] = array(
				"ACCOUNTING_MISCELLANEOUS_JOURNAL",
				"chaine",
				"OD"
		);
		$this->const[7] = array(
				"ACCOUNTING_ACCOUNT_TRANSFER_CASH",
				"chaine",
				"58"
		);
		$this->const[8] = array(
				"CHARTOFACCOUNTS",
				"chaine",
				"2"
		);
		$this->const[9] = array(
				"ACCOUNTING_EXPORT_MODELCSV",
				"chaine",
				"1"
		);
		$this->const[10] = array(
				"ACCOUNTING_LENGTH_GACCOUNT",
				"chaine",
				""
		);
		$this->const[11] = array(
				"ACCOUNTING_LENGTH_AACCOUNT",
				"chaine",
				""
		);
		$this->const[13] = array(
				"ACCOUNTING_LIST_SORT_VENTILATION_TODO",
				"yesno",
				"1"
		);
		$this->const[14] = array(
				"ACCOUNTING_LIST_SORT_VENTILATION_DONE",
				"yesno",
				"1"
		);
		/*
		$this->const[15] = array (
				"ACCOUNTING_GROUPBYACCOUNT",
				"yesno",
				"1"
		);
		*/
		$this->const[16] = array (
				"ACCOUNTING_EXPORT_DATE",
				"chaine",
				"%d%m%Y"
		);
		/*
		$this->const[17] = array (
				"ACCOUNTING_EXPORT_PIECE",
				"yesno",
				"1"
		);
		$this->const[18] = array (
				"ACCOUNTING_EXPORT_GLOBAL_ACCOUNT",
				"yesno",
				"1"
		);
		$this->const[19] = array (
				"ACCOUNTING_EXPORT_LABEL",
				"yesno",
				"1"
		);
		$this->const[20] = array (
				"ACCOUNTING_EXPORT_AMOUNT",
				"yesno",
				"1"
		);
		$this->const[21] = array (
				"ACCOUNTING_EXPORT_DEVISE",
				"yesno",
				"1"
		);
		*/
		$this->const[22] = array(
				"ACCOUNTING_EXPENSEREPORT_JOURNAL",
				"chaine",
				"ER"
		);
		$this->const[23] = array(
				"ACCOUNTING_EXPORT_FORMAT",
				"chaine",
				"csv"
		);
		/* Not required to disable this. This make not possible to do complete reconciliation.
		Also, this is not a problem, lines added manually will be reported as "not binded into accounting export module
		and will be binded manually to be created into general ledger
		$this->const[24] = array(
				"BANK_DISABLE_DIRECT_INPUT",
				"yesno",
				"1"
		);*/

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
		$this->rights[$r][1] = 'Read operations in General Ledger';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mouvements';
		$this->rights[$r][5] = 'lire';
		$r++;

		$this->rights[$r][0] = 50412;
		$this->rights[$r][1] = 'Write/Edit operations in General Ledger';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'mouvements';
		$this->rights[$r][5] = 'creer';
		$r++;

		$this->rights[$r][0] = 50420;
		$this->rights[$r][1] = 'Report and export reports (turnover, balance, journals, general ledger)';
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

	}
}
