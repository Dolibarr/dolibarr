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
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");

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
$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Legend").'</td></tr>';
print '<tr '.$bc[$var].'><td nowrap>';
print '<img src="./statut0.png" border="0" alt="statut">&nbsp;Statut initial<br />';
print '<img src="./statut4.png" border="0" alt="statut">&nbsp;'.$langs->trans("ContractStatusRunning").'<br />';
print '<img src="./statut5.png" border="0" alt="statut">&nbsp;'.$langs->trans("Closed").'<br />';
print '</td></tr>';
print '</table>';

print '<br>';
    
/*
 * Recherche Contrat
 */
if ($conf->contrat->enabled) {
    $var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/liste.php">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAContract").'</td></tr>';
	print '<tr '.$bc[$var].'><td nowrap>';
	print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_contract" size="18"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "</table></form><br>\n";
}


print '</td><td width="70%" valign="top">';


// Last contracts
$max=5;
$sql = "SELECT count(cd.rowid) as nb, c.rowid as cid, c.datec, c.statut, s.nom, s.idp as sidp";
$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.idp ";
if ($socid > 0) $sql .= " AND s.idp = $socid";
$sql.= " GROUP BY c.rowid, c.datec, c.statut, s.nom, s.idp";
$sql.= " ORDER BY c.date_contrat DESC";
$sql.= " LIMIT $max";

$result=$db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    
    print '<table class="noborder" width="100%">';
    
    print '<tr class="liste_titre"><td colspan="5">'.$langs->trans("LastContracts",5).'</td>';
    print "</tr>\n";
    
    $contratstatic=new Contrat($db);

    $var=True;
    while ($i < $num)
    {
        $obj = $db->fetch_object($result);
        $var=!$var;
    
        print "<tr $bc[$var]>";
        print "<td><a href=\"fiche.php?id=$obj->cid\">";
        print img_object($langs->trans("ShowContract"),"contract").' '.$obj->cid.'</a></td>';
        print '<td align="center">'.$langs->trans("ServicesNomberShort",$obj->nb).'</td>';
        print '<td><a href="../comm/fiche.php?socid='.$obj->sidp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
        print '<td align="center">'.dolibarr_print_date($obj->datec).'</td>';
        print '<td align="center">'.$contratstatic->LibStatut($obj->statut).'</td>';
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
$sql = "SELECT cd.rowid as cid, cd.statut, cd.label, cd.description as note, cd.fk_contrat, c.fk_soc, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE c.statut=1 AND cd.statut = 0";
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
        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
        if ($obj->label) print ' '.dolibarr_trunc($obj->label,20).'</a></td>';
        else print '</a> '.dolibarr_trunc($obj->note,20).'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->fk_soc.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,44).'</a></td>';
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

$sql = "SELECT cd.rowid as cid, cd.statut, cd.label, cd.description as note, cd.fk_contrat, c.fk_soc, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
$sql.= " WHERE cd.statut = 4";
$sql.= " AND cd.fk_contrat = c.rowid AND c.fk_soc = s.idp";
$sql.= " ORDER BY cd.date_ouverture DESC";

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
        print '<td><a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
        if ($obj->label) print ' '.dolibarr_trunc($obj->label,20).'</a></td>';
        else print '</a> '.dolibarr_trunc($obj->note,20).'</td>';
        print '<td><a href="'.DOL_URL_ROOT.'/soc.php?socid='.$obj->fk_soc.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,44).'</a></td>';
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

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
