<?php
/* Copyright (C) 2014		Alexandre Spangaro	 <aspangaro@open-dsi.fr>
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
 * or see https://www.gnu.org/
 */

/**
 * 		\defgroup   tax		Module Loans
 * 		\brief      Module to include loans management
 *      \file       htdocs/core/modules/modLoan.class.php
 *      \ingroup    loan
 *      \brief      File to activate module loan
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *	Class to manage loan module
 */
class modLoan extends DolibarrModules
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
		$this->numero = 520;

		$this->family = "financial";
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		// Module description used if translation string 'ModuleXXXDesc' not found (where XXX is value of numeric property 'numero' of module)
		$this->description = "Loans management";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';

		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto = 'money-bill-alt';

		// Data directories to create when module is enabled
		$this->dirs = array("/loan/temp");

		// Config pages
		$this->config_page_url = array('loan.php');

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array(); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(5, 4); // Minimum version of PHP required by module
		$this->langfiles = array("loan");

		// Constants
		$this->const = array();
		$this->const[0] = array(
				"LOAN_ACCOUNTING_ACCOUNT_CAPITAL",
				"chaine",
				"164"
		);
		$this->const[1] = array(
				"LOAN_ACCOUNTING_ACCOUNT_INTEREST",
				"chaine",
				"6611"
		);
		$this->const[1] = array(
				"LOAN_ACCOUNTING_ACCOUNT_INSURANCE",
				"chaine",
				"6162"
		);

		// Boxes
		$this->boxes = array();

		// Permissions
		$this->rights = array();
		$this->rights_class = 'loan';
		$r = 0;

		$r++;
		$this->rights[$r][0] = 520;
		$this->rights[$r][1] = 'Read loans';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'read';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 522;
		$this->rights[$r][1] = 'Create/modify loans';
		$this->rights[$r][2] = 'w';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'write';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 524;
		$this->rights[$r][1] = 'Delete loans';
		$this->rights[$r][2] = 'd';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'delete';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 525;
		$this->rights[$r][1] = 'Access loan calculator';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'calc';
		$this->rights[$r][5] = '';

		$r++;
		$this->rights[$r][0] = 527;
		$this->rights[$r][1] = 'Export loans';
		$this->rights[$r][2] = 'r';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'export';
		$this->rights[$r][5] = '';


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		//--------
		$r = 0;
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

		// Clean before activation
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
