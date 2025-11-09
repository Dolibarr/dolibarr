<?php
/*
 * Copyright (C) 2019-2025  Frédéric France         <frederic.france@free.fr>
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
 *  return json events calendar
 *
 */

// use ICal\ICal;

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1); // Disables token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOHEADERNOFOOTER')) {
	define('NOHEADERNOFOOTER', '1');
}
if (!defined("NOCSRFCHECK")) {
	define("NOCSRFCHECK", 1);
}
// Load Dolibarr environment
require '../../main.inc.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';


$langs->loadLangs(["agenda", "other", "commercial", "companies"]);

top_httphead('application/json', 1);
// dol_syslog('posted events ajax GET '.print_r($_GET, true), LOG_WARNING);
dol_syslog('posted events ajax POST '.print_r($_POST, true), LOG_WARNING);
dol_syslog('posted events ajax REQUEST '.print_r($_REQUEST, true), LOG_WARNING);
$action = GETPOSTISSET('action') ? GETPOST('action', 'aZ09') : 'getevents';
switch ($action) {
	case 'getconfig':
		print json_encode([]);
		break;
	case 'getevents':
		$resourceId = GETPOST('resourceId');
		$calendarName = GETPOST('calendarName');
		$startDate = GETPOSTINT('start');
		$endDate = GETPOSTINT('end');
		$offset = GETPOSTINT('offset');
		$onlylast = (bool) GETPOSTINT('onlylast');
		$search_actioncode = GETPOST('search_actioncode');
		$search_userid = GETPOST('search_user');
		$search_socid = GETPOST('search_socid');
		$search_states = GETPOST('search_states', 'alphanohtml');
		$search_all = GETPOST('search_all', 'alphanohtml');
		$arrayofevents = getEvents($resourceId, $calendarName, $startDate, $endDate, $offset, $onlylast, $search_actioncode, $search_userid, $search_socid, $search_states, $search_all);
		print json_encode($arrayofevents);
		break;
	case 'getdeletedevents':
		$resourceId = GETPOST('resourceId', 'alpha');
		$calendarName = GETPOST('calendarName', 'alpha');
		$arrayofevents = getDeletedEventsId($resourceId);
		print json_encode($arrayofevents);
		break;
	case 'putevent':
		if (GETPOSTISSET('schedule')) {
			$date = new DateTime();
			$timeZone = $date->getTimezone();
			$servertz = $timeZone->getName();
			$updatedevent = json_decode(GETPOST('schedule', 'none'));
			$datestart = json_decode(GETPOST('start', 'none'));
			$dateend = json_decode(GETPOST('end', 'none'));
			$offset = json_decode(GETPOST('offset', 'none'));
			$offset_start = 0;
			$date_start = \DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $datestart->_date, $timeZone);
			if ($date_start !== false) {
				$offset_start = $timeZone->getOffset($date_start);
			}
			$offset_end = 0;
			$date_end = \DateTime::createFromFormat('Y-m-d\TH:i:s.v\Z', $dateend->_date, $timeZone);
			if ($date_end !== false) {
				$offset_end = $timeZone->getOffset($date_end);
			}
			// dol_syslog('updated events ajax REQUEST event ' . print_r($updatedevent, true), LOG_WARNING);
			// dol_syslog('updated events ajax REQUEST datestart '.print_r($datestart, true), LOG_WARNING);
			// dol_syslog('updated events ajax REQUEST dateend '.print_r($dateend, true), LOG_WARNING);
			// dol_syslog('updated events ajax REQUEST offset '.print_r(((int) $offset * 60), true), LOG_WARNING);
			// dol_syslog('updated events ajax REQUEST start '.strtotime($datestart->_date), LOG_WARNING);
			$action = new ActionComm($db);
			$action->fetch($updatedevent->id);
			$action->fetch_optionals();
			$action->fetch_userassigned();
			$action->fetchObjectLinked();
			$action->oldcopy = clone $action;
			$action->location = $updatedevent->location;
			$action->fulldayevent = $updatedevent->isAllDay ? 1 : 0;
			$action->datep = strtotime($datestart->_date) - ((int) $offset * 60);
			$action->datef = strtotime($dateend->_date) - ((int) $offset * 60);
			$res = $action->update($user);
			if ($res < 0) {
				print json_encode([]);
				dol_syslog('updated action error ' . print_r($action->error, true), LOG_ERR);
				dol_syslog('updated action errors ' . print_r($action->errors, true), LOG_ERR);
			} else {
				print json_encode(['id' => $res]);
				// dol_syslog('updated action datep '.print_r($action->datep, true), LOG_WARNING);
				// dol_syslog('updated action datef '.print_r($action->datef, true), LOG_WARNING);
			}
		}
		break;
	case 'postevent':
		if (GETPOSTISSET('event')) {
			$postevent = json_decode(GETPOST('event', 'none'));
			$offset = json_decode(GETPOST('offset', 'none'));
			dol_syslog('posted events ajax REQUEST ' . print_r($postevent, true), LOG_NOTICE);
			$action = new ActionComm($db);
			// a changer
			$action->userownerid = $user->id;
			$action->transparency = 1;
			// type 'autre'
			$action->type_code = 'AC_OTH';
			$action->label = $postevent->title;
			$action->location = $postevent->location;
			$action->fulldayevent = $postevent->isAllDay ? 1 : 0;
			$action->datep = strtotime($postevent->start->_date) - ((int) $offset * 60);
			$action->datef = strtotime($postevent->end->_date) - ((int) $offset * 60);
			$action->percentage = -1;
			$res = $action->create($user);
			if ($res <= 0) {
				dol_syslog('created action error ' . print_r($action->error, true), LOG_ERR);
				dol_syslog('created action errors ' . print_r($action->errors, true), LOG_ERR);
				print json_encode([]);
			} else {
				//dol_syslog('created action datep '.print_r($action->datep, true), LOG_NOTICE);
				//dol_syslog('created action datef '.print_r($action->datef, true), LOG_NOTICE);
				print json_encode(['id' => $res]);
			}
		}
		break;
	case 'deleteevent':
		//dol_syslog('posted events ajax REQUEST '.print_r($_POST, true), LOG_NOTICE);
		if (GETPOSTISSET('schedule')) {
			//$deletedevent = json_decode(GETPOST('schedule'), 'none');
			$deletedevent = json_decode($_POST['schedule']);
			dol_syslog('posted events ajax REQUEST ' . print_r($deletedevent, true), LOG_NOTICE);
			$action = new ActionComm($db);
			$action->fetch($deletedevent->id);
			$action->fetch_optionals();
			$action->fetch_userassigned();
			$action->oldcopy = clone $action;
			$res = $action->delete($user);
			if ($res < 0) {
				dol_syslog('posted events ajax REQUEST ' . print_r($action->error, true), LOG_ERR);
				dol_syslog('posted events ajax REQUEST ' . print_r($action->errors, true), LOG_ERR);
			}
		}
		print json_encode([]);
		break;
	case 'getcustomers':
		$response = [];
		$limit = 100;
		$filterkey = GETPOST('q', 'alphanohtml');
		// On recherche les societes
		$sql = "SELECT s.rowid, s.nom as name, s.name_alias, s.client, s.fournisseur, s.code_client, s.code_fournisseur";
		if (getDolGlobalInt('COMPANY_SHOW_ADDRESS_SELECTLIST')) {
			$sql .= ", s.address, s.zip, s.town";
			$sql .= ", dictp.code as country_code";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
		}
		if (!empty($conf->global->COMPANY_SHOW_ADDRESS_SELECTLIST)) {
			$sql .= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "c_country as dictp ON dictp.rowid=s.fk_pays";
		}
		$sql .= " WHERE s.entity IN (" . getEntity('societe') . ")";
		if (!empty($user->socid)) {
			$sql .= " AND s.rowid = " . $user->socid;
		}
		if (!$user->rights->societe->client->voir && !$user->socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " . $user->id;
		}
		if (!empty($conf->global->COMPANY_HIDE_INACTIVE_IN_COMBOBOX)) {
			$sql .= " AND s.status <> 0";
		}
		// Add criteria
		if ($filterkey && $filterkey != '') {
			$sql .= " AND (";
			// Can use index if COMPANY_DONOTSEARCH_ANYWHERE is on
			$prefix = empty($conf->global->COMPANY_DONOTSEARCH_ANYWHERE) ? '%' : '';
			// For natural search
			$scriteria = explode(' ', $filterkey);
			$i = 0;
			if (count($scriteria) > 1) {
				$sql .= "(";
			}
			foreach ($scriteria as $crit) {
				if ($i > 0) {
					$sql .= " AND ";
				}
				$sql .= "(s.nom LIKE '" . $db->escape($prefix . $crit) . "%')";
				$i++;
			}
			if (count($scriteria) > 1) {
				$sql .= ")";
			}
			if (!empty($conf->barcode->enabled)) {
				$sql .= " OR s.barcode LIKE '" . $db->escape($prefix . $filterkey) . "%'";
			}
			$sql .= " OR s.code_client LIKE '" . $db->escape($prefix . $filterkey) . "%' OR s.code_fournisseur LIKE '" . $db->escape($prefix . $filterkey) . "%'";
			$sql .= ")";
		}
		$sql .= $db->order("nom", "ASC");
		$sql .= $db->plimit($limit, 0);
		// Build output string
		$resql = $db->query($sql);
		while ($resql && $obj = $db->fetch_object($resql)) {
			$label = '';
			if ($conf->global->SOCIETE_ADD_REF_IN_LIST) {
				if (($obj->client) && (!empty($obj->code_client))) {
					$label = $obj->code_client . ' - ';
				}
				if (($obj->fournisseur) && (!empty($obj->code_fournisseur))) {
					$label .= $obj->code_fournisseur . ' - ';
				}
				$label .= ' ' . $obj->name;
			} else {
				$label = $obj->name;
			}
			if (!empty($obj->name_alias)) {
				$label .= ' (' . $obj->name_alias . ')';
			}
			$response[] = [
				'id' => $obj->rowid,
				'value' => $obj->rowid,
				'text' => $label,
			];
		}
		print json_encode($response);
		break;
	case 'getprojects':
		require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
		$response = [];
		$data = [];
		$filterkey = GETPOST('q', 'alphanohtml');
		$limit = 100;
		// on restreint à une société
		$socid = GETPOST("search_socid", "int") ? GETPOST("search_socid", "int") : GETPOST("socid", "int");
		// l'utilisateur est externe , il ne peux voir que ce qui concerne cette société
		if ($user->socid) {
			$socid = $user->socid;
		}

		// print $formproject->select_projects($socid?$socid:-1, $pid, 'search_projectid', 0, 0, 1, 0, 0, 0, 0, '', 1, 0, 'maxwidth500');
		if ($user->hasRight('projet', 'lire')) {
			$projectsListId = false;
			if (!$user->hasRight('projet', 'all', 'lire')) {
				$projectstatic = new Project($db);
				$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			}
			// Search all projects
			$sql = 'SELECT p.rowid, p.ref, p.title, p.fk_soc, p.fk_statut, p.public, s.nom as name, s.name_alias';
			$sql .= ' FROM ' . MAIN_DB_PREFIX . 'projet as p LEFT JOIN ' . MAIN_DB_PREFIX . 'societe as s ON s.rowid = p.fk_soc';
			$sql .= " WHERE p.entity IN (" . getEntity('project') . ")";
			if ($projectsListId !== false) {
				$sql .= " AND p.rowid IN (" . $projectsListId . ")";
			}
			if ($socid == 0) {
				$sql .= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
			}
			if ($socid > 0) {
				if (empty($conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY)) {
					$sql .= " AND (p.fk_soc = " . $socid . " OR p.fk_soc IS NULL)";
				} elseif ($conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY != 'all') {
					// PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY is 'all' or a list of ids separated by coma.
					$sql .= " AND (p.fk_soc IN (" . $socid . ", " . $conf->global->PROJECT_ALLOW_TO_LINK_FROM_OTHER_COMPANY . ") OR p.fk_soc IS NULL)";
				}
			}
			if (!empty($filterkey)) {
				$sql .= natural_search(['p.title', 'p.ref'], $filterkey);
			}
			$sql .= " ORDER BY p.ref ASC";
			$sql .= $db->plimit($limit, 0);
			// Build output string
			$resql = $db->query($sql);
			while ($resql && $obj = $db->fetch_object($resql)) {
				$label = $obj->ref . ' ' . $obj->title;
				$response[] = [
					'value' => $obj->rowid,
					'text' => $label,
				];
			}
		}
		print json_encode($response);
		break;
	case 'getdolusers':
		$response = [];
		$filterkey = GETPOST('q', 'alphanohtml');
		$noactive = GETPOSTINT('noactive');;
		$force_entity = GETPOSTINT('force_entity');
		$limit = 100;

		if ($user->hasRight('agenda', 'allactions', 'read')) {
			$sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut, u.login, u.admin, u.entity";
			if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
				$sql .= ", e.label";
			}
			$sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
			if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
				$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "entity as e ON e.rowid=u.entity";
				if ($force_entity) {
					$sql .= " WHERE u.entity IN (0," . $force_entity . ")";
				} else {
					$sql .= " WHERE u.entity IS NOT NULL";
				}
			} else {
				if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
					$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ug";
					$sql .= " ON ug.fk_user = u.rowid";
					$sql .= " WHERE ug.entity = " . $conf->entity;
				} else {
					$sql .= " WHERE u.entity IN (0," . $conf->entity . ")";
				}
			}
			if (!empty($user->socid)) {
				$sql .= " AND u.fk_soc = " . $user->socid;
			}
			if (!empty($conf->global->USER_HIDE_INACTIVE_IN_COMBOBOX) || $noactive) {
				$sql .= " AND u.statut <> 0";
			}
			if (!empty($filterkey)) {
				$sql .= natural_search(['u.firstname', 'u.lastname'], $db->escape($filterkey));
			}
			if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) {
				// MAIN_FIRSTNAME_NAME_POSITION is 0 means firstname+lastname
				$sql .= " ORDER BY u.firstname ASC";
			} else {
				$sql .= " ORDER BY u.lastname ASC";
			}
			dol_syslog($sql, LOG_WARNING);
			// Build output string
			$resql = $db->query($sql);
			while ($resql && $obj = $db->fetch_object($resql)) {
				$label = $obj->firstname . ' ' . $obj->lastname;
				$response[] = [
					'id' => $obj->rowid,
					'value' => $obj->rowid,
					'text' => $label,
				];
			}
		}
		print json_encode($response);
		break;
	case 'getdolgroups':
		$response = [];
		$filterkey = GETPOST('q', 'alphanohtml');
		$limit = 100;

		if ($user->hasRight('agenda', 'allactions', 'read')) {
			// $html .= $form->select_dolgroups($usergroupid, 'usergroup', 1, '', ! $canedit);
			// Build output string
			$sql = '';
			$resql = $db->query($sql);
			while ($resql && $obj = $db->fetch_object($resql)) {
				$label = $obj->ref . ' ' . $obj->title;
				$response[] = [
					'id' => (int) $obj->rowid,
					'value' => $obj->rowid,
					'text' => $label,
				];
			}
		}
		print json_encode($response);
		break;
	case 'getresources':
		$response = [];
		if (isModEnabled('resource') && $user->hasRight('agenda', 'allactions', 'read')) {
			// include_once DOL_DOCUMENT_ROOT . '/resource/class/html.formresource.class.php';
			// $formresource = new FormResource($db);

			// // Resource
			// $html .= '<tr>';
			// $html .= '<td class="nowrap" style="padding-bottom: 2px; padding-right: 4px;">';
			// $html .= $langs->trans("Resource");
			// $html .= ' &nbsp;</td><td class="nowrap maxwidthonsmartphone" style="padding-bottom: 2px; padding-right: 4px;">';
			// $html .= $formresource->select_resource_list($resourceid, "search_resourceid", '', 1, 0, 0, null, '', 2);
			// $html .= '</td></tr>';
		}
		print json_encode($response);
		break;
	case 'gettypeactions':
		$preselectedtypes = [];
		$response = [];
		if (!empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE)) {
			$preselectedtypes = explode(',', $conf->global->AGENDA_DEFAULT_FILTER_TYPE);
		}
		$sql = "SELECT id, code, libelle as label, module, type, color, picto";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_actioncomm";
		$sql .= " WHERE active=1";
		$sql .= " ORDER BY module, position, type";
		$resql = $db->query($sql);
		while ($resql && $obj = $db->fetch_array($resql)) {
			$obj['selected'] = in_array($obj['code'], $preselectedtypes);
			//$keyfortrans = "Action".$obj['code'].'Short';
			//$obj['label'] = $langs->trans($keyfortrans);
			$obj['label'] = $langs->trans($obj['label']);
			$response[] = $obj;
		}
		// if ($user->rights->agenda->allactions->read) {
		// }
		print json_encode($response);
		break;
	case 'getstates':
		$preselectedstates = [];
		$response = [];
		$sql = "SELECT d.rowid as id, d.code_departement as code, d.nom as label, d.fk_region as region_id, r.nom as region, c.code as country_code, c.label as country, d.active";
		$sql .= " FROM " . MAIN_DB_PREFIX . "c_departements as d, ";
		$sql .= MAIN_DB_PREFIX . "c_regions as r, ";
		$sql .= MAIN_DB_PREFIX . "c_country as c";
		$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=c.rowid and r.active=1 and c.active=1 and fk_pays=1";
		$sql .= " ORDER BY d.code_departement";

		$resql = $db->query($sql);
		while ($resql && $obj = $db->fetch_array($resql)) {
			$obj['selected'] = in_array($obj['code'], $preselectedstates);
			$obj['label'] = $obj['code'] . '-' . $langs->trans($obj['label']);
			$response[] = $obj;
		}
		print json_encode($response);
		break;
	default:
		print json_encode([]);
		break;
}
$db->close();

