<?php
/*
 * Copyright (C) 2013   Cédric Salvador    <csalvador@gpcsolutions.fr>
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
 *  \file       htdocs/product/stock/lib/replenishment.lib.php
 *  \ingroup    produit
 *  \brief      Contains functions used in replenish.php and replenishorders.php
 */

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

/**
 * Check if there is still some dispatching of stock to do.
 *
 * @param 	int		$order_id		Id of order to check
 * @return	boolean					True = There is some dispatching to do, False = All dispatching is done (may be we receive more) or is not required
 */
function dolDispatchToDo($order_id)
{
	global $db;

	$dispatched = array();
	$ordered = array();

	// Count nb of quantity dispatched per product
	$sql = 'SELECT fk_product, SUM(qty) FROM '.MAIN_DB_PREFIX.'commande_fournisseur_dispatch';
	$sql .= ' WHERE fk_commande = '.((int) $order_id);
	$sql .= ' GROUP BY fk_product';
	$sql .= ' ORDER by fk_product';
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql)) {
		while ($obj = $db->fetch_object($resql)) {
			$dispatched[$obj->fk_product] = $obj;
		}
	}

	// Count nb of quantity to dispatch per product
	$sql = 'SELECT fk_product, SUM(qty) FROM '.MAIN_DB_PREFIX.'commande_fournisseurdet';
	$sql .= ' WHERE fk_commande = '.((int) $order_id);
	$sql .= ' AND fk_product > 0';
	if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
		$sql .= ' AND product_type = 0';
	}
	$sql .= ' GROUP BY fk_product';
	$sql .= ' ORDER by fk_product';
	$resql = $db->query($sql);
	if ($resql && $db->num_rows($resql)) {
		while ($obj = $db->fetch_object($resql)) {
			$ordered[$obj->fk_product] = $obj;
		}
	}

	$todispatch = 0;
	foreach ($ordered as $key => $val) {
		if ($ordered[$key] > $dispatched[$key]) {
			$todispatch++;
		}
	}

	return ($todispatch ? true : false);
	//return true;
}

/**
 * dispatchedOrders
 *
 * @return string		Array of id of orders wit all dispathing already done or not required
 */
function dispatchedOrders()
{
	global $db;

	$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'commande_fournisseur';
	$resql = $db->query($sql);
	$resarray = array();
	if ($resql && $db->num_rows($resql) > 0) {
		while ($obj = $db->fetch_object($resql)) {
			if (!dolDispatchToDo($obj->rowid)) {
				$resarray[] = $obj->rowid;
			}
		}
	}

	if (count($resarray)) {
		$res = '('.implode(',', $resarray).')';
	} else {
		//hack to make sure ordered SQL request won't syntax error
		$res = '(0)';
	}
	return $res;
}

/**
 * ordered
 *
 * @param 	int		$product_id		Product id
 * @return	string|null
 */
function ordered($product_id)
{
	global $db, $langs, $conf;

	$sql = 'SELECT DISTINCT cfd.fk_product, SUM(cfd.qty) as qty FROM';
	$sql .= ' '.MAIN_DB_PREFIX.'commande_fournisseurdet as cfd ';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'commande_fournisseur as cf';
	$sql .= ' ON cfd.fk_commande = cf.rowid WHERE';
	if ($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) {
		$sql .= ' cf.fk_statut < 3';
	} elseif ($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER) {
		$sql .= ' cf.fk_statut < 6 AND cf.rowid NOT IN '.dispatchedOrders();
	} else {
		$sql .= ' cf.fk_statut < 5';
	}
	$sql .= ' AND cfd.fk_product = '.((int) $product_id);
	$sql .= ' GROUP BY cfd.fk_product';

	$resql = $db->query($sql);
	if ($resql) {
		$exists = $db->num_rows($resql);
		if ($exists) {
			$obj = $db->fetch_array($resql);
			return $obj['qty']; //. ' ' . img_picto('','tick');
		} else {
			return null; //img_picto('', 'stcomm-1');
		}
	} else {
		$error = $db->lasterror();
		dol_print_error($db);

		return $langs->trans('error');
	}
}

/**
 * getProducts
 *
 * @param 	int		$order_id		Order id
 * @return	array|integer[]
 */
function getProducts($order_id)
{
	global $db;
	$order = new CommandeFournisseur($db);
	$f = $order->fetch($order_id);
	$products = array();
	if ($f) {
		foreach ($order->lines as $line) {
			if (!in_array($line->fk_product, $products)) {
				$products[] = $line->fk_product;
			}
		}
	}
	return $products;
}
