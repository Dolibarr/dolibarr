<?php
/* Copyright (C) 2013-2016    Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016         Christophe Battarel <christophe@altairis.fr>
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
 *       \file       htdocs/public/ticket/create_ticket.php
 *       \ingroup    ticket
 *       \brief      Display public form to add new ticket
 */

/* We need object $user->default_values
if (!defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}*/
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOLOGIN')) {
	define("NOLOGIN", 1); // This means this output page does not require to be logged.
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'mails', 'ticket'));

// Get parameters
$id = GETPOST('id', 'int');
$msg_id = GETPOST('msg_id', 'int');

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

$backtopage = '';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('publicnewticketcard', 'globalcard'));

$object = new Ticket($db);
$extrafields = new ExtraFields($db);
$contacts = array();
$with_contact = null;
if (!empty($conf->global->TICKET_CREATE_THIRD_PARTY_WITH_CONTACT_IF_NOT_EXIST)) {
	$with_contact = new Contact($db);
}

$extrafields->fetch_name_optionals_label($object->table_element);

if (empty($conf->ticket->enabled)) {
	accessforbidden('', 0, 0, 1);
}


/*
 * Actions
 */

$parameters = array(
	'id' => $id,
);
// Note that $action and $object may have been modified by some hooks
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
// Add file in email form
if (empty($reshook)) {
	if ($cancel) {
		$backtopage = DOL_URL_ROOT.'/public/ticket/index.php';

		header("Location: ".$backtopage);
		exit;
	}

	if (GETPOST('addfile', 'alpha') && !GETPOST('save', 'alpha')) {
		////$res = $object->fetch('','',GETPOST('track_id'));
		////if($res > 0)
		////{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Set tmp directory TODO Use a dedicated directory for temp mails files
		$vardir = $conf->ticket->dir_output;
		$upload_dir_tmp = $vardir.'/temp/'.session_id();
		if (!dol_is_dir($upload_dir_tmp)) {
			dol_mkdir($upload_dir_tmp);
		}

		dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile', '', null, '', 0);
		$action = 'create_ticket';
		////}
	}

	// Remove file
	if (GETPOST('removedfile', 'alpha') && !GETPOST('save', 'alpha')) {
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Set tmp directory
		$vardir = $conf->ticket->dir_output.'/';
		$upload_dir_tmp = $vardir.'/temp/'.session_id();

		// TODO Delete only files that was uploaded from email form
		dol_remove_file_process(GETPOST('removedfile'), 0, 0);
		$action = 'create_ticket';
	}

	if ($action == 'create_ticket' && GETPOST('save', 'alpha')) {
		$error = 0;
		$origin_email = GETPOST('email', 'alpha');
		if (empty($origin_email)) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
			$action = '';
		} else {
			// Search company saved with email
			$searched_companies = $object->searchSocidByEmail($origin_email, '0');

			// Chercher un contact existant avec cette adresse email
			// Le premier contact trouvé est utilisé pour déterminer le contact suivi
			$contacts = $object->searchContactByEmail($origin_email);

			// Option to require email exists to create ticket
			if (!empty($conf->global->TICKET_EMAIL_MUST_EXISTS) && !$contacts[0]->socid) {
				$error++;
				array_push($object->errors, $langs->trans("ErrorEmailMustExistToCreateTicket"));
				$action = '';
			}
		}

		$contact_lastname = '';
		$contact_firstname = '';
		$company_name = '';
		$contact_phone = '';
		if ($with_contact) {
			// set linked contact to add in form
			if (is_array($contacts) && count($contacts) == 1) {
				$with_contact = current($contacts);
			}

			// check mandatory fields on contact
			$contact_lastname = trim(GETPOST('contact_lastname', 'alphanohtml'));
			$contact_firstname = trim(GETPOST('contact_firstname', 'alphanohtml'));
			$company_name = trim(GETPOST('company_name', 'alphanohtml'));
			$contact_phone = trim(GETPOST('contact_phone', 'alphanohtml'));
			if (!($with_contact->id > 0)) {
				// check lastname
				if (empty($contact_lastname)) {
					$error++;
					array_push($object->errors, $langs->trans('ErrorFieldRequired', $langs->transnoentities('Lastname')));
					$action = '';
				}
				// check firstname
				if (empty($contact_firstname)) {
					$error++;
					array_push($object->errors, $langs->trans('ErrorFieldRequired', $langs->transnoentities('Firstname')));
					$action = '';
				}
			}
		}

		if (!GETPOST("subject", "restricthtml")) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Subject")));
			$action = '';
		}
		if (!GETPOST("message", "restricthtml")) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Message")));
			$action = '';
		}

		// Check email address
		if (!empty($origin_email) && !isValidEmail($origin_email)) {
			$error++;
			array_push($object->errors, $langs->trans("ErrorBadEmailAddress", $langs->transnoentities("email")));
			$action = '';
		}

		// Check Captcha code if is enabled
		if (!empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA_TICKET)) {
			$sessionkey = 'dol_antispam_value';
			$ok = (array_key_exists($sessionkey, $_SESSION) === true && (strtolower($_SESSION[$sessionkey]) === strtolower(GETPOST('code', 'restricthtml'))));
			if (!$ok) {
				$error++;
				array_push($object->errors, $langs->trans("ErrorBadValueForCode"));
				$action = '';
			}
		}

		if (!$error) {
			$object->db->begin();

			$object->track_id = generate_random_id(16);

			$object->subject = GETPOST("subject", "restricthtml");
			$object->message = GETPOST("message", "restricthtml");
			$object->origin_email = $origin_email;

			$object->type_code = GETPOST("type_code", 'aZ09');
			$object->category_code = GETPOST("category_code", 'aZ09');
			$object->severity_code = GETPOST("severity_code", 'aZ09');

			if (!is_object($user)) {
				$user = new User($db);
			}

			// create third-party with contact
			$usertoassign = 0;
			if ($with_contact && !($with_contact->id > 0)) {
				$company = new Societe($db);
				if (!empty($company_name)) {
					$company->name = $company_name;
				} else {
					$company->particulier = 1;
					$company->name = dolGetFirstLastname($contact_firstname, $contact_lastname);
				}
				$result = $company->create($user);
				if ($result < 0) {
					$error++;
					$errors = ($company->error ? array($company->error) : $company->errors);
					array_push($object->errors, $errors);
					$action = 'create_ticket';
				}

				// create contact and link to this new company
				if (!$error) {
					$with_contact->email = $origin_email;
					$with_contact->lastname = $contact_lastname;
					$with_contact->firstname = $contact_firstname;
					$with_contact->socid = $company->id;
					$with_contact->phone_pro = $contact_phone;
					$result = $with_contact->create($user);
					if ($result < 0) {
						$error++;
						$errors = ($with_contact->error ? array($with_contact->error) : $with_contact->errors);
						array_push($object->errors, $errors);
						$action = 'create_ticket';
					} else {
						$contacts = array($with_contact);
					}
				}
			}

			if (is_array($searched_companies)) {
				$object->fk_soc = $searched_companies[0]->id;
			}

			if (is_array($contacts) and count($contacts) > 0) {
				$object->fk_soc = $contacts[0]->socid;
				$usertoassign = $contacts[0]->id;
			}

			$ret = $extrafields->setOptionalsFromPost(null, $object);

			// Generate new ref
			$object->ref = $object->getDefaultRef();

			$object->context['disableticketemail'] = 1; // Disable emails sent by ticket trigger when creation is done from this page, emails are already sent later

			$id = $object->create($user);
			if ($id <= 0) {
				$error++;
				$errors = ($object->error ? array($object->error) : $object->errors);
				array_push($object->errors, $object->error ? array($object->error) : $object->errors);
				$action = 'create_ticket';
			}

			if (!$error && $id > 0) {
				if ($usertoassign > 0) {
					$object->add_contact($usertoassign, "SUPPORTCLI", 'external', 0);
				}
			}

			if (!$error) {
				$object->db->commit();
				$action = "infos_success";
			} else {
				$object->db->rollback();
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'create_ticket';
			}

			if (!$error) {
				$res = $object->fetch($id);
				if ($res) {
					// Create form object
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					$formmail = new FormMail($db);

					// Init to avoid errors
					$filepath = array();
					$filename = array();
					$mimetype = array();

					$attachedfiles = $formmail->get_attached_files();
					$filepath = $attachedfiles['paths'];
					$filename = $attachedfiles['names'];
					$mimetype = $attachedfiles['mimes'];

					// Send email to customer

					$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketNewEmailSubject', $object->ref, $object->track_id);
					$message  = ($conf->global->TICKET_MESSAGE_MAIL_NEW ? $conf->global->TICKET_MESSAGE_MAIL_NEW : $langs->transnoentities('TicketNewEmailBody')).'<br><br>';
					$message .= $langs->transnoentities('TicketNewEmailBodyInfosTicket').'<br>';

					$url_public_ticket = ($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE.'/view.php' : dol_buildpath('/public/ticket/view.php', 2)).'?track_id='.$object->track_id;
					$infos_new_ticket = $langs->transnoentities('TicketNewEmailBodyInfosTrackId', '<a href="'.$url_public_ticket.'" rel="nofollow noopener">'.$object->track_id.'</a>').'<br>';
					$infos_new_ticket .= $langs->transnoentities('TicketNewEmailBodyInfosTrackUrl').'<br><br>';

					$message .= $infos_new_ticket;
					$message .= getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE', $langs->transnoentities('TicketMessageMailSignatureText', $mysoc->name));

					$sendto = GETPOST('email', 'alpha');

					$from = $conf->global->MAIN_INFO_SOCIETE_NOM.' <'.getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM').'>';
					$replyto = $from;
					$sendtocc = '';
					$deliveryreceipt = 0;

					if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
						$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
						$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
					}
					include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
					$mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1, '', '', 'tic'.$object->id, '', 'ticket');
					if ($mailfile->error || $mailfile->errors) {
						setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					} else {
						$result = $mailfile->sendfile();
					}
					if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
						$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
					}

					// Send email to TICKET_NOTIFICATION_EMAIL_TO
					$sendto = $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
					if ($sendto) {
						$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketNewEmailSubjectAdmin', $object->ref, $object->track_id);
						$message_admin = $langs->transnoentities('TicketNewEmailBodyAdmin', $object->track_id).'<br><br>';
						$message_admin .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
						$message_admin .= '<li>'.$langs->trans('Type').' : '.$object->type_label.'</li>';
						$message_admin .= '<li>'.$langs->trans('Category').' : '.$object->category_label.'</li>';
						$message_admin .= '<li>'.$langs->trans('Severity').' : '.$object->severity_label.'</li>';
						$message_admin .= '<li>'.$langs->trans('From').' : '.$object->origin_email.'</li>';
						// Extrafields
						$extrafields->fetch_name_optionals_label($object->table_element);
						if (is_array($object->array_options) && count($object->array_options) > 0) {
							foreach ($object->array_options as $key => $value) {
								$key = substr($key, 8); // remove "options_"
								$message_admin .= '<li>'.$langs->trans($extrafields->attributes[$object->table_element]['label'][$key]).' : '.$extrafields->showOutputField($key, $value, '', $object->table_element).'</li>';
							}
						}
						$message_admin .= '</ul>';

						$message_admin .= '<p>'.$langs->trans('Message').' : <br>'.$object->message.'</p>';
						$message_admin .= '<p><a href="'.dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id.'" rel="nofollow noopener">'.$langs->trans('SeeThisTicketIntomanagementInterface').'</a></p>';

						$from = $conf->global->MAIN_INFO_SOCIETE_NOM.' <'.$conf->global->TICKET_NOTIFICATION_EMAIL_FROM.'>';
						$replyto = $from;

						if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
							$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
							$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
						}
						include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
						$mailfile = new CMailFile($subject, $sendto, $from, $message_admin, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1, '', '', 'tic'.$object->id, '', 'ticket');
						if ($mailfile->error || $mailfile->errors) {
							setEventMessages($mailfile->error, $mailfile->errors, 'errors');
						} else {
							$result = $mailfile->sendfile();
						}
						if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
							$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
						}
					}
				}

				// Copy files into ticket directory
				$destdir = $conf->ticket->dir_output.'/'.$object->ref;
				if (!dol_is_dir($destdir)) {
					dol_mkdir($destdir);
				}
				foreach ($filename as $i => $val) {
					dol_move($filepath[$i], $destdir.'/'.$filename[$i], 0, 1);
					$formmail->remove_attached_files($i);
				}

				//setEventMessages($langs->trans('YourTicketSuccessfullySaved'), null, 'mesgs');

				// Make a redirect to avoid to have ticket submitted twice if we make back
				$messagetoshow = $langs->trans('MesgInfosPublicTicketCreatedWithTrackId', '{s1}', '{s2}');
				$messagetoshow = str_replace(array('{s1}', '{s2}'), array('<strong>'.$object->track_id.'</strong>', '<strong>'.$object->ref.'</strong>'), $messagetoshow);
				setEventMessages($messagetoshow, null, 'warnings');
				setEventMessages($langs->trans('PleaseRememberThisId'), null, 'warnings');
				header("Location: index.php".(!empty($entity) && !empty($conf->multicompany->enabled)?'?entity='.$entity:''));
				exit;
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


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
$arrayofcss = array('/opensurvey/css/style.css', '/ticket/css/styles.css.php');

