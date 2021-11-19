<?php

/*
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
 *	\file       /htdocs/product/inventory/ajax/searchfrombarcode.php
 *	\brief      File to make Ajax action on product and stock
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
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
require '../../../main.inc.php';
//include_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
$object = new Inventory($db);


$action = GETPOST("action", "alpha");
$barcode = GETPOST("barcode", "aZ09");
$product = GETPOST("product");
$response = "";
$fk_entrepot = GETPOST("fk_entrepot", "int");
$warehousefound = 0;
$warehouseid = 0;
if ($action == "existbarcode" && !empty($barcode)) {
	$sql = "SELECT ps.fk_entrepot, ps.fk_product, p.barcode";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
	$sql .= " WHERE p.barcode = '".$db->escape($barcode)."'";
	if (!empty($fk_entrepot)) {
		$sql .= "AND ps.fk_entrepot = '".$db->escape($fk_entrepot)."'";
	}
	$result = $db->query($sql);
	if ($result) {
		$nbline = $db->num_rows($resql);
		for ($i=0; $i < $nbline; $i++) {
			$object = $db->fetch_object($resql);
			if ($barcode == $object->barcode) {
				if (!empty($object->fk_entrepot) && $product["Warehouse"] == $object->fk_entrepot) {
					$warehousefound++;
					$warehouseid = $object->fk_entrepot;
				}
			}
		}
		if ($warehousefound < 1) {
			$response = array('status'=>'error','errorcode'=>'NotFound','message'=>'No warehouse found for barcode'.$barcode);
		} elseif ($warehousefound > 1) {
			$response = array('status'=>'error','errorcode'=>'TooManyWarehouse','message'=>'Too many warehouse found');
		} else {
			$response = array('status'=>'success','message'=>'Warehouse found','warehouse'=>$warehouseid);
		}
		$response = json_encode($response);
	} else {
		$response = "No results found for barcode";
	}
} else {
	$response = "Error on action";
}

echo $response;
