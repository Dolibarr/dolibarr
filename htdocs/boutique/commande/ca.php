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

print_barre_liste("Liste des commandes", $page, "ca.php");

print '<table class="noborder" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre"><td>'.$langs->trans("Description").'</td>';
print '<td align="right">'.$langs->trans("LastName").'</td></tr>';

$sql = "SELECT sum(t.value) as value";
$sql .= " FROM ".DB_NAME_OSC.".orders_total as t";
$sql .= " WHERE t.class = 'ot_subtotal'";
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();

  $var=True;
  if ($num > 0)
    {
      $objp = $db->fetch_object(0);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>Somme des commandes</td>';
      print '<td align="right">'.price($objp->value).'</td>';

      print "</tr>\n";
      $i++;
    }

  $db->free();
}
else
{
  print $db->error();
}

$sql = "SELECT sum(t.value) as value";
$sql .= " FROM ".DB_NAME_OSC.".orders_total as t";
$sql .= " WHERE t.class = 'ot_shipping'";
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();

  $var=True;
  if ($num > 0)
    {
      $objp = $db->fetch_object(0);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>Somme des frais de port</td>';
      print '<td align="right">'.price($objp->value).'</td></tr>';
      $i++;
    }

  $db->free();
}
else
{
  print $db->error();
}


print "</table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
