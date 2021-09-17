<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014	   Florian Henry		<florian.henry@open-concept.pro>
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
 *	\file       htdocs/product/stats/facture.php
 *	\ingroup    product service facture
 *	\brief      Page of invoice statistics for a product
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'bills', 'products', 'supplier_proposal'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
$socid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productstatsinvoice'));

$showmessage = GETPOST('showmessage');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
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

$search_month = GETPOST('search_month', 'int');
$search_year = GETPOST('search_year', 'int');

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$search_month = '';
	$search_year = '';
}

$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);


/*
 * View
 */

$invoicestatic = new Facture($db);
$societestatic = new Societe($db);

$form = new Form($db);
$formother = new FormOther($db);

if ($id > 0 || !empty($ref)) {
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$object = $product;

	$parameters = array('id'=>$id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $product, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	$title = $langs->trans('ProductServiceCard');
	$helpurl = '';
	$shortlabel = dol_trunc($object->label, 16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Referers');
		$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Referers');
		$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $helpurl);

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
		if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) {
			$shownav = 0;
		}

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border tableforfield" width="100%">';

		$nboflines = show_stats_for_company($product, $socid);

		print "</table>";

		print '</div>';
		print '<div style="clear:both"></div>';

		print dol_get_fiche_end();

		if ($showmessage && $nboflines > 1) {
			print '<span class="opacitymedium">'.$langs->trans("ClinkOnALinkOfColumn", $langs->transnoentitiesnoconv("Referers")).'</span>';
		} elseif ($user->rights->facture->lire) {
			$sql = "SELECT DISTINCT s.nom as name, s.rowid as socid, s.code_client,";
			$sql .= " f.ref, f.datef, f.paye, f.type, f.fk_statut as statut, f.rowid as facid,";
			$sql .= " d.rowid, d.total_ht as total_ht, d.qty"; // We must keep the d.rowid here to not loose record because of the distinct used to ignore duplicate line when link on societe_commerciaux is used
			if (!$user->rights->societe->client->voir && !$socid) {
				$sql .= ", sc.fk_soc, sc.fk_user ";
			}
			$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
			$sql .= ", ".MAIN_DB_PREFIX."facture as f";
			$sql .= ", ".MAIN_DB_PREFIX."facturedet as d";
			if (!$user->rights->societe->client->voir && !$socid) {
				$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
			}
			$sql .= " WHERE f.fk_soc = s.rowid";
			$sql .= " AND f.entity IN (".getEntity('invoice').")";
			$sql .= " AND d.fk_facture = f.rowid";
			$sql .= " AND d.fk_product = ".((int) $product->id);
			if (!empty($search_month)) {
				$sql .= ' AND MONTH(f.datef) IN ('.$db->sanitize($search_month).')';
			}
			if (!empty($search_year)) {
				$sql .= ' AND YEAR(f.datef) IN ('.$db->sanitize($search_year).')';
			}
			if (!$user->rights->societe->client->voir && !$socid) {
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
			}
			if ($socid) {
				$sql .= " AND f.fk_soc = ".((int) $socid);
			}
			$sql .= $db->order($sortfield, $sortorder);

			// Calcul total qty and amount for global if full scan list
			$total_ht = 0;
			$total_qty = 0;

			// Count total nb of records
			$totalofrecords = '';
			if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
				$result = $db->query($sql);
				$totalofrecords = $db->num_rows($result);
			}

			$sql .= $db->plimit($limit + 1, $offset);

			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);

				if ($limit > 0 && $limit != $conf->liste_limit) {
					$option .= '&limit='.urlencode($limit);
				}
				if (!empty($id)) {
					$option .= '&id='.$product->id;
				}
				if (!empty($search_month)) {
					$option .= '&search_month='.urlencode($search_month);
				}
				if (!empty($search_year)) {
					$option .= '&search_year='.urlencode($search_year);
				}

				print '<form method="post" action="'.$_SERVER ['PHP_SELF'].'?id='.$product->id.'" name="search_form">'."\n";
				if (!empty($sortfield)) {
					print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
				}
				if (!empty($sortorder)) {
					print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';
				}

				print_barre_liste($langs->trans("CustomersInvoices"), $page, $_SERVER["PHP_SELF"], $option, $sortfield, $sortorder, '', $num, $totalofrecords, '', 0, '', '', $limit, 0, 0, 1);

				if (!empty($page)) {
					$option .= '&page='.urlencode($page);
				}

				print '<div class="liste_titre liste_titre_bydiv centpercent">';
				print '<div class="divsearchfield">';
				print $langs->trans('Period').' ('.$langs->trans("DateInvoice").') - ';
				print $langs->trans('Month').':<input class="flat" type="text" size="4" name="search_month" value="'.$search_month.'"> ';
				print $langs->trans('Year').':'.$formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);
				print '<div style="vertical-align: middle; display: inline-block">';
				print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
				print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
				print '</div>';
				print '</div>';
				print '</div>';

				$i = 0;
				print '<div class="div-table-responsive">';
				print '<table class="tagtable liste listwithfilterbefore" width="100%">';
				print '<tr class="liste_titre">';
				print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "s.rowid", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("CustomerCode", $_SERVER["PHP_SELF"], "s.code_client", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("DateInvoice", $_SERVER["PHP_SELF"], "f.datef", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre("Qty", $_SERVER["PHP_SELF"], "d.qty", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre("AmountHT", $_SERVER["PHP_SELF"], "d.total_ht", "", $option, 'align="right"', $sortfield, $sortorder);
				print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "f.paye,f.fk_statut", "", $option, 'align="right"', $sortfield, $sortorder);
				print "</tr>\n";

				if ($num > 0) {
					while ($i < min($num, $limit)) {
						$objp = $db->fetch_object($result);

						if ($objp->type == Facture::TYPE_CREDIT_NOTE) {
							$objp->qty = -($objp->qty);
						}

						$total_ht += $objp->total_ht;
						$total_qty += $objp->qty;

						$invoicestatic->id = $objp->facid;
						$invoicestatic->ref = $objp->ref;
						$societestatic->fetch($objp->socid);
						$paiement = $invoicestatic->getSommePaiement();

						print '<tr class="oddeven">';
						print '<td>';
						print $invoicestatic->getNomUrl(1);
						print "</td>\n";
						print '<td>'.$societestatic->getNomUrl(1).'</td>';
						print "<td>".$objp->code_client."</td>\n";
						print '<td class="center">';
						print dol_print_date($db->jdate($objp->datef), 'dayhour')."</td>";
						print '<td class="center">'.$objp->qty."</td>\n";
						print '<td align="right">'.price($objp->total_ht)."</td>\n";
						print '<td align="right">'.$invoicestatic->LibStatut($objp->paye, $objp->statut, 5, $paiement, $objp->type).'</td>';
						print "</tr>\n";
						$i++;
					}
				}
				print '<tr class="liste_total">';
				if ($num < $limit) {
					print '<td class="left">'.$langs->trans("Total").'</td>';
				} else {
					print '<td class="left">'.$langs->trans("Totalforthispage").'</td>';
				}
				print '<td colspan="3"></td>';
				print '<td class="center">'.$total_qty.'</td>';
				print '<td align="right">'.price($total_ht).'</td>';
				print '<td></td>';
				print "</table>";
				print '</div>';
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
