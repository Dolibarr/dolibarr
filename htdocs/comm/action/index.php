<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018-2021 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

if (!isset($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW)) {
	$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW = 3;
}

if (!getDolGlobalString('AGENDA_EXT_NB')) {
	$conf->global->AGENDA_EXT_NB = 5;
}
$MAXAGENDA = getDolGlobalString('AGENDA_EXT_NB');
$DELAYFORCACHE = 300;	// 300 seconds

$disabledefaultvalues = GETPOSTINT('disabledefaultvalues');

$check_holiday = GETPOSTINT('check_holiday');
$filter = GETPOST("search_filter", 'alpha', 3) ? GETPOST("search_filter", 'alpha', 3) : GETPOST("filter", 'alpha', 3);
$filtert = GETPOST("search_filtert", "intcomma", 3) ? GETPOST("search_filtert", "intcomma", 3) : GETPOST("filtert", "intcomma", 3);
$usergroup = GETPOST("search_usergroup", "intcomma", 3) ? GETPOST("search_usergroup", "intcomma", 3) : GETPOST("usergroup", "intcomma", 3);
$showbirthday = empty($conf->use_javascript_ajax) ? GETPOSTINT("showbirthday") : 1;
$search_categ_cus = GETPOST("search_categ_cus", 'intcomma', 3) ? GETPOST("search_categ_cus", 'intcomma', 3) : 0;

// If not choice done on calendar owner (like on left menu link "Agenda"), we filter on user.
if (empty($filtert) && !getDolGlobalString('AGENDA_ALL_CALENDARS')) {
	$filtert = $user->id;
}

$newparam = '';

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
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
	$filtert = $user->id;
}

$action = GETPOST('action', 'aZ09');

$mode = GETPOST('mode', 'aZ09');
if (empty($mode) && preg_match('/show_/', $action)) {
	$mode = $action;	// For backward compatibility
}
$resourceid = GETPOST("search_resourceid", 'int');
$year = GETPOSTINT("year") ? GETPOSTINT("year") : date("Y");
$month = GETPOSTINT("month") ? GETPOSTINT("month") : date("m");
$week = GETPOSTINT("week") ? GETPOSTINT("week") : date("W");
$day = GETPOSTINT("day") ? GETPOSTINT("day") : date("d");
$pid = GETPOSTINT("search_projectid", 3) ? GETPOSTINT("search_projectid", 3) : GETPOSTINT("projectid", 3);
$status = GETPOSTISSET("search_status") ? GETPOST("search_status", 'aZ09') : GETPOST("status", 'aZ09'); // status may be 0, 50, 100, 'todo', 'na' or -1
$type = GETPOSTISSET("search_type") ? GETPOST("search_type", 'aZ09') : GETPOST("type", 'aZ09');
$maxprint = GETPOSTISSET("maxprint") ? GETPOSTINT("maxprint") : getDolGlobalInt('AGENDA_MAX_EVENTS_DAY_VIEW');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

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

if ($status == '' && !GETPOSTISSET('search_status')) {
	$status = ((!getDolGlobalString('AGENDA_DEFAULT_FILTER_STATUS') || $disabledefaultvalues) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_STATUS);
}

$defaultview = getDolGlobalString('AGENDA_DEFAULT_VIEW', 'show_month');	// default for app
$defaultview = getDolUserString('AGENDA_DEFAULT_VIEW', $defaultview);	// default for user
if (empty($mode) && !GETPOSTISSET('mode')) {
	$mode = $defaultview;
}
if ($mode == 'default') {	// When action is default, we want a calendar view and not the list
	$mode = (($defaultview != 'show_list') ? $defaultview : 'show_month');
}
if (GETPOST('viewcal') && GETPOST('mode') != 'show_day' && GETPOST('mode') != 'show_week') {
	$mode = 'show_month';
	$day = '';
} // View by month
if (GETPOST('viewweek') || GETPOST('mode') == 'show_week') {
	$mode = 'show_week';
	$week = ($week ? $week : date("W"));
	$day = ($day ? $day : date("d"));
} // View by week
if (GETPOST('viewday') || GETPOST('mode') == 'show_day') {
	$mode = 'show_day';
	$day = ($day ? $day : date("d"));
} // View by day

$object = new ActionComm($db);

// Load translation files required by the page
$langs->loadLangs(array('agenda', 'other', 'commercial'));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('agenda'));

