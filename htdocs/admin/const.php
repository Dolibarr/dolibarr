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

print_titre("Configuration Dolibarr");

print '<table border="1" cellpadding="3" cellspacing="0">';

$db = new Db();

if ($HTTP_POST_VARS["action"] == 'update')
{

  $sql = "UPDATE llx_const set value = '".$HTTP_POST_VARS["constvalue"]."' where rowid=".$HTTP_POST_VARS["rowid"].";";

  $result = $db->query($sql);
}

$sql = "SELECT rowid, name, value, type, note FROM llx_const";
$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);

      print '<tr><td>'.$obj->name.'</td><td>' . $obj->value . '</td><td>';

      if ($rowid == $obj->rowid && $action == 'edit')
	{
	  print '<form action="'.$PHP_SELF.'" method="POST">';
	  print '<input type="hidden" name="action" value="update">';
	  print '<input type="hidden" name="rowid" value="'.$rowid.'">';

	  if ($obj->type == 'yesno')
	    {
	      print '<select name="constvalue">';
	      
	      if ($obj->value == "1")
		{
		  print '<option value="0">non</option>';
		  print '<option value="1" SELECTED>oui</option>';
		}
	      else
		{
		  print '<option value="0" SELECTED>non</option>';
		  print '<option value="1">oui</option>';
		}
	      print '</select>';
	    }
	  else
	    {
	      print '<input type="text" name="constvalue" value="'.stripslashes($obj->value).'">';
	    }

	  print '<input type="submit">';
	  print '</form>';
	}
      else 
	{
	  print '<a href="'.$PHP_SELF.'?rowid='.$obj->rowid.'&action=edit">edit</a>';
	}

      print '</td></tr>';
      print '<tr><td colspan="3">'.stripslashes(nl2br($obj->note)).'</td></tr>';
      $i++;
    }
}


print '</table>';

$db->close();

llxFooter();
?>
