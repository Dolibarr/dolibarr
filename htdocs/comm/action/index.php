<?php
/* Copyright (C) 2001-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Eric Seigne             <erics@rycks.com>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2014       Cedric GROSS            <c.gross@kreiz-it.fr>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2017       Open-DSI                <support@open-dsi.fr>
 * Copyright (C) 2021-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Anthony Berton		<anthony.berton@bb2a.fr>
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
 *  \file       htdocs/comm/action/index.php
 *  \ingroup    agenda
 *  \brief      Home page of calendar events
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$MAXAGENDA = getDolGlobalString('AGENDA_EXT_NB', 6);
$DELAYFORCACHE = 300;	// 300 seconds

$action = GETPOST('action', 'aZ09');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$mode = GETPOST('mode', 'aZ09');
if (empty($mode) && preg_match('/show_/', $action)) {
	$mode = $action;	// For backward compatibility
}

$disabledefaultvalues = GETPOSTINT('disabledefaultvalues');

$check_holiday = GETPOSTINT('check_holiday');
$check_birthday = !empty($conf->use_javascript_ajax) ? GETPOSTINT("check_birthday") : 1;
$filter = GETPOST("search_filter", 'alpha', 3) ? GETPOST("search_filter", 'alpha', 3) : GETPOST("filter", 'alpha', 3);
$filtert = GETPOST("search_filtert", "intcomma", 3) ? GETPOST("search_filtert", "intcomma", 3) : GETPOST("filtert", "intcomma", 3);
$usergroup = GETPOST("search_usergroup", "intcomma", 3) ? GETPOST("search_usergroup", "intcomma", 3) : GETPOST("usergroup", "intcomma", 3);
$usergroupids = array_map('intval', array_filter(explode(',', $usergroup)));
$search_categ_cus = GETPOST("search_categ_cus", 'intcomma', 3) ? GETPOST("search_categ_cus", 'intcomma', 3) : 0;

// If no choice done on calendar owner (like on left menu link "Agenda"), we filter on current user by default.
if (empty($filtert) && !getDolGlobalString('AGENDA_ALL_CALENDARS')) {
	$filtert = (string) $user->id;
}
if (empty($filtert)) {
	$filtert = -1;
}

$newparam = '';

// Pagination parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "a.datec";
}

// Security check
$socid = GETPOSTINT("search_socid") ? GETPOSTINT("search_socid") : GETPOSTINT("socid");
if ($user->socid) {
	$socid = $user->socid;
}
if ($socid < 0) {
	$socid = '';
}

$canedit = 1;
if (!$user->hasRight('agenda', 'myactions', 'read')) {
	accessforbidden();
}
if (!$user->hasRight('agenda', 'allactions', 'read')) {
	$canedit = 0;
}
if (!$user->hasRight('agenda', 'allactions', 'read') || $filter == 'mine') {  // If no permission to see all, we show only affected to me
	$filtert = (string) $user->id;
}

$resourceid = GETPOSTINT("search_resourceid");
$year = GETPOSTINT("year") ? GETPOSTINT("year") : date("Y");
$month = GETPOSTINT("month") ? GETPOSTINT("month") : date("m");
$week = GETPOSTINT("week") ? GETPOSTINT("week") : date("W");
$day = GETPOSTINT("day") ? GETPOSTINT("day") : date("d");
$pid = GETPOSTISSET("search_projectid") ? GETPOSTINT("search_projectid", 3) : GETPOSTINT("projectid", 3);
$status = GETPOSTISSET("search_status") ? GETPOST("search_status", 'aZ09') : GETPOST("status", 'aZ09'); // status may be 0, 50, 100, 'todo', 'na' or -1
$type = GETPOSTISSET("search_type") ? GETPOST("search_type", 'aZ09') : GETPOST("type", 'aZ09');
$maxprint = GETPOSTISSET("maxprint") ? GETPOSTINT("maxprint") : getDolGlobalInt('AGENDA_MAX_EVENTS_DAY_VIEW', 3);

$dateselect = dol_mktime(0, 0, 0, GETPOSTINT('dateselectmonth'), GETPOSTINT('dateselectday'), GETPOSTINT('dateselectyear'));
if ($dateselect > 0) {
	$day = GETPOSTINT('dateselectday');
	$month = GETPOSTINT('dateselectmonth');
	$year = GETPOSTINT('dateselectyear');
}

// Set actioncode (this code must be same for setting actioncode into peruser, listacton and index)
if (GETPOST('search_actioncode', 'array:aZ09')) {
	$actioncode = GETPOST('search_actioncode', 'array:aZ09', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("search_actioncode", "alpha", 3) ? GETPOST("search_actioncode", "alpha", 3) : (GETPOST("search_actioncode") == '0' ? '0' : ((!getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE') || $disabledefaultvalues) ? '' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE')));
}
if (is_scalar($actioncode) && $actioncode == '-1') {
	$actioncode = '';
}

if ($status == '' && !GETPOSTISSET('search_status')) {
	$status = ((!getDolGlobalString('AGENDA_DEFAULT_FILTER_STATUS') || $disabledefaultvalues) ? '' : getDolGlobalString('AGENDA_DEFAULT_FILTER_STATUS'));
}

$defaultview = getDolGlobalString('AGENDA_DEFAULT_VIEW', 'show_month');	// default for app
$defaultview = getDolUserString('AGENDA_DEFAULT_VIEW', $defaultview);	// default for user
if (empty($mode) && !GETPOSTISSET('mode')) {
	$mode = $defaultview;
}
if ($mode == 'default') {	// When action is default, we want a calendar view and not the list
	$mode = (($defaultview != 'show_list') ? $defaultview : 'show_month');
}
// View by month
if (GETPOST('viewcal') && GETPOST('mode') != 'show_day' && GETPOST('mode') != 'show_week') {
	$mode = 'show_month';
	$day = '';
}
// View by week
if (GETPOST('viewweek') || GETPOST('mode') == 'show_week') {
	$mode = 'show_week';
	$week = ($week ? $week : date("W"));
	$day = ($day ? $day : date("d"));
}
// View by day
if (GETPOST('viewday') || GETPOST('mode') == 'show_day') {
	$mode = 'show_day';
	$day = ($day ? $day : date("d"));
}

$object = new ActionComm($db);

// Load translation files required by the page
$langs->loadLangs(array('agenda', 'other', 'commercial'));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('agenda'));

$result = restrictedArea($user, 'agenda', 0, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');
if ($user->socid && $socid) {
	$result = restrictedArea($user, 'societe', $socid);
}

require_once DOL_DOCUMENT_ROOT.'/core/redirect_if_setup_not_complete.inc.php';


/*
 * Actions
 */

if (GETPOST("viewlist", 'alpha') || $mode == 'show_list') {
	$param = '';
	if (is_array($_POST)) {
		foreach ($_POST as $key => $val) {
			if ($key == 'token') {
				continue;
			}
			$param .= '&'.urlencode($key).'='.urlencode($val);
		}
	}
	if (!preg_match('/action=/', $param)) {
		$param .= ($param ? '&' : '').'mode=show_list';
	}
	//print $param;
	header("Location: ".DOL_URL_ROOT.'/comm/action/list.php?'.$param);
	exit;
}

if (GETPOST("viewperuser", 'alpha') || $mode == 'show_peruser') {
	$param = '';
	if (is_array($_POST)) {
		foreach ($_POST as $key => $val) {
			if ($key == 'token') {
				continue;
			}
			$param .= '&'.urlencode($key).'='.urlencode($val);
		}
	}
	//print $param;
	header("Location: ".DOL_URL_ROOT.'/comm/action/peruser.php?'.$param);
	exit;
}


/*
 * View
 */

