<?php
/* Copyright (C) 2024	Laurent Destailleur (eldy)	<eldy@users.sourceforge.net>
 * Copyright (C) 2024	Lionel Vessiller			<lvessiller@open-dsi.fr>
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
 *	\file       htdocs/expedition/ajax/interface.php
 *	\brief      Ajax search component for Shipment.
 */

if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

require '../../main.inc.php'; // Load $user and permissions
/**
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

$warehouse_id = GETPOSTINT('warehouse_id');
$batch = GETPOST('batch', 'alphanohtml');
$product_id = GETPOSTINT('product_id');
$action = GETPOST('action', 'alphanohtml');

$result = restrictedArea($user, 'expedition');

$permissiontowrite = $user->hasRight('expedition', 'write');

$is_eat_by_enabled = !getDolGlobalInt('PRODUCT_DISABLE_EATBY');
$is_sell_by_enabled = !getDolGlobalInt('PRODUCT_DISABLE_SELLBY');


/*
 * View
 */

top_httphead("application/json");

if ($action == 'updateselectbatchbywarehouse' && $permissiontowrite) {
	$resArr = array();

	$sql = "SELECT pb.batch, pb.rowid, ps.fk_entrepot, pb.qty, e.ref as label, ps.fk_product";
	if ($is_eat_by_enabled) {
		$sql .= ", pl.eatby";
	}
	if ($is_sell_by_enabled) {
		$sql .= ", pl.sellby";
	}
	$sql .= " FROM ".$db->prefix()."product_batch as pb";
	$sql .= " LEFT JOIN ".$db->prefix()."product_stock as ps on ps.rowid = pb.fk_product_stock";
	$sql .= " LEFT JOIN ".$db->prefix()."entrepot as e on e.rowid = ps.fk_entrepot AND e.entity IN (".getEntity('stock').")";
	if ($is_eat_by_enabled || $is_sell_by_enabled) {
		$sql .= " LEFT JOIN ".$db->prefix()."product_lot as pl on ps.fk_product = pl.fk_product AND pb.batch = pl.batch";
	}
	$sql .= " WHERE ps.fk_product = ".((int) $product_id);
	if ($warehouse_id > 0) {
		$sql .= " AND fk_entrepot = '".((int) $warehouse_id)."'";
	}
	$sql .= " ORDER BY e.ref, pb.batch";

	$resql = $db->query($sql);

	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$eat_by_date_formatted = '';
			if ($is_eat_by_enabled && !empty($obj->eatby)) {
				$eat_by_date_formatted = dol_print_date($db->jdate($obj->eatby), 'day');
			}
			$sell_by_date_formatted = '';
			if ($is_sell_by_enabled && !empty($obj->sellby)) {
				$sell_by_date_formatted =  dol_print_date($db->jdate($obj->sellby), 'day');
			}

			// set qty
			if (!isset($resArr[$obj->batch])) {
				$resArr[$obj->batch] = array(
					'qty' => (float) $obj->qty,
				);
			} else {
				$resArr[$obj->batch]['qty'] += $obj->qty;
			}

			// set eat-by date
			if (!isset($resArr[$obj->batch]['eatbydate'])) {
				$resArr[$obj->batch]['eatbydate'] = $eat_by_date_formatted;
			}

			// set sell-by date
			if (!isset($resArr[$obj->batch]['sellbydate'])) {
				$resArr[$obj->batch]['sellbydate'] = $sell_by_date_formatted;
			}
		}
	}

	echo json_encode($resArr);
} elseif ($action == 'updateselectwarehousebybatch' && $permissiontowrite) {
	$res = 0;

	$sql = "SELECT pb.batch, pb.rowid, ps.fk_entrepot, e.ref, pb.qty";
	$sql .= " FROM ".$db->prefix()."product_batch as pb";
	$sql .= " JOIN ".$db->prefix()."product_stock as ps on ps.rowid = pb.fk_product_stock";
	$sql .= " JOIN ".$db->prefix()."entrepot as e on e.rowid = ps.fk_entrepot AND e.entity IN (".getEntity('stock').")";
	$sql .= " WHERE ps.fk_product = ".((int) $product_id);
	if ($batch) {
		$sql .= " AND pb.batch = '".$db->escape($batch)."'";
	}
	$sql .= " ORDER BY e.ref, pb.batch";

	$resql = $db->query($sql);

	if ($resql) {
		if ($db->num_rows($resql) == 1) {
			$obj = $db->fetch_object($resql);
			$res = $obj->fk_entrepot;
		}
	}

	echo json_encode($res);
}
