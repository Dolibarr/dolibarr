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
require("../lib/functions.inc.php3");


require("$GLJ_WWW_ROOT/../www/lib/CMailFile.class.php3");

$author = $GLOBALS["REMOTE_USER"];

llxHeader();
print "<table width=\"100%\">";
print "<tr><td>Propositions commerciales</td>";
print "<td align=\"right\"><a href=\"propal.php3\">Liste</a></td>";
print "<td align=\"right\"><a href=\"/compta/prev.php3\">CA Prévisionnel</a></td>";
print "<td align=\"right\"><a href=\"$PHP_SELF?viewstatut=2\">Propal Signées</a></td></tr>";
print "</table>";

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

function calc($st) {
  global $db;
  $sum = 0;
  $sql = "SELECT sum(price-remise) as sum FROM llx_propal WHERE fk_statut = $st";
  if ( $db->query($sql) ) {
    if ($db->num_rows()) {
      $arr = $db->fetch_array(0);
      $sum = $arr[0];
    }  
  }
  return $sum ;
}

function calcf($st) {
  global $db;
  $sum = 0;
  $sql = "SELECT sum(amount) as sum FROM llx_facture WHERE fk_statut = 1 and paye = $st";
  if ( $db->query($sql) ) {
    if ($db->num_rows()) {
      $arr = $db->fetch_array(0);
      $sum = $arr[0];
    }  
  }
  return $sum ;
}

/*
 *
 *
 * Liste des propals
 *
 * 
 */
print "<p><TABLE border=\"1\" cellspacing=\"0\" cellpadding=\"2\">";
echo '<tr><td colspan="2">Propales</td></tr>';

$po = calc(1);
$ps = calc(2);
$pns = calc(3);

print "<tr><td>Propales ouvertes</td><td align=\"right\">".price($po)."</td></tr>";
print "<tr><td>Propales signées</td><td align=\"right\">".price($ps)."</td></tr>";
print "<tr><td>Total</td><td align=\"right\">".price($ps + $po )."</td></tr>";
print "<tr><td>Propales non signées</td><td align=\"right\">".price($pns)."</td></tr>";
print "<tr><td>Total</td><td align=\"right\">".price($ps + $po + $pns)."</td></tr>";
print "</table>";


print "<p><TABLE border=\"1\" cellspacing=\"0\" cellpadding=\"2\">";
echo '<tr><td colspan="2">Factures</td></tr>';

$fnp = calcf(0);
$fp = calcf(1);
print "<tr><td>Factures non payées</td><td align=\"right\">".price($fnp)."</td></tr>";
print "<tr><td>Factures payées</td><td align=\"right\">".price($fp)."</td></tr>";
print "<tr><td>Total</td><td align=\"right\">".price($fnp + $fp )."</td></tr>";

print "</table>";


$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
