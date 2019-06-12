<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015 	   Claudio Aschieri     <c.aschieri@19.coop>
 * Copyright (C) 2019 	   Juanjo Menent	    <jmenent@2byte.es>
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

$langs->loadLangs(array('projects', 'companies', 'commercial'));

$action=GETPOST('action','alpha');
$massaction=GETPOST('massaction','alpha');
$show_files=GETPOST('show_files','int');
$confirm=GETPOST('confirm','alpha');
$toselect = GETPOST('toselect', 'array');

$title = $langs->trans("Projects");

// Security check
$socid = (is_numeric($_GET["socid"]) ? $_GET["socid"] : 0 );
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if ($socid > 0)
{
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title .= ' (<a href="list.php">'.$soc->name.'</a>)';
}
if (!$user->rights->projet->lire) accessforbidden();

$diroutputmassaction=$conf->projet->dir_output . '/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield","alpha");
$sortorder = GETPOST("sortorder");
$page = GETPOST("page");
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
if (! $sortfield) $sortfield="p.ref";
if (! $sortorder) $sortorder="ASC";
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_all=GETPOST('search_all', 'alphanohtml');
$search_categ=GETPOST("search_categ",'alpha');
$search_ref=GETPOST("search_ref",'alpha');
$search_label=GETPOST("search_label",'alpha');
$search_societe=GETPOST("search_societe",'alpha');
$search_year=GETPOST("search_year");
$search_status=GETPOST("search_status",'int');
$search_opp_status=GETPOST("search_opp_status",'alpha');
$search_opp_percent=GETPOST("search_opp_percent",'alpha');
$search_opp_amount=GETPOST("search_opp_amount",'alpha');
$search_budget_amount=GETPOST("search_budget_amount",'alpha');
$search_public=GETPOST("search_public",'int');
$search_project_user=GETPOST('search_project_user','int');
$search_sale=GETPOST('search_sale','int');
$optioncss = GETPOST('optioncss','alpha');

$mine = $_REQUEST['mode']=='mine' ? 1 : 0;
if ($mine) { $search_project_user = $user->id; $mine=0; }

$search_sday	= GETPOST('search_sday','int');
$search_smonth	= GETPOST('search_smonth','int');
$search_syear	= GETPOST('search_syear','int');
$search_eday	= GETPOST('search_eday','int');
$search_emonth	= GETPOST('search_emonth','int');
$search_eyear	= GETPOST('search_eyear','int');

if ($search_status == '') $search_status=-1;	// -1 or 1


// Initialize context for list
$contextpage=GETPOST('contextpage','aZ')?GETPOST('contextpage','aZ'):'projectlist';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array($contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('projet');
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
	's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1, 'enabled'=>(empty($conf->societe->enabled)?0:1)),
	'commercial'=>array('label'=>$langs->trans("SaleRepresentativesOfThirdParty"), 'checked'=>0),
	'p.dateo'=>array('label'=>$langs->trans("DateStart"), 'checked'=>1, 'position'=>100),
	'p.datee'=>array('label'=>$langs->trans("DateEnd"), 'checked'=>1, 'position'=>101),
	'p.public'=>array('label'=>$langs->trans("Visibility"), 'checked'=>1, 'position'=>102),
	'p.opp_amount'=>array('label'=>$langs->trans("OpportunityAmountShort"), 'checked'=>1, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>103),
	'p.fk_opp_status'=>array('label'=>$langs->trans("OpportunityStatusShort"), 'checked'=>1, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>104),
	'p.opp_percent'=>array('label'=>$langs->trans("OpportunityProbabilityShort"), 'checked'=>1, 'enabled'=>($conf->global->PROJECT_USE_OPPORTUNITIES?1:0), 'position'=>105),
	'p.budget_amount'=>array('label'=>$langs->trans("Budget"), 'checked'=>0, 'position'=>110),
	'p.datec'=>array('label'=>$langs->trans("DateCreationShort"), 'checked'=>0, 'position'=>500),
	'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
	'p.fk_statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
   foreach($extrafields->attribute_label as $key => $val)
   {
		if (! empty($extrafields->attribute_list[$key])) $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>(($extrafields->attribute_list[$key]<0)?0:1), 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>(abs($extrafields->attribute_list[$key])!=3 && $extrafields->attribute_perms[$key]));
   }
}

