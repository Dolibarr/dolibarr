<?PHP
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 */

/**
        \file       dev/skeletons/skeleton_script.php
        \ingroup    core
        \brief      Example for scripts
        \version    $Revision$
*/

// Test if batch mode
$sapi_type = php_sapi_name();
$script_file=__FILE__; 
if (eregi('([^\\\/]+)$',$script_file,$reg)) $script_file=$reg[1];
$path=eregi_replace($script_file,'',$_SERVER["PHP_SELF"]);

if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer $script_file en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

// Includes
require_once($path."../../htdocs/master.inc.php");

// Main
$version='$Revision$';
@set_time_limit(0);
$error=0;

$langs->load("main");


print "***** $script_file ($version) *****\n";


// -------------------- START OF YOUR CODE HERE --------------------

// Check parameters
if (! isset($argv[1])) {
    print "Usage: $script_file param1 param2 ...\n";
    exit;
}

// Show parameters
// print 'Arg1='.$argv[1]."\n";
// print 'Arg2='.$argv[2]."\n";


// An example of loading an object
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog("***** $script_file FETCH");
$myobject=new Skeleton_class($db);
$result=$myobject->fetch(1,$user);
if ($result < 0) dolibarr_print_error($db,$myobject->error);
*/


// An example of inserting an object in database
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog("***** $script_file DELETE");
$myobject=new Skeleton_class($db);
$result=$myobject->delete($user);
if ($result < 0) dolibarr_print_error($db,$myobject->error);
*/


// An example of updating an object in database
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog("***** $script_file UPDATE");
$myobject=new Skeleton_class($db);
$myobject->prop1='newvalue_prop1';
$myobject->prop2='newvalue_prop2';
$result=$myobject->update($user);
if ($result < 0) dolibarr_print_error($db,$myobject->error);
*/


// An example of deleting an object in database
/*
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");
dolibarr_syslog("***** $script_file DELETE");
$myobject=new Skeleton_class($db);
$result=$myobject->delete($user);
if ($result < 0) dolibarr_print_error($db,$myobject->error);
*/


// An example of a direct SQL read
/*
$sql = "SELECT field1, field2";
$sql.= " FROM ".MAIN_DB_PREFIX."c_pays";
$sql.= " WHERE field3 = 'xxx'";
$sql.= " ORDER BY field1 ASC";

dolibarr_syslog("***** $script_file sql=".$sql);
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


// -------------------- END OY YOUR CODE --------------------


return $error;
?>
