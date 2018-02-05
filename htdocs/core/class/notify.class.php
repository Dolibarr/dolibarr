<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/class/notify.class.php
 *      \ingroup    notification
 *      \brief      File of class to manage notifications
 */
require_once DOL_DOCUMENT_ROOT .'/core/class/CMailFile.class.php';


/**
 *      Class to manage notifications
 */
class Notify
{
	var $id;
	var $db;
	var $error;
	var $errors=array();

	var $author;
	var $ref;
	var $date;
	var $duree;
	var $note;
	var $fk_project;

	// Les codes actions sont definis dans la table llx_notify_def

	// codes actions supported are
	public $arrayofnotifsupported = array(
		'BILL_VALIDATE',
		'BILL_PAYED',
		'ORDER_VALIDATE',
		'PROPAL_VALIDATE',
		'PROPAL_CLOSE_SIGNED',
		'FICHINTER_VALIDATE',
		'FICHINTER_ADD_CONTACT',
		'ORDER_SUPPLIER_VALIDATE',
		'ORDER_SUPPLIER_APPROVE',
		'ORDER_SUPPLIER_REFUSE',
		'SHIPPING_VALIDATE'
	);


	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
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
	function confirmMessage($action,$socid,$object)
	{
		global $langs;
		$langs->load("mails");

		$listofnotiftodo=$this->getNotificationsArray($action,$socid,$object,0);

		$nb=-1;
		if (is_array($listofnotiftodo)) $nb=count($listofnotiftodo);
		if ($nb < 0)  $texte=img_object($langs->trans("Notifications"),'email').' '.$langs->trans("ErrorFailedToGetListOfNotificationsToSend");
		if ($nb == 0) $texte=img_object($langs->trans("Notifications"),'email').' '.$langs->trans("NoNotificationsWillBeSent");
   		if ($nb == 1) $texte=img_object($langs->trans("Notifications"),'email').' '.$langs->trans("ANotificationsWillBeSent");
   		if ($nb >= 2) $texte=img_object($langs->trans("Notifications"),'email').' '.$langs->trans("SomeNotificationsWillBeSent",$nb);

   		if (is_array($listofnotiftodo))
   		{
			$i=0;
			foreach ($listofnotiftodo as $key => $val)
			{
				if ($i) $texte.=', ';
				else $texte.=' (';
				if ($val['isemailvalid']) $texte.=$val['email'];
				else $texte.=$val['emaildesc'];
				$i++;
			}
			if ($i) $texte.=')';
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
	function getNotificationsArray($notifcode, $socid=0, $object=null, $userid=0, $scope=array('thirdparty', 'user', 'global'))
	{
		global $conf, $user;

		$error=0;
		$resarray=array();

		$valueforthreshold = 0;
		if (is_object($object)) $valueforthreshold = $object->total_ht;

		if (! $error)
		{
			if ($socid >= 0 && in_array('thirdparty', $scope))
			{
				$sql = "SELECT a.code, c.email, c.rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."notify_def as n,";
				$sql.= " ".MAIN_DB_PREFIX."socpeople as c,";
				$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as a,";
				$sql.= " ".MAIN_DB_PREFIX."societe as s";
				$sql.= " WHERE n.fk_contact = c.rowid";
				$sql.= " AND a.rowid = n.fk_action";
				$sql.= " AND n.fk_soc = s.rowid";
				if ($notifcode)
				{
					if (is_numeric($notifcode)) $sql.= " AND n.fk_action = ".$notifcode;	// Old usage
					else $sql.= " AND a.code = '".$notifcode."'";			// New usage
				}
				$sql.= " AND s.entity IN (".getEntity('societe').")";
				if ($socid > 0) $sql.= " AND s.rowid = ".$socid;

				dol_syslog(__METHOD__." ".$notifcode.", ".$socid."", LOG_DEBUG);

				$resql = $this->db->query($sql);
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					$i=0;
					while ($i < $num)
					{
						$obj = $this->db->fetch_object($resql);
						if ($obj)
						{
							$newval2=trim($obj->email);
							$isvalid=isValidEmail($newval2);
							if (empty($resarray[$newval2])) $resarray[$newval2] = array('type'=> 'tocontact', 'code'=>trim($obj->code), 'emaildesc'=>'Contact id '.$obj->rowid, 'email'=>$newval2, 'contactid'=>$obj->rowid, 'isemailvalid'=>$isvalid);
						}
						$i++;
					}
				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror();
				}
			}
		}

		if (! $error)
		{
			if ($userid >= 0 && in_array('user', $scope))
			{
				$sql = "SELECT a.code, c.email, c.rowid";
				$sql.= " FROM ".MAIN_DB_PREFIX."notify_def as n,";
				$sql.= " ".MAIN_DB_PREFIX."user as c,";
				$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as a";
				$sql.= " WHERE n.fk_user = c.rowid";
				$sql.= " AND a.rowid = n.fk_action";
				if ($notifcode)
				{
					if (is_numeric($notifcode)) $sql.= " AND n.fk_action = ".$notifcode;	// Old usage
					else $sql.= " AND a.code = '".$notifcode."'";			// New usage
				}
				$sql.= " AND c.entity IN (".getEntity('user').")";
				if ($userid > 0) $sql.= " AND c.rowid = ".$userid;

				dol_syslog(__METHOD__." ".$notifcode.", ".$socid."", LOG_DEBUG);

				$resql = $this->db->query($sql);
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					$i=0;
					while ($i < $num)
					{
						$obj = $this->db->fetch_object($resql);
						if ($obj)
						{
							$newval2=trim($obj->email);
							$isvalid=isValidEmail($newval2);
							if (empty($resarray[$newval2])) $resarray[$newval2] = array('type'=> 'touser', 'code'=>trim($obj->code), 'emaildesc'=>'User id '.$obj->rowid, 'email'=>$newval2, 'userid'=>$obj->rowid, 'isemailvalid'=>$isvalid);
						}
						$i++;
					}
				}
				else
				{
					$error++;
					$this->error=$this->db->lasterror();
				}
			}
		}

		if (! $error)
		{
			if (in_array('global', $scope))
			{
				// List of notifications enabled for fixed email
				foreach($conf->global as $key => $val)
				{
					if ($notifcode)
					{
						if ($val == '' || ! preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifcode.'_THRESHOLD_HIGHER_(.*)$/', $key, $reg)) continue;
					}
					else
					{
						if ($val == '' || ! preg_match('/^NOTIFICATION_FIXEDEMAIL_.*_THRESHOLD_HIGHER_(.*)$/', $key, $reg)) continue;
					}

					$threshold = (float) $reg[1];
					if ($valueforthreshold < $threshold) continue;

					$tmpemail=explode(',',$val);
					foreach($tmpemail as $key2 => $val2)
					{
						$newval2=trim($val2);
						if ($newval2 == '__SUPERVISOREMAIL__')
						{
							if ($user->fk_user > 0)
							{
								$tmpuser=new User($this->db);
								$tmpuser->fetch($user->fk_user);
								if ($tmpuser->email) $newval2=trim($tmpuser->email);
								else $newval2='';
							}
							else $newval2='';
						}
						if ($newval2)
						{
							$isvalid=isValidEmail($newval2, 0);
							if (empty($resarray[$newval2])) $resarray[$newval2]=array('type'=> 'tofixedemail', 'code'=>trim($key), 'emaildesc'=>trim($val2), 'email'=>$newval2, 'isemailvalid'=>$isvalid);
						}
					}
				}
			}
		}

