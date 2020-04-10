<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/comm/action/pertype.php
 *  \ingroup    agenda
 *  \brief      Tab of calendar events per type
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';


if (!isset($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW)) $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW = 3;

$filter = GETPOST("filter", 'alpha', 3);
$filtert = GETPOST("filtert", "int", 3);
$usergroup = GETPOST("usergroup", "int", 3);
//if (! ($usergroup > 0) && ! ($filtert > 0)) $filtert = $user->id;
//$showbirthday = empty($conf->use_javascript_ajax)?GETPOST("showbirthday","int"):1;
$showbirthday = 0;

// If not choice done on calendar owner, we filter on user.
if (empty($filtert) && empty($conf->global->AGENDA_ALL_CALENDARS))
{
	$filtert = $user->id;
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$offset = $limit * $page;
if (!$sortorder) $sortorder = "ASC";
if (!$sortfield) $sortfield = "a.datec";

// Security check
$socid = GETPOST("socid", "int");
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if ($socid < 0) $socid = '';

$canedit = 1;
if (!$user->rights->agenda->myactions->read) accessforbidden();
if (!$user->rights->agenda->allactions->read) $canedit = 0;
if (!$user->rights->agenda->allactions->read || $filter == 'mine')  // If no permission to see all, we show only affected to me
{
    $filtert = $user->id;
}

//$action=GETPOST('action','alpha');
$action = 'show_pertype';
$resourceid = GETPOST("resourceid", "int");
$year = GETPOST("year", "int") ?GETPOST("year", "int") : date("Y");
$month = GETPOST("month", "int") ?GETPOST("month", "int") : date("m");
$week = GETPOST("week", "int") ?GETPOST("week", "int") : date("W");
$day = GETPOST("day", "int") ?GETPOST("day", "int") : date("d");
$pid = GETPOST("projectid", "int", 3);
$status = GETPOST("status", 'alpha');
$type = GETPOST("type", 'alpha');
$maxprint = ((GETPOST("maxprint", 'int') != '') ?GETPOST("maxprint", 'int') : $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
// Set actioncode (this code must be same for setting actioncode into peruser, listacton and index)
if (GETPOST('actioncode', 'array'))
{
    $actioncode = GETPOST('actioncode', 'array', 3);
    if (!count($actioncode)) $actioncode = '0';
}
else
{
    $actioncode = GETPOST("actioncode", "alpha", 3) ?GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode", "alpha") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE));
}
if ($actioncode == '' && empty($actioncodearray)) $actioncode = (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE);

$dateselect = dol_mktime(0, 0, 0, GETPOST('dateselectmonth', 'int'), GETPOST('dateselectday', 'int'), GETPOST('dateselectyear', 'int'));
if ($dateselect > 0)
{
	$day = GETPOST('dateselectday', 'int');
	$month = GETPOST('dateselectmonth', 'int');
	$year = GETPOST('dateselectyear', 'int');
}

$tmp = empty($conf->global->MAIN_DEFAULT_WORKING_HOURS) ? '9-18' : $conf->global->MAIN_DEFAULT_WORKING_HOURS;
$tmparray = explode('-', $tmp);
$begin_h = GETPOST('begin_h', 'int') != '' ?GETPOST('begin_h', 'int') : ($tmparray[0] != '' ? $tmparray[0] : 9);
$end_h   = GETPOST('end_h', 'int') ?GETPOST('end_h', 'int') : ($tmparray[1] != '' ? $tmparray[1] : 18);
if ($begin_h < 0 || $begin_h > 23) $begin_h = 9;
if ($end_h < 1 || $end_h > 24) $end_h = 18;
if ($end_h <= $begin_h) $end_h = $begin_h + 1;

$tmp = empty($conf->global->MAIN_DEFAULT_WORKING_DAYS) ? '1-5' : $conf->global->MAIN_DEFAULT_WORKING_DAYS;
$tmparray = explode('-', $tmp);
$begin_d = 1;
$end_d   = 53;

if ($status == '' && !isset($_GET['status']) && !isset($_POST['status'])) $status = (empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_STATUS);
if (empty($action) && !isset($_GET['action']) && !isset($_POST['action'])) $action = (empty($conf->global->AGENDA_DEFAULT_VIEW) ? 'show_month' : $conf->global->AGENDA_DEFAULT_VIEW);

if (GETPOST('viewcal') && $action != 'show_day' && $action != 'show_week' && $action != 'show_peruser') {
    $action = 'show_month'; $day = '';
}                                                   // View by month
if (GETPOST('viewweek', 'alpha') || $action == 'show_week') {
    $action = 'show_week'; $week = ($week ? $week : date("W")); $day = ($day ? $day : date("d"));
}  // View by week
if (GETPOST('viewday', 'alpha') || $action == 'show_day') {
    $action = 'show_day'; $day = ($day ? $day : date("d"));
}                                  // View by day
if (GETPOST('viewyear', 'alpha') || $action == 'show_year') {
    $action = 'show_year';
}                                  // View by year

// Load translation files required by the page
$langs->loadLangs(array('users', 'agenda', 'other', 'commercial'));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('agenda'));


/*
 * Actions
 */

if ($action == 'delete_action')
{
    $event = new ActionComm($db);
    $event->fetch($actionid);
    $result = $event->delete();
}



/*
 * View
 */

$form = new Form($db);
$companystatic = new Societe($db);

$help_url = 'EN:Module_Agenda_En|FR:Module_Agenda|ES:M&oacute;dulo_Agenda';
llxHeader('', $langs->trans("Agenda"), $help_url);

$now = dol_now();
$nowarray = dol_getdate($now);
$nowyear = $nowarray['year'];
$nowmonth = $nowarray['mon'];
$nowday = $nowarray['mday'];


// Define list of all external calendars (global setup)
$listofextcals = array();

$prev = dol_get_first_day($year, $month);
$first_day   = 1;
$first_month = 1;
$first_year  = $year;

$week = $prev['week'];

$day = (int) $day;
$next = dol_get_next_day($day, $month, $year);
$next_year  = $year + 1;
$next_month = $month;
$next_day   = $day;

$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));