/**
 * get events.
 *
 * @param   string  $resourceId     calendar id
 *
 * @return array<int,array{id:int,resourceId:string}> array of events
 */
function getDeletedEventsId($resourceId)
{
	global $db;
	$events = [];
	if ($resourceId != '1') {
		return $events;
	}
	$sql = "SELECT fk_actioncomm FROM " . MAIN_DB_PREFIX . "actioncomm_deleted WHERE tms>'" . (int) (time() - (3 * 60 * 60)) . "'";
	$resql = $db->query($sql);
	while ($resql && $obj = $db->fetch_object($resql)) {
		$events[] = [
			'id' => (int) $obj->fk_actioncomm,
			'resourceId' => $resourceId,
		];
	}

	return $events;
}

/**
 * get events.
 *
 * @param   string  $resourceId     calendar id
 * @param   string  $calendarName   calendar name
 * @param   int     $startDate      start date
 * @param   int     $endDate        end date
 * @param   int     $offset         timezone offset
 * @param   bool    $onlylast       only last refreshed events
 * @param   string  $search_actioncode actions code comma separated
 * @param   int     $search_userid  user id to search
 * @param   int     $socid          customer id
 * @param   string  $search_states  comma separated list of states
 * @param   string  $search_all     search everywhere
 *
 * @return array<int,array<string,mixed>>
 */