$parameters = array(
	'socid' => $socid,
	'status' => $status,
	'year' => $year,
	'month' => $month,
	'day' => $day,
	'type' => $type,
	'maxprint' => $maxprint,
	'filter' => $filter,
	'filtert' => $filtert,
	'showbirthday' => $check_birthday,
	'canedit' => $canedit,
	'optioncss' => $optioncss,
	'actioncode' => $actioncode,
	'pid' => $pid,
	'resourceid' => $resourceid,
	'usergroup' => $usergroup,
);
$reshook = $hookmanager->executeHooks('beforeAgenda', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$form = new Form($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&oacute;dulo_Agenda|DE:Modul_Terminplanung';
llxHeader('', $langs->trans("Agenda"), $help_url);

$now = dol_now();
$nowarray = dol_getdate($now);
$nowyear = $nowarray['year'];
$nowmonth = $nowarray['mon'];
$nowday = $nowarray['mday'];

$listofextcals = array();

// Define list of external calendars (global admin setup)
$i = 0;
while ($i < $MAXAGENDA) {
	$i++;
	$source = 'AGENDA_EXT_SRC'.$i;
	$name = 'AGENDA_EXT_NAME'.$i;
	$offsettz = 'AGENDA_EXT_OFFSETTZ'.$i;
	$color = 'AGENDA_EXT_COLOR'.$i;
	$enabled = 'AGENDA_EXT_ENABLED'.$i;
	$default = 'AGENDA_EXT_ACTIVEBYDEFAULT'.$i;
	$buggedfile = 'AGENDA_EXT_BUGGEDFILE'.$i;
	if (getDolGlobalString($source) && getDolGlobalString($name) && getDolGlobalString($enabled)) {
		// Note: $conf->global->buggedfile can be empty or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
		$listofextcals[] = array(
			'type' => 'globalsetup',
			'src' => getDolGlobalString($source),
			'name' => dol_string_nohtmltag(getDolGlobalString($name)),
			'offsettz' => (int) getDolGlobalInt($offsettz, 0),
			'color' => dol_string_nohtmltag(getDolGlobalString($color)),
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			'default' => dol_string_nohtmltag(getDolGlobalString($default)),
			'buggedfile' => dol_string_nohtmltag(getDolGlobalString('buggedfile', ''))
		);
	}
}

// Define list of external calendars (user setup)
$i = 0;
while ($i < $MAXAGENDA) {
	$i++;
	$source = 'AGENDA_EXT_SRC_'.$user->id.'_'.$i;
	$name = 'AGENDA_EXT_NAME_'.$user->id.'_'.$i;
	$offsettz = 'AGENDA_EXT_OFFSETTZ_'.$user->id.'_'.$i;
	$color = 'AGENDA_EXT_COLOR_'.$user->id.'_'.$i;
	$enabled = 'AGENDA_EXT_ENABLED_'.$user->id.'_'.$i;
	$default = 'AGENDA_EXT_ACTIVEBYDEFAULT_'.$user->id.'_'.$i;
	$buggedfile = 'AGENDA_EXT_BUGGEDFILE_'.$user->id.'_'.$i;

	if (getDolUserString($source) && getDolUserString($name)) {
		// Note: $conf->global->buggedfile can be empty or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
		$listofextcals[] = array(
			'type' => 'usersetup',
			'src' => getDolUserString($source),
			'name' => dol_string_nohtmltag(getDolUserString($name)),
			'offsettz' => (int) (empty($user->conf->$offsettz) ? 0 : $user->conf->$offsettz),
			'color' => dol_string_nohtmltag(getDolUserString($color)),
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			'default' => dol_string_nohtmltag(getDolUserString($default)),
			'buggedfile' => dol_string_nohtmltag(isset($user->conf->buggedfile) ? $user->conf->buggedfile : '')
		);
	}
}

$firstdaytoshow = 0;
$max_day_in_month = 0;
$lastdaytoshow = 0;
$tmpday = 0;
$datestart = 0;
$dateend = 0;
$first_day = 0;
$first_month = 0;
$first_year = 0;
$prev_day = 0;
$prev_month = 0;
$prev_year = 0;
$max_day_in_prev_month = 0;
$next_day = 0;
$next_month = 0;
$next_year = 0;
if (empty($mode) || $mode == 'show_month') {
	$prev = dol_get_prev_month($month, $year);
	$prev_year  = $prev['year'];
	$prev_month = $prev['month'];
	$next = dol_get_next_month($month, $year);
	$next_year  = $next['year'];
	$next_month = $next['month'];

	$max_day_in_prev_month = (int) date("t", dol_mktime(12, 0, 0, $prev_month, 1, $prev_year, 'gmt')); // Nb of days in previous month
	$max_day_in_month = (int) date("t", dol_mktime(12, 0, 0, $month, 1, $year, 'gmt')); // Nb of days in next month
	// tmpday is a negative or null cursor to know how many days before the 1st to show on month view (if tmpday=0, 1st is monday)
	$tmpday = - (int) date("w", dol_mktime(12, 0, 0, $month, 1, $year, 'gmt')) + 2; // date('w') is 0 for sunday
	$tmpday += (getDolGlobalInt('MAIN_START_WEEK', 1) - 1);
	if ($tmpday >= 1) {
		$tmpday -= 7; // If tmpday is 0 we start with sunday, if -6, we start with monday of previous week.
	}
	// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
	$firstdaytoshow = dol_mktime(0, 0, 0, $prev_month, $max_day_in_prev_month + $tmpday, $prev_year, 'tzuserrel');
	$next_day = 7 - ($max_day_in_month + 1 - $tmpday) % 7;
	if ($next_day < 6) {
		$next_day += 7;
	}
	$lastdaytoshow = dol_mktime(0, 0, 0, $next_month, $next_day, $next_year, 'tzuserrel');
}
if ($mode == 'show_week') {
	$prev = dol_get_first_day_week($day, $month, $year);
	$prev_year  = $prev['prev_year'];
	$prev_month = $prev['prev_month'];
	$prev_day   = $prev['prev_day'];
	$first_day  = $prev['first_day'];
	$first_month = $prev['first_month'];
	$first_year = $prev['first_year'];

	$week = $prev['week'];

	$day = (int) $day;
	$next = dol_get_next_week($first_day, (int) $week, $first_month, $first_year);
	$next_year  = $next['year'];
	$next_month = $next['month'];
	$next_day   = $next['day'];

	// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
	$firstdaytoshow = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year, 'tzuserrel');
	$lastdaytoshow = dol_time_plus_duree($firstdaytoshow, 7, 'd');

	$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year, 'gmt'));

	$tmpday = $first_day;
}
if ($mode == 'show_day') {
	$prev = dol_get_prev_day($day, $month, $year);
	$prev_year  = $prev['year'];
	$prev_month = $prev['month'];
	$prev_day   = $prev['day'];
	$next = dol_get_next_day($day, $month, $year);
	$next_year  = $next['year'];
	$next_month = $next['month'];
	$next_day   = $next['day'];
	// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
	$firstdaytoshow = dol_mktime(0, 0, 0, $prev_month, $prev_day, $prev_year, 'tzuserrel');
	$lastdaytoshow = dol_mktime(0, 0, 0, $next_month, $next_day, $next_year, 'tzuserrel');
}
//print 'xx'.$prev_year.'-'.$prev_month.'-'.$prev_day;
//print 'xx'.$next_year.'-'.$next_month.'-'.$next_day;
//print dol_print_date($firstdaytoshow,'dayhour').' '.dol_print_date($lastdaytoshow,'dayhour');

/*$title = $langs->trans("DoneAndToDoActions");
if ($status == 'done') {
	$title = $langs->trans("DoneActions");
}
if ($status == 'todo') {
	$title = $langs->trans("ToDoActions");
}
*/

$param = '';
if (($actioncode && $actioncode !== '-1') || GETPOSTISSET('search_actioncode')) {
	if (is_array($actioncode)) {
		foreach ($actioncode as $str_action) {
			if ($str_action != '-1') {
				$param .= "&search_actioncode[]=".urlencode($str_action);
			}
		}
	} else {
		$param .= "&search_actioncode=".urlencode($actioncode);
	}
}
if ($resourceid > 0) {
	$param .= "&search_resourceid=".urlencode((string) ($resourceid));
}
if ($status || GETPOSTISSET('status') || GETPOSTISSET('search_status')) {
	$param .= "&search_status=".urlencode($status);
}
if ($filter) {
	$param .= "&search_filter=".urlencode((string) $filter);
}
if ($filtert) {
	$param .= "&search_filtert=".urlencode((string) $filtert);
}
if ($usergroup > 0) {
	$param .= "&search_usergroup=".urlencode((string) ($usergroup));
}
if ($socid > 0) {
	$param .= "&search_socid=".urlencode((string) ($socid));
}
if ($check_birthday) {
	$param .= "&check_birthday=1";
}
if ($check_holiday) {
	$param .= "&check_holiday=1";
}
if ($pid) {
	$param .= "&search_projectid=".urlencode((string) ($pid));
}
if ($type) {
	$param .= "&search_type=".urlencode($type);
}
$param .= "&maxprint=".urlencode((string) ($maxprint));
if ($mode == 'show_day' || $mode == 'show_week' || $mode == 'show_month') {
	$param .= '&mode='.urlencode($mode);
}
if ($search_categ_cus != 0) {
	$param .= '&search_categ_cus='.urlencode((string) ($search_categ_cus));
}
if ($check_holiday) {
	$param .= '&check_holiday=1';
}

