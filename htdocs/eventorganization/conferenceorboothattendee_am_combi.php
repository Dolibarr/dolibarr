<?php
/* Copyright (C) 2026		Direct copy of htdocs/societe/agenda.php
 * Copyright (C) 2026		Jon Bendtsen			<jon.bendtsen.github@jonb.dk>
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
 *  \file       htdocs/eventorganization/conferenceorboothattendee_am_combi.php
 *  \ingroup    eventorganization
 *  \brief      Page of conference attendee events
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
// require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';
require_once DOL_DOCUMENT_ROOT.'/eventorganization/lib/eventorganization_conferenceorbooth.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('agenda', 'eventorganization'));

$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'attendeeagenda';

if (GETPOSTISARRAY('actioncode')) {
	$actioncode = GETPOST('actioncode', 'array:alpha', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	} else {
		$actioncode = implode(',', $actioncode);
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}

$search_rowid = GETPOST('search_rowid');
$search_agenda_label = GETPOST('search_agenda_label');
$search_complete = GETPOST('search_complete');
$search_filtert = GETPOSTINT('search_filtert');
$search_dateevent_start = GETPOSTDATE('dateevent_start');
$search_dateevent_end = GETPOSTDATE('dateevent_end');
$withproject = GETPOSTINT('withproject');
$combi = GETPOST('combi');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

// Initialize a technical objects
$attendeestatic = new ConferenceOrBoothAttendee($db);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('attendeeagenda', 'agendaattendee', 'globalcard'));

// Security check
$attendeeid = GETPOSTINT('attendeeid');
$result = $attendeestatic->fetch($attendeeid);
if ($result <= 0) {
	dol_syslog('Can not find attendee='.$attendeeid, LOG_ERR);
	accessforbidden($langs->trans("ErrorObjectNotFound", $langs->trans("ConferenceOrBoothAttendee")));
}
$fk_project=0;
$projectstatic = new Project($db);
if (isset($attendeestatic->fk_project)) {
	$fk_project = $attendeestatic->fk_project;
	$projectfetchresult = $projectstatic->fetch($attendeestatic->fk_project);
	if ($projectfetchresult < 0) {
		dol_syslog('Can not find attendee project id='.$attendeestatic->fk_project, LOG_ERR);
		setEventMessages($langs->trans("ErrorRefNotFound", $langs->trans("Project")), null, 'errors');
		header("Location: " . DOL_URL_ROOT . "/eventorganization/conferenceorboothattendee_card.php?id=" . $attendeeid);
		exit;
	}
}

$result = restrictedArea($user, 'projet', $attendeestatic->fk_project, 'projet&project');



/*
 *	Actions
 */

$parameters = array('id' => $attendeeid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $attendeestatic, $action); // Note that $action and $attendeestatic may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$actioncode = '';
		$search_rowid = '';
		$search_agenda_label = '';
		$search_complete = '';
		$search_filtert = '';
	}
}



/*
 *	View
 */

$title = $langs->trans("Agenda");
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/attendeenameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $attendeestatic->getFullName($langs, 0, -1)) {
	$title = $attendeestatic->getFullName($langs)." - ".$title;
}
$help_url = '';
llxHeader('', $title, $help_url);

// Tabs for project
$tab = 'eventorganisation';

$head = project_prepare_head($projectstatic);

print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'), 0, '', '');

// Project card
$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$project_morehtmlref = '<div class="refidno">';
// Title
$project_morehtmlref .= $projectstatic->title;
// Thirdparty
if (isset($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
	$project_morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
}
$project_morehtmlref .= '</div>';

// Define a complementary filter for search of next/prev ref.
if (!$user->hasRight('project', 'all', 'lire')) {
	$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
	$projectstatic->next_prev_filter = "rowid:IN:".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0');
}

dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $project_morehtmlref);

print dol_get_fiche_end();

$moreparam = '';
if (isset($withproject)) {
	$moreparam .= '&withproject=1';
}
if ($fk_project) {
	$moreparam .= '&fk_project='.((int) $fk_project);
}

$head = conferenceorboothAttendeePrepareHead($attendeestatic);
print dol_get_fiche_head($head, 'agenda', $langs->trans("ConferenceOrBoothAttendee"), -1, $attendeestatic->picto);

$linkback = '<a href="'.DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_card.php?id='.$attendeeid.'&restore_lastsearch_values=1&withproject=1">'.$langs->trans("BackToCard").'</a>';

$attendee_morehtmlref = '<div class="refidno">';
// Attendee
$attendee_morehtmlref .= $attendeestatic->getNomUrl(1, "conferenceorboothattendee");
// Full Name
$attendee_morehtmlref .= '<br>'.$attendeestatic->getFullName($langs);
$attendee_morehtmlref .= '</div>';

