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
 *  \brief      Return, via Ajax, agenda events created or updated since a given timestamp, for the currently displayed calendar view
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
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
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
// NOREQUIREHTML (set above) makes main.inc.php skip its usual require of html.form.class.php.
// show_day_events() below calls User->getNomUrl(), which needs the Form class (Form::showphoto()),
// so it must be loaded explicitly here.
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

$langs->loadLangs(array('errors', 'agenda'));

top_httphead('application/json');

$response = array('error' => 0, 'newevents' => array(), 'checktime' => dol_now());

if (!getDolGlobalString('AGENDA_AUTOREFRESH_ENABLED')) {
	$response['error'] = 1;
	$response['message'] = $langs->trans('NotEnoughPermissions');
} elseif (!$user->hasRight('agenda', 'myactions', 'read') || !restrictedArea($user, 'agenda', 0, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id', 0, 1)) {
	$response['error'] = 1;
	$response['message'] = $langs->trans('NotEnoughPermissions');
} else {
	$mode = GETPOST('mode', 'aZ09');
	$year = GETPOSTINT('year');
	$month = GETPOSTINT('month');
	$day = GETPOSTINT('day');
	$firstdaytoshow = GETPOSTINT('firstdaytoshow');
	$lastdaytoshow = GETPOSTINT('lastdaytoshow');
	$since = GETPOSTINT('since');
	$filtersjson = GETPOST('filters', 'restricthtml');
	$filtersraw = json_decode((string) $filtersjson, true);
	if (!is_array($filtersraw)) {
		$filtersraw = array();
	}

	// $day is meaningful only for show_day/show_week: month view has no "current day" concept and
	// index.php itself sends day=0 for it (see agenda_build_eventarray(), whose show_month branch
	// never references $day at all), so it must not be validated as 1-31 in that mode.
	$daycheckfails = ($mode == 'show_day' || $mode == 'show_week') && ($day <= 0 || $day > 31);
	if (!in_array($mode, array('show_day', 'show_week', 'show_month', ''), true) || $year <= 0 || $month <= 0 || $month > 12 || $daycheckfails || $since <= 0 || empty($firstdaytoshow) || empty($lastdaytoshow)) {
		$response['error'] = 1;
		$response['message'] = $langs->trans('ErrorBadParameters');
	} else {
		$filtert = isset($filtersraw['filtert']) ? preg_replace('/[^0-9,\-]/', '', (string) $filtersraw['filtert']) : '-1';
		$usergroup = isset($filtersraw['usergroup']) ? preg_replace('/[^0-9,\-]/', '', (string) $filtersraw['usergroup']) : '';
		$resourceid = isset($filtersraw['resourceid']) ? (int) $filtersraw['resourceid'] : 0;
		$actioncode = isset($filtersraw['actioncode']) ? $filtersraw['actioncode'] : '';
		if (is_array($actioncode)) {
			$actioncode = array_map(
				/**
				 * @param string $v
				 * @return string
				 */
				function ($v) {
					return preg_replace('/[^a-zA-Z0-9_]/', '', (string) $v);
				},
				$actioncode
			);
		} else {
			$actioncode = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $actioncode);
		}
		$pid = isset($filtersraw['pid']) ? (int) $filtersraw['pid'] : 0;
		$socid = isset($filtersraw['socid']) ? (int) $filtersraw['socid'] : 0;
		$type = isset($filtersraw['type']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string) $filtersraw['type']) : '';
		$status = isset($filtersraw['status']) ? preg_replace('/[^a-zA-Z0-9_]/', '', (string) $filtersraw['status']) : '';
		$search_categ_cus = isset($filtersraw['search_categ_cus']) ? (int) $filtersraw['search_categ_cus'] : 0;

		// Re-enforce the same permission-based overrides index.php itself applies (comm/action/index.php,
		// near its own top: "$filtert forced to the user's own id when the user lacks allactions-read",
		// "$socid forced to $user->socid for third-party-linked users") - never trust the client-sent
		// filtert/socid for these two security-relevant restrictions, they are only a display-scoping hint.
		if (!$user->hasRight('agenda', 'allactions', 'read')) {
			$filtert = (string) $user->id;
		}
		if ($user->socid) {
			$socid = $user->socid;
		}

		$filters = array(
			'usergroup' => $usergroup,
			'filtert' => $filtert,
			'resourceid' => $resourceid,
			'actioncode' => $actioncode,
			'pid' => $pid,
			'socid' => $socid,
			'type' => $type,
			'status' => $status,
			'search_categ_cus' => $search_categ_cus,
		);

		$object = new ActionComm($db);
		$action = '';
		$filter = '';
		$hookmanager->initHooks(array('agenda'));

		ob_start();
		$agendaeventresult = agenda_build_eventarray($db, $hookmanager, $user, $object, $action, $mode, $year, $month, $day, $firstdaytoshow, $lastdaytoshow, $filters, $since);
		$sqlerroroutput = ob_get_clean();
		if ($sqlerroroutput !== '') {
			// A SQL error inside agenda_build_eventarray() calls dol_print_error() directly (no output
			// buffering of its own), which would otherwise corrupt this endpoint's JSON body - discard it
			// and report a generic error instead.
			$response['error'] = 1;
			$response['message'] = $langs->trans('ErrorBadParameters');
		} else {
			$eventarray = $agendaeventresult['eventarray'];

			// Load Bookcal calendars exactly as comm/action/index.php does: agenda_build_eventarray() can
			// return bookcal_calendar-typed events, and show_day_events() dereferences
			// $bookcalcalendarsarray["availabilitieslink"][...] for them without a null-guard in one place.
			$bookcalcalendars = array();
			if (isModEnabled("bookcal")) {
				$sqlbc = "SELECT ba.rowid, bc.label, bc.ref, bc.rowid as id_cal";
				$sqlbc .= " FROM ".MAIN_DB_PREFIX."bookcal_availabilities as ba";
				$sqlbc .= " JOIN ".MAIN_DB_PREFIX."bookcal_calendar as bc";
				$sqlbc .= " ON bc.rowid = ba.fk_bookcal_calendar";
				$sqlbc .= " WHERE bc.status = 1";
				$sqlbc .= " AND ba.status = 1";
				$sqlbc .= " AND bc.entity IN (".getEntity('agenda').")";
				if (!empty($filtert) && $filtert != '-1') {
					$sqlbc .= " AND bc.visibility IN (".$db->sanitize($filtert, 0, 0, 0, 0).")";
				}
				$resqlbc = $db->query($sqlbc);
				if ($resqlbc) {
					$numbc = $db->num_rows($resqlbc);
					for ($ibc = 0; $ibc < $numbc; $ibc++) {
						$objbc = $db->fetch_object($resqlbc);
						$labelbc = !empty($objbc->label) ? $objbc->label : $objbc->ref;
						$bookcalcalendars["calendars"][$objbc->id_cal] = array("id" => $objbc->id_cal, "label" => $labelbc);
						$bookcalcalendars["availabilitieslink"][$objbc->rowid] = $objbc->id_cal;
					}
				}
			}

			// show_day_events() (moved to this file's own require_once'd agenda.lib.php in a prior task)
			// reads these as globals - initialize them exactly as comm/action/index.php does before its
			// own first call to that function.
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
			$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/theme_vars.inc.php";
			if (is_readable($color_file)) {
				include $color_file;
			}

			// $maxnbofchar=0 (no truncation) and $showinfo matching the page's own per-mode value - must
			// mirror comm/action/index.php's own show_day_events() calls, or polled event boxes render
			// differently (truncated labels, missing location/full-day line) than server-rendered ones.
			$showinfo = ($mode == 'show_day' || $mode == 'show_week') ? 1 : 0;

			foreach ($eventarray as $daykey => $notused) {
				$yy = (int) dol_print_date($daykey, '%Y', 'gmt');
				$mm = (int) dol_print_date($daykey, '%m', 'gmt');
				$dd = (int) dol_print_date($daykey, '%d', 'gmt');
				$dateint = sprintf('%04d%02d%02d', $yy, $mm, $dd);

				$hourlybuckets = array();
				ob_start();
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				show_day_events($db, $dd, $mm, $yy, $mm, '', $eventarray, 0, 0, '', $showinfo, 300, 1, $bookcalcalendars, $hourlybuckets);
				ob_end_clean();

				foreach ($hourlybuckets as $slotkey => $slotevents) {
					foreach ($slotevents as $eventhtml) {
						if (!preg_match('/data-agenda-event-id="(\d+)"/', $eventhtml, $matchid)) {
							continue;
						}
						$eventid = (int) $matchid[1];

						if ($mode == 'show_month' || $mode == '') {
							$targetselector = '#dayevent_'.$dateint.' .sortable';
						} elseif ($slotkey === 'allday') {
							// Week view's all-day row shares the "dayevent_YYYYMMDD" id with show_day_events()'s
							// own (unrelated) day-title wrapper - .sortable disambiguates to the real all-day
							// row. Day view's all-day row has its own distinct id (see index.php), no ambiguity.
							$targetselector = ($mode == 'show_week') ? '#dayevent_'.$dateint.'.sortable' : '#alldayevent_'.$dateint;
						} else {
							$targetselector = '#hourslot_'.$dateint.$slotkey;
						}

						$response['newevents'][] = array(
							'eventid' => $eventid,
							'targetselector' => $targetselector,
							'html' => $eventhtml,
						);
					}
				}
			}
		}
	}
}

print json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);

$db->close();
