<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/contrat/services.php
        \ingroup    contrat
		\brief      Page liste des contrats en service
		\version    $Id$
*/

require("./pre.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/contrat.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe.class.php");

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

$filter=isset($_GET["filter"])?$_GET["filter"]:$_POST["filter"];
$search_nom=isset($_GET["search_nom"])?$_GET["search_nom"]:$_POST["search_nom"];
$search_contract=isset($_GET["search_contract"])?$_GET["search_contract"]:$_POST["search_contract"];
$search_service=isset($_GET["search_service"])?$_GET["search_service"]:$_POST["search_service"];
$statut=isset($_GET["statut"])?$_GET["statut"]:1;
$socid=$_GET["socid"];

// Security check
$contratid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');


$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);
$companystatic=new Societe($db);

/*
 * View
 */

$now=gmmktime();

$form=new Form($db);

llxHeader();

$sql = "SELECT c.rowid as cid, c.ref, c.statut as cstatut,";
$sql.= " s.rowid as socid, s.nom,";
$sql.= " cd.rowid, cd.description, cd.statut, p.rowid as pid, p.label as label,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " sc.fk_soc, sc.fk_user,";
$sql.= " ".$db->pdate("cd.date_ouverture_prevue")." as date_ouverture_prevue,";
$sql.= " ".$db->pdate("cd.date_ouverture")." as date_ouverture,";
$sql.= " ".$db->pdate("cd.date_fin_validite")." as date_fin_validite,";
$sql.= " ".$db->pdate("cd.date_cloture")." as date_cloture";
$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c,";
$sql.= " ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql.= " WHERE";
$sql.= " c.rowid = cd.fk_contrat";
$sql.= " AND c.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($mode == "0") $sql.= " AND cd.statut = 0";
if ($mode == "4") $sql.= " AND cd.statut = 4";
if ($mode == "5") $sql.= " AND cd.statut = 5";
if ($filter == "expired") $sql.= " AND date_fin_validite < ".$db->idate(mktime());
if ($search_nom)      $sql.= " AND s.nom like '%".addslashes($search_nom)."%'";
if ($search_contract) $sql.= " AND c.rowid = '".addslashes($search_contract)."'";
if ($search_service)  $sql.= " AND (p.ref like '%".addslashes($search_service)."%' OR p.description like '%".addslashes($search_service)."%')";
if ($socid > 0)       $sql.= " AND s.rowid = ".$socid;
$filter_date1=dolibarr_mktime(0,0,0,$_REQUEST['op1month'],$_REQUEST['op1day'],$_REQUEST['op1year']);
$filter_date2=dolibarr_mktime(0,0,0,$_REQUEST['op2month'],$_REQUEST['op2day'],$_REQUEST['op2year']);
if (! empty($_REQUEST['filter_op1']) && $_REQUEST['filter_op1'] != -1 && $filter_date1 != '') $sql.= " AND date_ouverture_prevue ".$_REQUEST['filter_op1']." ".$db->idate($filter_date1);
if (! empty($_REQUEST['filter_op2']) && $_REQUEST['filter_op2'] != -1 && $filter_date2 != '') $sql.= " AND date_fin_validite ".$_REQUEST['filter_op2']." ".$db->idate($filter_date2);
$sql .= $db->order($sortfield,$sortorder);
$sql .= $db->plimit($limit + 1 ,$offset);

