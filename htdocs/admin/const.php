<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

print_titre("Constantes de configuration Dolibarr");

//print_r(get_defined_constants());

print '<table border="1" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<TD>Nom</TD>';
print '<TD>Valeur</TD>';
print '<TD>Type</TD>';
print '<TD>Note</TD>';
print "<TD>Action</TD>";
print "</TR>\n";

$db = new Db();
$form = new Form($db);

if ($user->admin)
{
  if ($HTTP_POST_VARS["action"] == 'update' || $HTTP_POST_VARS["action"] == 'add')
    {
  
      if ($HTTP_POST_VARS["consttype"] == 0){
	$sql = "REPLACE INTO llx_const SET name='".$_POST["constname"]."', value = '".$HTTP_POST_VARS["constvalue"]."',note='".$HTTP_POST_VARS["constnote"]."', type='yesno'";
      }else{
	$sql = "REPLACE INTO llx_const SET name='".$_POST["constname"]."', value = '".$HTTP_POST_VARS["constvalue"]."',note='".$HTTP_POST_VARS["constnote"]."'";
      }
      
      
      $result = $db->query($sql);
      if (!$result)
	{
	  print $db->error();
	}
    }

  if ($action == 'delete')
    {
      $sql = "DELETE FROM llx_const WHERE rowid='$rowid'";
      
      $result = $db->query($sql);
      if (!$result)
	{
	  print $db->error();
	}
    }
}

$sql = "SELECT rowid, name, value, type, note FROM llx_const";
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

      print '<form action="'.$PHP_SELF.'" method="POST">';
      print '<input type="hidden" name="action" value="update">';
      print '<input type="hidden" name="rowid" value="'.$rowid.'">';
      print '<input type="hidden" name="constname" value="'.$obj->name.'">';

      print "<tr $bc[$var] class=value><td>$obj->name</td>\n";

      print '<td>';
      if ($obj->type == 'yesno')
	{
	  $form->selectyesnonum('constvalue',$obj->value);
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte'),0);
	}
      else
	{
	  print '<input type="text" size="15" name="constvalue" value="'.stripslashes($obj->value).'">';
	  print '</td><td>';
	  $form->select_array('consttype',array('yesno','texte'),1);
	}
      print '</td><td>';

      print '<input type="text" size="15" name="constnote" value="'.stripslashes(nl2br($obj->note)).'">';
      print '</td><td>';
      print '<input type="Submit" value="Update" name="Button"><BR>';
      print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&action=delete">Delete</a>';
      print "</td></tr>\n";

      print '</form>';
      $i++;
    }
}
$var=!$var;

print '<form action="'.$PHP_SELF.'" method="POST">';
print '<input type="hidden" name="action" value="add">';

print "<tr $bc[$var] class=value><td><input type=\"text\" size=\"15\" name=\"constname\" value=\"\"></td>\n";

print '<td>';
print '<input type="text" size="15" name="constvalue" value="">';
print '</td><td>';

$form->select_array('consttype',array('yesno','texte'),1);
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
