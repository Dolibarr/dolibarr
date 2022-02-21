<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *    \file       handson/fllindex.php
 *    \ingroup    handson
 *    \brief      Home page of fll top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
dol_include_once('custom/handson/class/team.class.php');


// Load translation files required by the page
$langs->loadLangs(array("handson@handson"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->handson->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$teamstatic = new Team($db);


llxHeader("", $langs->trans("HandsOnArea"));

print load_fiche_titre($langs->trans("FLL-Bereich"), '', 'first.png@handson');


print '<div class="fichecenter"><div class="fichehalfleft">';

if (!empty($conf->handson->enabled) && $user->rights->handson->team->read) {

	$sql = "SELECT c.rowid, c.ref, c.label";
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_team as c";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">' . $langs->trans("Neu angemeldete Teams - zu bearbeiten") . ($num ? '<span class="badge marginleftonlyshort">' . $num . '</span>' : '') . '</th></tr>';

		$var = true;
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$teamstatic->id = $obj->rowid;
				$teamstatic->ref = $obj->ref;
				$teamstatic->label = $obj->label;
				$obj->total_ttc = 25;


				print $teamstatic->getNomUrl(0, '', 0, '', 0, 1);
				print '</td>';
				print '<td class="nowrap">';
				print $teamstatic->getNomUrl(0, '', 0, '', 0, 0);
				print '</td>';
				print '</tr>';
				$i++;
			}

		} else {

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div><div class="fichehalfright">';

if (!empty($conf->handson->enabled) && $user->rights->handson->saison->read) {

	$sql = "SELECT c.rowid, c.ref";
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_saison as c";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);
		$num = '';

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">' . $langs->trans("Neue Coaches") . ($num ? '<span class="badge marginleftonlyshort">' . $num . '</span>' : '') . '</th></tr>';

		/*$var = true;
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$saisonstatic->id = $obj->rowid;
				$saisonstatic->ref = $obj->ref;

				print $saisonstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		} else {

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
		}*/
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div></div><div class="fichecenter"><div class="fichehalfleft">';

/*if (!empty($conf->handson->enabled) && $user->rights->handson->foerderung->read) {

	$sql = "SELECT c.rowid, c.ref";
	$sql .= " FROM " . MAIN_DB_PREFIX . "handson_foerderung as c";

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">' . $langs->trans("Förderungen") . ($num ? '<span class="badge marginleftonlyshort">' . $num . '</span>' : '') . '</th></tr>';

		$var = true;
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {

				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven"><td class="nowrap">';

				$saisonstatic->id = $obj->rowid;
				$saisonstatic->ref = $obj->ref;

				print $saisonstatic->getNomUrl(0);
				print '</td>';
				print '<td class="nowrap">';
				print '</td>';
				print '</tr>';
				$i++;
			}

		} else {

			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">' . $langs->trans("NoOrder") . '</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}
}

print '</div>';

*/

print '</div></div>';

// End of page
llxFooter();
$db->close();
