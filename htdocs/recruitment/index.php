<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		MDW						<mdeweerd@users.noreply.github.com>
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
 *	\file       recruitment/index.php
 *	\ingroup    recruitment
 *	\brief      Home page of recruitment top menu
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentcandidature.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/dashboard.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("recruitment", "boxes"));

$action = GETPOST('action', 'aZ09');

$NBMAX = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);
$max = getDolUserInt('MAIN_SIZE_SHORTLIST_LIMIT', getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5));
$now = dol_now();

$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
// if (! $user->hasRight('mymodule', 'myobject', 'read')) {
// 	accessforbidden();
// }
restrictedArea($user, 'recruitment', 0, 'recruitment_recruitmentjobposition', 'recruitmentjobposition', '', 'rowid');


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$staticrecruitmentjobposition = new RecruitmentJobPosition($db);
$staticrecruitmentcandidature = new RecruitmentCandidature($db);

llxHeader("", $langs->trans("RecruitmentArea"));

print load_fiche_titre($langs->trans("RecruitmentArea"), '', 'object_recruitmentjobposition');

print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Statistics
 */

$colors = getThemeBadgeStatusColors();

$sql = "SELECT COUNT(t.rowid) as nb, status";
$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as t";
$sql .= " WHERE t.entity IN (".getEntity('recruitmentjobposition').")";
$sql .= " GROUP BY t.status";
$sql .= " ORDER BY t.status ASC";
$resql = $db->query($sql);
if ($resql) {
	$vals = array();
	while ($obj = $db->fetch_object($resql)) {
		$vals[$obj->status] = $obj->nb;
	}
	$db->free($resql);

	$colorofstatus = array(
		RecruitmentJobPosition::STATUS_DRAFT => '-'.$colors[0],
		RecruitmentJobPosition::STATUS_VALIDATED => $colors[4],
		RecruitmentJobPosition::STATUS_RECRUITED => $colors[6],
		RecruitmentJobPosition::STATUS_CANCELED => $colors[9],
	);
	$series = array();
	foreach (array(0, 1, 3, 9) as $status) {
		$series[] = array(
			'label' => dol_html_entity_decode($staticrecruitmentjobposition->LibStatut($status, 1), ENT_QUOTES | ENT_HTML5),
			'labelnojs' => $staticrecruitmentjobposition->LibStatut($status, 0),
			'nb' => (isset($vals[$status]) ? (int) $vals[$status] : 0),
			'color' => $colorofstatus[$status],
			'url' => 'recruitmentjobposition_list.php?search_status='.$status,
		);
	}
	print getStatusPieChart($langs->trans("Statistics").' - '.$langs->trans("JobPositions"), $series, array('total' => false));
} else {
	dol_print_error($db);
}

$sql = "SELECT COUNT(t.rowid) as nb, status";
$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as t";
$sql .= " WHERE t.entity IN (".getEntity('recruitmentcandidature').")";
$sql .= " GROUP BY t.status";
$sql .= " ORDER BY t.status ASC";
$resql = $db->query($sql);
if ($resql) {
	$vals = array();
	while ($obj = $db->fetch_object($resql)) {
		$vals[$obj->status] = $obj->nb;
	}
	$db->free($resql);

	$colorofstatus = array(
		RecruitmentCandidature::STATUS_DRAFT => '-'.$colors[0],
		RecruitmentCandidature::STATUS_VALIDATED => $colors[1],
		RecruitmentCandidature::STATUS_CONTRACT_PROPOSED => $colors[4],
		RecruitmentCandidature::STATUS_CONTRACT_SIGNED => $colors[5],
		RecruitmentCandidature::STATUS_REFUSED => $colors[9],
		RecruitmentCandidature::STATUS_CANCELED => $colors[9],
	);
	$series = array();
	foreach (array(0, 1, 3, 5, 8, 9) as $status) {
		$series[] = array(
			'label' => dol_html_entity_decode($staticrecruitmentcandidature->LibStatut($status, 1), ENT_QUOTES | ENT_HTML5),
			'labelnojs' => $staticrecruitmentcandidature->LibStatut($status, 0),
			'nb' => (isset($vals[$status]) ? (int) $vals[$status] : 0),
			'color' => $colorofstatus[$status],
			'url' => 'recruitmentcandidature_list.php?search_status='.$status,
		);
	}
	print getStatusPieChart($langs->trans("Statistics").' - '.$langs->trans("RecruitmentCandidatures"), $series, array('graphid' => 'idgraphstatuscandidature', 'total' => false));
} else {
	dol_print_error($db);
}

print '<br>';

/* BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if (isModEnabled('recruitment') && $user->rights->recruitment->read) {
	$langs->load("orders");

	$sql = "SELECT c.rowid, c.ref, c.ref_client, c.total_ht, c.total_tva, c.total_ttc, s.rowid as socid, s.nom as name, s.client, s.canvas";
	$sql.= ", s.code_client";
	$sql.= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.fk_statut = 0";
	$sql.= " AND c.entity IN (".getEntity('commande').")";
	if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	if ($socid)	$sql.= " AND c.fk_soc = ".((int) $socid);

	$resql = $db->query($sql);
	if ($resql)
	{
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftOrders").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		if ($num > 0)
		{
			$i = 0;
			while ($i < $num)
			{

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';
				$orderstatic->id=$obj->rowid;
				$orderstatic->ref=$obj->ref;
				$orderstatic->ref_client=$obj->ref_client;
				$orderstatic->total_ht = $obj->total_ht;
				$orderstatic->total_tva = $obj->total_tva;
				$orderstatic->total_ttc = $obj->total_ttc;
				print $orderstatic->getNomUrl(1);
				print '</td>';
				print '<td class="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=$obj->client;
				$companystatic->code_client = $obj->code_client;
				$companystatic->code_fournisseur = $obj->code_fournisseur;
				$companystatic->canvas=$obj->canvas;
				print $companystatic->getNomUrl(1,'customer',16);
				print '</td>';
				print '<td class="right" class="nowrap">'.price($obj->total_ttc).'</td></tr>';
				$i++;
				$total += $obj->total_ttc;
			}
			if ($total>0)
			{

				print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td colspan="2" class="right">'.price($total)."</td></tr>";
			}
		}
		else
		{

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}
END MODULEBUILDER DRAFT MYOBJECT */


print '</div><div class="fichetwothirdright">';


// Last modified job position
if (isModEnabled('recruitment') && $user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
	$sql = "SELECT s.rowid, s.ref, s.label, s.date_creation, s.tms, s.status, COUNT(rc.rowid) as nbapplications";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as s";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as rc ON rc.fk_recruitmentjobposition = s.rowid";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE s.entity IN (".getEntity($staticrecruitmentjobposition->element).")";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.fk_soc = ".((int) $socid);
	}
	$sql .= " GROUP BY s.rowid, s.ref, s.label, s.date_creation, s.tms, s.status";
	$sql .= $db->order('s.tms', 'DESC');
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedJobPositions", $max);
		print '</th>';
		print '<th class="right">';
		print $langs->trans("Applications");
		print '</th>';
		print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/recruitment/recruitmentjobposition_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
		print '</tr>';
		if ($num) {
			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$staticrecruitmentjobposition->id = $objp->rowid;
				$staticrecruitmentjobposition->ref = $objp->ref;
				$staticrecruitmentjobposition->label = $objp->label;
				$staticrecruitmentjobposition->status = $objp->status;
				$staticrecruitmentjobposition->date_creation = $objp->date_creation;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$staticrecruitmentjobposition->getNomUrl(1, '').'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right">';
				print $objp->nbapplications;
				print '</td>';
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '<td class="right nowrap" width="16">';
				print $staticrecruitmentjobposition->getLibStatut(3);
				print "</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		print "</table>";
		print "</div>";
		print "<br>";
	} else {
		dol_print_error($db);
	}
}

// Last modified job position
if (isModEnabled('recruitment') && $user->hasRight('recruitment', 'recruitmentjobposition', 'read')) {
	$sql = "SELECT rc.rowid, rc.ref, rc.email, rc.lastname, rc.firstname, rc.date_creation, rc.tms, rc.status";
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentcandidature as rc";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as s ON rc.fk_recruitmentjobposition = s.rowid";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE rc.entity IN (".getEntity($staticrecruitmentjobposition->element).")";
	if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$socid) {
		$sql .= " AND s.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	if ($socid) {
		$sql .= " AND s.fk_soc = ".((int) $socid);
	}
	$sql .= $db->order('rc.tms', 'DESC');
	$sql .= $db->plimit($max, 0);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedCandidatures", $max);
		print '</th>';
		print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/recruitment/recruitmentcandidature_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</th>';
		print '</tr>';
		if ($num) {
			while ($i < $num) {
				$objp = $db->fetch_object($resql);
				$staticrecruitmentcandidature->id = $objp->rowid;
				$staticrecruitmentcandidature->ref = $objp->ref;
				$staticrecruitmentcandidature->email = $objp->email;
				$staticrecruitmentcandidature->status = $objp->status;
				$staticrecruitmentcandidature->date_creation = $objp->date_creation;
				$staticrecruitmentcandidature->firstname = $objp->firstname;
				$staticrecruitmentcandidature->lastname = $objp->lastname;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$staticrecruitmentcandidature->getNomUrl(1, '').'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '<td class="right nowrap" width="16">';
				print $staticrecruitmentcandidature->getLibStatut(3);
				print "</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		}
		print "</table>";
		print "</div>";
		print "<br>";
	} else {
		dol_print_error($db);
	}
}

print '</div></div>';

// End of page
llxFooter();
$db->close();
