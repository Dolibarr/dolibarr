<?php
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
  $sortfield="lower(l.title)";
}
if ($sortorder == "")
{
  $sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$form = '<form action="index.php">'.
'<input type="hidden" name="mode" value="search">'.
'<input type="hidden" name="mode-search" value="soc">'.
'Titre : <input type="text" name="searchvalue" class="flat" size="15">&nbsp;'.
'<input type="submit" class="flat" value="go"></form>';


print_barre_liste("Liste des Livres", $page, "index.php", "", $sortfield, $sortorder, $form);

$sql = "SELECT l.rowid, l.title, l.oscid, l.ref, l.status FROM ".MAIN_DB_PREFIX."livre as l";

if ($searchvalue)
{
  $sql .= " WHERE l.title like '%$searchvalue%'";
}
  
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print "<table class=\"noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\"><td>";
  print_liste_field_titre($langs->trans("Ref"),"index.php", "l.ref");
  print "</td><td>";
  print_liste_field_titre("Titre","index.php", "l.title");
  print "</td>";
  print '<td colspan="3">&nbsp;</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num) {
    $objp = $db->fetch_object();
    $var=!$var;
    print "<tr $bc[$var]>";

    print '<td><a href="fiche.php?id='.$objp->rowid.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche livre"></a>&nbsp;';

    print "<a href=\"fiche.php?id=$objp->rowid\">$objp->ref</a></TD>\n";
    print "<TD width='70%'><a href=\"fiche.php?id=$objp->rowid\">$objp->title</a></TD>\n";


    if ($objp->status == 1)
      {
	print '<td align="center"><img src="/theme/'.$conf->theme.'/img/icon_status_green.png" border="0"></a></td>';
	print '<td align="center"><img src="/theme/'.$conf->theme.'/img/icon_status_red_light.png" border="0"></a></td>';
	print '<td align="right"><a href="'.OSC_CATALOG_URL.'product_info.php?products_id='.$objp->oscid.'">Fiche en ligne</a></TD>';
      }
    else
      {
	print '<td align="center"><img src="/theme/'.$conf->theme.'/img/icon_status_green_light.png" border="0"></a></td>';
	print '<td align="center"><img src="/theme/'.$conf->theme.'/img/icon_status_red.png" border="0"></a></td>';
	print '<td>&nbsp;</td>';
      }

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
