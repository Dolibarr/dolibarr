<?php
/* Copyright (C) 2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
    	\file       dev/skeletons/skeleton_page.php
		\ingroup    unknown
		\brief      This file is an example of a php page
		\version    $Id$
		\author		Put author name here
		\remarks	Put here some comments
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");

// Load traductions files
$langs->load("companies");
$langs->load("other");

// Load permissions
$user->getrights("commercial");

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Protection quand utilisateur externe
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}
if ($socid == '') accessforbidden();



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

if ($_REQUEST["action"] == 'add')
{
	$myobject=new Skeleton_class($db);
	$myobject->prop1=$_POST["field1"];
	$myobject->prop2=$_POST["field2"];
	$result=$myobject->create($user);
	if ($result > 0)
	{
		// Creation OK
	}
	{
		// Creation KO
		$mesg=$myobject->error;
	}
}





/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/

llxHeader();

$html=new Form($db);


// Put here content of your page
// ...


// End of page
$db->close();
llxFooter('$Date$ - $Revision$');
?>
