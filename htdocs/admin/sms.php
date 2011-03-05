<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/admin/sms.php
 *       \brief      Page to setup emails sending
 *       \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("mails");
$langs->load("other");
$langs->load("errors");

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

if (isset($_POST["action"]) && $_POST["action"] == 'update' && empty($_POST["cancel"]))
{
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_SMS",   $_POST["MAIN_DISABLE_ALL_SMS"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_SMS_SENDMODE",      $_POST["MAIN_SMS_SENDMODE"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_MAIL_SMS_FROM",     $_POST["MAIN_MAIL_SMS_FROM"],'chaine',0,'',$conf->entity);
	//dolibarr_set_const($db, "MAIN_MAIL_AUTOCOPY_TO",    $_POST["MAIN_MAIL_AUTOCOPY_TO"],'chaine',0,'',$conf->entity);

	Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * Send sms
 */
if (($_POST['action'] == 'send' || $_POST['action'] == 'sendhtml')
&& ! $_POST['addfile'] && ! $_POST['addfilehtml'] && ! $_POST["removedfile"] && ! $_POST['cancel'])
{
	$error=0;

	$email_from='';
	if (! empty($_POST["fromname"])) $email_from=$_POST["fromname"].' ';
	if (! empty($_POST["fromsms"])) $email_from.='<'.$_POST["fromsms"].'>';

	$errors_to  = $_POST["errorstosms"];
	$sendto     = $_POST["sendto"];
	$sendtocc   = $_POST["sendtocc"];
	$sendtoccc  = $_POST["sendtoccc"];
	$subject    = $_POST['subject'];
	$body       = $_POST['message'];
	$deliveryreceipt= $_POST["deliveryreceipt"];

	// Create form object
	include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formsms.class.php');
	$formsms = new FormSms($db);

	if (empty($_POST["fromsms"]))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("SmsFrom")).'</div>';
		$_GET["action"]='test';
		$error++;
	}
	if (empty($sendto))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("SmsTo")).'</div>';
		$_GET["action"]='test';
		$error++;
	}
	if (! $error)
	{
		// Le message est-il en html
		$msgishtml=0;	// Message is not HTML
		if ($_POST['action'] == 'sendhtml') $msgishtml=1;	// Force message to HTML

		// Pratique les substitutions sur le sujet et message
		$subject=make_substitutions($subject,$substitutionarrayfortest,$langs);
		$body=make_substitutions($body,$substitutionarrayfortest,$langs);

		require_once(DOL_DOCUMENT_ROOT."/lib/CSMSFile.class.php");
		$mailfile = new CSMSFile($subject,$sendto,$email_from,$body,
		$filepath,$mimetype,$filename,
		$sendtocc, $sendtoccc, $deliveryreceipt, $msgishtml,$errors_to);

		$result=$mailfile->sendfile();

		if ($result)
		{
			$message='<div class="ok">'.$langs->trans("SmsSuccessfulySent",$email_from,$sendto).'</div>';
		}
		else
		{
			$message='<div class="error">'.$langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result.'</div>';
		}

		$_GET["action"]='';
	}
}



/*
 * View
 */

$linuxlike=1;
if (preg_match('/^win/i',PHP_OS)) $linuxlike=0;
if (preg_match('/^mac/i',PHP_OS)) $linuxlike=0;



/*
 * View
 */

$wikihelp='EN:Setup Sms|FR:Paramétrage Sms|ES:Configuración Sms';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print_fiche_titre($langs->trans("SmsSetup"),'','setup');

print $langs->trans("SmsDesc")."<br>\n";
print "<br>\n";

if ($message) print $message.'<br>';

// List of sending methods
$listofmethods=$conf->sms_engine;


