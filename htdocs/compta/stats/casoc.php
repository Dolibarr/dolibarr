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
require("./pre.inc.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
   $socidp = $user->societe_id;
}
/*
 *
 */
llxHeader();

print_titre("Chiffre d'affaire par société");

/*
 * Ca total
 *
 */

$sql = "SELECT sum(f.total) as ca FROM llx_facture as f";
if ($socidp)
{
  $sql .= " WHERE f.fk_soc = $socidp";
}
$result = $db->query($sql);
if ($result)
{
  if ($db->num_rows() > 0)
    {
      $objp = $db->fetch_object(0);
      $catotal = $objp->ca;
    }
}

print "<b>Total : ".price($catotal)."</b>";

if ($catotal == 0) { $catotal = 1; };


$sql = "SELECT s.nom, s.idp, sum(f.total) as ca";
$sql .= " FROM llx_societe as s,llx_facture as f";
$sql .= " WHERE f.fk_soc = s.idp";
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY s.nom, s.idp ORDER BY ca DESC";
 
$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  if ($num > 0)
    {
      $i = 0;
      print "<p><TABLE border=\"0\" width=\"50%\" cellspacing=\"0\" cellpadding=\"4\">";
      print "<TR class=\"liste_titre\">";
      print "<TD>Société</td>";
      print '<TD align="right">Montant</TD><td align="right">Pourcentage</td>';
      print "<td>&nbsp;</td></tr>\n";
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	  print "<TR $bc[$var]>";
	  
	  print "<TD><a href=\"../fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
	  print '<TD align="right">'.price($objp->ca).'</td>';
	  print '<td align="right">'.price(100 / $catotal * $objp->ca).'%</td>';
	  print "<td align=\"center\"><a href=\"../facture.php3?socidp=$objp->idp\">Voir les factures</a></TD>\n";

	  print "</TR>\n";
	  $i++;
	}
      print "</TABLE>";
    }
  $db->free();
}
else 
{
  print $db->error();
}


$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
