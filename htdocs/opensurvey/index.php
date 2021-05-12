<?php
/* Copyright (C) 2013-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
=======
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/opensurvey/index.php
 *	\ingroup    opensurvey
 *	\brief      Home page of opensurvey area
 */

<<<<<<< HEAD
require_once('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
=======
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Security check
if (!$user->rights->opensurvey->read) accessforbidden();

/*
 * View
 */

<<<<<<< HEAD
=======

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('opensurveyindex'));

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 // Load translation files required by the page
$langs->load("opensurvey");

llxHeader();

$nbsondages=0;
$sql='SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'opensurvey_sondage';
$resql=$db->query($sql);
if ($resql)
{
	$obj=$db->fetch_object($resql);
	$nbsondages=$obj->nb;
}
<<<<<<< HEAD
else dol_print_error($db,'');
=======
else dol_print_error($db, '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9



print load_fiche_titre($langs->trans("OpenSurveyArea"));


print '<div class="fichecenter"><div class="fichethirdleft">';


$nbsondages=0;
$sql='SELECT COUNT(*) as nb FROM '.MAIN_DB_PREFIX.'opensurvey_sondage';
$resql=$db->query($sql);
if ($resql)
{
	$obj=$db->fetch_object($resql);
	$nbsondages=$obj->nb;
}
<<<<<<< HEAD
else dol_print_error($db,'');
=======
else dol_print_error($db, '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("OpenSurveyArea").'</td></tr>';
print "<tr ".$bc[0].">";
<<<<<<< HEAD
print '<td>'.$langs->trans("NbOfSurveys").'</td><td align="right"><a href="list.php">'.$nbsondages.'</a></td>';
print "</tr>";
//print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">';
=======
print '<td>'.$langs->trans("NbOfSurveys").'</td><td class="right"><a href="list.php">'.$nbsondages.'</a></td>';
print "</tr>";
//print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
//print $total;
//print '</td></tr>';
print '</table>';


print '</div></div></div>';

<<<<<<< HEAD


llxFooter();

=======
$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardOpenSurvey', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
