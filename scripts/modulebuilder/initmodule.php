#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2005-2013	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *      \file       scripts/modulebuilder/initmodule.php
 *      \ingroup    modulebuilder
 *      \brief      Script to initialize a module.
 */


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

if (! isset($argv[1]) || ! $argv[1]) {
	print "Usage: ".$script_file." ModuleName\n";
	exit(-1);
}
$modulename=$argv[1];

require_once ($path."../../htdocs/master.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/modulebuilder.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

$langs->loadLangs(array("admin", "modulebuilder", "other", "cron"));


// Global variables
$version=DOL_VERSION;
$error=0;

// Dir for custom dirs
$tmp=explode(',', $dolibarr_main_document_root_alt);
$dirins = $tmp[0];
$dirread = $dirins;
$forceddirread = 0;

$tmpdir = explode('@', $module);
if (! empty($tmpdir[1]))
{
	$module=$tmpdir[0];
	$dirread=$tmpdir[1];
	$forceddirread=1;
}

$FILEFLAG='modulebuilder.txt';

$now=dol_now();
$newmask = 0;
if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
if (empty($newmask))	// This should no happen
{
	$newmask='0664';
}


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
print "modulename=".$modulename."\n";
print "dirins=".$dirins."\n";

if (preg_match('/[^a-z0-9_]/i', $modulename))
{
	$error++;
	print 'Error '.$langs->trans("SpaceOrSpecialCharAreNotAllowed")."\n";
	exit(1);
}

if (! $error)
{
	$srcdir = DOL_DOCUMENT_ROOT.'/modulebuilder/template';
	$destdir = $dirins.'/'.strtolower($modulename);

	$arrayreplacement=array(
	'mymodule'=>strtolower($modulename),
	'MyModule'=>$modulename
	);

	$result = dolCopyDir($srcdir, $destdir, 0, 0, $arrayreplacement);
	//dol_mkdir($destfile);
	if ($result <= 0)
	{
		if ($result < 0)
		{
			$error++;
			$langs->load("errors");
			print $langs->trans("ErrorFailToCopyDir", $srcdir, $destdir)."\n";
			exit(2);
		}
		else	// $result == 0
		{
			print $langs->trans("AllFilesDidAlreadyExist", $srcdir, $destdir)."\n";
		}
	}

	// Delete some files
	dol_delete_file($destdir.'/myobject_card.php');
	dol_delete_file($destdir.'/myobject_note.php');
	dol_delete_file($destdir.'/myobject_document.php');
	dol_delete_file($destdir.'/myobject_agenda.php');
	dol_delete_file($destdir.'/myobject_list.php');
	dol_delete_file($destdir.'/lib/myobject.lib.php');
	dol_delete_file($destdir.'/test/phpunit/MyObjectTest.php');
	dol_delete_file($destdir.'/sql/llx_mymodule_myobject.sql');
	dol_delete_file($destdir.'/sql/llx_mymodule_myobject_extrafields.sql');
	dol_delete_file($destdir.'/sql/llx_mymodule_myobject.key.sql');
	dol_delete_file($destdir.'/scripts/myobject.php');
	dol_delete_file($destdir.'/img/object_myobject.png');
	dol_delete_file($destdir.'/class/myobject.class.php');
	dol_delete_file($destdir.'/class/api_mymodule.class.php');
}

// Edit PHP files
if (! $error)
{
	$listofphpfilestoedit = dol_dir_list($destdir, 'files', 1, '\.(php|MD|js|sql|txt|xml|lang)$', '', 'fullname', SORT_ASC, 0, 1);
	foreach($listofphpfilestoedit as $phpfileval)
	{
		//var_dump($phpfileval['fullname']);
		$arrayreplacement=array(
		'mymodule'=>strtolower($modulename),
		'MyModule'=>$modulename,
		'MYMODULE'=>strtoupper($modulename),
		'My module'=>$modulename,
		'my module'=>$modulename,
		'Mon module'=>$modulename,
		'mon module'=>$modulename,
		'htdocs/modulebuilder/template'=>strtolower($modulename),
		'---Put here your own copyright and developer email---'=>dol_print_date($now,'%Y').' '.$user->getFullName($langs).($user->email?' <'.$user->email.'>':'')
		);


		$result=dolReplaceInFile($phpfileval['fullname'], $arrayreplacement);
		//var_dump($result);
		if ($result < 0)
		{
			print $langs->trans("ErrorFailToMakeReplacementInto", $phpfileval['fullname'])."\n";
			exit(3);
		}
	}
}

print 'Module initialized'."\n";
exit(0);