$result = restrictedArea($user, 'agenda', 0, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');
if ($user->socid && $socid) {
	$result = restrictedArea($user, 'societe', $socid);
}


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
			$param .= '&'.$key.'='.urlencode($val);
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
			$param .= '&'.$key.'='.urlencode($val);
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
	'showbirthday' => $showbirthday,
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

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&oacute;dulo_Agenda|DE:Modul_Terminplanung';
llxHeader('', $langs->trans("Agenda"), $help_url);

$form = new Form($db);
$companystatic = new Societe($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

$now = dol_now();
$nowarray = dol_getdate($now);
$nowyear = $nowarray['year'];
$nowmonth = $nowarray['mon'];
$nowday = $nowarray['mday'];

$listofextcals = array();

// Define list of external calendars (global admin setup)
if (!getDolGlobalString('AGENDA_DISABLE_EXT')) {
	$i = 0;
	while ($i < $MAXAGENDA) {
		$i++;
		$source = 'AGENDA_EXT_SRC'.$i;
		$name = 'AGENDA_EXT_NAME'.$i;
		$offsettz = 'AGENDA_EXT_OFFSETTZ'.$i;
		$color = 'AGENDA_EXT_COLOR'.$i;
		$default = 'AGENDA_EXT_ACTIVEBYDEFAULT'.$i;
		$buggedfile = 'AGENDA_EXT_BUGGEDFILE'.$i;
		if (getDolGlobalString($source) && getDolGlobalString($name)) {
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
}

// Define list of external calendars (user setup)
if (empty($user->conf->AGENDA_DISABLE_EXT)) {
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
}

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
	$tmpday += ((isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1) - 1);
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
	$next = dol_get_next_week($first_day, $week, $first_month, $first_year);
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
 if ($status == 'done') $title = $langs->trans("DoneActions");
 if ($status == 'todo') $title = $langs->trans("ToDoActions");
 */

$param = '';
if ($actioncode || GETPOSTISSET('search_actioncode')) {
	if (is_array($actioncode)) {
		foreach ($actioncode as $str_action) {
			$param .= "&search_actioncode[]=".urlencode($str_action);
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
	$param .= "&search_filter=".urlencode($filter);
}
if ($filtert) {
	$param .= "&search_filtert=".urlencode($filtert);
}
if ($usergroup > 0) {
	$param .= "&search_usergroup=".urlencode((string) ($usergroup));
}
if ($socid > 0) {
	$param .= "&search_socid=".urlencode((string) ($socid));
}
if ($showbirthday) {
	$param .= "&search_showbirthday=1";
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
	$nav .= ' &nbsp; <a href="?year='.$nowyear.'&month='.$nowmonth.'&day='.$nowday.$param.'" class="datenowlink">'.$langs->trans("Today").'</a> ';
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
	$tmpforcreatebutton = dol_getdate(dol_now(), true);

	$newparam .= '&month='.((int) $month).'&year='.((int) $tmpforcreatebutton['year']).'&mode='.urlencode($mode);

	//$param='month='.$monthshown.'&year='.$year;
	$hourminsec = dol_print_date(dol_mktime(10, 0, 0, 1, 1, 1970, 'gmt'), '%H', 'gmt').'0000';	// Set $hourminsec to '100000' to auto set hour to 10:00 at creation

	$urltocreateaction = DOL_URL_ROOT.'/comm/action/card.php?action=create';
	$urltocreateaction .= '&apyear='.$tmpforcreatebutton['year'].'&apmonth='.$tmpforcreatebutton['mon'].'&apday='.$tmpforcreatebutton['mday'].'&aphour='.$tmpforcreatebutton['hours'].'&apmin='.$tmpforcreatebutton['minutes'];
	$urltocreateaction .= '&backtopage='.urlencode($_SERVER["PHP_SELF"].($newparam ? '?'.$newparam : ''));

	$newcardbutton .= dolGetButtonTitle($langs->trans("AddAction"), '', 'fa fa-plus-circle', $urltocreateaction);
}

// Define the legend/list of calendard to show
$s = '';
$link = '';


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
	if (!empty($filtert) && $filtert != -1) {
		$sql .= " AND bc.visibility = ".(int) $filtert ;
	}
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$objp = $db->fetch_object($resql);
			$label = !empty($objp->label) ? $objp->label : $objp->ref;
			$bookcalcalendars["calendars"][] = array("id" => $objp->id_cal, "label" => $label);
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
	$s .= 'jQuery(".check_birthday").click(function() { console.log("Toggle birthdays"); jQuery(".family_birthday").toggle(); });'."\n";
	$s .= 'jQuery(".check_holiday").click(function() { console.log("Toggle holidays"); jQuery(".family_holiday").toggle(); });'."\n";
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
	$s .= '<div class="nowrap inline-block minheight30"><input type="checkbox" id="check_mytasks" name="check_mytasks" value="1" checked disabled><label class="labelcalendar"><span class="check_holiday_text"> '.$langs->trans("LocalAgenda").' &nbsp; </span></label></div>';

	// Holiday calendar
	if ($user->hasRight("holiday", "read")) {
		$s .= '
            <div class="nowrap inline-block minheight30"><input type="checkbox" id="check_holiday" name="check_holiday" value="1" class="check_holiday"' . ($check_holiday
					? ' checked' : '') . '>
                <label for="check_holiday" class="labelcalendar">
                    <span class="check_holiday_text">' . $langs->trans("Holidays") . '</span>
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

			$s .= '<div class="nowrap inline-block minheight30"><input type="checkbox" id="check_ext'.$htmlname.'" name="check_ext'.$htmlname.'" value="1" '.$default.'><label for="check_ext'.$htmlname.'" title="'.dol_escape_htmltag($tooltip).'" class="labelcalendar">'.dol_escape_htmltag($val['name']).'</label> &nbsp; </div>';
		}
	}

	// Birthdays
	$s .= '<div class="nowrap inline-block minheight30"><input type="checkbox" id="check_birthday" name="check_birthday" class="check_birthday"><label for="check_birthday" class="labelcalendar"> <span class="check_birthday_text">'.$langs->trans("AgendaShowBirthdayEvents").'</span></label> &nbsp; </div>';

	// Bookcal Calendar
	if (isModEnabled("bookcal")) {
		if (!empty($bookcalcalendars["calendars"])) {
			foreach ($bookcalcalendars["calendars"] as $key => $value) {
				$label = $value['label'];
				$s .= '<div class="nowrap inline-block minheight30"><input '.(GETPOST('check_bookcal_calendar_'.$value['id']) ? "checked" : "").' type="checkbox" id="check_bookcal_calendar_'.$value['id'].'" name="check_bookcal_calendar_'.$value['id'].'" class="check_bookcal_calendar_'.$value['id'].'"><label for="check_bookcal_calendar_'.$value['id'].'" class="labelcalendar"> <span class="check_bookcal_calendar_'.$value['id'].'_text">'.$langs->trans("AgendaShowBookcalCalendar", $label).'</span></label> &nbsp; </div>';
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
	$newparam = preg_replace('/showbirthday=[0-1]/i', 'showbirthday='.(empty($showbirthday) ? 1 : 0), $newparam);
	if (!preg_match('/showbirthday=/i', $newparam)) {
		$newparam .= '&showbirthday=1';
	}
	$link = '<a href="'.$_SERVER['PHP_SELF'].'?'.dol_escape_htmltag($newparam);
	$link .= '">';
	if (empty($showbirthday)) {
		$link .= $langs->trans("AgendaShowBirthdayEvents");
	} else {
		$link .= $langs->trans("AgendaHideBirthdayEvents");
	}
	$link .= '</a>';
}


// Load events from database into $eventarray
$eventarray = array();


// DEFAULT CALENDAR + AUTOEVENT CALENDAR + CONFERENCEBOOTH CALENDAR
$sql = 'SELECT ';
if ($usergroup > 0) {
	$sql .= " DISTINCT";
}
$sql .= ' a.id, a.label,';
$sql .= ' a.datep,';
$sql .= ' a.datep2,';
$sql .= ' a.percent,';
$sql .= ' a.fk_user_author,a.fk_user_action,';
$sql .= ' a.transparency, a.priority, a.fulldayevent, a.location,';
$sql .= ' a.fk_soc, a.fk_contact, a.fk_project, a.fk_bookcal_calendar,';
$sql .= ' a.fk_element, a.elementtype,';
$sql .= ' ca.code as type_code, ca.libelle as type_label, ca.color as type_color, ca.type as type_type, ca.picto as type_picto';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' FROM '.MAIN_DB_PREFIX.'c_actioncomm as ca, '.MAIN_DB_PREFIX."actioncomm as a";
// We must filter on resource table
if ($resourceid > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."element_resources as r";
}
// We must filter on assignment table
if ($filtert > 0 || $usergroup > 0) {
	$sql .= ", ".MAIN_DB_PREFIX."actioncomm_resources as ar";
}
if ($usergroup > 0) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
}
$sql .= ' WHERE a.fk_action = ca.id';
$sql .= ' AND a.entity IN ('.getEntity('agenda').')';
// Condition on actioncode
if (!empty($actioncode)) {
	if (!getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " AND ca.type != 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " AND ca.type = 'systemauto'";
		} else {
			if ($actioncode == 'AC_OTH') {
				$sql .= " AND ca.type != 'systemauto'";
			}
			if ($actioncode == 'AC_OTH_AUTO') {
				$sql .= " AND ca.type = 'systemauto'";
			}
		}
	} else {
		if ($actioncode == 'AC_NON_AUTO') {
			$sql .= " AND ca.type != 'systemauto'";
		} elseif ($actioncode == 'AC_ALL_AUTO') {
			$sql .= " AND ca.type = 'systemauto'";
		} else {
			if (is_array($actioncode)) {
				$sql .= " AND ca.code IN (".$db->sanitize("'".implode("','", $actioncode)."'", 1).")";
			} else {
				$sql .= " AND ca.code IN (".$db->sanitize("'".implode("','", explode(',', $actioncode))."'", 1).")";
			}
		}
	}
}
if ($resourceid > 0) {
	$sql .= " AND r.element_type = 'action' AND r.element_id = a.id AND r.resource_id = ".((int) $resourceid);
}
if ($pid) {
	$sql .= " AND a.fk_project=".((int) $pid);
}
// If the internal user must only see his customers, force searching by him
$search_sale = 0;
if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = a.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = a.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search on socid
if ($socid) {
	$sql .= " AND a.fk_soc = ".((int) $socid);
}
// We must filter on assignment table
if ($filtert > 0 || $usergroup > 0) {
	$sql .= " AND ar.fk_actioncomm = a.id AND ar.element_type='user'";
}
//var_dump($day.' '.$month.' '.$year);
if ($mode == 'show_day') {
	$sql .= " AND (";
	$sql .= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel'))."'";
	$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel'))."')";
	$sql .= " OR ";
	$sql .= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel'))."'";
	$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel'))."')";
	$sql .= " OR ";
	$sql .= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year, 'tzuserrel'))."'";
	$sql .= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year, 'tzuserrel'))."')";
	$sql .= ')';
} else {
	// To limit array
	$sql .= " AND (";
	$sql .= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7))."'"; // Start 7 days before
	$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10))."')"; // End 7 days after + 3 to go from 28 to 31
	$sql .= " OR ";
	$sql .= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7))."'";
	$sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10))."')";
	$sql .= " OR ";
	$sql .= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year) - (60 * 60 * 24 * 7))."'";
	$sql .= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year) + (60 * 60 * 24 * 10))."')";
	$sql .= ')';
}
if ($type) {
	$sql .= " AND ca.id = ".((int) $type);
}
if ($status == '0') {
	// To do (not started)
	$sql .= " AND a.percent = 0";
}
if ($status == 'na') {
	// Not applicable
	$sql .= " AND a.percent = -1";
}
if ($status == '50') {
	// Running already started
	$sql .= " AND (a.percent > 0 AND a.percent < 100)";
}
if ($status == 'done' || $status == '100') {
	$sql .= " AND (a.percent = 100)";
}
if ($status == 'todo') {
	$sql .= " AND (a.percent >= 0 AND a.percent < 100)";
}
// We must filter on assignment table
if ($filtert > 0 || $usergroup > 0) {
	$sql .= " AND (";
	if ($filtert > 0) {
		$sql .= "ar.fk_element = ".((int) $filtert);
	}
	if ($usergroup > 0) {
		$sql .= ($filtert > 0 ? " OR " : "")." ugu.fk_usergroup = ".((int) $usergroup);
	}
	$sql .= ")";
}