function getEvents($resourceId, $calendarName, $startDate, $endDate, $offset, $onlylast, $search_actioncode, $search_userid, $socid, $search_states, $search_all)
{
	global $db, $conf, $langs, $user, $hookmanager;

	$events = [];
	$now = dol_now('gmt');
	$date = new DateTime();
	$timeZone = $date->getTimezone();
	$servertz = $timeZone->getName();

	$hookmanager->initHooks(['agenda']);

	if ($resourceId == '1') {
		$pid = GETPOST("projectid", "int", 3);
		$status = GETPOST("status", 'int');
		$type = GETPOST("type", 'alpha');

		// $maxprint = (GETPOST("maxprint") ? GETPOST("maxprint") : $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
		$actioncode = explode(',', $search_actioncode);

		$usergroup = GETPOST("usergroup", "int", 3);
		if (empty($search_userid) && !getDolGlobalInt('AGENDA_ALL_CALENDARS')) {
			$search_userid = $user->id;
		}
		// $socid = (int) GETPOST("socid", "int");
		// $socid = 0;
		if ($user->socid) {
			$socid = $user->socid;
		}
		// timestamp en millisecondes
		$t_start = $startDate / 1000;
		$t_end = $endDate / 1000;

		$sql = 'SELECT ';
		if ($usergroup > 0) {
			$sql .= " DISTINCT";
		}
		$sql .= ' a.id, a.label, a.datep, a.datep2, a.percent,';
		$sql .= ' a.fk_user_author,a.fk_user_action,';
		$sql .= ' a.transparency, a.priority, a.fulldayevent, a.location,';
		$sql .= ' a.fk_soc, a.fk_contact, a.note,';
		$sql .= ' u.color,';
		$sql .= ' ca.color as type_color, ca.code as type_code, ca.libelle as type_label';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . "actioncomm as a";
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'c_actioncomm as ca ON (a.fk_action = ca.id)';
		$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'user u ON (a.fk_user_action=u.rowid )';
		if (!empty($search_states)) {
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'societe s ON (s.rowid = a.fk_soc)';
			$sql .= ' LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople sp ON (sp.rowid = a.fk_contact)';
		}
		if (!$user->hasRight('societe', 'client', 'voir') && !$socid) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
		}
		// We must filter on assignment table
		if ($search_userid > 0 || $usergroup > 0) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm_resources as ar ON (ar.fk_actioncomm = a.id)";
		}
		if ($usergroup > 0) {
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
		}
		$sql .= ' WHERE  a.entity IN (' . getEntity('agenda', 1) . ')';
		if (!empty($actioncode)) {
			// verifier avec le code du dictionaire
			// a.code au lieu de ca.code
			// $sql .= " AND ca.code IN ('".implode("','", $actioncode)."')";
			//$sql .= " AND a.code IN ('" . implode("','", $actioncode) . "')";
		}
		if (getDolGlobalInt('EVENT_CALENDAR_DONT_SHOW_AUTO_EVENTS') && strpos(implode(',', $actioncode), 'AC_OTH_AUTO') === false) {
			// a.code au lieu de ca.code
			$sql .= " AND ca.code != 'AC_OTH_AUTO'";
			// avec a.code ça ne marche pas...
			// $sql .= " AND a.code != 'AC_OTH_AUTO'";
		}
		if ($pid) {
			$sql .= " AND a.fk_project=" . $db->escape($pid);
		}
		if (!$user->rights->societe->client->voir && !$socid) {
			$sql .= " AND (a.fk_soc IS NULL OR sc.fk_user = " . $user->id . ")";
		}
		if ($socid > 0) {
			$sql .= ' AND a.fk_soc = ' . $socid;
		}
		if (!empty($search_states)) {
			$sql .= ' AND (s.fk_departement IN (' . $db->escape($search_states) . ') OR sp.fk_departement IN (' . $db->escape($search_states) . ')';
			// on recherche les codes des départements...
			$sqlcode = "SELECT d.rowid as id, d.code_departement as code";
			$sqlcode .= " FROM " . MAIN_DB_PREFIX . "c_departements as d";
			$sqlcode .= " WHERE d.rowid IN (" . $db->escape($search_states) . ")";
			$resqlcode = $db->query($sqlcode);
			while ($resqlcode && $objc = $db->fetch_object($resqlcode)) {
				$sql .= ' OR s.zip LIKE "' . $objc->code . '%"';
				$sql .= ' OR sp.zip LIKE "' . $objc->code . '%"';
			}
			$sql .= ')';
		}
		// We must filter on assignment table
		if ($search_userid > 0 || $usergroup > 0) {
			$sql .= " AND ar.element_type='user'";
		}
		$sql .= " AND ((a.datep2 BETWEEN '" . $db->idate($t_start) . "' AND '" . $db->idate($t_end) . "')
						OR (a.datep BETWEEN '" . $db->idate($t_start) . "' AND '" . $db->idate($t_end) . "')
						OR (a.datep < '" . $db->idate($t_start) . "' AND a.datep2 > '" . $db->idate($t_end) . "'))";
		if ($type) {
			$sql .= " AND ca.id = " . $type;
		}
		if ($status == '0') {
			$sql .= " AND a.percent = 0";
		}
		if ($status == '-1') {
			// Not applicable
			$sql .= " AND a.percent = -1";
		}
		if ($status == '50') {
			// Running already started
			$sql .= " AND (a.percent > 0 AND a.percent < 100)";
		}
		if ($status == 'done' || $status == '100') {
			$sql .= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep2 <= '" . $db->idate($now) . "'))";
		}
		if ($status == 'todo') {
			$sql .= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '" . $db->idate($now) . "'))";
		}
		// We must filter on assignment table
		if ($search_userid > 0 || $usergroup > 0) {
			$sql .= " AND (";
			if ($search_userid > 0) {
				$sql .= "ar.fk_element = " . $search_userid;
			}
			if ($usergroup > 0) {
				$sql .= ($search_userid > 0 ? " OR " : "") . " ugu.fk_usergroup = " . $usergroup;
			}
			$sql .= ")";
		}
		if ($onlylast) {
			$sql .= " AND a.tms > '" . $db->idate($now - 180, 'gmt') . "'";
		}
		if ($search_all) {
			// le champs de recherche Divers, ça se passe ici
			$sql .= natural_search(['a.note', 'a.label', 'a.location'], $search_all);
		}
		// Sort on date
		$sql .= ' ORDER BY datep';
		// SELECT a.id, a.label, a.datep, a.datep2, a.percent, a.fk_user_author,a.fk_user_action, a.transparency, a.priority, a.fulldayevent, a.location, a.fk_soc, a.fk_contact,a.note, u.color, ca.color as type_color, ca.code as type_code, ca.libelle as type_label
		// FROM llx_actioncomm as a
		// LEFT JOIN llx_c_actioncomm as ca ON (a.fk_action=ca.id)
		// LEFT JOIN llx_user u ON (a.fk_user_action=u.rowid )
		// LEFT JOIN llx_actioncomm_resources as ar ON (ar.fk_actioncomm = a.id)
		// WHERE a.entity IN (1)
		// AND ar.element_type='user'
		// AND ((a.datep2>='2019-08-18 00:00:00' AND datep<='2019-10-15 00:00:00') OR (a.datep BETWEEN '2019-08-18 00:00:00' AND '2019-10-15 00:00:00'))
		// AND (ar.fk_element = 1) ORDER BY datep
		// print $sql;
		$resql = $db->query($sql);

		$CacheSociete = [];
		$CacheContact = [];
		$CacheUser = [];
		$CacheProject = [];

		while ($resql && $obj = $db->fetch_object($resql)) {
			$event = new ActionComm($db);
			$event->fetch($obj->id);
			$event->fetch_thirdparty();
			$event->fetchObjectLinked();
			// $event->fetch_optionals();
			$event->fetch_userassigned();
			$event->color = $obj->color ? '#' . $obj->color : '';
			$event->type_color = $obj->type_color;

			$raw = new stdClass();
			$raw->location = !empty($event->location) ? $event->location : '';
			$isallday = $event->fulldayevent ? true : false;

			$tz = new \DateTimeZone($servertz);
			$dtstart = new DateTime();
			$dtstart->setTimezone($tz);
			$dtstart->setTimestamp($event->datep);
			$offset_start = $dtstart->getOffset();
			// on recalcule avec l'offset
			$dtstart->setTimestamp($event->datep - $offset_start);

			$dtend = new DateTime();
			$dtend->setTimezone($tz);
			$dtend->setTimestamp((empty($event->datef) ? ($event->datep - $offset_start + 10) : ($event->datef)));
			$offset_end = $dtend->getOffset();
			// on recalcule avec l'offset
			$dtend->setTimestamp((empty($event->datef) ? ($event->datep - $offset_start + 10) : ($event->datef - $offset_end)));

			$assignedUsers = [];
			foreach ($event->userassigned as $key => $value) {
				if (!isset($CacheUser[$value['id']])) {
					$CacheUser[$value['id']] = new User($db);
					$CacheUser[$value['id']]->fetch($value['id']);
					$CacheUser[$value['id']]->tooltip = $CacheUser[$value['id']]->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom');
				}
				//$assignedUsers[] = dolGetFirstLastname($CacheUser[$value['id']]->firstname, $CacheUser[$value['id']]->lastname);
				$assignedUsers[] = $CacheUser[$value['id']]->tooltip;
			}
			// Is Editable ?
			$isEditable = true;
			if (($event->type_code == 'AC_OTH_AUTO') || (($user->id != $event->userownerid) && !$user->rights->agenda->allactions->create)) {
				$isEditable = false;
			}

			$events[] = [
				// id : The unique schedule id depends on calendar id
				'id' => (int) $event->id,
				// resourceId : The unique calendar id
				'resourceId' => 1,
				// title : The schedule title
				'title' => $event->label,
				// body : The schedule body text which is text/plain
				'body' => $event->note_private,
				'start' => $dtstart->format(DATE_ATOM),
				'end' => $dtend->format(DATE_ATOM),
				'editable' => $isEditable,
				'startEditable' => $isEditable,
				'durationEditable' => $isEditable,
				'allDay' => $isallday,
				// color : The schedule text color (black or white)
				'textColor' => ($obj->color != '' && isDarkColor($obj->color)) ? '#ffffff' : '#000000',
				// bgColor : The schedule background color
				'backgroundColor' => $event->color,
				// borderColor : The schedule border color
				'borderColor' => $event->color,
				// dragBgColor : The schedule drag background color
				'dragBgColor' => $event->color,
				'attendees' => $assignedUsers,
				// busy or free
				'state' => '',
				'location' => $event->location,
				// raw : The user data
				'raw' => $raw,
			];
		}
	}

	// Complete $eventarray with birthdates
	if ($resourceId == '2') {
		// Add events in array
		$sql = 'SELECT sp.rowid, sp.lastname, sp.firstname, sp.birthday';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'socpeople as sp';
		$sql .= ' WHERE (priv=0 OR (priv=1 AND fk_user_creat=' . $user->id . '))';
		$sql .= " AND sp.entity IN (" . getEntity('socpeople') . ")";

		$sql .= ' AND (MONTH(birthday) = ' . date("m", $startDate / 1000);
		$sql .= ' OR MONTH(birthday) = "12"';
		$sql .= ' OR MONTH(birthday) = ' . date("m", $endDate / 1000) . ')';
		//$sql.= ' AND DAY(birthday) >= '.date("d", strtotime($startDate));
		//$sql.= ' AND DAY(birthday) <= '.date("d", strtotime($endDate));
		$sql .= ' ORDER BY birthday';

		$resql = $db->query($sql);
		while ($resql && $obj = $db->fetch_object($resql)) {
			// A SUPPRIMER pas besoin de ActionComm
			// $event = new ActionComm($db);
			// We put contact id in action id for birthdays events
			// may create conflicts ??
			// $event->id = $obj->rowid;
			$datebirth = dol_stringtotime($obj->birthday, 1);
			// print 'ee'.$obj->birthday.'-'.$datebirth;
			$datearray = dol_getdate($datebirth, true);
			// determiner correctement le choix de l'année
			// For full day events, date are also GMT but they won't but converted during output
			$datep = dol_mktime(0, 0, 0, $datearray['mon'], $datearray['mday'], (int) date("Y", $endDate / 1000), true);
			// $event->datef = $event->datep;
			// $event->type_code = 'BIRTHDAY';
			// $event->label = $langs->trans("Birthday") . ' ' . dolGetFirstLastname($obj->firstname, $obj->lastname);
			// $event->percentage = 100;
			// $event->fulldayevent = 1;
			// $event->ponctuel = 0;
			// $event->note = '';
			// // Add an entry in actionarray for each day
			// $daycursor = $date_start_in_calendar;
			// $year = date('Y', $daycursor);
			// $month = date('m', $daycursor);
			// $day = date('d', $daycursor);
			// $loop = true;
			// $daykey=dol_mktime(0, 0, 0, $month, $day, $year);
			// do {
			//     $eventarray[$daykey][] = $event;
			//     $daykey += 60*60*24;
			//     if ($daykey > $date_end_in_calendar) {
			//         $loop=false;
			//     }
			// } while ($loop);
			$raw = new stdClass();
			$raw->location = !empty($event->location) ? $event->location : '';
			$events[] = [
				// id : The unique schedule id depends on calendar id
				'id' => 'birthday_' . (string) $obj->rowid,
				// resourceId : The unique calendar id
				'resourceId' => 2,
				// title : The schedule title
				'title' => $langs->trans("Birthday") . ' ' . dolGetFirstLastname($obj->firstname, $obj->lastname),
				// body : The schedule body text which is text/plain
				'start' => dol_print_date($datep, "%Y-%m-%dT%H:%M:%S"),
				'end' => dol_print_date($datep + 86400, "%Y-%m-%dT%H:%M:%S"),
				// birthdays are readonly
				'editable' => false,
				'allDay' => true,
				// color : The schedule text color
				'textColor' => isDarkColor($obj->color) ? '#ffffff' : '#000000',
				// bgColor : The schedule background color
				'backgroundColor' => $obj->color,
				// borderColor : The schedule border color
				'borderColor' => $obj->color,
				// raw : The user data
				// 'raw' => $raw,
			];
		}
	}
	$listofextcals = [];
	if ($resourceId > 2) {
		$MAXAGENDA = getDolGlobalInt('AGENDA_EXT_NB', 5);
		// Define list of external calendars (global admin setup)
		if (empty($conf->global->AGENDA_DISABLE_EXT)) {
			$i = 0;
			while ($i < $MAXAGENDA) {
				$i++;
				$source = 'AGENDA_EXT_SRC' . $i;
				$name = 'AGENDA_EXT_NAME' . $i;
				$offsettz = 'AGENDA_EXT_OFFSETTZ' . $i;
				$color = 'AGENDA_EXT_COLOR' . $i;
				$cachetime = 'AGENDA_EXT_CACHE' . $i;
				//$buggedfile = 'AGENDA_EXT_BUGGEDFILE' . $i;
				if (!empty($conf->global->$source) && !empty($conf->global->$name)) {
					// Note: $conf->global->buggedfile can be empty
					// or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
					if ($calendarName == $conf->global->$name) {
						$listofextcals[] = [
							'cachename' => 'global',
							'cachetime' => $conf->global->$cachetime ?? -1,
							'number' => md5($conf->global->$name),
							'src' => $conf->global->$source,
							'name' => $conf->global->$name,
							'offsettz' => empty($conf->global->$offsettz) ? 0 : $conf->global->$offsettz,
							'color' => $conf->global->$color,
							//'buggedfile' => (isset($conf->global->buggedfile) ? $conf->global->buggedfile : 0),
						];
					}
				}
			}
		}
		// Define list of external calendars (user setup)
		if (empty($user->conf->AGENDA_DISABLE_EXT)) {
			$i = 0;
			while ($i < $MAXAGENDA) {
				$i++;
				$source = 'AGENDA_EXT_SRC_' . $user->id . '_' . $i;
				$name = 'AGENDA_EXT_NAME_' . $user->id . '_' . $i;
				$offsettz = 'AGENDA_EXT_OFFSETTZ_' . $user->id . '_' . $i;
				$color = 'AGENDA_EXT_COLOR_' . $user->id . '_' . $i;
				$cachetime = 'AGENDA_EXT_CACHE_' . $user->id . '_' . $i;
				//$enabled = 'AGENDA_EXT_ENABLED_' . $user->id . '_' . $i;
				//$buggedfile = 'AGENDA_EXT_BUGGEDFILE_' . $user->id . '_' . $i;
				if (!empty($user->conf->$source) && !empty($user->conf->$name)) {
					// Note: $conf->global->buggedfile can be empty or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
					if ($calendarName == $user->conf->$name) {
						$listofextcals[] = [
							'cachename' => 'private',
							'cachetime' => $user->conf->$cachetime ?? -1,
							'number' => md5($user->conf->$name),
							'src' => $user->conf->$source,
							'name' => $user->conf->$name,
							'offsettz' => empty($user->conf->$offsettz) ? 0 : $user->conf->$offsettz,
							'color' => $user->conf->$color,
							//'buggedfile' => (isset($user->conf->buggedfile) ? $user->conf->buggedfile : 0),
						];
					}
				}
			}
		}
	}
	//
	// Complete $eventarray with external import Ical
	// if (count($listofextcals)) {
	// 	$firstdaytoshow = $startDate;
	// 	$lastdaytoshow = $endDate;

	// 	// require_once DOL_DOCUMENT_ROOT . '/comm/action/class/ical.class.php';
	// 	// cache des fichiers ics
	// 	require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
	// 	// cette valeur peut se trouver dans le ical source, mais pas toujours
	// 	$cachetime = 7200;   // 7200 : 120mn
	// 	$cachedir = DOL_DATA_ROOT . '/agenda/temp';

	// 	foreach ($listofextcals as $extcal) {
	// 		$url = $extcal['src'];
	// 		$namecal = $extcal['name'];
	// 		$offsettz = $extcal['offsettz'];
	// 		$colorcal = $extcal['color'];
	// 		//$buggedfile = $extcal['buggedfile'];
	// 		//print "url=".$url." namecal=".$namecal." colorcal=".$colorcal." buggedfile=".$buggedfile;
	// 		if ($extcal['cachename'] == 'private') {
	// 			$fileid = 'u' . $user->id . '-' . $extcal['number'] . '.cache';
	// 			$cachetime = ($extcal['cachetime'] > 0 ? $extcal['cachetime'] : 3600);   // 3600 : 60mn
	// 		} else {
	// 			$fileid = $extcal['number'] . '.cache';
	// 			$cachetime = ($extcal['cachetime'] > 0 ? $extcal['cachetime'] : 1800);   // 1800 : 30mn
	// 		}
	// 		$filename = '/ical-e' . $conf->entity . '-' . $fileid;
	// 		$refresh = dol_cache_refresh($cachedir, $filename, (int) $cachetime);
	// 		// dol_include_once('/prune/vendor/autoload.php');
	// 		// on cache le fichier si besoin
	// 		if ($refresh) {
	// 			try {
	// 				$ical = new ICal(false, [
	// 					// Default value
	// 					'defaultSpan' => 2,
	// 					'defaultTimeZone' => 'Europe/Paris',
	// 					// Default value
	// 					'defaultWeekStart' => 'MO',
	// 					// Default value
	// 					'disableCharacterReplacement' => false,
	// 					// Default value
	// 					'filterDaysAfter' => null,
	// 					// Default value
	// 					'filterDaysBefore' => null,
	// 					// Default value
	// 					'skipRecurrence' => false,
	// 				]);
	// 				// $ical->initFile(DOL_DATA_ROOT . '/agenda/temp/ICal.ics');
	// 				$ical->initUrl($url);
	// 			} catch (Exception $e) {
	// 				//die($e);
	// 				return [];
	// 			}
	// 			dol_syslog('Ical : ' . $namecal . ' cachetime : ' . print_r($ical->events(), true), LOG_WARNING);
	// 			// on cache le fichier parsé
	// 			dol_filecache($cachedir, $filename, $ical);
	// 		} else {
	// 			dol_syslog('reading Ical from cache : ' . $namecal . ' cachetime : ' . $cachetime, LOG_DEBUG);
	// 			// on récupère le fichier déjà parsé
	// 			$ical = dol_readcachefile($cachedir, $filename);
	// 		}
	// 		// pour faire des dumps dans la librairie ical, il faut désactiver le cache...
	// 		// print '<pre>' . print_r($ical, true)  . '</pre>';
	// 		$icalevents = $ical->events();

	// 		// Loop on each entry into cal file to know if entry is qualified and add an ActionComm into $eventarray
	// 		foreach ($icalevents as $icalevent) {
	// 			//print '<pre>' . print_r($icalevent, true)  . '</pre>';
	// 			$fulldayevent = false;
	// 			if (isset($icalevent->dtstart_array[0]['VALUE']) && $icalevent->dtstart_array[0]['VALUE'] == 'DATE') {
	// 				$fulldayevent = true;
	// 			}

	// 			$datep = $icalevent->dtstart_array[2] + ($offsettz * 3600);
	// 			if (isset($icalevent->dtend_array[2])) {
	// 				// si fulldayevent on retire 1 sec pour avoir 23.59.59
	// 				$datef = $icalevent->dtend_array[2] + ($offsettz * 3600) - ($fulldayevent ? 1 : 0);
	// 			} else {
	// 				$datef = $datep;
	// 			}

	// 			$date_start_in_calendar = $datep;

	// 			if ($datef != '' && $datef >= $datep) {
	// 				$date_end_in_calendar = $datef;
	// 			} else {
	// 				$date_end_in_calendar = $datep;
	// 			}

	// 			// Add event into $events if date range are ok.
	// 			if ($date_end_in_calendar < $firstdaytoshow || $date_start_in_calendar >= $lastdaytoshow) {
	// 				//print '<pre>' . print_r($icalevent, true)  . '</pre>';
	// 				//print '<pre>' . $icalevent->printData()  . '</pre>';
	// 				//print 'x'.$datestart.'-'.$dateend;exit;
	// 				//print 'x'.$datestart.'-'.$dateend;exit;
	// 				//print 'x'.$datestart.'-'.$dateend;exit;
	// 				// This record is out of visible range
	// 			} else {
	// 				$dtstart = new DateTime();
	// 				$dtstart->setTimestamp($date_start_in_calendar);
	// 				$dtstart->setTimezone(new DateTimeZone($ical->defaultTimeZone));
	// 				$dtend = new DateTime();
	// 				$dtend->setTimestamp($date_end_in_calendar);
	// 				$dtend->setTimezone(new DateTimeZone($ical->defaultTimeZone));

	// 				$events[] = [
	// 					// id : The unique schedule id depends on calendar id
	// 					'id' => $icalevent->uid,
	// 					// resourceId : The unique calendar id
	// 					'resourceId' => $resourceId,
	// 					// title : The schedule title
	// 					'title' => html_entity_decode($icalevent->summary),
	// 					// body : The schedule body text which is text/plain
	// 					'body' => nl2br($icalevent->description),
	// 					// 'start' => dol_print_date($date_start_in_calendar, "%Y-%m-%dT%H:%M:%S").'+03:00',
	// 					// 'end' => dol_print_date($date_end_in_calendar, "%Y-%m-%dT%H:%M:%S").'+03:00',
	// 					'start' => $dtstart->format('Y-m-d H:i:sP'),
	// 					'end' => $dtend->format('Y-m-d H:i:sP'),
	// 					// icals are readonly
	// 					'editable' => false,
	// 					'allDay' => $fulldayevent,
	// 					// color : The schedule text color
	// 					'color' => isDarkColor($colorcal) ? '#ffffff' : '#000000',
	// 					// bgColor : The schedule background color
	// 					'bgColor' => '#' . $colorcal,
	// 					// borderColor : The schedule border color
	// 					'borderColor' => '#' . $colorcal,
	// 					'location' => $icalevent->location,
	// 					// raw : The user data
	// 					'raw' => new stdClass(),
	// 				];
	// 			}
	// 		}
	// 	}
	// }
	return $events;
}

