<?PHP
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011	Laurent Destailleur		<eldy@uers.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \file       htdocs/comm/mailing/fiche.php
 *       \ingroup    mailing
 *       \brief      Fiche mailing, onglet general
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/emailing.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/comm/mailing/class/mailing.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");

$langs->load("mails");

if (! $user->rights->mailing->lire || $user->societe_id > 0) accessforbidden();

$id=(GETPOST('mailid','int') ? GETPOST('mailid','int') : GETPOST('id','int'));
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$message = '';

$object=new Mailing($db);


// Tableau des substitutions possibles
$substitutionarray=array(
'__ID__' => 'IdRecord',
'__EMAIL__' => 'EMail',
'__CHECK_READ__' => 'CheckMail',
'__UNSUSCRIBE__' => 'Unsuscribe',
'__LASTNAME__' => 'Lastname',
'__FIRSTNAME__' => 'Firstname',
'__OTHER1__' => 'Other1',
'__OTHER2__' => 'Other2',
'__OTHER3__' => 'Other3',
'__OTHER4__' => 'Other4',
'__OTHER5__' => 'Other5',
'__SIGNATURE__' => 'Signature',
'__PERSONALIZED__' => 'Personalized'
);
$substitutionarrayfortest=array(
'__ID__' => 'TESTIdRecord',
'__EMAIL__' => 'TESTEMail',
'__CHECK_READ__' => 'TESTCheckMail',
'__UNSUSCRIBE__' => 'TESTUnsuscribe',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname',
'__OTHER1__' => 'TESTOther1',
'__OTHER2__' => 'TESTOther2',
'__OTHER3__' => 'TESTOther3',
'__OTHER4__' => 'TESTOther4',
'__OTHER5__' => 'TESTOther5',
'__SIGNATURE__' => 'TESTSignature',
'__PERSONALIZED__' => 'TESTPersonalized'
);


// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
	if (empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$result=$object->createFromClone($id,$_REQUEST["clone_content"],$_REQUEST["clone_receivers"]);
		if ($result > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
			exit;
		}
		else
		{
			$mesg=$object->error;
			$action='';
		}
	}
}

