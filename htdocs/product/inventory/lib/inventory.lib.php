<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 * Copyright (C) 2024		MDW				<mdeweerd@users.noreply.github.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/inventory.lib.php
 *	\ingroup	inventory
 *	\brief		This file is an example module library
 */

/**
 *  Define head array for tabs of inventory tools setup pages
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function inventoryAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("inventory");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/admin/inventory.php";
	$head[$h][1] = $langs->trans("Parameters");
	$head[$h][2] = 'settings';
	$h++;


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@inventory:/inventory/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@inventory:/inventory/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'inventory');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'inventory', 'remove');

	return $head;
}

/**
 *  Define head array for tabs of inventory tools setup pages
 *
 *  @param  Inventory   $inventory      Object inventory
 *  @param  string      $title          parameter
 *  @param  string      $get            parameter
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function inventoryPrepareHead(&$inventory, $title = 'Inventory', $get = '')
{
	global $langs, $conf;

	$head = array(
		array(dol_buildpath('/product/inventory/card.php?id='.$inventory->id.$get, 1), $langs->trans('Card'), 'card'),
		array(dol_buildpath('/product/inventory/inventory.php?id='.$inventory->id.$get, 1), $langs->trans('Inventory'), 'inventory')
	);

	$h = 2;

	complete_head_from_modules($conf, $langs, $inventory, $head, $h, 'inventory');
	complete_head_from_modules($conf, $langs, $inventory, $head, $h, 'inventory', 'remove');

	return $head;
}