$tmpday = $first_day;
//print 'xx'.$prev_year.'-'.$prev_month.'-'.$prev_day;
//print 'xx'.$next_year.'-'.$next_month.'-'.$next_day;

$title = $langs->trans("DoneAndToDoActions");
if ($status == 'done') $title = $langs->trans("DoneActions");
if ($status == 'todo') $title = $langs->trans("ToDoActions");

$param = '';
if ($actioncode || isset($_GET['actioncode']) || isset($_POST['actioncode'])) $param .= "&actioncode=".$actioncode;
if ($resourceid > 0) $param .= "&resourceid=".$resourceid;
if ($status || isset($_GET['status']) || isset($_POST['status'])) $param .= "&status=".$status;
if ($filter)  $param .= "&filter=".$filter;
if ($filtert) $param .= "&filtert=".$filtert;
if ($usergroup) $param .= "&usergroup=".$usergroup;
if ($socid)   $param .= "&socid=".$socid;
if ($showbirthday) $param .= "&showbirthday=1";
if ($pid)     $param .= "&projectid=".$pid;
if ($type)   $param .= "&type=".$type;
if ($action == 'show_day' || $action == 'show_week' || $action == 'show_month' || $action != 'show_peruser' || $action != 'show_pertype') $param .= '&action='.$action;
$param .= "&maxprint=".$maxprint;

$prev = dol_get_first_day($year, 1);
$prev_year  = $year - 1;
$prev_month = $month;
$prev_day   = $day;
$first_day  = 1;
$first_month = 1;
$first_year = $year;

$week = $prev['week'];

$day = (int) $day;
$next = dol_get_next_day(31, 12, $year);
$next_year  = $year + 1;
$next_month = $month;
$next_day   = $day;

// Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
$firstdaytoshow = dol_mktime(0, 0, 0, $first_month, $first_day, $first_year);
$lastdaytoshow = dol_time_plus_duree($firstdaytoshow, 7, 'd');
//print $firstday.'-'.$first_month.'-'.$first_year;
//print dol_print_date($firstdaytoshow,'dayhour');
//print dol_print_date($lastdaytoshow,'dayhour');

$max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));

$tmpday = $first_day;

$nav = "<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
$nav .= " <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $first_month, $first_day, $first_year), "%Y")."</span> \n";
$nav .= "<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\">".img_next($langs->trans("Next"))."</a>\n";
$nav .= " &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
$picto = 'calendarweek';

$nav .= ' &nbsp; <form name="dateselect" action="'.$_SERVER["PHP_SELF"].'?action=show_peruser'.$param.'">';
$nav .= '<input type="hidden" name="token" value="'.newToken().'">';
$nav .= '<input type="hidden" name="action" value="'.$action.'">';
$nav .= '<input type="hidden" name="filtert" value="'.$filtert.'">';
$nav .= '<input type="hidden" name="usergroup" value="'.$usergroup.'">';
$nav .= '<input type="hidden" name="actioncode" value="'.$actioncode.'">';
$nav .= '<input type="hidden" name="resourceid" value="'.$resourceid.'">';
$nav .= '<input type="hidden" name="status" value="'.$status.'">';
$nav .= '<input type="hidden" name="socid" value="'.$socid.'">';
$nav .= '<input type="hidden" name="projectid" value="'.$projectid.'">';
$nav .= '<input type="hidden" name="begin_h" value="'.$begin_h.'">';
$nav .= '<input type="hidden" name="end_h" value="'.$end_h.'">';
$nav .= '<input type="hidden" name="begin_d" value="'.$begin_d.'">';
$nav .= '<input type="hidden" name="end_d" value="'.$end_d.'">';
$nav .= '<input type="hidden" name="showbirthday" value="'.$showbirthday.'">';

$nav .= $form->selectDate($dateselect, 'dateselect', 0, 0, 1, '', 1, 0);
$nav .= ' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';
$nav .= '</form>';

// Must be after the nav definition
$param .= '&year='.$year.'&month='.$month.($day ? '&day='.$day : '');
//print 'x'.$param;




$tabactive = 'cardpertype';

$paramnoaction = preg_replace('/action=[a-z_]+/', '', $param);

$head = calendars_prepare_head($paramnoaction);

dol_fiche_head($head, $tabactive, $langs->trans('Agenda'), 0, 'action');
print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, $listofextcals, $actioncode, $usergroup, '', $resourceid);
dol_fiche_end();

