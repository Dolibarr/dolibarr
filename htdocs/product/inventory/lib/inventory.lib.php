<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file		lib/inventory.lib.php
 *	\ingroup	inventory
 *	\brief		This file is an example module library
 */

/**
 *  Define head array for tabs of inventory tools setup pages
 *
 *  @return array Array of head
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
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'inventory');

    return $head;
}

/**
 *  Define head array for tabs of inventory tools setup pages
 *
 *  @param  Inventory   $inventory      Object inventory
 *  @param  string      $title          parameter
 *  @param  string      $get            parameter
 *
 *  @return array                       Array of head
 */
function inventoryPrepareHead(&$inventory, $title = 'Inventory', $get = '')
{
	global $langs;

	return array(
		array(dol_buildpath('/product/inventory/card.php?id='.$inventory->id.$get, 1), $langs->trans($title),'inventory')
	);
}



/**
 *  Define head array for tabs of inventory tools setup pages
 *
 *  @param   Inventory  $inventory      Object inventory
 *
 *  @return string                      html of products
 */
function inventorySelectProducts(&$inventory)
{
	global $conf,$db,$langs;

	$except_product_id = array();

	foreach ($inventory->Inventorydet as $Inventorydet)
	{
		$except_product_id[] = $Inventorydet->fk_product;
	}

	ob_start();
	$form = new Form($db);
	$form->select_produits(-1, 'fk_product');

	$TChildWarehouses = array($inventory->fk_warehouse);
	$e = new Entrepot($db);
	$e->fetch($inventory->fk_warehouse);
	if(method_exists($e, 'get_children_warehouses')) $e->get_children_warehouses($e->id, $TChildWarehouses);

	$Tab = array();
	$sql = 'SELECT rowid, label
			FROM '.MAIN_DB_PREFIX.'entrepot WHERE rowid IN('.implode(', ', $TChildWarehouses).')';
	if(method_exists($e, 'get_children_warehouses')) $sql.= ' ORDER BY fk_parent';
	$resql = $db->query($sql);
	while($res = $db->fetch_object($resql)) {
		$Tab[$res->rowid] = $res->label;
	}
	print '&nbsp;&nbsp;&nbsp;';
	print $langs->trans('Warehouse').' : '.$form::selectarray('fk_warehouse', $Tab);

	$select_html = ob_get_clean();

	return $select_html;
}
