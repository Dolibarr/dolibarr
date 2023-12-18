<?php
/* Copyright (C) 2003-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2019		Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2020		Tobias Sekan			<tobias.sekan@startmail.com>
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
 *  \file		htdocs/comm/propal/index.php
 *	\ingroup	propal
 *	\brief	Home page of proposal area
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager = new HookManager($db);
$hookmanager->initHooks(array('proposalindex'));

// Load translation files required by the page
$langs->loadLangs(array('propal', 'companies'));

$now = dol_now();
$max = 5;

// Security check
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

restrictedArea($user, 'propal');


/*
 * View
 */

$propalstatic = new Propal($db);
$companystatic = new Societe($db);
$form = new Form($db);
$formfile = new FormFile($db);
$help_url = "EN:Module_Commercial_Proposals|FR:Module_Propositions_commerciales|ES:Módulo_Presupuestos";

llxHeader("", $langs->trans("ProspectionArea"), $help_url);

print load_fiche_titre($langs->trans("ProspectionArea"), '', 'propal');

print '<div class="fichecenter">';
print '<div class="fichethirdleft">';

$tmp = getCustomerProposalPieChart($socid);
if ($tmp) {
	print $tmp;
	print '<br>';
}

/*
 * Draft proposals
 */
if (isModEnabled("propal")) {
	$sql = "SELECT p.rowid, p.ref, p.ref_client, p.total_ht, p.total_tva, p.total_ttc";
	$sql .= ", s.rowid as socid, s.nom as name, s.client, s.canvas, s.code_client, s.code_fournisseur, s.email, s.entity, s.code_compta";
	$sql .= " FROM ".MAIN_DB_PREFIX."propal as p";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND p.fk_soc = s.rowid";
	$sql .= " AND p.fk_statut =".Propal::STATUS_DRAFT;
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND p.fk_soc = ".((int) $socid);
	}

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("DraftPropals", "comm/propal/list.php", "search_status=".Propal::STATUS_DRAFT, 2, $num);

		$total = 0;
		if ($num) {
			$i = 0;

			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				$propalstatic->id = $obj->rowid;
				$propalstatic->ref = $obj->ref;
				$propalstatic->ref_client = $obj->ref_client;
				$propalstatic->total_ht = $obj->total_ht;
				$propalstatic->total_tva = $obj->total_tva;
				$propalstatic->total_ttc = $obj->total_ttc;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$companystatic->client = $obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->canvas = $obj->canvas;
				$companystatic->entity = $obj->entity;
				$companystatic->email = $obj->email;
				$companystatic->code_compta = $obj->code_compta;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$propalstatic->getNomUrl(1).'</td>';
				print '<td class="nowrap">'.$companystatic->getNomUrl(1, 'customer', 16).'</td>';
				print '<td class="nowrap right">'.price(getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '</tr>';

				$i++;
				$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
			}
		}

		addSummaryTableLine(3, $num, $nbofloop, $total, "NoProposal");
		finishSimpleTable(true);
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div>';

print '<div class="fichetwothirdright">';

/*
 * Last modified proposals
 */

$sql = "SELECT c.rowid, c.entity, c.ref, c.fk_statut, date_cloture as datec";
$sql .= ", s.nom as socname, s.rowid as socid, s.canvas, s.client, s.email, s.code_compta";
$sql .= " FROM ".MAIN_DB_PREFIX."propal as c";
$sql .= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE c.entity IN (".getEntity($propalstatic->element).")";
$sql .= " AND c.fk_soc = s.rowid";
//$sql.= " AND c.fk_statut > 2";
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " ORDER BY c.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	startSimpleTable($langs->trans("LastModifiedProposals", $max), "", "", 3);

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$propalstatic->id = $obj->rowid;
			$propalstatic->ref = $obj->ref;

			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->socname;
			$companystatic->client = $obj->client;
			$companystatic->canvas = $obj->canvas;
			$companystatic->email = $obj->email;
			$companystatic->code_compta = $obj->code_compta;

			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->propal->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;

			print '<tr class="oddeven">';

			print '<td width="20%" class="nowrap">';
			print '<table class="nobordernopadding">';
			print '<tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowrap">'.$propalstatic->getNomUrl(1).'</td>';
			print '<td width="16" class="nobordernopadding nowrap"></td>';
			print '<td width="16" class="nobordernopadding right">'.$formfile->getDocumentsLink($propalstatic->element, $filename, $filedir).'</td>';
			print '</tr>';
			print '</table>';
			print '</td>';

			print '<td>'.$companystatic->getNomUrl(1, 'customer').'</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datec), 'day').'</td>';
			print '<td class="right">'.$propalstatic->LibStatut($obj->fk_statut, 3).'</td>';

			print '</tr>';

			$i++;
		}
	}

	finishSimpleTable(true);
	$db->free($resql);
} else {
	dol_print_error($db);
}


/*
 * Open (validated) proposals
 */