$object = new Project($db);


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
		$search_all='';
		$search_categ='';
		$search_ref="";
		$search_label="";
		$search_societe="";
		$search_year="";
		$search_status=-1;
		$search_opp_status=-1;
		$search_opp_amount='';
		$search_opp_percent='';
		$search_budget_amount='';
		$search_public="";
		$search_sale="";
		$search_project_user='';
		$search_sday="";
		$search_smonth="";
		$search_syear="";
		$search_eday="";
		$search_emonth="";
		$search_eyear="";
		$toselect='';
		$search_array_options=array();
	}


	// Mass actions
	$objectclass='Project';
	$objectlabel='Project';
	$permtoread = $user->rights->projet->lire;
	$permtodelete = $user->rights->projet->supprimer;
	$uploaddir = $conf->projet->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$socstatic = new Societe($db);
$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);

$title=$langs->trans("Projects");
//if ($search_project_user == $user->id) $title=$langs->trans("MyProjects");


// Get list of project id allowed to user (in a string list separated by coma)
$projectsListId='';
if (! $user->rights->projet->all->lire) $projectsListId = $object->getProjectsAuthorizedForUser($user,0,1,$socid);

// Get id of types of contacts for projects (This list never contains a lot of elements)
$listofprojectcontacttype=array();
$sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
$sql.= " WHERE ctc.element = '" . $object->element . "'";
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
$sql = "SELECT ".$distinct." p.rowid as id, p.ref, p.title, p.fk_statut, p.fk_opp_status, p.public, p.fk_user_creat";
$sql.= ", p.datec as date_creation, p.dateo as date_start, p.datee as date_end, p.opp_amount, p.opp_percent, p.tms as date_update, p.budget_amount";
$sql.= ", s.nom as name, s.rowid as socid";
$sql.= ", cls.code as opp_status_code";
// We'll need these fields in order to filter by categ
if ($search_categ) $sql .= ", cs.fk_categorie, cs.fk_project";
// Add fields for extrafields
foreach ($extrafields->attribute_label as $key => $val) $sql.=($extrafields->attribute_type[$key] != 'separate' ? ",ef.".$key.' as options_'.$key : '');
// Add fields from hooks
$parameters=array();
$reshook=$hookmanager->executeHooks('printFieldListSelect',$parameters);    // Note that $action and $object may have been modified by hook
$sql.=$hookmanager->resPrint;
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."projet_extrafields as ef on (p.rowid = ef.fk_object)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls on p.fk_opp_status = cls.rowid";
// We'll need this table joined to the select in order to filter by categ
if (! empty($search_categ)) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_project as cs ON p.rowid = cs.fk_project"; // We'll need this table joined to the select in order to filter by categ
// We'll need this table joined to the select in order to filter by sale
// For external user, no check is done on company permission because readability is managed by public status of project and assignement.
//if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
if ($search_sale > 0) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
if ($search_project_user > 0)
{
	$sql.=", ".MAIN_DB_PREFIX."element_contact as ecp";
}
$sql.= " WHERE p.entity IN (".getEntity('project').')';
if (! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";     // public and assigned to, or restricted to company for external users
// No need to check if company is external user, as filtering of projects must be done by getProjectsAuthorizedForUser
if ($socid > 0) $sql.= " AND (p.fk_soc = ".$socid.")";
if ($search_categ > 0)    $sql.= " AND cs.fk_categorie = ".$db->escape($search_categ);
if ($search_categ == -2)  $sql.= " AND cs.fk_categorie IS NULL";
if ($search_ref) $sql .= natural_search('p.ref', $search_ref);
if ($search_label) $sql .= natural_search('p.title', $search_label);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_opp_amount) $sql .= natural_search('p.opp_amount', $search_opp_amount, 1);
if ($search_opp_percent) $sql .= natural_search('p.opp_percent', $search_opp_percent, 1);
if ($search_smonth > 0)
{
	if ($search_syear > 0 && empty($search_sday))
		$sql.= " AND p.dateo BETWEEN '".$db->idate(dol_get_first_day($search_syear,$search_smonth,false))."' AND '".$db->idate(dol_get_last_day($search_syear,$search_smonth,false))."'";
	else if ($search_syear > 0 && ! empty($search_sday))
		$sql.= " AND p.dateo BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_smonth, $search_sday, $search_syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_smonth, $search_sday, $search_syear))."'";
	else
		$sql.= " AND date_format(p.dateo, '%m') = '".$search_smonth."'";
}
else if ($search_syear > 0)
{
	$sql.= " AND p.dateo BETWEEN '".$db->idate(dol_get_first_day($search_syear,1,false))."' AND '".$db->idate(dol_get_last_day($search_syear,12,false))."'";
}
if ($search_emonth > 0)
{
	if ($search_eyear > 0 && empty($search_eday))
		$sql.= " AND p.datee BETWEEN '".$db->idate(dol_get_first_day($search_eyear,$search_emonth,false))."' AND '".$db->idate(dol_get_last_day($search_eyear,$search_emonth,false))."'";
	else if ($search_eyear > 0 && ! empty($search_eday))
		$sql.= " AND p.datee BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $search_emonth, $search_eday, $search_eyear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $search_emonth, $search_eday, $search_eyear))."'";
	else
		$sql.= " AND date_format(p.datee, '%m') = '".$search_emonth."'";
}
else if ($search_eyear > 0)
{
	$sql.= " AND p.datee BETWEEN '".$db->idate(dol_get_first_day($search_eyear,1,false))."' AND '".$db->idate(dol_get_last_day($search_eyear,12,false))."'";
}
if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_status >= 0)
{
	if ($search_status == 99) $sql .= " AND p.fk_statut <> 2";
	else $sql .= " AND p.fk_statut = ".$db->escape($search_status);
}
if ($search_opp_status)
{
	if (is_numeric($search_opp_status) && $search_opp_status > 0) $sql .= " AND p.fk_opp_status = ".$db->escape($search_opp_status);
	if ($search_opp_status == 'all') $sql .= " AND p.fk_opp_status IS NOT NULL";
	if ($search_opp_status == 'openedopp') $sql .= " AND p.fk_opp_status IS NOT NULL AND p.fk_opp_status NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_lead_status WHERE code IN ('WON','LOST'))";
	if ($search_opp_status == 'none') $sql .= " AND p.fk_opp_status IS NULL";
}
if ($search_public!='') $sql .= " AND p.public = ".$db->escape($search_public);
if ($search_sale > 0) $sql.= " AND sc.fk_user = " .$search_sale;
// For external user, no check is done on company permission because readability is managed by public status of project and assignement.
//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";
if ($search_project_user > 0) $sql.= " AND ecp.fk_c_type_contact IN (".join(',',array_keys($listofprojectcontacttype)).") AND ecp.element_id = p.rowid AND ecp.fk_socpeople = ".$search_project_user;
if ($search_opp_amount != '') $sql .= natural_search('p.opp_amount', $search_opp_amount, 1);
if ($search_budget_amount != '') $sql .= natural_search('p.budget_amount', $search_budget_amount, 1);
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
}

