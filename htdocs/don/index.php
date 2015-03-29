<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/don/index.php
 *  \ingroup    donations
 *  \brief      Home page of donation module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

$langs->load("donations");

// Security check
$result = restrictedArea($user, 'don');

$donation_static=new Don($db);


/*
 * Actions
 */

// None


/*
 * View
 */

$donstatic=new Don($db);

$help_url='EN:Module_Donations|FR:Module_Dons|ES:M&oacute;dulo_Donaciones';
llxHeader('',$langs->trans("Donations"),$help_url);

$nb=array();
$somme=array();

$sql = "SELECT count(d.rowid) as nb, sum(d.amount) as somme , d.fk_statut";
$sql.= " FROM ".MAIN_DB_PREFIX."don as d";
$sql.= " GROUP BY d.fk_statut";
$sql.= " ORDER BY d.fk_statut";

$result = $db->query($sql);
if ($result)
{
	$i = 0;
    $num = $db->num_rows($result);
    while ($i < $num)
    {
        $objp = $db->fetch_object($result);

        $somme[$objp->fk_statut] = $objp->somme;
        $nb[$objp->fk_statut] = $objp->nb;
        $i++;
    }
    $db->free($result);
} else {
    dol_print_error($db);
}

print_fiche_titre($langs->trans("DonationsArea"));


print '<table width="100%" class="notopnoleftnoright">';

// Left area
print '<tr><td class="notopnoleft" width="30%" valign="top">';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="4">'.$langs->trans("Statistics").'</td>';
print "</tr>\n";

$listofstatus=array(0,1,-1,2);
foreach ($listofstatus as $status)
{
    $dataseries[]=array('label'=>$donstatic->LibStatut($status,1),'data'=>(isset($nb[$status])?(int) $nb[$status]:0));
}

if ($conf->use_javascript_ajax)
{
    print '<tr><td align="center" colspan="4">';
    $data=array('series'=>$dataseries);
    dol_print_graph('stats',300,180,$data,1,'pie',1);
    print '</td></tr>';
}

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Status").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("Total").'</td>';
print '<td align="right">'.$langs->trans("Average").'</td>';
print '</tr>';

$total=0;
$totalnb=0;
$var=true;
foreach ($listofstatus as $status)
{
    $var=!$var;
    print "<tr ".$bc[$var].">";
    print '<td><a href="list.php?statut='.$status.'">'.$donstatic->LibStatut($status,4).'</a></td>';
    print '<td align="right">'.(! empty($nb[$status])?$nb[$status]:'&nbsp;').'</td>';
    print '<td align="right">'.(! empty($nb[$status])?price($somme[$status],'MT'):'&nbsp;').'</td>';
    print '<td align="right">'.(! empty($nb[$status])?price(price2num($somme[$status]/$nb[$status],'MT')):'&nbsp;').'</td>';
    $totalnb += (! empty($nb[$status])?$nb[$status]:0);
    $total += (! empty($somme[$status])?$somme[$status]:0);
    print "</tr>";
}

print '<tr class="liste_total">';
print '<td>'.$langs->trans("Total").'</td>';
print '<td align="right">'.$totalnb.'</td>';
print '<td align="right">'.price($total,'MT').'</td>';
print '<td align="right">'.($totalnb?price(price2num($total/$totalnb,'MT')):'&nbsp;').'</td>';
print '</tr>';
print "</table>";


// Right area
print '</td><td valign="top">';


$max=10;

/*
 * Last modified donations
 */

$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.societe, c.lastname, c.firstname, c.tms as datem, c.amount";
$sql.= " FROM ".MAIN_DB_PREFIX."don as c";
$sql.= " WHERE c.entity = ".$conf->entity;
//$sql.= " AND c.fk_statut > 2";
$sql.= " ORDER BY c.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="5">'.$langs->trans("LastModifiedDonations",$max).'</td></tr>';

    $num = $db->num_rows($resql);
    if ($num)
    {
        $i = 0;
        $var = True;
        while ($i < $num)
        {
            $var=!$var;
            $obj = $db->fetch_object($resql);

            print "<tr ".$bc[$var].">";

            $donation_static->id=$obj->rowid;
            $donation_static->ref=$obj->ref?$obj->ref:$obj->rowid;

            print '<td width="96" class="nobordernopadding nowrap">';
            print $donation_static->getNomUrl(1);
            print '</td>';

            print '<td class="nobordernopadding">';
            print $obj->societe;
            print ($obj->societe && ($obj->lastname || $obj->firstname)?' / ':'');
            print dolGetFirstLastname($obj->lastname,$obj->firstname);
            print '</td>';

            print '<td align="right" class="nobordernopadding">';
            print price($obj->amount,1);
            print '</td>';

            // Date
            print '<td align="center">'.dol_print_date($db->jdate($obj->datem),'day').'</td>';

            print '<td align="right">'.$donation_static->LibStatut($obj->fk_statut,5).'</td>';

            print '</tr>';
            $i++;
        }
    }
    print "</table><br>";
}
else dol_print_error($db);


print '</td></tr></table>';


llxFooter();

$db->close();
