<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/projet/tasks/list.php
 *	\ingroup    project
 *	\brief      List all task of a project
 */

require ("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load('projects');
$langs->load('users');
$langs->load('companies');

$id=GETPOST('id','int');

$search_all=GETPOST('search_all');
$search_project=GETPOST('search_project');
if (! isset($_GET['search_projectstatus']) && ! isset($_POST['search_projectstatus'])) $search_projectstatus=1;
else $search_projectstatus=GETPOST('search_projectstatus');
$search_project_ref=GETPOST('search_project_ref');
$search_project_title=GETPOST('search_project_title');
$search_task_ref=GETPOST('search_task_ref');
$search_task_label=GETPOST('search_task_label');
$search_task_description=GETPOST('search_task_description');
$search_project_user=GETPOST('search_project_user');
$search_task_user=GETPOST('search_task_user');

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
if ($mine) { $search_task_user = $user->id; $mine = 0; }

$sday	= GETPOST('sday','int');
$smonth	= GETPOST('smonth','int');
$syear	= GETPOST('syear','int');
$day	= GETPOST('day','int');
$month	= GETPOST('month','int');
$year	= GETPOST('year','int');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$contextpage='tasklist';

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('project');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();

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

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	't.ref'=>"Ref",
    't.label'=>"Label",
    't.description'=>"Description",
    't.note_public'=>"NotePublic",
);
if (empty($user->socid)) $fieldstosearchall['t.note_private']="NotePrivate";

$arrayfields=array(
    'p.ref'=>array('label'=>$langs->trans("ProjectRef"), 'checked'=>1),
    'p.title'=>array('label'=>$langs->trans("ProjectLabel"), 'checked'=>0),
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>0),
    'p.fk_statut'=>array('label'=>$langs->trans("ProjectStatus"), 'checked'=>1),
    't.ref'=>array('label'=>$langs->trans("RefTask"), 'checked'=>1, 'position'=>80),
    't.label'=>array('label'=>$langs->trans("LabelTask"), 'checked'=>1, 'position'=>80),
    't.dateo'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1, 'position'=>100),
    't.datee'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1, 'position'=>101),
    't.planned_workload'=>array('label'=>$langs->trans("PlannedWorkload"), 'checked'=>1, 'position'=>102),
    't.duration_effective'=>array('label'=>$langs->trans("TimeSpent"), 'checked'=>1, 'position'=>103),
    't.progress_calculated'=>array('label'=>$langs->trans("ProgressCalculated"), 'checked'=>1, 'position'=>104),
    't.progress'=>array('label'=>$langs->trans("ProgressDeclared"), 'checked'=>1, 'position'=>105),
    't.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    't.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    //'t.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
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

// Purge criteria
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_all="";
    $search_project="";
    $search_projectstatus=-1;
    $search_project_ref="";
    $search_project_title="";
    $search_task_ref="";
    $search_task_label="";
    $search_task_description="";
    $search_task_user=-1;
    $search_project_user=-1;
    $sday='';
    $smonth='';
    $syear='';
    $day='';
    $month='';
    $year='';
}
if (empty($search_projectstatus) && $search_projectstatus == '') $search_projectstatus=1;




/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);
$socstatic=new Societe($db);
$projectstatic = new Project($db);
$taskstatic = new Task($db);
$puser=new User($db);
$tuser=new User($db);
if ($search_project_user > 0) $puser->fetch($search_project_user);
if ($search_task_user > 0) $tuser->fetch($search_task_user);

$title=$langs->trans("Activities");
if ($search_task_user == $user->id) $title=$langs->trans("MyActivities");

llxHeader("",$title,"Projet");

if ($id)
{
	$projectstatic->fetch($id);
	$projectstatic->societe->fetch($projectstatic->societe->id);
}

