<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Éric Seigne          <erics@rycks.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/boutique/notification/produits.php
		\ingroup    boutique
		\brief      Page fiche notification produits OS Commerce
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();

if ($sortfield == "") {
  $sortfield="lower(p.products_name)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}


if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des produits suivis", $page, "produits.php");

$sql = "SELECT p.products_name, p.products_id, count(p.products_id) as nb";
$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_notifications as n,".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_description as p";
$sql .= " WHERE p.products_id=n.products_id";
$sql .= " AND p.language_id = ".$conf->global->OSC_LANGUAGE_ID;
$sql .= " GROUP BY p.products_name, p.products_id";
$sql .= $dbosc->plimit( $limit ,$offset);

$resql=$dbosc->query($sql);
if ($resql)
{
  $num = $dbosc->num_rows($resql);
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\">";
  print '<td>Produit</td><td align="center">Nb.</td>';
  print "<td></td>";
  print "<td></td>";
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $dbosc->fetch_object($resql);
      $var=!$var;
      print "<TR $bc[$var]>";

      print '<td><a href="'.DOL_URL_ROOT.'/boutique/livre/fiche.php?oscid='.$objp->products_id.'">'.$objp->products_name."</a></td>";
      print '<td align="center">'.$objp->nb.'</td>';

      print '<td align="center"><a href="index.php?products_id='.$objp->products_id.'">Voir les clients</td>';
      print '<td align="center"><a href="newsletter?products_id='.$objp->products_id.'">Envoyer une news</a></td>';

      print "</TR>\n";
      $i++;
    }
  print "</TABLE>";
  $dbosc->free();
}
else
{
	dol_print_error($dbosc);
}

$dbosc->close();

llxFooter('$Date$ - $Revision$');
?>