// Action send emailing for everybody
if ($action == 'sendallconfirmed' && $confirm == 'yes')
{
	if (empty($conf->global->MAILING_LIMIT_SENDBYWEB))
	{
		// Pour des raisons de securite, on ne permet pas cette fonction via l'IHM,
		// on affiche donc juste un message
		$message='<div class="warning">'.$langs->trans("MailingNeedCommand").'</div>';
		$message.='<br><textarea cols="70" rows="'.ROWS_2.'" wrap="soft">php ./scripts/emailings/mailing-send.php '.$id.'</textarea>';
		$message.='<br><br><div class="warning">'.$langs->trans("MailingNeedCommand2").'</div>';
		$action='';
	}
	else if ($conf->global->MAILING_LIMIT_SENDBYWEB < 0)
	{
		$message='<div class="warning">'.$langs->trans("NotEnoughPermissions").'</div>';
		$action='';
	}
	else
	{
		$result=$object->fetch($id);

		$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);

		if ($object->statut == 0)
		{
			dol_print_error('','ErrorMailIsNotValidated');
			exit;
		}

		$id       = $object->id;
		$subject  = $object->sujet;
		$message  = $object->body;
		$from     = $object->email_from;
		$replyto  = $object->email_replyto;
		$errorsto = $object->email_errorsto;
		// Le message est-il en html
		$msgishtml=-1;	// Unknown by default
		if (preg_match('/[\s\t]*<html>/i',$message)) $msgishtml=1;

		// Warning, we must not use begin-commit transaction here
		// because we want to save update for each mail sent.

		$nbok=0; $nbko=0;

		// On choisit les mails non deja envoyes pour ce mailing (statut=0)
		// ou envoyes en erreur (statut=-1)
		$sql = "SELECT mc.rowid, mc.nom, mc.prenom, mc.email, mc.other, mc.source_url, mc.source_id, mc.source_type, mc.tag";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
		$sql .= " WHERE mc.statut < 1 AND mc.fk_mailing = ".$object->id;

		dol_syslog("fiche.php: select targets sql=".$sql, LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);	// nb of possible recipients

			if ($num)
			{
				dol_syslog("comm/mailing/fiche.php: nb of targets = ".$num, LOG_DEBUG);
				
				$now=dol_now();

				// Positionne date debut envoi
				$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET date_envoi=".$db->idate($now)." WHERE rowid=".$object->id;
				$resql2=$db->query($sql);
				if (! $resql2)
				{
					dol_print_error($db);
				}

				// Loop on each email and send it
				$i = 0;

				while ($i < $num && $i < $conf->global->MAILING_LIMIT_SENDBYWEB)
				{

					$res=1;

					$obj = $db->fetch_object($resql);

					// sendto en RFC2822
					$sendto = str_replace(',',' ',$obj->prenom." ".$obj->nom)." <".$obj->email.">";

					// Make substitutions on topic and body. From (AA=YY;BB=CC;...) we keep YY, CC, ...
					$other=explode(';',$obj->other);
					$tmpfield=explode('=',$other[0],2); $other1=(isset($tmpfield[1])?$tmpfield[1]:$tmpfield[0]);
                    $tmpfield=explode('=',$other[1],2); $other2=(isset($tmpfield[1])?$tmpfield[1]:$tmpfield[0]);
                    $tmpfield=explode('=',$other[2],2); $other3=(isset($tmpfield[1])?$tmpfield[1]:$tmpfield[0]);
                    $tmpfield=explode('=',$other[3],2); $other4=(isset($tmpfield[1])?$tmpfield[1]:$tmpfield[0]);
                    $tmpfield=explode('=',$other[4],2); $other5=(isset($tmpfield[1])?$tmpfield[1]:$tmpfield[0]);
					$substitutionarray=array(
						'__ID__' => $obj->source_id,
						'__EMAIL__' => $obj->email,
						'__CHECK_READ__' => '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$obj->tag.'" style="width:0px;height:0px" border="0"/>',
						'__UNSUSCRIBE__' => '<a href="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-usubscribe.php?tag='.$obj->tag.'&unsuscrib=1" target="_blank"/>'.$langs->trans("MailUnsubcribe").'</a>',
						'__LASTNAME__' => $obj->nom,
						'__FIRSTNAME__' => $obj->prenom,
						'__OTHER1__' => $other1,
						'__OTHER2__' => $other2,
						'__OTHER3__' => $other3,
						'__OTHER4__' => $other4,
						'__OTHER5__' => $other5
					);

					$substitutionisok=true;
                    complete_substitutions_array($substitutionarray, $langs);
					$newsubject=make_substitutions($subject,$substitutionarray);
					$newmessage=make_substitutions($message,$substitutionarray);

					$arr_file = array();
					$arr_mime = array();
					$arr_name = array();
					$arr_css  = array();

					$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
					if (count($listofpaths))
					{
						foreach($listofpaths as $key => $val)
						{
							$arr_file[]=$listofpaths[$key]['fullname'];
							$arr_mime[]=dol_mimetype($listofpaths[$key]['name']);
							$arr_name[]=$listofpaths[$key]['name'];
						}
					}

					// Fabrication du mail
					$mail = new CMailFile($newsubject, $sendto, $from, $newmessage, $arr_file, $arr_mime, $arr_name, '', '', 0, $msgishtml, $errorsto, $arr_css);

					if ($mail->error)
					{
						$res=0;
					}
					if (! $substitutionisok)
					{
						$mail->error='Some substitution failed';
						$res=0;
					}

					// Send mail
					if ($res)
					{
						$res=$mail->sendfile();
					}

					if ($res)
					{
						// Mail successful
						$nbok++;

						dol_syslog("comm/mailing/fiche.php: ok for #".$i.($mail->error?' - '.$mail->error:''), LOG_DEBUG);

						$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
						$sql.=" SET statut=1, date_envoi=".$db->idate($now)." WHERE rowid=".$obj->rowid;
						$resql2=$db->query($sql);
						if (! $resql2)
						{
							dol_print_error($db);
						}
					}
					else
					{
						// Mail failed
						$nbko++;

						dol_syslog("comm/mailing/fiche.php: error for #".$i.($mail->error?' - '.$mail->error:''), LOG_WARNING);

						$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
						$sql.=" SET statut=-1, date_envoi=".$db->idate($now)." WHERE rowid=".$obj->rowid;
						$resql2=$db->query($sql);
						if (! $resql2)
						{
							dol_print_error($db);
						}
					}

					$i++;
				}
			}

			// Loop finished, set global statut of mail
			if ($nbko > 0)
			{
				$statut=2;	// Status 'sent partially' (because at least one error)
			}
			else
			{
				if ($nbok >= $num) $statut=3;	// Send to everybody
				else $statut=2;	// Status 'sent partially' (because not send to everybody)
			}

			$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$statut." WHERE rowid=".$object->id;
			dol_syslog("comm/mailing/fiche.php: update global status sql=".$sql, LOG_DEBUG);
			$resql2=$db->query($sql);
			if (! $resql2)
			{
				dol_print_error($db);
			}
		}
		else
		{
			dol_syslog($db->error());
			dol_print_error($db);
		}
		$message='';
		$action = '';

	}
}