		if ($error) return -1;

		//var_dump($resarray);
		return $resarray;
	}

	/**
	 *  Check if notification are active for couple action/company.
	 * 	If yes, send mail and save trace into llx_notify.
	 *
	 * 	@param	string	$notifcode		Code of action in llx_c_action_trigger (new usage) or Id of action in llx_c_action_trigger (old usage)
	 * 	@param	Object	$object			Object the notification deals on
	 *	@return	int						<0 if KO, or number of changes if OK
	 */
	function send($notifcode, $object)
	{
		global $user,$conf,$langs,$mysoc;
		global $hookmanager;
		global $dolibarr_main_url_root;

		if (! in_array($notifcode, $this->arrayofnotifsupported)) return 0;

		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('notification'));

		dol_syslog(get_class($this)."::send notifcode=".$notifcode.", object=".$object->id);

		$langs->load("other");

		// Define $urlwithroot
		$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
		$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;			// This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;						// This is to use same domain name than current

		// Define some vars
		$application = 'Dolibarr';
		if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $application = $conf->global->MAIN_APPLICATION_TITLE;
		$replyto = $conf->notification->email_from;
		$filename = basename($file);
		$mimefile = dol_mimetype($file);
		$object_type = '';
		$link = '';
		$num = 0;

		$oldref=(empty($object->oldref)?$object->ref:$object->oldref);
		$newref=(empty($object->newref)?$object->ref:$object->newref);

		// Check notification per third party
		$sql = "SELECT 'tocontactid' as type_target, c.email, c.rowid as cid, c.lastname, c.firstname, c.default_lang,";
		$sql.= " a.rowid as adid, a.label, a.code, n.rowid, n.type";
		$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as c,";
		$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as a,";
		$sql.= " ".MAIN_DB_PREFIX."notify_def as n,";
		$sql.= " ".MAIN_DB_PREFIX."societe as s";
		$sql.= " WHERE n.fk_contact = c.rowid AND a.rowid = n.fk_action";
		$sql.= " AND n.fk_soc = s.rowid";
		if (is_numeric($notifcode)) $sql.= " AND n.fk_action = ".$notifcode;	// Old usage
		else $sql.= " AND a.code = '".$notifcode."'";	// New usage
		$sql .= " AND s.rowid = ".$object->socid;

		// Check notification per user
		$sql.= "\nUNION\n";

		$sql.= "SELECT 'touserid' as type_target, c.email, c.rowid as cid, c.lastname, c.firstname, c.lang as default_lang,";
		$sql.= " a.rowid as adid, a.label, a.code, n.rowid, n.type";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as c,";
		$sql.= " ".MAIN_DB_PREFIX."c_action_trigger as a,";
		$sql.= " ".MAIN_DB_PREFIX."notify_def as n";
		$sql.= " WHERE n.fk_user = c.rowid AND a.rowid = n.fk_action";
		if (is_numeric($notifcode)) $sql.= " AND n.fk_action = ".$notifcode;	// Old usage
		else $sql.= " AND a.code = '".$notifcode."'";	// New usage

		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);

			if ($num > 0)
			{
				$i = 0;
				while ($i < $num && ! $error)	// For each notification couple defined (third party/actioncode)
				{
					$obj = $this->db->fetch_object($result);

					$sendto = dolGetFirstLastname($obj->firstname,$obj->lastname) . " <".$obj->email.">";
					$notifcodedefid = $obj->adid;

					if (dol_strlen($obj->email))
					{
						// Set output language
						$outputlangs = $langs;
						if ($obj->default_lang && $obj->default_lang != $langs->defaultlang)
						{
							$outputlangs = new Translate('', $conf);
							$outputlangs->setDefaultLang($obj->default_lang);
						}

						$subject = '['.$mysoc->name.'] '.$outputlangs->transnoentitiesnoconv("DolibarrNotification");

						switch ($notifcode) {
							case 'BILL_VALIDATE':
								$link='/compta/facture/card.php?facid='.$object->id;
								$dir_output = $conf->facture->dir_output;
								$object_type = 'facture';
								$mesg = $langs->transnoentitiesnoconv("EMailTextInvoiceValidated",$newref);
								break;
							case 'BILL_PAYED':
								$link='/compta/facture/card.php?facid='.$object->id;
								$dir_output = $conf->facture->dir_output;
								$object_type = 'facture';
								$mesg = $langs->transnoentitiesnoconv("EMailTextInvoicePayed",$newref);
								break;
							case 'ORDER_VALIDATE':
								$link='/commande/card.php?id='.$object->id;
								$dir_output = $conf->commande->dir_output;
								$object_type = 'order';
								$mesg = $langs->transnoentitiesnoconv("EMailTextOrderValidated",$newref);
								break;
							case 'PROPAL_VALIDATE':
								$link='/comm/propal/card.php?id='.$object->id;
								$dir_output = $conf->propal->dir_output;
								$object_type = 'propal';
								$mesg = $langs->transnoentitiesnoconv("EMailTextProposalValidated",$newref);
								break;
							case 'PROPAL_CLOSE_SIGNED':
								$link='/comm/propal/card.php?id='.$object->id;
								$dir_output = $conf->propal->dir_output;
								$object_type = 'propal';
								$mesg = $langs->transnoentitiesnoconv("EMailTextProposalClosedSigned",$newref);
								break;
							case 'FICHINTER_ADD_CONTACT':
								$link='/fichinter/card.php?id='.$object->id;
								$dir_output = $conf->ficheinter->dir_output;
								$object_type = 'ficheinter';
								$mesg = $langs->transnoentitiesnoconv("EMailTextInterventionAddedContact",$object->ref);
								break;
							case 'FICHINTER_VALIDATE':
								$link='/fichinter/card.php?id='.$object->id;
								$dir_output = $conf->ficheinter->dir_output;
								$object_type = 'ficheinter';
								$mesg = $langs->transnoentitiesnoconv("EMailTextInterventionValidated",$object->ref);
								break;
							case 'ORDER_SUPPLIER_VALIDATE':
								$link='/fourn/commande/card.php?id='.$object->id;
								$dir_output = $conf->fournisseur->commande->dir_output;
								$object_type = 'order_supplier';
								$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
								$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderValidatedBy",$object->ref,$user->getFullName($langs));
								$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
								break;
							case 'ORDER_SUPPLIER_APPROVE':
								$link='/fourn/commande/card.php?id='.$object->id;
								$dir_output = $conf->fournisseur->commande->dir_output;
								$object_type = 'order_supplier';
								$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
								$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderApprovedBy",$newref,$user->getFullName($langs));
								$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
								break;
							case 'ORDER_SUPPLIER_REFUSE':
								$link='/fourn/commande/card.php?id='.$object->id;
								$dir_output = $conf->fournisseur->commande->dir_output;
								$object_type = 'order_supplier';
								$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
								$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderRefusedBy",$newref,$user->getFullName($langs));
								$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
								break;
							case 'SHIPPING_VALIDATE':
								$dir_output = $conf->expedition->dir_output.'/sending/';
								$object_type = 'order_supplier';
								$mesg = $langs->transnoentitiesnoconv("EMailTextExpeditionValidated",$newref);
								break;
						}
						$ref = dol_sanitizeFileName($newref);
						$pdf_path = $dir_output."/".$ref."/".$ref.".pdf";
						if (! dol_is_file($pdf_path))
						{
							// We can't add PDF as it is not generated yet.
							$filepdf = '';
						}
						else
						{
							$filepdf = $pdf_path;
						}

						$message = $outputlangs->transnoentities("YouReceiveMailBecauseOfNotification",$application,$mysoc->name)."\n";
						$message.= $outputlangs->transnoentities("YouReceiveMailBecauseOfNotification2",$application,$mysoc->name)."\n";
						$message.= "\n";
						$message.= $mesg;
						if ($link) $message=dol_concatdesc($message,$urlwithroot.$link);

						$parameters=array('notifcode'=>$notifcode, 'sendto'=>$sendto, 'replyto'=>$replyto, 'file'=>$file, 'mimefile'=>$mimefile, 'filename'=>$filename);
						$reshook=$hookmanager->executeHooks('formatNotificationMessage',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
						if (empty($reshook))
						{
							if (! empty($hookmanager->resArray['subject'])) $subject.=$hookmanager->resArray['subject'];
							if (! empty($hookmanager->resArray['message'])) $message.=$hookmanager->resArray['message'];
						}

						$mailfile = new CMailFile(
							$subject,
							$sendto,
							$replyto,
							$message,
							array($file),
							array($mimefile),
							array($filename[count($filename)-1]),
							'',
							'',
							0,
							-1
						);

						if ($mailfile->sendfile())
						{
							if ($obj->type_target == 'touserid') {
	 							$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify (daten, fk_action, fk_soc, fk_user, type, objet_type, type_target, objet_id, email)";
								$sql.= " VALUES ('".$this->db->idate(dol_now())."', ".$notifcodedefid.", ".($object->socid?$object->socid:'null').", ".$obj->cid.", '".$obj->type."', '".$object_type."', '".$obj->type_target."', ".$object->id.", '".$this->db->escape($obj->email)."')";

							}
							else {
								$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify (daten, fk_action, fk_soc, fk_contact, type, objet_type, type_target, objet_id, email)";
								$sql.= " VALUES ('".$this->db->idate(dol_now())."', ".$notifcodedefid.", ".($object->socid?$object->socid:'null').", ".$obj->cid.", '".$obj->type."', '".$object_type."', '".$obj->type_target."', ".$object->id.", '".$this->db->escape($obj->email)."')";

							}
							if (! $this->db->query($sql))
							{
								dol_print_error($this->db);
							}
						}
						else
						{
							$error++;
							$this->errors[]=$mailfile->error;
						}
					}
					else
				  {
						dol_syslog("No notification sent for ".$sendto." because email is empty");
					}
					$i++;
				}
			}
			else
			{
				dol_syslog("No notification to thirdparty sent, nothing into notification setup for the thirdparty socid = ".$object->socid);
			}
		}
		else
		{
	   		$error++;
			$this->errors[]=$this->db->lasterror();
			dol_syslog("Failed to get list of notification to send ".$this->db->lasterror(), LOG_ERR);
	   		return -1;
		}

		// Check notification using fixed email
		if (! $error)
		{
			foreach($conf->global as $key => $val)
			{
				if ($val == '' || ! preg_match('/^NOTIFICATION_FIXEDEMAIL_'.$notifcode.'_THRESHOLD_HIGHER_(.*)$/', $key, $reg)) continue;

				$threshold = (float) $reg[1];
				if (!empty($object->total_ht) && $object->total_ht <= $threshold)
				{
					dol_syslog("A notification is requested for notifcode = ".$notifcode." but amount = ".$object->total_ht." so lower than threshold = ".$threshold.". We discard this notification");
					continue;
				}

				$param='NOTIFICATION_FIXEDEMAIL_'.$notifcode.'_THRESHOLD_HIGHER_'.$reg[1];

				$sendto = $conf->global->$param;
				$notifcodedefid = dol_getIdFromCode($this->db, $notifcode, 'c_action_trigger', 'code', 'rowid');
				if ($notifcodedefid <= 0) dol_print_error($this->db, 'Failed to get id from code');

				$object_type = '';
				$link = '';
				$num++;

				$subject = '['.$mysoc->name.'] '.$langs->transnoentitiesnoconv("DolibarrNotification");

				switch ($notifcode) {
					case 'BILL_VALIDATE':
						$link='/compta/facture/card.php?facid='.$object->id;
						$dir_output = $conf->facture->dir_output;
						$object_type = 'facture';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInvoiceValidated",$newref);
						break;
					case 'BILL_PAYED':
						$link='/compta/facture/card.php?facid='.$object->id;
						$dir_output = $conf->facture->dir_output;
						$object_type = 'facture';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInvoicePayed",$newref);
						break;
					case 'ORDER_VALIDATE':
						$link='/commande/card.php?id='.$object->id;
						$dir_output = $conf->commande->dir_output;
						$object_type = 'order';
						$mesg = $langs->transnoentitiesnoconv("EMailTextOrderValidated",$newref);
						break;
					case 'PROPAL_VALIDATE':
						$link='/comm/propal/card.php?id='.$object->id;
						$dir_output = $conf->propal->dir_output;
						$object_type = 'propal';
						$mesg = $langs->transnoentitiesnoconv("EMailTextProposalValidated",$newref);
						break;
					case 'PROPAL_CLOSE_SIGNED':
						$link='/comm/propal/card.php?id='.$object->id;
						$dir_output = $conf->propal->dir_output;
						$object_type = 'propal';
						$mesg = $langs->transnoentitiesnoconv("EMailTextProposalClosedSigned",$newref);
						break;
					case 'FICHINTER_ADD_CONTACT':
						$link='/fichinter/card.php?id='.$object->id;
						$dir_output = $conf->facture->dir_output;
						$object_type = 'ficheinter';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInterventionAddedContact",$newref);
						break;
					case 'FICHINTER_VALIDATE':
						$link='/fichinter/card.php?id='.$object->id;
						$dir_output = $conf->facture->dir_output;
						$object_type = 'ficheinter';
						$mesg = $langs->transnoentitiesnoconv("EMailTextInterventionValidated",$newref);
						break;
					case 'ORDER_SUPPLIER_VALIDATE':
						$link='/fourn/commande/card.php?id='.$object->id;
						$dir_output = $conf->fournisseur->commande->dir_output;
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderValidatedBy",$newref,$user->getFullName($langs));
						$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'ORDER_SUPPLIER_APPROVE':
						$link='/fourn/commande/card.php?id='.$object->id;
						$dir_output = $conf->fournisseur->commande->dir_output;
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderApprovedBy",$newref,$user->getFullName($langs));
						$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'ORDER_SUPPLIER_APPROVE2':
						$link='/fourn/commande/card.php?id='.$object->id;
						$dir_output = $conf->fournisseur->commande->dir_output;
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderApprovedBy",$newref,$user->getFullName($langs));
						$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'ORDER_SUPPLIER_REFUSE':
						$link='/fourn/commande/card.php?id='.$object->id;
						$dir_output = $conf->fournisseur->dir_output.'/commande/';
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("Hello").",\n\n";
						$mesg.= $langs->transnoentitiesnoconv("EMailTextOrderRefusedBy",$newref,$user->getFullName($langs));
						$mesg.= "\n\n".$langs->transnoentitiesnoconv("Sincerely").".\n\n";
						break;
					case 'SHIPPING_VALIDATE':
						$dir_output = $conf->expedition->dir_output.'/sending/';
						$object_type = 'order_supplier';
						$mesg = $langs->transnoentitiesnoconv("EMailTextExpeditionValidated",$newref);
						break;
				}
				$ref = dol_sanitizeFileName($newref);
				$pdf_path = $dir_output."/".$ref."/".$ref.".pdf";
				if (! dol_is_file($pdf_path))
				{
					// We can't add PDF as it is not generated yet.
					$filepdf = '';
				}
				else
				{
					$filepdf = $pdf_path;
				}

				$message = $langs->transnoentities("YouReceiveMailBecauseOfNotification",$application,$mysoc->name)."\n";
				$message.= $langs->transnoentities("YouReceiveMailBecauseOfNotification2",$application,$mysoc->name)."\n";
				$message.= "\n";
				$message.= $mesg;
				if ($link) $message=dol_concatdesc($message,$urlwithroot.$link);

				// Replace keyword __SUPERVISOREMAIL__
				if (preg_match('/__SUPERVISOREMAIL__/', $sendto))
				{
					$newval='';
					if ($user->fk_user > 0)
					{
						$supervisoruser=new User($this->db);
						$supervisoruser->fetch($user->fk_user);
						if ($supervisoruser->email) $newval=trim(dolGetFirstLastname($supervisoruser->firstname, $supervisoruser->lastname).' <'.$supervisoruser->email.'>');
					}
					dol_syslog("Replace the __SUPERVISOREMAIL__ key into recipient email string with ".$newval);
					$sendto = preg_replace('/__SUPERVISOREMAIL__/', $newval, $sendto);
					$sendto = preg_replace('/,\s*,/', ',', $sendto);	// in some case you can have $sendto like "email, __SUPERVISOREMAIL__ , otheremail" then you have "email,  , othermail" and it's not valid
					$sendto = preg_replace('/^[\s,]+/', '', $sendto);	// Clean start of string
					$sendto = preg_replace('/[\s,]+$/', '', $sendto);	// Clean end of string
				}

				if ($sendto)
				{
	   				$parameters=array('notifcode'=>$notifcode, 'sendto'=>$sendto, 'replyto'=>$replyto, 'file'=>$file, 'mimefile'=>$mimefile, 'filename'=>$filename);
					$reshook=$hookmanager->executeHooks('formatNotificationMessage',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
					if (empty($reshook))
					{
						if (! empty($hookmanager->resArray['subject'])) $subject.=$hookmanager->resArray['subject'];
						if (! empty($hookmanager->resArray['message'])) $message.=$hookmanager->resArray['message'];
					}

					$mailfile = new CMailFile(
						$subject,
						$sendto,
						$replyto,
						$message,
						array($file),
						array($mimefile),
						array($filename[count($filename)-1]),
						'',
						'',
						0,
						-1
					);

					if ($mailfile->sendfile())
					{
						$sql = "INSERT INTO ".MAIN_DB_PREFIX."notify (daten, fk_action, fk_soc, fk_contact, type, type_target, objet_type, objet_id, email)";
						$sql.= " VALUES ('".$this->db->idate(dol_now())."', ".$notifcodedefid.", ".($object->socid?$object->socid:'null').", null, 'email', 'tofixedemail', '".$object_type."', ".$object->id.", '".$this->db->escape($conf->global->$param)."')";
						if (! $this->db->query($sql))
						{
							dol_print_error($this->db);
						}
					}
					else
					{
						$error++;
						$this->errors[]=$mailfile->error;
					}
				}
			}
		}

		if (! $error) return $num;
		else return -1 * $error;
	}

}

