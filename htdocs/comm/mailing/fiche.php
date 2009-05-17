<?PHP
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@uers.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/comm/mailing/fiche.php
 *       \ingroup    mailing
 *       \brief      Fiche mailing, onglet general
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/mailing.class.php';

$langs->load("mails");

if (! $user->rights->mailing->lire || $user->societe_id > 0)
accessforbidden();

$message = '';

// Tableau des substitutions possibles
$substitutionarray=array(
'__ID__' => 'IdRecord',
'__EMAIL__' => 'EMail',
'__LASTNAME__' => 'Lastname',
'__FIRSTNAME__' => 'Firstname',
'__OTHER1__' => 'Other1',
'__OTHER2__' => 'Other2',
'__OTHER3__' => 'Other3',
'__OTHER4__' => 'Other4',
'__OTHER5__' => 'Other5'
);
$substitutionarrayfortest=array(
'__ID__' => 'TESTIdRecord',
'__EMAIL__' => 'TESTEMail',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname',
'__OTHER1__' => 'TESTOther1',
'__OTHER2__' => 'TESTOther2',
'__OTHER3__' => 'TESTOther3',
'__OTHER4__' => 'TESTOther4',
'__OTHER5__' => 'TESTOther5'
);



// Action clone object
if ($_POST["action"] == 'confirm_clone' && $_POST['confirm'] == 'yes')
{
	if (empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$object=new Mailing($db);
		$result=$object->createFromClone($_REQUEST['id'],$_REQUEST["clone_content"],$_REQUEST["clone_receivers"]);
		if ($result > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
			exit;
		}
		else
		{
			$mesg=$object->error;
			$_GET['action']='';
			$_GET['id']=$_REQUEST['id'];
		}
	}
}

