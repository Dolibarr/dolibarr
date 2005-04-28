<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/**
	    \file       htdocs/contrat/index.php
        \ingroup    contrat
		\brief      Page liste des contrats
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");
$langs->load("companies");


llxHeader();

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];

$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$socid=$_GET["socid"];


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

print_barre_liste($langs->trans("ContractsArea"), $page, "index.php", "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);


print '<table class="noborder" width="100%">';


print '<tr><td width="30%" valign="top">';

// Légende
print 'Légende<br />';
print '<img src="./statut0.png" border="0" alt="statut">&nbsp;Statut initial<br />';
print '<img src="./statut1.png" border="0" alt="statut">&nbsp;A commander<br />';
print '<img src="./statut2.png" border="0" alt="statut">&nbsp;Commandé chez le fournisseur<br />';
print '<img src="./statut3.png" border="0" alt="statut">&nbsp;Activé chez le fournisseur<br />';
print '<img src="./statut4.png" border="0" alt="statut">&nbsp;Activé chez le client<br />';

print '</td><td width="70%" valign="top">';

// Not activated services
$sql = "SELECT cd.rowid as cid, cd.statut, cd.label, cd.fk_contrat, c.fk_soc, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE cd.statut IN (0,3)";
$sql.= " AND cd.fk_contrat = c.rowid AND c.fk_soc = s.idp";
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

        print '<td width="50"><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowContract"),"contract").' '.$obj->fk_contrat.'</a></td>';
        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service").' '.$obj->label.'</a></td>';
        print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->fk_soc.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
        print '<td width="16"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'"><img src="./statut'.$obj->statut.'.png" border="0" alt="statut"></a></td>';
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

// Last activated services
$max=10;

$sql = "SELECT cd.rowid as cid, cd.statut, cd.label, cd.fk_contrat, c.fk_soc, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE cd.statut = 4";
$sql.= " AND cd.fk_contrat = c.rowid AND c.fk_soc = s.idp";
$sql.= " ORDER BY cd.tms DESC";

if ( $db->query($sql) )
{
    $num = $db->num_rows();
    $i = 0;

    print '<table class="noborder" width="100%">';

    print '<tr class="liste_titre"><td colspan="4">'.$langs->trans("LastActivatedServices",min($num,$max)).'</td>';
    print "</tr>\n";

    $var=True;
    while ($i < min($num,$max))
    {
        $obj = $db->fetch_object();
        $var=!$var;
        print "<tr $bc[$var]>";

        print '<td width="50"><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowContract"),"contract").' '.$obj->fk_contrat.'</a></td>';
        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service").' '.$obj->label.'</a></td>';
        print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->fk_soc.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
        print '<td width="16"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'"><img src="./statut'.$obj->statut.'.png" border="0" alt="statut"></a></td>';
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

$db->close();

llxFooter('$Date$ - $Revision$');
?>