$showextcals = $listofextcals;
// Legend
if ($conf->use_javascript_ajax)
{
	$s = '';
	$s .= '<script type="text/javascript">'."\n";
	$s .= 'jQuery(document).ready(function () {'."\n";
	$s .= 'jQuery("#check_mytasks").click(function() { jQuery(".family_mytasks").toggle(); jQuery(".family_other").toggle(); });'."\n";
	$s .= 'jQuery("#check_birthday").click(function() { jQuery(".family_birthday").toggle(); });'."\n";
	$s .= 'jQuery(".family_birthday").toggle();'."\n";
	if ($action == "show_week" || $action == "show_month" || empty($action))
	{
    	$s .= 'jQuery( "td.sortable" ).sortable({connectWith: ".sortable",placeholder: "ui-state-highlight",items: "div:not(.unsortable)", receive: function( event, ui ) {';
    	$s .= 'var frm=jQuery("#move_event");frm.attr("action",ui.item.find("a.cal_event").attr("href")).children("#newdate").val(jQuery(event.target).closest("div").attr("id"));frm.submit();}});'."\n";
	}
  	$s .= '});'."\n";
	$s .= '</script>'."\n";
	if (!empty($conf->use_javascript_ajax))
	{
		$s .= '<div class="nowrap clear float"><input type="checkbox" id="check_mytasks" name="check_mytasks" checked disabled> '.$langs->trans("LocalAgenda").' &nbsp; </div>';
		if (is_array($showextcals) && count($showextcals) > 0)
		{
			foreach ($showextcals as $val)
			{
				$htmlname = md5($val['name']);
				$s .= '<script type="text/javascript">'."\n";
				$s .= 'jQuery(document).ready(function () {'."\n";
				$s .= '		jQuery("#check_ext'.$htmlname.'").click(function() {';
				$s .= ' 		/* alert("'.$htmlname.'"); */';
				$s .= ' 		jQuery(".family_ext'.$htmlname.'").toggle();';
				$s .= '		});'."\n";
				$s .= '});'."\n";
				$s .= '</script>'."\n";
				$s .= '<div class="nowrap float"><input type="checkbox" id="check_ext'.$htmlname.'" name="check_ext'.$htmlname.'" checked> '.$val ['name'].' &nbsp; </div>';
			}
		}

		//$s.='<div class="nowrap float"><input type="checkbox" id="check_birthday" name="check_birthday"> '.$langs->trans("AgendaShowBirthdayEvents").' &nbsp; </div>';

		// Calendars from hooks
	    $parameters = array(); $object = null;
		$reshook = $hookmanager->executeHooks('addCalendarChoice', $parameters, $object, $action);
	    if (empty($reshook))
	    {
			$s .= $hookmanager->resPrint;
	    }
	    elseif ($reshook > 1)
		{
	    	$s = $hookmanager->resPrint;
	    }
	}
}



$link = '';
print load_fiche_titre($s, $link.' &nbsp; &nbsp; '.$nav, '');


// Get event in an array
$eventarray = array();

$sql = 'SELECT';
if ($usergroup > 0) $sql .= " DISTINCT";
$sql .= ' a.id, a.label,';
$sql .= ' a.datep,';
$sql .= ' a.datep2,';
$sql .= ' a.percent,';
$sql .= ' a.fk_user_author,a.fk_user_action,';
$sql .= ' a.transparency, a.priority, a.fulldayevent, a.location,';
$sql .= ' a.fk_soc, a.fk_contact, a.fk_element, a.elementtype, a.fk_project,';
$sql .= ' ca.code, ca.color';
$sql .= ' FROM '.MAIN_DB_PREFIX.'c_actioncomm as ca, '.MAIN_DB_PREFIX."actioncomm as a";
if (!$user->rights->societe->client->voir && !$socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
// We must filter on resource table
if ($resourceid > 0) $sql .= ", ".MAIN_DB_PREFIX."element_resources as r";
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0) $sql .= ", ".MAIN_DB_PREFIX."actioncomm_resources as ar";
if ($usergroup > 0) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
$sql .= ' WHERE a.fk_action = ca.id';
$sql .= ' AND a.entity IN ('.getEntity('agenda').')';
// Condition on actioncode
if (!empty($actioncode))
{
    if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
    {
        if ($actioncode == 'AC_NON_AUTO') $sql .= " AND ca.type != 'systemauto'";
        elseif ($actioncode == 'AC_ALL_AUTO') $sql .= " AND ca.type = 'systemauto'";
        else
        {
            if ($actioncode == 'AC_OTH') $sql .= " AND ca.type != 'systemauto'";
            if ($actioncode == 'AC_OTH_AUTO') $sql .= " AND ca.type = 'systemauto'";
        }
    }
    else
    {
        if ($actioncode == 'AC_NON_AUTO') $sql .= " AND ca.type != 'systemauto'";
        elseif ($actioncode == 'AC_ALL_AUTO') $sql .= " AND ca.type = 'systemauto'";
        else
        {
            $sql .= " AND ca.code IN ('".implode("','", explode(',', $actioncode))."')";
        }
    }
}
if ($resourceid > 0) $sql .= " AND r.element_type = 'action' AND r.element_id = a.id AND r.resource_id = ".$db->escape($resourceid);
if ($pid) $sql .= " AND a.fk_project=".$db->escape($pid);
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND (a.fk_soc IS NULL OR sc.fk_user = ".$user->id.")";
if ($socid > 0) $sql .= ' AND a.fk_soc = '.$socid;
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0) $sql .= " AND ar.fk_actioncomm = a.id AND ar.element_type='user'";
if ($action == 'show_day')
{
    $sql .= " AND (";
    $sql .= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."'";
    $sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."')";
    $sql .= " OR ";
    $sql .= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."'";
    $sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."')";
    $sql .= " OR ";
    $sql .= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."'";
    $sql .= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."')";
    $sql .= ')';
}
else
{
    // To limit array
    $sql .= " AND (";
    $sql .= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, 1, 1, $year) - (60 * 60 * 24 * 7))."'"; // Start 7 days before
    $sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, 12, 31, $year) + (60 * 60 * 24 * 7))."')"; // End 7 days after
    $sql .= " OR ";
    $sql .= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, 1, 1, $year) - (60 * 60 * 24 * 7))."'";
    $sql .= " AND '".$db->idate(dol_mktime(23, 59, 59, 12, 31, $year) + (60 * 60 * 24 * 7))."')";
    $sql .= " OR ";
    $sql .= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, 12, 1, $year) - (60 * 60 * 24 * 7))."'";
    $sql .= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, 12, 31, $year) + (60 * 60 * 24 * 7))."')";
    $sql .= ')';
}
if ($type) $sql .= " AND ca.id = ".$type;
if ($status == '0') { $sql .= " AND a.percent = 0"; }
if ($status == '-1') { $sql .= " AND a.percent = -1"; }	// Not applicable
if ($status == '50') { $sql .= " AND (a.percent > 0 AND a.percent < 100)"; }	// Running already started
if ($status == 'done' || $status == '100') { $sql .= " AND (a.percent = 100)"; }
if ($status == 'todo') { $sql .= " AND (a.percent >= 0 AND a.percent < 100)"; }
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0)
{
    $sql .= " AND (";
    if ($filtert > 0) $sql .= "ar.fk_element = ".$filtert;
    if ($usergroup > 0) $sql .= ($filtert > 0 ? " OR " : "")." ugu.fk_usergroup = ".$usergroup;
    $sql .= ")";
}
// Sort on date
$sql .= ' ORDER BY fk_user_action, datep'; //fk_user_action
//print $sql;

