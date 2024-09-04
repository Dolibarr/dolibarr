<?php
/* Copyright (C) 2023 	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2023   Lionel Vessiller     	<lvessiller@easya.solutions>
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
 *	\file       htdocs/product/ajax/product_lot.php
 *	\brief      Ajax search component for ProductLot.
 */

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
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// Load Dolibarr environment
require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

$action = GETPOST('action', 'aZ09');
$productId = GETPOSTINT('product_id');
$batch = GETPOST('batch', 'alphanohtml');

// Security check
restrictedArea($user, 'produit|service', $productId, 'product&product');

$permissiontoread = $user->hasRight('stock', 'lire');

/*
 * View
 */

top_httphead('application/json');

$rows = array();

if ($action == 'search' && $batch != '' && $permissiontoread) {
	$productLot = new Productlot($db);
	$result = $productLot->fetch('', $productId, $batch);

	if ($result > 0 && $productLot->id > 0) {
		$rows[] = array(
			'rowid' => $productLot->id,
			'sellby' => ($productLot->sellby ? dol_print_date($productLot->sellby, 'day') : ''),
			'eatby' => ($productLot->eatby ? dol_print_date($productLot->eatby, 'day') : ''),
		);
	}
}

echo json_encode($rows);
exit();