// Action envoi mailing pour tous
if ($_POST["action"] == 'sendallconfirmed' && $_POST['confirm'] == 'yes')
{
	if (empty($conf->global->MAILING_LIMIT_SENDBYWEB))
	{
		// Pour des raisons de securite, on ne permet pas cette fonction via l'IHM,
		// on affiche donc juste un message
		$message='<div class="warning">'.$langs->trans("MailingNeedCommand").'</div>';
		$message.='<br><textarea cols="70" rows="'.ROWS_2.'" wrap="soft">php ./scripts/mailing/mailing-send.php '.$_GET["id"].'</textarea>';
		$message.='<br><br><div class="warning">'.$langs->trans("MailingNeedCommand2").'</div>';
		$_GET["action"]='';
	}
	else
	{
		$mil=new Mailing($db);
		$result=$mil->fetch($_GET['id']);

		if ($mil->statut == 0)
		{
			dol_print_error('','ErrorMailIsNotValidated');
			exit;
		}

		$id       = $mil->id;
		$subject  = $mil->sujet;
		$message  = $mil->body;
		$from     = $mil->email_from;
		$errorsto = $mil->email_errorsto;
		// Le message est-il en html
		$msgishtml=-1;	// Unknown by default
		if (eregi('[ \t]*<html>',$message)) $msgishtml=1;

		// Warning, we must not use begin-commit transaction here
		// because we want to save update for each mail sent.

		$nbok=0; $nbko=0;

		// On choisit les mails non deja envoyes pour ce mailing (statut=0)
		// ou envoyes en erreur (statut=-1)
		$sql = "SELECT mc.rowid, mc.nom, mc.prenom, mc.email, mc.other";
		$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
		$sql .= " WHERE mc.statut < 1 AND mc.fk_mailing = ".$id;

		dol_syslog("fiche.php: select targets sql=".$sql, LOG_DEBUG);
		$resql=$db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);	// nb of possible recipients

			if ($num)
			{
				dol_syslog("fiche.php: nb of targets = ".$num, LOG_DEBUG);

				// Positionne date debut envoi
				$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET date_envoi=".$db->idate(gmmktime())." WHERE rowid=".$id;
				$resql2=$db->query($sql);
				if (! $resql2)
				{
					dol_print_error($db);
				}

				// Boucle sur chaque adresse et envoie le mail
				$i = 0;

				while ($i < $num && $i < $conf->global->MAILING_LIMIT_SENDBYWEB)
				{

					$res=1;

					$obj = $db->fetch_object($resql);

					// sendto en RFC2822
					$sendto = eregi_replace(',',' ',$obj->prenom." ".$obj->nom)." <".$obj->email.">";

					// Make subtsitutions on topic and body
					$other=split(';',$obj->other);
					$other1=$other[0];
					$other2=$other[1];
					$other3=$other[2];
					$other4=$other[3];
					$other5=$other[4];
					$substitutionarray=array(
						'__ID__' => $obj->rowid,
						'__EMAIL__' => $obj->email,
						'__LASTNAME__' => $obj->nom,
						'__FIRSTNAME__' => $obj->prenom,
						'__OTHER1__' => $other1,
						'__OTHER2__' => $other2,
						'__OTHER3__' => $other3,
						'__OTHER4__' => $other4,
						'__OTHER5__' => $other5
					);

					$substitutionisok=true;
					$newsubject=make_substitutions($subject,$substitutionarray);
					$newmessage=make_substitutions($message,$substitutionarray);

					// Fabrication du mail
					$mail = new CMailFile($newsubject, $sendto, $from, $newmessage,
					array(), array(), array(),
		            						'', '', 0, $msgishtml);
					$mail->errors_to = $errorsto;


					if ($mail->error)
					{
						$res=0;
					}
					if (! $substitutionisok)
					{
						$mail->error='Some substitution failed';
						$res=0;
					}

					// Envoi du mail
					if ($res)
					{
						$res=$mail->sendfile();
					}

					if ($res)
					{
						// Mail envoye avec succes
						$nbok++;

						dol_syslog("mailing-send: ok for #".$i.($mail->error?' - '.$mail->error:''), LOG_DEBUG);

						$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
						$sql.=" SET statut=1, date_envoi=".$db->idate(gmmktime())." WHERE rowid=".$obj->rowid;
						$resql2=$db->query($sql);
						if (! $resql2)
						{
							dol_print_error($db);
						}
					}
					else
					{
						// Mail en echec
						$nbko++;

						dol_syslog("mailing-send: error for #".$i.($mail->error?' - '.$mail->error:''), LOG_DEBUG);

						$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
						$sql.=" SET statut=-1, date_envoi=".$db->idate(gmmktime())." WHERE rowid=".$obj->rowid;
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

			$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$statut." WHERE rowid=".$id;
			dol_syslog("mailing-send: update global status sql=".$sql, LOG_DEBUG);
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
		$_GET["action"] = '';

	}
}

// Action envoi test mailing
if ($_POST["action"] == 'send' && ! $_POST["cancel"])
{
	$mil = new Mailing($db);
	$result=$mil->fetch($_POST["mailid"]);

	$mil->sendto       = $_POST["sendto"];
	if (! $mil->sendto)
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("MailTo")).'</div>';
	}
	if ($mil->sendto)
	{
		$arr_file = array();
		$arr_mime = array();
		$arr_name = array();

		// Le message est-il en html
		$msgishtml=-1;	// Inconnu par defaut
		if (eregi('[ \t]*<html>',$message)) $msgishtml=1;

		// Pratique les substitutions sur le sujet et message
		$mil->sujet=make_substitutions($mil->sujet,$substitutionarrayfortest);
		$mil->body=make_substitutions($mil->body,$substitutionarrayfortest);

		$mailfile = new CMailFile($mil->sujet,$mil->sendto,$mil->email_from,$mil->body,
		$arr_file,$arr_mime,$arr_name,
        							'', '', 0, $msgishtml);

		$result=$mailfile->sendfile();
		if ($result)
		{
			$message='<div class="ok">'.$langs->trans("MailSuccessfulySent",$mil->email_from,$mil->sendto).'</div>';
		}
		else
		{
			$message='<div class="error">'.$langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result.'</div>';
		}

		$_GET["action"]='';
		$_GET["id"]=$mil->id;
	}
}

// Action ajout mailing
if ($_POST["action"] == 'add')
{
	$message='';

	$mil = new Mailing($db);

	$mil->email_from   = trim($_POST["from"]);
	$mil->titre        = trim($_POST["titre"]);
	$mil->sujet        = trim($_POST["sujet"]);
	$mil->body         = trim($_POST["body"]);

	if (! $mil->titre) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailTitle"));
	if (! $mil->sujet) $message.=($message?'<br>':'').$langs->trans("ErrorFieldRequired",$langs->trans("MailTopic"));

	if (! $message)
	{
		if ($mil->create($user) >= 0)
		{
			Header("Location: fiche.php?id=".$mil->id);
			exit;
		}
		$message=$mil->error;
	}

	$message='<div class="error">'.$message.'</div>';
	$_GET["action"]="create";
}