dol_syslog("comm/action/index.php", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);

        // Discard auto action if option is on
        if (!empty($conf->global->AGENDA_ALWAYS_HIDE_AUTO) && $obj->code == 'AC_OTH_AUTO')
        {
        	$i++;
        	continue;
        }

        $datep = $db->jdate($obj->datep);
        $datep2 = $db->jdate($obj->datep2);

        // Create a new object action
        $event = new ActionComm($db);
        $event->id = $obj->id;
        $event->datep = $datep; // datep and datef are GMT date
        $event->datef = $datep2;
        $event->type_code = $obj->code;
        $event->type_color = $obj->color;
        $event->label = $obj->label;
        $event->percentage = $obj->percent;
        $event->authorid = $obj->fk_user_author; // user id of creator
        $event->userownerid = $obj->fk_user_action; // user id of owner
        $event->priority = $obj->priority;
        $event->fulldayevent = $obj->fulldayevent;
        $event->location = $obj->location;
        $event->transparency = $obj->transparency;

        $event->fk_project = $obj->fk_project;

        $event->socid = $obj->fk_soc;
        $event->contactid = $obj->fk_contact;

        $event->fk_element = $obj->fk_element;
        $event->elementtype = $obj->elementtype;

        // Defined date_start_in_calendar and date_end_in_calendar property
        // They are date start and end of action but modified to not be outside calendar view.
        if ($event->percentage <= 0)
        {
        	$event->date_start_in_calendar = $datep;
        	if ($datep2 != '' && $datep2 >= $datep) $event->date_end_in_calendar = $datep2;
        	else $event->date_end_in_calendar = $datep;
        }
        else
        {
        	$event->date_start_in_calendar = $datep;
        	if ($datep2 != '' && $datep2 >= $datep) $event->date_end_in_calendar = $datep2;
        	else $event->date_end_in_calendar = $datep;
        }
        // Define ponctual property
        if ($event->date_start_in_calendar == $event->date_end_in_calendar)
        {
            $event->ponctuel = 1;
        }

        // Check values
        if ($event->date_end_in_calendar < $firstdaytoshow ||
        $event->date_start_in_calendar >= $lastdaytoshow)
        {
            // This record is out of visible range
        	unset($event);
        }
        else
		{
			//print $i.' - '.dol_print_date($this->date_start_in_calendar, 'dayhour').' - '.dol_print_date($this->date_end_in_calendar, 'dayhour').'<br>'."\n";
			$event->fetch_userassigned(); // This load $event->userassigned

			if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar = $firstdaytoshow;
            if ($event->date_end_in_calendar >= $lastdaytoshow) $event->date_end_in_calendar = ($lastdaytoshow - 1);

            // Add an entry in actionarray for each day
            $daycursor = $event->date_start_in_calendar;
            $annee = date('Y', $daycursor);
            $mois = date('m', $daycursor);
            $jour = date('d', $daycursor);

            // Loop on each day covered by action to prepare an index to show on calendar
            $loop = true; $j = 0;
            $daykey = dol_mktime(0, 0, 0, $mois, $jour, $annee);
            do
			{
                //if ($event->id==408) print 'daykey='.$daykey.' '.$event->datep.' '.$event->datef.'<br>';

                $eventarray[$daykey][] = $event;
                $j++;

                $daykey += 60 * 60 * 24;
                if ($daykey > $event->date_end_in_calendar) $loop = false;
            }
            while ($loop);

            //print 'Event '.$i.' id='.$event->id.' (start='.dol_print_date($event->datep).'-end='.dol_print_date($event->datef);
            //print ' startincalendar='.dol_print_date($event->date_start_in_calendar).'-endincalendar='.dol_print_date($event->date_end_in_calendar).') was added in '.$j.' different index key of array<br>';
        }
        $i++;
    }
}
else
{
    dol_print_error($db);
}

