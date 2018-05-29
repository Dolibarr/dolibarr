<?php
/* Copyright (C) 2007-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013 	   Juanjo Menent		<jmenent@2byte.es>
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
 *       \file       htdocs/admin/sms.php
 *       \brief      Page to setup emails sending
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies","admin","products","sms","other","errors"));

if (!$user->admin)
accessforbidden();

$substitutionarrayfortest=array(
'__ID__' => 'TESTIdRecord',
'__PHONEFROM__' => 'TESTPhoneFrom',
'__PHONETO__' => 'TESTPhoneTo',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname'
);

$action=GETPOST('action','aZ09');


/*
 * Actions
 */

if ($action == 'update' && empty($_POST["cancel"]))
{
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_SMS",   $_POST["MAIN_DISABLE_ALL_SMS"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_SMS_SENDMODE",      $_POST["MAIN_SMS_SENDMODE"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_MAIL_SMS_FROM",     $_POST["MAIN_MAIL_SMS_FROM"],'chaine',0,'',$conf->entity);
	//dolibarr_set_const($db, "MAIN_MAIL_AUTOCOPY_TO",    $_POST["MAIN_MAIL_AUTOCOPY_TO"],'chaine',0,'',$conf->entity);

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * Send sms
 */
if ($action == 'send' && ! $_POST['cancel'])
{
	$error=0;

	$smsfrom='';
	if (! empty($_POST["fromsms"])) $smsfrom=GETPOST("fromsms");
	if (empty($smsfrom)) $smsfrom=GETPOST("fromname");
	$sendto     = GETPOST("sendto");
	$body       = GETPOST('message');
	$deliveryreceipt= GETPOST("deliveryreceipt");
    $deferred   = GETPOST('deferred');
    $priority   = GETPOST('priority');
    $class      = GETPOST('class');
    $errors_to  = GETPOST("errorstosms");

	// Create form object
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formsms.class.php';
	$formsms = new FormSms($db);

	if (! empty($formsms->error))
	{
		setEventMessages($formsms->error, $formsms->errors, 'errors');
	    $action='test';
	    $error++;
	}
    if (empty($body))
    {
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Message")), null, 'errors');
        $action='test';
        $error++;
    }
	if (empty($smsfrom) || ! str_replace('+','',$smsfrom))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsFrom")), null, 'errors');
        $action='test';
		$error++;
	}
	if (empty($sendto) || ! str_replace('+','',$sendto))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("SmsTo")), null, 'errors');
        $action='test';
		$error++;
	}
	if (! $error)
	{
		// Make substitutions into message
        complete_substitutions_array($substitutionarrayfortest, $langs);
	    $body=make_substitutions($body,$substitutionarrayfortest);

		require_once DOL_DOCUMENT_ROOT.'/core/class/CSMSFile.class.php';

		$smsfile = new CSMSFile($sendto, $smsfrom, $body, $deliveryreceipt, $deferred, $priority, $class);  // This define OvhSms->login, pass, session and account
		$result=$smsfile->sendfile(); // This send SMS

		if ($result)
		{
			setEventMessages($langs->trans("SmsSuccessfulySent",$smsfrom,$sendto), null, 'mesgs');
			setEventMessages($smsfile->error, $smsfile->errors, 'mesgs');
		}
		else
		{
			setEventMessages($langs->trans("ResultKo"), null, 'errors');
			setEventMessages($smsfile->error, $smsfile->errors, 'errors');
		}

		$action='';
	}
}



/*
 * View
 */

$linuxlike=1;
if (preg_match('/^win/i',PHP_OS)) $linuxlike=0;
if (preg_match('/^mac/i',PHP_OS)) $linuxlike=0;

$wikihelp='EN:Setup Sms|FR:Paramétrage Sms|ES:Configuración Sms';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print load_fiche_titre($langs->trans("SmsSetup"),'','title_setup');

print $langs->trans("SmsDesc")."<br>\n";
print "<br>\n";

// List of sending methods
$listofmethods=(is_array($conf->modules_parts['sms'])?$conf->modules_parts['sms']:array());
asort($listofmethods);

