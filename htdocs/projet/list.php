<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Bariley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015 	   Claudio Aschieri     <c.aschieri@19.coop>
 * Copyright (C) 2018 	   Ferran Marcet	    <fmarcet@2byte.es>
 * Copyright (C) 2019 	   Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2020	   Tobias Sean			<tobias.sekan@startmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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

if (!empty($conf->categorie->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies', 'commercial'));

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'projectlist';

$title = $langs->trans("Projects");

// Security check
$socid = (is_numeric($_GET["socid"]) ? $_GET["socid"] : 0);
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
if ($socid > 0)
{
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title .= ' (<a href="list.php">'.$soc->name.'</a>)';
}
if (!$user->rights->projet->lire) accessforbidden();

$diroutputmassaction = $conf->projet->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", "aZ09comma");
$sortorder = GETPOST("sortorder", 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters
if (!$sortfield) $sortfield = "p.ref";
if (!$sortorder) $sortorder = "ASC";
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_all = GETPOST('search_all', 'alphanohtml') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml');
$search_ref = GETPOST("search_ref", 'alpha');
$search_label = GETPOST("search_label", 'alpha');
$search_societe = GETPOST("search_societe", 'alpha');
$search_status = GETPOST("search_status", 'int');
$search_opp_status = GETPOST("search_opp_status", 'alpha');
$search_opp_percent = GETPOST("search_opp_percent", 'alpha');
$search_opp_amount = GETPOST("search_opp_amount", 'alpha');
$search_budget_amount = GETPOST("search_budget_amount", 'alpha');
$search_public = GETPOST("search_public", 'int');
$search_project_user = GETPOST('search_project_user', 'int');
$search_sale = GETPOST('search_sale', 'int');
$search_usage_opportunity = GETPOST('search_usage_opportunity', 'int');
$search_usage_task = GETPOST('search_usage_task', 'int');
$search_usage_bill_time = GETPOST('search_usage_bill_time', 'int');
$optioncss = GETPOST('optioncss', 'alpha');

$mine = $_REQUEST['mode'] == 'mine' ? 1 : 0;
if ($mine) { $search_project_user = $user->id; $mine = 0; }

$search_sday	= GETPOST('search_sday', 'int');
$search_smonth	= GETPOST('search_smonth', 'int');
$search_syear	= GETPOST('search_syear', 'int');
$search_eday	= GETPOST('search_eday', 'int');
$search_emonth	= GETPOST('search_emonth', 'int');
$search_eyear	= GETPOST('search_eyear', 'int');

if ($search_status == '') $search_status = -1; // -1 or 1

if (!empty($conf->categorie->enabled))
{
	$search_category_array = GETPOST("search_category_".Categorie::TYPE_PROJECT."_list", "array");
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Project($db);
$hookmanager->initHooks(array('projectlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array();
foreach ($object->fields as $key => $val) {
	if (empty($val['searchall'])) {
		continue;
	}

	// Don't allow search in private notes for external users when doing "search in all"
	if (!empty($user->socid) && $key == "note_private") {
		continue;
	}

	$fieldstosearchall['p.'.$key] = $val['label'];
}

// Add name object fields to "search in all"
$fieldstosearchall['s.nom'] = "ThirdPartyName";

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = dol_eval($val['visible'], 1);
		$arrayfields['p.'.$key] = array(
			'label'=>$val['label'],
			'checked'=>(($visible < 0) ? 0 : 1),
			'enabled'=>($visible != 3 && dol_eval($val['enabled'], 1)),
			'position'=>$val['position'],
			'help'=>$val['help']
		);
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

// Add none object fields to fields for list
$arrayfields['s.nom'] = array('label'=>$langs->trans("ThirdParty"), 'checked'=>1, 'position'=>21, 'enabled'=>(empty($conf->societe->enabled) ? 0 : 1));
$arrayfields['commercial'] = array('label'=>$langs->trans("SaleRepresentativesOfThirdParty"), 'checked'=>0, 'position'=>23);
$arrayfields['opp_weighted_amount'] = array('label'=>$langs->trans('OpportunityWeightedAmountShort'), 'checked'=>0, 'position'=> 116, 'enabled'=>(empty($conf->global->PROJECT_USE_OPPORTUNITIES) ? 0 : 1), 'position'=>106);

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend' && $massaction != 'confirm_createbills') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$search_all = '';
		$search_ref = "";
		$search_label = "";
		$search_societe = "";
		$search_status = -1;
		$search_opp_status = -1;
		$search_opp_amount = '';
		$search_opp_percent = '';
		$search_budget_amount = '';
		$search_public = "";
		$search_sale = "";
		$search_project_user = '';
		$search_sday = "";
		$search_smonth = "";
		$search_syear = "";
		$search_eday = "";
		$search_emonth = "";
		$search_eyear = "";
		$search_usage_opportunity = '';
		$search_usage_task = '';
		$search_usage_bill_time = '';
		$toselect = '';
		$search_array_options = array();
		$search_category_array = array();
	}


	// Mass actions
	$objectclass = 'Project';
	$objectlabel = 'Project';
	$permissiontoread = $user->rights->projet->lire;
	$permissiontodelete = $user->rights->projet->supprimer;
	$uploaddir = $conf->projet->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	// Close records
	if (!$error && $massaction == 'close' && $user->rights->projet->creer)
	{
		$db->begin();

		$objecttmp = new $objectclass($db);
		$nbok = 0;
		foreach ($toselect as $toselectid)
		{
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0)
			{
				$userWrite = $object->restrictedProjectArea($user, 'write');
				if ($userWrite > 0 && $objecttmp->statut == 1) {
					$result = $objecttmp->setClose($user);
					if ($result <= 0) {
						setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
						$error++;
						break;
					} else $nbok++;
				} elseif ($userWrite <= 0) {
					setEventMessages($langs->trans("DontHavePermissionForCloseProject", $objecttmp->ref), null, 'warnings');
				} else {
					setEventMessages($langs->trans("DontHaveTheValidateStatus", $objecttmp->ref), null, 'warnings');
				}
			} else {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			}
		}

		if (!$error)
		{
			if ($nbok > 1) setEventMessages($langs->trans("RecordsClosed", $nbok), null, 'mesgs');
			else setEventMessages($langs->trans("RecordsClosed", $nbok), null, 'mesgs');
			$db->commit();
		} else {
			$db->rollback();
		}
	}
}


/*
 * View
 */

$socstatic = new Societe($db);
$form = new Form($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);

$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
$title = $langs->trans("Projects");


// Get list of project id allowed to user (in a string list separated by comma)
$projectsListId = '';
if (!$user->rights->projet->all->lire) $projectsListId = $object->getProjectsAuthorizedForUser($user, 0, 1, $socid);

// Get id of types of contacts for projects (This list never contains a lot of elements)
$listofprojectcontacttype = array();
$sql = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
$sql .= " WHERE ctc.element = '".$db->escape($object->element)."'";
$sql .= " AND ctc.source = 'internal'";
$resql = $db->query($sql);
if ($resql)
{
	while ($obj = $db->fetch_object($resql))
	{
		$listofprojectcontacttype[$obj->rowid] = $obj->code;
	}
} else dol_print_error($db);
if (count($listofprojectcontacttype) == 0) $listofprojectcontacttype[0] = '0'; // To avoid sql syntax error if not found

$distinct = 'DISTINCT'; // We add distinct until we are added a protection to be sure a contact of a project and task is only once.
$sql = "SELECT ".$distinct." p.rowid as id, p.ref, p.title, p.fk_statut as status, p.fk_opp_status, p.public, p.fk_user_creat";
$sql .= ", p.datec as date_creation, p.dateo as date_start, p.datee as date_end, p.opp_amount, p.opp_percent, (p.opp_amount*p.opp_percent/100) as opp_weighted_amount, p.tms as date_update, p.budget_amount, p.usage_opportunity, p.usage_task, p.usage_bill_time";
$sql .= ", s.rowid as socid, s.nom as name, s.email";
$sql .= ", cls.code as opp_status_code";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= preg_replace('/^,/', '', $hookmanager->resPrint);
$sql = preg_replace('/,\s*$/', '', $sql);
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as p";
if (!empty($conf->categorie->enabled))
{
	$sql .= Categorie::getFilterJoinQuery(Categorie::TYPE_PROJECT, "p.rowid");
}
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (p.rowid = ef.fk_object)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls on p.fk_opp_status = cls.rowid";
// We'll need this table joined to the select in order to filter by sale
// No check is done on company permission because readability is managed by public status of project and assignement.
//if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
if ($search_sale > 0) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
if ($search_project_user > 0)
{
	$sql .= ", ".MAIN_DB_PREFIX."element_contact as ecp";
}
$sql .= " WHERE p.entity IN (".getEntity('project').')';
if (!empty($conf->categorie->enabled))
{
	$sql .= Categorie::getFilterSelectQuery(Categorie::TYPE_PROJECT, "p.rowid", $search_category_array);
}
if (!$user->rights->projet->all->lire) $sql .= " AND p.rowid IN (".$projectsListId.")"; // public and assigned to, or restricted to company for external users
// No need to check if company is external user, as filtering of projects must be done by getProjectsAuthorizedForUser
if ($socid > 0) $sql .= " AND (p.fk_soc = ".$socid.")"; // This filter if when we use a hard coded filter on company on url (not related to filter for external users)
if ($search_ref) $sql .= natural_search('p.ref', $search_ref);
if ($search_label) $sql .= natural_search('p.title', $search_label);
if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
if ($search_opp_amount) $sql .= natural_search('p.opp_amount', $search_opp_amount, 1);
if ($search_opp_percent) $sql .= natural_search('p.opp_percent', $search_opp_percent, 1);
$sql .= dolSqlDateFilter('p.dateo', $search_sday, $search_smonth, $search_syear);
$sql .= dolSqlDateFilter('p.datee', $search_eday, $search_emonth, $search_eyear);
if ($search_all) $sql .= natural_search(array_keys($fieldstosearchall), $search_all);
if ($search_status >= 0)
{
	if ($search_status == 99) $sql .= " AND p.fk_statut <> 2";
	else $sql .= " AND p.fk_statut = ".$db->escape($search_status);
}
if ($search_opp_status)
{
	if (is_numeric($search_opp_status) && $search_opp_status > 0) $sql .= " AND p.fk_opp_status = ".$db->escape($search_opp_status);
	if ($search_opp_status == 'all') $sql .= " AND (p.fk_opp_status IS NOT NULL AND p.fk_opp_status <> -1)";
	if ($search_opp_status == 'openedopp') $sql .= " AND p.fk_opp_status IS NOT NULL AND p.fk_opp_status <> -1 AND p.fk_opp_status NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_lead_status WHERE code IN ('WON','LOST'))";
	if ($search_opp_status == 'notopenedopp') $sql .= " AND (p.fk_opp_status IS NULL OR p.fk_opp_status = -1 OR p.fk_opp_status IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_lead_status WHERE code IN ('WON')))";
	if ($search_opp_status == 'none') $sql .= " AND (p.fk_opp_status IS NULL OR p.fk_opp_status = -1)";
}
if ($search_public != '') $sql .= " AND p.public = ".$db->escape($search_public);
// For external user, no check is done on company permission because readability is managed by public status of project and assignement.
//if ($socid > 0) $sql.= " AND s.rowid = ".$socid;
if ($search_sale > 0) $sql .= " AND sc.fk_user = ".$search_sale;
// No check is done on company permission because readability is managed by public status of project and assignement.
//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";
if ($search_project_user > 0) $sql .= " AND ecp.fk_c_type_contact IN (".join(',', array_keys($listofprojectcontacttype)).") AND ecp.element_id = p.rowid AND ecp.fk_socpeople = ".$search_project_user;
if ($search_opp_amount != '') $sql .= natural_search('p.opp_amount', $search_opp_amount, 1);
if ($search_budget_amount != '') $sql .= natural_search('p.budget_amount', $search_budget_amount, 1);
if ($search_usage_opportunity != '' && $search_usage_opportunity >= 0) $sql .= natural_search('p.usage_opportunity', $search_usage_opportunity, 2);
if ($search_usage_task != '' && $search_usage_task >= 0)               $sql .= natural_search('p.usage_task', $search_usage_task, 2);
if ($search_usage_bill_time != '' && $search_usage_bill_time >= 0)     $sql .= natural_search('p.usage_bill_time', $search_usage_bill_time, 2);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total of record found is smaller than page * limit, goto and load page 0
		$page = 0;
		$offset = 0;
	}
}
// if total of record found is smaller than limit, no need to do paging and to restart another select with limits set.
if (is_numeric($nbtotalofrecords) && ($limit > $nbtotalofrecords || empty($limit))) {
	$num = $nbtotalofrecords;
} else {
	if ($limit) $sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
		exit;
	}

	$num = $db->num_rows($resql);
}