// Search in categories, -1 is all and -2 is no categories
if ($search_categ_cus != -1) {
	if ($search_categ_cus == -2) {
		$sql .= " AND NOT EXISTS (SELECT ca.fk_actioncomm FROM ".MAIN_DB_PREFIX."categorie_actioncomm as ca WHERE ca.fk_actioncomm = a.id)";
	} elseif ($search_categ_cus > 0) {
		$sql .= " AND EXISTS (SELECT ca.fk_actioncomm FROM ".MAIN_DB_PREFIX."categorie_actioncomm as ca WHERE ca.fk_actioncomm = a.id AND ca.fk_categorie IN (".$db->sanitize($search_categ_cus)."))";
	}
}

// Sort on date
$sql .= ' ORDER BY datep';
//print $sql;

dol_syslog("comm/action/index.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$MAXONSAMEPAGE = 10000; // Useless to have more. Protection to avoid memory overload when high number of event (for example after a mass import)
	$i = 0;
	while ($i < $num && $i < $MAXONSAMEPAGE) {
		$obj = $db->fetch_object($resql);

		// Discard auto action if option is on
		if (getDolGlobalString('AGENDA_ALWAYS_HIDE_AUTO') && $obj->type_code == 'AC_OTH_AUTO') {
			$i++;
			continue;
		}

		// Create a new object action
		$event = new ActionComm($db);

		$event->id = $obj->id;
		$event->ref = $event->id;

		$event->fulldayevent = $obj->fulldayevent;

		// event->datep and event->datef must be GMT date.
		if ($event->fulldayevent) {
			$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
			$event->datep = $db->jdate($obj->datep, $tzforfullday ? 'tzuser' : 'tzserver');	// If saved in $tzforfullday = gmt, we must invert date to be in user tz
			$event->datef = $db->jdate($obj->datep2, $tzforfullday ? 'tzuser' : 'tzserver');
		} else {
			// Example: $obj->datep = '1970-01-01 01:00:00', jdate will return 0 if TZ of PHP server is Europe/Berlin (+1)
			$event->datep = $db->jdate($obj->datep, 'tzserver');
			$event->datef = $db->jdate($obj->datep2, 'tzserver');
		}
		//$event->datep_formated_gmt = dol_print_date($event->datep, 'dayhour', 'gmt');
		//var_dump($obj->id.' '.$obj->datep.' '.dol_print_date($obj->datep, 'dayhour', 'gmt'));
		//var_dump($obj->id.' '.$event->datep.' '.dol_print_date($event->datep, 'dayhour', 'gmt'));

		$event->type_code = $obj->type_code;
		$event->type_label = $obj->type_label;
		$event->type_color = $obj->type_color;
		$event->type = $obj->type_type;
		$event->type_picto = $obj->type_picto;

		$event->label = $obj->label;
		$event->percentage = $obj->percent;

		$event->authorid = $obj->fk_user_author; // user id of creator
		$event->userownerid = $obj->fk_user_action; // user id of owner
		$event->fetch_userassigned(); // This load $event->userassigned

		$event->priority = $obj->priority;
		$event->location = $obj->location;
		$event->transparency = $obj->transparency;
		$event->fk_element = $obj->fk_element;
		$event->elementtype = $obj->elementtype;

		$event->fk_project = $obj->fk_project;

		$event->socid = $obj->fk_soc;
		$event->contact_id = $obj->fk_contact;
		$event->fk_bookcal_calendar = $obj->fk_bookcal_calendar;
		if (!empty($event->fk_bookcal_calendar)) {
			$event->type = "bookcal_calendar";
		}

		// Defined date_start_in_calendar and date_end_in_calendar property
		// They are date start and end of action but modified to not be outside calendar view.
		$event->date_start_in_calendar = $event->datep;
		if ($event->datef != '' && $event->datef >= $event->datep) {
			$event->date_end_in_calendar = $event->datef;
		} else {
			$event->date_end_in_calendar = $event->datep;
		}

		// Check values
		if ($event->date_end_in_calendar < $firstdaytoshow || $event->date_start_in_calendar >= $lastdaytoshow) {
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
			$annee = dol_print_date($daycursor, '%Y', 'tzuserrel');
			$mois = dol_print_date($daycursor, '%m', 'tzuserrel');
			$jour = dol_print_date($daycursor, '%d', 'tzuserrel');

			$daycursorend = $event->date_end_in_calendar;
			$anneeend = dol_print_date($daycursorend, '%Y', 'tzuserrel');
			$moisend = dol_print_date($daycursorend, '%m', 'tzuserrel');
			$jourend = dol_print_date($daycursorend, '%d', 'tzuserrel');

			//var_dump(dol_print_date($event->date_start_in_calendar, 'dayhour', 'gmt'));	// Hour at greenwich
			//var_dump($annee.'-'.$mois.'-'.$jour);
			//print 'annee='.$annee.' mois='.$mois.' jour='.$jour.'<br>';

			// Loop on each day covered by action to prepare an index to show on calendar
			$loop = true;
			$j = 0;
			$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');	// $mois, $jour, $annee has been set for user tz
			$daykeyend = dol_mktime(0, 0, 0, $moisend, $jourend, $anneeend, 'gmt');	// $moisend, $jourend, $anneeend has been set for user tz
			/*
			 print 'GMT '.$event->date_start_in_calendar.' '.dol_print_date($event->date_start_in_calendar, 'dayhour', 'gmt').'<br>';
			 print 'TZSERVER '.$event->date_start_in_calendar.' '.dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzserver').'<br>';
			 print 'TZUSERREL '.$event->date_start_in_calendar.' '.dol_print_date($event->date_start_in_calendar, 'dayhour', 'tzuserrel').'<br>';
			 print 'GMT '.$event->date_end_in_calendar.' '.dol_print_date($event->date_end_in_calendar, 'dayhour', 'gmt').'<br>';
			 print 'TZSERVER '.$event->date_end_in_calendar.' '.dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzserver').'<br>';
			 print 'TZUSER '.$event->date_end_in_calendar.' '.dol_print_date($event->date_end_in_calendar, 'dayhour', 'tzuserrel').'<br>';
			 */
			do {
				//if ($event->id==408)
				//print 'daykey='.$daykey.' daykeyend='.$daykeyend.' '.dol_print_date($daykey, 'dayhour', 'gmt').' - '.dol_print_date($event->datep, 'dayhour', 'gmt').' '.dol_print_date($event->datef, 'dayhour', 'gmt').'<br>';
				//print 'daykey='.$daykey.' daykeyend='.$daykeyend.' '.dol_print_date($daykey, 'dayhour', 'tzuserrel').' - '.dol_print_date($event->datep, 'dayhour', 'tzuserrel').' '.dol_print_date($event->datef, 'dayhour', 'tzuserrel').'<br>';

				$eventarray[$daykey][] = $event;
				$j++;

				$daykey += 60 * 60 * 24;
				//if ($daykey > $event->date_end_in_calendar) {
				if ($daykey > $daykeyend) {
					$loop = false;
				}
			} while ($loop);
			//var_dump($eventarray);
			//print 'Event '.$i.' id='.$event->id.' (start='.dol_print_date($event->datep).'-end='.dol_print_date($event->datef);
			//print ' startincalendar='.dol_print_date($event->date_start_in_calendar).'-endincalendar='.dol_print_date($event->date_end_in_calendar).') was added in '.$j.' different index key of array<br>';
		}

		$parameters['obj'] = $obj;
		$reshook = $hookmanager->executeHooks('hookEventElements', $parameters, $event, $action); // Note that $action and $object may have been modified by some hooks
		$event = $hookmanager->resPrint;
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		$i++;
	}
} else {
	dol_print_error($db);
}
//var_dump($eventarray);


