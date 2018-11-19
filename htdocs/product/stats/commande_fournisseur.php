<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 * \file htdocs/product/stats/commande_fournisseur.php
 * \ingroup product service commande
 * \brief Page des stats des commandes fournisseurs pour un produit
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('orders', 'products', 'companies'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$socid = '';
if (! empty($user->societe_id))
	$socid = $user->societe_id;
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array (
		'productstatssupplyorder'
));

$mesg = '';

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder)
	$sortorder = "DESC";
if (! $sortfield)
	$sortfield = "c.date_commande";
$search_month = GETPOST('search_month', 'aplha');
$search_year = GETPOST('search_year', 'int');

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter','alpha')) {
	$search_month = '';
	$search_year = '';
}


/*
 * View
 */

$supplierorderstatic = new CommandeFournisseur($db);
$societestatic = new Societe($db);

$form = new Form($db);
$formother = new FormOther($db);

if ($id > 0 || ! empty($ref)) {
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$object = $product;

	$parameters = array ('id' => $id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $product, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0)
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	llxHeader("", "", $langs->trans("CardProduct" . $product->type));

	if ($result > 0)
	{
		$head = product_prepare_head($product);
		$titre = $langs->trans("CardProduct" . $product->type);
		$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');
		dol_fiche_head($head, 'referers', $titre, -1, $picto);

		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $product, $action); // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        $shownav = 1;
        if ($user->societe_id && ! in_array('product', explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav=0;

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

        print '<div class="fichecenter">';

        print '<div class="underbanner clearboth"></div>';
        print '<table class="border tableforfield" width="100%">';

        $nboflines = show_stats_for_company($product, $socid);

		print "</table>";

        print '</div>';
        print '<div style="clear:both"></div>';

		dol_fiche_end();


		if ($user->rights->fournisseur->commande->lire)
		{
			$sql = "SELECT DISTINCT s.nom as name, s.rowid as socid, s.code_client,";
			$sql .= " c.rowid, d.total_ht as total_ht, c.ref,";
			$sql .= " c.date_commande, c.fk_statut as statut, c.rowid as commandeid, d.rowid, d.qty";
			if (! $user->rights->societe->client->voir && ! $socid)
				$sql .= ", sc.fk_soc, sc.fk_user ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
			$sql .= ", " . MAIN_DB_PREFIX . "commande_fournisseur as c";
			$sql .= ", " . MAIN_DB_PREFIX . "commande_fournisseurdet as d";
			if (! $user->rights->societe->client->voir && ! $socid)
				$sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
			$sql .= " WHERE c.fk_soc = s.rowid";
			$sql .= " AND c.entity = " . $conf->entity;
			$sql .= " AND d.fk_commande = c.rowid";
			$sql .= " AND d.fk_product =" . $product->id;
			if (! empty($search_month))
				$sql .= ' AND MONTH(c.date_commande) IN (' . $search_month . ')';
			if (! empty($search_year))
				$sql .= ' AND YEAR(c.date_commande) IN (' . $search_year . ')';
			if (! $user->rights->societe->client->voir && ! $socid)
				$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
			if ($socid)
				$sql .= " AND c.fk_soc = " . $socid;
			$sql.= $db->order($sortfield, $sortorder);

			// Calcul total qty and amount for global if full scan list
			$total_ht = 0;
			$total_qty = 0;

			// Count total nb of records
			$totalofrecords = '';
			if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
			{
				$result = $db->query($sql);
				$totalofrecords = $db->num_rows($result);
			}

			$sql .= $db->plimit($limit + 1, $offset);

			$result = $db->query($sql);
			if ($result) {
				$num = $db->num_rows($result);

				if (! empty($id))
					$option .= '&amp;id=' . $product->id;
				if (! empty($search_month))
					$option .= '&amp;search_month=' . $search_month;
				if (! empty($search_year))
					$option .= '&amp;search_year=' . $search_year;
				if ($limit > 0 && $limit != $conf->liste_limit) $option.='&limit='.urlencode($limit);

				print '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $product->id . '" name="search_form">' . "\n";
				if (! empty($sortfield))
					print '<input type="hidden" name="sortfield" value="' . $sortfield . '"/>';
				if (! empty($sortorder))
					print '<input type="hidden" name="sortorder" value="' . $sortorder . '"/>';
				if (! empty($page)) {
					print '<input type="hidden" name="page" value="' . $page . '"/>';
					$option .= '&amp;page=' . $page;
				}

				print_barre_liste($langs->trans("SuppliersOrders"), $page, $_SERVER["PHP_SELF"], "&amp;id=".$product->id, $sortfield, $sortorder, '', $num, $totalofrecords, '', 0, '', '', $limit);
                print '<div class="liste_titre liste_titre_bydiv centpercent">';
                print '<div class="divsearchfield">';
				print $langs->trans('Period') . ' (' . $langs->trans("OrderDate") . ') - ';
				print $langs->trans('Month') . ':<input class="flat" type="text" size="4" name="search_month" value="' . $search_month . '"> ';
				print $langs->trans('Year') . ':' . $formother->selectyear($search_year ? $search_year : - 1, 'search_year', 1, 20, 5);
				print '<div style="vertical-align: middle; display: inline-block">';
				print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
				print '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
				print '</div>';
				print '</div>';
				print '</div>';

				$i = 0;
                print '<div class="div-table-responsive">';
				print '<table class="tagtable liste listwithfilterbefore" width="100%">';
				print '<tr class="liste_titre">';
				print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "c.rowid", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("SupplierCode", $_SERVER["PHP_SELF"], "s.code_client", "", $option, '', $sortfield, $sortorder);
				print_liste_field_titre("OrderDate", $_SERVER["PHP_SELF"], "c.date_commande", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre("Qty", $_SERVER["PHP_SELF"], "d.qty", "", $option, 'align="center"', $sortfield, $sortorder);
				print_liste_field_titre("AmountHT", $_SERVER["PHP_SELF"], "c.total_ht", "", $option, 'align="right"', $sortfield, $sortorder);
				print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "c.fk_statut", "", $option, 'align="right"', $sortfield, $sortorder);
				print "</tr>\n";

				if ($num > 0)
				{
					while ($i < min($num, $limit))
					{
						$objp = $db->fetch_object($result);

						$total_ht+=$objp->total_ht;
						$total_qty+=$objp->qty;

						$supplierorderstatic->id = $objp->commandeid;
						$supplierorderstatic->ref = $objp->ref;
						$supplierorderstatic->statut = $objp->statut;
						$societestatic->fetch($objp->socid);

						print '<tr class="oddeven">';
						print '<td>';
						print $supplierorderstatic->getNomUrl(1);
						print "</td>\n";
						print '<td>' . $societestatic->getNomUrl(1) . '</td>';
						print "<td>" . $objp->code_client . "</td>\n";
						print '<td align="center">';
						print dol_print_date($db->jdate($objp->date_commande), 'dayhour') . "</td>";
						print '<td align="center">' . $objp->qty . "</td>\n";
						print '<td align="right">' . price($objp->total_ht) . "</td>\n";
						print '<td align="right">' . $supplierorderstatic->getLibStatut(4) . '</td>';
						print "</tr>\n";
						$i++;
					}
				}
				print '<tr class="liste_total">';
				if ($num < $limit) print '<td align="left">'.$langs->trans("Total").'</td>';
				else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
				print '<td colspan="3"></td>';
				print '<td align="center">' . $total_qty . '</td>';
				print '<td align="right">' . price($total_ht) . '</td>';
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