// Show navigation bar
$nav = '';
$nav .= '<div class="navselectiondate inline-block nowraponall">';
if (empty($mode) || $mode == 'show_month') {
	$nav .= "<a href=\"?year=".$prev_year."&month=".$prev_month.$param."\"><i class=\"fa fa-chevron-left\"></i></a> &nbsp;\n";
	$nav .= " <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), "%b %Y");
	$nav .= " </span>\n";
	$nav .= " &nbsp; <a href=\"?year=".$next_year."&month=".$next_month.$param."\"><i class=\"fa fa-chevron-right\"></i></a>\n";
	$picto = 'calendar';
}
if ($mode == 'show_week') {
	$nav .= "<a href=\"?year=".$prev_year."&month=".$prev_month."&day=".$prev_day.$param."\"><i class=\"fa fa-chevron-left\" title=\"".dol_escape_htmltag($langs->trans("Previous"))."\"></i></a> &nbsp;\n";
	$nav .= " <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $first_month, $first_day, $first_year), "%Y").", ".$langs->trans("WeekShort")." ".$week;
	$nav .= " </span>\n";
	$nav .= " &nbsp; <a href=\"?year=".$next_year."&month=".$next_month."&day=".$next_day.$param."\"><i class=\"fa fa-chevron-right\" title=\"".dol_escape_htmltag($langs->trans("Next"))."\"></i></a>\n";
	$picto = 'calendarweek';
}
if ($mode == 'show_day') {
	$nav .= "<a href=\"?year=".$prev_year."&month=".$prev_month."&day=".$prev_day.$param."\"><i class=\"fa fa-chevron-left\"></i></a> &nbsp;\n";
	$nav .= " <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $month, $day, $year), "daytextshort");
	$nav .= " </span>\n";
	$nav .= " &nbsp; <a href=\"?year=".$next_year."&month=".$next_month."&day=".$next_day.$param."\"><i class=\"fa fa-chevron-right\"></i></a>\n";
	$picto = 'calendarday';
}
if (empty($conf->dol_optimize_smallscreen)) {
	$nav .= ' <a href="?year='.$nowyear.'&month='.$nowmonth.'&day='.$nowday.$param.'" class="datenowlink marginleftonly marginrightonly">'.$langs->trans("Today").'</a> ';
}
$nav .= '</div>';

$nav .= $form->selectDate($dateselect, 'dateselect', 0, 0, 1, '', 1, 0);
//$nav .= ' <input type="submit" class="button button-save" name="submitdateselect" value="'.$langs->trans("Refresh").'">';
$nav .= '<button type="submit" class="liste_titre button_search valignmiddle" name="button_search_x" value="x"><span class="fa fa-search"></span></button>';

// Must be after the nav definition
$paramnodate = $param;
$param .= '&year='.$year.'&month='.$month.($day ? '&day='.$day : '');
//print 'x'.$param;




/*$tabactive = '';
 if ($mode == 'show_month') $tabactive = 'cardmonth';
 if ($mode == 'show_week') $tabactive = 'cardweek';
 if ($mode == 'show_day')  $tabactive = 'cardday';
 if ($mode == 'show_list') $tabactive = 'cardlist';
 if ($mode == 'show_pertuser') $tabactive = 'cardperuser';
 if ($mode == 'show_pertype') $tabactive = 'cardpertype';
 */

$paramnoaction = preg_replace('/mode=[a-z_]+/', '', preg_replace('/action=[a-z_]+/', '', $param));
$paramnoactionodate = preg_replace('/mode=[a-z_]+/', '', preg_replace('/action=[a-z_]+/', '', $paramnodate));

$head = calendars_prepare_head($paramnoaction);

print '<form method="POST" id="searchFormList" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


$viewmode = '<div class="navmode inline-block">';

$viewmode .= '<a class="btnTitle'.($mode == 'list' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&restore_lastsearch_values=1'.$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("List"), 'object_calendarlist', 'class="imgforviewmode pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewList").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_month' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_month&year='.(isset($object->datep) ? dol_print_date($object->datep, '%Y') : $year).'&month='.(isset($object->datep) ? dol_print_date($object->datep, '%m') : $month).'&day='.(isset($object->datep) ? dol_print_date($object->datep, '%d') : $day).$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewCal"), 'object_calendarmonth', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewCal").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_week' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_week&year='.(isset($object->datep) ? dol_print_date($object->datep, '%Y') : $year).'&month='.(isset($object->datep) ? dol_print_date($object->datep, '%m') : $month).'&day='.(isset($object->datep) ? dol_print_date($object->datep, '%d') : $day).$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewWeek"), 'object_calendarweek', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewWeek").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_day' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_day&year='.(isset($object->datep) ? dol_print_date($object->datep, '%Y') : $year).'&month='.(isset($object->datep) ? dol_print_date($object->datep, '%m') : $month).'&day='.(isset($object->datep) ? dol_print_date($object->datep, '%d') : $day).$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewDay"), 'object_calendarday', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow">'.$langs->trans("ViewDay").'</span></a>';

$viewmode .= '<a class="btnTitle'.($mode == 'show_peruser' ? ' btnTitleSelected' : '').' reposition" href="'.DOL_URL_ROOT.'/comm/action/peruser.php?mode=show_peruser&year='.(isset($object->datep) ? dol_print_date($object->datep, '%Y') : $year).'&month='.(isset($object->datep) ? dol_print_date($object->datep, '%m') : $month).'&day='.(isset($object->datep) ? dol_print_date($object->datep, '%d') : $day).$paramnoactionodate.'">';
//$viewmode .= '<span class="fa paddingleft imgforviewmode valignmiddle btnTitle-icon">';
$viewmode .= img_picto($langs->trans("ViewPerUser"), 'object_calendarperuser', 'class="pictoactionview block"');
//$viewmode .= '</span>';
$viewmode .= '<span class="valignmiddle text-plus-circle btnTitle-label hideonsmartphone inline-block width75 divoverflow" title="'.dolPrintHTML($langs->trans("ViewPerUser")).'">'.$langs->trans("ViewPerUser").'</span></a>';

// Add more views from hooks
$parameters = array();
$object = null;
$reshook = $hookmanager->executeHooks('addCalendarView', $parameters, $object, $action);
if (empty($reshook)) {
	$viewmode .= $hookmanager->resPrint;
} elseif ($reshook > 1) {
	$viewmode = $hookmanager->resPrint;
}

$viewmode .= '</div>';

$viewmode .= '<span class="marginrightonly"></span>';	// To add a space before the navigation tools


$newparam = '';
$newcardbutton = '';
if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
	$tmpforcreatebutton = dol_getdate(dol_now('tzuserrel'), true, 'gmt');

	$newparam .= '&month='.((int) $month).'&year='.((int) $tmpforcreatebutton['year']).'&mode='.urlencode($mode);

	//$param='month='.$monthshown.'&year='.$year;
	//$hourminsec = dol_print_date(dol_mktime(10, 0, 0, 1, 1, 1970, 'gmt'), '%H', 'gmt').'0000';	// Set $hourminsec to '100000' to auto set hour to 10:00 at creation

	$urltocreateaction = DOL_URL_ROOT.'/comm/action/card.php?action=create';
	$urltocreateaction .= '&apyear='.$tmpforcreatebutton['year'].'&apmonth='.$tmpforcreatebutton['mon'].'&apday='.$tmpforcreatebutton['mday'].'&aphour='.$tmpforcreatebutton['hours'].'&apmin='.$tmpforcreatebutton['minutes'];
	$urltocreateaction .= '&backtopage='.urlencode($_SERVER["PHP_SELF"].($newparam ? '?'.$newparam : ''));

	$newcardbutton .= dolGetButtonTitle($langs->trans("AddAction"), '', 'fa fa-plus-circle', $urltocreateaction);
}

// Define the legend/list of calendard to show
$s = '';


$showextcals = $listofextcals;
$bookcalcalendars = array();

// Load Bookcal Calendars
if (isModEnabled("bookcal")) {
	$sql = "SELECT ba.rowid, bc.label, bc.ref, bc.rowid as id_cal";
	$sql .= " FROM ".MAIN_DB_PREFIX."bookcal_availabilities as ba";
	$sql .= " JOIN ".MAIN_DB_PREFIX."bookcal_calendar as bc";
	$sql .= " ON bc.rowid = ba.fk_bookcal_calendar";
	$sql .= " WHERE bc.status = 1";
	$sql .= " AND ba.status = 1";
	$sql .= " AND bc.entity IN (".getEntity('agenda').")";	// bookcal is a "virtual view" of agenda
	if (!empty($filtert) && $filtert != '-1') {
		$sql .= " AND bc.visibility IN (".$db->sanitize($filtert, 0, 0, 0, 0).")";
	}
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($resql);
			$label = !empty($objp->label) ? $objp->label : $objp->ref;
			$bookcalcalendars["calendars"][$objp->id_cal] = array("id" => $objp->id_cal, "label" => $label);
			$bookcalcalendars["availabilitieslink"][$objp->rowid] = $objp->id_cal;
			$i++;
		}
	} else {
		dol_print_error($db);
	}
}

