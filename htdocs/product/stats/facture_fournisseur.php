<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/product/stats/facture_fournisseur.php
 * \ingroup product service facture
 * \brief Page des stats des factures fournisseurs pour un produit
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("products");
$langs->load("companies");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$socid = '';
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productstatssupplyinvoice'));

$mesg = '';

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder = "DESC";
if (! $sortfield) $sortfield = "f.datef";
$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) {
	$search_month = '';
	$search_year = '';
}

/*
 * View
 */

$supplierinvoicestatic = new FactureFournisseur($db);
$societestatic = new Societe($db);

$form = new Form($db);
$formother = new FormOther($db);

if ($id > 0 || ! empty($ref))
{
	$product = new Product($db);
	$result = $product->fetch($id, $ref);
	
	$parameters = array('id' => $id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $product, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	
	llxHeader("", "", $langs->trans("CardProduct" . $product->type));
	
	if ($result > 0)
	{
		$head = product_prepare_head($product, $user);
		$titre = $langs->trans("CardProduct" . $product->type);
		$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');
		dol_fiche_head($head, 'referers', $titre, 0, $picto);
		
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $product, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		
		print '<table class="border" width="100%">';
		
		// Reference
		print '<tr>';
		print '<td width="30%">' . $langs->trans("Ref") . '</td><td colspan="3">';
		print $form->showrefnav($product, 'ref', '', 1, 'ref');
		print '</td>';
		print '</tr>';
		
		// Libelle
		print '<tr><td>' . $langs->trans("Label") . '</td><td colspan="3">' . $product->libelle . '</td>';
		print '</tr>';
		
		// Status (to sell)
		print '<tr><td>' . $langs->trans("Status") . ' (' . $langs->trans("Sell") . ')</td><td colspan="3">';
		print $product->getLibStatut(2, 0);
		print '</td></tr>';
		
		// Status (to buy)
		print '<tr><td>' . $langs->trans("Status") . ' (' . $langs->trans("Buy") . ')</td><td colspan="3">';
		print $product->getLibStatut(2, 1);
		print '</td></tr>';
		
		show_stats_for_company($product, $socid);
		
		print "</table>";
		
		print '</div>';
		
		if ($user->rights->fournisseur->facture->lire)
		{
			$sql = "SELECT distinct s.nom as name, s.rowid as socid, s.code_client, f.ref, d.total_ht as total_ht,";
			$sql .= " f.datef, f.paye, f.fk_statut as statut, f.rowid as facid, d.qty";
			if (! $user->rights->societe->client->voir && ! $socid)
				$sql .= ", sc.fk_soc, sc.fk_user ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
			$sql .= ", " . MAIN_DB_PREFIX . "facture_fourn as f";
			$sql .= ", " . MAIN_DB_PREFIX . "facture_fourn_det as d";
			if (! $user->rights->societe->client->voir && ! $socid)	$sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
			$sql .= " WHERE f.fk_soc = s.rowid";
			$sql .= " AND f.entity = " . $conf->entity;
			$sql .= " AND d.fk_facture_fourn = f.rowid";
			$sql .= " AND d.fk_product =" . $product->id;
			if (! empty($search_month))	
				$sql .= ' AND MONTH(f.datef) IN (' . $search_month . ')';
			if (! empty($search_year)) 
				$sql .= ' AND YEAR(f.datef) IN (' . $search_year . ')';
			if (! $user->rights->societe->client->voir && ! $socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
			if ($socid) $sql .= " AND f.fk_soc = " . $socid;
			$sql .= " ORDER BY $sortfield $sortorder ";
			
			// Calcul total qty and amount for global if full scan list
			$total_ht = 0;
			$total_qty = 0;
			$totalrecords = 0;
			if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
				$result = $db->query($sql);
				if ($result) {
					$totalrecords = $db->num_rows($result);
					while ( $objp = $db->fetch_object($result) ) {
						$total_ht += $objp->total_ht;
						$total_qty += $objp->qty;
					}
				}
			}
			
			$sql .= $db->plimit($conf->liste_limit + 1, $offset);
			
			$result = $db->query($sql);
			if ($result)
			{
				$num = $db->num_rows($result);
				
				if (! empty($id))
					$option .= '&amp;id=' . $product->id;
				if (! empty($search_month))
					$option .= '&amp;search_month=' . $search_month;
				if (! empty($search_year))
					$option .= '&amp;search_year=' . $search_year;
				
				print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $product->id . '" name="search_form">' . "\n";
				if (! empty($sortfield))
					print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
				if (! empty($sortorder))
					print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
				if (! empty($page)) {
					print '<input type="hidden" name="page" value="' . $page . '"/>';
					$option .= '&amp;page=' . $page;
				}

				print_barre_liste($langs->trans("SuppliersInvoices"), $page, $_SERVER["PHP_SELF"], "&amp;id=$product->id", $sortfield, $sortorder, '', $num, $totalrecords, '');
				print '<div class="liste_titre">';
				print $langs->trans('Period') . ' (' . $langs->trans("DateInvoice") . ') - ';
				print $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '"> ';
				print $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);
				print '<div style="vertical-align: middle; display: inline-block">';
				print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
				print '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
				print '</div>';
				print '</div>';
				
				$i = 0;
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre">';
				print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "s.rowid", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("Company"), $_SERVER["PHP_SELF"], "s.nom", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("SupplierCode"), $_SERVER["PHP_SELF"], "s.code_client", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("DateInvoice"), $_SERVER["PHP_SELF"], "f.datef", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("Qty"), $_SERVER["PHP_SELF"], "d.qty", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("AmountHT"), $_SERVER["PHP_SELF"], "f.total_ht", "", $option, 'align="right"', $sortfield, $sortorder);
				print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "f.paye,f.fk_statut", "", $option, 'align="right"', $sortfield, $sortorder);
				print "</tr>\n";
				
				if ($num > 0)
				{
					$var = True;
					while ($i < $num && $i < $conf->liste_limit)
					{
						$objp = $db->fetch_object($result);
						$var = ! $var;
						
						print '<tr ' . $bc[$var] . '>';
						print '<td>';
						$supplierinvoicestatic->id = $objp->facid;
						$supplierinvoicestatic->ref = $objp->facnumber;
						print $supplierinvoicestatic->getNomUrl(1);
						print "</td>\n";
						$societestatic->fetch($objp->socid);
						print '<td>' . $societestatic->getNomUrl(1) . '</td>';
						print "<td>" . $objp->code_client . "</td>\n";
                   		print '<td align="center">';
						print dol_print_date($db->jdate($objp->datef)) . "</td>";
						print '<td align="center">' . $objp->qty . "</td>\n";
						print '<td align="right">' . price($objp->total_ht) . "</td>\n";
						print '<td align="right">' . $supplierinvoicestatic->LibStatut($objp->paye, $objp->statut, 5) . '</td>';
						print "</tr>\n";
						$i ++;
						
						if (! empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
							$total_ht += $objp->total_ht;
							$total_qty += $objp->qty;
						}
					}
				}
				print '<tr class="liste_total">';
				print '<td>' . $langs->trans('Total') . '</td>';
				print '<td colspan="3"></td>';
				print '<td align="center">' . $total_qty . '</td>';
				print '<td align="right">' . price($total_ht) . '</td>';
				print '<td></td>';
				print "</table>";
				print '</form>';
				print '<br>';
			} else {
				dol_print_error($db);
			}
			$db->free($result);
		}
	}
} else {
	dol_print_error();
}

llxFooter();
$db->close();