dol_banner_tab($attendeestatic, 'ref', $linkback, 1, 'rowid', 'rowid', $attendee_morehtmlref);

print '<div class="fichecenter">';

print '<div class="underbanner clearboth"></div>';

$attendeestatic->info($attendeeid);
dol_print_object_info($attendeestatic, 1);

print '</div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();


// Actions buttons
$addActionUrl = '';
$permok = $user->hasRight('agenda', 'myactions', 'create');
if ((!empty($attendeestatic->id)) && $permok) {
	// Build the clean URL for adding an action
	$addActionUrl = DOL_URL_ROOT.'/comm/action/card.php?action=create';
	$addActionUrl .= '&origin='.$attendeestatic->element.'@'.$attendeestatic->module;
	$addActionUrl .= '&originid='.$attendeestatic->id; // This is the attendee ID
	// Add project if available
	if (!empty($attendeestatic->fk_project)) {
		$addActionUrl .= '&projectid='.$attendeestatic->fk_project;
	}
	// Add thirdparty if available
	if (!empty($attendeestatic->fk_soc)) {
		$addActionUrl .= '&socid='.$attendeestatic->fk_soc;
	}
	$addActionUrl .= '&backtopage='.urlencode($_SERVER['PHP_SELF'].'?attendeeid='.$attendeestatic->id);
	$addActionUrl .= '&datep='.dol_print_date(dol_now(), 'dayhourlog', 'tzuserrel');
}

$morehtmlright = '';

$messagingUrl = DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_am_combi.php?combi=messaging&attendeeid='.$attendeeid;
$agendaUrl = DOL_URL_ROOT.'/eventorganization/conferenceorboothattendee_am_combi.php?combi=agenda&attendeeid='.$attendeeid;

// Determine button status based on current view
$buttonstatus_messaging = ($combi == 'messaging') ? 2 : 1;
$buttonstatus_agenda   = ($combi == 'agenda')   ? 2 : 1;

$morehtmlright .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', $buttonstatus_messaging);
$morehtmlright .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $agendaUrl, '', $buttonstatus_agenda);

if (isModEnabled('agenda')) {
	if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
		$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', $addActionUrl);
	}
}

if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
	print '<br>';

	$param = '&attendeeid='.urlencode((string) ($attendeeid));
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($search_rowid) {
		$param .= '&search_rowid='.urlencode($search_rowid);
	}
	if ($actioncode !== '' && $actioncode !== '-1') {
		$param .= '&actioncode='.urlencode($actioncode);
	}
	if ($search_agenda_label) {
		$param .= '&search_agenda_label='.urlencode($search_agenda_label);
	}
	if ($search_complete != '') {
		$param .= '&search_complete='.urlencode($search_complete);
	}
	if ($search_filtert != '') {
		$param .= '&search_filtert='.urlencode((string) $search_filtert);
	}
	if ($search_dateevent_start != '') {
		$param .= '&dateevent_startyear='.GETPOSTINT('dateevent_startyear');
		$param .= '&dateevent_startmonth='.GETPOSTINT('dateevent_startmonth');
		$param .= '&dateevent_startday='.GETPOSTINT('dateevent_startday');
	}
	if ($search_dateevent_end != '') {
		$param .= '&dateevent_endyear='.GETPOSTINT('dateevent_endyear');
		$param .= '&dateevent_endmonth='.GETPOSTINT('dateevent_endmonth');
		$param .= '&dateevent_endday='.GETPOSTINT('dateevent_endday');
	}

	// Try to know count of actioncomm from cache
	require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
	$cachekey = 'count_events_attendee_'.$attendeestatic->id;
	$nbEvent = dol_getcache($cachekey);

	$titlelist = $langs->trans("ActionsOnAttendee").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
	if (!empty($conf->dol_optimize_smallscreen)) {
		$titlelist = $langs->trans("Actions").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
	}

	print_barre_liste($titlelist, 0, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlright, '', 0, 1, 0);

	// List of all actions
	$filters = array();
	$filters['search_agenda_label'] = $search_agenda_label;
	$filters['search_rowid'] = $search_rowid;
	$filters['search_complete'] = $search_complete;		// Can be 'na', '0', '100', '50'
	$filters['search_filtert'] = $search_filtert;

	// TODO Replace this with the same code than into list.php
	if ($combi == 'agenda') {
		show_actions_done($conf, $langs, $db, $attendeestatic, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $attendeestatic->module);
	} else {
		show_actions_messaging($conf, $langs, $db, $attendeestatic, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
	}
}


// End of page
llxFooter();

$db->close();
