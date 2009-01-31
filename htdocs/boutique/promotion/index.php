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
	    \file       htdocs/boutique/promotions/index.php
		\ingroup    boutique
		\brief      Page gestion promotions OSCommerce
		\version    $Revision$
*/

require("./pre.inc.php");

llxHeader();

if ($action == "inactive")
{
  $promotion = new Promotion($dbosc);
  $promotion->set_inactive($id);
}
if ($action == "active")
{
  $promotion = new Promotion($dbosc);
  $promotion->set_active($id);
}

if ($sortfield == "")
{
  $sortfield="pd.products_name";
}
if ($sortorder == "")
{
  $sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des promotions", $page, "index.php", "",$sortfield, $sortorder);

$urladd = "&sortorder=$sortorder&sortfield=$sortfield";

$sql = "SELECT pd.products_name, s.specials_new_products_price, p.products_price, p.products_model, s.status, p.products_id";
$sql .= ",".$dbosc->pdate("expires_date")." as fin";
$sql .= " FROM ".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."specials as s,".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products_description as pd,".$conf->global->OSC_DB_NAME.".".$conf->global->OSC_DB_TABLE_PREFIX."products as p";
$sql .= " WHERE s.products_id = pd.products_id AND pd.products_id = p.products_id AND pd.language_id = ".$conf->global->OSC_LANGUAGE_ID;
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $dbosc->plimit( $limit ,$offset);

$resql=$dbosc->query($sql);
if ($resql)
{
  $num = $dbosc->num_rows($resql);
  $i = 0;
  print '<table class=\"noborder width="100%">';
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"index.php", "p.products_model");
  print_liste_field_titre("Titre","index.php", "pd.products_name");
  print "<td>&nbsp;</td><td>&nbsp;</td><td>Fin</td>";
  print '<td align="right">Prix initial</td>';
  print '<td align="right">Prix remisé</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $dbosc->fetch_object( $i);
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$objp->products_model."</td>";
      print '<td>'.$objp->products_name."</td>";

      if ($objp->status == 1)
	{
	  print '<td align="center"><img src="/theme/'.$conf->theme.'/img/icon_status_green.png" border="0" alt="actif"></td>';
	  print '<td align="center">';
	  print '<a href="index.php?action=inactive&id='.$objp->products_id.''.$urladd.'&page='.$page.'">';
	  print '<img src="/theme/'.$conf->theme.'/img/icon_status_red_light.png" border="0"></a></td>';
	}
      else
	{
	  print '<td align="center">';
	  print '<a href="index.php?action=active&id='.$objp->products_id.''.$urladd.'&page='.$page.'">';
	  print '<img src="/theme/'.$conf->theme.'/img/icon_status_green_light.png" border="0"></a></td>';
	  print '<td align="center"><img src="/theme/'.$conf->theme.'/img/icon_status_red.png" border="0" alt="inactif"></td>';
	}
      print "<td>".dolibarr_print_date($objp->fin,'day')."</td>";
      print '<td align="right">'.price($objp->products_price)."</td>";
      print '<td align="right">'.price($objp->specials_new_products_price)."</td>";
      print "</tr>";
      $i++;
    }
  print "</TABLE>";
  $dbosc->free();
}
else
{
	dolibarr_print_error($dbosc);
}
$dbosc->close();

llxFooter('$Date$ - $Revision$');

?>
