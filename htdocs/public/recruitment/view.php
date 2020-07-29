<?php
/* Copyright (C) 2013-2016  Jean-François FERRY     <hello@librethic.io>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       htdocs/public/recruitment/view.php
 *       \ingroup    recruitment
 *       \brief      Public file to show on job
 */

if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
// If this page is public (can be called outside logged session)

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$cancel   = GETPOST('cancel', 'alpha');
$action   = GETPOST('action', 'aZ09');
$email    = GETPOST('email', 'alpha');

$ref = GETPOST('ref', 'alpha');

if (GETPOST('btn_view')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new RecruitmentJobPosition($db);


/*
 * Actions
 */

if ($cancel)
{
	if (!empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}
	$action = 'view';
}

if ($action == "view" || $action == "presend" || $action == "close" || $action == "confirm_public_close" || $action == "add_message") {
	$error = 0;
	$display_ticket = false;
	if (!strlen($ref)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")));
		$action = '';
	}
	if (!strlen($email)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
		$action = '';
	} else {
		if (!isValidEmail($email)) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorEmailInvalid"));
			$action = '';
		}
	}

	if (!$error) {
		$ret = $object->fetch('', $ref);
	}

	/*
	if (!$error && $action == "add_message" && $display_ticket && GETPOSTISSET('btn_add_message'))
	{
		// TODO Add message...
		$ret = $object->dao->newMessage($user, $action, 0, 1);




		if (!$error)
		{
			$action = 'view';
		}
	}
	*/

	if ($error || $errors) {
		setEventMessages($object->error, $object->errors, 'errors');
		if ($action == "add_message")
		{
			$action = 'presend';
		} else {
			$action = '';
		}
	}
}
//var_dump($action);
//$object->doActions($action);

// Actions to send emails (for ticket, we need to manage the addfile and removefile only)
$triggersendname = 'CANDIDATURE_SENTBYMAIL';
$paramname = 'id';
$autocopy = 'MAIN_MAIL_AUTOCOPY_CANDIDATURE_TO'; // used to know the automatic BCC to add
$trackid = 'can'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';



/*
 * View
 */

$form = new Form($db);