if (!empty($conf->use_javascript_ajax)) {	// If javascript on
	$s .= "\n".'<!-- Div to calendars selectors -->'."\n";

	$s .= '<script type="text/javascript">'."\n";
	$s .= 'jQuery(document).ready(function () {'."\n";
	$s .= 'jQuery(".check_birthday").click(function() { console.log("Toggle class .family_birthday"); jQuery(".family_birthday").toggle(); });'."\n";
	$s .= 'jQuery(".check_holiday").click(function() { console.log("Toggle class .family_holiday"); jQuery(".family_holiday").toggle(); jQuery(this).closest("form").submit(); });'."\n";
	if (isModEnabled("bookcal") && !empty($bookcalcalendars["calendars"])) {
		foreach ($bookcalcalendars["calendars"] as $key => $value) {
			$s .= 'jQuery(".check_bookcal_calendar_'.$value['id'].'").click(function() { console.log("Toggle Bookcal Calendar '.$value['id'].'"); jQuery(".family_bookcal_calendar_'.$value['id'].'").toggle(); });'."\n";
		}
	}
	if ($mode == "show_week" || $mode == "show_month" || empty($mode)) {
		// Code to enable drag and drop
		$s .= 'jQuery( "div.sortable" ).sortable({connectWith: ".sortable", placeholder: "ui-state-highlight", items: "div.movable", receive: function( event, ui ) {'."\n";
		// Code to submit form
		$s .= 'console.log("submit form to record new event");'."\n";
		//$s.='console.log(event.target);';
		$s .= 'var newval = jQuery(event.target).closest("div.dayevent").attr("id");'."\n";
		$s .= 'console.log("found parent div.dayevent with id = "+newval);'."\n";
		$s .= 'var frm=jQuery("#searchFormList");'."\n";
		$s .= 'var newurl = ui.item.find("a.cal_event").attr("href");'."\n";
		$s .= 'console.log("Found url on href of a.cal_event"+newurl+", we submit form with actionmove=mupdate");'."\n";
		$s .= 'frm.attr("action", newurl).children("#newdate").val(newval);frm.submit();}'."\n";
		$s .= '});'."\n";
	}
	$s .= '});'."\n";
	$s .= '</script>'."\n";

	// Local calendar
	$s .= '<div class="nowrap inline-block minheight30 hideonsmartphone"><input type="checkbox" id="check_mytasks" name="check_mytasks" value="1" checked disabled><label class="labelcalendar"><span class="check_local_text small"> '.$langs->trans("LocalAgenda").' &nbsp; </span></label></div>';

	// Holiday calendar
	if ($user->hasRight("holiday", "read")) {
		$s .= '
            <div class="nowrap inline-block minheight30"><input type="checkbox" id="check_holiday" name="check_holiday" value="1" class="marginleftonly check_holiday"' . ($check_holiday ? ' checked' : '') . '>
                <label for="check_holiday" class="labelcalendar">
                    <span class="check_holiday_text small">' . $langs->trans("Holidays") . '</span>
                </label> &nbsp;
            </div>';
	}

	// External calendars
	if (is_array($showextcals) && count($showextcals) > 0) {
		$s .= '<script type="text/javascript">'."\n";
		$s .= 'jQuery(document).ready(function () {
				jQuery("div input[name^=\"check_ext\"]").each(function(index, elem) {
					var name = jQuery(elem).attr("name");
					if (jQuery(elem).is(":checked")) {
					    jQuery(".family_ext" + name.replace("check_ext", "")).show();
					} else {
					    jQuery(".family_ext" + name.replace("check_ext", "")).hide();
					}
				});

				jQuery("div input[name^=\"check_ext\"]").click(function() {
					var name = $(this).attr("name");
					jQuery(".family_ext" + name.replace("check_ext", "")).toggle();
				});
			});' . "\n";
		$s .= '</script>'."\n";

		foreach ($showextcals as $val) {
			$htmlname = md5($val['name']);	// not used for security purpose, only to get a string with no special char

			if (!empty($val['default']) || GETPOSTINT('check_ext'.$htmlname)) {
				$default = "checked";
			} else {
				$default = '';
			}

			$tooltip = $langs->trans("Cache").' '.round($DELAYFORCACHE / 60).'mn';

			$s .= '<div class="nowrap inline-block minheight30"><input type="checkbox" id="check_ext'.$htmlname.'" class="marginleftonly check_ext_'.$htmlname.'" name="check_ext'.$htmlname.'" value="1" '.$default.'><label for="check_ext'.$htmlname.'" title="'.dol_escape_htmltag($tooltip).'" class="labelcalendar"><span class="check_ext_text small">'.dol_escape_htmltag($val['name']).'</small></label> &nbsp; </div>';
		}
	}

	// Birthdays
	$s .= '<div class="nowrap inline-block minheight30"><input type="checkbox" id="check_birthday" name="check_birthday" class="marginleftonly check_birthday" value="1" '. (GETPOSTINT('check_birthday') ? ' checked' : '') .'><label for="check_birthday" class="labelcalendar"><span class="check_birthday_text small">'.$langs->trans("AgendaShowBirthdayEvents").'</span></label> &nbsp; </div>';

	// Bookcal Calendar
	if (isModEnabled("bookcal")) {
		if (!empty($bookcalcalendars["calendars"])) {
			foreach ($bookcalcalendars["calendars"] as $key => $value) {
				$label = $value['label'];
				$s .= '<div class="nowrap inline-block minheight30">';
				$s .= '<input '.(GETPOST('check_bookcal_calendar_'.$value['id']) ? "checked" : "").' type="checkbox" id="check_bookcal_calendar_'.$value['id'].'" name="check_bookcal_calendar_'.$value['id'].'" class="marginleftonly check_bookcal_calendar_'.$value['id'].'">';
				$s .= '<label for="check_bookcal_calendar_'.$value['id'].'" class="labelcalendar">';
				$s .= '<span class="check_bookcal_calendar_'.$value['id'].'_text">'.$langs->trans("AgendaShowBookcalCalendar", $label).'</span>';
				$s .= '</label> &nbsp; </div>';
			}
		}
	}

	// Calendars from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addCalendarChoice', $parameters, $object, $action);
	if (empty($reshook)) {
		$s .= $hookmanager->resPrint;
	} elseif ($reshook > 1) {
		$s = $hookmanager->resPrint;
	}

	$s .= "\n".'<!-- End div to calendars selectors -->'."\n";
} else { // If javascript off
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/check_birthday=[0-1]/i', 'check_birthday='.(empty($check_birthday) ? 1 : 0), $newparam);
	if (!preg_match('/check_birthday=/i', $newparam)) {
		$newparam .= '&check_birthday=1';
	}
	$s = '<a href="'.$_SERVER['PHP_SELF'].'?'.dol_escape_htmltag($newparam);
	$s .= '">';
	if (empty($check_birthday)) {
		$s .= $langs->trans("AgendaShowBirthdayEvents");
	} else {
		$s .= $langs->trans("AgendaHideBirthdayEvents");
	}
	$s .= '</a>';
}


// Load events from database into $eventarray
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
$agendaeventresult = agenda_build_eventarray($db, $hookmanager, $user, $object, $action, $mode, $year, $month, $day, $firstdaytoshow, $lastdaytoshow, $filters);
$eventarray = $agendaeventresult['eventarray'];
$nbevents = $agendaeventresult['nbevents'];
$MAXONSAMEPAGE = $agendaeventresult['maxonsamepage'];


// BIRTHDATES CALENDAR
// Complete $eventarray with birthdates
if ($check_birthday) {
	agenda_get_birthday_events($db, $langs, $user, $mode, $month, $day, $year, $eventarray, $nbevents);
}