// Action send test emailing
if ($action == 'send' && empty($_POST["cancel"]))
{
	$result=$object->fetch($id);

	$error=0;

	$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);

	$object->sendto = $_POST["sendto"];
	if (! $object->sendto)
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("MailTo")).'</div>';
		$error++;
	}

	if (! $error)
	{
		// Le message est-il en html
		$msgishtml=-1;	// Inconnu par defaut
		if (preg_match('/[\s\t]*<html>/i',$message)) $msgishtml=1;

		// Pratique les substitutions sur le sujet et message
		$object->sujet=make_substitutions($object->sujet,$substitutionarrayfortest,$langs);
		$object->body=make_substitutions($object->body,$substitutionarrayfortest,$langs);

		$arr_file = array();
		$arr_mime = array();
		$arr_name = array();
		$arr_css  = array();

        // Ajout CSS
        if (!empty($object->bgcolor)) $arr_css['bgcolor'] = (preg_match('/^#/',$object->bgcolor)?'':'#').$object->bgcolor;
        if (!empty($object->bgimage)) $arr_css['bgimage'] = $object->bgimage;

        // Attached files
		$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
		if (count($listofpaths))
		{
			foreach($listofpaths as $key => $val)
			{
				$arr_file[]=$listofpaths[$key]['fullname'];
				$arr_mime[]=dol_mimetype($listofpaths[$key]['name']);
				$arr_name[]=$listofpaths[$key]['name'];
			}
		}

		$mailfile = new CMailFile($object->sujet,$object->sendto,$object->email_from,$object->body, $arr_file,$arr_mime,$arr_name,'', '', 0, $msgishtml,$object->email_errorsto,$arr_css);

		$result=$mailfile->sendfile();
		if ($result)
		{
			$message='<div class="ok">'.$langs->trans("MailSuccessfulySent",$mailfile->getValidAddress($object->email_from,2),$mailfile->getValidAddress($object->sendto,2)).'</div>';
		}
		else
		{
			$message='<div class="error">'.$langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result.'</div>';
		}

		$action='';
	}
}