$sql.= $db->plimit($limit + 1,$offset);

//print $sql;
dol_syslog("list allowed project", LOG_DEBUG);
//print $sql;
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
	header("Location: ".DOL_URL_ROOT.'/projet/card.php?id='.$obj->id);
	exit;
}

$help_url="EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
llxHeader("", $title, $help_url);

$param='';
if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
if ($search_all != '') 			$param.='&search_all='.$search_all;
if ($search_sday)              		    $param.='&search_sday='.$search_sday;
if ($search_smonth)              		$param.='&search_smonth='.$search_smonth;
if ($search_syear)               		$param.='&search_syear=' .$search_syear;
if ($search_eday)               		$param.='&search_eday='.$search_eday;
if ($search_emonth)              		$param.='&search_emonth='.$search_emonth;
if ($search_eyear)               		$param.='&search_eyear=' .$search_eyear;
if ($socid)				        $param.='&socid='.$socid;
if ($search_ref != '') 			$param.='&search_ref='.$search_ref;
if ($search_label != '') 		$param.='&search_label='.$search_label;
if ($search_societe != '') 		$param.='&search_societe='.$search_societe;
if ($search_status >= 0) 		$param.='&search_status='.$search_status;
if ((is_numeric($search_opp_status) && $search_opp_status >= 0) || in_array($search_opp_status, array('all','openedopp','none'))) 	    $param.='&search_opp_status='.urlencode($search_opp_status);
if ($search_opp_percent != '') 	$param.='&search_opp_percent='.urlencode($search_opp_percent);
if ($search_public != '') 		$param.='&search_public='.$search_public;
if ($search_project_user != '')   $param.='&search_project_user='.$search_project_user;
if ($search_sale > 0)    		$param.='&search_sale='.$search_sale;
if ($search_opp_amount != '')    $param.='&search_opp_amount='.$search_opp_amount;
if ($search_budget_amount != '') $param.='&search_budget_amount='.$search_budget_amount;
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

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="type" value="'.$type.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'title_project', 0, '', '', $limit);

