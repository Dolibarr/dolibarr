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
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		Benjamin Falière	<benjamin.faliere@altairis.fr>
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
 *	\ingroup    project
 *	\brief      Page to list projects
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies', 'commercial'));
if (isModEnabled('eventorganization') && $conf->eventorganization->enabled) {
	$langs->loadLangs(array('eventorganization'));
}

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOSTINT('show_files');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'projectlist';
$mode = GETPOST('mode', 'alpha');
$groupby = GETPOST('groupby', 'aZ09');	// Example: $groupby = 'p.fk_opp_status' or $groupby = 'p.fk_statut'

$title = $langs->trans("Projects");

// Security check
$socid = GETPOSTINT('socid');
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
if ($socid > 0) {
	$soc = new Societe($db);
	$soc->fetch($socid);
	$title .= ' (<a href="list.php">'.$soc->name.'</a>)';
}
if (!$user->hasRight('projet', 'lire')) {
	accessforbidden();
}

$diroutputmassaction = $conf->project->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
if (!$sortfield) {
	$sortfield = "p.ref";
}
if (!$sortorder) {
	$sortorder = "ASC";
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_all = GETPOST('search_all', 'alphanohtml') ? GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml');
$search_ref = GETPOST("search_ref", 'alpha');
$search_label = GETPOST("search_label", 'alpha');
$search_societe = GETPOST("search_societe", 'alpha');
$search_societe_alias = GETPOST("search_societe_alias", 'alpha');
$search_societe_country = GETPOST("search_societe_country", 'alpha');
$search_opp_status = GETPOST("search_opp_status", 'alpha');
$search_opp_percent = GETPOST("search_opp_percent", 'alpha');
$search_opp_amount = GETPOST("search_opp_amount", 'alpha');
$search_budget_amount = GETPOST("search_budget_amount", 'alpha');
$search_public = GETPOST("search_public", 'intcomma');
$search_project_user = GETPOSTINT('search_project_user');
$search_project_contact = GETPOSTINT('search_project_contact');
$search_sale = GETPOSTINT('search_sale');
$search_usage_opportunity = GETPOST('search_usage_opportunity', 'intcomma');
$search_usage_task = GETPOST('search_usage_task', 'intcomma');
$search_usage_bill_time = GETPOST('search_usage_bill_time', 'intcomma');
$search_usage_event_organization = GETPOST('search_usage_event_organization', 'intcomma');
$search_accept_conference_suggestions = GETPOST('search_accept_conference_suggestions', 'intcomma');
$search_accept_booth_suggestions = GETPOST('search_accept_booth_suggestions', 'intcomma');
$search_price_registration = GETPOST("search_price_registration", 'alpha');
$search_price_booth = GETPOST("search_price_booth", 'alpha');
$search_login = GETPOST('search_login', 'alpha');
$search_import_key = GETPOST('search_import_key', 'alpha');
$searchCategoryCustomerOperator = 0;
if (GETPOSTISSET('formfilteraction')) {
	$searchCategoryCustomerOperator = GETPOSTINT('search_category_customer_operator');
} elseif (getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT')) {
	$searchCategoryCustomerOperator = getDolGlobalString('MAIN_SEARCH_CAT_OR_BY_DEFAULT');
}
$searchCategoryCustomerList = GETPOST('search_category_customer_list', 'array');
if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
	$search_omitChildren = GETPOST('search_omitChildren', 'alpha') == 'on' ? 1 : 0;
}


$mine = ((GETPOST('mode') == 'mine') ? 1 : 0);
if ($mine) {
	$search_project_user = $user->id;
	$mine = 0;
}

$search_sday	= GETPOSTINT('search_sday');
$search_smonth	= GETPOSTINT('search_smonth');
$search_syear	= GETPOSTINT('search_syear');
$search_eday	= GETPOSTINT('search_eday');
$search_emonth	= GETPOSTINT('search_emonth');
$search_eyear	= GETPOSTINT('search_eyear');

$search_date_start_startmonth = GETPOSTINT('search_date_start_startmonth');
$search_date_start_startyear = GETPOSTINT('search_date_start_startyear');
$search_date_start_startday = GETPOSTINT('search_date_start_startday');
$search_date_start_start = dol_mktime(0, 0, 0, $search_date_start_startmonth, $search_date_start_startday, $search_date_start_startyear);	// Use tzserver
$search_date_start_endmonth = GETPOSTINT('search_date_start_endmonth');
$search_date_start_endyear = GETPOSTINT('search_date_start_endyear');
$search_date_start_endday = GETPOSTINT('search_date_start_endday');
$search_date_start_end = dol_mktime(23, 59, 59, $search_date_start_endmonth, $search_date_start_endday, $search_date_start_endyear);	// Use tzserver

$search_date_end_startmonth = GETPOSTINT('search_date_end_startmonth');
$search_date_end_startyear = GETPOSTINT('search_date_end_startyear');
$search_date_end_startday = GETPOSTINT('search_date_end_startday');
$search_date_end_start = dol_mktime(0, 0, 0, $search_date_end_startmonth, $search_date_end_startday, $search_date_end_startyear);	// Use tzserver
$search_date_end_endmonth = GETPOSTINT('search_date_end_endmonth');
$search_date_end_endyear = GETPOSTINT('search_date_end_endyear');
$search_date_end_endday = GETPOSTINT('search_date_end_endday');
$search_date_end_end = dol_mktime(23, 59, 59, $search_date_end_endmonth, $search_date_end_endday, $search_date_end_endyear);	// Use tzserver

$search_date_creation_startmonth = GETPOSTINT('search_date_creation_startmonth');
$search_date_creation_startyear = GETPOSTINT('search_date_creation_startyear');
$search_date_creation_startday = GETPOSTINT('search_date_creation_startday');
$search_date_creation_start = dol_mktime(0, 0, 0, $search_date_creation_startmonth, $search_date_creation_startday, $search_date_creation_startyear);	// Use tzserver
$search_date_creation_endmonth = GETPOSTINT('search_date_creation_endmonth');
$search_date_creation_endyear = GETPOSTINT('search_date_creation_endyear');
$search_date_creation_endday = GETPOSTINT('search_date_creation_endday');
$search_date_creation_end = dol_mktime(23, 59, 59, $search_date_creation_endmonth, $search_date_creation_endday, $search_date_creation_endyear);	// Use tzserver

$search_date_modif_startmonth = GETPOSTINT('search_date_modif_startmonth');
$search_date_modif_startyear = GETPOSTINT('search_date_modif_startyear');
$search_date_modif_startday = GETPOSTINT('search_date_modif_startday');
$search_date_modif_start = dol_mktime(0, 0, 0, $search_date_modif_startmonth, $search_date_modif_startday, $search_date_modif_startyear);	// Use tzserver
$search_date_modif_endmonth = GETPOSTINT('search_date_modif_endmonth');
$search_date_modif_endyear = GETPOSTINT('search_date_modif_endyear');
$search_date_modif_endday = GETPOSTINT('search_date_modif_endday');
$search_date_modif_end = dol_mktime(23, 59, 59, $search_date_modif_endmonth, $search_date_modif_endday, $search_date_modif_endyear);	// Use tzserver

$search_category_array = array();

if (isModEnabled('category')) {
	$search_category_array = GETPOST("search_category_".Categorie::TYPE_PROJECT."_list", "array");
}

