<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       htdocs/bookcal/bookcalindex.php
 *	\ingroup    bookcal
 *	\brief      Home page of bookcal top menu
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("agenda"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->hasRight('bookcal', 'myobject', 'read')) {
// 	accessforbidden();
// }
$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$now = dol_now();
$NBMAX = getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT');
$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("BookcalBookingTitle"), '', '', 0, 0, '', '', '', 'mod-bookcal page-index');

print load_fiche_titre($langs->trans("BookcalBookingTitle"), '', 'fa-calendar-check');

print '<div class="fichecenter"><div class="fichethirdleft">';


// BEGIN MODULEBUILDER DRAFT MYOBJECT
// Draft MyObject
if ($user->hasRight('bookcal', 'availabilities', 'read') && isModEnabled('bookcal')) {
	$langs->load("orders");
	/*$myobjectstatic = new Booking($db);

	$sql = "SELECT rowid, `ref`, fk_soc, fk_project, description, note_public, note_private, date_creation, tms, fk_user_creat, fk_user_modif, last_main_doc, import_key, model_pdf, status, firstname, lastname, email, `start`, duration";
	$sql .= " FROM ". MAIN_DB_PREFIX . 'bookcal_booking';

	$resql = $db->query($sql);
	if ($resql) {
		$total = 0;
		$num = $db->num_rows($resql);

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="21">'.$langs->trans("Bookings").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

		$var = true;
		print '
		<tr>
		<th colspan="3">id</th>
		<th colspan="3">ref</th>
		<th colspan="3">name</th>
		<th colspan="3">hour</th>
		<th colspan="3">duration</th>
		<th colspan="3">description</th>
		</tr>';
		if ($num > 0) {
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';

				$myobjectstatic->id=$obj->rowid;
				$myobjectstatic->ref=$obj->ref;
				$myobjectstatic->firstname = $obj->firstname;
				$myobjectstatic->lastname = $obj->lastname;
				$myobjectstatic->start = $obj->start;
				$myobjectstatic->duration = $obj->duration;
				$myobjectstatic->description = $obj->description;


				print '<td colspan="3" class="nowrap">' . $myobjectstatic->id . "</td>";
				print '<td colspan="3" class="nowrap">' . $myobjectstatic->ref . "</td>";
				print '<td colspan="3" class="nowrap">' . $myobjectstatic->firstname . " " . $myobjectstatic->lastname . "</td>";
				print '<td colspan="3" class="nowrap">' . dol_print_date($myobjectstatic->start, 'dayhourtext') . "</td>";
				print '<td colspan="3" class="nowrap">' . $myobjectstatic->duration . "</td>";
				print '<td colspan="3" class="nowrap">' . $myobjectstatic->description . "</td>";
				$i++;
			}
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoOrder").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	} else {
		dol_print_error($db);
	}*/
}
//END MODULEBUILDER DRAFT MYOBJECT */



print '</div><div class="fichetwothirdright">';


/* BEGIN MODULEBUILDER LASTMODIFIED MYOBJECT
// Last modified myobject
if (isModEnabled('bookcal')) {
	$sql = "SELECT rowid, `ref`, fk_soc, fk_project, description, note_public, note_private, date_creation, tms, fk_user_creat, fk_user_modif, last_main_doc, import_key, model_pdf, status, firstname, lastname, email, `start`, duration";
	$sql .= " FROM ". MAIN_DB_PREFIX . 'bookcal_booking';
	print "here2";
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">';
		print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
		print '</th>';
		print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
		print '</tr>';
		print $num;
		if ($num)
		{
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);

				$myobjectstatic->id=$objp->rowid;
				$myobjectstatic->ref=$objp->ref;
				$myobjectstatic->label=$objp->label;
				$myobjectstatic->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowrap">'.$myobjectstatic->getNomUrl(1).'</td>';
				print '<td class="right nowrap">';
				print "</td>";
				print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
				print '</tr>';
				$i++;
			}

			$db->free($resql);
		} else {
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
		}
		print "</table><br>";
	}
}

*/
print '</div></div>';

// End of page
llxFooter();
$db->close();
