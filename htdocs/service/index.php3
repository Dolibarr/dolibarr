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
  $sortfield="lower(p.label),p.price";
}
if ($sortorder == "") {
  $sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print '<div class="titre">Liste des services</div><br>';

 /*
  *
  * Liste
  *
  */

$now = strftime ("%Y-%m-%d %H:%M", time());

$sql = "SELECT p.rowid, p.label, p.price, p.duration,p.ref FROM llx_service as p";
$sql .= " WHERE p.fin_comm >= '$now'";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print "<TH>Réf</TH>";
  print "<TH><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Nom</a></th>";
  print "<TH align=\"right\">Prix</th>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD><a href=\"fiche.php3?id=$objp->rowid\">$objp->ref</a></TD>\n";
    print "<TD>$objp->label</TD>\n";
    print '<TD align="right">'.price($objp->price).'</TD>';
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
} else {
  print $db->error() . ' in ' . $sql;
}


$sql = "SELECT p.rowid, p.label, p.fin_comm, p.price, p.duration,p.ref FROM llx_service as p";
$sql .= " WHERE p.fin_comm < '$now'";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print "<TD>Réf</TD>";
  print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Nom</a></td>";
  print "<TD align=\"right\">Prix</TD>";
  print "<TD align=\"right\">Fin</TD>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object( $i);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD><a href=\"fiche.php3?id=$objp->rowid\">$objp->ref</a></TD>\n";
    print "<TD>$objp->label</TD>\n";
    print '<TD align="right">'.price($objp->price).'</TD>';
    print "<TD align=\"right\">$objp->fin_comm</TD>\n";
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
} else {
  print $this->db->error() . ' in ' . $sql;
}



$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
