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

$yn[1] = "oui";
$yn[0] = "<b>non</b>";
	
if ($action == 'valid') {
  $sql = "UPDATE llx_facture_fourn set fk_statut = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}

if ($action == 'payed') {
  $sql = "UPDATE llx_facture_fourn set paye = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}




if ($action == 'add') {
  $datefacture = $db->idate(mktime(12, 0 , 0, $pmonth, $pday, $pyear)); 

  $tva = ($tva_taux * $amount) / 100 ;
  $remise = 0;
  $total = $tva + $amount ;

  $sql = "INSERT INTO llx_facture_fourn (facnumber, libelle, fk_soc, datec, datef, note, amount, remise, tva, total, fk_user_author) ";
  $sql .= " VALUES ('$facnumber','$libelle', $socidp, now(), $datefacture,'$note', $amount, $remise, $tva, $total, $user->id);";
  $result = $db->query($sql);

  if ($result) {
    $sql = "SELECT rowid, facnumber FROM llx_facture_fourn WHERE facnumber='$facnumber';";
    $result = $db->query($sql);
    if ($result) {
      $objfac = $db->fetch_object( 0);
      $facid = $objfac->rowid;
      $facnumber = $objfac->facnumber;
      $action = '';

    }
  } else {
    print "<p><b>Erreur : la facture n'a pas été créée, vérifier le numéro !</b>$sql<br>". $db->error();
    print "<p>Retour à la <a href=\"propal.php3?propalid=$propalid\">propal</a>";
  }
  $facid = $facid;
  $action = '';

}
/*
 *
 * Mode creation
 *
 *
 *
 */

if ($action == 'create') {
  print_titre("Emettre une facture");


  print "<form action=\"$PHP_SELF\" method=\"post\">";
  print '<table cellspacing="0" border="1" width="100%">';
  print "<tr bgcolor=\"#e0e0e0\"><td>Société :</td>";

  print '<td><select name="socidp">';

  $sql = "SELECT s.nom, s.prefix_comm, s.idp";
  $sql .= " FROM societe as s WHERE s.fournisseur = 1 ORDER BY s.nom ASC";

  if ( $db->query($sql) ) {
    $num = $db->num_rows();
    $i = 0;
    while ($i < $num) {
      $obj = $db->fetch_object($i);
      print '<option value="'.$obj->idp;

      if ($socid == $obj->idp) {
	print '" SELECTED>'.$obj->nom.'</option>';
      } else {
	print '">'.$obj->nom.'</option>';
      }
      $i++;
    }
  }
  print '</select></td>';
  print "<td rowspan=6>Commentaires :<br>";
  print "<textarea name=\"note\" wrap=\"soft\" cols=\"30\" rows=\"15\"></textarea></td></tr>";

  print '<tr><td>Numéro :</td><td><input name="facnumber" type="text"></td></tr>';
  print '<tr><td>Libellé :</td><td><input size="30" name="libelle" type="text"></td></tr>';

  print '<tr bgcolor="#e0e0e0"><td>Montant HT :</td>';
  print '<td><input type="text" size="8" name="amount"></td></tr>';

  print '<tr bgcolor="#e0e0e0"><td>TVA :</td>';
  print '<td><select name="tva_taux">';
  print '<option value="19.6">19.6';
  print '<option value="5.5">5.5';
  print '<option value="0">0';
  print '</select></td></tr>';
      
  print "<input type=\"hidden\" name=\"action\" value=\"add\">";
  
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
  
  print "<tr><td>Auteur :</td><td>".$user->fullname."</td></tr>";
  print "<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" value=\"Enregistrer\"></td></tr>";
  print "</form>";
  print "</table>";
  
} else {

  if ($facid > 0) {

    $sql = "SELECT s.nom as socnom, s.idp as socidp, f.facnumber, f.amount, f.tva, f.total, ".$db->pdate("f.datef")." as df, f.paye, f.fk_statut as statut, f.note, f.libelle";
    $sql .= " FROM societe as s,llx_facture_fourn as f WHERE f.fk_soc = s.idp AND f.rowid = $facid";

    $result = $db->query( $sql);
  
    if ($result) {
      $num = $db->num_rows();
      if ($num) {
	$obj = $db->fetch_object( $i);    
      }
      $db->free();
    } else {
      print $db->error();
    }
  
    print "<table border=\"0\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
    print "<tr>";
    print '<td><div class="titre">Facture : '.$obj->facnumber.'</div></td>';
    print "<td align=\"right\"><a href=\"facture.php3?socidp=$obj->socidp\">Autres factures de $obj->socnom</a></td>\n";
    print "</tr>";
    print "<tr><td width=\"50%\">";
    /*
     *   Facture
     */
    print "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
    print "<tr><td>Société</td><td colspan=\"3\"><b><a href=\"fiche.php3?socid=$obj->socidp\">$obj->socnom</a></b></td></tr>";

    print "<tr><td>Date</td><td colspan=\"3\">".strftime("%A %d %B %Y",$obj->df)."</td></tr>\n";
    print "<tr><td>Libelle</td><td colspan=\"3\">$obj->libelle</td>";
    print "<tr><td>Auteur</td><td colspan=\"3\">$obj->author&nbsp;</td>";
  
    print "<tr><td>Montant</td><td align=\"right\"><b>".price($obj->amount)."</b></td><td>euros HT</td></tr>";
    print "<tr><td>TVA</td><td align=\"right\">".price($obj->tva)."</td><td>euros</td></tr>";
    print "<tr><td>Total</td><td align=\"right\">".price($obj->total)."</td><td>euros TTC</td></tr>";

    print "<tr><td>Statut</td><td align=\"center\">$obj->statut</td>";
    print "<td>Paye</td><td align=\"center\" bgcolor=\"#f0f0f0\"><b>".$yn[$obj->paye]."</b></td>";

    print "</tr>";
    print "</table>";
  
    print "</td><td valign=\"top\">";

    $_MONNAIE="euros";

    /*
     * Paiements
     */
    $sql = "SELECT ".$db->pdate("datep")." as dp, p.amount, c.libelle as paiement_type, p.num_paiement, p.rowid";
    $sql .= " FROM llx_paiement as p, c_paiement as c WHERE p.fk_facture = $facid AND p.fk_paiement = c.id";
  
    //$result = $db->query($sql);
    $result = 0;
    if ($result) {
      $num = $db->num_rows();
      $i = 0; $total = 0;
      print "<p><b>Paiements</b>";
      echo '<TABLE border="1" width="100%" cellspacing="0" cellpadding="3">';
      print "<TR class=\"liste_titre\">";
      print "<td>Date</td>";
      print "<td>Type</td>";
      print "<td align=\"right\">Montant</TD><td>&nbsp;</td>";
      print "</TR>\n";
    
      $var=True;
      while ($i < $num) {
	$objp = $db->fetch_object( $i);
	$var=!$var;
	print "<TR $bc[$var]>";
	print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	print "<TD>$objp->paiement_type $objp->num_paiement</TD>\n";
	print "<TD align=\"right\">".price($objp->amount)."</TD><td>$_MONNAIE</td>\n";
	print "</tr>";
	$total = $total + $objp->amount;
	$i++;
      }
      print "<tr><td colspan=\"2\" align=\"right\">Total :</td><td align=\"right\"><b>".price($total)."</b></td><td>$_MONNAIE</td></tr>\n";
      print "<tr><td colspan=\"2\" align=\"right\">Facturé :</td><td align=\"right\" bgcolor=\"#d0d0d0\">".price($obj->total)."</td><td bgcolor=\"#d0d0d0\">$_MONNAIE</td></tr>\n";

      $resteapayer = $obj->total - $total;

      print "<tr><td colspan=\"2\" align=\"right\">Reste a payer :</td>";
      print "<td align=\"right\" bgcolor=\"#f0f0f0\"><b>".price($resteapayer)."</b></td><td bgcolor=\"#f0f0f0\">$_MONNAIE</td></tr>\n";

      print "</table>";
      $db->free();
    } else {
      //      print $db->error();
    }

    print "</td></tr>";
    print "<tr><td>Note : ".nl2br($obj->note)."</td></tr>";
    print "</table>";

    print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
  
    if ($obj->statut == 0) {
      print '<td align="center" bgcolor="#e0e0e0" width="25%">[<a href="index.php3?facid='.$facid.'&action=delete">'.translate("Delete").'</a>]</td>';
    } else {
      print "<td align=\"center\" width=\"25%\">-</td>";
    } 
    if ($obj->statut == 1 && $resteapayer > 0) {
      print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"paiement.php3?facid=$facid&action=create\">Emettre un paiement</a>]</td>";
    } else {
      print "<td align=\"center\" width=\"25%\">-</td>";
    }
    if ($obj->statut == 1 && abs($resteapayer == 0) && $obj->paye == 0) {
      print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=payed\">Classer 'Payée'</a>]</td>";
    } else {
      print "<td align=\"center\" width=\"25%\">-</td>";
    }
    if ($obj->statut == 0) {
      print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=valid\">Valider</a>]</td>";
    } else {
      print "<td align=\"center\" width=\"25%\">-</td>";
    }
    print "</tr></table><p>";

    /*
     * Documents générés
     *
     */
    print "<hr>";
    print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";
    print "<b>Documents associés</b><br>";
    print "<table width=\"100%\" cellspacing=0 border=1 cellpadding=3>";

    $file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/facture/$obj->facnumber/$obj->facnumber.pdf";
    if (file_exists($file)) {
      print "<tr><td>Propale PDF</a></td><td><a href=\"../../doc/facture/$obj->facnumber/$obj->facnumber.pdf\">$obj->facnumber.pdf</a></td></tr>";
    }  
    $file = $GLOBALS["GLJ_ROOT"] . "/www-sys/doc/facture/$obj->facnumber/$obj->facnumber.ps";
    if (file_exists($file)) {
      print "<tr><td>Propale Postscript</a></td><td><a href=\"../../doc/facture/$obj->facnumber/$obj->facnumber.ps\">$obj->facnumber.ps</a></td>";
      print "</tr>";
    }
    print "<tr><td colspan=\"2\">(<a href=\"../../doc/facture/$obj->facnumber/\">liste...</a>)</td></tr>";  

    print "</table>\n</table>";
  
    /*
     * Generation de la facture
     *
     */
    if ($action == 'pdf') {
      print "<hr><b>Génération de la facture</b><br>";
      $command = "export DBI_DSN=\"dbi:mysql:dbname=lolixfr\" ";
      $command .= " ; ../../scripts/facture-tex.pl --html -vv --facture=$facid --pdf --gljroot=" . $GLOBALS["GLJ_ROOT"] ;
    
      $output = system($command);
      print "<p>command :<br><small>$command</small><br>";
      print "<p>output :<br><small>$output</small><br>";
    } 


    /*
     *   Propales
     */
  
    $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.price, p.ref, p.rowid as propalid";
    $sql .= " FROM llx_propal as p, llx_fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $facid";
  
    $result = $db->query($sql);
    if ($result) {
      $num = $db->num_rows();
      if ($num) {
	$i = 0; $total = 0;
	print "<p><b>Proposition(s) commerciale(s) associée(s)</b>";
	print '<TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
	print "<TR class=\"liste_titre\">";
	print "<td>Num</td>";
	print "<td>Date</td>";
	print "<td align=\"right\">Prix</TD>";
	print "</TR>\n";
    
	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD><a href=\"propal.php3?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
	  print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
	  print '<TD align="right">'.price($objp->price).'</TD>';
	  print "</tr>";
	  $total = $total + $objp->price;
	  $i++;
	}
	print "<tr><td align=\"right\" colspan=\"3\">Total : <b>".price($total)."</b> $_MONNAIE HT</td></tr>\n";
	print "</table>";
      }
    } else {
      print $db->error();
    }

  } else {
    /*
     *
     * Liste
     *
     *
     */
    print_barre_liste("Factures",$page,$PHP_SELF);

    $sql = "SELECT s.nom,s.idp,f.facnumber,f.amount,".$db->pdate("f.datef")." as df,f.paye,f.rowid as facid";
    $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp";
  
    if ($socidp) {
      $sql .= " AND s.idp = $socidp";
    }
  
    if ($month > 0) {
      $sql .= " AND date_format(f.datef, '%m') = $month";
    }
    if ($year > 0) {
      $sql .= " AND date_format(f.datef, '%Y') = $year";
    }
  
    $sql .= " ORDER BY f.fk_statut, f.paye, f.datef DESC ";
  
    $result = $db->query($sql);
    if ($result) {
      $num = $db->num_rows();
    
      $i = 0;
      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
      print '<TR class="liste_titre">';
      print "<TD>Num&eacute;ro</TD><td>";
      print_liste_field_titre("Société",$PHP_SELF,"s.nom");
      print "</td><TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";
      print "<TD align=\"right\">Payé</TD>";
      print "</TR>\n";
    
      if ($num > 0) {
	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	
	  if ($objp->paye && !$sep) {
	    print "<tr><td colspan=\"3\" align=\"right\">";
	    print "&nbsp;</small></td>";
	    print "<td align=\"right\">Sous Total :<b> ".price($total)."</b></td><td>euros HT</td></tr>";
	  
	    print '<TR class="liste_titre">';
	    print "<TD>Num&eacute;ro</TD><td>";
	    print_liste_field_titre("Société",$PHP_SELF,"s.nom");
	    print "</td><TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";
	    print "<TD align=\"right\">Payé</TD></TR>\n";
	    $sep = 1 ; $j = 0;
	    $subtotal = 0;
	  }
	
	  print "<TR $bc[$var]>";
	  print "<td><a href=\"facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	  print "<TD><a href=\"fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
	
	  if ($objp->df > 0 ) {
	    print "<TD align=\"right\">";
	    $y = strftime("%Y",$objp->df);
	    $m = strftime("%m",$objp->df);
	  
	    print strftime("%d",$objp->df)."\n";
	    print " <a href=\"facture.php3?year=$y&month=$m\">";
	    print strftime("%B",$objp->df)."</a>\n";
	    print " <a href=\"facture.php3?year=$y\">";
	    print strftime("%Y",$objp->df)."</a></TD>\n";
	  } else {
	    print "<TD align=\"right\"><b>!!!</b></TD>\n";
	  }
	
	  print "<TD align=\"right\">".price($objp->amount)."</TD>\n";
	
	  $total = $total + $objp->amount;
	  $subtotal = $subtotal + $objp->amount;	  
	  print "<TD align=\"right\">".$yn[$objp->paye]."</TD>\n";

	  print "</TR>\n";
	  $i++;
	  $j++;
	
	}
      }
      if ($i == 0) { $i=1; }  if ($j == 0) { $j=1; }
      print "<tr><td></td><td>$j factures</td><td colspan=\"1\" align=\"right\">&nbsp;</td>";
      print "<td align=\"right\">Sous Total :<b> ".price($subtotal)."</b></td><td>euros HT</td></tr>";
    
      print "<tr bgcolor=\"#d0d0d0\"><td></td><td>$i factures</td><td colspan=\"1\" align=\"right\">&nbsp;</td>";
      print "<td align=\"right\"><b>Total : ".price($total)."</b></td><td>euros HT</td></tr>";
    
      print "</TABLE>";
      $db->free();
    } else {
      print $db->error();
    }

  }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
