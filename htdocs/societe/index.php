<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/societe/index.php
 *  \ingroup    societe
 *  \brief      Home page for third parties area
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

$langs->load("companies");

$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;

// Security check
$result=restrictedArea($user,'societe',0,'','','','');

$thirdparty_static = new Societe($db);


/*
 * View
 */

$transAreaType = $langs->trans("ThirdPartiesArea");
$helpurl='EN:Module_Third_Parties|FR:Module_Tiers|ES:M&oacute;dulo_Terceros';

llxHeader("",$langs->trans("ThirdParties"),$helpurl);

print_fiche_titre($transAreaType);


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Search area
 */
$rowspan=2;
if (! empty($conf->barcode->enabled)) $rowspan++;
print '<form method="post" action="'.DOL_URL_ROOT.'/societe/societe.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr ".$bc[false]."><td>";
print $langs->trans("Name").':</td><td><input class="flat" type="text" size="14" name="search_nom_only"></td>';
print '<td rowspan="'.$rowspan.'"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
if (! empty($conf->barcode->enabled))
{
	print "<tr ".$bc[false]."><td>";
	print $langs->trans("BarCode").':</td><td><input class="flat" type="text" size="14" name="sbarcode"></td>';
	//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
	print '</tr>';
}
print "<tr ".$bc[false]."><td>";
print $langs->trans("Other").':</td><td><input class="flat" type="text" size="14" name="search_all"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';

print "</table></form><br>";


/*
 * Statistics area
 */
$third = array(
		'customer' => 0,
		'prospect' => 0,
		'supplier' => 0,
		'other' =>0
);
$total=0;

$sql = "SELECT s.rowid, s.client, s.fournisseur";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ' WHERE s.entity IN ('.getEntity('societe', 1).')';
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = ".$socid;
if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur <> 1 OR s.client <> 0)";    // client=0, fournisseur=0 must be visible
//print $sql;
$result = $db->query($sql);
if ($result)
{
    while ($objp = $db->fetch_object($result))
    {
        $found=0;
        if (! empty($conf->societe->enabled) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS) && ($objp->client == 1 || $objp->client == 3)) { $found=1; $third['customer']++; }
        if (! empty($conf->societe->enabled) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS) && ($objp->client == 2 || $objp->client == 3)) { $found=1; $third['prospect']++; }
        if (! empty($conf->fournisseur->enabled) && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS) && $objp->fournisseur) { $found=1; $third['supplier']++; }
        if (! empty($conf->societe->enabled) && $objp->client == 0 && $objp->fournisseur == 0) { $found=1; $third['other']++; }
        if ($found) $total++;
    }
}
else dol_print_error($db);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';
if (! empty($conf->use_javascript_ajax) && ((round($third['prospect'])?1:0)+(round($third['customer'])?1:0)+(round($third['supplier'])?1:0)+(round($third['other'])?1:0) >= 2))
{
    print '<tr><td align="center">';
    $dataseries=array();
    if (! empty($conf->societe->enabled) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS))     $dataseries[]=array('label'=>$langs->trans("Prospects"),'data'=>round($third['prospect']));
    if (! empty($conf->societe->enabled) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS))     $dataseries[]=array('label'=>$langs->trans("Customers"),'data'=>round($third['customer']));
    if (! empty($conf->fournisseur->enabled) && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS)) $dataseries[]=array('label'=>$langs->trans("Suppliers"),'data'=>round($third['supplier']));
    if (! empty($conf->societe->enabled))                                                              $dataseries[]=array('label'=>$langs->trans("Others"),'data'=>round($third['other']));
    $data=array('series'=>$dataseries);
    dol_print_graph('stats',300,180,$data,1,'pie',0);
    print '</td></tr>';
}
else
{
    if (! empty($conf->societe->enabled) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS_STATS))
    {
        $statstring = "<tr $bc[0]>";
        $statstring.= '<td><a href="'.DOL_URL_ROOT.'/comm/prospect/list.php">'.$langs->trans("Prospects").'</a></td><td align="right">'.round($third['prospect']).'</td>';
        $statstring.= "</tr>";
    }
    if (! empty($conf->societe->enabled) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS_STATS))
    {
        $statstring.= "<tr $bc[1]>";
        $statstring.= '<td><a href="'.DOL_URL_ROOT.'/comm/list.php">'.$langs->trans("Customers").'</a></td><td align="right">'.round($third['customer']).'</td>';
        $statstring.= "</tr>";
    }
    if (! empty($conf->fournisseur->enabled) && empty($conf->global->SOCIETE_DISABLE_SUPPLIERS_STATS))
    {
        $statstring2 = "<tr $bc[0]>";
        $statstring2.= '<td><a href="'.DOL_URL_ROOT.'/fourn/liste.php">'.$langs->trans("Suppliers").'</a></td><td align="right">'.round($third['supplier']).'</td>';
        $statstring2.= "</tr>";
    }
    print $statstring;
    print $statstring2;
}
print '<tr class="liste_total"><td>'.$langs->trans("UniqueThirdParties").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Last third parties modified
 */
