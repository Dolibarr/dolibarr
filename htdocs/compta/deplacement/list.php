<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011	Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012		Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2018           charlene Benke	     <charlie@patas-monkey.com>
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
 *  \file       htdocs/compta/deplacement/list.php
 *  \brief      Page to list trips and expenses
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'users', 'trips'));

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'deplacement', '', '');

$search_ref = GETPOST('search_ref', 'alpha');
$search_name = GETPOST('search_name', 'alpha');
$search_company = GETPOST('search_company', 'alpha');
// $search_amount=GETPOST('search_amount','alpha');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
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

$year = GETPOST("year");
$month = GETPOST("month");
$day = GETPOST("day");

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$search_ref = "";
	$search_name = "";
	$search_company = "";
	// $search_amount="";
	$year = "";
	$month = "";
	$day = "";
}

/*
 * View
 */

$formother = new FormOther($db);
$tripandexpense_static = new Deplacement($db);
$userstatic = new User($db);

$childids = $user->getAllChildIds();
$childids[] = $user->id;

llxHeader();

$sql = "SELECT s.nom, d.fk_user, s.rowid as socid,"; // Ou
$sql .= " d.rowid, d.type, d.dated as dd, d.km,"; // Comment
$sql .= " d.fk_statut,";
$sql .= " u.lastname, u.firstname"; // Qui
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= ", ".MAIN_DB_PREFIX."deplacement as d";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON d.fk_soc = s.rowid";
$sql .= " WHERE d.fk_user = u.rowid";
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

if ($search_ref) {
	$sql .= " AND d.rowid = ".((int) $search_ref);
}
if ($search_name) {
	$sql .= natural_search('u.lastname', $search_name);
}
if ($search_company) {
	$sql .= natural_search('s.nom', $search_company);
}
$sql .= dolSqlDateFilter("d.dated", $day, $month, $year);

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

//print $sql;
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	print_barre_liste($langs->trans("TripsAndExpenses"), $page, $_SERVER["PHP_SELF"], "&socid=$socid", $sortfield, $sortorder, '', $num);

	$i = 0;
	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">'."\n";
	print '<table class="noborder centpercent">';
	print "<tr class=\"liste_titre\">";
	print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "d.rowid", "", "&socid=$socid", '', $sortfield, $sortorder);
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "d.type", "", "&socid=$socid", '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "d.dated", "", "&socid=$socid", 'align="center"', $sortfield, $sortorder);
	print_liste_field_titre("Person", $_SERVER["PHP_SELF"], "u.lastname", "", "&socid=$socid", '', $sortfield, $sortorder);
	print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", "&socid=$socid", '', $sortfield, $sortorder);
	print_liste_field_titre("FeesKilometersOrAmout", $_SERVER["PHP_SELF"], "d.km", "", "&socid=$socid", 'class="right"', $sortfield, $sortorder);
	print_liste_field_titre('');
	print "</tr>\n";

	// Filters lines
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input class="flat" size="4" type="text" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '&nbsp;';
	print '</td>';
	print '<td class="liste_titre" align="center">';
	if (getDolGlobalString('MAIN_LIST_FILTER_ON_DAY')) {
		print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
	}
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	print $formother->selectyear($year ? $year : -1, 'year', 1, 20, 5);
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_name" value="'.$search_name.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input class="flat" size="10" type="text" name="search_company" value="'.$search_company.'">';
	print '</td>';
	print '<td class="liste_titre right">';
	// print '<input class="flat" size="10" type="text" name="search_amount" value="'.$search_amount.'">';
	print '</td>';
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		$soc = new Societe($db);
		if ($obj->socid) {
			$soc->fetch($obj->socid);
		}

		print '<tr class="oddeven">';
		// Id
		print '<td><a href="card.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowTrip"), "trip").' '.$obj->rowid.'</a></td>';
		// Type
		print '<td>'.$langs->trans($obj->type).'</td>';
		// Date
		print '<td class="center">'.dol_print_date($db->jdate($obj->dd), 'day').'</td>';
		// User
		print '<td>';
		$userstatic->id = $obj->fk_user;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		print $userstatic->getNomUrl(1);
		print '</td>';

		if ($obj->socid) {
			print '<td>'.$soc->getNomUrl(1).'</td>';
		} else {
			print '<td>&nbsp;</td>';
		}

		print '<td class="right">'.$obj->km.'</td>';

		$tripandexpense_static->statut = $obj->fk_statut;
		print '<td class="right">'.$tripandexpense_static->getLibStatut(5).'</td>';
		print "</tr>\n";

		$i++;
	}

	print "</table>";
	print "</form>\n";
	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
