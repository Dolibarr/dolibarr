<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/adherents/index.php
        \ingroup    adherent
        \brief      Page accueil module adherents
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/adherent_type.class.php");


$langs->load("companies");
$langs->load("members");


llxHeader();

$staticmember=new Adherent($db);
$statictype=new AdherentType($db);

print_fiche_titre($langs->trans("MembersArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="100%" colspan="2" class="notopnoleft">';



print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Type").'</td>';
print '<td align=right>'.$langs->trans("MembersStatusToValid").'</td>';
print '<td align=right>'.$langs->trans("MenuMembersNotUpToDate").'</td>';
print '<td align=right>'.$langs->trans("MenuMembersUpToDate").'</td>';
print '<td align=right>'.$langs->trans("MembersStatusResiliated").'</td>';
print "</tr>\n";

$var=True;


$Adherents=array();
$AdherentsAValider=array();
$AdherentsResilies=array();
$AdherentType=array();
$Cotisants=array();

# Liste les adherents
$sql = "SELECT t.rowid, t.libelle, t.cotisation,";
$sql.= " d.statut, count(d.rowid) as somme";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as d ON t.rowid = d.fk_adherent_type";
$sql.= " GROUP BY t.rowid, t.libelle, t.cotisation, d.statut";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
		$objp = $db->fetch_object($result);

		$adhtype=new AdherentType($db);
		$adhtype->id=$objp->rowid;
		$adhtype->cotisation=$objp->cotisation;
		$adhtype->libelle=$objp->libelle;
		$AdherentType[$objp->rowid]=$adhtype;

		if ($objp->statut == -1) { $AdherentsAValider[$objp->rowid]=$objp->somme; }
		if ($objp->statut == 1)  { $Adherents[$objp->rowid]=$objp->somme; }
		if ($objp->statut == 0)  { $AdherentsResilies[$objp->rowid]=$objp->somme; }

		if ($objp->cotisation != 'yes')
		{
			$Cotisants[$objp->rowid]=$Adherents[$objp->rowid]=$objp->somme;
		}
		else
		{
			$Cotisants[$objp->rowid]=0;	// Calculé plus loin
		}
		$i++;
	}
	$db->free($result);
}

# Liste les cotisants a jour
$sql = "SELECT count(*) as somme , d.fk_adherent_type";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d";
$sql.= " WHERE d.statut = 1 AND d.datefin >= ".$db->idate(mktime());
$sql.= " GROUP BY d.fk_adherent_type";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);
      $Cotisants[$objp->fk_adherent_type]=$objp->somme;
      $i++;
    }
  $db->free();

}
$SommeA=0;
$SommeB=0;
$SommeC=0;
$SommeD=0;

foreach ($AdherentType as $key => $adhtype)
{
	$var=!$var;
	print "<tr $bc[$var]>";
	print '<td><a href="type.php?rowid='.$adhtype->id.'">'.img_object($langs->trans("ShowType"),"group").' '.$adhtype->libelle.'</a></td>';
	print '<td align="right">'.(isset($AdherentsAValider[$key]) && $AdherentsAValider[$key] > 0?$AdherentsAValider[$key]:'').' '.$staticmember->LibStatut(-1,$adhtype->cotisation,0,3).'</td>';
	print '<td align="right">'.(isset($Adherents[$key]) && ($Adherents[$key]-$Cotisants[$key] > 0) ? $Adherents[$key]-$Cotisants[$key]:'').' '.$staticmember->LibStatut(1,$adhtype->cotisation,0,3).'</td>';
	print '<td align="right">'.(isset($Cotisants[$key]) && $Cotisants[$key] > 0 ? $Cotisants[$key]:'').' '.$staticmember->LibStatut(1,$adhtype->cotisation,mktime(),3).'</td>';
	print '<td align="right">'.(isset($AdherentsResilies[$key]) && $AdherentsResilies[$key]> 0 ?$AdherentsResilies[$key]:'').' '.$staticmember->LibStatut(0,$adhtype->cotisation,0,3).'</td>';
	print "</tr>\n";
	$SommeA+=isset($AdherentsAValider[$key])?$AdherentsAValider[$key]:0;
	$SommeB+=isset($Adherents[$key])?$Adherents[$key]-$Cotisants[$key]:0;
	$SommeC+=isset($Cotisants[$key])?$Cotisants[$key]:0;
	$SommeD+=isset($AdherentsResilies[$key])?$AdherentsResilies[$key]:0;
}
print '<tr class="liste_total">';
print '<td> <b>'.$langs->trans("Total").'</b> </td>';
print '<td align="right"><b>'.$SommeA.' '.$staticmember->LibStatut(-1,$adhtype->cotisation,0,3).'</b></td>';
print '<td align="right"><b>'.$SommeB.' '.$staticmember->LibStatut(1,$adhtype->cotisation,0,3).'</b></td>';
print '<td align="right"><b>'.$SommeC.' '.$staticmember->LibStatut(1,$adhtype->cotisation,mktime(),3).'</b></td>';
print '<td align="right"><b>'.$SommeD.' '.$staticmember->LibStatut(0,$adhtype->cotisation,0,3).'</b></td>';
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


