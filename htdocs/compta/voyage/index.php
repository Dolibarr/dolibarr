<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

require("./pre.inc.php");

llxHeader();

if ($action == 'add') {

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."voyage (date_depart, date_arrivee, amount, depart, arrivee, fk_reduc, reduction) ";
  $sql .= " VALUES ('$date_depart','$date_arrivee',$amount,'$depart','$arrivee',$reducid, $reduc);";

  $result = $db->query($sql);
  if ($result) {
    $rowid = $db->last_insert_id(MAIN_DB_PREFIX."voyage");

  } else {
    print $db->error();
    print "<p>$sql";
  }

}
if ($action == 'del') {
  /*  $sql = "DELETE FROM ".MAIN_DB_PREFIX."voyage WHERE rowid = $rowid";
   *$result = $db->query($sql);
   */
}

if ($vline) {
  $viewline = $vline;
} else {
  $viewline = 20;
}

$sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."voyage_reduc;";
$result = $db->query($sql);
if ($result) {
  $var=True;  
  $num = $db->num_rows();
  $i = 0;
  $options = "<option value=\"0\" selected=\"true\"></option>";
  while ($i < $num) {
    $obj = $db->fetch_object($result);
    $options .= "<option value=\"$obj->rowid\">$obj->label</option>\n"; $i++;
  }
  $db->free();
}


print_titre("Voyages");


$sql = "SELECT b.rowid,".$db->pdate("b.date_depart")." as date_depart,".$db->pdate("b.date_arrivee")." as date_arrivee, b.amount, b.reduction,b.depart, b.arrivee ";
$sql .= " FROM ".MAIN_DB_PREFIX."voyage as b ORDER BY b.date_depart ASC"; 

$result = $db->query($sql);
if ($result) {

  print "<form method=\"post\" action=\"index.php?viewall=$viewall&vline=$vline&account=$account\">";
  print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  print "<table class=\"border\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print "<tr class=\"liste_titre\">";
  print '<td>Date</td><td>'.$langs->trans("Description").'</td>';
  print "<td align=\"right\">Montant</td>";
  print "<td align=\"right\">Réduction</td>";
  print "</tr>\n";
  

  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;

  $sep = 0;

  while ($i < $num) {
    $objp = $db->fetch_object($result);
    $total = $total + $objp->amount;
    $time = time();

    $var=!$var;

    print "<tr $bc[$var]>";
    print "<td>".strftime("%d&nbsp;%b&nbsp;%y&nbsp;%H:%M",$objp->date_depart)."<br>\n";
    print "".strftime("%d %b %y %H:%M",$objp->date_arrivee)."</TD>\n";

    print "<td>$objp->depart - $objp->arrivee</td>";

    print "<td align=\"right\">".price($objp->amount)."</TD>\n";
    print "<td align=\"right\">".price($objp->reduction)."</TD>\n";
    print "<td align=\"center\"><a href=\"index.php?action=del&rowid=$objp->rowid\">[Del]</a></td>";    
    print "</tr>";


    $i++;
  }
  $db->free();

  print "<tr>";
  print "<td><input name=\"date_depart\" type=\"text\" size=16 maxlength=16><br>";
  print "<input name=\"date_arrivee\" type=\"text\" size=16 maxlength=16></td>";
  print "<td><input name=\"depart\" type=\"text\" size=40><br>";
  print "<input name=\"arrivee\" type=\"text\" size=40></td>";

  print "<td><input name=\"amount\" type=\"text\" size=8></td>";

  print "<td colspan=\"2\" align=\"center\"><input type=\"submit\" value=\"".$langs->trans("Add")."\"</td>";
  print "</tr><tr><td colspan=\"1\">Réduction</td>";

  print "<td><select name=\"reducid\">$options";

  print "</select></td>";

  print '<td><input name="reduc" type="text" size="6" value="0.00">'.$langs->trans("Currency".$conf->monnaie).'</td><td>&nbsp;</td>';

  print '</tr>';
  print "</table></form>";
} else {
  print "<p>".$db->error();

}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
