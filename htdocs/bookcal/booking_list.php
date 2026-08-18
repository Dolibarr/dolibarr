<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004       Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2016  Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2014  Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2017       Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2023-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Daniel Bauer			<d.bauer@elaax.net>
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
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/lib/bookcal_calendar.lib.php';
require_once DOL_DOCUMENT_ROOT.'/bookcal/class/calendar.class.php';


// Load translation files required by the page
$langs->loadLangs(array("agenda", "other"));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('facid')); // For backward compatibility
$ref = GETPOST('ref', 'alpha');
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$type = GETPOST('type', 'aZ09');
$lineid = GETPOSTINT('lineid');

$fieldid = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

$moreparam = '';

$object = new Calendar($db);

/**
 * Send a booking status mail to the booked contact.
 *
 * @param	Contact		$contact		Booked contact
 * @param	ActionComm	$actioncomm		Booking event
 * @param	string		$status			confirm|cancel
 * @return	int							1 if mail was sent, 0 if skipped, -1 on error
 */
function bookcalSendBookingStatusMail($contact, $actioncomm, $status)
{
	global $db, $langs, $mysoc, $user;

	if (empty($contact->email)) {
		return 0;
	}

	$from = getDolGlobalString('BOOKCAL_MAIL_FROM');
	if (empty($from)) {
		$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
	}
	if (empty($from)) {
		$from = getDolGlobalString('MAIN_INFO_SOCIETE_MAIL');
	}
	if (empty($from) && is_object($mysoc) && !empty($mysoc->email)) {
		$from = $mysoc->email;
	}
	if (empty($from)) {
		dol_syslog('BookCal booking status mail skipped: no sender email configured', LOG_WARNING);
		return 0;
	}

	$companyname = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
	if (empty($companyname) && is_object($mysoc) && !empty($mysoc->name)) {
		$companyname = $mysoc->name;
	}

	$bookingdate = dol_print_date($actioncomm->datep, 'dayhourtext');
	$fullname = trim($contact->getFullName($langs));
	if (empty($fullname)) {
		$fullname = trim($contact->firstname.' '.$contact->lastname);
	}

	$templatelabel = 'BookCalBookingConfirmed';
	if ($status == 'confirm') {
		$subject = 'Your booking has been confirmed - __MAIN_INFO_SOCIETE_NOM__';
		$msg = '<p>Hallo __BOOKCAL_CONTACT_FULLNAME__,</p>';
		$msg .= '<p>Your booking on __BOOKCAL_BOOKING_DATE__ has been confirmed.</p>';
		$msg .= '<p>Mit freundlichen Gruessen<br>__MAIN_INFO_SOCIETE_NOM__</p>';
	} else {
		$templatelabel = 'BookCalBookingCanceled';
		$subject = 'Your booking has been canceled - __MAIN_INFO_SOCIETE_NOM__';
		$msg = '<p>Hallo __BOOKCAL_CONTACT_FULLNAME__,</p>';
		$msg .= '<p>Your booking request for __BOOKCAL_BOOKING_DATE__ has been canceled. We will contact you with an alternative suggestion.</p>';
		$msg .= '<p>Mit freundlichen Gruessen<br>__MAIN_INFO_SOCIETE_NOM__</p>';
	}

	$formmail = new FormMail($db);
	$template = $formmail->getEMailTemplate($db, 'bookcal_send', $user, $langs, -2, 1, $templatelabel);
	if (is_object($template)) {
		$subject = $template->topic;
		$msg = $template->content;
		if (!empty($template->email_from)) {
			$from = $template->email_from;
		}
	}

	$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $actioncomm);
	complete_substitutions_array($substitutionarray, $langs, $actioncomm);
	$substitutionarray['__BOOKCAL_CONTACT_FULLNAME__'] = $fullname;
	$substitutionarray['__BOOKCAL_CONTACT_EMAIL__'] = $contact->email;
	$substitutionarray['__BOOKCAL_BOOKING_DATE__'] = $bookingdate;
	$substitutionarray['__BOOKCAL_BOOKING_REF__'] = $actioncomm->ref;
	if (!empty($companyname)) {
		$substitutionarray['__BOOKCAL_COMPANY_NAME__'] = $companyname;
	}

	$subject = make_substitutions($subject, $substitutionarray, $langs);
	$msg = make_substitutions($msg, $substitutionarray, $langs);
	$from = make_substitutions($from, $substitutionarray, $langs);

	try {
		$mail = new CMailFile($subject, $contact->email, $from, $msg, array(), array(), array(), '', '', 0, 1, '', '', 'bookcal_'.$actioncomm->id, '', 'bookcal');
		$result = $mail->sendfile();
		if (!$result) {
			dol_syslog('BookCal booking status mail failed: '.$mail->error, LOG_WARNING);
			return -1;
		}
	} catch (Throwable $e) {
		dol_syslog('BookCal booking status mail failed: '.$e->getMessage(), LOG_WARNING);
		return -1;
	}

	return 1;
}

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

