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
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 *		\file 		htdocs/admin/tools/export.php
 *		\brief      Page to export a database into a dump file
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

$langs->load("admin");

$action=GETPOST('action','alpha');
$what=GETPOST('what','alpha');
$export_type=GETPOST('export_type','alpha');
$file=GETPOST('zipfilename_template','alpha');
$compression = GETPOST('compression');

$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST("page",'int');
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="date";
if ($page < 0) { $page = 0; }
elseif (empty($page)) $page = 0;
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page;

if (! $user->admin) accessforbidden();

$errormsg='';


/*
 * Actions
 */

if ($action == 'delete')
{
	$file=$conf->admin->dir_output.'/'.GETPOST('urlfile');
	$ret=dol_delete_file($file, 1);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	$action='';
}


/*
 * View
 */

// Increase limit of time. Works only if we are not in safe mode
$ExecTimeLimit=600;
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

$form=new Form($db);
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
    $ret = dol_compress_dir(DOL_DATA_ROOT, $outputdir."/".$file, $compression);
    if ($ret < 0)
    {
        $errormsg = $langs->trans("ErrorFailedToWriteInDir",$outputfile);
    }
}
elseif (in_array($compression, array('gz', 'bz')))
{
    $file = substr($file, 0, strrpos($file, '.'));
    $file .= '.tar';
    $cmd = 'tar -cf '.$outputdir."/".$file." --exclude=documents/admin/documents -C ".DOL_DATA_ROOT." ".DOL_DATA_ROOT."/../documents/";
    exec($cmd, $out, $retval);
    //var_dump($cmd, DOL_DATA_ROOT);exit;
    
    if ($retval != 0)
    {
        $langs->load("errors");
        dol_syslog("Documents tar retval after exec=".$retval, LOG_ERR);
        $errormsg = 'Error tar generation return '.$retval;
    }
    else
    {
        if ($compression == 'gz')
        {
            $cmd = "gzip " . $outputdir."/".$file;
        }
        if ($compression == 'bz')
        {
            $cmd = "bzip2 " . $outputdir."/".$file;
        }
        
        exec($cmd, $out, $retval);
        if ($retval != 0)
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

print '<br>';


// Redirect t backup page
header("Location: dolibarr_export.php");

$time_end = time();

$db->close();

