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
	define('NOTOKENRENEWAL', 1); // Disables token renewal
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
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/product/stock/class/entrepot.class.php";
$warehouse = new Entrepot($db);

$action = GETPOST("action", "alpha");
$barcode = GETPOST("barcode", "aZ09");
$product = GETPOST("product");
$response = "";

$fk_entrepot = GETPOSTINT("fk_entrepot");
$fk_inventory = GETPOSTINT("fk_inventory");
$fk_product = GETPOSTINT("fk_product");
$reelqty = GETPOSTINT("reelqty");
$batch = GETPOSTINT("batch");
$mode = GETPOST("mode", "aZ");

$warehousefound = 0;
$warehouseid = 0;
$objectreturn = array();

/*
 * Action
 */

// None


/*
 * View
 */

top_httphead('application/json');

if ($action == "existbarcode" && !empty($barcode) && $user->hasRight('stock', 'lire')) {
	if (!empty($mode) && $mode == "lotserial") {
		$sql = "SELECT ps.fk_entrepot, ps.fk_product, p.barcode, ps.reel, pb.batch";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_batch as pb";
		$sql .= " JOIN ".MAIN_DB_PREFIX."product_stock as ps ON pb.fk_product_stock = ps.rowid JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
		$sql .= " WHERE pb.batch = '".$db->escape($barcode)."'";
	} else {
		$sql = "SELECT ps.fk_entrepot, ps.fk_product, p.barcode,ps.reel";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_stock as ps JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
		$sql .= " WHERE p.barcode = '".$db->escape($barcode)."'";
	}
	if (!empty($fk_entrepot)) {
		$sql .= " AND ps.fk_entrepot = '".$db->escape($fk_entrepot)."'";
	}
	if (!empty($fk_product)) {
		$sql .= " AND ps.fk_product = '".$db->escape($fk_product)."'";
	}
	$result = $db->query($sql);
	if ($result) {
		$nbline = $db->num_rows($result);
		for ($i=0; $i < $nbline; $i++) {
			$object = $db->fetch_object($result);
			if (($mode == "barcode" && $barcode == $object->barcode) || ($mode == "lotserial" && $barcode == $object->batch)) {
				$warehouse->fetch(0, $product["Warehouse"]);
				if (!empty($object->fk_entrepot) && $warehouse->id == $object->fk_entrepot) {
					$warehousefound++;
					$warehouseid = $object->fk_entrepot;
					$fk_product = $object->fk_product;
					$reelqty = $object->reel;

					$objectreturn = array('fk_warehouse'=>$warehouseid,'fk_product'=>$fk_product,'reelqty'=>$reelqty);
				}
			}
		}
		if ($warehousefound < 1) {
			$response = array('status'=>'error','errorcode'=>'NotFound','message'=>'No warehouse found for barcode'.$barcode);
		} elseif ($warehousefound > 1) {
			$response = array('status'=>'error','errorcode'=>'TooManyWarehouse','message'=>'Too many warehouse found');
		} else {
			$response = array('status'=>'success','message'=>'Warehouse found','object'=>$objectreturn);
		}
	} else {
		$response = array('status'=>'error','errorcode'=>'NotFound','message'=>"No results found for barcode");
	}
} else {
	$response = array('status'=>'error','errorcode'=>'ActionError','message'=>"Error on action");
}

if ($action == "addnewlineproduct" && $user->hasRight('stock', 'creer')) {
	require_once DOL_DOCUMENT_ROOT."/product/inventory/class/inventory.class.php";
	$inventoryline = new InventoryLine($db);
	if (!empty($fk_inventory)) {
		$inventoryline->fk_inventory = $fk_inventory;

		$inventoryline->fk_warehouse = $fk_entrepot;
		$inventoryline->fk_product = $fk_product;
		$inventoryline->qty_stock = $reelqty;
		if (!empty($batch)) {
			$inventoryline->batch = $batch;
		}
		$inventoryline->datec = dol_now();

		$result = $inventoryline->create($user);
		if ($result > 0) {
			$response = array('status'=>'success','message'=>'Success on creating line','id_line'=>$result);
		} else {
			$response = array('status'=>'error','errorcode'=>'ErrorCreation','message'=>"Error on line creation");
		}
	} else {
		$response = array('status'=>'error','errorcode'=>'NoIdForInventory','message'=>"No id for inventory");
	}
}

$response = json_encode($response);
echo $response;
