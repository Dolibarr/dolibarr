<?php
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
 * $Source$
 */

/**
    	\file       dev/skeletons/skeleton_page.php
		\ingroup    core
		\brief      Example of a php page
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");
require("osc_categories.class.php");
//require_once(DOL_DOCUMENT_ROOT."/../dev/skeletons/skeleton_class.class.php");

// Load traductions files
$langs->load("companies");
$langs->load("other");

// Load permissions
//$user->getrights("commercial");
//if (!$user->rights->categorie->lire) accessforbidden();

// Get parameters
$socid = isset($_GET["socid"])?$_GET["socid"]:'';

// Protection quand utilisateur externe
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}
//if ($socid == '') accessforbidden();



/*******************************************************************
* ACTIONS
*
* Put here all code to do according to value of "action" parameter
********************************************************************/

if ($_REQUEST["action"] == 'maj')
{
	$myobject=new Osc_categorie($db);
	if ($myobject->fetch_dolicat($_POST["dolicat"]) <0)
	{
		$mesg = "erreur dans fetch_dolicat";
	}
	elseif ($myobject->id > 0) 
	{
		$myobject->dolicatid=$_POST["dolicat"];
		$myobject->osccatid=$_POST["osccat"];
	
		$result=$myobject->update($user);
		if ($result > 0)
		{
			// Creation OK
			$mesg="";
		}
		else
		{
			// Creation KO
			$mesg=$myobject->error;
		}

	}
	else
	{
		$myobject->dolicatid=$_POST["dolicat"];
		$myobject->osccatid=$_POST["osccat"];
	
		$result=$myobject->create($user);
		if ($result > 0)
		{
			// Creation OK
			$mesg="";
		}
		else
		{
			// Creation KO
			$mesg=$myobject->error;
		}
	}
}





/***************************************************
* PAGE
*
* Put here all code to build page
****************************************************/

llxHeader();

$html=new Form($db);

if ($mesg) print '<div class="ok">'.$mesg.'</div>';

// Put here content of your page
// ...
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT  c.label, c.rowid dolicatid, oc.osccatid FROM ".MAIN_DB_PREFIX."categorie as c ";
$sql .= "LEFT OUTER JOIN llx_osc_categories as oc ON oc.dolicatid = c.rowid ";
$sql .= "WHERE c.visible = 1";

print_barre_liste("Correspondance des catégories", $page, "categories.php");

	dolibarr_syslog("Osc_Categorie.class::get_Osccat sql=".$sql);
   $resql=$db->query($sql);
   if ($resql)
	{
	   $num = $db->num_rows($resql);
	   $i = 0;

		//titre
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>Id</td><td>Label</td><td>Osc_id</td><td>Action</td>';
		print '</tr>'."\n";

   	$var=true;
   	$oscid = 1;
    while ($i < min($num,$limit))
    {
         $obj = $db->fetch_object($resql);
         $var=!$var;
   	   print "\t<tr ".$bc[$var].">\n";
   	   print "\t\t<td><a href='../../categories/viewcat.php?id=".$obj->dolicatid."'>".$obj->dolicatid."</a></td>\n";
   	   print "\t\t<td><a href='../../categories/viewcat.php?id=".$obj->dolicatid."'>".$obj->label."</a></td>\n";
   	   print '<td><form action="categories.php" METHOD="POST"><input type="text" size="5" name="osccat" value="'.$obj->osccatid.'"/></td>'."\n";
   	   print '<input type="hidden" name="action" value="maj"/>';
   	   print '<input type="hidden" name="dolicat" value="'.$obj->dolicatid.'"/>';
   	   print '<td align="center"><input type="submit" class="button" value="'.$langs->trans('maj').'"></td>';
   	   print "\t</tr></form>\n";
   	   $i++;
   	 }

		print '</table>';
	}
	else
	{
  		dolibarr_print_error();
	}


// End of page
$db->close();
llxFooter('$Date$ - $Revision$');
?>
