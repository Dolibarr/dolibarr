<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2020 Floiran Henry <florian.henry@scopen.fr>
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
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array('mrp', 'products', 'companies'));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('productstatscontract'));

$mesg = '';
$option = '';

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = "DESC";
if (!$sortfield) $sortfield = "b.date_valid";


/*
 * View
 */

$form = new Form($db);

if ($id > 0 || !empty($ref))
{
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	$object = $product;

	$parameters = array('id'=>$id);
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $product, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	llxHeader("", "", $langs->trans("CardProduct".$product->type));

	if ($result > 0)
	{
		$head = product_prepare_head($product);
		$titre = $langs->trans("CardProduct".$product->type);
		$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');
		print dol_get_fiche_head($head, 'referers', $titre, -1, $picto);

		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $product, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$shownav = 1;
		if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

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

		//Calcul total qty and amount for global if full scan list
		$total_qty_toconsume = 0;
		$total_qty_toproduce = 0;
		$bom_data_result = array();


		//Qauntity  to produce
		$sql = "SELECT b.rowid as rowid, b.ref, b.status, b.date_valid,";
		$sql .= " b.qty as qty_toproduce";
		$sql .= " FROM ".MAIN_DB_PREFIX."bom_bom as b";
		$sql .= " WHERE ";
		$sql .= " b.entity IN (".getEntity('bom').")";
		$sql .= " AND b.fk_product =".$product->id;
		$sql .= $db->order($sortfield, $sortorder);

		// Count total nb of records
		$totalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $db->query($sql);
			if ($result) {
				$totalofrecords = $db->num_rows($result);
				while ($objp = $db->fetch_object($result)) {
					$total_qty_toproduce += $objp->qty_toproduce;
				}
			} else {
				dol_print_error($db);
			}
		}
		$sql .= $db->plimit($limit + 1, $offset);

		$result = $db->query($sql);
		if ($result) {
			$bomtmp = new BOM($db);
			$num = $db->num_rows($result);
			$i = 0;
			if ($num > 0) {
				while ($i < min($num, $limit)) {
					$objp = $db->fetch_object($result);
					$bomtmp->id = $objp->rowid;
					$bomtmp->ref = $objp->ref;
					$bom_data_result[$objp->rowid]['link'] = $bomtmp->getNomUrl(1, 'production');
					$bom_data_result[$objp->rowid]['qty_toproduce'] += ($objp->qty_toproduce > 0 ? $objp->qty_toproduce : 0);
					$bom_data_result[$objp->rowid]['qty_toconsume'] = 0;
					$bom_data_result[$objp->rowid]['date_valid'] = dol_print_date($db->jdate($objp->date_valid), 'dayhour');
					$bom_data_result[$objp->rowid]['status'] = $bomtmp->LibStatut($objp->status, 5);
					$i++;
				}
			}
		} else {
			dol_print_error($db);
		}
		$db->free($result);

		//Qauntity  to consume
		$sql = "SELECT b.rowid as rowid, b.ref, b.status, b.date_valid,";
		$sql .= " SUM(bl.qty) as qty_toconsume";
		$sql .= " FROM ".MAIN_DB_PREFIX."bom_bom as b";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."bom_bomline as bl ON bl.fk_bom=b.rowid";
		$sql .= " WHERE ";
		$sql .= " b.entity IN (".getEntity('bom').")";
		$sql .= " AND bl.fk_product=".$product->id;
		$sql .= " GROUP BY b.rowid, b.ref, b.date_valid, b.status";
		$sql .= $db->order($sortfield, $sortorder);

		// Count total nb of records
		$totalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $db->query($sql);
			if ($result) {
				$totalofrecords = $db->num_rows($result);
				while ($objp = $db->fetch_object($result)) {
					$total_qty_toconsume += $objp->qty_toconsume;
				}
			} else {
				dol_print_error($db);
			}
		}
		$sql .= $db->plimit($limit + 1, $offset);

		$result = $db->query($sql);
		if ($result) {
			$bomtmp = new BOM($db);
			$num = $db->num_rows($result);
			$i = 0;
			if ($num > 0) {
				while ($i < min($num, $limit)) {
					$objp = $db->fetch_object($result);
					$bomtmp->id = $objp->rowid;
					$bomtmp->ref = $objp->ref;

					if (!array_key_exists($objp->rowid, $bom_data_result)) {
						$bom_data_result[$objp->rowid]['link'] = $bomtmp->getNomUrl(1, 'production');
						$bom_data_result[$objp->rowid]['qty_toproduce'] = 0;
						$bom_data_result[$objp->rowid]['qty_toconsume'] += ($objp->qty_toconsume > 0 ? $objp->qty_toconsume : 0);
						$bom_data_result[$objp->rowid]['date_valid'] = dol_print_date($db->jdate($objp->date_valid), 'dayhour');
						$bom_data_result[$objp->rowid]['status'] = $bomtmp->LibStatut($objp->status, 5);
					} else {
						$bom_data_result[$objp->rowid]['qty_toconsume'] += ($objp->qty_toconsume > 0 ? $objp->qty_toconsume : 0);
					}
					$i++;
				}
			}
		} else {
			dol_print_error($db);
		}
		$db->free($result);


		if ($limit > 0 && $limit != $conf->liste_limit) $option .= '&limit='.urlencode($limit);
		if (!empty($id)) $option .= '&id='.$product->id;
		if (!empty($search_month)) $option .= '&search_month='.urlencode($search_month);
		if (!empty($search_year)) $option .= '&search_year='.urlencode($search_year);

		print '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$product->id.'" name="search_form">'."\n";
		if (!empty($sortfield))
			print '<input type="hidden" name="sortfield" value="'.$sortfield.'"/>';
		if (!empty($sortorder))
			print '<input type="hidden" name="sortorder" value="'.$sortorder.'"/>';

		print_barre_liste($langs->trans("BOMs"), $page, $_SERVER["PHP_SELF"], $option, $sortfield, $sortorder, '', count($bom_data_result), count($bom_data_result), '', 0, '', '', $limit, 0, 0, 1);

		if (!empty($page)) $option .= '&page='.urlencode($page);

		print '<div class="div-table-responsive">';
		print '<table class="tagtable liste listwithfilterbefore" width="100%">';

		print '<tr class="liste_titre">';
		print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "b.rowid", "", "&amp;id=".$product->id, '', $sortfield, $sortorder);
		print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "b.date_valid", "", "&amp;id=".$product->id, 'align="center"', $sortfield, $sortorder);
		print_liste_field_titre("ToConsume", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("QtyToProduce", $_SERVER["PHP_SELF"], "", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "b.status", "", "&amp;id=".$product->id, '', $sortfield, $sortorder, 'center ');
		print "</tr>\n";

		if (!empty($bom_data_result)) {
			foreach ($bom_data_result as $data)
			{
				print '<tr class="oddeven">';
				print '<td>';
				print $data['link'];
				print "</td>\n";
				print "<td align=\"center\">";
				print $data['date_valid']."</td>";
				print '<td class="center">'.$data['qty_toconsume'].'</td>';
				print '<td class="center">'.$data['qty_toproduce'].'</td>';
				print '<td class="center">'.$data['status'].'</td>';
				print "</tr>\n";
			}
			print '</table>';
			print '</div>';
			print '</form>';
		}
	}
} else {
	dol_print_error();
}

// End of page
llxFooter();
$db->close();