// BIRTHDATES CALENDAR
// Complete $eventarray with birthdates
if ($showbirthday) {
	// Add events in array
	$sql = 'SELECT sp.rowid, sp.lastname, sp.firstname, sp.birthday';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'socpeople as sp';
	$sql .= ' WHERE (priv=0 OR (priv=1 AND fk_user_creat='.((int) $user->id).'))';
	$sql .= " AND sp.entity IN (".getEntity('contact').")";
	if ($mode == 'show_day') {
		$sql .= ' AND MONTH(birthday) = '.((int) $month);
		$sql .= ' AND DAY(birthday) = '.((int) $day);
	} else {
		$sql .= ' AND MONTH(birthday) = '.((int) $month);
	}
	$sql .= ' ORDER BY birthday';

	dol_syslog("comm/action/index.php", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$event = new ActionComm($db);

			$event->id = $obj->rowid; // We put contact id in action id for birthdays events
			$event->ref = $event->id;

			$datebirth = dol_stringtotime($obj->birthday, 1);
			//print 'ee'.$obj->birthday.'-'.$datebirth;
			$datearray = dol_getdate($datebirth, true);
			$event->datep = dol_mktime(0, 0, 0, $datearray['mon'], $datearray['mday'], $year, true); // For full day events, date are also GMT but they won't but converted during output
			$event->datef = $event->datep;

			$event->type_code = 'BIRTHDAY';
			$event->type_label = '';
			$event->type_color = '';
			$event->type = 'birthdate';
			$event->type_picto = 'birthdate';

			$event->label = $langs->trans("Birthday").' '.dolGetFirstLastname($obj->firstname, $obj->lastname);
			$event->percentage = 100;
			$event->fulldayevent = 1;

			$event->contact_id = $obj->rowid;

			$event->date_start_in_calendar = $db->jdate($event->datep);
			$event->date_end_in_calendar = $db->jdate($event->datef);

			// Add an entry in eventarray for each day
			$daycursor = $event->datep;
			$annee = dol_print_date($daycursor, '%Y', 'tzuserrel');
			$mois = dol_print_date($daycursor, '%m', 'tzuserrel');
			$jour = dol_print_date($daycursor, '%d', 'tzuserrel');

			$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');

			$eventarray[$daykey][] = $event;

			/*$loop = true;
			 $daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee);
			 do {
			 $eventarray[$daykey][] = $event;
			 $daykey += 60 * 60 * 24;
			 if ($daykey > $event->date_end_in_calendar) $loop = false;
			 } while ($loop);
			 */
			$i++;
		}
	} else {
		dol_print_error($db);
	}
}

