<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

if (!$user->admin)
  accessforbidden();


llxHeader();

print_titre("Configuration autre (Dolibarr version ".DOL_VERSION.")");

//print_r(get_defined_constants());
print "<br>\n";


$typeconst=array('yesno','texte','chaine');

if ($_POST["action"] == 'update' || $_POST["action"] == 'add')
{
	if (! dolibarr_set_const($db, $_POST["constname"],$_POST["constvalue"],$typeconst[$_POST["consttype"]],1,isset($_POST["constnote"])?$_POST["constnote"]:''));
	{
	  	print $db->error();
	}
}

if ($_GET["action"] == 'delete')
{
	if (! dolibarr_del_const($db, $_GET["rowid"]));
	{
	  	print $db->error();
	}
}



print '<table class="noborder" cellpadding="2" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td>';
print '<td>Type</td>';
print '<td>Note</td>';
print "<td>Action</td>";
print "</tr>\n";


# Affiche lignes des constantes
$form = new Form($db);

if ($all==1){
  $sql = "SELECT rowid, name, value, type, note FROM llx_const ORDER BY name ASC";
}else{
  $sql = "SELECT rowid, name, value, type, note FROM llx_const WHERE visible = 1 ORDER BY name ASC";
}
$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;

      print '<form action="const.php" method="POST">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="rowid" value="'.$rowid.'">';
      print '<input type="hidden" name="constname" value="'.$obj->name.'">';

      print "<tr $bc[$var] class=value><td>$obj->name</td>\n";

      print '<td>';
      if ($obj->type == 'yesno')
	{
	  $form->selectyesnonum('constvalue',$obj->value);
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),0);
	}
      elseif ($obj->type == 'texte')
	{
	  print '<textarea name="constvalue" cols="35" rows="4" wrap="soft">';
	  print $obj->value;
	  print "</textarea>\n";
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),1);
	}
      else
	{
	  print '<input type="text" size="30" name="constvalue" value="'.stripslashes($obj->value).'">';
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte','chaine'),2);
	}
      print '</td><td>';

      print '<input type="text" size="15" name="constnote" value="'.stripslashes(nl2br($obj->note)).'">';
      print '</td><td>';
      print '<input type="Submit" value="Update" name="Button"> &nbsp; ';
      print '<a href="const.php?rowid='.$obj->rowid.'&action=delete">'.img_delete().'</a>';
      print "</td></tr>\n";

      print '</form>';
      $i++;
    }
}


# Affiche ligne d'ajout
$var=!$var;
print '<form action="const.php" method="POST">';
print '<input type="hidden" name="action" value="add">';

print "<tr $bc[$var] class=value><td><input type=\"text\" size=\"15\" name=\"constname\" value=\"\"></td>\n";

print '<td>';
print '<input type="text" size="30" name="constvalue" value="">';
print '</td><td>';

$form->select_array('consttype',array('yesno','texte','chaine'),1);
print '</td><td>';

print '<input type="text" size="15" name="constnote" value="">';
print '</td><td>';

print '<input type="Submit" value="Add" name="Button"><BR>';
print "</td>\n";
print '</form>';
	
print '</tr>';



print '</table>';

$db->close();

llxFooter();
?>
