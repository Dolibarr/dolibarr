<?php
/* Copyright (C) 2006-2017	Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin		    <regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 *		\file 		htdocs/admin/tools/purge.php
 *		\brief      Page to purge files (temporary or not)
 */

if (! defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
}

// Load Dolibarr environment
require '../../main.inc.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_data_root
 */

$langs->load("admin");

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$choice = GETPOST('choice', 'aZ09');
$nbsecondsold = GETPOSTINT('nbsecondsold');

// Define filelog to discard it from purge
$filelog = '';
if (isModEnabled('syslog')) {
	$filelog = getDolGlobalString('SYSLOG_FILE');
	$filelog = preg_replace('/DOL_DATA_ROOT/i', DOL_DATA_ROOT, $filelog);
}

// Security
if (!$user->admin) {
	accessforbidden();
}


/*
 *	Actions
 */

if ($action == 'purge' && !preg_match('/^confirm/i', $choice) && ($choice != 'allfiles' || $confirm == 'yes')) {
	// Increase limit of time. Works only if we are not in safe mode
	$ExecTimeLimit = getDolGlobalInt('MAIN_ADMIN_TOOLS_PURGE_EXEC_TIME_LIMIT', 600);
	if (!empty($ExecTimeLimit)) {
		$err = error_reporting();
		error_reporting(0); // Disable all errors
		//error_reporting(E_ALL);
		@set_time_limit($ExecTimeLimit); // Need more than 240 on Windows 7/64
		error_reporting($err);
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
	$utils = new Utils($db);

	$result = $utils->purgeFiles($choice, $nbsecondsold);

	$mesg = $utils->output;
	setEventMessages($mesg, null, 'mesgs');
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-tools_purge');

$form = new Form($db);

print load_fiche_titre($langs->trans("Purge"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("PurgeAreaDesc", $dolibarr_main_data_root).'</span><br>';
print '<br>';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'" />';
print '<input type="hidden" name="action" value="purge" />';

print '<div class="divsection wordbreak">';
print '<table class="border centpercent">';

print '<tr class="border"><td style="padding: 4px">';

if (isModEnabled('syslog')) {
	print '<input type="radio" name="choice" id="choicelogfile" value="logfile"';
	print ($choice && $choice == 'logfile') ? ' checked' : '';
	$filelogparam = $filelog;
	if ($user->admin && preg_match('/^dolibarr.*\.log$/', basename($filelog))) {
		$filelogparam = '<a class="wordbreak" href="'.DOL_URL_ROOT.'/document.php?modulepart=logs&file=';
		$filelogparam .= basename($filelog);
		$filelogparam .= '">'.$filelog.'</a>';
	}
	$desc = $langs->trans("PurgeDeleteLogFile", '{filelogparam}');
	$desc = str_replace('{filelogparam}', $filelogparam, $desc);
	print '> <label for="choicelogfile">'.$desc.'</label>';
	print '<br><br>';
}

print '<input type="radio" name="choice" id="choicetempfiles" value="tempfiles"';
print (!$choice || $choice == 'tempfiles' || $choice == 'allfiles') ? ' checked' : '';
print '> <label for="choicetempfiles">'.$langs->trans("PurgeDeleteTemporaryFilesShort").'</label><br><br>';

print '<input type="radio" name="choice" id="choiceallfiles" value="confirm_allfiles"';
print ($choice && $choice == 'confirm_allfiles') ? ' checked' : '';
print '> <label for="choiceallfiles">'.$langs->trans("PurgeDeleteAllFilesInDocumentsDir", $dolibarr_main_data_root).'</label>';
print '<br>';
if (getDolGlobalInt('MAIN_PURGE_ACCEPT_NBSECONDSOLD')) {
	print 'NbSecondsOld = <input class="width50 right" type="text" name="nbsecondsold" value="'.$nbsecondsold.'">';
}
print '</td></tr></table>';
print '</div>';

//if ($choice != 'confirm_allfiles')
//{
	print '<br>';
	print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("PurgeRunNow").'"></div>';
//}

print '</form>';

if (preg_match('/^confirm/i', $choice)) {
	print '<br>';
	$formquestion = array();
	print $form->formconfirm($_SERVER["PHP_SELF"].'?choice=allfiles&nbsecondsold='.$nbsecondsold, $langs->trans('Purge'), $langs->trans('ConfirmPurge').img_warning().' ', 'purge', $formquestion, 'no', 2);
}

// End of page
llxFooter();
$db->close();
