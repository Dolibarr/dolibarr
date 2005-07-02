<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 
/**
        \file       htdocs/comm/bookmark.php
        \brief      Page affichage des bookmarks
        \version    $Revision$
*/

 
require("./pre.inc.php");


llxHeader();

if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="idp";

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$yn["t"] = "oui";
$yn["f"] = "non";
$ynn["1"] = "oui";
$ynn["0"] = "non";



if ($action == 'add')
{
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES ($socidp, now(),'". $user->login ."');";
  if (! $db->query($sql) )
    {
      print $db->error();
    }
}

if ($action == 'delete')
{
  $sql = "DELETE FROM  ".MAIN_DB_PREFIX."bookmark WHERE rowid=$bid AND fk_user = '". $user->login ."'";
  $result = $db->query($sql);
}



print_fiche_titre($langs->trans("Bookmarks"));
 
$sql = "SELECT s.idp, s.nom, ".$db->pdate("b.dateb")." as dateb, b.rowid as bid, b.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."bookmark as b";
$sql.= " WHERE b.fk_soc = s.idp AND s.datea is not null";
$sql.= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  if ($sortorder == "DESC") $sortorder="ASC";
  else $sortorder="DESC";

  print "<table class=\"noborder\" width=\"100%\">";
  print "<tr class=\"liste_titre\">";
  print "<td>&nbsp;</td>";
  print "<td align=\"center\"><a href=\"index.php?sortfield=idp&sortorder=$sortorder&begin=$begin\">Id</a></td>";
  print "<td><a href=\"index.php?sortfield=lower(s.nom)&sortorder=$sortorder&begin=$begin\">Societe</a></td>";

  print "<td>".$langs->trans("Author")."</td>";
  print "<td>".$langs->trans("Date")."</td>";

  print "<td>&nbsp;</td>";
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $obj = $db->fetch_object();
      
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td>" . ($i + 1 + ($limit * $page)) . "</td>";
      print "<td align=\"center\"><b>$obj->idp</b></td>";
      print "<td><a href=\"index.php?socid=$obj->idp\">$obj->nom</a></td>\n";
      print "<td>$obj->fk_user</td>\n";
      print "<td>".dolibarr_print_date($obj->dateb) ."</td>";
      print "<td><a href=\"bookmark.php?action=delete&bid=$obj->bid\">".img_delete()."</a></td>\n";
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

llxFooter('$Date$ - $Revision$');
?>
