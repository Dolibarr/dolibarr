<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 * 	    \file       htdocs/boutique/notification/index.php
 * 		\ingroup    boutique
 * 		\brief      Page gestion notification OS Commerce
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/boutique/osc_master.inc.php';

$langs->load("products");



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

print_barre_liste("Liste des notifications", $page, "index.php");

$sql = "SELECT c.customers_id, c.customers_lastname, c.customers_firstname, p.products_name, p.products_id";
$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_notifications as n,".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_description as p";
$sql .= ",".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."customers as c";
$sql .= " WHERE n.customers_id = c.customers_id AND p.products_id=n.products_id";
$sql .= " AND p.language_id = ".$conf->global->OSC_LANGUAGE_ID;
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $dbosc->plimit($limit,$offset);

$resql=$dbosc->query($sql);
if ($resql)
{
  $num = $dbosc->num_rows($resql);
  $i = 0;
  print "<table class=\noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre("Client","index.php", "c.customers_lastname");
  print '<td>'.$langs->trans("Product").'</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $dbosc->fetch_object($resql);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td width='70%'><a href=\"fiche.php?id=$objp->rowid\">$objp->customers_firstname $objp->customers_lastname</a></TD>\n";
      print '<td><a href="'.DOL_URL_ROOT.'/boutique/livre/fiche.php?oscid='.$objp->products_id.'">'.$objp->products_name."</a></td>";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $dbosc->free($resql);
}
else
{
  dol_print_error($dbosc);
}

$dbosc->close();

llxFooter();
?>
