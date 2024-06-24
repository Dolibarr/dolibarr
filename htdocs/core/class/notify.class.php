<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2018 	   Philippe Grand		<philippe.grand@atoo-net.com>
 * Copyright (C) 2021 	   Thibault FOUCART		<support@ptibogxiv.net>
 * Copyright (C) 2022      Anthony Berton     	<anthony.berton@bb2a.fr>
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
 *      \file       htdocs/core/class/notify.class.php
 *      \ingroup    notification
 *      \brief      File of class to manage notifications
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

/**
 *      Class to manage notifications
 */
class Notify
{
	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	public $author;
	public $ref;
	public $date;
	public $duree;
	public $note;

	/**
	 * @var int Project ID
	 */
	public $fk_project;

	// This codes actions are defined into table llx_notify_def
	static public $arrayofnotifsupported = array(
		'BILL_VALIDATE',
		'BILL_PAYED',
		'ORDER_CREATE',
		'ORDER_VALIDATE',
		'ORDER_CLOSE',
		'PROPAL_VALIDATE',
		'PROPAL_CLOSE_SIGNED',
		'PROPAL_CLOSE_REFUSED',
		'FICHINTER_VALIDATE',
		'FICHINTER_ADD_CONTACT',
		'ORDER_SUPPLIER_VALIDATE',
		'ORDER_SUPPLIER_APPROVE',
		'ORDER_SUPPLIER_REFUSE',
		'SHIPPING_VALIDATE',
		'EXPENSE_REPORT_VALIDATE',
		'EXPENSE_REPORT_APPROVE',
		'HOLIDAY_VALIDATE',
		'HOLIDAY_APPROVE',
		'ACTION_CREATE'
	);

	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Return message that say how many notification (and to which email) will occurs on requested event.
	 *	This is to show confirmation messages before event is recorded.
	 *
	 * 	@param	string	$action		Id of action in llx_c_action_trigger
	 * 	@param	int		$socid		Id of third party
	 *  @param	Object	$object		Object the notification is about
	 *	@return	string				Message
	 */
	public function confirmMessage($action, $socid, $object)
	{
		global $conf, $langs;
		$langs->load("mails");

		// Get full list of all notifications subscribed for $action, $socid and $object
		$listofnotiftodo = $this->getNotificationsArray($action, $socid, $object, 0);

		if (!empty($conf->global->NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_USER)) {
			foreach ($listofnotiftodo as $val) {
				if ($val['type'] == 'touser') {
					unset($listofnotiftodo[$val['email']]);
					//$listofnotiftodo = array_merge($listofnotiftodo);
				}
			}
		}
		if (!empty($conf->global->NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_CONTACT)) {
			foreach ($listofnotiftodo as $val) {
				if ($val['type'] == 'tocontact') {
					unset($listofnotiftodo[$val['email']]);
					//$listofnotiftodo = array_merge($listofnotiftodo);
				}
			}
		}
		if (!empty($conf->global->NOTIFICATION_EMAIL_DISABLE_CONFIRM_MESSAGE_FIX)) {
			foreach ($listofnotiftodo as $val) {
				if ($val['type'] == 'tofixedemail') {
					unset($listofnotiftodo[$val['email']]);
					//$listofnotiftodo = array_merge($listofnotiftodo);
				}
			}
		}

		$texte = '';
		$nb = -1;
		if (is_array($listofnotiftodo)) {
			$nb = count($listofnotiftodo);
		}
		if ($nb < 0) {
			$texte = img_object($langs->trans("Notifications"), 'email', 'class="pictofixedwidth"').$langs->trans("ErrorFailedToGetListOfNotificationsToSend");
		} elseif ($nb == 0) {
			$texte = img_object($langs->trans("Notifications"), 'email', 'class="pictofixedwidth"').$langs->trans("NoNotificationsWillBeSent");
		} elseif ($nb == 1) {
			$texte = img_object($langs->trans("Notifications"), 'email', 'class="pictofixedwidth"').$langs->trans("ANotificationsWillBeSent");
		} elseif ($nb >= 2) {
			$texte = img_object($langs->trans("Notifications"), 'email', 'class="pictofixedwidth"').$langs->trans("SomeNotificationsWillBeSent", $nb);
		}

		if (is_array($listofnotiftodo)) {
			$i = 0;
			foreach ($listofnotiftodo as $val) {
				if ($i) {
					$texte .= ', ';
				} else {
					$texte .= ' (';
				}
				if ($val['isemailvalid']) {
					$texte .= $val['email'];
				} else {
					$texte .= $val['emaildesc'];
				}
				$i++;
			}
			if ($i) {
				$texte .= ')';
			}
		}

		return $texte;
	}

