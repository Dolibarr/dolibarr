<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader("","","Lolix - Société a activer");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.idp,s.nom, s.ville,".$db->pdate("datec") ." as da";
$sql .= " FROM lolixfr.societe as s";
$sql .= " WHERE active = 0";

$sortfield = "s.datec";
$sortorder = "DESC";

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();


  print_barre_liste("Societe a activer", $page, "activation.php","",$sortfield,$sortorder,'',$num);

  $i = 0;
  
  if ($sortorder == "DESC")
    {
      $sortorder="ASC";
    }
  else
    {
      $sortorder="DESC";
    }
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<TR class="liste_titre">';
  print "<TD valign=\"center\">";
  print_liste_field_titre("Société",$PHP_SELF,"s.nom");
  print "</td><td>";
  print_liste_field_titre("Ville",$PHP_SELF,"s.ville");
  print "</td><td>&nbsp;</td>";

  print "</tr>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$obj->idp.'">';
      print img_file();
      print "&nbsp;".$obj->nom.'</td>';


      print "</a>&nbsp;<a href=\"offre.php?id=$obj->idp\">$obj->ref</A></td>\n";     
      print "<TD>".$obj->ville."&nbsp;</TD>\n";

      print '<td align="center">'.strftime("%d/%m/%Y",$obj->da)."</td>\n";

      print "</TR>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
