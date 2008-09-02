<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/prospect/prospects.php
        \ingroup    prospect
		\brief      Page de la liste des prospects
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/prospect.class.php");

$langs->load("propal");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe',$socid,'');

$socname=isset($_GET["socname"])?$_GET["socname"]:$_POST["socname"];
$stcomm=isset($_GET["stcomm"])?$_GET["stcomm"]:$_POST["stcomm"];

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";



/* 
 * Actions
 */
if ($_GET["action"] == 'cstc')
{
  $sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm = ".$_GET["pstcomm"];
  $sql .= " WHERE rowid = ".$_GET["socid"];
  $result=$db->query($sql);
}


/*
 * Affichage liste
 */

$sql = "SELECT s.rowid, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,";
$sql.= " st.libelle as stcomm, s.prefix_comm, s.fk_stcomm, s.fk_prospectlevel,";
$sql.= " d.nom as departement";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d on (d.rowid = s.fk_departement)";
$sql.= " WHERE s.fk_stcomm = st.id AND s.client = 2";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

if (isset($stcomm) && $stcomm != '')
{
    $sql .= " AND s.fk_stcomm=".$stcomm;
}
if ($user->societe_id)
{
    $sql .= " AND s.rowid = " .$user->societe_id;
}

if ($_GET["search_nom"])   $sql .= " AND s.nom like '%".addslashes(strtolower($_GET["search_nom"]))."%'";
if ($_GET["search_ville"]) $sql .= " AND s.ville like '%".addslashes(strtolower($_GET["search_ville"]))."%'";

if ($socname)
{
    $sql .= " AND s.nom like '%".addslashes(strtolower($socname))."%'";
    $sortfield = "s.nom";
    $sortorder = "ASC";
}

// Count total nb of records
$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
}

$sql .= " ORDER BY $sortfield $sortorder, s.nom ASC";
$sql .= $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    if ($num == 1 && $socname)
    {
        $obj = $db->fetch_object($resql);
        Header("Location: fiche.php?socid=".$obj->rowid);
        exit;
    }
    else
    {
        llxHeader();
    }

	$param='&amp;stcomm='.$stcomm.'&amp;search_nom='.urlencode($_GET["search_nom"]).'&amp;search_ville='.urlencode($_GET["search_ville"]);

    print_barre_liste($langs->trans("ListOfProspects"), $page, $_SERVER["PHP_SELF"], $param, $sortfield,$sortorder,'',$num,$nbtotalofrecords);

    print '<table class="liste" width="100%">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Company"),"prospects.php","s.nom","",$param,"valign=\"center\"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Town"),"prospects.php","s.ville","",$param,"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("State"),"prospects.php","s.fk_departement","",$param,"align=\"center\"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateCreation"),"prospects.php","s.datec","",$param,"align=\"center\"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("ProspectLevelShort"),"prospects.php","s.fk_prospectlevel","",$param,"align=\"center\"",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),"prospects.php","s.fk_stcomm","",$param,"align=\"center\"",$sortfield,$sortorder);
    print '<td class="liste_titre" colspan="4">&nbsp;</td>';
    print "</tr>\n";

    print '<form method="get" action="prospects.php">';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" name="search_nom" value="'.$_GET["search_nom"].'">';
    print '</td><td class="liste_titre">';
    print '<input type="text" class="flat" name="search_ville" size="12" value="'.$_GET["search_ville"].'">';
    print '</td>';
    print '<td class="liste_titre" colspan="7" align="right">';
    print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print '</td>';

    print "</tr>\n";

    $i = 0;
    $var=true;

    $prospectstatic=new Prospect($db);
    $prospectstatic->client=2;
    
    while ($i < min($num,$conf->liste_limit))
    {
        $obj = $db->fetch_object($resql);

        $var=!$var;

        print "<tr $bc[$var]>";
        print '<td>';
        $prospectstatic->id=$obj->rowid;
        $prospectstatic->nom=$obj->nom;
        print $prospectstatic->getNomUrl(1);
        print '</td>';
        print "<td>".$obj->ville."&nbsp;</td>";
        print "<td align=\"center\">$obj->departement</td>";
        // Creation date
        print "<td align=\"center\">".dolibarr_print_date($obj->datec)."</td>";
        // Level
        print "<td align=\"center\">";
        print $prospectstatic->LibLevel($obj->fk_prospectlevel);
        print "</td>";
        // Statut
        print "<td align=\"center\">";
        print $prospectstatic->LibStatut($obj->fk_stcomm,2);
        print "</td>";
        
        $sts = array(-1,0,1,2,3);
        print '<td align="right" nowrap>';
        foreach ($sts as $key => $value)
        {
            if ($value <> $obj->fk_stcomm)
            {
                print '<a href="prospects.php?socid='.$obj->rowid.'&amp;pstcomm='.$value.'&amp;action=cstc&amp;'.$param.'">';
                print img_action(0,$value);
                print '</a>&nbsp;';
            }
        }
        print '</td>';

        print "</tr>\n";
        $i++;
    }
    
    if ($num > $conf->liste_limit || $page > 0) print_barre_liste('', $page, $_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords);
    
    print "</table>";
    $db->free($resql);
}
else
{
    dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
