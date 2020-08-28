<?php
/* Copyright (C) 2015-2016 Marcos García de La Fuente	<hola@marcosgdf.com>
 * Copyright (C) 2020      Alexandre Spangaro			<aspangaro@open-dsi.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/multismtp.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/multismtp.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "users", "errors"));

if (!$user->admin)
	accessforbidden();

$form = new Form($db);
$action = GETPOST('action');
$c = GETPOST('c');

$allowed_constants = array(
	'MULTISMTP_SMTP_ENABLED',
	'MULTISMTP_ALLOW_CHANGESERVER',
	'MULTISMTP_IMAP_ENABLED',
	'MULTISMTP_IMAP_NOVALIDATECERT'
);

if (in_array($c, $allowed_constants)) {
	if ($action == 'enable') {
		dolibarr_set_const($db, $c, '1', 'int', 0, '', $conf->entity);
	} elseif ($action == 'disable') {

		if ($c == 'MULTISMTP_IMAP_ENABLED') {
			Multismtp::removeAllImapCredentials();
		} elseif ($c == 'MULTISMTP_SMTP_ENABLED') {
			Multismtp::removeAllSmtpCredentials();
		}

		dolibarr_set_const($db, $c, '0', 'int', 0, '', $conf->entity);
	}
}

if ($_POST) {

	$imap_server = GETPOST('MULTISMTP_IMAP_CONF_SERVER');
	$imap_port = GETPOST('MULTISMTP_IMAP_CONF_PORT');
	$imap_tls = GETPOST('MULTISMTP_IMAP_CONF_TLS', 'int');

	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_SERVER', $imap_server, 'chaine', 0, '',
		$conf->entity);
	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_PORT', $imap_port, 'chaine', 0, '',
		$conf->entity);
	dolibarr_set_const($db, 'MULTISMTP_IMAP_CONF_TLS', $imap_tls, 'int', 0, '',
		$conf->entity);

	Multismtp::removeAllImapServerInfo();

	setEventMessage($langs->trans('SetupSaved'));
}



$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';

llxHeader('',$langs->trans("Setup"));
print load_fiche_titre($langs->trans('ModuleSetup').' Multi SMTP', $linkback, 'title_setup');

if (!isset($conf->global->MAIN_ACTIVATE_UPDATESESSIONTRIGGER)) {
	print info_admin($langs->trans('TriggerNotActive'));
}

/**
 * SMTP
 */

print '<br><div class="titre">SMTP</div>';
print '<p>'.$langs->trans('SMTPDescription').'</p>';

if (!empty($conf->global->MAIN_DISABLE_ALL_MAILS)) {
	echo info_admin($langs->trans('WarningMailDisabled',
		'<a href="'.dol_buildpath('/admin/mails.php', 2).'">', '</a>'));
}

if (empty($conf->global->MAIN_MAIL_SENDMODE) || $conf->global->MAIN_MAIL_SENDMODE == 'mail') {
	echo info_admin($langs->trans('WarningMailSendMode', $langs->transnoentities('MAIN_MAIL_SENDMODE'),
		'<a href="'.dol_buildpath('/admin/mails.php', 2).'">', '</a>'));
} else {

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Enable/Disable
	print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_SMTP_ENABLED").'</td><td>';
	if ($conf->global->MULTISMTP_SMTP_ENABLED == 1) {
		print '<a href="index.php?action=disable&c=MULTISMTP_SMTP_ENABLED">'.img_picto($langs->trans("Enabled"),
				'switch_on').'</a>';
	} else {
		print '<a href="index.php?action=enable&c=MULTISMTP_SMTP_ENABLED">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	}
	print '</td></tr>';

	// Allow changing SMTP server
	print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_ALLOW_CHANGESERVER").'</td><td>';
	if ($conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
		print '<a href="index.php?action=disable&c=MULTISMTP_ALLOW_CHANGESERVER">'.img_picto($langs->trans("Enabled"),
				'switch_on').'</a>';
	} else {
		print '<a href="index.php?action=enable&c=MULTISMTP_ALLOW_CHANGESERVER">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	}
	print '</td></tr>';

	print '</table>';
	print '<p>'.$langs->trans('PreParameterAdvice').'</p>';
}


/**
 * IMAP
 */

print '<form method="post">';

print '<br><div class="titre">IMAP</div>';
print '<p>'.$langs->trans('IMAPDescription').'</p>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

// Enable/Disable
print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_IMAP_ENABLED").'</td><td>';
if (imapEnabled($conf)) {
	print '<a href="index.php?action=disable&c=MULTISMTP_IMAP_ENABLED">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
} else {

	if (function_exists('imap_open')) {
		print '<a href="index.php?action=enable&c=MULTISMTP_IMAP_ENABLED">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	} else {
		print img_warning().' '.$langs->trans('IMAPNotAvailable');
	}
}
print '</td></tr>';

if (function_exists('imap_open')) {

	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans('ForceIMAPConfiguration').'</td></tr>';

	// Allow self-signed certificates
	print '<tr class="oddeven"><td>'.$langs->trans('MULTISMTP_IMAP_NOVALIDATECERT').'</td><td>';
	if ($conf->global->MULTISMTP_IMAP_NOVALIDATECERT == 1) {
		print '<a href="index.php?action=disable&c=MULTISMTP_IMAP_NOVALIDATECERT">'.img_picto($langs->trans("Enabled"),
				'switch_on').'</a>';
	} else {
		print '<a href="index.php?action=enable&c=MULTISMTP_IMAP_NOVALIDATECERT">'.img_picto($langs->trans("Disabled"),
				'switch_off').'</a>';
	}
	print '</td></tr>';

	// Restrict IMAP server
	print '<tr class="oddeven"><td>'.$langs->trans("Host").'</td><td>';
	print '<input type="text" name="MULTISMTP_IMAP_CONF_SERVER" value="'.$conf->global->MULTISMTP_IMAP_CONF_SERVER.'" class="flat"';
	if (!imapEnabled($conf)) {
		print 'disabled';
	}
	print '>';
	print '</td></tr>';

	// Restrict IMAP port
	print '<tr class="oddeven"><td>'.$langs->trans("Port").'</td><td>';
	print '<input type="text" name="MULTISMTP_IMAP_CONF_PORT" value="'.$conf->global->MULTISMTP_IMAP_CONF_PORT.'" size="4" class="flat"';
	if (!imapEnabled($conf)) {
		print 'disabled';
	}
	print '>';
	print '</td></tr>';

	// Restrict IMAP tls
	print '<tr class="oddeven"><td>'.$langs->trans("MULTISMTP_IMAP_CONF_SSL").'</td><td>';
	print $form->selectyesno('MULTISMTP_IMAP_CONF_TLS', $conf->global->MULTISMTP_IMAP_CONF_TLS, 1, !imapEnabled($conf));
	print '</td></tr>';
}

print '</table>';

print '<br><br><div style="text-align:center"><input type="submit" value="'.$langs->trans('Save').'" class="button"></div>';

print '</form>';

llxFooter();
