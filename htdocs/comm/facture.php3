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
$author = $GLOBALS["REMOTE_USER"];
$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";


if ($action == 'add') {
  $datefacture = $db->idate(mktime(12, 0 , 0, $pmonth, $pday, $pyear)); 

  $sql = "INSERT INTO llx_facture (facnumber, fk_soc, datec, datef, note, amount, remise, tva, total, author) ";
  $sql .= " VALUES ('$facnumber', $socid, now(), $datefacture,'$note', $amount, $remise, $tva, $total, '$author');";
  $result = $db->query($sql);

  if ($result) {
    $sql = "SELECT rowid, facnumber FROM llx_facture WHERE facnumber='$facnumber';";
    $result = $db->query($sql);
    if ($result) {
      $objfac = $db->fetch_object( 0);
      $facid = $objfac->rowid;
      $facnumber = $objfac->facnumber;
      $action = '';

      $sql = "INSERT INTO llx_fa_pr (fk_facture,fk_propal) VALUES ($facid, $propalid);";
      $result = $db->query($sql);


      /*
       *
       * Génération du PDF
       *
       */

      print "<hr><b>Génération du PDF</b><p>";

      $command = "export DBI_DSN=\"".$GLOBALS["DBI"]."\" ";
      $command .= " ; ../../scripts/facture-tex.pl --facture=$facid --pdf --gljroot=" . $GLOBALS["GLJ_ROOT"] ;

      $output = system($command);
      print "<p>command : $command<br>";




    }
  } else {
    print "<p><b>Erreur : la facture n'a pas été créée, vérifier le numéro !</b>";
    print "<p>Retour à la <a href=\"propal.php3?propalid=$propalid\">propal</a>";
  }

} elseif ($action == 'create') {

  $sql = "SELECT s.nom, s.prefix_comm, s.idp, p.price, p.remise, p.tva, p.total, p.ref, ".$db->pdate("p.datep")." as dp, c.id as statut, c.label as lst";
  $sql .= " FROM societe as s, llx_propal as p, c_propalst as c WHERE p.fk_soc = s.idp AND p.fk_statut = c.id";

  $sql .= " AND p.rowid = $propalid";

  if ( $db->query($sql) ) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object( 0);

      $numfa = "F-" . $obj->prefix_comm . "-" . strftime("%y%m%d", time());

      print "Emettre une facture<p>";
      print "<form action=\"$PHP_SELF\" method=\"post\">";
      print "<input name=\"amount\" type=\"hidden\" value=\"".($obj->price - $obj->remise)."\">";
      print "<input name=\"total\" type=\"hidden\" value=\"$obj->total\">";
      print "<table cellspacing=0 border=1>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Société :</td><td>$obj->nom</td>";
      print "<td rowspan=7>Commentaires :<br>";
      print "<textarea name=\"note\" wrap=\"soft\" cols=\"30\" rows=\"15\"></textarea></td></tr>";


      print "<tr bgcolor=\"#e0e0e0\"><td>Propal :</td><td>$obj->ref</td></tr>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Montant HT :</td><td align=\"right\">".price($obj->price - $obj->remise)."</td></tr>";
      print "<tr bgcolor=\"#e0e0e0\"><td>TVA :</td><td align=\"right\">".price($obj->tva)."</td></tr>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Total TTC :</td><td align=\"right\">".price($obj->total)."</td></tr>";

      print "<input type=\"hidden\" name=\"remise\" value=\"$obj->remise\">";
      print "<input type=\"hidden\" name=\"tva\" value=\"$obj->tva\">";

      print "<input type=\"hidden\" name=\"action\" value=\"add\">";
      print "<input type=\"hidden\" name=\"propalid\" value=\"$propalid\">";
      print "<input type=\"hidden\" name=\"socid\" value=\"$obj->idp\">";

      $strmonth[1] = "Janvier";   $strmonth[2] = "F&eacute;vrier";   $strmonth[3] = "Mars";       $strmonth[4] = "Avril";  
      $strmonth[5] = "Mai"; $strmonth[6] = "Juin"; $strmonth[7] = "Juillet";          $strmonth[8] = "Ao&ucirc;t"; 
      $strmonth[9] = "Septembre"; $strmonth[10] = "Octobre";
      $strmonth[11] = "Novembre"; $strmonth[12] = "D&eacute;cembre";

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
      print "</select></td></tr>";
      $author = $GLOBALS["REMOTE_USER"];
      print "<input type=\"hidden\" name=\"author\" value=\"$author\">";

      print "<tr><td>Auteur :</td><td>$author</td></tr>";
      print "<tr><td>Numéro :</td><td> <input name=\"facnumber\" type=\"text\" value=\"$numfa\"></td></tr>";
      print "<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" value=\"Enregistrer\"></td></tr>";
      print "</form>";
      print "</table>";

    }
  } else {
    print $db->error();
  }


  print "<p><table border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<tr bgcolor=\"orange\">";
  print "<td>Societe</a></td><td>Num</td>";
  print "<td align=\"right\">Date</td>";
  print "<td align=\"right\">Montant</td>";
  print "<td align=\"right\">Payé</td>";
  print "</tr>\n";
  
  $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount,".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
  $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp ORDER BY f.datec DESC ";
        
  if ( $db->query($sql) ) {
    $num = $db->num_rows();
    if ($num > 0) {
      $i = 0; $var=True;
      while ($i < $num) {
	$objp = $db->fetch_object( $i);
	$var=!$var;
	print "<TR $bc[$var]>";
	print "<TD><a href=\"../comm/index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
	print "<td><a href=\"facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	  
	if ($objp->df > 0 ) {
	  print "<TD align=\"right\">";
	  print strftime("%d %B %Y",$objp->df)."</a></TD>\n";
	} else {
	  print "<TD align=\"right\"><b>!!!</b></TD>\n";
	}
	print "<TD align=\"right\">".price($objp->amount)."</TD>\n";
	  
	$yn[1] = "oui";
	$yn[0] = "<b>non</b>";
	
	$total = $total + $objp->amount;	  
	print "<TD align=\"right\">".$yn[$objp->paye]."</TD>\n";
	print "</TR>\n";
	$i++;
      }
      print "<tr><td></td><td>$i factures</td><td align=\"right\"><b>Total : ".francs($total)." FF</b></td>";
      print "<td align=\"right\"><b>Total : ".price($total)."</b></td><td>$_MONNAIES HT</td></tr>";
    }
    $db->free();
  } else {
    print "<tr><td>".$db->error()."</td></tr>";
  }
  print "</TABLE>";  
}

if ($facid) {

  $sql = "SELECT s.nom,s.idp, f.amount, f.facnumber, f.rowid";
  $sql .= " FROM societe as s, llx_facture as f  WHERE f.fk_soc = s.idp";
  $sql .= " AND f.rowid = $facid";

  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num) {
      $obj = $db->fetch_object( 0);
      print "Facture<p>";
      print "<table cellspacing=0 border=1>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Numero :</td><td><a href=\"../compta/facture.php3?facid=$obj->rowid\">$obj->facnumber</a></td></tr>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Société :</td><td>$obj->nom</td></tr>";
      print "<tr bgcolor=\"#e0e0e0\"><td>Montant :</td><td>$obj->amount</td></tr>";
      print "</table>";
    }
  }
}


$db->close();

llxFooter();
?>
