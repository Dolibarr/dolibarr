<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("../paiement.class.php");
llxHeader();

$db = new Db();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}


if ($action == 'add') {
  $datepaye = $db->idate(mktime(12, 0 , 0, $pmonth, $pday, $pyear));

  $paiement = new Paiement($db);

  $paiement->facid        = $facid;  
  $paiement->datepaye     = $datepaye;
  $paiement->amount       = $amount;
  $paiement->author       = $author;
  $paiement->paiementid   = $paiementid;
  $paiement->num_paiement = $num_paiement;
  $paiement->note         = $note;

  $paiement->create();

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

      $total = $obj->total;

      print_titre("Emettre un paiement");
      print '<form action="facture.php3?id='.$facid.'" method="post">';
      print '<input type="hidden" name="action" value="add_paiement">';
      print '<table cellspacing="0" border="1" width="100%" cellpadding="3">';

      print "<tr class=\"liste_titre\"><td colspan=\"3\">Facture</td>";

      print "<tr><td>Numéro :</td><td colspan=\"2\">$obj->facnumber</td></tr>";
      print "<tr><td>Société :</td><td colspan=\"2\">$obj->nom</td></tr>";

      print "<tr><td>Montant :</td><td colspan=\"2\">".price($obj->total)." euros TTC</td></tr>";

      $sql = "SELECT sum(p.amount) FROM llx_paiement as p WHERE p.fk_facture = $facid;";
      $result = $db->query($sql);
      if ($result) {
	$sumpayed = $db->result(0,0);
	$db->free();
      }
      print '<tr><td>Déjà payé</td><td colspan=\"2\"><b>'.price($sumpayed).'</b> euros TTC</td></tr>';

      print "<tr class=\"liste_titre\"><td colspan=\"3\">Paiement</td>";


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
      print '<textarea name="comment" wrap="soft" cols="40" rows="10"></textarea></td></tr>';

      $author = $GLOBALS["REMOTE_USER"];
      print "<input type=\"hidden\" name=\"author\" value=\"$author\">\n";

      print "<tr><td>Type :</td><td><select name=\"paiementid\">\n";

      $sql = "SELECT id, libelle FROM c_paiement ORDER BY id";
  
      $result = $db->query($sql);
      if ($result) {
	$num = $db->num_rows();
	$i = 0; 
	while ($i < $num) {
	  $objopt = $db->fetch_object( $i);
	  print "<option value=\"$objopt->id\">$objopt->libelle</option>\n";
	  $i++;
	}
      }
      print "</select><br>";
      print "</td></tr>\n";
      print "<tr><td>Numéro :</td><td><input name=\"num_paiement\" type=\"text\"><br><em>Num du cheque ou virement</em></td></tr>\n";
      print "<tr><td valign=\"top\">&nbsp;</td><td>Reste à payer : <b>".price($total - $sumpayed)."</b> euros TTC</td></tr>\n";
      print "<tr><td valign=\"top\">Montant :</td><td><input name=\"amount\" type=\"text\"></td></tr>\n";
      print '<tr><td colspan="3" align="center"><input type="submit" value="Enregistrer"></td></tr>';
      print "</form>\n";
      print "</table>\n";

    }
  }
} 

if ($action == '') {

  $sql = "SELECT ".$db->pdate("p.datep")." as dp, p.amount, f.amount as fa_amount, f.facnumber";
  $sql .=", f.rowid as facid, c.libelle as paiement_type, p.num_paiement";
  $sql .= " FROM llx_paiement as p, llx_facture as f, c_paiement as c";
  $sql .= " WHERE p.fk_facture = f.rowid AND p.fk_paiement = c.id";

  if ($socidp)
    {
      $sql .= " AND f.fk_soc = $socidp";
    }

  $sql .= " ORDER BY datep DESC";
  $result = $db->query($sql);

  if ($result)
    {
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

      print "</tr>";
      $total = $total + $objp->amount;
      $i++;
    }
    print "<tr><td align=\"right\" colspan=\"4\">Total : <b>".price($total)."</b></td><td>Euros HT</td></tr>\n";
    print "</table>";
  }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
