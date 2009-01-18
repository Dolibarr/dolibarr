<?PHP
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       dev/translation/autotranslator.php
 *		\ingroup    mymodule othermodule1 othermodule2
 *      \brief      This file is an example for a command line script
 *      \version    $Id$
 * 		\author		Put author name here
 *		\remarks	Put here some comments
 */

// Test if batch mode
$sapi_type = php_sapi_name();
$script_file=__FILE__;
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You ar usingr PH for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
// After this $db is an opened handler to database. We close it at end of file.

// Load main language strings
$langs->load("main");

// Global variables
$version='$Revision$';
$error=0;


// -------------------- START OF YOUR CODE HERE --------------------
@set_time_limit(0);
print "***** ".$script_file." (".$version.") *****\n";

// Check parameters
if (! isset($argv[2])) {
    print "Usage:   ".$script_file." lang_code_src lang_code_dest\n";
    print "Example: ".$script_file." en_US         pt_PT\n";
    exit;
}

// Show parameters
print 'Argument 1='.$argv[1]."\n";
print 'Argument 2='.$argv[2]."\n";
print 'Files will be generated/updated in directory '.DOL_DOCUMENT_ROOT."/langs\n";

// Examples for manipulating class skeleton_class
require_once(DOL_DOCUMENT_ROOT."/../dev/translation/langAutoParser.class.php");

$langParser = new langAutoParser($argv[2],$argv[1],DOL_DOCUMENT_ROOT.'/langs');

// -------------------- END OF YOUR CODE --------------------

$db->close();

return $error;
?>