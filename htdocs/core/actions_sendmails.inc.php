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
// $paramname must be defined
// $mode must be defined (used to know the automatic BCC to add)
// $trigger_name must be set (can be '')
// $actiontypecode can be set
// $object and $uobject may be defined

/*
 * Add file in email form
 */
if (GETPOST('addfile','alpha'))
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
if (GETPOST('removAll','alpha'))
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
	if (empty($trackid)) $trackid = GETPOST('trackid','aZ09');

	$subject='';$actionmsg='';$actionmsg2='';

	$langs->load('mails');

	if (is_object($object))
	{
		$result=$object->fetch($id);

		$sendtosocid=0;    // Thirdparty on object
		if (method_exists($object,"fetch_thirdparty") && ! in_array($object->element, array('societe','member','user','expensereport')))
		{
			$result=$object->fetch_thirdparty();
			if ($object->element == 'user' && $result == 0) $result=1;    // Even if not found, we consider ok
			$thirdparty=$object->thirdparty;
			$sendtosocid=$thirdparty->id;
		}
		else if ($object->element == 'member' || $object->element == 'user')
		{
			$thirdparty=$object;
			if ($thirdparty->id > 0) $sendtosocid=$thirdparty->id;
		}
		else if ($object->element == 'societe')
		{
			$thirdparty=$object;
			if ($thirdparty->id > 0) $sendtosocid=$thirdparty->id;
			elseif (! empty($conf->dolimail->enabled))
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
		else dol_print_error('','Use actions_sendmails.in.php for an element/object that is not supported');
	}
	else $thirdparty = $mysoc;

	if ($result > 0)
	{
		$sendto='';
		$sendtocc='';
		$sendtobcc='';
		$sendtoid = array();

		// Define $sendto
		$receiver=$_POST['receiver'];
		if (! is_array($receiver))
		{
			if ($receiver == '-1') $receiver=array();
			else $receiver=array($receiver);
		}
		$tmparray=array();
		if (trim($_POST['sendto']))
		{
			// Recipients are provided into free text
			$tmparray[] = trim($_POST['sendto']);
		}
		if (count($receiver)>0)
		{
			foreach($receiver as $key=>$val)
			{
				// Recipient was provided from combo list
				if ($val == 'thirdparty') // Id of third party
				{
					$tmparray[] = $thirdparty->name.' <'.$thirdparty->email.'>';
				}
				elseif ($val)	// Id du contact
				{
					$tmparray[] = $thirdparty->contact_get_property((int) $val,'email');
					$sendtoid[] = $val;
				}
			}
		}
		$sendto=implode(',',$tmparray);

		// Define $sendtocc
		$receivercc=$_POST['receivercc'];
		if (! is_array($receivercc))
		{
			if ($receivercc == '-1') $receivercc=array();
			else $receivercc=array($receivercc);
		}
		$tmparray=array();
		if (trim($_POST['sendtocc']))
		{
			$tmparray[] = trim($_POST['sendtocc']);
		}
		if (count($receivercc) > 0)
		{
			foreach($receivercc as $key=>$val)
			{
				// Recipient was provided from combo list
				if ($val == 'thirdparty') // Id of third party
				{
					$tmparray[] = $thirdparty->name.' <'.$thirdparty->email.'>';
				}
				elseif ($val)	// Id du contact
				{
					$tmparray[] = $thirdparty->contact_get_property((int) $val,'email');
					//$sendtoid[] = $val;  TODO Add also id of contact in CC ?
				}
			}
		}
		$sendtocc=implode(',',$tmparray);

		if (dol_strlen($sendto))
		{
            // Define $urlwithroot
            $urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
            $urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
            //$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		    require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

			$langs->load("commercial");

			$fromtype = GETPOST('fromtype','alpha');
			if ($fromtype === 'robot') {
				$from = $conf->global->MAIN_MAIL_EMAIL_FROM .' <'.$conf->global->MAIN_MAIL_EMAIL_FROM.'>';
			}
			elseif ($fromtype === 'user') {
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
			elseif (preg_match('/senderprofile_(\d+)_(\d+)/', $fromtype, $reg)) {
				$sql='SELECT rowid, label, email FROM '.MAIN_DB_PREFIX.'c_email_senderprofile WHERE rowid = '.(int) $reg[1];
				$resql = $db->query($sql);
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$from = $obj->label.' <'.$obj->email.'>';
				}
			}
			else {
				$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
			}

			$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
			$message = GETPOST('message','none');
			$subject = GETPOST('subject','none');

			// Make a change into HTML code to allow to include images from medias directory with an external reabable URL.
			// <img alt="" src="/dolibarr_dev/htdocs/viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
			// become
			// <img alt="" src="'.$urlwithroot.'viewimage.php?modulepart=medias&amp;entity=1&amp;file=image/ldestailleur_166x166.jpg" style="height:166px; width:166px" />
			$message=preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $message);

			$sendtobcc= GETPOST('sendtoccc');
			// Autocomplete the $sendtobcc
			// $autocopy can be MAIN_MAIL_AUTOCOPY_PROPOSAL_TO, MAIN_MAIL_AUTOCOPY_ORDER_TO, MAIN_MAIL_AUTOCOPY_INVOICE_TO, MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO...
			if (! empty($autocopy))
			{
				$sendtobcc .= (empty($conf->global->$autocopy) ? '' : (($sendtobcc?", ":"").$conf->global->$autocopy));
			}

			$deliveryreceipt = $_POST['deliveryreceipt'];

			if ($action == 'send' || $action == 'relance')
			{
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
			/* This code must be now included into the hook mail, method sendMailAfter
			if (! empty($conf->dolimail->enabled))
			{
				$mailfromid = explode("#", $_POST['frommail'],3);	// $_POST['frommail'] = 'aaa#Sent# <aaa@aaa.com>'	// TODO Use a better way to define Sent dir.
				if (count($mailfromid)==0) $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
				else
				{
					$mbid = $mailfromid[1];

					// IMAP Postbox
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
			*/

			// Make substitution in email content
			$substitutionarray=getCommonSubstitutionArray($langs, 0, null, $object);
			$substitutionarray['__EMAIL__'] = $sendto;
			$substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty))?'<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$object->thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>':'';

			$parameters=array('mode'=>'formemail');
			complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

			$subject=make_substitutions($subject, $substitutionarray);
			$message=make_substitutions($message, $substitutionarray);

			if (method_exists($object, 'makeSubstitution'))
			{
				$subject = $object->makeSubstitution($subject);
				$message = $object->makeSubstitution($message);
			}

			// Send mail (substitutionarray must be done just before this)
			if (empty($sendcontext)) $sendcontext = 'standard';
			$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$sendtobcc,$deliveryreceipt,-1,'','',$trackid,'', $sendcontext);

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
					// Two hooks are available into method $mailfile->sendfile, so dedicated code is no more required
					/*
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
					}*/

					// Initialisation of datas
					if (is_object($object))
					{
					    if (empty($actiontypecode)) $actiontypecode='AC_OTH_AUTO'; // Event insert into agenda automatically

						$object->socid			= $sendtosocid;	   // To link to a company
						$object->sendtoid		= $sendtoid;	   // To link to contacts/addresses. This is an array.
						$object->actiontypecode	= $actiontypecode; // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
						$object->actionmsg		= $actionmsg;      // Long text
						$object->actionmsg2		= $actionmsg2;     // Short text
						$object->trackid        = $trackid;
						$object->fk_element		= $object->id;
						$object->elementtype	= $object->element;
						if (is_array($attachedfiles) && count($attachedfiles)>0) {
							$object->attachedfiles	= $attachedfiles;
						}

						// Call of triggers
						if (! empty($trigger_name))
						{
    						include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    						$interface=new Interfaces($db);
    						$result=$interface->run_triggers($trigger_name,$object,$user,$langs,$conf);
							if ($result < 0) {
    							setEventMessages($interface->error, $interface->errors, 'errors');
    						}
						}
					}

					// Redirect here
					// This avoid sending mail twice if going out and then back to page
					$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));
					setEventMessages($mesg, null, 'mesgs');

					$moreparam='';
					if (isset($paramname2) || isset($paramval2)) $moreparam.= '&'.($paramname2?$paramname2:'mid').'='.$paramval2;
					header('Location: '.$_SERVER["PHP_SELF"].'?'.($paramname?$paramname:'id').'='.(is_object($object)?$object->id:'').$moreparam);
					exit;
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
			dol_syslog('Try to send email with no recipient defined', LOG_WARNING);
			$action = 'presend';
		}
	}
	else
	{
		$langs->load("other");
		setEventMessages($langs->trans('ErrorFailedToReadObject',$object->element), null, 'errors');
		dol_syslog('Failed to read data of object id='.$object->id.' element='.$object->element);
		$action = 'presend';
	}

}
