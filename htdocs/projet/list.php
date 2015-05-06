<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015 	   Claudio Aschieri     <c.aschieri@19.coop>
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
 *	\file       htdocs/projet/list.php
 *	\ingroup    projet
 *	\brief      Page to list projects
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('projects');

$title = $langs->trans("Projects");

// Security check
$socid = (is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );
if ($user->societe_id > 0) $socid=$user->societe_id;
if ($socid > 0)
{
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title .= ' (<a href="list.php">'.$soc->name.'</a>)';
}
if (!$user->rights->projet->lire) accessforbidden();


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page = isset($_GET["page"])? $_GET["page"]:$_POST["page"];
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;

$search_ref=GETPOST("search_ref");
$search_label=GETPOST("search_label");
$search_societe=GETPOST("search_societe");
$search_year=GETPOST("search_year");
$search_all=GETPOST("search_all");
$search_status=GETPOST("search_status",'int');
$search_public=GETPOST("search_public",'int');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');

$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');
$sday	= GETPOST('sday','int');
$smonth	= GETPOST('smonth','int');
$syear	= GETPOST('syear','int');

if ($search_status == '') $search_status=-1;	// -1 or 1

// Purge criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_societe="";
	$search_year="";
	$search_all=0;
	$search_status=-1;
	$search_public="";
	$search_sale="";
	$search_user='';
	$day="";
	$month="";
	$year="";
	$sday="";
	$smonth="";
	$syear="";
}

/*
 * View
 */

$projectstatic = new Project($db);
$socstatic = new Societe($db);
$form = new Form($db);
$formother = new FormOther($db);

llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");


$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:($user->rights->projet->all->lire?2:0)),1,$socid);

$sql = "SELECT p.rowid as projectid, p.ref, p.title, p.fk_statut, p.public, p.fk_user_creat";
$sql.= ", p.datec as date_create, p.dateo as date_start, p.datee as date_end";
$sql.= ", s.nom as name, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";

// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as c";
	$sql.=", ".MAIN_DB_PREFIX."c_type_contact as tc";
}