// LEAVE-HOLIDAY CALENDAR
if ($user->hasRight("holiday", "read")) {
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.statut, x.rowid, x.date_debut as date_start, x.date_fin as date_end, x.halfday, x.statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."holiday as x, ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE u.rowid = x.fk_user";
	$sql .= " AND u.statut = '1'"; // Show only active users  (0 = inactive user, 1 = active user)
	$sql .= " AND (x.statut = '2' OR x.statut = '3')"; // Show only public leaves (2 = leave wait for approval, 3 = leave approved)
	if ($mode == 'show_day') {
		// Request only leaves for the current selected day
		$sql .= " AND '".$db->escape($year."-".$month."-".$day)."' BETWEEN x.date_debut AND x.date_fin";	// date_debut and date_fin are date without time
	} elseif ($mode == 'show_week') {
		// Restrict on current month (we get more, but we will filter later)
		$sql .= " AND x.date_debut < '".$db->idate(dol_get_last_day($year, $month))."'";
		$sql .= " AND x.date_fin >= '".$db->idate(dol_get_first_day($year, $month))."'";
	} elseif ($mode == 'show_month') {
		// Restrict on current month
		$sql .= " AND x.date_debut <= '".$db->idate(dol_get_last_day($year, $month))."'";
		$sql .= " AND x.date_fin >= '".$db->idate(dol_get_first_day($year, $month))."'";
	}
	if (!$user->hasRight('holiday', 'readall') || $filtert == '-3') {
		// Restrict on users of current user and his children
		$sql .= " AND x.fk_user IN(".$db->sanitize(implode(", ", $user->getAllChildIds(1))).") ";
	}
	if ($filtert > 0) {
		// Restrict on user
		$sql .= " AND x.fk_user = ".((int) $filtert);
	}

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		$nbevents += $num;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$event = new ActionComm($db);

			// Need the id of the leave object for link to it
			$event->id                      = $obj->rowid;
			$event->ref                     = (string) $event->id;

			$event->type_code = 'HOLIDAY';
			$event->type_label = '';
			$event->type_color = '';
			$event->type = 'holiday';
			$event->type_picto = 'holiday';

			// date_debut and date_fin are dates without time, so they must be read and rendered in GMT to
			// stay independent from the server and user timezones (otherwise the calendar day box is shifted).
			$event->datep                   = (int) $db->jdate($obj->date_start, 'gmt') + (empty($obj->halfday) || $obj->halfday == 1 ? 0 : 12) * 60 * 60;
			$event->datef                   = (int) $db->jdate($obj->date_end, 'gmt') + (empty($obj->halfday) || $obj->halfday == -1 ? 24 : 12) * 60 * 60 - 1;
			$event->date_start_in_calendar  = $event->datep;
			$event->date_end_in_calendar    = $event->datef;

			if ($obj->status == 3) {
				// Show no symbol for leave with state "leave approved"
				$event->percentage = -1;
			} elseif ($obj->status == 2) {
				// Show TO-DO symbol for leave with state "leave wait for approval"
				$event->percentage = 0;
			}

			$event->label = $langs->trans("Holiday");

			$daycursor = $event->date_start_in_calendar;
			$annee = (int) dol_print_date($daycursor, '%Y', 'gmt');
			$mois = (int) dol_print_date($daycursor, '%m', 'gmt');
			$jour = (int) dol_print_date($daycursor, '%d', 'gmt');

			$daycursorend = $event->date_end_in_calendar;
			$anneeend = (int) dol_print_date($daycursorend, '%Y', 'gmt');
			$moisend = (int) dol_print_date($daycursorend, '%m', 'gmt');
			$jourend = (int) dol_print_date($daycursorend, '%d', 'gmt');

			// daykey must be date that represent day box in calendar so must be a user time
			$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');
			$daykeygmt = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');
			$ifornbofdays = 0;
			do {
				$ifornbofdays++;

				$firstdayofholiday = ($ifornbofdays == 1);
				$lastdayofholiday = ($daykeygmt == dol_get_first_hour($event->date_end_in_calendar, 'gmt'));

				/*
				var_dump(dol_print_date($daykeygmt, 'dayhour', 'gmt'));
				var_dump(dol_print_date(dol_get_first_hour($event->date_end_in_calendar, 'gmt'), 'dayhour', 'gmt'));
				var_dump($lastdayofholiday);
				var_dump($obj->halfday);
				*/

				if ((in_array($obj->halfday, array(1, 2)) == 1 && $lastdayofholiday) || (in_array($obj->halfday, array(-1, 2)) && $firstdayofholiday)) {
					// We create a copy of event because we want tochange the label
					$newevent = dol_clone($event, 1);
					if (in_array($obj->halfday, array(1, 2)) && $lastdayofholiday) {
						$newevent->label .= ' ('.$langs->trans("Morning").')';
					} elseif (in_array($obj->halfday, array(-1, 2)) && $firstdayofholiday) {
						$newevent->label .= ' ('.$langs->trans("Afternoon").')';
					}
					$eventarray[$daykey][] = $newevent;	// We need to use ->gtTypePicto, getXXXon object, so clone must be PHP clone.
				} else {
					$eventarray[$daykey][] = $event;	// We can use the event unchanged
				}

				$daykey += 60 * 60 * 24;
				$daykeygmt += 60 * 60 * 24;
			} while ($daykey <= $event->date_end_in_calendar);

			$i++;
		}
	}
}