	/**
	 * Return number of notifications activated for action code (and third party)
	 *
	 * @param	string	$notifcode		Code of action in llx_c_action_trigger (new usage) or Id of action in llx_c_action_trigger (old usage)
	 * @param	int		$socid			Id of third party or 0 for all thirdparties or -1 for no thirdparties
	 * @param	Object	$object			Object the notification is about (need it to check threshold value of some notifications)
	 * @param	int		$userid         Id of user or 0 for all users or -1 for no users
	 * @param   array   $scope          Scope where to search
	 * @return	array|int				<0 if KO, array of notifications to send if OK
	 */
	public function getNotificationsArray($notifcode, $socid = 0, $object = null, $userid = 0, $scope = array('thirdparty', 'user', 'global'))
	{
		global $conf, $user;

		$error = 0;
		$resarray = array();

		$valueforthreshold = 0;
		if (is_object($object)) {
			$valueforthreshold = $object->total_ht;
		}

		$sqlnotifcode = '';
		if ($notifcode) {
			if (is_numeric($notifcode)) {
				$sqlnotifcode = " AND n.fk_action = ".((int) $notifcode); // Old usage
			} else {
				$sqlnotifcode = " AND a.code = '".$this->db->escape($notifcode)."'"; // New usage
			}
		}

		if (!$error) {
			if ($socid >= 0 && in_array('thirdparty', $scope)) {
				$sql = "SELECT a.code, c.email, c.rowid";
				$sql .= " FROM ".$this->db->prefix()."notify_def as n,";
				$sql .= " ".$this->db->prefix()."socpeople as c,";
				$sql .= " ".$this->db->prefix()."c_action_trigger as a,";
				$sql .= " ".$this->db->prefix()."societe as s";
				$sql .= " WHERE n.fk_contact = c.rowid";
				$sql .= " AND a.rowid = n.fk_action";
				$sql .= " AND n.fk_soc = s.rowid";
				$sql .= $sqlnotifcode;
				$sql .= " AND s.entity IN (".getEntity('societe').")";
				if ($socid > 0) {
					$sql .= " AND s.rowid = ".((int) $socid);
				}

				dol_syslog(__METHOD__." ".$notifcode.", ".$socid, LOG_DEBUG);

				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						if ($obj) {
							$newval2 = trim($obj->email);
							$isvalid = isValidEmail($newval2);
							if (empty($resarray[$newval2])) {
								$resarray[$newval2] = array('type'=> 'tocontact', 'code'=>trim($obj->code), 'emaildesc'=>'Contact id '.$obj->rowid, 'email'=>$newval2, 'contactid'=>$obj->rowid, 'isemailvalid'=>$isvalid);
							}
						}
						$i++;
					}
				} else {
					$error++;
					$this->error = $this->db->lasterror();
				}
			}
		}

		if (!$error) {
			if ($userid >= 0 && in_array('user', $scope)) {
				$sql = "SELECT a.code, c.email, c.rowid";
				$sql .= " FROM ".$this->db->prefix()."notify_def as n,";
				$sql .= " ".$this->db->prefix()."user as c,";
				$sql .= " ".$this->db->prefix()."c_action_trigger as a";
				$sql .= " WHERE n.fk_user = c.rowid";
				$sql .= " AND a.rowid = n.fk_action";
				$sql .= $sqlnotifcode;
				$sql .= " AND c.entity IN (".getEntity('user').")";
				if ($userid > 0) {
					$sql .= " AND c.rowid = ".((int) $userid);
				}

				dol_syslog(__METHOD__." ".$notifcode.", ".$socid, LOG_DEBUG);

				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);
						if ($obj) {
							$newval2 = trim($obj->email);
							$isvalid = isValidEmail($newval2);
							if (empty($resarray[$newval2])) {
								$resarray[$newval2] = array('type'=> 'touser', 'code'=>trim($obj->code), 'emaildesc'=>'User id '.$obj->rowid, 'email'=>$newval2, 'userid'=>$obj->rowid, 'isemailvalid'=>$isvalid);
							}
						}
						$i++;
					}
				} else {
					$error++;
					$this->error = $this->db->lasterror();
				}
			}
		}

		if (!$error) {
			if (in_array('global', $scope)) {
				// List of notifications enabled for fixed email
				foreach ($conf->global as $key => $val) {
					if ($notifcode) {
						if ($val == '' || !preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifcode.'_THRESHOLD_HIGHER_(.*)$/', $key, $reg)) {
							continue;
						}
					} else {
						if ($val == '' || !preg_match('/^NOTIFICATION_FIXEDEMAIL_.*_THRESHOLD_HIGHER_(.*)$/', $key, $reg)) {
							continue;
						}
					}

					$threshold = (float) $reg[1];
					if ($valueforthreshold < $threshold) {
						continue;
					}

					$tmpemail = explode(',', $val);
					foreach ($tmpemail as $key2 => $val2) {
						$newval2 = trim($val2);
						if ($newval2 == '__SUPERVISOREMAIL__') {
							if ($user->fk_user > 0) {
								$tmpuser = new User($this->db);
								$tmpuser->fetch($user->fk_user);
								if ($tmpuser->email) {
									$newval2 = trim($tmpuser->email);
								} else {
									$newval2 = '';
								}
							} else {
								$newval2 = '';
							}
						}
						if ($newval2) {
							$isvalid = isValidEmail($newval2, 0);
							if (empty($resarray[$newval2])) {
								$resarray[$newval2] = array('type'=> 'tofixedemail', 'code'=>trim($key), 'emaildesc'=>trim($val2), 'email'=>$newval2, 'isemailvalid'=>$isvalid);
							}
						}
					}
				}
			}
		}

		if ($error) {
			return -1;
		}

		//var_dump($resarray);
		return $resarray;
	}

	/**
	 *  Check if notification are active for couple action/company.
	 * 	If yes, send mail and save trace into llx_notify.
	 *
	 * 	@param	string	$notifcode			Code of action in llx_c_action_trigger (new usage) or Id of action in llx_c_action_trigger (old usage)
	 * 	@param	Object	$object				Object the notification deals on
	 *	@param 	array	$filename_list		List of files to attach (full path of filename on file system)
	 *	@param 	array	$mimetype_list		List of MIME type of attached files
	 *	@param 	array	$mimefilename_list	List of attached file name in message
	 *	@return	int							<0 if KO, or number of changes if OK
	 */
	public function send($notifcode, $object, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array())
	{
		global $user, $conf, $langs, $mysoc;
		global $hookmanager;
		global $dolibarr_main_url_root;
		global $action;

		// Complete the array Notify::$arrayofnotifsupported
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('notification'));

		$parameters = array('notifcode' => $notifcode);
		$reshook = $hookmanager->executeHooks('notifsupported', $parameters, $object, $action);
		if (empty($reshook)) {
			if (!empty($hookmanager->resArray['arrayofnotifsupported'])) {
				Notify::$arrayofnotifsupported = array_merge(Notify::$arrayofnotifsupported, $hookmanager->resArray['arrayofnotifsupported']);
			}
		}

		// If the trigger code is not managed by the Notification module
		if (!in_array($notifcode, Notify::$arrayofnotifsupported)) {
			return 0;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_syslog(get_class($this)."::send notifcode=".$notifcode.", object id=".$object->id);

		$langs->load("other");

		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;						// This is to use same domain name than current

		// Define some vars
		$application = 'Dolibarr';
		if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
			$application = $conf->global->MAIN_APPLICATION_TITLE;
		}
		$replyto = $conf->notification->email_from;
		$object_type = '';
		$link = '';
		$num = 0;
		$error = 0;

		$oldref = (empty($object->oldref) ? $object->ref : $object->oldref);
		$newref = (empty($object->newref) ? $object->ref : $object->newref);

		$sql = '';

		// Check notification per third party
		if (!empty($object->socid) && $object->socid > 0) {
			$sql .= "SELECT 'tocontactid' as type_target, c.email, c.rowid as cid, c.lastname, c.firstname, c.default_lang,";
			$sql .= " a.rowid as adid, a.label, a.code, n.rowid, n.threshold, n.context, n.type";
			$sql .= " FROM ".$this->db->prefix()."socpeople as c,";
			$sql .= " ".$this->db->prefix()."c_action_trigger as a,";
			$sql .= " ".$this->db->prefix()."notify_def as n,";
			$sql .= " ".$this->db->prefix()."societe as s";
			$sql .= " WHERE n.fk_contact = c.rowid AND a.rowid = n.fk_action";
			$sql .= " AND n.fk_soc = s.rowid";
			$sql .= " AND c.statut = 1";
			if (is_numeric($notifcode)) {
				$sql .= " AND n.fk_action = ".((int) $notifcode); // Old usage
			} else {
				$sql .= " AND a.code = '".$this->db->escape($notifcode)."'"; // New usage
			}
			$sql .= " AND s.rowid = ".((int) $object->socid);

			$sql .= "\nUNION\n";
		}

		// Check notification per user
		$sql .= "SELECT 'touserid' as type_target, c.email, c.rowid as cid, c.lastname, c.firstname, c.lang as default_lang,";
		$sql .= " a.rowid as adid, a.label, a.code, n.rowid, n.threshold, n.context, n.type";
		$sql .= " FROM ".$this->db->prefix()."user as c,";
		$sql .= " ".$this->db->prefix()."c_action_trigger as a,";
		$sql .= " ".$this->db->prefix()."notify_def as n";
		$sql .= " WHERE n.fk_user = c.rowid AND a.rowid = n.fk_action";
		$sql .= " AND c.statut = 1";
		if (is_numeric($notifcode)) {
			$sql .= " AND n.fk_action = ".((int) $notifcode); // Old usage
		} else {
			$sql .= " AND a.code = '".$this->db->escape($notifcode)."'"; // New usage
		}

		// Check notification fixed
		// TODO Move part found after, into a sql here


		// Loop on all notifications enabled
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$projtitle = '';
			if (!empty($object->fk_project)) {
				require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$proj = new Project($this->db);
				$proj->fetch($object->fk_project);
				$projtitle = '('.$proj->title.')';
			}

			if ($num > 0) {
				$i = 0;
				while ($i < $num && !$error) {	// For each notification couple defined (third party/actioncode)
					$obj = $this->db->fetch_object($result);

					$sendto = dolGetFirstLastname($obj->firstname, $obj->lastname)." <".$obj->email.">";
					$notifcodedefid = $obj->adid;
					$trackid = '';
					if ($obj->type_target == 'tocontactid') {
						$trackid = 'ctc'.$obj->cid;
					}
					if ($obj->type_target == 'touserid') {
						$trackid = 'use'.$obj->cid;
					}

					if (dol_strlen($obj->email)) {
						// Set output language
						$outputlangs = $langs;
						if ($obj->default_lang && $obj->default_lang != $langs->defaultlang) {
							$outputlangs = new Translate('', $conf);
							$outputlangs->setDefaultLang($obj->default_lang);
							$outputlangs->loadLangs(array("main", "other"));
						}

						$subject = '['.$mysoc->name.'] '.$outputlangs->transnoentitiesnoconv("DolibarrNotification").($projtitle ? ' '.$projtitle : '');

						switch ($notifcode) {
							case 'BILL_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/compta/facture/card.php?facid='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->facture->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'invoice');
								$object_type = 'facture';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextInvoiceValidated", $link);
								break;
							case 'BILL_PAYED':
								$link = '<a href="'.$urlwithroot.'/compta/facture/card.php?facid='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->facture->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'invoice');
								$object_type = 'facture';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextInvoicePayed", $link);
								break;
							case 'ORDER_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->commande->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'commande');
								$object_type = 'order';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextOrderValidated", $link);
								break;
							case 'ORDER_CLOSE':
								$link = '<a href="'.$urlwithroot.'/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->commande->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'commande');
								$object_type = 'order';
								$labeltouse = $conf->global->ORDER_CLOSE_TEMPLATE;
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextOrderClose", $link);
								break;
							case 'PROPAL_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/comm/propal/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
								$object_type = 'propal';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextProposalValidated", $link);
								break;
							case 'PROPAL_CLOSE_REFUSED':
								$link = '<a href="'.$urlwithroot.'/comm/propal/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
								$object_type = 'propal';
								$labeltouse = $conf->global->PROPAL_CLOSE_REFUSED_TEMPLATE;
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextProposalClosedRefused", $link);
								if (!empty($object->context['closedfromonlinesignature'])) {
									$mesg .= ' - From online page';
								}
								break;
							case 'PROPAL_CLOSE_SIGNED':
								$link = '<a href="'.$urlwithroot.'/comm/propal/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
								$object_type = 'propal';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextProposalClosedSigned", $link);
								if (!empty($object->context['closedfromonlinesignature'])) {
									$mesg .= ' - From online page';
								}
								break;
							case 'FICHINTER_ADD_CONTACT':
								$link = '<a href="'.$urlwithroot.'/fichinter/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->ficheinter->dir_output;
								$object_type = 'ficheinter';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextInterventionAddedContact", $link);
								break;
							case 'FICHINTER_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/fichinter/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->ficheinter->dir_output;
								$object_type = 'ficheinter';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextInterventionValidated", $link);
								break;
							case 'ORDER_SUPPLIER_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->fournisseur->commande->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object);
								$object_type = 'order_supplier';
								$labeltouse = isset($conf->global->ORDER_SUPPLIER_VALIDATE_TEMPLATE) ? $conf->global->ORDER_SUPPLIER_VALIDATE_TEMPLATE : '';
								$mesg = $outputlangs->transnoentitiesnoconv("Hello").",\n\n";
								$mesg .= $outputlangs->transnoentitiesnoconv("EMailTextOrderValidatedBy", $link, $user->getFullName($outputlangs));
								$mesg .= "\n\n".$outputlangs->transnoentitiesnoconv("Sincerely").".\n\n";
								break;
							case 'ORDER_SUPPLIER_APPROVE':
								$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->fournisseur->commande->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object);
								$object_type = 'order_supplier';
								$labeltouse = isset($conf->global->ORDER_SUPPLIER_APPROVE_TEMPLATE) ? $conf->global->ORDER_SUPPLIER_APPROVE_TEMPLATE : '';
								$mesg = $outputlangs->transnoentitiesnoconv("Hello").",\n\n";
								$mesg .= $outputlangs->transnoentitiesnoconv("EMailTextOrderApprovedBy", $link, $user->getFullName($outputlangs));
								$mesg .= "\n\n".$outputlangs->transnoentitiesnoconv("Sincerely").".\n\n";
								break;
							case 'ORDER_SUPPLIER_REFUSE':
								$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->fournisseur->commande->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object);
								$object_type = 'order_supplier';
								$labeltouse = isset($conf->global->ORDER_SUPPLIER_REFUSE_TEMPLATE) ? $conf->global->ORDER_SUPPLIER_REFUSE_TEMPLATE : '';
								$mesg = $outputlangs->transnoentitiesnoconv("Hello").",\n\n";
								$mesg .= $outputlangs->transnoentitiesnoconv("EMailTextOrderRefusedBy", $link, $user->getFullName($outputlangs));
								$mesg .= "\n\n".$outputlangs->transnoentitiesnoconv("Sincerely").".\n\n";
								break;
							case 'SHIPPING_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/expedition/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->expedition->dir_output."/sending/".get_exdir(0, 0, 0, 1, $object, 'shipment');
								$object_type = 'shipping';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextExpeditionValidated", $link);
								break;
							case 'EXPENSE_REPORT_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/expensereport/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->expensereport->dir_output;
								$object_type = 'expensereport';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextExpenseReportValidated", $link);
								break;
							case 'EXPENSE_REPORT_APPROVE':
								$link = '<a href="'.$urlwithroot.'/expensereport/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->expensereport->dir_output;
								$object_type = 'expensereport';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextExpenseReportApproved", $link);
								break;
							case 'HOLIDAY_VALIDATE':
								$link = '<a href="'.$urlwithroot.'/holiday/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->holiday->dir_output;
								$object_type = 'holiday';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextHolidayValidated", $link);
								break;
							case 'HOLIDAY_APPROVE':
								$link = '<a href="'.$urlwithroot.'/holiday/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->holiday->dir_output;
								$object_type = 'holiday';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextHolidayApproved", $link);
								break;
							case 'ACTION_CREATE':
								$link = '<a href="'.$urlwithroot.'/comm/action/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
								$dir_output = $conf->agenda->dir_output;
								$object_type = 'action';
								$mesg = $outputlangs->transnoentitiesnoconv("EMailTextActionAdded", $link);
								break;
							default:
								$object_type = $object->element;
								$dir_output = $conf->$object_type->multidir_output[$object->entity ? $object->entity : $conf->entity]."/".get_exdir(0, 0, 0, 1, $object, $object_type);
								$template = $notifcode.'_TEMPLATE';
								$mesg = $outputlangs->transnoentitiesnoconv('Notify_'.$notifcode).' '.$newref.' '.$dir_output;
							break;
						}

						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($this->db);
						$arraydefaultmessage = null;

						$template = $notifcode.'_TEMPLATE';
						$labeltouse = getDolGlobalString($template);
						if (!empty($labeltouse)) {
							$arraydefaultmessage = $formmail->getEMailTemplate($this->db, $object_type.'_send', $user, $outputlangs, 0, 1, $labeltouse);
						}
						if (!empty($labeltouse) && is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0) {
							$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
							complete_substitutions_array($substitutionarray, $outputlangs, $object);
							$subject = make_substitutions($arraydefaultmessage->topic, $substitutionarray, $outputlangs);
							$message = make_substitutions($arraydefaultmessage->content, $substitutionarray, $outputlangs);
						} else {
							$message = $outputlangs->transnoentities("YouReceiveMailBecauseOfNotification", $application, $mysoc->name)."\n";
							$message .= $outputlangs->transnoentities("YouReceiveMailBecauseOfNotification2", $application, $mysoc->name)."\n";
							$message .= "\n";
							$message .= $mesg;
						}

						$ref = dol_sanitizeFileName($newref);
						$pdf_path = $dir_output."/".$ref.".pdf";
						if (!dol_is_file($pdf_path)||(is_object($arraydefaultmessage) && $arraydefaultmessage->id > 0 && !$arraydefaultmessage->joinfiles)) {
							// We can't add PDF as it is not generated yet.
							$filepdf = '';
						} else {
							$filepdf = $pdf_path;
							$filename_list[] = $filepdf;
							$mimetype_list[] = mime_content_type($filepdf);
							$mimefilename_list[] = $ref.".pdf";
						}

						$labeltouse = !empty($labeltouse) ? $labeltouse : '';

						// Replace keyword __SUPERVISOREMAIL__
						if (preg_match('/__SUPERVISOREMAIL__/', $sendto)) {
							$newval = '';
							if ($user->fk_user > 0) {
								$supervisoruser = new User($this->db);
								$supervisoruser->fetch($user->fk_user);
								if ($supervisoruser->email) {
									$newval = trim(dolGetFirstLastname($supervisoruser->firstname, $supervisoruser->lastname).' <'.$supervisoruser->email.'>');
								}
							}
							dol_syslog("Replace the __SUPERVISOREMAIL__ key into recipient email string with ".$newval);
							$sendto = preg_replace('/__SUPERVISOREMAIL__/', $newval, $sendto);
							$sendto = preg_replace('/,\s*,/', ',', $sendto); // in some case you can have $sendto like "email, __SUPERVISOREMAIL__ , otheremail" then you have "email,  , othermail" and it's not valid
							$sendto = preg_replace('/^[\s,]+/', '', $sendto); // Clean start of string
							$sendto = preg_replace('/[\s,]+$/', '', $sendto); // Clean end of string
						}

						$parameters = array('notifcode'=>$notifcode, 'sendto'=>$sendto, 'replyto'=>$replyto, 'file'=>$filename_list, 'mimefile'=>$mimetype_list, 'filename'=>$mimefilename_list, 'outputlangs'=>$outputlangs, 'labeltouse'=>$labeltouse);
						if (!isset($action)) {
							$action = '';
						}

						$reshook = $hookmanager->executeHooks('formatNotificationMessage', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
						if (empty($reshook)) {
							if (!empty($hookmanager->resArray['files'])) {
								$filename_list = $hookmanager->resArray['files']['file'];
								$mimetype_list = $hookmanager->resArray['files']['mimefile'];
								$mimefilename_list = $hookmanager->resArray['files']['filename'];
							}
							if (!empty($hookmanager->resArray['subject'])) {
								$subject .= $hookmanager->resArray['subject'];
							}
							if (!empty($hookmanager->resArray['message'])) {
								$message .= $hookmanager->resArray['message'];
							}
						}

						$mailfile = new CMailFile(
							$subject,
							$sendto,
							$replyto,
							$message,
							$filename_list,
							$mimetype_list,
							$mimefilename_list,
							'',
							'',
							0,
							-1,
							'',
							'',
							$trackid,
							'',
							'notification'
						);

						if ($mailfile->sendfile()) {
							if ($obj->type_target == 'touserid') {
								$sql = "INSERT INTO ".$this->db->prefix()."notify (daten, fk_action, fk_soc, fk_user, type, objet_type, type_target, objet_id, email)";
								$sql .= " VALUES ('".$this->db->idate(dol_now())."', ".((int) $notifcodedefid).", ".($object->socid > 0 ? ((int) $object->socid) : 'null').", ".((int) $obj->cid).", '".$this->db->escape($obj->type)."', '".$this->db->escape($object_type)."', '".$this->db->escape($obj->type_target)."', ".((int) $object->id).", '".$this->db->escape($obj->email)."')";
							} else {
								$sql = "INSERT INTO ".$this->db->prefix()."notify (daten, fk_action, fk_soc, fk_contact, type, objet_type, type_target, objet_id, email)";
								$sql .= " VALUES ('".$this->db->idate(dol_now())."', ".((int) $notifcodedefid).", ".($object->socid > 0 ? ((int) $object->socid) : 'null').", ".((int) $obj->cid).", '".$this->db->escape($obj->type)."', '".$this->db->escape($object_type)."', '".$this->db->escape($obj->type_target)."', ".((int) $object->id).", '".$this->db->escape($obj->email)."')";
							}
							if (!$this->db->query($sql)) {
								dol_print_error($this->db);
							}
						} else {
							$error++;
							$this->errors[] = $mailfile->error;
						}
					} else {
						dol_syslog("No notification sent for ".$sendto." because email is empty");
					}
					$i++;
				}
			} else {
				dol_syslog("No notification to thirdparty sent, nothing into notification setup for the thirdparty socid = ".(empty($object->socid) ? '' : $object->socid));
			}
		} else {
			$error++;
			$this->errors[] = $this->db->lasterror();
			dol_syslog("Failed to get list of notification to send ".$this->db->lasterror(), LOG_ERR);
			return -1;
		}

		// Check notification using fixed email
		// TODO Move vars NOTIFICATION_FIXEDEMAIL into table llx_notify_def and inclulde the case into previous loop of sql result
		if (!$error) {
			foreach ($conf->global as $key => $val) {
				$reg = array();
				if ($val == '' || !preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifcode.'_THRESHOLD_HIGHER_(.*)$/', $key, $reg)) {
					continue;
				}

				$threshold = (float) $reg[1];
				if (!empty($object->total_ht) && $object->total_ht <= $threshold) {
					dol_syslog("A notification is requested for notifcode = ".$notifcode." but amount = ".$object->total_ht." so lower than threshold = ".$threshold.". We discard this notification");
					continue;
				}

				$param = 'NOTIFICATION_FIXEDEMAIL_'.$notifcode.'_THRESHOLD_HIGHER_'.$reg[1];

				$sendto = $conf->global->$param;
				$notifcodedefid = dol_getIdFromCode($this->db, $notifcode, 'c_action_trigger', 'code', 'rowid');
				if ($notifcodedefid <= 0) {
					dol_print_error($this->db, 'Failed to get id from code');
				}
				$trackid = '';

				$object_type = '';
				$link = '';
				$num++;

				$subject = '['.$mysoc->name.'] '.$langs->transnoentitiesnoconv("DolibarrNotification").($projtitle ? ' '.$projtitle : '');

				switch ($notifcode) {
					case 'BILL_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/compta/facture/card.php?facid='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->facture->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'invoice');
						$object_type = 'facture';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInvoiceValidated", $link);
						break;
					case 'BILL_PAYED':
						$link = '<a href="'.$urlwithroot.'/compta/facture/card.php?facid='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->facture->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'invoice');
						$object_type = 'facture';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInvoicePayed", $link);
						break;
					case 'ORDER_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->commande->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'commande');
						$object_type = 'order';
						$mesg = $langs->transnoentitiesnoconv("EMailTextOrderValidated", $link);
						break;
					case 'ORDER_CLOSE':
						$link = '<a href="'.$urlwithroot.'/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->commande->dir_output."/".get_exdir(0, 0, 0, 1, $object, 'commande');
						$object_type = 'order';
						$mesg = $langs->transnoentitiesnoconv("EMailTextOrderClose", $link);
						break;
					case 'PROPAL_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/comm/propal/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
						$object_type = 'propal';
						$mesg = $langs->transnoentitiesnoconv("EMailTextProposalValidated", $link);
						break;
					case 'PROPAL_CLOSE_SIGNED':
						$link = '<a href="'.$urlwithroot.'/comm/propal/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->propal->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object, 'propal');
						$object_type = 'propal';
						$mesg = $langs->transnoentitiesnoconv("EMailTextProposalClosedSigned", $link);
						break;
					case 'FICHINTER_ADD_CONTACT':
						$link = '<a href="'.$urlwithroot.'/fichinter/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->ficheinter->dir_output;
						$object_type = 'ficheinter';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInterventionAddedContact", $link);
						break;
					case 'FICHINTER_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/fichinter/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->facture->dir_output;
						$object_type = 'ficheinter';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInterventionValidated", $link);
						break;
					case 'ORDER_SUPPLIER_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->fournisseur->commande->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object);
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg .= $langs->transnoentitiesnoconv("EMailTextOrderValidatedBy", $link, $user->getFullName($langs));
						$mesg .= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'ORDER_SUPPLIER_APPROVE':
						$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->fournisseur->commande->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object);
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg .= $langs->transnoentitiesnoconv("EMailTextOrderApprovedBy", $link, $user->getFullName($langs));
						$mesg .= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'ORDER_SUPPLIER_APPROVE2':
						$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->fournisseur->commande->multidir_output[$object->entity]."/".get_exdir(0, 0, 0, 1, $object);
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg .= $langs->transnoentitiesnoconv("EMailTextOrderApprovedBy", $link, $user->getFullName($langs));
						$mesg .= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'ORDER_SUPPLIER_REFUSE':
						$link = '<a href="'.$urlwithroot.'/fourn/commande/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->fournisseur->dir_output.'/commande/';
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg .= $langs->transnoentitiesnoconv("EMailTextOrderRefusedBy", $link, $user->getFullName($langs));
						$mesg .= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'SHIPPING_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/expedition/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->expedition->dir_output."/sending/".get_exdir(0, 0, 0, 1, $object, 'shipment');
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("EMailTextExpeditionValidated", $link);
						break;
					case 'EXPENSE_REPORT_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/expensereport/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->expensereport->dir_output;
						$object_type = 'expensereport';
						$mesg = $langs->transnoentitiesnoconv("EMailTextExpenseReportValidated", $link);
						break;
					case 'EXPENSE_REPORT_APPROVE':
						$link = '<a href="'.$urlwithroot.'/expensereport/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->expensereport->dir_output;
						$object_type = 'expensereport';
						$mesg = $langs->transnoentitiesnoconv("EMailTextExpenseReportApproved", $link);
						break;
					case 'HOLIDAY_VALIDATE':
						$link = '<a href="'.$urlwithroot.'/holiday/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->holiday->dir_output;
						$object_type = 'holiday';
						$mesg = $langs->transnoentitiesnoconv("EMailTextHolidayValidated", $link);
						break;
					case 'HOLIDAY_APPROVE':
						$link = '<a href="'.$urlwithroot.'/holiday/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->holiday->dir_output;
						$object_type = 'holiday';
						$mesg = $langs->transnoentitiesnoconv("EMailTextHolidayApproved", $link);
						break;
					case 'ACTION_CREATE':
						$link = '<a href="'.$urlwithroot.'/comm/action/card.php?id='.$object->id.'&entity='.$object->entity.'">'.$newref.'</a>';
						$dir_output = $conf->agenda->dir_output;
						$object_type = 'action';
						$mesg = $langs->transnoentitiesnoconv("EMailTextActionAdded", $link);
						break;
					default:
						$object_type = $object->element;
						$dir_output = $conf->$object_type->multidir_output[$object->entity ? $object->entity : $conf->entity]."/".get_exdir(0, 0, 0, 1, $object, $object_type);
						$mesg = $langs->transnoentitiesnoconv('Notify_'.$notifcode).' '.$newref;
						break;
				}
				$ref = dol_sanitizeFileName($newref);
				$pdf_path = $dir_output."/".$ref."/".$ref.".pdf";
				if (!dol_is_file($pdf_path)) {
					// We can't add PDF as it is not generated yet.
					$filepdf = '';
				} else {
					$filepdf = $pdf_path;
					$filename_list[] = $pdf_path;
					$mimetype_list[] = mime_content_type($filepdf);
					$mimefilename_list[] = $ref.".pdf";
				}

				// if an e-mail template is configured for this notification code (for instance
				// 'SHIPPING_VALIDATE_TEMPLATE'), we fetch this template by its label. Otherwise, a default message
				// content will be sent.
				$mailTemplateLabel = isset($conf->global->{$notifcode.'_TEMPLATE'}) ? $conf->global->{$notifcode.'_TEMPLATE'} : '';
				$emailTemplate = null;
				if (!empty($mailTemplateLabel)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($this->db);
					$emailTemplate = $formmail->getEMailTemplate($this->db, $object_type.'_send', $user, $langs, 0, 1, $labeltouse);
				}
				if (!empty($mailTemplateLabel) && is_object($emailTemplate) && $emailTemplate->id > 0) {
					$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
					complete_substitutions_array($substitutionarray, $langs, $object);
					$subject = make_substitutions($emailTemplate->topic, $substitutionarray, $langs);
					$message = make_substitutions($emailTemplate->content, $substitutionarray, $langs);
				} else {
					$message = '';
					$message .= $langs->transnoentities("YouReceiveMailBecauseOfNotification2", $application, $mysoc->name)."\n";
					$message .= "\n";
					$message .= $mesg;

					$message = nl2br($message);
				}

				// Replace keyword __SUPERVISOREMAIL__
				if (preg_match('/__SUPERVISOREMAIL__/', $sendto)) {
					$newval = '';
					if ($user->fk_user > 0) {
						$supervisoruser = new User($this->db);
						$supervisoruser->fetch($user->fk_user);
						if ($supervisoruser->email) {
							$newval = trim(dolGetFirstLastname($supervisoruser->firstname, $supervisoruser->lastname).' <'.$supervisoruser->email.'>');
						}
					}
					dol_syslog("Replace the __SUPERVISOREMAIL__ key into recipient email string with ".$newval);
					$sendto = preg_replace('/__SUPERVISOREMAIL__/', $newval, $sendto);
					$sendto = preg_replace('/,\s*,/', ',', $sendto); // in some case you can have $sendto like "email, __SUPERVISOREMAIL__ , otheremail" then you have "email,  , othermail" and it's not valid
					$sendto = preg_replace('/^[\s,]+/', '', $sendto); // Clean start of string
					$sendto = preg_replace('/[\s,]+$/', '', $sendto); // Clean end of string
				}

				if ($sendto) {
					$parameters = array('notifcode'=>$notifcode, 'sendto'=>$sendto, 'replyto'=>$replyto, 'file'=>$filename_list, 'mimefile'=>$mimetype_list, 'filename'=>$mimefilename_list);
					$reshook = $hookmanager->executeHooks('formatNotificationMessage', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
					if (empty($reshook)) {
						if (!empty($hookmanager->resArray['files'])) {
							$filename_list = $hookmanager->resArray['files']['file'];
							$mimetype_list = $hookmanager->resArray['files']['mimefile'];
							$mimefilename_list = $hookmanager->resArray['files']['filename'];
						}
						if (!empty($hookmanager->resArray['subject'])) {
							$subject .= $hookmanager->resArray['subject'];
						}
						if (!empty($hookmanager->resArray['message'])) {
							$message .= $hookmanager->resArray['message'];
						}
					}
					$mailfile = new CMailFile(
						$subject,
						$sendto,
						$replyto,
						$message,
						$filename_list,
						$mimetype_list,
						$mimefilename_list,
						'',
						'',
						0,
						1,
						'',
						$trackid,
						'',
						'',
						'notification'
					);

					if ($mailfile->sendfile()) {
						$sql = "INSERT INTO ".$this->db->prefix()."notify (daten, fk_action, fk_soc, fk_contact, type, type_target, objet_type, objet_id, email)";
						$sql .= " VALUES ('".$this->db->idate(dol_now())."', ".((int) $notifcodedefid).", ".($object->socid > 0 ? ((int) $object->socid) : 'null').", null, 'email', 'tofixedemail', '".$this->db->escape($object_type)."', ".((int) $object->id).", '".$this->db->escape($conf->global->$param)."')";
						if (!$this->db->query($sql)) {
							dol_print_error($this->db);
						}
					} else {
						$error++;
						$this->errors[] = $mailfile->error;
					}
				}
			}
		}

		if (!$error) {
			return $num;
		} else {
			return -1 * $error;
		}
	}
}