if (!$conf->global->RECRUITMENT_ENABLE_PUBLIC_INTERFACE) {
	print '<div class="error">'.$langs->trans('PublicInterfaceForbidden').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array('/ticket/css/styles.css.php');

llxHeaderRecruitment($langs->trans("Jobs"), "", 0, 0, $arrayofjs, $arrayofcss);

print '<div class="ticketpublicarea">';

if ($action == "view" || $action == "presend" || $action == "close" || $action == "confirm_public_close") {
	if ($display_ticket)
	{
		// Confirmation close
		if ($action == 'close') {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?track_id=".$track_id, $langs->trans("CloseATicket"), $langs->trans("ConfirmCloseAticket"), "confirm_public_close", '', '', 1);
		}

		print '<div id="form_view" class="margintoponly">';

		print '<table class="ticketpublictable centpercent tableforfield">';

		// Ref
		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>';
		print $object->dao->ref;
		print '</td></tr>';

		// Tracking ID
		print '<tr><td>'.$langs->trans("TicketTrackId").'</td><td>';
		print $object->dao->track_id;
		print '</td></tr>';

		// Subject
		print '<tr><td>'.$langs->trans("Subject").'</td><td>';
		print $object->dao->subject;
		print '</td></tr>';

		// Statut
		print '<tr><td>'.$langs->trans("Status").'</td><td>';
		print $object->dao->getLibStatut(2);
		print '</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("Type").'</td><td>';
		print $object->dao->type_label;
		print '</td></tr>';

		// Category
		print '<tr><td>'.$langs->trans("Category").'</td><td>';
		print $object->dao->category_label;
		print '</td></tr>';

		// Severity
		print '<tr><td>'.$langs->trans("Severity").'</td><td>';
		print $object->dao->severity_label;
		print '</td></tr>';

		// Creation date
		print '<tr><td>'.$langs->trans("DateCreation").'</td><td>';
		print dol_print_date($object->dao->datec, 'dayhour');
		print '</td></tr>';

		// Author
		print '<tr><td>'.$langs->trans("Author").'</td><td>';
		if ($object->dao->fk_user_create > 0) {
			$langs->load("users");
			$fuser = new User($db);
			$fuser->fetch($object->dao->fk_user_create);
			print $fuser->getFullName($langs);
		} else {
			print dol_escape_htmltag($object->dao->origin_email);
		}

		print '</td></tr>';

		// Read date
		if (!empty($object->dao->date_read)) {
			print '<tr><td>'.$langs->trans("TicketReadOn").'</td><td>';
			print dol_print_date($object->dao->date_read, 'dayhour');
			print '</td></tr>';
		}

		// Close date
		if (!empty($object->dao->date_close)) {
			print '<tr><td>'.$langs->trans("TicketCloseOn").'</td><td>';
			print dol_print_date($object->dao->date_close, 'dayhour');
			print '</td></tr>';
		}

		// User assigned
		print '<tr><td>'.$langs->trans("AssignedTo").'</td><td>';
		if ($object->dao->fk_user_assign > 0) {
			$fuser = new User($db);
			$fuser->fetch($object->dao->fk_user_assign);
			print $fuser->getFullName($langs, 1);
		}
		print '</td></tr>';

		// Progression
		print '<tr><td>'.$langs->trans("Progression").'</td><td>';
		print ($object->dao->progress > 0 ? $object->dao->progress : '0').'%';
		print '</td></tr>';

		print '</table>';

		print '</div>';

		print '<div style="clear: both; margin-top: 1.5em;"></div>';

		if ($action == 'presend') {
			print load_fiche_titre($langs->trans('AddMessage'), '', 'messages@ticket');

			/*$formticket = new FormTicket($db);

			$formticket->action = "add_message";
			$formticket->track_id = $object->dao->track_id;
			$formticket->id = $object->dao->id;

			$formticket->param = array('track_id' => $object->dao->track_id, 'fk_user_create' => '-1', 'returnurl' => DOL_URL_ROOT.'/public/ticket/view.php');

			$formticket->withfile = 2;
			$formticket->withcancel = 1;

			$formticket->showMessageForm('100%');
			*/
			print 'TODO Show message form';
		}

		if ($action != 'presend') {
			print '<form method="post" id="form_view_list" name="form_view_list" enctype="multipart/form-data" action="'.DOL_URL_ROOT.'/public/recruitment/list.php">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="viewlist">';
			print '<input type="hidden" name="ref" value="'.$object->ref.'">';
			print '<input type="hidden" name="email" value="'.$_SESSION['email_customer'].'">';
			//print '<input type="hidden" name="search_fk_status" value="non_closed">';
			print "</form>\n";

			print '<div class="tabsAction">';

			// List ticket
			print '<div class="inline-block divButAction"><a class="left" style="padding-right: 50px" href="javascript:$(\'#form_view_list\').submit();">'.$langs->trans('ViewMyTicketList').'</a></div>';

			if ($object->dao->fk_statut < Ticket::STATUS_CLOSED) {
				// New message
				print '<div class="inline-block divButAction"><a  class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=presend&mode=init&ref='.$object->ref.'">'.$langs->trans('AddMessage').'</a></div>';

				// Close ticket
				if ($object->dao->fk_statut >= Ticket::STATUS_NOT_READ && $object->dao->fk_statut < Ticket::STATUS_CLOSED) {
					print '<div class="inline-block divButAction"><a  class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=close&track_id='.$object->dao->track_id.'">'.$langs->trans('CloseTicket').'</a></div>';
				}
			}

			print '</div>';
		}

		// Message list
		print load_fiche_titre($langs->trans('JobMessagesList'), '', 'object_conversation');
		//$object->viewTicketMessages(false, true, $object->dao);
	} else {
		print '<div class="error">Not Allowed<br><a href="'.$_SERVER['PHP_SELF'].'?ref='.$object->ref.'">'.$langs->trans('Back').'</a></div>';
	}
}

print "</div>";

// End of page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix, $object);

llxFooter('', 'public');

$db->close();
