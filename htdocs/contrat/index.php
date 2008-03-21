<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/contrat/index.php
        \ingroup    contrat
		\brief      Page liste des contrats
		\version    $Revision$
*/

require("./pre.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

$langs->load("products");
$langs->load("companies");

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

$statut=isset($_GET["statut"])?$_GET["statut"]:1;

// Security check
$contratid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);


/*
 * Affichage page
 */

llxHeader();

print_fiche_titre($langs->trans("ContractsArea"));


print '<table class="notopnoleftnoright" width="100%">';

print '<tr><td width="30%" valign="top" class="notopnoleft">';

/*
 * Recherche Contrat
 */
if ($conf->contrat->enabled)
{
    $var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/liste.php">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAContract").'</td></tr>';
	print '<tr '.$bc[$var].'>';
	print '<td nowrap>'.$langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_contract" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
	print '</tr>';
	print "</table></form>\n";
	print "<br>";
}

// Legend
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("ServicesLegend").'</td></tr>';
print '<tr '.$bc[$var].'><td nowrap>';
print $staticcontratligne->LibStatut(0,4).'<br />';
print $staticcontratligne->LibStatut(4,4).'<br />';
print $staticcontratligne->LibStatut(5,4).'<br />';
print '</td></tr>';
print '</table>';


print '</td><td width="70%" valign="top" class="notopnoleftnoright">';


// Last contracts
$max=5;
$sql = 'SELECT ';
$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND cd.date_fin_validite > sysdate()",1,0).') as nb_running,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite <= sysdate())",1,0).') as nb_late,';
$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
$sql.= " c.rowid as cid, c.ref, c.datec, c.statut, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.rowid ";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid > 0) $sql .= " AND s.rowid = ".$socid;
$sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.rowid";
$sql.= " ORDER BY c.datec DESC";
$sql.= " LIMIT $max";

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    
    print '<table class="noborder" width="100%">';
    
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("LastContracts",5).'</td>';
    print '<td align="center">'.$langs->trans("DateCreation").'</td>';
    print '<td align="left">'.$langs->trans("Status").'</td>';
    print '<td align="center" width="80" colspan="3">'.$langs->trans("Services").'</td>';
    print "</tr>\n";
    
    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var=!$var;
    
        print "<tr $bc[$var]>";
        print "<td><a href=\"fiche.php?id=$obj->cid\">";
        print img_object($langs->trans("ShowContract"),"contract").' '
        . (isset($obj->ref) ? $obj->ref : $obj->cid).'</a>';
        if ($obj->nb_late) print img_warning($langs->trans("Late"));
        print '</td>';
        print '<td><a href="../comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
        print '<td align="center">'.dolibarr_print_date($obj->datec).'</td>';
        print '<td align="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
        print '<td align="center">'.($obj->nb_initial>0 ? $obj->nb_initial.$staticcontratligne->LibStatut(0,3):'').'</td>';
        print '<td align="center">'.($obj->nb_running+$obj->nb_late>0 ? ($obj->nb_running+$obj->nb_late).$staticcontratligne->LibStatut(4,3):'').'</td>';
        print '<td align="center">'.($obj->nb_closed>0 ? $obj->nb_closed.$staticcontratligne->LibStatut(5,3):'').'</td>';
        print "</tr>\n";
        $i++;
    }
    $db->free($result);
    
    print "</table>";
    
}
else
{
    dolibarr_print_error($db);
}

print '<br>';


// Not activated services
$sql = "SELECT cd.rowid as cid, c.ref, cd.statut, cd.label, cd.description as note, cd.fk_contrat, c.fk_soc, s.nom";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.statut=1 AND cd.statut = 0";
$sql.= " AND cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY cd.tms DESC";

if ( $db->query($sql) )
{
    $num = $db->num_rows();
    $i = 0;

    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("NotActivatedServices").'</td>';
    print "</tr>\n";

    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object();
        $var=!$var;
        print "<tr $bc[$var]>";

        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowContract"),"contract").' '
        	.(isset($obj->ref) ? $obj->ref : $obj->fk_contrat).'</a></td>';
        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
        if ($obj->label) print ' '.dolibarr_trunc($obj->label,20).'</a></td>';
        else print '</a> '.dolibarr_trunc($obj->note,20).'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->fk_soc.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,44).'</a></td>';
        print '<td width="16"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
        print $staticcontratligne->LibStatut($obj->statut,3);
        print '</a></td>';
        print "</tr>\n";
        $i++;
    }
    $db->free();

    print "</table>";

}
else
{
    dolibarr_print_error($db);
}

print '<br>';

// Last modified services
$max=5;

$sql = "SELECT cd.rowid as cid, c.ref, cd.statut, cd.label, cd.description as note, cd.fk_contrat, c.fk_soc, s.nom";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY cd.tms DESC";

if ( $db->query($sql) )
{
    $num = $db->num_rows();
    $i = 0;

    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastModifiedServices",min($num,$max)).'</td>';
    print "</tr>\n";

    $var=True;
    while ($i < min($num,$max))
    {
        $obj = $db->fetch_object();
        $var=!$var;
        print "<tr $bc[$var]>";
// width="50" nowrap
        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowContract"),"contract").' '
        .(isset($obj->ref) ? $obj->ref : $obj->fk_contrat).'</a>';
        if ($obj->nb_late) print img_warning($langs->trans("Late"));
        print '</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
        if ($obj->label) print ' '.dolibarr_trunc($obj->label,20).'</a></td>';
        else print '</a> '.dolibarr_trunc($obj->note,20).'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->fk_soc.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,28).'</a></td>';
        print '<td nowrap="nowrap" align="right"><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
        print $staticcontratligne->LibStatut($obj->statut,5);
        print '</a></td>';
        print "</tr>\n";
        $i++;
    }
    $db->free();

    print "</table>";

}
else
{
    dolibarr_print_error($db);
}

print '</td></tr></table>';

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