if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
	$html=new Form($db);

	if (! sizeof($listofmethods)) print '<div class="error">'.$langs->trans("NoSmsEngine").'</div>';

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_SMS").'</td><td>';
	print $html->selectyesno('MAIN_DISABLE_ALL_SMS',$conf->global->MAIN_DISABLE_ALL_SMS,1);
	print '</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';

	// Method
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_SMS_SENDMODE").'</td><td>';
	if (sizeof($listofmethods)) print $html->selectarray('MAIN_SMS_SENDMODE',$listofmethods,$conf->global->MAIN_SMS_SENDMODE);
	else print '<font class="error">'.$langs->trans("None").'</font>';
    print '</td></tr>';

	// From
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMS_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_SMS_FROM" size="32" value="' . $conf->global->MAIN_MAIL_SMS_FROM;
	print '"></td></tr>';

	// Autocopy to
	/*$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_AUTOCOPY_TO" size="32" value="' . $conf->global->MAIN_MAIL_AUTOCOPY_TO;
	print '"></td></tr>';
	*/
	print '</table>';

	print '<br><center>';
	print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'"'.(!sizeof($listofmethods)?' disabled="disbaled"':'').'>';
	print ' &nbsp; &nbsp; ';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'"'.(!sizeof($listofmethods)?' disabled="disbaled"':'').'>';
	print '</center>';

	print '</form>';
	print '<br>';
}
else
{
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_SMS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_SMS).'</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';

	// Method
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_SMS_SENDMODE").'</td><td>';
	$text=$listofmethods[$conf->global->MAIN_SMS_SENDMODE];
	if (empty($text)) $text=$langs->trans("Undefined").img_warning();
	print $text;
	print '</td></tr>';

	// From
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMS_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td>';
	print '<td>'.$conf->global->MAIN_MAIL_SMS_FROM;
	if (!empty($conf->global->MAIN_MAIL_SMS_FROM) && ! isValidEmail($conf->global->MAIN_MAIL_SMS_FROM)) print img_warning($langs->trans("ErrorBadEMail"));
	print '</td></tr>';

	// Autocopy to
	/*$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td>'.$conf->global->MAIN_MAIL_AUTOCOPY_TO;
	if (!empty($conf->global->MAIN_MAIL_AUTOCOPY_TO) && ! isValidEmail($conf->global->MAIN_MAIL_AUTOCOPY_TO)) print img_warning($langs->trans("ErrorBadEMail"));
	print '</td></tr>';
    */

	print '</table>';


	// Boutons actions

	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';

	/*if ($conf->global->MAIN_SMS_SENDMODE != 'mail' || ! $linuxlike)
	{
		if (function_exists('fsockopen') && $port && $server)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect">'.$langs->trans("DoTestServerAvailability").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("FeatureNotAvailableOnLinux").'">'.$langs->trans("DoTestServerAvailability").'</a>';
	}*/

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&amp;mode=init">'.$langs->trans("DoTestSend").'</a>';

	print '</div>';


	// Run the test to connect
	/*
	if ($_GET["action"] == 'testconnect')
	{
		print '<br>';
		print_titre($langs->trans("DoTestServerAvailability"));

		// If we use SSL/TLS
		if (! empty($conf->global->MAIN_MAIL_EMAIL_TLS) && function_exists('openssl_open')) $server='ssl://'.$server;

		include_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
		$mail = new CSMSFile('','','','');
		$result=$mail->check_server_port($server,$port);
		if ($result) print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort",$server,$port).'</div>';
		else
		{
			print '<div class="error">'.$langs->trans("ServerNotAvailableOnIPOrPort",$server,$port);
			if ($mail->error) print ' - '.$langs->convToOutputCharset($mail->error,'ISO-8859-1');
			print '</div>';
		}
		print '<br>';
	}*/

	// Affichage formulaire de TEST simple
	if ($_GET["action"] == 'test')
	{
		print '<br>';
		print_titre($langs->trans("DoTestSend"));

		// Cree l'objet formulaire mail
		include_once(DOL_DOCUMENT_ROOT."/core/class/html.formsms.class.php");
		$formsms = new FormSms($db);
		$formsms->fromname = (isset($_POST['fromname'])?$_POST['fromname']:$conf->global->MAIN_MAIL_SMS_FROM);
		$formsms->fromsms = (isset($_POST['fromsms'])?$_POST['fromsms']:$conf->global->MAIN_MAIL_SMS_FROM);
		$formsms->withfromreadonly=0;
		$formsms->withsubstit=0;
		$formsms->withfrom=1;
		$formsms->witherrorsto=1;
		$formsms->withto=(isset($_POST['sendto'])?$_POST['sendto']:$user->email?$user->email:1);
		$formsms->withtocc=(isset($_POST['sendtocc'])?$_POST['sendtocc']:1);
		$formsms->withtoccc=(isset($_POST['sendtoccc'])?$_POST['sendtoccc']:1);
		$formsms->withtopic=(isset($_POST['subject'])?$_POST['subject']:$langs->trans("Test"));
		$formsms->withtopicreadonly=0;
		$formsms->withfile=2;
		$formsms->withbody=(isset($_POST['message'])?$_POST['message']:$langs->trans("PredefinedMailTest"));
		$formsms->withbodyreadonly=0;
		$formsms->withcancel=1;
		$formsms->withdeliveryreceipt=1;
		$formsms->withfckeditor=0;
		// Tableau des substitutions
		$formsms->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formsms->param["action"]="send";
		$formsms->param["models"]="body";
		$formsms->param["mailid"]=$mil->id;
		$formsms->param["returnurl"]=DOL_URL_ROOT."/admin/sms.php";

		// Init list of files
		if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
		{
			$formsms->clear_attached_files();
		}

		$formsms->show_form('addfile','removefile');

		print '<br>';
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