if ($user->hasRight("holiday", "read")) {
	// LEAVE-HOLIDAY CALENDAR
	$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.statut, x.rowid, x.date_debut as date_start, x.date_fin as date_end, x.halfday, x.statut as status";
	$sql .= " FROM ".MAIN_DB_PREFIX."holiday as x, ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE u.rowid = x.fk_user";
	$sql .= " AND u.statut = '1'"; // Show only active users  (0 = inactive user, 1 = active user)
	$sql .= " AND (x.statut = '2' OR x.statut = '3')"; // Show only public leaves (2 = leave wait for approval, 3 = leave approved)

	if ($mode == 'show_day') {
		// Request only leaves for the current selected day
		$sql .= " AND '".$db->escape($year)."-".$db->escape($month)."-".$db->escape($day)."' BETWEEN x.date_debut AND x.date_fin";	// date_debut and date_fin are date without time
	} elseif ($mode == 'show_week') {
		// Restrict on current month (we get more, but we will filter later)
		$sql .= " AND date_debut < '".$db->idate(dol_get_last_day($year, $month))."'";
		$sql .= " AND date_fin >= '".$db->idate(dol_get_first_day($year, $month))."'";
	} elseif ($mode == 'show_month') {
		// Restrict on current month
		$sql .= " AND date_debut <= '".$db->idate(dol_get_last_day($year, $month))."'";
		$sql .= " AND date_fin >= '".$db->idate(dol_get_first_day($year, $month))."'";
	}

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i   = 0;

		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$event = new ActionComm($db);

			// Need the id of the leave object for link to it
			$event->id                      = $obj->rowid;
			$event->ref                     = $event->id;

			$event->type_code = 'HOLIDAY';
			$event->type_label = '';
			$event->type_color = '';
			$event->type = 'holiday';
			$event->type_picto = 'holiday';

			$event->datep                   = $db->jdate($obj->date_start) + (empty($halfday) || $halfday == 1 ? 0 : 12 * 60 * 60 - 1);
			$event->datef                   = $db->jdate($obj->date_end) + (empty($halfday) || $halfday == -1 ? 24 : 12) * 60 * 60 - 1;
			$event->date_start_in_calendar  = $event->datep;
			$event->date_end_in_calendar    = $event->datef;

			if ($obj->status == 3) {
				// Show no symbol for leave with state "leave approved"
				$event->percentage = -1;
			} elseif ($obj->status == 2) {
				// Show TO-DO symbol for leave with state "leave wait for approval"
				$event->percentage = 0;
			}

			if ($obj->halfday == 1) {
				$event->label = $obj->lastname.' ('.$langs->trans("Morning").')';
			} elseif ($obj->halfday == -1) {
				$event->label = $obj->lastname.' ('.$langs->trans("Afternoon").')';
			} else {
				$event->label = $obj->lastname;
			}

			$daycursor = $event->date_start_in_calendar;
			$annee = dol_print_date($daycursor, '%Y', 'tzuserrel');
			$mois = dol_print_date($daycursor, '%m', 'tzuserrel');
			$jour = dol_print_date($daycursor, '%d', 'tzuserrel');

			$daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee, 'gmt');
			do {
				$eventarray[$daykey][] = $event;

				$daykey += 60 * 60 * 24;
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
		if (is_array($ical->get_event_list())) {
			$icalevents = array_merge($icalevents, $ical->get_event_list()); // Add $ical->cal['VEVENT']
		}
		if (is_array($ical->get_freebusy_list())) {
			$icalevents = array_merge($icalevents, $ical->get_freebusy_list()); // Add $ical->cal['VFREEBUSY']
		}

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
						$localtzs = new DateTimeZone(preg_replace('/"/', '', $icalevent['DTSTART']['TZID']));
						$localtze = new DateTimeZone(preg_replace('/"/', '', $icalevent['DTEND']['TZID']));
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
					$event->ref = $event->id;
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
					$event->datep = $datestart + $usertime;
					$event->datef = $dateend + $usertime;

					if ($icalevent['SUMMARY']) {
						$event->label = dol_string_nohtmltag($icalevent['SUMMARY']);
					} elseif ($icalevent['DESCRIPTION']) {
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

					if ($event->datef != '' && $event->datef >= $event->datep) {
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
						$annee = dol_print_date($daycursor, '%Y', 'tzuserrel');
						$mois = dol_print_date($daycursor, '%m', 'tzuserrel');
						$jour = dol_print_date($daycursor, '%d', 'tzuserrel');

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

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/theme_vars.inc.php";
if (is_readable($color_file)) {
	include $color_file;
}
if (!is_array($theme_datacolor)) {
	$theme_datacolor = array(array(137, 86, 161), array(60, 147, 183), array(250, 190, 80), array(80, 166, 90), array(190, 190, 100), array(91, 115, 247), array(140, 140, 220), array(190, 120, 120), array(115, 125, 150), array(100, 170, 20), array(150, 135, 125), array(85, 135, 150), array(150, 135, 80), array(150, 80, 150));
}

$massactionbutton = '';

print_barre_liste($langs->trans("Agenda"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, -1, 'object_action', 0, $nav.'<span class="marginleftonly"></span>'.$newcardbutton, '', $limit, 1, 0, 1, $viewmode);

// Show div with list of calendars
print $s;


if (empty($mode) || $mode == 'show_month') {      // View by month
	$newparam = $param; // newparam is for birthday links
	$newparam = preg_replace('/showbirthday=/i', 'showbirthday_=', $newparam); // To avoid replacement when replace day= is done
	$newparam = preg_replace('/mode=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/mode=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/viewcal=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/showbirthday_=/i', 'showbirthday=', $newparam); // Restore correct parameter
	$newparam .= '&viewcal=1';

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, -1, $actioncode, $usergroup, '', $resourceid, $search_categ_cus);
	print '</div>';

	print '<div class="div-table-responsive-no-min sectioncalendarbymonth maxscreenheightless300">';
	print '<table class="centpercent noborder nocellnopadd cal_pannel cal_month">';
	print ' <tr class="liste_titre">';
	// Column title of weeks numbers
	echo '  <td class="center">#</td>';
	$i = 0;
	while ($i < 7) {
		print '  <td class="center bold uppercase tdfordaytitle'.($i == 0 ? ' borderleft' : '').'">';
		$numdayinweek = (($i + (isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1)) % 7);
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

	$todayarray = dol_getdate($now, 'fast');
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
	$newparam = preg_replace('/showbirthday=/i', 'showbirthday_=', $newparam); // To avoid replacement when replace day= is done
	$newparam = preg_replace('/mode=show_month&?/i', '', $newparam);
	$newparam = preg_replace('/mode=show_week&?/i', '', $newparam);
	$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/viewweek=[0-9]+&?/i', '', $newparam);
	$newparam = preg_replace('/showbirthday_=/i', 'showbirthday=', $newparam); // Restore correct parameter
	$newparam .= '&viewweek=1';

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, -1, $actioncode, $usergroup, '', $resourceid);
	print '</div>';

	print '<div class="div-table-responsive-no-min sectioncalendarbyweek maxscreenheightless300">';
	print '<table class="centpercent noborder nocellnopadd cal_pannel cal_month">';
	print ' <tr class="liste_titre">';
	$i = 0;
	while ($i < 7) {
		echo '  <td class="center bold uppercase tdfordaytitle">'.$langs->trans("Day".(($i + (isset($conf->global->MAIN_START_WEEK) ? $conf->global->MAIN_START_WEEK : 1)) % 7))."</td>\n";
		$i++;
	}
	echo " </tr>\n";

	echo " <tr>\n";

	for ($iter_day = 0; $iter_day < 7; $iter_day++) {
		// Show days of the current week
		$curtime = dol_time_plus_duree($firstdaytoshow, $iter_day, 'd');		// $firstdaytoshow is in timezone of server
		$tmpday = dol_print_date($curtime, '%d', 'tzuserrel');
		$tmpmonth = dol_print_date($curtime, '%m', 'tzuserrel');
		$tmpyear = dol_print_date($curtime, '%Y', 'tzuserrel');

		$style = 'cal_current_month';
		if ($iter_day == 6) {
			$style .= ' cal_other_month_right';
		}

		$today = 0;
		$todayarray = dol_getdate($now, 'fast');
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
	$todayarray = dol_getdate($now, 'fast');
	if ($todayarray['mday'] == $day && $todayarray['mon'] == $month && $todayarray['year'] == $year) {
		$today = 1;
	}
	//if ($today) $style='cal_today';

	$timestamp = dol_mktime(12, 0, 0, $month, $day, $year);
	$arraytimestamp = dol_getdate($timestamp);

	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, -1, $actioncode, $usergroup, '', $resourceid);
	print '</div>';

	print '<div class="div-table-responsive-no-min sectioncalendarbyday maxscreenheightless300">';
	echo '<table class="tagtable centpercent noborder nocellnopadd cal_pannel cal_month noborderbottom" style="margin-bottom: 5px !important;">';

	echo ' <tr class="tagtr liste_titre">';
	echo '  <td class="tagtd center bold uppercase">'.$langs->trans("Day".$arraytimestamp['wday'])."</td>\n";
	echo " </td>\n";

	/*
	 echo ' <div class="tagtr">';
	 echo '  <div class="tagtd width100"></div>';
	 echo '  <div class="tagtd center">';
	 echo show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, -1);
	 echo '  </div>'."\n";
	 echo " </div>\n";
	 */

	echo '</table>';
	print '</div>';

	/* WIP View per hour */
	$useviewhour = 0;
	if ($useviewhour) {
		print '<div class="div-table-responsive-no-min borderbottom">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table

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
		print '<div class="div-table-responsive-no-min borderbottom">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table

		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, 0, $bookcalcalendars);

		print '</div>';
	}
}

print "\n".'</form>';

// End of page
llxFooter();
$db->close();


/**
 * Show event of a particular day
 *
 * @param	DoliDB	$db              Database handler
 * @param   int		$day             Day
 * @param   int		$month           Month
 * @param   int		$year            Year
 * @param   int		$monthshown      Current month shown in calendar view
 * @param   string	$style           Style to use for this day
 * @param   array	$eventarray      Array of events
 * @param   int		$maxprint        Nb of actions to show each day on month view (0 means no limit)
 * @param   int		$maxnbofchar     Nb of characters to show for event line
 * @param   string	$newparam        Parameters on current URL
 * @param   int		$showinfo        Add extended information (used by day and week view)
 * @param   int		$minheight       Minimum height for each event. 60px by default.
 * @param	int		$nonew			 0=Add "new entry button", 1=No "new entry button", -1=Only "new entry button"
 * @param	array{}|array{0:array{0:int,1:int,2:int},1:array{0:int,1:int,2:int},2:array{0:int,1:int,2:int}}	$bookcalcalendarsarray	 Used for Bookcal module array of calendar of bookcal
 * @return	void
 */
function show_day_events($db, $day, $month, $year, $monthshown, $style, &$eventarray, $maxprint = 0, $maxnbofchar = 16, $newparam = '', $showinfo = 0, $minheight = 60, $nonew = 0, $bookcalcalendarsarray = array())
{
	global $user, $conf, $langs;
	global $action, $mode, $filter, $filtert, $status, $actioncode, $usergroup; // Filters used into search form
	global $theme_datacolor;
	global $cachethirdparties, $cachecontacts, $cacheusers, $colorindexused;
	global $hookmanager;

	'@phan-var-force array{0:array{0:int,1:int,2:int},1:array{0:int,1:int,2:int},2:array{0:int,1:int,2:int},3:array{0:int,1:int,2:int}} $theme_datacolor
	 @phan-var-force User[] $cacheusers
	 @phan-var-force array<int<0,3>> $colorindexused';

	if ($conf->use_javascript_ajax) {	// Enable the "Show more button..."
		$conf->global->MAIN_JS_SWITCH_AGENDA = 1;
	}

	$dateint = sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $day);

	//print 'show_day_events day='.$day.' month='.$month.' year='.$year.' dateint='.$dateint;

	print "\n";

	$curtime = dol_mktime(0, 0, 0, $month, $day, $year);
	$urltoshow = DOL_URL_ROOT.'/comm/action/index.php?mode=show_day&day='.str_pad((string) $day, 2, "0", STR_PAD_LEFT).'&month='.str_pad((string) $month, 2, "0", STR_PAD_LEFT).'&year='.$year.$newparam;
	$urltocreate = '';
	if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
		$newparam .= '&month='.str_pad((string) $month, 2, "0", STR_PAD_LEFT).'&year='.$year;
		$hourminsec = '100000';
		$urltocreate = DOL_URL_ROOT.'/comm/action/card.php?action=create&datep='.sprintf("%04d%02d%02d", $year, $month, $day).$hourminsec.'&backtopage='.urlencode($_SERVER["PHP_SELF"].($newparam ? '?'.$newparam : ''));
	}

	// Line with title of day
	print '<div id="dayevent_'.$dateint.'" class="dayevent tagtable centpercent nobordernopadding">'."\n";

	if ($nonew <= 0) {
		print '<div class="tagtr cursorpointer" onclick="window.location=\''.$urltocreate.'\';"><div class="nowrap tagtd"><div class="left inline-block">';
		print '<a class="dayevent-aday" style="color: #666" href="'.$urltoshow.'">';
		if ($showinfo) {
			print dol_print_date($curtime, 'daytextshort');
		} else {
			print dol_print_date($curtime, '%d');
		}
		print '</a>';
		print '</div><div class="nowrap floatright inline-block marginrightonly">';
		if ($user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create')) {
			print '<a class="cursoradd" href="'.$urltocreate.'">'; // Explicit link, useful for nojs interfaces
			print img_picto($langs->trans("NewAction"), 'edit_add.png');
			print '</a>';
		}
		print '</div></div></div>'."\n";
	}

	if ($nonew < 0) {
		print '</div>';
		return;
	}

	// Line with td contains all div of each events
	print '<div class="tagtr">';
	print '<div class="tagtd centpercent agendacell sortable">';

	//$curtime = dol_mktime (0, 0, 0, $month, $day, $year);
	$i = 0;
	$ireallyshown = 0;
	$itoshow = 0;
	$numother = 0;
	$numbirthday = 0;
	$numical = 0;
	$numicals = array();
	$ymd = sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $day);

	$colorindexused[$user->id] = 0; // Color index for current user (user->id) is always 0
	$nextindextouse = is_array($colorindexused) ? count($colorindexused) : 0; // At first run this is 0, so fist user has 0, next 1, ...
	//var_dump($colorindexused);

	include_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
	$tmpholiday = new Holiday($db);

	foreach ($eventarray as $daykey => $notused) {		// daykey is the 'YYYYMMDD' to show according to user
		$annee = dol_print_date($daykey, '%Y', 'gmt');	// We use gmt because we want the value represented by string 'YYYYMMDD'
		$mois =  dol_print_date($daykey, '%m', 'gmt');	// We use gmt because we want the value represented by string 'YYYYMMDD'
		$jour =  dol_print_date($daykey, '%d', 'gmt');	// We use gmt because we want the value represented by string 'YYYYMMDD'

		//print 'event daykey='.$daykey.' dol_print_date(daykey)='.dol_print_date($daykey, 'dayhour', 'gmt').' jour='.$jour.' mois='.$mois.' annee='.$annee."<br>\n";
		//print 'event daykey='.$daykey.' dol_print_date(daykey)='.dol_print_date($daykey, 'dayhour', 'gmt').' day='.$day.' month='.$month.' year='.$year."<br>\n";

		if ($day == $jour && $month == $mois && $year == $annee) {
			foreach ($eventarray[$daykey] as $index => $event) {
				if ($i < $maxprint || $maxprint == 0 || getDolGlobalString('MAIN_JS_SWITCH_AGENDA')) {
					$keysofuserassigned = array_keys($event->userassigned);
					$ponct = ($event->date_start_in_calendar == $event->date_end_in_calendar);

					// Define $color (Hex string like '0088FF') and $cssclass of event
					$color = -1;
					$cssclass = '';
					$colorindex = -1;
					if (in_array($user->id, $keysofuserassigned)) {
						$cssclass = 'family_mytasks';

						if (empty($cacheusers[$event->userownerid])) {
							$newuser = new User($db);
							$newuser->fetch($event->userownerid);
							$cacheusers[$event->userownerid] = $newuser;
						}
						//var_dump($cacheusers[$event->userownerid]->color);

						// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
						if (!empty($cacheusers[$event->userownerid]->color)) {
							$color = $cacheusers[$event->userownerid]->color;
						}
					} elseif ($event->type_code == 'ICALEVENT') {      // Event come from external ical file
						$numical++;
						if (!empty($event->icalname)) {
							if (!isset($numicals[dol_string_nospecial($event->icalname)])) {
								$numicals[dol_string_nospecial($event->icalname)] = 0;
							}
							$numicals[dol_string_nospecial($event->icalname)]++;
						}

						$color = ($event->icalcolor ? $event->icalcolor : -1);
						$cssclass = (!empty($event->icalname) ? 'family_ext'.md5($event->icalname) : 'family_other');
					} elseif ($event->type_code == 'BIRTHDAY') {
						$numbirthday++;
						$colorindex = 2;
						$cssclass = 'family_birthday ';
						$color = sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
					} elseif ($event->type == 'bookcal_calendar') {
						$numbirthday++;
						$colorindex = 3;
						$cssclass = 'family_bookcal_calendar_'.(!empty($bookcalcalendarsarray["availabilitieslink"]) ? $bookcalcalendarsarray["availabilitieslink"][$event->fk_bookcal_calendar] : "");
						$color = sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
					} else {
						$numother++;
						$color = ($event->icalcolor ? $event->icalcolor : -1);
						$cssclass = (!empty($event->icalname) ? 'family_ext'.md5($event->icalname) : 'family_other');

						if (empty($cacheusers[$event->userownerid])) {
							$newuser = new User($db);
							$newuser->fetch($event->userownerid);
							$cacheusers[$event->userownerid] = $newuser;
						}
						//var_dump($cacheusers[$event->userownerid]->color);

						// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
						if (!empty($cacheusers[$event->userownerid]->color)) {
							$color = $cacheusers[$event->userownerid]->color;
						}
					}

					if ($color < 0) {	// Color was not set on user card. Set color according to color index.
						// Define color index if not yet defined
						$idusertouse = ($event->userownerid ? $event->userownerid : 0);
						if (isset($colorindexused[$idusertouse])) {
							$colorindex = $colorindexused[$idusertouse]; // Color already assigned to this user
						} else {
							$colorindex = $nextindextouse;
							$colorindexused[$idusertouse] = $colorindex;
							if (!empty($theme_datacolor[$nextindextouse + 1])) {
								$nextindextouse++; // Prepare to use next color
							}
						}
						//print '|'.($color).'='.($idusertouse?$idusertouse:0).'='.$colorindex.'<br>';
						// Define color  // @suppress-next-line PhanPluginPrintfIncompatibleArgumentType
						$color = sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
					}
					$cssclass = $cssclass.' eventday_'.$ymd;

					// Defined style to disable drag and drop feature
					if ($event->type_code == 'AC_OTH_AUTO') {
						$cssclass .= " unmovable";
					} elseif ($event->type_code == 'HOLIDAY') {
						$cssclass .= " unmovable";
					} elseif ($event->type_code == 'BIRTHDAY') {
						$cssclass .= " unmovable";
					} elseif ($event->type_code == 'ICALEVENT') {
						$cssclass .= " unmovable";
					} elseif ($event->date_start_in_calendar && $event->date_end_in_calendar && date('Ymd', $event->date_start_in_calendar) != date('Ymd', $event->date_end_in_calendar)) {
						// If the event is on several days
						$tmpyearend = dol_print_date($event->date_start_in_calendar, '%Y', 'tzuserrel');
						$tmpmonthend = dol_print_date($event->date_start_in_calendar, '%m', 'tzuserrel');
						$tmpdayend = dol_print_date($event->date_start_in_calendar, '%d', 'tzuserrel');
						//var_dump($tmpyearend.' '.$tmpmonthend.' '.$tmpdayend);
						if ($tmpyearend != $annee || $tmpmonthend != $mois || $tmpdayend != $jour) {
							$cssclass .= " unmovable unmovable-mustusefirstdaytodrag";
						} else {
							$cssclass .= ' movable cursormove';
						}
					} else {
						if ($user->hasRight('agenda', 'allactions', 'create') ||
							(($event->authorid == $user->id || $event->userownerid == $user->id) && $user->hasRight('agenda', 'myactions', 'create'))) {
							$cssclass .= " movable cursormove";
						} else {
							$cssclass .= " unmovable";
						}
					}

					$h = '';
					$nowrapontd = 1;
					if ($mode == 'show_day') {
						$h = 'height: 100%; ';
						$nowrapontd = 0;
					}
					if ($mode == 'show_week') {
						$h = 'height: 100%; ';
						$nowrapontd = 0;
					}

					// Show event box
					print "\n";
					print '<!-- start event '.$i.' -->'."\n";

					$morecss = '';
					if ($maxprint && $ireallyshown >= $maxprint) {
						$morecss = 'showifmore';
					}
					if ($event->type == 'birthdate' && !GETPOST('check_birthday')) {
						$morecss = 'hidden';
					}
					if ($event->type == 'holiday' && !GETPOST('check_holiday')) {
						$morecss = 'hidden';
					}
					/* I comment this because it hides event recorded from bookcal online page
					if ($event->type == 'bookcal_calendar' && !GETPOST('check_bookcal_calendar_'.$bookcalcalendarsarray["availabilitieslink"][$event->fk_bookcal_calendar])) {
						$morecss = 'hidden';
					} */
					if ($morecss != 'hidden') {
						$itoshow++;
					}
					if ($morecss != 'showifmore' && $morecss != 'hidden') {
						$ireallyshown++;
					}

					//var_dump($event->type.' - '.$morecss.' - '.$cssclass.' - '.$i.' - '.$ireallyshown.' - '.$itoshow);
					if (isModEnabled("bookcal") && $event->type == 'bookcal_calendar') {
						print '<div id="event_'.$ymd.'_'.$i.'" class="event family_'.$event->type.'_'.$bookcalcalendarsarray["availabilitieslink"][$event->fk_bookcal_calendar].' '.$cssclass.($morecss ? ' '.$morecss : '').'"';
					} else {
						print '<div id="event_'.$ymd.'_'.$i.'" class="event family_'.$event->type.' '.$cssclass.($morecss ? ' '.$morecss : '').'"';
					}
					//print ' style="height: 100px;';
					//print ' position: absolute; top: 40px; width: 50%;';
					//print '"';
					print '>';

					//var_dump($event->userassigned);
					//var_dump($event->transparency);
					print '<table class="centpercent cal_event';
					print(empty($event->transparency) ? ' cal_event_notbusy' : ' cal_event_busy');
					//if (empty($event->transparency) && empty($conf->global->AGENDA_NO_TRANSPARENT_ON_NOT_BUSY)) print ' opacitymedium';	// Not busy
					print '" style="'.$h;
					$colortouse = $color;
					// If colortouse is similar than background, we force to change it.
					if (empty($event->transparency) && !getDolGlobalString('AGENDA_NO_TRANSPARENT_ON_NOT_BUSY')) {
						print 'background: #f0f0f0;';
						print 'border-left: 5px solid #'.$colortouse.';';
					} else {
						print 'background: #f0f0f0;';
						print 'border-left: 5px solid #'.dol_color_minus($colortouse, -3).';';
						//print 'background: -webkit-gradient(linear, left top, left bottom, from(#'.dol_color_minus($colortouse, -3).'), to(#'.dol_color_minus($colortouse, -1).'));';
					}
					//print 'background: #'.$colortouse.';';
					//print 'background: -webkit-gradient(linear, left top, left bottom, from(#'.dol_color_minus($color, -3).'), to(#'.dol_color_minus($color, -1).'));';
					//if (!empty($event->transparency)) print 'background: #'.$color.'; background: -webkit-gradient(linear, left top, left bottom, from(#'.$color.'), to(#'.dol_color_minus($color,1).'));';
					//else print 'background-color: transparent !important; background: none; border: 1px solid #bbb;';
					//print ' -moz-border-radius:4px;"';
					//print 'border: 1px solid #ccc" width="100%"';
					print '">';
					print '<tr>';
					print '<td class="tdoverflow nobottom small centpercent '.($nowrapontd ? 'nowrap ' : '').'cal_event'.($event->type_code == 'BIRTHDAY' ? ' cal_event_birthday' : '').'">';
					print '<!-- left section of event -->';

					$daterange = '';

					if ($event->type_code == 'BIRTHDAY') {
						// It's birthday calendar
						$picb = '<i class="fas fa-birthday-cake inline-block valignmiddle"></i>';
						//$pice = '<i class="fas fa-briefcase inline-block"></i>';
						//$typea = ($objp->typea == 'birth') ? $picb : $pice;
						//var_dump($event);
						print $picb.' '.$langs->trans("Birthday").'<br>';
						//print img_picto($langs->trans("Birthday"), 'birthday-cake').' ';

						$tmpid = $event->id;

						if (empty($cachecontacts[$tmpid])) {
							$newcontact = new Contact($db);
							$newcontact->fetch($tmpid);
							$cachecontacts[$tmpid] = $newcontact;
						}
						print $cachecontacts[$tmpid]->getNomUrl(1, '', 0, '', -1, 0, 'valignmiddle inline-block');

						//$event->picto = 'birthday-cake';
						//print $event->getNomUrl(1, $maxnbofchar, 'cal_event', 'birthday', 'contact');
						/*$listofcontacttoshow = '';
						$listofcontacttoshow .= '<br>'.$cacheusers[$tmpid]->getNomUrl(-1, '', 0, 0, 0, 0, '', 'paddingright valignmiddle');
						print $listofcontacttoshow;
						*/
					} elseif ($event->type_code == 'HOLIDAY') {
						// It's holiday calendar
						$tmpholiday->fetch($event->id);

						print $tmpholiday->getNomUrl(1, -1, 0, 'valignmiddle inline-block');

						$tmpid = $tmpholiday->fk_user;
						if (empty($cacheusers[$tmpid])) {
							$newuser = new User($db);
							$newuser->fetch($tmpid);
							$cacheusers[$tmpid] = $newuser;
						}

						$listofusertoshow = '';
						$listofusertoshow .= '<br>'.$cacheusers[$tmpid]->getNomUrl(-1, '', 0, 0, 0, 0, '', 'paddingright valignmiddle inline-block');
						print $listofusertoshow;
					}

					$parameters = array();
					$reshook = $hookmanager->executeHooks('eventOptions', $parameters, $event, $action); // Note that $action and $object may have been modified by some hooks
					if ($reshook < 0) {
						setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
					} else {
						'@phan-var-force ActionComm $event';
						if (empty($reshook)) {
							// Other calendar
							if (empty($event->fulldayevent)) {
								//print $event->getNomUrl(2).' ';
							}

							// Date
							if (empty($event->fulldayevent)) {
								// Show hours (start ... end)
								$tmpyearstart  = dol_print_date($event->date_start_in_calendar, '%Y', 'tzuserrel');
								$tmpmonthstart = dol_print_date($event->date_start_in_calendar, '%m', 'tzuserrel');
								$tmpdaystart   = dol_print_date($event->date_start_in_calendar, '%d', 'tzuserrel');
								$tmpyearend    = dol_print_date($event->date_end_in_calendar, '%Y', 'tzuserrel');
								$tmpmonthend   = dol_print_date($event->date_end_in_calendar, '%m', 'tzuserrel');
								$tmpdayend     = dol_print_date($event->date_end_in_calendar, '%d', 'tzuserrel');

								// Hour start
								if ($tmpyearstart == $annee && $tmpmonthstart == $mois && $tmpdaystart == $jour) {
									$daterange .= dol_print_date($event->date_start_in_calendar, 'hour', 'tzuserrel');
									if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar) {
										if ($tmpyearstart == $tmpyearend && $tmpmonthstart == $tmpmonthend && $tmpdaystart == $tmpdayend) {
											$daterange .= '-';
										}
										//else
										//print '...';
									}
								}
								if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar) {
									if ($tmpyearstart != $tmpyearend || $tmpmonthstart != $tmpmonthend || $tmpdaystart != $tmpdayend) {
										$daterange .= '...';
									}
								}
								// Hour end
								if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar) {
									if ($tmpyearend == $annee && $tmpmonthend == $mois && $tmpdayend == $jour) {
										$daterange .= dol_print_date($event->date_end_in_calendar, 'hour', 'tzuserrel');
									}
								}
							} else {
								if ($showinfo) {
									print $langs->trans("EventOnFullDay")."<br>\n";
								}
							}

							// Show title
							$titletoshow = $daterange;
							$titletoshow .= ($titletoshow ? ' ' : '').dol_escape_htmltag($event->label);

							if ($event->type_code != 'ICALEVENT') {
								$savlabel = $event->label;
								$event->label = $titletoshow;
								// Note: List of users are inside $event->userassigned. Link may be clickable depending on permissions of user.
								$titletoshow = (($event->type_picto || $event->type_code) ? $event->getTypePicto() : '');
								$titletoshow .= $event->getNomUrl(0, $maxnbofchar, 'cal_event cal_event_title valignmiddle', '', 0, 0);	// do not add 'inline-block' in css here: it makes the title transformed completely into '...'
								$event->label = $savlabel;
							}

							// Loop on each assigned user
							$listofusertoshow = '';
							$posuserassigned = 0;
							foreach ($event->userassigned as $tmpid => $tmpdata) {
								if (!$posuserassigned && $titletoshow) {
									$listofusertoshow .= '<br>';
								}
								$posuserassigned++;
								if (empty($cacheusers[$tmpid])) {
									$newuser = new User($db);
									$newuser->fetch($tmpid);
									$cacheusers[$tmpid] = $newuser;
								}

								$listofusertoshow .= $cacheusers[$tmpid]->getNomUrl(-3, '', 0, 0, 0, 0, '', 'valignmiddle inline-block');
							}

							print $titletoshow;
							print $listofusertoshow.' &nbsp;';

							if ($event->type_code == 'ICALEVENT') {
								print '<br>('.dol_trunc($event->icalname, $maxnbofchar).')';
							}

							$thirdparty_id = ($event->socid > 0 ? $event->socid : ((is_object($event->societe) && $event->societe->id > 0) ? $event->societe->id : 0));
							$contact_id = ($event->contact_id > 0 ? $event->contact_id : ((is_object($event->contact) && $event->contact->id > 0) ? $event->contact->id : 0));

							// If action related to company / contact
							$linerelatedto = '';
							if ($thirdparty_id > 0) {
								if (!isset($cachethirdparties[$thirdparty_id]) || !is_object($cachethirdparties[$thirdparty_id])) {
									$thirdparty = new Societe($db);
									$thirdparty->fetch($thirdparty_id);
									$cachethirdparties[$thirdparty_id] = $thirdparty;
								} else {
									$thirdparty = $cachethirdparties[$thirdparty_id];
								}
								if (!empty($thirdparty->id)) {
									$linerelatedto .= $thirdparty->getNomUrl(1, '', 0, 0, -1, 0, '', 'valignmiddle inline-block');
								}
							}
							if (!empty($contact_id) && $contact_id > 0) {
								if (empty($cachecontacts[$contact_id]) || !is_object($cachecontacts[$contact_id])) {
									$contact = new Contact($db);
									$contact->fetch($contact_id);
									$cachecontacts[$contact_id] = $contact;
								} else {
									$contact = $cachecontacts[$contact_id];
								}
								if ($linerelatedto) {
									$linerelatedto .= '&nbsp;';
								}
								if (!empty($contact->id)) {
									$linerelatedto .= $contact->getNomUrl(1, '', 0, '', -1, 0, 'valignmiddle inline-block');
								}
							}
							if (!empty($event->fk_element) && $event->fk_element > 0 && !empty($event->elementtype) && getDolGlobalString('AGENDA_SHOW_LINKED_OBJECT')) {
								include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
								if ($linerelatedto) {
									$linerelatedto .= '<br>';
								}
								$linerelatedto .= dolGetElementUrl($event->fk_element, $event->elementtype, 1);
							}
							if ($linerelatedto) {
								print ' '.$linerelatedto;
							}
						} elseif (!empty($reshook)) {
							print $hookmanager->resPrint;
						}
					}

					// Show location
					if ($showinfo) {
						if ($event->location) {
							print '<br>';
							print $langs->trans("Location").': '.$event->location;
						}
					}

					print '</td>';
					// Status - Percent
					$withstatus = 0;
					if ($event->type_code != 'BIRTHDAY' && $event->type_code != 'ICALEVENT') {
						$withstatus = 1;
						if ($event->percentage >= 0) {
							$withstatus = 2;
						}
					}
					print '<td class="nobottom right nowrap cal_event_right'.($withstatus >= 2 ? ' cal_event_right_status' : '').'">';
					if ($withstatus) {
						print $event->getLibStatut(3, 1);
					} else {
						print '&nbsp;';
					}
					print '</td></tr></table>';
					print '</div><!-- end event '.$i.' -->'."\n";

					$i++;
				} else {
					print '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode='.$mode.'&maxprint=0&month='.((int) $monthshown).'&year='.((int) $year);
					print($status ? '&status='.$status : '').($filter ? '&filter='.urlencode($filter) : '');
					print($filtert ? '&search_filtert='.urlencode((string) $filtert) : '');
					print($usergroup ? '&search_usergroup='.urlencode($usergroup) : '');
					print($actioncode != '' ? '&search_actioncode='.urlencode($actioncode) : '');
					print '">'.img_picto("all", "1downarrow_selected.png").' ...';
					print ' +'.(count($eventarray[$daykey]) - $maxprint);
					print '</a>';
					break;
					//$ok=false;        // To avoid to show twice the link
				}
			}

			break;
		}
	}
	if (!$i) {	// No events
		print '&nbsp;';
	}

	if (getDolGlobalString('MAIN_JS_SWITCH_AGENDA') && $itoshow > $ireallyshown && $maxprint) {
		print '<div class="center cursorpointer" id="more_'.$ymd.'">'.img_picto("All", "angle-double-down", 'class="warning"').' +'.($itoshow - $ireallyshown).'</div>';
		//print ' +'.(count($eventarray[$daykey])-$maxprint);

		print '<script type="text/javascript">'."\n";
		print 'jQuery(document).ready(function () {'."\n";
		print ' var open=0;'."\n";
		print ' jQuery("#more_'.$ymd.'").click(function() { console.log("Click on showmore for '.$ymd.'"); reinit_day_'.$ymd.'(); event.stopImmediatePropagation(); });'."\n";
		print ' function reinit_day_'.$ymd.'() {'."\n";
		print '  jQuery(".eventday_'.$ymd.'.showifmore").toggle();'."\n";
		print '  open = open + 1; if (open > 1) { open = 0; }'."\n";
		print '  if (open) { ';
		print '   jQuery("#more_'.$ymd.'").html(\''.img_picto("All", "angle-double-up", 'class="warning"').'\');'."\n";
		print '  } else { ';
		print '   jQuery("#more_'.$ymd.'").html(\''.img_picto("All", "angle-double-down", 'class="warning"').' +'.($itoshow - $ireallyshown).'\');'."\n";
		print '  }'."\n";
		print ' }'."\n";
		print '});'."\n";
		print '</script>'."\n";
	}

	print '</div></div>'; // td tr

	print '</div>'; // table
	print "\n";
}


/**
 * Change color with a delta
 *
 * @param	string	$color		Color
 * @param 	int		$minus		Delta (1 = 16 unit). Positive value = darker color, Negative value = brighter color.
 * @param   int     $minusunit  Minus unit
 * @return	string				New color
 */
function dol_color_minus($color, $minus, $minusunit = 16)
{
	$newcolor = $color;
	if ($minusunit == 16) {
		$newcolor[0] = dechex(max(min(hexdec($newcolor[0]) - $minus, 15), 0));
		$newcolor[2] = dechex(max(min(hexdec($newcolor[2]) - $minus, 15), 0));
		$newcolor[4] = dechex(max(min(hexdec($newcolor[4]) - $minus, 15), 0));
	} else {
		// Not yet implemented
	}
	return $newcolor;
}

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
		return $a->datep - $b->datep;
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
	return $b->datef - $a->datef;
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

	return $b->percentage - $a->percentage;
}
