<?php
/* Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2015      Francis Appels       <francis.appels@z-application.com>
 * Copyright (C) 2016      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       /htdocs/fourn/ajax/getSupplierPrices.php
 *	\brief      File to return an Ajax response to get list of possible prices for margin calculation
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

$idprod = GETPOST('idprod', 'int');

$prices = array();

// Load translation files required by the page
$langs->loadLangs(array("stocks", "margins", "products"));


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

if ($idprod > 0) {
	$producttmp = new ProductFournisseur($db);
	$producttmp->fetch($idprod);

	$sorttouse = 's.nom, pfp.quantity, pfp.price';
	if (GETPOST('bestpricefirst')) {
		$sorttouse = 'pfp.unitprice, s.nom, pfp.quantity, pfp.price';
	}

	$productSupplierArray = $producttmp->list_product_fournisseur_price($idprod, $sorttouse); // We list all price per supplier, and then firstly with the lower quantity. So we can choose first one with enough quantity into list.
	if (is_array($productSupplierArray)) {
		foreach ($productSupplierArray as $productSupplier) {
			$price = $productSupplier->fourn_price * (1 - $productSupplier->fourn_remise_percent / 100);
			$unitprice = $productSupplier->fourn_unitprice * (1 - $productSupplier->fourn_remise_percent / 100);

			$title = $productSupplier->fourn_name.' - '.$productSupplier->fourn_ref.' - ';

			if ($productSupplier->fourn_qty == 1) {
				$title .= price($price, 0, $langs, 0, 0, -1, $conf->currency)."/";
			}
			$title .= $productSupplier->fourn_qty.' '.($productSupplier->fourn_qty == 1 ? $langs->trans("Unit") : $langs->trans("Units"));

			if ($productSupplier->fourn_qty > 1) {
				$title .= " - ";
				$title .= price($unitprice, 0, $langs, 0, 0, -1, $conf->currency)."/".$langs->trans("Unit");
				$price = $unitprice;
			}

			$label = price($price, 0, $langs, 0, 0, -1, $conf->currency)."/".$langs->trans("Unit");
			if ($productSupplier->fourn_ref) {
				$label .= ' ('.$productSupplier->fourn_ref.')';
			}

			$prices[] = array("id" => $productSupplier->product_fourn_price_id, "price" => price2num($price, 0, '', 0), "label" => $label, "title" => $title); // For price field, we must use price2num(), for label or title, price()
		}
	}

	// After best supplier prices and before costprice
	if (!empty($conf->stock->enabled)) {
		// Add price for pmp
		$price = $producttmp->pmp;
		if (empty($price) && !empty($conf->global->PRODUCT_USE_SUB_COST_PRICES_IF_COST_PRICE_EMPTY)) {
			// get pmp for subproducts if any
			$producttmp->get_sousproduits_arbo();
			$prods_arbo=$producttmp->get_arbo_each_prod();
			if (!empty($prods_arbo)) {
				$price = 0;
				foreach ($prods_arbo as $child) {
					$sousprod = new Product($db);
					$sousprod->fetch($child['id']);
					$price += $sousprod->pmp;
				}
			}
		}

		$prices[] = array("id" => 'pmpprice', "price" => price2num($price), "label" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency));  // For price field, we must use price2num(), for label or title, price()
	}

	// Add price for costprice (at end)
	$price = $producttmp->cost_price;
	if (empty($price) && ! empty($conf->global->PRODUCT_USE_SUB_COST_PRICES_IF_COST_PRICE_EMPTY)) {
		// get costprice for subproducts if any
		$producttmp->get_sousproduits_arbo();
		$prods_arbo=$producttmp->get_arbo_each_prod();
		if (!empty($prods_arbo)) {
			$price = 0;
			foreach ($prods_arbo as $child) {
				$sousprod = new Product($db);
				$sousprod->fetch($child['id']);
				$price += $sousprod->cost_price;
			}
		}
	}

	$prices[] = array("id" => 'costprice', "price" => price2num($price), "label" => $langs->trans("CostPrice").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency), "title" => $langs->trans("PMPValueShort").': '.price($price, 0, $langs, 0, 0, -1, $conf->currency)); // For price field, we must use price2num(), for label or title, price()
}

echo json_encode($prices);
