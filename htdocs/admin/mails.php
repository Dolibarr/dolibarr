<?php
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/admin/mails.php
        \brief      Page de configuration des emails
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

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
		
	Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * Add file
 */
if ($_POST['addfile'] || $_POST['addfilehtml'])
{
	// Set tmp user directory
	$conf->users->dir_tmp=DOL_DATA_ROOT."/users/".$user->id;
	$upload_dir = $conf->users->dir_tmp.'/temp/';
	
	if (! empty($_FILES['addedfile']['tmp_name']))
	{
	    if (! is_dir($upload_dir)) create_exdir($upload_dir);
	
	    if (is_dir($upload_dir))
	    {
	    	if (dol_move_uploaded_file($_FILES['addedfile']['tmp_name'], $upload_dir . "/" . $_FILES['addedfile']['name'],0) > 0)
	        {
	        	$message = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
	            //print_r($_FILES);

				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->add_attached_files($upload_dir . "/" . $_FILES['addedfile']['name'],$_FILES['addedfile']['name'],$_FILES['addedfile']['type']);
	        }
	        else
	        {
	            // Echec transfert (fichier dépassant la limite ?)
	            $message = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
	            // print_r($_FILES);
	        }
	    }
	}
	if ($_POST['addfile'])     $_GET["action"]='test';
	if ($_POST['addfilehtml']) $_GET["action"]='testhtml';
}

/*
 * Send mail
 */
