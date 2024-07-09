<?php
/* Copyright (C) 2012-2013	Christophe Battarel	<christophe.battarel@altairis.fr>
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
 *	\file       htdocs/margin/tabs/thirdpartyMargins.php
 *	\ingroup    product margins
 *	\brief      Page for invoice margins of a thirdparty
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->loadLangs(array("companies", "bills", "products", "margins"));

// Security check
$socid = GETPOSTINT('socid');
if (!empty($user->socid)) {
	$socid = $user->socid;
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "f.datef";
}

$object = new Societe($db);
if ($socid > 0) {
	$object->fetch($socid);
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartymargins', 'globalcard'));

$result = restrictedArea($user, 'societe', $object->id, '');

if (!$user->hasRight('margins', 'liretous')) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$search_invoice_date_start = '';
$search_invoice_date_end = '';
if (GETPOSTINT('search_invoice_date_start_month')) {
	$search_invoice_date_start = dol_mktime(0, 0, 0, GETPOSTINT('search_invoice_date_start_month'), GETPOSTINT('search_invoice_date_start_day'), GETPOSTINT('search_invoice_date_start_year'));
}
if (GETPOSTINT('search_invoice_date_end_month')) {
	$search_invoice_date_end = dol_mktime(23, 59, 59, GETPOSTINT('search_invoice_date_end_month'), GETPOSTINT('search_invoice_date_end_day'), GETPOSTINT('search_invoice_date_end_year'));
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_invoice_date_start = '';
	$search_invoice_date_end = '';
}

// set default dates from fiscal year
if (empty($search_invoice_date_start) && empty($search_invoice_date_end) && !GETPOSTISSET('restore_lastsearch_values')) {
	$query = "SELECT date_start, date_end";
	$query .= " FROM ".MAIN_DB_PREFIX."accounting_fiscalyear";
	$query .= " WHERE date_start < '".$db->idate(dol_now())."' and date_end > '".$db->idate(dol_now())."' limit 1";
	$res = $db->query($query);

	if ($res && $db->num_rows($res) > 0) {
		$fiscalYear = $db->fetch_object($res);
		$search_invoice_date_start = strtotime($fiscalYear->date_start);
		$search_invoice_date_end = strtotime($fiscalYear->date_end);
	} else {
		$month_start = ($conf->global->SOCIETE_FISCAL_MONTH_START ? $conf->global->SOCIETE_FISCAL_MONTH_START : 1);
		$year_start = dol_print_date(dol_now(), '%Y');
		if (dol_print_date(dol_now(), '%m') < $month_start) {
			$year_start--; // If current month is lower that starting fiscal month, we start last year
		}
		$year_end = $year_start + 1;
		$month_end = $month_start - 1;
		if ($month_end < 1) {
			$month_end = 12;
			$year_end--;
		}
		$search_invoice_date_start = dol_mktime(0, 0, 0, $month_start, 1, $year_start);
		$search_invoice_date_end = dol_get_last_day($year_end, $month_end);
	}
}

/*
 * View
 */

$invoicestatic = new Facture($db);
$form = new Form($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Margins");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->name.' - '.$langs->trans("Files");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

$param = "&socid=".$socid;
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_invoice_date_start) {
	$param .= '&search_invoice_date_start_day='.dol_print_date($search_invoice_date_start, '%d').'&search_invoice_date_start_month='.dol_print_date($search_invoice_date_start, '%m').'&search_invoice_date_start_year='.dol_print_date($search_invoice_date_start, '%Y');
}
if ($search_invoice_date_end) {
	$param .= '&search_invoice_date_end_day='.dol_print_date($search_invoice_date_end, '%d').'&search_invoice_date_end_month='.dol_print_date($search_invoice_date_end, '%m').'&search_invoice_date_end_year='.dol_print_date($search_invoice_date_end, '%Y');
}

$totalMargin = 0;
$marginRate = '';
$markRate = '';

