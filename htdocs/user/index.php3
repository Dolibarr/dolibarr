<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
$db = new Db();

print_titre("Liste des utilisateurs");

$sql = "SELECT u.rowid, u.name, u.firstname, u.code, u.login, u.module_comm, u.module_compta FROM llx_user as u";
$sql .= " ORDER BY u.name";

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
  $i = 0;
  
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print '<TR class="liste_titre">';
  print "<TD>Prenom</TD>";
  print "<TD>Nom</TD>";
  print "<TD>code</TD>";
  print '<TD>login</TD><td align="center">Comm.</td><td align="center">Compta</td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    $var=!$var;
    
    print "<TR $bc[$var]>";
    print '<TD><a href="fiche.php3?id='.$obj->rowid.'">'.$obj->firstname.'</a></td>';
    print '<TD>'.$obj->name.'</TD>';
    print '<TD>'.$obj->code.'</TD>';
    print '<TD><a href="fiche.php3?id='.$obj->rowid.'">'.$obj->login.'</a></TD>';
    print '<TD align="center">'.$obj->module_comm.'</TD>';
    print '<TD align="center">'.$obj->module_compta.'</TD>';
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
