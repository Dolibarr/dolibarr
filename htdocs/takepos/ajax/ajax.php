<?php
/* Copyright (C) 2001-2004	Andreu Bisquerra	<jove@bisquerra.com>
 * Copyright (C) 2020		Thibault FOUCART	<support@ptibogxiv.net>
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
 *	\file       htdocs/takepos/ajax/ajax.php
 *	\brief      Ajax search component for TakePos. It search products of a category.
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
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$category = GETPOST('category', 'alphanohtml');	// Can be id of category or 'supplements'
$action = GETPOST('action', 'aZ09');
$term = GETPOST('term', 'alpha');
$id = GETPOST('id', 'int');
$search_start = GETPOST('search_start', 'int');
$search_limit = GETPOST('search_limit', 'int');

if (!$user->hasRight('takepos', 'run')) {
	accessforbidden();
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('takeposproductsearch')); // new context for product search hooks

$pricelevel = 1;	// default price level if PRODUIT_MULTIPRICES. TODO Get price level from thirdparty.



/*
 * View
 */

$thirdparty = new Societe($db);

if ($action == 'getProducts') {
	$tosell = GETPOSTISSET('tosell') ? GETPOST('tosell', 'int') : '';
	$limit = GETPOSTISSET('limit') ? GETPOST('limit', 'int') : 0;
	$offset = GETPOSTISSET('offset') ? GETPOST('offset', 'int') : 0;

	top_httphead('application/json');

	// Search
	if (GETPOSTINT('thirdpartyid') > 0) {
		$result = $thirdparty->fetch(GETPOSTINT('thirdpartyid'));
		if ($result > 0) {
			$pricelevel = $thirdparty->price_level;
		}
	}

	$object = new Categorie($db);
	if ($category == "supplements") {
		$category = getDolGlobalInt('TAKEPOS_SUPPLEMENTS_CATEGORY');
		if (empty($category)) {
			echo 'Error, the category to use for supplements is not defined. Go into setup of module TakePOS.';
			exit;
		}
	}

	$result = $object->fetch($category);
	if ($result > 0) {
		$filter = array();
		if ($tosell != '') {
			$filter = array('customsql' => 'o.tosell = '.((int) $tosell));
		}
		$prods = $object->getObjectsInCateg("product", 0, $limit, $offset, getDolGlobalString('TAKEPOS_SORTPRODUCTFIELD'), 'ASC', $filter);
		// Removed properties we don't need
		$res = array();
		if (is_array($prods) && count($prods) > 0) {
			foreach ($prods as $prod) {
				if (getDolGlobalInt('TAKEPOS_PRODUCT_IN_STOCK') == 1) {
					// remove products without stock
					$prod->load_stock('nobatch,novirtual');
					if ($prod->stock_warehouse[getDolGlobalString('CASHDESK_ID_WAREHOUSE'.$_SESSION['takeposterminal'])]->real <= 0) {
						continue;
					}
				}
				unset($prod->fields);
				unset($prod->db);

				$prod->price_formated = price(price2num(empty($prod->multiprices[$pricelevel]) ? $prod->price : $prod->multiprices[$pricelevel], 'MT'), 1, $langs, 1, -1, -1, $conf->currency);
				$prod->price_ttc_formated = price(price2num(empty($prod->multiprices_ttc[$pricelevel]) ? $prod->price_ttc : $prod->multiprices_ttc[$pricelevel], 'MT'), 1, $langs, 1, -1, -1, $conf->currency);

				$res[] = $prod;
			}
		}
		echo json_encode($res);
	} else {
		echo 'Failed to load category with id='.dol_escape_htmltag($category);
	}
} elseif ($action == 'search' && $term != '') {
	top_httphead('application/json');

	// Search barcode into thirdparties. If found, it means we want to change thirdparties.
	$result = $thirdparty->fetch('', '', '', $term);

	if ($result && $thirdparty->id > 0) {
		$rows = array();
		$rows[] = array(
				'rowid' => $thirdparty->id,
				'name' => $thirdparty->name,
				'barcode' => $thirdparty->barcode,
				'object' => 'thirdparty'
			);
		echo json_encode($rows);
		exit;
	}

	// Search
	if (GETPOSTINT('thirdpartyid') > 0) {
		$result = $thirdparty->fetch(GETPOSTINT('thirdpartyid'));
		if ($result > 0) {
			$pricelevel = $thirdparty->price_level;
		}
	}

	// Define $filteroncategids, the filter on category ID if there is a Root category defined.
	$filteroncategids = '';
	if (getDolGlobalInt('TAKEPOS_ROOT_CATEGORY_ID') > 0) {	// A root category is defined, we must filter on products inside this category tree
		$object = new Categorie($db);
		//$result = $object->fetch($conf->global->TAKEPOS_ROOT_CATEGORY_ID);
		$arrayofcateg = $object->get_full_arbo('product', $conf->global->TAKEPOS_ROOT_CATEGORY_ID, 1);
		if (is_array($arrayofcateg) && count($arrayofcateg) > 0) {
			foreach ($arrayofcateg as $val) {
				$filteroncategids .= ($filteroncategids ? ', ' : '').$val['id'];
			}
		}
	}

	$barcode_rules = getDolGlobalString('TAKEPOS_BARCODE_RULE_TO_INSERT_PRODUCT');
	if (isModEnabled('barcode') && !empty($barcode_rules)) {
		$barcode_rules_list = array();

		// get barcode rules
		$barcode_char_nb = 0;
		$barcode_rules_arr = explode('+', $barcode_rules);
		foreach ($barcode_rules_arr as $barcode_rules_values) {
			$barcode_rules_values_arr = explode(':', $barcode_rules_values);
			if (count($barcode_rules_values_arr) == 2) {
				$char_nb = intval($barcode_rules_values_arr[1]);
				$barcode_rules_list[] = array('code' => $barcode_rules_values_arr[0], 'char_nb' => $char_nb);
				$barcode_char_nb += $char_nb;
			}
		}

		$barcode_value_list = array();
		$barcode_offset = 0;
		$barcode_length = dol_strlen($term);
		if ($barcode_length == $barcode_char_nb) {
			$rows = array();

			// split term with barcode rules
			foreach ($barcode_rules_list as $barcode_rule_arr) {
				$code = $barcode_rule_arr['code'];
				$char_nb = $barcode_rule_arr['char_nb'];
				$barcode_value_list[$code] = substr($term, $barcode_offset, $char_nb);
				$barcode_offset += $char_nb;
			}

			if (isset($barcode_value_list['ref'])) {
				// search product from reference
				$sql  = "SELECT rowid, ref, label, tosell, tobuy, barcode, price, price_ttc";
				$sql .= " FROM " . $db->prefix() . "product as p";
				$sql .= " WHERE entity IN (" . getEntity('product') . ")";
				$sql .= " AND ref = '" . $db->escape($barcode_value_list['ref']) . "'";
				if ($filteroncategids) {
					$sql .= " AND EXISTS (SELECT cp.fk_product FROM " . $db->prefix() . "categorie_product as cp WHERE cp.fk_product = p.rowid AND cp.fk_categorie IN (".$db->sanitize($filteroncategids)."))";
				}
				$sql .= " AND tosell = 1";
				$sql .= " AND (barcode IS NULL OR barcode <> '" . $db->escape($term) . "')";

				$resql = $db->query($sql);
				if ($resql && $db->num_rows($resql) == 1) {
					if ($obj = $db->fetch_object($resql)) {
						$qty = 1;
						if (isset($barcode_value_list['qu'])) {
							$qty_str = $barcode_value_list['qu'];
							if (isset($barcode_value_list['qd'])) {
								$qty_str .= '.' . $barcode_value_list['qd'];
							}
							$qty = (float) $qty_str;
						}

						$objProd = new Product($db);
						$objProd->fetch($obj->rowid);

						$ig = '../public/theme/common/nophoto.png';
						if (!getDolGlobalString('TAKEPOS_HIDE_PRODUCT_IMAGES')) {
							$image = $objProd->show_photos('product', $conf->product->multidir_output[$objProd->entity], 'small', 1);

							$match = array();
							preg_match('@src="([^"]+)"@', $image, $match);
							$file = array_pop($match);

							if ($file != '') {
								if (!defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
									$ig = $file.'&cache=1';
								} else {
									$ig = $file.'&cache=1&publictakepos=1&modulepart=product';
								}
							}
						}

						$rows[] = array(
							'rowid' => $obj->rowid,
							'ref' => $obj->ref,
							'label' => $obj->label,
							'tosell' => $obj->tosell,
							'tobuy' => $obj->tobuy,
							'barcode' => $obj->barcode,
							'price' => empty($objProd->multiprices[$pricelevel]) ? $obj->price : $objProd->multiprices[$pricelevel],
							'price_ttc' => empty($objProd->multiprices_ttc[$pricelevel]) ? $obj->price_ttc : $objProd->multiprices_ttc[$pricelevel],
							'object' => 'product',
							'img' => $ig,
							'qty' => $qty,
						);
					}
					$db->free($resql);
				}
			}

			if (count($rows) == 1) {
				echo json_encode($rows);
				exit();
			}
		}
	}

	$sql = 'SELECT p.rowid, p.ref, p.label, p.tosell, p.tobuy, p.barcode, p.price, p.price_ttc' ;
	if (getDolGlobalInt('TAKEPOS_PRODUCT_IN_STOCK') == 1) {
		if (getDolGlobalInt('CASHDESK_ID_WAREHOUSE'.$_SESSION['takeposterminal'])) {
			$sql .= ', ps.reel';
		} else {
			$sql .= ', SUM(ps.reel) as reel';
		}
	}
	/* this will be possible when field archive will be supported into llx_product_price
	if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
		$sql .= ', pp.price_level, pp.price as multiprice_ht, pp.price_ttc as multiprice_ttc';
	}*/
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);
	if ($reshook >= 0) {
		$sql .= $hookmanager->resPrint;
	}

	$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
	/* this will be possible when field archive will be supported into llx_product_price
	if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_price as pp ON pp.fk_product = p.rowid AND pp.entity = ".((int) $conf->entity)." AND pp.price_level = ".((int) $pricelevel);
		$sql .= " AND archive = 0";
	}*/
	if (getDolGlobalInt('TAKEPOS_PRODUCT_IN_STOCK') == 1) {
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_stock as ps';
		$sql .= ' ON (p.rowid = ps.fk_product';
		if (getDolGlobalString('CASHDESK_ID_WAREHOUSE'.$_SESSION['takeposterminal'])) {
			$sql .= " AND ps.fk_entrepot = ".((int) getDolGlobalInt("CASHDESK_ID_WAREHOUSE".$_SESSION['takeposterminal']));
		}
		$sql .= ')';
	}

	// Add tables from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListTables', $parameters);
	if ($reshook >= 0) {
		$sql .= $hookmanager->resPrint;
	}

	$sql .= ' WHERE p.entity IN ('.getEntity('product').')';
	if ($filteroncategids) {
		$sql .= ' AND EXISTS (SELECT cp.fk_product FROM '.MAIN_DB_PREFIX.'categorie_product as cp WHERE cp.fk_product = p.rowid AND cp.fk_categorie IN ('.$db->sanitize($filteroncategids).'))';
	}
	$sql .= ' AND p.tosell = 1';
	if (getDolGlobalInt('TAKEPOS_PRODUCT_IN_STOCK') == 1 && getDolGlobalInt('CASHDESK_ID_WAREHOUSE'.$_SESSION['takeposterminal'])) {
		$sql .= ' AND ps.reel > 0';
	}
	$sql .= natural_search(array('ref', 'label', 'barcode'), $term);
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
	if ($reshook >= 0) {
		$sql .= $hookmanager->resPrint;
	}

	if (getDolGlobalInt('TAKEPOS_PRODUCT_IN_STOCK') == 1 && !getDolGlobalInt('CASHDESK_ID_WAREHOUSE'.$_SESSION['takeposterminal'])) {
		$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.tosell, p.tobuy, p.barcode, p.price, p.price_ttc';
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters);
		if ($reshook >= 0) {
			$sql .= $hookmanager->resPrint;
		}
		$sql .= ' HAVING SUM(ps.reel) > 0';
	}

	// load only one page of products
	$sql.= $db->plimit($search_limit, $search_start);

	$resql = $db->query($sql);
	if ($resql) {
		$rows = array();

		while ($obj = $db->fetch_object($resql)) {
			$objProd = new Product($db);
			$objProd->fetch($obj->rowid);
			$image = $objProd->show_photos('product', $conf->product->multidir_output[$objProd->entity], 'small', 1);

			$match = array();
			preg_match('@src="([^"]+)"@', $image, $match);
			$file = array_pop($match);

			if ($file == "") {
				$ig = '../public/theme/common/nophoto.png';
			} else {
				if (!defined('INCLUDE_PHONEPAGE_FROM_PUBLIC_PAGE')) {
					$ig = $file.'&cache=1';
				} else {
					$ig = $file.'&cache=1&publictakepos=1&modulepart=product';
				}
			}

			$row = array(
				'rowid' => $obj->rowid,
				'ref' => $obj->ref,
				'label' => $obj->label,
				'tosell' => $obj->tosell,
				'tobuy' => $obj->tobuy,
				'barcode' => $obj->barcode,
				'price' => empty($objProd->multiprices[$pricelevel]) ? $obj->price : $objProd->multiprices[$pricelevel],
				'price_ttc' => empty($objProd->multiprices_ttc[$pricelevel]) ? $obj->price_ttc : $objProd->multiprices_ttc[$pricelevel],
				'object' => 'product',
				'img' => $ig,
				'qty' => 1,
				'price_formated' => price(price2num(empty($objProd->multiprices[$pricelevel]) ? $obj->price : $objProd->multiprices[$pricelevel], 'MT'), 1, $langs, 1, -1, -1, $conf->currency),
				'price_ttc_formated' => price(price2num(empty($objProd->multiprices_ttc[$pricelevel]) ? $obj->price_ttc : $objProd->multiprices_ttc[$pricelevel], 'MT'), 1, $langs, 1, -1, -1, $conf->currency)
			);
			// Add entries to row from hooks
			$parameters=array();
			$parameters['row'] = $row;
			$parameters['obj'] = $obj;
			$reshook = $hookmanager->executeHooks('completeAjaxReturnArray', $parameters);
			if ($reshook > 0) {
				// replace
				if (count($hookmanager->resArray)) {
					$row = $hookmanager->resArray;
				} else {
					$row = array();
				}
				$rows[] = $row;
			} else {
				// add
				if (count($hookmanager->resArray)) {
					$rows[] = $hookmanager->resArray;
				}
				$rows[] = $row;
			}
		}

		echo json_encode($rows);
	} else {
		echo 'Failed to search product : '.$db->lasterror();
	}
} elseif ($action == "opendrawer" && $term != '') {
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
	$printer = new dolReceiptPrinter($db);
	// check printer for terminal
	if (getDolGlobalInt('TAKEPOS_PRINTER_TO_USE'.$term) > 0) {
		$printer->initPrinter(getDolGlobalInt('TAKEPOS_PRINTER_TO_USE'.$term));
		// open cashdrawer
		$printer->pulse();
		$printer->close();
	}
} elseif ($action == "printinvoiceticket" && $term != '' && $id > 0 && $user->hasRight('facture', 'lire')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$printer = new dolReceiptPrinter($db);
	// check printer for terminal
	if ((getDolGlobalInt('TAKEPOS_PRINTER_TO_USE'.$term) > 0 || getDolGlobalString('TAKEPOS_PRINT_METHOD') == "takeposconnector") && getDolGlobalInt('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$term) > 0) {
		$object = new Facture($db);
		$object->fetch($id);
		$ret = $printer->sendToPrinter($object, getDolGlobalString('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$term), getDolGlobalString('TAKEPOS_PRINTER_TO_USE'.$term));
	}
} elseif ($action == 'getInvoice') {
	top_httphead('application/json');

	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

	$object = new Facture($db);
	if ($id > 0) {
		$object->fetch($id);
	}

	echo json_encode($object);
} elseif ($action == 'thecheck') {
	$place = GETPOST('place', 'alpha');
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';

	$object = new Facture($db);

	$printer = new dolReceiptPrinter($db);
	$printer->sendToPrinter($object, getDolGlobalString('TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$term), getDolGlobalString('TAKEPOS_PRINTER_TO_USE'.$term));
}
