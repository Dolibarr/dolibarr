<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	    \file       htdocs/contrat/services.php
 *      \ingroup    contrat
 *		\brief      Page to list services in contracts
 */

require ("../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once (DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

$langs->load("products");
$langs->load("contracts");
$langs->load("companies");

$mode = GETPOST("mode");
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$offset = $limit * $page ;

if (! $sortfield) $sortfield="c.rowid";
if (! $sortorder) $sortorder="ASC";

$filter=GETPOST("filter");
$search_name=GETPOST("search_name");
$search_contract=GETPOST("search_contract");
$search_service=GETPOST("search_service");
$search_status=GETPOST("search_status","alpha");
$statut=GETPOST('statut')?GETPOST('statut'):1;
$socid=GETPOST('socid','int');

$op1month=GETPOST('op1month');
$op1day=GETPOST('op1day');
$op1year=GETPOST('op1year');
$filter_op1=GETPOST('filter_op1');
$op2month=GETPOST('op2month');
$op2day=GETPOST('op2day');
$op2year=GETPOST('op2year');
$filter_op2=GETPOST('filter_op2');

// Security check
$contratid = GETPOST('id','int');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid);

if ($search_status != '')
{
    $tmp=explode('&', $search_status);
    $mode=$tmp[0];
    if (empty($tmp[1])) $filter='';
    else
    {
        if ($tmp[1] == 'filter=notexpired') $filter='notexpired';
        if ($tmp[1] == 'filter=expired') $filter='expired';
    }
}
else
{
    $search_status = $mode;
    if ($filter == 'expired') $search_status.='&filter=expired';
    if ($filter == 'notexpired') $search_status.='&filter=notexpired';
}

$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);
$companystatic=new Societe($db);

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_name="";
	$search_contract="";
	$search_service="";
	$search_status=-1;
	$op1month="";
	$op1day="";
	$op1year="";
	$filter_op1="";
	$op2month="";
	$op2day="";
	$op2year="";
	$filter_op2="";
	$mode='';
	$filter='';
}

/*
 * View
 */

$now=dol_now();

$form=new Form($db);

llxHeader();

$sql = "SELECT c.rowid as cid, c.ref, c.statut as cstatut,";
$sql.= " s.rowid as socid, s.nom as name,";
$sql.= " cd.rowid, cd.description, cd.statut,";
$sql.= " p.rowid as pid, p.ref as pref, p.label as label, p.fk_product_type as ptype, p.entity as pentity,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " sc.fk_soc, sc.fk_user,";
$sql.= " cd.date_ouverture_prevue,";
$sql.= " cd.date_ouverture,";
$sql.= " cd.date_fin_validite,";
$sql.= " cd.date_cloture";
$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c,";
$sql.= " ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql.= " WHERE c.entity = ".$conf->entity;
$sql.= " AND c.rowid = cd.fk_contrat";
$sql.= " AND c.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($mode == "0") $sql.= " AND cd.statut = 0";
if ($mode == "4") $sql.= " AND cd.statut = 4";
if ($mode == "5") $sql.= " AND cd.statut = 5";
if ($filter == "expired") $sql.= " AND cd.date_fin_validite < '".$db->idate($now)."'";
if ($filter == "notexpired") $sql.= " AND cd.date_fin_validite >= '".$db->idate($now)."'";
if ($search_name)     $sql.= " AND s.nom LIKE '%".$db->escape($search_name)."%'";
if ($search_contract) $sql.= " AND c.rowid = '".$db->escape($search_contract)."'";
if ($search_service)  $sql.= " AND (p.ref LIKE '%".$db->escape($search_service)."%' OR p.description LIKE '%".$db->escape($search_service)."%' OR cd.description LIKE '%".$db->escape($search_service)."%')";
if ($socid > 0)       $sql.= " AND s.rowid = ".$socid;
$filter_date1=dol_mktime(0,0,0,$op1month,$op1day,$op1year);
$filter_date2=dol_mktime(0,0,0,$op2month,$op2day,$op2year);
if (! empty($filter_op1) && $filter_op1 != -1 && $filter_date1 != '') $sql.= " AND date_ouverture_prevue ".$filter_op1." '".$db->idate($filter_date1)."'";
if (! empty($filter_op2) && $filter_op2 != -1 && $filter_date2 != '') $sql.= " AND date_fin_validite ".$filter_op2." '".$db->idate($filter_date2)."'";
$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}
$sql .= $db->order($sortfield,$sortorder);
$sql .= $db->plimit($limit + 1, $offset);

