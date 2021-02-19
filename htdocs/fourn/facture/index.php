<?php
/* Copyright (C) 2020	Tobias Sekan	<tobias.sekan@startmail.com>
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
 *	\file		htdocs/forun/facture/index.php
*	\ingroup	facture
 *	\brief		Home page of customer invoices area
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

// Security check
restrictedArea($user, 'fournisseur', 0, '', 'facture');

// Load translation files required by the page
$langs->loadLangs(['bills', 'boxes']);

// Filter to show only result of one supplier
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

// Maximum elements of the tables
$maxDraftCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;
$maxLatestEditCount = 5;
$maxOpenCount = empty($conf->global->MAIN_MAXLIST_OVERLOAD) ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD;

/*
* View
*/

llxHeader("", $langs->trans("SupplierInvoicesArea"), 'EN:Suppliers_Invoices|FR:FactureFournisseur|ES:Facturas_de_proveedores');

print load_fiche_titre($langs->trans("SupplierInvoicesArea"), '', 'supplier_invoice');

print '<div class="fichecenter">';

print '<div class="fichethirdleft">';

// This is useless due to the global search combo
if (!empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))
{
	print getAreaSearchFrom();
	print '<br>';
}

print getPieChart($socid);
print '<br>';
print getDraftTable($maxDraftCount, $socid);

print '</div>';

print '<div class="fichetwothirdright">';
print '<div class="ficheaddleft">';

print getLatestEditTable($maxLatestEditCount, $socid);
print '<br>';
print getOpenTable($maxOpenCount, $socid);

print '</div>';
print '</div>';

print '</div>';

// End of page
llxFooter();
$db->close();

/**
 * Return a HTML string that contains a additional search form
 *
 * @return string A HTML string that contains a additional search form
 */
function getAreaSearchFrom()
{
	global $langs;

	$result = '<form method="post" action="'.DOL_URL_ROOT.'/compta/facture/list.php">';
	$result .= '<div class="div-table-responsive-no-min">';
	$result .= '<input type="hidden" name="token" value="'.newToken().'">';
	$result .= '<table class="noborder nohover centpercent">';

	$result .= '<tr class="liste_titre">';
	$result .= '<td colspan="3">'.$langs->trans("Search").'</td>';
	$result .= '</tr>';

	$result .= '<tr class="oddeven">';
	$result .= '<td>'.$langs->trans("Invoice").':</td><td><input type="text" class="flat" name="sall" size=18></td>';
	$result .= '<td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
	$result .= '</tr>';

	$result .= "</table>";
	$result .= "</div>";
	$result .= "</form>";

	return $result;
}

/**
 * Return a HTML table that contains a pie chart of supplier invoices
 *
 * @param	int		$socid		(Optional) Show only results from the supplier with this id
 * @return	string				A HTML table that contains a pie chart of supplier invoices
 */
function getPieChart($socid = 0)
{
	global $conf, $db, $langs, $user;

	$sql = "SELECT count(f.rowid), f.fk_statut";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE f.fk_soc = s.rowid";
	$sql .= " AND f.entity IN (".getEntity('facture_fourn').")";
	if ($user->socid) $sql .= ' AND f.fk_soc = '.$user->socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	$sql .= " GROUP BY f.fk_statut";

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		return '';
	}

	$num = $db->num_rows($resql);
	$i = 0;

	$total = 0;
	$vals = [];

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		if ($row)
		{
			$vals[$row[1]] = $row[0];
			$total += $row[0];
		}

		$i++;
	}

	$db->free($resql);

	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder nohover centpercent">';

	$result .= '<tr class="liste_titre">';
	$result .= '<td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("SupplierInvoice").'</td>';
	$result .= '</tr>';

	$objectstatic = new FactureFournisseur($db);
	$array = [FactureFournisseur::STATUS_DRAFT, FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_CLOSED, FactureFournisseur::STATUS_ABANDONED];
	$dataseries = [];

	foreach ($array as $status)
	{
		$objectstatic->statut = $status;
		$objectstatic->paye = $status == FactureFournisseur::STATUS_CLOSED ? -1 : 0;

		$dataseries[] = [$objectstatic->getLibStatut(1), (isset($vals[$status]) ? (int) $vals[$status] : 0)];
		if (!$conf->use_javascript_ajax)
		{
			$result .= '<tr class="oddeven">';
			$result .= '<td>'.$objectstatic->getLibStatut(0).'</td>';
			$result .= '<td class="right"><a href="list.php?statut='.$status.'">'.(isset($vals[$status]) ? $vals[$status] : 0).'</a></td>';
			$result .= '</tr>';
		}
	}

	if ($conf->use_javascript_ajax)
	{
		$dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		$dolgraph->setShowLegend(2);
		$dolgraph->setShowPercent(1);
		$dolgraph->SetType(['pie']);
		$dolgraph->setHeight('200');
		$dolgraph->draw('idgraphthirdparties');

		$result .= '<tr>';
		$result .= '<td align="center" colspan="2">'.$dolgraph->show($total ? 0 : 1).'</td>';
		$result .= '</tr>';
	}

	$result .= '<tr class="liste_total">';
	$result .= '<td>'.$langs->trans("Total").'</td>';
	$result .= '<td class="right">'.$total.'</td>';
	$result .= '</tr>';

	$result .= '</table>';
	$result .= '</div>';

	return $result;
}

