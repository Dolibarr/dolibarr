<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
$_MONNAIE = $GLOBALS["_MONNAIE"];
$db = new Db();
if ($sortorder == "") {
  $sortfield="lower(s.nom)";
  $sortorder="ASC";
}

$yn["1"] = "oui";
$yn["0"] = "non";

if ($action == 'valid') {
  $sql = "UPDATE llx_facture set fk_statut = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}

if ($action == 'payed') {
  $sql = "UPDATE llx_facture set paye = 1 WHERE rowid = $facid ;";
  $result = $db->query( $sql);
}

if ($action == 'delete') {
  $sql = "DELETE FROM llx_facture WHERE rowid = $facid;";
  if ( $db->query( $sql) ) {
    $sql = "DELETE FROM llx_fa_pr WHERE fk_facture = $facid;";
    if (! $db->query( $sql) ) {
      print $db->error();
    }
  } else {
    print $db->error();
  }
  $facid = 0 ;
}


if ($facid > 0) {

  $sql = "SELECT s.nom as socnom, s.idp as socidp, f.facnumber, f.amount, f.total, ".$db->pdate("f.datef")." as df, f.paye, f.fk_statut as statut, f.author, f.note";
  $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND f.rowid = $facid";

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
  print "<td align=\"right\"><a href=\"index.php3?socidp=$obj->socidp\">Autres factures de $obj->socnom</a></td>\n";
  print "</tr>";
  print "<tr><td width=\"50%\">";
  /*
   *   Facture
   */
  print "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\" width=\"100%\">";
  print "<tr><td>Société</td><td colspan=\"2\"><b><a href=\"../comm/index.php3?socid=$obj->socidp\">$obj->socnom</a></b></td></tr>";

  print "<tr><td>date</td><td colspan=\"2\">".strftime("%A %d %B %Y",$obj->df)."</td></tr>\n";
  print "<tr><td>Auteur</td><td colspan=\"2\">$obj->author</td>";
  print "<tr><td>Statut</td><td align=\"center\" colspan=\"2\">$obj->statut</td>";
  print "<tr><td>Paye</td><td align=\"center\" colspan=\"2\" bgcolor=\"#f0f0f0\"><b>".$yn[$obj->paye]."</b></td>";
  
  print "<tr><td>Montant</td><td align=\"right\"><b>".price($obj->amount)."</b></td><td>euros HT</td></tr>";
  print "<tr><td>TVA</td><td align=\"right\">".tva($obj->amount)."</td><td>euros</td></tr>";
  print "<tr><td>Total</td><td align=\"right\">".price($obj->total)."</td><td>euros TTC</td></tr>";

  print "</tr>";
  print "</table>";
  
  print "</td><td valign=\"top\">";

  $_MONNAIE="euros";

  /*
   * Paiements
   */
  $sql = "SELECT ".$db->pdate("datep")." as dp, p.amount, c.libelle as paiement_type, p.num_paiement, p.rowid";
  $sql .= " FROM llx_paiement as p, c_paiement as c WHERE p.fk_facture = $facid AND p.fk_paiement = c.id";
  
  $result = $db->query($sql);
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
    print $db->error();
  }

  print "</td></tr>";
  print "<tr><td>Note : ".nl2br($obj->note)."</td></tr>";
  print "</table>";

  print "<p><TABLE border=\"1\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\"><tr>";
  
  if ($obj->statut == 0) {
    print "<td align=\"center\" bgcolor=\"#e0e0e0\" width=\"25%\">[<a href=\"$PHP_SELF?facid=$facid&action=delete\">Supprimer</a>]</td>";
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
    print "<td align=\"center\" width=\"25%\"><a href=\"facture.php3?facid=$facid&action=pdf\">Générer la facture</a></td>";
  }
  print "</tr></table><p>";

  /*
   * Documents générés
   *
   */
  print "<hr>";
  print "<table width=\"100%\" cellspacing=2><tr><td width=\"50%\" valign=\"top\">";
  print "<b>Documents générés</b><br>";
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
      print "<TD><a href=\"../comm/propal.php3?propalid=$objp->propalid\">$objp->ref</a></TD>\n";
      print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
      print '<TD align="right">'.price($objp->price).'</TD>';
      print "</tr>";
      $total = $total + $objp->price;
      $i++;
    }
    print "<tr><td align=\"right\" colspan=\"3\">Total : <b>".price($total)."</b> $_MONNAIE HT</td></tr>\n";
    print "</table>";
  } else {
    print $db->error();
  }

} else {
  /*
   * Liste
   *
   */

  function liste($db, $paye) {
    global $bc, $year, $month;
    $sql = "SELECT s.nom, s.idp, f.facnumber, f.amount,".$db->pdate("f.datef")." as df, f.paye, f.rowid as facid ";
    $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND f.paye = $paye";
  
    if ($socidp) {
      $sql .= " AND s.idp = $socidp";
    }

    if ($month > 0) {
      $sql .= " AND date_part('month', date(f.datef)) = $month";
    }
    if ($year > 0) {
      $sql .= " AND date_format(f.datef, '%Y') = $year";
    }
    
    $sql .= " ORDER BY f.datef DESC ";
        
    $result = $db->query($sql);
    if ($result) {
      $num = $db->num_rows();
      if ($num > 0) {
	$i = 0;
	print '<p><TABLE border="1" width="100%" cellspacing="0" cellpadding="4">';
	print "<TR bgcolor=\"orange\">";
	print "<TD>[<a href=\"$PHP_SELF\">Tous</a>]</td>";
	print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
	print "<TD>Num</TD>";
	print "<TD align=\"right\">Date</TD>";
	print "<TD align=\"right\">Montant</TD>";
	print "<TD align=\"right\">Payé</TD>";
	print "<TD align=\"right\">Moyenne</TD>";
	print "</TR>\n";
	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  print "<TD>[<a href=\"$PHP_SELF?socidp=$objp->idp\">Filtre</a>]</TD>\n";
	  print "<TD><a href=\"../comm/index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
	  
	  
	  print "<td><a href=\"facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
	  
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
	  
	  print '<TD align="right">'.price($objp->amount).'</TD>';
	  
	  $yn[1] = "oui";
	  $yn[0] = "<b>non</b>";
	  
	  $total = $total + $objp->amount;	  
	  print "<TD align=\"right\">".$yn[$objp->paye]."</TD>\n";
	  print "<TD align=\"right\">".round($total / ($i + 1))."</TD>\n";
	  print "</TR>\n";
	  $i++;
	}
	print "<tr><td></td><td>$i factures</td><td colspan=\"2\" align=\"right\"><b>Total : ".round($total * 6.55957)." FF</b></td>";
	print "<td align=\"right\"><b>Total : $total</b></td><td>$_MONNAIE HT</td>";
	print "<td align=\"right\"><b>Moyenne : ".round($total/ $i)."</b></td></tr>";
	print "</TABLE>";
      }
      $db->free();
    } else {
      print $db->error();
    }
  }
  print "<P>";
  liste($db, 0);
  print "<P>";
  liste($db, 1);

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