$sql.= " WHERE p.entity = ".$conf->entity;
if ($mine || ! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($search_ref)
{
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_label)
{
	$sql .= natural_search('p.title', $search_label);
}
if ($search_societe)
{
	$sql .= natural_search('s.nom', $search_societe);
}
if ($smonth > 0)
{
    if ($syear > 0 && empty($sday))
    	$sql.= " AND p.datee BETWEEN '".$db->idate(dol_get_first_day($syear,$smonth,false))."' AND '".$db->idate(dol_get_last_day($syear,$smonth,false))."'";
    else if ($syear > 0 && ! empty($sday))
    	$sql.= " AND p.datee BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
    else
    	$sql.= " AND date_format(p.datee, '%m') = '".$smonth."'";
}
else if ($syear > 0)
{
    $sql.= " AND p.datee BETWEEN '".$db->idate(dol_get_first_day($syear,1,false))."' AND '".$db->idate(dol_get_last_day($syear,12,false))."'";
}
if ($month > 0)
{
    if ($year > 0 && empty($day))
    	$sql.= " AND p.datee BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    	$sql.= " AND p.datee BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    	$sql.= " AND date_format(p.datee, '%m') = '".$month."'";
}
else if ($year > 0)
{
    $sql.= " AND p.datee BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_all)
{
	$sql .= natural_search(array('p.ref','p.title','s.nom'), $search_all);
}
if ($search_status >= 0) $sql .= " AND p.fk_statut = ".$db->escape($search_status);
if ($search_public!='') $sql .= " AND p.public = ".$db->escape($search_public);
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
if ($search_user > 0)
{
	$sql.= " AND c.fk_c_type_contact = tc.rowid AND tc.element='project' AND tc.source='internal' AND c.element_id = p.rowid AND c.fk_socpeople = ".$search_user;
}


$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($conf->liste_limit+1, $offset);
//print $sql;

dol_syslog("list allowed project", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$var=true;
	$num = $db->num_rows($resql);
	$i = 0;

	$param='';
	if ($month)              		$param.='&month='.$month;
	if ($year)               		$param.='&year=' .$year;
	if ($search_ref != '') 			$param.='&search_ref='.$search_ref;
	if ($search_label != '') 		$param.='&search_label='.$search_label;
	if ($search_societe != '') 		$param.='&search_societe='.$search_societe;
	if ($search_status >= 0) 		$param.='&search_status='.$search_status;
	if ($search_public != '') 		$param.='&search_public='.$search_public;
	if ($search_user > 0)    		$param.='&search_user='.$search_user;
	if ($search_sale > 0)    		$param.='&search_sale='.$search_sale;


	$text=$langs->trans("Projects");
	if ($mine) $text=$langs->trans('MyProjects');
	print_barre_liste($text, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num,'','title_project');

	print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

	print '<table class="noborder" width="100%">';


	// Show description of content
	if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
	else
	{
		if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
		else print $langs->trans("ProjectsPublicDesc").'<br><br>';
	}

	// If the user can view prospects other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$langs->load("commercial");
		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale,'search_sale',$user);
		$moreforfilter.=' &nbsp; &nbsp; &nbsp; ';
	}
	// If the user can view prospects other than his'

	if (($user->rights->societe->client->voir || $socid) && !$mine)
	{
		$moreforfilter.=$langs->trans('LinkedToSpecificUsers'). ': ';
		$moreforfilter.=$form->select_dolusers($search_user,'search_user',1);
	}
	if (! empty($moreforfilter))
	{
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" colspan="10">';
		print $moreforfilter;
		print '</td></tr>';
	}


	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.ref","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"p.title","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("SalesRepresentative"),$_SERVER["PHP_SELF"],"","",$param,"",$sortfield,$sortorder);
	if (! empty($conf->global->PROJECT_LIST_SHOW_STARTDATE)) print_liste_field_titre($langs->trans("DateStart"),$_SERVER["PHP_SELF"],"p.dateo","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateEnd"),$_SERVER["PHP_SELF"],"p.datee","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Visibility"),$_SERVER["PHP_SELF"],"p.public","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],'p.fk_statut',"",$param,'align="right"',$sortfield,$sortorder);
	print '<td class="liste_titre">&nbsp;</td>';
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_label" value="'.$search_label.'">';
	print '</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_societe" value="'.$search_societe.'">';
	print '</td>';
	// Sale representative
	print '<td class="liste_titre">&nbsp;</td>';
	// Start date
	if (! empty($conf->global->PROJECT_LIST_SHOW_STARTDATE))
	{
		print '<td class="liste_titre center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="sday" value="'.$sday.'">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="smonth" value="'.$smonth.'">';
		$formother->select_year($syear?$syear:-1,'syear',1, 20, 5);
		print '</td>';
	}
	// End date
	print '<td class="liste_titre center">';
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	$formother->select_year($year?$year:-1,'year',1, 20, 5);
	print '</td>';

	print '<td class="liste_titre">';
	$array=array(''=>'',0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
    print $form->selectarray('search_public',$array,$search_public);
    print '</td>';

	print '<td class="liste_titre nowrap" align="right">';
	print $form->selectarray('search_status', array('-1'=>'', '0'=>$langs->trans('Draft'),'1'=>$langs->trans('Opened'),'2'=>$langs->trans('Closed')),$search_status);
    print '</td><td width="56">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("RemoveFilter"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';

    while ($i < $num)
    {
    	$objp = $db->fetch_object($resql);

    	$projectstatic->id = $objp->projectid;
    	$projectstatic->user_author_id = $objp->fk_user_creat;
    	$projectstatic->public = $objp->public;

    	$userAccess = $projectstatic->restrictedProjectArea($user);

    	if ($userAccess >= 0)
    	{
    		$var=!$var;
    		print "<tr ".$bc[$var].">";

    		// Project url
    		print "<td>";
    		$projectstatic->ref = $objp->ref;
    		print $projectstatic->getNomUrl(1);
    		print "</td>";

    		// Title
    		print '<td>';
    		print dol_trunc($objp->title,80);
    		print '</td>';

    		// Company
    		print '<td>';
    		if ($objp->socid)
    		{
    			$socstatic->id=$objp->socid;
    			$socstatic->name=$objp->name;
    			print $socstatic->getNomUrl(1);
    		}
    		else
    		{
    			print '&nbsp;';
    		}
    		print '</td>';


    		// Sales Rapresentatives
    		print '<td>';
    		if($objp->socid)
    		{
    			$listsalesrepresentatives=$socstatic->getSalesRepresentatives($user);
    			$nbofsalesrepresentative=count($listsalesrepresentatives);
    			if ($nbofsalesrepresentative > 3)   // We print only number
    			{
    				print '<a href="'.DOL_URL_ROOT.'/societe/commerciaux.php?socid='.$socstatic->id.'">';
    				print $nbofsalesrepresentative;
    				print '</a>';
    			}
    			else if ($nbofsalesrepresentative > 0)
    			{
    				$userstatic=new User($db);
    				$j=0;
    				foreach($listsalesrepresentatives as $val)
    				{
    					$userstatic->id=$val['id'];
    					$userstatic->lastname=$val['lastname'];
    					$userstatic->firstname=$val['firstname'];
    					print $userstatic->getNomUrl(1);
    					$j++;
    					if ($j < $nbofsalesrepresentative) print ', ';
    				}
    			}
    			//else print $langs->trans("NoSalesRepresentativeAffected");
    		}
    		else
    		{
    			print '&nbsp';
    		}
    		print '</td>';

    		// Date start
    		print '<td class="center">';
    		print dol_print_date($db->jdate($objp->date_start),'day');
    		print '</td>';

    		// Date end
    		print '<td class="center">';
    		print dol_print_date($db->jdate($objp->date_end),'day');
    		print '</td>';

    		// Visibility
    		print '<td align="left">';
    		if ($objp->public) print $langs->trans('SharedProject');
    		else print $langs->trans('PrivateProject');
    		print '</td>';

    		// Status
    		$projectstatic->statut = $objp->fk_statut;
    		print '<td align="right" colspan="2">'.$projectstatic->getLibStatut(5).'</td>';

    		print "</tr>\n";

    	}

    	$i++;
    }

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

print "</table>";


llxFooter();

$db->close();