if (GETPOSTISARRAY('search_status') || GETPOST('search_status_multiselect')) {
	$search_status = implode(',', GETPOST('search_status', 'array:intcomma'));
} else {
	$search_status = (GETPOST('search_status', 'intcomma') != '' ? GETPOST('search_status', 'intcomma') : '0,1');
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
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
$fieldstosearchall['s.name_alias'] = "AliasNameShort";
$fieldstosearchall['s.code_client'] = "CustomerCode";

// Definition of array of fields for columns
$arrayfields = array();
foreach ($object->fields as $key => $val) {
	// If $val['visible']==0, then we never show the field
	if (!empty($val['visible'])) {
		$visible = (int) dol_eval($val['visible'], 1, 1, '1');
		$arrayfields['p.'.$key] = array(
			'label' => $val['label'],
			'checked' => (($visible < 0) ? 0 : 1),
			'enabled' => (abs($visible) != 3 && (int) dol_eval($val['enabled'], 1, 1, '1')),
			'position' => $val['position'],
			'help' => isset($val['help']) ? $val['help'] : ''
		);
	}
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

// Add non object fields to fields for list
$arrayfields['s.nom'] = array('label' => $langs->trans("ThirdParty"), 'checked' => 1, 'position' => 21, 'enabled' => (!isModEnabled('societe') ? 0 : 1));
$arrayfields['s.name_alias'] = array('label' => "AliasNameShort", 'checked' => 0, 'position' => 22);
$arrayfields['co.country_code'] = array('label' => "Country", 'checked' => -1, 'position' => 23);
$arrayfields['commercial'] = array('label' => $langs->trans("SaleRepresentativesOfThirdParty"), 'checked' => 0, 'position' => 25);
$arrayfields['c.assigned'] = array('label' => $langs->trans("AssignedTo"), 'checked' => 1, 'position' => 120);
$arrayfields['opp_weighted_amount'] = array('label' => $langs->trans('OpportunityWeightedAmountShort'), 'checked' => 0, 'enabled' => (!getDolGlobalString('PROJECT_USE_OPPORTUNITIES') ? 0 : 1), 'position' => 106);
$arrayfields['u.login'] = array('label' => "Author", 'checked' => -1, 'position' => 165);
// Force some fields according to search_usage filter...
if (GETPOST('search_usage_opportunity')) {
	//$arrayfields['p.usage_opportunity']['visible'] = 1;	// Not require, filter on search_opp_status is enough
	//$arrayfields['p.usage_opportunity']['checked'] = 1;	// Not require, filter on search_opp_status is enough
}
if (GETPOST('search_usage_event_organization')) {
	$arrayfields['p.fk_opp_status']['enabled'] = 0;
	$arrayfields['p.opp_amount']['enabled'] = 0;
	$arrayfields['p.opp_percent']['enabled'] = 0;
	$arrayfields['opp_weighted_amount']['enabled'] = 0;
	$arrayfields['p.usage_organize_event']['visible'] = 1;
	$arrayfields['p.usage_organize_event']['checked'] = 1;
}
$arrayfields['p.fk_project']['enabled'] = 0;

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked?:int<0,1>,position?:int,help?:string}> $arrayfields';  // dol_sort_array looses type for Phan

// Add a groupby field. Set $groupby and $groupbyvalues.
// TODO Move this into a inc file
$groupbyvalues = array();
$groupofcollpasedvalues = array();
if ($mode == 'kanban' && $groupby) {
	$groupbyold = null;
	$groupbyfield = preg_replace('/[a-z]\./', '', $groupby);
	if (!empty($object->fields[$groupbyfield]['alias'])) {
		$groupbyfield = $object->fields[$groupbyfield]['alias'];
	}
	if (!in_array(preg_replace('/[a-z]\./', '', $groupby), array_keys($object->fields))) {
		$groupby = '';
	} else {
		if (!empty($object->fields[$groupby]['arrayofkeyval'])) {
			$groupbyvalues = $object->fields[$groupby]['arrayofkeyval'];
		} elseif (!empty($object->fields[preg_replace('/[a-z]\./', '', $groupby)]['arrayofkeyval'])) {
			$groupbyvalues = $object->fields[preg_replace('/[a-z]\./', '', $groupby)]['arrayofkeyval'];
		} else {
			// var_dump($object->fields[$groupby]['type']);
			// If type is 'integer:Object:classpath', for example "integer:CLeadStatus:core/class/cleadstatus.class.php"
			// TODO
			// $groupbyvalues = ...

			$sql = "SELECT cls.rowid, cls.code, cls.percent, cls.label";
			$sql .= " FROM ".MAIN_DB_PREFIX."c_lead_status as cls";
			$sql .= " WHERE active = 1";
			//$sql .= " AND cls.code <> 'LOST'";
			//$sql .= " AND cls.code <> 'WON'";
			$sql .= $db->order('cls.rowid', 'ASC');	// Must use the same order key than the key in $groupby
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;

				while ($i < $num) {
					$objp = $db->fetch_object($resql);
					$groupbyvalues[$objp->rowid] = $objp->label;
					$i++;
				}
			}

			$groupofcollpasedvalues = array(6,7);	// LOST and WON
		}
		//var_dump($groupbyvalues);
	}
	// Add a filter on the group by if not yet included first
	if ($groupby && !preg_match('/^'.preg_quote($db->sanitize($groupby), '/').'/', $sortfield)) {
		//var_dump($arrayfields);
		$sortfield = $db->sanitize($groupby).($sortfield ? ",".$sortfield : "");
		$sortorder = "ASC".($sortfield ? ",".$sortorder : "");
	}
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('socid' => $socid, 'arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_all = '';
		$search_ref = "";
		$search_label = "";
		$search_societe = "";
		$search_societe_alias = '';
		$search_societe_country = '';
		$search_status = -1;
		$search_opp_status = -1;
		$search_opp_amount = '';
		$search_opp_percent = '';
		$search_budget_amount = '';
		$search_public = "";
		$search_sale = "";
		$search_project_user = '';
		$search_project_contact = '';
		$search_sday = "";
		$search_smonth = "";
		$search_syear = "";
		$search_eday = "";
		$search_emonth = "";
		$search_eyear = "";
		$search_date_start_startmonth = "";
		$search_date_start_startyear = "";
		$search_date_start_startday = "";
		$search_date_start_start = "";
		$search_date_start_endmonth = "";
		$search_date_start_endyear = "";
		$search_date_start_endday = "";
		$search_date_start_end = "";
		$search_date_end_startmonth = "";
		$search_date_end_startyear = "";
		$search_date_end_startday = "";
		$search_date_end_start = "";
		$search_date_end_endmonth = "";
		$search_date_end_endyear = "";
		$search_date_end_endday = "";
		$search_date_end_end = "";
		$search_date_creation_startmonth = "";
		$search_date_creation_startyear = "";
		$search_date_creation_startday = "";
		$search_date_creation_start = "";
		$search_date_creation_endmonth = "";
		$search_date_creation_endyear = "";
		$search_date_creation_endday = "";
		$search_date_creation_end = "";
		$search_date_modif_startmonth = "";
		$search_date_modif_startyear = "";
		$search_date_modif_startday = "";
		$search_date_modif_start = "";
		$search_date_modif_endmonth = "";
		$search_date_modif_endyear = "";
		$search_date_modif_endday = "";
		$search_date_modif_end = "";
		$search_usage_opportunity = '';
		$search_usage_task = '';
		$search_usage_bill_time = '';
		$search_usage_event_organization = '';
		$search_accept_conference_suggestions = '';
		$search_accept_booth_suggestions = '';
		$search_price_registration = '';
		$search_price_booth = '';
		$search_login = '';
		$search_import_key = '';
		$toselect = array();
		$search_array_options = array();
		$search_category_array = array();
	}


	// Mass actions
	$objectclass = 'Project';
	$objectlabel = 'Project';
	$permissiontoread = $user->hasRight('projet', 'lire');
	$permissiontodelete = $user->hasRight('projet', 'supprimer');
	$permissiontoadd = $user->hasRight('projet', 'creer');
	$uploaddir = $conf->project->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

	// Close records
	if (!$error && $massaction == 'close' && $user->hasRight('projet', 'creer')) {
		$db->begin();

		$objecttmp = new $objectclass($db);
		$nbok = 0;
		foreach ($toselect as $toselectid) {
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$userWrite = $object->restrictedProjectArea($user, 'write');
				if ($userWrite > 0 && $objecttmp->statut == 1) {
					$result = $objecttmp->setClose($user);
					if ($result <= 0) {
						setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
						$error++;
						break;
					} else {
						$nbok++;
					}
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

		if (!$error) {
			setEventMessages($langs->trans("RecordsClosed", $nbok), null, 'mesgs');
			$db->commit();
		} else {
			$db->rollback();
		}
	}
}


/*
 * View
 */

unset($_SESSION['pageforbacktolist']['project']);

$form = new Form($db);
$formcompany = new FormCompany($db);

$now = dol_now();

$companystatic = new Societe($db);
$taskstatic = new Task($db);
$formother = new FormOther($db);
$formproject = new FormProjets($db);
$userstatic = new User($db);

$help_url = "EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos";
$title = $langs->trans("LeadsOrProjects");
if (!getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
	$title = $langs->trans("Projects");
}
if (getDolGlobalInt('PROJECT_USE_OPPORTUNITIES') == 2) {	// 2 = leads only
	$title = $langs->trans("Leads");
}
$morejs = array();
$morecss = array();


// Get list of project id allowed to user (in a string list separated by comma)
$projectsListId = '';
if (!$user->hasRight('projet', 'all', 'lire')) {
	$projectsListId = $object->getProjectsAuthorizedForUser($user, 0, 1, $socid);
}

// Get id of types of contacts for projects (This list never contains a lot of elements)
$listofprojectcontacttype = array();
$listofprojectcontacttypeexternal = array();
$sql = "SELECT ctc.rowid, ctc.code, ctc.source FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
$sql .= " WHERE ctc.element = '".$db->escape($object->element)."'";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		if ($obj->source == 'internal') {
			$listofprojectcontacttype[$obj->rowid] = $obj->code;
		} else {
			$listofprojectcontacttypeexternal[$obj->rowid] = $obj->code;
		}
	}
} else {
	dol_print_error($db);
}
if (count($listofprojectcontacttype) == 0) {
	$listofprojectcontacttype[0] = '0'; // To avoid sql syntax error if not found
}
if (count($listofprojectcontacttypeexternal) == 0) {
	$listofprojectcontacttypeexternal[0] = '0'; // To avoid sql syntax error if not found
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$sql = "SELECT p.rowid as id, p.ref, p.title, p.fk_statut as status, p.fk_opp_status, p.public, p.fk_user_creat,";
$sql .= " p.datec as date_creation, p.dateo as date_start, p.datee as date_end, p.opp_amount, p.opp_percent, (p.opp_amount * p.opp_percent / 100) as opp_weighted_amount, p.tms as date_modification, p.budget_amount,";
$sql .= " p.usage_opportunity, p.usage_task, p.usage_bill_time, p.usage_organize_event,";
$sql .= " p.email_msgid, p.import_key,";
$sql .= " p.accept_conference_suggestions, p.accept_booth_suggestions, p.price_registration, p.price_booth,";
$sql .= " s.rowid as socid, s.nom as name, s.name_alias as alias, s.email, s.email, s.phone, s.fax, s.address, s.town, s.zip, s.fk_pays, s.client, s.code_client,";
$sql .= " country.code as country_code,";
$sql .= " cls.code as opp_status_code,";
$sql .= ' u.login, u.lastname, u.firstname, u.email as user_email, u.statut as user_statut, u.entity, u.photo, u.office_phone, u.office_fax, u.user_mobile, u.job, u.gender';
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as p";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (p.rowid = ef.fk_object)";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as country on country.rowid = s.fk_pays";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_lead_status as cls on p.fk_opp_status = cls.rowid";
$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'user AS u ON p.fk_user_creat = u.rowid';
// We'll need this table joined to the select in order to filter by sale
// No check is done on company permission because readability is managed by public status of project and assignment.
//if ($search_sale > 0 || (! $user->rights->societe->client->voir && ! $socid)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";

$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " WHERE p.entity IN (".getEntity('project', (GETPOSTINT('search_current_entity') ? 0 : 1)).')';
if (!$user->hasRight('projet', 'all', 'lire')) {
	$sql .= " AND p.rowid IN (".$db->sanitize($projectsListId).")"; // public and assigned to, or restricted to company for external users
}
// No need to check if company is external user, as filtering of projects must be done by getProjectsAuthorizedForUser
if ($socid > 0) {
	$sql .= " AND (p.fk_soc = ".((int) $socid).")"; // This filter if when we use a hard coded filter on company on url (not related to filter for external users)
}
if ($search_ref) {
	$sql .= natural_search('p.ref', $search_ref);
}
if ($search_label) {
	$sql .= natural_search('p.title', $search_label);
}
if (empty($arrayfields['s.name_alias']['checked']) && $search_societe) {
	$sql .= natural_search(array("s.nom", "s.name_alias"), $search_societe);
} else {
	if ($search_societe) {
		$sql .= natural_search('s.nom', $search_societe);
	}
	if ($search_societe_alias) {
		$sql .= natural_search('s.name_alias', $search_societe_alias);
	}
}
if ($search_societe_country) {
	$sql .= natural_search('country.code', $search_societe_country);
}
if ($search_opp_amount) {
	$sql .= natural_search('p.opp_amount', $search_opp_amount, 1);
}
if ($search_opp_percent) {
	$sql .= natural_search('p.opp_percent', $search_opp_percent, 1);
}
$sql .= dolSqlDateFilter('p.dateo', $search_sday, $search_smonth, $search_syear);
$sql .= dolSqlDateFilter('p.datee', $search_eday, $search_emonth, $search_eyear);

if ($search_date_start_start) {
	$sql .= " AND p.dateo >= '".$db->idate($search_date_start_start)."'";
}
if ($search_date_start_end) {
	$sql .= " AND p.dateo <= '".$db->idate($search_date_start_end)."'";
}

if ($search_date_end_start) {
	$sql .= " AND p.datee >= '".$db->idate($search_date_end_start)."'";
}
if ($search_date_end_end) {
	$sql .= " AND p.datee <= '".$db->idate($search_date_end_end)."'";
}

if ($search_date_creation_start) {
	$sql .= " AND p.datec >= '".$db->idate($search_date_creation_start)."'";
}
if ($search_date_creation_end) {
	$sql .= " AND p.datec <= '".$db->idate($search_date_creation_end)."'";
}

if ($search_date_modif_start) {
	$sql .= " AND p.tms >= '".$db->idate($search_date_modif_start)."'";
}
if ($search_date_modif_end) {
	$sql .= " AND p.tms <= '".$db->idate($search_date_modif_end)."'";
}

if ($search_all) {
	$sql .= natural_search(array_keys($fieldstosearchall), $search_all);
}
if ($search_status != '' && $search_status != '-1') {
	if ($search_status == 99) {
		$sql .= " AND p.fk_statut IN (0,1)";
	} else {
		$sql .= " AND p.fk_statut IN (".$db->sanitize($db->escape($search_status)).")";
	}
}
if ($search_opp_status) {
	if (is_numeric($search_opp_status) && $search_opp_status > 0) {
		$sql .= " AND p.fk_opp_status = ".((int) $search_opp_status);
	}
	if ($search_opp_status == 'all') {
		$sql .= " AND (p.fk_opp_status IS NOT NULL AND p.fk_opp_status <> -1)";
	}
	if ($search_opp_status == 'openedopp') {
		$sql .= " AND p.fk_opp_status IS NOT NULL AND p.fk_opp_status <> -1 AND p.fk_opp_status NOT IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_lead_status WHERE code IN ('WON','LOST'))";
	}
	if ($search_opp_status == 'notopenedopp') {
		$sql .= " AND (p.fk_opp_status IS NULL OR p.fk_opp_status = -1 OR p.fk_opp_status IN (SELECT rowid FROM ".MAIN_DB_PREFIX."c_lead_status WHERE code = 'WON'))";
	}
	if ($search_opp_status == 'none') {
		$sql .= " AND (p.fk_opp_status IS NULL OR p.fk_opp_status = -1)";
	}
}
if ($search_public != '') {
	$sql .= " AND p.public = ".((int) $search_public);
}
// No check is done on company permission because readability is managed by public status of project and assignment.
//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id).") OR (s.rowid IS NULL))";
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
if ($search_project_user > 0) {
	$sql .= " AND EXISTS (SELECT ecp.rowid FROM ".MAIN_DB_PREFIX."element_contact as ecp WHERE ecp.fk_c_type_contact IN (".$db->sanitize(implode(',', array_keys($listofprojectcontacttype))).") AND ecp.element_id = p.rowid AND ecp.fk_socpeople = ".((int) $search_project_user).")";
}
if ($search_project_contact > 0) {
	$sql .= " AND EXISTS (SELECT ecp_contact.rowid FROM ".MAIN_DB_PREFIX."element_contact as ecp_contact WHERE ecp_contact.fk_c_type_contact IN (".$db->sanitize(implode(',', array_keys($listofprojectcontacttypeexternal))).") AND ecp_contact.element_id = p.rowid AND ecp_contact.fk_socpeople = ".((int) $search_project_contact).")";
}
if ($search_opp_amount != '') {
	$sql .= natural_search('p.opp_amount', $search_opp_amount, 1);
}
if ($search_budget_amount != '') {
	$sql .= natural_search('p.budget_amount', $search_budget_amount, 1);
}
if ($search_usage_opportunity != '' && $search_usage_opportunity >= 0) {
	$sql .= natural_search('p.usage_opportunity', $search_usage_opportunity, 2);
}
if ($search_usage_task != '' && $search_usage_task >= 0) {
	$sql .= natural_search('p.usage_task', $search_usage_task, 2);
}
if ($search_usage_bill_time != '' && $search_usage_bill_time >= 0) {
	$sql .= natural_search('p.usage_bill_time', $search_usage_bill_time, 2);
}
if ($search_usage_event_organization != '' && $search_usage_event_organization >= 0) {
	$sql .= natural_search('p.usage_organize_event', $search_usage_event_organization, 2);
}
if ($search_accept_conference_suggestions != '' && $search_accept_conference_suggestions >= 0) {
	$sql .= natural_search('p.accept_conference_suggestions', $search_accept_conference_suggestions, 2);
}
if ($search_accept_booth_suggestions != '' && $search_accept_booth_suggestions >= 0) {
	$sql .= natural_search('p.accept_booth_suggestions', $search_accept_booth_suggestions, 2);
}
if ($search_price_registration != '') {
	$sql .= natural_search('p.price_registration', $search_price_registration, 1);
}
if ($search_price_booth != '') {
	$sql .= natural_search('p.price_booth', $search_price_booth, 1);
}
if ($search_login) {
	$sql .= natural_search(array('u.login', 'u.firstname', 'u.lastname'), $search_login);
}
if ($search_import_key) {
	$sql .= natural_search(array('p.import_key'), $search_import_key);
}
if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
	if ($search_omitChildren == 1) {
		$sql .= " AND p.fk_project IS NULL";
	}
}