// Action mise a jour mailing
if ($_POST["action"] == 'update')
{
	$mil = new Mailing($db);

	$mil->id           = $_POST["id"];
	$mil->email_from   = $_POST["from"];
	$mil->titre        = $_POST["titre"];
	$mil->sujet        = $_POST["sujet"];
	$mil->body         = $_POST["body"];

	if ($mil->update())
	{
		Header("Location: fiche.php?id=".$mil->id);
	}
}

// Action confirmation validation
if ($_POST["action"] == 'confirm_valide')
{

	if ($_POST["confirm"] == 'yes')
	{
		$mil = new Mailing($db);

		if ($mil->fetch($_GET["id"]) >= 0)
		{
			$mil->valid($user);

			Header("Location: fiche.php?id=".$mil->id);
			exit;
		}
		else
		{
			dol_print_error($db);
		}
	}
	else
	{
		Header("Location: fiche.php?id=".$_GET["id"]);
		exit;
	}
}

// Resend
if ($_POST["action"] == 'confirm_reset')
{
	if ($_POST["confirm"] == 'yes')
	{
		$mil = new Mailing($db);

		if ($mil->fetch($_GET["id"]) >= 0)
		{
			$db->begin();

			$result=$mil->valid($user);
			if ($result > 0)
			{
				$result=$mil->reset_targets_status($user);
			}

			if ($result > 0)
			{
				$db->commit();
				Header("Location: fiche.php?id=".$mil->id);
				exit;
			}
			else
			{
				$mesg=$mil->error;
				$db->rollback();
			}
		}
		else
		{
			dol_print_error($db);
		}
	}
	else
	{
		Header("Location: fiche.php?id=".$_GET["id"]);
		exit;
	}
}

// Action confirmation suppression
if ($_POST["action"] == 'confirm_delete')
{
	if ($_POST["confirm"] == 'yes')
	{
		$mil = new Mailing($db);
		$mil->id = $_GET["id"];

		if ($mil->delete($mil->id))
		{
			Header("Location: index.php");
		}
	}
}

if ($_POST["cancel"] == $langs->trans("Cancel"))
{
	$action = '';
}



/*
 * View
 */

llxHeader("","","Fiche Mailing");

$html = new Form($db);

$mil = new Mailing($db);


