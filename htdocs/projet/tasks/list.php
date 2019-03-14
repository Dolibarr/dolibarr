<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2018	   Ferran Marcet        <fmarcet@2byte.es>
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

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'users', 'companies'));

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$id=GETPOST('id','int');

$search_all=trim((GETPOST('search_all', 'alphanohtml')!='')?GETPOST('search_all', 'alphanohtml'):GETPOST('sall', 'alphanohtml'));
$search_categ=GETPOST("search_categ",'alpha');
$search_project=GETPOST('search_project');
if (! isset($_GET['search_projectstatus']) && ! isset($_POST['search_projectstatus']))
{
	if ($search_all != '') $search_projectstatus=-1;
	else $search_projectstatus=1;
}
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

$search_sday	= GETPOST('search_sday','int');
$search_smonth	= GETPOST('search_smonth','int');
$search_syear	= GETPOST('search_syear','int');
$search_eday	= GETPOST('search_eday','int');
$search_emonth	= GETPOST('search_emonth','int');
$search_eyear	= GETPOST('search_eyear','int');

// Initialize context for list
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'tasklist';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Task($db);
$hookmanager->initHooks(array('tasklist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('projet_task');
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// Security check
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if (!$user->rights->projet->lire) accessforbidden();

$diroutputmassaction=$conf->projet->dir_output . '/tasks/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
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
	't.ref'=>array('label'=>$langs->trans("RefTask"), 'checked'=>1, 'position'=>80),
	't.label'=>array('label'=>$langs->trans("LabelTask"), 'checked'=>1, 'position'=>80),
	't.dateo'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1, 'position'=>100),
	't.datee'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1, 'position'=>101),
	'p.ref'=>array('label'=>$langs->trans("ProjectRef"), 'checked'=>1),
	'p.title'=>array('label'=>$langs->trans("ProjectLabel"), 'checked'=>0),
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>0),
	'p.fk_statut'=>array('label'=>$langs->trans("ProjectStatus"), 'checked'=>1),
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
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
	}
}


/*
 * Actions
 */