// EXTERNAL CALENDAR
// Complete $eventarray with external import Ical
if (count($listofextcals)) {
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/ical.class.php';

	foreach ($listofextcals as $key => $extcal) {
		$url = $extcal['src']; // Example: https://www.google.com/calendar/ical/eldy10%40gmail.com/private-cde92aa7d7e0ef6110010a821a2aaeb/basic.ics
		$namecal = $extcal['name'];
		$offsettz = $extcal['offsettz'];
		$colorcal = $extcal['color'];
		$buggedfile = $extcal['buggedfile'];

		$pathforcachefile = dol_sanitizePathName($conf->user->dir_temp).'/'.dol_sanitizeFileName('extcal_'.$namecal.'_user'.$user->id).'.cache';
		//var_dump($pathforcachefile);exit;

		$ical = new ICal();
		$ical->parse($url, $pathforcachefile, $DELAYFORCACHE);
		if ($ical->error) {
			// Save error message for extcal
			$listofextcals[$key]['error'] = $ical->error;
			$s .= '<br><div class="warning">'.dol_escape_htmltag($listofextcals[$key]['name']).': '.$url.'<br>Error message: '.dol_escape_htmltag($ical->error).'</div>';
		}

		// After this $ical->cal['VEVENT'] contains array of events, $ical->cal['DAYLIGHT'] contains daylight info, $ical->cal['STANDARD'] contains non daylight info, ...
		//var_dump($ical->cal); exit;
		$icalevents = array();
		$tmparray = $ical->get_event_list();
		if (is_array($tmparray)) {
			$icalevents = array_merge($icalevents, $tmparray); // Add $ical->cal['VEVENT']
		}
		$tmparray = $ical->get_freebusy_list();
		if (is_array($tmparray)) {
			$icalevents = array_merge($icalevents, $tmparray); // Add $ical->cal['VFREEBUSY']
		}

		$nbevents += count($icalevents);

		if (count($icalevents) > 0) {
			// Duplicate all repeatable events into new entries
			$moreicalevents = array();
			foreach ($icalevents as $icalevent) {
				if (isset($icalevent['RRULE']) && is_array($icalevent['RRULE'])) { //repeatable event
					//if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
					//if ($event->date_end_in_calendar > $lastdaytoshow) $event->date_end_in_calendar=($lastdaytoshow-1);
					if ($icalevent['DTSTART;VALUE=DATE']) { //fullday event
						$datecurstart = dol_stringtotime($icalevent['DTSTART;VALUE=DATE'], 1);
						$datecurend = dol_stringtotime($icalevent['DTEND;VALUE=DATE'], 1) - 1; // We remove one second to get last second of day
					} elseif (is_array($icalevent['DTSTART']) && !empty($icalevent['DTSTART']['unixtime'])) {
						$datecurstart = $icalevent['DTSTART']['unixtime'];
						$datecurend = $icalevent['DTEND']['unixtime'];
						if (!empty($ical->cal['DAYLIGHT']['DTSTART']) && $datecurstart) {
							//var_dump($ical->cal);
							$tmpcurstart = $datecurstart;
							$tmpcurend = $datecurend;
							$tmpdaylightstart = dol_mktime(0, 0, 0, 1, 1, 1970, 1) + (int) $ical->cal['DAYLIGHT']['DTSTART'];
							$tmpdaylightend = dol_mktime(0, 0, 0, 1, 1, 1970, 1) + (int) $ical->cal['STANDARD']['DTSTART'];
							//var_dump($tmpcurstart);var_dump($tmpcurend); var_dump($ical->cal['DAYLIGHT']['DTSTART']);var_dump($ical->cal['STANDARD']['DTSTART']);
							// Edit datecurstart and datecurend
							if ($tmpcurstart >= $tmpdaylightstart && $tmpcurstart < $tmpdaylightend) {
								$datecurstart -= ((int) $ical->cal['DAYLIGHT']['TZOFFSETTO']) * 36;
							} else {
								$datecurstart -= ((int) $ical->cal['STANDARD']['TZOFFSETTO']) * 36;
							}
							if ($tmpcurend >= $tmpdaylightstart && $tmpcurstart < $tmpdaylightend) {
								$datecurend -= ((int) $ical->cal['DAYLIGHT']['TZOFFSETTO']) * 36;
							} else {
								$datecurend -= ((int) $ical->cal['STANDARD']['TZOFFSETTO']) * 36;
							}
						}
						// datecurstart and datecurend are now GMT date
						//var_dump($datecurstart); var_dump($datecurend); exit;
					} else {
						// Not a recognized record
						dol_syslog("Found a not recognized repeatable record with unknown date start", LOG_ERR);
						continue;
					}
					//print 'xx'.$datecurstart;exit;

					$interval = (empty($icalevent['RRULE']['INTERVAL']) ? 1 : $icalevent['RRULE']['INTERVAL']);
					$until = empty($icalevent['RRULE']['UNTIL']) ? 0 : dol_stringtotime($icalevent['RRULE']['UNTIL'], 1);
					$maxrepeat = empty($icalevent['RRULE']['COUNT']) ? 0 : $icalevent['RRULE']['COUNT'];
					if ($until && ($until + ($datecurend - $datecurstart)) < $firstdaytoshow) {
						continue; // We discard repeatable event that end before start date to show
					}
					if ($datecurstart >= $lastdaytoshow) {
						continue; // We discard repeatable event that start after end date to show
					}

					$numofevent = 0;
					while (($datecurstart < $lastdaytoshow) && (empty($maxrepeat) || ($numofevent < $maxrepeat))) {
						if ($datecurend >= $firstdaytoshow) {    // We add event
							$newevent = $icalevent;
							unset($newevent['RRULE']);
							if ($icalevent['DTSTART;VALUE=DATE']) {
								$newevent['DTSTART;VALUE=DATE'] = dol_print_date($datecurstart, '%Y%m%d');
								$newevent['DTEND;VALUE=DATE'] = dol_print_date($datecurend + 1, '%Y%m%d');
							} else {
								$newevent['DTSTART'] = $datecurstart;
								$newevent['DTEND'] = $datecurend;
							}
							$moreicalevents[] = $newevent;
						}
						// Jump on next occurrence
						$numofevent++;
						$savdatecurstart = $datecurstart;
						if ($icalevent['RRULE']['FREQ'] == 'DAILY') {
							$datecurstart = dol_time_plus_duree($datecurstart, $interval, 'd');
							$datecurend = dol_time_plus_duree($datecurend, $interval, 'd');
						}
						if ($icalevent['RRULE']['FREQ'] == 'WEEKLY') {
							$datecurstart = dol_time_plus_duree($datecurstart, $interval, 'w');
							$datecurend = dol_time_plus_duree($datecurend, $interval, 'w');
						} elseif ($icalevent['RRULE']['FREQ'] == 'MONTHLY') {
							$datecurstart = dol_time_plus_duree($datecurstart, $interval, 'm');
							$datecurend = dol_time_plus_duree($datecurend, $interval, 'm');
						} elseif ($icalevent['RRULE']['FREQ'] == 'YEARLY') {
							$datecurstart = dol_time_plus_duree($datecurstart, $interval, 'y');
							$datecurend = dol_time_plus_duree($datecurend, $interval, 'y');
						}
						// Test to avoid infinite loop ($datecurstart must increase)
						if ($savdatecurstart >= $datecurstart) {
							dol_syslog("Found a rule freq ".$icalevent['RRULE']['FREQ']." not managed by dolibarr code. Assume 1 week frequency.", LOG_ERR);
							$datecurstart += 3600 * 24 * 7;
							$datecurend += 3600 * 24 * 7;
						}
					}
				}
			}
			$icalevents = array_merge($icalevents, $moreicalevents);

			// Loop on each entry into cal file to know if entry is qualified and add an ActionComm into $eventarray
			foreach ($icalevents as $icalevent) {
				//var_dump($icalevent);

				//print $icalevent['SUMMARY'].'->';
				//var_dump($icalevent);exit;
				if (!empty($icalevent['RRULE'])) {
					continue; // We found a repeatable event. It was already split into unitary events, so we discard general rule.
				}

				// Create a new object action
				$event = new ActionComm($db);
				$addevent = false;
				if (isset($icalevent['DTSTART;VALUE=DATE'])) { // fullday event
					// For full day events, date are also GMT but they won't but converted using tz during output
					$datestart = dol_stringtotime($icalevent['DTSTART;VALUE=DATE'], 1);
					if (empty($icalevent['DTEND;VALUE=DATE'])) {
						$dateend = $datestart + 86400 - 1;
					} else {
						$dateend = dol_stringtotime($icalevent['DTEND;VALUE=DATE'], 1) - 1; // We remove one second to get last second of day
					}
					//print 'x'.$datestart.'-'.$dateend;exit;
					//print dol_print_date($dateend,'dayhour','gmt');
					$event->fulldayevent = 1;
					$addevent = true;
				} elseif (!is_array($icalevent['DTSTART'])) { // not fullday event (DTSTART is not array. It is a value like '19700101T000000Z' for 00:00 in greenwitch)
					$datestart = $icalevent['DTSTART'];
					$dateend = empty($icalevent['DTEND']) ? $datestart : $icalevent['DTEND'];

					$datestart += +($offsettz * 3600);
					$dateend += +($offsettz * 3600);

					$addevent = true;
					//var_dump($offsettz);
					//var_dump(dol_print_date($datestart, 'dayhour', 'gmt'));
				} elseif (isset($icalevent['DTSTART']['unixtime'])) {	// File contains a local timezone + a TZ (for example when using bluemind)
					$datestart = $icalevent['DTSTART']['unixtime'];
					$dateend = $icalevent['DTEND']['unixtime'];

					$datestart += +($offsettz * 3600);
					$dateend += +($offsettz * 3600);

					// $buggedfile is set to uselocalandtznodaylight if conf->global->AGENDA_EXT_BUGGEDFILEx = 'uselocalandtznodaylight'
					if ($buggedfile === 'uselocalandtznodaylight') {	// unixtime is a local date that does not take daylight into account, TZID is +1 for example for 'Europe/Paris' in summer instead of 2
						// TODO
					}
					// $buggedfile is set to uselocalandtzdaylight if conf->global->AGENDA_EXT_BUGGEDFILEx = 'uselocalandtzdaylight' (for example with bluemind)
					if ($buggedfile === 'uselocalandtzdaylight') {	// unixtime is a local date that does take daylight into account, TZID is +2 for example for 'Europe/Paris' in summer
						$localtzs = new DateTimeZone((string) preg_replace('/"/', '', $icalevent['DTSTART']['TZID']));
						$localtze = new DateTimeZone((string) preg_replace('/"/', '', $icalevent['DTEND']['TZID']));
						$localdts = new DateTime(dol_print_date($datestart, 'dayrfc', 'gmt'), $localtzs);
						$localdte = new DateTime(dol_print_date($dateend, 'dayrfc', 'gmt'), $localtze);
						$tmps = -1 * $localtzs->getOffset($localdts);
						$tmpe = -1 * $localtze->getOffset($localdte);
						$datestart += $tmps;
						$dateend += $tmpe;
						//var_dump($datestart);
					}
					$addevent = true;
				}

				if ($addevent) {
					$event->id = $icalevent['UID'];
					$event->ref = (string) $event->id;
					$userId = $userstatic->findUserIdByEmail($namecal);
					if (!empty($userId) && $userId > 0) {
						$event->userassigned[$userId] = $userId;
						$event->percentage = -1;
					}

					$event->type_code = "ICALEVENT";
					$event->type_label = $namecal;
					$event->type_color = $colorcal;
					$event->type = 'icalevent';
					$event->type_picto = 'rss';

					$event->icalname = $namecal;
					$event->icalcolor = $colorcal;
					$usertime = 0; // We don't modify date because we want to have date into memory datep and datef stored as GMT date. Compensation will be done during output.
					$event->datep = (int) ($datestart + $usertime);
					$event->datef = (int) ($dateend + $usertime);

					if (isset($icalevent['SUMMARY']) && $icalevent['SUMMARY']) {
						$event->label = dol_string_nohtmltag($icalevent['SUMMARY']);
					} elseif (isset($icalevent['DESCRIPTION']) && $icalevent['DESCRIPTION']) {
						$event->label = dol_nl2br(dol_string_nohtmltag($icalevent['DESCRIPTION']), 1);
					} else {
						$event->label = $langs->trans("ExtSiteNoLabel");
					}

					// Priority (see https://www.kanzaki.com/docs/ical/priority.html)
					// LOW      = 0 to 4
					// MEDIUM   = 5
					// HIGH     = 6 to 9
					if (!empty($icalevent['PRIORITY'])) {
						$event->priority = $icalevent['PRIORITY'];
					}

					// Transparency (see https://www.kanzaki.com/docs/ical/transp.html)
					if (!empty($icalevent['TRANSP'])) {
						if ($icalevent['TRANSP'] == "TRANSPARENT") {
							$event->transparency = 0; // 0 = available / free
						}
						if ($icalevent['TRANSP'] == "OPAQUE") {
							$event->transparency = 1; // 1 = busy
						}

						// TODO: MS outlook states
						// X-MICROSOFT-CDO-BUSYSTATUS:FREE      + TRANSP:TRANSPARENT => Available / Free
						// X-MICROSOFT-CDO-BUSYSTATUS:FREE      + TRANSP:OPAQUE      => Work another place
						// X-MICROSOFT-CDO-BUSYSTATUS:TENTATIVE + TRANSP:OPAQUE      => With reservations
						// X-MICROSOFT-CDO-BUSYSTATUS:BUSY      + TRANSP:OPAQUE      => Busy
						// X-MICROSOFT-CDO-BUSYSTATUS:OOF       + TRANSP:OPAQUE      => Away from the office / off-site
					}

					if (!empty($icalevent['LOCATION'])) {
						$event->location = $icalevent['LOCATION'];
					}

					$event->date_start_in_calendar = $event->datep;

					if ((int) $event->datef != 0 && $event->datef >= $event->datep) {
						$event->date_end_in_calendar = $event->datef;
					} else {
						$event->date_end_in_calendar = $event->datep;
					}

					// Add event into $eventarray if date range are ok.
					if ($event->date_end_in_calendar < $firstdaytoshow || $event->date_start_in_calendar >= $lastdaytoshow) {
						//print 'x'.$datestart.'-'.$dateend;exit;
						//print 'x'.$datestart.'-'.$dateend;exit;
						//print 'x'.$datestart.'-'.$dateend;exit;
						// This record is out of visible range
					} else {
						if ($event->date_start_in_calendar < $firstdaytoshow) {
							$event->date_start_in_calendar = $firstdaytoshow;
						}
						if ($event->date_end_in_calendar >= $lastdaytoshow) {
							$event->date_end_in_calendar = ($lastdaytoshow - 1);
						}

						// Add an entry in actionarray for each day
						$daycursor = $event->date_start_in_calendar;
						$annee = (int) dol_print_date($daycursor, '%Y', 'tzuserrel');
						$mois = (int) dol_print_date($daycursor, '%m', 'tzuserrel');
						$jour = (int) dol_print_date($daycursor, '%d', 'tzuserrel');

						// Loop on each day covered by action to prepare an index to show on calendar
						$loop = true;
						$j = 0;
						// daykey must be date that represent day box in calendar so must be a user time
						$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');
						$daykeygmt = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');
						do {
							//if ($event->fulldayevent) print dol_print_date($daykeygmt,'dayhour','gmt').'-'.dol_print_date($daykey,'dayhour','gmt').'-'.dol_print_date($event->date_end_in_calendar,'dayhour','gmt').' ';
							$eventarray[$daykey][] = $event;
							$daykey += 60 * 60 * 24;
							$daykeygmt += 60 * 60 * 24; // Add one day
							if (($event->fulldayevent ? $daykeygmt : $daykey) > $event->date_end_in_calendar) {
								$loop = false;
							}
						} while ($loop);
					}
				}
			}
		}
	}
}

