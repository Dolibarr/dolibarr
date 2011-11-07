<?php
/* Copyright (C) 2003		Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004		Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011	Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/compta/deplacement/index.php
 *  \brief      Page list of expenses
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/compta/tva/class/tva.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacement.class.php");

$langs->load("companies");
$langs->load("users");
$langs->load("trips");

// Security check
$socid = GETPOST('socid');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'deplacement','','');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="d.dated";
$limit = $conf->liste_limit;


/*
 * View
 */

$tripandexpense_static=new Deplacement($db);

//$help_url='EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Subvenciones';
$help_url='';
llxHeader('',$langs->trans("ListOfFees"),$help_url);




$totalnb=0;
$sql = "SELECT count(d.rowid) as nb, sum(d.km) as km, d.type";
$sql.= " FROM ".MAIN_DB_PREFIX."deplacement as d";
$sql.= " WHERE d.entity = ".$conf->entity;
$sql.= " GROUP BY d.type";
$sql.= " ORDER BY d.type";

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);

        $somme[$objp->type] = $objp->km;
        $nb[$objp->type] = $objp->nb;
        $totalnb += $objp->nb;
        $i++;
    }
    $db->free($result);
} else {
    dol_print_error($db);
}


print_fiche_titre($langs->trans("ExpensesArea"));

print '<table width="100%" class="notopnoleftnoright">';

// Left area
print '<tr><td class="notopnoleft" width="30%" valign="top">';



print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("Statistics").'</td>';
print "</tr>\n";

$listoftype=$tripandexpense_static->listOfTypes();
foreach ($listoftype as $code => $label)
{
    $dataseries[]=array('label'=>$label,'values'=>array(0=>(isset($nb[$code])?$nb[$code]:0)));
}

if ($conf->use_javascript_ajax)
{
    print '<tr><td align="center" colspan="4">';
    $data=array('series'=>$dataseries);
    dol_print_graph('stats',300,180,$data,1,'pie',1);
    print '</td></tr>';
}

print '<tr class="liste_total">';
print '<td>'.$langs->trans("Total").'</td>';
print '<td align="right">'.$totalnb.'</td>';
print '</tr>';

print '</table>';


// Right area
print '</td><td valign="top">';

$max=10;

$langs->load("boxes");

$sql = "SELECT u.rowid as uid, u.name, u.firstname, d.rowid, d.dated as date, d.tms as dm, d.km";
$sql.= " FROM ".MAIN_DB_PREFIX."deplacement as d, ".MAIN_DB_PREFIX."user as u";
if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE u.rowid = d.fk_user";
$sql.= " AND d.entity = ".$conf->entity;
if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND d.fk_soc = s. rowid AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND d.fk_soc = ".$socid;
$sql.= $db->order("d.tms","DESC");
$sql.= $db->plimit($max, 0);

$result = $db->query($sql);
if ($result)
{
    $var=false;
    $num = $db->num_rows($result);

    $i = 0;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="2">'.$langs->trans("BoxTitleLastModifiedExpenses",min($max,$num)).'</td>';
    print '<td align="right">'.$langs->trans("FeesKilometersOrAmout").'</td>';
    print '<td align="right">'.$langs->trans("DateModificationShort").'</td>';
    print '<td width="16">&nbsp;</td>';
    print '</tr>';
    if ($num)
    {
        $total_ttc = $totalam = $total = 0;

        $deplacementstatic=new Deplacement($db);
        $userstatic=new User($db);
        while ($i < $num && $i < $max)
        {
            $objp = $db->fetch_object($result);
            $deplacementstatic->ref=$objp->rowid;
            $deplacementstatic->id=$objp->rowid;
            $userstatic->id=$objp->uid;
            $userstatic->nom=$objp->name;
            $userstatic->prenom=$objp->firstname;
            print '<tr '.$bc[$var].'>';
            print '<td>'.$deplacementstatic->getNomUrl(1).'</td>';
            print '<td>'.$userstatic->getNomUrl(1).'</td>';
            print '<td align="right">'.$objp->km.'</td>';
            print '<td align="right">'.dol_print_date($db->jdate($objp->dm),'day').'</td>';
            print '<td>'.$deplacementstatic->LibStatut($objp->fk_statut,3).'</td>';
            print '</tr>';
            $var=!$var;
            $i++;
        }

    }
    else
    {
        print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("None").'</td></tr>';
    }
    print '</table><br>';
}
else dol_print_error($db);


$db->close();

llxFooter();
?>
