<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';

$langs->load('projects');
$langs->load('companies');
$langs->load('commercial');

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


$sortfield = GETPOST("sortfield","alpha");
$sortorder = GETPOST("sortorder");
$page = GETPOST("page");
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;

if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_all=GETPOST("search_all");
$search_ref=GETPOST("search_ref");
$search_label=GETPOST("search_label");
$search_societe=GETPOST("search_societe");
$search_year=GETPOST("search_year");
$search_all=GETPOST("search_all");
$search_status=GETPOST("search_status",'int');
$search_opp_status=GETPOST("search_opp_status",'alpha');
$search_public=GETPOST("search_public",'int');
$search_user=GETPOST('search_user','int');
$search_sale=GETPOST('search_sale','int');
$optioncss = GETPOST('optioncss','alpha');

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
if ($mine) { $search_user = $user->id; $mine=0; }

$sday	= GETPOST('sday','int');
$smonth	= GETPOST('smonth','int');
$syear	= GETPOST('syear','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');

if ($search_status == '') $search_status=-1;	// -1 or 1

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='p.ref';
if (! $sortorder) $sortorder='DESC';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='projectlist';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('project');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'p.ref'=>"Ref",
	'p.title'=>"Label",
	's.nom'=>"ThirdPartyName",
    "p.note_public"=>"NotePublic"
);
if (empty($user->socid)) $fieldstosearchall["p.note_private"]="NotePrivate";

$arrayfields=array(
    'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'p.title'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
    'commercial'=>array('label'=>$langs->trans("SalesRepresentative"), 'checked'=>1),
	'p.dateo'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1, 'position'=>100),
    'p.datee'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1, 'position'=>101),
    'p.public'=>array('label'=>$langs->trans("Visibility"), 'checked'=>1, 'position'=>102),
    'p.opp_amount'=>array('label'=>$langs->trans("OpportunityAmountShort"), 'checked'=>1, 'enabled'=>$conf->global->PROJECT_USE_OPPORTUNITIES, 'position'=>103),
	'p.fk_opp_status'=>array('label'=>$langs->trans("OpportunityStatusShort"), 'checked'=>1, 'enabled'=>$conf->global->PROJECT_USE_OPPORTUNITIES, 'position'=>104),
	'p.datec'=>array('label'=>$langs->trans("DateCreationShort"), 'checked'=>0, 'position'=>500),
    'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'p.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val) 
   {
       $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
   }
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_all='';
	$search_ref="";
	$search_label="";
	$search_societe="";
	$search_year="";
	$search_status=-1;
	$search_opp_status=-1;
	$search_public="";
	$search_sale="";
	$search_user='';
	$sday="";
	$smonth="";
	$syear="";
	$day="";
	$month="";
	$year="";
	$search_array_options=array();
}



/*
 * View
 */

$projectstatic = new Project($db);
$socstatic = new Societe($db);
$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);

$title=$langs->trans("Projects");
if ($search_user == $user->id) $title=$langs->trans("MyProjects");

llxHeader("",$title,"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

// Get list of project id allowed to user (in a string list separated by coma)
if (! $user->rights->projet->all->lire) $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1,$socid);

// Get id of types of contacts for projects (This list never contains a lot of elements)
$listofprojectcontacttype=array();
$sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
$sql.= " WHERE ctc.element = '" . $projectstatic->element . "'";
$sql.= " AND ctc.source = 'internal'";
$resql = $db->query($sql);
if ($resql)
{
    while($obj = $db->fetch_object($resql))
    {
        $listofprojectcontacttype[$obj->rowid]=$obj->code;
    }
}
else dol_print_error($db);
if (count($listofprojectcontacttype) == 0) $listofprojectcontacttype[0]='0';    // To avoid sql syntax error if not found

