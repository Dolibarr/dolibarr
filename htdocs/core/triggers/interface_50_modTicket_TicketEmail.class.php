<?php
/*
 * Copyright (C) 2014-2016  Jean-François Ferry	<hello@librethic.io>
 * 				 2016       Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2023		Benjamin Falière	<benjamin.faliere@altairis.fr>
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
 *  \file       htdocs/core/triggers/interface_50_modTicket_TicketEmail.class.php
 *  \ingroup    core
 *  \brief      File of trigger for ticket module
 */
require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for ticket module
 */
class InterfaceTicketEmail extends DolibarrTriggers
{
	/**
	 *   Constructor
	 *
	 *   @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "ticket";
		$this->description = "Triggers of the module ticket to send notifications to internal users and to third-parties";
		$this->version = self::VERSION_DOLIBARR; // 'development', 'experimental', 'dolibarr' or version
		$this->picto = 'ticket';
	}

	/**
	 *      Function called when a Dolibarrr business event is done.
	 *      All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers
	 *
	 *      @param  string    $action Event action code
	 *      @param  Ticket    $object Object
	 *      @param  User      $user   Object user
	 *      @param  Translate $langs  Object langs
	 *      @param  conf      $conf   Object conf
	 *      @return int                     Return integer <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		global $mysoc;

		$ok = 0;

		if (empty($conf->ticket) || !isModEnabled('ticket')) {
			return 0; // Module not active, we do nothing
		}

		switch ($action) {
			case 'TICKET_ASSIGNED':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				if ($object->fk_user_assign > 0) {
					if ($object->fk_user_assign != $user->id) {
						$userstat = new User($this->db);
						$res = $userstat->fetch($object->fk_user_assign);
						if ($res > 0) {
							// Send email to notification email
							if (!getDolGlobalString('TICKET_DISABLE_ALL_MAILS')) {
								// Init to avoid errors
								$filepath = array();
								$filename = array();
								$mimetype = array();

								$appli = $mysoc->name;

								// Send email to assigned user
								$subject = '['.$appli.'] '.$langs->transnoentities('TicketAssignedToYou');
								$message = '<p>'.$langs->transnoentities('TicketAssignedEmailBody', $object->track_id, dolGetFirstLastname($user->firstname, $user->lastname))."</p>";
								$message .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
								$message .= '<li>'.$langs->trans('Type').' : '.$object->type_label.'</li>';
								$message .= '<li>'.$langs->trans('Category').' : '.$object->category_label.'</li>';
								$message .= '<li>'.$langs->trans('Severity').' : '.$object->severity_label.'</li>';
								// Extrafields
								if (is_array($object->array_options) && count($object->array_options) > 0) {
									foreach ($object->array_options as $key => $value) {
										$message .= '<li>'.$langs->trans($key).' : '.$value.'</li>';
									}
								}

								$message .= '</ul>';
								$message .= '<p>'.$langs->trans('Message').' : <br>'.$object->message.'</p>';
								$message .= '<p><a href="'.dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id.'">'.$langs->trans('SeeThisTicketIntomanagementInterface').'</a></p>';

								$sendto = $userstat->email;
								$from = dolGetFirstLastname($user->firstname, $user->lastname).'<'.$user->email.'>';

								$message = dol_nl2br($message);

								if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
									$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
									$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
								}
								include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
								$mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, '', '', 0, -1);
								if ($mailfile->error) {
									setEventMessages($mailfile->error, $mailfile->errors, 'errors');
								} else {
									$result = $mailfile->sendfile();
								}
								if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
									$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
								}
							}
						} else {
							$this->error = $userstat->error;
							$this->errors = $userstat->errors;
						}
					}

					// Send an email to the Customer to inform him that his ticket has been taken in charge.
					if (getDolGlobalString('TICKET_NOTIFY_CUSTOMER_TICKET_ASSIGNED') && empty($object->oldcopy->fk_user_assign)) {
						$langs->load('ticket');

						$subject_customer = 'TicketAssignedCustomerEmail';
						$body_customer = 'TicketAssignedCustomerBody';
						$see_ticket_customer = 'TicketNewEmailBodyInfosTrackUrlCustomer';

						// Get all external contacts linked to the ticket
						$linked_contacts = $object->listeContact(-1, 'thirdparty');

						// Initialize and fill recipient addresses at least with origin_email
						$sendto = '';
						$temp_emails = [];
						if ($object->origin_email) {
							$temp_emails[] = $object->origin_email;
						}

						if (!empty($linked_contacts)) {
							foreach ($linked_contacts as $contact) {
								// Avoid the email from being sent twice in case of duplicated contact
								if (!in_array($contact['email'], $temp_emails)) {
									$temp_emails[] = $contact['email'];
								}
							}
						}

						$sendto = implode(", ", $temp_emails);
						unset($temp_emails);
						unset($linked_contacts);

						// If recipients, we send the email
						if ($sendto) {
							$this->composeAndSendCustomerMessage($sendto, $subject_customer, $body_customer, $see_ticket_customer, $object, $langs);
						}
					}
					$ok = 1;
				}
				break;

			case 'TICKET_CREATE':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				$langs->load('ticket');

				$subject_admin = 'TicketNewEmailSubjectAdmin';
				$body_admin = 'TicketNewEmailBodyAdmin';
				$subject_customer = 'TicketNewEmailSubjectCustomer';
				$body_customer = 'TicketNewEmailBodyCustomer';
				$see_ticket_customer = 'TicketNewEmailBodyInfosTrackUrlCustomer';

				// Send email to notification email
				if (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO') && empty($object->context['disableticketemail'])) {
					$sendto = !getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO') ? '' : $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
					if ($sendto) {
						$this->composeAndSendAdminMessage($sendto, $subject_admin, $body_admin, $object, $langs);
					}
				}

				// Send email to customer
				if (!getDolGlobalString('TICKET_DISABLE_CUSTOMER_MAILS') && empty($object->context['disableticketemail']) && $object->notify_tiers_at_create) {
					$sendto = '';

					//if contact selected send to email's contact else send to email's thirdparty

					$contactid = GETPOST('contactid', 'alpha');
					$res = 0;

					if (!empty($contactid)) {
						$contact = new Contact($this->db);
						$res = $contact->fetch($contactid);
					}

					if ($res > 0 && !empty($contact->email) && !empty($contact->statut)) {
						$sendto = $contact->email;
					} elseif (!empty($object->fk_soc)) {
						$object->fetch_thirdparty();
						$sendto = $object->thirdparty->email;
					}

					if ($sendto) {
						$this->composeAndSendCustomerMessage($sendto, $subject_customer, $body_customer, $see_ticket_customer, $object, $langs);
					}
				}

				$ok = 1;
				break;

			case 'TICKET_DELETE':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;

			case 'TICKET_MODIFY':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				break;

			case 'TICKET_CLOSE':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
				$langs->load('ticket');

				$subject_admin = 'TicketCloseEmailSubjectAdmin';
				$body_admin = 'TicketCloseEmailBodyAdmin';
				$subject_customer = 'TicketCloseEmailSubjectCustomer';
				$body_customer = 'TicketCloseEmailBodyCustomer';
				$see_ticket_customer = 'TicketCloseEmailBodyInfosTrackUrlCustomer';

				// Send email to notification email
				if (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO') && empty($object->context['disableticketemail'])) {
					$sendto = !getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO') ? '' : $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
					if ($sendto) {
						$this->composeAndSendAdminMessage($sendto, $subject_admin, $body_admin, $object, $langs);
					}
				}

				// Send email to customer.
				if (!getDolGlobalString('TICKET_DISABLE_CUSTOMER_MAILS') && empty($object->context['disableticketemail'])) {
					$linked_contacts = $object->listeContact(-1, 'thirdparty');
					$linked_contacts = array_merge($linked_contacts, $object->listeContact(-1, 'internal'));
					if (empty($linked_contacts) && getDolGlobalString('TICKET_NOTIFY_AT_CLOSING') && !empty($object->fk_soc)) {
						$object->fetch_thirdparty();
						$linked_contacts[]['email'] = $object->thirdparty->email;
					}

					$contactid = GETPOST('contactid', 'int');
					$res = 0;

					if ($contactid > 0) {
						// TODO This security test has no sens. We must check that $contactid is inside $linked_contacts[]['id'] when $linked_contacts[]['source'] = 'external' or 'thirdparty'
						// Refuse email if not
						$contact = new Contact($this->db);
						$res = $contact->fetch($contactid);
						if (! in_array($contact, $linked_contacts)) {
							$error_msg = $langs->trans('Error'). ': ';
							$error_msg .= $langs->transnoentities('TicketWrongContact');
							setEventMessages($error_msg, [], 'errors');
							$ok = 0;
							break;
						}
					}

					$sendto = '';
					if ($res > 0 && !empty($contact->email) && !empty($contact->statut)) {
						$sendto = $contact->email;
					} elseif (!empty($linked_contacts) && ($contactid == -2 || (GETPOST('massaction', 'alpha') == 'close' && GETPOST('confirm', 'alpha') == 'yes'))) {
						// if sending to all contacts or sending to contacts while mass closing
						$temp_emails = [];
						foreach ($linked_contacts as $contact) {
							$temp_emails[] = $contact['email'];
						}
						$sendto = implode(", ", $temp_emails);
						unset($temp_emails);
						unset($linked_contacts);
					}
					if ($sendto) {
						$this->composeAndSendCustomerMessage($sendto, $subject_customer, $body_customer, $see_ticket_customer, $object, $langs);
					}
				}
				$ok = 1;
				break;
		}

		return $ok;
	}

	/**
	 * Composes and sends a message concerning a ticket, to be sent to admin address.
	 *
	 * @param string 	$sendto			Addresses to send the mail, format "first@address.net, second@address.net," etc.
	 * @param string 	$base_subject	email subject. Non-translated string.
	 * @param string 	$body			email body (first line). Non-translated string.
	 * @param Ticket 	$object			the ticket thet the email refers to
	 * @param Translate $langs			the translation object
	 * @return void
	 */
	private function composeAndSendAdminMessage($sendto, $base_subject, $body, Ticket $object, Translate $langs)
	{
		global $conf, $mysoc;

		// Init to avoid errors
		$filepath = array();
		$filename = array();
		$mimetype = array();

		$appli = $mysoc->name;

		/* Send email to admin */
		$subject = '['.$appli.'] '.$langs->transnoentities($base_subject, $object->ref, $object->track_id);
		$message_admin = $langs->transnoentities($body, $object->track_id).'<br>';
		$message_admin .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
		$message_admin .= '<li>'.$langs->trans('Type').' : '.$langs->getLabelFromKey($this->db, 'TicketTypeShort'.$object->type_code, 'c_ticket_type', 'code', 'label', $object->type_code).'</li>';
		$message_admin .= '<li>'.$langs->trans('TicketCategory').' : '.$langs->getLabelFromKey($this->db, 'TicketCategoryShort'.$object->category_code, 'c_ticket_category', 'code', 'label', $object->category_code).'</li>';
		$message_admin .= '<li>'.$langs->trans('Severity').' : '.$langs->getLabelFromKey($this->db, 'TicketSeverityShort'.$object->severity_code, 'c_ticket_severity', 'code', 'label', $object->severity_code).'</li>';
		$message_admin .= '<li>'.$langs->trans('From').' : '.($object->email_from ? $object->email_from : ($object->fk_user_create > 0 ? $langs->trans('Internal') : '')).'</li>';
		// Extrafields
		$extraFields = new ExtraFields($this->db);
		$extraFields->fetch_name_optionals_label($object->table_element);
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			foreach ($object->array_options as $key => $value) {
				$key = substr($key, 8); // remove "options_"
				$message_admin .= '<li>'.$langs->trans($extraFields->attributes[$object->element]['label'][$key]).' : '.$extraFields->showOutputField($key, $value, '', $object->table_element).'</li>';
			}
		}
		if ($object->fk_soc > 0) {
			$object->fetch_thirdparty();
			$message_admin .= '<li>'.$langs->trans('Company').' : '.$object->thirdparty->name.'</li>';
		}
		$message_admin .= '</ul>';

