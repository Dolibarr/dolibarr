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
if ($sortfield == "") {
  $sortfield="lower(p.label)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}

$yn["t"] = "oui";
$yn["f"] = "non";

if ($page == -1) { $page = 0 ; }
$limit = $conf->limit_liste;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print_barre_liste("Factures",$page,$PHP_SELF);

$sep = 0;
$sept = 0;

$sql = "SELECT s.nom,s.idp,f.facnumber,f.amount,".$db->pdate("f.datef")." as df,f.paye,f.rowid as facid";
$sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp";
  
if ($socidp) {
  $sql .= " AND s.idp = $socidp";
}
  
if ($month > 0) {
  $sql .= " AND date_part('month', date(f.datef)) = $month";
}
if ($year > 0) {
    $sql .= " AND date_part('year', date(f.datef)) = $year";
}
  
$sql .= " ORDER BY f.fk_statut, f.paye, f.datef DESC ";
    
$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();

  $i = 0;
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
    print '<TR class="liste_titre">';
  print "<TD>[<a href=\"$PHP_SELF\">Tous</a>]</td>";
  print "<TD>Num&eacute;ro</TD>";
  print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
  print "<TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";
  print "<TD align=\"right\">Payé</TD>";
  print "</TR>\n";

  if ($num > 0) {
    $var=True;
    while ($i < $num) {
      $objp = $db->fetch_object( $i);
      $var=!$var;

      if ($objp->paye && !$sep) {
	print "<tr><td></td><td>$i factures</td><td colspan=\"2\" align=\"right\">";
	print "&nbsp;</small></td>";
	print "<td align=\"right\">Sous Total :<b> ".price($total)."</b></td><td>euros HT</td></tr>";

	print '<TR class="liste_titre">';
	print "<TD>[<a href=\"$PHP_SELF\">Tous</a>]</td>";
	print "<TD>Num&eacute;ro</TD>";
	print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
	print "<TD align=\"right\">Date</TD><TD align=\"right\">Montant</TD>";
	print "<TD align=\"right\">Payé</TD></TR>\n";
	$sep = 1 ; $j = 0;
	$subtotal = 0;
      }

      print "<TR $bc[$var]>";
      print "<TD>[<a href=\"$PHP_SELF?socidp=$objp->idp\">Filtre</a>]</TD>\n";
      print "<td><a href=\"facture.php3?facid=$objp->facid\">$objp->facnumber</a></TD>\n";
      print "<TD><a href=\"../comm/index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
	
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
	
      $yn[1] = "oui";
      $yn[0] = "<b>non</b>";
	
      $total = $total + $objp->amount;
      $subtotal = $subtotal + $objp->amount;	  
      print "<TD align=\"right\">".$yn[$objp->paye]."</TD>\n";

      print "</TR>\n";
      $i++;
      $j++;

    }
  }
  if ($i == 0) { $i=1; }  if ($j == 0) { $j=1; }
  print "<tr><td></td><td>$j factures</td><td colspan=\"2\" align=\"right\">&nbsp;</td>";
  print "<td align=\"right\">Sous Total :<b> ".price($subtotal)."</b></td><td>euros HT</td></tr>";

  print "<tr bgcolor=\"#d0d0d0\"><td></td><td>$i factures</td><td colspan=\"2\" align=\"right\">&nbsp;</td>";
  print "<td align=\"right\"><b>Total : ".price($total)."</b></td><td>euros HT</td></tr>";

  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
}



$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
