<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$user->getrights('compta');
if (!$user->admin && !$user->rights->compta->charges)
  accessforbidden();


$year=$_GET["year"];
$filtre=$_GET["filtre"];


print_titre("Charges - Résumé".($year > 0?" $year":""));

print "<br>";
print '<table class="noborder" cellspacing="0" cellpadding="4" width="100%">';
print "<tr class=\"liste_titre\">";
print "<td>Groupe</td>";
print "<td align=\"right\">Nb</td>";
print "<td align=\"right\">Montant TTC</td>";
print "<td align=\"right\">Montant Payé</td>";
print "</tr>\n";

/*
 * Charges sociales
 */
$sql = "SELECT c.libelle as lib, s.fk_type as type, count(s.rowid) as nb, sum(s.amount) as total, sum(IF(paye=1,s.amount,0)) as totalpaye";
$sql .= " FROM ".MAIN_DB_PREFIX."c_chargesociales as c, ".MAIN_DB_PREFIX."chargesociales as s";
$sql .= " WHERE s.fk_type = c.id";
if ($year > 0)
{
    $sql .= " AND date_format(s.date_ech, '%Y') = $year";
}
$sql .= " GROUP BY lower(c.libelle) ASC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    $var = !$var;
    print "<tr $bc[$var]>";
    print '<td><a href="../sociales/index.php?filtre=s.fk_type:'.$obj->type.'">'.$obj->lib.'</a></td>';
    print '<td align="right">'.$obj->nb.'</td>';
    print '<td align="right">'.price($obj->total).'</td>';
    print '<td align="right">'.price($obj->totalpaye).'</td>';
    print '</tr>';
    $i++;
  }
} else {
  print "<tr><td>".$db->error()."</td></tr>";
}

/*
 * Factures fournisseurs
 */
$sql = "SELECT count(f.rowid) as nb, sum(total_ttc) as total, sum(IF(paye=1,total_ttc,0)) as totalpaye";
$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn as f";
if ($year > 0)
{
    $sql .= " WHERE date_format(f.datef, '%Y') = $year";
}

if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;

  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    $var = !$var;
    print "<tr $bc[$var]>";
    print '<td>Factures founisseurs</td>';
    print '<td align="right">'.$obj->nb.'</td>';
    print '<td align="right">'.price($obj->total).'</td>';
    print '<td align="right">'.price($obj->totalpaye).'</td>';
    print '</tr>';
    $i++;
  }
} else {
  print "<tr><td>".$db->error()."</td></tr>";
}

print "</table><br>";


$db->close();
 
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
