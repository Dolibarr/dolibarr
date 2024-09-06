<?php
/* Copyright (C) 2015-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021 Nicolas ZABOURI	<info@inovea-conseil.com>
 * Copyright (C) 2018 	   Juanjo Menent  <jmenent@2byte.es>
 * Copyright (C) 2019 	   Ferran Marcet  <fmarcet@2byte.es>
 * Copyright (C) 2019-2021 Frédéric France <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * or see https://www.gnu.org/
 */

/**
 *    \file            htdocs/core/actions_massactions.inc.php
 *  \brief            Code for actions done with massaction button (send by email, merge pdf, delete, ...)
 */


// $massaction must be defined
// $objectclass and $objectlabel must be defined
// $parameters, $object, $action must be defined for the hook.

// $permissiontoread, $permissiontoadd, $permissiontodelete, $permissiontoclose may be defined
// $uploaddir may be defined (example to $conf->project->dir_output."/";)
// $toselect may be defined
// $diroutputmassaction may be defined


// Protection
if (empty($objectclass) || empty($uploaddir)) {
	dol_print_error(null, 'include of actions_massactions.inc.php is done but var $objectclass or $uploaddir was not defined');
	exit;
}

// Mass actions. Controls on number of lines checked.
$maxformassaction = (!getDolGlobalString('MAIN_LIMIT_FOR_MASS_ACTIONS') ? 1000 : $conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
if (!empty($massaction) && is_array($toselect) && count($toselect) < 1) {
	$error++;
	setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
}
if (!$error && is_array($toselect) && count($toselect) > $maxformassaction) {
	setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
	$error++;
}

if (!$error && $massaction == 'confirm_presend_attendees' && !GETPOST('sendmail')) {  // If we do not choose button send (for example when we change template or limit), we must not send email, but keep on send email form
	$massaction = 'presend_attendees';
}
if (!$error && $massaction == 'confirm_presend_attendees') {
	$resaction = '';
	$nbsent = 0;
	$nbignored = 0;
	$langs->load("mails");
	include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

	$listofobjectid = array();

	$listofobjectref = array();
	$oneemailperrecipient = (GETPOSTINT('oneemailperrecipient') ? 1 : 0);

	if (!$error) {
		require_once DOL_DOCUMENT_ROOT . '/eventorganization/class/conferenceorboothattendee.class.php';
		$attendee = new ConferenceOrBoothAttendee($db);
		$listofselectedid = array();
		$listofselectedref = array();
		$objecttmp = new $objectclass($db);

		foreach ($toselect as $toselectid) {
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$attendees = $attendee->fetchAll();
				if (is_array($attendees) && count($attendees) > 0) {
					foreach ($attendees as $attmail) {
						if (!empty($attmail->email)) {
							$attmail->fetch_thirdparty();
							$listofselectedid[$attmail->email] = $attmail;
							$listofselectedref[$attmail->email] = $objecttmp;
						}
					}
				}
			}
		}
	}
	'@phan-var-force CommonObject $objecttmp';

	// Check mandatory parameters
	if (GETPOST('fromtype', 'alpha') === 'user' && empty($user->email)) {
		$error++;
		setEventMessages($langs->trans("NoSenderEmailDefined"), null, 'warnings');
		$massaction = 'presend_attendees';
	}

	$receiver = GETPOST('receiver', 'alphawithlgt');
	if (!is_array($receiver)) {
		if (empty($receiver) || $receiver == '-1') {
			$receiver = array();
		} else {
			$receiver = array($receiver);
		}
	}
	if (!trim(GETPOST('sendto', 'alphawithlgt')) && count($receiver) == 0 && count($listofselectedid) == 0) {    // if only one recipient, receiver is mandatory
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Recipient")), null, 'warnings');
		$massaction = 'presend_attendees';
	}

	if (!GETPOST('subject', 'restricthtml')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MailTopic")), null, 'warnings');
		$massaction = 'presend_attendees';
	}

	if (!$error) {
		$objecttmp->fetch_thirdparty();
		foreach ($listofselectedid as $email => $attendees) {
			$sendto = '';
			$sendtocc = '';
			$sendtobcc = '';
			$sendtoid = array();

			// Define $sendto
			$sendto = $attendees->thirdparty->name . '<' . trim($attendees->email) . '>';

			// Define $sendtocc
			$receivercc = GETPOST('receivercc', 'alphawithlgt');
			if (!is_array($receivercc)) {
				if ($receivercc == '-1') {
					$receivercc = array();
				} else {
					$receivercc = array($receivercc);
				}
			}
			$tmparray = array();
			if (trim(GETPOST('sendtocc', 'alphawithlgt'))) {
				$tmparray[] = trim(GETPOST('sendtocc', 'alphawithlgt'));
			}
			$sendtocc = implode(',', $tmparray);


			$langs->load("commercial");

			$reg = array();
			$fromtype = GETPOST('fromtype');
			if ($fromtype === 'user') {
				$from = $user->getFullName($langs) . ' <' . $user->email . '>';
			} elseif ($fromtype === 'company') {
				$from = getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . ' <' . getDolGlobalString('MAIN_INFO_SOCIETE_MAIL') . '>';
			} elseif (preg_match('/user_aliases_(\d+)/', $fromtype, $reg)) {
				$tmp = explode(',', $user->email_aliases);
				$from = trim($tmp[((int) $reg[1] - 1)]);
			} elseif (preg_match('/global_aliases_(\d+)/', $fromtype, $reg)) {
				$tmp = explode(',', getDolGlobalString('MAIN_INFO_SOCIETE_MAIL_ALIASES'));
				$from = trim($tmp[((int) $reg[1] - 1)]);
			} elseif (preg_match('/senderprofile_(\d+)_(\d+)/', $fromtype, $reg)) {
				$sql = "SELECT rowid, label, email FROM " . MAIN_DB_PREFIX . "c_email_senderprofile WHERE rowid = " . (int) $reg[1];
				$resql = $db->query($sql);
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$from = dol_string_nospecial($obj->label, ' ', array(",")) . ' <' . $obj->email . '>';
				}
			} else {
				$from = dol_string_nospecial(GETPOST('fromname'), ' ', array(",")) . ' <' . GETPOST('frommail') . '>';
			}

			$replyto = $from;
			$subject = GETPOST('subject', 'restricthtml');
			$message = GETPOST('message', 'restricthtml');

			$sendtobcc = GETPOST('sendtoccc', 'alphawithlgt');

			// $objecttmp is a real object or an empty object if we choose to send one email per thirdparty instead of one per object
			// Make substitution in email content
			$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $attendees);

			if (getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY')) {
				$urlwithouturlroot = preg_replace('/' . preg_quote(DOL_URL_ROOT, '/') . '$/i', '', trim($dolibarr_main_url_root));
				$urlwithroot = $urlwithouturlroot . DOL_URL_ROOT;
				$url_link = $urlwithroot . '/public/agenda/agendaexport.php?format=ical' . ($conf->entity > 1 ? "&entity=" . $conf->entity : "");
				$url_link .= '&exportkey=' . ($conf->global->MAIN_AGENDA_XCAL_EXPORTKEY ? urlencode(getDolGlobalString('MAIN_AGENDA_XCAL_EXPORTKEY')) : '...');
				$url_link .= "&project=" . $listofselectedref[$email]->fk_project . '&module=' . urlencode('@eventorganization') . '&status=' . ConferenceOrBooth::STATUS_CONFIRMED;
				$html_link = '<a href="' . $url_link . '">' . $langs->trans('DownloadICSLink') . '</a>';
			}
			$substitutionarray['__EVENTORGANIZATION_ICS_LINK__'] = $html_link;
			$substitutionarray['__EVENTORGANIZATION_URL_LINK__'] = $url_link;

			$parameters = array('mode' => 'formemail');

			if (!empty($listofobjectref)) {
				$parameters['listofobjectref'] = $listofobjectref;
			}

			complete_substitutions_array($substitutionarray, $langs, $attendees, $parameters);

			$subjectreplaced = make_substitutions($subject, $substitutionarray);
			$messagereplaced = make_substitutions($message, $substitutionarray);


			if (empty($sendcontext)) {
				$sendcontext = 'standard';
			}

			// Send mail (substitutionarray must be done just before this)
			require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
			$mailfile = new CMailFile($subjectreplaced, $sendto, $from, $messagereplaced, array(), array(), array(), $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', "attendees_".$attendees->id, '', $sendcontext);
			if ($mailfile->error) {
				$resaction .= '<div class="error">' . $mailfile->error . '</div>';
			} else {
				$result = $mailfile->sendfile();
				if ($result) {
					$resaction .= $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($sendto, 2)) . '<br>'; // Must not contain "
					$error = 0;

					dol_syslog("Try to insert email event into agenda for objid=" . $attendees->id . " => objectobj=" . get_class($attendees));

					$actionmsg = $langs->transnoentities('MailSentByTo', $from, $sendto);
					if ($message) {
						if ($sendtocc) {
							$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
						}
						$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subjectreplaced);
						$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
						$actionmsg = dol_concatdesc($actionmsg, $messagereplaced);
					}
					$actionmsg2 = '';

					$objectobj2 = $listofselectedref[$email];
					// Initialisation donnees
					$objectobj2->actionmsg = $actionmsg; // Long text
					$objectobj2->actionmsg2 = $actionmsg2; // Short text
					$objectobj2->fk_element = $objectobj2->id;
					$objectobj2->elementtype = $objectobj2->element;

					$triggername = 'CONFERENCEORBOOTHATTENDEE_SENTBYMAIL';
					if (!empty($triggername)) {
						// Call trigger
						$result = $objectobj2->call_trigger($triggername, $user);
						if ($result < 0) {
							$error++;
						}
						// End call triggers

						if ($error) {
							setEventMessages($db->lasterror(), $objectobj2->errors, 'errors');
							dol_syslog("Error in trigger " . $triggername . ' ' . $db->lasterror(), LOG_ERR);
						}
					}

					$nbsent++; // Nb of object sent
				} else {
					$langs->load("other");
					if ($mailfile->error) {
						$resaction .= $langs->trans('ErrorFailedToSendMail', $from, $sendto);
						$resaction .= '<br><div class="error">' . $mailfile->error . '</div>';
					} elseif (getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
						$resaction .= '<div class="warning">No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS</div>';
					} else {
						$resaction .= $langs->trans('ErrorFailedToSendMail', $from, $sendto) . '<br><div class="error">(unhandled error)</div>';
					}
				}
			}
		}
	}
	$resaction .= ($resaction ? '<br>' : $resaction);
	$resaction .= '<strong>' . $langs->trans("ResultOfMailSending") . ':</strong><br>' . "\n";
	$resaction .= $langs->trans("NbSelected") . ': ' . count($toselect) . "\n<br>";
	$resaction .= $langs->trans("NbIgnored") . ': ' . ($nbignored ? $nbignored : 0) . "\n<br>";
	$resaction .= $langs->trans("NbSent") . ': ' . ($nbsent ? $nbsent : 0) . "\n<br>";

	if ($nbsent) {
		$action = ''; // Do not show form post if there was at least one successful sent
		//setEventMessages($langs->trans("EMailSentToNRecipients", $nbsent.'/'.count($toselect)), null, 'mesgs');
		setEventMessages($langs->trans("EMailSentForNElements", $nbsent . '/' . count($toselect)), null, 'mesgs');
		setEventMessages($resaction, null, 'mesgs');
	} else {
		//setEventMessages($langs->trans("EMailSentToNRecipients", 0), null, 'warnings');  // May be object has no generated PDF file
		setEventMessages($resaction, null, 'warnings');
	}

	$action = 'list';
	$massaction = '';
}



$parameters['toselect'] = $toselect;
$parameters['uploaddir'] = $uploaddir;
$parameters['massaction'] = $massaction;
$parameters['diroutputmassaction'] = isset($diroutputmassaction) ? $diroutputmassaction : null;

$reshook = $hookmanager->executeHooks('doMassActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