if (GETPOST('cancel','alpha')) { $action='list'; $massaction=''; }
if (! GETPOST('confirmmassaction','alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction=''; }

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All tests are required to be compatible with all browsers
	{
		$search_all="";
		$search_categ="";
		$search_project="";
		$search_projectstatus=-1;
		$search_project_ref="";
		$search_project_title="";
		$search_task_ref="";
		$search_task_label="";
		$search_task_description="";
		$search_task_user=-1;
		$search_project_user=-1;
		$search_sday='';
		$search_smonth='';
		$search_syear='';
		$search_eday='';
		$search_emonth='';
		$search_eyear='';
		$toselect='';
		$search_array_options=array();
	}

	// Mass actions
	$objectclass='Task';
	$objectlabel='Tasks';
	$permtoread = $user->rights->projet->lire;
	$permtodelete = $user->rights->projet->supprimer;
	$uploaddir = $conf->projet->dir_output.'/tasks';
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

if (empty($search_projectstatus) && $search_projectstatus == '') $search_projectstatus=1;




/*
 * View
 */

$now = dol_now();
$form=new Form($db);
$formother=new FormOther($db);
$socstatic=new Societe($db);
$projectstatic = new Project($db);
$puser=new User($db);
$tuser=new User($db);
if ($search_project_user > 0) $puser->fetch($search_project_user);
if ($search_task_user > 0) $tuser->fetch($search_task_user);

$title=$langs->trans("Activities");
//if ($search_task_user == $user->id) $title=$langs->trans("MyActivities");

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
$sql.= " WHERE ctc.element = '" . $object->element . "'";
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
$sql = "SELECT ".$distinct." p.rowid as projectid, p.ref as projectref, p.title as projecttitle, p.fk_statut as projectstatus, p.datee as projectdatee, p.fk_opp_status, p.public, p.fk_user_creat as projectusercreate";
$sql.= ", s.nom as name, s.rowid as socid";
$sql.= ", t.datec as date_creation, t.dateo as date_start, t.datee as date_end, t.tms as date_update";
$sql.= ", t.rowid as id, t.ref, t.label, t.planned_workload, t.duration_effective, t.progress, t.fk_statut";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_project";
// Add fields from extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
// We'll need this table joined to the select in order to filter by categ
if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_project as cs ON p.rowid = cs.fk_project"; // We'll need this table joined to the select in order to filter by categ
$sql.= ", ".MAIN_DB_PREFIX."projet_task as t";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_task_extrafields as ef on (t.rowid = ef.fk_object)";
if ($search_project_user > 0)  $sql.=", ".MAIN_DB_PREFIX."element_contact as ecp";
if ($search_task_user > 0)     $sql.=", ".MAIN_DB_PREFIX."element_contact as ect";
$sql.= " WHERE t.fk_projet = p.rowid";
$sql.= " AND p.entity IN (".getEntity('project').')';
if (! $user->rights->projet->all->lire) $sql.=" AND p.rowid IN (".($projectsListId?$projectsListId:'0').")";    // public and assigned to projects, or restricted to company for external users
// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
if ($socid) $sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
if ($search_categ > 0)     $sql.= " AND cs.fk_categorie = ".$db->escape($search_categ);
if ($search_categ == -2)   $sql.= " AND cs.fk_categorie IS NULL";
if ($search_project_ref)   $sql .= natural_search('p.ref', $search_project_ref);
if ($search_project_title) $sql .= natural_search('p.title', $search_project_title);
if ($search_task_ref)      $sql .= natural_search('t.ref', $search_task_ref);
if ($search_task_label)    $sql .= natural_search('t.label', $search_task_label);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_smonth > 0)
{
	if ($search_syear > 0 && empty($search_sday))
		$sql.= " AND t.dateo BETWEEN '".$db->idate(dol_get_first_day($search_syear,$search_smonth,false))."' AND '".$db->idate(dol_get_last_day($search_syear,$search_smonth,false))."'";
		else if ($search_syear > 0 && ! empty($search_sday))
			$sql.= " AND t.dateo BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_smonth, $search_sday, $search_syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_smonth, $search_sday, $search_syear))."'";
			else
				$sql.= " AND date_format(t.dateo, '%m') = '".$search_smonth."'";
}
else if ($search_syear > 0)
{
	$sql.= " AND t.dateo BETWEEN '".$db->idate(dol_get_first_day($search_syear,1,false))."' AND '".$db->idate(dol_get_last_day($search_syear,12,false))."'";
}
if ($search_emonth > 0)
{
	if ($search_eyear > 0 && empty($search_eday))
		$sql.= " AND t.datee BETWEEN '".$db->idate(dol_get_first_day($search_eyear,$search_emonth,false))."' AND '".$db->idate(dol_get_last_day($search_eyear,$search_emonth,false))."'";
		else if ($search_eyear > 0 && ! empty($search_eday))
			$sql.= " AND t.datee BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_emonth, $search_eday, $search_eyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_emonth, $search_eday, $search_eyear))."'";
			else
				$sql.= " AND date_format(t.datee, '%m') = '".$search_emonth."'";
}
else if ($search_eyear > 0)
{
	$sql.= " AND t.datee BETWEEN '".$db->idate(dol_get_first_day($search_eyear,1,false))."' AND '".$db->idate(dol_get_last_day($search_eyear,12,false))."'";
}
if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_projectstatus >= 0)
{
	if ($search_projectstatus == 99) $sql .= " AND p.fk_statut <> 2";
	else $sql .= " AND p.fk_statut = ".$db->escape($search_projectstatus);
}
if ($search_public!='') $sql .= " AND p.public = ".$db->escape($search_public);
if ($search_project_user > 0) $sql.= " AND ecp.fk_c_type_contact IN (".join(',',array_keys($listofprojectcontacttype)).") AND ecp.element_id = p.rowid AND ecp.fk_socpeople = ".$search_project_user;
if ($search_task_user > 0) $sql.= " AND ect.fk_c_type_contact IN (".join(',',array_keys($listoftaskcontacttype)).") AND ect.element_id = t.rowid AND ect.fk_socpeople = ".$search_task_user;
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListWhere',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= $db->order($sortfield,$sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql.= $db->plimit($limit + 1,$offset);

