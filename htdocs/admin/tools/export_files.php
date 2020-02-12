<?php
/* Copyright (C) 2006-2014  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("admin");

$action=GETPOST('action', 'alpha');
$what=GETPOST('what', 'alpha');
$export_type=GETPOST('export_type', 'alpha');
$file=trim(GETPOST('zipfilename_template', 'alpha'));
$compression = GETPOST('compression');

$file = dol_sanitizeFileName($file);
$file = preg_replace('/(\.zip|\.tar|\.tgz|\.gz|\.tar\.gz|\.bz2)$/i', '', $file);

$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST("page", 'int');
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="date";
if ($page < 0) { $page = 0; }
elseif (empty($page)) $page = 0;
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$offset = $limit * $page;

if (! $user->admin) accessforbidden();

$errormsg='';


/*
 * Actions
 */

if ($action == 'delete')
{
    $filerelative = dol_sanitizeFileName(GETPOST('urlfile', 'alpha'));
    $filepath=$conf->admin->dir_output.'/'.$filerelative;
	$ret=dol_delete_file($filepath, 1);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", $filerelative), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", $filerelative), null, 'errors');
	$action='';
}


/*
 * View
 */

// Increase limit of time. Works only if we are not in safe mode
$ExecTimeLimit=1800;	// 30mn
if (!empty($ExecTimeLimit))
{
    $err=error_reporting();
    error_reporting(0);     // Disable all errors
    //error_reporting(E_ALL);
    @set_time_limit($ExecTimeLimit);   // Need more than 240 on Windows 7/64
    error_reporting($err);
}
$MemoryLimit=0;
if (!empty($MemoryLimit))
{
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
$result=dol_mkdir($outputdir);

$utils = new Utils($db);

if ($compression == 'zip')
{
	$file .= '.zip';
    $ret = dol_compress_dir(DOL_DATA_ROOT, $outputdir."/".$file, $compression, '/(\.log|\/temp\/|documents\/admin\/documents\/)/');
    if ($ret < 0)
    {
    	if ($ret == -2) {
    		$langs->load("errors");
    		$errormsg = $langs->trans("ErrNoZipEngine");
    	}
    	else {
    		$langs->load("errors");
    		$errormsg = $langs->trans("ErrorFailedToWriteInDir", $outputdir);
    	}
    }
}
elseif (in_array($compression, array('gz', 'bz')))
{
	$userlogin = ($user->login ? $user->login : 'unknown');

	$outputfile = $conf->admin->dir_temp.'/export_files.'.$userlogin.'.out';	// File used with popen method

    $file .= '.tar';
    // We also exclude '/temp/' dir and 'documents/admin/documents'
    $cmd = "tar -cf ".$outputdir."/".$file." --exclude-vcs --exclude 'temp' --exclude 'dolibarr.log' --exclude='documents/admin/documents' -C ".dirname(DOL_DATA_ROOT)." ".basename(DOL_DATA_ROOT);

    $result = $utils->executeCLI($cmd, $outputfile);

    $retval = $result['error'];
    if ($result['result'] || ! empty($retval))
    {
        $langs->load("errors");
        dol_syslog("Documents tar retval after exec=".$retval, LOG_ERR);
        $errormsg = 'Error tar generation return '.$retval;
    }
    else
    {
        if ($compression == 'gz')
        {
            $cmd = "gzip -f " . $outputdir."/".$file;
        }
        if ($compression == 'bz')
        {
            $cmd = "bzip2 -f " . $outputdir."/".$file;
        }

        $result = $utils->executeCLI($cmd, $outputfile);

        $retval = $result['error'];
        if ($result['result'] || ! empty($retval))
        {
            $errormsg = 'Error '.$compression.' generation return '.$retval;
            unlink($outputdir."/".$file);
        }
    }
}

if ($errormsg)
{
	setEventMessages($langs->trans("Error")." : ".$errormsg, null, 'errors');
}

// Redirect t backup page
header("Location: dolibarr_export.php");

$time_end = time();

$db->close();
