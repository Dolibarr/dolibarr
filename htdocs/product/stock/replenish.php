<?php
/* Copyright (C) 2013		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2013-2018	Laurent Destaileur	<ely@users.sourceforge.net>
 * Copyright (C) 2014		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2016		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016		ATM Consulting		<support@atm-consulting.fr>
 * Copyright (C) 2019-2024	Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2021		Antonin MARCHAL		<antonin@letempledujeu.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/product/stock/replenish.php
 *  \ingroup    stock
 *  \brief      Page to list stocks to replenish
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/html.formproduct.class.php';
require_once './lib/replenishment.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'orders'));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('stockreplenishlist'));

$result = restrictedArea($user, 'produit|service');

//checks if a product has been ordered

$action = GETPOST('action', 'aZ09');
$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$type = GETPOSTINT('type');
$tobuy = GETPOSTINT('tobuy');
$salert = GETPOST('salert', 'alpha');
$includeproductswithoutdesiredqty = GETPOST('includeproductswithoutdesiredqty', 'alpha');
$mode = GETPOST('mode', 'alpha');
$draftorder = GETPOST('draftorder', 'alpha');


$fourn_id = GETPOSTINT('fourn_id');
$fk_supplier = GETPOSTINT('fk_supplier');
$fk_entrepot = GETPOSTINT('fk_entrepot');

// List all visible warehouses
$resWar = $db->query("SELECT rowid FROM " . MAIN_DB_PREFIX . "entrepot WHERE entity IN (" . $db->sanitize(getEntity('stock')) . ")");
$listofqualifiedwarehousesid = "";
$count = 0;
while ($tmpobj = $db->fetch_object($resWar)) {
	if (!empty($listofqualifiedwarehousesid)) {
		$listofqualifiedwarehousesid .= ",";
	}
	$listofqualifiedwarehousesid .= $tmpobj->rowid;
	$lastWarehouseID = $tmpobj->rowid;
	$count++;
}

//MultiCompany : If only 1 Warehouse is visible, filter will automatically be set to it.
if ($count == 1 && (empty($fk_entrepot) || $fk_entrepot <= 0) && getDolGlobalString('MULTICOMPANY_PRODUCT_SHARING_ENABLED')) {
	$fk_entrepot = $lastWarehouseID;
}

$texte = '';

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$offset = $limit * $page;

if (!$sortfield) {
	$sortfield = 'p.ref';
}

if (!$sortorder) {
	$sortorder = 'ASC';
}

// Define virtualdiffersfromphysical
$virtualdiffersfromphysical = 0;
if (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT')
	|| getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER')
	|| getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE')
	|| getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION')
	|| getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE')
	|| isModEnabled('mrp')) {
	$virtualdiffersfromphysical = 1; // According to increase/decrease stock options, virtual and physical stock may differs.
}

if ($virtualdiffersfromphysical) {
	$usevirtualstock = getDolGlobalString('STOCK_USE_REAL_STOCK_BY_DEFAULT_FOR_REPLENISHMENT') ? 0 : 1;
} else {
	$usevirtualstock = 0;
}
if ($mode == 'physical') {
	$usevirtualstock = 0;
}
if ($mode == 'virtual') {
	$usevirtualstock = 1;
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$search_ref = '';
	$search_label = '';
	$sall = '';
	$salert = '';
	$includeproductswithoutdesiredqty = '';
	$draftorder = '';
}
$draftchecked = "";
if ($draftorder == 'on') {
	$draftchecked = "checked";
}

// Create purchase orders
if ($action == 'order' && GETPOST('valid') && $user->hasRight('fournisseur', 'commande', 'creer')) {
	$linecount = GETPOSTINT('linecount');
	$box = 0;
	$errorQty = 0;
	unset($_POST['linecount']);
	if ($linecount > 0) {
		$db->begin();

		$suppliers = array();
		require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.product.class.php';
		$productsupplier = new ProductFournisseur($db);
		for ($i = 0; $i < $linecount; $i++) {
			if (GETPOST('choose'.$i) === 'on' && GETPOSTINT('fourn'.$i) > 0) {
				//one line
				$box = $i;
				$supplierpriceid = GETPOSTINT('fourn'.$i);
				//get all the parameters needed to create a line
				$qty = GETPOSTINT('tobuy'.$i);
				$idprod = $productsupplier->get_buyprice($supplierpriceid, $qty);
				$res = $productsupplier->fetch($idprod);
				if ($res && $idprod > 0) {
					if ($qty) {
						//might need some value checks
						$line = new CommandeFournisseurLigne($db);
						$line->qty = $qty;
						$line->fk_product = $idprod;

						//$product = new Product($db);
						//$product->fetch($obj->fk_product);
						if (getDolGlobalInt('MAIN_MULTILANGS')) {
							$productsupplier->getMultiLangs();
						}

						// if we use supplier description of the products
						if (!empty($productsupplier->desc_supplier) && getDolGlobalString('PRODUIT_FOURN_TEXTS')) {
							$desc = $productsupplier->desc_supplier;
						} else {
							$desc = $productsupplier->description;
						}
						$line->desc = $desc;
						if (getDolGlobalInt('MAIN_MULTILANGS')) {
							// TODO Get desc in language of thirdparty
						}

						// If we use multicurrency
						if (isModEnabled('multicurrency') && !empty($productsupplier->fourn_multicurrency_code) && $productsupplier->fourn_multicurrency_code != $conf->currency) {
							$line->multicurrency_code = $productsupplier->fourn_multicurrency_code;
							$line->fk_multicurrency = $productsupplier->fourn_multicurrency_id;
							$line->multicurrency_subprice = $productsupplier->fourn_multicurrency_unitprice;
						}

						$line->tva_tx = $productsupplier->vatrate_supplier;
						$line->subprice = $productsupplier->fourn_pu;
						$line->total_ht = $productsupplier->fourn_pu * $qty;
						$tva = $line->tva_tx / 100;
						$line->total_tva = $line->total_ht * $tva;
						$line->total_ttc = $line->total_ht + $line->total_tva;
						$line->remise_percent = (float) $productsupplier->remise_percent;
						$line->ref_fourn = $productsupplier->ref_supplier;
						$line->type = $productsupplier->type;
						$line->fk_unit = $productsupplier->fk_unit;

						$suppliers[$productsupplier->fourn_socid]['lines'][] = $line;
					}
				} elseif ($idprod == -1) {
					$errorQty++;
				} else {
					$error = $db->lasterror();
					dol_print_error($db);
				}

				unset($_POST['fourn' . $i]);
			}
			unset($_POST[$i]);
		}

		//we now know how many orders we need and what lines they have
		$i = 0;
		$fail = 0;
		$orders = array();
		$suppliersid = array_keys($suppliers);	// array of ids of suppliers
		foreach ($suppliers as $supplier) {
			$order = new CommandeFournisseur($db);

			// Check if an order for the supplier exists
			$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "commande_fournisseur";
			$sql .= " WHERE fk_soc = " . ((int) $suppliersid[$i]);
			$sql .= " AND source = " . ((int) $order::SOURCE_ID_REPLENISHMENT) . " AND fk_statut = " . ((int) $order::STATUS_DRAFT);
			$sql .= " AND entity IN (" . getEntity('commande_fournisseur') . ")";
			$sql .= " ORDER BY date_creation DESC";
			$resql = $db->query($sql);
			if ($resql && $db->num_rows($resql) > 0) {
				$obj = $db->fetch_object($resql);

				$order->fetch($obj->rowid);
				$order->fetch_thirdparty();

				foreach ($supplier['lines'] as $line) {
					if (empty($line->remise_percent)) {
						$line->remise_percent = (float) $order->thirdparty->remise_supplier_percent;
					}
					$result = $order->addline(
						$line->desc,
						$line->subprice,
						$line->qty,
						$line->tva_tx,
						$line->localtax1_tx,
						$line->localtax2_tx,
						$line->fk_product,
						0,
						$line->ref_fourn,
						$line->remise_percent,
						'HT',
						0,
						$line->type,
						0,
						false,
						null,
						null,
						0,
						$line->fk_unit,
						$line->multicurrency_subprice ?? 0
					);
				}
				if ($result < 0) {
					$fail++;
					$msg = $langs->trans('OrderFail') . "&nbsp;:&nbsp;";
					$msg .= $order->error;
					setEventMessages($msg, null, 'errors');
				} else {
					$id = $result;
				}
				$i++;
			} else {
				$order->socid = $suppliersid[$i];
				$order->fetch_thirdparty();
				$order->multicurrency_code = $order->thirdparty->multicurrency_code;

				// Trick to know which orders have been generated using the replenishment feature
				$order->source = $order::SOURCE_ID_REPLENISHMENT;

				foreach ($supplier['lines'] as $line) {
					if (empty($line->remise_percent)) {
						$line->remise_percent = (float) $order->thirdparty->remise_supplier_percent;
					}
					$order->lines[] = $line;
				}
				$order->cond_reglement_id = (int) $order->thirdparty->cond_reglement_supplier_id;
				$order->mode_reglement_id = (int) $order->thirdparty->mode_reglement_supplier_id;

				$id = $order->create($user);
				if ($id < 0) {
					$fail++;
					$msg = $langs->trans('OrderFail') . "&nbsp;:&nbsp;";
					$msg .= $order->error;
					setEventMessages($msg, null, 'errors');
				}
				$i++;
			}
		}

		if ($errorQty) {
			setEventMessages($langs->trans('ErrorOrdersNotCreatedQtyTooLow'), null, 'warnings');
		}

		if (!$fail && $id) {
			$db->commit();

			setEventMessages($langs->trans('OrderCreated'), null, 'mesgs');
			header('Location: replenishorders.php');
			exit;
		} else {
			$db->rollback();
		}
	}
	if ($box == 0) {
		setEventMessages($langs->trans('SelectProductWithNotNullQty'), null, 'warnings');
	}
}


/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
$prod = new Product($db);

$title = $langs->trans('MissingStocks');

if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	$sqldesiredtock = $db->ifsql("pse.desiredstock IS NULL", "p.desiredstock", "pse.desiredstock");
	$sqlalertstock = $db->ifsql("pse.seuil_stock_alerte IS NULL", "p.seuil_stock_alerte", "pse.seuil_stock_alerte");
} else {
	$sqldesiredtock = 'p.desiredstock';
	$sqlalertstock = 'p.seuil_stock_alerte';
}

$sql = 'SELECT p.rowid, p.ref, p.label, p.description, p.price,';
$sql .= ' p.price_ttc, p.price_base_type, p.fk_product_type,';
$sql .= ' p.tms as datem, p.duration, p.tobuy,';
$sql .= ' p.desiredstock, p.seuil_stock_alerte,';
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	$sql .= ' pse.desiredstock as desiredstockpse, pse.seuil_stock_alerte as seuil_stock_alertepse,';
}
$sql .= " " . $sqldesiredtock . " as desiredstockcombined, " . $sqlalertstock . " as seuil_stock_alertecombined,";
$sql .= ' s.fk_product,';
$sql .= " SUM(".$db->ifsql("s.reel IS NULL", "0", "s.reel").') as stock_physique';
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	$sql .= ", SUM(".$db->ifsql("s.reel IS NULL OR s.fk_entrepot <> ".((int) $fk_entrepot), "0", "s.reel").') as stock_real_warehouse';
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$list_warehouse = (empty($listofqualifiedwarehousesid) ? '0' : $listofqualifiedwarehousesid);

$sql .= ' FROM ' . MAIN_DB_PREFIX . 'product as p';
$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'product_stock as s ON p.rowid = s.fk_product';
$sql .= ' AND s.fk_entrepot  IN (' . $db->sanitize($list_warehouse) . ')';

$list_warehouse_selected = ($fk_entrepot < 0 || empty($fk_entrepot)) ? $list_warehouse : $fk_entrepot;
$sql .= ' AND s.fk_entrepot  IN (' . $db->sanitize($list_warehouse_selected) . ')';


//$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'entrepot AS ent ON s.fk_entrepot = ent.rowid AND ent.entity IN('.getEntity('stock').')';
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'product_warehouse_properties AS pse ON (p.rowid = pse.fk_product AND pse.fk_entrepot = '.((int) $fk_entrepot).')';
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListJoin', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' WHERE p.entity IN (' . getEntity('product') . ')';
if ($sall) {
	$sql .= natural_search(array('p.ref', 'p.label', 'p.description', 'p.note'), $sall);
}
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen((string) $type)) {
	if ($type == 1) {
		$sql .= ' AND p.fk_product_type = 1';
	} else {
		$sql .= ' AND p.fk_product_type <> 1';
	}
}
if ($search_ref) {
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_label) {
	$sql .= natural_search('p.label', $search_label);
}
$sql .= ' AND p.tobuy = 1';
if (isModEnabled('variants') && !getDolGlobalString('VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT')) {	// Add test to exclude products that has variants
	$sql .= ' AND p.rowid NOT IN (SELECT pac.fk_product_parent FROM '.MAIN_DB_PREFIX.'product_attribute_combination as pac WHERE pac.entity IN ('.getEntity('product').'))';
}
if ($fk_supplier > 0) {
	$sql .= ' AND EXISTS (SELECT pfp.rowid FROM ' . MAIN_DB_PREFIX . 'product_fournisseur_price as pfp WHERE pfp.fk_product = p.rowid AND pfp.fk_soc = ' . ((int) $fk_supplier) . ' AND pfp.entity IN (' . getEntity('product_fournisseur_price') . '))';
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' GROUP BY p.rowid, p.ref, p.label, p.description, p.price';
$sql .= ', p.price_ttc, p.price_base_type,p.fk_product_type, p.tms';
$sql .= ', p.duration, p.tobuy';
$sql .= ', p.desiredstock';
$sql .= ', p.seuil_stock_alerte';
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	$sql .= ', pse.desiredstock';
	$sql .= ', pse.seuil_stock_alerte';
}
$sql .= ', s.fk_product';

if ($usevirtualstock) {
	if (isModEnabled('order')) {
		$sqlCommandesCli = "(SELECT ".$db->ifsql("SUM(cd1.qty) IS NULL", "0", "SUM(cd1.qty)")." as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlCommandesCli .= " FROM ".MAIN_DB_PREFIX."commandedet as cd1, ".MAIN_DB_PREFIX."commande as c1";
		$sqlCommandesCli .= " WHERE c1.rowid = cd1.fk_commande AND c1.entity IN (".getEntity(getDolGlobalString('STOCK_CALCULATE_VIRTUAL_STOCK_TRANSVERSE_MODE') ? 'stock' : 'commande').")";
		$sqlCommandesCli .= " AND cd1.fk_product = p.rowid";
		$sqlCommandesCli .= " AND c1.fk_statut IN (1,2))";
	} else {
		$sqlCommandesCli = '0';
	}

	if (isModEnabled("shipping")) {
		$sqlExpeditionsCli = "(SELECT ".$db->ifsql("SUM(ed2.qty) IS NULL", "0", "SUM(ed2.qty)")." as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlExpeditionsCli .= " FROM ".MAIN_DB_PREFIX."expedition as e2,";
		$sqlExpeditionsCli .= " ".MAIN_DB_PREFIX."expeditiondet as ed2,";
		$sqlExpeditionsCli .= " ".MAIN_DB_PREFIX."commande as c2,";
		$sqlExpeditionsCli .= " ".MAIN_DB_PREFIX."commandedet as cd2";
		$sqlExpeditionsCli .= " WHERE ed2.fk_expedition = e2.rowid AND cd2.rowid = ed2.fk_elementdet AND e2.entity IN (".getEntity(getDolGlobalString('STOCK_CALCULATE_VIRTUAL_STOCK_TRANSVERSE_MODE') ? 'stock' : 'expedition').")";
		$sqlExpeditionsCli .= " AND cd2.fk_commande = c2.rowid";
		$sqlExpeditionsCli .= " AND c2.fk_statut IN (1,2)";
		$sqlExpeditionsCli .= " AND cd2.fk_product = p.rowid";
		$sqlExpeditionsCli .= " AND e2.fk_statut IN (1,2))";
	} else {
		$sqlExpeditionsCli = '0';
	}

	if (isModEnabled("supplier_order")) {
		$sqlCommandesFourn = "(SELECT " . $db->ifsql("SUM(cd3.qty) IS NULL", "0", "SUM(cd3.qty)") . " as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlCommandesFourn .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as cd3,";
		$sqlCommandesFourn .= " " . MAIN_DB_PREFIX . "commande_fournisseur as c3";
		$sqlCommandesFourn .= " WHERE c3.rowid = cd3.fk_commande";
		$sqlCommandesFourn .= " AND c3.entity IN (".getEntity(getDolGlobalString('STOCK_CALCULATE_VIRTUAL_STOCK_TRANSVERSE_MODE') ? 'stock' : 'supplier_order').")";
		$sqlCommandesFourn .= " AND cd3.fk_product = p.rowid";
		$sqlCommandesFourn .= " AND c3.fk_statut IN (3,4))";

		$sqlReceptionFourn = "(SELECT ".$db->ifsql("SUM(fd4.qty) IS NULL", "0", "SUM(fd4.qty)")." as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlReceptionFourn .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as cf4,";
		$sqlReceptionFourn .= " ".MAIN_DB_PREFIX."receptiondet_batch as fd4";
		$sqlReceptionFourn .= " WHERE fd4.fk_element = cf4.rowid AND cf4.entity IN (".getEntity(getDolGlobalString('STOCK_CALCULATE_VIRTUAL_STOCK_TRANSVERSE_MODE') ? 'stock' : 'supplier_order').")";
		$sqlReceptionFourn .= " AND fd4.fk_product = p.rowid";
		$sqlReceptionFourn .= " AND cf4.fk_statut IN (3,4))";
	} else {
		$sqlCommandesFourn = '0';
		$sqlReceptionFourn = '0';
	}

	if (isModEnabled('mrp')) {
		$sqlProductionToConsume = "(SELECT GREATEST(0, ".$db->ifsql("SUM(".$db->ifsql("mp5.role = 'toconsume'", 'mp5.qty', '- mp5.qty').") IS NULL", "0", "SUM(".$db->ifsql("mp5.role = 'toconsume'", 'mp5.qty', '- mp5.qty').")").") as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlProductionToConsume .= " FROM ".MAIN_DB_PREFIX."mrp_mo as mm5,";
		$sqlProductionToConsume .= " ".MAIN_DB_PREFIX."mrp_production as mp5";
		$sqlProductionToConsume .= " WHERE mm5.rowid = mp5.fk_mo AND mm5.entity IN (".getEntity(getDolGlobalString('STOCK_CALCULATE_VIRTUAL_STOCK_TRANSVERSE_MODE') ? 'stock' : 'mo').")";
		$sqlProductionToConsume .= " AND mp5.fk_product = p.rowid";
		$sqlProductionToConsume .= " AND mp5.role IN ('toconsume', 'consumed')";
		$sqlProductionToConsume .= " AND mm5.status IN (1,2))";

		$sqlProductionToProduce = "(SELECT GREATEST(0, ".$db->ifsql("SUM(".$db->ifsql("mp5.role = 'toproduce'", 'mp5.qty', '- mp5.qty').") IS NULL", "0", "SUM(".$db->ifsql("mp5.role = 'toproduce'", 'mp5.qty', '- mp5.qty').")").") as qty"; // We need the ifsql because if result is 0 for product p.rowid, we must return 0 and not NULL
		$sqlProductionToProduce .= " FROM ".MAIN_DB_PREFIX."mrp_mo as mm5,";
		$sqlProductionToProduce .= " ".MAIN_DB_PREFIX."mrp_production as mp5";
		$sqlProductionToProduce .= " WHERE mm5.rowid = mp5.fk_mo AND mm5.entity IN (".getEntity(getDolGlobalString('STOCK_CALCULATE_VIRTUAL_STOCK_TRANSVERSE_MODE') ? 'stock' : 'mo').")";
		$sqlProductionToProduce .= " AND mp5.fk_product = p.rowid";
		$sqlProductionToProduce .= " AND mp5.role IN ('toproduce', 'produced')";
		$sqlProductionToProduce .= " AND mm5.status IN (1,2))";
	} else {
		$sqlProductionToConsume = '0';
		$sqlProductionToProduce = '0';
	}

	$sql .= ' HAVING (';
	$sql .= " (" . $sqldesiredtock . " >= 0 AND (" . $sqldesiredtock . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ')';
	$sql .= " - (" . $sqlCommandesCli . " - " . $sqlExpeditionsCli . ") + (" . $sqlCommandesFourn . " - " . $sqlReceptionFourn . ") + (" . $sqlProductionToProduce . " - " . $sqlProductionToConsume . ")))";
	$sql .= ' OR';
	if ($includeproductswithoutdesiredqty == 'on') {
		$sql .= " ((" . $sqlalertstock . " >= 0 OR " . $sqlalertstock . " IS NULL) AND (" . $db->ifsql($sqlalertstock . " IS NULL", "0", $sqlalertstock) . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ")";
	} else {
		$sql .= " (" . $sqlalertstock . " >= 0 AND (" . $sqlalertstock . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ')';
	}
	$sql .= " - (" . $sqlCommandesCli . " - " . $sqlExpeditionsCli . ") + (" . $sqlCommandesFourn . " - " . $sqlReceptionFourn . ") + (" . $sqlProductionToProduce . " - " . $sqlProductionToConsume . ")))";
	$sql .= ")";
	if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
		$sql .= " AND (";
		$sql .= " pse.desiredstock > 0)";
	}

	if ($salert == 'on') {    // Option to see when stock is lower than alert
		$sql .= ' AND (';
		if ($includeproductswithoutdesiredqty == 'on') {
			$sql .= "(" . $sqlalertstock . " >= 0 OR " . $sqlalertstock . " IS NULL) AND (" . $db->ifsql($sqlalertstock . " IS NULL", "0", $sqlalertstock) . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ")";
		} else {
			$sql .= $sqlalertstock . " >= 0 AND (" . $sqlalertstock . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ")";
		}
		$sql .= " - (" . $sqlCommandesCli . " - " . $sqlExpeditionsCli . ") + (" . $sqlCommandesFourn . " - " . $sqlReceptionFourn . ")  + (" . $sqlProductionToProduce . " - " . $sqlProductionToConsume . "))";
		$sql .= ")";
		$alertchecked = 'checked';
	}
} else {
	$sql .= ' HAVING (';
	$sql .= "(" . $sqldesiredtock . " >= 0 AND (" . $sqldesiredtock . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ")))";
	$sql .= ' OR';
	if ($includeproductswithoutdesiredqty == 'on') {
		$sql .= " ((" . $sqlalertstock . " >= 0 OR " . $sqlalertstock . " IS NULL) AND (" . $db->ifsql($sqlalertstock . " IS NULL", "0", $sqlalertstock) . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ')))';
	} else {
		$sql .= " (" . $sqlalertstock . " >= 0 AND (" . $sqlalertstock . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . ')))';
	}
	$sql .= ')';
	if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
		$sql .= " AND (";
		$sql .= " pse.desiredstock > 0)";
	}

	if ($salert == 'on') {    // Option to see when stock is lower than alert
		$sql .= " AND (";
		if ($includeproductswithoutdesiredqty == 'on') {
			$sql .= " (" . $sqlalertstock . " >= 0 OR " . $sqlalertstock . " IS NULL) AND (" . $db->ifsql($sqlalertstock . " IS NULL", "0", $sqlalertstock) . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . "))";
		} else {
			$sql .= " " . $sqlalertstock . " >= 0 AND (" . $sqlalertstock . " > SUM(" . $db->ifsql("s.reel IS NULL", "0", "s.reel") . '))';
		}
		$sql .= ')';
		$alertchecked = 'checked';
	}
}

$includeproductswithoutdesiredqtychecked = '';
if ($includeproductswithoutdesiredqty == 'on') {
	$includeproductswithoutdesiredqtychecked = 'checked';
}

$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

//print $sql;
$resql = $db->query($sql);
if (empty($resql)) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);
$i = 0;

$helpurl = 'EN:Module_Stocks_En|FR:Module_Stock|';
$helpurl .= 'ES:M&oacute;dulo_Stocks';

llxHeader('', $title, $helpurl, '', 0, 0, '', '', '', 'mod-product page-stock_replenish');

$head = array();

$head[0][0] = DOL_URL_ROOT . '/product/stock/replenish.php';
$head[0][1] = $title;
$head[0][2] = 'replenish';

$head[1][0] = DOL_URL_ROOT . '/product/stock/replenishorders.php';
$head[1][1] = $langs->trans("ReplenishmentOrders");
$head[1][2] = 'replenishorders';


print load_fiche_titre($langs->trans('Replenishment'), '', 'stock');

print dol_get_fiche_head($head, 'replenish', '', -1, '');

print '<span class="opacitymedium">' . $langs->trans("ReplenishmentStatusDesc") . '</span>' . "\n";

//$link = '<a title=' .$langs->trans("MenuNewWarehouse"). ' href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create">'.$langs->trans("MenuNewWarehouse").'</a>';

if (empty($fk_entrepot) && getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE')) {
	print '<span class="opacitymedium">'.$langs->trans("ReplenishmentStatusDescPerWarehouse").'</span>'."\n";
}
print '<br><br>';
if ($usevirtualstock == 1) {
	print $langs->trans("CurentSelectionMode") . ': ';
	print '<span class="a-mesure">' . $langs->trans("UseVirtualStock") . '</span>';
	print ' <a class="a-mesure-disabled" href="' . $_SERVER["PHP_SELF"] . '?mode=physical' . ($fk_supplier > 0 ? '&fk_supplier=' . $fk_supplier : '') . ($fk_entrepot > 0 ? '&fk_entrepot=' . $fk_entrepot : '') . '">' . $langs->trans("UsePhysicalStock") . '</a>';
	print '<br>';
}
if ($usevirtualstock == 0) {
	print $langs->trans("CurentSelectionMode") . ': ';
	print '<a class="a-mesure-disabled" href="' . $_SERVER["PHP_SELF"] . '?mode=virtual' . ($fk_supplier > 0 ? '&fk_supplier=' . $fk_supplier : '') . ($fk_entrepot > 0 ? '&fk_entrepot=' . $fk_entrepot : '') . '">' . $langs->trans("UseVirtualStock") . '</a>';
	print ' <span class="a-mesure">' . $langs->trans("UsePhysicalStock") . '</span>';
	print '<br>';
}
print '<br>' . "\n";

print '<form name="formFilterWarehouse" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="filter">';
print '<input type="hidden" name="search_ref" value="' . $search_ref . '">';
print '<input type="hidden" name="search_label" value="' . $search_label . '">';
print '<input type="hidden" name="salert" value="' . $salert . '">';
print '<input type="hidden" name="includeproductswithoutdesiredqty" value="' . $includeproductswithoutdesiredqty . '">';
print '<input type="hidden" name="draftorder" value="' . $draftorder . '">';
print '<input type="hidden" name="mode" value="' . $mode . '">';
if ($limit > 0 && $limit != $conf->liste_limit) {
	print '<input type="hidden" name="limit" value="' . $limit . '">';
}
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE')) {
	print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
	print $langs->trans('Warehouse') . ' ' . $formproduct->selectWarehouses($fk_entrepot, 'fk_entrepot', '', 1);
	print '</div>';
}
print '<div class="inline-block valignmiddle" style="padding-right: 20px;">';
$filter = '(fournisseur:=:1)';
print $langs->trans('Supplier') . ' ' . $form->select_company($fk_supplier, 'fk_supplier', $filter, 1);
print '</div>';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	print $hookmanager->resPrint;
}

print '<div class="inline-block valignmiddle">';
print '<input type="submit" class="button smallpaddingimp" name="valid" value="' . $langs->trans('ToFilter') . '">';
print '</div>';

print '</form>';

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST" name="formulaire">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="fk_supplier" value="' . $fk_supplier . '">';
print '<input type="hidden" name="fk_entrepot" value="' . $fk_entrepot . '">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="type" value="' . $type . '">';
print '<input type="hidden" name="linecount" value="' . $num . '">';
print '<input type="hidden" name="action" value="order">';
print '<input type="hidden" name="mode" value="' . $mode . '">';


if ($search_ref || $search_label || $sall || $salert || $draftorder || GETPOST('search', 'alpha')) {
	$filters = '&search_ref=' . urlencode($search_ref) . '&search_label=' . urlencode($search_label);
	$filters .= '&sall=' . urlencode($sall);
	$filters .= '&salert=' . urlencode($salert);
	$filters .= '&draftorder=' . urlencode($draftorder);
	$filters .= '&mode=' . urlencode($mode);
	if ($fk_supplier > 0) {
		$filters .= '&fk_supplier='.urlencode((string) ($fk_supplier));
	}
	if ($fk_entrepot > 0) {
		$filters .= '&fk_entrepot='.urlencode((string) ($fk_entrepot));
	}
} else {
	$filters = '&search_ref='.urlencode($search_ref).'&search_label='.urlencode($search_label);
	$filters .= '&fourn_id='.urlencode((string) ($fourn_id));
	$filters .= (isset($type) ? '&type='.urlencode((string) ($type)) : '');
	$filters .= '&salert='.urlencode($salert);
	$filters .= '&draftorder='.urlencode($draftorder);
	$filters .= '&mode='.urlencode($mode);
	if ($fk_supplier > 0) {
		$filters .= '&fk_supplier='.urlencode((string) ($fk_supplier));
	}
	if ($fk_entrepot > 0) {
		$filters .= '&fk_entrepot='.urlencode((string) ($fk_entrepot));
	}
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$filters .= '&limit=' . ((int) $limit);
}
if (!empty($includeproductswithoutdesiredqty)) {
	$filters .= '&includeproductswithoutdesiredqty='.urlencode($includeproductswithoutdesiredqty);
}
if (!empty($salert)) {
	$filters .= '&salert='.urlencode($salert);
}

$param = (isset($type) ? '&type='.urlencode((string) ($type)) : '');
$param .= '&fourn_id='.urlencode((string) ($fourn_id)).'&search_label='.urlencode((string) ($search_label)).'&includeproductswithoutdesiredqty='.urlencode((string) ($includeproductswithoutdesiredqty)).'&salert='.urlencode((string) ($salert)).'&draftorder='.urlencode((string) ($draftorder));
$param .= '&search_ref='.urlencode($search_ref);
$param .= '&mode='.urlencode($mode);
$param .= '&fk_supplier='.urlencode((string) ($fk_supplier));
$param .= '&fk_entrepot='.urlencode((string) ($fk_entrepot));
if (!empty($includeproductswithoutdesiredqty)) {
	$param .= '&includeproductswithoutdesiredqty='.urlencode($includeproductswithoutdesiredqty);
}
if (!empty($salert)) {
	$param .= '&salert='.urlencode($salert);
}

$stocklabel = $langs->trans('Stock');
$stocklabelbis = $langs->trans('Stock');
$stocktooltip = '';
if ($usevirtualstock == 1) {
	$stocklabel = $langs->trans('VirtualStock');
	$stocktooltip = $langs->trans("VirtualStockDesc");
}
if ($usevirtualstock == 0) {
	$stocklabel = $langs->trans('PhysicalStock');
}
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	$stocklabelbis = $stocklabel.' (Selected warehouse)';
	$stocklabel .= ' ('.$langs->trans("AllWarehouses").')';
}
$texte = $langs->trans('Replenishment');

print '<br>';


if (getDolGlobalString('REPLENISH_ALLOW_VARIABLESIZELIST')) {
	print_barre_liste(
		$texte,
		$page,
		'replenish.php',
		$filters,
		$sortfield,
		$sortorder,
		'',
		$num,
		$nbtotalofrecords,
		'',
		0,
		'',
		'',
		$limit
	);
} else {
	print_barre_liste(
		$texte,
		$page,
		'replenish.php',
		$filters,
		$sortfield,
		$sortorder,
		'',
		$num,
		$nbtotalofrecords,
		''
	);
}


print '<div class="div-table-responsive-no-min">';
print '<table class="liste centpercent">';

// Fields title search
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre"><input class="flat" type="text" name="search_ref" size="8" value="' . dol_escape_htmltag($search_ref) . '"></td>';
print '<td class="liste_titre"><input class="flat" type="text" name="search_label" size="8" value="' . dol_escape_htmltag($search_label) . '"></td>';
if (isModEnabled("service") && $type == 1) {
	print '<td class="liste_titre">&nbsp;</td>';
}
print '<td class="liste_titre right">' . $form->textwithpicto($langs->trans('IncludeEmptyDesiredStock'), $langs->trans('IncludeProductWithUndefinedAlerts')) . '&nbsp;<input type="checkbox" id="includeproductswithoutdesiredqty" name="includeproductswithoutdesiredqty" ' . (!empty($includeproductswithoutdesiredqtychecked) ? $includeproductswithoutdesiredqtychecked : '') . '></td>';
print '<td class="liste_titre right"></td>';
print '<td class="liste_titre right">'.$langs->trans('AlertOnly').'&nbsp;<input type="checkbox" id="salert" name="salert" '.(!empty($alertchecked) ? $alertchecked : '').'></td>';
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	print '<td class="liste_titre">&nbsp;</td>';
}
print '<td class="liste_titre right">';
if (getDolGlobalString('STOCK_REPLENISH_ADD_CHECKBOX_INCLUDE_DRAFT_ORDER')) {
	print $langs->trans('IncludeAlsoDraftOrders').'&nbsp;<input type="checkbox" id="draftorder" name="draftorder" '.(!empty($draftchecked) ? $draftchecked : '').'>';
}
print '</td>';
print '<td class="liste_titre">&nbsp;</td>';
// Fields from hook
$parameters = array('param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '<td class="liste_titre maxwidthsearch right">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print '</tr>';

// Lines of title
print '<tr class="liste_titre">';
print_liste_field_titre('<input type="checkbox" onClick="toggle(this)" />', $_SERVER["PHP_SELF"], '');
print_liste_field_titre('ProductRef', $_SERVER["PHP_SELF"], 'p.ref', $param, '', '', $sortfield, $sortorder);
print_liste_field_titre('Label', $_SERVER["PHP_SELF"], 'p.label', $param, '', '', $sortfield, $sortorder);
if (isModEnabled("service") && $type == 1) {
	print_liste_field_titre('Duration', $_SERVER["PHP_SELF"], 'p.duration', $param, '', '', $sortfield, $sortorder, 'center ');
}
print_liste_field_titre('DesiredStock', $_SERVER["PHP_SELF"], 'p.desiredstock', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('StockLimitShort', $_SERVER["PHP_SELF"], 'p.seuil_stock_alerte', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($stocklabel, $_SERVER["PHP_SELF"], 'stock_physique', $param, '', '', $sortfield, $sortorder, 'right ', $stocktooltip);
if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
	print_liste_field_titre($stocklabelbis, $_SERVER["PHP_SELF"], 'stock_real_warehouse', $param, '', '', $sortfield, $sortorder, 'right ');
}
print_liste_field_titre('Ordered', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('StockToBuy', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');
print_liste_field_titre('SupplierRef', $_SERVER["PHP_SELF"], '', $param, '', '', $sortfield, $sortorder, 'right ');

// Hook fields
$parameters = array('param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</tr>\n";

while ($i < ($limit ? min($num, $limit) : $num)) {
	$objp = $db->fetch_object($resql);

	if (getDolGlobalString('STOCK_SUPPORTS_SERVICES') || $objp->fk_product_type == 0) {
		$result = $prod->fetch($objp->rowid);
		if ($result < 0) {
			dol_print_error($db);
			exit;
		}

		$prod->load_stock('warehouseopen, warehouseinternal'.(!$usevirtualstock ? ', novirtual' : ''), $draftchecked);

		// Multilangs
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			$sql = 'SELECT label,description';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'product_lang';
			$sql .= ' WHERE fk_product = ' . ((int) $objp->rowid);
			$sql .= " AND lang = '" . $db->escape($langs->getDefaultLang()) . "'";
			$sql .= ' LIMIT 1';

			$resqlm = $db->query($sql);
			if ($resqlm) {
				$objtp = $db->fetch_object($resqlm);
				if (!empty($objtp->description)) {
					$objp->description = $objtp->description;
				}
				if (!empty($objtp->label)) {
					$objp->label = $objtp->label;
				}
			}
		}

		$stockwarehouse = 0;
		if ($usevirtualstock) {
			// If option to increase/decrease is not on an object validation, virtual stock may differs from physical stock.
			$stock = $prod->stock_theorique;
			//if conf active, stock virtual by warehouse is calculated
			if (getDolGlobalString('STOCK_ALLOW_VIRTUAL_STOCK_PER_WAREHOUSE')) {
				$stockwarehouse = $prod->stock_warehouse[$fk_entrepot]->virtual;
			}
		} else {
			$stock = $prod->stock_reel;
			if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
				$stockwarehouse = $prod->stock_warehouse[$fk_entrepot]->real;
			}
		}

		// Force call prod->load_stats_xxx to choose status to count (otherwise it is loaded by load_stock function)
		if (isset($draftchecked)) {
			$result = $prod->load_stats_commande_fournisseur(0, '0,1,2,3,4');
		} elseif (!$usevirtualstock) {
			$result = $prod->load_stats_commande_fournisseur(0, '1,2,3,4');
		}

		if (!$usevirtualstock) {
			$result = $prod->load_stats_reception(0, '4');
		}

		//print $prod->stats_commande_fournisseur['qty'].'<br>'."\n";
		//print $prod->stats_reception['qty'];
		$ordered = $prod->stats_commande_fournisseur['qty'] - $prod->stats_reception['qty'];

		$desiredstock = $objp->desiredstock;
		$alertstock = $objp->seuil_stock_alerte;
		$desiredstockwarehouse = (!empty($objp->desiredstockpse) ? $objp->desiredstockpse : 0);
		$alertstockwarehouse = (!empty($objp->seuil_stock_alertepse) ? $objp->seuil_stock_alertepse : 0);

		$warning = '';
		if ($alertstock && ($stock < $alertstock)) {
			$warning = img_warning($langs->trans('StockTooLow')) . ' ';
		}
		$warningwarehouse = '';
		if ($alertstockwarehouse && ($stockwarehouse < $alertstockwarehouse)) {
			$warningwarehouse = img_warning($langs->trans('StockTooLow')) . ' ';
		}

		//depending on conf, use either physical stock or
		//virtual stock to compute the stock to buy value

		if (empty($usevirtualstock)) {
			$stocktobuy = max(max($desiredstock, $alertstock) - $stock - $ordered, 0);
		} else {
			$stocktobuy = max(max($desiredstock, $alertstock) - $stock, 0); //ordered is already in $stock in virtual mode
		}
		if (empty($usevirtualstock)) {
			$stocktobuywarehouse = max(max($desiredstockwarehouse, $alertstockwarehouse) - $stockwarehouse - $ordered, 0);
		} else {
			$stocktobuywarehouse = max(max($desiredstockwarehouse, $alertstockwarehouse) - $stockwarehouse, 0); //ordered is already in $stock in virtual mode
		}

		$picto = '';
		if ($ordered > 0) {
			$stockforcompare = ($usevirtualstock ? $stock : $stock + $ordered);
			/*if ($stockforcompare >= $desiredstock)
			{
			$picto = img_picto('', 'help');
			} else {
			$picto = img_picto('', 'help');
			}*/
		} else {
			$picto = img_picto($langs->trans("NoPendingReceptionOnSupplierOrder"), 'help');
		}

		print '<tr class="oddeven">';

		// Select field
		print '<td><input type="checkbox" class="check" name="choose' . $i . '"></td>';

		print '<td class="nowrap">' . $prod->getNomUrl(1, 'stock') . '</td>';

		print '<td class="tdoverflowmax200" title="' . dol_escape_htmltag($objp->label) . '">';
		print dol_escape_htmltag($objp->label);
		print '<input type="hidden" name="desc' . $i . '" value="' . dol_escape_htmltag($objp->description) . '">'; // TODO Remove this and make a fetch to get description when creating order instead of a GETPOST
		print '</td>';

		if (isModEnabled("service") && $type == 1) {
			$regs = array();
			if (preg_match('/([0-9]+)y/i', $objp->duration, $regs)) {
				$duration = $regs[1] . ' ' . $langs->trans('DurationYear');
			} elseif (preg_match('/([0-9]+)m/i', $objp->duration, $regs)) {
				$duration = $regs[1] . ' ' . $langs->trans('DurationMonth');
			} elseif (preg_match('/([0-9]+)d/i', $objp->duration, $regs)) {
				$duration = $regs[1] . ' ' . $langs->trans('DurationDay');
			} else {
				$duration = $objp->duration;
			}
			print '<td class="center">' . $duration . '</td>';
		}

		// Desired stock
		print '<td class="right">'.((getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) > 0 ? ($objp->desiredstockpse ? $desiredstockwarehouse : img_info($langs->trans('ProductValuesUsedBecauseNoValuesForThisWarehouse')) . '0') : $desiredstock).'</td>';

		// Limit stock for alert
		print '<td class="right">'.((getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) > 0 ? ($objp->seuil_stock_alertepse ? $alertstockwarehouse : img_info($langs->trans('ProductValuesUsedBecauseNoValuesForThisWarehouse')) . '0') : $alertstock).'</td>';

		// Current stock (all warehouses)
		print '<td class="right">' . $warning . $stock;
		print '<!-- stock returned by main sql is ' . $objp->stock_physique . ' -->';
		print '</td>';

		// Current stock (warehouse selected only)
		if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
			print '<td class="right">'.$warningwarehouse.$stockwarehouse.'</td>';
		}

		// Already ordered
		print '<td class="right"><a href="replenishorders.php?search_product=' . $prod->id . '">' . $ordered . '</a> ' . $picto . '</td>';

		// To order
		$tobuy = ((getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) > 0 ? $stocktobuywarehouse : $stocktobuy);
		print '<td class="right"><input type="text" size="4" name="tobuy'.$i.'" value="'.$tobuy.'"></td>';

		// Supplier
		print '<td class="right">';
		print $form->select_product_fourn_price($prod->id, 'fourn' . $i, $fk_supplier);
		print '</td>';

		// Fields from hook
		$parameters = array('objp' => $objp, 'i' => $i, 'tobuy' => $tobuy);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print '</tr>';
	}
	$i++;
}

if ($num == 0) {
	$colspan = 9;
	if (isModEnabled("service") && $type == 1) {
		$colspan++;
	}
	if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE') && $fk_entrepot > 0) {
		$colspan++;
	}
	print '<tr><td colspan="' . $colspan . '">';
	print '<span class="opacitymedium">';
	print $langs->trans("None");
	print '</span>';
	print '</td></tr>';
}

$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>';
print '</div>';

$db->free($resql);

print dol_get_fiche_end();


$value = $langs->trans("CreateOrders");
print '<div class="center"><input type="submit" class="button" name="valid" value="' . $value . '"></div>';


print '</form>';


// TODO Replace this with jquery
print '
<script type="text/javascript">
function toggle(source)
{
	checkboxes = document.getElementsByClassName("check");
	for (var i=0; i < checkboxes.length;i++) {
		if (!checkboxes[i].disabled) {
			checkboxes[i].checked = source.checked;
		}
	}
}
</script>';


llxFooter();

$db->close();
