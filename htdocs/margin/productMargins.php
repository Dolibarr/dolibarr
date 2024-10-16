<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2014		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2020		Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 *	\file       htdocs/margin/productMargins.php
 *	\ingroup    margin
 *	\brief      Page des marges par produit
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'products', 'margins'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$TSelectedCats = GETPOST('categories', 'array');
$socid = 0;

$mesg = '';

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	if ($id > 0) {
		$sortfield = "f.datef";
		$sortorder = "DESC";
	} else {
		$sortfield = "p.ref";
		$sortorder = "ASC";
	}
}

$startdate = $enddate = '';
if (GETPOST('startdatemonth')) {
	$startdate = dol_mktime(0, 0, 0, GETPOSTINT('startdatemonth'), GETPOSTINT('startdateday'), GETPOSTINT('startdateyear'));
}
if (GETPOST('enddatemonth')) {
	$enddate = dol_mktime(23, 59, 59, GETPOSTINT('enddatemonth'), GETPOSTINT('enddateday'), GETPOST('enddateyear'));
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Product($db);
$hookmanager->initHooks(array('marginproductlist'));

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
if (!$user->hasRight('margins', 'liretous')) {
	accessforbidden();
}


/*
 * View
 */

$product_static = new Product($db);
$invoicestatic = new Facture($db);

$form = new Form($db);

llxHeader('', $langs->trans("Margins").' - '.$langs->trans("Products"), '', '', 0, 0, '', '', '', 'mod-margin page-productmargins');

$text = $langs->trans("Margins");
//print load_fiche_titre($text);

// Show tabs
$head = marges_prepare_head();

$titre = $langs->trans("Margins");
$picto = 'margin';

print '<form method="post" name="sel" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'productMargins', $titre, 0, $picto);

print '<table class="border centpercent">';

// Product
print '<tr><td class="titlefield">'.$langs->trans('ProductOrService').'</td>';
print '<td class="maxwidthonsmartphone" colspan="4">';
print img_picto('', 'product').$form->select_produits(($id > 0 ? $id : ''), 'id', '', 20, 0, 1, 2, '', 1, array(), 0, 'All', 0, '', 0, '', null, 1);
print '</td></tr>';

// Categories
$TCats = $form->select_all_categories('product', array(), '', 64, 0, 3);

print '<tr>';
print '<td class="titlefield">'.$langs->trans('Category').'</td>';
print '<td class="maxwidthonsmartphone" colspan="4">';
print img_picto('', 'category', 'class="pictofixedwidth"').$form->multiselectarray('categories', $TCats, $TSelectedCats, 0, 0, 'quatrevingtpercent widthcentpercentminusx');
print '</td>';
print '</tr>';

// Start date
print '<tr>';
print '<td class="titlefield">'.$langs->trans('DateStart').' ('.$langs->trans("DateValidation").')</td>';
print '<td>';
print $form->selectDate($startdate, 'startdate', 0, 0, 1, "sel", 1, 1);
print '</td>';
print '<td>'.$langs->trans('DateEnd').' ('.$langs->trans("DateValidation").')</td>';
print '<td>';
print $form->selectDate($enddate, 'enddate', 0, 0, 1, "sel", 1, 1);
print '</td>';
print '<td style="text-align: center;">';
print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans('Refresh')).'" />';
print '</td></tr>';

print "</table>";

print '<br>';

print '<table class="border centpercent">';

// Total Margin
print '<tr><td class="titlefield">'.$langs->trans("TotalMargin").'</td><td colspan="4">';
print '<span id="totalMargin" class="amount"></span> <span class="amount">'.$langs->getCurrencySymbol($conf->currency).'</span>'; // set by jquery (see below)
print '</td></tr>';

// Margin Rate
if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
	print '<tr><td>'.$langs->trans("MarginRate").'</td><td colspan="4">';
	print '<span id="marginRate"></span>'; // set by jquery (see below)
	print '</td></tr>';
}

// Mark Rate
if (getDolGlobalString('DISPLAY_MARK_RATES')) {
	print '<tr><td>'.$langs->trans("MarkRate").'</td><td colspan="4">';
	print '<span id="markRate"></span>'; // set by jquery (see below)
	print '</td></tr>';
}

print "</table>";

print dol_get_fiche_end();

print '</form>';

$invoice_status_except_list = array(Facture::STATUS_DRAFT, Facture::STATUS_ABANDONED);

$sql = "SELECT p.label, p.rowid, p.fk_product_type, p.ref, p.entity as pentity,";
if ($id > 0) {
	$sql .= " d.fk_product,";
}
if ($id > 0) {
	$sql .= " f.rowid as facid, f.ref, f.total_ht, f.datef, f.paye, f.fk_statut as statut,";
}
$sql .= " SUM(d.total_ht) as selling_price,";
$sql .= " SUM(d.qty) as product_qty,";

