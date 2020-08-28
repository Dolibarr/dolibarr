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
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/multismtp.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/multismtp.php';

// Load translation files required by page
$langs->loadLangs(array('admin', 'users'));

// Defini si peux lire/modifier permisssions
$canreaduser=($user->admin || $user->rights->multismtp->read);

$action = GETPOST('action');
$id = GETPOST('id','int');

$result = restrictedArea($user, 'user', $id, '&user', $feature2);
if ($user->id <> $id && !$canreaduser) {
	accessforbidden();
}

// Charge utilisateur edite
$fuser = new User($db);
if ($fuser->fetch($id) < 0) {
	return dol_print_error($db, 'User not found');
}

$multismtp = new Multismtp($db, $conf);

$multismtp->fetch($fuser);

if ($action == 'update' && empty($_POST["cancel"])) {

	if (imapEnabled($conf)) {

		$multismtp->imap_server = GETPOST('IMAP_SERVER');
		$multismtp->imap_id = GETPOST('IMAP_ID');
		$multismtp->imap_pw = GETPOST('IMAP_PW');
		$multismtp->imap_tls = null;
		$multismtp->imap_folder = null;
		$multismtp->imap_port = null;

		if (GETPOST('IMAP_PORT', 'int') != 0) {
			$multismtp->imap_port = GETPOST('IMAP_PORT', 'int');
		}

		if ($multismtp->checkImapConfig()) {

			$multismtp->imap_tls = GETPOST('IMAP_TLS', 'int');

			if (!$multismtp->checkImap()) {

				$msg = $langs->trans('IMAPConnectionError');

				if ($lasterror = imap_last_error()) {
					$msg .= "<br>".$lasterror;
				}

				setEventMessage($msg, 'warnings');
			}
		}
	}

	if ($conf->global->MULTISMTP_SMTP_ENABLED) {

		$main_mail_smtps_id = GETPOST('MAIN_MAIL_SMTPS_ID');
		$main_mail_smtps_pw = GETPOST('MAIN_MAIL_SMTPS_PW');
		$mail_mail_smtps_tls = GETPOST('MAIN_MAIL_EMAIL_TLS', 'int');
		$mail_mail_smtps_starttls = GETPOST('MAIN_MAIL_EMAIL_STARTTLS', 'int');
		$mail_mail_smtps_server = GETPOST('MAIN_MAIL_SMTP_SERVER');
		$mail_mail_smtps_port = null;

		if (GETPOST('MAIN_MAIL_SMTP_PORT', 'int') != 0) {
			$mail_mail_smtps_port = GETPOST('MAIN_MAIL_SMTP_PORT', 'int');
		}

		$multismtp->smtp_id = $main_mail_smtps_id;
		$multismtp->smtp_pw = $main_mail_smtps_pw;
		$multismtp->smtp_tls = (bool) $mail_mail_smtps_tls;
		$multismtp->smtp_starttls = (bool) $mail_mail_smtps_starttls;

		if (!empty($mail_mail_smtps_server)) {
			$multismtp->smtp_server = $mail_mail_smtps_server;
		}

		if (!empty($mail_mail_smtps_port)) {
			$multismtp->smtp_port = $mail_mail_smtps_port;
		}

		/**
		 * SMTP check is disabled because of bug #5750
		 * https://github.com/Dolibarr/dolibarr/issues/5750
		 */

//		$smtpcred_check = $multismtp->checkSmtp($fuser);
//
//		if (is_string($smtpcred_check) && $multismtp->checkSmtpConfig()) {
//
//			$msg = $langs->trans('SMTPConnectionError').'<br>'.$smtpcred_check;
//
//			setEventMessage($msg, 'warnings');
//		}
	}

	try {
		$multismtp->update();

		setEventMessage('SetupSaved');
	} catch (Exception $e) {
		setEventMessage($e->getMessage(), 'errors');
	}
}

/*
 * View
 */
$form = new Form($db);

llxHeader(null, $langs->trans('Email'));

$head = user_prepare_head($fuser);

$title = $langs->trans("User");

dol_fiche_head($head, 'email', $title, 0, 'user');

if ($action == 'edit') {

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'">';
	print '<input type="hidden" name="action" value="update">';
}

$linkback = '';

if ($user->rights->multismtp->read || $user->admin) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

dol_banner_tab($fuser,'id', $linkback, $user->rights->multismtp->read || $user->admin);

print '<div class="fichecenter"><div class="fichehalfleft">';

print '<div class="underbanner clearboth"></div>';

print '<table class="border centpercent tableforfield">';

$atlestoneenabled = $conf->global->MULTISMTP_SMTP_ENABLED || imapEnabled($conf);