		$message = $object->message;
		if (!dol_textishtml($message)) {
			$message = dol_nl2br($message);
		}
		$message_admin .= '<p>'.$langs->trans('Message').' : <br><br>'.$message.'</p><br>';
		$message_admin .= '<p><a href="'.dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id.'">'.$langs->trans('SeeThisTicketIntomanagementInterface').'</a></p>';

		$from = getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . '<' . getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM').'>';

		$trackid = 'tic'.$object->id;

		if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
			$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
			$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
		}
		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($subject, $sendto, $from, $message_admin, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', $trackid, '', 'ticket');
		if ($mailfile->error) {
			dol_syslog($mailfile->error, LOG_DEBUG);
		} else {
			$result = $mailfile->sendfile();
		}
		if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
			$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
		}
	}

	/**
	 * Composes and sends a message concerning a ticket, to be sent to customer addresses.
	 *
	 * @param string 	$sendto			Addresses to send the mail, format "first@address.net, second@address.net, " etc.
	 * @param string 	$base_subject	email subject. Non-translated string.
	 * @param string	$body			email body (first line). Non-translated string.
	 * @param string 	$see_ticket		string indicating the ticket public address
	 * @param Ticket 	$object			the ticket thet the email refers to
	 * @param Translate $langs			the translation object
	 * @return void
	 */
	private function composeAndSendCustomerMessage($sendto, $base_subject, $body, $see_ticket, Ticket $object, Translate $langs)
	{
		global $conf, $extrafields, $mysoc, $user;

		// Init to avoid errors
		$filepath = array();
		$filename = array();
		$mimetype = array();

		$appli = $mysoc->name;

		$subject = '['.$appli.'] '.$langs->transnoentities($base_subject);
		$message_customer = $langs->transnoentities($body, $object->track_id).'<br>';
		$message_customer .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
		$message_customer .= '<li>'.$langs->trans('Type').' : '.$langs->getLabelFromKey($this->db, 'TicketTypeShort'.$object->type_code, 'c_ticket_type', 'code', 'label', $object->type_code).'</li>';
		$message_customer .= '<li>'.$langs->trans('TicketCategory').' : '.$langs->getLabelFromKey($this->db, 'TicketCategoryShort'.$object->category_code, 'c_ticket_category', 'code', 'label', $object->category_code).'</li>';
		$message_customer .= '<li>'.$langs->trans('Severity').' : '.$langs->getLabelFromKey($this->db, 'TicketSeverityShort'.$object->severity_code, 'c_ticket_severity', 'code', 'label', $object->severity_code).'</li>';

		// Extrafields
		if (is_array($extrafields->attributes[$object->table_element]['label'])) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $value) {
				$enabled = 1;
				if ($enabled && isset($extrafields->attributes[$object->table_element]['list'][$key])) {
					$enabled = dol_eval($extrafields->attributes[$object->table_element]['list'][$key], 1);
				}
				$perms = 1;
				if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$key])) {
					$perms = dol_eval($extrafields->attributes[$object->table_element]['perms'][$key], 1);
				}

				$qualified = true;
				if (empty($enabled)) {
					$qualified = false;
				}
				if (empty($perms)) {
					$qualified = false;
				}

				if ($qualified) {
					$message_customer .= '<li>' . $langs->trans($key) . ' : ' . $value . '</li>';
				}
			}
		}

		$message_customer .= '</ul>';

		$message = $object->message;
		if (!dol_textishtml($message)) {
			$message = dol_nl2br($message);
		}
		$message_customer .= '<p>'.$langs->trans('Message').' : <br><br>'.$message.'</p><br>';

		$url_public_ticket = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', dol_buildpath('/public/ticket/', 2)).'view.php?track_id='.$object->track_id;

		$message_customer .= '<p>'.$langs->trans($see_ticket).' : <a href="'.$url_public_ticket.'">'.$url_public_ticket.'</a></p>';
		$message_customer .= '<p>'.$langs->trans('TicketEmailPleaseDoNotReplyToThisEmail').'</p>';

		$from = (!getDolGlobalString('MAIN_INFO_SOCIETE_NOM') ? '' : getDolGlobalString('MAIN_INFO_SOCIETE_NOM') . ' ').'<' . getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM').'>';

		$trackid = 'tic'.$object->id;

		$old_MAIN_MAIL_AUTOCOPY_TO = getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO');

		if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
			$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
		}

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mailfile = new CMailFile($subject, $sendto, $from, $message_customer, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', $trackid, '', 'ticket');
		if ($mailfile->error) {
			dol_syslog($mailfile->error, LOG_DEBUG);
		} else {
			$result = $mailfile->sendfile();
			if ($result) {
				// update last_msg_sent date
				$object->fetch($object->id);
				$object->date_last_msg_sent = dol_now();
				$object->update($user);
			}
		}
		if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
			$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
		}
	}
}
