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

// $mysoc must be defined
// $id must be defined
// $actiontypecode must be defined
// $paramname must be defined
// $mode must be defined
// $object and $uobject may be defined.


/*
 * Add file in email form
 */
if (GETPOST('addfile'))
{
	$trackid = GETPOST('trackid','aZ09');
	
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';             // TODO Add $keytoavoidconflict in upload_dir path

	dol_add_file_process($upload_dir_tmp, 0, 0, 'addedfile', '', null, $trackid);
	$action='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']) && empty($_POST['removAll']))
{
	$trackid = GETPOST('trackid','aZ09');
    
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';             // TODO Add $keytoavoidconflict in upload_dir path

	// TODO Delete only files that was uploaded from email form. This can be addressed by adding the trackid into the temp path then changing donotdeletefile to 2 instead of 1 to say "delete only if into temp dir"
	// GETPOST('removedfile','alpha') is position of file into $_SESSION["listofpaths"...] array.
	dol_remove_file_process(GETPOST('removedfile','alpha'), 0, 1, $trackid);   // We do not delete because if file is the official PDF of doc, we don't want to remove it physically
	$action='presend';
}

/*
 * Remove all files in email form
 */
if (GETPOST('removAll'))
{
	$trackid = GETPOST('trackid','aZ09');
	
    $listofpaths=array();
	$listofnames=array();
	$listofmimes=array();
    $keytoavoidconflict = empty($trackid)?'':'-'.$trackid;
	if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
	if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
	if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);

	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);
	$formmail->trackid = $trackid;
	
	foreach($listofpaths as $key => $value)
	{
		$pathtodelete = $value;
		$filetodelete = $listofnames[$key];
		$result = dol_delete_file($pathtodelete,1); // Delete uploded Files

		$langs->load("other");
		setEventMessages($langs->trans("FileWasRemoved",$filetodelete), null, 'mesgs');

		$formmail->remove_attached_files($key); // Update Session
	}
}

/*
 * Send mail
 */
