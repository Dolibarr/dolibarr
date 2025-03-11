<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2014		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2024		MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/margin/customerMargins.php
 *	\ingroup    margin
 *	\brief      Page des marges par client
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/margin/lib/margins.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'products', 'margins'));

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
	$sortfield = "s.nom"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$startdate = $enddate = '';
if (GETPOST('startdatemonth')) {
	$startdate = dol_mktime(0, 0, 0, GETPOSTINT('startdatemonth'), GETPOSTINT('startdateday'), GETPOSTINT('startdateyear'));
}
if (GETPOST('enddatemonth')) {
	$enddate = dol_mktime(23, 59, 59, GETPOSTINT('enddatemonth'), GETPOSTINT('enddateday'), GETPOST('enddateyear'));
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$object = new Societe($db);
$hookmanager->initHooks(array('margincustomerlist'));

// Security check
$socid = GETPOSTINT('socid');
$TSelectedProducts = GETPOST('products', 'array');
$TSelectedCats = GETPOST('categories', 'array');

if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'societe', '', '');
$result = restrictedArea($user, 'margins');


/*
 * View
 */

$companystatic = new Societe($db);
$invoicestatic = new Facture($db);

$form = new Form($db);

llxHeader('', $langs->trans("Margins").' - '.$langs->trans("Clients"), '', '', 0, 0, '', '', '', 'mod-margin page-customermargins');

$text = $langs->trans("Margins");
//print load_fiche_titre($text);

// Show tabs
$head = marges_prepare_head();

$titre = $langs->trans("Margins");
$picto = 'margin';


print '<form method="post" name="sel" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'customerMargins', $titre, 0, $picto);

print '<table class="border centpercent">';

$client = false;
if ($socid > 0) {
	$soc = new Societe($db);
	$soc->fetch($socid);

	if ($soc->client) {
		print '<tr><td class="titlefield">'.$langs->trans('ThirdPartyName').'</td>';
		print '<td class="maxwidthonsmartphone" colspan="4">';
		$filter = '(client:IN:1,3)';
		print img_picto('', 'company').$form->select_company($socid, 'socid', $filter, 1, 0, 0);
		print '</td></tr>';

		$client = true;
		if (!$sortorder) {
			$sortorder = "DESC";
		}
		if (!$sortfield) {
			$sortfield = "f.datef";
		}
	}
} else {
	print '<tr><td class="titlefield">'.$langs->trans('ThirdPartyName').'</td>';
	print '<td class="maxwidthonsmartphone" colspan="4">';
	print img_picto('', 'company').$form->select_company(null, 'socid', '((client:=:1) OR (client:=:3))', 1, 0, 0);
	print '</td></tr>';
}

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	if ($client) {
		$sortfield = "f.datef";
		$sortorder = "DESC";
	} else {
		$sortfield = "s.nom";
		$sortorder = "ASC";
	}
}

// Products
$TRes = $form->select_produits_list('', '', '', '', 0, '', 1, 2, 1, 0, '', 1);

$TProducts = array();
foreach ($TRes as $prod) {
	$TProducts[$prod['key']] = $prod['label'];
}

print '<tr><td class="titlefield">'.$langs->trans('ProductOrService').'</td>';
print '<td class="maxwidthonsmartphone" colspan="4">';
print img_picto('', 'product').$form->multiselectarray('products', $TProducts, $TSelectedProducts, 0, 0, 'minwidth500');
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
print '<td>'.$langs->trans('DateStart').' ('.$langs->trans("DateValidation").')</td>';
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

$sql = "SELECT";
$sql .= " s.rowid as socid, s.nom as name, s.code_client, s.client,";
if ($client) {
	$sql .= " f.rowid as facid, f.ref, f.total_ht, f.datef, f.paye, f.type, f.fk_statut as statut,";
}
$sql .= " sum(d.total_ht) as selling_price,";
// Note: qty and buy_price_ht is always positive (if not, your database may be corrupted, you can update this)

$sql .= " sum(".$db->ifsql('(d.total_ht < 0 OR (d.total_ht = 0 AND f.type = 2))', '-1 * d.qty * d.buy_price_ht * (d.situation_percent / 100)', 'd.qty * d.buy_price_ht * (d.situation_percent / 100)').") as buying_price,";
$sql .= " sum(".$db->ifsql('(d.total_ht < 0 OR (d.total_ht = 0 AND f.type = 2))', '-1 * (abs(d.total_ht) - (d.buy_price_ht * d.qty * (d.situation_percent / 100)))', 'd.total_ht - (d.buy_price_ht * d.qty * (d.situation_percent / 100))').") as marge";

$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."facture as f";
$sql .= ", ".MAIN_DB_PREFIX."facturedet as d";
if (!empty($TSelectedCats)) {
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON cp.fk_product=d.fk_product';
}