// Direct jump if only one record found
if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $search_all)
{
	$obj = $db->fetch_object($resql);
	header("Location: ".DOL_URL_ROOT.'/projet/card.php?id='.$obj->id);
	exit;
}


// Output page
// --------------------------------------------------------------------

dol_syslog("list allowed project", LOG_DEBUG);

llxHeader('', $title, $help_url);

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
if ($search_all != '') 			$param .= '&search_all='.urlencode($search_all);
if ($search_sday)              		    $param .= '&search_sday='.urlencode($search_sday);
if ($search_smonth)              		$param .= '&search_smonth='.urlencode($search_smonth);
if ($search_syear)               		$param .= '&search_syear='.urlencode($search_syear);
if ($search_eday)               		$param .= '&search_eday='.urlencode($search_eday);
if ($search_emonth)              		$param .= '&search_emonth='.urlencode($search_emonth);
if ($search_eyear)               		$param .= '&search_eyear='.urlencode($search_eyear);
if ($socid)				        $param .= '&socid='.urlencode($socid);
if ($search_categ)              $param .= '&search_categ='.urlencode($search_categ);
if ($search_ref != '') 			$param .= '&search_ref='.urlencode($search_ref);
if ($search_label != '') 		$param .= '&search_label='.urlencode($search_label);
if ($search_societe != '') 		$param .= '&search_societe='.urlencode($search_societe);
if ($search_status >= 0) 		$param .= '&search_status='.urlencode($search_status);
if ((is_numeric($search_opp_status) && $search_opp_status >= 0) || in_array($search_opp_status, array('all', 'openedopp', 'notopenedopp', 'none'))) 	    $param .= '&search_opp_status='.urlencode($search_opp_status);
if ($search_opp_percent != '') 	$param .= '&search_opp_percent='.urlencode($search_opp_percent);
if ($search_public != '') 		$param .= '&search_public='.urlencode($search_public);
if ($search_project_user != '')   $param .= '&search_project_user='.urlencode($search_project_user);
if ($search_sale > 0)    		$param .= '&search_sale='.urlencode($search_sale);
if ($search_opp_amount != '')    $param .= '&search_opp_amount='.urlencode($search_opp_amount);
if ($search_budget_amount != '') $param .= '&search_budget_amount='.urlencode($search_budget_amount);
if ($optioncss != '') $param .= '&optioncss='.urlencode($optioncss);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
	'generate_doc'=>$langs->trans("ReGeneratePDF"),
	//'builddoc'=>$langs->trans("PDFMerge"),
	//'presend'=>$langs->trans("SendByMail"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if ($user->rights->projet->creer) $arrayofmassactions['close'] = $langs->trans("Close");