if ($_GET["action"] == 'create')
{
	// EMailing in creation mode
	print '<form action="fiche.php" method="post">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	print_fiche_titre($langs->trans("NewMailing"));

	if ($message) print "$message<br>";

	print '<table class="border" width="100%">';

	print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td><input class="flat" name="titre" size="40" value=""></td></tr>';
	print '<tr><td colspan="2">&nbsp;</td></tr>';
	print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td><input class="flat" name="from" size="40" value="'.$conf->global->MAILING_EMAIL_FROM.'"></td></tr>';
	print '<tr><td width="25%">'.$langs->trans("MailTopic").'</td><td><input class="flat" name="sujet" size="60" value=""></td></tr>';
	print '<tr><td width="25%" valign="top">'.$langs->trans("MailMessage").'<br>';
	print '<br><i>'.$langs->trans("CommonSubstitutions").':<br>';
	foreach($substitutionarray as $key => $val)
	{
		print $key.' = '.$langs->trans($val).'<br>';
	}
	print '</i></td>';
	print '<td>';
	// Editeur wysiwyg
	if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_MAILING)
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('body','',320,'dolibarr_mailings','',true,true);
		$doleditor->Create();
	}
	else
	{
		print '<textarea cols="70" rows="20" name="body"></textarea>';
	}
	print '</td></tr>';
	print '<tr><td colspan="2" align="center"><input type="submit" class="button" value="'.$langs->trans("CreateMailing").'"></td></tr>';
	print '</table>';
	print '</form>';
}
else
{
	if ($mil->fetch($_GET["id"]) >= 0)
	{
		$h=0;
		$head[$h][0] = DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;
		$head[$h][1] = $langs->trans("MailCard");
		$hselected = $h;
		$h++;

		$head[$h][0] = DOL_URL_ROOT."/comm/mailing/cibles.php?id=".$mil->id;
		$head[$h][1] = $langs->trans('MailRecipients');
		$h++;

		/*
		 $head[$h][0] = DOL_URL_ROOT."/comm/mailing/info.php?id=".$mil->id;
		 $head[$h][1] = $langs->trans("MailHistory");
		 $h++;
		 */
		dol_fiche_head($head, $hselected, $langs->trans("Mailing"));

		// Confirmation de la validation du mailing
		if ($_GET["action"] == 'valide')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$mil->id,$langs->trans("ValidMailing"),$langs->trans("ConfirmValidMailing"),"confirm_valide");
			if ($ret == 'html') print '<br>';
		}

		// Confirm reset
		if ($_GET["action"] == 'reset')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$mil->id,$langs->trans("ResetMailing"),$langs->trans("ConfirmResetMailing",$mil->ref),"confirm_reset");
			if ($ret == 'html') print '<br>';
		}

		// Confirm delete
		if ($_GET["action"] == 'delete')
		{
			$ret=$html->form_confirm($_SERVER["PHP_SELF"]."?id=".$mil->id,$langs->trans("DeleteAMailing"),$langs->trans("ConfirmDeleteMailing"),"confirm_delete");
			if ($ret == 'html') print '<br>';
		}


		if ($_GET["action"] != 'edit')
		{
			/*
			 * Mailing en mode visu
			 *
			 */
			if ($_GET["action"] == 'sendall')
			{
				if (empty($conf->global->MAILING_LIMIT_SENDBYWEB))
				{
					// Pour des raisons de securite, on ne permet pas cette fonction via l'IHM,
					// on affiche donc juste un message
					$message='<div class="warning">'.$langs->trans("MailingNeedCommand").'</div>';
					$message.='<br><textarea cols="50" rows="'.ROWS_2.'" wrap="soft">php ./scripts/mailing/mailing-send.php '.$_GET["id"].'</textarea>';
					$message.='<br><br><div class="warning">'.$langs->trans("MailingNeedCommand2").'</div>';
					$_GET["action"]='';
				}
				else
				{
					$text=$langs->trans('ConfirmSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
					$ret=$html->form_confirm($_SERVER['PHP_SELF'].'?id='.$_REQUEST['id'],$langs->trans('SendMailing'),$text,'sendallconfirmed');
					if ($ret == 'html') print '<br>';
				}
			}

			print '<table class="border" width="100%">';

			print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">';
			print $html->showrefnav($mil,'id');
			print '</td></tr>';

			print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$mil->titre.'</td></tr>';
			print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.htmlentities($mil->email_from).'</td></tr>';
			print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$mil->getLibStatut(4).'</td></tr>';
			print '<tr><td width="25%">'.$langs->trans("TotalNbOfDistinctRecipients").'</td><td colspan="3">'.($mil->nbemail?$mil->nbemail:'<font class="error">'.$langs->trans("NoTargetYet").'</font>').'</td></tr>';

			$uc = new User($db, $mil->user_creat);
			$uc->fetch();
			print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>'.$uc->getNomUrl(1).'</td>';
			print '<td>'.$langs->trans("Date").'</td>';
			print '<td>'.dol_print_date($mil->date_creat,"dayhour").'</td></tr>';

			if ($mil->statut > 0)
			{
				$uv = new User($db, $mil->user_valid);
				$uv->fetch();
				print '<tr><td>'.$langs->trans("ValidatedBy").'</td><td>'.$uv->getNomUrl(1).'</td>';
				print '<td>'.$langs->trans("Date").'</td>';
				print '<td>'.dol_print_date($mil->date_valid,"dayhour").'</td></tr>';
			}

			if ($mil->statut > 1)
			{
				print '<tr><td>'.$langs->trans("SentBy").'</td><td>'.$langs->trans("Unknown").'</td>';
				print '<td>'.$langs->trans("Date").'</td>';
				print '<td>'.dol_print_date($mil->date_envoi,"dayhour").'</td></tr>';
			}

			// Sujet
			print '<tr><td>'.$langs->trans("MailTopic").'</td><td colspan="3">'.$mil->sujet.'</td></tr>';

			// Message
			print '<tr><td valign="top">'.$langs->trans("MailMessage").'</td>';
			print '<td colspan="3">';
			print dol_htmlentitiesbr($mil->body);
			print '</td>';
			print '</tr>';

			print '</table>';

			print "</div>";


			// Clone confirmation
			if ($_GET["action"] == 'clone')
			{
				// Create an array for form
				$formquestion=array(
					'text' => $langs->trans("ConfirmClone"),
				array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneContent"),   'value' => 1),
				array('type' => 'checkbox', 'name' => 'clone_receivers', 'label' => $langs->trans("CloneReceivers").' ('.$langs->trans("FeatureNotYetAvailable").')', 'value' => 0, 'disabled' => true)
				);
				// Paiement incomplet. On demande si motif = escompte ou autre
				$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$mil->id,$langs->trans('CloneEMailing'),$langs->trans('ConfirmCloneEMailing',$mil->ref),'confirm_clone',$formquestion,'yes');
			}


			if ($mesg) print $mesg;


			if ($_GET["action"] == 'sendall')
			{
				// Pour des raisons de securite, on ne permet pas cette fonction via l'IHM,
				// on affiche donc juste un message
				$message='<div class="warning">'.$langs->trans("MailingNeedCommand").'</div>';
				$message.='<br><textarea cols="70" rows="'.ROWS_2.'" wrap="soft">php ./scripts/mailing/mailing-send.php '.$_GET["id"].'</textarea>';
			}

			if ($message) print "$message<br>";

			/*
			 * Boutons d'action
			 */
			if ($_GET["action"] == '')
			{
				print "\n\n<div class=\"tabsAction\">\n";

				if ($mil->statut == 0 && $user->rights->mailing->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&amp;id='.$mil->id.'">'.$langs->trans("EditMailing").'</a>';
				}

				//print '<a class="butAction" href="fiche.php?action=test&amp;id='.$mil->id.'">'.$langs->trans("PreviewMailing").'</a>';

				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=test&amp;id='.$mil->id.'">'.$langs->trans("TestMailing").'</a>';

				if ($mil->statut == 0 && $mil->nbemail > 0 && $user->rights->mailing->valider)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=valide&amp;id='.$mil->id.'">'.$langs->trans("ValidMailing").'</a>';
				}

				if (($mil->statut == 1 || $mil->statut == 2) && $mil->nbemail > 0 && $user->rights->mailing->valider)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=sendall&amp;id='.$mil->id.'">'.$langs->trans("SendMailing").'</a>';
				}

				if ($user->rights->mailing->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=clone&amp;object=emailing&amp;id='.$mil->id.'">'.$langs->trans("ToClone").'</a>';
				}

				if (($mil->statut == 2 || $mil->statut == 3) && $user->rights->mailing->valider)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=reset&amp;id='.$mil->id.'">'.$langs->trans("ResetMailing").'</a>';
				}

				if (($mil->statut <= 1 && $user->rights->mailing->creer) || $user->rights->mailing->supprimer)
				{
					print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$mil->id.'">'.$langs->trans("DeleteMailing").'</a>';
				}

				print '<br /><br /></div>';
			}

			// Affichage formulaire de TEST
			if ($_GET["action"] == 'test')
			{
				print_titre($langs->trans("TestMailing"));

				// Create l'objet formulaire mail
				include_once("../../html.formmail.class.php");
				$formmail = new FormMail($db);
				$formmail->fromname = $mil->email_from;
				$formmail->frommail = $mil->email_from;
				$formmail->withsubstit=1;
				$formmail->withfrom=0;
				$formmail->withto=$user->email?$user->email:1;
				$formmail->withtocc=0;
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
				$formmail->param["mailid"]=$mil->id;
				$formmail->param["returnurl"]=DOL_URL_ROOT."/comm/mailing/fiche.php?id=".$mil->id;

				// Init list of files
				if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
				{
					$formmail->clear_attached_files();
				}

				$formmail->show_form();

				print '<br>';
			}

		}
		else
		{
			/*
			 * Mailing en mode edition
			 */
			print '<form action="fiche.php" method="post">'."\n";
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$mil->id.'">';
			print '<table class="border" width="100%">';

			print '<tr><td width="25%">'.$langs->trans("Ref").'</td><td colspan="3">'.$mil->id.'</td></tr>';
			print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3"><input class="flat" type="text" size=40 name="titre" value="'.$mil->titre.'"></td></tr>';
			print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3"><input class="flat" type="text" size=40 name="from" value="'.$mil->email_from.'"></td></tr>';
			print '<tr><td width="25%">'.$langs->trans("MailTopic").'</td><td colspan="3"><input class="flat" type="text" size=60 name="sujet" value="'.$mil->sujet.'"></td></tr>';
			print '<tr><td width="25%" valign="top">'.$langs->trans("MailMessage").'<br>';
			print '<br><i>'.$langs->trans("CommonSubstitutions").':<br>';
			print '__ID__ = '.$langs->trans("IdRecord").'<br>';
			print '__EMAIL__ = '.$langs->trans("EMail").'<br>';
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
			if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_MAILING)
			{
				require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
				$doleditor=new DolEditor('body',$mil->body,320,'dolibarr_mailings','',true,true);
				$doleditor->Create();
			}
			else
			{
				print '<textarea class="flat" name="body" cols="70" rows="20">';
				print dol_htmlentitiesbr_decode($mil->body).'</textarea>';
			}
			print '</td></tr>';

			print '<tr><td colspan="4" align="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></td></tr>';
			print '</table>';
			print '</form>';

			print "</div>";
		}
	}

}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
