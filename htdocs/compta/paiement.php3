<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
 *
 * action : - create
 *          - add
 *
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
 *
 *
 *
 */
require("./pre.inc.php3");

llxHeader();

$db = new Db();


if ($action == 'add') {
  $datepaye = $db->idate(mktime(12, 0 , 0, $pmonth, $pday, $pyear));
  $author = $GLOBALS["REMOTE_USER"];

  $sql = "INSERT INTO llx_paiement (fk_facture, datec, datep, amount, author, fk_paiement, num_paiement, note)";
  $sql .= " VALUES ($facid, now(), $datepaye,$amount,'$author', $paiementid, '$num_paiement', '$note')";
  $result = $db->query($sql);
  if ($result) {
    $label = "Facture $facnumber - $societe";
    $sql = "INSERT INTO llx_bank (datec, dateo, amount, author, label)";
    $sql .= " VALUES (now(), $datepaye, $amount,'$author', '$label')";
      $result = $db->query($sql);
  } else {
    print "$sql";
  }
  $action = '';

} elseif ($action == 'create') {

  $sql = "SELECT s.nom,s.idp, f.amount, f.total, f.facnumber";
  $sql .= " FROM societe as s, llx_facture as f WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object( 0);
      print "Emettre un paiement<p>";
      print "<form action=\"$PHP_SELF\" method=\"post\">";
      print "<table cellspacing=0 border=1>";
      print "<tr bgcolor=\"#f0f0f0\"><td colspan=\"3\">Facture</td>";

      print "<tr bgcolor=\"#e0e0e0\"><td>Société :</td><td>$obj->nom</td></tr>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Numéro :</td><td>$obj->facnumber</td></tr>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Montant :</td><td align=\"right\">".price($obj->total)." euros TTC</td></tr>";
      print "<tr bgcolor=\"#f0f0f0\"><td colspan=\"3\">Paiement</td>";


      print "<input type=\"hidden\" name=\"action\" value=\"add\">";
      print "<input type=\"hidden\" name=\"facid\" value=\"$facid\">";
      print "<input type=\"hidden\" name=\"facnumber\" value=\"$obj->facnumber\">";
      print "<input type=\"hidden\" name=\"socid\" value=\"$obj->idp\">";
      print "<input type=\"hidden\" name=\"societe\" value=\"$obj->nom\">";

      $strmonth[1] = "Janvier";
      $strmonth[2] = "F&eacute;vrier";
      $strmonth[3] = "Mars";
      $strmonth[4] = "Avril";
      $strmonth[5] = "Mai";
      $strmonth[6] = "Juin";
      $strmonth[7] = "Juillet";
      $strmonth[8] = "Ao&ucirc;t";
      $strmonth[9] = "Septembre";
      $strmonth[10] = "Octobre";
      $strmonth[11] = "Novembre";
      $strmonth[12] = "D&eacute;cembre";
      

      print "<tr><td>Date :</td><td>";
      $cday = date("d", time());
      print "<select name=\"pday\">";    
      for ($day = 1 ; $day < $sday + 32 ; $day++) {
	if ($day == $cday) {
	  print "<option value=\"$day\" SELECTED>$day";
	} else {
	  print "<option value=\"$day\">$day";
	}
      }
      print "</select>";
      $cmonth = date("n", time());
      print "<select name=\"pmonth\">";    
      for ($month = 1 ; $month <= 12 ; $month++) {
	if ($month == $cmonth) {
	  print "<option value=\"$month\" SELECTED>" . $strmonth[$month];
	} else {
	  print "<option value=\"$month\">" . $strmonth[$month];
	}
      }
      print "</select>";
      
      print "<select name=\"pyear\">";
      $syear = date("Y", time() ) ;
      print "<option value=\"".($syear-1)."\">".($syear-1);
      print "<option value=\"$syear\" SELECTED>$syear";

      for ($year = $syear +1 ; $year < $syear + 5 ; $year++) {
	print "<option value=\"$year\">$year";
      }
      print "</select></td>";

      print "<td rowspan=\"5\">Commentaires :<br>";
      print "<textarea name=\"comment\" wrap=\"soft\" cols=\"30\" rows=\"15\"></textarea></td></tr>";


      $author = $GLOBALS["REMOTE_USER"];
      print "<input type=\"hidden\" name=\"author\" value=\"$author\">\n";

      print "<tr><td>Auteur :</td><td>$author</td></tr>\n";

      print "<tr><td>Type :</td><td><select name=\"paiementid\">\n";

      $sql = "SELECT id, libelle FROM c_paiement ORDER BY id";
  
      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
	$i = 0; $total = 0;

	while ($i < $num) {
	  $objopt = $db->fetch_object( $i);
	  print "<option value=\"$objopt->id\">$objopt->libelle</option>\n";
	  $i++;
	}
      }
      print "</select><br>";
      print "</td></tr>\n";
      print "<tr><td>Numéro :</td><td><input name=\"num_paiement\" type=\"text\"><br><em>Num du cheque ou virement</em></td></tr>\n";
      print "<tr><td>Montant :</td><td><input name=\"amount\" type=\"text\" value=\"$obj->price\"></td></tr>\n";
      print "<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" value=\"Enregistrer\"></td></tr>\n";
      print "</form>\n";
      print "</table>\n";

    }
  }
} 

if ($action == '') {

  $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.amount, f.amount as fa_amount, f.facnumber, f.rowid as facid, c.libelle as paiement_type, p.num_paiement";
  $sql .= " FROM llx_paiement as p, llx_facture as f, c_paiement as c WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id";
  $sql .= " ORDER BY datep DESC";
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0; $total = 0;

    print_barre_liste("Paiements", $page, $PHP_SELF);
    print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

    print '<TR class="liste_titre">';
    print "<td>Facture</td>";
    print "<td>Date</td>";
    print "<td>Type</TD>";
    print "<td align=\"right\">Montant</TD>";
    print "<td>&nbsp;</td>";
    print "<td align=\"right\">FF TTC</td>";
    print "<td>&nbsp;</td>";
    print "</TR>\n";
    
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
      print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
      print "<TD>$objp->paiement_type $objp->num_paiement</TD>\n";
      print '<TD align="right">'.price($objp->amount).'</TD><td>&nbsp;</td>';
      print "<TD align=\"right\">".francs(inctva($objp->amount))."</TD><td>&nbsp;</td>\n";
      print "</tr>";
      $total = $total + $objp->amount;
      $i++;
    }
    print "<tr><td align=\"right\" colspan=\"4\">Total : <b>$total</b></td><td>Euros HT</td></tr>\n";
    print "</table>";
  }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
