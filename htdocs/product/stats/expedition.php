<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2024	   Jean-Rémi Taponier	<jean-remi@netlogic.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *       \file       htdocs/product/stats/expedition.php
 *       \ingroup    product expedition
 *       \brief      Page des stats des expeditions pour un produit
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings', 'products', 'companies'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
$socid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('productstatsexpedition'));

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
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "e.date_creation";
}
$search_month = GETPOSTINT('search_month');
$search_year = GETPOSTINT('search_year');

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$search_month = '';
	$search_year = '';
	$search_status = '';
}

$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);


/*
 * View
 */

$expeditionstatic = new Expedition($db);
$societestatic = new Societe($db);

$form = new Form($db);
$formother = new FormOther($db);

if ($id > 0 || !empty($ref)) {
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$object = $product;

	$parameters = array('id' => $id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $product, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	llxHeader("", "", $langs->trans("CardProduct".$product->type), '', '', 0, 0, '', '', 'mod-product page-stats_expedition');

	if ($result > 0) {
		$head = product_prepare_head($product);
		$titre = $langs->trans("CardProduct".$product->type);
		$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');
		print dol_get_fiche_head($head, 'referers', $titre, -1, $picto);

		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $product, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$shownav = 1;
		if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
			$shownav = 0;
		}

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		$nboflines = show_stats_for_company($product, $socid);

		print "</table>";

		print '</div>';
		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();


		if ($user->hasRight('shipping', 'lire')) {
			$sql = "SELECT DISTINCT s.nom as name, s.rowid as socid, s.code_client, e.ref, e.ref_customer";
			$sql .= ", e.date_creation, e.date_delivery, e.fk_statut as statut, e.rowid as expeditionid, ed.rowid";
			$sql .= ", ed.qty , cd.subprice * (100 - cd.remise_percent) / 100 * ed.qty AS total_ht";
			if (empty($user->rights->societe->client->voir) && !$socid) {
				$sql .= ", sc.fk_soc, sc.fk_user ";
			}
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expedition as e ON e.fk_soc = s.rowid";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."expeditiondet as ed ON ed.fk_expedition = e.rowid";
			$sql .= " INNER JOIN ".MAIN_DB_PREFIX."commandedet as cd ON cd.rowid = ed.fk_elementdet AND (ed.element_type = 'commande' OR ed.element_type = 'order')";
			if (empty($user->rights->societe->client->voir) && !$socid) {
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			$sql .= " WHERE e.entity IN (".getEntity('expedition').")";
			$sql .= " AND cd.fk_product = ".((int) $product->id);
			if (!empty($search_month)) {
				$sql .= ' AND MONTH(e.date_creation) IN ('.$db->sanitize($search_month).')';
			}
			if (!empty($search_year)) {
				$sql .= ' AND YEAR(e.date_creation) IN ('.$db->sanitize($search_year).')';
			}
			if ($socid) {
				$sql .= " AND e.fk_soc = ".((int) $socid);
			}
			$sql .= $db->order($sortfield, $sortorder);

			//Calcul total qty and amount for global if full scan list
			$total_ht = 0;
			$total_qty = 0;

			// Count total nb of records
			$totalofrecords = '';
			if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
				$result = $db->query($sql);
				$totalofrecords = $db->num_rows($result);
			}

			$sql .= $db->plimit($limit + 1, $offset);

			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);

				$option = '&id='.$product->id;

				if ($limit > 0 && $limit != $conf->liste_limit) {
					$option .= '&limit='.((int) $limit);
				}
				if (!empty($search_month)) {
					$option .= '&search_month='.urlencode((string) ($search_month));
				}
				if (!empty($search_year)) {
					$option .= '&search_year='.urlencode((string) ($search_year));
				}

				print '<form method="post" action="'.$_SERVER ['PHP_SELF'].'?id='.$product->id.'" name="search_form">'."\n";
				print '<input type="hidden" name="token" value="'.newToken().'">';
				if (!empty($sortfield)) {
					print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
				}
				if (!empty($sortorder)) {
					print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';
				}

				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				print_barre_liste($langs->trans("Shipments"), $page, $_SERVER["PHP_SELF"], $option, $sortfield, $sortorder, '', $num, $totalofrecords, '', 0, '', '', $limit, 0, 0, 1);

				if (!empty($page)) {
					$option .= '&page='.urlencode((string) ($page));
				}

				print '<div class="liste_titre liste_titre_bydiv centpercent">';
				print '<div class="divsearchfield">';
				print $langs->trans('Period').' ('.$langs->trans("DateCreation").') - ';
				print $langs->trans('Month').':<input class="flat" type="text" size="4" name="search_month" value="'.($search_month > 0 ? $search_month : '').'"> ';
				print $langs->trans('Year').':'.$formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);
				print '<div style="vertical-align: middle; display: inline-block">';
				print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', 0, 1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
				print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"), 'searchclear.png', '', 0, 1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				print '</div>';
				print '</div>';
				print '</div>';

				$i = 0;
				print '<div class="div-table-responsive">';
				print '<table class="tagtable liste listwithfilterbefore" width="100%">';
				print '<tr class="liste_titre">';
				print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "e.rowid", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("CustomerCode", $_SERVER["PHP_SELF"], "s.code_client", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "e.date_creation", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre('DateDeliveryPlanned', $_SERVER['PHP_SELF'], 'e.date_delivery', '', $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre("Qty", $_SERVER["PHP_SELF"], "ed.qty", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre("AmountHT", $_SERVER["PHP_SELF"], "total_ht", "", $option, 'align="right"', $sortfield, $sortorder);
				print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "e.fk_statut", "", $option, 'align="right"', $sortfield, $sortorder);
				print "</tr>\n";

				if ($num > 0) {
					while ($i < min($num, $limit)) {
						$objp = $db->fetch_object($result);

						$objp->total_ht = price2num($objp->total_ht, 'MT');
						$total_ht += $objp->total_ht;
						$total_qty += $objp->qty;

						$expeditionstatic->id = $objp->expeditionid;
						$expeditionstatic->ref = $objp->ref;
						$expeditionstatic->ref_customer = $objp->ref_customer;
						$societestatic->fetch($objp->socid);

						print '<tr class="oddeven">';
						print '<td>';
						print $expeditionstatic->getNomUrl(1);
						print "</td>\n";
						print '<td>'.$societestatic->getNomUrl(1).'</td>';
						print "<td>".$objp->code_client."</td>\n";
						print '<td class="center">';
						print dol_print_date($db->jdate($objp->date_creation), 'dayhour')."</td>";
						// delivery planned date
						print '<td class="center">';
						print dol_print_date($db->jdate($objp->date_delivery), 'dayhour');
						print '</td>';
						print  '<td class="center">'.$objp->qty."</td>\n";
						print '<td align="right">'.price($objp->total_ht)."</td>\n";
						print '<td align="right">'.$expeditionstatic->LibStatut($objp->statut, 4).'</td>';
						print "</tr>\n";
						$i++;
					}
				}
				print '<tr class="liste_total">';
				if ($num < $limit && empty($offset)) {
					print '<td>'.$langs->trans("Total").'</td>';
				} else {
					print '<td>'.$form->textwithpicto($langs->trans("Total"), $langs->trans("Totalforthispage")).'</td>';
				}
				print '<td colspan="3"></td>';
				print '<td></td>';
				print '<td class="center">'.$total_qty.'</td>';
				print '<td align="right">'.price($total_ht).'</td>';
				print '<td></td>';
				print "</table>";
				print "</div>";
				print '</form>';
			} else {
				dol_print_error($db);
			}
			$db->free($result);
		}
	}
} else {
	dol_print_error();
}

// End of page
llxFooter();
$db->close();