// Complete $eventarray with events coming from external module
$parameters = array();
$object = null;
$reshook = $hookmanager->executeHooks('getCalendarEvents', $parameters, $object, $action);
if (!empty($hookmanager->resArray['eventarray'])) {
	foreach ($hookmanager->resArray['eventarray'] as $keyDate => $events) {
		if (!isset($eventarray[$keyDate])) {
			$eventarray[$keyDate] = array();
		}
		$eventarray[$keyDate] = array_merge($eventarray[$keyDate], $events);
	}
}

// Sort events
foreach ($eventarray as $keyDate => &$dateeventarray) {
	usort($dateeventarray, 'sort_events_by_date');
}


$maxnbofchar = 0;
$cachethirdparties = array();
$cachecontacts = array();
$cacheusers = array();
// default values
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

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/theme_vars.inc.php";
if (is_readable($color_file)) {
	global $theme_datacolor;
	include $color_file;
	/** @var array<int,mixed> $theme_datacolor */
}

$massactionbutton = '';

print_barre_liste($langs->trans("Agenda"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, 1, -1, 'object_action', 0, $nav.'<span class="marginleftonly"></span>'.$newcardbutton, '', $limit, 1, 0, 1, $viewmode);

if ($nbevents > $MAXONSAMEPAGE) {
	print info_admin('Number of results has been truncated to '.$MAXONSAMEPAGE, 0, 0, 'warning').'<br>';
}

// Show div with list of calendars
print $s;


if (empty($mode) || $mode == 'show_month') {      // View by month
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/check_birthday=/i', 'check_birthday_=', $newparam); // To avoid replacement when replace day= is done
	$newparam = preg_replace('/mode=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/mode=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/viewcal=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/check_birthday_=/i', 'check_birthday=', $newparam); // Restore correct parameter
	$newparam .= '&viewcal=1';

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print_actions_filter($form, $canedit, $status, $year, $month, $day, $check_birthday, '', $filtert, '', $pid, $socid, $action, -1, $actioncode, $usergroupids, '', $resourceid, $search_categ_cus);
	print '</div>';

	print '<div class="div-table-responsive-no-min sectioncalendarbymonth maxscreenheightless300">';
	print '<table class="centpercent noborder nocellnopadd cal_pannel cal_month listwithfilterbefore">';
	print ' <tr class="liste_titre sticky">';
	// Column title of weeks numbers
	echo '  <td class="center">#</td>';
	$i = 0;
	while ($i < 7) {
		print '  <td class="center bold uppercase tdfordaytitle'.($i == 0 ? ' borderleft' : '').'">';
		$numdayinweek = (($i + (getDolGlobalInt('MAIN_START_WEEK', 1))) % 7);
		if (!empty($conf->dol_optimize_smallscreen)) {
			$labelshort = array(0 => 'SundayMin', 1 => 'MondayMin', 2 => 'TuesdayMin', 3 => 'WednesdayMin', 4 => 'ThursdayMin', 5 => 'FridayMin', 6 => 'SaturdayMin');
			print $langs->trans($labelshort[$numdayinweek]);
		} else {
			print $langs->trans("Day".$numdayinweek);
		}
		print '  </td>'."\n";
		$i++;
	}
	echo ' </tr>'."\n";

	$todayarray = dol_getdate($now, true);
	$todaytms = dol_mktime(0, 0, 0, $todayarray['mon'], $todayarray['mday'], $todayarray['year']);

	// In loops, tmpday contains day nb in current month (can be zero or negative for days of previous month)
	//var_dump($eventarray);
	for ($iter_week = 0; $iter_week < 6; $iter_week++) {
		echo " <tr>\n";
		// Get date of the current day, format 'yyyy-mm-dd'
		if ($tmpday <= 0) { // If number of the current day is in previous month
			$currdate0 = sprintf("%04d", $prev_year).sprintf("%02d", $prev_month).sprintf("%02d", $max_day_in_prev_month + $tmpday);
		} elseif ($tmpday <= $max_day_in_month) { // If number of the current day is in current month
			$currdate0 = sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $tmpday);
		} else { // If number of the current day is in next month
			$currdate0 = sprintf("%04d", $next_year).sprintf("%02d", $next_month).sprintf("%02d", $tmpday - $max_day_in_month);
		}
		// Get week number for the targeted date '$currdate0'
		$numweek0 = date("W", strtotime(date($currdate0)));
		// Show the week number, and define column width
		echo ' <td class="center weeknumber opacitymedium" width="2%">'.$numweek0.'</td>';

		for ($iter_day = 0; $iter_day < 7; $iter_day++) {
			if ($tmpday <= 0) {
				/* Show days before the beginning of the current month (previous month)  */
				$style = 'cal_other_month cal_past';
				if ($iter_day == 6) {
					$style .= ' cal_other_month_right';
				}
				echo '  <td class="'.$style.' nowrap tdtop" width="14%">';
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				show_day_events($db, $max_day_in_prev_month + $tmpday, $prev_month, $prev_year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam);
				echo "  </td>\n";
			} elseif ($tmpday <= $max_day_in_month) {
				/* Show days of the current month */
				$curtime = dol_mktime(0, 0, 0, $month, $tmpday, $year);
				$style = 'cal_current_month';
				if ($iter_day == 6) {
					$style .= ' cal_current_month_right';
				}
				$today = 0;
				if ($todayarray['mday'] == $tmpday && $todayarray['mon'] == $month && $todayarray['year'] == $year) {
					$today = 1;
				}
				if ($today) {
					$style = 'cal_today';
				}
				if ($curtime < $todaytms) {
					$style .= ' cal_past';
				}
				//var_dump($todayarray['mday']."==".$tmpday." && ".$todayarray['mon']."==".$month." && ".$todayarray['year']."==".$year.' -> '.$style);
				echo '  <td class="'.$style.' nowrap tdtop" width="14%">';
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				show_day_events($db, $tmpday, $month, $year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam, 0, 60, 0, $bookcalcalendars);
				echo "</td>\n";
			} else {
				/* Show days after the current month (next month) */
				$style = 'cal_other_month';
				if ($iter_day == 6) {
					$style .= ' cal_other_month_right';
				}
				echo '  <td class="'.$style.' nowrap tdtop" width="14%">';
				// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
				show_day_events($db, $tmpday - $max_day_in_month, $next_month, $next_year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam);
				echo "</td>\n";
			}
			$tmpday++;
		}
		echo " </tr>\n";
	}
	print "</table>\n";
	print '</div>';

	print '<input type="hidden" name="actionmove" value="mupdate">';
	print '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?mode=show_month&'.dol_escape_htmltag($_SERVER['QUERY_STRING']).'">';
	print '<input type="hidden" name="newdate" id="newdate">';
} elseif ($mode == 'show_week') {
	// View by week
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/check_birthday=/i', 'check_birthday_=', $newparam); // To avoid replacement when replace day= is done
	$newparam = preg_replace('/mode=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/mode=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/viewweek=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/check_birthday_=/i', 'check_birthday=', $newparam); // Restore correct parameter
	$newparam .= '&viewweek=1';

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print_actions_filter($form, $canedit, $status, $year, $month, $day, $check_birthday, '', $filtert, '', $pid, $socid, $action, -1, $actioncode, $usergroupids, '', $resourceid);
	print '</div>';

	print '<div class="div-table-responsive-no-min sectioncalendarbyweek maxscreenheightless300">';
	print '<table class="centpercent noborder nocellnopadd cal_pannel cal_month listwithfilterbefore">';
	print ' <tr class="liste_titre">';
	$i = 0;
	while ($i < 7) {
		echo '  <td class="center bold uppercase tdfordaytitle">'.$langs->trans("Day".(($i + (getDolGlobalInt('MAIN_START_WEEK', 1))) % 7))."</td>\n";
		$i++;
	}
	echo " </tr>\n";

	echo ' <tr class="trcalweek">'."\n";

	for ($iter_day = 0; $iter_day < 7; $iter_day++) {
		// Show days of the current week
		$curtime = dol_time_plus_duree($firstdaytoshow, $iter_day, 'd');		// $firstdaytoshow is in timezone of server
		$tmpday = (int) dol_print_date($curtime, '%d', 'tzuserrel');
		$tmpmonth = (int) dol_print_date($curtime, '%m', 'tzuserrel');
		$tmpyear = (int) dol_print_date($curtime, '%Y', 'tzuserrel');

		$style = 'cal_current_month';
		if ($iter_day == 6) {
			$style .= ' cal_other_month_right';
		}

		$today = 0;
		$todayarray = dol_getdate($now, true);
		if ($todayarray['mday'] == $tmpday && $todayarray['mon'] == $tmpmonth && $todayarray['year'] == $tmpyear) {
			$today = 1;
		}
		if ($today) {
			$style = 'cal_today';
		}

		echo '  <td class="'.$style.'" width="14%" valign="top">';
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		show_day_events($db, $tmpday, $tmpmonth, $tmpyear, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, 0, $bookcalcalendars);
		echo "  </td>\n";
	}
	echo " </tr>\n";

	print "</table>\n";
	print '</div>';

	echo '<input type="hidden" name="actionmove" value="mupdate">';
	echo '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?mode=show_week&'.dol_escape_htmltag($_SERVER['QUERY_STRING']).'">';
	echo '<input type="hidden" name="newdate" id="newdate">';
} else { // View by day
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/mode=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/mode=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/viewday=[0-9]+&?/i', '', $newparam);
	$newparam .= '&viewday=1';
	// Code to show just one day
	$style = 'cal_current_month cal_current_month_oneday';
	$today = 0;
	$todayarray = dol_getdate($now, true);
	if ($todayarray['mday'] == $day && $todayarray['mon'] == $month && $todayarray['year'] == $year) {
		$today = 1;
	}
	//if ($today) $style='cal_today';

	$timestamp = dol_mktime(12, 0, 0, $month, $day, $year);
	$arraytimestamp = dol_getdate($timestamp);

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print_actions_filter($form, $canedit, $status, $year, $month, $day, $check_birthday, '', $filtert, '', $pid, $socid, $action, -1, $actioncode, $usergroupids, '', $resourceid);
	print '</div>';

	print '<div class="div-table-responsive-no-min sectioncalendarbyday maxscreenheightless300">';
	echo '<table class="tagtable centpercent noborder nocellnopadd cal_pannel cal_month listwithfilterbefore" style="margin-bottom: 10px !important;">';

	echo ' <tr class="tagtr liste_titre">';
	echo '  <td class="tagtd center bold uppercase">'.$langs->trans("Day".$arraytimestamp['wday'])."</td>\n";
	echo " </tr>\n";

	/*
	 echo ' <div class="tagtr">';
	 echo '  <div class="tagtd width100"></div>';
	 echo '  <div class="tagtd center">';
	 echo show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, -1);
	 echo '  </div>'."\n";
	 echo " </div>\n";
	 */

	print '<tr class="trcalday"><td class="tdtop">';

	/* WIP View per hour */
	$useviewhour = 0;
	if ($useviewhour) {
		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table

		$maxheightwin = (isset($_SESSION["dol_screenheight"]) && $_SESSION["dol_screenheight"] > 500) ? ($_SESSION["dol_screenheight"] - 200) : 660; // Also into index.php file

		echo '<div style="max-height: '.$maxheightwin.'px;">';
		echo '<div class="tagtable centpercent calendarviewcontainer">';

		$maxnbofchar = 80;

		$tmp = explode('-', getDolGlobalString('MAIN_DEFAULT_WORKING_HOURS'));
		$minhour = round((float) $tmp[0], 0);
		$maxhour = round((float) $tmp[1], 0);
		if ($minhour > 23) {
			$minhour = 23;
		}
		if ($maxhour < 1) {
			$maxhour = 1;
		}
		if ($maxhour <= $minhour) {
			$maxhour = $minhour + 1;
		}

		$i = 0;
		$j = 0;
		while ($i < 24) {
			echo ' <div class="tagtr calendarviewcontainertr">'."\n";
			echo '  <div class="tagtd width100 tdtop">'.dol_print_date($i * 3600, 'hour', 'gmt').'</div>';
			echo '  <div class="tagtd '.$style.' tdtop"></div>'."\n";
			echo ' </div>'."\n";
			$i++;
			$j++;
		}

		echo '</div></div>';

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, 1, $bookcalcalendars);

		print '</div>';
	} else {
		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, 0, $bookcalcalendars);

		print '</div>';
	}


	print '</td></tr>';

	echo '</table>';
	print '</div>';
}