// Show description of content
print '<div class="opacitymedium">';
if ($search_project_user == $user->id) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if ($user->rights->projet->all->lire && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}
print '</div>';

$topicmail="Information";
$modelmail="project";
$objecttmp=new Project($db);
$trackid='prj'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all)
{
	foreach($fieldstosearchall as $key => $val) $fieldstosearchall[$key]=$langs->trans($val);
	print $langs->trans("FilterOnInto", $search_all) . join(', ',$fieldstosearchall);
}

$moreforfilter='';

// Filter on categories
if (! empty($conf->categorie->enabled))
{
	require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('ProjectCategories'). ': ';
	$moreforfilter.=$formother->select_categories('project', $search_categ, 'search_categ', 1, 1, 'maxwidth300');
	$moreforfilter.='</div>';
}

// If the user can view user other than himself
$moreforfilter.='<div class="divsearchfield">';
$moreforfilter.=$langs->trans('ProjectsWithThisUserAsContact'). ': ';
$includeonly='hierachyme';
if (empty($user->rights->user->user->lire)) $includeonly=array($user->id);
$moreforfilter.=$form->select_dolusers($search_project_user?$search_project_user:'', 'search_project_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth200');
$moreforfilter.='</div>';

// If the user can view thirdparties other than his'
if ($user->rights->societe->client->voir || $socid)
{
	$langs->load("commercial");
	$moreforfilter.='<div class="divsearchfield">';
	$moreforfilter.=$langs->trans('ThirdPartiesOfSaleRepresentative'). ': ';
	$moreforfilter.=$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
	$moreforfilter.='</div>';
}

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
print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