if (($action == 'send' || $action == 'relance') && ! $_POST['addfile'] && ! $_POST['removAll'] && ! $_POST['removedfile'] && ! $_POST['cancel'] && !$_POST['modelselected'])
{
	$trackid = GETPOST('trackid','aZ09');
	$subject='';$actionmsg='';$actionmsg2='';
	
    if (! empty($conf->dolimail->enabled)) $langs->load("dolimail@dolimail");
	$langs->load('mails');

	if (is_object($object))
	{
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
    		if ($thirdparty->id > 0) $sendtosocid=$thirdparty->id;
    		elseif($conf->dolimail->enabled)
    		{
    			$dolimail = new Dolimail($db);
    			$possibleaccounts=$dolimail->get_societe_by_email($_POST['sendto'],"1");
    			$possibleuser=$dolimail->get_from_user_by_mail($_POST['sendto'],"1"); // suche in llx_societe and socpeople
    			if (!$possibleaccounts && !$possibleuser) 
    			{
    					setEventMessages($langs->trans('ErrorFailedToFindSocieteRecord',$_POST['sendto']), null, 'errors');
    			}
    			elseif (count($possibleaccounts)>1) 
    			{
    					$sendtosocid=$possibleaccounts[1]['id'];
    					$result=$object->fetch($sendtosocid);
    					
    					setEventMessages($langs->trans('ErrorFoundMoreThanOneRecordWithEmail',$_POST['sendto'],$object->name), null, 'mesgs');
    			}
    			else 
    			{
    				if($possibleaccounts){ 
    					$sendtosocid=$possibleaccounts[1]['id'];
    					$result=$object->fetch($sendtosocid);
    				}elseif($possibleuser){ 
    					$sendtosocid=$possibleuser[0]['id'];
    
    					$result=$uobject->fetch($sendtosocid);
    					$object=$uobject;
    				}
    				
    			}
    		}
    	}
    	else dol_print_error('','Use actions_sendmails.in.php for a type that is not supported');
	}
	else $thirdparty = $mysoc;

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
				$sendto = $thirdparty->name.' <'.$thirdparty->email.'>';
				$sendtoid = 0;
			}
			else	// Id du contact
			{
				$sendto = $thirdparty->contact_get_property((int) $_POST['receiver'],'email');
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
				$sendtocc = $thirdparty->name.' <'.$thirdparty->email.'>';
			}
			else	// Id du contact
			{
				$sendtocc = $thirdparty->contact_get_property((int) $_POST['receivercc'],'email');
			}
		}

		if (dol_strlen($sendto))
		{
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		    
			$langs->load("commercial");

			$fromtype = GETPOST('fromtype');
            if ($fromtype === 'user') {
                $from = $user->getFullName($langs) .' <'.$user->email.'>';
            }
            elseif ($fromtype === 'company') {
                $from = $conf->global->MAIN_INFO_SOCIETE_NOM .' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
            }
		    elseif (preg_match('/user_aliases_(\d+)/', $fromtype, $reg)) {
		        $tmp=explode(',', $user->email_aliases);
                $from = trim($tmp[($reg[1] - 1)]);
            }
		    elseif (preg_match('/global_aliases_(\d+)/', $fromtype, $reg)) {
                $tmp=explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);
                $from = trim($tmp[($reg[1] - 1)]);
            }
            else {
                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
            }

            $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
			$message = $_POST['message'];
			$sendtobcc= GETPOST('sendtoccc');
			if ($mode == 'emailfromproposal') $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO));
			if ($mode == 'emailfromorder')    $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO));
			if ($mode == 'emailfrominvoice')  $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO));
			if ($mode == 'emailfromsupplierproposal') $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO));
			if ($mode == 'emailfromsupplierorder')    $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO));
			if ($mode == 'emailfromsupplierinvoice')  $sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO));
				
			$deliveryreceipt = $_POST['deliveryreceipt'];

			if ($action == 'send' || $action == 'relance')
			{
				if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
				$actionmsg2=$langs->transnoentities('MailSentBy').' '.CMailFile::getValidAddress($from,4,0,1).' '.$langs->transnoentities('To').' '.CMailFile::getValidAddress($sendto,4,0,1);
				if ($message)
				{
					$actionmsg=$langs->transnoentities('MailFrom').': '.dol_escape_htmltag($from);
					$actionmsg=dol_concatdesc($actionmsg, $langs->transnoentities('MailTo').': '.dol_escape_htmltag($sendto));
					if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . dol_escape_htmltag($sendtocc));
					$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
					$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
					$actionmsg = dol_concatdesc($actionmsg, $message);
				}
			}

			// Create form object
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			$formmail->trackid = $trackid;      // $trackid must be defined
            
			$attachedfiles=$formmail->get_attached_files();
			$filepath = $attachedfiles['paths'];
			$filename = $attachedfiles['names'];
			$mimetype = $attachedfiles['mimes'];


			// Feature to push mail sent into Sent folder
			if (! empty($conf->dolimail->enabled))
			{
				$mailfromid = explode("#", $_POST['frommail'],3);	// $_POST['frommail'] = 'aaa#Sent# <aaa@aaa.com>'	// TODO Use a better way to define Sent dir.
				if (count($mailfromid)==0) $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
				else
				{
					$mbid = $mailfromid[1];

					/*IMAP Postbox*/
					$mailboxconfig = new IMAP($db);
					$mailboxconfig->fetch($mbid);
					if ($mailboxconfig->mailbox_imap_host) $ref=$mailboxconfig->get_ref();

					$mailboxconfig->folder_id=$mailboxconfig->mailbox_imap_outbox;
					$mailboxconfig->userfolder_fetch();

					if ($mailboxconfig->mailbox_save_sent_mails == 1)
					{

						$folder=str_replace($ref, '', $mailboxconfig->folder_cache_key);
						if (!$folder) $folder = "Sent";	// Default Sent folder

						$mailboxconfig->mbox = imap_open($mailboxconfig->get_connector_url().$folder, $mailboxconfig->mailbox_imap_login, $mailboxconfig->mailbox_imap_password);
						if (FALSE === $mailboxconfig->mbox)
						{
							$info = FALSE;
							$err = $langs->trans('Error3_Imap_Connection_Error');
							setEventMessages($err,$mailboxconfig->element, null, 'errors');
						}
						else
						{
							$mailboxconfig->mailboxid=$_POST['frommail'];
							$mailboxconfig->foldername=$folder;
							$from = $mailfromid[0] . $mailfromid[2];
							$imap=1;
						}

					}
				}
			}

			// Send mail
			$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$sendtobcc,$deliveryreceipt,-1,'','',$trackid);
			if ($mailfile->error)
			{
				setEventMessage($mailfile->error, 'errors');
				$action='presend';
			}
			else
			{
				$result=$mailfile->sendfile();
				if ($result)
				{
					$error=0;

					// FIXME This must be moved into a trigger for action $trigger_name
					if (! empty($conf->dolimail->enabled))
					{
						$mid = (GETPOST('mid','int') ? GETPOST('mid','int') : 0);	// Original mail id is set ?
						if ($mid)
						{
							// set imap flag answered if it is an answered mail
							$dolimail=new DoliMail($db);
							$dolimail->id = $mid;
							$res=$dolimail->set_prop($user, 'answered',1);
				  		}
						if ($imap==1)
						{
							// write mail to IMAP Server
							$movemail = $mailboxconfig->putMail($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$folder,$deliveryreceipt,$mailfile);
							if ($movemail) setEventMessages($langs->trans("MailMovedToImapFolder",$folder), null, 'mesgs');
							else setEventMessages($langs->trans("MailMovedToImapFolder_Warning",$folder), null, 'warnings');
				 	 	}
				 	}

					// Initialisation of datas
					if (is_object($object))
					{
    					$object->socid			= $sendtosocid;	// To link to a company
    					$object->sendtoid		= $sendtoid;	// To link to a contact/address
    					$object->actiontypecode	= $actiontypecode;
    					$object->actionmsg		= $actionmsg;  // Long text
    					$object->actionmsg2		= $actionmsg2; // Short text
    					$object->trackid        = $trackid;
    					$object->fk_element		= $object->id;
    					$object->elementtype	= $object->element;
    
    					// Call of triggers
    					include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    					$interface=new Interfaces($db);
    					$result=$interface->run_triggers($trigger_name,$object,$user,$langs,$conf);
    					if ($result < 0) {
    						$error++; $errors=$interface->errors;
    					}
    					// End call of triggers
					}
					
					if ($error)
					{
						dol_print_error($db);
					}
					else
					{
						// Redirect here
						// This avoid sending mail twice if going out and then back to page
						$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));
						setEventMessages($mesg, null, 'mesgs');
						if ($conf->dolimail->enabled) header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.$object->id.'&'.($paramname2?$paramname2:'mid').'='.$parm2val);
						else header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.$object->id);
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

					setEventMessages($mesg, null, 'warnings');
					$action = 'presend';
				}
			}
		}
		else
		{
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("MailTo")), null, 'warnings');
			dol_syslog('Try to send email with no recipiend defined', LOG_WARNING);
			$action = 'presend';
		}
	}
	else
	{
		$langs->load("other");
		setEventMessages($langs->trans('ErrorFailedToReadEntity',$object->element), null, 'errors');
		dol_syslog('Failed to read data of object id='.$object->id.' element='.$object->element);
		$action = 'presend';
	}

}