/**
 * Return a HTML table that contains a list with supplier invoice drafts
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the supplier with this id
 * @return	string				A HTML table that contains a list with supplier invoice drafts
 */
function getDraftTable($maxCount = 500, $socid = 0)
{
	global $db, $langs, $user;

	$sql = "SELECT f.rowid, f.ref, s.nom as socname, s.rowid as socid, s.canvas, s.client, f.total_ttc";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE f.fk_soc = s.rowid";
	$sql .= " AND f.entity IN (".getEntity('facture_fourn').")";
	$sql .= " AND f.fk_statut = ".FactureFournisseur::STATUS_DRAFT;
	if ($socid) $sql .= " AND f.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	$sql .= $db->plimit($maxCount, 0);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		return '';
	}

	$num = $db->num_rows($resql);

	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder centpercent">';

	$result .= '<tr class="liste_titre">';
	$result .= '<td colspan="3">';
	$result .= $langs->trans("SuppliersDraftInvoices");
	$result .= ' <a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?search_status=0">';
	$result .= '<span class="badge">'.$num.'</span>';
	$result .= '</a>';
	$result .= '</td>';
	$result .= '</tr>';

	if ($num < 1)
	{
		$result .= '</table>';
		$result .= '</div>';
		return $result;
	}

	$objectstatic = new FactureFournisseur($db);
	$companystatic = new Societe($db);
	$nbofloop = min($num, $maxCount);
	$total = 0;
	$i = 0;

	while ($i < $nbofloop)
	{
		$obj = $db->fetch_object($resql);

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->socname;
		$companystatic->client = $obj->client;
		$companystatic->canvas = $obj->canvas;

		$result .= '<tr class="oddeven">';
		$result .= '<td class="nowrap">'.$objectstatic->getNomUrl(1).'</td>';
		$result .= '<td>'.$companystatic->getNomUrl(1, 'supplier', 24).'</td>';
		$result .= '<td class="right">'.price($obj->total_ttc).'</td>';
		$result .= '</tr>';

		$i++;
		$total += $obj->total_ttc;
	}

	if ($num > $nbofloop)
	{
		$result .= '<tr class="liste_total">';
		$result .= '<td colspan="3" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop)).'</td>';
		$result .= '</tr>';
	}
	elseif ($total > 0)
	{
		$result .= '<tr class="liste_total">';
		$result .= '<td colspan="2" class="right">'.$langs->trans("Total").'</td>';
		$result .= '<td class="right">'.price($total).'</td>';
		$result .= '</tr>';
	}

	$result .= '</table>';
	$result .= '</div>';
	return $result;
}

/**
 * Return a HTML table that contains a list with latest edited supplier invoices
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the supplier with this id
 * @return	string				A HTML table that contains a list with latest edited supplier invoices
 */
function getLatestEditTable($maxCount = 5, $socid = 0)
{
	global $conf, $db, $langs, $user;

	$sql = "SELECT f.rowid, f.entity, f.ref, f.fk_statut as status, f.paye, s.nom as socname, s.rowid as socid, s.canvas, s.client,";
	$sql .= " f.datec";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE f.fk_soc = s.rowid";
	$sql .= " AND f.entity IN (".getEntity('facture_fourn').")";
	if ($socid) $sql .= " AND f.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	$sql .= " ORDER BY f.tms DESC";
	$sql .= $db->plimit($maxCount, 0);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		return '';
	}

	$num = $db->num_rows($resql);

	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder centpercent">';
	$result .= '<tr class="liste_titre">';
	$result .= '<td colspan="4">'.$langs->trans("BoxTitleLastSupplierBills", $maxCount).'</td>';
	$result .= '</tr>';

	if ($num < 1)
	{
		$result .= '</table>';
		$result .= '</div>';
		return $result;
	}

	$objectstatic = new FactureFournisseur($db);
	$companystatic = new Societe($db);
	$formfile = new FormFile($db);
	$i = 0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		$objectstatic->id = $obj->rowid;
		$objectstatic->ref = $obj->ref;
		$objectstatic->paye = $obj->paye;
		$objectstatic->statut = $obj->status;

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->socname;
		$companystatic->client = $obj->client;
		$companystatic->canvas = $obj->canvas;

		$filename = dol_sanitizeFileName($obj->ref);
		$filedir = $conf->propal->multidir_output[$obj->entity].'/'.$filename;

		$result .= '<tr width="20%" class="nowrap">';

		$result .= '<td class="oddeven">';
		$result .= '<table class="nobordernopadding">';
		$result .= '<tr class="nocellnopadd">';

		$result .= '<td width="96" class="nobordernopadding nowrap">'.$objectstatic->getNomUrl(1).'</td>';
		$result .= '<td width="16" class="nobordernopadding nowrap">&nbsp;</td>';
		$result .= '<td width="16" class="nobordernopadding right">'.$formfile->getDocumentsLink($objectstatic->element, $filename, $filedir).'</td>';

		$result .= '</tr>';
		$result .= '</table>';
		$result .= '</td>';

		$result .= '<td>'.$companystatic->getNomUrl(1, 'supplier').'</td>';
		$result .= '<td>'.dol_print_date($db->jdate($obj->datec), 'day').'</td>';
		$result .= '<td class="right">'.$objectstatic->getLibStatut(5).'</td>';

		$result .= '</tr>';

		$i++;
	}

	$result .= '</table>';
	$result .= '</div>';
	return $result;
}