print '<tr class="liste_titre_filter">';
if (! empty($arrayfields['p.ref']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="6">';
	print '</td>';
}
if (! empty($arrayfields['p.title']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_label" size="8" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
}
if (! empty($arrayfields['s.nom']['checked']))
{
	print '<td class="liste_titre">';
	if ($socid > 0)
	{
		$tmpthirdparty=new Societe($db);
		$tmpthirdparty->fetch($socid);
		$search_societe=$tmpthirdparty->nom;
	}
	print '<input type="text" class="flat" name="search_societe" size="8" value="'.dol_escape_htmltag($search_societe).'">';
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
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="search_sday" value="'.dol_escape_htmltag($search_sday).'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="search_smonth" value="'.dol_escape_htmltag($search_smonth).'">';
	$formother->select_year($search_syear?$search_syear:-1,'search_syear',1, 20, 5);
	print '</td>';
}
// End date
if (! empty($arrayfields['p.datee']['checked']))
{
	print '<td class="liste_titre center">';
	if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="search_eday" value="'.dol_escape_htmltag($search_eday).'">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="search_emonth" value="'.dol_escape_htmltag($search_emonth).'">';
	$formother->select_year($search_eyear?$search_eyear:-1,'search_eyear',1, 20, 5);
	print '</td>';
}
if (! empty($arrayfields['p.public']['checked']))
{
	print '<td class="liste_titre">';
	$array=array(''=>'',0 => $langs->trans("PrivateProject"),1 => $langs->trans("SharedProject"));
	print $form->selectarray('search_public',$array,$search_public);
	print '</td>';
}
if (! empty($arrayfields['p.fk_opp_status']['checked']))
{
	print '<td class="liste_titre nowrap center">';
	print $formproject->selectOpportunityStatus('search_opp_status', $search_opp_status, 1, 0, 1, 0, 'maxwidth100');
	print '</td>';
}
if (! empty($arrayfields['p.opp_amount']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_opp_amount" size="3" value="'.$search_opp_amount.'">';
	print '</td>';
}
if (! empty($arrayfields['p.opp_percent']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_opp_percent" size="2" value="'.$search_opp_percent.'">';
	print '</td>';
}
if (! empty($arrayfields['p.budget_amount']['checked']))
{
	print '<td class="liste_titre nowrap" align="right">';
	print '<input type="text" class="flat" name="search_budget_amount" size="4" value="'.$search_budget_amount.'">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

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
	$arrayofstatus = array();
	foreach($object->statuts_short as $key => $val) $arrayofstatus[$key]=$langs->trans($val);
	$arrayofstatus['99']=$langs->trans("NotClosed").' ('.$langs->trans('Draft').' + '.$langs->trans('Opened').')';
	print $form->selectarray('search_status', $arrayofstatus, $search_status, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100 selectarrowonleft');
	print ajax_combobox('search_status');
	print '</td>';
}
// Action column
print '<td class="liste_titre" align="right">';
$searchpicto=$form->showFilterButtons();
print $searchpicto;
print '</td>';

print '</tr>'."\n";

print '<tr class="liste_titre">';
if (! empty($arrayfields['p.ref']['checked']))           print_liste_field_titre($arrayfields['p.ref']['label'],$_SERVER["PHP_SELF"],"p.ref","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['p.title']['checked']))         print_liste_field_titre($arrayfields['p.title']['label'],$_SERVER["PHP_SELF"],"p.title","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['s.nom']['checked']))           print_liste_field_titre($arrayfields['s.nom']['label'],$_SERVER["PHP_SELF"],"s.nom","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['commercial']['checked']))      print_liste_field_titre($arrayfields['commercial']['label'],$_SERVER["PHP_SELF"],"","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['p.dateo']['checked']))         print_liste_field_titre($arrayfields['p.dateo']['label'],$_SERVER["PHP_SELF"],"p.dateo","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['p.datee']['checked']))         print_liste_field_titre($arrayfields['p.datee']['label'],$_SERVER["PHP_SELF"],"p.datee","",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['p.public']['checked']))        print_liste_field_titre($arrayfields['p.public']['label'],$_SERVER["PHP_SELF"],"p.public","",$param,"",$sortfield,$sortorder);
if (! empty($arrayfields['p.fk_opp_status']['checked'])) print_liste_field_titre($arrayfields['p.fk_opp_status']['label'],$_SERVER["PHP_SELF"],'p.fk_opp_status',"",$param,'align="center"',$sortfield,$sortorder);
if (! empty($arrayfields['p.opp_amount']['checked']))    print_liste_field_titre($arrayfields['p.opp_amount']['label'],$_SERVER["PHP_SELF"],'p.opp_amount',"",$param,'align="right"',$sortfield,$sortorder);
if (! empty($arrayfields['p.opp_percent']['checked']))   print_liste_field_titre($arrayfields['p.opp_percent']['label'],$_SERVER["PHP_SELF"],'p.opp_percent',"",$param,'align="right"',$sortfield,$sortorder);
if (! empty($arrayfields['p.budget_amount']['checked'])) print_liste_field_titre($arrayfields['p.budget_amount']['label'],$_SERVER["PHP_SELF"],'p.budget_amount',"",$param,'align="right"',$sortfield,$sortorder);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters=array('arrayfields'=>$arrayfields);
$reshook=$hookmanager->executeHooks('printFieldListTitle',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (! empty($arrayfields['p.datec']['checked']))  print_liste_field_titre($arrayfields['p.datec']['label'],$_SERVER["PHP_SELF"],"p.datec","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['p.tms']['checked']))    print_liste_field_titre($arrayfields['p.tms']['label'],$_SERVER["PHP_SELF"],"p.tms","",$param,'align="center" class="nowrap"',$sortfield,$sortorder);
if (! empty($arrayfields['p.fk_statut']['checked'])) print_liste_field_titre($arrayfields['p.fk_statut']['label'],$_SERVER["PHP_SELF"],"p.fk_statut","",$param,'align="right"',$sortfield,$sortorder);
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"],"",'','','align="center"',$sortfield,$sortorder,'maxwidthsearch ');
print "</tr>\n";

$i=0;
$totalarray=array();
while ($i < min($num,$limit))
{
	$obj = $db->fetch_object($resql);

	$object->id = $obj->id;
	$object->user_author_id = $obj->fk_user_creat;
	$object->public = $obj->public;
	$object->ref = $obj->ref;
	$object->datee = $db->jdate($obj->date_end);
	$object->statut = $obj->fk_statut;
	$object->opp_status = $obj->fk_opp_status;
	$object->title = $obj->title;

	$userAccess = $object->restrictedProjectArea($user);    // why this ?
	if ($userAccess >= 0)
	{
		print '<tr class="oddeven">';

		// Project url
		if (! empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="nowrap">';
			print $object->getNomUrl(1);
			if ($object->hasDelay()) print img_warning($langs->trans('Late'));
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Title
		if (! empty($arrayfields['p.title']['checked']))
		{
			print '<td class="tdoverflowmax100">';
			print dol_trunc($obj->title,80);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Company
		if (! empty($arrayfields['s.nom']['checked']))
		{
			print '<td class="tdoverflowmax100">';
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
		// Sales Representatives
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
						$userstatic->statut=$val['statut'];
						$userstatic->entity=$val['entity'];
						$userstatic->photo=$val['photo'];
						//print $userstatic->getNomUrl(1, '', 0, 0, 12);
						print $userstatic->getNomUrl(-2);
						$j++;
						if ($j < $nbofsalesrepresentative) print ' ';
					}
				}
				//else print $langs->trans("NoSalesRepresentativeAffected");
			}
			else
			{
				print '&nbsp';
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date start
		if (! empty($arrayfields['p.dateo']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_start),'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date end
		if (! empty($arrayfields['p.datee']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_end),'day');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Visibility
		if (! empty($arrayfields['p.public']['checked']))
		{
			print '<td align="left">';
			if ($obj->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Opp Status
		if (! empty($arrayfields['p.fk_opp_status']['checked']))
		{
			print '<td class="center">';
			if ($obj->opp_status_code) print $langs->trans("OppStatus".$obj->opp_status_code);
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Opp Amount
		if (! empty($arrayfields['p.opp_amount']['checked']))
		{
			print '<td align="right">';
			//if ($obj->opp_status_code)
			if (strcmp($obj->opp_amount,''))
			{
				print price($obj->opp_amount, 1, $langs, 1, -1, -1, '');
				$totalarray['totalopp'] += $obj->opp_amount;
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totaloppfield']=$totalarray['nbfield'];
		}
		// Opp percent
		if (! empty($arrayfields['p.opp_percent']['checked']))
		{
			print '<td align="right">';
			if ($obj->opp_percent) print price($obj->opp_percent, 1, $langs, 1, 0).'%';
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Budget
		if (! empty($arrayfields['p.budget_amount']['checked']))
		{
			print '<td align="right">';
			if ($obj->budget_amount != '')
			{
				print price($obj->budget_amount, 1, $langs, 1, -1, -1);
				$totalarray['totalbudget'] += $obj->budget_amount;
			}
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
			if (! $i) $totalarray['totalbudgetfield']=$totalarray['nbfield'];
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters=array('arrayfields'=>$arrayfields, 'obj'=>$obj);
		$reshook=$hookmanager->executeHooks('printFieldListValue',$parameters);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (! empty($arrayfields['p.datec']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Date modification
		if (! empty($arrayfields['p.tms']['checked']))
		{
			print '<td align="center">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (! $i) $totalarray['nbfield']++;
		}
		// Status
		if (! empty($arrayfields['p.fk_statut']['checked']))
		{
			print '<td align="right">'.$object->getLibStatut(5).'</td>';
			if (! $i) $totalarray['nbfield']++;
		}
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

	}

	$i++;
}

// Show total line
if (isset($totalarray['totaloppfield']) || isset($totalarray['totalbudgetfield']))
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
		elseif ($totalarray['totaloppfield'] == $i) print '<td align="right">'.price($totalarray['totalopp'], 1, $langs, 1, -1, -1).'</td>';
		elseif ($totalarray['totalbudgetfield'] == $i) print '<td align="right">'.price($totalarray['totalbudget'], 1, $langs, 1, -1, -1).'</td>';
		else print '<td></td>';
	}
	print '</tr>';
}

$db->free($resql);

$parameters=array('sql' => $sql);
$reshook=$hookmanager->executeHooks('printFieldListFooter',$parameters);    // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print '</div>';
print "</form>\n";


llxFooter();

$db->close();
