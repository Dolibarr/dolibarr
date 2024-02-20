<?php
/**
 * Copyright (C) 2020 Laurent Destailleur <eldy@users.sourceforge.net>
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
 *	\file       htdocs/bom/ajax/ajax.php
 *	\brief      Ajax component for BOM.
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

include_once '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';


$action = GETPOST('action', 'aZ09');
$idproduct = GETPOST('idproduct', 'int');


/*
 * View
 */

top_httphead('application/json');

if ($action == 'getDurationUnitByProduct' && $user->hasRight('product', 'lire')) {
	$product = new Product($db);
	$res = $product->fetch($idproduct);

	$cUnit = new CUnits($db);
	$fk_unit = $cUnit->getUnitFromCode($product->duration_unit, 'short_label', 'time');

	echo json_encode($fk_unit);
	exit();
}

if ($action == 'getWorkstationByProduct' && $user->hasRight('product', 'lire')) {
	$product = new Product($db);
	$res = $product->fetch($idproduct);

	$result = array();

	if ($res < 0) {
		$error = 'SQL ERROR';
	} elseif ($res == 0) {
		$error = 'NOT FOUND';
	} else {
		$error = null;
		$result['defaultWk']=$product->fk_default_workstation;
	}

	$result['error']=$error;

	echo json_encode($result);
	exit();
}
