<?php
/* Copyright (C) 2007-2020	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2016		Jonathan TISSEAU			<jonathan.tisseau@86dev.fr>
 * Copyright (C) 2023		Anthony Berton				<anthony.berton@bb2a.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/admin/mails.php
 *       \brief      Page to setup emails sending
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "products", "admin", "mails", "other", "errors"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

$trackid = GETPOST('trackid');

if (!$user->admin) {
	accessforbidden();
}

$usersignature = $user->signature;
// For action = test or send, we ensure that content is not html, even for signature, because for this we want a test with NO html.
if ($action == 'test' || ($action == 'send' && $trackid = 'test')) {
	$usersignature = dol_string_nohtmltag($usersignature, 2);
}

$substitutionarrayfortest = array(
	'__USER_LOGIN__' => $user->login,
	'__USER_EMAIL__' => $user->email,
	'__USER_FIRSTNAME__' => $user->firstname,
	'__USER_LASTNAME__' => $user->lastname,
	'__USER_SIGNATURE__' => (($user->signature && !getDolGlobalString('MAIN_MAIL_DO_NOT_USE_SIGN')) ? $usersignature : ''), // Done into actions_sendmails
	'__SENDEREMAIL_SIGNATURE__' => (($user->signature && !getDolGlobalString('MAIN_MAIL_DO_NOT_USE_SIGN')) ? $usersignature : ''), // Done into actions_sendmails
	//'__ID__' => 'RecipientID',
	//'__EMAIL__' => 'RecipientEMail',				// Done into actions_sendmails
	'__LASTNAME__' => $langs->trans("Lastname").' ('.$langs->trans("Recipient").')',
	'__FIRSTNAME__' => $langs->trans("Firstname").' ('.$langs->trans("Recipient").')',
	//'__ADDRESS__'=> $langs->trans("Address").' ('.$langs->trans("Recipient").')',
	//'__ZIP__'=> $langs->trans("Zip").' ('.$langs->trans("Recipient").')',
	//'__TOWN_'=> $langs->trans("Town").' ('.$langs->trans("Recipient").')',
	//'__COUNTRY__'=> $langs->trans("Country").' ('.$langs->trans("Recipient").')',
	'__DOL_MAIN_URL_ROOT__' => DOL_MAIN_URL_ROOT,
	'__CHECK_READ__' => '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag=undefinedtag&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY')."-undefinedtag", 'md5').'" width="1" height="1" style="width:1px;height:1px" border="0" />',
);
complete_substitutions_array($substitutionarrayfortest, $langs);



/*
 * Actions
 */

