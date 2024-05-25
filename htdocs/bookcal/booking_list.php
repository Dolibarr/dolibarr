<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2023-2024  Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/bookcal/booking_list.php
 *	\ingroup    bookcal
 *	\brief      Management of direct debit order or credit transfer of invoices
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/lib/bookcal_calendar.lib.php';

// load module libraries
require_once __DIR__.'/class/calendar.class.php';

// Load translation files required by the page
$langs->loadLangs(array("agenda", "other"));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$type = GETPOST('type', 'aZ09');

$fieldid = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

$moreparam = '';

$object = new Calendar($db);

// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
	$isdraft = (($object->status == Calendar::STATUS_DRAFT) ? 1 : 0);
	if ($ret > 0) {
		$object->fetch_thirdparty();
	}
}

// There is several ways to check permission.
// Set $enablepermissioncheck to 1 to enable a minimum low level of checks
$enablepermissioncheck = 0;
if ($enablepermissioncheck) {
	$permissiontoread = $user->hasRight('bookcal', 'calendar', 'read');
	$permissiontoadd = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = $user->hasRight('bookcal', 'calendar', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
	$permissionnote = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_setnotes.inc.php
	$permissiondellink = $user->hasRight('bookcal', 'calendar', 'write'); // Used by the include of actions_dellink.inc.php
} else {
	$permissiontoread = 1;
	$permissiontoadd = 1; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
	$permissiontodelete = 1;
	$permissionnote = 1;
	$permissiondellink = 1;
}

if (!isModEnabled("bookcal")) {
	accessforbidden();
}
if (!$permissiontoread) {
	accessforbidden();
}

/*
 * Actions
 */

$parameters = '';
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();
$title = $langs->trans('Calendar')." - ".$langs->trans('Bookings');

llxHeader('', $title, $helpurl);


if ($object->id > 0) {
	$head = calendarPrepareHead($object);

	print dol_get_fiche_head($head, 'booking', $langs->trans("Calendar"), -1, $object->picto, 0, '', '', 0, '', 1);

	$formconfirm = '';

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/bookcal/calendar_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	// Link to public page
	print '<tr><td>Link</td>';
	print '<td><a href="'. DOL_URL_ROOT.'/public/bookcal/index.php?id='.$object->id.'" target="_blank">Public page</a>';
	print '</td></tr>';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	/*
	 * Bookings
	 */

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';

	print '<td class="left">'.$langs->trans("Ref").'</td>';
	print '<td>'.$langs->trans("Title").'</td>';
	print '<td class="center">'.$langs->trans("DateStart").'</td>';
	print '<td class="center">'.$langs->trans("DateEnd").'</td>';
	print '<td class="left">'.$langs->trans("Contact").'</td>';
	print '</tr>';


	$sql = "SELECT ac.id, ac.ref, ac.datep as date_start, ac.datep2 as date_end, ac.label, acr.fk_element";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as ac";
	$sql .= " JOIN ".MAIN_DB_PREFIX."actioncomm_resources as acr on acr.fk_actioncomm = ac.id";
	$sql .= " WHERE ac.fk_bookcal_calendar = ".((int) $object->id);
	$sql .= " AND ac.code = 'AC_RDV'";
	$sql .= " AND acr.element_type = 'socpeople'";
	$resql = $db->query($sql);

	$num = 0;
	if ($resql) {
		$i = 0;

		$tmpcontact = new Contact($db);
		$tmpactioncomm = new ActionComm($db);

		$num = $db->num_rows($result);
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$tmpcontact->fetch($obj->fk_element);
			$tmpactioncomm->fetch($obj->id);

			print '<tr class="oddeven">';

			// Ref
			print '<td class="nowraponall">'.$tmpactioncomm->getNomUrl(1, -1)."</td>\n";

			// Title
			print '<td class="tdoverflowmax125">';
			print $obj->label;
			print '</td>';

			// Amount
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_start), "dayhour").'</td>';

			// Date process
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_end), "dayhour").'</td>';

			// Link to make payment now
			print '<td class="minwidth75">';
			print $tmpcontact->getNomUrl(1, -1);
			print '</td>';


			print "</tr>\n";
			$i++;
		}

		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	print "</table>";
	print '</div>';
}

// End of page
llxFooter();
$db->close();
