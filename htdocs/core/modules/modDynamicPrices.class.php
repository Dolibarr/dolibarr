<?php
/* Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
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
 *  \defgroup   produit     Module dynamic prices
 *  \brief      Module to manage dynamic prices in products
 *  \file       htdocs/core/modules/modDynamicPrices.class.php
 *  \ingroup    produit
 *  \brief      Description and activation file for the module to manage dynamic prices in products
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';


/**
 *  Class descriptor of DynamicPrices module
 */
class modDynamicPrices extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 2200;

		$this->family = "products";
		$this->module_position = '85';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Enable the usage of math expressions for prices";
		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = 'experimental';
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		$this->picto = 'dynamicprice';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		//-------------
		$this->config_page_url = array("dynamic_prices.php@product");

		// Dependencies
		//-------------
		$this->depends = array();
		$this->requiredby = array();
		$this->langfiles = array("other");

		// Constants
		//-----------
		$this->const = array();

		// New pages on tabs
		// -----------------
		$this->tabs = array();

		// Boxes
		//------
		$this->boxes = array();

		// Permissions
		//------------
		$this->rights = array();
		$this->rights_class = 'dynamicprices';
		$r = 0;
	}

	/**
	 *  Function called when module is enabled.
	 *  The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *  It also creates data directories.
	 *
	 *  @param      string  $options    Options
	 *  @return     int                 1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		$result = $this->_load_tables('/install/mysql/', 'dynamicprices');
		if ($result < 0) {
			return -1; // Do not activate module if error 'not allowed' returned when loading module SQL queries (the _load_table run sql with run_sql with the error allowed parameter set to 'default')
		}

		// Remove permissions and default values
		$this->remove($options);

		$sql = array();

		return $this->_init($sql, $options);
	}
}
