<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2014 Cedric GROSS         <c.gross@kreiz-it.fr>
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
 *	\defgroup   productbatch     Module batch number management
 *	\brief      Management module for batch number, eat-by and sell-by date for product
 *  \file       htdocs/core/modules/modProductBatch.class.php
 *  \ingroup    productbatch
 *  \brief      Description and activation file for the module productbatch
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Description and activation class for module productdluo
 */
class modProductBatch extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		$this->db = $db;
		$this->numero = 39000;

		$this->family = "products";
		$this->module_position = '45';

		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Batch number, eat-by and sell-by date management module";

		$this->rights_class = 'productbatch';
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'dolibarr';
		// Key used in llx_const table to save module status enabled/disabled (where dluo is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);

		$this->picto = 'lot';

		$this->module_parts = array();

		// Data directories to create when module is enabled.
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into productdluo/admin directory, to use to setup module.
		$this->config_page_url = array("product_lot.php@product");

		// Dependencies
		$this->hidden = false; // A condition to hide module
		$this->depends = array("modProduct", "modStock", "modExpedition", "modFournisseur"); // List of module class names as string that must be enabled if this module is enabled
		$this->requiredby = array(); // List of module ids to disable if this one is disabled
		$this->conflictwith = array(); // List of module class names as string this module is in conflict with
		$this->phpmin = array(7, 0); // Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3, 0); // Minimum version of Dolibarr required by module
		$this->langfiles = array("productbatch");

		// Constants
		// Constants
		$this->const = array();
		$r = 0;

		$this->const[$r][0] = "PRODUCTBATCH_LOT_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_lot_free";
		$this->const[$r][3] = 'Module to control lot number';
		$this->const[$r][4] = 0;
		$r++;

		$this->const[$r][0] = "PRODUCTBATCH_SN_ADDON";
		$this->const[$r][1] = "chaine";
		$this->const[$r][2] = "mod_sn_free";
		$this->const[$r][3] = 'Module to control serial number';
		$this->const[$r][4] = 0;
		$r++;

		$this->tabs = array();

		// Dictionaries
		if (!isset($conf->productbatch->enabled)) {
			$conf->productbatch = new stdClass();
			$conf->productbatch->enabled = 0;
		}
		$this->dictionaries = array();

		// Boxes
		$this->boxes = array(); // List of boxes

		// Permissions
		$this->rights = array(); // Permission array used by this module
		$r = 0;


		// Menus
		//-------
		$this->menu = 1; // This module add menu entries. They are coded into menu manager.


		// Exports
		$r = 0;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $db, $conf;

		$sql = array();

		if (!empty($conf->cashdesk->enabled)) {
			if (!getDolGlobalString('CASHDESK_NO_DECREASE_STOCK')) {
				include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
				$res = dolibarr_set_const($db, "CASHDESK_NO_DECREASE_STOCK", 1, 'chaine', 0, '', $conf->entity);
			}
		}

		return $this->_init($sql, $options);
	}
}
