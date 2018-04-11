<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
'__DOL_MAIN_URL_ROOT__'=>DOL_MAIN_URL_ROOT,
'__ID__' => 'RecipientIdRecord',
//'__EMAIL__' => 'RecipientEMail',				// Done into actions_sendmails
'__CHECK_READ__' => (is_object($object) && is_object($object->thirdparty))?'<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$object->thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>':'',
'__USER_SIGNATURE__' => (($user->signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN))?$usersignature:''),		// Done into actions_sendmails
'__LOGIN__' => 'RecipientLogin',
'__LASTNAME__' => 'RecipientLastname',
'__FIRSTNAME__' => 'RecipientFirstname',
'__ADDRESS__'=> 'RecipientAddress',
'__ZIP__'=> 'RecipientZip',
'__TOWN_'=> 'RecipientTown',
'__COUNTRY__'=> 'RecipientCountry'
);
complete_substitutions_array($substitutionarrayfortest, $langs);



/*
 * Actions
 */

if ($action == 'update' && empty($_POST["cancel"]))
{
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS",     GETPOST("MAIN_DISABLE_ALL_MAILS"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_FORCE_SENDTO",     GETPOST("MAIN_MAIL_FORCE_SENDTO"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_ENABLED_USER_DEST_SELECT",     GETPOST("MAIN_MAIL_ENABLED_USER_DEST_SELECT"),'chaine',0,'',$conf->entity);
	// Send mode parameters
	dolibarr_set_const($db, "MAIN_MAIL_SENDMODE",         GETPOST("MAIN_MAIL_SENDMODE"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",        GETPOST("MAIN_MAIL_SMTP_PORT"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER",      GETPOST("MAIN_MAIL_SMTP_SERVER"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",         GETPOST("MAIN_MAIL_SMTPS_ID"), 'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",         GETPOST("MAIN_MAIL_SMTPS_PW"), 'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS",        GETPOST("MAIN_MAIL_EMAIL_TLS"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_STARTTLS",   GETPOST("MAIN_MAIL_EMAIL_STARTTLS"),'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_ENABLED",     GETPOST("MAIN_MAIL_EMAIL_DKIM_ENABLED"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_DOMAIN",      GETPOST("MAIN_MAIL_EMAIL_DKIM_DOMAIN"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_SELECTOR",    GETPOST("MAIN_MAIL_EMAIL_DKIM_SELECTOR"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY", GETPOST("MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY"),'chaine',0,'',$conf->entity);
	// Content parameters
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",       GETPOST("MAIN_MAIL_EMAIL_FROM"), 'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_ERRORS_TO",		  GETPOST("MAIN_MAIL_ERRORS_TO"),  'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_AUTOCOPY_TO",      GETPOST("MAIN_MAIL_AUTOCOPY_TO"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, 'MAIN_MAIL_DEFAULT_FROMTYPE', GETPOST('MAIN_MAIL_DEFAULT_FROMTYPE'),'chaine',0,'',$conf->entity);

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
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

if ($action == 'presend' && GETPOST('trackid') == 'test')       $action='test';
if ($action == 'presend' && GETPOST('trackid') == 'testhtml')   $action='testhtml';




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


$wikihelp='EN:Setup_EMails|FR:Paramétrage_EMails|ES:Configuración_EMails';
llxHeader('',$langs->trans("Setup"),$wikihelp);

print load_fiche_titre($langs->trans("EMailsSetup"),'','title_setup');

$head = email_admin_prepare_head();

// List of sending methods
$listofmethods=array();
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
                        if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'mail\')
                        {
							console.log("I choose php mail mode");
                            jQuery(".drag").hide();
                            jQuery("#MAIN_MAIL_EMAIL_TLS").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_TLS").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_DOMAIN").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_SELECTOR").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").prop("disabled", true);
                            jQuery(".dkim").hide();
                            ';
		if ($linuxlike)
		{
			print '
                            jQuery("#MAIN_MAIL_SMTP_SERVER").hide();
                            jQuery("#MAIN_MAIL_SMTP_PORT").hide();
                            jQuery("#smtp_server_mess").show();
                            jQuery("#smtp_port_mess").show();';
		}
		else
		{
            print '
                            jQuery("#MAIN_MAIL_SMTP_SERVER").prop("disabled", true);
                            jQuery("#MAIN_MAIL_SMTP_PORT").prop("disabled", true);
                            jQuery("#smtp_server_mess").hide();
                            jQuery("#smtp_port_mess").hide();';
        }
        print '
                        }
                        if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'smtps\')
                        {
							console.log("I choose smtps mode");
                            jQuery(".drag").show();
                            jQuery("#MAIN_MAIL_EMAIL_TLS").val('.$conf->global->MAIN_MAIL_EMAIL_TLS.');
                            jQuery("#MAIN_MAIL_EMAIL_TLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val('.$conf->global->MAIN_MAIL_EMAIL_STARTTLS.');
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_DOMAIN").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_SELECTOR").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_DOMAIN").hide();
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_SELECTOR").hide();
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").hide();
                            jQuery("#MAIN_MAIL_SMTP_SERVER").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_PORT").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER").show();
                            jQuery("#MAIN_MAIL_SMTP_PORT").show();
                            jQuery("#smtp_server_mess").hide();
			                jQuery("#smtp_port_mess").hide();
                            jQuery(".dkim").hide();
						}
                        if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'swiftmailer\')
                        {
							console.log("I choose swiftmailer mode");
                            jQuery(".drag").show();
                            jQuery("#MAIN_MAIL_EMAIL_TLS").val('.$conf->global->MAIN_MAIL_EMAIL_TLS.');
                            jQuery("#MAIN_MAIL_EMAIL_TLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val('.$conf->global->MAIN_MAIL_EMAIL_STARTTLS.');
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").val('.$conf->global->MAIN_MAIL_EMAIL_DKIM_ENABLED.');
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_DOMAIN").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_SELECTOR").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_DOMAIN").show();
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_SELECTOR").show();
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").show();
                            jQuery("#MAIN_MAIL_SMTP_SERVER").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_PORT").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_SMTP_SERVER").show();
                            jQuery("#MAIN_MAIL_SMTP_PORT").show();
                            jQuery("#smtp_server_mess").hide();
                            jQuery("#smtp_port_mess").hide();
                            jQuery(".dkim").show();
                        }
                    }
                    initfields();
                    jQuery("#MAIN_MAIL_SENDMODE").change(function() {
                        initfields();
                    });
                    jQuery("#MAIN_MAIL_EMAIL_TLS").change(function() {
						if (jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val() == 1)
							jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val(0);
					});
					jQuery("#MAIN_MAIL_EMAIL_STARTTLS").change(function() {
						if (jQuery("#MAIN_MAIL_EMAIL_TLS").val() == 1)
							jQuery("#MAIN_MAIL_EMAIL_TLS").val(0);
                    });
               })';
        print '</script>'."\n";
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	dol_fiche_head($head, 'common', '', -1);

	print $langs->trans("EMailsDesc")."<br>\n";
	print "<br>\n";


	clearstatcache();
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>';
	print $form->selectyesno('MAIN_DISABLE_ALL_MAILS',$conf->global->MAIN_DISABLE_ALL_MAILS,1);
	print '</td></tr>';

	// Force e-mail recipient
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_FORCE_SENDTO").'</td><td>';
	print '<input class="flat" name="MAIN_MAIL_FORCE_SENDTO" size="32" value="' . (! empty($conf->global->MAIN_MAIL_FORCE_SENDTO)?$conf->global->MAIN_MAIL_FORCE_SENDTO:'') . '" />';
	print '</td></tr>';


	//Add user to select destinaries list
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ENABLED_USER_DEST_SELECT").'</td><td>';
	print $form->selectyesno('MAIN_MAIL_ENABLED_USER_DEST_SELECT',$conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT,1);
	print '</td></tr>';

	// Separator

	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// Method

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';

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
	print '<tr class="oddeven"><td>';
	if (! $conf->use_javascript_ajax && $linuxlike && $conf->global->MAIN_MAIL_SENDMODE == 'mail')
	{
		print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		print '</td><td>';
		print '<span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
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
			print '<span id="smtp_server_mess" class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
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

	print '<tr class="oddeven"><td>';
	if (! $conf->use_javascript_ajax && $linuxlike && $conf->global->MAIN_MAIL_SENDMODE == 'mail')
	{
		print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		print '</td><td>';
		print '<span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
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
			print '<span id="smtp_port_mess" class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
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
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer'))))
	{

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
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer'))))
	{

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

    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer'))))
	{
		if (function_exists('openssl_open'))
		{
			print $form->selectyesno('MAIN_MAIL_EMAIL_TLS',(! empty($conf->global->MAIN_MAIL_EMAIL_TLS)?$conf->global->MAIN_MAIL_EMAIL_TLS:0),1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	// STARTTLS
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer'))))
	{
		if (function_exists('openssl_open'))
		{
			print $form->selectyesno('MAIN_MAIL_EMAIL_STARTTLS',(! empty($conf->global->MAIN_MAIL_EMAIL_STARTTLS)?$conf->global->MAIN_MAIL_EMAIL_STARTTLS:0),1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	// DKIM
	print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_ENABLED").'</td><td>';
	if (! empty($conf->use_javascript_ajax) || (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('swiftmailer'))))
	{
		if (function_exists('openssl_open'))
		{
			print $form->selectyesno('MAIN_MAIL_EMAIL_DKIM_ENABLED',(! empty($conf->global->MAIN_MAIL_EMAIL_DKIM_ENABLED)?$conf->global->MAIN_MAIL_EMAIL_DKIM_ENABLED:0),1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

    // DKIM Domain
    print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_DOMAIN").'</td>';
    print '<td><input class="flat" id="MAIN_MAIL_EMAIL_DKIM_DOMAIN" name="MAIN_MAIL_EMAIL_DKIM_DOMAIN" size="32" value="' . (! empty($conf->global->MAIN_MAIL_EMAIL_DKIM_DOMAIN)?$conf->global->MAIN_MAIL_EMAIL_DKIM_DOMAIN:'');
    print '"></td></tr>';

    // DKIM Selector
    print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_SELECTOR").'</td>';
    print '<td><input class="flat" id="MAIN_MAIL_EMAIL_DKIM_SELECTOR" name="MAIN_MAIL_EMAIL_DKIM_SELECTOR" size="32" value="' . (! empty($conf->global->MAIN_MAIL_EMAIL_DKIM_SELECTOR)?$conf->global->MAIN_MAIL_EMAIL_DKIM_SELECTOR:'');
    print '"></td></tr>';

    // DKIM PRIVATE KEY
    print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").'</td>';
    print '<td><textarea id="MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY" name="MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY" rows="15" cols="100">' . (! empty($conf->global->MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY)?$conf->global->MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY:'').'</textarea>';
    print '</td></tr>';

    // Separator
	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// From

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_EMAIL_FROM" size="32" value="' . (! empty($conf->global->MAIN_MAIL_EMAIL_FROM)?$conf->global->MAIN_MAIL_EMAIL_FROM:'');
	print '"></td></tr>';

	// Default from type
	$liste = array();
	$liste['user'] = $langs->trans('UserEmail');
	$liste['company'] = $langs->trans('CompanyEmail').' ('.(empty($conf->global->MAIN_INFO_SOCIETE_MAIL)?$langs->trans("NotDefined"):$conf->global->MAIN_INFO_SOCIETE_MAIL).')';
	/*
	$sql='SELECT rowid, label, email FROM '.MAIN_DB_PREFIX.'c_email_senderprofile WHERE active = 1';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i=0;
		while($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				$liste['senderprofile_'.$obj->rowid] = $obj->label.' <'.$obj->email.'>';
			}
			$i++;
		}
	}
	else dol_print_error($db);*/

	print '<tr '.$bc[$var?1:0].'><td>'.$langs->trans('MAIN_MAIL_DEFAULT_FROMTYPE').'</td><td>';
	print $form->selectarray('MAIN_MAIL_DEFAULT_FROMTYPE', $liste, $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE, 0);
	print '</td></tr>';

	// Separator

	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// From

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ERRORS_TO").'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_ERRORS_TO" size="32" value="' . (! empty($conf->global->MAIN_MAIL_ERRORS_TO)?$conf->global->MAIN_MAIL_ERRORS_TO:'');
	print '"></td></tr>';

	// Autocopy to

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td><input class="flat" name="MAIN_MAIL_AUTOCOPY_TO" size="32" value="' . (! empty($conf->global->MAIN_MAIL_AUTOCOPY_TO)?$conf->global->MAIN_MAIL_AUTOCOPY_TO:'');
	print '"></td></tr>';

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
	dol_fiche_head($head, 'common', '', -1);

	print $langs->trans("EMailsDesc")."<br>\n";
	print "<br>\n";


	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_MAILS).'</td></tr>';

	// Force e-mail recipient
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_FORCE_SENDTO").'</td><td>'.$conf->global->MAIN_MAIL_FORCE_SENDTO;
	if (! empty($conf->global->MAIN_MAIL_FORCE_SENDTO) && ! isValidEmail($conf->global->MAIN_MAIL_FORCE_SENDTO)) print img_warning($langs->trans("ErrorBadEMail"));
	print '</td></tr>';

	//Add user to select destinaries list
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ENABLED_USER_DEST_SELECT").'</td><td>'.yn($conf->global->MAIN_MAIL_ENABLED_USER_DEST_SELECT).'</td></tr>';

	// Separator

	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// Method

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';
	$text=$listofmethods[$conf->global->MAIN_MAIL_SENDMODE];
	if (empty($text)) $text=$langs->trans("Undefined").img_warning();
	print $text;
	print '</td></tr>';

	// Host server

	if ($linuxlike && (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'mail'))
	{
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike").'</td><td><span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span></td></tr>';
	}
	else
	{
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER",ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->MAIN_MAIL_SMTP_SERVER)?$conf->global->MAIN_MAIL_SMTP_SERVER:'').'</td></tr>';
	}

	// Port

	if ($linuxlike && (isset($conf->global->MAIN_MAIL_SENDMODE) && $conf->global->MAIN_MAIL_SENDMODE == 'mail'))
	{
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike").'</td><td><span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span></td></tr>';
	}
	else
	{
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT",ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined")).'</td><td>'.(! empty($conf->global->MAIN_MAIL_SMTP_PORT)?$conf->global->MAIN_MAIL_SMTP_PORT:'').'</td></tr>';
	}

	// SMTPS ID

	if (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer')))
	{
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>'.$conf->global->MAIN_MAIL_SMTPS_ID.'</td></tr>';
	}

	// SMTPS PW

	if (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer')))
	{
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>'.preg_replace('/./','*',$conf->global->MAIN_MAIL_SMTPS_PW).'</td></tr>';
	}

	// TLS

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer')))
	{
		if (function_exists('openssl_open'))
		{
			print yn($conf->global->MAIN_MAIL_EMAIL_TLS);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print '<span class="opacitymedium">'.yn(0).' ('.$langs->trans("NotSupported").')</span>';
	print '</td></tr>';

	// STARTTLS

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
	if (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('smtps', 'swiftmailer')))
	{
		if (function_exists('openssl_open'))
		{
			print yn($conf->global->MAIN_MAIL_EMAIL_STARTTLS);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print '<span class="opacitymedium">'.yn(0).' ('.$langs->trans("NotSupported").')</span>';
	print '</td></tr>';


	if ($conf->global->MAIN_MAIL_SENDMODE == 'swiftmailer')
	{
		// DKIM

		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_ENABLED").'</td><td>';
		if (isset($conf->global->MAIN_MAIL_SENDMODE) && in_array($conf->global->MAIN_MAIL_SENDMODE, array('swiftmailer')))
		{
			if (function_exists('openssl_open'))
			{
				print yn($conf->global->MAIN_MAIL_EMAIL_DKIM_ENABLED);
			}
			else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
		else print yn(0).' ('.$langs->trans("NotSupported").')';
		print '</td></tr>';

	    // Domain
	    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_DOMAIN").'</td>';
	    print '<td>' . $conf->global->MAIN_MAIL_EMAIL_DKIM_DOMAIN;
	    print '</td></tr>';

	    // Selector
	    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_SELECTOR").'</td>';
	    print '<td>' . $conf->global->MAIN_MAIL_EMAIL_DKIM_SELECTOR;
	    print '</td></tr>';

	    // PRIVATE KEY
	    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").'</td>';
	    print '<td>' . $conf->global->MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY;
	    print '</td></tr>';
	}

    // Separator

	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// From

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td>';
	print '<td>'.$conf->global->MAIN_MAIL_EMAIL_FROM;
	if (! empty($conf->global->MAIN_MAIL_EMAIL_FROM) && ! isValidEmail($conf->global->MAIN_MAIL_EMAIL_FROM)) print img_warning($langs->trans("ErrorBadEMail"));
	print '</td></tr>';

	// Default from type
	$liste = array();
	$liste['user'] = $langs->trans('UserEmail');
	$liste['company'] = $langs->trans('CompanyEmail').' ('.(empty($conf->global->MAIN_INFO_SOCIETE_MAIL)?$langs->trans("NotDefined"):$conf->global->MAIN_INFO_SOCIETE_MAIL).')';
	$sql='SELECT rowid, label, email FROM '.MAIN_DB_PREFIX.'c_email_senderprofile WHERE active = 1';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i=0;
		while($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				$liste['senderprofile_'.$obj->rowid] = $obj->label.' <'.$obj->email.'>';
			}
			$i++;
		}
	}
	else dol_print_error($db);

	print '<tr class="oddeven"><td>'.$langs->trans('MAIN_MAIL_DEFAULT_FROMTYPE').'</td>';
	print '<td>';
	if ($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE === 'robot')
	{
		print $langs->trans('RobotEmail');
	}
	else if ($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE === 'user')
	{
		print $langs->trans('UserEmail');
	}
	else if ($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE === 'company')
	{
		print $langs->trans('CompanyEmail').' '.dol_escape_htmltag('<'.$mysoc->email.'>');
	}
	else {
		$id = preg_replace('/senderprofile_/', '', $conf->global->MAIN_MAIL_DEFAULT_FROMTYPE);
		if ($id > 0)
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/emailsenderprofile.class.php';
			$emailsenderprofile = new EmailSenderProfile($db);
			$emailsenderprofile->fetch($id);
			print $emailsenderprofile->label.' '.dol_escape_htmltag('<'.$emailsenderprofile->email.'>');
		}
	}
	print '</td></tr>';

	// Separator

	print '<tr class="oddeven"><td colspan="2">&nbsp;</td></tr>';

	// Errors To

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ERRORS_TO").'</td>';
	print '<td>'.$conf->global->MAIN_MAIL_ERRORS_TO;
	if (! empty($conf->global->MAIN_MAIL_ERRORS_TO) && ! isValidEmail($conf->global->MAIN_MAIL_ERRORS_TO)) print img_warning($langs->trans("ErrorBadEMail"));
	print '</td></tr>';

	// Autocopy to

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
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

	dol_fiche_end();


	// Actions button
	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';

	if ($conf->global->MAIN_MAIL_SENDMODE != 'mail' || ! $linuxlike)
	{
		if (function_exists('fsockopen') && $port && $server)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect#formmailbeforetitle">'.$langs->trans("DoTestServerAvailability").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("FeatureNotAvailableOnLinux").'">'.$langs->trans("DoTestServerAvailability").'</a>';
	}

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&mode=init#formmailbeforetitle">'.$langs->trans("DoTestSend").'</a>';

	if (! empty($conf->fckeditor->enabled))
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testhtml&mode=init#formmailbeforetitle">'.$langs->trans("DoTestSendHTML").'</a>';
	}

	print '</div>';


	if ($conf->global->MAIN_MAIL_SENDMODE == 'mail' && empty($conf->global->MAIN_FIX_FOR_BUGGED_MTA))
	{
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

	if (! in_array($action, array('testconnect', 'test', 'testhtml')))
	{
		$text = '';
		if ($conf->global->MAIN_MAIL_SENDMODE == 'mail')
		{
			$text.= $langs->trans("WarningPHPMail");
		}
		//$conf->global->MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS='1.2.3.4';
		if (! empty($conf->global->MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS))
		{
			$text.= ($text?'<br>':'').$langs->trans("WarningPHPMail2", $conf->global->MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS);
		}
		if ($text) print info_admin($text);
	}

	// Run the test to connect
	if ($action == 'testconnect')
	{
		print load_fiche_titre($langs->trans("DoTestServerAvailability"));

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
		print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
		print load_fiche_titre($action == 'testhtml'?$langs->trans("DoTestSendHTML"):$langs->trans("DoTestSend"));

		dol_fiche_head('');

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->trackid=(($action == 'testhtml')?"testhtml":"test");
		$formmail->fromname = (isset($_POST['fromname'])?$_POST['fromname']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->frommail = (isset($_POST['frommail'])?$_POST['frommail']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->fromid=$user->id;
		$formmail->fromalsorobot=1;
		$formmail->fromtype=(GETPOST('fromtype')?GETPOST('fromtype'):(!empty($conf->global->MAIN_MAIL_DEFAULT_FROMTYPE)?$conf->global->MAIN_MAIL_DEFAULT_FROMTYPE:'user'));
		$formmail->withfromreadonly=1;
		$formmail->withsubstit=1;
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

		print $formmail->get_form('addfile','removefile');

		dol_fiche_end();
	}
}


llxFooter();

$db->close();