$maxnbofchar = 18;
$cachethirdparties = array();
$cachecontacts = array();

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/theme_vars.inc.php";
if (is_readable($color_file))
{
    include_once $color_file;
}
if (!is_array($theme_datacolor)) $theme_datacolor = array(array(120, 130, 150), array(200, 160, 180), array(190, 190, 220));


$newparam = $param; // newparam is for birthday links
$newparam = preg_replace('/showbirthday=/i', 'showbirthday_=', $newparam); // To avoid replacement when replace day= is done
$newparam = preg_replace('/action=show_month&?/i', '', $newparam);
$newparam = preg_replace('/action=show_week&?/i', '', $newparam);
$newparam = preg_replace('/day=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/month=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/year=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/viewweek=[0-9]+&?/i', '', $newparam);
$newparam = preg_replace('/showbirthday_=/i', 'showbirthday=', $newparam); // Restore correct parameter
$newparam .= '&viewweek=1';

echo '<form id="move_event" action="" method="POST"><input type="hidden" name="action" value="mupdate">';
echo '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER['QUERY_STRING']).'">';
echo '<input type="hidden" name="token" value="'.newToken().'">';
echo '<input type="hidden" name="newdate" id="newdate">';
echo '</form>';


// Line header with list of days

//print "begin_d=".$begin_d." end_d=".$end_d;


echo '<table width="100%" class="nocellnopadd cal_month">';

echo '<tr class="liste_titre">';
echo '<td></td>';
$i = 0; // 0 = sunday,
echo '<td align="center" colspan="'.($end_d - $begin_d).'">';
echo $langs->trans("Year");
print "<br>";
print $year;
echo "</td>\n";
echo "</tr>\n";

echo '<tr class="liste_titre">';
echo '<td></td>';
$i = 0;
for ($h = $begin_d; $h < $end_d; $h++)
{
	echo '<td class="center">';
	print '<small style="font-family: courier">'.sprintf("%02d", $h).'</small>';
	print "</td>";
}
echo "</td>\n";
echo "</tr>\n";


$typeofevents = array();

// Load array of colors by type
$colorsbytype = array();
$labelbytype = array();
$sql = "SELECT code, color, libelle as label FROM ".MAIN_DB_PREFIX."c_actioncomm ORDER BY position";
$resql = $db->query($sql);
while ($obj = $db->fetch_object($resql))
{
	$colorsbytype[$obj->code] = $obj->color;
	$labelbytype[$obj->code] = $obj->label;
}

// Loop on each user to show calendar
$todayarray = dol_getdate($now, 'fast');
$sav = $tmpday;
$showheader = true;
$var = false;
foreach ($typeofevents as $typeofevent)
{
	$var = !$var;
	echo "<tr>";
	echo '<td class="cal_current_month cal_peruserviewname'.($var ? ' cal_impair' : '').'">'.$username->getNomUrl(1).'</td>';
	$tmpday = $sav;

	// Lopp on each day of week
	$i = 0;
	for ($iter_day = 0; $iter_day < 8; $iter_day++)
	{
		if (($i + 1) < $begin_d || ($i + 1) > $end_d)
		{
			$i++;
			continue;
		}

        // Show days of the current week
		$curtime = dol_time_plus_duree($firstdaytoshow, $iter_day, 'd');
		$tmparray = dol_getdate($curtime, 'fast');
		$tmpday = $tmparray['mday'];
		$tmpmonth = $tmparray['mon'];
		$tmpyear = $tmparray['year'];

		$style = 'cal_current_month';
		if ($iter_day == 6) $style .= ' cal_other_month';
		$today = 0;
		if ($todayarray['mday'] == $tmpday && $todayarray['mon'] == $tmpmonth && $todayarray['year'] == $tmpyear) $today = 1;
		if ($today) $style = 'cal_today_peruser';

		show_day_events_pertype($username, $tmpday, $tmpmonth, $tmpyear, $monthshown, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, $showheader, $colorsbytype, $var);

		$i++;
	}
	echo "</tr>\n";
	$showheader = false;
}

echo "</table>\n";


if (!empty($conf->global->AGENDA_USE_EVENT_TYPE))
{
	$langs->load("commercial");
	print '<br>'.$langs->trans("Legend").': <br>';
	foreach ($colorsbytype as $code => $color)
	{
		if ($color)
		{
			print '<div style="float: left; padding: 2px; margin-right: 6px;"><div style="'.($color ? 'background: #'.$color.';' : '').'width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
			print $langs->trans("Action".$code) != "Action".$code ? $langs->trans("Action".$code) : $labelbytype[$code];
			//print $code;
			print '</div>';
		}
	}
	//$color=sprintf("%02x%02x%02x",$theme_datacolor[0][0],$theme_datacolor[0][1],$theme_datacolor[0][2]);
	print '<div style="float: left; padding: 2px; margin-right: 6px;"><div class="peruser_busy" style="width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
	print $langs->trans("Other");
	print '</div>';
	/* TODO Show this if at least one cumulated event
	print '<div style="float: left; padding: 2px; margin-right: 6px;"><div style="background: #222222; width:16px; float: left; margin-right: 4px;">&nbsp;</div>';
	print $langs->trans("SeveralEvents");
	print '</div>';
	*/
}