/**
 * function to check if a color is dark
 *
 * @param string $color color string
 * @return bool
 */
function isDarkColor($color)
{
	if (empty($color)) {
		return false;
	}
	$lightness_swap = getDolGlobalInt('EVENT_CALENDAR_LIGTHNESS_SWAP', 155);

	$rgb = HTMLToRGB($color);
	$hsl = RGBToHSL($rgb);

	return ($hsl['lightness'] < $lightness_swap) ? true : false;
}

/**
 * function HTMLToRGB
 *
 * @param string $htmlCode html code
 * @return int|float
 */
function HTMLToRGB($htmlCode)
{
	if ($htmlCode[0] == '#') {
		$htmlCode = substr($htmlCode, 1);
	}

	if (strlen($htmlCode) == 3) {
		$htmlCode = $htmlCode[0] . $htmlCode[0] . $htmlCode[1] . $htmlCode[1] . $htmlCode[2] . $htmlCode[2];
	}

	$r = hexdec($htmlCode[0] . $htmlCode[1]);
	$g = hexdec($htmlCode[2] . $htmlCode[3]);
	$b = hexdec($htmlCode[4] . $htmlCode[5]);

	return $b + ($g << 0x8) + ($r << 0x10);
}

/**
 * RGB to HSL
 * @param float|int $RGB RGB color
 * @return array{hue:int,saturation:int,lightness:int}
 */
