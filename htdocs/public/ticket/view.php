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
 *       \file       htdocs/public/ticket/view.php
 *       \ingroup    ticket
 *       \brief      Public file to add and manage ticket
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
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket"));

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$cancel   = GETPOST('cancel', 'alpha');
$action   = GETPOST('action', 'aZ09');
$email    = GETPOST('email', 'alpha');

if (GETPOST('btn_view_ticket')) {
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['email_customer'])) {
	$email = $_SESSION['email_customer'];
}

$object = new ActionsTicket($db);


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
	$action = 'view_ticket';
}

if ($action == "view_ticket" || $action == "presend" || $action == "close" || $action == "confirm_public_close" || $action == "add_message") {
	$error = 0;
	$display_ticket = false;
	if (!strlen($track_id)) {
		$error++;
		array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("TicketTrackId")));
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
		$ret = $object->fetch('', '', $track_id);
		if ($ret && $object->dao->id > 0) {
			// Check if emails provided is the one of author
			$emailofticket = CMailFile::getValidAddress($object->dao->origin_email, 2);
			if ($emailofticket == $email)
			{
				$display_ticket = true;
				$_SESSION['email_customer'] = $email;
			}
			// Check if emails provided is inside list of contacts
			else {
				$contacts = $object->dao->liste_contact(-1, 'external');
				foreach ($contacts as $contact) {
					if ($contact['email'] == $email) {
						$display_ticket = true;
						$_SESSION['email_customer'] = $email;
						break;
					} else {
						$display_ticket = false;
					}
				}
			}
			// Check email of thirdparty of ticket
			if ($object->dao->fk_soc > 0 || $object->dao->socid > 0) {
				$object->dao->fetch_thirdparty();
				if ($email == $object->dao->thirdparty->email) {
					$display_ticket = true;
					$_SESSION['email_customer'] = $email;
				}
			}
			// Check if email is email of creator
			if ($object->dao->fk_user_create > 0)
			{
				$tmpuser = new User($db);
				$tmpuser->fetch($object->dao->fk_user_create);
				if ($email == $tmpuser->email) {
					$display_ticket = true;
					$_SESSION['email_customer'] = $email;
				}
			}
			// Check if email is email of creator
			if ($object->dao->fk_user_assign > 0 && $object->dao->fk_user_assign != $object->dao->fk_user_create)
			{
				$tmpuser = new User($db);
				$tmpuser->fetch($object->dao->fk_user_assign);
				if ($email == $tmpuser->email) {
					$display_ticket = true;
					$_SESSION['email_customer'] = $email;
				}
			}
		} else {
			$error++;
			array_push($object->errors, $langs->trans("ErrorTicketNotFound", $track_id));
			$action = '';
		}
	}

	if (!$error && $action == 'confirm_public_close' && $display_ticket)
	{
		if ($object->dao->close($user)) {
			setEventMessages($langs->trans('TicketMarkedAsClosed'), null, 'mesgs');

			$url = 'view.php?action=view_ticket&track_id='.GETPOST('track_id', 'alpha');
			header("Location: ".$url);
		} else {
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if (!$error && $action == "add_message" && $display_ticket && GETPOSTISSET('btn_add_message'))
	{
		// TODO Add message...
		$ret = $object->dao->newMessage($user, $action, 0);




		if (!$error)
		{
			$action = 'view_ticket';
		}
	}

	if ($error || $errors) {
		setEventMessages($object->error, $object->errors, 'errors');
		if ($action == "add_message")
		{
			$action = 'presend';
		}
		else
		{
			$action = '';
		}
	}
}
//var_dump($action);
//$object->doActions($action);

// Actions to send emails (for ticket, we need to manage the addfile and removefile only)
$triggersendname = 'TICKET_SENTBYMAIL';
$paramname = 'id';
$autocopy = 'MAIN_MAIL_AUTOCOPY_TICKET_TO'; // used to know the automatic BCC to add
$trackid = 'tic'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';



/*
 * View
 */

$form = new Form($db);
$formticket = new FormTicket($db);

if (!$conf->global->TICKET_ENABLE_PUBLIC_INTERFACE) {
	print '<div class="error">'.$langs->trans('TicketPublicInterfaceForbidden').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array('/ticket/css/styles.css.php');

llxHeaderTicket($langs->trans("Tickets"), "", 0, 0, $arrayofjs, $arrayofcss);

print '<div class="ticketpublicarea">';

if ($action == "view_ticket" || $action == "presend" || $action == "close" || $action == "confirm_public_close") {
	if ($display_ticket)
	{
		// Confirmation close
		if ($action == 'close') {
			print $form->formconfirm($_SERVER["PHP_SELF"]."?track_id=".$track_id, $langs->trans("CloseATicket"), $langs->trans("ConfirmCloseAticket"), "confirm_public_close", '', '', 1);
		}

		print '<div id="form_view_ticket" class="margintoponly">';

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
			print load_fiche_titre($langs->trans('TicketAddMessage'), '', 'messages@ticket');

			$formticket = new FormTicket($db);

			$formticket->action = "add_message";
			$formticket->track_id = $object->dao->track_id;
			$formticket->id = $object->dao->id;

			$formticket->param = array('track_id' => $object->dao->track_id, 'fk_user_create' => '-1', 'returnurl' => DOL_URL_ROOT.'/public/ticket/view.php');

			$formticket->withfile = 2;
			$formticket->withcancel = 1;

			$formticket->showMessageForm('100%');
		}

		if ($action != 'presend') {
			print '<form method="post" id="form_view_ticket_list" name="form_view_ticket_list" enctype="multipart/form-data" action="'.DOL_URL_ROOT.'/public/ticket/list.php">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="view_ticketlist">';
			print '<input type="hidden" name="track_id" value="'.$object->dao->track_id.'">';
			print '<input type="hidden" name="email" value="'.$_SESSION['email_customer'].'">';
			//print '<input type="hidden" name="search_fk_status" value="non_closed">';
			print "</form>\n";

			print '<div class="tabsAction">';

			// List ticket
			print '<div class="inline-block divButAction"><a class="left" style="padding-right: 50px" href="javascript:$(\'#form_view_ticket_list\').submit();">'.$langs->trans('ViewMyTicketList').'</a></div>';

			if ($object->dao->fk_statut < Ticket::STATUS_CLOSED) {
				// New message
				print '<div class="inline-block divButAction"><a  class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=presend&mode=init&track_id='.$object->dao->track_id.'">'.$langs->trans('AddMessage').'</a></div>';

				// Close ticket
				if ($object->dao->fk_statut >= Ticket::STATUS_NOT_READ && $object->dao->fk_statut < Ticket::STATUS_CLOSED) {
					print '<div class="inline-block divButAction"><a  class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=close&track_id='.$object->dao->track_id.'">'.$langs->trans('CloseTicket').'</a></div>';
				}
			}

			print '</div>';
		}

		// Message list
		print load_fiche_titre($langs->trans('TicketMessagesList'), '', 'object_conversation');
		$object->viewTicketMessages(false, true, $object->dao);
	}
	else
	{
		print '<div class="error">Not Allowed<br><a href="'.$_SERVER['PHP_SELF'].'?track_id='.$object->dao->track_id.'">'.$langs->trans('Back').'</a></div>';
	}
} else {
	print '<div class="center opacitymedium margintoponly marginbottomonly">'.$langs->trans("TicketPublicMsgViewLogIn").'</div>';

	print '<div id="form_view_ticket">';
	print '<form method="post" name="form_view_ticket"  enctype="multipart/form-data" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="view_ticket">';

	print '<p><label for="track_id" style="display: inline-block; width: 30%; "><span class="fieldrequired">'.$langs->trans("TicketTrackId").'</span></label>';
	print '<input size="30" id="track_id" name="track_id" value="'.(GETPOST('track_id', 'alpha') ? GETPOST('track_id', 'alpha') : '').'" />';
	print '</p>';

	print '<p><label for="email" style="display: inline-block; width: 30%; "><span class="fieldrequired">'.$langs->trans('Email').'</span></label>';
	print '<input size="30" id="email" name="email" value="'.(GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : $_SESSION['customer_email']).'" />';
	print '</p>';

	print '<p style="text-align: center; margin-top: 1.5em;">';
	print '<input class="button" type="submit" name="btn_view_ticket" value="'.$langs->trans('ViewTicket').'" />';
	print "</p>\n";

	print "</form>\n";
	print "</div>\n";
}

print "</div>";

// End of page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix, $object);

llxFooter('', 'public');

$db->close();
