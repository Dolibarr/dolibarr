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
require("./pre.inc.php3");

llxHeader();

$db = new Db();

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
$db = new Db();


if ($action == 'add') {
  $sql = "INSERT INTO llx_chargesociales (fk_type, libelle, date_ech,amount) ";
  $sql .= " VALUES ($type,'$libelle','$date',$amount);";

  if (! $db->query($sql) ) {
    print $db->error();
  }
}

if ($action == 'del_bookmark') {
  $sql = "DELETE FROM llx_bookmark WHERE rowid=$bid";
  $result = $db->query($sql);
}

print_titre("Charges");

/*
 *
 *
 */

print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print "<TR class=\"liste_titre\">";
print '<td>Date</td><TD>Charges</td><td align="right">Montant</td><td>&nbsp;</td>';
print "</TR>\n";


$sql = "SELECT c.libelle as nom, s.amount, s.date_ech, s.date_pai, s.libelle";
$sql .= " FROM c_chargesociales as c, llx_chargesociales as s";
$sql .= " WHERE s.fk_type = c.id";
$sql .= " ORDER BY lower(s.date_ech) DESC";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    $var = !$var;
    print "<tr $bc[$var]>";
    print '<td>'.$obj->date_ech.'</td>';
    print '<td>'.$obj->nom.' '.$obj->libelle.'</td>';
    print '<td align="right">'.price($obj->amount).'</td>';
    print '<td><img src="/theme/'.$conf->theme.'/img/editdelete.png" border="0"></a></td>';
    print '</tr>';
    $i++;
  }
} else {
  print $db->error();
}
/*
 * 
 *
 *
 */
print '<tr><form method="post" action="index.php3">';
print '<input type="hidden" name="action" value="add">';
print '<td><input type="text" size="8" name="date"></td>';

print '<td><select name="type">';


$sql = "SELECT c.id, c.libelle as nom FROM c_chargesociales as c";
$sql .= " ORDER BY lower(c.libelle) ASC";

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    print '<option value="'.$obj->id.'">'.$obj->nom;
    $i++;
  }
}
print '</select>';

print '<input type="text" size="20" name="libelle"></td>';
print '<td align="right"><input type="text" size="6" name="amount"></td>';


print '<tr><td><input type="submit"></form></td>';

print '</table>';



$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
