<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file       htdocs/core/lib/stock.lib.php
 * \brief      Library file with function for stock module
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function stock_prepare_head($object)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Warehouse");
	$head[$h][2] = 'card';
	$h++;

	if ($user->hasRight('stock', 'mouvement', 'lire')) {
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/movement_list.php?id='.$object->id;
		$head[$h][1] = $langs->trans("StockMovements");
		$head[$h][2] = 'movements';
		$h++;
	}

	/*
	$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche-valo.php?id='.$object->id;
	$head[$h][1] = $langs->trans("EnhancedValue");
	$head[$h][2] = 'value';
	$h++;
	*/

	/* Disabled because will never be implemented. Table always empty.
	if (!empty($conf->global->STOCK_USE_WAREHOUSE_BY_USER))
	{
		// Should not be enabled by defaut because does not work yet correctly because
		// personnal stocks are not tagged into table llx_entrepot
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/user.php?id='.$object->id;
		$head[$h][1] = $langs->trans("Users");
		$head[$h][2] = 'user';
		$h++;
	}
	*/

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'stock', 'add', 'core');

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'stock', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'stock', 'remove');

	return $head;
}

/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function stock_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('entrepot');
	$extrafields->fetch_name_optionals_label('stock_mouvement');
	$extrafields->fetch_name_optionals_label('inventory');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/admin/stock.php';
	$head[$h][1] = $langs->trans("Miscellaneous");
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'stock_admin');

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/stock_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['entrepot']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/stock_mouvement_extrafields.php';
	$head[$h][1] = $langs->trans("StockMouvementExtraFields");
	$nbExtrafields = $extrafields->attributes['stock_mouvement']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'stockMouvementAttributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/inventory_extrafields.php';
	$head[$h][1] = $langs->trans("InventoryExtraFields");
	$nbExtrafields = $extrafields->attributes['inventory']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'inventoryAttributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'stock_admin', 'remove');

	return $head;
}
