<?php
/* Copyright (C) 2017		Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2017		Saasprov				<saasprov@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * 	\defgroup   stripe     Module stripe
 * 	\brief      Add integration with Stripe online payment system.
 *  \file       htdocs/core/modules/modStripe.class.php
 *  \ingroup    stripe
 *  \brief      Description and activation file for the module Stripe
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 * 	Description and activation class for module Paybox
 */
class modStripe extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		// Id for module (must be unique).
		// Use here a free id (See in Home -> System information -> Dolibarr for list of used modules id).
		$this->numero = 50300;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'stripe';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "interface";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description, used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Module to offer an online payment page by credit card with Stripe";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		// If file is in theme/yourtheme/img directory under name object_pictovalue.png, use this->picto='pictovalue'
		// If file is in module/img directory, use this->picto=DOL_URL_ROOT.'/module/img/file.png'
		$this->picto = 'stripe';

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page names stored in admin directory used to setup module.
		$this->config_page_url = array("stripe.php@stripe");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(5, 0); // Minimum version of Dolibarr required by module
		$this->langfiles = array("stripe");

		// Constants
		$this->const = array(); // List of particular constants to add when module is enabled

		// New pages on tabs
		$this->tabs = array();

		// List of boxes
		$this->boxes = array();
		$r = 0;

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;

		// Main menu entries
		$r = 0;
		/* $this->menu[$r]=array(
			'fk_menu'=>'fk_mainmenu=billing,fk_leftmenu=customers_bills_payment',		    // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'mainmenu'=>'billing',
			'leftmenu'=>'customers_bills_payment_stripe',
			'type'=>'left',			                // This is a Left menu entry
			'titre'=>'StripeImportPayment',
			'url'=>'/stripe/importpayments.php',
			'langs'=>'stripe',	        // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position'=>500,
			'enabled'=>'$conf->stripe->enabled && isModEnabled("banque") && $conf->global->MAIN_FEATURES_LEVEL >= 2',  // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms'=>'$user->rights->banque->modifier',	// Use 'perms'=>'$user->hasRight("mymodule","level1","level2")' if you want your menu with a permission rules
			'target'=>'',
			'user'=>2
		);				                // 0=Menu for internal users, 1=external users, 2=both
		$r++;*/

		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=bank',
			'type' => 'left',
			'titre' => 'StripeAccount',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'mainmenu' => 'bank',
			'leftmenu' => 'stripe',
			'url' => '',
			'langs' => 'stripe',
			'position' => 100,
			'enabled' => 'isModEnabled("stripe") && isModenabled("banque")',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);

		$r++;
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=stripe',
			'type' => 'left',
			'titre' => 'StripeChargeList',
			'url' => '/stripe/charge.php',
			'langs' => 'stripe',
			'position' => 102,
			'enabled' => 'isModEnabled("stripe") && isModenabled("banque") && getDolGlobalInt("MAIN_FEATURES_LEVEL") >= 1',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);

		$r++;
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=stripe',
			'type' => 'left',
			'titre' => 'StripeTransactionList',
			'url' => '/stripe/transaction.php',
			'langs' => 'stripe',
			'position' => 102,
			'enabled' => 'isModEnabled("stripe") && isModenabled("banque") && getDolGlobalInt("MAIN_FEATURES_LEVEL") >= 2',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);

		$r++;
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=bank,fk_leftmenu=stripe',
			'type' => 'left',
			'titre' => 'StripePayoutList',
			'url' => '/stripe/payout.php',
			'langs' => 'stripe',
			'position' => 103,
			'enabled' => 'isModEnabled("stripe") && isModenabled("banque")',
			'perms' => '$user->rights->banque->lire',
			'target' => '',
			'user' => 0
		);

		// Exports
		$r = 1;
	}
}
