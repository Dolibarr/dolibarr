<?php
/* Copyright (C) 2019	Laurent Destailleur (eldy)	<eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/mrp/ajax/interface.php
 *	\brief      Ajax search component for Mrp.
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

$warehouse_id = GETPOSTINT('warehouse_id');
$batch = GETPOST('batch', 'alphanohtml');
$fk_product = GETPOSTINT('product_id');
$action = GETPOST('action', 'alphanohtml');

$result = restrictedArea($user, 'mrp');

$permissiontoproduce = $user->hasRight('mrp', 'write');



/*
 * View
 */

top_httphead("application/json");

if ($action == 'updateselectbatchbywarehouse' && $permissiontoproduce) {
	$TRes = array();

	$sql = "SELECT pb.batch, pb.rowid, ps.fk_entrepot, pb.qty, e.ref as label, ps.fk_product";
	$sql .= " FROM " . MAIN_DB_PREFIX . "product_batch as pb";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps on ps.rowid = pb.fk_product_stock";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entrepot as e on e.rowid = ps.fk_entrepot AND e.entity IN (" . getEntity('stock') . ")";
	$sql .= " WHERE ps.fk_product = " .((int) $fk_product);
	if ($warehouse_id > 0) {
		$sql .= " AND fk_entrepot = '" . ((int) $warehouse_id) . "'";
	}
	$sql .= " ORDER BY e.ref, pb.batch";

	$resql = $db->query($sql);

	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			if (empty($TRes[$obj->batch])) {
				$TRes[$obj->batch]  = $obj->qty;
			} else {
				$TRes[$obj->batch] += $obj->qty;
			}
		}
	}

	echo json_encode($TRes);
} elseif ($action == 'updateselectwarehousebybatch' && $permissiontoproduce) {
	$res = 0;

	$sql = "SELECT pb.batch, pb.rowid, ps.fk_entrepot, e.ref, pb.qty";
	$sql .= " FROM " . MAIN_DB_PREFIX . "product_batch as pb";
	$sql .= " JOIN " . MAIN_DB_PREFIX . "product_stock as ps on ps.rowid = pb.fk_product_stock";
	$sql .= " JOIN " . MAIN_DB_PREFIX . "entrepot as e on e.rowid = ps.fk_entrepot AND e.entity IN (" . getEntity('stock') . ")";
	$sql .= " WHERE ps.fk_product = " .((int) $fk_product);
	if ($batch) {
		$sql.= " AND pb.batch = '" . $db->escape($batch) . "'";
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
