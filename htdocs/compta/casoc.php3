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
print "Chiffres d'affaire par société";

$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";


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
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

function liste($db, $paye) {
  global $bc, $year, $month, $socidp;
  $sql = "SELECT s.nom, s.idp, sum(f.amount) as ca";
  $sql .= " FROM societe as s,llx_facture as f WHERE f.fk_soc = s.idp AND f.paye = $paye GROUP BY s.nom, s.idp";
  
  if ($socidp) {
    $sql .= " AND s.idp = $socidp";
  }
  if ($month > 0) {
    $sql .= " AND date_part('month', date(f.datef)) = $month";
  }
  if ($year > 0) {
    $sql .= " AND date_part('year', date(f.datef)) = $year";
  }
  
  $sql .= " ORDER BY f.datef DESC ";
    
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    if ($num > 0) {
      $i = 0;
      print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
      print "<TR bgcolor=\"orange\">";
      print "<TD>[<a href=\"$PHP_SELF\">Tous</a>]</td>";
      print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
      print "<TD align=\"right\">Montant</TD><td>&nbsp;</td>";
      print "<TD align=\"right\">Moyenne</TD>";
      print "</TR>\n";
      $var=True;
      while ($i < $num) {
	$objp = $db->fetch_object( $i);
	$var=!$var;
	print "<TR $bc[$var]>";
	print "<TD>[<a href=\"$PHP_SELF?socidp=$objp->idp\">Filtre</a>]</TD>\n";
	print "<TD><a href=\"../comm/index.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
	print "<TD align=\"right\">".price($objp->ca)."</TD><td>&nbsp;</td>\n";
		
	$total = $total + $objp->ca;	  

	print "<TD align=\"right\">".price($total / ($i + 1))."</TD>\n";
	print "</TR>\n";
	$i++;
      }
      print "<tr><td></td><td align=\"right\"><b>Total : ".francs($total)." FF</b></td>";
      print "<td align=\"right\"><b>Total : ".price($total)."</b></td><td>euros HT</td>";
      print "<td align=\"right\"><b>Moyenne : ".price($total/ $i)."</b></td></tr>";
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



$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