$max=15;
$sql = "SELECT s.rowid, s.nom as name, s.client, s.fournisseur, s.canvas, s.tms as datem, s.status as status";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ' WHERE s.entity IN ('.getEntity('societe', 1).')';
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid)	$sql.= " AND s.rowid = ".$socid;
if (! $user->rights->fournisseur->lire) $sql.=" AND (s.fournisseur != 1 OR s.client != 0)";
$sql.= $db->order("s.tms","DESC");
$sql.= $db->plimit($max,0);

//print $sql;
$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);

    $i = 0;

    if ($num > 0)
    {
        $transRecordedType = $langs->trans("LastModifiedThirdParties",$max);

        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre"><th colspan="2">'.$transRecordedType.'</td>';
        print '<th>&nbsp;</td>';
        print '<th align="right">'.$langs->trans('Status').'</td>';
        print '</tr>';

        $var=True;

        while ($i < $num)
        {
            $objp = $db->fetch_object($result);

            $var=!$var;
            print "<tr ".$bc[$var].">";
            // Name
            print '<td class="nowrap">';
            $thirdparty_static->id=$objp->rowid;
            $thirdparty_static->name=$objp->name;
            $thirdparty_static->client=$objp->client;
            $thirdparty_static->fournisseur=$objp->fournisseur;
            $thirdparty_static->datem=$db->jdate($objp->datem);
            $thirdparty_static->status=$objp->status;
            $thirdparty_static->canvas=$objp->canvas;
            print $thirdparty_static->getNomUrl(1);
            print "</td>\n";
            // Type
            print '<td align="center">';
            if ($thirdparty_static->client==1 || $thirdparty_static->client==3)
            {
            	$thirdparty_static->name=$langs->trans("Customer");
            	print $thirdparty_static->getNomUrl(0,'customer');
            }
            if ($thirdparty_static->client == 3 && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print " / ";
            if (($thirdparty_static->client==2 || $thirdparty_static->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
            {
            	$thirdparty_static->name=$langs->trans("Prospect");
            	print $thirdparty_static->getNomUrl(0,'prospect');
            }
            if (! empty($conf->fournisseur->enabled) && $thirdparty_static->fournisseur)
            {
                if ($thirdparty_static->client) print " / ";
            	$thirdparty_static->name=$langs->trans("Supplier");
            	print $thirdparty_static->getNomUrl(0,'supplier');
            }
            print '</td>';
            // Last modified date
            print '<td align="right">';
            print dol_print_date($thirdparty_static->datem,'day');
            print "</td>";
            print '<td align="right" class="nowrap">';
            print $thirdparty_static->getLibStatut(3);
            print "</td>";
            print "</tr>\n";
            $i++;
        }

        $db->free();

        print "</table>";
    }
}
else
{
    dol_print_error($db);
}

//print '</td></tr></table>';
print '</div></div></div>';

llxFooter();

$db->close();
?>