if ($user->rights->projet->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$url = DOL_URL_ROOT.'/projet/card.php?action=create';
if (!empty($socid)) $url .= '&socid='.$socid;
$newcardbutton = dolGetButtonTitle($langs->trans('NewProject'), '', 'fa fa-plus-circle', $url, '', $user->rights->projet->creer);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

// Show description of content
$texthelp = '';
if ($search_project_user == $user->id) $texthelp .= $langs->trans("MyProjectsDesc");
else {
	if ($user->rights->projet->all->lire && !$socid) $texthelp .= $langs->trans("ProjectsDesc");
	else $texthelp .= $langs->trans("ProjectsPublicDesc");
}

print_barre_liste($form->textwithpicto($title, $texthelp), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'project', 0, $newcardbutton, '', $limit, 0, 0, 1);


$topicmail = "Information";
$modelmail = "project";
$objecttmp = new Project($db);
$trackid = 'proj'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all)
{
	foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

// Filter on categories
if (!empty($conf->categorie->enabled) && $user->rights->categorie->lire)
{
	$formcategory = new FormCategory($db);
	$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_PROJECT, $search_category_array);
}

// If the user can view user other than himself
$moreforfilter .= '<div class="divsearchfield">';
$moreforfilter .= $langs->trans('ProjectsWithThisUserAsContact').': ';
//$includeonly = 'hierarchyme';
$includeonly = '';
if (empty($user->rights->user->user->lire)) $includeonly = array($user->id);
$moreforfilter .= $form->select_dolusers($search_project_user ? $search_project_user : '', 'search_project_user', 1, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth200');
$moreforfilter .= '</div>';

// If the user can view thirdparties other than his'
if ($user->rights->societe->client->voir || $socid)
{
	$langs->load("commercial");
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('ThirdPartiesOfSaleRepresentative').': ';
	$moreforfilter .= $formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, 1, 'maxwidth200');
	$moreforfilter .= '</div>';
}

