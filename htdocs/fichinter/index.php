<?php
/* Copyright (C) 2003-2004  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2025  Charlene Benke       <charlene@patas-monkey.com>
 * Copyright (C) 2019       Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2024-2026  Frédéric France		 <frederic.france@free.fr>
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
 *	\file       htdocs/fichinter/index.php
 *	\ingroup    ficheinter
 *	\brief      Home page of interventional module
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/dashboard.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';


if (!$user->hasRight('ficheinter', 'lire')) {
	accessforbidden();
}

// Load translation files required by the page
$langs->load("interventions");

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('interventionindex'));


// Security check
$socid = GETPOSTINT('socid');
if ($user->isExternalUser()) {
	$action = '';
	$socid = $user->isExternalUser();
}

// Load $resultboxes
$resultboxes = FormOther::getBoxesArea($user, "20");

$max = getDolUserInt('MAIN_SIZE_SHORTLIST_LIMIT', getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5));


/*
 * View
 */

$fichinterstatic = new Fichinter($db);
$companystatic = new Societe($db);
$form = new Form($db);
$formfile = new FormFile($db);

$help_url = "EN:ModuleFichinters|FR:Module_Fiche_Interventions|ES:Módulo_FichaInterventiones";

llxHeader("", $langs->trans("Interventions"), $help_url, '', 0, 0, '', '', '', 'mod-fichinter page-index');

print load_fiche_titre($langs->trans("InterventionsArea"), '', 'intervention');

print '<div class="fichecenter"><div class="fichethirdleft">';

// Statistics

$sql = "SELECT count(f.rowid), f.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ", ".MAIN_DB_PREFIX."fichinter as f";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE f.entity IN (".getEntity('intervention').")";
$sql .= " AND f.fk_soc = s.rowid";
if ($user->socid) {
	$sql .= ' AND f.fk_soc = '.((int) $user->socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " GROUP BY f.fk_statut";
$resql = $db->query($sql);
if ($resql) {
	$vals = array();
	while ($row = $db->fetch_row($resql)) {
		if (!isset($vals[$row[1]])) {
			$vals[$row[1]] = 0;
		}
		$vals[$row[1]] += $row[0];
	}
	$db->free($resql);

	$colors = getThemeBadgeStatusColors();
	$colorofstatus = array(
		Fichinter::STATUS_DRAFT => '-'.$colors[0],
		Fichinter::STATUS_VALIDATED => $colors[1],
		Fichinter::STATUS_CLOSED => $colors[2],
		Fichinter::STATUS_BILLED => $colors[4],
	);
	$listofstatus = array(Fichinter::STATUS_DRAFT, Fichinter::STATUS_VALIDATED, Fichinter::STATUS_CLOSED);
	if (getDolGlobalString('FICHINTER_CLASSIFY_BILLED')) {
		$listofstatus[] = Fichinter::STATUS_BILLED;
	}
	$series = array();
	foreach ($listofstatus as $status) {
		$series[] = array(
			'label' => $fichinterstatic->LibStatut($status, 1),
			'labelnojs' => $fichinterstatic->LibStatut($status, 0),
			'nb' => (isset($vals[$status]) ? (int) $vals[$status] : 0),
			'color' => $colorofstatus[$status],
			'url' => 'list.php?search_status='.$status,
		);
	}
	print getStatusPieChart($langs->trans("Statistics").' - '.$langs->trans("Interventions"), $series);
} else {
	dol_print_error($db);
}


/*
 * Draft interventions
 */
if (isModEnabled('intervention')) {
	$sql = "SELECT f.rowid, f.ref, s.nom as name, s.rowid as socid";
	$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE f.entity IN (".getEntity('intervention').")";
	$sql .= " AND f.fk_soc = s.rowid";
	$sql .= " AND f.fk_statut = 0";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}

	$resql = $db->query($sql);
	if ($resql) {
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("DraftFichinter").'</th></tr>';
		$langs->load("interventions");
		$num = $db->num_rows($resql);
		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				$fichinterstatic->id = $obj->rowid;
				$fichinterstatic->ref = $obj->ref;
				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$fichinterstatic->getNomUrl(1).'</td>';
				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				print '<td>'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '</tr>';
				$i++;
			}
		}
		print "</table></div><br>";
	}
}