// Action add emailing
if ($action == 'add')
{
	$message='';

	$object->email_from     = trim($_POST["from"]);
	$object->email_replyto  = trim($_POST["replyto"]);
	$object->email_errorsto = trim($_POST["errorsto"]);
	$object->titre          = trim($_POST["titre"]);
	$object->sujet          = trim($_POST["sujet"]);
	$object->body           = trim($_POST["body"]);
	$object->bgcolor        = trim($_POST["bgcolor"]);
	$object->bgimage        = trim($_POST["bgimage"]);

	if (! $object->titre) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailTitle"));
	if (! $object->sujet) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailTopic"));
	if (! $object->body)  $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailBody"));

	if (! $message)
	{
		if ($object->create($user) >= 0)
		{
			Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		$message=$object->error;
	}

	$message='<div class="error">'.$message.'</div>';
	$action="create";
}

// Action update description of emailing
if ($action == 'setdesc' || $action == 'setfrom' || $actino == 'setreplyto' || $action == 'seterrorsto')
{
	$object->fetch($id);

	$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);

	if ($action == 'setdesc')     $object->titre          = trim($_REQUEST["desc"]);
	else if ($action == 'setfrom')     $object->email_from     = trim($_REQUEST["from"]);
	else if ($action == 'setreplyto')  $object->email_replyto  = trim($_REQUEST["replyto"]);
	else if ($action == 'seterrorsto') $object->email_errorsto = trim($_REQUEST["errorsto"]);

	else if ($action == 'setdesc' && empty($object->titre))      $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailTitle"));
	else if ($action == 'setfrom' && empty($object->email_from)) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailFrom"));

	if (! $message)
	{
		if ($object->update($user) >= 0)
		{
			Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		$message=$object->error;
	}

	$message='<div class="error">'.$message.'</div>';
	$action="";
}

/*
 * Add file in email form
 */
if (! empty($_POST['addfile']))
{
	$object->fetch($id);

	$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);

	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory
    $mesg=dol_add_file_process($upload_dir,0,0);

	$action="edit";
}

// Action update emailing
if (! empty($_POST["removedfile"]))
{
	$object->fetch($id);

	$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);

	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    $mesg=dol_remove_file_process($_POST['removedfile'],0);

	$action="edit";
}

