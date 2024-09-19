<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020      Josep Lluís Amador   <joseplluis@lliuretic.cat>
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
 * \file 	htdocs/product/ajax/products.php
 * \brief 	File to return Ajax response on product list request, with default VAT rate.
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
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML')) {	// Keep $_GET here, GETPOST is not yet defined
	define('NOREQUIREHTML', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';

$htmlname = GETPOST('htmlname', 'aZ09');
$socid = GETPOSTINT('socid');
// type can be empty string or 0 or 1
$type = GETPOST('type', 'int');
$mode = GETPOSTINT('mode');
$status = ((GETPOSTINT('status') >= 0) ? GETPOSTINT('status') : - 1);	// status buy when mode = customer , status purchase when mode = supplier
$status_purchase = ((GETPOSTINT('status_purchase') >= 0) ? GETPOSTINT('status_purchase') : - 1);	// status purchase when mode = customer
$outjson = (GETPOSTINT('outjson') ? GETPOSTINT('outjson') : 0);
$price_level = GETPOSTINT('price_level');
$action = GETPOST('action', 'aZ09');
$id = GETPOSTINT('id');
$price_by_qty_rowid = GETPOSTINT('pbq');
$finished = GETPOSTINT('finished');
$alsoproductwithnosupplierprice = GETPOSTINT('alsoproductwithnosupplierprice');
$warehouseStatus = GETPOST('warehousestatus', 'alpha');
$hidepriceinlabel = GETPOSTINT('hidepriceinlabel');
$warehouseId = GETPOST('warehouseid', 'int');

// Security check
restrictedArea($user, 'produit|service|commande|propal|facture', 0, 'product&product');

/*
 * Actions
 */

// None


/*
 * View
 */

// print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

if ($action == 'fetch' && !empty($id)) {
	// action='fetch' is used to get product information on a product. So when action='fetch', id must be the product id.
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	top_httphead('application/json');

	$outjson = array();

	$object = new Product($db);
	$ret = $object->fetch($id);
	if ($ret > 0) {
		$outref = $object->ref;
		$outlabel = $object->label;
		$outlabel_trans = '';
		$outdesc = $object->description;
		$outdesc_trans = '';
		$outtype = $object->type;
		$outprice_ht = null;
		$outprice_ttc = null;
		$outpricebasetype = null;
		$outtva_tx_formated = 0;
		$outtva_tx = 0;
		$outdefault_vat_code = '';
		$outqty = 1;
		$outdiscount = 0;
		$mandatory_period = $object->mandatory_period;
		$found = false;

		$price_level = 1;
		if ($socid > 0) {
			$needchangeaccordingtothirdparty = 0;
			if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
				$needchangeaccordingtothirdparty = 1;
			}
			if (getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) {
				$needchangeaccordingtothirdparty = 1;
			}
			if ($needchangeaccordingtothirdparty) {
				$thirdpartytemp = new Societe($db);
				$thirdpartytemp->fetch($socid);

				//Load translation description and label according to thirdparty language
				if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
					$newlang = $thirdpartytemp->default_lang;

					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
						$outdesc_trans = (!empty($object->multilangs[$outputlangs->defaultlang]["description"])) ? $object->multilangs[$outputlangs->defaultlang]["description"] : $object->description;
						$outlabel_trans = (!empty($object->multilangs[$outputlangs->defaultlang]["label"])) ? $object->multilangs[$outputlangs->defaultlang]["label"] : $object->label;
					} else {
						$outdesc_trans = $object->description;
						$outlabel_trans = $object->label;
					}
				}

				//Set price level according to thirdparty
				if (getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES')) {
					$price_level = $thirdpartytemp->price_level;
				}
			}
		}

		// Price by qty
		if (!empty($price_by_qty_rowid) && $price_by_qty_rowid >= 1 && (getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES'))) { // If we need a particular price related to qty
			$sql = "SELECT price, unitprice, quantity, remise_percent";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_price_by_qty";
			$sql .= " WHERE rowid = ".((int) $price_by_qty_rowid);

			$result = $db->query($sql);
			if ($result) {
				$objp = $db->fetch_object($result);
				if ($objp) {
					$found = true;
					$outprice_ht = price($objp->unitprice);
					$outprice_ttc = price($objp->unitprice * (1 + ($object->tva_tx / 100)));

					$outpricebasetype = $object->price_base_type;
					$outtva_tx_formated = price($object->tva_tx);
					$outtva_tx = price2num($object->tva_tx);
					$outdefault_vat_code = $object->default_vat_code;

					$outqty = $objp->quantity;
					$outdiscount = $objp->remise_percent;
				}
			}
		}

		// Multiprice (1 price per level)
		if (!$found && isset($price_level) && $price_level >= 1 && (getDolGlobalString('PRODUIT_MULTIPRICES') || getDolGlobalString('PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES'))) { // If we need a particular price level (from 1 to 6)
			$sql = "SELECT price, price_ttc, price_base_type,";
			$sql .= " tva_tx, default_vat_code";	// Vat rate and code will be used if PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL is on.
			$sql .= " FROM ".MAIN_DB_PREFIX."product_price ";
			$sql .= " WHERE fk_product = ".((int) $id);
			$sql .= " AND entity IN (".getEntity('productprice').")";
			$sql .= " AND price_level = ".((int) $price_level);
			$sql .= " ORDER BY date_price";
			$sql .= " DESC LIMIT 1";

			$result = $db->query($sql);
			if ($result) {
				$objp = $db->fetch_object($result);
				if ($objp) {
					$found = true;
					$outprice_ht = price($objp->price);			// formatted for language user because is inserted into input field
					$outprice_ttc = price($objp->price_ttc);	// formatted for language user because is inserted into input field
					$outpricebasetype = $objp->price_base_type;
					if (getDolGlobalString('PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL')) {
						$outtva_tx_formated = price($objp->tva_tx);	// formatted for language user because is inserted into input field
						$outtva_tx = price2num($objp->tva_tx);		// international numeric
						$outdefault_vat_code = $objp->default_vat_code;
					} else {
						// The common and default behaviour.
						$outtva_tx_formated = price($object->tva_tx);
						$outtva_tx = price2num($object->tva_tx);
						$outdefault_vat_code = $object->default_vat_code;
					}
				}
			}
		}

		// Price by customer
		if (getDolGlobalString('PRODUIT_CUSTOMER_PRICES') && !empty($socid)) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/productcustomerprice.class.php';

			$prodcustprice = new ProductCustomerPrice($db);

			$filter = array('t.fk_product' => $object->id, 't.fk_soc' => $socid);

			$result = $prodcustprice->fetchAll('', '', 0, 0, $filter);
			if ($result) {
				if (count($prodcustprice->lines) > 0) {
					$found = true;
					$outprice_ht = price($prodcustprice->lines[0]->price);
					$outprice_ttc = price($prodcustprice->lines[0]->price_ttc);
					$outpricebasetype = $prodcustprice->lines[0]->price_base_type;
					$outtva_tx_formated = price($prodcustprice->lines[0]->tva_tx);
					$outtva_tx = price2num($prodcustprice->lines[0]->tva_tx);
					$outdefault_vat_code = $prodcustprice->lines[0]->default_vat_code;
				}
			}
		}

		if (!$found) {
			$outprice_ht = price($object->price);
			$outprice_ttc = price($object->price_ttc);
			$outpricebasetype = $object->price_base_type;
			$outtva_tx_formated = price($object->tva_tx);
			$outtva_tx = price2num($object->tva_tx);
			$outdefault_vat_code = $object->default_vat_code;
		}

		// VAT to use and default VAT for product are set to same value by default
		$product_outtva_tx_formated =  $outtva_tx_formated;
		$product_outtva_tx =  $outtva_tx;
		$product_outdefault_vat_code = $outdefault_vat_code;

		// If we ask the price according to buyer, we change it.
		if (GETPOSTINT('addalsovatforthirdpartyid')) {
			$thirdparty_buyer = new Societe($db);
			$thirdparty_buyer->fetch($socid);

			$tmpvatwithcode = get_default_tva($mysoc, $thirdparty_buyer, $id, 0);

			if (!is_numeric($tmpvatwithcode) || $tmpvatwithcode != -1) {
				$reg =array();
				if (preg_match('/(.+)\s\((.+)\)/', $tmpvatwithcode, $reg)) {
					$outtva_tx = price2num($reg[1]);
					$outtva_tx_formated = price($outtva_tx);
					$outdefault_vat_code = $reg[2];
				} else {
					$outtva_tx = price2num($tmpvatwithcode);
					$outtva_tx_formated = price($outtva_tx);
					$outdefault_vat_code = '';
				}
			}
		}

		$outjson = array(
			'ref' => $outref,
			'label' => $outlabel,
			'label_trans' => $outlabel_trans,
			'desc' => $outdesc,
			'desc_trans' => $outdesc_trans,
			'type' => $outtype,
			'price_ht' => $outprice_ht,
			'price_ttc' => $outprice_ttc,
			'pricebasetype' => $outpricebasetype,
			'product_tva_tx_formated' => $product_outtva_tx_formated,
			'product_tva_tx' => $product_outtva_tx,
			'product_default_vat_code' => $product_outdefault_vat_code,

			'tva_tx_formated' => $outtva_tx_formated,
			'tva_tx' => $outtva_tx,
			'default_vat_code' => $outdefault_vat_code,

			'qty' => $outqty,
			'discount' => $outdiscount,
			'mandatory_period' => $mandatory_period,
			'array_options'=>$object->array_options
		);
	}

	echo json_encode($outjson);
} else {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

	$langs->loadLangs(array("main", "products"));

	top_httphead();

	if (empty($htmlname)) {
		print json_encode(array());
		return;
	}

	// Filter on the product to search can be:
	// Into an array with key $htmlname123 (we take first one found). Which page use this ?
	// Into a var with name $htmlname can be 'prodid', 'productid', ...
	$match = preg_grep('/('.preg_quote($htmlname, '/').'[0-9]+)/', array_keys($_GET));
	sort($match);

	$idprod = (empty($match[0]) ? '' : $match[0]);		// Take first key found into GET array with matching $htmlname123

	if (GETPOST($htmlname, 'alpha') == '' && (!$idprod || !GETPOST($idprod, 'alpha'))) {
		print json_encode(array());
		return;
	}

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey = (($idprod && GETPOST($idprod, 'alpha')) ? GETPOST($idprod, 'alpha') : (GETPOST($htmlname, 'alpha') ? GETPOST($htmlname, 'alpha') : ''));

	if (!isset($form) || !is_object($form)) {
		$form = new Form($db);
	}

	if (empty($mode) || $mode == 1) {  // mode=1: customer
		$maxResults = getDolGlobalInt('PRODUCT_MAX_RESULTS_COMBO', 50);
		$arrayresult = $form->select_produits_list("", $htmlname, $type, $maxResults, $price_level, $searchkey, $status, $finished, $outjson, $socid, '1', 0, '', $hidepriceinlabel, $warehouseStatus, $status_purchase, $warehouseId);
	} elseif ($mode == 2) {            // mode=2: supplier
		$arrayresult = $form->select_produits_fournisseurs_list($socid, "", $htmlname, $type, "", $searchkey, $status, $outjson, 0, $alsoproductwithnosupplierprice);
	}

	$db->close();

	if ($outjson) {
		print json_encode($arrayresult);
	}
}
