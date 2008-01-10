<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/admin/mails.php
        \brief      Page de configuration des emails
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("mails");

if (!$user->admin)
  accessforbidden();

$substitutionarrayfortest=array(	
'__ID__' => 'TESTIdRecord',
'__EMAIL__' => 'TESTEMail',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname'
);


/*
* Actions
*/

if (isset($_POST["action"]) && $_POST["action"] == 'update')
{
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",      $_POST["MAIN_MAIL_SMTP_PORT"]);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER",    $_POST["MAIN_MAIL_SMTP_SERVER"]);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",     $_POST["MAIN_MAIL_EMAIL_FROM"]);
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS",   $_POST["MAIN_DISABLE_ALL_MAILS"]);
		
	$_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer
	
	Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}

// Action envoi test mailing
if ($_POST["action"] == 'send' && ! $_POST["cancel"])
{
	$filepath = array();
	$mimetype = array();
	$filename = array();

	$email_from = $conf->global->MAIN_MAIL_EMAIL_FROM;
	$errors_to  = $_POST["errorstomail"];
	$sendto     = $_POST["sendto"];
	$subject    = $_POST['subject'];
	$body       = $_POST['message'];
	if ($_FILES['addedfile']['tmp_name'])
	{
		$filepath[0] = $_FILES['addedfile']['tmp_name'];
		$mimetype[0] = $_FILES['addedfile']['type'];
		$filename[0] = $_FILES['addedfile']['name'];
	}

	if (! $sendto)
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->trans("MailTo")).'</div>';
	}
    if ($sendto)
    {
		// Le message est-il en html
		$msgishtml=0;	// Non par defaut
		//if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_MAILING) $msgishtml=1;
		if (eregi('[ \t]*<html>',$message)) $msgishtml=1;						

        // Pratique les substitutions sur le sujet et message
		$subject=make_substitutions($subject,$substitutionarrayfortest);
		$body=make_substitutions($body,$substitutionarrayfortest);
		
        require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
		$mailfile = new CMailFile($subject,$sendto,$email_from,$body,
        							$filepath,$mimetype,$filename,
        							'', '', 0, $msgishtml,$errors_to);
        
		$result=$mailfile->sendfile();
        if ($result)
        {
            $message='<div class="ok">'.$langs->trans("MailSuccessfulySent",$email_from,$sendto).'</div>';
        }
        else
        {
            $message='<div class="error">'.$langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result.'</div>';
        }

        $_GET["action"]='';
    }
}


/*
* Affichage page
*/

llxHeader();

print_fiche_titre($langs->trans("EMailsSetup"),'','setup');

print $langs->trans("EMailsDesc")."<br>\n";
print "<br>\n";

if ($message) print $message.'<br>';


if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
	$html=new Form($db);

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT",ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined")).'</td><td><input class="flat" name="MAIN_MAIL_SMTP_PORT" size="3" value="' . $conf->global->MAIN_MAIL_SMTP_PORT . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER",ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined")).'</td><td><input class="flat" name="MAIN_MAIL_SMTP_SERVER" size="18" value="' . $conf->global->MAIN_MAIL_SMTP_SERVER . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td><td><input class="flat" name="MAIN_MAIL_EMAIL_FROM" size="24" value="' . $conf->global->MAIN_MAIL_EMAIL_FROM . '"></td></tr>';

    $var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>';
	print $html->selectyesno('MAIN_DISABLE_ALL_MAILS',$conf->global->MAIN_DISABLE_ALL_MAILS,1);
    print '</td></tr>';

    print '</table>';

    print '<br><center>';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</center>';

    print '</form>';
    print '<br>';
}
else
{
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT",ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_SMTP_PORT.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER",ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_SMTP_SERVER.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_EMAIL_FROM.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_MAILS).'</td></tr>';

    print '</table>';


	// Boutons actions
    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test">'.$langs->trans("DoTest").'</a>';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	
	
	// Affichage formulaire de TEST
	if ($_GET["action"] == 'test')
	{
			  print '<br>';
			  print_titre($langs->trans("TestMailing"));
			  
			  // Créé l'objet formulaire mail
			  include_once("../html.formmail.class.php");
			  $formmail = new FormMail($db);	    
			  $formmail->fromname = $conf->global->MAIN_MAIL_EMAIL_FROM;
			  $formmail->frommail = $conf->global->MAIN_MAIL_EMAIL_FROM;
			  $formmail->withsubstit=0;
			  $formmail->withfrom=1;
			  $formmail->witherrorsto=1;
			  $formmail->withto=$user->email?$user->email:1;
			  $formmail->withcc=0;
			  $formmail->withtopic=$langs->trans("Test");
			  $formmail->withtopicreadonly=0;
			  $formmail->withfile=1;
			  $formmail->withbody=$langs->trans("Test");
			  $formmail->withbodyreadonly=0;
			  $formmail->withcancel=1;
			  // Tableau des substitutions
			  $formmail->substit=$substitutionarrayfortest;
			  // Tableau des paramètres complémentaires du post
			  $formmail->param["action"]="send";
			  $formmail->param["models"]="body";
			  $formmail->param["mailid"]=$mil->id;
			  $formmail->param["returnurl"]=DOL_URL_ROOT."/admin/mails.php";
	
			  $formmail->show_form();
			  
			  print '<br>';
	}
	
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
