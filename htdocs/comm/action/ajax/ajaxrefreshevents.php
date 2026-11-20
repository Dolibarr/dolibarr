<?php
/* Copyright (C) 2026	Frédéric France			<frederic.france@free.fr>
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
 *  \file       htdocs/comm/action/ajax/ajaxrefreshevents.php
 *  \brief      Return, via Ajax, the agenda events created or modified since a given time, for the calendar view on screen
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
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

// Load Dolibarr environment
require '../../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
// NOREQUIREHTML makes main.inc.php skip html.form.class.php, and show_day_events() needs it (User::getNomUrl())
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->loadLangs(array('errors', 'agenda', 'other', 'commercial', 'companies'));

$mode = GETPOST('mode', 'aZ09');
$year = GETPOSTINT('year');
$month = GETPOSTINT('month');
$day = GETPOSTINT('day');
$firstdaytoshow = GETPOSTINT('firstdaytoshow');
$lastdaytoshow = GETPOSTINT('lastdaytoshow');
$since = GETPOSTINT('since');

// The filters of the page, as comm/action/index.php resolved them (default user, saved search...). They only
// scope what is shown: what the user may see is enforced again below.
$filtersraw = json_decode(GETPOST('filters', 'restricthtml'), true);
if (!is_array($filtersraw)) {
	$filtersraw = array();
}
$filter = isset($filtersraw['filter']) ? preg_replace('/[^a-z]/', '', (string) $filtersraw['filter']) : '';
$filtert = isset($filtersraw['filtert']) ? preg_replace('/[^0-9,\-]/', '', (string) $filtersraw['filtert']) : '-1';
$usergroup = isset($filtersraw['usergroup']) ? preg_replace('/[^0-9,\-]/', '', (string) $filtersraw['usergroup']) : '';
$resourceid = isset($filtersraw['resourceid']) ? (int) $filtersraw['resourceid'] : 0;
$pid = isset($filtersraw['pid']) ? (int) $filtersraw['pid'] : 0;
$socid = isset($filtersraw['socid']) ? (int) $filtersraw['socid'] : 0;
$type = isset($filtersraw['type']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string) $filtersraw['type']) : '';
$status = isset($filtersraw['status']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $filtersraw['status']) : '';
$search_categ_cus = isset($filtersraw['search_categ_cus']) ? (int) $filtersraw['search_categ_cus'] : 0;
$actioncode = isset($filtersraw['actioncode']) ? $filtersraw['actioncode'] : '';
if (is_array($actioncode)) {
	// Not array_filter(): '0' and '-1' are values the page sends and agenda_build_eventarray() understands
	$actioncode = array_values(array_map(
		/**
		 * @param mixed $v
		 * @return string
		 */
		function ($v) {
			return (string) preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $v);
		},
		$actioncode
	));
} else {
	$actioncode = (string) preg_replace('/[^a-zA-Z0-9_,\-]/', '', (string) $actioncode);
}
$action = '';

top_httphead('application/json');

$response = array('error' => 0, 'message' => '', 'newevents' => array());

// The same restrictions comm/action/index.php applies, whatever the request says
if ($user->socid) {
	$socid = $user->socid;
}
if (!$user->hasRight('agenda', 'allactions', 'read') || $filter == 'mine') {
	$filtert = (string) $user->id;
}

// $day only means something in the day and week views: the month view sends 0
$daycheckfails = ($mode == 'show_day' || $mode == 'show_week') && ($day <= 0 || $day > 31);