$smtp_iniserver = ini_get('SMTP') ?: $langs->transnoentities("Undefined");
$smtp_iniport = ini_get('smtp_port') ?: $langs->transnoentities("Undefined");
$smtp_credentials = $multismtp->getSmtpCredentials();
$imap_credentials = $multismtp->getImapCredentials();

if ($action == 'edit') {

	if ($conf->global->MULTISMTP_SMTP_ENABLED) {
		print '<div class="titre">'.$langs->trans('SMTPConfiguration').'</div>';
		print '<br><table class="border" width="100%">';

		// Server
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER", $smtp_iniserver).'</td><td>';

		if ($conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
			print '<input type="text" class="flat" size="32" name="MAIN_MAIL_SMTP_SERVER" value="'.$smtp_credentials['server'].'">';
		} else {
			print dol_htmlentities($smtp_credentials['server']);
		}
		print '</td></tr>';

		// Port
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT", $smtp_iniport).'</td><td>';
		if ($conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
			print '<input type="text" class="flat" size="3" name="MAIN_MAIL_SMTP_PORT" value="'.$smtp_credentials['port'].'">';
		} else {
			print dol_htmlentities($smtp_credentials['port']);
		}
		print '</td></tr>';

		// TLS
		print '<tr><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		if (function_exists('openssl_open')) {
			if ($conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
				print $form->selectyesno('MAIN_MAIL_EMAIL_TLS', $smtp_credentials['tls'], 1);
			} else {
				print yn($smtp_credentials['tls']);
			}
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
		print '</td></tr>';

		// STARTTLS
		if (versioncompare(versiondolibarrarray(), array(4,0,-5)) >= 0) {
			$var = !$var;
			print '<tr><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
			if ($conf->global->MULTISMTP_ALLOW_CHANGESERVER == 1) {
				print $form->selectyesno('MAIN_MAIL_EMAIL_STARTTLS', $smtp_credentials['starttls'], 1);
			} else {
				print yn($smtp_credentials['starttls']);
			}
			print '</td></tr>';
		}

		// SMTPS ID
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td><input type="text" class="flat" size="32" name="MAIN_MAIL_SMTPS_ID" value="'.$smtp_credentials['id'].'"></td></tr>';

		// SMTPS PW
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td><input type="password" class="flat" size="32" name="MAIN_MAIL_SMTPS_PW" value="'.$smtp_credentials['pw'].'"></td></tr>';

		print '</table><br>';
	}

	if (imapEnabled($conf)) {
		print '<div class="titre">'.$langs->trans('IMAPConfiguration').'</div>';

		print '<br><table class="border" width="100%">';

		// Server
		print '<tr>
		<td>'.$langs->trans('IMAP_SERVER').'</td>
		<td>';
		if ($conf->global->MULTISMTP_IMAP_CONF_SERVER) {
			print dol_htmlentities($imap_credentials['server']);
		} else {
			print '<input type="text" class="flat" size="32" name="IMAP_SERVER" value="'.$imap_credentials['server'].'">';
		}
		print '</td>
		</tr>';

		// Port
		print '<tr><td>'.$langs->trans('IMAP_PORT').'</td><td>';
		if ($conf->global->MULTISMTP_IMAP_CONF_PORT) {
			print dol_htmlentities($imap_credentials['port']);
		} else {
			print '<input type="text" class="flat" size="5" name="IMAP_PORT" value="'.$imap_credentials['port'].'"></td></tr>';
		}

		// TLS
		print '<tr><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		if ($conf->global->MULTISMTP_IMAP_CONF_SERVER) {
			print yn($conf->global->MULTISMTP_IMAP_CONF_TLS);
		} else {
			print $form->selectyesno('IMAP_TLS', $imap_credentials['tls'], 1);
		}
		print '</td></tr>';

		// IMAP ID
		print '<tr><td>'.$langs->trans('IMAP_ID').'</td><td><input type="text" class="flat" size="32" name="IMAP_ID" value="'.$multismtp->imap_id.'"></td></tr>';

		// IMAP PW
		print '<tr>
	<td>'.$langs->trans('IMAP_PW').'</td>
	<td><input type="password" class="flat" size="32" name="IMAP_PW" value="'.$multismtp->imap_pw.'"></td></tr>';

		print '</table><br>';
	}

	print '<div class="center">';
	print '<input class="button" value="'.$langs->trans("Modify").'" type="submit">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</div>';

	print '</form>';
} else {

	if ($conf->global->MULTISMTP_SMTP_ENABLED) {
		print '<div class="titre">'.$langs->trans('SMTPConfiguration').'</div>';

		print '<br><table class="border" width="100%">';

		// Server
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER", $smtp_iniserver).'</td><td>'.dol_htmlentities($smtp_credentials['server']).'</td></tr>';

		// Port
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT", $smtp_iniport).'</td><td>'.dol_htmlentities($smtp_credentials['port']).'</td></tr>';

		// TLS
		print '<tr><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		if (function_exists('openssl_open')) {
			print yn($smtp_credentials['tls']);
		} else {
			print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
		}
		print '</td></tr>';

		// STARTTLS
		if (versioncompare(versiondolibarrarray(), array(4,0,-5)) >= 0) {
			$var = !$var;
			print '<tr><td>'.$langs->trans("MAIN_MAIL_EMAIL_STARTTLS").'</td><td>';
			if (function_exists('openssl_open')) {
				print yn($smtp_credentials['starttls']);
			} else {
				print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
			}
			print '</td></tr>';
		}

		// SMTPS ID
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>'.dol_htmlentities($smtp_credentials['id']).'</td></tr>';

		// SMTPS PW
		print '<tr><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>'.preg_replace('/./', '*',
				$smtp_credentials['pw']).'</td></tr>';

		print '</table><br>';
	}

	if (imapEnabled($conf)) {
		print '<div class="titre">'.$langs->trans('IMAPConfiguration').'</div>';

		print '<br><table class="border" width="100%">';

		// Server
		print '<tr><td>'.$langs->trans('IMAP_SERVER').'</td><td>'.dol_htmlentities($imap_credentials['server']).'</td></tr>';

		// Port
		print '<tr><td>'.$langs->trans('IMAP_PORT').'</td><td>'.dol_htmlentities($imap_credentials['port']).'</td></tr>';

		// TLS
		print '<tr><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
		print yn($imap_credentials['tls']);
		print '</td></tr>';

		// IMAP ID
		print '<tr><td>'.$langs->trans('IMAP_ID').'</td><td>'.dol_htmlentities($imap_credentials['id']).'</td></tr>';

		// IMAP PW
		print '<tr><td>'.$langs->trans('IMAP_PW').'</td><td>'.preg_replace('/./', '*',
				$imap_credentials['pw']).'</td></tr>';

		// IMAP Folders
		print '<tr><td>'.$langs->trans('IMAP_FOLDER').'</td><td>';

		//To avoid showing an error when no server is configured
		if ($multismtp->checkImapConfig()) {

			$imap_folders = $multismtp->getImapFolders();

			if (!$imap_folders) {
				print img_warning().' '.$langs->trans('ErrorRetrievingIMAPFolders');
			} else {
				print Form::selectarray('IMAP_FOLDER', $imap_folders, $imap_credentials['folder'], true);
				print ' <div id="loadingfolder" style="visibility:hidden;display:inline">
				<img src="'.dol_buildpath('/includes/jquery/plugins/fileupload/img/loading.gif', 2).'" style="width: 16px; height: 16px">
				</div>';
			}
		}

		print '</td></tr>';

		print '</table>';

		?>
		<script>

			jQuery('select#IMAP_FOLDER').change(function() {

				var loadingicon = jQuery('div#loadingfolder');

				loadingicon.css('visibility', 'visible');

				jQuery.ajax({
					url: '<?php echo dol_buildpath('../core/ajax/updateImapFolder.php', 1) ?>',
					dataType: 'json',
					data: {
						userid: <?php echo $id ?>,
						folder: jQuery('select#IMAP_FOLDER').val()
					}
				}).done(function(data) {
					if (data.status == 'error') {
						jQuery.jnotify(data.msg, 'error', true, { remove: function (){} } );
					} else {
						jQuery.jnotify('<?php echo dol_escape_js($langs->trans('SetupSaved')) ?>', 3000, false, { remove: function (){} } );
					}

					loadingicon.css('visibility', 'hidden');
				}).fail(function(data) {
					jQuery.jnotify('<?php echo dol_escape_js($langs->trans('CoreErrorMessage')) ?>: Request ajax fail', 'error', true, { remove: function (){} } );
					loadingicon.css('visibility', 'hidden');
				});
			});
		</script>
		<?php
	}

	if (!$atlestoneenabled) {

		$url = dol_buildpath('/user/smtp', 2);

		print '<p class="center">'.img_warning().' ';

		if ($user->admin) {
			print '<a href="'.$url.'">';
		}

		print $langs->trans('CheckModuleConfiguration');

		if ($user->admin) {
			print '</a>';
		}

		print '</p>';
	}
}

dol_fiche_end();

if ($action != 'edit' && $atlestoneenabled) {
// Boutons actions
	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=edit">'.$langs->trans("Modify").'</a>';

	print '</div>';
}

llxFooter();
