<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

print_titre("Commandes");

print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="30%">';
/*
 *
 */
print '<form method="post" action="liste.php">';
print '<table border="0" cellspacing="0" cellpadding="3" width="100%">';
print '<tr class="liste_titre"><td colspan="2">Rechercher une commande</td></tr>';
print "<tr $bc[1]><td>";
print 'Num. : <input type="text" name="sf_ref"><input type="submit" value="Rechercher" class="flat"></td></tr>';
print "</table></form>\n";


/*
 * Commandes à valider
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM llx_commande as c, llx_societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 0";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      $i = 0;
      print '<table border="0" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.translate("Commandes à valider").'</td></tr>';
      
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($i);
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">$obj->ref</a></td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}
/*
 *
 */
print '</td><td valign="top" width="70%">';

/*
 * Commandes à traiter
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM llx_commande as c, llx_societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut = 1";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}

if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      $i = 0;
      print '<table border="0" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">'.translate("Commandes à traiter").'</td></tr>';
      
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($i);
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">$obj->ref</a></td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}
/*
 * Commandes à traiter
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.idp FROM llx_commande as c, llx_societe as s";
$sql .= " WHERE c.fk_soc = s.idp AND c.fk_statut > 1 ";
if ($socidp)
{
  $sql .= " AND c.fk_soc = $socidp";
}
$sql .= " ORDER BY c.rowid DESC";
$sql .= $db->plimit(5, 0);
if ( $db->query($sql) ) 
{
  $num = $db->num_rows();
  if ($num)
    {
      $i = 0;
      print '<table border="0" cellspacing="0" cellpadding="3" width="100%">';
      print '<tr class="liste_titre">';
      print '<td colspan="2">5 dernières commandes</td></tr>';
      
      while ($i < $num)
	{
	  $var=!$var;
	  $obj = $db->fetch_object($i);
	  print "<tr $bc[$var]><td width=\"20%\"><a href=\"fiche.php?id=$obj->rowid\">$obj->ref</a></td>";
	  print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	  $i++;
	}
      print "</table><br>";
    }
}




print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
