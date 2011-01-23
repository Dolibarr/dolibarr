<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/societe/index.php
 *  \ingroup    societe
 *  \brief      Home page for third parties area
 *  \version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');

// Security check
$result=restrictedArea($user,'societe',0,'','','','');

$thirdparty_static = new Societe($db);

$langs->load("companies");


/*
 * View
 */

$transAreaType = $langs->trans("ThirdPartiesArea");
$helpurl='EN:Module_ThirdParty|FR:Module_Tiers|ES:M&oacute;dulo_Tierceros';

llxHeader("",$langs->trans("ThirdParties"),$helpurl);

print_fiche_titre($transAreaType);

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche tiers
 */
$rowspan=2;
print '<form method="post" action="'.DOL_URL_ROOT.'/societe/societe.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="3">'.$langs->trans("Search").'</td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Name").':</td><td><input class="flat" type="text" size="14" name="search_nom_only"></td>';
print '<td rowspan="'.$rowspan.'"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "<tr $bc[0]><td>";
print $langs->trans("Other").':</td><td><input class="flat" type="text" size="14" name="search_all"></td>';
//print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
print '</tr>';

print "</table></form><br>";


/*
 * Nombre de tiers
 */
$third = array();
$total=0;

$sql = "SELECT s.rowid, s.client, s.fournisseur";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE s.entity = ".$conf->entity;
$result = $db->query($sql);
if ($result)
{
    while ($objp = $db->fetch_object($result))
    {
        if ($objp->client == 1 || $objp->client == 3) $third['customer']++;
        if ($objp->client == 2 || $objp->client == 3) $third['prospect']++;
        if ($objp->fournisseur) $third['supplier']++;

        $total++;
    }
}
else dol_print_error($db);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").'</td></tr>';
if ($conf->societe->enabled)
{
    if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
    {
        $statProducts = "<tr $bc[0]>";
        $statProducts.= '<td><a href="'.DOL_URL_ROOT.'/comm/prospect/prospects.php">'.$langs->trans("Prospects").'</a></td><td align="right">'.round($third['prospect']).'</td>';
        $statProducts.= "</tr>";
    }
    $statProducts.= "<tr $bc[1]>";
    $statProducts.= '<td><a href="'.DOL_URL_ROOT.'/comm/clients.php">'.$langs->trans("Customers").'</a></td><td align="right">'.round($third['customer']).'</td>';
    $statProducts.= "</tr>";
}
if ($conf->fournisseur->enabled)
{
    $statServices = "<tr $bc[0]>";
    $statServices.= '<td><a href="'.DOL_URL_ROOT.'/fourn/liste.php">'.$langs->trans("Suppliers").'</a></td><td align="right">'.round($third['supplier']).'</td>';
    $statServices.= "</tr>";
}
print $statProducts;
print $statServices;
print '<tr class="liste_total"><td>'.$langs->trans("UniqueThirdParties").'</td><td align="right">';
print $total;
print '</td></tr>';
print '</table>';

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';

/*
 * Last third parties modified
 */
$max=15;
$sql = "SELECT s.rowid, s.nom, s.client, s.fournisseur,";
$sql.= " s.tms as datem";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE s.entity = ".$conf->entity;
$sql.= " AND (";
if (! empty($conf->societe->enabled)) $sql.=" s.client in (1,2,3)";
if (! empty($conf->fournisseur->enabled)) $sql.=" OR s.fournisseur in (1)";
$sql.= ")";
$sql.= $db->order("s.tms","DESC");
$sql.= $db->plimit($max,0);

//print $sql;
$result = $db->query($sql) ;
if ($result)
{
    $num = $db->num_rows($result);

    $i = 0;

    if ($num > 0)
    {
        $transRecordedType = $langs->trans("LastModifiedThirdParties",$max);

        print '<table class="noborder" width="100%">';

        print '<tr class="liste_titre"><td colspan="3">'.$transRecordedType.'</td></tr>';

        $var=True;

        while ($i < $num)
        {
            $objp = $db->fetch_object($result);

            $var=!$var;
            print "<tr $bc[$var]>";
            // Name
            print '<td nowrap="nowrap">';
            $thirdparty_static->id=$objp->rowid;
            $thirdparty_static->nom=$objp->nom;
            $thirdparty_static->client=$objp->client;
            $thirdparty_static->fournisseur=$objp->fournisseur;
            $thirdparty_static->datem=$db->jdate($objp->datem);
            print $thirdparty_static->getNomUrl(1,'',16);
            print "</td>\n";
            // Type
            print '<td align="center">';
            if ($thirdparty_static->client==1 || $thirdparty_static->client==3)
            {
                print "<a href=\"".DOL_URL_ROOT."/comm/fiche.php?socid=".$thirdparty_static->id."\">".$langs->trans("Customer")."</a>\n";
            }
            if ($thirdparty_static->client == 3 && empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print " / ";
            if (($thirdparty_static->client==2 || $thirdparty_static->client==3) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
            {
                print "<a href=\"".DOL_URL_ROOT."/comm/prospect/fiche.php?socid=".$thirdparty_static->id."\">".$langs->trans("Prospect")."</a>\n";
            }
            if ($conf->fournisseur->enabled && $thirdparty_static->fournisseur)
            {
                if ($thirdparty_static->client) print " / ";
                print '<a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$thirdparty_static->id.'">'.$langs->trans("Supplier").'</a>';
            }
            print '</td>';
            // Last modified date
            print '<td align="right">';
            print dol_print_date($thirdparty_static->datem,'day');
            print "</td>";
            //          print '<td align="right" nowrap="nowrap">';
            //            print $product_static->LibStatut($objp->tobuy,5,1);
            //            print "</td>";
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

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