//print $sql;
dolibarr_syslog("contrat/services.php sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $param='';
    if ($search_contract) $param.='&amp;search_contract='.urlencode($search_contract);
    if ($search_nom)      $param.='&amp;search_nom='.urlencode($search_nom);
    if ($search_service)  $param.='&amp;search_service='.urlencode($search_service);
    if ($mode)            $param.='&amp;mode='.$mode;
    if ($filter)          $param.='&amp;filter='.$filter;
    if (! empty($_REQUEST['filter_op1']) && $_REQUEST['filter_op1'] != -1) $param.='&amp;filter_op1='.urlencode($_REQUEST['filter_op1']);
    if (! empty($_REQUEST['filter_op2']) && $_REQUEST['filter_op2'] != -1) $param.='&amp;filter_op2='.urlencode($_REQUEST['filter_op2']);
    if ($filter_date1 != '') $param.='&amp;op1day='.$_REQUEST['op1day'].'&amp;op1month='.$_REQUEST['op1month'].'&amp;op1year='.$_REQUEST['op1year'];
    if ($filter_date2 != '') $param.='&amp;op2day='.$_REQUEST['op2day'].'&amp;op2month='.$_REQUEST['op2month'].'&amp;op2year='.$_REQUEST['op2year'];

    print_barre_liste($langs->trans("ListOfServices"), $page, "services.php", $param, $sortfield, $sortorder,'',$num);

    print '<table class="liste" width="100%">';

    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans("Contract"),"services.php", "c.rowid",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Service"),"services.php", "p.description",$param,"","",$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Company"),"services.php", "s.nom",$param,"","",$sortfield,$sortorder);
    // Date debut
    if ($mode == "0") print_liste_field_titre($langs->trans("DateStartPlannedShort"),"services.php", "cd.date_ouverture_prevue",$param,'',' align="center"',$sortfield,$sortorder);
    if ($mode == "" || $mode > 0) print_liste_field_titre($langs->trans("DateStartRealShort"),"services.php", "cd.date_ouverture",$param,'',' align="center"',$sortfield,$sortorder);
    // Date fin
    if ($mode == "" || $mode < 5) print_liste_field_titre($langs->trans("DateEndPlannedShort"),"services.php", "cd.date_fin_validite",$param,'',' align="center"',$sortfield,$sortorder);
    else print_liste_field_titre($langs->trans("DateEndRealShort"),"services.php", "cd.date_cloture",$param,'',' align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans("Status"),"services.php", "cd.statut,c.statut",$param,"","align=\"right\"",$sortfield,$sortorder);
    print "</tr>\n";

    print '<form method="POST" action="services.php">';
    print '<tr class="liste_titre">';
    print '<td class="liste_titre">';
    print '<input type="hidden" name="filter" value="'.$filter.'">';
    print '<input type="hidden" name="mode" value="'.$mode.'">';
    print '<input type="text" class="flat" size="3" name="search_contract" value="'.stripslashes($search_contract).'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="18" name="search_service" value="'.stripslashes($search_service).'">';
    print '</td>';
    print '<td class="liste_titre">';
    print '<input type="text" class="flat" size="24" name="search_nom" value="'.stripslashes($search_nom).'">';
    print '</td>';
    print '<td class="liste_titre" align="center">';
    $arrayofoperators=array('<'=>'<','>'=>'>');
	print $form->select_array('filter_op1',$arrayofoperators,$_REQUEST['filter_op1'],1);
    print ' ';
    $filter_date1=dolibarr_mktime(0,0,0,$_REQUEST['op1month'],$_REQUEST['op1day'],$_REQUEST['"op1year']);
    print $form->select_date($filter_date1,'op1',0,0,1);
    print '</td>';
    print '<td class="liste_titre" align="center">';
    $arrayofoperators=array('<'=>'<','>'=>'>');
    print $form->select_array('filter_op2',$arrayofoperators,$_REQUEST['filter_op2'],1);
    print ' ';
    $filter_date2=dolibarr_mktime(0,0,0,$_REQUEST['op2month'],$_REQUEST['op2day'],$_REQUEST['op2year']);
    print $form->select_date($filter_date2,'op2',0,0,1);
    print '</td>';
    print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
    print "</td>";
    print "</tr>\n";
    print '</form>';

    $var=True;
    while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($resql);
        $var=!$var;
        print "<tr $bc[$var]>";
        print '<td>';
        $contractstatic=new Contrat($db);
        $contractstatic->id=$obj->cid;
        $contractstatic->ref=$obj->ref?$obj->ref:$obj->cid;
        print $contractstatic->getNomUrl(1);
        print '</td>';

        // Service
        print '<td>';
        if ($obj->pid)
        {
        	print '<a href="../product/fiche.php?id='.$obj->pid.'">'.img_object($langs->trans("ShowService"),"service").' '.dolibarr_trunc($obj->label,20).'</a>';
        }
        else
        {
        	print dolibarr_trunc($obj->description,20);
    	}
        print '</td>';

        // Third party
        print '<td>';
        $companystatic->id=$obj->socid;
        $companystatic->nom=$obj->nom;
        $companystatic->client=1;
		print $companystatic->getNomUrl(1,'customer',32);
		print '</td>';

        // Start date
        if ($mode == "0") {
            print '<td align="center">';
            print ($obj->date_ouverture_prevue?dolibarr_print_date($obj->date_ouverture_prevue):'&nbsp;');
            if ($obj->date_ouverture_prevue && ($obj->date_ouverture_prevue < ($now - $conf->contrat->services->inactifs->warning_delay)))
            print img_picto($langs->trans("Late"),"warning");
            else print '&nbsp;&nbsp;&nbsp;&nbsp;';
            print '</td>';
        }
        if ($mode == "" || $mode > 0) print '<td align="center">'.($obj->date_ouverture?dolibarr_print_date($obj->date_ouverture):'&nbsp;').'</td>';
        // Date fin
        if ($mode == "" || $mode < 5) print '<td align="center">'.($obj->date_fin_validite?dolibarr_print_date($obj->date_fin_validite):'&nbsp;');
        else print '<td align="center">'.dolibarr_print_date($obj->date_cloture);
        // Icone warning
        if ($obj->date_fin_validite && $obj->date_fin_validite < ($now - $conf->contrat->services->expires->warning_delay) && $obj->statut < 5) print img_warning($langs->trans("Late"));
        else print '&nbsp;&nbsp;&nbsp;&nbsp;';
        print '</td>';
        print '<td align="right" nowrap="nowrap">';
        if ($obj->cstatut == 0)
        {
        	print $contractstatic->LibStatut(0,5,($obj->date_fin_validite && $obj->date_fin_validite < $now));
        }
        else
        {
        	print '<a href="'.DOL_URL_ROOT.'/contrat/fiche.php?id='.$obj->cid.'&line='.$obj->rowid.'">';
			print $staticcontratligne->LibStatut($obj->statut,5,($obj->date_fin_validite && $obj->date_fin_validite < $now));
        	print '</a>';
        }
        print '</td>';
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
