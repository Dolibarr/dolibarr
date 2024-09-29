<?php
/* Copyright (C) 2013-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2019      Frédéric France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/opensurvey/index.php
 *	\ingroup    opensurvey
 *	\brief      Home page of opensurvey area
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

// Load translation files required by the page
$langs->load("opensurvey");

// Security check
if (!$user->hasRight('opensurvey', 'read')) {
	accessforbidden();
}

$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('opensurveyindex'));


/*
 * View
 */

$nbsondages = 0;
$sql = 'SELECT COUNT(*) as nb';
$sql .= ' FROM '.MAIN_DB_PREFIX.'opensurvey_sondage';
$sql .= ' WHERE entity IN ('.getEntity('survey').')';
$resql = $db->query($sql);
if ($resql) {
	$obj = $db->fetch_object($resql);
	$nbsondages = $obj->nb;
} else {
	dol_print_error($db, '');
}


$title = $langs->trans("OpenSurveyArea");
llxHeader('', $title);

print load_fiche_titre($title, '', 'poll');


print '<div class="fichecenter"><div class="fichethirdleft">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("OpenSurveyArea").'</td></tr>';
print '<tr class="oddeven">';
print '<td>'.$langs->trans("NbOfSurveys").'</td><td class="right"><a href="list.php">'.$nbsondages.'</a></td>';
print "</tr>";
//print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">';
//print $total;
//print '</td></tr>';
print '</table>';
print '</div>';

print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardOpenSurvey', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
