<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/compta/deplacement/index.php
 *  \brief      Page list of expenses
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'trips'));

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'deplacement', '', '');

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
	$sortfield = "d.dated";
}
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;


/*
 * View
 */

$tripandexpense_static = new Deplacement($db);

$childids = $user->getAllChildIds();
$childids[] = $user->id;

//$help_url='EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones';
$help_url = '';
llxHeader('', $langs->trans("TripsAndExpenses"), $help_url);



$totalnb = 0;
$sql = "SELECT count(d.rowid) as nb, sum(d.km) as km, d.type";
$sql .= " FROM ".MAIN_DB_PREFIX."deplacement as d";
$sql .= " WHERE d.entity = ".$conf->entity;
if (!$user->hasRight('deplacement', 'readall') && !$user->hasRight('deplacement', 'lire_tous')) {
	$sql .= ' AND d.fk_user IN ('.$db->sanitize(implode(',', $childids)).')';
}
$sql .= " GROUP BY d.type";
$sql .= " ORDER BY d.type";

$result = $db->query($sql);
$somme = array();
$nb = array();
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($result);

		$somme[$objp->type] = $objp->km;
		$nb[$objp->type] = $objp->nb;
		$totalnb += $objp->nb;
		$i++;
	}
	$db->free($result);
} else {
	dol_print_error($db);
}


print load_fiche_titre($langs->trans("ExpensesArea"));


print '<div class="fichecenter"><div class="fichethirdleft">';


// Statistics
print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("Statistics").'</td>';
print "</tr>\n";

$listoftype = $tripandexpense_static->listOfTypes();
$dataseries = array();
foreach ($listoftype as $code => $label) {
	$dataseries[] = array($label, (isset($nb[$code]) ? (int) $nb[$code] : 0));
}

if ($conf->use_javascript_ajax) {
	print '<tr><td align="center" colspan="4">';

	include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
	$dolgraph = new DolGraph();
	$dolgraph->SetData($dataseries);
	$dolgraph->setShowLegend(2);
	$dolgraph->setShowPercent(1);
	$dolgraph->SetType(array('pie'));
	$dolgraph->setHeight('200');
	$dolgraph->draw('idgraphstatus');
	print $dolgraph->show($totalnb ? 0 : 1);

	print '</td></tr>';
}

print '<tr class="liste_total">';
print '<td>'.$langs->trans("Total").'</td>';
print '<td class="right">'.$totalnb.'</td>';
print '</tr>';

print '</table>';



print '</div><div class="fichetwothirdright">';


$max = 10;

$langs->load("boxes");

$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, d.rowid, d.dated as date, d.tms as dm, d.km, d.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."deplacement as d, ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE u.rowid = d.fk_user";
$sql .= " AND d.entity = ".$conf->entity;
if (!$user->hasRight('deplacement', 'readall') && !$user->hasRight('deplacement', 'lire_tous')) {
	$sql .= ' AND d.fk_user IN ('.$db->sanitize(implode(',', $childids)).')';
}
// If the internal user must only see his customers, force searching by him
$search_sale = 0;
if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = d.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = d.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search on socid
if ($socid) {
	$sql .= " AND d.fk_soc = ".((int) $socid);
}
$sql .= $db->order("d.tms", "DESC");
$sql .= $db->plimit($max, 0);

$result = $db->query($sql);
if ($result) {
	$var = false;
	$num = $db->num_rows($result);

	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses", min($max, $num)).'</td>';
	print '<td class="right">'.$langs->trans("FeesKilometersOrAmout").'</td>';
	print '<td class="right">'.$langs->trans("DateModificationShort").'</td>';
	print '<td width="16">&nbsp;</td>';
	print '</tr>';
	if ($num) {
		$total_ttc = $totalam = $total = 0;

		$deplacementstatic = new Deplacement($db);
		$userstatic = new User($db);
		while ($i < $num && $i < $max) {
			$obj = $db->fetch_object($result);
			$deplacementstatic->ref = $obj->rowid;
			$deplacementstatic->id = $obj->rowid;
			$userstatic->id = $obj->uid;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			print '<tr class="oddeven">';
			print '<td>'.$deplacementstatic->getNomUrl(1).'</td>';
			print '<td>'.$userstatic->getNomUrl(1).'</td>';
			print '<td class="right">'.$obj->km.'</td>';
			print '<td class="right">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>';
			print '<td>'.$deplacementstatic->LibStatut($obj->fk_statut, 3).'</td>';
			print '</tr>';

			$i++;
		}
	} else {
		print '<tr class="oddeven"><td colspan="2" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print '</table><br>';
} else {
	dol_print_error($db);
}


print '</div></div>';

// End of page
llxFooter();
$db->close();