// Search for tag/category ($searchCategoryProjectList is an array of ID)
$searchCategoryProjectList = $search_category_array;
$searchCategoryProjectOperator = 0;
if (!empty($searchCategoryProjectList)) {
	$searchCategoryProjectSqlList = array();
	$listofcategoryid = '';
	foreach ($searchCategoryProjectList as $searchCategoryProject) {
		if (intval($searchCategoryProject) == -2) {
			$searchCategoryProjectSqlList[] = "NOT EXISTS (SELECT ck.fk_project FROM ".MAIN_DB_PREFIX."categorie_project as ck WHERE p.rowid = ck.fk_project)";
		} elseif (intval($searchCategoryProject) > 0) {
			if ($searchCategoryProjectOperator == 0) {
				$searchCategoryProjectSqlList[] = " EXISTS (SELECT ck.fk_project FROM ".MAIN_DB_PREFIX."categorie_project as ck WHERE p.rowid = ck.fk_project AND ck.fk_categorie = ".((int) $searchCategoryProject).")";
			} else {
				$listofcategoryid .= ($listofcategoryid ? ', ' : '') .((int) $searchCategoryProject);
			}
		}
	}
	if ($listofcategoryid) {
		$searchCategoryProjectSqlList[] = " EXISTS (SELECT ck.fk_project FROM ".MAIN_DB_PREFIX."categorie_project as ck WHERE p.rowid = ck.fk_project AND ck.fk_categorie IN (".$db->sanitize($listofcategoryid)."))";
	}
	if ($searchCategoryProjectOperator == 1) {
		if (!empty($searchCategoryProjectSqlList)) {
			$sql .= " AND (".implode(' OR ', $searchCategoryProjectSqlList).")";
		}
	} else {
		if (!empty($searchCategoryProjectSqlList)) {
			$sql .= " AND (".implode(' AND ', $searchCategoryProjectSqlList).")";
		}
	}
}
$searchCategoryCustomerSqlList = array();
if ($searchCategoryCustomerOperator == 1) {
	$existsCategoryCustomerList = array();
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$sqlCategoryCustomerNotExists  = " NOT EXISTS (";
			$sqlCategoryCustomerNotExists .= " SELECT cat_cus.fk_soc";
			$sqlCategoryCustomerNotExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
			$sqlCategoryCustomerNotExists .= " WHERE cat_cus.fk_soc = p.fk_soc";
			$sqlCategoryCustomerNotExists .= " )";
			$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerNotExists;
		} elseif (intval($searchCategoryCustomer) > 0) {
			$existsCategoryCustomerList[] = $db->escape($searchCategoryCustomer);
		}
	}
	if (!empty($existsCategoryCustomerList)) {
		$sqlCategoryCustomerExists = " EXISTS (";
		$sqlCategoryCustomerExists .= " SELECT cat_cus.fk_soc";
		$sqlCategoryCustomerExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
		$sqlCategoryCustomerExists .= " WHERE cat_cus.fk_soc = p.fk_soc";
		$sqlCategoryCustomerExists .= " AND cat_cus.fk_categorie IN (".$db->sanitize(implode(',', $existsCategoryCustomerList)).")";
		$sqlCategoryCustomerExists .= " )";
		$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerExists;
	}
	if (!empty($searchCategoryCustomerSqlList)) {
		$sql .= " AND (".implode(' OR ', $searchCategoryCustomerSqlList).")";
	}
} else {
	foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
		if (intval($searchCategoryCustomer) == -2) {
			$sqlCategoryCustomerNotExists = " NOT EXISTS (";
			$sqlCategoryCustomerNotExists .= " SELECT cat_cus.fk_soc";
			$sqlCategoryCustomerNotExists .= " FROM ".$db->prefix()."categorie_societe AS cat_cus";
			$sqlCategoryCustomerNotExists .= " WHERE cat_cus.fk_soc = p.fk_soc";
			$sqlCategoryCustomerNotExists .= " )";
			$searchCategoryCustomerSqlList[] = $sqlCategoryCustomerNotExists;
		} elseif (intval($searchCategoryCustomer) > 0) {
			$searchCategoryCustomerSqlList[] = "p.fk_soc IN (SELECT fk_soc FROM ".$db->prefix()."categorie_societe WHERE fk_categorie = ".((int) $searchCategoryCustomer).")";
		}
	}
	if (!empty($searchCategoryCustomerSqlList)) {
		$sql .= " AND (".implode(' AND ', $searchCategoryCustomerSqlList).")";
	}
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
//print $sql;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}
//print $sql;

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// Direct jump if only one record found
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && $search_all && !$page) {
	$obj = $db->fetch_object($resql);
	header("Location: ".DOL_URL_ROOT.'/projet/card.php?id='.$obj->id);
	exit;
}


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url);

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($groupby != '') {
	$param .= '&groupby='.urlencode($groupby);
}