if ($socid > 0) {
	$object = new Societe($db);
	$object->fetch($socid);

	/*
	 * Affichage onglets
	 */

	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'margin', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	// Type Prospect/Customer/Supplier
	print '<tr><td class="titlefield">'.$langs->trans('NatureOfThirdParty').'</td><td>';
	print $object->getTypeUrl(1);
	print '</td></tr>';

	if ($object->client) {
		print '<tr><td class="titlefield">';
		print $langs->trans('CustomerCode').'</td><td colspan="3">';
		print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_client));
		$tmpcheck = $object->check_codeclient();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <span class="error">('.$langs->trans("WrongCustomerCode").')</span>';
		}
		print '</td></tr>';
	}

	if (((isModEnabled("fournisseur") && $user->hasRight('fournisseur', 'lire') && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))) && $object->fournisseur) {
		print '<tr><td class="titlefield">';
		print $langs->trans('SupplierCode').'</td><td colspan="3">';
		print showValueWithClipboardCPButton(dol_escape_htmltag($object->code_fournisseur));
		$tmpcheck = $object->check_codefournisseur();
		if ($tmpcheck != 0 && $tmpcheck != -5) {
			print ' <span class="error">('.$langs->trans("WrongSupplierCode").')</span>';
		}
		print '</td></tr>';
	}

	// Total Margin
	print '<tr><td class="titlefield">'.$langs->trans("TotalMargin").'</td><td colspan="3">';
	print '<span id="totalMargin" class="amount"></span>'; // set by jquery (see below)
	print '</td></tr>';

	// Margin Rate
	if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
		print '<tr><td>'.$langs->trans("MarginRate").'</td><td colspan="3">';
		print '<span id="marginRate"></span>'; // set by jquery (see below)
		print '</td></tr>';
	}

	// Mark Rate
	if (getDolGlobalString('DISPLAY_MARK_RATES')) {
		print '<tr><td>'.$langs->trans("MarkRate").'</td><td colspan="3">';
		print '<span id="markRate"></span>'; // set by jquery (see below)
		print '</td></tr>';
	}

	print "</table>";

	print '</div>';
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	print '<br>';

	$sql = "SELECT distinct s.nom, s.rowid as socid, s.code_client,";
	$sql .= " f.rowid as facid, f.ref, f.total_ht,";
	$sql .= " f.datef, f.paye, f.fk_statut as statut, f.type,";
	$sql .= " sum(d.total_ht) as selling_price,"; // may be negative or positive
	$sql .= " sum(d.qty * d.buy_price_ht * (d.situation_percent / 100)) as buying_price,"; // always positive
	$sql .= " sum(abs(d.total_ht) - (d.buy_price_ht * d.qty * (d.situation_percent / 100))) as marge"; // always positive
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."facture as f";
	$sql .= ", ".MAIN_DB_PREFIX."facturedet as d";
	$sql .= " WHERE f.fk_soc = s.rowid";
	$sql .= " AND f.fk_statut > 0";
	$sql .= " AND f.entity IN (".getEntity('invoice').")";
	$sql .= " AND d.fk_facture = f.rowid";
	$sql .= " AND f.fk_soc = $socid";
	$sql .= " AND d.buy_price_ht IS NOT NULL";
	// We should not use this here. Option ForceBuyingPriceIfNull should have effect only when inserting data. Once data is recorded, it must be used as it is for report.
	// We keep it with value ForceBuyingPriceIfNull = 2 for retroactive effect but results are unpredictable.
	if (getDolGlobalInt('ForceBuyingPriceIfNull') == 2) {
		$sql .= " AND d.buy_price_ht <> 0";
	}
	if (!empty($search_invoice_date_start)) {
		$sql .= " AND f.datef >= '".$db->idate($search_invoice_date_start)."'";
	}
	if (!empty($search_invoice_date_end)) {
		$sql .= " AND f.datef <= '".$db->idate($search_invoice_date_end)."'";
	}
	$sql .= " GROUP BY s.nom, s.rowid, s.code_client, f.rowid, f.ref, f.total_ht, f.datef, f.paye, f.fk_statut, f.type";

	// TODO: calculate total to display then restore pagination

	$sql .= $db->order($sortfield, $sortorder);
	if ($limit) {
		$sql .= $db->plimit($limit + 1, $offset);
	}

	dol_syslog('margin:tabs:thirdpartyMargins.php', LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);

		print '<form method="post" action="'.$_SERVER ['PHP_SELF'].'?socid='.$socid.'" name="search_form">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">';
		if (!empty($sortfield)) {
			print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
		}
		if (!empty($sortorder)) {
			print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition, PhanPluginSuspiciousParamOrder
		print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, '', '');

		$moreforfilter = '';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) {
			$moreforfilter .= $hookmanager->resPrint;
		} else {
			$moreforfilter = $hookmanager->resPrint;
		}

		if (!empty($moreforfilter)) {
			print '<div class="liste_titre liste_titre_bydiv centpercent">';
			print $moreforfilter;
			print '</div>';
		}

		$selectedfields = '';

		$i = 0;
		print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print "<table class=\"noborder\" width=\"100%\">";

		// Fields title search
		// --------------------------------------------------------------------
		print '<tr class="liste_titre_filter">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre center maxwidthsearch">';
			$searchpicto = $form->showFilterButtons('left');
			print $searchpicto;
			print '</td>';
		}

		// invoice ref
		print '<td class="liste_titre">';
		print '</td>';

		// invoice date
		print '<td class="liste_titre center">';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_invoice_date_start ?: -1, 'search_invoice_date_start_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
		print '</div>';
		print '<div class="nowrapfordate">';
		print $form->selectDate($search_invoice_date_end ?: -1, 'search_invoice_date_end_', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
		print '</div>';
		print '</td>';

		// selling price
		print '<td class="liste_titre">';
		print '</td>';

		// buying price
		print '<td class="liste_titre">';
		print '</td>';

		// margin
		print '<td class="liste_titre">';
		print '</td>';

		// margin rate
		if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
			print '<td class="liste_titre">';
			print '</td>';
		}

		// mark rate
		if (getDolGlobalString('DISPLAY_MARK_RATES')) {
			print '<td class="liste_titre">';
			print '</td>';
		}

		// status
		print '<td class="liste_titre">';
		print '</td>';

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre center maxwidthsearch">';
			$searchpicto = $form->showFilterButtons();
			print $searchpicto;
			print '</td>';
		}

		print '</tr>'."\n";

		print '<tr class="liste_titre">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ');
		}
		print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", $param, '', $sortfield, $sortorder);
		print_liste_field_titre("DateInvoice", $_SERVER["PHP_SELF"], "f.datef", "", $param, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("SoldAmount", $_SERVER["PHP_SELF"], "selling_price", "", $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("PurchasedAmount", $_SERVER["PHP_SELF"], "buying_price", "", $param, '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("Margin", $_SERVER["PHP_SELF"], "marge", "", $param, '', $sortfield, $sortorder, 'right ');
		if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
			print_liste_field_titre("MarginRate", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
		}
		if (getDolGlobalString('DISPLAY_MARK_RATES')) {
			print_liste_field_titre("MarkRate", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
		}
		print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "f.paye,f.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'maxwidthsearch center ');
		}
		print "</tr>\n";

		$cumul_achat = 0;
		$cumul_vente = 0;

		if ($num > 0) {
			$imaxinloop = ($limit ? min($num, $limit) : $num);
			while ($i < $imaxinloop) {
				$objp = $db->fetch_object($result);

				$marginRate = ($objp->buying_price != 0) ? (100 * $objp->marge / $objp->buying_price) : '';
				$markRate = ($objp->selling_price != 0) ? (100 * $objp->marge / $objp->selling_price) : '';

				$sign = '';
				if ($objp->type == Facture::TYPE_CREDIT_NOTE) {
					$sign = '-';
				}

				print '<tr class="oddeven">';
				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					print '</td>';
				}

				print '<td>';
				$invoicestatic->id = $objp->facid;
				$invoicestatic->ref = $objp->ref;
				print $invoicestatic->getNomUrl(1);
				print "</td>\n";
				print "<td class=\"center\">";
				print dol_print_date($db->jdate($objp->datef), 'day')."</td>";
				print "<td class=\"right amount\">".price(price2num($objp->selling_price, 'MT'))."</td>\n";
				print "<td class=\"right amount\">".price(price2num(($objp->type == 2 ? -1 : 1) * $objp->buying_price, 'MT'))."</td>\n";
				print "<td class=\"right amount\">".$sign.price(price2num($objp->marge, 'MT'))."</td>\n";
				if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
					print "<td class=\"right\">".(($marginRate === '') ? 'n/a' : $sign.price(price2num($marginRate, 'MT'))."%")."</td>\n";
				}
				if (getDolGlobalString('DISPLAY_MARK_RATES')) {
					print "<td class=\"right\">".(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%")."</td>\n";
				}
				print '<td class="right">'.$invoicestatic->LibStatut($objp->paye, $objp->statut, 5).'</td>';

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="nowrap center">';
					print '</td>';
				}
				print "</tr>\n";
				$i++;
				$cumul_vente += $objp->selling_price;
				$cumul_achat += ($objp->type == 2 ? -1 : 1) * $objp->buying_price;
			}
		}

		// affichage totaux marges

		$totalMargin = $cumul_vente - $cumul_achat;
		if ($totalMargin < 0) {
			$marginRate = ($cumul_achat != 0) ? -1 * (100 * $totalMargin / $cumul_achat) : '';
			$markRate = ($cumul_vente != 0) ? -1 * (100 * $totalMargin / $cumul_vente) : '';
		} else {
			$marginRate = ($cumul_achat != 0) ? (100 * $totalMargin / $cumul_achat) : '';
			$markRate = ($cumul_vente != 0) ? (100 * $totalMargin / $cumul_vente) : '';
		}

		// Total
		print '<tr class="liste_total">';
		$colspan = 2;
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			$colspan++; // add action column
		}
		print '<td colspan="'.$colspan.'">'.$langs->trans('TotalMargin')."</td>";
		print "<td class=\"right\">".price(price2num($cumul_vente, 'MT'))."</td>\n";
		print "<td class=\"right\">".price(price2num($cumul_achat, 'MT'))."</td>\n";
		print "<td class=\"right\">".price(price2num($totalMargin, 'MT'))."</td>\n";
		if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
			print "<td class=\"right\">".(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%")."</td>\n";
		}
		if (getDolGlobalString('DISPLAY_MARK_RATES')) {
			print "<td class=\"right\">".(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%")."</td>\n";
		}
		print '<td class="right">&nbsp;</td>';
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			// add action column
			print '<td class="center">';
			print '</td>';
		}
		print "</tr>\n";
	} else {
		dol_print_error($db);
	}
	print "</table>";
	print '</div>';
	print '</form>';

	print '<br>';
	$db->free($result);
} else {
	dol_print_error(null, 'Parameter socid not defined');
}


print '
    <script type="text/javascript">
    $(document).ready(function() {
        $("#totalMargin").html("'. price(price2num($totalMargin, 'MT')).'");
        $("#marginRate").html("'.(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%").'");
        $("#markRate").html("'.(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%").'");
    });
    </script>
';

// End of page
llxFooter();
$db->close();
