<?php
/* Copyright (C) 2004-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011 Juanjo Menent 		<jmenent@2byte.es>
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
 *	\defgroup   	paymentbybanktransfer     Module paymentbybanktransfer
 *	\brief      	Module to manage payment by bank transfer
 *	\file       	htdocs/core/modules/modPaymentByBankTransfer.class.php
 *	\ingroup    	paymentbybanktransfer
 *	\brief      	Description and activation file for the module PaymentByBankTransfer
 */

include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to describe and enable module of payment by Bank transfer
 */
class modPaymentByBankTransfer extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;
		$this->numero = 56;

		$this->family = "financial";
		$this->module_position = '52';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Management of payment by bank transfer";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of png file (without png) used for this module
		$this->picto = 'payment';

		// Data directories to create when module is enabled
		$this->dirs = array("/paymentbybanktransfer/temp", "/paymentbybanktransfer/receipts");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array("modFournisseur", "modBanque"); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module

		// Config pages
		$this->config_page_url = array("paymentbybanktransfer.php");

		// Constants
		$this->const = array();
		$r = 0;

		/*$this->const[$r][0] = "BANK_ADDON_PDF";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "sepamandate";
		$this->const[$r][3] = 'Name of manager to generate SEPA mandate';
		$this->const[$r][4] = 0;
		$r++;*/


		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'paymentbybanktransfer';
		$r = 0;
		$r++;
		$this->rights[$r][0] = 561;
		$this->rights[$r][1] = 'Read bank transfer payment orders';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';

		$r++;
		$this->rights[$r][0] = 562;
		$this->rights[$r][1] = 'Create/modify a bank transfer payment order';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'create';

		$r++;
		$this->rights[$r][0] = 563;
		$this->rights[$r][1] = 'Send/Transmit bank transfer payment order';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'send';

		$r++;
		$this->rights[$r][0] = 564;
		$this->rights[$r][1] = 'Record Debits/Rejects of bank transfer payment order';
		$this->rights[$r][2] = 'a';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'debit';

		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.
	}


	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories
	 *
	 *  @param      string	$options    Options when enabling module ('', 'noboxes')
	 *  @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf;

		// Permissions
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
