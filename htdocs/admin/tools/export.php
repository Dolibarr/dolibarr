<?php
/* Copyright (C) 2006-2014  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2021		Regis Houssin		<regis.houssin@inodbox.com>
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
 *		\file 		htdocs/admin/tools/export.php
 *		\brief      Page to export a database into a dump file
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("admin");

$action = GETPOST('action', 'aZ09');
$what = GETPOST('what', 'alpha');
$export_type = GETPOST('export_type', 'alpha');
$file = dol_sanitizeFileName(GETPOST('filename_template', 'alpha'));

// Load variable for pagination
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha') || (empty($toselect) && $massaction === '0')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "date";
}

if (!$user->admin) {
	accessforbidden();
}

$errormsg = '';

$utils = new Utils($db);


/*
 * Actions
 */

if ($file && !$what) {
	//print DOL_URL_ROOT.'/dolibarr_export.php';
	header("Location: ".DOL_URL_ROOT.'/admin/tools/dolibarr_export.php?msg='.urlencode($langs->trans("ErrorFieldRequired", $langs->transnoentities("ExportMethod"))).(GETPOST('page_y', 'int') ? '&page_y='.GETPOST('page_y', 'int') : ''));
	exit;
}

if ($action == 'delete') {
	$file = $conf->admin->dir_output.'/'.dol_sanitizeFileName(GETPOST('urlfile'));
	$ret = dol_delete_file($file, 1);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	}
	$action = '';
}

$_SESSION["commandbackuplastdone"] = '';
$_SESSION["commandbackuptorun"] = '';
$_SESSION["commandbackupresult"] = '';

// Increase limit of time. Works only if we are not in safe mode
$ExecTimeLimit = 600; // Set it to 0 to not use a forced time limit
if (!empty($ExecTimeLimit)) {
	$err = error_reporting();
	error_reporting(0); // Disable all errors
	//error_reporting(E_ALL);
	@set_time_limit($ExecTimeLimit); // Need more than 240 on Windows 7/64
	error_reporting($err);
}
$MemoryLimit = 0;
if (!empty($MemoryLimit)) {
	@ini_set('memory_limit', $MemoryLimit);
}

// Start with empty buffer
$dump_buffer = '';
$dump_buffer_len = 0;

// We will send fake headers to avoid browser timeout when buffering
$time_start = time();


$outputdir  = $conf->admin->dir_output.'/backup';
$result = dol_mkdir($outputdir);


$lowmemorydump = GETPOSTISSET("lowmemorydump") ? GETPOST("lowmemorydump") : getDolGlobalString('MAIN_LOW_MEMORY_DUMP');


// MYSQL
if ($what == 'mysql') {
	$cmddump = GETPOST("mysqldump", 'none'); // Do not sanitize here with 'alpha', will be sanitize later by dol_sanitizePathName and escapeshellarg
	$cmddump = dol_sanitizePathName($cmddump);

	if (!empty($dolibarr_main_restrict_os_commands)) {
		$arrayofallowedcommand = explode(',', $dolibarr_main_restrict_os_commands);
		$arrayofallowedcommand = array_map('trim', $arrayofallowedcommand);
		dol_syslog("Command are restricted to ".$dolibarr_main_restrict_os_commands.". We check that one of this command is inside ".$cmddump);
		$basenamecmddump = basename(str_replace('\\', '/', $cmddump));
		if (!in_array($basenamecmddump, $arrayofallowedcommand)) {	// the provided command $cmddump must be an allowed command
			$langs->load("errors");
			$errormsg = $langs->trans('CommandIsNotInsideAllowedCommands');
			$errormsg .= '<br>'.$langs->trans('ErrorCheckTheCommandInsideTheAdvancedOptions');
		}
	}

	if (!$errormsg && $cmddump) {
		dolibarr_set_const($db, 'SYSTEMTOOLS_MYSQLDUMP', $cmddump, 'chaine', 0, '', $conf->entity);
	}

	if (!$errormsg) {
		$utils->dumpDatabase(GETPOST('compression', 'alpha'), $what, 0, $file, 0, 0, $lowmemorydump);
		$errormsg = $utils->error;
		$_SESSION["commandbackuplastdone"] = $utils->result['commandbackuplastdone'];
		$_SESSION["commandbackuptorun"] = $utils->result['commandbackuptorun'];
	}
}