if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE f.fk_soc = s.rowid";
if ($socid > 0) {
	$sql .= ' AND s.rowid = '.((int) $socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " AND f.fk_statut NOT IN (".$db->sanitize(implode(', ', $invoice_status_except_list)).")";
$sql .= ' AND s.entity IN ('.getEntity('societe').')';
$sql .= ' AND f.entity IN ('.getEntity('invoice').')';
$sql .= " AND d.fk_facture = f.rowid";
$sql .= " AND (d.product_type = 0 OR d.product_type = 1)";
if (!empty($TSelectedProducts)) {
	$sql .= ' AND d.fk_product IN ('.$db->sanitize(implode(',', $TSelectedProducts)).')';
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
if ($client) {
	$sql .= " GROUP BY s.rowid, s.nom, s.code_client, s.client, f.rowid, f.ref, f.total_ht, f.datef, f.paye, f.type, f.fk_statut";
} else {
	$sql .= " GROUP BY s.rowid, s.nom, s.code_client, s.client";
}
$sql .= $db->order($sortfield, $sortorder);
// TODO: calculate total to display then restore pagination
//$sql.= $db->plimit($conf->liste_limit +1, $offset);

$param = '&socid='.((int) $socid);
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
$listofproducts = GETPOST('products', 'array:int');
if (is_array($listofproducts)) {
	foreach ($listofproducts as $val) {
		$param .= '&products[]='.$val;
	}
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
dol_syslog('margin::customerMargins.php', LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	print '<br>';
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition, PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num, $num, '', 0, '', '', 0, 1);

	if (getDolGlobalString('MARGIN_TYPE') == "1") {
		$labelcostprice = 'BuyingPrice';
	} else { // value is 'costprice' or 'pmp'
		$labelcostprice = 'CostPrice';
	}

	$moreforfilter = '';

	$i = 0;
	print '<div class="div-table-responsive">';
	print '<table class="tagtable noborder nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	print '<tr class="liste_titre">';
	if (!empty($client)) {
		print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DateInvoice", $_SERVER["PHP_SELF"], "f.datef", "", $param, 'align="center"', $sortfield, $sortorder);
	} else {
		print_liste_field_titre("Customer", $_SERVER["PHP_SELF"], "s.nom", "", $param, '', $sortfield, $sortorder);
	}
	print_liste_field_titre("SellingPrice", $_SERVER["PHP_SELF"], "selling_price", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre($labelcostprice, $_SERVER["PHP_SELF"], "buying_price", "", $param, 'align="right"', $sortfield, $sortorder);
	print_liste_field_titre("Margin", $_SERVER["PHP_SELF"], "marge", "", $param, 'align="right"', $sortfield, $sortorder);
	if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
		print_liste_field_titre("MarginRate", $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield, $sortorder);
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES')) {
		print_liste_field_titre("MarkRate", $_SERVER["PHP_SELF"], "", "", $param, 'align="right"', $sortfield, $sortorder);
	}
	print "</tr>\n";

	$cumul_achat = 0;
	$cumul_vente = 0;

	if ($num > 0) {
		while ($i < $num /*&& $i < $conf->liste_limit*/) {
			$objp = $db->fetch_object($result);

			$pa = $objp->buying_price;
			$pv = $objp->selling_price;
			$marge = $objp->marge;

			if ($marge < 0) {
				$marginRate = ($pa != 0) ? -1 * (100 * $marge / $pa) : '';
				$markRate = ($pv != 0) ? -1 * (100 * $marge / $pv) : '';
			} else {
				$marginRate = ($pa != 0) ? (100 * $marge / $pa) : '';
				$markRate = ($pv != 0) ? (100 * $marge / $pv) : '';
			}

			print '<tr class="oddeven">';
			if ($client) {
				$invoicestatic->id = $objp->facid;
				$invoicestatic->ref = $objp->ref;
				$invoicestatic->statut = $objp->statut;
				$invoicestatic->type = $objp->type;

				print '<td>';
				print $invoicestatic->getNomUrl(1);
				print '</td>';
				print '<td class="center">';
				print dol_print_date($db->jdate($objp->datef), 'day').'</td>';
			} else {
				$companystatic->id = $objp->socid;
				$companystatic->name = $objp->name;
				$companystatic->client = $objp->client;

				print '<td>'.$companystatic->getNomUrl(1, 'margin').'</td>';
			}

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
		}
	}

	// affichage totaux marges

	$totalMargin = $cumul_vente - $cumul_achat;
	/*if ($totalMargin < 0)
	{
		$marginRate = ($cumul_achat != 0)?-1*(100 * $totalMargin / $cumul_achat):'';
		$markRate = ($cumul_vente != 0)?-1*(100 * $totalMargin / $cumul_vente):'';
	}
	else
	{*/
	$marginRate = ($cumul_achat != 0) ? (100 * $totalMargin / $cumul_achat) : '';
	$markRate = ($cumul_vente != 0) ? (100 * $totalMargin / $cumul_vente) : '';
	//}

	print '<tr class="liste_total">';
	if ($client) {
		print '<td colspan="2">';
	} else {
		print '<td>';
	}
	print $langs->trans('TotalMargin')."</td>";
	print '<td class="nowrap right">'.price(price2num($cumul_vente, 'MT')).'</td>';
	print '<td class="nowrap right">'.price(price2num($cumul_achat, 'MT')).'</td>';
	print '<td class="nowrap right">'.price(price2num($totalMargin, 'MT')).'</td>';
	if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
		print '<td class="nowrap right">'.(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%").'</td>';
	}
	if (getDolGlobalString('DISPLAY_MARK_RATES')) {
		print '<td class="nowrap right">'.(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%").'</td>';
	}
	print '</tr>';

	print '</table>';
	print '</div>';
} else {
	dol_print_error($db);
}
$db->free($result);

print '<script type="text/javascript">
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
