<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./reduc.class.php3");


llxHeader();

if ($action == 'add') {
  $author = $GLOBALS["REMOTE_USER"];

  $sql = "INSERT INTO llx_voyage (date_depart, date_arrivee, amount, depart, arrivee, fk_reduc) ";
  $sql .= " VALUES ('$date_depart','$date_arrivee',$amount,'$depart','$arrivee',$reduc);";

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

$sql = "SELECT rowid, label FROM llx_voyage_reduc;";
$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0;
  $options = "<option value=\"0\" SELECTED></option>";
  while ($i < $num) {
    $obj = $db->fetch_object($i);
    $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
  }
  $db->free();
}


print_titre("Bilan des reductions");
/*
 * Cartes
 *
 */
$sql = "SELECT rowid FROM llx_voyage_reduc";

$result = $db->query($sql);
if ($result) {
  $cartes= array();
  $i = 0;
  while ($i < $num) {
    $obj = $db->fetch_object($i);
    $cartes[$i] = $obj->rowid;
    $i++;
  }
  $db->free;
}
/*
 *
 */

for ($j = 0 ; $j < sizeof($cartes) ; $j++) {

  $reduc = new Reduc($db);
  $reduc->fetch($cartes[$j]);

  print "<TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<TR>";
  print '<td colspan="2">Description</TD>';
  print '<td align="right">Montant</TD>';
  print '<td>&nbsp;</TD>';
  print "</TR>\n";

  print '<tr><td colspan="2">'.$reduc->label.'</td><td align="right">'.$reduc->price.'</td>';
  print '<td>&nbsp;</TD></tr>';
 
  /*
   *
   */
  $sql = "SELECT b.rowid,".$db->pdate("b.date_depart")." as date_depart,".$db->pdate("b.date_arrivee")." as date_arrivee, b.amount, b.depart, b.arrivee , b.reduction";
  $sql .= " FROM llx_voyage as b WHERE fk_reduc=".$reduc->id." ORDER BY b.date_depart ASC"; 

  $result = $db->query($sql);
  if ($result) {

    print "<TR class=\"liste_titre\">";
    print "<td>Date</td><td>Description</TD>";
    print "<td align=\"right\">Montant</TD>";
    print "<td align=\"right\">Réduction</td>";
    print "</TR>\n";
  

    $var=True;  
    $num = $db->num_rows();
    $i = 0; 
    $total = 0;
    $total_reduc = 0;

    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $total = $total + $objp->amount;
      $total_reduc = $total_reduc + $objp->reduction;
      $time = time();

      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".strftime("%d&nbsp;%b&nbsp;%y&nbsp;%H:%M",$objp->date_depart)."<br>\n";
      print "".strftime("%d %b %y %H:%M",$objp->date_arrivee)."</TD>\n";

      print "<td>$objp->depart<br>$objp->arrivee</td>";

      print "<td align=\"right\">".price($objp->amount)."</TD>\n";
      print "<td align=\"right\">".price($objp->reduction)."</TD>\n";

      print "</tr>";


      $i++;
    }
    $db->free();


    print "<tr><td align=\"right\" colspan=\"2\">Total :</td>";
    print "<td align=\"right\"><b>".price($total)."</b></td><td align=\"right\">".price($total_reduc)."</td></tr>\n";

    print "<tr><td align=\"right\" colspan=\"3\">Carte :</td>";
    print "<td align=\"right\">".price($reduc->price)."</td></tr>\n";

    print "<tr><td align=\"right\" colspan=\"3\">Gain :</td>";
    print "<td align=\"right\">".price($total_reduc - $reduc->price)."</td></tr>\n";



    print "</table>";
  } else {
    print "<p>".$db->error();

  }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
