<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016      Jonathan TISSEAU     <jonathan.tisseau@86dev.fr>
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

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'admin', 'mails', 'other', 'errors'));

$action=GETPOST('action', 'alpha');

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
'__USER_SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))?$usersignature:''),
//'__PERSONALIZED__' => 'TESTPersonalized'	// Hiden because not used yet
);
complete_substitutions_array($substitutionarrayfortest, $langs);



/*
 * Actions
 */

if ($action == 'update' && empty($_POST["cancel"]))
{
    // Send mode parameters
	dolibarr_set_const($db, "MAIN_MAIL_SENDMODE_EMAILING", GETPOST("MAIN_MAIL_SENDMODE_EMAILING"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT_EMAILING", GETPOST("MAIN_MAIL_SMTP_PORT_EMAILING"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER_EMAILING", GETPOST("MAIN_MAIL_SMTP_SERVER_EMAILING"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID_EMAILING", GETPOST("MAIN_MAIL_SMTPS_ID_EMAILING"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW_EMAILING", GETPOST("MAIN_MAIL_SMTPS_PW_EMAILING"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS_EMAILING", GETPOST("MAIN_MAIL_EMAIL_TLS_EMAILING"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_STARTTLS_EMAILING", GETPOST("MAIN_MAIL_EMAIL_STARTTLS_EMAILING"), 'chaine', 0, '', $conf->entity);

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


// Actions to send emails
$id=0;
$actiontypecode='';     // Not an event for agenda
$trigger_name='';       // Disable triggers
$paramname='id';
$mode='emailfortest';
$trackid=(($action == 'testhtml')?"testhtml":"test");
$sendcontext='emailing';	// Force to use dedicated context of setup for emailing
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

if ($action == 'presend' && GETPOST('trackid') == 'test')       $action='test';
if ($action == 'presend' && GETPOST('trackid') == 'testhtml')   $action='testhtml';




/*
 * View
 */

$linuxlike=1;
if (preg_match('/^win/i', PHP_OS)) $linuxlike=0;
if (preg_match('/^mac/i', PHP_OS)) $linuxlike=0;

if (empty($conf->global->MAIN_MAIL_SENDMODE_EMAILING)) $conf->global->MAIN_MAIL_SENDMODE_EMAILING='default';
$port=! empty($conf->global->MAIN_MAIL_SMTP_PORT_EMAILING)?$conf->global->MAIN_MAIL_SMTP_PORT_EMAILING:ini_get('smtp_port');
if (! $port) $port=25;
$server=! empty($conf->global->MAIN_MAIL_SMTP_SERVER_EMAILING)?$conf->global->MAIN_MAIL_SMTP_SERVER_EMAILING:ini_get('SMTP');
if (! $server) $server='127.0.0.1';


$wikihelp='EN:Setup_EMails|FR:Paramétrage_EMails|ES:Configuración_EMails';
llxHeader('', $langs->trans("Setup"), $wikihelp);

print load_fiche_titre($langs->trans("EMailsSetup"), '', 'title_setup');

$head = email_admin_prepare_head();

// List of sending methods
$listofmethods=array();
$listofmethods['default']=$langs->trans('DefaultOutgoingEmailSetup');
$listofmethods['mail']='PHP mail function';
//$listofmethods['simplemail']='Simplemail class';
$listofmethods['smtps']='SMTP/SMTPS socket library';
$listofmethods['swiftmailer']='Swift Mailer socket library';


if ($action == 'edit')
{
	$form=new Form($db);

	if ($conf->use_javascript_ajax)
	{
		print "\n".'<script type="text/javascript" language="javascript">';
		print 'jQuery(document).ready(function () {
                    function initfields()
                    {
                        if (jQuery("#MAIN_MAIL_SENDMODE_EMAILING").val()==\'default\')
                        {
                            jQuery(".hideifdefault").hide();
						}
						else
						{
                            jQuery(".hideifdefault").show();
						}

						if (jQuery("#MAIN_MAIL_SENDMODE_EMAILING").val()==\'mail\')
                        {
                            jQuery(".drag").hide();
                            jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").prop("disabled", true);
                            ';
		if ($linuxlike)
		{
			print '
			               jQuery("#MAIN_MAIL_SMTP_SERVER_EMAILING").hide();
			               jQuery("#MAIN_MAIL_SMTP_PORT_EMAILING").hide();
			               jQuery("#smtp_server_mess").show();
			               jQuery("#smtp_port_mess").show();
			               ';
		}
		else
		{
			print '
			               jQuery("#MAIN_MAIL_SMTP_SERVER_EMAILING").prop("disabled", true);
			               jQuery("#MAIN_MAIL_SMTP_PORT_EMAILING").prop("disabled", true);
			               jQuery("#smtp_server_mess").hide();
			               jQuery("#smtp_port_mess").hide();
			               ';
		}
		print '
                        }
                        if (jQuery("#MAIN_MAIL_SENDMODE_EMAILING").val()==\'smtps\')
                        {
                            jQuery(".drag").show();
                            jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").val('.$conf->global->MAIN_MAIL_EMAIL_TLS_EMAILING.');
                            jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").val('.$conf->global->MAIN_MAIL_EMAIL_STARTTLS_EMAILING.');
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_PORT_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER_EMAILING").show();
                            jQuery("#MAIN_MAIL_SMTP_PORT_EMAILING").show();
                            jQuery("#smtp_server_mess").hide();
			                jQuery("#smtp_port_mess").hide();
						}
                        if (jQuery("#MAIN_MAIL_SENDMODE_EMAILING").val()==\'swiftmailer\')
                        {
                            jQuery(".drag").show();
                            jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").val('.$conf->global->MAIN_MAIL_EMAIL_TLS_EMAILING.');
                            jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").val('.$conf->global->MAIN_MAIL_EMAIL_STARTTLS_EMAILING.');
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_PORT_EMAILING").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER_EMAILING").show();
                            jQuery("#MAIN_MAIL_SMTP_PORT_EMAILING").show();
                            jQuery("#smtp_server_mess").hide();
                            jQuery("#smtp_port_mess").hide();
                        }
                    }
                    initfields();
                    jQuery("#MAIN_MAIL_SENDMODE_EMAILING").change(function() {
                        initfields();
                    });
					jQuery("#MAIN_MAIL_EMAIL_TLS").change(function() {
						if (jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").val() == 1)
							jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").val(0);
					});
					jQuery("#MAIN_MAIL_EMAIL_STARTTLS_EMAILING").change(function() {
						if (jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").val() == 1)
							jQuery("#MAIN_MAIL_EMAIL_TLS_EMAILING").val(0);
					});
               })';
		print '</script>'."\n";
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	dol_fiche_head($head, 'common_emailing', '', -1);

	print $langs->trans("EMailsDesc")."<br>\n";
	print "<br>\n";


	clearstatcache();

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Method

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';

	// SuperAdministrator access only
	if ((empty($conf->global->MAIN_MODULE_MULTICOMPANY)) || ($user->admin && !$user->entity))
	{
		print $form->selectarray('MAIN_MAIL_SENDMODE_EMAILING', $listofmethods, $conf->global->MAIN_MAIL_SENDMODE_EMAILING);
	}
	else
	{
		$text = $listofmethods[$conf->global->MAIN_MAIL_SENDMODE_EMAILING];
		if (empty($text)) $text = $langs->trans("Undefined");
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $form->textwithpicto($text, $htmltext, 1, 'superadmin');
		print '<input type="hidden" name="MAIN_MAIL_SENDMODE_EMAILING" value="'.$conf->global->MAIN_MAIL_SENDMODE_EMAILING.'">';
	}
	print '</td></tr>';

	// Host server

	print '<tr class="oddeven hideifdefault"><td>';
	if (! $conf->use_javascript_ajax && $linuxlike && $conf->global->MAIN_MAIL_SENDMODE_EMAILING == 'mail')
	{
		print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		print '</td><td>';
		print $langs->trans("SeeLocalSendMailSetup");
	}
	else
	{
		$mainserver = (! empty($conf->global->MAIN_MAIL_SMTP_SERVER_EMAILING)?$conf->global->MAIN_MAIL_SMTP_SERVER_EMAILING:'');
		$smtpserver = ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined");
		if ($linuxlike) print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		else print $langs->trans("MAIN_MAIL_SMTP_SERVER", $smtpserver);
		print '</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
		{
			print '<input class="flat" id="MAIN_MAIL_SMTP_SERVER_EMAILING" name="MAIN_MAIL_SMTP_SERVER_EMAILING" size="18" value="' . $mainserver . '">';
			print '<input type="hidden" id="MAIN_MAIL_SMTP_SERVER_EMAILING_sav" name="MAIN_MAIL_SMTP_SERVER_EMAILING_sav" value="' . $mainserver . '">';
			print '<span id="smtp_server_mess">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
		}
		else
		{
			$text = ! empty($mainserver) ? $mainserver : $smtpserver;
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($text, $htmltext, 1, 'superadmin');
			print '<input type="hidden" id="MAIN_MAIL_SMTP_SERVER_EMAILING" name="MAIN_MAIL_SMTP_SERVER_EMAILING" value="'.$mainserver.'">';
		}
	}
	print '</td></tr>';

	// Port

	print '<tr class="oddeven hideifdefault"><td>';
	if (! $conf->use_javascript_ajax && $linuxlike && $conf->global->MAIN_MAIL_SENDMODE_EMAILING == 'mail')
	{
		print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		print '</td><td>';
		print $langs->trans("SeeLocalSendMailSetup");
	}
	else
	{
		$mainport = (! empty($conf->global->MAIN_MAIL_SMTP_PORT_EMAILING) ? $conf->global->MAIN_MAIL_SMTP_PORT_EMAILING : '');
		$smtpport = ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined");
		if ($linuxlike) print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		else print $langs->trans("MAIN_MAIL_SMTP_PORT", $smtpport);
		print '</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && ! $user->entity))
		{
			print '<input class="flat" id="MAIN_MAIL_SMTP_PORT_EMAILING" name="MAIN_MAIL_SMTP_PORT_EMAILING" size="3" value="' . $mainport . '">';
			print '<input type="hidden" id="MAIN_MAIL_SMTP_PORT_EMAILING_sav" name="MAIN_MAIL_SMTP_PORT_EMAILING_sav" value="' . $mainport . '">';
			print '<span id="smtp_port_mess">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
		}
		else
		{
			$text = (! empty($mainport) ? $mainport : $smtpport);
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($text, $htmltext, 1, 'superadmin');
			print '<input type="hidden" id="MAIN_MAIL_SMTP_PORT_EMAILING" name="MAIN_MAIL_SMTP_PORT_EMAILING" value="'.$mainport.'">';
		}
	}
	print '</td></tr>';

	// ID
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer'))))
	{

		$mainstmpid=(! empty($conf->global->MAIN_MAIL_SMTPS_ID_EMAILING)?$conf->global->MAIN_MAIL_SMTPS_ID_EMAILING:'');
		print '<tr class="drag drop oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" name="MAIN_MAIL_SMTPS_ID_EMAILING" size="32" value="' . $mainstmpid . '">';
		}
		else
		{
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($conf->global->MAIN_MAIL_SMTPS_ID_EMAILING, $htmltext, 1, 'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_ID_EMAILING" value="'.$mainstmpid.'">';
		}
		print '</td></tr>';
	}

	// PW
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer'))))
	{
		$mainsmtppw=(! empty($conf->global->MAIN_MAIL_SMTPS_PW_EMAILING)?$conf->global->MAIN_MAIL_SMTPS_PW_EMAILING:'');
		print '<tr class="drag drop oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>';
		// SuperAdministrator access only
		if (empty($conf->multicompany->enabled) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" type="password" name="MAIN_MAIL_SMTPS_PW_EMAILING" size="32" value="' . $mainsmtppw . '">';
		}
		else
		{
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($conf->global->MAIN_MAIL_SMTPS_PW_EMAILING, $htmltext, 1, 'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_PW_EMAILING" value="'.$mainsmtppw.'">';
		}
		print '</td></tr>';
	}

	// TLS

	print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer'))))
	{
		if (function_exists('openssl_open'))
		{
			print $form->selectyesno('MAIN_MAIL_EMAIL_TLS_EMAILING', (! empty($conf->global->MAIN_MAIL_EMAIL_TLS_EMAILING)?$conf->global->MAIN_MAIL_EMAIL_TLS_EMAILING:0), 1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	// STARTTLS

	print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer'))))
	{
		if (function_exists('openssl_open'))
		{
			print $form->selectyesno('MAIN_MAIL_EMAIL_STARTTLS_EMAILING', (! empty($conf->global->MAIN_MAIL_EMAIL_STARTTLS_EMAILING)?$conf->global->MAIN_MAIL_EMAIL_STARTTLS_EMAILING:0), 1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

    print '</table>';

    dol_fiche_end();

    print '<br><div class="center">';
    print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
    print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

	print '</form>';
}
else
{
    dol_fiche_head($head, 'common_emailing', '', -1);

    print '<span class="opacitymedium">'.$langs->trans("EMailsDesc")."</span><br>\n";
    print "<br>\n";

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Method
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';
	$text=$listofmethods[$conf->global->MAIN_MAIL_SENDMODE_EMAILING];
	if (empty($text)) $text=$langs->trans("Undefined").img_warning();
	print $text;
	print '</td></tr>';

	if (! empty($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && $conf->global->MAIN_MAIL_SENDMODE_EMAILING != 'default')
	{
		// Host server
		if ($linuxlike && (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && $conf->global->MAIN_MAIL_SENDMODE_EMAILING == 'mail'))
		{
			print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike").'</td><td>'.$langs->trans("SeeLocalSendMailSetup").'</td></tr>';
		}
		else
		{
			print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER", ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->MAIN_MAIL_SMTP_SERVER_EMAILING)?$conf->global->MAIN_MAIL_SMTP_SERVER_EMAILING:'').'</td></tr>';
		}

		// Port
		if ($linuxlike && (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && $conf->global->MAIN_MAIL_SENDMODE_EMAILING == 'mail'))
		{
			print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike").'</td><td>'.$langs->trans("SeeLocalSendMailSetup").'</td></tr>';
		}
		else
		{
			print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT", ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->MAIN_MAIL_SMTP_PORT_EMAILING)?$conf->global->MAIN_MAIL_SMTP_PORT_EMAILING:'').'</td></tr>';
		}

		// SMTPS ID
		if (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer')))
		{
			print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>'.$conf->global->MAIN_MAIL_SMTPS_ID_EMAILING.'</td></tr>';
		}

		// SMTPS PW
		if (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer')))
		{
			print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>'.preg_replace('/./', '*', $conf->global->MAIN_MAIL_SMTPS_PW_EMAILING).'</td></tr>';
		}

		// TLS
		print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		if (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer')))
		{
			if (function_exists('openssl_open'))
			{
				print yn($conf->global->MAIN_MAIL_EMAIL_TLS_EMAILING);
			}
			else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
		else print yn(0).' ('.$langs->trans("NotSupported").')';
		print '</td></tr>';

		// STARTTLS
		print '<tr class="oddeven hideifdefault"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
		if (isset($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && in_array($conf->global->MAIN_MAIL_SENDMODE_EMAILING, array('smtps', 'swiftmailer')))
		{
			if (function_exists('openssl_open'))
			{
				print yn($conf->global->MAIN_MAIL_EMAIL_STARTTLS_EMAILING);
			}
			else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
		else print yn(0).' ('.$langs->trans("NotSupported").')';
		print '</td></tr>';
	}

	print '</table>';

	dol_fiche_end();


    if ($conf->global->MAIN_MAIL_SENDMODE_EMAILING == 'mail' && empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA))
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


    // Buttons for actions

	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';

	if (! empty($conf->global->MAIN_MAIL_SENDMODE_EMAILING) && $conf->global->MAIN_MAIL_SENDMODE_EMAILING != 'default')
	{
		if ($conf->global->MAIN_MAIL_SENDMODE_EMAILING != 'mail' || ! $linuxlike)
		{
			if (function_exists('fsockopen') && $port && $server)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect">'.$langs->trans("DoTestServerAvailability").'</a>';
			}
		}
		else
		{
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("FeatureNotAvailableOnLinux").'">'.$langs->trans("DoTestServerAvailability").'</a>';
		}

		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&amp;mode=init">'.$langs->trans("DoTestSend").'</a>';

		if (! empty($conf->fckeditor->enabled))
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testhtml&amp;mode=init">'.$langs->trans("DoTestSendHTML").'</a>';
		}
	}

	print '</div>';


	if ($conf->global->MAIN_MAIL_SENDMODE_EMAILING == 'mail' && ! in_array($action, array('testconnect', 'test', 'testhtml')))
	{
        $text = $langs->trans("WarningPHPMail");
	    print info_admin($text);
	}

	// Run the test to connect
	if ($action == 'testconnect')
	{
		print load_fiche_titre($langs->trans("DoTestServerAvailability"));

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mail = new CMailFile('', '', '', '');
		$result=$mail->check_server_port($server, $port);
		if ($result) print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort", $server, $port).'</div>';
		else
		{
			$errormsg = $langs->trans("ServerNotAvailableOnIPOrPort", $server, $port);

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
	    print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
		print load_fiche_titre($action == 'testhtml'?$langs->trans("DoTestSendHTML"):$langs->trans("DoTestSend"));

		dol_fiche_head('');

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->fromname = (isset($_POST['fromname'])?$_POST['fromname']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->frommail = (isset($_POST['frommail'])?$_POST['frommail']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->trackid=(($action == 'testhtml')?"testhtml":"test");
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
		$formmail->param["action"]="send";
		$formmail->param["models"]="body";
		$formmail->param["mailid"]=0;
		$formmail->param["returnurl"]=$_SERVER["PHP_SELF"];

		// Init list of files
        if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
		}

		print $formmail->get_form('addfile', 'removefile');

		dol_fiche_end();
	}
}

// End of page
llxFooter();
$db->close();
