<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
		\file 		htdocs/boutique/produits/osc-liste.php
		\ingroup    boutique
		\brief      Page gestion produits du module OsCommerce
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();

if ($sortfield == "") {
  $sortfield="lower(p.label),p.price";
}
if ($sortorder == "") {
  $sortorder="ASC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;


print_barre_liste("Liste des produits oscommerce", $page, "osc-liste.php");

$sql = "SELECT p.products_id, p.products_model, p.products_quantity, p.products_status, d.products_name, m.manufacturers_name, m.manufacturers_id";
$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products as p, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_description as d, ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."manufacturers as m";
$sql .= " WHERE p.products_id = d.products_id AND d.language_id =" . $conf->global->OSC_LANGUAGE_ID;
$sql .= " AND p.manufacturers_id=m.manufacturers_id";
if ($reqstock=='epuise')
{
  $sql .= " AND p.products_quantity <= 0";
}

//$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $dbosc->plimit( $limit ,$offset);

print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print '<TR class="liste_titre">';
print "<td>id</td>";
print "<td>Ref</td>";
print "<td>Titre</td>";
print "<td>Groupe</td>";
print '<td align="center">Stock</td>';
print '<TD align="center">Status</TD>';
print "</TR>\n";

$resql=$dbosc->query($sql);
if ($resql)
{
  $num = $dbosc->num_rows($resql);
  $i = 0;

  $var=True;
  while ($i < $num)
  {
    $objp = $dbosc->fetch_object($resql);
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD>$objp->products_id</TD>\n";
    print "<TD>$objp->products_model</TD>\n";
    print "<TD>$objp->products_name</TD>\n";
    print "<TD>$objp->manufacturers_name</TD>\n";
    print '<TD align="center">'.$objp->products_quantity."</TD>\n";
    print '<TD align="center">'.$objp->products_status."</TD>\n";
    print "</TR>\n";
    $i++;
  }
  $dbosc->free();
}
else
{
	dol_print_error($dbosc);
}

print "</TABLE>";


$dbosc->close();

llxFooter('$Date$ - $Revision$');
?>
