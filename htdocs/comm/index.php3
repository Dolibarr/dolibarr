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

llxHeader();

$db = new Db();

function valeur($sql) 
{
  global $db;
  if ( $db->query($sql) ) 
    {
      if ( $db->num_rows() ) 
	{
	  $valeur = $db->result(0,0);
	}
      $db->free();
    }
  return $valeur;
}
/*
 *
 */
$db = new Db();


if ($action == 'add_bookmark')
{
  $sql = "DELETE FROM llx_bookmark WHERE fk_soc = ".$socidp." AND fk_user=".$user->id;
  if (! $db->query($sql) )
    {
      print $db->error();
    }
  $sql = "INSERT INTO llx_bookmark (fk_soc, dateb, fk_user) VALUES ($socidp, now(),".$user->id.");";
  if (! $db->query($sql) )
    {
      print $db->error();
    }
}

if ($action == 'del_bookmark')
{
  $sql = "DELETE FROM llx_bookmark WHERE rowid=$bid";
  $result = $db->query($sql);
}


print_titre("Espace commercial");

print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="30%">';

print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
print "<TR class=\"liste_titre\">";
print "<td colspan=\"2\">Propositions commerciales</td>";
print "</TR>\n";

$sql = "SELECT count(*) FROM llx_propal WHERE fk_statut = 0";
if (valeur($sql))
{
  $var=!$var;
  print "<tr $bc[$var]><td><a href=\"propal.php3?viewstatut=0\">Brouillons</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
}

$sql = "SELECT count(*) FROM llx_propal WHERE fk_statut = 1";
if (valeur($sql))
{
  $var=!$var;
  print "<tr $bc[$var]><td><a href=\"propal.php3?viewstatut=1\">Ouvertes</a></td><td align=\"right\">".valeur($sql)."</td></tr>";
}


print "</table><br>";
/*
 *
 *
 */

$sql = "SELECT s.idp, s.nom,b.rowid as bid";
$sql .= " FROM llx_societe as s, llx_bookmark as b";
$sql .= " WHERE b.fk_soc = s.idp AND b.fk_user = ".$user->id;
$sql .= " ORDER BY lower(s.nom) ASC";

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\">";
  print "<TD colspan=\"2\">Bookmark</td>";
  print "</TR>\n";

  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var = !$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php3?socid='.$obj->idp.'">'.$obj->nom.'</a></td>';
      print '<td align="right"><a href="index.php3?action=del_bookmark&bid='.$obj->bid.'">';
      print '<img src="'DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/editdelete.png" border="0"></a></td>';
      print '</tr>';
      $i++;
    }
  print '</table>';
}
/*
 * Actions commerciales a faire
 *
 *
 */
print '</td><td valign="top" width="70%">';

$sql = "SELECT a.id, ".$db->pdate("a.datea")." as da, c.libelle, a.fk_user_author, a.fk_contact, p.name, s.nom as sname";
$sql .= " FROM llx_actioncomm as a, c_actioncomm as c, llx_socpeople as p, llx_societe as s";
$sql .= " WHERE c.id=a.fk_action AND a.percent < 100 AND a.fk_user_action = $user->id AND a.fk_contact = p.idp AND p.fk_soc = s.idp";
$sql .= " ORDER BY a.datea DESC";

if ( $db->query($sql) ) 
{

  print '<TABLE border="0" cellspacing="0" cellpadding="3" width="100%">';
  print '<TR class="liste_titre">';
  print '<td colspan="4">Actions à faire</td>';
  print "</TR>\n";

  $i = 0;
  while ($i < $db->num_rows() ) 
    {
      $obj = $db->fetch_object($i);
      $var=!$var;
      
      print "<tr $bc[$var]><td>".strftime("%d %b %Y",$obj->da)."</td>";
      print "<td><a href=\"action/fiche.php3?id=$obj->id\">$obj->libelle $obj->label</a></td><td>$obj->name</td><td>$obj->sname</td></tr>";
      $i++;
    }
  $db->free();
  print "</table><br>";
} 
else
{
  print $db->error();
}


print '</td></tr>';

print '</table>';

$db->close();
 


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
