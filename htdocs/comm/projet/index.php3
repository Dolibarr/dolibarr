<?PHP
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
require("./pre.inc.php3");
require("./project.class.php3");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader("","../");
print_titre("Projets");
print '<table width="100%">';
print '<tr>';

if($socidp && $user->societe_id == 0)
{
  print '<td>[<a href="fiche.php3?socidp='.$socidp.'&action=create">Nouveau projet</a>]</td>';
}
print '</tr></table>';

$db = new Db();
/*
 * Traitements des actions
 *
 */
if ($action == 'create') {

  $pro = new Project($db);
  $pro->socidp = $socidp;
  $pro->ref = $ref;
  $pro->title = $title;

  $pro->create( $user->id);
}

/*
 *
 * Affichage
 *
 */
if ($sortfield == "")
{
  $sortfield="lower(p.label)";
}
if ($sortorder == "")
{
  $sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 *
 *
 * Liste des projets
 *
 * 
 */

print "<P>";
$sql = "SELECT s.nom, s.idp, p.rowid as projectid, p.ref, p.title,".$db->pdate("p.dateo")." as do";
$sql .= " FROM societe as s, llx_projet as p";
$sql .= " WHERE p.fk_soc = s.idp";

if ($socidp)
{ 
  $sql .= " AND s.idp = $socidp"; 
}

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print '<p><TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  
  print '<TR class="liste_titre">';
  print "<TD><a href=\"$PHP_SELF?sortfield=lower(p.label)&sortorder=ASC\">Societe</a></td>";
  print "<TD>Réf</TD><td>Titre</td>";
  print "</TR>\n";

  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
    
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"../fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
      print "<TD><a href=\"fiche.php3?id=$objp->projectid\">$objp->ref</a></TD>\n";
      print "<TD><a href=\"fiche.php3?id=$objp->projectid\">$objp->title</a></TD>\n";
      print "<td>&nbsp;</td>";
      print "</TR>\n";
    
      $i++;
    }
  
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error();
}

$db->close();
llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