print "\n".'</form>';

// End of page
llxFooter();
$db->close();


/**
 * Sort events by date
 *
 * @param   object  $a      Event A
 * @param   object  $b      Event B
 * @return  int             Return integer < 0 if event A should be before event B, > 0 otherwise, 0 if they have the exact same time slot
 */
function sort_events_by_date($a, $b)
{
	// Sort holidays at first
	if ($a->type_code === 'HOLIDAY') {
		return -1;
	}
	if ($b->type_code === 'HOLIDAY') {
		return 1;
	}

	// datep => Event start time
	// datef => Event end time

	// Events have different start time
	if ($a->datep !== $b->datep) {
		return (int) ($a->datep - $b->datep);
	}

	// Events have same start time and no end time
	if ((!is_numeric($b->datef)) || (!is_numeric($a->datef))) {
		return sort_events_by_percentage($a, $b);
	}

	// Events have the same start time and same end time
	if ($b->datef === $a->datef) {
		return sort_events_by_percentage($a, $b);
	}

	// Events have the same start time, but have different end time -> longest event first
	return (int) ($b->datef - $a->datef);
}

/**
 * Sort events by percentage
 *
 * @param   object  $a      Event A
 * @param   object  $b      Event B
 * @return  int             Return integer < 0 if event A should be before event B, > 0 otherwise, 0 if they have the exact same percentage
 */
function sort_events_by_percentage($a, $b)
{
	// Sort events with no percentage before each other
	// (useful to sort holidays, sick days or similar on the top)

	if ($a->percentage < 0) {
		return -1;
	}

	if ($b->percentage < 0) {
		return 1;
	}

	return (int) ($b->percentage - $a->percentage);
}
