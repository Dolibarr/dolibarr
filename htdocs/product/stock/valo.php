<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/product/stock/valo.php
 *  \ingroup    stock
 *  \brief      Page with stock values
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';

// Load translation files required by the page
$langs->load("stocks");

// Security check
$result = restrictedArea($user, 'stock');

$sref = GETPOST("sref", 'alpha');
$snom = GETPOST("snom", 'alpha');
$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (!$sortfield) {
	$sortfield = "e.ref";
}
if (!$sortorder) {
	$sortorder = "ASC";
}
$page = $_GET["page"];
if ($page < 0) {
	$page = 0;
}
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;

$year = dol_print_date(dol_now('gmt'), "%Y", 'gmt');


/*
 *	View
 */

$sql = "SELECT e.rowid, e.ref, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays,";
$sql .= " SUM(ps.pmp * ps.reel) as estimatedvalue, SUM(p.price * ps.reel) as sellvalue";
$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON e.rowid = ps.fk_entrepot";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON ps.fk_product = p.rowid";
$sql .= " WHERE e.entity IN (".getEntity('stock').")";
if ($sref) {
	$sql .= natural_search("e.ref", $sref);
}
if ($sall) {
	$sql .= " AND (e.ref LIKE '%".$db->escape($sall)."%'";
	$sql .= " OR e.description LIKE '%".$db->escape($sall)."%'";
	$sql .= " OR e.lieu LIKE '%".$db->escape($sall)."%'";
	$sql .= " OR e.address LIKE '%".$db->escape($sall)."%'";
	$sql .= " OR e.town LIKE '%".$db->escape($sall)."%')";
}
$sql .= " GROUP BY e.rowid, e.ref, e.statut, e.lieu, e.address, e.zip, e.town, e.fk_pays";
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$i = 0;

	$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
	llxHeader("", $langs->trans("EnhancedValueOfWarehouses"), $help_url);

	print_barre_liste($langs->trans("EnhancedValueOfWarehouses"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num);

	print '<table class="noborder centpercent">';
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "e.ref", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("LocationSummary", $_SERVER["PHP_SELF"], "e.lieu", "", "", "", $sortfield, $sortorder);
	print_liste_field_titre("EstimatedStockValue", $_SERVER["PHP_SELF"], "e.valo_pmp", '', '', '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("EstimatedStockValueSell", $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "e.statut", '', '', '', $sortfield, $sortorder, 'right ');
	print "</tr>\n";

	if ($num) {
		$entrepot = new Entrepot($db);
		$total = $totalsell = 0;
		$var = false;
		while ($i < min($num, $limit)) {
			$objp = $db->fetch_object($result);
			print '<tr class="oddeven">';
			print '<td><a href="card.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowWarehouse"), 'stock').' '.$objp->ref.'</a></td>';
			print '<td>'.$objp->lieu.'</td>';
			// PMP value
			print '<td class="right">';
			if (price2num($objp->estimatedvalue, 'MT')) {
				print price(price2num($objp->estimatedvalue, 'MT'), 1);
			} else {
				print '';
			}
			print '</td>';
			// Selling value
			print '<td class="right">';
			if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
				print price(price2num($objp->sellvalue, 'MT'), 1);
			} else {
				print $langs->trans("Variable");
			}
			print '</td>';
			// Status
			print '<td class="right">'.$entrepot->LibStatut($objp->statut, 5).'</td>';
			print "</tr>\n";
			$total += price2num($objp->estimatedvalue, 'MU');
			$totalsell += price2num($objp->sellvalue, 'MU');

			$i++;
		}

		print '<tr class="liste_total">';
		print '<td colspan="2" class="right">'.$langs->trans("Total").'</td>';
		print '<td class="right">'.price(price2num($total, 'MT'), 1, $langs, 0, 0, -1, $conf->currency).'</td>';
		print '<td class="right">'.price(price2num($totalsell, 'MT'), 1, $langs, 0, 0, -1, $conf->currency).'</td>';
		print '<td class="right">&nbsp;</td>';
		print "</tr>\n";
	}

	$db->free($result);

	print "</table>";

	print '<br>';

	$file = 'entrepot-'.$year.'.png';
	if (file_exists($conf->stock->dir_temp.'/'.$file)) {
		$url = DOL_URL_ROOT.'/viewimage.php?modulepart=graph_stock&amp;file='.$file;
		print '<img src="'.$url.'">';
	}

	$file = 'entrepot-'.($year - 1).'.png';
	if (file_exists($conf->stock->dir_temp.'/'.$file)) {
		$url = DOL_URL_ROOT.'/viewimage.php?modulepart=graph_stock&amp;file='.$file;
		print '<br><img src="'.$url.'">';
	}
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
