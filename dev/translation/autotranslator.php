#!/usr/bin/php
<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       dev/translation/autotranslator.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *      \brief      This file is an example for a command line script
 * 		\author		Put author name here
 *		\remarks	Put here some comments
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Include Dolibarr environment
require_once($path.'../../htdocs/master.inc.php');
require_once($path.'../../htdocs/core/lib/files.lib.php');
// After this $db is an opened handler to database. We close it at end of file.

// Load main language strings
$langs->load("main");

// Global variables
$version='1.14';
$error=0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0);
print "***** ".$script_file." (".$version.") *****\n";
$dir=DOL_DOCUMENT_ROOT."/langs";

// Check parameters
if (! isset($argv[2])) {
    print "Usage:   ".$script_file."  lang_code_src lang_code_dest|all [langfile.lang]\n";
    print "Example: ".$script_file."  en_US         pt_PT\n";
    print "Rem:     lang_code to use can be found on http://www.google.com/language_tools\n";
    exit;
}

// Show parameters
print 'Argument 1='.$argv[1]."\n";
print 'Argument 2='.$argv[2]."\n";
$file='';
if (isset($argv[3]))
{
	$file=$argv[3];
	print 'Argument 3='.$argv[3]."\n";
}
print 'Files will be generated/updated in directory '.$dir."\n";

if ($argv[2] != 'all')
{
	if (! is_dir($dir.'/'.$argv[2]))
	{
		print 'Create directory '.$dir.'/'.$argv[2]."\n";
		$result=mkdir($dir.'/'.$argv[2]);
		if (! $result)
		{
			$db->close();
			return -1;
		}
	}
}

require_once(DOL_DOCUMENT_ROOT."/../dev/translation/langAutoParser.class.php");

$langParser = new langAutoParser($argv[2],$argv[1],$dir,$file);

print "***** Finished *****\n";

// -------------------- END OF YOUR CODE --------------------

$db->close();

return $error;
?>