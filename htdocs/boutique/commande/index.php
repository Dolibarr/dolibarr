<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

if ($sortfield == "")
{
  $sortfield="date_purchased";
}
if ($sortorder == "")
{
  $sortorder="DESC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des commandes", $page, "commande.php");

    $sql = "SELECT o.orders_id, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, cc_type, cc_owner, cc_number, cc_expires, last_modified,".$db->pdate("date_purchased")." as date_purchased, orders_status, orders_date_finished, currency, currency_value, t.value";

$sql .= " FROM ".DB_NAME_OSC.".orders as o, ".DB_NAME_OSC.".orders_total as t";
$sql .= " WHERE o.orders_id = t.orders_id AND t.class = 'ot_total'";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
  print "<tr class=\"liste_titre\"><td>Numéro</td><td>".$langs->trans("Date")."</td><td>";
  print_liste_field_titre("Client","commande.php", "customers_name");
  print '</td><td align="right">'.$langs->trans("Total").'</td>';
  //  print '<td align="center">Statut</td>';
  //  print '<td></td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";

      print '<td><a href="fiche.php?id='.$objp->orders_id.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche">&nbsp;';
      print $objp->orders_id ."</a></td><td>";
      print strftime("%d %B %Y",$objp->date_purchased).'</td>';
      print '<TD><a href="../client/fiche.php?id='.$objp->customers_id.'">'.$objp->customers_name."</a></TD>\n";
      print '<td align="right">'.price($objp->value).'</td>';
      //      print '<td align="center">'.$objp->orders_status.'</td>';
      //  print '<td>'.$objp->orders_date_finished.'</td>';
      print "</TR>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
