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
$socid = GETPOST('socid', 'int');
if (!empty($user->socid)) {
	$socid = $user->socid;
}

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
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

$parameters = array('id'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}



/*
 * View
 */

$invoicestatic = new Facture($db);
$form = new Form($db);

$title = $langs->trans("ThirdParty").' - '.$langs->trans("Margins");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = $object->name.' - '.$langs->trans("Files");
}
$help_url = 'EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('', $title, $help_url);

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
	// We keep it with value ForceBuyingPriceIfNull = 2 for retroactive effect but results are unpredicable.
	if (getDolGlobalInt('ForceBuyingPriceIfNull') == 2) {
		$sql .= " AND d.buy_price_ht <> 0";
	}
	$sql .= " GROUP BY s.nom, s.rowid, s.code_client, f.rowid, f.ref, f.total_ht, f.datef, f.paye, f.fk_statut, f.type";
	$sql .= $db->order($sortfield, $sortorder);
	// TODO: calculate total to display then restore pagination
	//$sql.= $db->plimit($conf->liste_limit +1, $offset);

	dol_syslog('margin:tabs:thirdpartyMargins.php', LOG_DEBUG);
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);

		print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], "&amp;socid=".$object->id, $sortfield, $sortorder, '', $num, $num, '');

		$i = 0;
		print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
		print "<table class=\"noborder\" width=\"100%\">";

		print '<tr class="liste_titre">';
		print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder);
		print_liste_field_titre("DateInvoice", $_SERVER["PHP_SELF"], "f.datef", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("SoldAmount", $_SERVER["PHP_SELF"], "selling_price", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("PurchasedAmount", $_SERVER["PHP_SELF"], "buying_price", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder, 'right ');
		print_liste_field_titre("Margin", $_SERVER["PHP_SELF"], "marge", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder, 'right ');
		if (getDolGlobalString('DISPLAY_MARGIN_RATES')) {
			print_liste_field_titre("MarginRate", $_SERVER["PHP_SELF"], "", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder, 'right ');
		}
		if (getDolGlobalString('DISPLAY_MARK_RATES')) {
			print_liste_field_titre("MarkRate", $_SERVER["PHP_SELF"], "", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder, 'right ');
		}
		print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "f.paye,f.fk_statut", "", "&amp;socid=".$_REQUEST["socid"], '', $sortfield, $sortorder, 'right ');
		print "</tr>\n";

		$cumul_achat = 0;
		$cumul_vente = 0;

		if ($num > 0) {
			while ($i < $num /*&& $i < $conf->liste_limit*/) {
				$objp = $db->fetch_object($result);

				$marginRate = ($objp->buying_price != 0) ? (100 * $objp->marge / $objp->buying_price) : '';
				$markRate = ($objp->selling_price != 0) ? (100 * $objp->marge / $objp->selling_price) : '';

				$sign = '';
				if ($objp->type == Facture::TYPE_CREDIT_NOTE) {
					$sign = '-';
				}

				print '<tr class="oddeven">';
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
		print '<td colspan=2>'.$langs->trans('TotalMargin')."</td>";
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
		print "</tr>\n";
	} else {
		dol_print_error($db);
	}
	print "</table>";
	print '</div>';

	print '<br>';
	$db->free($result);
} else {
	dol_print_error('', 'Parameter socid not defined');
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
