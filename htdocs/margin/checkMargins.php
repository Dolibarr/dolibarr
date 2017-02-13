<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2014		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2016       Florian Henry       <florian.henry@open-concept.pro>
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
 * \file htdocs/margin/checkMargins.php
 * \ingroup margin
 * \brief Check margins
 */
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/margin/lib/margins.lib.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("products");
$langs->load("margins");

// Security check

if ($user->rights->margins->creer) {
	$agentid = $user->id;
} else {
	accessforbidden();
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
if (! $sortorder)
	$sortorder = "DESC";
if (! $sortfield) {
	$sortfield = 'f.rowid';
}
$page = GETPOST("page", 'int');
if ($page == - 1) {
	$page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Both test are required to be compatible with all browsers
if (GETPOST("button_search_x") || GETPOST("button_search")) {
	$action = 'search';
} elseif (GETPOST("button_updatemagins_x") || GETPOST("button_updatemagins")) {
	$action = 'update';
}

if ($action == 'update') {
	$datapost = $_POST;
	
	foreach ( $datapost as $key => $value ) {
		if (strpos($key, 'buyingprice_') !== false) {
			$tmp_array = explode('_', $key);
			if (count($tmp_array) > 0) {
				$invoicedet_id = $tmp_array[1];
				if (! empty($invoicedet_id)) {
					$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'facturedet';
					$sql .= ' SET buy_price_ht=\'' . price2num($value) . '\'';
					$sql .= ' WHERE rowid=' . $invoicedet_id;
					$result = $db->query($sql);
					if (!$result) {
						setEventMessages($db->lasterror, null, 'errors');
					}
				}
			}
		}
	}
}

$startdate = $enddate = '';

$startdate = dol_mktime(0, 0, 0, GETPOST('startdatemonth', 'int'), GETPOST('startdateday', 'int'), GETPOST('startdateyear', 'int'));
$enddate = dol_mktime(23, 59, 59, GETPOST('enddatemonth', 'int'), GETPOST('enddateday', 'int'), GETPOST('enddateyear', 'int'));

if (! empty($startdate)) {
	$options .= '&amp;startdatemonth=' . GETPOST('startdatemonth', 'int') . '&amp;startdateday=' . GETPOST('startdateday', 'int') . '&amp;startdateyear=' . GETPOST('startdateyear', 'int');
}
if (! empty($enddate)) {
	$options .= '&amp;enddatemonth=' . GETPOST('enddatemonth', 'int') . '&amp;enddateday=' . GETPOST('enddateday', 'int') . '&amp;enddateyear=' . GETPOST('enddateyear', 'int');
}

/*
 * View
 */

$userstatic = new User($db);
$companystatic = new Societe($db);
$invoicestatic = new Facture($db);
$productstatic = new Product($db);

$form = new Form($db);

$title = $langs->trans("Margins");

llxHeader('', $title);

// print_fiche_titre($text);

// Show tabs
$head = marges_prepare_head($user);
$picto = 'margin';

print '<form method="post" name="sel" action="' . $_SERVER['PHP_SELF'] . '">';

dol_fiche_head($head, $langs->trans('checkMargins'), $title, 0, $picto);

print '<table class="border" width="100%">';

// Start date
print '<td class="titlefield">' . $langs->trans('DateStart') . ' (' . $langs->trans("DateValidation") . ')</td>';
print '<td>';
$form->select_date($startdate, 'startdate', '', '', 1, "sel", 1, 1);
print '</td>';
print '<td>' . $langs->trans('DateEnd') . ' (' . $langs->trans("DateValidation") . ')</td>';
print '<td>';
$form->select_date($enddate, 'enddate', '', '', 1, "sel", 1, 1);
print '</td>';
print '<td style="text-align: center;">';
print '<input type="submit" class="button" value="' . dol_escape_htmltag($langs->trans('Refresh')) . '" name="button_search" />';
print '</td></tr>';
print "</table>";

dol_fiche_end();


$sql = "SELECT";
$sql .= " f.facnumber, f.rowid as invoiceid, d.rowid as invoicedetid, d.buy_price_ht, d.total_ht, d.subprice, d.label, d.description , d.qty";
$sql .= " ,d.fk_product";
$sql .= " FROM " . MAIN_DB_PREFIX . "facture as f ";
$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as d  ON d.fk_facture = f.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON d.fk_product = p.rowid";
$sql .= " WHERE f.fk_statut > 0";
$sql .= " AND f.entity = " . getEntity('facture', 1);
if (! empty($startdate))
	$sql .= " AND f.datef >= '" . $db->idate($startdate) . "'";
if (! empty($enddate))
	$sql .= " AND f.datef <= '" . $db->idate($enddate) . "'";
$sql .= " AND d.buy_price_ht IS NOT NULL";
$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	
	dol_syslog(__FILE__, LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$nbtotalofrecords = $db->num_rows($result);
	} else {
		setEventMessages($db->lasterror, null, 'errors');
	}
}

$sql .= $db->plimit($conf->liste_limit + 1, $offset);

dol_syslog(__FILE__, LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	
	print '<br>';
	print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num, $nbtotalofrecords, '');
	
	if ($conf->global->MARGIN_TYPE == "1")
	    $labelcostprice=$langs->trans('BuyingPrice');
	else   // value is 'costprice' or 'pmp'
	    $labelcostprice=$langs->trans('CostPrice');
	
	$moreforfilter='';
	
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";
	
	print '<tr class="liste_titre">';
	
	print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "f.ref", "", $options, '', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Description"), $_SERVER["PHP_SELF"], "", "", $options, 'width=20%', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("UnitPriceHT"), $_SERVER["PHP_SELF"], "d.subprice", "", $options, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($labelcostprice, $_SERVER["PHP_SELF"], "d.buy_price_ht", "", $options, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("Qty"), $_SERVER["PHP_SELF"], "d.qty", "", $options, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"), $_SERVER["PHP_SELF"], "d.total_ht", "", $options, 'align="right"', $sortfield, $sortorder);
	
	print "</tr>\n";
	
	if ($num > 0) {
		$var = true;
		
		while ( $objp = $db->fetch_object($result) ) {
			$var = ! $var;
			
			print "<tr " . $bc[$var] . ">";
			print '<td>';
			$result_inner = $invoicestatic->fetch($objp->invoiceid);
			if ($result_inner < 0) {
				setEventMessages($invoicestatic->error, null, 'errors');
			} else {
				print $invoicestatic->getNomUrl(1);
			}
			print '</td>';
			print '<td>';
			if (! empty($objp->fk_product)) {
				$result_inner = $productstatic->fetch($objp->fk_product);
				if ($result_inner < 0) {
					setEventMessages($productstatic->error, null, 'errors');
				} else {
					print $productstatic->getNomUrl(1);
				}
			} else {
				print $objp->label;
				print '&nbsp;';
				print $objp->description;
			}
			print '</td>';
			print '<td align="right">';
			print price($objp->subprice);
			print '</td>';
			print '<td align="right">';
			print '<input type="text" name="buyingprice_' . $objp->invoicedetid . '" id="buyingprice_' . $objp->invoicedetid . '" size="6" value="' . price($objp->buy_price_ht) . '" class="flat">';
			print '</td>';
			print '<td align="right">';
			print $objp->qty;
			print '</td>';
			print '<td align="right">';
			print price($objp->total_ht);
			print '</td>';
			
			print "</tr>\n";
			
			$i ++;
		}
	}
	print "</table>";
	
	print "</div>";
} else {
	dol_print_error($db);
}


print '<div class="tabsAction">' . "\n";
print '<div class="inline-block divButAction"><input type="submit"  name="button_updatemagins" id="button_updatemagins" class="butAction" value="' . $langs->trans("Update") . '" /></div>';
print '</div>';

print '</form>';

$db->free($result);

llxFooter();
$db->close();