if ($action == 'edit')
{
	$form=new Form($db);

	if (! count($listofmethods)) print '<div class="warning">'.$langs->trans("NoSmsEngine",'<a href="http://www.dolistore.com/search.php?orderby=position&orderway=desc&search_query=smsmanager">DoliStore</a>').'</div>';

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable	
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DISABLE_ALL_SMS").'</td><td>';
	print $form->selectyesno('MAIN_DISABLE_ALL_SMS',$conf->global->MAIN_DISABLE_ALL_SMS,1);
	print '</td></tr>';

	// Separator	
	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// Method	
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_SMS_SENDMODE").'</td><td>';
	if (count($listofmethods)) print $form->selectarray('MAIN_SMS_SENDMODE',$listofmethods,$conf->global->MAIN_SMS_SENDMODE,1);
	else print '<font class="error">'.$langs->trans("None").'</font>';
    print '</td></tr>';

	// From	
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMS_FROM",$langs->transnoentities("Undefined")).'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_SMS_FROM" size="32" value="' . $conf->global->MAIN_MAIL_SMS_FROM;
	print '"></td></tr>';

	// Autocopy to
	/*
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_AUTOCOPY_TO" size="32" value="' . $conf->global->MAIN_MAIL_AUTOCOPY_TO;
	print '"></td></tr>';
	*/
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'"'.(!count($listofmethods)?' disabled':'').'>';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
	print '<br>';
}
else
{

	if (! count($listofmethods)) print '<div class="warning">'.$langs->trans("NoSmsEngine",'<a target="_blank" href="http://www.dolistore.com/search.php?orderby=position&orderway=desc&search_query=smsmanager">DoliStore</a>').'</div>';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable	
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DISABLE_ALL_SMS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_SMS).'</td></tr>';

	// Separator	
	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// Method	
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_SMS_SENDMODE").'</td><td>';
	$text=$listofmethods[$conf->global->MAIN_SMS_SENDMODE];
	if (empty($text)) $text=$langs->trans("Undefined").' '.img_warning();
	print $text;
	print '</td></tr>';

	// From	
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMS_FROM",$langs->transnoentities("Undefined")).'</td>';
	print '<td>'.$conf->global->MAIN_MAIL_SMS_FROM;
	if (!empty($conf->global->MAIN_MAIL_SMS_FROM) && ! isValidPhone($conf->global->MAIN_MAIL_SMS_FROM)) print ' '.img_warning($langs->trans("ErrorBadPhone"));
	print '</td></tr>';

	// Autocopy to
	/*
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
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

	if (count($listofmethods) && ! empty($conf->global->MAIN_SMS_SENDMODE))
	{
	   print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&amp;mode=init">'.$langs->trans("DoTestSend").'</a>';
	}
	else
	{
       print '<a class="butActionRefused" href="#">'.$langs->trans("DoTestSend").'</a>';
	}
	print '</div>';


	// Run the test to connect
	/*
	if ($_GET["action"] == 'testconnect')
	{
		print '<br>';
		print load_fiche_titre($langs->trans("DoTestServerAvailability"));

		// If we use SSL/TLS
		if (! empty($conf->global->MAIN_MAIL_EMAIL_TLS) && function_exists('openssl_open')) $server='ssl://'.$server;

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mail = new CSMSFile('','','','');
		$result=$mail->check_server_port($server,$port);
		if ($result) print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort",$server,$port).'</div>';
		else
		{
			print '<div class="error">'.$langs->trans("ServerNotAvailableOnIPOrPort",$server,$port);
			if ($mail->error) print ' - '.$mail->error;
			print '</div>';
		}
		print '<br>';
	}*/

	// Affichage formulaire de TEST simple
	if ($action == 'test')
	{
		print '<br>';
		print load_fiche_titre($langs->trans("DoTestSend"));

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formsms.class.php';
		$formsms = new FormSms($db);
        $formsms->fromtype='user';
        $formsms->fromid=$user->id;
        $formsms->fromsms = (isset($_POST['fromsms'])?$_POST['fromsms']:($conf->global->MAIN_MAIL_SMS_FROM?$conf->global->MAIN_MAIL_SMS_FROM:$user->user_mobile));
		$formsms->withfromreadonly=0;
		$formsms->withsubstit=0;
		$formsms->withfrom=1;
		$formsms->witherrorsto=1;
		$formsms->withto=(isset($_POST['sendto'])?$_POST['sendto']:$user->user_mobile?$user->user_mobile:1);
		$formsms->withfile=2;
		$formsms->withbody=(isset($_POST['message'])?(empty($_POST['message'])?1:$_POST['message']):$langs->trans("ThisIsATestMessage"));
		$formsms->withbodyreadonly=0;
		$formsms->withcancel=1;
		$formsms->withfckeditor=0;
		// Tableau des substitutions
		$formsms->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formsms->param["action"]="send";
		$formsms->param["models"]="body";
		$formsms->param["smsid"]=0;
		$formsms->param["returnurl"]=$_SERVER["PHP_SELF"];

		$formsms->show_form();

		print '<br>';
	}
}


llxFooter();

$db->close();
