<?php
/* Copyright (C) 2013-2015 Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2024      Destailleur Laurent <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *    \file       ticket/class/actions_ticket.class.php
 *    \ingroup    ticket
 *    \brief      File Class ticket
 */

require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonhookactions.class.php';

// TODO Only the last method emailElementlist is a hook method. Other must be moved into standard ticket.class.php


/**
 *  Class Actions of the module ticket
 */
class ActionsTicket extends CommonHookActions
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var Ticket Ticket
	 */
	public $dao;

	public $mesg;

	/**
	 * @var string Error code (or message)
	 */
	public $error;

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	//! Numero de l'erreur
	public $errno = 0;

	public $template_dir;
	public $template;

	/**
	 * @var string ticket action label
	 */
	public $label;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int ID
	 */
	public $fk_statut;

	/**
	 * @var int Thirdparty ID
	 */
	public $fk_soc;


	/**
	 *    Constructor
	 *
	 *    @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Instantiation of DAO class
	 *
	 * @return void
	 */
	public function getInstanceDao()
	{
		if (!is_object($this->dao)) {
			$this->dao = new Ticket($this->db);
		}
	}

	/**
	 * Fetch object
	 *
	 * @param	int		$id				ID of ticket
	 * @param	string	$ref			Reference of ticket
	 * @param	string	$track_id		Track ID of ticket (for public area)
	 * @return int              		Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id = 0, $ref = '', $track_id = '')
	{
		$this->getInstanceDao();
		return $this->dao->fetch($id, $ref, $track_id);
	}

	/**
	 * Print statut
	 *
	 * @param		int		$mode		Display mode
	 * @return 		string				Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		$this->getInstanceDao();
		$this->dao->fk_statut = $this->fk_statut;
		return $this->dao->getLibStatut($mode);
	}

	/**
	 * Get ticket info
	 *
	 * @param  int $id    Object id
	 * @return void
	 */
	public function getInfo($id)
	{
		$this->getInstanceDao();
		$this->dao->fetch($id);

		$this->label = $this->dao->label;
		$this->description = $this->dao->description;
	}

	/**
	 * Get action title
	 *
	 * @param string 	$action    	Type of action
	 * @return string			Title of action
	 */
	public function getTitle($action = '')
	{
		global $langs;

		if ($action == 'create') {
			return $langs->trans("CreateTicket");
		} elseif ($action == 'edit') {
			return $langs->trans("EditTicket");
		} elseif ($action == 'view') {
			return $langs->trans("TicketCard");
		} elseif ($action == 'add_message') {
			return $langs->trans("TicketAddMessage");
		} else {
			return $langs->trans("TicketsManagement");
		}
	}

	/**
	 * Show ticket original message
	 *
	 * @param 	User		$user		User which display
	 * @param 	string 		$action    	Action mode
	 * @param	Ticket		$object		Object ticket
	 * @return	void
	 */
	public function viewTicketOriginalMessage($user, $action, $object)
	{
		global $langs;

		print '<!-- initial message of ticket -->'."\n";
		if ($user->hasRight('ticket', 'manage') && $action == 'edit_message_init') {
			// MESSAGE
			print '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="track_id" value="'.$object->track_id.'">';
			print '<input type="hidden" name="action" value="set_message">';
		}

		// Initial message
		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="border centpercent margintable">';
		print '<tr class="liste_titre trforfield"><td class="nowrap titlefield">';
		print $langs->trans("InitialMessage");
		print '</td><td>';
		if ($user->hasRight("ticket", "manage")) {
			if ($action != 'edit_message_init') {
				print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=edit_message_init&token='.newToken().'&track_id='.$object->track_id.'">'.img_edit($langs->trans('Modify')).'</a>';
			} else {
				print '<input type="submit" class="button button-edit smallpaddingimp" value="'.$langs->trans('Modify').'">';
				print ' <input type="submit" class="button button-cancel smallpaddingimp" name="cancel" value="'.$langs->trans("Cancel").'">';
			}
		}
		print '</td></tr>';

		print '<tr>';
		print '<td colspan="2">';
		if ($user->hasRight('ticket', 'manage') && $action == 'edit_message_init') {
			// Message
			$msg = GETPOSTISSET('message_initial') ? GETPOST('message_initial', 'restricthtml') : $object->message;
			include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
			$uselocalbrowser = true;
			$ckeditorenabledforticket = getDolGlobalString('FCKEDITOR_ENABLE_TICKET');
			if (!$ckeditorenabledforticket) {
				$msg = dol_string_nohtmltag($msg, 2);
			}
			$doleditor = new DolEditor('message_initial', $msg, '100%', 250, 'dolibarr_details', 'In', true, $uselocalbrowser, $ckeditorenabledforticket, ROWS_9, '95%');
			$doleditor->Create();
		} else {
			print '<div class="longmessagecut small">';
			print dolPrintHTML($object->message);
			print '</div>';
			/*print '<div class="clear center">';
			print $langs->trans("More").'...';
			print '</div>';*/

			//print '<div>' . $object->message . '</div>';
		}
		print '</td>';
		print '</tr>';
		print '</table>';
		print '</div>';

		if ($user->hasRight('ticket', 'manage') && $action == 'edit_message_init') {
			// MESSAGE
			print '</form>';
		}
	}

	/**
	 * View html list of message for ticket
	 *
	 * @param 	boolean 	$show_private 	Show private messages
	 * @param 	boolean 	$show_user    	Show user who make action
	 * @param	Ticket		$object			Object ticket
	 * @return 	void
	 */
	public function viewTicketMessages($show_private, $show_user, $object)
	{
		global $langs, $user;

		// Load logs in cache
		$ret = $this->dao->loadCacheMsgsTicket();
		if ($ret < 0) {
			dol_print_error($this->dao->db);
		}

		$action = GETPOST('action', 'aZ09');

		print '<div class="ticketpublicarea ticketlargemargin centpercent" style="padding-top: 0">';
		$this->viewTicketOriginalMessage($user, $action, $object);
		print '</div>';

		if (is_array($this->dao->cache_msgs_ticket) && count($this->dao->cache_msgs_ticket) > 0) {
			print '<div class="ticketpublicarea ticketlargemargin centpercent">';

			print '<div class="div-table-responsive-no-min">';
			print '<table class="border centpercent">';

			print '<tr class="liste_titre">';

			print '<td>';
			print $langs->trans('TicketMessagesList');
			print '</td>';

			if ($show_user) {
				print '<td>';
				print $langs->trans('User');
				print '</td>';
			}
			print '</tr>';

			foreach ($this->dao->cache_msgs_ticket as $id => $arraymsgs) {
				if (!$arraymsgs['private']
					|| ($arraymsgs['private'] == "1" && $show_private)
				) {
					//print '<tr>';
					print '<tr class="oddeven">';
					print '<td><strong>';
					print img_picto('', 'object_action', 'class="paddingright"').dol_print_date($arraymsgs['datep'], 'dayhour');
					print '<strong></td>';
					if ($show_user) {
						print '<td>';
						if ($arraymsgs['fk_user_author'] > 0) {
							$userstat = new User($this->db);
							$res = $userstat->fetch($arraymsgs['fk_user_author']);
							if ($res) {
								print $userstat->getNomUrl(0);
							}
						} elseif (isset($arraymsgs['fk_contact_author'])) {
							$contactstat = new Contact($this->db);
							$res = $contactstat->fetch(0, null, '', $arraymsgs['fk_contact_author']);
							if ($res) {
								print $contactstat->getNomUrl(0, 'nolink');
							} else {
								print $arraymsgs['fk_contact_author'];
							}
						} else {
							print $langs->trans('Customer');
						}
						print '</td>';
					}
					print '</td>';
					print '<tr class="oddeven">';
					print '<td colspan="2">';
					print $arraymsgs['message'];

					//attachment

					$documents = array();

					$sql = 'SELECT ecm.rowid as id, ecm.src_object_type, ecm.src_object_id';
					$sql .= ', ecm.filepath, ecm.filename, ecm.share';
					$sql .= ' FROM '.MAIN_DB_PREFIX.'ecm_files ecm';
					$sql .= " WHERE ecm.filepath = 'agenda/".$arraymsgs['id']."'";
					$sql .= ' ORDER BY ecm.position ASC';

					$resql = $this->db->query($sql);
					if ($resql) {
						if ($this->db->num_rows($resql)) {
							while ($obj = $this->db->fetch_object($resql)) {
								$documents[$obj->id] = $obj;
							}
						}
					}
					if (!empty($documents)) {
						$isshared = 0;
						$footer = '<div class="timeline-documents-container">';
						foreach ($documents as $doc) {
							if (!empty($doc->share)) {
								$isshared = 1;
								$footer .= '<span id="document_'.$doc->id.'" class="timeline-documents" ';
								$footer .= ' data-id="'.$doc->id.'" ';
								$footer .= ' data-path="'.$doc->filepath.'"';
								$footer .= ' data-filename="'.dol_escape_htmltag($doc->filename).'" ';
								$footer .= '>';

								$filePath = DOL_DATA_ROOT.'/'.$doc->filepath.'/'.$doc->filename;
								$mime = dol_mimetype($filePath);
								$thumb = $arraymsgs['id'].'/thumbs/'.substr($doc->filename, 0, strrpos($doc->filename, '.')).'_mini'.substr($doc->filename, strrpos($doc->filename, '.'));
								$doclink = DOL_URL_ROOT.'/document.php?hashp='.urlencode($doc->share);

								$mimeAttr = ' mime="'.$mime.'" ';
								$class = '';
								if (in_array($mime, array('image/png', 'image/jpeg', 'application/pdf'))) {
									$class .= ' documentpreview';
								}

								$footer .= '<a href="'.$doclink.'" class="btn-link '.$class.'" target="_blank"  '.$mimeAttr.' >';
								$footer .= img_mime($filePath).' '.$doc->filename;
								$footer .= '</a>';

								$footer .= '</span>';
							}
						}
						$footer .= '</div>';
						if ($isshared == 1) {
							print '<br>';
							print '<br>';
							print $footer;
						}
					}
					print '</td>';
					print '</tr>';
				}
			}

			print '</table>';
			print '</div>';
			print '</div>';
		} else {
			print '<div class="ticketpublicarea ticketlargemargin centpercent">';
			print '<div class="info">'.$langs->trans('NoMsgForThisTicket').'</div>';
			print '</div>';
		}
	}

	/**
	 * View list of message for ticket with timeline display
	 *
	 * @param 	boolean 	$show_private Show private messages
	 * @param 	boolean 	$show_user    Show user who make action
	 * @param	Ticket	$object		 Object ticket
	 * @return void
	 */
	public function viewTicketTimelineMessages($show_private, $show_user, Ticket $object)
	{
		global $conf, $langs, $user;

		// Load logs in cache
		$ret = $object->loadCacheMsgsTicket();
		$action = GETPOST('action');

		if (is_array($object->cache_msgs_ticket) && count($object->cache_msgs_ticket) > 0) {
			print '<section id="cd-timeline">';

			foreach ($object->cache_msgs_ticket as $id => $arraymsgs) {
				if (!$arraymsgs['private']
					|| ($arraymsgs['private'] == "1" && $show_private)
				) {
					print '<div class="cd-timeline-block">';
					print '<div class="cd-timeline-img">';
					print '<img src="img/messages.png" alt="">';
					print '</div> <!-- cd-timeline-img -->';

					print '<div class="cd-timeline-content">';
					print $arraymsgs['message'];

					print '<span class="cd-date">';
					print dol_print_date($arraymsgs['datec'], 'dayhour');

					if ($show_user) {
						if ($arraymsgs['fk_user_action'] > 0) {
							$userstat = new User($this->db);
							$res = $userstat->fetch($arraymsgs['fk_user_action']);
							if ($res) {
								print '<br>';
								print $userstat->getNomUrl(1);
							}
						} else {
							print '<br>';
							print $langs->trans('Customer');
						}
					}
					print '</span>';
					print '</div> <!-- cd-timeline-content -->';
					print '</div> <!-- cd-timeline-block -->';
				}
			}
			print '</section>';
		} else {
			print '<div class="info">'.$langs->trans('NoMsgForThisTicket').'</div>';
		}
	}

	/**
	 * Print html navbar with link to set ticket status
	 *
	 * @param	Ticket	$object		Ticket sup
	 * @return	void
	 */
	public function viewStatusActions(Ticket $object)
	{
		global $langs;

		print '<div class="div-table-responsive-no-min margintoponly navBarForStatus">';
		print '<div class="centpercent right">';
		// Exclude status which requires specific method
		$exclude_status = array(Ticket::STATUS_CLOSED, Ticket::STATUS_CANCELED);
		// Exclude actual status
		$exclude_status = array_merge($exclude_status, array((int) $object->status));
		// Exclude also the Waiting/Pending/Suspended status
		if (!getDolGlobalString('TICKET_INCLUDE_SUSPENDED_STATUS')) {
			$exclude_status[] = $object::STATUS_WAITING;
		}

		// Sort results to be similar to status object list
		//sort($exclude_status);

		foreach ($object->labelStatusShort as $status => $status_label) {
			if (!in_array($status, $exclude_status)) {
				print '<div class="inline-block center margintoponly marginbottomonly">';

				if ($status == 1) {
					$urlforbutton = $_SERVER['PHP_SELF'].'?track_id='.$object->track_id.'&action=set_read&token='.newToken(); // To set as read, we use a dedicated action
				} else {
					$urlforbutton = $_SERVER['PHP_SELF'].'?track_id='.$object->track_id.'&action=confirm_set_status&token='.newToken().'&new_status='.((int) $status);
				}

				print '<a class="butAction butStatus marginbottomonly" href="'.$urlforbutton.'">';
				print $object->LibStatut($status, 3, 1).' ';
				//print img_picto($langs->trans($object->labelStatusShort[$status]), 'statut'.$status.'.png@ticket', '', false, 0, 0, '', 'valignmiddle').' ';
				print $langs->trans($object->labelStatusShort[$status]);
				print '</a>';
				print '</div>';
			}
		}
		print '</div>';
		print '</div>';
		print '<br>';
	}

	/**
	 * Hook to add email element template
	 *
	 * @param array 		$parameters   Parameters
	 * @param Ticket		$object       Object for action
	 * @param string 		$action       Action string
	 * @param HookManager 	$hookmanager  Hookmanager object
	 * @return int
	 */
	public function emailElementlist($parameters, &$object, &$action, $hookmanager)
	{
		global $langs;

		$error = 0;

		if (in_array('admin', explode(':', $parameters['context']))) {
			$this->results = array('ticket_send' => $langs->trans('MailToSendTicketMessage'));
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}
}
