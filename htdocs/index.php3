<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */
require("./pre.inc.php3");

llxHeader();

print "<h3>Dolibarr</h3>";

print translate("User") . ' : <b>' . $user->prenom . ' ' . $user->nom .'</b> ['.$user->code.']';
print "<p>";
/*
 * Boites
 *
 * TODO mettre les boites dans une table
 */

$db = new Db();

$sql = "SELECT b.rowid, b.box_id, d.file FROM llx_boxes as b, llx_boxes_def as d WHERE b.box_id = d.rowid";
$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);

      $boxs = "includes/boxes/".$obj->file;

      include($boxs);
      $i++;
    }
}

$db->close();


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>