llxHeaderTicket($langs->trans("CreateTicket"), "", 0, 0, $arrayofjs, $arrayofcss);


print '<div class="ticketpublicarea">';

if ($action != "infos_success") {
	$formticket->withfromsocid = isset($socid) ? $socid : $user->socid;
	$formticket->withtitletopic = 1;
	$formticket->withcompany = 0;
	$formticket->withusercreate = 1;
	$formticket->fk_user_create = 0;
	$formticket->withemail = 1;
	$formticket->ispublic = 1;
	$formticket->withfile = 2;
	$formticket->action = 'create_ticket';
	$formticket->withcancel = 1;

	$formticket->param = array('returnurl' => $_SERVER['PHP_SELF'].($conf->entity > 1 ? '?entity='.$conf->entity : ''));

	print load_fiche_titre($langs->trans('NewTicket'), '', '', 0, 0, 'marginleftonly');

	if (empty($conf->global->TICKET_NOTIFICATION_EMAIL_FROM)) {
		$langs->load("errors");
		print '<div class="error">';
		print $langs->trans("ErrorFieldRequired", $langs->transnoentities("TicketEmailNotificationFrom")).'<br>';
		print $langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentities("Ticket"));
		print '</div>';
	} else {
		//print '<div class="info marginleftonly marginrightonly">'.$langs->trans('TicketPublicInfoCreateTicket').'</div>';
		$formticket->showForm(0, 'edit', 1, $with_contact);
	}
}

print '</div>';

// End of page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 1, $suffix, $object);

llxFooter('', 'public');

$db->close();
