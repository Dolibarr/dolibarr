<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/adherents/index.php
        \ingroup    adherent
        \brief      Page accueil module adherents
*/


require("./pre.inc.php");

$langs->load("companies");
$langs->load("members");


llxHeader();


print_fiche_titre($langs->trans("MembersArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="100%" colspan="2" class="notopnoleft">';



print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Type").'</td>';
print '<td align=right width="80">'.$langs->trans("MembersStatusToValid").'</td>';
print '<td align=right width="80">'.$langs->trans("MembersStatusValidated").'</td>';
print '<td align=right width="80">'.$langs->trans("MembersStatusPayed").'</td>';
print '<td align=right width="80">'.$langs->trans("MembersStatusResiliated").'</td>';
print "</tr>\n";

$var=True;


$AdherentsAll=array();
$Adherents=array();
$AdherentsAValider=array();
$AdherentsResilies=array();
$Cotisants=array();

# Liste les adherents
$sql  = "SELECT count(*) as somme , t.rowid, t.libelle, d.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid";
$sql .= " GROUP BY t.libelle, d.statut";

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows($result);
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);
      $AdherentsAll[$objp->libelle]=$objp->rowid; 
      if ($objp->statut == -1) { $AdherentsAValider[$objp->libelle]=$objp->somme; }
      if ($objp->statut == 1)  { $Adherents[$objp->libelle]=$objp->somme; }
      if ($objp->statut == 0)  { $AdherentsResilies[$objp->libelle]=$objp->somme; }
      $i++;
    }
  $db->free($result);

}

# Liste les cotisants a jour
$sql  = "SELECT count(*) as somme , t.libelle";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid  AND d.statut = 1 AND d.datefin >= now()";
$sql .= " GROUP BY t.libelle";

$result = $db->query($sql);

if ($result) 
{
  $num = $db->num_rows($result);
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);
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
  print '<td><a href="type.php?rowid='.$AdherentsAll[$key].'">'.img_object($langs->trans("ShowType"),"group").' '.$key.'</a></td>';
  print '<td align="right">'.(isset($AdherentsAValider[$key])?$AdherentsAValider[$key]:'').'</td>';
  print '<td align="right">'.(isset($Adherents[$key])?$Adherents[$key]:'').'</td>';
  print '<td align="right">'.(isset($Cotisants[$key])?$Cotisants[$key]:'').'</td>';
  print '<td align="right">'.(isset($AdherentsResilies[$key])?$AdherentsResilies[$key]:'').'</td>';
  print "</tr>\n";
  $SommeA+=isset($AdherentsAValider[$key])?$AdherentsAValider[$key]:0;
  $SommeB+=isset($Adherents[$key])?$Adherents[$key]:0;
  $SommeC+=isset($Cotisants[$key])?$Cotisants[$key]:0;
  $SommeD+=isset($AdherentsResilies[$key])?$AdherentsResilies[$key]:0;
}
print '<tr class="liste_total">';
print '<td> <b>'.$langs->trans("Total").'</b> </td>';
print '<td align="right"><b>'.$SommeA.'</b></td>';
print '<td align="right"><b>'.$SommeB.'</b></td>';
print '<td align="right"><b>'.$SommeC.'</b></td>';
print '<td align="right"><b>'.$SommeD.'</b></td>';
print '</tr>';

print "</table>\n";

print '<br>';


print '</td></tr>';
print '<tr><td width="30%" class="notopnoleft" valign="top">';


// Formulaire recherche adhérent
print '<form action="liste.php" method="post">';
print '<input type="hidden" name="action" value="search">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("SearchAMember").'</td>';
print "</tr>\n";
$var=false;
print "<tr $bc[$var]>";
print '<td>';
print $langs->trans("Name").':</td><td><input type="text" name="search" class="flat" size="16">';
print '</td><td rowspan="2"><input class="button" type="submit" value="'.$langs->trans("Search").'"></td></tr>';
print "<tr $bc[$var]>";
print '<td>';
print $langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="16">';
print '</td></tr>';
print "</table></form>";


print '</td><td class="notopnoleftnoright">';

$sql = "SELECT c.cotisation, ".$db->pdate("c.dateadh")." as dateadh";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql.= " WHERE d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != ''){
  $sql .= " AND dateadh LIKE '$date_select%'";
}
$result = $db->query($sql);
$Total=array();
$Number=array();
$tot=0;
$numb=0;
if ($result) 
{
  $num = $db->num_rows($result);
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);
      $year=strftime("%Y",$objp->dateadh);
      $Total[$year]+=$objp->cotisation;
      $Number[$year]+=1;
      $tot+=$objp->cotisation;
      $numb+=1;
      $i++;
    }
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("Subscriptions").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("Average").'</td>';
print "</tr>\n";

$var=true;
foreach ($Total as $key=>$value)
{
    $var=!$var;
    print "<tr $bc[$var]><td><a href=\"cotisations.php?date_select=$key\">$key</a></td><td align=\"right\">".price($value)."</td><td align=\"right\">".$Number[$key]."</td><td align=\"right\">".price($value/$Number[$key])."</td></tr>\n";
}

// Total
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.price($tot)."</td><td align=\"right\">".$numb."</td><td align=\"right\">".price($numb>0?($tot/$numb):0)."</td></tr>\n";
print "</table><br>\n";

print '</td></tr>';
print '</table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
