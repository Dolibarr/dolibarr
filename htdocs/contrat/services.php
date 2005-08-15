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
	    \file       htdocs/contrat/services.php
        \ingroup    contrat
		\brief      Page liste des contrats en service
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("products");
$langs->load("companies");


$mode = isset($_GET["mode"])?$_GET["mode"]:$_POST["mode"];
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])?$_GET["page"]:$_POST["page"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

if (! $sortfield) $sortfield="c.rowid";
if (! $sortorder) $sortorder="ASC";

$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_contract=isset($_GET["search_contract"])?$_GET["search_contract"]:$_POST["search_contract"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$socid=$_GET["socid"];

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


llxHeader();


$sql = "SELECT s.nom, c.rowid as cid, s.idp as sidp, cd.rowid, cd.label, cd.statut, p.rowid as pid,";
$sql.= " ".$db->pdate("cd.date_ouverture_prevue")." as date_ouverture_prevue,";
$sql.= " ".$db->pdate("cd.date_ouverture")." as date_ouverture,";
$sql.= " ".$db->pdate("cd.date_fin_validite")." as date_fin_validite,";
$sql.= " ".$db->pdate("cd.date_cloture")." as date_cloture";
$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
$sql.= " , ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product as p";
$sql.= " , ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= " WHERE c.statut > 0";
$sql.= " AND c.rowid = cd.fk_contrat";
$sql.= " AND c.fk_soc = s.idp AND cd.fk_product = p.rowid";
if ($mode == "0") $sql.= " AND cd.statut = 0";
if ($mode == "4") $sql.= " AND cd.statut = 4";
if ($mode == "5") $sql.= " AND cd.statut = 5";
// \todo filtre sur services expirés
//if ($mode == "expired") $sql.= " AND cd.statut = 1";
if ($search_nom)      $sql.= " AND s.nom like '%".$search_nom."%'";
if ($search_contract) $sql.= " AND c.rowid = '".$search_contract."'";
if ($socid > 0)       $sql.= " AND s.idp = $socid";
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit($limit + 1 ,$offset);

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    print_barre_liste($langs->trans("ListOfServices"), $page, "services.php", "&sref=$sref&snom=$snom&mode=$mode", $sortfield, $sortorder,'',$num);

    print '<table class="liste" width="100%">';

    print '<tr class="liste_titre">';
    $param='&amp;search_contract='.$search_contract;
    $param.='&amp;search_nom='.$search_nom;
    $param.='&amp;mode='.$mode;
    print_liste_field_titre($langs->trans("Contract"),"services.php", "c.rowid","$param","","",$sortfield);
    print_liste_field_titre($langs->trans("Service"),"services.php", "p.label","$param","","",$sortfield);
    print_liste_field_titre($langs->trans("Company"),"services.php", "s.nom","$param","","",$sortfield);
    // Date debut
    if ($mode == "0") print_liste_field_titre($langs->trans("DateStartPlannedShort"),"services.php", "cd.date_ouverture_prevue","$param",'',' align="center"',$sortfield);
    if ($mode == "" || $mode > 0) print_liste_field_titre($langs->trans("DateStartRealShort"),"services.php", "cd.date_ouverture","$param",'',' align="center"',$sortfield);
    // Date fin
    if ($mode == "" || $mode < 5) print_liste_field_titre($langs->trans("DateEndPlannedShort"),"services.php", "cd.date_fin_validite","$param",'',' align="center"',$sortfield);
    else print_liste_field_titre($langs->trans("DateEndRealShort"),"services.php", "cd.date_cloture","$param",'',' align="center"',$sortfield);
    print_liste_field_titre($langs->trans("Status"),"services.php", "cd.statut","$param","","",$sortfield);
    print "</tr>\n";

    print '<form method="POST" action="services.php">';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="3" name="search_contract" value="'.stripslashes($search_contract).'">';
    print '</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" valign="right">';
    print '<input type="text" class="flat" size="24" name="search_nom" value="'.stripslashes($search_nom).'">';
    print '</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print "</td>";
    print "</tr>\n";
    print '</form>';

    $now=mktime();
    $var=True;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;
        print "<tr $bc[$var]>";
        print '<td><a href="fiche.php?id='.$obj->cid.'">'.img_object($langs->trans("ShowContract"),"contract").' '.$obj->cid.'</a></td>';
        print '<td><a href="../product/fiche.php?id='.$obj->pid.'">'.img_object($langs->trans("ShowService"),"service").' '.dolibarr_trunc($obj->label,20).'</a></td>';
        print '<td><a href="../comm/fiche.php?socid='.$obj->sidp.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,44).'</a></td>';
        // Date debut
        if ($mode == "0") {
            print '<td align="center">';
            print ($obj->date_ouverture_prevue?dolibarr_print_date($obj->date_ouverture_prevue):'&nbsp;');
            if ($obj->date_ouverture_prevue && ($obj->date_ouverture_prevue < (time() - $conf->contrat->services->inactifs->warning_delay)))
            print img_picto($langs->trans("Late"),"warning");
            else print '&nbsp;&nbsp;&nbsp;&nbsp;';
            print '</td>';
        }
        if ($mode == "" || $mode > 0) print '<td align="center">'.($obj->date_ouverture?dolibarr_print_date($obj->date_ouverture):'&nbsp;').'</td>';
        // Date fin
        if ($mode == "" || $mode < 5) print '<td align="center">'.($obj->date_fin_validite?dolibarr_print_date($obj->date_fin_validite):'&nbsp;');
        else print '<td align="center">'.dolibarr_print_date($obj->date_cloture);
        // Icone warning
        if ($obj->date_fin_validite && $obj->date_fin_validite < (time() - $conf->contrat->services->expires->warning_delay) && $obj->statut < 5) print img_warning($langs->trans("Late"));
        else print '&nbsp;&nbsp;&nbsp;&nbsp;';
        print '</td>';
        print '<td align="center"><a href="'.DOL_URL_ROOT.'/contrat/ligne.php?id='.$obj->cid.'&ligne='.$obj->rowid.'"><img src="./statut'.$obj->statut.'.png" border="0" alt="statut"></a></td>';
        print "</tr>\n";
        $i++;
    }
    $db->free($resql);

    print "</table>";

}
else
{
    dolibarr_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
