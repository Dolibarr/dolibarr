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

print_titre("Boites affichées");

$db = new Db();

if ($HTTP_POST_VARS["action"] == 'add')
{

  $sql = "INSERT INTO llx_boxes (box_id, position) values (".$HTTP_POST_VARS["rowid"].",".$HTTP_POST_VARS["constvalue"].");";

  $result = $db->query($sql);
}

if ($action == 'delete')
{
  $sql = "DELETE FROM llx_boxes WHERE rowid=$rowid";

  $result = $db->query($sql);
}


/*
 *
 *
 *
 */
$boxes = array();

$pos[0] = "Homepage";

print '<table border="1" cellpadding="3" cellspacing="0">';

$sql = "SELECT b.rowid, b.box_id, b.position, d.name FROM llx_boxes as b, llx_boxes_def as d where b.box_id = d.rowid";
$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);

      print '<tr><td>'.$obj->name.'</td><td>' . $pos[$obj->position] . '</td><td>';


      print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&action=delete">Supprimer</a>';

      array_push($boxes, $obj->box_id);

      print '</td></tr>';

      $i++;
    }
}
print '</table>';

/*
 *
 *
 *
 */
print "<p>";
print_titre("Boites disponibles");
print '<table border="1" cellpadding="3" cellspacing="0">';

$sql = "SELECT rowid, name, file FROM llx_boxes_def";
$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);

      print '<tr><td>'.$obj->name.'</td><td>' . $obj->file . '</td><td align="center">';

      if ($rowid == $obj->rowid && $action == 'edit')
	{
	  print '<form action="'.$PHP_SELF.'" method="POST">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="rowid" value="'.$rowid.'">';

	  print '<select name="constvalue">';
	  
	  print '<option value="0">Homepage</option>';
	  /*
	  print '<option value="1">Gauche</option>';
	  print '<option value="1">Droite</option>';
	  */

	  print '</select>';
	
	  print '<input type="submit" value="Ajouter">';
	  print '</form>';
	}
      else 
	{
	  if (! in_array($obj->rowid, $boxes))
	    {
	      print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&action=edit">Ajouter</a>';
	    }
	  else
	    {
	      print "&nbsp;";
	    }
	}

      print '</td></tr>';

      $i++;
    }
}
print '</table>';

$db->close();

llxFooter();
?>
