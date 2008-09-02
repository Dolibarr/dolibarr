<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
		\file 		htdocs/boutique/commande/ca.php
		\ingroup    boutique
		\brief      Page ca commandes du module OsCommerce
		\version    $Revision$
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

print_barre_liste("Liste des commandes", $page, "ca.php");

print '<table class="noborder" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td>'.$langs->trans("Description").'</td>';
print '<td align="right">'.$langs->trans("Lastname").'</td></tr>';

$sql = "SELECT sum(t.value) as value";
$sql .= " FROM ".OSC_DB_NAME.".".OSC_DB_TABLE_PREFIX."orders_total as t";
$sql .= " WHERE t.class = 'ot_subtotal'";
 
if ( $dbosc->query($sql) )
{
  $num = $dbosc->num_rows();

  $var=True;
  if ($num > 0)
    {
      $objp = $dbosc->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>Somme des commandes</td>';
      print '<td align="right">'.price($objp->value).'</td>';

      print "</tr>\n";
      $i++;
    }

  $dbosc->free();
}
else
{
  dolibarr_print_error($dbosc);
}

$sql = "SELECT sum(t.value) as value";
$sql .= " FROM ".OSC_DB_NAME.".".OSC_DB_TABLE_PREFIX."orders_total as t";
$sql .= " WHERE t.class = 'ot_shipping'";
 
if ( $dbosc->query($sql) )
{
  $num = $dbosc->num_rows();

  $var=True;
  if ($num > 0)
    {
      $objp = $dbosc->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>Somme des frais de port</td>';
      print '<td align="right">'.price($objp->value).'</td></tr>';
      $i++;
    }

  $dbosc->free();
}
else
{
  dolibarr_print_error($dbosc);
}


print "</table>";

$dbosc->close();

llxFooter('$Date$ - $Revision$');
?>
