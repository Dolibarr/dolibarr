#!/usr/bin/env php
<?php
/* Copyright (C) 2015   Jean-François Ferry <jfefe@aternatik.fr>
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
 *	\file       dev/skeletons/build_api_class.php
 *  \ingroup    core
 *  \brief      Create a complete API class file from existant class file
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
require_once($path."../../htdocs/master.inc.php");
// After this $db is a defined handler to database.

// Main
$version='1';
@set_time_limit(0);
$error=0;

$langs->load("main");


print "***** $script_file ($version) *****\n";


// -------------------- START OF BUILD_API_FROM_CLASS --------------------

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
$classmin=strtolower($classname);
$classnameApi = $classname.'Api';
$property=array();
$targetcontent='';

// Load the class and read properties
require_once($classfile);

$property=array();
$class = new $classname($db);
$values=get_class_vars($classname);

unset($values['db']);
unset($values['error']);
unset($values['errors']);
unset($values['element']);
unset($values['table_element']);
unset($values['table_element_line']);
unset($values['fk_element']);
unset($values['ismultientitymanaged']);

// Read skeleton_api_class.class.php file
$skeletonfile=$path.'skeleton_api_class.class.php';
$sourcecontent=file_get_contents($skeletonfile);
if (! $sourcecontent)
{
	print "\n";
	print "Error: Failed to read skeleton sample '".$skeletonfile."'\n";
	print "Try to run script from skeletons directory.\n";
	exit;
}

// Define output variables
$outfile='out.api_'.$classmin.'.class.php';
$targetcontent=$sourcecontent;

// Substitute class name
$targetcontent=preg_replace('/skeleton_api_class\.class\.php/', 'api_'.$classmin.'.class.php', $targetcontent);
$targetcontent=preg_replace('/skeleton/', $classmin, $targetcontent);
//$targetcontent=preg_replace('/\$table_element=\'skeleton\'/', '\$table_element=\''.$tablenoprefix.'\'', $targetcontent);
$targetcontent=preg_replace('/SkeletonApi/', $classnameApi, $targetcontent);
$targetcontent=preg_replace('/Skeleton/', $classname, $targetcontent);

// Build file
$fp=fopen($outfile,"w");
if ($fp)
{
	fputs($fp, $targetcontent);
	fclose($fp);
	print "\n";
	print "File '".$outfile."' has been built in current directory.\n";
}
else $error++;



// -------------------- END OF BUILD_CLASS_FROM_TABLE SCRIPT --------------------

print "You can now rename generated files by removing the 'out.' prefix in their name and store them into directory /module/class.\n";
return $error;