/**
 * Return a HTML table that contains a list with open (unpaid) supplier invoices
 *
 * @param	int		$maxCount	(Optional) The maximum count of elements inside the table
 * @param	int		$socid		(Optional) Show only results from the supplier with this id
 * @return	string				A HTML table that conatins a list with open (unpaid) supplier invoices
 */
function getOpenTable($maxCount = 500, $socid = 0)
{
	global $conf, $db, $langs, $user;

	$sql = "SELECT s.nom as socname, s.rowid as socid, s.canvas, s.client";
	$sql .= ", f.rowid as id, f.entity, f.total_ttc, f.total_ht, f.ref, f.fk_statut";
	$sql .= ", f.datef as df, f.date_lim_reglement as datelimite";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f";
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql .= " WHERE f.fk_soc = s.rowid";
	$sql .= " AND f.entity IN (".getEntity('facture_fourn').")";
	$sql .= " AND f.fk_statut = ".FactureFournisseur::STATUS_VALIDATED;
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql .= " AND s.rowid = ".$socid;
	$sql .= " ORDER BY f.rowid DESC";
	$sql .= $db->plimit($maxCount, 0);

	$resql = $db->query($sql);
	if (!$resql)
	{
		dol_print_error($db);
		return '';
	}

	$num = $db->num_rows($resql);

	$result = '<div class="div-table-responsive-no-min">';
	$result .= '<table class="noborder centpercent">';
	$result .= '<tr class="liste_titre">';
	$result .= '<td colspan="4">';
	$result .= $langs->trans("BillsCustomersUnpaid");
	$result .= ' <a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?search_status=1">';
	$result .= '<span class="badge">'.$num.'</span>';
	$result .= '</a>';
	$result .= '</td>';
	$result .= '</tr>';

	if ($num < 1)
	{
		$result .= '</table>';
		$result .= '</div>';
		return $result;
	}

	$objectstatic = new FactureFournisseur($db);
	$companystatic = new Societe($db);
	$formfile		= new FormFile($db);
	$nbofloop		= min($num, $maxCount);
	$now = dol_now();
	$total = 0;
	$i = 0;

	while ($i < $nbofloop)
	{
		$obj = $db->fetch_object($resql);

		$objectstatic->id = $obj->id;
		$objectstatic->ref = $obj->ref;

		$companystatic->id = $obj->socid;
		$companystatic->name = $obj->socname;
		$companystatic->client = $obj->client;
		$companystatic->canvas = $obj->canvas;

		$filename = dol_sanitizeFileName($obj->ref);
		$filedir = $conf->propal->multidir_output[$obj->entity].'/'.$filename;

		$result .= '<tr class="oddeven">';

		$result .= '<td class="nowrap" width="140">';
		$result .= '<table class="nobordernopadding">';
		$result .= '<tr class="nocellnopadd">';

		$result .= '<td class="nobordernopadding nowrap">'.$objectstatic->getNomUrl(1).'</td>';
		$result .= '<td width="18" class="nobordernopadding nowrap">';

		if ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay))
		{
			$result .= img_warning($langs->trans("Late"));
		}

		$result .= '</td>';

		$result .= '<td width="16" align="center" class="nobordernopadding">'.$formfile->getDocumentsLink($objectstatic->element, $filename, $filedir).'</td>';

		$result .= '</tr>';
		$result .= '</table>';
		$result .= '</td>';

		$result .= '<td class="left">'.$companystatic->getNomUrl(1, 'customer', 44).'</td>';
		$result .= '<td class="right">'.dol_print_date($db->jdate($obj->df), 'day').'</td>';
		$result .= '<td class="right">'.price($obj->total_ttc).'</td>';

		$result .= '</tr>';

		$i++;
		$total += $obj->total_ttc;
	}

	if ($num > $nbofloop)
	{
		$result .= '<tr class="liste_total">';
		$result .= '<td colspan="4" class="right">'.$langs->trans("XMoreLines", ($num - $nbofloop)).'</td>';
		$result .= '</tr>';
	}
	elseif ($total > 0)
	{
		$result .= '<tr class="liste_total">';
		$result .= '<td colspan="2" class="right">'.$langs->trans("Total").'</td>';
		$result .= '<td align="right">'.price($total).'</td>';
		$result .= '<td>&nbsp;</td>';
		$result .= '</tr>';
	}

	$result .= '</table>';
	$result .= '</div>';
	return $result;
}
