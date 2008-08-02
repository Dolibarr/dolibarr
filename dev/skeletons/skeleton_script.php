<?PHP
/* Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
        \file       dev/skeletons/skeleton_script.php
		\ingroup    mymodule othermodule1 othermodule2
        \brief      This file is an example for a command line script
        \version    $Id$
		\author		Put author name here
		\remarks	Put here some comments
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

print "***** ".$script_file." (".$version.") *****\n";

// Check parameters
if (! isset($argv[1])) {
    print "Usage: ".$script_file." param1 param2 ...\n";
    exit;
}
@set_time_limit(0);

// Show parameters
print 'Argument 1='.$argv[1]."\n";
print 'Argument 2='.$argv[2]."\n";


// Example for inserting creating object in database
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog($script_file." CREATE", LOG_DEBUG);
$myobject=new Skeleton_class($db);
$id=$myobject->create($user);
if ($id < 0) dolibarr_print_error($db,$myobject->error);
*/


// Example for reading object from database
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog($script_file." FETCH", LOG_DEBUG);
$myobject=new Skeleton_class($db);
$id=1;
$result=$myobject->fetch($id);
if ($result < 0) dolibarr_print_error($db,$myobject->error);
*/


// Example for updating object in database
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog($script_file." UPDATE", LOG_DEBUG);
$myobject=new Skeleton_class($db);
$myobject->prop1='newvalue_prop1';
$myobject->prop2='newvalue_prop2';
$result=$myobject->update($user);
if ($result < 0) dolibarr_print_error($db,$myobject->error);
*/


// Example for deleting object in database
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog($script_file." DELETE", LOG_DEBUG);
$myobject=new Skeleton_class($db);
$result=$myobject->delete($user);
if ($result < 0) dolibarr_print_error($db,$myobject->error);
*/


// An example of a direct SQL read without using the fetch method
/*
$sql = "SELECT field1, field2";
$sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
$sql.= " WHERE field3 = 'xxx'";
$sql.= " ORDER BY field1 ASC";

dolibarr_syslog($script_file." sql=".$sql, LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				// You can use here results
				print $obj->field1;
				print $obj->field2;
			}
			$i++;
		}
	}
}
else
{
	dolibarr_print_error($db);
	exit;
}
*/


// -------------------- END OF YOUR CODE --------------------

$db->close();

return $error;
?>