if ($socid) {
	$param .= '&socid='.urlencode((string) $socid);
}
if ($search_all != '') {
	$param .= '&search_all='.urlencode($search_all);
}
if ($search_sday) {
	$param .= '&search_sday='.urlencode((string) ($search_sday));
}
if ($search_smonth) {
	$param .= '&search_smonth='.urlencode((string) ($search_smonth));
}
if ($search_syear) {
	$param .= '&search_syear='.urlencode((string) ($search_syear));
}
if ($search_eday) {
	$param .= '&search_eday='.urlencode((string) ($search_eday));
}
if ($search_emonth) {
	$param .= '&search_emonth='.urlencode((string) ($search_emonth));
}
if ($search_eyear) {
	$param .= '&search_eyear='.urlencode((string) ($search_eyear));
}
if ($search_date_start_startmonth) {
	$param .= '&search_date_start_startmonth='.urlencode((string) ($search_date_start_startmonth));
}
if ($search_date_start_startyear) {
	$param .= '&search_date_start_startyear='.urlencode((string) ($search_date_start_startyear));
}
if ($search_date_start_startday) {
	$param .= '&search_date_start_startday='.urlencode((string) ($search_date_start_startday));
}
if ($search_date_start_start) {
	$param .= '&search_date_start_start='.urlencode($search_date_start_start);
}
if ($search_date_start_endmonth) {
	$param .= '&search_date_start_endmonth='.urlencode((string) ($search_date_start_endmonth));
}
if ($search_date_start_endyear) {
	$param .= '&search_date_start_endyear='.urlencode((string) ($search_date_start_endyear));
}
if ($search_date_start_endday) {
	$param .= '&search_date_start_endday='.urlencode((string) ($search_date_start_endday));
}
if ($search_date_start_end) {
	$param .= '&search_date_start_end='.urlencode($search_date_start_end);
}
if ($search_date_end_startmonth) {
	$param .= '&search_date_end_startmonth='.urlencode((string) ($search_date_end_startmonth));
}
if ($search_date_end_startyear) {
	$param .= '&search_date_end_startyear='.urlencode((string) ($search_date_end_startyear));
}
if ($search_date_end_startday) {
	$param .= '&search_date_end_startday='.urlencode((string) ($search_date_end_startday));
}
if ($search_date_end_start) {
	$param .= '&search_date_end_start='.urlencode($search_date_end_start);
}
if ($search_date_end_endmonth) {
	$param .= '&search_date_end_endmonth='.urlencode((string) ($search_date_end_endmonth));
}
if ($search_date_end_endyear) {
	$param .= '&search_date_end_endyear='.urlencode((string) ($search_date_end_endyear));
}
if ($search_date_end_endday) {
	$param .= '&search_date_end_endday='.urlencode((string) ($search_date_end_endday));
}
if ($search_date_end_end) {
	$param .= '&search_date_end_end=' . urlencode($search_date_end_end);
}
if ($search_date_creation_startmonth) {
	$param .= '&search_date_creation_startmonth='.urlencode((string) ($search_date_creation_startmonth));
}
if ($search_date_creation_startyear) {
	$param .= '&search_date_creation_startyear='.urlencode((string) ($search_date_creation_startyear));
}
if ($search_date_creation_startday) {
	$param .= '&search_date_creation_startday='.urlencode((string) ($search_date_creation_startday));
}
if ($search_date_creation_start) {
	$param .= '&search_date_creation_start='.urlencode($search_date_creation_start);
}
if ($search_date_creation_endmonth) {
	$param .= '&search_date_creation_endmonth='.urlencode((string) ($search_date_creation_endmonth));
}
if ($search_date_creation_endyear) {
	$param .= '&search_date_creation_endyear='.urlencode((string) ($search_date_creation_endyear));
}
if ($search_date_creation_endday) {
	$param .= '&search_date_creation_endday='.urlencode((string) ($search_date_creation_endday));
}
if ($search_date_creation_end) {
	$param .= '&search_date_creation_end='.urlencode($search_date_creation_end);
}
if ($search_date_modif_startmonth) {
	$param .= '&search_date_modif_startmonth='.urlencode((string) ($search_date_modif_startmonth));
}
if ($search_date_modif_startyear) {
	$param .= '&search_date_modif_startyear='.urlencode((string) ($search_date_modif_startyear));
}
if ($search_date_modif_startday) {
	$param .= '&search_date_modif_startday='.urlencode((string) ($search_date_modif_startday));
}
if ($search_date_modif_start) {
	$param .= '&search_date_modif_start='.urlencode($search_date_modif_start);
}
if ($search_date_modif_endmonth) {
	$param .= '&search_date_modif_endmonth='.urlencode((string) ($search_date_modif_endmonth));
}
if ($search_date_modif_endyear) {
	$param .= '&search_date_modif_endyear='.urlencode((string) ($search_date_modif_endyear));
}
if ($search_date_modif_endday) {
	$param .= '&search_date_modif_endday='.urlencode((string) ($search_date_modif_endday));
}
if ($search_date_modif_end) {
	$param .= '&search_date_modif_end=' . urlencode($search_date_modif_end);
}
if (!empty($search_category_array)) {
	foreach ($search_category_array as $tmpval) {
		$param .= '&search_categegory_project_list[]='.urlencode($tmpval);
	}
}
if ($search_ref != '') {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_label != '') {
	$param .= '&search_label='.urlencode($search_label);
}
if ($search_societe != '') {
	$param .= '&search_societe='.urlencode($search_societe);
}
if ($search_societe_alias != '') {
	$param .= '&search_societe_alias='.urlencode($search_societe_alias);
}
if ($search_societe_country != '') {
	$param .= '&search_societe_country='.urlencode($search_societe_country);
}
if ($search_status != '' && $search_status != '-1') {
	$param .= "&search_status=".urlencode($search_status);
}
if ((is_numeric($search_opp_status) && $search_opp_status >= 0) || in_array($search_opp_status, array('all', 'openedopp', 'notopenedopp', 'none'))) {
	$param .= '&search_opp_status='.urlencode($search_opp_status);
}
if ($search_opp_percent != '') {
	$param .= '&search_opp_percent='.urlencode($search_opp_percent);
}
if ($search_public != '') {
	$param .= '&search_public='.urlencode($search_public);
}
if ($search_project_user > 0) {
	$param .= '&search_project_user='.urlencode($search_project_user);
}
if ($search_project_contact > 0) {
	$param .= '&search_project_contact='.urlencode((string) ($search_project_contact));
}
if ($search_sale > 0) {
	$param .= '&search_sale='.urlencode((string) ($search_sale));
}
if ($search_opp_amount != '') {
	$param .= '&search_opp_amount='.urlencode($search_opp_amount);
}
if ($search_budget_amount != '') {
	$param .= '&search_budget_amount='.urlencode($search_budget_amount);
}
if ($search_usage_task != '') {
	$param .= '&search_usage_task='.urlencode($search_usage_task);
}
if ($search_usage_bill_time != '') {
	$param .= '&search_usage_opportunity='.urlencode($search_usage_bill_time);
}
if ($search_usage_event_organization != '') {
	$param .= '&search_usage_event_organization='.urlencode($search_usage_event_organization);
}
if ($search_accept_conference_suggestions != '') {
	$param .= '&search_accept_conference_suggestions='.urlencode($search_accept_conference_suggestions);
}
if ($search_accept_booth_suggestions != '') {
	$param .= '&search_accept_booth_suggestions='.urlencode($search_accept_booth_suggestions);
}
if ($search_price_registration != '') {
	$param .= '&search_price_registration='.urlencode($search_price_registration);
}
if ($search_price_booth != '') {
	$param .= '&search_price_booth='.urlencode($search_price_booth);
}
if ($search_login) {
	$param .= '&search_login='.urlencode($search_login);
}
if ($search_import_key) {
	$param .= '&search_import_key='.urlencode($search_import_key);
}
foreach ($searchCategoryCustomerList as $searchCategoryCustomer) {
	$param .= "&search_category_customer_list[]=".urlencode($searchCategoryCustomer);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	'validate' => img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	'generate_doc' => img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
//if($user->rights->societe->creer) $arrayofmassactions['createbills']=$langs->trans("CreateInvoiceForThisCustomer");
if ($user->hasRight('projet', 'creer')) {
	$arrayofmassactions['close'] = img_picto('', 'close_title', 'class="pictofixedwidth"').$langs->trans("Close");
	$arrayofmassactions['preaffectuser'] = img_picto('', 'user', 'class="pictofixedwidth"').$langs->trans("AffectUser");
}
if ($user->hasRight('projet', 'supprimer')) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (isModEnabled('category') && $user->hasRight('projet', 'creer')) {
	$arrayofmassactions['preaffecttag'] = img_picto('', 'category', 'class="pictofixedwidth"').$langs->trans("AffectTag");
}
if (in_array($massaction, array('presend', 'predelete', 'preaffecttag', 'preaffectuser'))) {
	$arrayofmassactions = array();
}

$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$url = DOL_URL_ROOT.'/projet/card.php?action=create';
if (!empty($socid)) {
	$url .= '&socid='.$socid;
}
if ($search_usage_event_organization == 1) {
	$url .= '&usage_organize_event=1';
	if (((int) $search_usage_opportunity) < 1) {
		$url .= '&usage_opportunity=0';
	}
}

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewProject'), '', 'fa fa-plus-circle', $url, '', $user->hasRight('projet', 'creer'));

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<input type="hidden" name="groupby" value="'.$groupby.'">';

// Show description of content
$texthelp = '';
if ($search_project_user == $user->id) {
	$texthelp .= $langs->trans("MyProjectsDesc");
} else {
	if ($user->hasRight('projet', 'all', 'lire') && !$socid) {
		$texthelp .= $langs->trans("ProjectsDesc");
	} else {
		$texthelp .= $langs->trans("ProjectsPublicDesc");
	}
}

print_barre_liste($form->textwithpicto($title, $texthelp), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'project', 0, $newcardbutton, '', $limit, 0, 0, 1);


$topicmail = "Information";
$modelmail = "project";
$objecttmp = new Project($db);
$trackid = 'proj'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
	}
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