$parameters = array();
$helpurl = '';
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook) && in_array($action, array('confirmbooking', 'cancelbooking')) && $permissiontoadd) {
	if (!checkToken()) {
		accessforbidden();
	}

	$actionid = GETPOSTINT('actionid');
	$tmpactioncomm = new ActionComm($db);
	$tmpcontact = new Contact($db);

	$result = $tmpactioncomm->fetch($actionid);
	$sqlcheck = "SELECT id FROM ".MAIN_DB_PREFIX."actioncomm";
	$sqlcheck .= " WHERE id = ".((int) $actionid);
	$sqlcheck .= " AND fk_bookcal_calendar = ".((int) $object->id);
	$sqlcheck .= " AND code = 'AC_RDV'";
	$resqlcheck = $db->query($sqlcheck);
	$validbooking = ($resqlcheck && $db->num_rows($resqlcheck) > 0);
	if ($resqlcheck) {
		$db->free($resqlcheck);
	}
	if ($result <= 0 || !$validbooking) {
		setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
	} else {
		$tmpcontact->fetch($tmpactioncomm->contact_id);

		$db->begin();
		if ($action == 'confirmbooking') {
			$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " SET percent = 100, status = 0, transparency = 1, fk_user_mod = ".((int) $user->id);
			$sql .= " WHERE id = ".((int) $tmpactioncomm->id);
			$resql = $db->query($sql);

			if ($resql) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm_resources";
				$sql .= " SET answer_status = '1', transparency = 1";
				$sql .= " WHERE fk_actioncomm = ".((int) $tmpactioncomm->id);
				$resql = $db->query($sql);
			}
		} else {
			$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " SET percent = -1, status = 9, fk_user_mod = ".((int) $user->id);
			$sql .= " WHERE id = ".((int) $tmpactioncomm->id);
			$resql = $db->query($sql);

			if ($resql) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm_resources";
				$sql .= " SET answer_status = '2', transparency = 0";
				$sql .= " WHERE fk_actioncomm = ".((int) $tmpactioncomm->id);
				$resql = $db->query($sql);
			}
		}

		if ($resql) {
			$db->commit();
			$tmpactioncomm->fetch($actionid);
			bookcalSendBookingStatusMail($tmpcontact, $tmpactioncomm, ($action == 'confirmbooking' ? 'confirm' : 'cancel'));
			setEventMessages($langs->trans($action == 'confirmbooking' ? 'BookcalBookingConfirmed' : 'BookcalBookingCanceled'), null);
		} else {
			$db->rollback();
			setEventMessages($db->lasterror(), null, 'errors');
		}

		header("Location: ".$_SERVER["PHP_SELF"].'?id='.(int) $object->id);
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);

$now = dol_now();
$title = $langs->trans('Calendar')." - ".$langs->trans('Bookings');

llxHeader('', $title, $helpurl, '', 0, 0, '', '', '', 'mod-bookcal page-list');


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
	print '<td class="center">'.$langs->trans("Status").'</td>';
	print '<td class="right">'.$langs->trans("Action").'</td>';
	print '</tr>';


	$sql = "SELECT ac.id, ac.ref, ac.datep as date_start, ac.datep2 as date_end, ac.label, ac.percent, ac.status, acr.fk_element as elementid, acr.answer_status";
	$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as ac";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources as acr on acr.fk_actioncomm = ac.id AND acr.element_type = 'socpeople'";
	$sql .= " WHERE ac.fk_bookcal_calendar = ".((int) $object->id);
	$sql .= " AND ac.code = 'AC_RDV'";
	$sql .= " ORDER BY ac.datep DESC";

	$resql = $db->query($sql);

	$num = 0;
	if ($resql) {
		$i = 0;

		$tmpcontact = new Contact($db);
		$tmpactioncomm = new ActionComm($db);

		$num = $db->num_rows($resql);
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$tmpcontact->fetch($obj->elementid);
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
			print $tmpcontact->id > 0 ? $tmpcontact->getNomUrl(1, '-1') : '';
			print '</td>';

			print '<td class="center">';
			if ((int) $obj->status !== 0) {
				print '<span class="badge badge-status4">Abgebrochen</span>';
			} elseif ((int) $obj->percent === 100 || (string) $obj->answer_status === '1') {
				print '<span class="badge badge-status4">Bestaetigt</span>';
			} else {
				print '<span class="badge badge-status1">Offen</span>';
			}
			print '</td>';

			print '<td class="right nowraponall">';
			if ((int) $obj->status === 0) {
				if ((int) $obj->percent !== 100 && (string) $obj->answer_status !== '1') {
					print '<a class="button button-small" href="'.$_SERVER["PHP_SELF"].'?id='.(int) $object->id.'&action=confirmbooking&actionid='.(int) $obj->id.'&token='.newToken().'">'.$langs->trans("Confirm").'</a>';
				}
				print ' <a class="button button-small button-delete" href="'.$_SERVER["PHP_SELF"].'?id='.(int) $object->id.'&action=cancelbooking&actionid='.(int) $obj->id.'&token='.newToken().'">'.$langs->trans("Cancel").'</a>';
			}
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
