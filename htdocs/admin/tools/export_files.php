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
 *		\file 		htdocs/admin/tools/export_files.php
 *		\brief      Page to export documents into a compressed file
 */

if (! defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1');		// Force use of CSRF protection with tokens even for GET
}

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
$file = trim(GETPOST('zipfilename_template', 'alpha'));
$compression = GETPOST('compression', 'aZ09');

$file = dol_sanitizeFileName($file);
$file = preg_replace('/(\.zip|\.tar|\.tgz|\.gz|\.tar\.gz|\.bz2|\.zst)$/i', '', $file);

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "date";
}
if ($page < 0) {
	$page = 0;
} elseif (empty($page)) {
	$page = 0;
}
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;

if (!$user->admin) {
	accessforbidden();
}

$errormsg = '';


/*
 * Actions
 */

if ($action == 'delete') {
	$filerelative = dol_sanitizeFileName(GETPOST('urlfile', 'alpha'));
	$filepath = $conf->admin->dir_output.'/'.$filerelative;
	$ret = dol_delete_file($filepath, 1);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", $filerelative), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", $filerelative), null, 'errors');
	}
	$action = '';
}


/*
 * View
 */

// Increase limit of time. Works only if we are not in safe mode
$ExecTimeLimit = 1800; // 30mn
if (!empty($ExecTimeLimit)) {
	$err = error_reporting();
	error_reporting(0); // Disable all errors
	//error_reporting(E_ALL);
	@set_time_limit($ExecTimeLimit); // Need more than 240 on Windows 7/64
	error_reporting($err);
}

/* If value has been forced with a php_admin_value, this has no effect. Example of value: '512M' */
$MemoryLimit = getDolGlobalString('MAIN_MEMORY_LIMIT_ARCHIVE_DATAROOT');
if (!empty($MemoryLimit)) {
	@ini_set('memory_limit', $MemoryLimit);
}

$form = new Form($db);
$formfile = new FormFile($db);

//$help_url='EN:Backups|FR:Sauvegardes|ES:Copias_de_seguridad';
//llxHeader('','',$help_url);

//print load_fiche_titre($langs->trans("Backup"),'','title_setup');


// Start with empty buffer
$dump_buffer = '';
$dump_buffer_len = 0;

// We will send fake headers to avoid browser timeout when buffering
$time_start = time();


$outputdir  = $conf->admin->dir_output.'/documents';
$result = dol_mkdir($outputdir);

$utils = new Utils($db);

if ($export_type == 'externalmodule' && !empty($what)) {
	$fulldirtocompress = DOL_DOCUMENT_ROOT.'/custom/'.dol_sanitizeFileName($what);
} else {
	$fulldirtocompress = DOL_DATA_ROOT;
}
$dirtoswitch = dirname($fulldirtocompress);
$dirtocompress = basename($fulldirtocompress);

if ($compression == 'zip') {
	$file .= '.zip';

	$excludefiles = '/(\.back|\.old|\.log|\.pdf_preview-.*\.png|[\/\\\]temp[\/\\\]|[\/\\\]admin[\/\\\]documents[\/\\\])/i';

	//var_dump($fulldirtocompress);
	//var_dump($outputdir."/".$file);exit;

	$rootdirinzip = '';
	if ($export_type == 'externalmodule' && !empty($what)) {
		$rootdirinzip = $what;

		global $dolibarr_allow_download_external_modules;
		if (empty($dolibarr_allow_download_external_modules)) {
			print 'Download of external modules is not allowed by $dolibarr_allow_download_external_modules in conf.php file';
			$db->close();
			exit();
		}
	}

	$ret = dol_compress_dir($fulldirtocompress, $outputdir."/".$file, $compression, $excludefiles, $rootdirinzip);
	if ($ret < 0) {
		if ($ret == -2) {
			$langs->load("errors");
			$errormsg = $langs->trans("ErrNoZipEngine");
		} else {
			$langs->load("errors");
			$errormsg = $langs->trans("ErrorFailedToWriteInDir", $outputdir);
		}
	}
} elseif (in_array($compression, array('gz', 'bz', 'zstd'))) {
	$userlogin = ($user->login ? $user->login : 'unknown');

	$outputfile = $conf->admin->dir_temp.'/export_files.'.$userlogin.'.out'; // File used with popen method

	$file .= '.tar';

	// We also exclude '/temp/' dir and 'documents/admin/documents'
	// We make escapement here and call executeCLI without escapement because we don't want to have the '*.log' escaped.
	$cmd = "tar -cf '".escapeshellcmd($outputdir."/".$file)."' --exclude-vcs --exclude-caches-all --exclude='temp' --exclude='*.log' --exclude='*.pdf_preview-*.png' --exclude='documents/admin/documents' -C '".escapeshellcmd(dol_sanitizePathName($dirtoswitch))."' '".escapeshellcmd(dol_sanitizeFileName($dirtocompress))."'";

	$result = $utils->executeCLI($cmd, $outputfile, 0, null, 1);

	$retval = $result['error'];
	if ($result['result'] || !empty($retval)) {
		$langs->load("errors");
		dol_syslog("Documents tar retval after exec=".$retval, LOG_ERR);
		$errormsg = 'Error tar generation return '.$retval;
	} else {
		if ($compression == 'gz') {
			$cmd = "gzip -f ".$outputdir."/".$file;
		} elseif ($compression == 'bz') {
			$cmd = "bzip2 -f ".$outputdir."/".$file;
		} elseif ($compression == 'zstd') {
			$cmd = "zstd -z -9 -q --rm ".$outputdir."/".$file;
		}

		$result = $utils->executeCLI($cmd, $outputfile);

		$retval = $result['error'];
		if ($result['result'] || !empty($retval)) {
			$errormsg = 'Error '.$compression.' generation return '.$retval;
			unlink($outputdir."/".$file);
		}
	}
} else {
	$errormsg = 'Bad value for compression method';
	print $errormsg;
}


// Output export

if ($export_type != 'externalmodule' || empty($what)) {
	top_httphead();

	if ($errormsg) {
		setEventMessages($langs->trans("Error")." : ".$errormsg, null, 'errors');
	} else {
		setEventMessages($langs->trans("BackupFileSuccessfullyCreated").'.<br>'.$langs->trans("YouCanDownloadBackupFile"), null, 'mesgs');
	}

	$db->close();

	// Redirect to calling page
	$returnto = 'dolibarr_export.php';

	header("Location: ".$returnto);

	exit();
} else {
	top_httphead('application/zip');

	$zipname = $outputdir."/".$file;

	// Then download the zipped file.

	header('Content-disposition: attachment; filename='.basename($zipname));
	header('Content-Length: '.filesize($zipname));
	readfile($zipname);

	dol_delete_file($zipname);

	$db->close();

	exit();
}
