<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

$acts[0] = "add";
$acts[1] = "delete";
$actl[0] = "Ajouter";
$actl[1] = "Enlever";

$tabid[1] = "llx_c_forme_juridique";
$tabid[2] = "llx_c_departements";
$tabid[3] = "llx_c_regions";

if (!$user->admin)
  accessforbidden();

if ($user->admin)
{
  if ($_GET["action"] == 'delete')
    {
      $sql = "UPDATE ".$tabid[$_GET["id"]]." SET active = 0 WHERE rowid=".$_GET["rowid"];
      
      $result = $db->query($sql);
      if (!$result)
	{
	  print $db->error();
	}
    }
  if ($_GET["action"] == 'add')
    {
      $sql = "UPDATE ".$tabid[$_GET["id"]]." SET active = 1 WHERE rowid=".$_GET["rowid"];
      
      $result = $db->query($sql);
      if (!$result)
	{
	  print $db->error();
	}
    }
}



llxHeader();

print_titre("Configuration forme juridique");

$active = 1;
$sql = array();
if ($_GET["id"])
{
  ShowTable($db,1, $_GET["id"], $actl, $acts);
  ShowTable($db,0, $_GET["id"], $actl, $acts);
}
else
{
  print '<a href="dict.php?id=1">Forme Juridique</a><br>';
  print '<a href="dict.php?id=2">Départements</a><br>';
  print '<a href="dict.php?id=3">Régions</a><br>';
}



$db->close();

llxFooter();

Function ShowTable($db, $active, $id, $actl, $acts)
{
  global $bc;
  $sql[1] = "SELECT rowid, code, libelle, active FROM llx_c_forme_juridique WHERE active = $active ORDER BY code ASC";
  $sql[2] = "SELECT rowid, code_departement as code , nom as libelle, active FROM llx_c_departements WHERE active = $active ORDER BY code ASC";
  $sql[3] = "SELECT rowid, code_region as code , nom as libelle, active FROM llx_c_regions WHERE active = $active ORDER BY code ASC";


  if ($db->query($sql[$id]))
    {
      $num = $db->num_rows();
      $i = 0; $var=True;
      if ($num)
	{
	  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
	  print '<tr class="liste_titre"><td>Code</td><td>Valeur</td><td>Type</td></tr>';      
	  while ($i < $num)
	    {
	      $obj = $db->fetch_object( $i);
	      $var=!$var;
	      
	      print "<tr $bc[$var] class=\"value\"><td width=\"10%\">\n";
	      print $obj->code.'</td><td>'.$obj->libelle.'</td><td width=\"30%\">';
	      print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&amp;id='.$id.'&amp;action='.$acts[$active].'">'.$actl[$active].'</a>';
	      print "</td></tr>\n";
	      $i++;
	    }
	  print '</table>';
	}
    }
}

?>
