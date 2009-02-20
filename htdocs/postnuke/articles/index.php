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
  $sortfield="lower(p.pn_title)";
}
if ($sortorder == "")
{
  $sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des articles", $page, "index.php", "", $sortfield, $sortorder, $form);

$sql = "SELECT p.pn_sid, p.pn_title FROM " . PN_DB_NAME . "." . PN_TABLE_STORIES_NAME . " as p";

$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print "<table class=\"noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre($langs->trans("Ref"),"index.php", "p.pn_title");
  print '<td colspan="3">&nbsp;</td>';
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object();
      $var=!$var;
      print "<tr $bc[$var]>";
      
      print '<td><a href="fiche.php?id='.$objp->pn_sid.'"><img src="/theme/'.$conf->theme.'/img/filenew.png" border="0" alt="Fiche livre"></a>&nbsp;';
      
      print "<a href=\"fiche.php?id=$objp->pn_sid\">$objp->pn_title</a></td>\n";
      print "<td width='70%'><a href=\"fiche.php?id=$objp->pn_sid\">$objp->title</a></td>\n";
            
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else
{
  dol_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