// Add js code to manage click on a box
print '<script type="text/javascript" language="javascript">
jQuery(document).ready(function() {
	jQuery(".onclickopenref").click(function() {
		var ref=$(this).attr(\'ref\');
		var res = ref.split("_");
		var userid = res[1];
		var year = res[2];
		var month = res[3];
		var day = res[4];
		var hour = res[5];
		var min = res[6];
		var ids = res[7];
		if (ids == \'none\') /* No event */
		{
			/* alert(\'no event\'); */
			url = "'.DOL_URL_ROOT.'/comm/action/card.php?action=create&assignedtouser="+userid+"&datep="+year+month+day+hour+min+"00&backtopage='.urlencode($_SERVER["PHP_SELF"].'?year='.$year.'&month='.$month.'&day='.$day).'"
			window.location.href = url;
		}
		else if (ids.indexOf(",") > -1)	/* There is several events */
		{
			/* alert(\'several events\'); */
			url = "'.DOL_URL_ROOT.'/comm/action/list.php?filtert="+userid+"&dateselectyear="+year+"&dateselectmonth="+month+"&dateselectday="+day;
			window.location.href = url;
		}
		else	/* One event */
		{
			/* alert(\'one event\'); */
			url = "'.DOL_URL_ROOT.'/comm/action/card.php?action=view&id="+ids
			window.location.href = url;
		}
	});
});
</script>';

// End of page
llxFooter();
$db->close();




/**
 * Show event line of a particular day for a user
 *
 * @param	User    $username		Login
 * @param   int		$day            Day
 * @param   int		$month          Month
 * @param   int		$year           Year
 * @param   int		$monthshown     Current month shown in calendar view
 * @param   string	$style          Style to use for this day
 * @param   array	$eventarray    	Array of events
 * @param   int		$maxprint       Nb of actions to show each day on month view (0 means no limit)
 * @param   int		$maxnbofchar    Nb of characters to show for event line
 * @param   string	$newparam       Parameters on current URL
 * @param   int		$showinfo       Add extended information (used by day view)
 * @param   int		$minheight      Minimum height for each event. 60px by default.
 * @param	boolean	$showheader		Show header
 * @param	array	$colorsbytype	Array with colors by type
 * @param	bool	$var			true or false for alternat style on tr/td
 * @return	void
 */
function show_day_events_pertype($username, $day, $month, $year, $monthshown, $style, &$eventarray, $maxprint = 0, $maxnbofchar = 16, $newparam = '', $showinfo = 0, $minheight = 60, $showheader = false, $colorsbytype = array(), $var = false)
{
	global $db;
	global $user, $conf, $langs, $hookmanager, $action;
	global $filter, $filtert, $status, $actioncode; // Filters used into search form
	global $theme_datacolor; // Array with a list of different we can use (come from theme)
	global $cachethirdparties, $cachecontacts, $cacheprojects, $colorindexused;
	global $begin_h, $end_h;

	$cases1 = array(); // Color first half hour
	$cases2 = array(); // Color second half hour

	$curtime = dol_mktime(0, 0, 0, $month, $day, $year);

	$i = 0; $nummytasks = 0; $numother = 0; $numbirthday = 0; $numical = 0; $numicals = array();
	$ymd = sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $day);

	$nextindextouse = count($colorindexused); // At first run, this is 0, so fist user has 0, next 1, ...
	//if ($username->id && $day==1) var_dump($eventarray);

	// We are in a particular day for $username, now we scan all events
	foreach ($eventarray as $daykey => $notused)
	{
		$annee = date('Y', $daykey);
		$mois = date('m', $daykey);
		$jour = date('d', $daykey);
		//print $annee.'-'.$mois.'-'.$jour.' '.$year.'-'.$month.'-'.$day."<br>\n";

		if ($day == $jour && $month == $mois && $year == $annee)	// Is it the day we are looking for when calling function ?
		{
			// Scan all event for this date
			foreach ($eventarray[$daykey] as $index => $event)
			{
				//var_dump($event);

				$keysofuserassigned = array_keys($event->userassigned);
				if (!in_array($username->id, $keysofuserassigned)) continue; // We discard record if event is from another user than user we want to show
				//if ($username->id != $event->userownerid) continue;	// We discard record if event is from another user than user we want to show

				$parameters = array();
				$reshook = $hookmanager->executeHooks('formatEvent', $parameters, $event, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

				$ponct = ($event->date_start_in_calendar == $event->date_end_in_calendar);

				// Define $color (Hex string like '0088FF') and $cssclass of event
				$color = -1; $cssclass = ''; $colorindex = -1;
				if (in_array($user->id, $keysofuserassigned))
				{
					$nummytasks++; $cssclass = 'family_mytasks';
					if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) $color = $event->type_color;
				}
				elseif ($event->type_code == 'ICALEVENT')
				{
					$numical++;
					if (!empty($event->icalname))
					{
						if (!isset($numicals[dol_string_nospecial($event->icalname)])) {
							$numicals[dol_string_nospecial($event->icalname)] = 0;
						}
						$numicals[dol_string_nospecial($event->icalname)]++;
					}

					$color = $event->icalcolor;
					$cssclass = (!empty($event->icalname) ? 'family_ext'.md5($event->icalname) : 'family_other unsortable');
				}
				elseif ($event->type_code == 'BIRTHDAY')
				{
					$numbirthday++; $colorindex = 2; $cssclass = 'family_birthday unsortable'; $color = sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
				}
				else
				{
					$numother++; $cssclass = 'family_other';
					if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) $color = $event->type_color;
				}

				if ($color < 0)	// Color was not forced. Set color according to color index.
				{
					// Define color index if not yet defined
					$idusertouse = ($event->userownerid ? $event->userownerid : 0);
					if (isset($colorindexused[$idusertouse]))
					{
						$colorindex = $colorindexused[$idusertouse]; // Color already assigned to this user
					}
					else
					{
						$colorindex = $nextindextouse;
						$colorindexused[$idusertouse] = $colorindex;
						if (!empty($theme_datacolor[$nextindextouse + 1])) $nextindextouse++; // Prepare to use next color
					}
					// Define color
					$color = sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
				}
				//$cssclass=$cssclass.' '.$cssclass.'_day_'.$ymd;

				// Define all rects with event (cases1 is first half hour, cases2 is second half hour)
				for ($h = $begin_h; $h < $end_h; $h++)
				{
					//if ($username->id == 1 && $day==1) print 'h='.$h;
					$newcolor = ''; //init
					if (empty($event->fulldayevent))
					{
						$a = dol_mktime((int) $h, 0, 0, $month, $day, $year, false, 0);
						$b = dol_mktime((int) $h, 30, 0, $month, $day, $year, false, 0);
						$c = dol_mktime((int) $h + 1, 0, 0, $month, $day, $year, false, 0);

						$dateendtouse = $event->date_end_in_calendar;
						if ($dateendtouse == $event->date_start_in_calendar) $dateendtouse++;

						//print dol_print_date($event->date_start_in_calendar,'dayhour').'-'.dol_print_date($a,'dayhour').'-'.dol_print_date($b,'dayhour').'<br>';

						if ($event->date_start_in_calendar < $b && $dateendtouse > $a)
						{
							$busy = $event->transparency;
							$cases1[$h][$event->id]['busy'] = $busy;
							$cases1[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour');
		                    if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar)
			        		{
				        		$tmpa = dol_getdate($event->date_start_in_calendar, true);
				        		$tmpb = dol_getdate($event->date_end_in_calendar, true);
				        		if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) $cases1[$h][$event->id]['string'] .= '-'.dol_print_date($event->date_end_in_calendar, 'hour');
				        		else $cases1[$h][$event->id]['string'] .= '-'.dol_print_date($event->date_end_in_calendar, 'dayhour');
			        		}
							$cases1[$h][$event->id]['string'] .= ' - '.$event->label;
							$cases1[$h][$event->id]['typecode'] = $event->type_code;
							$cases1[$h][$event->id]['color'] = $color;
							if ($event->fk_project > 0)
							{
								if (empty($cacheprojects[$event->fk_project]))
								{
									$tmpproj = new Project($db);
									$tmpproj->fetch($event->fk_project);
									$cacheprojects[$event->fk_project] = $tmpproj;
								}
								$cases1[$h][$event->id]['string'] .= ', '.$langs->trans("Project").': '.$cacheprojects[$event->fk_project]->ref.' - '.$cacheprojects[$event->fk_project]->title;
							}
							if ($event->socid > 0)
							{
								if (empty($cachethirdparties[$event->socid]))
								{
									$tmpthirdparty = new Societe($db);
									$tmpthirdparty->fetch($event->socid);
									$cachethirdparties[$event->socid] = $tmpthirdparty;
								}
								$cases1[$h][$event->id]['string'] .= ', '.$cachethirdparties[$event->socid]->name;
							}
							if ($event->contactid > 0)
							{
								if (empty($cachecontacts[$event->contactid]))
								{
									$tmpcontact = new Contact($db);
									$tmpcontact->fetch($event->contactid);
									$cachecontacts[$event->contactid] = $tmpcontact;
								}
								$cases1[$h][$event->id]['string'] .= ', '.$cachecontacts[$event->contactid]->getFullName($langs);
							}
						}
						if ($event->date_start_in_calendar < $c && $dateendtouse > $b)
						{
							$busy = $event->transparency;
							$cases2[$h][$event->id]['busy'] = $busy;
							$cases2[$h][$event->id]['string'] = dol_print_date($event->date_start_in_calendar, 'dayhour');
							if ($event->date_end_in_calendar && $event->date_end_in_calendar != $event->date_start_in_calendar)
			        		{
				        		$tmpa = dol_getdate($event->date_start_in_calendar, true);
				        		$tmpb = dol_getdate($event->date_end_in_calendar, true);
				        		if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year']) $cases2[$h][$event->id]['string'] .= '-'.dol_print_date($event->date_end_in_calendar, 'hour');
				        		else $cases2[$h][$event->id]['string'] .= '-'.dol_print_date($event->date_end_in_calendar, 'dayhour');
			        		}
							$cases2[$h][$event->id]['string'] .= ' - '.$event->label;
							$cases2[$h][$event->id]['typecode'] = $event->type_code;
							$cases2[$h][$event->id]['color'] = $color;
							if ($event->fk_project > 0)
							{
								if (empty($cacheprojects[$event->fk_project]))
								{
									$tmpproj = new Project($db);
									$tmpproj->fetch($event->fk_project);
									$cacheprojects[$event->fk_project] = $tmpproj;
								}
								$cases2[$h][$event->id]['string'] .= ', '.$langs->trans("Project").': '.$cacheprojects[$event->fk_project]->ref.' - '.$cacheprojects[$event->fk_project]->title;
							}
							if ($event->socid > 0)
							{
								if (empty($cachethirdparties[$event->socid]))
								{
									$tmpthirdparty = new Societe($db);
									$tmpthirdparty->fetch($event->socid);
									$cachethirdparties[$event->socid] = $tmpthirdparty;
								}
								$cases2[$h][$event->id]['string'] .= ', '.$cachethirdparties[$event->socid]->name;
							}
							if ($event->contactid > 0)
							{
								if (empty($cachecontacts[$event->contactid]))
								{
									$tmpcontact = new Contact($db);
									$tmpcontact->fetch($event->contactid);
									$cachecontacts[$event->contactid] = $tmpcontact;
								}
								$cases2[$h][$event->id]['string'] .= ', '.$cachecontacts[$event->contactid]->getFullName($langs);
							}
						}
					}
					else
					{
						$busy = $event->transparency;
						$cases1[$h][$event->id]['busy'] = $busy;
						$cases2[$h][$event->id]['busy'] = $busy;
						$cases1[$h][$event->id]['string'] = $event->label;
						$cases2[$h][$event->id]['string'] = $event->label;
						$cases1[$h][$event->id]['typecode'] = $event->type_code;
						$cases2[$h][$event->id]['typecode'] = $event->type_code;
						$cases1[$h][$event->id]['color'] = $color;
						$cases2[$h][$event->id]['color'] = $color;
					}
				}
				$i++;
			}

			break; // We found the date we were looking for. No need to search anymore.
		}
	}

	// Now output $casesX
	for ($h = $begin_h; $h < $end_h; $h++)
	{
		$color1 = ''; $color2 = '';
		$style1 = ''; $style2 = '';
		$string1 = '&nbsp;'; $string2 = '&nbsp;';
		$title1 = ''; $title2 = '';
		if (isset($cases1[$h]) && $cases1[$h] != '')
		{
			//$title1.=count($cases1[$h]).' '.(count($cases1[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases1[$h]) > 1) $title1 .= count($cases1[$h]).' '.(count($cases1[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			$string1 = '&nbsp;';
			if (empty($conf->global->AGENDA_NO_TRANSPARENT_ON_NOT_BUSY)) $style1 = 'peruser_notbusy';
			else $style1 = 'peruser_busy';
			foreach ($cases1[$h] as $id => $ev)
			{
				if ($ev['busy']) $style1 = 'peruser_busy';
			}
		}
		if (isset($cases2[$h]) && $cases2[$h] != '')
		{
			//$title2.=count($cases2[$h]).' '.(count($cases2[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			if (count($cases2[$h]) > 1) $title2 .= count($cases2[$h]).' '.(count($cases2[$h]) == 1 ? $langs->trans("Event") : $langs->trans("Events"));
			$string2 = '&nbsp;';
			if (empty($conf->global->AGENDA_NO_TRANSPARENT_ON_NOT_BUSY)) $style2 = 'peruser_notbusy';
			else $style2 = 'peruser_busy';
			foreach ($cases2[$h] as $id => $ev)
			{
				if ($ev['busy']) $style2 = 'peruser_busy';
			}
		}

		$ids1 = ''; $ids2 = '';
		if (count($cases1[$h]) && array_keys($cases1[$h])) $ids1 = join(',', array_keys($cases1[$h]));
		if (count($cases2[$h]) && array_keys($cases2[$h])) $ids2 = join(',', array_keys($cases2[$h]));

		if ($h == $begin_h) echo '<td class="'.$style.'_peruserleft cal_peruser'.($var ? ' cal_impair '.$style.'_impair' : '').'">';
		else echo '<td class="'.$style.' cal_peruser'.($var ? ' cal_impair '.$style.'_impair' : '').'">';
		if (count($cases1[$h]) == 1)	// only 1 event
		{
			$output = array_slice($cases1[$h], 0, 1);
			$title1 = $langs->trans("Ref").' '.$ids1.($title1 ? ' - '.$title1 : '');
			if ($output[0]['string']) $title1 .= ($title1 ? ' - ' : '').$output[0]['string'];
			if ($output[0]['color']) $color1 = $output[0]['color'];
		}
		elseif (count($cases1[$h]) > 1)
		{
			$title1 = $langs->trans("Ref").' '.$ids1.($title1 ? ' - '.$title1 : '');
			$color1 = '222222';
		}

		if (count($cases2[$h]) == 1)	// only 1 event
		{
			$output = array_slice($cases2[$h], 0, 1);
			$title2 = $langs->trans("Ref").' '.$ids2.($title2 ? ' - '.$title2 : '');
			if ($output[0]['string']) $title2 .= ($title2 ? ' - ' : '').$output[0]['string'];
			if ($output[0]['color']) $color2 = $output[0]['color'];
		}
		elseif (count($cases2[$h]) > 1)
		{
			$title2 = $langs->trans("Ref").' '.$ids2.($title2 ? ' - '.$title2 : '');
			$color2 = '222222';
		}
		print '<table class="nobordernopadding" width="100%">';
		print '<tr><td '.($color1 ? 'style="background: #'.$color1.';"' : '').'class="'.($style1 ? $style1.' ' : '').'onclickopenref'.($title1 ? ' cursorpointer' : '').'" ref="ref_'.$username->id.'_'.sprintf("%04d", $year).'_'.sprintf("%02d", $month).'_'.sprintf("%02d", $day).'_'.sprintf("%02d", $h).'_00_'.($ids1 ? $ids1 : 'none').'"'.($title1 ? ' title="'.$title1.'"' : '').'>';
		print $string1;
		print '</td><td '.($color2 ? 'style="background: #'.$color2.';"' : '').'class="'.($style2 ? $style2.' ' : '').'onclickopenref'.($title1 ? ' cursorpointer' : '').'" ref="ref_'.$username->id.'_'.sprintf("%04d", $year).'_'.sprintf("%02d", $month).'_'.sprintf("%02d", $day).'_'.sprintf("%02d", $h).'_30_'.($ids2 ? $ids2 : 'none').'"'.($title2 ? ' title="'.$title2.'"' : '').'>';
		print $string2;
		print '</td></tr>';
		print '</table>';
		print '</td>';
	}
}