// If the user can view user other than himself
$moreforfilter .= '<div class="divsearchfield">';
$tmptitle = $langs->trans('ProjectsWithThisUserAsContact');
//$includeonly = 'hierarchyme';
$includeonly = '';
if (!$user->hasRight('user', 'user', 'lire')) {
	$includeonly = array($user->id);
}
$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$form->select_dolusers($search_project_user ? $search_project_user : '', 'search_project_user', $tmptitle, '', 0, $includeonly, '', 0, 0, 0, '', 0, '', 'maxwidth300 widthcentpercentminusx');
$moreforfilter .= '</div>';

$moreforfilter .= '<div class="divsearchfield">';
$tmptitle = $langs->trans('ProjectsWithThisContact');
$moreforfilter .= img_picto($tmptitle, 'contact', 'class="pictofixedwidth"').$form->select_contact(0, $search_project_contact ? $search_project_contact : '', 'search_project_contact', $tmptitle, '', '', 0, 'maxwidth300 widthcentpercentminusx');

$moreforfilter .= '</div>';

// If the user can view thirdparties other than his'
if ($user->hasRight('user', 'user', 'lire')) {
	$langs->load("commercial");
	$moreforfilter .= '<div class="divsearchfield">';
	$tmptitle = $langs->trans('ThirdPartiesOfSaleRepresentative');
	$moreforfilter .= img_picto($tmptitle, 'user', 'class="pictofixedwidth"').$formother->select_salesrepresentatives($search_sale, 'search_sale', $user, 0, $tmptitle, 'maxwidth300 widthcentpercentminusx');
	$moreforfilter .= '</div>';
}

// Filter on categories
if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
	$formcategory = new FormCategory($db);
	$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_PROJECT, $search_category_array, 'minwidth300imp minwidth300 widthcentpercentminusx');
}

// Filter on customer categories
if (getDolGlobalString('MAIN_SEARCH_CATEGORY_CUSTOMER_ON_PROJECT_LIST') && isModEnabled("category") && $user->hasRight('categorie', 'lire')) {
	$formcategory = new FormCategory($db);
	$moreforfilter .= $formcategory->getFilterBox(Categorie::TYPE_CUSTOMER, $searchCategoryCustomerList, 'minwidth300', $searchCategoryCustomerList ? $searchCategoryCustomerList : 0);
}