// Action update emailing
if ($action == 'update' && empty($_POST["removedfile"]) && empty($_POST["cancel"]))
{
	require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

	$object->fetch($id);

	$isupload=0;

	if (! $isupload)
	{
		$object->sujet          = trim($_POST["sujet"]);
		$object->body           = trim($_POST["body"]);
		$object->bgcolor        = trim($_POST["bgcolor"]);
		$object->bgimage        = trim($_POST["bgimage"]);

		if (! $object->sujet) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailTopic"));
		if (! $object->body)  $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailBody"));

		if (! $message)
		{
			if ($object->update($user) >= 0)
			{
				Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			$message=$object->error;
		}

		$message='<div class="error">'.$message.'</div>';
		$action="edit";
	}
	else
	{
		$action="edit";
	}
}

// Action confirmation validation
if ($action == 'confirm_valid' && $confirm == 'yes')
{	
	if ($object->fetch($id) >= 0)
	{
		$object->valid($user);
	
		Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

// Resend
if ($action == 'confirm_reset' && $confirm == 'yes')
{	
	if ($object->fetch($id) >= 0)
	{
		$db->begin();
	
		$result=$object->valid($user);
		if ($result > 0)
		{
			$result=$object->reset_targets_status($user);
		}
	
		if ($result > 0)
		{
			$db->commit();
			Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else
		{
			$mesg=$object->error;
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db);
	}
}

// Action confirmation suppression
if ($action == 'confirm_delete' && $confirm == 'yes')
{
	$object->fetch($id);
	
	if ($object->delete($object->id))
	{
		Header("Location: liste.php");
		exit;
	}
}

if (! empty($_POST["cancel"]))
{
	$action = '';
}



/*
 * View
 */

$help_url='EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing';
llxHeader('',$langs->trans("Mailing"),$help_url);

$form = new Form($db);
$htmlother = new FormOther($db);

if ($action == 'create')
{
	// EMailing in creation mode
	print '<form name="new_mailing" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print_fiche_titre($langs->trans("NewMailing"));

	dol_htmloutput_mesg($message);

	print '<table class="border" width="100%">';
	print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("MailTitle").'</td><td><input class="flat" name="titre" size="40" value="'.$_POST['titre'].'"></td></tr>';
	print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("MailFrom").'</td><td><input class="flat" name="from" size="40" value="'.$conf->global->MAILING_EMAIL_FROM.'"></td></tr>';
	print '<tr><td width="25%">'.$langs->trans("MailErrorsTo").'</td><td><input class="flat" name="errorsto" size="40" value="'.(!empty($conf->global->MAILING_EMAIL_ERRORSTO)?$conf->global->MAILING_EMAIL_ERRORSTO:$conf->global->MAIN_MAIL_ERRORS_TO).'"></td></tr>';
	print '</table>';
	print '</br><br>';

	print '<table class="border" width="100%">';
	print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("MailTopic").'</td><td><input class="flat" name="sujet" size="60" value="'.$_POST['sujet'].'"></td></tr>';
	print '<tr><td width="25%">'.$langs->trans("BackgroundColorByDefault").'</td><td colspan="3">';
	$htmlother->select_color($_POST['bgcolor'],'bgcolor','new_mailing',0);
	print '</td></tr>';
	print '<tr><td width="25%" class="fieldrequired" valign="top">'.$langs->trans("MailMessage").'<br>';
	print '<br><i>'.$langs->trans("CommonSubstitutions").':<br>';
	foreach($substitutionarray as $key => $val)
	{
		print $key.' = '.$langs->trans($val).'<br>';
	}
	print '</i></td>';
	print '<td>';
	// Editeur wysiwyg
	require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
	$doleditor=new DolEditor('body',$_POST['body'],'',320,'dolibarr_mailings','',true,true,$conf->global->FCKEDITOR_ENABLE_MAILING,20,70);
	$doleditor->Create();
	print '</td></tr>';
	print '</table>';

	print '<br><center><input type="submit" class="button" value="'.$langs->trans("CreateMailing").'"></center>';

	print '</form>';
}
else
{
	if ($object->fetch($id) >= 0)
	{
		$upload_dir = $conf->mailing->dir_output . "/" . get_exdir($object->id,2,0,1);

		$head = emailing_prepare_head($object);

		dol_fiche_head($head, 'card', $langs->trans("Mailing"), 0, 'email');

		dol_htmloutput_mesg($message);

		// Confirmation de la validation du mailing
		if ($action == 'valid')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("ValidMailing"),$langs->trans("ConfirmValidMailing"),"confirm_valid",'','',1);
			if ($ret == 'html') print '<br>';
		}

		// Confirm reset
		else if ($action == 'reset')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("ResetMailing"),$langs->trans("ConfirmResetMailing",$object->ref),"confirm_reset",'','',2);
			if ($ret == 'html') print '<br>';
		}

		// Confirm delete
		else if ($action == 'delete')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$object->id,$langs->trans("DeleteAMailing"),$langs->trans("ConfirmDeleteMailing"),"confirm_delete",'','',1);
			if ($ret == 'html') print '<br>';
		}

		else if ($action != 'edit')
		{
			/*
			 * Mailing en mode visu
			 */
			if ($action == 'sendall')
			{
                // Define message to recommand from command line

			    // Pour des raisons de securite, on ne permet pas cette fonction via l'IHM,
                // on affiche donc juste un message

				if (empty($conf->global->MAILING_LIMIT_SENDBYWEB))
				{
					// Pour des raisons de securite, on ne permet pas cette fonction via l'IHM,
					// on affiche donc juste un message
				    $mesg.='<div class="warning">'.$langs->trans("MailingNeedCommand").'</div>';
					$mesg.='<br><textarea cols="60" rows="'.ROWS_2.'" wrap="soft">php ./scripts/emailings/mailing-send.php '.$object->id.'</textarea>';
					$mesg.='<br><br><div class="warning">'.$langs->trans("MailingNeedCommand2").'</div>';
					$_GET["action"]='';
				}
				else
				{
					$text='';
                    if ($conf->file->mailing_limit_sendbyweb == 0)
                    {
                    	$text.=$langs->trans("MailingNeedCommand");
                    	$text.='<br><textarea cols="60" rows="'.ROWS_2.'" wrap="soft">php ./scripts/emailings/mailing-send.php '.$object->id.'</textarea>';
                    	$text.='<br><br>';
                    }
				    $text.=$langs->trans('ConfirmSendingEmailing').'<br>';
					$text.=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
					$ret=$form->form_confirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('SendMailing'),$text,'sendallconfirmed',$formquestion,'',1,260);
					if ($ret == 'html') print '<br>';
				}
			}

			print '<table class="border" width="100%">';

			print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">';
			print $form->showrefnav($object,'id');
			print '</td></tr>';

			// Description
			print '<tr><td>'.$form->editfieldkey("MailTitle",'desc',$object->titre,$object,$user->rights->mailing->creer).'</td><td colspan="3">';
			print $form->editfieldval("MailTitle",'desc',$object->titre,$object,$user->rights->mailing->creer);
			print '</td></tr>';

			// From
			print '<tr><td>'.$form->editfieldkey("MailFrom",'from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'email').'</td><td colspan="3">';
			print $form->editfieldval("MailFrom",'from',$object->email_from,$object,$user->rights->mailing->creer && $object->statut < 3,'email');
			print '</td></tr>';

			// Errors to
			print '<tr><td>'.$form->editfieldkey("MailErrorsTo",'errorsto',$object->email_errorsto,$object,$user->rights->mailing->creer && $object->statut < 3,'email').'</td><td colspan="3">';
			print $form->editfieldval("MailErrorsTo",'errorsto',$object->email_errorsto,$object,$user->rights->mailing->creer && $object->statut < 3,'email');
			print '</td></tr>';

			// Status
			print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

			// Nb of distinct emails
			print '<tr><td width="25%">';
			print $langs->trans("TotalNbOfDistinctRecipients");
			print '</td><td colspan="3">';
			$nbemail = ($object->nbemail?$object->nbemail:img_warning('').' <font class="warning">'.$langs->trans("NoTargetYet").'</font>');
			if (!empty($conf->global->MAILING_LIMIT_SENDBYWEB) && is_numeric($nbemail) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
			{
				if ($conf->global->MAILING_LIMIT_SENDBYWEB > 0)
				{
					$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
					print $form->textwithpicto($nbemail,$text,1,'warning');
				}
				else
				{
					$text=$langs->trans('NotEnoughPermissions');
					print $form->textwithpicto($nbemail,$text,1,'warning');
				}

			}
			else
			{
				print $nbemail;
			}
			print '</td></tr>';

			print '</table>';

			print "</div>";


			// Clone confirmation
			if ($action == 'clone')
			{
				// Create an array for form
				$formquestion=array(
					'text' => $langs->trans("ConfirmClone"),
				array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneContent"),   'value' => 1),
				array('type' => 'checkbox', 'name' => 'clone_receivers', 'label' => $langs->trans("CloneReceivers").' ('.$langs->trans("FeatureNotYetAvailable").')', 'value' => 0, 'disabled' => true)
				);
				// Paiement incomplet. On demande si motif = escompte ou autre
				$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneEMailing'),$langs->trans('ConfirmCloneEMailing',$object->ref),'confirm_clone',$formquestion,'yes');
				print '<br>';
			}


			if ($mesg) print $mesg;


			/*
			 * Boutons d'action
			 */

			if (GETPOST("cancel") || $confirm=='no' || $action == '' || in_array($action,array('valid','delete','sendall')))
			{
				print "\n\n<div class=\"tabsAction\">\n";

				if ($object->statut == 0 && $user->rights->mailing->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("EditMailing").'</a>';
				}

				//print '<a class="butAction" href="fiche.php?action=test&amp;id='.$object->id.'">'.$langs->trans("PreviewMailing").'</a>';

				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=test&amp;id='.$object->id.'">'.$langs->trans("TestMailing").'</a>';

				if ($object->statut == 0)
				{
					if ($object->nbemail <= 0)
					{
						print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NoTargetYet")).'">'.$langs->trans("ValidMailing").'</a>';
					}
					else if (empty($user->rights->mailing->valider))
					{
						print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("ValidMailing").'</a>';
					}
					else
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=valid&amp;id='.$object->id.'">'.$langs->trans("ValidMailing").'</a>';
					}
				}

				if (($object->statut == 1 || $object->statut == 2) && $object->nbemail > 0 && $user->rights->mailing->valider)
				{
					if ($conf->global->MAILING_LIMIT_SENDBYWEB < 0)
					{
						print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendMailing").'</a>';
					}
					else
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=sendall&amp;id='.$object->id.'">'.$langs->trans("SendMailing").'</a>';
					}
				}

				if ($user->rights->mailing->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=clone&amp;object=emailing&amp;id='.$object->id.'">'.$langs->trans("ToClone").'</a>';
				}

				if (($object->statut == 2 || $object->statut == 3) && $user->rights->mailing->valider)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=reset&amp;id='.$object->id.'">'.$langs->trans("ResetMailing").'</a>';
				}

				if (($object->statut <= 1 && $user->rights->mailing->creer) || $user->rights->mailing->supprimer)
				{
					print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("DeleteMailing").'</a>';
				}

				print '<br><br></div>';
			}

			// Affichage formulaire de TEST
			if ($action == 'test')
			{
				print_titre($langs->trans("TestMailing"));

				// Create l'objet formulaire mail
				include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->fromname = $object->email_from;
				$formmail->frommail = $object->email_from;
				$formmail->withsubstit=1;
				$formmail->withfrom=0;
				$formmail->withto=$user->email?$user->email:1;
				$formmail->withtocc=0;
				$formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
				$formmail->withtopic=0;
				$formmail->withtopicreadonly=1;
				$formmail->withfile=0;
				$formmail->withbody=0;
				$formmail->withbodyreadonly=1;
				$formmail->withcancel=1;
				$formmail->withdeliveryreceipt=0;
				// Tableau des substitutions
				$formmail->substit=$substitutionarrayfortest;
				// Tableau des parametres complementaires du post
				$formmail->param["action"]="send";
				$formmail->param["models"]="body";
				$formmail->param["mailid"]=$object->id;
				$formmail->param["returnurl"]=$_SERVER['PHP_SELF']."?id=".$object->id;

				$formmail->show_form();

				print '<br>';
			}

			// Print mail content
			print_fiche_titre($langs->trans("EMail"),'','');
			print '<table class="border" width="100%">';

			// Subject
			print '<tr><td width="25%">'.$langs->trans("MailTopic").'</td><td colspan="3">'.$object->sujet.'</td></tr>';

			// Joined files
			print '<tr><td>'.$langs->trans("MailFile").'</td><td colspan="3">';
			// List of files
			$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
			if (count($listofpaths))
			{
				foreach($listofpaths as $key => $val)
				{
					print img_mime($listofpaths[$key]['name']).' '.$listofpaths[$key]['name'];
					print '<br>';
				}
			}
			else
			{
				print $langs->trans("NoAttachedFiles").'<br>';
			}
			print '</td></tr>';

            // Background color
            /*print '<tr><td width="25%">'.$langs->trans("BackgroundColorByDefault").'</td><td colspan="3">';
            $htmlother->select_color($object->bgcolor,'bgcolor','edit_mailing',0);
            print '</td></tr>';*/

		    // Message
			print '<tr><td valign="top">'.$langs->trans("MailMessage").'</td>';
			print '<td colspan="3" bgcolor="'.($object->bgcolor?(preg_match('/^#/',$object->bgcolor)?'':'#').$object->bgcolor:'white').'">';
			print dol_htmlentitiesbr($object->body);
			print '</td>';
			print '</tr>';

			print '</table>';
			print "<br>";
		}
		else
		{
			/*
			 * Mailing en mode edition
			 */

			if ($mesg) print $mesg."<br>";
			if ($message) print $message."<br>";

			print '<table class="border" width="100%">';

			print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">'.$object->id.'</td></tr>';
			print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$object->titre.'</td></tr>';
			print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.dol_print_email($object->email_from,0,0,0,0,1).'</td></tr>';
			print '<tr><td width="25%">'.$langs->trans("MailErrorsTo").'</td><td colspan="3">'.dol_print_email($object->email_errorsto,0,0,0,0,1).'</td></tr>';

			// Status
			print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

			// Nb of distinct emails
			print '<tr><td width="25%">';
			print $langs->trans("TotalNbOfDistinctRecipients");
			print '</td><td colspan="3">';
			$nbemail = ($object->nbemail?$object->nbemail:'<font class="error">'.$langs->trans("NoTargetYet").'</font>');
			if (!empty($conf->global->MAILING_LIMIT_SENDBYWEB) && is_numeric($nbemail) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
			{
				$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
				print $form->textwithpicto($nbemail,$text,1,'warning');
			}
			else
			{
				print $nbemail;
			}
			print '</td></tr>';

			print '</table>';
			print "</div>";

			print "\n";
			print '<form name="edit_mailing" action="fiche.php" method="post" enctype="multipart/form-data">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			// Print mail content
			print_fiche_titre($langs->trans("EMail"),'','');
			print '<table class="border" width="100%">';

			// Subject
			print '<tr><td width="25%" class="fieldrequired">'.$langs->trans("MailTopic").'</td><td colspan="3"><input class="flat" type="text" size=60 name="sujet" value="'.$object->sujet.'"></td></tr>';

			dol_init_file_process($upload_dir);

			// Joined files
			$addfileaction='addfile';
			print '<tr><td>'.$langs->trans("MailFile").'</td>';
			print '<td colspan="3">';
			// List of files
			$listofpaths=dol_dir_list($upload_dir,'all',0,'','','name',SORT_ASC,0);
			// TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
			$out.= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
			$out.= '<script type="text/javascript" language="javascript">';
			$out.= 'jQuery(document).ready(function () {';
			$out.= '    jQuery(".removedfile").click(function() {';
			$out.= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
			$out.= '    });';
			$out.= '})';
			$out.= '</script>'."\n";
			if (count($listofpaths))
			{
				foreach($listofpaths as $key => $val)
				{
					$out.= '<div id="attachfile_'.$key.'">';
					$out.= img_mime($listofpaths[$key]['name']).' '.$listofpaths[$key]['name'];
					$out.= ' <input type="image" style="border: 0px;" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/delete.png" value="'.($key+1).'" class="removedfile" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
					$out.= '<br></div>';
				}
			}
			else
			{
				$out.= $langs->trans("NoAttachedFiles").'<br>';
			}
			// Add link to add file
			$out.= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
			$out.= ' ';
			$out.= '<input type="submit" class="button" id="'.$addfileaction.'" name="'.$addfileaction.'" value="'.$langs->trans("MailingAddFile").'" />';
			print $out;
			print '</td></tr>';

		    // Background color
			print '<tr><td width="25%">'.$langs->trans("BackgroundColorByDefault").'</td><td colspan="3">';
			$htmlother->select_color($object->bgcolor,'bgcolor','edit_mailing',0);
			print '</td></tr>';

			// Message
			print '<tr><td width="25%" valign="top">'.$langs->trans("MailMessage").'<br>';
			print '<br><i>'.$langs->trans("CommonSubstitutions").':<br>';
			print '__ID__ = '.$langs->trans("IdRecord").'<br>';
			print '__EMAIL__ = '.$langs->trans("EMail").'<br>';
			print '__CHECK_READ__ = '.$langs->trans("CheckRead").'<br>';
			print '__UNSUSCRIBE__ = '.$langs->trans("MailUnsubcribe").'<br>';
			print '__LASTNAME__ = '.$langs->trans("Lastname").'<br>';
			print '__FIRSTNAME__ = '.$langs->trans("Firstname").'<br>';
			print '__OTHER1__ = '.$langs->trans("Other").'1<br>';
			print '__OTHER2__ = '.$langs->trans("Other").'2<br>';
			print '__OTHER3__ = '.$langs->trans("Other").'3<br>';
			print '__OTHER4__ = '.$langs->trans("Other").'4<br>';
			print '__OTHER5__ = '.$langs->trans("Other").'5<br>';
			print '</i></td>';
			print '<td colspan="3">';
			// Editeur wysiwyg
			require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
			$doleditor=new DolEditor('body',$object->body,'',320,'dolibarr_mailings','',true,true,$conf->global->FCKEDITOR_ENABLE_MAILING,20,70);
			$doleditor->Create();
			print '</td></tr>';

			print '<tr><td colspan="4" align="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'" name="save">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" value="'.$langs->trans("Cancel").'" name="cancel">';
			print '</td></tr>';

			print '</table>';

			print '</form>';
			print '<br>';
		}
	}
	else
	{
		dol_print_error($db,$object->error);
	}
}


llxFooter();
$db->close();
?>
