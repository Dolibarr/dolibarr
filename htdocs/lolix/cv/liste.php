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

llxHeader("","","Lolix, liste des candidats");

if ($_GET["action"] == 'deac')
{
  $cv = new Cv($db);
  $cv->id = $_GET["id"];
  $cv->fetch();
  $cv->deactivate();
}


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT c.idp, c.nom, c.prenom";
$sql .= ",".$db->pdate("c.datea")." as da";
$sql .= " FROM lolixfr.candidat as c";
$sql .= " WHERE c.active = 1";

$sortfield = "c.datea";
$sortorder = "ASC";

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();


  print_barre_liste("Liste des CV", $page, $PHP_SELF,"",$sortfield,$sortorder,'',$num);

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
  print "<td>id</td><TD valign=\"center\">";
  print_liste_field_titre("Nom",$PHP_SELF,"c.nom","","",'valign="center"',$sortfield,$sortorder);
  print "</td><td>";
  print_liste_field_titre("Prénom",$PHP_SELF,"c.prenom","","",'valign="center"',$sortfield,$sortorder);
  print "</td>";
  print "<td align=\"center\">";
  print_liste_field_titre("Activé le",$PHP_SELF,"s.fk_departement","","",'valign="center"',$sortfield,$sortorder);
  print "</td>";
  print "</TR>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<tr $bc[$var]><td>".($i+1).'</td>';
      print '<td><a href="offre.php?id='.$obj->idp.'">';
      print img_file();
      print "</a>&nbsp;<a href=\"offre.php?id=$obj->idp\">$obj->nom</A></td>\n";      print "<TD>".$obj->prenom."&nbsp;</TD>\n";

      print '<td align="center">'.dolibarr_print_date($obj->da,'day')."</td>\n";
      print '<td align="center"><a href="liste.php?id='.$obj->idp.'&amp;action=deac">Deac</a></td>';
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