if (getDolGlobalInt('PROJECT_ENABLE_SUB_PROJECT')) {
	//Checkbox for omitting child projects filter
	$moreforfilter .= '<p style="display: inline-block; margin-left: 5px;">'.$langs->trans("Omit sub-projects").' </p><input type="checkbox" style="margin-left: 10px" class="valignmiddle" id="search_omitChildren" name="search_omitChildren"'.($search_omitChildren ? ' checked="checked"' : '').'"> ';
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');


print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
// Project ref
if (!empty($arrayfields['p.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.dol_escape_htmltag($search_ref).'" size="6">';
	print '</td>';
}
// Project label
if (!empty($arrayfields['p.title']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_label" size="8" value="'.dol_escape_htmltag($search_label).'">';
	print '</td>';
}
// Third party
if (!empty($arrayfields['s.nom']['checked'])) {
	print '<td class="liste_titre">';
	if ($socid > 0) {
		$tmpthirdparty = new Societe($db);
		$tmpthirdparty->fetch($socid);
		$search_societe = $tmpthirdparty->name;
	}
	print '<input type="text" class="flat" name="search_societe" size="8" value="'.dol_escape_htmltag($search_societe).'">';
	print '</td>';
}

// Alias
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print '<td class="liste_titre">';
	if ($socid > 0) {
		$tmpthirdparty = new Societe($db);
		$tmpthirdparty->fetch($socid);
		$search_societe_alias = $tmpthirdparty->name_alias;
	}
	print '<input type="text" class="flat" name="search_societe_alias" size="8" value="'.dol_escape_htmltag($search_societe_alias).'">';
	print '</td>';
}
// Country of thirdparty
if (!empty($arrayfields['co.country_code']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat width50" name="search_societe_country" value="'.dol_escape_htmltag($search_societe_country).'">';
	print '</td>';
}
// Sale representative
if (!empty($arrayfields['commercial']['checked'])) {
	print '<td class="liste_titre">&nbsp;</td>';
}
// Start date
if (!empty($arrayfields['p.dateo']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	/*if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
	 print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_sday" value="'.dol_escape_htmltag($search_sday).'">';
	 }
	 print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_smonth" value="'.dol_escape_htmltag($search_smonth).'">';
	 print $formother->selectyear($search_syear ? $search_syear : -1, 'search_syear', 1, 20, 5, 0, 0, '', 'widthauto valignmiddle');*/
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start_start ? $search_date_start_start : -1, 'search_date_start_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_start_end ? $search_date_start_end : -1, 'search_date_start_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// End date
if (!empty($arrayfields['p.datee']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	/*if (!empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) {
	 print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_eday" value="'.dol_escape_htmltag($search_eday).'">';
	 }
	 print '<input class="flat valignmiddle" type="text" size="1" maxlength="2" name="search_emonth" value="'.dol_escape_htmltag($search_emonth).'">';
	 print $formother->selectyear($search_eyear ? $search_eyear : -1, 'search_eyear', 1, 20, 5, 0, 0, '', 'widthauto valignmiddle');*/
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end_start ? $search_date_end_start : -1, 'search_date_end_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_end_end ? $search_date_end_end : -1, 'search_date_end_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Visibility
if (!empty($arrayfields['p.public']['checked'])) {
	print '<td class="liste_titre center">';
	$array = array('' => '', 0 => $langs->trans("PrivateProject"), 1 => $langs->trans("SharedProject"));
	print $form->selectarray('search_public', $array, $search_public, 0, 0, 0, '', 0, 0, 0, '', 'maxwidth75');
	print '</td>';
}
if (!empty($arrayfields['c.assigned']['checked'])) {
	print '<td class="liste_titre center">';
	print '</td>';
}
// Opp status
if (!empty($arrayfields['p.fk_opp_status']['checked'])) {
	print '<td class="liste_titre nowrap center">';
	print $formproject->selectOpportunityStatus('search_opp_status', $search_opp_status, 1, 1, 1, 0, 'maxwidth125 nowrapoption', 1, 1);
	print '</td>';
}
if (!empty($arrayfields['p.opp_amount']['checked'])) {
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_opp_amount" size="3" value="'.$search_opp_amount.'">';
	print '</td>';
}
if (!empty($arrayfields['p.opp_percent']['checked'])) {
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_opp_percent" size="2" value="'.$search_opp_percent.'">';
	print '</td>';
}
if (!empty($arrayfields['opp_weighted_amount']['checked'])) {
	print '<td class="liste_titre nowrap right">';
	print '</td>';
}
if (!empty($arrayfields['p.budget_amount']['checked'])) {
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_budget_amount" size="4" value="'.$search_budget_amount.'">';
	print '</td>';
}
if (!empty($arrayfields['p.usage_opportunity']['checked'])) {
	print '<td class="liste_titre nowrap">';
	print $form->selectyesno('search_usage_opportunity', $search_usage_opportunity, 1, false, 1, 1);
	print '';
	print '</td>';
}
if (!empty($arrayfields['p.usage_task']['checked'])) {
	print '<td class="liste_titre nowrap">';
	print $form->selectyesno('search_usage_task', $search_usage_task, 1, false, 1, 1);
	print '</td>';
}
if (!empty($arrayfields['p.usage_bill_time']['checked'])) {
	print '<td class="liste_titre nowrap">';
	print $form->selectyesno('search_usage_bill_time', $search_usage_bill_time, 1, false, 1, 1);
	print '</td>';
}
if (!empty($arrayfields['p.usage_organize_event']['checked'])) {
	print '<td class="liste_titre nowrap">';
	print $form->selectyesno('search_usage_event_organization', $search_usage_event_organization, 1, false, 1, 1);
	print '</td>';
}
if (!empty($arrayfields['p.accept_conference_suggestions']['checked'])) {
	print '<td class="liste_titre nowrap">';
	print $form->selectyesno('search_accept_conference_suggestions', $search_accept_conference_suggestions, 1, false, 1, 1);
	print '</td>';
}
if (!empty($arrayfields['p.accept_booth_suggestions']['checked'])) {
	print '<td class="liste_titre nowrap">';
	print $form->selectyesno('search_accept_booth_suggestions', $search_accept_booth_suggestions, 1, false, 1, 1);
	print '</td>';
}
if (!empty($arrayfields['p.price_registration']['checked'])) {
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_price_registration" size="4" value="'.dol_escape_htmltag($search_price_registration).'">';
	print '</td>';
}
if (!empty($arrayfields['p.price_booth']['checked'])) {
	print '<td class="liste_titre nowrap right">';
	print '<input type="text" class="flat" name="search_price_booth" size="4" value="'.dol_escape_htmltag($search_price_booth).'">';
	print '</td>';
}
if (!empty($arrayfields['u.login']['checked'])) {
	// Author
	print '<td class="liste_titre" align="center">';
	print '<input class="flat" size="4" type="text" name="search_login" value="'.dol_escape_htmltag($search_login).'">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Creation date
if (!empty($arrayfields['p.datec']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_creation_start ? $search_date_creation_start : -1, 'search_date_creation_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_creation_end ? $search_date_creation_end : -1, 'search_date_creation_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
// Modification date
if (!empty($arrayfields['p.tms']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modif_start ? $search_date_modif_start : -1, 'search_date_modif_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_date_modif_end ? $search_date_modif_end : -1, 'search_date_modif_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}
if (!empty($arrayfields['p.email_msgid']['checked'])) {
	// Email msg id
	print '<td class="liste_titre">';
	print '</td>';
}
if (!empty($arrayfields['p.import_key']['checked'])) {
	// Import key
	print '<td class="liste_titre">';
	print '<input class="flat width75" type="text" name="search_import_key" value="'.dol_escape_htmltag($search_import_key).'">';
	print '</td>';
}
if (!empty($arrayfields['p.fk_statut']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	$formproject->selectProjectsStatus($search_status, 1, 'search_status');
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.ref']['checked'])) {
	print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.title']['checked'])) {
	print_liste_field_titre($arrayfields['p.title']['label'], $_SERVER["PHP_SELF"], "p.title", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.nom']['checked'])) {
	print_liste_field_titre($arrayfields['s.nom']['label'], $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['s.name_alias']['checked'])) {
	print_liste_field_titre($arrayfields['s.name_alias']['label'], $_SERVER["PHP_SELF"], "s.name_alias", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['co.country_code']['checked'])) {
	print_liste_field_titre($arrayfields['co.country_code']['label'], $_SERVER["PHP_SELF"], "co.country_code", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['commercial']['checked'])) {
	print_liste_field_titre($arrayfields['commercial']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder, 'tdoverflowmax100imp ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.dateo']['checked'])) {
	print_liste_field_titre($arrayfields['p.dateo']['label'], $_SERVER["PHP_SELF"], "p.dateo", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.datee']['checked'])) {
	print_liste_field_titre($arrayfields['p.datee']['label'], $_SERVER["PHP_SELF"], "p.datee", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.public']['checked'])) {
	print_liste_field_titre($arrayfields['p.public']['label'], $_SERVER["PHP_SELF"], "p.public", "", $param, "", $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['c.assigned']['checked'])) {
	print_liste_field_titre($arrayfields['c.assigned']['label'], $_SERVER["PHP_SELF"], "", '', $param, '', $sortfield, $sortorder, 'center ', '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_opp_status']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_opp_status']['label'], $_SERVER["PHP_SELF"], 'p.fk_opp_status', "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.opp_amount']['checked'])) {
	print_liste_field_titre($arrayfields['p.opp_amount']['label'], $_SERVER["PHP_SELF"], 'p.opp_amount', "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.opp_percent']['checked'])) {
	print_liste_field_titre($arrayfields['p.opp_percent']['label'], $_SERVER['PHP_SELF'], 'p.opp_percent', "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['opp_weighted_amount']['checked'])) {
	print_liste_field_titre($arrayfields['opp_weighted_amount']['label'], $_SERVER['PHP_SELF'], 'opp_weighted_amount', '', $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.budget_amount']['checked'])) {
	print_liste_field_titre($arrayfields['p.budget_amount']['label'], $_SERVER["PHP_SELF"], 'p.budget_amount', "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.usage_opportunity']['checked'])) {
	print_liste_field_titre($arrayfields['p.usage_opportunity']['label'], $_SERVER["PHP_SELF"], 'p.usage_opportunity', "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.usage_task']['checked'])) {
	print_liste_field_titre($arrayfields['p.usage_task']['label'], $_SERVER["PHP_SELF"], 'p.usage_task', "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.usage_bill_time']['checked'])) {
	print_liste_field_titre($arrayfields['p.usage_bill_time']['label'], $_SERVER["PHP_SELF"], 'p.usage_bill_time', "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.usage_organize_event']['checked'])) {
	print_liste_field_titre($arrayfields['p.usage_organize_event']['label'], $_SERVER["PHP_SELF"], 'p.usage_organize_event', "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.accept_conference_suggestions']['checked'])) {
	print_liste_field_titre($arrayfields['p.accept_conference_suggestions']['label'], $_SERVER["PHP_SELF"], 'p.accept_conference_suggestions', "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.accept_booth_suggestions']['checked'])) {
	print_liste_field_titre($arrayfields['p.accept_booth_suggestions']['label'], $_SERVER["PHP_SELF"], 'p.accept_booth_suggestions', "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.price_registration']['checked'])) {
	print_liste_field_titre($arrayfields['p.price_registration']['label'], $_SERVER["PHP_SELF"], 'p.price_registration', "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.price_booth']['checked'])) {
	print_liste_field_titre($arrayfields['p.price_booth']['label'], $_SERVER["PHP_SELF"], 'p.price_booth', "", $param, '', $sortfield, $sortorder, 'right ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['u.login']['checked'])) {
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, 'align="center"', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['p.datec']['checked'])) {
	print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.tms']['checked'])) {
	print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.email_msgid']['checked'])) {
	print_liste_field_titre($arrayfields['p.email_msgid']['label'], $_SERVER["PHP_SELF"], "p.email_msgid", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.import_key']['checked'])) {
	print_liste_field_titre($arrayfields['p.import_key']['label'], $_SERVER["PHP_SELF"], "p.import_key", "", $param, '', $sortfield, $sortorder, '');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['p.fk_statut']['checked'])) {
	print_liste_field_titre($arrayfields['p.fk_statut']['label'], $_SERVER["PHP_SELF"], "p.fk_statut", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";


$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array(
	'nbfield' => 0,
	'val' => array()
);
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	// Thirdparty
	$companystatic->id = $obj->socid;
	$companystatic->name = $obj->name;
	$companystatic->name_alias = $obj->alias;
	$companystatic->client = $obj->client;
	$companystatic->code_client = $obj->code_client;
	$companystatic->email = $obj->email;
	$companystatic->phone = $obj->phone;
	$companystatic->address = $obj->address;
	$companystatic->zip = $obj->zip;
	$companystatic->town = $obj->town;
	$companystatic->country_code = $obj->country_code;

	// Project
	$object->id = $obj->id;
	$object->ref = $obj->ref;
	$object->title = $obj->title;
	$object->fk_opp_status = $obj->fk_opp_status;
	$object->user_author_id = $obj->fk_user_creat;
	$object->date_creation = $db->jdate($obj->date_creation);
	$object->date_start = $db->jdate($obj->date_start);
	$object->date_end = $db->jdate($obj->date_end);
	$object->statut = $obj->status; // deprecated
	$object->status = $obj->status;
	$object->public = $obj->public;
	$object->opp_percent = $obj->opp_percent;
	$object->opp_status = $obj->fk_opp_status;
	$object->opp_status_code = $obj->opp_status_code;
	$object->opp_amount = !empty($obj->opp_amount) ? $obj->opp_amount : "";
	$object->opp_weighted_amount = $obj->opp_weighted_amount;
	$object->budget_amount = $obj->budget_amount;
	$object->usage_opportunity = $obj->usage_opportunity;
	$object->usage_task = $obj->usage_task;
	$object->usage_bill_time = $obj->usage_bill_time;
	$object->usage_organize_event = $obj->usage_organize_event;
	$object->email_msgid = $obj->email_msgid;
	$object->import_key = $obj->import_key;
	$object->thirdparty = $companystatic;

	//$userAccess = $object->restrictedProjectArea($user); // disabled, permission on project must be done by the select

	$stringassignedusers = '';

	if (!empty($arrayfields['c.assigned']['checked'])) {
		$ifisrt = 1;
		foreach (array('internal', 'external') as $source) {
			$tab = $object->liste_contact(-1, $source, 0, '', 1);
			$numcontact = count($tab);
			if (!empty($numcontact)) {
				foreach ($tab as $contactproject) {
					//var_dump($contacttask);
					if ($source == 'internal') {
						if (!empty($conf->cache['user'][$contactproject['id']])) {
							$c = $conf->cache['user'][$contactproject['id']];
						} else {
							$c = new User($db);
							$c->fetch($contactproject['id']);
							$conf->cache['user'][$contactproject['id']] = $c;
						}
					} else {
						if (!empty($conf->cache['contact'][$contactproject['id']])) {
							$c = $conf->cache['contact'][$contactproject['id']];
						} else {
							$c = new Contact($db);
							$c->fetch($contactproject['id']);
							$conf->cache['contact'][$contactproject['id']] = $c;
						}
					}
					if (get_class($c) == 'User') {
						$stringassignedusers .= $c->getNomUrl(-2, '', 0, 0, 24, 1, '', 'valignmiddle'.($ifisrt ? '' : ' notfirst'));
					} else {
						$stringassignedusers .= $c->getNomUrl(-2, '', 0, '', -1, 0, 'valignmiddle'.($ifisrt ? '' : ' notfirst'));
					}
					$ifisrt = 0;
				}
			}
		}
	}

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban'.(empty($groupby) ? '' : ' trkanbangroupby').'"><td colspan="'.$savnbfield.'">';
		}

		if (!empty($groupby)) {
			if (is_null($groupbyold)) {
				print '<div class="box-flex-container-columns kanban">';	// Start div for all kanban columns
			}
			// Start kanban column
			if (is_null($obj->$groupbyfield)) {
				$groupbyvalue = 'undefined';
			} else {
				$groupbyvalue = $obj->$groupbyfield;
			}
			if ($groupbyold !== $groupbyvalue) {
				if (!is_null($groupbyold)) {
					print '</div>';	// We need a new kanban column - end box-flex-container
				}
				foreach ($groupbyvalues as $tmpcursor => $tmpgroupbyvalue) {
					//var_dump("tmpcursor=".$tmpcursor." groupbyold=".$groupbyold." groupbyvalue=".$groupbyvalue);
					if (!is_null($groupbyold) && ($tmpcursor <= $groupbyold)) { continue; }
					if ($tmpcursor >= $groupbyvalue) { continue; }
					// We found a possible column with no value, we output the empty column
					print '<div class="box-flex-container-column kanban column';
					if (in_array($tmpcursor, $groupofcollpasedvalues)) {
						print ' kanbancollapsed';
					}
					print '" data-groupbyid="'.preg_replace('/[^a-z0-9]/', '', $tmpcursor).'">';
					print '<div class="kanbanlabel">'.$langs->trans($tmpgroupbyvalue).'</div>';
					print '</div>';	// Start and end the new column
				}
				print '<div class="box-flex-container-column kanban column" data-groupbyid="'.preg_replace('/[^a-z0-9]/', '', $groupbyvalue).'">';	// Start new column
				print '<div class="kanbanlabel">'.$langs->trans(empty($groupbyvalues[$groupbyvalue]) ? 'Undefined' : $groupbyvalues[$groupbyvalue]).'</div>';
			}
			$groupbyold = $groupbyvalue;
		} elseif ($i == 0) {
			print '<div class="box-flex-container kanban">';
		}

		// Output Kanban
		$selected = -1;
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		$arrayofdata = array('assignedusers' => $stringassignedusers, 'thirdparty' => $companystatic, 'selected' => $selected);

		print $object->getKanbanView('', $arrayofdata, ($groupby ? 'small' : ''));

		// if no more elements to show
		if ($i == ($imaxinloop - 1)) {
			// Close kanban column
			if (!empty($groupby)) {
				print '</div>';	// end box-flex-container
				foreach ($groupbyvalues as $tmpcursor => $tmpgroupbyvalue) {
					//var_dump("tmpcursor=".$tmpcursor." groupbyold=".$groupbyold." groupbyvalue=".$groupbyvalue);
					if ($tmpcursor <= $groupbyvalue) { continue; }
					// We found a possible column with no value, we output the empty column
					print '<div class="box-flex-container-column kanban column';
					if (in_array($tmpcursor, $groupofcollpasedvalues)) {
						print ' kanbancollapsed';
					}
					print '" data-groupbyid="'.preg_replace('/[^a-z0-9]/', '', $tmpcursor).'">';
					print '<div class="kanbanlabel">'.$langs->trans(empty($tmpgroupbyvalue) ? 'Undefined' : $tmpgroupbyvalue).'</div>';
					print '</div>';	// Start and end the new column
				}
				print '</div>';	// end box-flex-container-columns
			} else {
				print '</div>';	// end box-flex-container
			}

			print '</td></tr>';
		}
	} else {
		// Author
		$userstatic->id = $obj->fk_user_creat;
		$userstatic->login = $obj->login;
		$userstatic->lastname = $obj->lastname;
		$userstatic->firstname = $obj->firstname;
		$userstatic->email = $obj->user_email;
		$userstatic->status = $obj->user_statut;
		$userstatic->entity = $obj->entity;
		$userstatic->photo = $obj->photo;
		$userstatic->office_phone = $obj->office_phone;
		$userstatic->office_fax = $obj->office_fax;
		$userstatic->user_mobile = $obj->user_mobile;
		$userstatic->job = $obj->job;
		$userstatic->gender = $obj->gender;

		// Show here line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven '.((getDolGlobalInt('MAIN_FINISHED_LINES_OPACITY') == 1 && $object->status > 1) ? 'opacitymedium' : '').'">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Project url
		if (!empty($arrayfields['p.ref']['checked'])) {
			print '<td class="nowraponall tdoverflowmax200">';
			print $object->getNomUrl(1, (!empty(GETPOSTINT('search_usage_event_organization')) ? 'eventorganization' : ''));
			if ($object->hasDelay()) {
				print img_warning($langs->trans('Late'));
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Title
		if (!empty($arrayfields['p.title']['checked'])) {
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->title).'">';
			print dol_escape_htmltag($obj->title);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Company
		if (!empty($arrayfields['s.nom']['checked'])) {
			print '<td class="tdoverflowmax125">';
			if ($obj->socid) {
				print $companystatic->getNomUrl(1, '', 0, 0, -1, empty($arrayfields['s.name_alias']['checked']) ? 0 : 1);
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Alias
		if (!empty($arrayfields['s.name_alias']['checked'])) {
			print '<td class="tdoverflowmax100">';
			if ($obj->socid) {
				print $companystatic->name_alias;
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Country code
		if (!empty($arrayfields['co.country_code']['checked'])) {
			print '<td class="tdoverflowmax125">';
			print $obj->country_code;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Sales Representatives
		if (!empty($arrayfields['commercial']['checked'])) {
			print '<td class="tdoverflowmax150">';
			if ($obj->socid) {
				$companystatic->id = $obj->socid;
				$companystatic->name = $obj->name;
				$listsalesrepresentatives = $companystatic->getSalesRepresentatives($user);
				$nbofsalesrepresentative = count($listsalesrepresentatives);
				if ($nbofsalesrepresentative > 6) {
					// We print only number
					print $nbofsalesrepresentative;
				} elseif ($nbofsalesrepresentative > 0) {
					$userstatic = new User($db);
					$j = 0;
					foreach ($listsalesrepresentatives as $val) {
						$userstatic->id = $val['id'];
						$userstatic->lastname = $val['lastname'];
						$userstatic->firstname = $val['firstname'];
						$userstatic->email = $val['email'];
						$userstatic->status = $val['statut'];
						$userstatic->entity = $val['entity'];
						$userstatic->photo = $val['photo'];
						$userstatic->login = $val['login'];
						$userstatic->office_phone = $val['office_phone'];
						$userstatic->office_fax = $val['office_fax'];
						$userstatic->user_mobile = $val['user_mobile'];
						$userstatic->job = $val['job'];
						$userstatic->gender = $val['gender'];
						print ($nbofsalesrepresentative < 2) ? $userstatic->getNomUrl(-1, '', 0, 0, 12) : $userstatic->getNomUrl(-2);
						$j++;
						if ($j < $nbofsalesrepresentative) {
							print ' ';
						}
					}
				}
				//else print $langs->trans("NoSalesRepresentativeAffected");
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date start project
		if (!empty($arrayfields['p.dateo']['checked'])) {
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_start), 'day');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date end project
		if (!empty($arrayfields['p.datee']['checked'])) {
			print '<td class="center">';
			print dol_print_date($db->jdate($obj->date_end), 'day');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Visibility
		if (!empty($arrayfields['p.public']['checked'])) {
			print '<td class="center">';
			if ($obj->public) {
				print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
				//print $langs->trans('SharedProject');
			} else {
				print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
				//print $langs->trans('PrivateProject');
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Assigned contacts of project
		if (!empty($arrayfields['c.assigned']['checked'])) {
			print '<td class="center nowraponall tdoverflowmax200">';
			print $stringassignedusers;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Opp Status
		if (!empty($arrayfields['p.fk_opp_status']['checked'])) {
			$s = '';
			if ($obj->opp_status_code) {
				$s = $langs->trans("OppStatus".$obj->opp_status_code);
				if (empty($arrayfields['p.opp_percent']['checked']) && $obj->opp_percent) {
					$s .= ' ('.dol_escape_htmltag(price2num($obj->opp_percent, 1)).'%)';
				}
			}
			print '<td class="center tdoverflowmax150" title="'.$s.'">';
			print $s;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Opp Amount
		if (!empty($arrayfields['p.opp_amount']['checked'])) {
			print '<td class="right">';
			//if ($obj->opp_status_code)
			if (isset($obj->opp_amount) && strcmp($obj->opp_amount, '')) {
				print '<span class="amount">'.price($obj->opp_amount, 1, $langs, 1, -1, -1, '').'</span>';
				if (!isset($totalarray['val']['p.opp_amount'])) {
					$totalarray['val']['p.opp_amount'] = $obj->opp_amount;
				} else {
					$totalarray['val']['p.opp_amount'] += $obj->opp_amount;
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.opp_amount';
			}
		}
		// Opp percent
		if (!empty($arrayfields['p.opp_percent']['checked'])) {
			print '<td class="right">';
			if ($obj->opp_percent) {
				print price($obj->opp_percent, 1, $langs, 1, 0).'%';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Opp weighted amount
		if (!empty($arrayfields['opp_weighted_amount']['checked'])) {
			if (!isset($totalarray['val']['opp_weighted_amount'])) {
				$totalarray['val']['opp_weighted_amount'] = 0;
			}
			print '<td align="right">';
			if ($obj->opp_weighted_amount) {
				print '<span class="amount">'.price($obj->opp_weighted_amount, 1, $langs, 1, -1, -1, '').'</span>';
				$totalarray['val']['opp_weighted_amount'] += $obj->opp_weighted_amount;
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
				$totalarray['pos'][$totalarray['nbfield']] = 'opp_weighted_amount';
			}
		}
		// Budget
		if (!empty($arrayfields['p.budget_amount']['checked'])) {
			print '<td class="right">';
			if ($obj->budget_amount != '') {
				print '<span class="amount">'.price($obj->budget_amount, 1, $langs, 1, -1, -1).'</span>';
				if (!isset($totalarray['val']['p.budget_amount'])) {
					$totalarray['val']['p.budget_amount'] = $obj->budget_amount;
				} else {
					$totalarray['val']['p.budget_amount'] += $obj->budget_amount;
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
				$totalarray['pos'][$totalarray['nbfield']] = 'p.budget_amount';
			}
		}
		// Usage opportunity
		if (!empty($arrayfields['p.usage_opportunity']['checked'])) {
			print '<td class="">';
			if ($obj->usage_opportunity) {
				print yn($obj->usage_opportunity);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Usage task
		if (!empty($arrayfields['p.usage_task']['checked'])) {
			print '<td class="">';
			if ($obj->usage_task) {
				print yn($obj->usage_task);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Bill time
		if (!empty($arrayfields['p.usage_bill_time']['checked'])) {
			print '<td class="">';
			if ($obj->usage_bill_time) {
				print yn($obj->usage_bill_time);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Event Organization
		if (!empty($arrayfields['p.usage_organize_event']['checked'])) {
			print '<td class="">';
			if ($obj->usage_organize_event) {
				print yn($obj->usage_organize_event);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Allow unknown people to suggest conferences
		if (!empty($arrayfields['p.accept_conference_suggestions']['checked'])) {
			print '<td class="">';
			if ($obj->accept_conference_suggestions) {
				print yn($obj->accept_conference_suggestions);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Allow unknown people to suggest booth
		if (!empty($arrayfields['p.accept_booth_suggestions']['checked'])) {
			print '<td class="">';
			if ($obj->accept_booth_suggestions) {
				print yn($obj->accept_booth_suggestions);
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Price of registration
		if (!empty($arrayfields['p.price_registration']['checked'])) {
			print '<td class="right">';
			if ($obj->price_registration != '') {
				print '<span class="amount">'.price($obj->price_registration, 1, $langs, 1, -1, -1).'</span>';
				$totalarray['val']['p.price_registration'] += $obj->price_registration;
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.price_registration';
			}
		}
		// Price of booth
		if (!empty($arrayfields['p.price_booth']['checked'])) {
			print '<td class="right">';
			if ($obj->price_booth != '') {
				print '<span class="amount">'.price($obj->price_booth, 1, $langs, 1, -1, -1).'</span>';
				$totalarray['val']['p.price_booth'] += $obj->price_booth;
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['pos'][$totalarray['nbfield']] = 'p.price_booth';
			}
		}
		// Author
		if (!empty($arrayfields['u.login']['checked'])) {
			print '<td class="center tdoverflowmax150">';
			if ($userstatic->id) {
				print $userstatic->getNomUrl(-1);
			}
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['p.datec']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Date modification
		if (!empty($arrayfields['p.tms']['checked'])) {
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_modification), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Email MsgID
		if (!empty($arrayfields['p.email_msgid']['checked'])) {
			print '<td class="tdoverflowmax125" title="'.dol_escape_htmltag($obj->email_msgid).'">';
			print dol_escape_htmltag($obj->email_msgid);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Import key
		if (!empty($arrayfields['p.import_key']['checked'])) {
			print '<td class="right">'.dol_escape_htmltag($obj->import_key).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Status
		if (!empty($arrayfields['p.fk_statut']['checked'])) {
			print '<td class="center">'.$object->getLibStatut(5).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
	}

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

if (in_array('builddoc', array_keys($arrayofmassactions)) && ($nbtotalofrecords === '' || $nbtotalofrecords)) {
	$hidegeneratedfilelistifempty = 1;
	if ($massaction == 'builddoc' || $action == 'remove_file' || $show_files) {
		$hidegeneratedfilelistifempty = 0;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
	$formfile = new FormFile($db);

	// Show list of available documents
	$urlsource = $_SERVER['PHP_SELF'].'?sortfield='.$sortfield.'&sortorder='.$sortorder;
	$urlsource .= str_replace('&amp;', '&', $param);

	$filedir = $diroutputmassaction;
	$genallowed = $permissiontoread;
	$delallowed = $permissiontoadd;

	print $formfile->showdocuments('massfilesarea_'.$object->module, '', $filedir, $urlsource, 0, $delallowed, '', 1, 1, 0, 48, 1, $param, $title, '', '', '', null, $hidegeneratedfilelistifempty);
}

// End of page
llxFooter();
$db->close();