// Note: qty and buy_price_ht is always positive (if not your database may be corrupted, you can update this)
$sql .= " SUM(".$db->ifsql('(d.total_ht < 0 OR (d.total_ht = 0 AND f.type = 2))', '-1 * d.qty * d.buy_price_ht * (d.situation_percent / 100)', 'd.qty * d.buy_price_ht * (d.situation_percent / 100)').") as buying_price,";
$sql .= " SUM(".$db->ifsql('(d.total_ht < 0 OR (d.total_ht = 0 AND f.type = 2))', '-1 * (abs(d.total_ht) - (d.buy_price_ht * d.qty * (d.situation_percent / 100)))', 'd.total_ht - (d.buy_price_ht * d.qty * (d.situation_percent / 100))').") as marge";

$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."facture as f";
$sql .= ", ".MAIN_DB_PREFIX."facturedet as d";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = d.fk_product";
if (!empty($TSelectedCats)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=p.rowid';
}
$sql .= " WHERE f.fk_soc = s.rowid";
$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
$sql .= " AND f.fk_statut NOT IN (".$db->sanitize(implode(', ', $invoice_status_except_list)).")";
$sql .= " AND d.fk_facture = f.rowid";
if ($id > 0) {
	$sql .= " AND d.fk_product =".((int) $id);
}
if (!empty($TSelectedCats)) {
	$sql .= ' AND cp.fk_categorie IN ('.$db->sanitize(implode(',', $TSelectedCats)).')';
}
if (!empty($startdate)) {
	$sql .= " AND f.datef >= '".$db->idate($startdate)."'";
}
if (!empty($enddate)) {
	$sql .= " AND f.datef <= '".$db->idate($enddate)."'";
}
$sql .= " AND d.buy_price_ht IS NOT NULL";
// We should not use this here. Option ForceBuyingPriceIfNull should have effect only when inserting data. Once data is recorded, it must be used as it is for report.
// We keep it with value ForceBuyingPriceIfNull = 2 for retroactive effect but results are unpredictable.
if (getDolGlobalInt('ForceBuyingPriceIfNull') == 2) {
	$sql .= " AND d.buy_price_ht <> 0";
}
if ($id > 0) {
	$sql .= " GROUP BY p.label, p.rowid, p.fk_product_type, p.ref, p.entity, d.fk_product, f.rowid, f.ref, f.total_ht, f.datef, f.paye, f.fk_statut";
} else {
	$sql .= " GROUP BY p.label, p.rowid, p.fk_product_type, p.ref, p.entity";
}
$sql .= $db->order($sortfield, $sortorder);
// TODO: calculate total to display then restore pagination
//$sql.= $db->plimit($conf->liste_limit +1, $offset);

$param = '&id='.((int) $id);
if (GETPOSTINT('startdatemonth')) {
	$param .= '&startdateyear='.GETPOSTINT('startdateyear');
	$param .= '&startdatemonth='.GETPOSTINT('startdatemonth');
	$param .= '&startdateday='.GETPOSTINT('startdateday');
}
if (GETPOSTINT('enddatemonth')) {
	$param .= '&enddateyear='.GETPOSTINT('enddateyear');
	$param .= '&enddatemonth='.GETPOSTINT('enddatemonth');
	$param .= '&enddateday='.GETPOSTINT('enddateday');
}
$listofcateg = GETPOST('categories', 'array:int');
if (is_array($listofcateg)) {
	foreach ($listofcateg as $val) {
		$param .= '&categories[]='.$val;
	}
}