// Get list of project id allowed to user (in a string list separated by coma)
if (! $user->rights->projet->all->lire) $projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,0,1,$socid);
//var_dump($projectsListId);

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
// Get id of types of contacts for tasks (This list never contains a lot of elements)
$listoftaskcontacttype=array();
$sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
$sql.= " WHERE ctc.element = '" . $taskstatic->element . "'";
$sql.= " AND ctc.source = 'internal'";
$resql = $db->query($sql);
if ($resql)
{
    while($obj = $db->fetch_object($resql))
    {
        $listoftaskcontacttype[$obj->rowid]=$obj->code;
    }
}
else dol_print_error($db);
if (count($listoftaskcontacttype) == 0) $listoftaskcontacttype[0]='0';         // To avoid sql syntax error if not found

$distinct='DISTINCT';   // We add distinct until we are added a protection to be sure a contact of a project and task is only once.
$sql = "SELECT ".$distinct." p.rowid as projectid, p.ref as projectref, p.title as projecttitle, p.fk_statut as projectstatus, p.fk_opp_status, p.public, p.fk_user_creat as projectusercreate";
$sql.= ", s.nom as name, s.rowid as socid";
$sql.= ", t.datec as date_creation, t.dateo as date_start, t.datee as date_end, t.tms as date_update";
$sql.= ", t.rowid as id, t.ref, t.label, t.planned_workload, t.duration_effective, t.progress, t.fk_statut";
// Add fields for extrafields
foreach ($extrafields->attribute_list as $key => $val) $sql.=",ef.".$key.' as options_'.$key;
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid,";
$sql.= " ".MAIN_DB_PREFIX."projet_task as t";
if ($search_project_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ecp";
}
if ($search_task_user > 0)
{
    $sql.=", ".MAIN_DB_PREFIX."element_contact as ect";
}
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity IN (".getEntity('project').')';
if (! $user->rights->projet->all->lire) $sql.=" p.rowid IN (".join(',',$projectsListId).")";    // public and assigned to projects, or restricted to company for external users
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($search_project_ref)   $sql .= natural_search('p.ref', $search_project_ref);
if ($search_project_title) $sql .= natural_search('p.title', $search_project_title);
if ($search_task_ref)      $sql .= natural_search('t.ref', $search_task_ref);
if ($search_task_label)    $sql .= natural_search('t.label', $search_task_label);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($smonth > 0)
{
    if ($syear > 0 && empty($sday))
        $sql.= " AND t.dateo BETWEEN '".$db->idate(dol_get_first_day($syear,$smonth,false))."' AND '".$db->idate(dol_get_last_day($syear,$smonth,false))."'";
        else if ($syear > 0 && ! empty($sday))
            $sql.= " AND t.dateo BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
            else
                $sql.= " AND date_format(t.dateo, '%m') = '".$smonth."'";
}
else if ($syear > 0)
{
    $sql.= " AND t.dateo BETWEEN '".$db->idate(dol_get_first_day($syear,1,false))."' AND '".$db->idate(dol_get_last_day($syear,12,false))."'";
}
if ($month > 0)
{
    if ($year > 0 && empty($day))
        $sql.= " AND t.datee BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
        else if ($year > 0 && ! empty($day))
            $sql.= " AND t.datee BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
            else
                $sql.= " AND date_format(t.datee, '%m') = '".$month."'";
}
else if ($year > 0)
{
    $sql.= " AND t.datee BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_projectstatus >= 0) $sql .= " AND p.fk_statut = ".$db->escape($search_projectstatus);
if ($search_public!='') $sql .= " AND p.public = ".$db->escape($search_public);
if ($search_project_user > 0) $sql.= " AND ecp.fk_c_type_contact IN (".join(',',array_keys($listofprojectcontacttype)).") AND ecp.element_id = p.rowid AND ecp.fk_socpeople = ".$search_project_user;
if ($search_task_user > 0) $sql.= " AND ect.fk_c_type_contact IN (".join(',',array_keys($listoftaskcontacttype)).") AND ect.element_id = t.rowid AND ect.fk_socpeople = ".$search_task_user;
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
    if ($search_project_ref != '') 			$param.='&search_project_ref='.$search_project_ref;
    if ($search_project_title != '') 		$param.='&search_project_title='.$search_project_title;
    if ($search_ref != '') 			$param.='&search_ref='.$search_ref;
    if ($search_label != '') 		$param.='&search_label='.$search_label;
    if ($search_societe != '') 		$param.='&search_societe='.$search_societe;
    if ($search_projectstatus != '') $param.='&search_projectstatus='.$search_projectstatus;
    if ((is_numeric($search_opp_status) && $search_opp_status >= 0) || in_array($search_opp_status, array('all','none'))) 	$param.='&search_opp_status='.urlencode($search_opp_status);
    if ($search_public != '') 		$param.='&search_public='.$search_public;
    if ($search_project_user > 0)   $param.='&search_project_user='.$search_project_user;
    if ($search_task_user > 0)    	$param.='&search_task_user='.$search_task_user;
    if ($optioncss != '') $param.='&optioncss='.$optioncss;
    // Add $param from extra fields
    foreach ($search_array_options as $key => $val)
    {
        $crit=$val;
        $tmpkey=preg_replace('/search_options_/','',$key);
        if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
    }
        
    print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, "", $num, '', 'title_project');
    
    print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
    print '<input type="hidden" name="type" value="'.$type.'">';
    
    // Show description of content
    if ($search_task_user == $user->id) print $langs->trans("MyTasksDesc").'<br><br>';
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
    
    // If the user can view users
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.=$langs->trans('ProjectsWithThisUserAsContact'). ' ';
    $includeonly='';
    if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
    $moreforfilter.=$form->select_dolusers($search_project_user, 'search_project_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth300');
    $moreforfilter.='</div>';
    
    // If the user can view users
    $moreforfilter.='<div class="divsearchfield">';
    $moreforfilter.=$langs->trans('TasksWithThisUserAsContact'). ': ';
    $includeonly='';
    if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
    $moreforfilter.=$form->select_dolusers($search_task_user, 'search_task_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth300');
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
    
    print '<table class="liste '.($moreforfilter?"listwithfilterbefore":"").'" id="tablelines3">';

    print '<tr class="liste_titre">';
    if (! empty($arrayfields['p.ref']['checked']))           print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['p.title']['checked']))         print_liste_field_titre($arrayfields['p.title']['label'],$_SERVER["PHP_SELF"],"p.title","",$param,"",$sortfield,$sortorder);
	if (! empty($arrayfields['s.nom']['checked']))           print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['p.fk_statut']['checked']))     print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['t.ref']['checked']))           print_liste_field_titre($arrayfields['t.ref']['label'],$_SERVER["PHP_SELF"],"t.ref","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['t.label']['checked']))         print_liste_field_titre($arrayfields['t.label']['label'],$_SERVER["PHP_SELF"],"t.label","",$param,"",$sortfield,$sortorder);
    if (! empty($arrayfields['t.dateo']['checked']))         print_liste_field_titre($arrayfields['t.dateo']['label'],$_SERVER["PHP_SELF"],"t.dateo","",$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['t.datee']['checked']))         print_liste_field_titre($arrayfields['t.datee']['label'],$_SERVER["PHP_SELF"],"t.datee","",$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['t.planned_workload']['checked']))         print_liste_field_titre($arrayfields['t.planned_workload']['label'],$_SERVER["PHP_SELF"],"t.planned_workload","",$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['t.duration_effective']['checked']))       print_liste_field_titre($arrayfields['t.duration_effective']['label'],$_SERVER["PHP_SELF"],"t.duration_effective","",$param,'align="center"',$sortfield,$sortorder);
    if (! empty($arrayfields['t.progress_calculated']['checked']))      print_liste_field_titre($arrayfields['t.progress_calculated']['label'],$_SERVER["PHP_SELF"],"","",$param,'align="center"');
    if (! empty($arrayfields['t.progress']['checked']))      print_liste_field_titre($arrayfields['t.progress']['label'],$_SERVER["PHP_SELF"],"t.progress","",$param,'align="center"',$sortfield,$sortorder);
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
	if (! empty($arrayfields['t.datec']['checked']))  print_liste_field_titre($arrayfields['t.datec']['label'],$_SERVER["PHP_SELF"],"t.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	if (! empty($arrayfields['t.tms']['checked']))    print_liste_field_titre($arrayfields['t.tms']['label'],$_SERVER["PHP_SELF"],"t.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="right"',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

    print '<tr class="liste_titre">';
    if (! empty($arrayfields['p.ref']['checked']))
    {
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="search_project_ref" value="'.$search_project_ref.'" size="4">';
        print '</td>';
    }
    if (! empty($arrayfields['p.title']['checked']))
    {
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="search_project_title" value="'.$search_project_title.'" size="6">';
        print '</td>';
    }
    if (! empty($arrayfields['s.nom']['checked']))
    {
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="search_societe" value="'.dol_escape_htmltag($search_societe).'" size="4">';
        print '</td>';
    }
    if (! empty($arrayfields['p.fk_statut']['checked']))
    {
        print '<td class="liste_titre">';
        $listofstatus=array(-1=>'&nbsp;');
        foreach($projectstatic->statuts_short as $key => $val) $listofstatus[$key]=$langs->trans($val);
        print $form->selectarray('search_projectstatus', $listofstatus, $search_projectstatus);
        print '</td>';
    }
    if (! empty($arrayfields['t.ref']['checked']))
    {
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="search_task_ref" value="'.dol_escape_htmltag($search_task_ref).'" size="4">';
        print '</td>';
    }
    if (! empty($arrayfields['t.label']['checked']))
    {
        print '<td class="liste_titre">';
        print '<input type="text" class="flat" name="search_task_label" value="'.dol_escape_htmltag($search_task_label).'" size="8">';
        print '</td>';
    }
    // Start date
    if (! empty($arrayfields['t.dateo']['checked']))
    {
        print '<td class="liste_titre center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="sday" value="'.$sday.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="smonth" value="'.$smonth.'">';
        $formother->select_year($syear?$syear:-1,'syear',1, 20, 5);
        print '</td>';
    }
    // End date
    if (! empty($arrayfields['t.datee']['checked']))
    {
        print '<td class="liste_titre center">';
        if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
        print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
        $formother->select_year($year?$year:-1,'year',1, 20, 5);
        print '</td>';
    }
    if (! empty($arrayfields['t.planned_workload']['checked'])) print '<td></td>';
    if (! empty($arrayfields['t.duration_effective']['checked'])) print '<td></td>';
    if (! empty($arrayfields['t.progress_calculated']['checked'])) print '<td></td>';
    if (! empty($arrayfields['t.progress']['checked'])) print '<td></td>';
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
    if (! empty($arrayfields['t.datec']['checked']))
    {
        // Date creation
        print '<td class="liste_titre">';
        print '</td>';
    }
    if (! empty($arrayfields['t.tms']['checked']))
    {
        // Date modification
        print '<td class="liste_titre">';
        print '</td>';
    }
    // Action column
    print '<td class="liste_titre" align="right">';
    print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
    print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print '</td>';
    print '</tr>';
    
    
    $i = 0;
    while ($i < min($num,$limit))
    {
    	$obj = $db->fetch_object($resql);

    	$projectstatic->id = $obj->projectid;
    	$projectstatic->ref = $obj->projectref;
    	$projectstatic->title = $obj->projecttitle;
    	$projectstatic->public = $obj->public;
    	$projectstatic->statut = $obj->projectstatus;
    	
    	$taskstatic->id = $obj->id;
    	$taskstatic->ref = $obj->ref;
    	$taskstatic->label = $obj->label;
    	$taskstatic->fk_statut = $obj->fk_statut;
    	$taskstatic->progress = $obj->progress;
    	
    	$userAccess = $projectstatic->restrictedProjectArea($user);    // why this ?
    	if ($userAccess >= 0)
    	{
    		$var=!$var;
    		print "<tr ".$bc[$var].">";

    		// Project url
        	if (! empty($arrayfields['p.ref']['checked']))
        	{
        		print '<td class="nowrap">';
        		print $projectstatic->getNomUrl(1, 'task');
        		print '</td>';
        	}
    		// Title
        	if (! empty($arrayfields['p.title']['checked']))
        	{
            	print '<td>';
        		print dol_trunc($obj->projecttitle,80);
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
        	// Status
        	if (! empty($arrayfields['p.fk_statut']['checked']))
        	{
        	    print '<td>';
        	    print $projectstatic->getLibStatut(1);
        	    print '</td>';
        	}
        	// Ref
        	if (! empty($arrayfields['t.ref']['checked']))
        	{
        	    print '<td>';
        	    print $taskstatic->getNomUrl(1,'withproject');
        	    print '</td>';
        	}        	 
    	    // Label
        	if (! empty($arrayfields['t.label']['checked']))
        	{
        	    print '<td>';
        	    print $taskstatic->label;
        	    print '</td>';
        	}
        	// Date start
        	if (! empty($arrayfields['t.dateo']['checked']))
        	{
				print '<td class="center">';
	    		print dol_print_date($db->jdate($obj->date_start),'day');
	    		print '</td>';
			}
    		// Date end
        	if (! empty($arrayfields['t.datee']['checked']))
        	{
    			print '<td class="center">';
        		print dol_print_date($db->jdate($obj->date_end),'day');
        		print '</td>';
        	}
        	
        	$plannedworkloadoutputformat='allhourmin';
        	$timespentoutputformat='allhourmin';
        	if (! empty($conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT)) $plannedworkloadoutputformat=$conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT;
        	if (! empty($conf->global->PROJECT_TIMES_SPENT_FORMAT)) $timespentoutputformat=$conf->global->PROJECT_TIME_SPENT_FORMAT;
        	
        	// Planned workload
        	if (! empty($arrayfields['t.planned_workload']['checked']))
        	{
        	    print '<td class="center">';
        	    $fullhour=convertSecondToTime($obj->planned_workload,$plannedworkloadoutputformat);
        	    $workingdelay=convertSecondToTime($obj->planned_workload,'all',86400,7);	// TODO Replace 86400 and 7 to take account working hours per day and working day per weeks
        	    if ($obj->planned_workload != '')
        	    {
        	        print $fullhour;
        	        // TODO Add delay taking account of working hours per day and working day per week
        	        //if ($workingdelay != $fullhour) print '<br>('.$workingdelay.')';
        	    }
        	    //else print '--:--';
        	    print '</td>';
        	}
        	// Time spent
        	if (! empty($arrayfields['t.duration_effective']['checked']))
        	{
        	    $showlineingray=0;$showproject=1;
        	    print '<td class="center">';
				if ($showlineingray) print '<i>';
				else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$lines[$i]->id.($showproject?'':'&withproject=1').'">';
				if ($obj->duration_effective) print convertSecondToTime($obj->duration_effective,$timespentoutputformat);
				else print '--:--';
				if ($showlineingray) print '</i>';
				else print '</a>';
        	    print '</td>';
        	}    		
    	    // Calculated progress
        	if (! empty($arrayfields['t.progress_calculated']['checked']))
        	{
    			print '<td class="center">';
            	if ($obj->planned_workload || $obj->duration_effective)
				{
					if ($obj->planned_workload) print round(100 * $obj->duration_effective / $obj->planned_workload,2).' %';
					else print $langs->trans('WorkloadNotDefined');
				}
        		print '</td>';
        	}    		
    	    // Declared progress
        	if (! empty($arrayfields['t.progress']['checked']))
        	{
    			print '<td class="center">';
                if ($obj->progress != '')
				{
					print $obj->progress.' %';
				}
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
    		if (! empty($arrayfields['t.datec']['checked']))
    		{
    		    print '<td align="center">';
    		    print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
    		    print '</td>';
    		}
    		// Date modification
    		if (! empty($arrayfields['t.tms']['checked']))
    		{
    		    print '<td align="center">';
    		    print dol_print_date($db->jdate($obj->date_update), 'dayhour');
    		    print '</td>';
    		}
    		// Status
    		/*if (! empty($arrayfields['p.fk_statut']['checked']))
    		{
        		$projectstatic->statut = $obj->fk_statut;
        		print '<td align="right">'.$projectstatic->getLibStatut(5).'</td>';
    		}*/
    		// Action column
    		print '<td></td>';

    		print "</tr>\n";
        
    		//print projectLinesa();
    	}

    	$i++;    
    }
    $db->free($resql);
    
    $parameters=array('sql' => $sql);
    $reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    
    print "</table>";
    
    print '</form>';
}
else
{
    dol_print_error($db);
}


llxFooter();

$db->close();