print '</td><td class="notopnoleftnoright" valign="top">';

/*
 * Dernières adherent
 */
$max=5;

$sql = "SELECT a.rowid, a.statut, a.nom, a.prenom,";
$sql.= " ".$db->pdate("a.tms")." as datem,  ".$db->pdate("datefin")." as date_end_subscription,";
$sql.= " ta.rowid as typeid, ta.libelle, ta.cotisation";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as a, ".MAIN_DB_PREFIX."adherent_type as ta";
$sql.= " WHERE a.fk_adherent_type = ta.rowid";
$sql.= " ORDER BY a.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="4">'.$langs->trans("LastMembersModified",$max).'</td></tr>';

    $num = $db->num_rows($resql);
    if ($num)
    {
        $i = 0;
        $var = True;
        while ($i < $num)
        {
            $var=!$var;
            $obj = $db->fetch_object($resql);
            print "<tr $bc[$var]>";
            $staticmember->id=$obj->rowid;
            $staticmember->ref=trim($obj->prenom.' '.$obj->nom);
            $statictype->id=$obj->typeid;
            $statictype->libelle=$obj->libelle;
            print '<td>'.$staticmember->getNomUrl(1).'</td>';
            print '<td>'.$statictype->getNomUrl(1).'</td>';
            print '<td>'.dolibarr_print_date($obj->datem,'dayhour').'</td>';
            print '<td align="right">'.$staticmember->LibStatut($obj->statut,($obj->cotisation=='yes'?1:0),$obj->date_end_subscription,5).'</td>';
            print '</tr>';
            $i++;
        }
    }
    print "</table><br>";
}
else
{
	dolibarr_print_error($db);
}



// Tableau résumé par an
$Total=array();
$Number=array();
$tot=0;
$numb=0;

$sql = "SELECT c.cotisation, ".$db->pdate("c.dateadh")." as dateadh";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."cotisation as c";
$sql.= " WHERE d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != '')
{
	$sql .= " AND dateadh LIKE '$date_select%'";
}
$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows($result);
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);
      $year=dolibarr_print_date($objp->dateadh,"%Y");
      $Total[$year]=(isset($Total[$year])?$Total[$year]:0)+$objp->cotisation;
      $Number[$year]=(isset($Number[$year])?$Number[$year]:0)+1;
      $tot+=$objp->cotisation;
      $numb+=1;
      $i++;
    }
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">'.$langs->trans("AmountAverage").'</td>';
print "</tr>\n";

$var=true;
krsort($Total);
foreach ($Total as $key=>$value)
{
    $var=!$var;
    print "<tr $bc[$var]>";
	print "<td><a href=\"cotisations.php?date_select=$key\">$key</a></td>";
	print "<td align=\"right\">".$Number[$key]."</td>";
	print "<td align=\"right\">".price($value)."</td>";
	print "<td align=\"right\">".price(price2num($value/$Number[$key],'MT'))."</td>";
	print "</tr>\n";
}

// Total
print '<tr class="liste_total">';
print '<td>'.$langs->trans("Total").'</td>';
print "<td align=\"right\">".$numb."</td>";
print '<td align="right">'.price($tot)."</td>";
print "<td align=\"right\">".price(price2num($numb>0?($tot/$numb):0,'MT'))."</td>";
print "</tr>\n";
print "</table><br>\n";

print '</td></tr>';
print '</table>';






$db->close();

llxFooter('$Date$ - $Revision$');
?>
