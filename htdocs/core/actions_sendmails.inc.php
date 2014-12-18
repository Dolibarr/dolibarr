<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_sendmails.inc.php
 *  \brief			Code for actions on sending mails from object page
 */


// TODO Include this include file into all class objects

// $id must be defined
// $actiontypecode must be defined
// $paramname must be defined
// $mode must be defined

/*
 * Add file in email form
 */
if (GETPOST('addfile'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	dol_add_file_process($upload_dir_tmp,0,0);
	$action='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
	dol_remove_file_process($_POST['removedfile'],0);
	$action='presend';
}

/*
 * Send mail
 */
if (($action == 'send' || $action == 'relance') && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$subject='';$actionmsg='';$actionmsg2='';

	$result=$object->fetch($id);

	$sendtosocid=0;
	if (method_exists($object,"fetch_thirdparty") && $object->element != 'societe')
	{
		$result=$object->fetch_thirdparty();
		$thirdparty=$object->thirdparty;
		$sendtosocid=$thirdparty->id;
	}
	else if ($object->element == 'societe')
	{
		$thirdparty=$object;
		$sendtosocid=$thirdparty->id;
	}
	else dol_print_error('','Use actions_sendmails.in.php for a type that is not supported');

	if ($result > 0)
	{
		if (trim($_POST['sendto']))
		{
			// Recipient is provided into free text
			$sendto = trim($_POST['sendto']);
			$sendtoid = 0;
		}
		elseif ($_POST['receiver'] != '-1')
		{
			// Recipient was provided from combo list
			if ($_POST['receiver'] == 'thirdparty') // Id of third party
			{
				$sendto = $thirdparty->email;
				$sendtoid = 0;
			}
			else	// Id du contact
			{
				$sendto = $thirdparty->contact_get_property($_POST['receiver'],'email');
				$sendtoid = $_POST['receiver'];
			}
		}
		if (trim($_POST['sendtocc']))
		{
			$sendtocc = trim($_POST['sendtocc']);
		}
		elseif ($_POST['receivercc'] != '-1')
		{
			// Recipient was provided from combo list
			if ($_POST['receivercc'] == 'thirdparty')	// Id of third party
			{
				$sendtocc = $thirdparty->email;
			}
			else	// Id du contact
			{
				$sendtocc = $thirdparty->contact_get_property($_POST['receivercc'],'email');
			}
		}

		if (dol_strlen($sendto))
		{
			$langs->load("commercial");

			$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
			$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
			$message = $_POST['message'];
			$sendtocc = $sendtocc;
			$sendtobcc= GETPOST('sendtoccc');
			if ($mode == 'emailfromproposal') $sendtobcc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO);
			if ($mode == 'emailfromorder')    $sendtobcc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO);
			if ($mode == 'emailfrominvoice')  $sendtobcc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO);

			$deliveryreceipt = $_POST['deliveryreceipt'];

			if ($action == 'send' || $action == 'relance')
			{
				if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
				$actionmsg2=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
				if ($message)
				{
					$actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
					if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
					$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
					$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
					$actionmsg = dol_concatdesc($actionmsg, $message);
				}
			}

			// Create form object
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);

			$attachedfiles=$formmail->get_attached_files();
			$filepath = $attachedfiles['paths'];
			$filename = $attachedfiles['names'];
			$mimetype = $attachedfiles['mimes'];

			// Send mail
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$sendtobcc,$deliveryreceipt,-1);
			if ($mailfile->error)
			{
				$mesgs[]='<div class="error">'.$mailfile->error.'</div>';
			}
			else
			{
				$result=$mailfile->sendfile();
				if ($result)
				{
					$error=0;

					// Initialisation donnees
					$object->socid			= $sendtosocid;	// To link to a company
					$object->sendtoid		= $sendtoid;	// To link to a contact/address
					$object->actiontypecode	= $actiontypecode;
					$object->actionmsg		= $actionmsg;  // Long text
					$object->actionmsg2		= $actionmsg2; // Short text
					$object->fk_element		= $object->id;
					$object->elementtype	= $object->element;

					// Appel des triggers
					include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
					$interface=new Interfaces($db);
					$result=$interface->run_triggers($trigger_name,$object,$user,$langs,$conf);
					if ($result < 0) {
						$error++; $this->errors=$interface->errors;
					}
					// Fin appel triggers

					if ($error)
					{
						dol_print_error($db);
					}
					else
					{
						// Redirect here
						// This avoid sending mail twice if going out and then back to page
						$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));
						setEventMessage($mesg);
						header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.$object->id);
						exit;
					}
				}
				else
				{
					$langs->load("other");
					$mesg='<div class="error">';
					if ($mailfile->error)
					{
						$mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
						$mesg.='<br>'.$mailfile->error;
					}
					else
					{
						$mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
					}
					$mesg.='</div>';

					setEventMessage($mesg,'warnings');
					$action = 'presend';
				}
			}
			/*  }
			 else
			{
			$langs->load("other");
			$mesgs[]='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
			dol_syslog('Recipient email is empty');
			}*/
		}
		else
		{
			$langs->load("errors");
			setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("MailTo")),'warnings');
			dol_syslog('Try to send email with no recipiend defined', LOG_WARNING);
			$action = 'presend';
		}
	}
	else
	{
		$langs->load("other");
		setEventMessage($langs->trans('ErrorFailedToReadEntity',$object->element),'errors');
		dol_syslog('Failed to read data of object id='.$object->id.' element='.$object->element);
		$action = 'presend';
	}

}