if (!empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Project ref
if (!empty($arrayfields['p.ref']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="6">';
	print '</td>';
}
// Project label
if (!empty($arrayfields['p.title']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_label" size="8" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
}
// Third party
if (!empty($arrayfields['s.nom']['checked']))
{
	print '<td class="liste_titre">';
	if ($socid > 0)
	{
		$tmpthirdparty = new Societe($db);
		$tmpthirdparty->fetch($socid);
		$search_societe = $tmpthirdparty->nom;
	}
	print '<input type="text" class="flat" name="search_societe" size="8" value="'.dol_escape_htmltag($search_societe).'">';
	print '</td>';
}
// Sale representative
if (!empty($arrayfields['commercial']['checked']))
{
	print '<td class="liste_titre">&nbsp;</td>';
}
// Start date
if (!empty($arrayfields['p.dateo']['checked']))
{
	print '<td class="liste_titre center nowraponall">';
	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_sday" value="'.dol_escape_htmltag($search_sday).'">';
	print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_smonth" value="'.dol_escape_htmltag($search_smonth).'">';
	$formother->select_year($search_syear ? $search_syear : -1, 'search_syear', 1, 20, 5, 0, 0, '', 'widthauto valignmiddle');
	print '</td>';
}
// End date
if (!empty($arrayfields['p.datee']['checked']))
{
	print '<td class="liste_titre center nowraponall">';
	if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_eday" value="'.dol_escape_htmltag($search_eday).'">';
	print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_emonth" value="'.dol_escape_htmltag($search_emonth).'">';
	$formother->select_year($search_eyear ? $search_eyear : -1, 'search_eyear', 1, 20, 5, 0, 0, '', 'widthauto valignmiddle');
	print '</td>';
}
if (!empty($arrayfields['p.public']['checked']))
{
	print '<td class="liste_titre">';
	$array = array(''=>'', 0 => $langs->trans("PrivateProject"), 1 => $langs->trans("SharedProject"));
	print $form->selectarray('search_public', $array, $search_public);
	print '</td>';
}
// Opp status
if (!empty($arrayfields['p.fk_opp_status']['checked']))
{
	print '<td class="liste_titre nowrap center">';
	print $formproject->selectOpportunityStatus('search_opp_status', $search_opp_status, 1, 0, 1, 0, 'maxwidth100');
	print '</td>';
}
if (!empty($arrayfields['p.opp_amount']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_opp_amount" size="3" value="'.$search_opp_amount.'">';
	print '</td>';
}
if (!empty($arrayfields['p.opp_percent']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_opp_percent" size="2" value="'.$search_opp_percent.'">';
	print '</td>';
}
if (!empty($arrayfields['opp_weighted_amount']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print '</td>';
}
if (!empty($arrayfields['p.budget_amount']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_budget_amount" size="4" value="'.$search_budget_amount.'">';
	print '</td>';
}
if (!empty($arrayfields['p.usage_opportunity']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print $form->selectyesno('search_usage_opportunity', $search_usage_opportunity, 1, false, 1);
	print '';
	print '</td>';
}
if (!empty($arrayfields['p.usage_task']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print $form->selectyesno('search_usage_task', $search_usage_task, 1, false, 1);
	print '</td>';
}
if (!empty($arrayfields['p.usage_bill_time']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	print $form->selectyesno('search_usage_bill_time', $search_usage_bill_time, 1, false, 1);
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['p.datec']['checked']))
{
	// Date creation
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['p.tms']['checked']))
{
	// Date modification
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['p.fk_statut']['checked']))
{
	print '<td class="liste_titre nowrap right">';
	$arrayofstatus = array();
	foreach ($object->statuts_short as $key => $val) $arrayofstatus[$key] = $langs->trans($val);
	$arrayofstatus['99'] = $langs->trans("NotClosed").' ('.$langs->trans('Draft').' + '.$langs->trans('Opened').')';
	print $form->selectarray('search_status', $arrayofstatus, $search_status, 1, 0, 0, '', 0, 0, 0, '', 'minwidth75imp maxwidth150 selectarrowonleft');
	print ajax_combobox('search_status');
	print '</td>';
}
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';

print '</tr>'."\n";

print '<tr class="liste_titre">';
if (!empty($arrayfields['p.ref']['checked']))           print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['p.title']['checked']))         print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER["PHP_SELF"], "p.title", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['s.nom']['checked']))           print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['commercial']['checked']))      print_liste_field_titre($arrayfields['commercial']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder, 'tdoverflowmax100imp ');
if (!empty($arrayfields['p.dateo']['checked']))         print_liste_field_titre($arrayfields['p.dateo']['label'], $_SERVER["PHP_SELF"], "p.dateo", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['p.datee']['checked']))         print_liste_field_titre($arrayfields['p.datee']['label'], $_SERVER["PHP_SELF"], "p.datee", "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['p.public']['checked']))        print_liste_field_titre($arrayfields['p.public']['label'], $_SERVER["PHP_SELF"], "p.public", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['p.fk_opp_status']['checked'])) print_liste_field_titre($arrayfields['p.fk_opp_status']['label'], $_SERVER["PHP_SELF"], 'p.fk_opp_status', "", $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['p.opp_amount']['checked']))    print_liste_field_titre($arrayfields['p.opp_amount']['label'], $_SERVER["PHP_SELF"], 'p.opp_amount', "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['p.opp_percent']['checked']))   print_liste_field_titre($arrayfields['p.opp_percent']['label'], $_SERVER['PHP_SELF'], 'p.opp_percent', "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['opp_weighted_amount']['checked']))   print_liste_field_titre($arrayfields['opp_weighted_amount']['label'], $_SERVER['PHP_SELF'], 'opp_weighted_amount', '', $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['p.budget_amount']['checked'])) print_liste_field_titre($arrayfields['p.budget_amount']['label'], $_SERVER["PHP_SELF"], 'p.budget_amount', "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['p.usage_opportunity']['checked'])) print_liste_field_titre($arrayfields['p.usage_opportunity']['label'], $_SERVER["PHP_SELF"], 'p.usage_opportunity', "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['p.usage_task']['checked']))        print_liste_field_titre($arrayfields['p.usage_task']['label'], $_SERVER["PHP_SELF"], 'p.usage_task', "", $param, '', $sortfield, $sortorder, 'right ');
if (!empty($arrayfields['p.usage_bill_time']['checked']))   print_liste_field_titre($arrayfields['p.usage_bill_time']['label'], $_SERVER["PHP_SELF"], 'p.usage_bill_time', "", $param, '', $sortfield, $sortorder, 'right ');
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['p.datec']['checked']))  print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if (!empty($arrayfields['p.tms']['checked']))    print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if (!empty($arrayfields['p.fk_statut']['checked'])) print_liste_field_titre($arrayfields['p.fk_statut']['label'], $_SERVER["PHP_SELF"], "p.fk_statut", "", $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";

$i = 0;
$totalarray = array(
	'nbfield' => 0,
	'val' => array(),
);
while ($i < min($num, $limit))
{
	$obj = $db->fetch_object($resql);

	$object->id = $obj->id;
	$object->user_author_id = $obj->fk_user_creat;
	$object->public = $obj->public;
	$object->ref = $obj->ref;
	$object->datee = $db->jdate($obj->date_end);
	$object->statut = $obj->status; // deprecated
	$object->status = $obj->status;
	$object->public = $obj->public;
	$object->opp_status = $obj->fk_opp_status;
	$object->title = $obj->title;

	$userAccess = $object->restrictedProjectArea($user); // why this ?
	if ($userAccess >= 0)
	{
		$socstatic->id = $obj->socid;
		$socstatic->name = $obj->name;
		$socstatic->email = $obj->email;

		print '<tr class="oddeven">';

		// Project url
		if (!empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="nowraponall">';
			print $object->getNomUrl(1);
			if ($object->hasDelay()) print img_warning($langs->trans('Late'));
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Title
		if (!empty($arrayfields['p.title']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print dol_trunc($obj->title, 80);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Company
		if (!empty($arrayfields['s.nom']['checked']))
		{
			print '<td class="tdoverflowmax100">';
			if ($obj->socid)
			{
				print $socstatic->getNomUrl(1);
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Sales Representatives
		if (!empty($arrayfields['commercial']['checked']))
		{
			print '<td>';
			if ($obj->socid)
			{
				$socstatic->id = $obj->socid;
				$socstatic->name = $obj->name;
				$listsalesrepresentatives = $socstatic->getSalesRepresentatives($user);
				$nbofsalesrepresentative = count($listsalesrepresentatives);
				if ($nbofsalesrepresentative > 3)   // We print only number
				{
					print $nbofsalesrepresentative;
				} elseif ($nbofsalesrepresentative > 0)
				{
					$userstatic = new User($db);
					$j = 0;
					foreach ($listsalesrepresentatives as $val)
					{
						$userstatic->id = $val['id'];
						$userstatic->lastname = $val['lastname'];
						$userstatic->firstname = $val['firstname'];
						$userstatic->email = $val['email'];
						$userstatic->statut = $val['statut'];
						$userstatic->entity = $val['entity'];
						$userstatic->photo = $val['photo'];
						print $userstatic->getNomUrl(1, '', 0, 0, 12);
						//print $userstatic->getNomUrl(-2);
						$j++;
						if ($j < $nbofsalesrepresentative) print ' ';
					}
				}
				//else print $langs->trans("NoSalesRepresentativeAffected");
			} else {
				print '&nbsp';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date start
		if (!empty($arrayfields['p.dateo']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_start), 'day');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date end
		if (!empty($arrayfields['p.datee']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_end), 'day');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Visibility
		if (!empty($arrayfields['p.public']['checked']))
		{
			print '<td class="left">';
			if ($obj->public) print $langs->trans('SharedProject');
			else print $langs->trans('PrivateProject');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Opp Status
		if (!empty($arrayfields['p.fk_opp_status']['checked']))
		{
			print '<td class="center">';
			if ($obj->opp_status_code) print $langs->trans("OppStatus".$obj->opp_status_code);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Opp Amount
		if (!empty($arrayfields['p.opp_amount']['checked']))
		{
			print '<td class="right">';
			//if ($obj->opp_status_code)
			if (strcmp($obj->opp_amount, ''))
			{
				print price($obj->opp_amount, 1, $langs, 1, -1, -1, '');
				$totalarray['val']['p.opp_amount'] += $obj->opp_amount;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'p.opp_amount';
		}
		// Opp percent
		if (!empty($arrayfields['p.opp_percent']['checked']))
		{
			print '<td class="right">';
			if ($obj->opp_percent) print price($obj->opp_percent, 1, $langs, 1, 0).'%';
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Opp weighted amount
		if (!empty($arrayfields['opp_weighted_amount']['checked']))
		{
			if (!isset($totalarray['val']['opp_weighted_amount']))  $totalarray['val']['opp_weighted_amount'] = 0;
			print '<td align="right">';
			if ($obj->opp_weighted_amount) {
				print price($obj->opp_weighted_amount, 1, $langs, 1, -1, -1, '');
				$totalarray['val']['opp_weighted_amount'] += $obj->opp_weighted_amount;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'opp_weighted_amount';
		}
		// Budget
		if (!empty($arrayfields['p.budget_amount']['checked']))
		{
			print '<td class="right">';
			if ($obj->budget_amount != '')
			{
				print price($obj->budget_amount, 1, $langs, 1, -1, -1);
				$totalarray['val']['p.budget_amount'] += $obj->budget_amount;
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'p.budget_amount';
		}
		// Usage opportunity
		if (!empty($arrayfields['p.usage_opportunity']['checked']))
		{
			print '<td class="right">';
			if ($obj->usage_opportunity)
			{
				print yn($obj->usage_opportunity);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Usage task
		if (!empty($arrayfields['p.usage_task']['checked']))
		{
			print '<td class="right">';
			if ($obj->usage_task)
			{
				print yn($obj->usage_task);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Bill time
		if (!empty($arrayfields['p.usage_bill_time']['checked']))
		{
			print '<td class="right">';
			if ($obj->usage_bill_time)
			{
				print yn($obj->usage_bill_time);
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['p.datec']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['p.tms']['checked']))
		{
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Status
		if (!empty($arrayfields['p.fk_statut']['checked']))
		{
			print '<td class="right">'.$object->getLibStatut(5).'</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Action column
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->id, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";
	}

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

$db->free($resql);

$parameters = array('sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print "</table>\n";
print '</div>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
