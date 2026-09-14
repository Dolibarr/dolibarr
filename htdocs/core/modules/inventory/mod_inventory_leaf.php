<?php
/* Copyright (C) 2026	Jose MARTINEZ	<jose.martinez@pichinov.com>
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
 *  \file       htdocs/core/modules/inventory/mod_inventory_leaf.php
 *  \ingroup    stock
 *  \brief      File of class to manage free Inventory numbering (reference typed by the user)
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/inventory/modules_inventory.php';


/**
 * Class to manage the free numbering rule for Inventory (the reference is typed manually)
 */
class mod_inventory_leaf extends ModeleNumRefInventory
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr'; // 'development', 'experimental', 'dolibarr'

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string name
	 */
	public $name = 'leaf';

	/**
	 * @var int 	position
	 */
	public $position = 20;


	/**
	 *  Return description of numbering module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *  @return string      			Descriptive text
	 */
	public function info($langs)
	{
		global $langs;
		$langs->load("stocks");
		return $langs->trans("InventoryNumRefFreeDesc");
	}


	/**
	 *  Return an example of numbering
	 *
	 *  @return     string      Example
	 */
	public function getExample()
	{
		global $langs;
		return $langs->trans("InventoryRefTypedManually");
	}


	/**
	 *  Checks if the numbers already in the database do not
	 *  cause conflicts that would prevent this numbering working.
	 *
	 *  @param  CommonObject	$object		Object we need next value for
	 *  @return boolean     				false if conflict, true if ok
	 */
	public function canBeActivated($object)
	{
		return true;
	}

	/**
	 * 	Return next free value: empty string, the user types the reference.
	 *
	 *  @param  Inventory	$object		Object we need next value for
	 *  @return string|int<-1,0>		Value if OK, 0 if KO
	 */
	public function getNextValue($object)
	{
		return '';
	}
}
