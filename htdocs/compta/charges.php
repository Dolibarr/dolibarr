<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php");

llxHeader();

function valeur($sql) {
  global $db;
  if ( $db->query($sql) ) {
    if ( $db->num_rows() ) {
      $valeur = $db->result(0,0);
    }
    $db->free();
  }
  return $valeur;
}
/*
 *
 */

if ($action == 'add_bookmark') {
  $sql = "INSERT INTO llx_bookmark (fk_soc, dateb, fk_user) VALUES ($socidp, now(),".$user->id.");";
  if (! $db->query($sql) ) {
    print $db->error();
  }
}

if ($action == 'del_bookmark') {
  $sql = "DELETE FROM llx_bookmark WHERE rowid=$bid";
  $result = $db->query($sql);
}

print_titre("Charges");

print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="30%">';


print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
print "<TR class=\"liste_titre\">";
print "<td colspan=\"2\">Factures</td>";
print "</TR>\n";

$sql = "SELECT c.libelle as nom, sum(s.amount) as total";
$sql .= " FROM c_chargesociales as c, llx_chargesociales as s";
$sql .= " WHERE s.fk_type = c.id AND s.paye = 1";
$sql .= " GROUP BY lower(c.libelle) ASC";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    $var = !$var;
    print "<tr $bc[$var]>";
    print '<td>'.$obj->nom.'</td><td>'.price($obj->total).'</td>';
    print '</tr>';
    $i++;
  }
} else {
  print "<tr><td>".$db->error()."</td></tr>";
}


print "</table><br>";


/*
 *
 *
 */



print '</td></tr>';

print '</table>';

$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