//print $sql;
dol_syslog("contrat/services.php", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$param='';
	if ($search_contract) $param.='&amp;search_contract='.urlencode($search_contract);
	if ($search_name)      $param.='&amp;search_name='.urlencode($search_name);
	if ($search_service)  $param.='&amp;search_service='.urlencode($search_service);
	if ($mode)            $param.='&amp;mode='.$mode;
	if ($filter)          $param.='&amp;filter='.$filter;
	if (! empty($filter_op1) && $filter_op1 != -1) $param.='&amp;filter_op1='.urlencode($filter_op1);
	if (! empty($filter_op2) && $filter_op2 != -1) $param.='&amp;filter_op2='.urlencode($filter_op2);
	if ($filter_date1 != '') $param.='&amp;op1day='.$op1day.'&amp;op1month='.$op1month.'&amp;op1year='.$op1year;
	if ($filter_date2 != '') $param.='&amp;op2day='.$op2day.'&amp;op2month='.$op2month.'&amp;op2year='.$op2year;

	$title=$langs->trans("ListOfServices");
	if ($mode == "0") $title=$langs->trans("ListOfInactiveServices");	// Must use == "0"
	if ($mode == "4" && $filter != "expired") $title=$langs->trans("ListOfRunningServices");
	if ($mode == "4" && $filter == "expired") $title=$langs->trans("ListOfExpiredServices");
	if ($mode == "5") $title=$langs->trans("ListOfClosedServices");
	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder,'',$num,$totalnboflines,'title_commercial.png');
	
	print '<form method="POST" action="'. $_SERVER["PHP_SELF"] .'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="liste" width="100%">';

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Contract"),$_SERVER["PHP_SELF"], "c.rowid",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Service"),$_SERVER["PHP_SELF"], "p.description",$param,"","",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"], "s.nom",$param,"","",$sortfield,$sortorder);
	// Date debut
	if ($mode == "0") print_liste_field_titre($langs->trans("DateStartPlannedShort"),$_SERVER["PHP_SELF"], "cd.date_ouverture_prevue",$param,'',' align="center"',$sortfield,$sortorder);
	if ($mode == "" || $mode > 0) print_liste_field_titre($langs->trans("DateStartRealShort"),$_SERVER["PHP_SELF"], "cd.date_ouverture",$param,'',' align="center"',$sortfield,$sortorder);
	// Date fin
	if ($mode == "" || $mode < 5) print_liste_field_titre($langs->trans("DateEndPlannedShort"),$_SERVER["PHP_SELF"], "cd.date_fin_validite",$param,'',' align="center"',$sortfield,$sortorder);
	else print_liste_field_titre($langs->trans("DateEndRealShort"),$_SERVER["PHP_SELF"], "cd.date_cloture",$param,'',' align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"], "cd.statut,c.statut",$param,"","align=\"right\"",$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="hidden" name="filter" value="'.$filter.'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
	print '<input type="text" class="flat" size="3" name="search_contract" value="'.dol_escape_htmltag($search_contract).'">';
	print '</td>';
	// Service label
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="12" name="search_service" value="'.dol_escape_htmltag($search_service).'">';
	print '</td>';
	// Third party
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" size="12" name="search_name" value="'.dol_escape_htmltag($search_name).'">';
	print '</td>';
	print '<td class="liste_titre" align="center">';
	$arrayofoperators=array('<'=>'<','>'=>'>');
	print $form->selectarray('filter_op1',$arrayofoperators,$filter_op1,1);
	print ' ';
	$filter_date1=dol_mktime(0,0,0,$op1month,$op1day,$op1year);
	print $form->select_date($filter_date1,'op1',0,0,1,'',1,0,1);
	print '</td>';
	print '<td class="liste_titre" align="center">';
	$arrayofoperators=array('<'=>'<','>'=>'>');
	print $form->selectarray('filter_op2',$arrayofoperators,$filter_op2,1);
	print ' ';
	$filter_date2=dol_mktime(0,0,0,$op2month,$op2day,$op2year);
	print $form->select_date($filter_date2,'op2',0,0,1,'',1,0,1);
	print '</td>';
	print '<td align="right">';
	$arrayofstatus=array(
	    '0'=>$langs->trans("ServiceStatusInitial"),
	    '4'=>$langs->trans("ServiceStatusRunning"),
	    '4&filter=notexpired'=>$langs->trans("ServiceStatusNotLate"),
	    '4&filter=expired'=>$langs->trans("ServiceStatusLate"),
	    '5'=>$langs->trans("ServiceStatusClosed")
	);
	print $form->selectarray('search_status',$arrayofstatus,(strstr($search_status, ',')?-1:$search_status),1);
	print '</td>';
    print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print "</td></tr>\n";

	$contractstatic=new Contrat($db);
	$productstatic=new Product($db);

	$var=True;
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td>';
		$contractstatic->id=$obj->cid;
		$contractstatic->ref=$obj->ref?$obj->ref:$obj->cid;
		print $contractstatic->getNomUrl(1,16);
		print '</td>';

		// Service
		print '<td>';
		if ($obj->pid)
		{
			$productstatic->id=$obj->pid;
			$productstatic->type=$obj->ptype;
			$productstatic->ref=$obj->pref;
			$productstatic->entity=$obj->pentity;
			print $productstatic->getNomUrl(1,'',20);
            print $obj->label?' - '.dol_trunc($obj->label,16):'';
            if (! empty($obj->description) && ! empty($conf->global->PRODUCT_DESC_IN_LIST)) print '<br>'.dol_nl2br($obj->description);
		}
		else
		{
			if ($obj->type == 0) print img_object($obj->description,'product').dol_trunc($obj->description,20);
			if ($obj->type == 1) print img_object($obj->description,'service').dol_trunc($obj->description,20);
		}
		print '</td>';

		// Third party
		print '<td>';
		$companystatic->id=$obj->socid;
		$companystatic->name=$obj->name;
		$companystatic->client=1;
		print $companystatic->getNomUrl(1,'customer',28);
		print '</td>';

		// Start date
		if ($mode == "0") {
			print '<td align="center">';
			print ($obj->date_ouverture_prevue?dol_print_date($db->jdate($obj->date_ouverture_prevue)):'&nbsp;');
			if ($db->jdate($obj->date_ouverture_prevue) && ($db->jdate($obj->date_ouverture_prevue) < ($now - $conf->contrat->services->inactifs->warning_delay)))
			print img_picto($langs->trans("Late"),"warning");
			else print '&nbsp;&nbsp;&nbsp;&nbsp;';
			print '</td>';
		}
		if ($mode == "" || $mode > 0) print '<td align="center">'.($obj->date_ouverture?dol_print_date($db->jdate($obj->date_ouverture)):'&nbsp;').'</td>';
		// Date fin
		if ($mode == "" || $mode < 5) print '<td align="center">'.($obj->date_fin_validite?dol_print_date($db->jdate($obj->date_fin_validite)):'&nbsp;');
		else print '<td align="center">'.dol_print_date($db->jdate($obj->date_cloture));
		// Icone warning
		if ($obj->date_fin_validite && $db->jdate($obj->date_fin_validite) < ($now - $conf->contrat->services->expires->warning_delay) && $obj->statut < 5) print img_warning($langs->trans("Late"));
		else print '&nbsp;&nbsp;&nbsp;&nbsp;';
		print '</td>';
		print '<td align="right" class="nowrap">';
		if ($obj->cstatut == 0)	// If contract is draft, we say line is also draft
		{
			print $contractstatic->LibStatut(0,5,($obj->date_fin_validite && $db->jdate($obj->date_fin_validite) < $now));
		}
		else
		{
			print $staticcontratligne->LibStatut($obj->statut,5,($obj->date_fin_validite && $db->jdate($obj->date_fin_validite) < $now)?1:0);
		}
		print '</td>';
		print '<td></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($resql);

	print '</table></form>';

}
else
{
	dol_print_error($db);
}


llxFooter();

$db->close();