if ($action == 'update' && !$cancel) {
	if (!$error && !GETPOST("MAIN_MAIL_EMAIL_FROM", 'alphanohtml')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MAIN_MAIL_EMAIL_FROM")), null, 'errors');
		$action = 'edit';
	}
	if (!$error && !isValidEmail(GETPOST("MAIN_MAIL_EMAIL_FROM", 'alphanohtml'))) {
		$error++;
		setEventMessages($langs->trans("ErrorBadEMail", GETPOST("MAIN_MAIL_EMAIL_FROM", 'alphanohtml')), null, 'errors');
		$action = 'edit';
	}

	if (!$error) {
		dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS", GETPOSTINT("MAIN_DISABLE_ALL_MAILS"), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_FORCE_SENDTO", GETPOST("MAIN_MAIL_FORCE_SENDTO", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_ENABLED_USER_DEST_SELECT", GETPOSTINT("MAIN_MAIL_ENABLED_USER_DEST_SELECT"), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MAIN_MAIL_NO_WITH_TO_SELECTED', GETPOSTINT('MAIN_MAIL_NO_WITH_TO_SELECTED'), 'chaine', 0, '', $conf->entity);
		// Send mode parameters
		dolibarr_set_const($db, "MAIN_MAIL_SENDMODE", GETPOST("MAIN_MAIL_SENDMODE", 'aZ09'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT", GETPOSTINT("MAIN_MAIL_SMTP_PORT"), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER", GETPOST("MAIN_MAIL_SMTP_SERVER", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID", GETPOST("MAIN_MAIL_SMTPS_ID", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		if (GETPOSTISSET("MAIN_MAIL_SMTPS_PW")) {
			dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW", GETPOST("MAIN_MAIL_SMTPS_PW", 'password'), 'chaine', 0, '', $conf->entity);
		}
		if (GETPOSTISSET("MAIN_MAIL_SMTPS_AUTH_TYPE")) {
			dolibarr_set_const($db, "MAIN_MAIL_SMTPS_AUTH_TYPE", GETPOST("MAIN_MAIL_SMTPS_AUTH_TYPE", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		}
		if (GETPOSTISSET("MAIN_MAIL_SMTPS_OAUTH_SERVICE")) {
			dolibarr_set_const($db, "MAIN_MAIL_SMTPS_OAUTH_SERVICE", GETPOST("MAIN_MAIL_SMTPS_OAUTH_SERVICE", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		}
		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS", GETPOSTINT("MAIN_MAIL_EMAIL_TLS"), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_STARTTLS", GETPOSTINT("MAIN_MAIL_EMAIL_STARTTLS"), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED", GETPOSTINT("MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED"), 'chaine', 0, '', $conf->entity);

		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_ENABLED", GETPOSTINT("MAIN_MAIL_EMAIL_DKIM_ENABLED"), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_DOMAIN", GETPOST("MAIN_MAIL_EMAIL_DKIM_DOMAIN", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_SELECTOR", GETPOST("MAIN_MAIL_EMAIL_DKIM_SELECTOR", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY", GETPOST("MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		// Content parameters
		dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM", GETPOST("MAIN_MAIL_EMAIL_FROM", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_ERRORS_TO", GETPOST("MAIN_MAIL_ERRORS_TO", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "MAIN_MAIL_AUTOCOPY_TO", GETPOST("MAIN_MAIL_AUTOCOPY_TO", 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MAIN_MAIL_DEFAULT_FROMTYPE', GETPOST('MAIN_MAIL_DEFAULT_FROMTYPE', 'alphanohtml'), 'chaine', 0, '', $conf->entity);


		header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
		exit;
	}
}

if ($action == 'disablephpmailwarning' && !$cancel) {
	dolibarr_set_const($db, 'MAIN_HIDE_WARNING_TO_ENCOURAGE_SMTP_SETUP', 1, 'chaine', 1, '', $conf->entity);

	setEventMessages($langs->trans("WarningDisabled"), null, 'mesgs');
}

// Actions to send emails
$id = 0;
$actiontypecode = ''; // Not an event for agenda
$triggersendname = ''; // Disable triggers
$paramname = 'id';
$mode = 'emailfortest';
$trackid = ($action == 'send' ? GETPOST('trackid', 'aZ09') : $action);
$sendcontext = 'standard';
include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

if ($action == 'presend' && GETPOST('trackid', 'alphanohtml') == 'test') {
	$action = 'test';
}
if ($action == 'presend' && GETPOST('trackid', 'alphanohtml') == 'testhtml') {
	$action = 'testhtml';
}


/*
 * View
 */

$form = new Form($db);

// Set default variables
$linuxlike = 1;
if (preg_match('/^win/i', PHP_OS)) {
	$linuxlike = 0;
}
if (preg_match('/^mac/i', PHP_OS)) {
	$linuxlike = 0;
}

if (!getDolGlobalString('MAIN_MAIL_SENDMODE')) {
	$conf->global->MAIN_MAIL_SENDMODE = 'mail';
}

$port = getDolGlobalInt('MAIN_MAIL_SMTP_PORT', (int) ini_get('smtp_port'));
if (!$port) {
	$port = 25;
}
$server = getDolGlobalString('MAIN_MAIL_SMTP_SERVER', ini_get('SMTP'));
if (!$server) {
	$server = '127.0.0.1';
}


$wikihelp = 'EN:Setup_EMails|FR:Paramétrage_EMails|ES:Configuración_EMails';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-mails');

print load_fiche_titre($langs->trans("EMailsSetup"), '', 'title_setup');

$head = email_admin_prepare_head();

// List of sending methods
$listofmethods = array();
$listofmethods['mail'] = 'PHP mail function';
$listofmethods['smtps'] = 'SMTP/SMTPS socket library';
if (version_compare(phpversion(), '7.0', '>=')) {
	$listofmethods['swiftmailer'] = 'Swift Mailer socket library';
}

// List of oauth services
$oauthservices = array();

foreach ($conf->global as $key => $val) {
	if (!empty($val) && preg_match('/^OAUTH_.*_ID$/', $key)) {
		$key = preg_replace('/^OAUTH_/', '', $key);
		$key = preg_replace('/_ID$/', '', $key);
		if (preg_match('/^.*-/', $key)) {
			$name = preg_replace('/^.*-/', '', $key);
		} else {
			$name = $langs->trans("NoName");
		}
		$provider = preg_replace('/-.*$/', '', $key);
		$provider = ucfirst(strtolower($provider));

		$oauthservices[$key] = $name." (".$provider.")";
	}
}

if ($action == 'edit') {
	if ($conf->use_javascript_ajax) {
		print "\n".'<script type="text/javascript">';
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
                            jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").val(0);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_DOMAIN").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_SELECTOR").prop("disabled", true);
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").prop("disabled", true);
                            jQuery(".smtp_method").hide();
                            jQuery(".dkim").hide();
                            jQuery(".smtp_auth_method").hide();
                            ';
		if ($linuxlike) {
			print '
                            jQuery("#MAIN_MAIL_SMTP_SERVER").hide();
                            jQuery("#MAIN_MAIL_SMTP_PORT").hide();
                            jQuery("#smtp_server_mess").show();
                            jQuery("#smtp_port_mess").show();';
		} else {
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
                            jQuery("#MAIN_MAIL_EMAIL_TLS").val(' . getDolGlobalString('MAIN_MAIL_EMAIL_TLS').');
                            jQuery("#MAIN_MAIL_EMAIL_TLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val(' . getDolGlobalString('MAIN_MAIL_EMAIL_STARTTLS').');
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").val(' . getDolGlobalString('MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED').');
                            jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").removeAttr("disabled");
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
                            jQuery(".smtp_method").show();
							jQuery(".dkim").hide();
                            jQuery(".smtp_auth_method").show();
						}
                        if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'swiftmailer\')
                        {
							console.log("I choose swiftmailer mode");
                            jQuery(".drag").show();
                            jQuery("#MAIN_MAIL_EMAIL_TLS").val(' . getDolGlobalString('MAIN_MAIL_EMAIL_TLS').');
                            jQuery("#MAIN_MAIL_EMAIL_TLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val(' . getDolGlobalString('MAIN_MAIL_EMAIL_STARTTLS').');
                            jQuery("#MAIN_MAIL_EMAIL_STARTTLS").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").val(' . getDolGlobalString('MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED').');
                            jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").removeAttr("disabled");
                            jQuery("#MAIN_MAIL_EMAIL_DKIM_ENABLED").val(' . getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_ENABLED').');
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
							jQuery(".smtp_method").show();
                            jQuery(".dkim").show();
							jQuery(".smtp_auth_method").show();
                        }
                    }
					function change_smtp_auth_method() {
						console.log("Call smtp auth method");
						if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'smtps\') {
							if (jQuery("#radio_oauth").prop("checked")) {
								jQuery(".smtp_pw").hide();
								jQuery(".smtp_oauth_service").show();
							} else {
								jQuery(".smtp_pw").show();
								jQuery(".smtp_oauth_service").hide();
							}
						} else if (jQuery("#MAIN_MAIL_SENDMODE").val()==\'swiftmailer\') {
							if (jQuery("#radio_oauth").prop("checked")) {
								jQuery(".smtp_pw").hide();
								jQuery(".smtp_oauth_service").show();
							} else {
								jQuery(".smtp_pw").show();
								jQuery(".smtp_oauth_service").hide();
							}
						} else {
							jQuery(".smtp_pw").show();
							jQuery(".smtp_oauth_service").hide();
						}
					}
                    initfields();
					change_smtp_auth_method();
                    jQuery("#MAIN_MAIL_SENDMODE").change(function() {
                        initfields();
						change_smtp_auth_method();
                    });
					jQuery("#radio_pw, #radio_plain, #radio_oauth").change(function() {
						change_smtp_auth_method();
					});
                    jQuery("#MAIN_MAIL_EMAIL_TLS").change(function() {
						if (jQuery("#MAIN_MAIL_EMAIL_TLS").val() == 1)
							jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val(0);
						else
							jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").val(0);
					});
					jQuery("#MAIN_MAIL_EMAIL_STARTTLS").change(function() {
						if (jQuery("#MAIN_MAIL_EMAIL_STARTTLS").val() == 1)
							jQuery("#MAIN_MAIL_EMAIL_TLS").val(0);
						else
							jQuery("#MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").val(0);
                    });
               })';
		print '</script>'."\n";
	}

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print dol_get_fiche_head($head, 'common', '', -1);

	print '<span class="opacitymedium">'.$langs->trans("EMailsDesc")."</span><br>\n";
	print "<br><br>\n";


	clearstatcache();


	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td></td></tr>';

	// Method
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';

	// SuperAdministrator access only
	if (!isModEnabled('multicompany')  || ($user->admin && !$user->entity)) {
		print $form->selectarray('MAIN_MAIL_SENDMODE', $listofmethods, getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'));
	} else {
		$text = $listofmethods[getDolGlobalString('MAIN_MAIL_SENDMODE')];
		if (empty($text)) {
			$text = $langs->trans("Undefined");
		}
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $form->textwithpicto($text, $htmltext, 1, 'superadmin');
		print '<input type="hidden" name="MAIN_MAIL_SENDMODE" value="'.getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail').'">';
	}
	print '</td></tr>';

	// Host server
	print '<tr class="oddeven hideonmodemail">';
	if (!$conf->use_javascript_ajax && $linuxlike && getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail') {
		print '<td>';
		print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		print '</td><td>';
		print '<span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
		print '</td>';
	} else {
		print '<td>';
		$mainserver = (getDolGlobalString('MAIN_MAIL_SMTP_SERVER') ? $conf->global->MAIN_MAIL_SMTP_SERVER : '');
		$smtpserver = ini_get('SMTP') ? ini_get('SMTP') : $langs->transnoentities("Undefined");
		if ($linuxlike) {
			print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		} else {
			print $langs->trans("MAIN_MAIL_SMTP_SERVER", $smtpserver);
		}
		print '</td><td>';
		// SuperAdministrator access only
		if (!isModEnabled('multicompany') || ($user->admin && !$user->entity)) {
			print '<input class="flat minwidth300" id="MAIN_MAIL_SMTP_SERVER" name="MAIN_MAIL_SMTP_SERVER" value="'.$mainserver.'" autocomplete="off">';
			print '<input type="hidden" id="MAIN_MAIL_SMTP_SERVER_sav" name="MAIN_MAIL_SMTP_SERVER_sav" value="'.$mainserver.'">';
			print '<span id="smtp_server_mess" class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
			print ' <span class="opacitymedium smtp_method">'.$langs->trans("SeeLinkToOnlineDocumentation").'</span>';
		} else {
			$text = !empty($mainserver) ? $mainserver : $smtpserver;
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($text, $htmltext, 1, 'superadmin');
			print '<input type="hidden" id="MAIN_MAIL_SMTP_SERVER" name="MAIN_MAIL_SMTP_SERVER" value="'.$mainserver.'">';
		}
		print '</td>';
	}
	print '</tr>';

	// Port
	print '<tr class="oddeven hideonmodemail"><td>';
	if (!$conf->use_javascript_ajax && $linuxlike && getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail') {
		print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		print '</td><td>';
		print '<span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
	} else {
		$mainport = (getDolGlobalString('MAIN_MAIL_SMTP_PORT') ? $conf->global->MAIN_MAIL_SMTP_PORT : '');
		$smtpport = ini_get('smtp_port') ? ini_get('smtp_port') : $langs->transnoentities("Undefined");
		if ($linuxlike) {
			print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		} else {
			print $langs->trans("MAIN_MAIL_SMTP_PORT", $smtpport);
		}
		print '</td><td>';
		// SuperAdministrator access only
		if (!isModEnabled('multicompany') || ($user->admin && !$user->entity)) {
			print '<input class="flat" id="MAIN_MAIL_SMTP_PORT" name="MAIN_MAIL_SMTP_PORT" size="3" value="'.$mainport.'">';
			print '<input type="hidden" id="MAIN_MAIL_SMTP_PORT_sav" name="MAIN_MAIL_SMTP_PORT_sav" value="'.$mainport.'">';
			print '<span id="smtp_port_mess" class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span>';
		} else {
			$text = (!empty($mainport) ? $mainport : $smtpport);
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($text, $htmltext, 1, 'superadmin');
			print '<input type="hidden" id="MAIN_MAIL_SMTP_PORT" name="MAIN_MAIL_SMTP_PORT" value="'.$mainport.'">';
		}
	}
	print '</td></tr>';

	// Auth mode
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')))) {
		print '<tr class="oddeven smtp_auth_method"><td>'.$langs->trans("MAIN_MAIL_SMTPS_AUTH_TYPE").'</td><td>';
		$vartosmtpstype = 'MAIN_MAIL_SMTPS_AUTH_TYPE';
		if (!isModEnabled('multicompany') || ($user->admin && !$user->entity)) {
			// Note: Default value for MAIN_MAIL_SMTPS_AUTH_TYPE if not defined is 'LOGIN' (but login/pass may be empty and they won't be provided in such a case)
			print '<input type="radio" id="radio_pw" name="'.$vartosmtpstype.'" value="LOGIN"'.(getDolGlobalString($vartosmtpstype, 'LOGIN') == 'LOGIN' ? ' checked' : '').'> ';
			print '<label for="radio_pw" >'.$langs->trans("UseAUTHLOGIN").'</label>';
			print '<br>';
			print '<input type="radio" id="radio_plain" name="'.$vartosmtpstype.'" value="PLAIN"'.(getDolGlobalString($vartosmtpstype, 'PLAIN') == 'PLAIN' ? ' checked' : '').'> ';
			print '<label for="radio_plain" >'.$langs->trans("UseAUTHPLAIN").'</label>';
			print '<br>';
			print '<input type="radio" id="radio_oauth" name="'.$vartosmtpstype.'" value="XOAUTH2"'.(getDolGlobalString($vartosmtpstype) == 'XOAUTH2' ? ' checked' : '').(isModEnabled('oauth') ? '' : ' disabled').'> ';
			print '<label for="radio_oauth" >'.$form->textwithpicto($langs->trans("UseOauth"), $langs->trans("OauthNotAvailableForAllAndHadToBeCreatedBefore")).'</label>';
			if (!isModEnabled('oauth')) {
				print ' &nbsp; <a href="'.DOL_URL_ROOT.'/admin/modules.php?search_keyword=oauth">'.$langs->trans("EnableModuleX", "OAuth").'</a>';
			} else {
				print ' &nbsp; <a href="'.DOL_URL_ROOT.'/admin/oauth.php">'.$langs->trans("SetupModuleX", " OAuth").'</a>';
			}
		} else {
			$value = getDolGlobalString($vartosmtpstype, 'LOGIN');
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($langs->trans("MAIN_MAIL_SMTPS_AUTH_TYPE"), $htmltext, 1, 'superadmin');
			print '<input type="hidden" id="MAIN_MAIL_SMTPS_AUTH_TYPE" name="MAIN_MAIL_SMTPS_AUTH_TYPE" value="'.$value.'">';
		}
		print '</td></tr>';
	}

	// ID
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')))) {
		$mainstmpid = (getDolGlobalString('MAIN_MAIL_SMTPS_ID') ? $conf->global->MAIN_MAIL_SMTPS_ID : '');
		print '<tr class="drag drop oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>';
		// SuperAdministrator access only
		if (!isModEnabled('multicompany') || ($user->admin && !$user->entity)) {
			print '<input class="flat" name="MAIN_MAIL_SMTPS_ID" size="32" value="'.$mainstmpid.'">';
		} else {
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($conf->global->MAIN_MAIL_SMTPS_ID, $htmltext, 1, 'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_ID" value="'.$mainstmpid.'">';
		}
		print '</td></tr>';
	}


	// PW
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')))) {
		$mainsmtppw = getDolGlobalString('MAIN_MAIL_SMTPS_PW');
		print '<tr class="drag drop oddeven smtp_pw"><td>';
		print $form->textwithpicto($langs->trans("MAIN_MAIL_SMTPS_PW"), $langs->trans("WithGMailYouCanCreateADedicatedPassword"));
		print '</td><td>';
		// SuperAdministrator access only
		if (!isModEnabled('multicompany') || ($user->admin && !$user->entity)) {
			print '<input class="flat" type="password" name="MAIN_MAIL_SMTPS_PW" size="32" value="' . htmlspecialchars($mainsmtppw, ENT_COMPAT, 'UTF-8') . '" autocomplete="off">';
		} else {
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto(getDolGlobalString('MAIN_MAIL_SMTPS_PW'), $htmltext, 1, 'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_PW" value="' . htmlspecialchars($mainsmtppw, ENT_COMPAT, 'UTF-8') . '">';
		}
		print '</td></tr>';
	}

	// OAUTH service provider
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')))) {
		print '<tr class="oddeven smtp_oauth_service"><td>'.$langs->trans("MAIN_MAIL_SMTPS_OAUTH_SERVICE").'</td><td>';

		// SuperAdministrator access only
		if (!isModEnabled('multicompany')  || ($user->admin && !$user->entity)) {
			print $form->selectarray('MAIN_MAIL_SMTPS_OAUTH_SERVICE', $oauthservices, $conf->global->MAIN_MAIL_SMTPS_OAUTH_SERVICE);
		} else {
			$text = $oauthservices[getDolGlobalString('MAIN_MAIL_SMTPS_OAUTH_SERVICE')];
			if (empty($text)) {
				$text = $langs->trans("Undefined");
			}
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $form->textwithpicto($text, $htmltext, 1, 'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_OAUTH_SERVICE" value="' . getDolGlobalString('MAIN_MAIL_SMTPS_OAUTH_SERVICE').'">';
		}
		print '</td></tr>';
	}

	// TLS
	print '<tr class="oddeven hideonmodemail"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')))) {
		if (function_exists('openssl_open')) {
			print $form->selectyesno('MAIN_MAIL_EMAIL_TLS', (getDolGlobalString('MAIN_MAIL_EMAIL_TLS') ? $conf->global->MAIN_MAIL_EMAIL_TLS : 0), 1);
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
	} else {
		print yn(0).' ('.$langs->trans("NotSupported").')';
	}
	print '</td></tr>';

	// STARTTLS
	print '<tr class="oddeven hideonmodemail"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')))) {
		if (function_exists('openssl_open')) {
			print $form->selectyesno('MAIN_MAIL_EMAIL_STARTTLS', (getDolGlobalString('MAIN_MAIL_EMAIL_STARTTLS') ? $conf->global->MAIN_MAIL_EMAIL_STARTTLS : 0), 1);
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
	} else {
		print yn(0).' ('.$langs->trans("NotSupported").')';
	}
	print '</td></tr>';

	// SMTP_ALLOW_SELF_SIGNED
	print '<tr class="oddeven hideonmodemail"><td>'.$langs->trans("MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").'</td><td>';
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')))) {
		if (function_exists('openssl_open')) {
			print $form->selectyesno('MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED', (getDolGlobalString('MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED') ? $conf->global->MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED : 0), 1);
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
	} else {
		print yn(0).' ('.$langs->trans("NotSupported").')';
	}
	print '</td></tr>';

	// DKIM
	print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_ENABLED").'</td><td>';
	if (!empty($conf->use_javascript_ajax) || (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('swiftmailer')))) {
		if (function_exists('openssl_open')) {
			print $form->selectyesno('MAIN_MAIL_EMAIL_DKIM_ENABLED', (getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_ENABLED') ? $conf->global->MAIN_MAIL_EMAIL_DKIM_ENABLED : 0), 1);
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
	} else {
		print yn(0).' ('.$langs->trans("NotSupported").')';
	}
	print '</td></tr>';

	// DKIM Domain
	print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_DOMAIN").'</td>';
	print '<td><input class="flat" id="MAIN_MAIL_EMAIL_DKIM_DOMAIN" name="MAIN_MAIL_EMAIL_DKIM_DOMAIN" size="32" value="'.(getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_DOMAIN') ? $conf->global->MAIN_MAIL_EMAIL_DKIM_DOMAIN : '');
	print '"></td></tr>';

	// DKIM Selector
	print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_SELECTOR").'</td>';
	print '<td><input class="flat" id="MAIN_MAIL_EMAIL_DKIM_SELECTOR" name="MAIN_MAIL_EMAIL_DKIM_SELECTOR" size="32" value="'.(getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_SELECTOR') ? $conf->global->MAIN_MAIL_EMAIL_DKIM_SELECTOR : '');
	print '"></td></tr>';

	// DKIM PRIVATE KEY
	print '<tr class="oddeven dkim"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").'</td>';
	print '<td><textarea id="MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY" name="MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY" rows="15" cols="100">'.(getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY') ? $conf->global->MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY : '').'</textarea>';
	print '</td></tr>';

	print '</table>';


	print '<br>';


	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("OtherOptions").'</td><td></td></tr>';

	// Force e-mail recipient
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_FORCE_SENDTO").'</td><td>';
	print '<input class="flat width300" name="MAIN_MAIL_FORCE_SENDTO" value="'.getDolGlobalString('MAIN_MAIL_FORCE_SENDTO').'" />';
	print '</td></tr>';

	// From
	$help = $form->textwithpicto('', $langs->trans("EMailHelpMsgSPFDKIM"));
	print '<tr class="oddeven"><td class="fieldrequired">';
	print $langs->trans("MAIN_MAIL_EMAIL_FROM", ini_get('sendmail_from') ? ini_get('sendmail_from') : $langs->transnoentities("Undefined"));
	print ' '.$help;
	print '</td>';
	print '<td><input class="flat minwidth300" name="MAIN_MAIL_EMAIL_FROM" value="'.(getDolGlobalString('MAIN_MAIL_EMAIL_FROM') ? $conf->global->MAIN_MAIL_EMAIL_FROM : '');
	print '"></td></tr>';

	// Default from type
	$liste = array();
	$liste['user'] = $langs->trans('UserEmail');
	$liste['company'] = $langs->trans('CompanyEmail').' ('.(!getDolGlobalString('MAIN_INFO_SOCIETE_MAIL') ? $langs->trans("NotDefined") : $conf->global->MAIN_INFO_SOCIETE_MAIL).')';

	print '<tr class="oddeven"><td>'.$langs->trans('MAIN_MAIL_DEFAULT_FROMTYPE').'</td><td>';
	print $form->selectarray('MAIN_MAIL_DEFAULT_FROMTYPE', $liste, getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE'), 0);
	print '</td></tr>';

	// From
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ERRORS_TO").'</td>';
	print '<td><input class="flat width300" name="MAIN_MAIL_ERRORS_TO" value="'.getDolGlobalString('MAIN_MAIL_ERRORS_TO').'">';
	print '</td></tr>';

	// Autocopy to
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td><input class="flat width300" name="MAIN_MAIL_AUTOCOPY_TO" value="'.getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO').'">';
	print '</td></tr>';

	// Add user to select destinaries list
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ENABLED_USER_DEST_SELECT").'</td><td>';
	print $form->selectyesno('MAIN_MAIL_ENABLED_USER_DEST_SELECT', getDolGlobalString('MAIN_MAIL_ENABLED_USER_DEST_SELECT'), 1);
	print '</td></tr>';
	// Disable autoselect to
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_NO_WITH_TO_SELECTED").'</td><td>';
	print $form->selectyesno('MAIN_MAIL_NO_WITH_TO_SELECTED', getDolGlobalString('MAIN_MAIL_NO_WITH_TO_SELECTED'), 1);
	print '</td></tr>';

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel();

	print '</form>';
} else {
	print dol_get_fiche_head($head, 'common', '', -1);

	print '<span class="opacitymedium">'.$langs->trans("EMailsDesc")."</span><br>\n";
	print "<br>\n";

	print $langs->trans("MAIN_DISABLE_ALL_MAILS");
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('MAIN_DISABLE_ALL_MAILS', array(), null, 0, 0, 1, 2, 0, 0, '_red').'</a>';
	} else {
		print yn(getDolGlobalString('MAIN_DISABLE_ALL_MAILS'));
		if (getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
			print img_warning($langs->trans("Disabled"));
		}
	}

	print "<br>\n";
	print "<br>\n";
	print "<br>\n";


	if (!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td></td></tr>';

		// Method
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';
		$text = $listofmethods[getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail')];
		if (empty($text)) {
			$text = $langs->trans("Undefined").img_warning();
		}
		print $text;

		// Note MAIN_HIDE_WARNING_TO_ENCOURAGE_SMTP_SETUP is set to 1 by default if not set
		if (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail' && getDolGlobalString('MAIN_HIDE_WARNING_TO_ENCOURAGE_SMTP_SETUP')) {
			$textwarning = $langs->trans("WarningPHPMail", $listofmethods['mail'], $listofmethods['smtps']).'<br>'.$langs->trans("WarningPHPMailA").'<br>'.$langs->trans("WarningPHPMailB").'<br>'.$langs->trans("WarningPHPMailC").'<br><br>'.$langs->trans("WarningPHPMailD");
			print $form->textwithpicto('', '<span class="small">'.$textwarning.'</span>', 1, 'help', 'nomargintop');
		}

		print '</td></tr>';

		// Host server
		if ($linuxlike && (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail')) {
			//print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike").'</td><td><span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span></td></tr>';
		} else {
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER", ini_get('SMTP') ? ini_get('SMTP') : $langs->transnoentities("Undefined")).'</td><td>'.(getDolGlobalString('MAIN_MAIL_SMTP_SERVER') ? $conf->global->MAIN_MAIL_SMTP_SERVER : '').'</td></tr>';
		}


		// Port
		if ($linuxlike && (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail')) {
			//print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike").'</td><td><span class="opacitymedium">'.$langs->trans("SeeLocalSendMailSetup").'</span></td></tr>';
		} else {
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT", ini_get('smtp_port') ? ini_get('smtp_port') : $langs->transnoentities("Undefined")).'</td><td>'.(getDolGlobalString('MAIN_MAIL_SMTP_PORT') ? $conf->global->MAIN_MAIL_SMTP_PORT : '').'</td></tr>';
		}

		// AUTH method
		if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer'))) {
			$authtype = getDolGlobalString('MAIN_MAIL_SMTPS_AUTH_TYPE', 'LOGIN');
			$text = '';
			if ($authtype === "LOGIN") {
				$text = $langs->trans("UseAUTHLOGIN");
			} elseif ($authtype === "PLAIN") {
				$text = $langs->trans("UseAUTHPLAIN");
			} elseif ($authtype === "XOAUTH2") {
				$text = $langs->trans("UseOauth");
			}
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_AUTH_TYPE").'</td><td>'.$text.'</td></tr>';
		}

		// SMTPS ID
		if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer'))) {
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>' . getDolGlobalString('MAIN_MAIL_SMTPS_ID').'</td></tr>';
		}

		// SMTPS PW
		if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')) && getDolGlobalString('MAIN_MAIL_SMTPS_AUTH_TYPE') != "XOAUTH2") {
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>'.preg_replace('/./', '*', getDolGlobalString('MAIN_MAIL_SMTPS_PW')).'</td></tr>';
		}

		// SMTPS oauth service
		if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer')) && getDolGlobalString('MAIN_MAIL_SMTPS_AUTH_TYPE') === "XOAUTH2") {
			$text = $oauthservices[getDolGlobalString('MAIN_MAIL_SMTPS_OAUTH_SERVICE')];
			if (empty($text)) {
				$text = $langs->trans("Undefined").img_warning();
			}
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_SMTPS_OAUTH_SERVICE").'</td><td>'.$text.'</td></tr>';
		}

		// TLS
		if ($linuxlike && (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail')) {
			// Nothing
		} else {
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
			if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer'))) {
				if (function_exists('openssl_open')) {
					print yn(getDolGlobalString('MAIN_MAIL_EMAIL_TLS'));
				} else {
					print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
				}
			} else {
				print '<span class="opacitymedium">'.yn(0).' ('.$langs->trans("NotSupported").')</span>';
			}
			print '</td></tr>';
		}

		// STARTTLS
		if ($linuxlike && (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail')) {
			// Nothing
		} else {
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
			if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer'))) {
				if (function_exists('openssl_open')) {
					print yn(getDolGlobalString('MAIN_MAIL_EMAIL_STARTTLS'));
				} else {
					print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
				}
			} else {
				//print '<span class="opacitymedium">'.yn(0).' ('.$langs->trans("NotSupported").')</span>';
			}
			print '</td></tr>';
		}

		// SMTP_ALLOW_SELF_SIGNED
		if ($linuxlike && (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail')) {
			// Nothing
		} else {
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED").'</td><td>';
			if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('smtps', 'swiftmailer'))) {
				if (function_exists('openssl_open')) {
					print yn(getDolGlobalString('MAIN_MAIL_EMAIL_SMTP_ALLOW_SELF_SIGNED'));
				} else {
					print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
				}
			} else {
				print '<span class="opacitymedium">'.yn(0).' ('.$langs->trans("NotSupported").')</span>';
			}
			print '</td></tr>';
		}

		if (getDolGlobalString('MAIN_MAIL_SENDMODE') == 'swiftmailer') {
			// DKIM
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_ENABLED").'</td><td>';
			if (in_array(getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail'), array('swiftmailer'))) {
				if (function_exists('openssl_open')) {
					print yn(getDolGlobalInt('MAIN_MAIL_EMAIL_DKIM_ENABLED'));
				} else {
					print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
				}
			} else {
				print yn(0).' ('.$langs->trans("NotSupported").')';
			}
			print '</td></tr>';

			// Domain
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_DOMAIN").'</td>';
			print '<td>'.getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_DOMAIN');
			print '</td></tr>';

			// Selector
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_SELECTOR").'</td>';
			print '<td>'.getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_SELECTOR');
			print '</td></tr>';

			// PRIVATE KEY
			print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY").'</td>';
			print '<td>'.getDolGlobalString('MAIN_MAIL_EMAIL_DKIM_PRIVATE_KEY');
			print '</td></tr>';
		}

		print '</table>';
		print '</div>';

		if (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail' && !getDolGlobalString('MAIN_HIDE_WARNING_TO_ENCOURAGE_SMTP_SETUP')) {
			$messagetoshow = $langs->trans("WarningPHPMail", $listofmethods['mail'], $listofmethods['smtps']).'<br>'.$langs->trans("WarningPHPMailA").'<br>'.$langs->trans("WarningPHPMailB").'<br>'.$langs->trans("WarningPHPMailC").'<br><br>'.$langs->trans("WarningPHPMailD");
			$messagetoshow .= ' '.$langs->trans("WarningPHPMailDbis", '{s1}', '{s2}');
			$linktosetvar1 = '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=disablephpmailwarning&token='.newToken().'">';
			$linktosetvar2 = '</a>';
			$messagetoshow = str_replace('{s1}', $linktosetvar1, $messagetoshow);
			$messagetoshow = str_replace('{s2}', $linktosetvar2, $messagetoshow);
			//print $messagetoshow;
			print info_admin($messagetoshow, 0, 0, 'warning', 'nomargintop', '', 'warning');
		}

		print '<br>';
	}

	/*
	if (!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("ParametersForTestEnvironment").'</td><td></td></tr>';

		// Force e-mail recipient
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_FORCE_SENDTO").'</td><td>'.getDolGlobalString('MAIN_MAIL_FORCE_SENDTO');
		if (getDolGlobalString('MAIN_MAIL_FORCE_SENDTO')) {
			if (!isValidEmail(getDolGlobalString('MAIN_MAIL_FORCE_SENDTO'))) {
				print img_warning($langs->trans("ErrorBadEMail"));
			} else {
				print img_warning($langs->trans("RecipientEmailsWillBeReplacedWithThisValue"));
			}
		}
		print '</td></tr>';

		print '</table>';
		print '</div>';

		print '<br>';
	}
	*/


	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("OtherOptions").'</td><td></td></tr>';

	// Force e-mail recipient
	if (!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
		print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_FORCE_SENDTO").'</td><td>'.getDolGlobalString('MAIN_MAIL_FORCE_SENDTO');
		if (getDolGlobalString('MAIN_MAIL_FORCE_SENDTO')) {
			if (!isValidEmail(getDolGlobalString('MAIN_MAIL_FORCE_SENDTO'))) {
				print img_warning($langs->trans("ErrorBadEMail"));
			} else {
				print img_warning($langs->trans("RecipientEmailsWillBeReplacedWithThisValue"));
			}
		}
		print '</td></tr>';
	}

	// From
	$help = $form->textwithpicto('', $langs->trans("EMailHelpMsgSPFDKIM"));
	print '<tr class="oddeven"><td>';
	print $langs->trans("MAIN_MAIL_EMAIL_FROM", ini_get('sendmail_from') ? ini_get('sendmail_from') : $langs->transnoentities("Undefined"));
	print ' '.$help;
	print '</td>';
	print '<td>' . getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
	if (!getDolGlobalString('MAIN_MAIL_EMAIL_FROM')) {
		print img_warning($langs->trans("Mandatory"));
	} elseif (!isValidEmail($conf->global->MAIN_MAIL_EMAIL_FROM)) {
		print img_warning($langs->trans("ErrorBadEMail"));
	}
	print '</td></tr>';

	// Default from type
	$liste = array();
	$liste['user'] = $langs->trans('UserEmail');
	$liste['company'] = $langs->trans('CompanyEmail').' ('.getDolGlobalString('MAIN_INFO_SOCIETE_MAIL', $langs->trans("NotDefined")).')';
	$sql = 'SELECT rowid, label, email FROM '.MAIN_DB_PREFIX.'c_email_senderprofile';
	$sql .= ' WHERE active = 1 AND (private = 0 OR private = '.((int) $user->id).')';
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$liste['senderprofile_'.$obj->rowid] = $obj->label.' <'.$obj->email.'>';
			}
			$i++;
		}
	} else {
		dol_print_error($db);
	}

	print '<tr class="oddeven"><td>'.$langs->trans('MAIN_MAIL_DEFAULT_FROMTYPE').'</td>';
	print '<td>';
	if (getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE') === 'robot') {
		print $langs->trans('RobotEmail');
	} elseif (getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE') === 'user') {
		print $langs->trans('UserEmail');
	} elseif (getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE') === 'company') {
		print $langs->trans('CompanyEmail').' '.dol_escape_htmltag('<'.$mysoc->email.'>');
	} else {
		$id = preg_replace('/senderprofile_/', '', getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE'));
		if ($id > 0) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/emailsenderprofile.class.php';
			$emailsenderprofile = new EmailSenderProfile($db);
			$emailsenderprofile->fetch($id);
			print $emailsenderprofile->label.' '.dol_escape_htmltag('<'.$emailsenderprofile->email.'>');
		}
	}
	print '</td></tr>';

	// Errors To
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ERRORS_TO").'</td>';
	print '<td>'.(getDolGlobalString('MAIN_MAIL_ERRORS_TO'));
	if (getDolGlobalString('MAIN_MAIL_ERRORS_TO') && !isValidEmail($conf->global->MAIN_MAIL_ERRORS_TO)) {
		print img_warning($langs->trans("ErrorBadEMail"));
	}
	print '</td></tr>';

	// Autocopy to
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_AUTOCOPY_TO").'</td>';
	print '<td>';
	if (getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO')) {
		$listofemail = explode(',', getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO'));
		$i = 0;
		foreach ($listofemail as $key => $val) {
			if ($i) {
				print ', ';
			}
			$val = trim($val);
			print $val;
			if (!isValidEmail($val, 0, 1)) {
				print img_warning($langs->trans("ErrorBadEMail", $val));
			}
			$i++;
		}
	} else {
		print '&nbsp;';
	}
	print '</td></tr>';

	//Add user to select destinaries list
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_ENABLED_USER_DEST_SELECT").'</td><td>'.yn(getDolGlobalString('MAIN_MAIL_ENABLED_USER_DEST_SELECT')).'</td></tr>';
	//Disable autoselect to
	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAIL_NO_WITH_TO_SELECTED").'</td><td>'.yn(getDolGlobalString('MAIN_MAIL_NO_WITH_TO_SELECTED')).'</td></tr>';

	print '</table>';
	print '</div>';


	print dol_get_fiche_end();


	// Actions button
	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';

	if (!getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
		if (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') != 'mail' || !$linuxlike) {
			if (function_exists('fsockopen') && $port && $server) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect&token='.newToken().'&date='.dol_now().'#formmailaftertstconnect">'.$langs->trans("DoTestServerAvailability").'</a>';
			}
		} else {
			//print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("FeatureNotAvailableOnLinux").'">'.$langs->trans("DoTestServerAvailability").'</a>';
		}

		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&token='.newToken().'&mode=init#formmailbeforetitle">'.$langs->trans("DoTestSend").'</a>';

		if (isModEnabled('fckeditor')) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testhtml&token='.newToken().'&mode=init#formmailbeforetitle">'.$langs->trans("DoTestSendHTML").'</a>';
		}
	}

	print '</div>';

	if (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail' && !getDolGlobalString('MAIN_FIX_FOR_BUGGED_MTA')) {
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

	if (!in_array($action, array('testconnect', 'test', 'testhtml')) && !getDolGlobalString('MAIN_DISABLE_ALL_MAILS')) {
		$text = '';
		//if (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail') {
		//	$text .= $langs->trans("WarningPHPMail", $listofmethods['mail'], $listofmethods['smtps']); // To encourage to use SMTPS
		//}

		if (getDolGlobalString('MAIN_MAIL_SENDMODE', 'mail') == 'mail') {
			// mthode php mail
			if (getDolGlobalString('MAIN_EXTERNAL_MAIL_SPF_STRING_TO_ADD')) {	// Not defined by default. Depend on platform.
				// List of string to add in SPF if the setup use the mail method. Example 'include:sendgrid.net include:spf.mydomain.com'
				$text .= ($text ? '<br><br>' : '').$langs->trans("WarningPHPMailSPFDMARC");
			} else {
				// MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS is list of IPs where email is sent from. Example: '1.2.3.4, [aaaa:bbbb:cccc:dddd]'.
				if (getDolGlobalString('MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS')) {
					// List of IP shown as record to add in SPF if we use the mail method
					$text .= ($text ? '<br><br>' : '').$langs->trans("WarningPHPMailSPFDMARC");
				}
			}
		} else {
			// method smtps or swiftmail
			if (getDolGlobalString('MAIN_EXTERNAL_SMTP_SPF_STRING_TO_ADD')) {	// Should be required only if you have preset the Dolibarr to use your own SMTP and you want to warn users to update their domain name to match your SMTP server.
				// List of string to add in SPF if we use the smtp method. Example 'include:spf.mydomain.com'
				$text .= ($text ? '<br><br>' : '').$langs->trans("WarningPHPMailSPF", getDolGlobalString('MAIN_EXTERNAL_SMTP_SPF_STRING_TO_ADD'));
			}
			if (getDolGlobalString('MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS')) {	// Not defined by default. Depend on platform.
				// List of IP shown as record to add as allowed IP if we use the smtp method. Value is '1.2.3.4, [aaaa:bbbb:cccc:dddd]'
				// TODO Add a key to allow to show the IP/name of server detected dynamically
				$text .= ($text ? '<br><br>' : '').$langs->trans("WarningPHPMail2", getDolGlobalString('MAIN_EXTERNAL_SMTP_CLIENT_IP_ADDRESS'));
			}
		}

		// Build list of main email addresses in $emailstotest and their domain to test in $domainstotest
		$emailstotest = array();
		if (getDolGlobalString('MAIN_INFO_SOCIETE_MAIL')) {
			$emailstotest[getDolGlobalString('MAIN_INFO_SOCIETE_MAIL')] = getDomainFromURL(preg_replace('/^.*@/', '', getDolGlobalString('MAIN_INFO_SOCIETE_MAIL')), 1);
		}
		if (getDolGlobalString('MAIN_MAIL_EMAIL_FROM')) {
			$emailstotest[getDolGlobalString('MAIN_MAIL_EMAIL_FROM')] = getDomainFromURL(preg_replace('/^.*@/', '', getDolGlobalString('MAIN_MAIL_EMAIL_FROM')), 1);
		}
		if (!empty($user->email)) {
			$emailstotest[$user->email] = getDomainFromURL(preg_replace('/^.*@/', '', $user->email), 1);
		}
		$domainstotest = array();
		foreach ($emailstotest as $email => $domain) {
			if (empty($domainstotest[$domain])) {
				$domainstotest[$domain] = array($email => $email);
			} else {
				$domainstotest[$domain][$email] = $email;
			}
		}

		// Test DNS entry for emails
		foreach (array('SPF', 'DMARC') as $dnstype) {
			foreach ($domainstotest as $domaintotest => $listofemails) {
				$dnsinfo = false;
				$foundforemail = 0;
				if (!empty($domaintotest) && function_exists('dns_get_record') && !getDolGlobalString('MAIN_DISABLE_DNS_GET_RECORD')) {
					$domain = $domaintotest;
					if ($dnstype == 'DMARC') {
						$domain = '_dmarc.'.$domain;
					}
					$dnsinfo = dns_get_record($domain, DNS_TXT);
				}
				if (!empty($dnsinfo) && is_array($dnsinfo)) {
					foreach ($dnsinfo as $info) {
						if (($dnstype == 'SPF' && stripos($info['txt'], 'v=spf') !== false)
							|| ($dnstype == 'DMARC' && stripos($info['txt'], 'v=dmarc') !== false)) {
							$foundforemail++;
							$text .= ($text ? '<br>' : '').$langs->trans("ActualMailDNSRecordFound", $dnstype, implode(', ', $listofemails), $info['txt']);
						}
					}
				}
				if (!$foundforemail) {
					$text .= ($text ? '<br>' : '').$langs->trans("ActualMailDNSRecordFound", $dnstype, implode(', ', $listofemails), '<span class="opacitymedium">'.$langs->transnoentitiesnoconv("None").'</span>');
				}
			}
		}

		if ($text) {
			print info_admin($langs->trans("SPFAndDMARCInformation").' :<br>'.$text, 0, 0, '1', '');
		}
	}

	// Run the test to connect
	if ($action == 'testconnect') {
		print '<div id="formmailaftertstconnect" name="formmailaftertstconnect"></div>';
		print load_fiche_titre($langs->trans("DoTestServerAvailability"));

		include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
		$mail = new CMailFile('', '', '', '', array(), array(), array(), '', '', 0, 0, '', '', '', $trackid, $sendcontext);
		$result = $mail->check_server_port($server, $port);
		if ($result) {
			print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort", $server, $port).'</div>';
		} else {
			$errormsg = $langs->trans("ServerNotAvailableOnIPOrPort", $server, $port);

			if ($mail->error) {
				$errormsg .= ' - '.$mail->error;
			}

			setEventMessages($errormsg, null, 'errors');
		}
		print '<br>';
	}

	// Show email send test form
	if ($action == 'test' || $action == 'testhtml') {
		print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
		print load_fiche_titre($action == 'testhtml' ? $langs->trans("DoTestSendHTML") : $langs->trans("DoTestSend"));

		print dol_get_fiche_head(array(), '', '', -1);

		// Create form object
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->trackid = (($action == 'testhtml') ? "testhtml" : "test");
		$formmail->fromname = (GETPOSTISSET('fromname') ? GETPOST('fromname') : getDolGlobalString('MAIN_MAIL_EMAIL_FROM'));
		$formmail->frommail = (GETPOSTISSET('frommail') ? GETPOST('frommail') : getDolGlobalString('MAIN_MAIL_EMAIL_FROM'));
		$formmail->fromid = $user->id;
		$formmail->fromalsorobot = 1;
		$formmail->fromtype = (GETPOSTISSET('fromtype') ? GETPOST('fromtype', 'aZ09') : getDolGlobalString('MAIN_MAIL_DEFAULT_FROMTYPE', 'user'));
		$formmail->withfromreadonly = 1;
		$formmail->withsubstit = 1;
		$formmail->withfrom = 1;
		$formmail->witherrorsto = 1;
		$formmail->withto = (GETPOSTISSET('sendto') ? GETPOST('sendto', 'restricthtml') : ($user->email ? $user->email : 1));
		$formmail->withtocc = (GETPOSTISSET('sendtocc') ? GETPOST('sendtocc', 'restricthtml') : 1); // ! empty to keep field if empty
		$formmail->withtoccc = (GETPOSTISSET('sendtoccc') ? GETPOST('sendtoccc', 'restricthtml') : 1); // ! empty to keep field if empty
		$formmail->withtopic = (GETPOSTISSET('subject') ? GETPOST('subject') : $langs->trans("Test"));
		$formmail->withtopicreadonly = 0;
		$formmail->withfile = 2;

		$formmail->withlayout = 1;		// Note: MAIN_EMAIL_USE_LAYOUT must be set
		$formmail->withaiprompt = ($action == 'testhtml' ? 'html' : 'text');	// Note: Module AI must be enabled

		$formmail->withbody = (GETPOSTISSET('message') ? GETPOST('message', 'restricthtml') : ($action == 'testhtml' ? $langs->transnoentities("PredefinedMailTestHtml") : $langs->transnoentities("PredefinedMailTest")));
		$formmail->withbodyreadonly = 0;
		$formmail->withcancel = 1;
		$formmail->withdeliveryreceipt = 1;
		$formmail->withfckeditor = ($action == 'testhtml' ? 1 : 0);
		$formmail->ckeditortoolbar = 'dolibarr_mailings';
		// Array of substitutions
		$formmail->substit = $substitutionarrayfortest;
		// Array of complementary POST parameters
		$formmail->param["action"] = "send";
		$formmail->param["models"] = "body";
		$formmail->param["mailid"] = 0;
		$formmail->param["returnurl"] = $_SERVER["PHP_SELF"];

		// Init list of files
		if (GETPOST("mode", "aZ09") == 'init') {
			$formmail->clear_attached_files();
		}

		print $formmail->get_form('addfile', 'removefile');

		print dol_get_fiche_end();

		// References
		if (!empty($user->admin)) {
			print '<br><br>';
			print '<span class="opacitymedium">'.$langs->trans("EMailsWillHaveMessageID").': ';
			print dol_escape_htmltag('<timestamp.*@'.dol_getprefix('email').'>');
			print '</span>';
		}
	}
}

// End of page
llxFooter();
$db->close();
