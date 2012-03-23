<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/contrat/liste.php
 *       \ingroup    contrat
 *       \brief      Page liste des contrats
 */

require ("../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

$langs->load("contracts");
$langs->load("products");
$langs->load("companies");

$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_contract=isset($_GET["search_contract"])?$_GET["search_contract"]:$_POST["search_contract"];
$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$socid=isset($_GET['socid'])?$_GET['socid']:$_POST['socid'];

if (! $sortfield) $sortfield="c.rowid";
if (! $sortorder) $sortorder="DESC";

// Security check
$contratid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $contratid,'');

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);


/*
 * View
 */

$now=dol_now();

llxHeader();

$sql = 'SELECT';
$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= ".$db->idate($now).")",1,0).') as nb_running,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < ".$db->idate($now).")",1,0).') as nb_expired,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < ".$db->idate($now - $conf->contrat->services->expires->warning_delay).")",1,0).') as nb_late,';
$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
$sql.= " c.rowid as cid, c.ref, c.datec, c.date_contrat, c.statut,";
$sql.= " s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.rowid ";
$sql.= " AND c.entity = ".$conf->entity;
if ($socid) $sql.= " AND s.rowid = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($search_nom)      $sql.= " AND s.nom like '%".$db->escape($search_nom)."%'";
if ($search_contract) $sql.= " AND c.rowid = '".$db->escape($search_contract)."'";
if ($sall)            $sql.= " AND (s.nom like '%".$db->escape($sall)."%' OR cd.label like '%".$db->escape($sall)."%' OR cd.description like '%".$db->escape($sall)."%')";
$sql.= " GROUP BY c.rowid, c.ref, c.datec, c.date_contrat, c.statut,";
$sql.= " s.nom, s.rowid";
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($conf->liste_limit + 1, $offset);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print_barre_liste($langs->trans("ListOfContracts"), $page, $_SERVER["PHP_SELF"], "&sref=$sref&snom=$snom", $sortfield, $sortorder,'',$num);

    print '<table class="liste" width="100%">';

    print '<tr class="liste_titre">';
    $param='&amp;search_contract='.$search_contract;
    $param.='&amp;search_nom='.$search_nom;
    print_liste_field_titre($langs->trans("Ref"), $_SERVER["PHP_SELF"], "c.rowid","","$param",'',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"), $_SERVER["PHP_SELF"], "s.nom","","$param",'',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("DateCreation"), $_SERVER["PHP_SELF"], "c.datec","","$param",'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("DateContract"), $_SERVER["PHP_SELF"], "c.date_contrat","","$param",'align="center"',$sortfield,$sortorder);
    //print_liste_field_titre($langs->trans("Status"), $_SERVER["PHP_SELF"], "c.statut","","$param",'align="center"',$sortfield,$sortorder);
    print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(0,3).'</td>';
    print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(4,3,0).'</td>';
    print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(4,3,1).'</td>';
    print '<td class="liste_titre" width="16">'.$staticcontratligne->LibStatut(5,3).'</td>';
    print "</tr>\n";

    print '<form method="POST" action="liste.php">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="3" name="search_contract" value="'.$search_contract.'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="24" name="search_nom" value="'.$search_nom.'">';
    print '</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    //print '<td class="liste_titre">&nbsp;</td>';
    print '<td colspan="4" class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print "</td>";
    print "</tr>\n";
    print '</form>';

    $var=True;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;
        print '<tr '.$bc[$var].'>';
        print '<td nowrap="nowrap"><a href="fiche.php?id='.$obj->cid.'">';
        print img_object($langs->trans("ShowContract"),"contract").' '.(isset($obj->ref) ? $obj->ref : $obj->cid) .'</a>';
        if ($obj->nb_late) print img_warning($langs->trans("Late"));
        print '</td>';
        print '<td><a href="../comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
        //print '<td align="center">'.dol_print_date($obj->datec).'</td>';
        print '<td align="center">'.dol_print_date($obj->date_contrat).'</td>';
        //print '<td align="center">'.$staticcontrat->LibStatut($obj->statut,3).'</td>';
        print '<td align="center">'.($obj->nb_initial>0?$obj->nb_initial:'').'</td>';
        print '<td align="center">'.($obj->nb_running>0?$obj->nb_running:'').'</td>';
        print '<td align="center">'.($obj->nb_expired>0?$obj->nb_expired:'').'</td>';
        print '<td align="center">'.($obj->nb_closed>0 ?$obj->nb_closed:'').'</td>';
        print "</tr>\n";
        $i++;
    }
    $db->free($resql);

    print "</table>";

}
else
{
    dol_print_error($db);
}


$db->close();

llxFooter();
?>
