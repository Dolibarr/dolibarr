<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$langs->load("companies");

llxHeader();

if ($sortfield == "") {
  $sortfield="lower(c.customers_lastname)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des clients", $page, "index.php");

$sql = "SELECT c.customers_id, c.customers_lastname, c.customers_firstname, c.customers_email_address, c.customers_newsletter";
$sql .= " FROM ".DB_NAME_OSC.".customers as c";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print "<table class=\"noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\"><td>";
  print_liste_field_titre($langs->trans("Firstname"),"index.php", "c.customers_firstname");
  print "</td><td>";
  print_liste_field_titre($langs->trans("Lastname"),"index.php", "c.customers_lastname");
  print '</td><td>'.$langs->trans("EMail").'</td><td align="center">Newsletter</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$objp->customers_id.'">'.$objp->customers_firstname."</a></TD>\n";
      print '<td><a href="fiche.php?id='.$objp->customers_id.'">'.$objp->customers_lastname."</a></TD>\n";
      print "<td>$objp->customers_email_address</TD>\n";
      print "<td align=\"center\">$objp->customers_newsletter</TD>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