if (($_POST['action'] == 'send' || $_POST['action'] == 'sendhtml')
	 && ! $_POST['addfile'] && ! $_POST['addfilehtml'] && ! $_POST['cancel'])
{
	$error=0;
	
	$email_from='';
	if (! empty($_POST["fromname"])) $email_from=$_POST["fromname"].' ';
	if (! empty($_POST["frommail"])) $email_from.='<'.$_POST["frommail"].'>';

	$errors_to  = $_POST["errorstomail"];
	$sendto     = $_POST["sendto"];
	$subject    = $_POST['subject'];
	$body       = $_POST['message'];

	// Create form object
	include_once('../html.formmail.class.php');
	$formmail = new FormMail($db);

	$attachedfiles=$formmail->get_attached_files();
    $filepath = $attachedfiles['paths'];
    $filename = $attachedfiles['names'];
    $mimetype = $attachedfiles['mimes'];
	
	if (empty($_POST["frommail"]))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailFrom")).'</div>';
		$_GET["action"]='test';
		$error++;
	}
	if (empty($sendto))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailTo")).'</div>';
		$_GET["action"]='test';
		$error++;
	}
    if (! $error)
    {
		// Le message est-il en html
		$msgishtml=0;	// Message is not HTML
		if ($_POST['action'] == 'sendhtml') $msgishtml=1;	// Force message to HTML 
		
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

$port=! empty($conf->global->MAIN_MAIL_SMTP_PORT)?$conf->global->MAIN_MAIL_SMTP_PORT:ini_get('smtp_port');
if (! $port) $port=25;
$server=! empty($conf->global->MAIN_MAIL_SMTP_SERVER)?$conf->global->MAIN_MAIL_SMTP_SERVER:ini_get('SMTP');
if (! $server) $server='127.0.0.1';


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

//    $var=!$var;
//    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_SERVER",ini_get('SMTPs')?ini_get('SMTPs'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_SMTPS_SERVER.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_EMAIL_FROM.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_MAILS).'</td></tr>';

    print '</table>';


	// Boutons actions
    print '<div class="tabsAction">';
	if (function_exists('fsockopen') && $port && $server)
	{
	    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect">'.$langs->trans("DoTestServerAvailability").'</a>';
	}
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&amp;mode=init">'.$langs->trans("DoTestSend").'</a>';
	if ($conf->fckeditor->enabled)
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testhtml&amp;mode=init">'.$langs->trans("DoTestSendHTML").'</a>';
	}
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	
	
	// Affichage formulaire de TEST
	if ($_GET["action"] == 'testconnect')
	{
			  print '<br>';
			  print_titre($langs->trans("DoTestServerAvailability"));
			  
			  // Cree l'objet formulaire mail
			  include_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
			  $mail = new CMailFile('','','','');	    
			  $result=$mail->check_server_port($server,$port);
			  if ($result) print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort",$server,$port).'</div>';
			  else 
			  {
				print '<div class="error">'.$langs->trans("ServerNotAvailableOnIPOrPort",$server,$port);
				if ($mail->error) print ' - '.$mail->error;
				print '</div>';
			  }
			  print '<br>';
	}

	// Affichage formulaire de TEST simple
	if ($_GET["action"] == 'test')
	{	
		  print '<br>';
		  print_titre($langs->trans("DoTestSend"));
		  
		  // Cree l'objet formulaire mail
		  include_once(DOL_DOCUMENT_ROOT."/html.formmail.class.php");
		  $formmail = new FormMail($db);	    
		  $formmail->fromname = $conf->global->MAIN_MAIL_EMAIL_FROM;
		  $formmail->frommail = $conf->global->MAIN_MAIL_EMAIL_FROM;
		  $formmail->withfromreadonly=0;
		  $formmail->withsubstit=0;
		  $formmail->withfrom=1;
		  $formmail->witherrorsto=1;
		  $formmail->withto=$user->email?$user->email:1;
		  $formmail->withtocc=1;
		  $formmail->withtopic=$langs->trans("Test");
		  $formmail->withtopicreadonly=0;
		  $formmail->withfile=2;
		  $formmail->withbody=$langs->trans("Test");
		  $formmail->withbodyreadonly=0;
		  $formmail->withcancel=1;
		  $formmail->withdeliveryreceipt=1;
		  // Tableau des substitutions
		  $formmail->substit=$substitutionarrayfortest;
		  // Tableau des parametres complementaires du post
		  $formmail->param["action"]="send";
		  $formmail->param["models"]="body";
		  $formmail->param["mailid"]=$mil->id;
		  $formmail->param["returnurl"]=DOL_URL_ROOT."/admin/mails.php";
		
			// Init list of files
			if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
			{
				$formmail->clear_attached_files();
			}
		  
		  $formmail->show_form('addfile');
		  
		  print '<br>';
	}

	// Affichage formulaire de TEST HTML
	if ($_GET["action"] == 'testhtml')
	{	
		  print '<br>';
		  print_titre($langs->trans("DoTestSendHTML"));
		  
		  // Cree l'objet formulaire mail
		  include_once(DOL_DOCUMENT_ROOT."/html.formmail.class.php");
		  $formmail = new FormMail($db);	    
		  $formmail->fromname = $conf->global->MAIN_MAIL_EMAIL_FROM;
		  $formmail->frommail = $conf->global->MAIN_MAIL_EMAIL_FROM;
		  $formmail->withfromreadonly=0;
		  $formmail->withsubstit=0;
		  $formmail->withfrom=1;
		  $formmail->witherrorsto=1;
		  $formmail->withto=$user->email?$user->email:1;
		  $formmail->withtocc=1;
		  $formmail->withtopic=$langs->trans("Test");
		  $formmail->withtopicreadonly=0;
		  $formmail->withfile=2;
		  $formmail->withbody=$langs->trans("Test");
		  $formmail->withbodyreadonly=0;
		  $formmail->withcancel=1;
		  $formmail->withdeliveryreceipt=1;
		  $formmail->withfckeditor=1;
		  // Tableau des substitutions
		  $formmail->substit=$substitutionarrayfortest;
		  // Tableau des parametres complementaires du post
		  $formmail->param["action"]="sendhtml";
		  $formmail->param["models"]="body";
		  $formmail->param["mailid"]=$mil->id;
		  $formmail->param["returnurl"]=DOL_URL_ROOT."/admin/mails.php";
		
			// Init list of files
			if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
			{
				$formmail->clear_attached_files();
			}
		  
		  $formmail->show_form('addfilehtml');
		  
		  print '<br>';
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
