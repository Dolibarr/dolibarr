<?php
/*
 * Copyright (C) 2014-2016  Jean-François Ferry	<hello@librethic.io>
 * 				 2016       Christophe Battarel <christophe@altairis.fr>
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
	 *      @param  Object    $object Object
	 *      @param  User      $user   Object user
	 *      @param  Translate $langs  Object langs
	 *      @param  conf      $conf   Object conf
	 *      @return int                     <0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		$ok = 0;

		if (empty($conf->ticket->enabled)) return 0; // Module not active, we do nothing

		switch ($action) {
			case 'TICKET_ASSIGNED':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				if ($object->fk_user_assign > 0 && $object->fk_user_assign != $user->id) {
					$userstat = new User($this->db);
					$res = $userstat->fetch($object->fk_user_assign);
					if ($res > 0) {
						// Send email to notification email

						if (empty($conf->global->TICKET_DISABLE_ALL_MAILS)) {
							// Init to avoid errors
							$filepath = array();
							$filename = array();
							$mimetype = array();

							// Send email to assigned user
							$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketAssignedToYou');
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

							if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
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
							if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
								$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
							}
						}

						$ok = 1;
					} else {
						$this->error = $userstat->error;
						$this->errors = $userstat->errors;
					}
				}
				break;

			case 'TICKET_CREATE':
				dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

				$langs->load('ticket');

				// Send email to notification email
				if (!empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO) && empty($object->context['disableticketemail'])) {
					$sendto = empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO) ? '' : $conf->global->TICKET_NOTIFICATION_EMAIL_TO;

					if ($sendto) {
						// Init to avoid errors
						$filepath = array();
						$filename = array();
						$mimetype = array();

						/* Send email to admin */
						$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketNewEmailSubjectAdmin');
						$message_admin = $langs->transnoentities('TicketNewEmailBodyAdmin', $object->track_id).'<br><br>';
						$message_admin .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
						$message_admin .= '<li>'.$langs->trans('Type').' : '.$langs->getLabelFromKey($this->db, 'TicketTypeShort'.$object->type_code, 'c_ticket_type', 'code', 'label', $object->type_code).'</li>';
						$message_admin .= '<li>'.$langs->trans('Category').' : '.$langs->getLabelFromKey($this->db, 'TicketCategoryShort'.$object->category_code, 'c_ticket_category', 'code', 'label', $object->category_code).'</li>';
						$message_admin .= '<li>'.$langs->trans('Severity').' : '.$langs->getLabelFromKey($this->db, 'TicketSeverityShort'.$object->severity_code, 'c_ticket_severity', 'code', 'label', $object->severity_code).'</li>';
						$message_admin .= '<li>'.$langs->trans('From').' : '.($object->email_from ? $object->email_from : ($object->fk_user_create > 0 ? $langs->trans('Internal') : '')).'</li>';
						// Extrafields
						$extraFields = new ExtraFields($this->db);
						$extraFields->fetch_name_optionals_label($object->table_element);
						if (is_array($object->array_options) && count($object->array_options) > 0) {
							foreach ($object->array_options as $key => $value) {
								$key = substr($key, 8); // remove "options_"
								$message_admin .= '<li>'.$langs->trans($extraFields->attributes[$object->element]['label'][$key]).' : '.$extraFields->showOutputField($key, $value).'</li>';
							}
						}
						$message_admin .= '</ul>';

						if ($object->fk_soc > 0) {
								  $object->fetch_thirdparty();
								  $message_admin .= '<p>'.$langs->trans('Company').' : '.$object->thirdparty->name.'</p>';
						}

						$message = $object->message;
						if (!dol_textishtml($message)) {
							$message = dol_nl2br($message);
						}
						$message_admin .= '<p>'.$langs->trans('Message').' : <br>'.$message.'</p>';
						$message_admin .= '<p><a href="'.dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id.'">'.$langs->trans('SeeThisTicketIntomanagementInterface').'</a></p>';

						$from = $conf->global->MAIN_INFO_SOCIETE_NOM.'<'.$conf->global->TICKET_NOTIFICATION_EMAIL_FROM.'>';
						$replyto = $from;

						$trackid = 'tic'.$object->id;

						if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
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
						if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
							$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
						}
					}
				}

				// Send email to customer

				if (empty($conf->global->TICKET_DISABLE_CUSTOMER_MAILS) && empty($object->context['disableticketemail']) && $object->notify_tiers_at_create) {
					$sendto = '';
					if (empty($user->socid) && empty($user->email)) {
							  $object->fetch_thirdparty();
							  $sendto = $object->thirdparty->email;
					} else {
						$sendto = $user->email;
					}

					if ($sendto) {
						// Init to avoid errors
						$filepath = array();
						$filename = array();
						$mimetype = array();

						$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketNewEmailSubjectCustomer');
						$message_customer = $langs->transnoentities('TicketNewEmailBodyCustomer', $object->track_id).'<br><br>';
						$message_customer .= '<ul><li>'.$langs->trans('Title').' : '.$object->subject.'</li>';
						$message_customer .= '<li>'.$langs->trans('Type').' : '.$langs->getLabelFromKey($this->db, 'TicketTypeShort'.$object->type_code, 'c_ticket_type', 'code', 'label', $object->type_code).'</li>';
						$message_customer .= '<li>'.$langs->trans('Category').' : '.$langs->getLabelFromKey($this->db, 'TicketCategoryShort'.$object->category_code, 'c_ticket_category', 'code', 'label', $object->category_code).'</li>';
						$message_customer .= '<li>'.$langs->trans('Severity').' : '.$langs->getLabelFromKey($this->db, 'TicketSeverityShort'.$object->severity_code, 'c_ticket_severity', 'code', 'label', $object->severity_code).'</li>';

						// Extrafields
						foreach ($this->attributes[$object->table_element]['label'] as $key => $value) {
							$enabled = 1;
							if ($enabled && isset($this->attributes[$object->table_element]['list'][$key])) {
								$enabled = dol_eval($this->attributes[$object->table_element]['list'][$key], 1);
							}
							$perms = 1;
							if ($perms && isset($this->attributes[$object->table_element]['perms'][$key])) {
								$perms = dol_eval($this->attributes[$object->table_element]['perms'][$key], 1);
							}

							$qualified = true;
							if (empty($enabled)) $qualified = false;
							if (empty($perms)) $qualified = false;

							if ($qualified) $message_customer .= '<li>'.$langs->trans($key).' : '.$value.'</li>';
						}

						$message_customer .= '</ul>';

						$message = $object->message;
						if (!dol_textishtml($message)) {
							$message = dol_nl2br($message);
						}
						$message_customer .= '<p>'.$langs->trans('Message').' : <br>'.$message.'</p>';
						$url_public_ticket = ($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE.'/' : dol_buildpath('/public/ticket/view.php', 2)).'?track_id='.$object->track_id;
						$message_customer .= '<p>'.$langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer').' : <a href="'.$url_public_ticket.'">'.$url_public_ticket.'</a></p>';
						$message_customer .= '<p>'.$langs->trans('TicketEmailPleaseDoNotReplyToThisEmail').'</p>';

						$from = (empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? '' : $conf->global->MAIN_INFO_SOCIETE_NOM.' ').'<'.$conf->global->TICKET_NOTIFICATION_EMAIL_FROM.'>';
						$replyto = $from;

						$trackid = 'tic'.$object->id;

						if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
							$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
							$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
						}
						include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
						$mailfile = new CMailFile($subject, $sendto, $from, $message_customer, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', $trackid, '', 'ticket');
						if ($mailfile->error) {
							dol_syslog($mailfile->error, LOG_DEBUG);
						} else {
								  $result = $mailfile->sendfile();
						}
						if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
							$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
						}
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
		   		break;
		}


		return $ok;
	}
}