if (getDolGlobalInt('AGENDA_AUTOREFRESH_FREQUENCY') <= 0) {
	$response['error'] = 1;
	$response['message'] = $langs->transnoentitiesnoconv('ErrorForbidden');
} elseif (!$user->hasRight('agenda', 'myactions', 'read') || !restrictedArea($user, 'agenda', 0, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id', 0, 1)) {
	$response['error'] = 1;
	$response['message'] = $langs->transnoentitiesnoconv('NotEnoughPermissions');
} elseif (!in_array($mode, array('show_day', 'show_week', 'show_month'), true) || $year <= 0 || $month <= 0 || $month > 12 || $daycheckfails || $since <= 0 || $firstdaytoshow <= 0 || $lastdaytoshow <= $firstdaytoshow) {
	$response['error'] = 1;
	$response['message'] = $langs->transnoentitiesnoconv('ErrorBadParameters');
} else {
	// Taken before the query: an event saved while it runs is reported again by the next call, never lost
	$response['checktime'] = dol_now();

	$filters = array(
		'usergroup' => (string) $usergroup,
		'filtert' => (string) $filtert,
		'resourceid' => $resourceid,
		'actioncode' => $actioncode,
		'pid' => $pid,
		'socid' => $socid,
		'type' => (string) $type,
		'status' => (string) $status,
		'search_categ_cus' => $search_categ_cus,
	);

	$object = new ActionComm($db);
	$hookmanager->initHooks(array('agenda'));

	// agenda_build_eventarray() keeps the events whose datec or tms is strictly after $sincedatec. Both are
	// in seconds, so an event saved in the same second as the previous check, just after it, would be missed:
	// one second earlier means such an event comes back once more instead.
	ob_start();
	$agendaeventresult = agenda_build_eventarray($db, $hookmanager, $user, $object, $action, $mode, $year, $month, $day, $firstdaytoshow, $lastdaytoshow, $filters, $since - 1);
	$bookcalcalendars = agenda_get_bookcal_calendars($db, (string) $filtert);
	$sqlerroroutput = ob_get_clean();

	if ($sqlerroroutput !== '') {
		// A SQL error is printed by dol_print_error(): it would break the JSON, so report it instead
		$response['error'] = 1;
		$response['message'] = $langs->transnoentitiesnoconv('ErrorBadParameters');
		unset($response['checktime']);
	} else {
		$eventarray = $agendaeventresult['eventarray'];

		// show_day_events() reads these as globals: initialized as comm/action/index.php does
		$cachethirdparties = array();
		$cachecontacts = array();
		$cacheusers = array();
		$colorindexused = array();
		$theme_datacolor = array(
			array(137, 86, 161),
			array(60, 147, 183),
			array(250, 190, 80),
			array(80, 166, 90),
			array(190, 190, 100),
			array(91, 115, 247),
			array(140, 140, 220),
			array(190, 120, 120),
			array(115, 125, 150),
			array(100, 170, 20),
			array(150, 135, 125),
			array(85, 135, 150),
			array(150, 135, 80),
			array(150, 80, 150)
		);
		$color_file = dol_getThemeFilePath('theme_vars.inc.php');
		if ($color_file && is_readable($color_file)) {
			include $color_file;
		}

		$hourlygrid = ($mode == 'show_day' || ($mode == 'show_week' && !getDolGlobalString('AGENDA_WEEK_VIEW_NO_HOURLY_GRID')));
		// Same values as the calls of show_day_events() in comm/action/index.php, so that a box comes back
		// exactly as the page prints it
		$showinfo = ($mode == 'show_month') ? 0 : 1;
		$minheight = ($mode == 'show_month') ? 60 : 300;

		foreach (array_keys($eventarray) as $daykey) {
			$yy = (int) dol_print_date($daykey, '%Y', 'gmt');
			$mm = (int) dol_print_date($daykey, '%m', 'gmt');
			$dd = (int) dol_print_date($daykey, '%d', 'gmt');
			$dateint = sprintf('%04d%02d%02d', $yy, $mm, $dd);

			$hourlybuckets = array();
			ob_start();
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			show_day_events($db, $dd, $mm, $yy, $month, '', $eventarray, 0, 0, '', $showinfo, $minheight, 0, $bookcalcalendars, $hourlybuckets);
			ob_end_clean();

			foreach ($hourlybuckets as $slotkey => $slotevents) {
				foreach ($slotevents as $eventhtml) {
					// Only the boxes of real events carry an id (see show_day_events()): the others cannot be replaced
					if (!preg_match('/data-agenda-event-id="(\d+)"/', $eventhtml, $reg)) {
						continue;
					}
					if (!$hourlygrid) {
						// Month view, and week view without the hourly grid: one list per day
						$target = '#dayevent_'.$dateint.' .sortable';
					} elseif ($slotkey === 'allday') {
						$target = ($mode == 'show_week') ? '#dayevent_'.$dateint : '#alldayevent_'.$dateint;
					} else {
						$target = '#daylayer_'.$dateint;
					}
					$response['newevents'][] = array(
						'eventid' => (int) $reg[1],
						'target' => $target,
						'html' => $eventhtml,
					);
				}
			}
		}
	}
}

print json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);

$db->close();