$distinct='DISTINCT';   // We add distinct until we are added a protection to be sure a contact of a project and task is only once.
$sql = "SELECT ".$distinct." p.rowid as projectid, p.ref, p.title, p.fk_statut, p.fk_opp_status, p.public, p.fk_user_creat";
$sql.= ", p.datec as date_creation, p.dateo as date_start, p.datee as date_end, p.opp_amount, p.tms as date_update";
$sql.= ", s.nom as name, s.rowid as socid";
$sql.= ", cls.code as opp_status_code";
// Add fields for extrafields
foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls on p.fk_opp_status = cls.rowid";

// We'll need this table joined to the select in order to filter by sale
if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
if ($search_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as ecp";
}

$sql.= " WHERE p.entity IN (".getEntity('project').')';
if (! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";     // public and assigned to, or restricted to company for external users
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($search_ref) $sql .= natural_search('p.ref', $search_ref);
if ($search_label) $sql .= natural_search('p.title', $search_label);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($smonth > 0)
{
    if ($syear > 0 && empty($sday))
    	$sql.= " AND p.dateo BETWEEN '".$db->idate(dol_get_first_day($syear,$smonth,false))."' AND '".$db->idate(dol_get_last_day($syear,$smonth,false))."'";
    else if ($syear > 0 && ! empty($sday))
    	$sql.= " AND p.dateo BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
    else
    	$sql.= " AND date_format(p.dateo, '%m') = '".$smonth."'";
}
else if ($syear > 0)
{
    $sql.= " AND p.dateo BETWEEN '".$db->idate(dol_get_first_day($syear,1,false))."' AND '".$db->idate(dol_get_last_day($syear,12,false))."'";
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
if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_status >= 0) $sql .= " AND p.fk_statut = ".$db->escape($search_status);
if ($search_opp_status) 
{
    if (is_numeric($search_opp_status) && $search_opp_status > 0) $sql .= " AND p.fk_opp_status = ".$db->escape($search_opp_status);
    if ($search_opp_status == 'all') $sql .= " AND p.fk_opp_status IS NOT NULL";
    if ($search_opp_status == 'openedopp') $sql .= " AND p.fk_opp_status IS NOT NULL AND p.fk_opp_status NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_lead_status WHERE code IN ('WIN','LOST'))";
    if ($search_opp_status == 'none') $sql .= " AND p.fk_opp_status IS NULL";
}
if ($search_public!='') $sql .= " AND p.public = ".$db->escape($search_public);
if ($search_sale > 0) $sql.= " AND sc.fk_user = " .$search_sale;
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";
if ($search_user > 0) $sql.= " AND ecp.fk_c_type_contact IN (".join(',',array_keys($listofprojectcontacttype)).") AND ecp.element_id = p.rowid AND ecp.fk_socpeople = ".$search_user; 
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql.= $db->plimit($limit + 1,$offset);


dol_syslog("list allowed project", LOG_DEBUG);
//print $sql;
$resql = $db->query($sql);
if ($resql)
{
	$var=true;
	$num = $db->num_rows($resql);

	$param='';
	if ($sday)              		$param.='&sday='.$day;
	if ($smonth)              		$param.='&smonth='.$smonth;
	if ($syear)               		$param.='&syear=' .$syear;
	if ($day)               		$param.='&day='.$day;
	if ($month)              		$param.='&month='.$month;
	if ($year)               		$param.='&year=' .$year;
	if ($socid)				        $param.='&socid='.$socid;
	if ($search_all != '') 			$param.='&search_all='.$search_all;
	if ($search_ref != '') 			$param.='&search_ref='.$search_ref;
	if ($search_label != '') 		$param.='&search_label='.$search_label;
	if ($search_societe != '') 		$param.='&search_societe='.$search_societe;
	if ($search_status >= 0) 		$param.='&search_status='.$search_status;
	if ((is_numeric($search_opp_status) && $search_opp_status >= 0) || in_array($search_opp_status, array('all','none'))) 	$param.='&search_opp_status='.urlencode($search_opp_status);
	if ($search_public != '') 		$param.='&search_public='.$search_public;
	if ($search_user > 0)    		$param.='&search_user='.$search_user;
	if ($search_sale > 0)    		$param.='&search_sale='.$search_sale;
	if ($optioncss != '') $param.='&optioncss='.$optioncss;
	// Add $param from extra fields
	foreach ($search_array_options as $key => $val)
	{
	    $crit=$val;
	    $tmpkey=preg_replace('/search_options_/','',$key);
	    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
	}
	
	$text=$langs->trans("Projects");
	if ($search_user == $user->id) $text=$langs->trans('MyProjects');
	print_barre_liste($text, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num,'','title_project');

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

    // Show description of content
	if ($search_user == $user->id) print $langs->trans("MyProjectsDesc").'<br><br>';
	else
	{
		if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
		else print $langs->trans("ProjectsPublicDesc").'<br><br>';
	}

	if ($search_all)
	{
        foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
        print $langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall);
	}

	// If the user can view thirdparties other than his'
	if ($user->rights->societe->client->voir || $socid)
	{
		$langs->load("commercial");
		$moreforfilter.='<div class="divsearchfield">';
		$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
		$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth300');
		$moreforfilter.='</div>';
	}

	// If the user can view user other than himself
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('ProjectsWithThisUserAsContact'). ': ';
	$includeonly='';
	if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
	$moreforfilter.=$form->select_dolusers($search_user, 'search_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth300');
	$moreforfilter.='</div>';

	if (! empty($moreforfilter))
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
    	$parameters=array();
    	$reshook=$hookmanager->executeHooks('printFieldPreListTitle',$parameters);    // Note that $action and $object may have been modified by hook
    	print $hookmanager->resPrint;
    	print '</div>';
    }

	$varpage=empty($contextpage)?$_SERVER["PHP_SELF"]:$contextpage;
	$selectedfields=$form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);	// This also change content of $arrayfields
    
    print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'">';
    		
	print '<tr class="liste_titre">';
	if (! empty($arrayfields['p.ref']['checked']))           print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['p.title']['checked']))         print_liste_field_titre($arrayfields['p.title']['label'],$_SERVER["PHP_SELF"],"p.title","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))           print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['commercial']['checked']))      print_liste_field_titre($arrayfields['commercial']['label'],$_SERVER["PHP_SELF"],"","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['p.dateo']['checked']))         print_liste_field_titre($arrayfields['p.dateo']['label'],$_SERVER["PHP_SELF"],"p.dateo","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.datee']['checked']))         print_liste_field_titre($arrayfields['p.datee']['label'],$_SERVER["PHP_SELF"],"p.datee","",$param,'align="center"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.public']['checked']))        print_liste_field_titre($arrayfields['p.public']['label'],$_SERVER["PHP_SELF"],"p.public","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['p.opp_amount']['checked']))    print_liste_field_titre($arrayfields['p.opp_amount']['label'],$_SERVER["PHP_SELF"],'p.opp_amount',"",$param,'align="right"',$sortfield,$sortorder);
    if (! empty($arrayfields['p.fk_opp_status']['checked'])) print_liste_field_titre($arrayfields['p.fk_opp_status']['label'],$_SERVER["PHP_SELF"],'p.fk_opp_status',"",$param,'align="center"',$sortfield,$sortorder);
	// Extra fields
	if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
	{
	   foreach($extrafields->attribute_label as $key => $val) 
	   {
           if (! empty($arrayfields["ef.".$key]['checked'])) 
           {
				$align=$extrafields->getAlignFlag($key);
				print_liste_field_titre($extralabels[$key],$_SERVER["PHP_SELF"],"ef.".$key,"",$param,($align?'align="'.$align.'"':''),$sortfield,$sortorder);
           }
	   }
	}
	// Hook fields
	$parameters=array('arrayfields'=>$arrayfields);
	$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (! empty($arrayfields['p.datec']['checked']))  print_liste_field_titre($arrayfields['p.datec']['label'],$_SERVER["PHP_SELF"],"p.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.tms']['checked']))    print_liste_field_titre($arrayfields['p.tms']['label'],$_SERVER["PHP_SELF"],"p.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['p.fk_statut']['checked'])) print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	if (! empty($arrayfields['p.ref']['checked']))
	{
    	print '<td class="liste_titre">';
    	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
    	print '</td>';
	}
	if (! empty($arrayfields['p.title']['checked']))
	{
    	print '<td class="liste_titre">';
    	print '<input type="text" class="flat" name="search_label" size="8" value="'.$search_label.'">';
    	print '</td>';
	}
	if (! empty($arrayfields['s.nom']['checked']))
	{
    	print '<td class="liste_titre">';
    	print '<input type="text" class="flat" name="search_societe" size="8" value="'.$search_societe.'">';
    	print '</td>';
	}
	// Sale representative
	if (! empty($arrayfields['commercial']['checked']))
	{
    	print '<td class="liste_titre">&nbsp;</td>';
	}
	// Start date
	if (! empty($arrayfields['p.dateo']['checked']))
	{
		print '<td class="liste_titre center">';
		if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="sday" value="'.$sday.'">';
		print '<input class="flat" type="text" size="1" maxlength="2" name="smonth" value="'.$smonth.'">';
		$formother->select_year($syear?$syear:-1,'syear',1, 20, 5);
		print '</td>';
	}
	// End date
	if (! empty($arrayfields['p.datee']['checked']))
	{
    	print '<td class="liste_titre center">';
    	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
    	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
    	$formother->select_year($year?$year:-1,'year',1, 20, 5);
    	print '</td>';
	}
	if (! empty($arrayfields['p.public']['checked']))
	{
    	print '<td class="liste_titre">';
    	$array=array(''=>'',0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
        print $form->selectarray('search_public',$array,$search_public);
        print '</td>';
	}
	if (! empty($arrayfields['p.opp_amount']['checked']))
	{
		print '<td class="liste_titre nowrap">';
	    print '</td>';
	}
	if (! empty($arrayfields['p.fk_opp_status']['checked']))
	{
    	print '<td class="liste_titre nowrap center">';
		print $formproject->selectOpportunityStatus('search_opp_status',$search_opp_status,1,1,1);
	    print '</td>';
    }
    // Extra fields
    if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
    {
        foreach($extrafields->attribute_label as $key => $val)
        {
            if (! empty($arrayfields["ef.".$key]['checked'])) print '<td class="liste_titre"></td>';
        }
    }
    // Fields from hook
    $parameters=array('arrayfields'=>$arrayfields);
    $reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (! empty($arrayfields['p.datec']['checked']))
    {
        // Date creation
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (! empty($arrayfields['p.tms']['checked']))
    {
        // Date modification
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (! empty($arrayfields['p.fk_statut']['checked']))
    {
    	print '<td class="liste_titre nowrap" align="right">';
    	print $form->selectarray('search_status', array('-1'=>'', '0'=>$langs->trans('Draft'),'1'=>$langs->trans('Opened'),'2'=>$langs->trans('Closed')),$search_status);
        print '</td>';
    }
    // Action column
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print '</td>';

    print '</tr>'."\n";

    $i = 0;
    while ($i < min($num,$limit))
    {
    	$obj = $db->fetch_object($resql);

    	$projectstatic->id = $obj->projectid;
    	$projectstatic->user_author_id = $obj->fk_user_creat;
    	$projectstatic->public = $obj->public;
    	$projectstatic->ref = $obj->ref;
    	 
    	$userAccess = $projectstatic->restrictedProjectArea($user);    // why this ?
    	if ($userAccess >= 0)
    	{
    		$var=!$var;
    		print "<tr ".$bc[$var].">";

    		// Project url
        	if (! empty($arrayfields['p.ref']['checked']))
        	{
        		print '<td class="nowrap">';
        		print $projectstatic->getNomUrl(1);
        		print '</td>';
        	}
    		// Title
        	if (! empty($arrayfields['p.title']['checked']))
        	{
            	print '<td>';
        		print dol_trunc($obj->title,80);
        		print '</td>';
        	}
    		// Company
        	if (! empty($arrayfields['s.nom']['checked']))
        	{
            	print '<td>';
        		if ($obj->socid)
        		{
        			$socstatic->id=$obj->socid;
        			$socstatic->name=$obj->name;
        			print $socstatic->getNomUrl(1);
        		}
        		else
        		{
        			print '&nbsp;';
        		}
        		print '</td>';
        	}
    		// Sales Rapresentatives
        	if (! empty($arrayfields['commercial']['checked']))
        	{
            	print '<td>';
        		if ($obj->socid)
        		{
        			$socstatic->id=$obj->socid;
        			$socstatic->name=$obj->name;
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
        					$userstatic->email=$val['email'];
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
        	}
    		// Date start
        	if (! empty($arrayfields['p.dateo']['checked']))
        	{
				print '<td class="center">';
	    		print dol_print_date($db->jdate($obj->date_start),'day');
	    		print '</td>';
			}
    		// Date end
        	if (! empty($arrayfields['p.datee']['checked']))
        	{
    			print '<td class="center">';
        		print dol_print_date($db->jdate($obj->date_end),'day');
        		print '</td>';
        	}
    		// Visibility
        	if (! empty($arrayfields['p.public']['checked']))
        	{
        		print '<td align="left">';
        		if ($obj->public) print $langs->trans('SharedProject');
        		else print $langs->trans('PrivateProject');
        		print '</td>';
        	}
        	if (! empty($arrayfields['p.opp_amount']['checked']))
        	{
    			print '<td align="right">';
    			if ($obj->opp_status_code) print price($obj->opp_amount, 1, '', 1, - 1, - 1, $conf->currency);
    			print '</td>';
        	}
        	if (! empty($arrayfields['p.fk_opp_status']['checked']))
        	{
                print '<td align="middle">';
    			if ($obj->opp_status_code) print $langs->trans("OppStatusShort".$obj->opp_status_code);
    			print '</td>';
    		}
    		// Extra fields
    		if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
    		{
    		    foreach($extrafields->attribute_label as $key => $val)
    		    {
    		        if (! empty($arrayfields["ef.".$key]['checked']))
    		        {
    		            print '<td';
    		            $align=$extrafields->getAlignFlag($key);
    		            if ($align) print ' align="'.$align.'"';
    		            print '>';
    		            $tmpkey='options_'.$key;
    		            print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
    		            print '</td>';
    		        }
    		    }
    		}
    		// Fields from hook
    		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
    		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
    		print $hookmanager->resPrint;
    		// Date creation
    		if (! empty($arrayfields['p.datec']['checked']))
    		{
    		    print '<td align="center">';
    		    print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
    		    print '</td>';
    		}
    		// Date modification
    		if (! empty($arrayfields['p.tms']['checked']))
    		{
    		    print '<td align="center">';
    		    print dol_print_date($db->jdate($obj->date_update), 'dayhour');
    		    print '</td>';
    		}
    		// Status
    		if (! empty($arrayfields['p.fk_statut']['checked']))
    		{
        		$projectstatic->statut = $obj->fk_statut;
        		print '<td align="right">'.$projectstatic->getLibStatut(5).'</td>';
    		}
    		// Action column
    		print '<td></td>';

    		print "</tr>\n";

    	}

    	$i++;

    }
    $db->free($resql);

	$parameters=array('sql' => $sql);
	$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;

	print "</table>\n";
	print "</form>\n";
}
else
{
	dol_print_error($db);
}



llxFooter();

$db->close();
