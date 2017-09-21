<?php
/* Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/product/ajax/products.php
 * \brief File to return Ajax response on product list request
 */
if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))
	define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))
	define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))
	define('NOCSRFCHECK', '1');
if (empty($_GET ['keysearch']) && ! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');

require '../../main.inc.php';

$htmlname = GETPOST('htmlname', 'alpha');
$socid = GETPOST('socid', 'int');
$type = GETPOST('type', 'int');
$mode = GETPOST('mode', 'int');
$status = ((GETPOST('status', 'int') >= 0) ? GETPOST('status', 'int') : - 1);
$outjson = (GETPOST('outjson', 'int') ? GETPOST('outjson', 'int') : 0);
$price_level = GETPOST('price_level', 'int');
$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$price_by_qty_rowid = GETPOST('pbq', 'int');
$finished = GETPOST('finished', 'int');
$alsoproductwithnosupplierprice = GETPOST('alsoproductwithnosupplierprice', 'int');
$warehouseStatus = GETPOST('warehousestatus', 'alpha');
$hidepriceinlabel = GETPOST('hidepriceinlabel', 'int');


/*
 * View
 */

// print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

dol_syslog(join(',', $_GET));
// print_r($_GET);

if (! empty($action) && $action == 'fetch' && ! empty($id))
{
	require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

	$outjson = array();

	$object = new Product($db);
	$ret = $object->fetch($id);
	if ($ret > 0)
	{
		$outref = $object->ref;
		$outlabel = $object->label;
		$outdesc = $object->description;
		$outtype = $object->type;
		$outqty = 1;
		$outdiscount = 0;

		$found = false;

		// Price by qty
		if (! empty($price_by_qty_rowid) && $price_by_qty_rowid >= 1 && (! empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY))) 		// If we need a particular price related to qty
		{
			$sql = "SELECT price, unitprice, quantity, remise_percent";
			$sql .= " FROM " . MAIN_DB_PREFIX . "product_price_by_qty ";
			$sql .= " WHERE rowid=" . $price_by_qty_rowid . "";

			$result = $db->query($sql);
			if ($result) {
				$objp = $db->fetch_object($result);
				if ($objp) {
					$found = true;
					$outprice_ht = price($objp->unitprice);
					$outprice_ttc = price($objp->unitprice * (1 + ($object->tva_tx / 100)));
					$outpricebasetype = $object->price_base_type;
					$outtva_tx = $object->tva_tx;
					$outqty = $objp->quantity;
					$outdiscount = $objp->remise_percent;
				}
			}
		}

		// Multiprice
		if (! $found && isset($price_level) && $price_level >= 1 && (! empty($conf->global->PRODUIT_MULTIPRICES))) 		// If we need a particular price
		                                                                                                           // level (from 1 to 6)
		{
			$sql = "SELECT price, price_ttc, price_base_type, tva_tx";
			$sql .= " FROM " . MAIN_DB_PREFIX . "product_price ";
			$sql .= " WHERE fk_product='" . $id . "'";
			$sql .= " AND entity IN (" . getEntity('productprice') . ")";
			$sql .= " AND price_level=" . $price_level;
			$sql .= " ORDER BY date_price";
			$sql .= " DESC LIMIT 1";

			$result = $db->query($sql);
			if ($result) {
				$objp = $db->fetch_object($result);
				if ($objp) {
					$found = true;
					$outprice_ht = price($objp->price);
					$outprice_ttc = price($objp->price_ttc);
					$outpricebasetype = $objp->price_base_type;
					$outtva_tx = $objp->tva_tx;
				}
			}
		}

		// Price by customer
		if (! empty($conf->global->PRODUIT_CUSTOMER_PRICES) && ! empty($socid)) {

			require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

			$prodcustprice = new Productcustomerprice($db);

			$filter = array('t.fk_product' => $object->id,'t.fk_soc' => $socid);

			$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
			if ($result) {
				if (count($prodcustprice->lines) > 0) {
					$found = true;
					$outprice_ht = price($prodcustprice->lines [0]->price);
					$outprice_ttc = price($prodcustprice->lines [0]->price_ttc);
					$outpricebasetype = $prodcustprice->lines [0]->price_base_type;
					$outtva_tx = $prodcustprice->lines [0]->tva_tx;
				}
			}
		}

		if (! $found) {
			$outprice_ht = price($object->price);
			$outprice_ttc = price($object->price_ttc);
			$outpricebasetype = $object->price_base_type;
			$outtva_tx = $object->tva_tx;
		}

		$outjson = array('ref' => $outref,'label' => $outlabel,'desc' => $outdesc,'type' => $outtype,'price_ht' => $outprice_ht,'price_ttc' => $outprice_ttc,'pricebasetype' => $outpricebasetype,'tva_tx' => $outtva_tx,'qty' => $outqty,'discount' => $outdiscount);
	}

	echo json_encode($outjson);
}
else
{
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

	$langs->load("products");
	$langs->load("main");

	top_httphead();

	if (empty($htmlname))
	{
		print json_encode(array());
	    return;
	}

	$match = preg_grep('/(' . $htmlname . '[0-9]+)/', array_keys($_GET));
	sort($match);

	$idprod = (! empty($match[0]) ? $match[0] : '');

	if (GETPOST($htmlname,'alpha') == '' && (! $idprod || ! GETPOST($idprod,'alpha')))
	{
		print json_encode(array());
	    return;
	}

	// When used from jQuery, the search term is added as GET param "term".
	$searchkey = (($idprod && GETPOST($idprod,'alpha')) ? GETPOST($idprod,'alpha') :  (GETPOST($htmlname, 'alpha') ? GETPOST($htmlname, 'alpha') : ''));

	$form = new Form($db);
	if (empty($mode) || $mode == 1) {  // mode=1: customer
		$arrayresult = $form->select_produits_list("", $htmlname, $type, 0, $price_level, $searchkey, $status, $finished, $outjson, $socid, '1', 0, '', $hidepriceinlabel, $warehouseStatus);
	} elseif ($mode == 2) {            // mode=2: supplier
		$arrayresult = $form->select_produits_fournisseurs_list($socid, "", $htmlname, $type, "", $searchkey, $status, $outjson, 0, $alsoproductwithnosupplierprice);
	}

	$db->close();

	if ($outjson)
		print json_encode($arrayresult);
}