function RGBToHSL($RGB)
{
	$r = 0xFF & ($RGB >> 0x10);
	$g = 0xFF & ($RGB >> 0x8);
	$b = 0xFF & $RGB;
	$h = 0;

	$r = ((float) $r) / 255.0;
	$g = ((float) $g) / 255.0;
	$b = ((float) $b) / 255.0;

	$maxC = max($r, $g, $b);
	$minC = min($r, $g, $b);

	$l = ($maxC + $minC) / 2.0;

	if ($maxC == $minC) {
		$s = 0;
		$h = 0;
	} else {
		if ($l < .5) {
			$s = ($maxC - $minC) / ($maxC + $minC);
		} else {
			$s = ($maxC - $minC) / (2.0 - $maxC - $minC);
		}
		if ($r == $maxC) {
			$h = ($g - $b) / ($maxC - $minC);
		}
		if ($g == $maxC) {
			$h = 2.0 + ($b - $r) / ($maxC - $minC);
		}
		if ($b == $maxC) {
			$h = 4.0 + ($r - $g) / ($maxC - $minC);
		}
		$h = $h / 6.0;
	}
	$h = (int) round(255.0 * $h);
	$s = (int) round(255.0 * $s);
	$l = (int) round(255.0 * $l);

	return ['hue' => $h, 'saturation' => $s, 'lightness' => $l];
}
