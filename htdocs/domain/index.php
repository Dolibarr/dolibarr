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

if ($action == 'add') {
  $author = $GLOBALS["REMOTE_USER"];

  $sql = "INSERT INTO llx_voyage (date_depart, date_arrivee, amount, depart, arrivee, fk_reduc, reduction) ";
  $sql .= " VALUES ('$date_depart','$date_arrivee',$amount,'$depart','$arrivee',$reducid, $reduc);";

  $result = $db->query($sql);
  if ($result) {
    $rowid = $db->last_insert_id();

  } else {
    print $db->error();
    print "<p>$sql";
  }

}
if ($action == 'del') {
  /*  $sql = "DELETE FROM llx_voyage WHERE rowid = $rowid";
   *$result = $db->query($sql);
   */
}

if ($vline) {
  $viewline = $vline;
} else {
  $viewline = 20;
}


print_titre("Noms de domaines internet");


$sql = "SELECT label ";
$sql .= " FROM llx_domain ORDER BY label ASC";

$result = $db->query($sql);
if ($result) {

  print "<form method=\"post\" action=\"$PHP_SELF?viewall=$viewall&vline=$vline&account=$account\">";
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR class=\"liste_titre\">";
  print "<td>Date</td><td>Description</TD>";
  print "<td align=\"right\">Montant</TD>";
  print "<td align=\"right\">Réduction</td>";
  print "</TR>\n";
  

  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;

  $sep = 0;

  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $total = $total + $objp->amount;
    $time = time();

    $var=!$var;

    print "<tr $bc[$var]>";

    print "<td>$objp->label</td>";

    print '<td><a href="http://www.'.$objp->label.'/">www.'.$objp->label.'</a></TD>';

    print "</tr>";


    $i++;
  }
  $db->free();



  print "</table></form>";
} else {
  print "<p>".$db->error();

}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
