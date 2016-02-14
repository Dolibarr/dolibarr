<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Juanjo Menent		<jmenent@2byte.es>
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
 *       \file       htdocs/admin/mails.php
 *       \brief      Page to setup emails sending
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("mails");
$langs->load("other");
$langs->load("errors");

$action=GETPOST('action','alpha');

if (! $user->admin) accessforbidden();

$usersignature=$user->signature;
// For action = test or send, we ensure that content is not html, even for signature, because this we want a test with NO html.
if ($action == 'test' || $action == 'send')
{
	$usersignature=dol_string_nohtmltag($usersignature);
}

$substitutionarrayfortest=array(
'__LOGIN__' => $user->login,
'__ID__' => 'TESTIdRecord',
'__EMAIL__' => 'TESTEMail',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname',
'__SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))?$usersignature:''),
//'__PERSONALIZED__' => 'TESTPersonalized'	// Hiden because not used yet
);
complete_substitutions_array($substitutionarrayfortest, $langs);



/*
 * Actions
 */

if ($action == 'update' && empty($_POST["cancel"]))
{
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS",   GETPOST("MAIN_DISABLE_ALL_MAILS"),'chaine',0,'',$conf->entity);
    // Send mode parameters
	dolibarr_set_const($db, "MAIN_MAIL_SENDMODE",       GETPOST("MAIN_MAIL_SENDMODE"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",      GETPOST("MAIN_MAIL_SMTP_PORT"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER",    GETPOST("MAIN_MAIL_SMTP_SERVER"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",       GETPOST("MAIN_MAIL_SMTPS_ID"), 'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",       GETPOST("MAIN_MAIL_SMTPS_PW"), 'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",      GETPOST("MAIN_MAIL_EMAIL_TLS"),'chaine',0,'',$conf->entity);
    // Content parameters
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",     GETPOST("MAIN_MAIL_EMAIL_FROM"), 'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_ERRORS_TO",		GETPOST("MAIN_MAIL_ERRORS_TO"),  'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_AUTOCOPY_TO",    GETPOST("MAIN_MAIL_AUTOCOPY_TO"),'chaine',0,'',$conf->entity);

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * Add file in email form
 */
if (GETPOST('addfile') || GETPOST('addfilehtml'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp';
	dol_add_file_process($upload_dir,0,0);

	if ($_POST['addfile'])     $action='test';
	if ($_POST['addfilehtml']) $action='testhtml';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']) || ! empty($_POST['removedfilehtml']))
{
	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp';

	$keytodelete=isset($_POST['removedfile'])?$_POST['removedfile']:$_POST['removedfilehtml'];
	$keytodelete--;

	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();
	if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
	if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
	if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);

	if ($keytodelete >= 0)
	{
		$pathtodelete=$listofpaths[$keytodelete];
		$filetodelete=$listofnames[$keytodelete];
		$result = dol_delete_file($pathtodelete,1);
		if ($result)
		{
			setEventMessages(array($langs->trans("FileWasRemoved"), $filetodelete), null, 'mesgs');

			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);
			$formmail->remove_attached_files($keytodelete);
		}
	}
	if ($_POST['removedfile'] || $action='send')     $action='test';
	if ($_POST['removedfilehtml'] || $action='sendhtml') $action='testhtml';
}

/*
 * Send mail
 */
if (($action == 'send' || $action == 'sendhtml') && ! GETPOST('addfile') && ! GETPOST('addfilehtml') && ! GETPOST('removedfile') && ! GETPOST('cancel'))
{
	$error=0;

	$email_from='';
	if (! empty($_POST["fromname"])) $email_from=$_POST["fromname"].' ';
	if (! empty($_POST["frommail"])) $email_from.='<'.$_POST["frommail"].'>';

	$errors_to  = $_POST["errorstomail"];
	$sendto     = $_POST["sendto"];
	$sendtocc   = $_POST["sendtocc"];
	$sendtoccc  = $_POST["sendtoccc"];
	$subject    = $_POST['subject'];
	$body       = $_POST['message'];
	$deliveryreceipt= $_POST["deliveryreceipt"];
	$trackid    = GETPOST('trackid');
	
	//Check if we have to decode HTML
	if (!empty($conf->global->FCKEDITOR_ENABLE_MAILING) && dol_textishtml(dol_html_entity_decode($body, ENT_COMPAT | ENT_HTML401))) {
		$body=dol_html_entity_decode($body, ENT_COMPAT | ENT_HTML401);
	}

	// Create form object
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	$formmail = new FormMail($db);

	$attachedfiles=$formmail->get_attached_files();
	$filepath = $attachedfiles['paths'];
	$filename = $attachedfiles['names'];
	$mimetype = $attachedfiles['mimes'];

	if (empty($_POST["frommail"]))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("MailFrom")), null, 'errors');
		$action='test';
		$error++;
	}
	if (empty($sendto))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTo")), null, 'errors');
		$action='test';
		$error++;
	}
	if (! $error)
	{
		// Is the message in HTML?
		$msgishtml=0;	// Message is not HTML
		if ($action == 'sendhtml') $msgishtml=1;	// Force message to HTML

		// Pratique les substitutions sur le sujet et message
		$subject=make_substitutions($subject,$substitutionarrayfortest);
		$body=make_substitutions($body,$substitutionarrayfortest);

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
        $mailfile = new CMailFile(
            $subject,
            $sendto,
            $email_from,
            $body,
            $filepath,
            $mimetype,
            $filename,
            $sendtocc,
            $sendtoccc,
            $deliveryreceipt,
            $msgishtml,
            $errors_to,
        	'',
        	$trackid	
        );

		$result=$mailfile->sendfile();

		if ($result)
		{
			setEventMessages($langs->trans("MailSuccessfulySent",$mailfile->getValidAddress($email_from,2),$mailfile->getValidAddress($sendto,2)), null, 'mesgs');
		}
		else
		{
			setEventMessages($langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result, null, 'errors');
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

if (empty($conf->global->MAIN_MAIL_SENDMODE)) $conf->global->MAIN_MAIL_SENDMODE='mail';
$port=! empty($conf->global->MAIN_MAIL_SMTP_PORT)?$conf->global->MAIN_MAIL_SMTP_PORT:ini_get('smtp_port');
if (! $port) $port=25;
$server=! empty($conf->global->MAIN_MAIL_SMTP_SERVER)?$conf->global->MAIN_MAIL_SMTP_SERVER:ini_get('SMTP');
if (! $server) $server='127.0.0.1';


/*
 * View
 */

$wikihelp='EN:Setup EMails|FR:Paramétrage EMails|ES:Configuración EMails';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print load_fiche_titre($langs->trans("EMailsSetup"),'','title_setup');

print $langs->trans("EMailsDesc")."<br>\n";
print "<br>\n";

// List of sending methods
$listofmethods=array();
$listofmethods['mail']='PHP mail function';
//$listofmethods['simplemail']='Simplemail class';
$listofmethods['smtps']='SMTP/SMTPS socket library';


if ($action == 'edit')
{
	$form=new Form($db);

	if ($conf->use_javascript_ajax)
	{
		print "\n".'<script type="text/javascript" language="javascript">';
		print 'jQuery(document).ready(function () {
                    function initfields()
                    {
                        if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'mail\')
                        {
                            jQuery(".drag").hide();
                            jQuery("#MAIN_MAIL_EMAIL_TLS").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_TLS").prop("disabled", true);
                            ';
		if ($linuxlike)
		{
			print '
			               jQuery("#MAIN_MAIL_SMTP_SERVER").hide();
			               jQuery("#MAIN_MAIL_SMTP_PORT").hide();
			               jQuery("#smtp_server_mess").show();
			               jQuery("#smtp_port_mess").show();
			               ';
		}
		else
		{
			print '
			               jQuery("#MAIN_MAIL_SMTP_SERVER").prop("disabled", true);
			               jQuery("#MAIN_MAIL_SMTP_PORT").prop("disabled", true);
			               jQuery("#smtp_server_mess").hide();
			               jQuery("#smtp_port_mess").hide();
			               ';
		}
		print '
                        }
                        if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'smtps\')
                        {
                            jQuery(".drag").show();
                            jQuery("#MAIN_MAIL_EMAIL_TLS").val('.$conf->global->MAIN_MAIL_EMAIL_TLS.');
                            jQuery("#MAIN_MAIL_EMAIL_TLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_PORT").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER").show();
                            jQuery("#MAIN_MAIL_SMTP_PORT").show();
                            jQuery("#smtp_server_mess").hide();
			                jQuery("#smtp_port_mess").hide();
						}
                    }
                    initfields();
                    jQuery("#MAIN_MAIL_SENDMODE").change(function() {
                        initfields();
                    });
               })';
		print '</script>'."\n";
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>';
	print $form->selectyesno('MAIN_DISABLE_ALL_MAILS',$conf->global->MAIN_DISABLE_ALL_MAILS,1);
	print '</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';

	// Method
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';

	// SuperAdministrator access only
	if ((empty($conf->global->MAIN_MODULE_MULTICOMPANY)) || ($user->admin && !$user->entity))
	{
		print $form->selectarray('MAIN_MAIL_SENDMODE',$listofmethods,$conf->global->MAIN_MAIL_SENDMODE);
	}
	else
	{
		$text = $listofmethods[$conf->global->MAIN_MAIL_SENDMODE];
		if (empty($text)) $text = $langs->trans("Undefined");
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $form->textwithpicto($text,$htmltext,1,'superadmin');
		print '<input type="hidden" name="MAIN_MAIL_SENDMODE" value="'.$conf->global->MAIN_MAIL_SENDMODE.'">';
	}
	print '</td></tr>';

	// Host server
	$var=!$var;
	print '<tr '.$bc[$var].'><td>';
	if (! $conf->use_javascript_ajax && $linuxlike && $conf->global->MAIN_MAIL_SENDMODE == 'mail')
	{
		print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		print '</td><td>';
		print $langs->trans("SeeLocalSendMailSetup");
	}
	else
	{
		$mainserver = (! empty($conf->global->MAIN_MAIL_SMTP_SERVER)?$conf->global->MAIN_MAIL_SMTP_SERVER:'');
		$smtpserver = ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined");
		if ($linuxlike) print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		else print $langs->trans("MAIN_MAIL_SMTP_SERVER",$smtpserver);
		print '</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
		{
			print '<input class="flat" id="MAIN_MAIL_SMTP_SERVER" name="MAIN_MAIL_SMTP_SERVER" size="18" value="' . $mainserver . '">';
			print '<input type="hidden" id="MAIN_MAIL_SMTP_SERVER_sav" name="MAIN_MAIL_SMTP_SERVER_sav" value="' . $mainserver . '">';
			print '<span id="smtp_server_mess">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
		}
		else
		{
			$text = ! empty($mainserver) ? $mainserver : $smtpserver;
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($text,$htmltext,1,'superadmin');
			print '<input type="hidden" id="MAIN_MAIL_SMTP_SERVER" name="MAIN_MAIL_SMTP_SERVER" value="'.$mainserver.'">';
		}
	}
	print '</td></tr>';

	// Port
	$var=!$var;
	print '<tr '.$bc[$var].'><td>';
	if (! $conf->use_javascript_ajax && $linuxlike && $conf->global->MAIN_MAIL_SENDMODE == 'mail')
	{
		print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		print '</td><td>';
		print $langs->trans("SeeLocalSendMailSetup");
	}
	else
	{
		$mainport = (! empty($conf->global->MAIN_MAIL_SMTP_PORT) ? $conf->global->MAIN_MAIL_SMTP_PORT : '');
		$smtpport = ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined");
		if ($linuxlike) print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		else print $langs->trans("MAIN_MAIL_SMTP_PORT",$smtpport);
		print '</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
		{
			print '<input class="flat" id="MAIN_MAIL_SMTP_PORT" name="MAIN_MAIL_SMTP_PORT" size="3" value="' . $mainport . '">';
			print '<input type="hidden" id="MAIN_MAIL_SMTP_PORT_sav" name="MAIN_MAIL_SMTP_PORT_sav" value="' . $mainport . '">';
			print '<span id="smtp_port_mess">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
		}
		else
		{
			$text = (! empty($mainport) ? $mainport : $smtpport);
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($text,$htmltext,1,'superadmin');
			print '<input type="hidden" id="MAIN_MAIL_SMTP_PORT" name="MAIN_MAIL_SMTP_PORT" value="'.$mainport.'">';
		}
	}
	print '</td></tr>';

	// ID
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'smtps'))
	{
		$var=!$var;
		$mainstmpid=(! empty($conf->global->MAIN_MAIL_SMTPS_ID)?$conf->global->MAIN_MAIL_SMTPS_ID:'');
		print '<tr '.$bcdd[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" name="MAIN_MAIL_SMTPS_ID" size="32" value="' . $mainstmpid . '">';
		}
		else
		{
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($conf->global->MAIN_MAIL_SMTPS_ID,$htmltext,1,'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_ID" value="'.$mainstmpid.'">';
		}
		print '</td></tr>';
	}

	// PW
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'smtps'))
	{
		$var=!$var;
		$mainsmtppw=(! empty($conf->global->MAIN_MAIL_SMTPS_PW)?$conf->global->MAIN_MAIL_SMTPS_PW:'');
		print '<tr '.$bcdd[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" type="password" name="MAIN_MAIL_SMTPS_PW" size="32" value="' . $mainsmtppw . '">';
		}
		else
		{
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($conf->global->MAIN_MAIL_SMTPS_PW,$htmltext,1,'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_PW" value="'.$mainsmtppw.'">';
		}
		print '</td></tr>';
	}

	// TLS
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'smtps'))
	{
		if (function_exists('openssl_open'))
		{
			print $form->selectyesno('MAIN_MAIL_EMAIL_TLS',(! empty($conf->global->MAIN_MAIL_EMAIL_TLS)?$conf->global->MAIN_MAIL_EMAIL_TLS:0),1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';

	// From
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_EMAIL_FROM" size="32" value="' . (! empty($conf->global->MAIN_MAIL_EMAIL_FROM)?$conf->global->MAIN_MAIL_EMAIL_FROM:'');
	print '"></td></tr>';

	// From
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_ERRORS_TO").'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_ERRORS_TO" size="32" value="' . (! empty($conf->global->MAIN_MAIL_ERRORS_TO)?$conf->global->MAIN_MAIL_ERRORS_TO:'');
	print '"></td></tr>';

	// Autocopy to
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_AUTOCOPY_TO" size="32" value="' . (! empty($conf->global->MAIN_MAIL_AUTOCOPY_TO)?$conf->global->MAIN_MAIL_AUTOCOPY_TO:'');
	print '"></td></tr>';
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}
else
{
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_MAILS).'</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';

	// Method
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';
	$text=$listofmethods[$conf->global->MAIN_MAIL_SENDMODE];
	if (empty($text)) $text=$langs->trans("Undefined").img_warning();
	print $text;
	print '</td></tr>';

	// Host server
	$var=!$var;
	if ($linuxlike && (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'mail'))
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike").'</td><td>'.$langs->trans("SeeLocalSendMailSetup").'</td></tr>';
	}
	else
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER",ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->MAIN_MAIL_SMTP_SERVER)?$conf->global->MAIN_MAIL_SMTP_SERVER:'').'</td></tr>';
	}

	// Port
	$var=!$var;
	if ($linuxlike && (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'mail'))
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike").'</td><td>'.$langs->trans("SeeLocalSendMailSetup").'</td></tr>';
	}
	else
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT",ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->MAIN_MAIL_SMTP_PORT)?$conf->global->MAIN_MAIL_SMTP_PORT:'').'</td></tr>';
	}

	// SMTPS ID
	$var=!$var;
	if (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>'.$conf->global->MAIN_MAIL_SMTPS_ID.'</td></tr>';
	}

	// SMTPS PW
	$var=!$var;
	if (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>'.preg_replace('/./','*',$conf->global->MAIN_MAIL_SMTPS_PW).'</td></tr>';
	}

	// TLS
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		if (function_exists('openssl_open'))
		{
			print yn($conf->global->MAIN_MAIL_EMAIL_TLS);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	// Separator
	$var=!$var;
	print '<tr '.$bc[$var].'><td colspan="2">&nbsp;</td></tr>';

	// From
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td>';
	print '<td>'.$conf->global->MAIN_MAIL_EMAIL_FROM;
	if (! empty($conf->global->MAIN_MAIL_EMAIL_FROM) && ! isValidEmail($conf->global->MAIN_MAIL_EMAIL_FROM)) print img_warning($langs->trans("ErrorBadEMail"));
	print '</td></tr>';

	// Errors To
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_ERRORS_TO").'</td>';
	print '<td>'.$conf->global->MAIN_MAIL_ERRORS_TO;
	if (! empty($conf->global->MAIN_MAIL_ERRORS_TO) && ! isValidEmail($conf->global->MAIN_MAIL_ERRORS_TO)) print img_warning($langs->trans("ErrorBadEMail"));
	print '</td></tr>';

	// Autocopy to
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td>';
	if (! empty($conf->global->MAIN_MAIL_AUTOCOPY_TO))
	{
		print $conf->global->MAIN_MAIL_AUTOCOPY_TO;
		if (! isValidEmail($conf->global->MAIN_MAIL_AUTOCOPY_TO)) print img_warning($langs->trans("ErrorBadEMail"));
	}
	else
	{
		print '&nbsp;';
	}
	print '</td></tr>';

	print '</table>';

    if ($conf->global->MAIN_MAIL_SENDMODE == 'mail' && empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA))
    {
        print '<br>';
        /*
	    // Warning 1
    	if ($linuxlike)
    	{
    		$sendmailoption=ini_get('mail.force_extra_parameters');
    		if (empty($sendmailoption) || ! preg_match('/ba/',$sendmailoption))
    		{
    			print info_admin($langs->trans("SendmailOptionNotComplete"));
    		}
    	}*/
    	// Warning 2
   	    print info_admin($langs->trans("SendmailOptionMayHurtBuggedMTA"));
    }

	// Boutons actions
	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';

	if ($conf->global->MAIN_MAIL_SENDMODE != 'mail' || ! $linuxlike)
	{
		if (function_exists('fsockopen') && $port && $server)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect">'.$langs->trans("DoTestServerAvailability").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("FeatureNotAvailableOnLinux").'">'.$langs->trans("DoTestServerAvailability").'</a>';
	}

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&amp;mode=init">'.$langs->trans("DoTestSend").'</a>';

	if (! empty($conf->fckeditor->enabled))
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testhtml&amp;mode=init">'.$langs->trans("DoTestSendHTML").'</a>';
	}

	print '</div>';


	// Run the test to connect
	if ($action == 'testconnect')
	{
		print load_fiche_titre($langs->trans("DoTestServerAvailability"));

		// If we use SSL/TLS
		if (! empty($conf->global->MAIN_MAIL_EMAIL_TLS) && function_exists('openssl_open')) $server='ssl://'.$server;

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mail = new CMailFile('','','','');
		$result=$mail->check_server_port($server,$port);
		if ($result) print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort",$server,$port).'</div>';
		else
		{
			$errormsg = $langs->trans("ServerNotAvailableOnIPOrPort",$server,$port);

			if ($mail->error) {
				$errormsg .= ' - '.$mail->error;
			}

			setEventMessages($errormsg, null, 'errors');
		}
		print '<br>';
	}

	// Show email send test form
	if ($action == 'test' || $action == 'testhtml')
	{
		print load_fiche_titre($action == 'testhtml'?$langs->trans("DoTestSendHTML"):$langs->trans("DoTestSend"));

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->fromname = (isset($_POST['fromname'])?$_POST['fromname']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->frommail = (isset($_POST['frommail'])?$_POST['frommail']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->trackid='test';
		$formmail->withfromreadonly=0;
		$formmail->withsubstit=0;
		$formmail->withfrom=1;
		$formmail->witherrorsto=1;
		$formmail->withto=(! empty($_POST['sendto'])?$_POST['sendto']:($user->email?$user->email:1));
		$formmail->withtocc=(! empty($_POST['sendtocc'])?$_POST['sendtocc']:1);       // ! empty to keep field if empty
		$formmail->withtoccc=(! empty($_POST['sendtoccc'])?$_POST['sendtoccc']:1);    // ! empty to keep field if empty
		$formmail->withtopic=(isset($_POST['subject'])?$_POST['subject']:$langs->trans("Test"));
		$formmail->withtopicreadonly=0;
		$formmail->withfile=2;
		$formmail->withbody=(isset($_POST['message'])?$_POST['message']:($action == 'testhtml'?$langs->transnoentities("PredefinedMailTestHtml"):$langs->transnoentities("PredefinedMailTest")));
		$formmail->withbodyreadonly=0;
		$formmail->withcancel=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withfckeditor=($action == 'testhtml'?1:0);
		$formmail->ckeditortoolbar='dolibarr_mailings';
		// Tableau des substitutions
		$formmail->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formmail->param["action"]=($action == 'testhtml'?"sendhtml":"send");
		$formmail->param["models"]="body";
		$formmail->param["mailid"]=0;
		$formmail->param["returnurl"]=$_SERVER["PHP_SELF"];

		// Init list of files
        if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
		}

		print $formmail->get_form(($action == 'testhtml'?'addfilehtml':'addfile'),($action == 'testhtml'?'removefilehtml':'removefile'));

		print '<br>';
	}
}


llxFooter();

$db->close();