print '</div><div class="fichetwothirdright">';


/*
 * Last modified interventions
 */

$sql = "SELECT f.rowid, f.ref, f.fk_statut, f.date_valid as datec, f.tms as datem,";
$sql .= " s.nom as name, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE f.entity IN (".getEntity('intervention').")";
$sql .= " AND f.fk_soc = s.rowid";
//$sql.= " AND c.fk_statut > 2";
if ($socid) {
	$sql .= " AND f.fk_soc = ".((int) $socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " ORDER BY f.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LastModifiedInterventions", $max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td width="20%" class="nowrap">';

			$fichinterstatic->id = $obj->rowid;
			$fichinterstatic->ref = $obj->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowrap">';
			print $fichinterstatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" class="right nobordernopadding hideonsmartphone">';
			$filename = dol_sanitizeFileName($obj->ref);
			$filedir = $conf->ficheinter->dir_output.'/'.dol_sanitizeFileName($obj->ref);
			$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($fichinterstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';
			$companystatic->id = $obj->socid;
			$companystatic->name = $obj->name;
			print '<td>'.$companystatic->getNomUrl(1, 'customer').'</td>';
			print '<td>'.dol_print_date($db->jdate($obj->datem), 'day').'</td>';
			print '<td class="right">'.$fichinterstatic->LibStatut($obj->fk_statut, 5).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table></div><br>";
} else {
	dol_print_error($db);
}


/*
 * interventions to process
 */

if (isModEnabled('intervention')) {
	$sql = "SELECT f.rowid, f.ref, f.fk_statut, s.nom as name, s.rowid as socid";
	$sql .= " FROM ".MAIN_DB_PREFIX."fichinter as f";
	$sql .= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}
	$sql .= " WHERE f.entity IN (".getEntity('intervention').")";
	$sql .= " AND f.fk_soc = s.rowid";
	$sql .= " AND f.fk_statut = 1";
	if ($socid) {
		$sql .= " AND f.fk_soc = ".((int) $socid);
	}
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}
	$sql .= " ORDER BY f.rowid DESC";

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("FichinterToProcess").' <a href="'.DOL_URL_ROOT.'/fichinter/list.php?search_status=1"><span class="badge">'.$num.'</span></a></th></tr>';

		if ($num) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td class="nowrap" width="20%">';

				$fichinterstatic->id = $obj->rowid;
				$fichinterstatic->ref = $obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">';
				print $fichinterstatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" class="right nobordernopadding hideonsmartphone">';
				$filename = dol_sanitizeFileName($obj->ref);
				$filedir = $conf->ficheinter->dir_output.'/'.dol_sanitizeFileName($obj->ref);
				$urlsource = $_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($fichinterstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';
				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				print '<td>'.$companystatic->getNomUrl(1, 'customer').'</td>';
				print '<td class="right">'.$fichinterstatic->LibStatut($obj->fk_statut, 5).'</td>';
				print '</tr>';
				$i++;
			}
		}

		print "</table></div><br>";
	} else {
		dol_print_error($db);
	}
}

print '</div></div>';

// boxes
print '<div class="clearboth"></div>';
print '<div class="fichecenter fichecenterbis">';

$boxlist = '<div class="twocolumns">';

$boxlist .= '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';

$boxlist .= $resultboxes['boxlista'];
$boxlist .= "</div>\n";

$boxlist .= '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';
$boxlist .= $resultboxes['boxlistb'];
$boxlist .= '</div>'."\n";

$boxlist .= "</div>\n";

print $boxlist;

print '</div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardInterventions', $parameters, $object); // Note that $action and $object may have been modified by hook

llxFooter();

$db->close();