$totalMargin = 0;
$marginRate = '';
$markRate = '';
dol_syslog('margin::productMargins.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	print '<br>';
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition, PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $num, '', 0, '', '', 0, 1);

	//var_dump($conf->global->MARGIN_TYPE);
	if (getDolGlobalString('MARGIN_TYPE') == "1") {
		$labelcostprice = 'BuyingPrice';
	} else { // value is 'costprice' or 'pmp'
		$labelcostprice = 'CostPrice';
	}

	$moreforfilter = '';

	$i = 0;
	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre">';
	if ($id > 0) {
		print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DateInvoice", $_SERVER["PHP_SELF"], "f.datef", "", $param, '', $sortfield, $sortorder, 'center ');
	} else {
		print_liste_field_titre("ProductService", $_SERVER["PHP_SELF"], "p.ref", "", $param, '', $sortfield, $sortorder);
	}
	print_liste_field_titre("Qty", $_SERVER["PHP_SELF"], "product_qty", "", $param, '', $sortfield, $sortorder, 'center ');
	print_liste_field_titre("SellingPrice", $_SERVER["PHP_SELF"], "selling_price", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre($labelcostprice, $_SERVER["PHP_SELF"], "buying_price", "", $param, '', $sortfield, $sortorder, 'right ');
	print_liste_field_titre("Margin", $_SERVER["PHP_SELF"], "marge", "", $param, '', $sortfield, $sortorder, 'right ');
	if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
		print_liste_field_titre("MarginRate", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES')) {
		print_liste_field_titre("MarkRate", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
	}
	print "</tr>\n";

	$cumul_achat = 0;
	$cumul_vente = 0;
	$cumul_qty = 0;

	if ($num > 0) {
		while ($i < $num /*&& $i < $conf->liste_limit*/) {
			$objp = $db->fetch_object($result);
			$qty = $objp->product_qty;
			$pa = $objp->buying_price;
			$pv = $objp->selling_price;
			$marge = $objp->marge;

			if ($pa != 0) {
				$marginRate = (100 * $marge / $pa);
				// We invert the sign if the margin is negative, regardless of the sign of the purchase price
				if ($marge < 0) {
					$marginRate = -$marginRate;
				}
			} else {
				$marginRate = '';
			}

			if ($pv != 0) {
				$markRate = (100 * $marge / $pv);
				// We invert the sign if the margin is negative, as in the original logic
				if ($marge < 0) {
					$markRate = -$markRate;
				}
			} else {
				$markRate = '';
			}

			print '<tr class="oddeven">';
			if ($id > 0) {
				print '<td>';
				$invoicestatic->id = $objp->facid;
				$invoicestatic->ref = $objp->ref;
				print $invoicestatic->getNomUrl(1);
				print "</td>\n";
				print "<td class=\"center\">";
				print dol_print_date($db->jdate($objp->datef), 'day')."</td>";
			} else {
				print '<td>';
				if ($objp->rowid > 0) {
					$product_static->type = $objp->fk_product_type;
					$product_static->id = $objp->rowid;
					$product_static->ref = $objp->ref;
					$product_static->label = $objp->label;
					$product_static->entity = $objp->pentity;
					$text = $product_static->getNomUrl(1);
					print $text .= ' - '.$objp->label;
				} else {
					print img_object('', 'product').' '.$langs->trans("NotPredefinedProducts");
				}
				print "</td>\n";
				//print "<td>".$product_static->getNomUrl(1)."</td>\n";
			}
			print '<td class="center">'.$qty.'</td>';
			print '<td class="nowrap right"><span class="amount">'.price(price2num($pv, 'MT')).'</span></td>';
			print '<td class="nowrap right"><span class="amount">'.price(price2num($pa, 'MT')).'</span></td>';
			print '<td class="nowrap right"><span class="amount">'.price(price2num($marge, 'MT')).'</span></td>';
			if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
				print '<td class="nowrap right">'.(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%").'</td>';
			}
			if (getDolGlobalString('DISPLAY_MARK_RATES')) {
				print '<td class="nowrap right">'.(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%").'</td>';
			}
			print "</tr>\n";

			$i++;
			$cumul_achat += $objp->buying_price;
			$cumul_vente += $objp->selling_price;
			$cumul_qty += $objp->product_qty;
		}
	}

	// affichage totaux marges

	$totalMargin = $cumul_vente - $cumul_achat;

	$marginRate = ($cumul_achat != 0) ? (100 * $totalMargin / $cumul_achat) : '';
	$markRate = ($cumul_vente != 0) ? (100 * $totalMargin / $cumul_vente) : '';

	print '<tr class="liste_total">';
	if ($id > 0) {
		print '<td colspan=2>';
	} else {
		print '<td>';
	}
	print $langs->trans('TotalMargin').'</td>';
	print '<td class="center">'.$cumul_qty.'</td>';
	print '<td class="nowrap right">'.price(price2num($cumul_vente, 'MT')).'</td>';
	print '<td class="nowrap right">'.price(price2num($cumul_achat, 'MT')).'</td>';
	print '<td class="nowrap right">'.price(price2num($totalMargin, 'MT')).'</td>';
	if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
		print '<td class="nowrap right">'.(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%").'</td>';
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES')) {
		print '<td class="nowrap right">'.(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%").'</td>';
	}
	print "</tr>\n";

	print "</table>";
	print '</div>';
} else {
	dol_print_error($db);
}
$db->free($result);


print '
<script type="text/javascript">
$(document).ready(function() {
  console.log("Init some values");
  $("#totalMargin").html("'.price(price2num($totalMargin, 'MT')).'");
  $("#marginRate").html("'.(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%").'");
  $("#markRate").html("'.(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%").'");
});
</script>
';

// End of page
llxFooter();
$db->close();
