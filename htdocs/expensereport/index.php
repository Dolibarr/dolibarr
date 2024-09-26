<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2021	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2019       Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2019-2024  Frédéric France      <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/expensereport/index.php
 *  \ingroup    expensereport
 *  \brief      Page list of expenses
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';

$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('expensereportindex'));

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'trips'));

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
	$sortfield = "d.tms";
}
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;

$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'expensereport', '', '');


/*
 * View
 */

$tripandexpense_static = new ExpenseReport($db);

$childids = $user->getAllChildIds();
$childids[] = $user->id;

$help_url = "EN:Module_Expense_Reports|FR:Module_Notes_de_frais";

llxHeader('', $langs->trans("TripsAndExpenses"), $help_url);


$label = $somme = $nb = array();

$totalnb = $totalsum = 0;
$sql = "SELECT tf.code, tf.label, count(de.rowid) as nb, sum(de.total_ht) as km";
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as d, ".MAIN_DB_PREFIX."expensereport_det as de, ".MAIN_DB_PREFIX."c_type_fees as tf";
$sql .= " WHERE de.fk_expensereport = d.rowid AND d.entity IN (".getEntity('expensereport').") AND de.fk_c_type_fees = tf.id";
// RESTRICT RIGHTS
if (!$user->hasRight('expensereport', 'readall') && !$user->hasRight('expensereport', 'lire_tous')
	&& (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || !$user->hasRight('expensereport', 'writeall_advance'))) {
	$childids = $user->getAllChildIds();
	$childids[] = $user->id;
	$sql .= " AND d.fk_user_author IN (".$db->sanitize(implode(',', $childids)).")\n";
}

$sql .= " GROUP BY tf.code, tf.label";

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($result);

		$somme[$objp->code] = $objp->km;
		$nb[$objp->code] = $objp->nb;
		$label[$objp->code] = $objp->label;
		$totalnb += $objp->nb;
		$totalsum += $objp->km;
		$i++;
	}
	$db->free($result);
} else {
	dol_print_error($db);
}


print load_fiche_titre($langs->trans("ExpensesArea"), '', 'trip');


print '<div class="fichecenter"><div class="fichethirdleft">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder nohover centpercent">';
print '<tr class="liste_titre">';
print '<th colspan="4">'.$langs->trans("Statistics").'</th>';
print "</tr>\n";

$listoftype = $tripandexpense_static->listOfTypes();
$dataseries = array();
foreach ($listoftype as $code => $label) {
	$dataseries[] = array($label, (isset($somme[$code]) ? (int) $somme[$code] : 0));
}

// Sort array with most important first
$dataseries = dol_sort_array($dataseries, '1', 'desc');

// Merge all entries after the $KEEPNFIRST one into one entry called "Other..." (to avoid to have too much entries in graphic).
$KEEPNFIRST = 7;	// Keep first $KEEPNFIRST one + 1 with the remain
$i = 0;
if (count($dataseries) > ($KEEPNFIRST + 1)) {
	foreach ($dataseries as $key => $val) {
		if ($i < $KEEPNFIRST) {
			$i++;
			continue;
		}
		// Here $key = $KEEPNFIRST
		$dataseries[$KEEPNFIRST][0] = $langs->trans("Others").'...';
		if ($key == $KEEPNFIRST) {
			$i++;
			continue;
		}
		$dataseries[$KEEPNFIRST][1] += $dataseries[$key][1];
		unset($dataseries[$key]);
		$i++;
	}
}

if ($conf->use_javascript_ajax) {
	print '<tr><td class="center" colspan="4">';

	include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
	$dolgraph = new DolGraph();
	$dolgraph->SetData($dataseries);
	$dolgraph->setHeight(350);
	$dolgraph->combine = !getDolGlobalString('MAIN_EXPENSEREPORT_COMBINE_GRAPH_STAT') ? 0.05 : $conf->global->MAIN_EXPENSEREPORT_COMBINE_GRAPH_STAT;
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
print '<td class="right" colspan="3">'.price($totalsum, 1, $langs, 0, 0, 0, $conf->currency).'</td>';
print '</tr>';

print '</table>';
print '</div>';



// Right area
print '</div><div class="fichetwothirdright">';


$langs->load("boxes");

$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.statut as user_status, u.photo, u.email, u.admin,";
$sql .= " d.rowid, d.ref, d.date_debut as dated, d.date_fin as datef, d.date_create as dm, d.total_ht, d.total_ttc, d.fk_statut as status";
$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as d, ".MAIN_DB_PREFIX."user as u";
$sql .= " WHERE u.rowid = d.fk_user_author";
// RESTRICT RIGHTS
if (!$user->hasRight('expensereport', 'readall') && !$user->hasRight('expensereport', 'lire_tous')
	&& (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || !$user->hasRight('expensereport', 'writeall_advance'))) {
	$childids = $user->getAllChildIds();
	$childids[] = $user->id;
	$sql .= " AND d.fk_user_author IN (".$db->sanitize(implode(',', $childids)).")\n";
}
$sql .= ' AND d.entity IN ('.getEntity('expensereport').')';
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($max, 0);

$result = $db->query($sql);
if ($result) {
	$var = false;
	$num = $db->num_rows($result);

	$i = 0;

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses", min($max, $num)).'</th>';
	print '<th class="right">'.$langs->trans("AmountHT").'</th>';
	print '<th class="right">'.$langs->trans("AmountTTC").'</th>';
	print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
	print '<th>';
	print '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?sortfield=d.tms&sortorder=DESC">';
	print img_picto($langs->trans("FullList"), 'expensereport');
	print '</a>';
	print '</th>';
	print '</tr>';
	if ($num) {
		$total_ttc = $totalam = $total = 0;

		$expensereportstatic = new ExpenseReport($db);
		$userstatic = new User($db);
		while ($i < $num && $i < $max) {
			$obj = $db->fetch_object($result);

			$expensereportstatic->id = $obj->rowid;
			$expensereportstatic->ref = $obj->ref;
			$expensereportstatic->status = $obj->status;

			$userstatic->id = $obj->uid;
			$userstatic->admin = $obj->admin;
			$userstatic->email = $obj->email;
			$userstatic->lastname = $obj->lastname;
			$userstatic->firstname = $obj->firstname;
			$userstatic->login = $obj->login;
			$userstatic->status = $obj->user_status;
			$userstatic->photo = $obj->photo;

			print '<tr class="oddeven">';
			print '<td class="tdoverflowmax200">'.$expensereportstatic->getNomUrl(1).'</td>';
			print '<td class="tdoverflowmax150">'.$userstatic->getNomUrl(-1).'</td>';
			print '<td class="right amount">'.price($obj->total_ht).'</td>';
			print '<td class="right amount">'.price($obj->total_ttc).'</td>';
			print '<td class="right">'.dol_print_date($db->jdate($obj->dm), 'day').'</td>';
			print '<td class="right">';
			print $expensereportstatic->getLibStatut(3);
			print '</td>';
			print '</tr>';

			$i++;
		}
	} else {
		print '<tr class="oddeven"><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print '</table></div><br>';
} else {
	dol_print_error($db);
}

print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardExpenseReport', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
