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
 *      \file       scripts/modulebuilder/builddoc.php
 *      \ingroup    modulebuilder
 *      \brief      Script to build a documentation from input files (.asciidoc or .md files). Use asciidoctor tool.
 *
 *		If file is a MD file, convert image links into asciidoc format.
 *      ![Screenshot patient card](img/dolimed_screenshot_patientcard.png?raw=true "Patient card")
 *		image:img/dolimed_screenshot_patientcard.png[Screenshot patient card]
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
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';

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

$FILENAMEDOC=strtolower($module).'.html';			// TODO Use/text PDF
$dirofmodule = dol_buildpath(strtolower($module), 0).'/doc';
$outputfiledoc = $dirofmodule.'/'.$FILENAMEDOC;

$util = new Utils($db);
$result = $util->generateDoc($module);

if ($result <= 0)
{
	print $util->errors;
	exit(1);
}

print $langs->trans("DocFileGeneratedInto", $outputfiledoc);
exit(0);
