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
 *	\file       dev/skeletons/build_webservice_from_class.php
 *  \ingroup    core
 *  \brief      Create a complete webservice file from CRUD functions of a PHP class
 *  \version    $Id$
 */

// Test if batch mode
$sapi_type = php_sapi_name();
$script_file=__FILE__;
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

if (substr($sapi_type, 0, 3) == 'cgi')
{
	echo "Error: You use PHP for CGI mode. To execute $script_file as a command line program, you must use PHP for CLI mode (try php-cli).\n";
	exit;
}

// Include Dolibarr environment
require_once($path."../../htdocs/master.inc.php");
// After this $db is a defined handler to database.

// Main
$version='$Revision$';
@set_time_limit(0);
$error=0;

$langs->load("main");


print "***** $script_file ($version) *****\n";


// -------------------- START OF BUILD_CLASS_FROM_TABLE SCRIPT --------------------

// Check parameters
if (! isset($argv[1]) && ! isset($argv[2]))
{
    print "Usage: $script_file phpClassFile phpClassName\n";
    exit;
}

// Show parameters
print 'Classfile='.$argv[1]."\n";
print 'Classname='.$argv[2]."\n";

$classfile=$argv[1];
$classname=$argv[2];
$property=array();
$outfile='webservice_'.dol_sanitizeFileName($classfile).'.php';
$targetcontent='';

// This script must load the class, found the CRUD function and build a web service to call this functions.
// TODO ...





// Build file
$fp=fopen($outfile,"w");
if ($fp)
{
	fputs($fp, $targetcontent);
	fclose($fp);
	print "File '".$outfile."' has been built in current directory.\n";
}
else $error++;

// -------------------- END OF BUILD_CLASS_FROM_TABLE SCRIPT --------------------

print "You must rename files by removing the 'out.' prefix in their name.\n";
return $error;
?>
