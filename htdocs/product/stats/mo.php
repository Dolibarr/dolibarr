<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2021 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/product/stats/mo.php
 *       \ingroup    product mo
 *       \brief      Page of MO referring product
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array('mrp', 'products', 'companies'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productstatsmo'));

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
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
	$sortfield = "c.date_valid";
}

$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);


/*
 * View
 */

$staticmo = new Mo($db);
$staticmoligne = new MoLine($db);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$object = $product;

	$parameters = array('id'=>$id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $product, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	llxHeader("", "", $langs->trans("CardProduct".$product->type));

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


		$now = dol_now();

		$sql = "SELECT";
		$sql .= " sum(".$db->ifsql("cd.role='toconsume'", "cd.qty", 0).') as nb_toconsume,';
		$sql .= " sum(".$db->ifsql("cd.role='consumed'", "cd.qty", 0).') as nb_consumed,';
		$sql .= " sum(".$db->ifsql("cd.role='toproduce'", "cd.qty", 0).') as nb_toproduce,';
		$sql .= " sum(".$db->ifsql("cd.role='produced'", "cd.qty", 0).') as nb_produced,';
		$sql .= " c.rowid as rowid, c.ref, c.date_valid, c.status";
		//$sql .= " s.nom as name, s.rowid as socid, s.code_client";
		$sql .= " FROM ".MAIN_DB_PREFIX."mrp_mo as c";
		$sql .= ", ".MAIN_DB_PREFIX."mrp_production as cd";
		$sql .= " WHERE c.rowid = cd.fk_mo";
		$sql .= " AND c.entity IN (".getEntity('mo').")";
		$sql .= " AND cd.fk_product = ".((int) $product->id);
		if ($socid) {
			$sql .= " AND s.rowid = ".((int) $socid);
		}
		$sql .= " GROUP BY c.rowid, c.ref, c.date_valid, c.status";
		//$sql .= ", s.nom, s.rowid, s.code_client";
		$sql .= $db->order($sortfield, $sortorder);

		//Calcul total qty and amount for global if full scan list
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

			print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$product->id.'" name="search_form">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			if (!empty($sortfield)) {
				print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
			}
			if (!empty($sortorder)) {
				print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';
			}

			print_barre_liste($langs->trans("MOs"), $page, $_SERVER["PHP_SELF"], $option, $sortfield, $sortorder, '', $num, $totalofrecords, '', 0, '', '', $limit, 0, 0, 1);

			if (!empty($page)) {
				$option .= '&page='.urlencode($page);
			}

			$i = 0;
			print '<div class="div-table-responsive">';
			print '<table class="tagtable liste listwithfilterbefore" width="100%">';

			print '<tr class="liste_titre">';
			print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "c.rowid", "", "&amp;id=".$product->id, '', $sortfield, $sortorder);
			//print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", "&amp;id=".$product->id, '', $sortfield, $sortorder);
			print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "c.date_valid", "", "&amp;id=".$product->id, 'align="center"', $sortfield, $sortorder);
			//print_liste_field_titre("AmountHT"),$_SERVER["PHP_SELF"],"c.amount","","&amp;id=".$product->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre("ToConsume", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'center ');
			print_liste_field_titre("QtyAlreadyConsumed", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'center ');
			print_liste_field_titre("QtyToProduce", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'center ');
			print_liste_field_titre("QtyAlreadyProduced", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'center ');
			print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "b.status", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'right ');
			print "</tr>\n";

			$motmp = new Mo($db);

			if ($num > 0) {
				while ($i < min($num, $limit)) {
					$objp = $db->fetch_object($result);

					$motmp->id = $objp->rowid;
					$motmp->ref = $objp->ref;
					$motmp->status = $objp->status;

					print '<tr class="oddeven">';
					print '<td>';
					print $motmp->getNomUrl(1, 'production');
					print "</td>\n";
					print "<td align=\"center\">";
					print dol_print_date($db->jdate($objp->date_valid), 'dayhour')."</td>";
					//print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
					//print '<td align="right">';
					print '<td class="center">'.($objp->nb_toconsume > 0 ? $objp->nb_toconsume : '').'</td>';
					print '<td class="center">'.($objp->nb_consumed > 0 ? $objp->nb_consumed : '').'</td>';
					print '<td class="center">'.($objp->nb_toproduce > 0 ? $objp->nb_toproduce : '').'</td>';
					print '<td class="center">'.($objp->nb_produced > 0 ? $objp->nb_produced : '').'</td>';
					//$mostatic->LibStatut($objp->statut,5).'</td>';
					print '<td class="right">'.$motmp->getLibStatut(2).'</td>';
					print "</tr>\n";
					$i++;
				}
			}

			print '</table>';
			print '</div>';
			print '</form>';
		} else {
			dol_print_error($db);
		}
		$db->free($result);
	}
} else {
	dol_print_error();
}

// End of page
llxFooter();
$db->close();
