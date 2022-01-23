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
 *	\file       htdocs/margin/tabs/productMargins.php
 *	\ingroup    product margins
 *	\brief      Page des marges des factures clients pour un produit
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->loadLangs(array("companies", "bills", "products", "margins"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if (!empty($user->socid)) {
	$socid = $user->socid;
}

$object = new Product($db);

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

$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

if (empty($user->rights->margins->liretous)) {
	accessforbidden();
}


/*
 * View
 */

$invoicestatic = new Facture($db);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);

	$title = $langs->trans('ProductServiceCard');
	$help_url = '';
	$shortlabel = dol_trunc($object->label, 16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Card');
		$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Card');
		$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $help_url);

	// View mode
	if ($result > 0) {
		$head = product_prepare_head($object);
		$titre = $langs->trans("CardProduct".$object->type);
		$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');
		print dol_get_fiche_head($head, 'margin', $titre, -1, $picto);

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref');


		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield centpercent">';

		// Total Margin
		print '<tr><td class="titlefield">'.$langs->trans("TotalMargin").'</td><td>';
		print '<span id="totalMargin" class="amount"></span>'; // set by jquery (see below)
		print '</td></tr>';

		// Margin Rate
		if (!empty($conf->global->DISPLAY_MARGIN_RATES)) {
			print '<tr><td>'.$langs->trans("MarginRate").'</td><td>';
			print '<span id="marginRate"></span>'; // set by jquery (see below)
			print '</td></tr>';
		}

		// Mark Rate
		if (!empty($conf->global->DISPLAY_MARK_RATES)) {
			print '<tr><td>'.$langs->trans("MarkRate").'</td><td>';
			print '<span id="markRate"></span>'; // set by jquery (see below)
			print '</td></tr>';
		}

		print "</table>";

		print '</div>';
		print '<div style="clear:both"></div>';

		print dol_get_fiche_end();


		if ($user->rights->facture->lire) {
			$sql = "SELECT s.nom as name, s.rowid as socid, s.code_client,";
			$sql .= " f.rowid as facid, f.ref, f.total_ht,";
			$sql .= " f.datef, f.paye, f.fk_statut as statut, f.type,";
			if (empty($user->rights->societe->client->voir) && !$socid) {
				$sql .= " sc.fk_soc, sc.fk_user,";
			}
			$sql .= " sum(d.total_ht) as selling_price,"; // may be negative or positive
			$sql .= " ".$db->ifsql('f.type = 2', -1, 1)." * sum(d.qty) as qty,"; // not always positive in case of Credit note
			$sql .= " ".$db->ifsql('f.type = 2', -1, 1)." * sum(d.qty * d.buy_price_ht * (d.situation_percent / 100)) as buying_price,"; // not always positive in case of Credit note
			$sql .= " ".$db->ifsql('f.type = 2', -1, 1)." * sum(abs(d.total_ht) - (d.buy_price_ht * d.qty * (d.situation_percent / 100))) as marge"; // not always positive in case of Credit note
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= ", ".MAIN_DB_PREFIX."facture as f";
			$sql .= ", ".MAIN_DB_PREFIX."facturedet as d";
			if (empty($user->rights->societe->client->voir) && !$socid) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= " WHERE f.fk_soc = s.rowid";
			$sql .= " AND f.fk_statut > 0";
			$sql .= " AND f.entity IN (".getEntity('invoice').")";
			$sql .= " AND d.fk_facture = f.rowid";
			$sql .= " AND d.fk_product = ".((int) $object->id);
			if (empty($user->rights->societe->client->voir) && !$socid) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if (!empty($socid)) {
				$sql .= " AND f.fk_soc = $socid";
			}
			$sql .= " AND d.buy_price_ht IS NOT NULL";
			// We should not use this here. Option ForceBuyingPriceIfNull should have effect only when inserting data. Once data is recorded, it must be used as it is for report.
			// We keep it with value ForceBuyingPriceIfNull = 2 for retroactive effect but results are unpredicable.
			if (isset($conf->global->ForceBuyingPriceIfNull) && $conf->global->ForceBuyingPriceIfNull == 2) {
				$sql .= " AND d.buy_price_ht <> 0";
			}
			$sql .= " GROUP BY s.nom, s.rowid, s.code_client, f.rowid, f.ref, f.total_ht, f.datef, f.paye, f.fk_statut, f.type";
			if (empty($user->rights->societe->client->voir) && !$socid) {
				$sql .= ", sc.fk_soc, sc.fk_user";
			}
			$sql .= $db->order($sortfield, $sortorder);
			// TODO: calculate total to display then restore pagination
			//$sql.= $db->plimit($conf->liste_limit +1, $offset);
			dol_syslog('margin:tabs:productMargins.php', LOG_DEBUG);
			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);

				print_barre_liste($langs->trans("MarginDetails"), $page, $_SERVER["PHP_SELF"], "&amp;id=$object->id", $sortfield, $sortorder, '', 0, 0, '');

				$i = 0;

				print '<div class="div-table-responsive">';
				print '<table class="noborder centpercent">';

				print '<tr class="liste_titre">';
				print_liste_field_titre("Invoice", $_SERVER["PHP_SELF"], "f.ref", "", "&amp;id=".$object->id, '', $sortfield, $sortorder);
				print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", "&amp;id=".$object->id, '', $sortfield, $sortorder);
				print_liste_field_titre("CustomerCode", $_SERVER["PHP_SELF"], "s.code_client", "", "&amp;id=".$object->id, '', $sortfield, $sortorder);
				print_liste_field_titre("DateInvoice", $_SERVER["PHP_SELF"], "f.datef", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'left ');
				print_liste_field_titre("SellingPrice", $_SERVER["PHP_SELF"], "selling_price", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("BuyingPrice", $_SERVER["PHP_SELF"], "buying_price", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("Qty", $_SERVER["PHP_SELF"], "d.qty", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'right ');
				print_liste_field_titre("Margin", $_SERVER["PHP_SELF"], "marge", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'right ');
				if (!empty($conf->global->DISPLAY_MARGIN_RATES)) {
					print_liste_field_titre("MarginRate", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'right ');
				}
				if (!empty($conf->global->DISPLAY_MARK_RATES)) {
					print_liste_field_titre("MarkRate", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'right ');
				}
				print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "f.paye,f.fk_statut", "", "&amp;id=".$object->id, '', $sortfield, $sortorder, 'right ');
				print "</tr>\n";

				$cumul_achat = 0;
				$cumul_vente = 0;
				$cumul_qty = 0;

				if ($num > 0) {
					while ($i < $num /*&& $i < $conf->liste_limit*/) {
						$objp = $db->fetch_object($result);

						$marginRate = ($objp->buying_price != 0) ? (100 * $objp->marge / $objp->buying_price) : '';
						$markRate = ($objp->selling_price != 0) ? (100 * $objp->marge / $objp->selling_price) : '';

						print '<tr class="oddeven">';
						print '<td>';
						$invoicestatic->id = $objp->facid;
						$invoicestatic->ref = $objp->ref;
						print $invoicestatic->getNomUrl(1);
						print "</td>\n";
						print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"), "company").' '.dol_trunc($objp->name, 44).'</a></td>';
						print "<td>".$objp->code_client."</td>\n";
						print '<td class="center">';
						print dol_print_date($db->jdate($objp->datef), 'day')."</td>";
						print '<td class="right amount">'.price(price2num($objp->selling_price, 'MT'))."</td>\n";
						print '<td class="right amount">'.price(price2num($objp->buying_price, 'MT'))."</td>\n";
						print '<td class="right">'.price(price2num($objp->qty, 'MT'))."</td>\n";
						print '<td class="right amount">'.price(price2num($objp->marge, 'MT'))."</td>\n";
						if (!empty($conf->global->DISPLAY_MARGIN_RATES)) {
							print "<td class=\"right\">".(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%")."</td>\n";
						}
						if (!empty($conf->global->DISPLAY_MARK_RATES)) {
							print "<td class=\"right\">".(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%")."</td>\n";
						}
						print '<td class="right">'.$invoicestatic->LibStatut($objp->paye, $objp->statut, 5).'</td>';
						print "</tr>\n";
						$i++;
						$cumul_achat += $objp->buying_price;
						$cumul_vente += $objp->selling_price;
						$cumul_qty += $objp->qty;
					}
				}

				// affichage totaux marges

				$totalMargin = $cumul_vente - $cumul_achat;
				if ($totalMargin < 0) {
					$marginRate = ($cumul_achat != 0) ?-1 * (100 * $totalMargin / $cumul_achat) : '';
					$markRate = ($cumul_vente != 0) ?-1 * (100 * $totalMargin / $cumul_vente) : '';
				} else {
					$marginRate = ($cumul_achat != 0) ? (100 * $totalMargin / $cumul_achat) : '';
					$markRate = ($cumul_vente != 0) ? (100 * $totalMargin / $cumul_vente) : '';
				}
				print '<tr class="liste_total">';
				print '<td colspan=4>'.$langs->trans('TotalMargin')."</td>";
				print '<td class="right amount">'.price(price2num($cumul_vente, 'MT'))."</td>\n";
				print '<td class="right amount">'.price(price2num($cumul_achat, 'MT'))."</td>\n";
				print '<td class="right">'.price(price2num($cumul_qty, 'MT'))."</td>\n";
				print '<td class="right amount">'.price(price2num($totalMargin, 'MT'))."</td>\n";
				if (!empty($conf->global->DISPLAY_MARGIN_RATES)) {
					print '<td class="right">'.(($marginRate === '') ? 'n/a' : price(price2num($marginRate, 'MT'))."%")."</td>\n";
				}
				if (!empty($conf->global->DISPLAY_MARK_RATES)) {
					print "<td class=\"right\">".(($markRate === '') ? 'n/a' : price(price2num($markRate, 'MT'))."%")."</td>\n";
				}
				print '<td class="right">&nbsp;</td>';
				print "</tr>\n";
				print "</table>";
				print '</div>';
			} else {
				dol_print_error($db);
			}
			$db->free($result);
		}
	}
} else {
	dol_print_error();
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