// MYSQL NO BIN
if ($what == 'mysqlnobin') {
	$utils->dumpDatabase(GETPOST('compression', 'alpha'), $what, 0, $file, 0, 0, $lowmemorydump);

	$errormsg = $utils->error;
	$_SESSION["commandbackuplastdone"] = $utils->result['commandbackuplastdone'];
	$_SESSION["commandbackuptorun"] = $utils->result['commandbackuptorun'];
}

// POSTGRESQL
if ($what == 'postgresql') {
	$cmddump = GETPOST("postgresqldump", 'none'); // Do not sanitize here with 'alpha', will be sanitize later by dol_sanitizePathName and escapeshellarg
	$cmddump = dol_sanitizePathName($cmddump);

	/* Not required, the command is output on screen but not ran for pgsql
	if (!empty($dolibarr_main_restrict_os_commands))
	{
		$arrayofallowedcommand=explode(',', $dolibarr_main_restrict_os_commands);
		$arrayofallowedcommand = array_map('trim', $arrayofallowedcommand);
		dol_syslog("Command are restricted to ".$dolibarr_main_restrict_os_commands.". We check that one of this command is inside ".$cmddump);
		$basenamecmddump = basename(str_replace('\\', '/', $cmddump));
		if (! in_array($basenamecmddump, $arrayofallowedcommand))	// the provided command $cmddump must be an allowed command
		{
			$errormsg=$langs->trans('CommandIsNotInsideAllowedCommands');
		}
	} */

	if (!$errormsg && $cmddump) {
		dolibarr_set_const($db, 'SYSTEMTOOLS_POSTGRESQLDUMP', $cmddump, 'chaine', 0, '', $conf->entity);
	}

	if (!$errormsg) {
		$utils->dumpDatabase(GETPOST('compression', 'alpha'), $what, 0, $file, 0, 0, $lowmemorydump);
		$errormsg = $utils->error;
		$_SESSION["commandbackuplastdone"] = $utils->result['commandbackuplastdone'];
		$_SESSION["commandbackuptorun"] = $utils->result['commandbackuptorun'];
	}

	$what = ''; // Clear to show message to run command
}


if ($errormsg) {
	setEventMessages($langs->trans("Error")." : ".$errormsg, null, 'errors');

	$resultstring = '';
	$resultstring .= '<div class="error">'.$langs->trans("Error")." : ".$errormsg.'</div>';

	$_SESSION["commandbackupresult"] = $resultstring;
} else {
	if ($what) {
		setEventMessages($langs->trans("BackupFileSuccessfullyCreated").'.<br>'.$langs->trans("YouCanDownloadBackupFile"), null, 'mesgs');

		$resultstring = '<div class="ok">';
		$resultstring .= $langs->trans("BackupFileSuccessfullyCreated").'.<br>';
		$resultstring .= $langs->trans("YouCanDownloadBackupFile");
		$resultstring .= '</div>';

		$_SESSION["commandbackupresult"] = $resultstring;
	}
	/*else
	{
		setEventMessages($langs->trans("YouMustRunCommandFromCommandLineAfterLoginToUser",$dolibarr_main_db_user,$dolibarr_main_db_user), null, 'warnings');
	}*/
}



/*
 * View
 */

top_httphead();

$db->close();

// Redirect to backup page
header("Location: dolibarr_export.php".(GETPOST('page_y', 'int') ? '?page_y='.GETPOST('page_y', 'int') : ''));
exit();