if (isModEnabled("propal") && $user->hasRight('propal', 'lire')) {
	$sql = "SELECT s.nom as socname, s.rowid as socid, s.canvas, s.client, s.email, s.code_compta";
	$sql .= ", p.rowid as propalid, p.entity, p.total_ttc, p.total_ht, p.ref, p.fk_statut, p.datep as dp, p.fin_validite as dfv";
	$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
	$sql .= ", ".MAIN_DB_PREFIX."propal as p";
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE p.fk_soc = s.rowid";
	$sql .= " AND p.entity IN (".getEntity($propalstatic->element).")";
	$sql .= " AND p.fk_statut = ".Propal::STATUS_VALIDATED;
	if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
	$sql .= " ORDER BY p.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$nbofloop = min($num, (!getDolGlobalString('MAIN_MAXLIST_OVERLOAD') ? 500 : $conf->global->MAIN_MAXLIST_OVERLOAD));
		startSimpleTable("ProposalsOpened", "comm/propal/list.php", "search_status=".Propal::STATUS_VALIDATED, 4, $num);

		if ($num > 0) {
			$i = 0;
			while ($i < $nbofloop) {
				$obj = $db->fetch_object($resql);

				$propalstatic->id = $obj->propalid;
				$propalstatic->ref = $obj->ref;

				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->socname;
				$companystatic->client = $obj->client;
				$companystatic->canvas = $obj->canvas;
				$companystatic->email = $obj->email;
				$companystatic->code_compta = $obj->code_compta;

				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->propal->multidir_output[$obj->entity].'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->propalid;

				$warning = ($db->jdate($obj->dfv) < ($now - $conf->propal->cloture->warning_delay)) ? img_warning($langs->trans("Late")) : '';

				print '<tr class="oddeven">';

				// Ref
				print '<td class="nowrap" width="140">';
				print '<table class="nobordernopadding">';
				print '<tr class="nocellnopadd">';
				print '<td class="nobordernopadding nowrap">'.$propalstatic->getNomUrl(1).'</td>';
				print '<td width="18" class="nobordernopadding nowrap">'.$warning.'</td>';
				print '<td width="16" align="center" class="nobordernopadding">'.$formfile->getDocumentsLink($propalstatic->element, $filename, $filedir).'</td>';
				print '</tr>';
				print '</table>';
				print '</td>';

				print '<td class="left">'.$companystatic->getNomUrl(1, 'customer', 44).'</td>';
				print '<td class="right">'.dol_print_date($db->jdate($obj->dp), 'day').'</td>';
				print '<td class="right">'.price(getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc).'</td>';
				print '<td align="center" width="14">'.$propalstatic->LibStatut($obj->fk_statut, 3).'</td>';

				print '</tr>';

				$i++;
				$total += (getDolGlobalString('MAIN_DASHBOARD_USE_TOTAL_HT') ? $obj->total_ht : $obj->total_ttc);
			}
		}

		addSummaryTableLine(5, $num, $nbofloop, $total, "None", true);
		finishSimpleTable(true);
		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

/*
 * Proposals to process
 */

/*
if (isModEnabled('propal'))
{
	$sql = "SELECT c.rowid, c.ref, c.fk_statut, s.nom as name, s.rowid as socid";
	$sql.=" FROM ".MAIN_DB_PREFIX."propal as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (empty($user->rights->societe->client->voir) && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND c.fk_statut = 1";
	if ($socid) $sql.= " AND c.fk_soc = ".((int) $socid);
	if (empty($user->rights->societe->client->voir) && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .((int) $user->id);
	$sql.= " ORDER BY c.rowid DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("ProposalsToProcess").' <a href="'.DOL_URL_ROOT.'/commande/list.php?search_status=1"><span class="badge">'.$num.'</span></a></td></tr>';

		if ($num)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td class="nowrap">';

				$propalstatic->id=$obj->rowid;
				$propalstatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">';
				print $propalstatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" class="nobordernopadding right">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($propalstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->name,24).'</a></td>';

				print '<td class="right">'.$propalstatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';

				print '</tr>';
				$i++;
			}
		}

		print "</table>";
		print "</div><br>";
	}
	else dol_print_error($db);
}
*/

/*
 * Proposal that are in a shipping process
 */

/*
if (isModEnabled('propal'))
{
	$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.facture, s.nom as name, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (empty($user->rights->societe->client->voir) && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND c.fk_statut = 2 ";
	if ($socid) $sql.= " AND c.fk_soc = ".((int) $socid);
	if (empty($user->rights->societe->client->voir) && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .((int) $user->id);
	$sql.= " ORDER BY c.rowid DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("OnProcessOrders").' <a href="'.DOL_URL_ROOT.'/commande/list.php?search_status=2"><span class="badge">'.$num.'</span></a></td></tr>';

		if ($num)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td width="20%" class="nowrap">';

				$propalstatic->id=$obj->rowid;
				$propalstatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">';
				print $propalstatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" class="nobordernopadding right">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($propalstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->name.'</a></td>';

				print '<td class="right">'.$propalstatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';

				print '</tr>';
				$i++;
			}
		}
		print "</table>";
		print "</div><br>";
	}
	else dol_print_error($db);
}
*/

print '</div>';
print '</div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardPropals', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
