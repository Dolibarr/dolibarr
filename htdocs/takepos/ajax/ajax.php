<?php
/* Copyright (C) 2001-2004	Andreu Bisquerra	<jove@bisquerra.com>
/* Copyright (C) 2020		Thibault FOUCART	<support@ptibogxiv.net>
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

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN','1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');

require '../../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

$category = GETPOST('category', 'alpha');
$action = GETPOST('action', 'aZ09');
$term = GETPOST('term', 'alpha');
$id = GETPOST('id', 'int');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}


/*
 * View
 */

if ($action == 'getProducts') {
	$object = new Categorie($db);
	if ($category == "supplements") $category = $conf->global->TAKEPOS_SUPPLEMENTS_CATEGORY;
	$result = $object->fetch($category);
	if ($result > 0)
	{
		$prods = $object->getObjectsInCateg("product", 0, 0, 0, $conf->global->TAKEPOS_SORTPRODUCTFIELD, 'ASC');
		// Removed properties we don't need
		if (is_array($prods) && count($prods) > 0)
		{
			foreach ($prods as $prod)
			{
				unset($prod->fields);
				unset($prod->db);
			}
		}
		echo json_encode($prods);
	} else {
		echo 'Failed to load category with id='.$category;
	}
} elseif ($action == 'search' && $term != '') {
	// Change thirdparty with barcode
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$thirdparty = new Societe($db);
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

	// Define $filteroncategids, the filter on category ID if there is a Root category defined.
	$filteroncategids = '';
	if ($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0) {	// A root category is defined, we must filter on products inside this category tree
		$object = new Categorie($db);
		//$result = $object->fetch($conf->global->TAKEPOS_ROOT_CATEGORY_ID);
		$arrayofcateg = $object->get_full_arbo('product', $conf->global->TAKEPOS_ROOT_CATEGORY_ID, 1);
		if (is_array($arrayofcateg) && count($arrayofcateg) > 0) {
			foreach ($arrayofcateg as $val)
			{
				$filteroncategids .= ($filteroncategids ? ', ' : '').$val['id'];
			}
		}
	}

	$sql = 'SELECT rowid, ref, label, tosell, tobuy, barcode, price FROM '.MAIN_DB_PREFIX.'product as p';
	$sql .= ' WHERE entity IN ('.getEntity('product').')';
	if ($filteroncategids) {
		$sql .= ' AND EXISTS (SELECT cp.fk_product FROM '.MAIN_DB_PREFIX.'categorie_product as cp WHERE cp.fk_product = p.rowid AND cp.fk_categorie IN ('.$filteroncategids.'))';
	}
	$sql .= ' AND tosell = 1';
	$sql .= natural_search(array('ref', 'label', 'barcode'), $term);
	$resql = $db->query($sql);
	if ($resql)
	{
		$rows = array();
		while ($obj = $db->fetch_object($resql)) {
			$rows[] = array(
				'rowid' => $obj->rowid,
				'ref' => $obj->ref,
				'label' => $obj->label,
				'tosell' => $obj->tosell,
				'tobuy' => $obj->tobuy,
				'barcode' => $obj->barcode,
				'price' => $obj->price,
			'object' => 'product'
				//'price_formated' => price(price2num($obj->price, 'MU'), 1, $langs, 1, -1, -1, $conf->currency)
			);
		}
		echo json_encode($rows);
	} else {
		echo 'Failed to search product : '.$db->lasterror();
	}
} elseif ($action == "opendrawer" && $term != '') {
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
	$printer = new dolReceiptPrinter($db);
	// check printer for terminal
	if ($conf->global->{'TAKEPOS_PRINTER_TO_USE'.$term} > 0) {
		$printer->initPrinter($conf->global->{'TAKEPOS_PRINTER_TO_USE'.$term});
		// open cashdrawer
		$printer->pulse();
		$printer->close();
	}
} elseif ($action == "printinvoiceticket" && $term != '' && $id > 0 && !empty($user->rights->facture->lire)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/dolreceiptprinter.class.php';
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
	$printer = new dolReceiptPrinter($db);
	// check printer for terminal
	if (($conf->global->{'TAKEPOS_PRINTER_TO_USE'.$term} > 0 || $conf->global->TAKEPOS_PRINT_METHOD == "takeposconnector") && $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$term} > 0) {
		$object = new Facture($db);
		$object->fetch($id);
		$ret = $printer->sendToPrinter($object, $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$term}, $conf->global->{'TAKEPOS_PRINTER_TO_USE'.$term});
	}
} elseif ($action == 'getInvoice') {
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
	$printer = new dolReceiptPrinter($db);
	$printer->sendToPrinter($object, $conf->global->{'TAKEPOS_TEMPLATE_TO_USE_FOR_INVOICES'.$term}, $conf->global->{'TAKEPOS_PRINTER_TO_USE'.$term});
}