dol_syslog("list allowed project", LOG_DEBUG);

$resql = $db->query($sql);
if (! $resql)
{
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$arrayofselected=is_array($toselect)?$toselect:array();

if ($num == 1 && ! empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all)
{
	$obj = $db->fetch_object($resql);
	$id = $obj->id;
	header("Location: ".DOL_URL_ROOT.'/projet/tasks/task.php?id='.$id.'&withprojet=1');
	exit;
}

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("", $title, $help_url);

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($search_sday)                 		$param.='&search_sday='.$search_sday;
if ($search_smonth)              		$param.='&search_smonth='.$search_smonth;
if ($search_syear)               		$param.='&search_syear=' .$search_syear;
if ($search_eday)               		$param.='&search_eday='.$search_eday;
if ($search_emonth)              		$param.='&search_emonth='.$search_emonth;
if ($search_eyear)               		$param.='&search_eyear=' .$search_eyear;
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
if ($search_project_user != '')   $param.='&search_project_user='.$search_project_user;
if ($search_task_user > 0)    	$param.='&search_task_user='.$search_task_user;
if ($optioncss != '') $param.='&optioncss='.$optioncss;
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions =  array(
//    'presend'=>$langs->trans("SendByMail"),
//    'builddoc'=>$langs->trans("PDFMerge"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if ($user->rights->societe->supprimer) $arrayofmassactions['predelete']=$langs->trans("Delete");
if (in_array($massaction, array('presend','predelete'))) $arrayofmassactions=array();
$massactionbutton=$form->selectMassAction('', $arrayofmassactions);

$newcardbutton='';
if ($user->rights->projet->creer)
{
	$newcardbutton = '<a class="butActionNew" href="'.DOL_URL_ROOT.'/projet/tasks.php?action=create"><span class="valignmiddle">'.$langs->trans('NewTask').'</span>';
	$newcardbutton.= '<span class="fa fa-plus-circle valignmiddle"></span>';
	$newcardbutton.= '</a>';
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_project', 0, $newcardbutton, '', $limit);

// Show description of content
print '<div class="opacitymedium">';
if ($search_task_user == $user->id) print $langs->trans("MyTasksDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("TasksOnProjectsDesc").'<br><br>';
	else print $langs->trans("TasksOnProjectsPublicDesc").'<br><br>';
}
print '</div>';

$topicmail="Information";
$modelmail="task";
$objecttmp=new Task($db);
$trackid='tas'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall).'</div>';
}

$morehtmlfilter = '';

// Filter on categories
if (! empty($conf->categorie->enabled))
{
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('ProjectCategories'). ': ';
	$moreforfilter.=$formother->select_categories('project', $search_categ, 'search_categ', 1, 'maxwidth300');
	$moreforfilter.='</div>';
}

// If the user can view users
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('ProjectsWithThisUserAsContact'). ' ';
$includeonly='';
if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
$moreforfilter.=$form->select_dolusers($search_project_user?$search_project_user:'', 'search_project_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth200');
$moreforfilter.='</div>';

// If the user can view users
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('TasksWithThisUserAsContact'). ': ';
$includeonly='';
if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
$moreforfilter.=$form->select_dolusers($search_task_user, 'search_task_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth200');
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
if ($massactionbutton) $selectedfields.=$form->showCheckAddButtons('checkforselect', 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'" id="tablelines3">'."\n";

print '<tr class="liste_titre_filter">';
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
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="search_sday" value="'.$search_sday.'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="search_smonth" value="'.$search_smonth.'">';
	$formother->select_year($search_syear?$search_syear:-1,'search_syear',1, 20, 5);
	print '</td>';
}
// End date
if (! empty($arrayfields['t.datee']['checked']))
{
	print '<td class="liste_titre center">';
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="search_eday" value="'.$search_eday.'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="search_emonth" value="'.$search_emonth.'">';
	$formother->select_year($search_eyear?$search_eyear:-1,'search_eyear',1, 20, 5);
	print '</td>';
}
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
	print '<td class="liste_titre center">';
	$arrayofstatus = array();
	foreach($projectstatic->statuts_short as $key => $val) $arrayofstatus[$key]=$langs->trans($val);
	$arrayofstatus['99']=$langs->trans("NotClosed").' ('.$langs->trans('Draft').'+'.$langs->trans('Opened').')';
	print $form->selectarray('search_projectstatus', $arrayofstatus, $search_projectstatus, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
	print '</td>';
}
if (! empty($arrayfields['t.planned_workload']['checked'])) print '<td class="liste_titre"></td>';
if (! empty($arrayfields['t.duration_effective']['checked'])) print '<td class="liste_titre"></td>';
if (! empty($arrayfields['t.progress_calculated']['checked'])) print '<td class="liste_titre"></td>';
if (! empty($arrayfields['t.progress']['checked'])) print '<td class="liste_titre"></td>';
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
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
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
if (! empty($arrayfields['t.ref']['checked']))           print_liste_field_titre($arrayfields['t.ref']['label'],$_SERVER["PHP_SELF"],"t.ref","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['t.label']['checked']))         print_liste_field_titre($arrayfields['t.label']['label'],$_SERVER["PHP_SELF"],"t.label","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['t.dateo']['checked']))         print_liste_field_titre($arrayfields['t.dateo']['label'],$_SERVER["PHP_SELF"],"t.dateo","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['t.datee']['checked']))         print_liste_field_titre($arrayfields['t.datee']['label'],$_SERVER["PHP_SELF"],"t.datee","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['p.ref']['checked']))           print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['p.title']['checked']))         print_liste_field_titre($arrayfields['p.title']['label'],$_SERVER["PHP_SELF"],"p.title","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['s.nom']['checked']))           print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['p.fk_statut']['checked']))     print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['t.planned_workload']['checked']))         print_liste_field_titre($arrayfields['t.planned_workload']['label'],$_SERVER["PHP_SELF"],"t.planned_workload","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['t.duration_effective']['checked']))       print_liste_field_titre($arrayfields['t.duration_effective']['label'],$_SERVER["PHP_SELF"],"t.duration_effective","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['t.progress_calculated']['checked']))      print_liste_field_titre($arrayfields['t.progress_calculated']['label'],$_SERVER["PHP_SELF"],"","",$param,'align="center"');
if (! empty($arrayfields['t.progress']['checked']))      print_liste_field_titre($arrayfields['t.progress']['label'],$_SERVER["PHP_SELF"],"t.progress","",$param,'align="center"',$sortfield,$sortorder);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters=array('arrayfields'=>$arrayfields,'param'=>$param,'sortfield'=>$sortfield,'sortorder'=>$sortorder);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['t.datec']['checked']))  print_liste_field_titre($arrayfields['t.datec']['label'],$_SERVER["PHP_SELF"],"t.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['t.tms']['checked']))    print_liste_field_titre($arrayfields['t.tms']['label'],$_SERVER["PHP_SELF"],"t.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";


$plannedworkloadoutputformat='allhourmin';
$timespentoutputformat='allhourmin';
if (! empty($conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT)) $plannedworkloadoutputformat=$conf->global->PROJECT_PLANNED_WORKLOAD_FORMAT;
if (! empty($conf->global->PROJECT_TIMES_SPENT_FORMAT)) $timespentoutputformat=$conf->global->PROJECT_TIME_SPENT_FORMAT;

$i=0;
$totalarray=array();
while ($i < min($num,$limit))
{
	$obj = $db->fetch_object($resql);

	$object->id = $obj->id;
	$object->ref = $obj->ref;
	$object->label = $obj->label;
	$object->fk_statut = $obj->fk_statut;
	$object->progress = $obj->progress;
	$object->datee = $db->jdate($obj->date_end);	// deprecated
	$object->date_end = $db->jdate($obj->date_end);

	$projectstatic->id = $obj->projectid;
	$projectstatic->ref = $obj->projectref;
	$projectstatic->title = $obj->projecttitle;
	$projectstatic->public = $obj->public;
	$projectstatic->statut = $obj->projectstatus;
	$projectstatic->datee = $db->jdate($obj->projectdatee);

	$userAccess = $projectstatic->restrictedProjectArea($user);    // why this ?
	if ($userAccess >= 0)
	{
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';

		// Ref
		if (! empty($arrayfields['t.ref']['checked']))
		{
			print '<td>';
			print $object->getNomUrl(1,'withproject');
			if ($object->hasDelay()) print img_warning("Late");
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Label
		if (! empty($arrayfields['t.label']['checked']))
		{
			print '<td>';
			print $object->label;
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date start
		if (! empty($arrayfields['t.dateo']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_start),'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date end
		if (! empty($arrayfields['t.datee']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_end),'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Project ref
		if (! empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="nowrap">';
			print $projectstatic->getNomUrl(1, 'task');
			if ($projectstatic->hasDelay()) print img_warning("Late");
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Project title
		if (! empty($arrayfields['p.title']['checked']))
		{
			print '<td>';
			print dol_trunc($obj->projecttitle,80);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Third party
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
			if (! $i) $totalarray['nbfield']++;
		}
		// Project status
		if (! empty($arrayfields['p.fk_statut']['checked']))
		{
			print '<td align="center">';
			print $projectstatic->getLibStatut(1);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}

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
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalplannedworkloadfield']=$totalarray['nbfield'];
			$totalarray['totalplannedworkload'] += $obj->planned_workload;
		}
		// Time spent
		if (! empty($arrayfields['t.duration_effective']['checked']))
		{
			$showlineingray=0;$showproject=1;
			print '<td class="center">';
			if ($showlineingray) print '<i>';
			else print '<a href="'.DOL_URL_ROOT.'/projet/tasks/time.php?id='.$object->id.($showproject?'':'&withproject=1').'">';
			if ($obj->duration_effective) print convertSecondToTime($obj->duration_effective,$timespentoutputformat);
			else print '--:--';
			if ($showlineingray) print '</i>';
			else print '</a>';
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totaldurationeffectivefield']=$totalarray['nbfield'];
			$totalarray['totaldurationeffective'] += $obj->duration_effective;
		}
		// Calculated progress
		if (! empty($arrayfields['t.progress_calculated']['checked']))
		{
			print '<td class="center">';
			if ($obj->planned_workload || $obj->duration_effective)
			{
				if ($obj->planned_workload) print round(100 * $obj->duration_effective / $obj->planned_workload,2).' %';
				else print $form->textwithpicto('',$langs->trans('WorkloadNotDefined'), 1, 'help');
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalprogress_calculated']=$totalarray['nbfield'];
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
			if (! $i) $totalarray['nbfield']++;
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (! empty($arrayfields['t.datec']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['t.tms']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Status
		/*if (! empty($arrayfields['p.fk_statut']['checked']))
		{
    		$projectstatic->statut = $obj->fk_statut;
    		print '<td align="right">'.$projectstatic->getLibStatut(5).'</td>';
		}*/
		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected=0;
			if (in_array($obj->id, $arrayofselected)) $selected=1;
			print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected?' checked="checked"':'').'>';
		}
		print '</td>';
		if (! $i) $totalarray['nbfield']++;

		print "</tr>\n";

		//print projectLinesa();
	}

	$i++;
}

// Show total line
if (isset($totalarray['totaldurationeffectivefield']) || isset($totalarray['totalplannedworkloadfield']))
{
	print '<tr class="liste_total">';
	$i=0;
	while ($i < $totalarray['nbfield'])
	{
		$i++;
		if ($i == 1)
		{
			if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
			else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
		}
		elseif ($totalarray['totalplannedworkloadfield'] == $i) print '<td align="center">'.convertSecondToTime($totalarray['totalplannedworkload'],$plannedworkloadoutputformat).'</td>';
		elseif ($totalarray['totaldurationeffectivefield'] == $i) print '<td align="center">'.convertSecondToTime($totalarray['totaldurationeffective'],$timespentoutputformat).'</td>';
		elseif ($totalarray['totalprogress_calculated'] == $i) print '<td align="center">'.($totalarray['totalplannedworkload'] > 0 ? round(100 * $totalarray['totaldurationeffective'] / $totalarray['totalplannedworkload'], 2).' %' : '').'</td>';
		else print '<td></td>';
	}
	print '</tr>';
}

$db->free($resql);

$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>";
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
