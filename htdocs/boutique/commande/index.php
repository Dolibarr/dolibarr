<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * 	    \file       htdocs/boutique/commande/index.php
 * 		\ingroup    boutique
 * 		\brief      Page gestion commandes OSCommerce
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/boutique/osc_master.inc.php');



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

    $sql = "SELECT o.orders_id, customers_id, customers_name, customers_company, customers_street_address, customers_suburb, customers_city, customers_postcode, customers_state, customers_country, customers_telephone, customers_email_address, customers_address_format_id, delivery_name, delivery_company, delivery_street_address, delivery_suburb, delivery_city, delivery_postcode, delivery_state, delivery_country, delivery_address_format_id, billing_name, billing_company, billing_street_address, billing_suburb, billing_city, billing_postcode, billing_state, billing_country, billing_address_format_id, payment_method, cc_type, cc_owner, cc_number, cc_expires, last_modified, date_purchased, orders_status, orders_date_finished, currency, currency_value, t.value";

$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders as o, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."orders_total as t";
$sql .= " WHERE o.orders_id = t.orders_id AND t.class = 'ot_total'";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $dbosc->plimit($limit ,$offset);

$resql=$dbosc->query($sql);
if ($resql)
{
  $num = $dbosc->num_rows($resql);
  $i = 0;
  print "<table class=\"noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\">";
  print "<td>".$langs->trans("Ref")."</td>";
  print "<td>".$langs->trans("Date")."</td>";
  print_liste_field_titre("Client","commande.php", "customers_name");
  print '<td align="right">'.$langs->trans("Total").'</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $dbosc->fetch_object($resql);
      $var=!$var;
      print "<tr $bc[$var]>";

      print '<td><a href="fiche.php?id='.$objp->orders_id.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche">&nbsp;';
      print $objp->orders_id ."</a></td><td>";
      print dol_print_date($dbosc->jdate($objp->date_purchased),'dayhour').'</td>';
      print '<td><a href="../client/fiche.php?id='.$objp->customers_id.'">'.$objp->customers_name."</a></TD>\n";
      print '<td align="right">'.price($objp->value).'</td>';
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $dbosc->free();
}
else
{
  dol_print_error($dbosc);
}

$dbosc->close();

llxFooter();
?>
