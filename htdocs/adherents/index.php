<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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


print_titre("Gestion des adhérents");
print '<br>';

print '<table class="noborder" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre">';
print "<td>Type</td>";
print "<td align=right width=\"80\">A valider</td>";
print "<td align=right width=\"80\">Valides</td>";
print "<td align=right width=\"80\">Cotisants à jour</td>";
print "<td align=right width=\"80\">Résiliés</td>";
print "</tr>\n";

$var=True;


$AdherentsAll=array();
$Adherents=array();
$AdherentsAValider=array();
$AdherentsResilies=array();
$Cotisants=array();

# Liste les adherents
$sql = "SELECT count(*) as somme , t.rowid, t.libelle, d.statut FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid GROUP BY t.libelle, d.statut";

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $AdherentsAll[$objp->libelle]=$objp->rowid; 
      if ($objp->statut == -1) { $AdherentsAValider[$objp->libelle]=$objp->somme; }
      if ($objp->statut == 1) { $Adherents[$objp->libelle]=$objp->somme; }
      if ($objp->statut == 0) { $AdherentsResilies[$objp->libelle]=$objp->somme; }
      $i++;
    }
  $db->free();

}

# Liste les cotisants a jour
$sql = "SELECT count(*) as somme , t.libelle FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid  AND d.statut = 1 AND d.datefin >= now() GROUP BY t.libelle";

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $Cotisants[$objp->libelle]=$objp->somme;
      $i++;
    }
  $db->free();

}
$SommeA=0;
$SommeB=0;
$SommeC=0;
$SommeD=0;

foreach ($AdherentsAll as $key=>$value){
  $var=!$var;
  print "<tr $bc[$var]>";
  print '<td><a href="liste.php?type='.$AdherentsAll[$key].'">'.$key.'</a></td>';
  print '<td align="right">'.$AdherentsAValider[$key].'</td>';
  print '<td align="right">'.$Adherents[$key].'</td>';
  print '<td align="right">'.$Cotisants[$key].'</td>';
  print '<td align="right">'.($AdherentsResilies[$key]?$AdherentsResilies[$key]:0).'</td>';
  print "</tr>\n";
  $SommeA+=$AdherentsAValider[$key];
  $SommeB+=$Adherents[$key];
  $SommeC+=$Cotisants[$key];
  $SommeD+=$AdherentsResilies[$key];
}
$var=!$var;
print "<tr $bc[$var]>";
print '<td> <b>Total</b> </td>';
print '<td align="right"><b>'.$SommeA.'</b></td>';
print '<td align="right"><b>'.$SommeB.'</b></td>';
print '<td align="right"><b>'.$SommeC.'</b></td>';
print '<td align="right"><b>'.$SommeD.'</b></td>';
print "<tr>\n";

print "</table>";

print '<br>';

// Formulaire recherche adhérent
print '<form action="liste.php" method="post">';
print '<input type="hidden" name="action" value="search">';
print '<table class="noborder" cellspacing="0" cellpadding="3">';
print '<tr class="liste_titre">';
print "<td>Rechercher un adhérent</td>";
print "</tr>\n";

print "<tr $bc[$var]>";
print '<td>';

print 'Nom/Prénom <input type="text" name="search" class="flat" size="20">';

print '&nbsp; <input class="flat" type="submit" value="Chercher">';
print '</td></tr>';
print "</table></form>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
