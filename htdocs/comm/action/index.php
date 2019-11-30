<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *  \file       htdocs/comm/action/index.php
 *  \ingroup    agenda
 *  \brief      Home page of calendar events
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

if (! isset($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW)) $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW=3;

if (empty($conf->global->AGENDA_EXT_NB)) $conf->global->AGENDA_EXT_NB=5;
$MAXAGENDA=$conf->global->AGENDA_EXT_NB;

$filter = GETPOST("search_filter", 'alpha', 3)?GETPOST("search_filter", 'alpha', 3):GETPOST("filter", 'alpha', 3);
$filtert = GETPOST("search_filtert", "int", 3)?GETPOST("search_filtert", "int", 3):GETPOST("filtert", "int", 3);
$usergroup = GETPOST("search_usergroup", "int", 3)?GETPOST("search_usergroup", "int", 3):GETPOST("usergroup", "int", 3);
$showbirthday = empty($conf->use_javascript_ajax)?GETPOST("showbirthday", "int"):1;

// If not choice done on calendar owner (like on left menu link "Agenda"), we filter on user.
if (empty($filtert) && empty($conf->global->AGENDA_ALL_CALENDARS))
{
	$filtert=$user->id;
}

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", "int");
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$limit = GETPOST('limit', 'int')?GETPOST('limit', 'int'):$conf->liste_limit;
$offset = $limit * $page;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="a.datec";

// Security check
$socid = GETPOST("search_socid", "int")?GETPOST("search_socid", "int"):GETPOST("socid", "int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if ($socid < 0) $socid='';

$canedit=1;
if (! $user->rights->agenda->myactions->read) accessforbidden();
if (! $user->rights->agenda->allactions->read) $canedit=0;
if (! $user->rights->agenda->allactions->read || $filter =='mine')  // If no permission to see all, we show only affected to me
{
    $filtert=$user->id;
}

$action=GETPOST('action', 'alpha');
$resourceid=GETPOST("search_resourceid", "int");
$year=GETPOST("year", "int")?GETPOST("year", "int"):date("Y");
$month=GETPOST("month", "int")?GETPOST("month", "int"):date("m");
$week=GETPOST("week", "int")?GETPOST("week", "int"):date("W");
$day=GETPOST("day", "int")?GETPOST("day", "int"):date("d");
$pid=GETPOST("search_projectid", "int", 3)?GETPOST("search_projectid", "int", 3):GETPOST("projectid", "int", 3);
$status=GETPOST("search_status", 'aZ09')?GETPOST("search_status", 'aZ09'):GETPOST("status", 'aZ09');		// status may be 0, 50, 100, 'todo'
$type=GETPOST("search_type", 'aZ09')?GETPOST("search_type", 'aZ09'):GETPOST("type", 'aZ09');
$maxprint=(isset($_GET["maxprint"])?GETPOST("maxprint"):$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
// Set actioncode (this code must be same for setting actioncode into peruser, listacton and index)
if (GETPOST('search_actioncode', 'array'))
{
    $actioncode=GETPOST('search_actioncode', 'array', 3);
    if (! count($actioncode)) $actioncode='0';
}
else
{
    $actioncode=GETPOST("search_actioncode", "alpha", 3)?GETPOST("search_actioncode", "alpha", 3):(GETPOST("search_actioncode")=='0'?'0':(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE));
}
if ($actioncode == '' && empty($actioncodearray)) $actioncode=(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE);

if ($status == '' && ! GETPOSTISSET('search_status')) $status=(empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS)?'':$conf->global->AGENDA_DEFAULT_FILTER_STATUS);

$defaultview = (empty($conf->global->AGENDA_DEFAULT_VIEW) ? 'show_month' : $conf->global->AGENDA_DEFAULT_VIEW);
$defaultview = (empty($user->conf->AGENDA_DEFAULT_VIEW) ? $defaultview : $user->conf->AGENDA_DEFAULT_VIEW);
if (empty($action) && ! GETPOSTISSET('action')) $action=$defaultview;
if ($action == 'default')	// When action is default, we want a calendar view and not the list
{
	$action = (($defaultview != 'show_list') ? $defaultview : 'show_month');
}
if (GETPOST('viewcal', 'none') && GETPOST('action', 'alpha') != 'show_day' && GETPOST('action', 'alpha') != 'show_week')  {
    $action='show_month'; $day='';
} // View by month
if (GETPOST('viewweek', 'none') || GETPOST('action', 'alpha') == 'show_week') {
    $action='show_week'; $week=($week?$week:date("W")); $day=($day?$day:date("d"));
} // View by week
if (GETPOST('viewday', 'none') || GETPOST('action', 'alpha') == 'show_day')  {
    $action='show_day'; $day=($day?$day:date("d"));
} // View by day

// Load translation files required by the page
$langs->loadLangs(array('agenda', 'other', 'commercial'));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('agenda'));


/*
 * Actions
 */

if (GETPOST("viewlist", 'alpha') || $action == 'show_list')
{
    $param='';
    if (is_array($_POST))
    {
        foreach($_POST as $key => $val)
        {
            if ($key=='token') continue;
            $param.='&'.$key.'='.urlencode($val);
        }
    }
    //print $param;
    header("Location: ".DOL_URL_ROOT.'/comm/action/list.php?'.$param);
    exit;
}

if (GETPOST("viewperuser", 'alpha') || $action == 'show_peruser')
{
    $param='';
    if (is_array($_POST))
    {
        foreach($_POST as $key => $val)
        {
            if ($key=='token') continue;
            $param.='&'.$key.'='.urlencode($val);
        }
    }
    //print $param;
    header("Location: ".DOL_URL_ROOT.'/comm/action/peruser.php?'.$param);
    exit;
}

if ($action =='delete_action')
{
    $event = new ActionComm($db);
    $event->fetch($actionid);
    $result=$event->delete();
}


/*
 * View
 */

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&oacute;dulo_Agenda';
llxHeader('', $langs->trans("Agenda"), $help_url);

$form=new Form($db);
$companystatic=new Societe($db);
$contactstatic=new Contact($db);

$now=dol_now();
$nowarray=dol_getdate($now);
$nowyear=$nowarray['year'];
$nowmonth=$nowarray['mon'];
$nowday=$nowarray['mday'];

$listofextcals=array();

// Define list of external calendars (global admin setup)
if (empty($conf->global->AGENDA_DISABLE_EXT))
{
    $i=0;
    while($i < $MAXAGENDA)
    {
        $i++;
        $source='AGENDA_EXT_SRC'.$i;
        $name='AGENDA_EXT_NAME'.$i;
        $offsettz='AGENDA_EXT_OFFSETTZ'.$i;
        $color='AGENDA_EXT_COLOR'.$i;
        $buggedfile='AGENDA_EXT_BUGGEDFILE'.$i;
        if (! empty($conf->global->$source) && ! empty($conf->global->$name))
        {
        	// Note: $conf->global->buggedfile can be empty or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
        	$listofextcals[]=array('src'=>$conf->global->$source,'name'=>$conf->global->$name,'offsettz'=>$conf->global->$offsettz,'color'=>$conf->global->$color,'buggedfile'=>(isset($conf->global->buggedfile)?$conf->global->buggedfile:0));
        }
    }
}
// Define list of external calendars (user setup)
if (empty($user->conf->AGENDA_DISABLE_EXT))
{
	$i=0;
	while($i < $MAXAGENDA)
	{
		$i++;
		$source='AGENDA_EXT_SRC_'.$user->id.'_'.$i;
		$name='AGENDA_EXT_NAME_'.$user->id.'_'.$i;
        $offsettz='AGENDA_EXT_OFFSETTZ_'.$user->id.'_'.$i;
		$color='AGENDA_EXT_COLOR_'.$user->id.'_'.$i;
		$enabled='AGENDA_EXT_ENABLED_'.$user->id.'_'.$i;
		$buggedfile='AGENDA_EXT_BUGGEDFILE_'.$user->id.'_'.$i;
		if (! empty($user->conf->$source) && ! empty($user->conf->$name))
		{
			// Note: $conf->global->buggedfile can be empty or 'uselocalandtznodaylight' or 'uselocalandtzdaylight'
			$listofextcals[]=array('src'=>$user->conf->$source,'name'=>$user->conf->$name,'offsettz'=>$user->conf->$offsettz,'color'=>$user->conf->$color,'buggedfile'=>(isset($user->conf->buggedfile)?$user->conf->buggedfile:0));
		}
	}
}

if (empty($action) || $action=='show_month')
{
    $prev = dol_get_prev_month($month, $year);
    $prev_year  = $prev['year'];
    $prev_month = $prev['month'];
    $next = dol_get_next_month($month, $year);
    $next_year  = $next['year'];
    $next_month = $next['month'];

    $max_day_in_prev_month = date("t", dol_mktime(0, 0, 0, $prev_month, 1, $prev_year));  // Nb of days in previous month
    $max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));                 // Nb of days in next month
    // tmpday is a negative or null cursor to know how many days before the 1st to show on month view (if tmpday=0, 1st is monday)
    $tmpday = -date("w", dol_mktime(12, 0, 0, $month, 1, $year, true))+2;		// date('w') is 0 fo sunday
    $tmpday+=((isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:1)-1);
    if ($tmpday >= 1) $tmpday -= 7;	// If tmpday is 0 we start with sunday, if -6, we start with monday of previous week.
    // Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
    $firstdaytoshow=dol_mktime(0, 0, 0, $prev_month, $max_day_in_prev_month+$tmpday, $prev_year);
    $next_day=7 - ($max_day_in_month+1-$tmpday) % 7;
    if ($next_day < 6) $next_day+=7;
    $lastdaytoshow=dol_mktime(0, 0, 0, $next_month, $next_day, $next_year);
}
if ($action=='show_week')
{
    $prev = dol_get_first_day_week($day, $month, $year);
    $prev_year  = $prev['prev_year'];
    $prev_month = $prev['prev_month'];
    $prev_day   = $prev['prev_day'];
    $first_day  = $prev['first_day'];
    $first_month= $prev['first_month'];
    $first_year = $prev['first_year'];

    $week = $prev['week'];

    $day = (int) $day;
    $next = dol_get_next_week($first_day, $week, $first_month, $first_year);
    $next_year  = $next['year'];
    $next_month = $next['month'];
    $next_day   = $next['day'];

    // Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
    $firstdaytoshow=dol_mktime(0, 0, 0, $first_month, $first_day, $first_year);
	$lastdaytoshow=dol_time_plus_duree($firstdaytoshow, 7, 'd');

    $max_day_in_month = date("t", dol_mktime(0, 0, 0, $month, 1, $year));

    $tmpday = $first_day;
}
if ($action == 'show_day')
{
    $prev = dol_get_prev_day($day, $month, $year);
    $prev_year  = $prev['year'];
    $prev_month = $prev['month'];
    $prev_day   = $prev['day'];
    $next = dol_get_next_day($day, $month, $year);
    $next_year  = $next['year'];
    $next_month = $next['month'];
    $next_day   = $next['day'];

    // Define firstdaytoshow and lastdaytoshow (warning: lastdaytoshow is last second to show + 1)
    $firstdaytoshow=dol_mktime(0, 0, 0, $prev_month, $prev_day, $prev_year);
    $lastdaytoshow=dol_mktime(0, 0, 0, $next_month, $next_day, $next_year);
}
//print 'xx'.$prev_year.'-'.$prev_month.'-'.$prev_day;
//print 'xx'.$next_year.'-'.$next_month.'-'.$next_day;
//print dol_print_date($firstdaytoshow,'day');
//print dol_print_date($lastdaytoshow,'day');

$title=$langs->trans("DoneAndToDoActions");
if ($status == 'done') $title=$langs->trans("DoneActions");
if ($status == 'todo') $title=$langs->trans("ToDoActions");

$param='';
if ($actioncode || isset($_GET['search_actioncode']) || isset($_POST['search_actioncode'])) {
	if(is_array($actioncode)) {
		foreach($actioncode as $str_action) $param.="&search_actioncode[]=".urlencode($str_action);
	} else $param.="&search_actioncode=".urlencode($actioncode);
}
if ($resourceid > 0)  $param.="&search_resourceid=".urlencode($resourceid);
if ($status || isset($_GET['status']) || isset($_POST['status'])) $param.="&search_status=".urlencode($status);
if ($filter)       $param.="&search_filter=".urlencode($filter);
if ($filtert)      $param.="&search_filtert=".urlencode($filtert);
if ($usergroup)    $param.="&search_usergroup=".urlencode($usergroup);
if ($socid)        $param.="&search_socid=".urlencode($socid);
if ($showbirthday) $param.="&search_showbirthday=1";
if ($pid)          $param.="&search_projectid=".urlencode($pid);
if ($type)         $param.="&search_type=".urlencode($type);
if ($action == 'show_day' || $action == 'show_week' || $action == 'show_month') $param.='&action='.urlencode($action);
$param.="&maxprint=".urlencode($maxprint);

// Show navigation bar
if (empty($action) || $action=='show_month')
{
    $nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month.$param."\"><i class=\"fa fa-chevron-left\"></i></a> &nbsp;\n";
    $nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $month, 1, $year), "%b %Y");
    $nav.=" </span>\n";
    $nav.=" &nbsp; <a href=\"?year=".$next_year."&amp;month=".$next_month.$param."\"><i class=\"fa fa-chevron-right\"></i></a>\n";
    $nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth.$param."\">".$langs->trans("Today")."</a>)";
    $picto='calendar';
}
if ($action=='show_week')
{
    $nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\"><i class=\"fa fa-chevron-left\" title=\"".dol_escape_htmltag($langs->trans("Previous"))."\"></i></a> &nbsp;\n";
    $nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $first_month, $first_day, $first_year), "%Y").", ".$langs->trans("Week")." ".$week;
    $nav.=" </span>\n";
    $nav.=" &nbsp; <a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\"><i class=\"fa fa-chevron-right\" title=\"".dol_escape_htmltag($langs->trans("Next"))."\"></i></a>\n";
    $nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
    $picto='calendarweek';
}
if ($action=='show_day')
{
    $nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\"><i class=\"fa fa-chevron-left\"></i></a> &nbsp;\n";
    $nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0, 0, 0, $month, $day, $year), "daytextshort");
    $nav.=" </span>\n";
    $nav.=" &nbsp; <a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\"><i class=\"fa fa-chevron-right\"></i></a>\n";
    $nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
    $picto='calendarday';
}

// Must be after the nav definition
$param.='&year='.$year.'&month='.$month.($day?'&day='.$day:'');
//print 'x'.$param;




$tabactive='';
if ($action == 'show_month') $tabactive='cardmonth';
if ($action == 'show_week') $tabactive='cardweek';
if ($action == 'show_day')  $tabactive='cardday';
if ($action == 'show_list') $tabactive='cardlist';

$paramnoaction=preg_replace('/action=[a-z_]+/', '', $param);

$head = calendars_prepare_head($paramnoaction);

print '<form method="POST" id="searchFormList" class="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

dol_fiche_head($head, $tabactive, $langs->trans('Agenda'), 0, 'action');
print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, 0, $filtert, 0, $pid, $socid, $action, $listofextcals, $actioncode, $usergroup, '', $resourceid);
dol_fiche_end();


// Define the legend/list of calendard to show
$s=''; $link='';

$showextcals=$listofextcals;

if (! empty($conf->use_javascript_ajax))	// If javascript on
{
	$s.="\n".'<!-- Div to calendars selectors -->'."\n";
	$s.='<script type="text/javascript">' . "\n";
	$s.='jQuery(document).ready(function () {' . "\n";
	$s.='jQuery("#check_birthday").click(function() { console.log("Toggle birthday"); jQuery(".family_birthday").toggle(); });' . "\n";
	$s.='jQuery(".family_birthday").toggle();' . "\n";
	if ($action=="show_week" || $action=="show_month" || empty($action))
	{
        // Code to enable drag and drop
		$s.='jQuery( "div.sortable" ).sortable({connectWith: ".sortable", placeholder: "ui-state-highlight", items: "div.movable", receive: function( event, ui ) {'."\n";
        // Code to submit form
		$s.='console.log("submit form to record new event");'."\n";
        //$s.='console.log(event.target);';
		$s.='var newval = jQuery(event.target).closest("div.dayevent").attr("id");'."\n";
		$s.='console.log("found parent div.dayevent with id = "+newval);'."\n";
		$s.='var frm=jQuery("#searchFormList");'."\n";
		$s.='var newurl = ui.item.find("a.cal_event").attr("href");'."\n";
		$s.='console.log(newurl);'."\n";
		$s.='frm.attr("action", newurl).children("#newdate").val(newval);frm.submit();}'."\n";
        $s.='});'."\n";
    }
    $s.='});' . "\n";
    $s.='</script>' . "\n";

	// Local calendar
	$s.='<div class="nowrap clear inline-block minheight20"><input type="checkbox" id="check_mytasks" name="check_mytasks" checked disabled> ' . $langs->trans("LocalAgenda").' &nbsp; </div>';

	// External calendars
	if (is_array($showextcals) && count($showextcals) > 0)
	{
		$s.='<script type="text/javascript">' . "\n";
		$s.='jQuery(document).ready(function () {
				jQuery("table input[name^=\"check_ext\"]").click(function() {
					var name = $(this).attr("name");
					jQuery(".family_ext" + name.replace("check_ext", "")).toggle();
				});
			});' . "\n";
		$s.='</script>' . "\n";

		foreach ($showextcals as $val)
		{
			$htmlname = md5($val['name']);
			$s.='<div class="nowrap inline-block"><input type="checkbox" id="check_ext' . $htmlname . '" name="check_ext' . $htmlname . '" checked> ' . $val['name'] . ' &nbsp; </div>';
		}
	}

	// Birthdays
	$s.='<div class="nowrap inline-block"><input type="checkbox" id="check_birthday" name="check_birthday"> '.$langs->trans("AgendaShowBirthdayEvents").' &nbsp; </div>';

	// Calendars from hooks
    $parameters=array(); $object=null;
	$reshook=$hookmanager->executeHooks('addCalendarChoice', $parameters, $object, $action);
    if (empty($reshook))
    {
		$s.= $hookmanager->resPrint;
    }
    elseif ($reshook > 1)
	{
    	$s = $hookmanager->resPrint;
    }
}
else 									// If javascript off
{
	$newparam=$param;   // newparam is for birthday links
    $newparam=preg_replace('/showbirthday=[0-1]/i', 'showbirthday='.(empty($showbirthday)?1:0), $newparam);
    if (! preg_match('/showbirthday=/i', $newparam)) $newparam.='&showbirthday=1';
    $link='<a href="'.dol_escape_htmltag($_SERVER['PHP_SELF']);
    $link.='?'.dol_escape_htmltag($newparam);
    $link.='">';
    if (empty($showbirthday)) $link.=$langs->trans("AgendaShowBirthdayEvents");
    else $link.=$langs->trans("AgendaHideBirthdayEvents");
    $link.='</a>';
}

print load_fiche_titre($s, $link.' &nbsp; &nbsp; '.$nav, '', 0, 0, 'tablelistofcalendars');


// Load events from database into $eventarray
$eventarray=array();

$sql = 'SELECT ';
if ($usergroup > 0) $sql.=" DISTINCT";
$sql.= ' a.id, a.label,';
$sql.= ' a.datep,';
$sql.= ' a.datep2,';
$sql.= ' a.percent,';
$sql.= ' a.fk_user_author,a.fk_user_action,';
$sql.= ' a.transparency, a.priority, a.fulldayevent, a.location,';
$sql.= ' a.fk_soc, a.fk_contact,';
$sql.= ' a.fk_element, a.elementtype,';
$sql.= ' ca.code as type_code, ca.libelle as type_label, ca.color as type_color';
$sql.= ' FROM '.MAIN_DB_PREFIX.'c_actioncomm as ca, '.MAIN_DB_PREFIX."actioncomm as a";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
// We must filter on resource table
if ($resourceid > 0) $sql.=", ".MAIN_DB_PREFIX."element_resources as r";
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0) $sql.=", ".MAIN_DB_PREFIX."actioncomm_resources as ar";
if ($usergroup > 0) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."usergroup_user as ugu ON ugu.fk_user = ar.fk_element";
$sql.= ' WHERE a.fk_action = ca.id';
$sql.= ' AND a.entity IN ('.getEntity('agenda').')';
// Condition on actioncode
if (! empty($actioncode))
{
    if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
    {
        if ($actioncode == 'AC_NON_AUTO') $sql.= " AND ca.type != 'systemauto'";
        elseif ($actioncode == 'AC_ALL_AUTO') $sql.= " AND ca.type = 'systemauto'";
        else
        {
            if ($actioncode == 'AC_OTH') $sql.= " AND ca.type != 'systemauto'";
            if ($actioncode == 'AC_OTH_AUTO') $sql.= " AND ca.type = 'systemauto'";
        }
    }
    else
    {
        if ($actioncode == 'AC_NON_AUTO') $sql.= " AND ca.type != 'systemauto'";
        elseif ($actioncode == 'AC_ALL_AUTO') $sql.= " AND ca.type = 'systemauto'";
        else
        {
		if (is_array($actioncode))
		{
	        	$sql.=" AND ca.code IN ('".implode("','", $actioncode)."')";
		}
		else
		{
	        	$sql.=" AND ca.code IN ('".implode("','", explode(',', $actioncode))."')";
		}
        }
    }
}
if ($resourceid > 0) $sql.=" AND r.element_type = 'action' AND r.element_id = a.id AND r.resource_id = ".$db->escape($resourceid);
if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
if ($socid > 0) $sql.= ' AND a.fk_soc = '.$socid;
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0) $sql.= " AND ar.fk_actioncomm = a.id AND ar.element_type='user'";
if ($action == 'show_day')
{
    $sql.= " AND (";
    $sql.= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."'";
    $sql.= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."')";
    $sql.= " OR ";
    $sql.= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."'";
    $sql.= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."')";
    $sql.= " OR ";
    $sql.= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."'";
    $sql.= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."')";
    $sql.= ')';
}
else
{
    // To limit array
    $sql.= " AND (";
    $sql.= " (a.datep BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year)-(60*60*24*7))."'";   // Start 7 days before
    $sql.= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year)+(60*60*24*10))."')";            // End 7 days after + 3 to go from 28 to 31
    $sql.= " OR ";
    $sql.= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year)-(60*60*24*7))."'";
    $sql.= " AND '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year)+(60*60*24*10))."')";
    $sql.= " OR ";
    $sql.= " (a.datep < '".$db->idate(dol_mktime(0, 0, 0, $month, 1, $year)-(60*60*24*7))."'";
    $sql.= " AND a.datep2 > '".$db->idate(dol_mktime(23, 59, 59, $month, 28, $year)+(60*60*24*10))."')";
    $sql.= ')';
}
if ($type) $sql.= " AND ca.id = ".$type;
if ($status == '0') { $sql.= " AND a.percent = 0"; }
if ($status == '-1') { $sql.= " AND a.percent = -1"; }	// Not applicable
if ($status == '50') { $sql.= " AND (a.percent > 0 AND a.percent < 100)"; }	// Running already started
if ($status == 'done' || $status == '100') { $sql.= " AND (a.percent = 100)"; }
if ($status == 'todo') { $sql.= " AND (a.percent >= 0 AND a.percent < 100)"; }
// We must filter on assignement table
if ($filtert > 0 || $usergroup > 0)
{
    $sql.= " AND (";
    if ($filtert > 0) $sql.= "ar.fk_element = ".$filtert;
    if ($usergroup > 0) $sql.= ($filtert>0?" OR ":"")." ugu.fk_usergroup = ".$usergroup;
    $sql.= ")";
}
// Sort on date
$sql.= ' ORDER BY datep';
//print $sql;


dol_syslog("comm/action/index.php", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);

    $MAXONSAMEPAGE=10000;   // Useless to have more. Protection to avoid memory overload when high number of event (for example after a mass import)
    $i=0;
    while ($i < $num && $i < $MAXONSAMEPAGE)
    {
        $obj = $db->fetch_object($resql);

        // Discard auto action if option is on
        if (! empty($conf->global->AGENDA_ALWAYS_HIDE_AUTO) && $obj->type_code == 'AC_OTH_AUTO')
        {
        	$i++;
        	continue;
        }

        // Create a new object action
        $event=new ActionComm($db);
        $event->id=$obj->id;

        $event->datep=$db->jdate($obj->datep);      // datep and datef are GMT date. Example: 1970-01-01 01:00:00, jdate will return 0 if TZ of PHP server is Europe/Berlin
        $event->datef=$db->jdate($obj->datep2);
		//var_dump($obj->datep);
        //var_dump($event->datep);

        $event->type_code=$obj->type_code;
        $event->type_label=$obj->type_label;
        $event->type_color=$obj->type_color;

        $event->libelle=$obj->label;
        $event->label=$obj->label;
        $event->percentage=$obj->percent;
        $event->authorid=$obj->fk_user_author;		// user id of creator
        $event->userownerid=$obj->fk_user_action;	// user id of owner
        $event->fetch_userassigned();				// This load $event->userassigned
        $event->priority=$obj->priority;
        $event->fulldayevent=$obj->fulldayevent;
        $event->location=$obj->location;
        $event->transparency=$obj->transparency;
        $event->fk_element=$obj->fk_element;
        $event->elementtype=$obj->elementtype;

        $event->societe->id=$obj->fk_soc;
        $event->thirdparty_id=$obj->fk_soc;
        $event->contact->id=$obj->fk_contact;
        $event->contact_id=$obj->fk_contact;

        // Defined date_start_in_calendar and date_end_in_calendar property
        // They are date start and end of action but modified to not be outside calendar view.
        $event->date_start_in_calendar=$event->datep;
        if ($event->datef != '' && $event->datef >= $event->datep) $event->date_end_in_calendar=$event->datef;
        else $event->date_end_in_calendar=$event->datep;
        // Define ponctual property
        if ($event->date_start_in_calendar == $event->date_end_in_calendar)
        {
            $event->ponctuel=1;
        }

        // Check values
        if ($event->date_end_in_calendar < $firstdaytoshow ||
        $event->date_start_in_calendar >= $lastdaytoshow)
        {
            // This record is out of visible range
        }
        else
        {
            if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
            if ($event->date_end_in_calendar >= $lastdaytoshow) $event->date_end_in_calendar=($lastdaytoshow-1);

            // Add an entry in actionarray for each day
            $daycursor=$event->date_start_in_calendar;
            $annee = date('Y', $daycursor);
            $mois = date('m', $daycursor);
            $jour = date('d', $daycursor);

            // Loop on each day covered by action to prepare an index to show on calendar
            $loop=true; $j=0;
            $daykey=dol_mktime(0, 0, 0, $mois, $jour, $annee);
            do
            {
                //if ($event->id==408) print 'daykey='.$daykey.' '.$event->datep.' '.$event->datef.'<br>';

                $eventarray[$daykey][]=$event;
                $j++;

                $daykey+=60*60*24;
                if ($daykey > $event->date_end_in_calendar) $loop=false;
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

// Complete $eventarray with birthdates
if ($showbirthday)
{
    // Add events in array
    $sql = 'SELECT sp.rowid, sp.lastname, sp.firstname, sp.birthday';
    $sql.= ' FROM '.MAIN_DB_PREFIX.'socpeople as sp';
    $sql.= ' WHERE (priv=0 OR (priv=1 AND fk_user_creat='.$user->id.'))';
    $sql.= " AND sp.entity IN (".getEntity('socpeople').")";
    if ($action == 'show_day')
    {
        $sql.= ' AND MONTH(birthday) = '.$month;
        $sql.= ' AND DAY(birthday) = '.$day;
    }
    else
    {
        $sql.= ' AND MONTH(birthday) = '.$month;
    }
    $sql.= ' ORDER BY birthday';

    dol_syslog("comm/action/index.php", LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i=0;
        while ($i < $num)
        {
            $obj = $db->fetch_object($resql);
            $event=new ActionComm($db);
            $event->id=$obj->rowid; // We put contact id in action id for birthdays events
            $datebirth=dol_stringtotime($obj->birthday, 1);
            //print 'ee'.$obj->birthday.'-'.$datebirth;
            $datearray=dol_getdate($datebirth, true);
            $event->datep=dol_mktime(0, 0, 0, $datearray['mon'], $datearray['mday'], $year, true);    // For full day events, date are also GMT but they wont but converted during output
            $event->datef=$event->datep;
            $event->type_code='BIRTHDAY';
            $event->libelle=$langs->trans("Birthday").' '.dolGetFirstLastname($obj->firstname, $obj->lastname);
            $event->percentage=100;
            $event->fulldayevent=1;

            $event->date_start_in_calendar=$event->datep;
            $event->date_end_in_calendar=$event->datef;
            $event->ponctuel=0;

            // Add an entry in actionarray for each day
            $daycursor=$event->date_start_in_calendar;
            $annee = date('Y', $daycursor);
            $mois = date('m', $daycursor);
            $jour = date('d', $daycursor);

            $loop=true;
            $daykey=dol_mktime(0, 0, 0, $mois, $jour, $annee);
            do
            {
                $eventarray[$daykey][]=$event;
                $daykey+=60*60*24;
                if ($daykey > $event->date_end_in_calendar) $loop=false;
            }
            while ($loop);
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
    }
}

// Complete $eventarray with external import Ical
if (count($listofextcals))
{
    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/ical.class.php';
    foreach($listofextcals as $extcal)
    {
        $url=$extcal['src'];    // Example: https://www.google.com/calendar/ical/eldy10%40gmail.com/private-cde92aa7d7e0ef6110010a821a2aaeb/basic.ics
        $namecal = $extcal['name'];
        $offsettz = $extcal['offsettz'];
        $colorcal = $extcal['color'];
        $buggedfile = $extcal['buggedfile'];
        //print "url=".$url." namecal=".$namecal." colorcal=".$colorcal." buggedfile=".$buggedfile;
        $ical=new ICal();
        $ical->parse($url);

        // After this $ical->cal['VEVENT'] contains array of events, $ical->cal['DAYLIGHT'] contains daylight info, $ical->cal['STANDARD'] contains non daylight info, ...
        //var_dump($ical->cal); exit;
        $icalevents=array();
        if (is_array($ical->get_event_list())) $icalevents=array_merge($icalevents, $ical->get_event_list());        // Add $ical->cal['VEVENT']
        if (is_array($ical->get_freebusy_list())) $icalevents=array_merge($icalevents, $ical->get_freebusy_list());  // Add $ical->cal['VFREEBUSY']

        if (count($icalevents)>0)
        {
            // Duplicate all repeatable events into new entries
            $moreicalevents=array();
            foreach($icalevents as $icalevent)
            {
                if (isset($icalevent['RRULE']) && is_array($icalevent['RRULE'])) //repeatable event
                {
                    //if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
                    //if ($event->date_end_in_calendar > $lastdaytoshow) $event->date_end_in_calendar=($lastdaytoshow-1);
                    if ($icalevent['DTSTART;VALUE=DATE']) //fullday event
                    {
                        $datecurstart=dol_stringtotime($icalevent['DTSTART;VALUE=DATE'], 1);
                        $datecurend=dol_stringtotime($icalevent['DTEND;VALUE=DATE'], 1)-1;  // We remove one second to get last second of day
                    }
                    elseif (is_array($icalevent['DTSTART']) && ! empty($icalevent['DTSTART']['unixtime']))
                    {
                        $datecurstart=$icalevent['DTSTART']['unixtime'];
                        $datecurend=$icalevent['DTEND']['unixtime'];
                        if (! empty($ical->cal['DAYLIGHT']['DTSTART']) && $datecurstart)
                        {
                            //var_dump($ical->cal);
                            $tmpcurstart=$datecurstart;
                            $tmpcurend=$datecurend;
                            $tmpdaylightstart=dol_mktime(0, 0, 0, 1, 1, 1970, 1) + (int) $ical->cal['DAYLIGHT']['DTSTART'];
                            $tmpdaylightend=dol_mktime(0, 0, 0, 1, 1, 1970, 1) + (int) $ical->cal['STANDARD']['DTSTART'];
                            //var_dump($tmpcurstart);var_dump($tmpcurend); var_dump($ical->cal['DAYLIGHT']['DTSTART']);var_dump($ical->cal['STANDARD']['DTSTART']);
                            // Edit datecurstart and datecurend
                            if ($tmpcurstart >= $tmpdaylightstart && $tmpcurstart < $tmpdaylightend) $datecurstart-=((int) $ical->cal['DAYLIGHT']['TZOFFSETTO'])*36;
                            else $datecurstart-=((int) $ical->cal['STANDARD']['TZOFFSETTO'])*36;
                            if ($tmpcurend >= $tmpdaylightstart && $tmpcurstart < $tmpdaylightend) $datecurend-=((int) $ical->cal['DAYLIGHT']['TZOFFSETTO'])*36;
                            else $datecurend-=((int) $ical->cal['STANDARD']['TZOFFSETTO'])*36;
                        }
                        // datecurstart and datecurend are now GMT date
                        //var_dump($datecurstart); var_dump($datecurend); exit;
                    }
                    else
                    {
                        // Not a recongized record
                        dol_syslog("Found a not recognized repeatable record with unknown date start", LOG_ERR);
                        continue;
                    }
                    //print 'xx'.$datecurstart;exit;

                    $interval=(empty($icalevent['RRULE']['INTERVAL'])?1:$icalevent['RRULE']['INTERVAL']);
                    $until=empty($icalevent['RRULE']['UNTIL'])?0:dol_stringtotime($icalevent['RRULE']['UNTIL'], 1);
                    $maxrepeat=empty($icalevent['RRULE']['COUNT'])?0:$icalevent['RRULE']['COUNT'];
                    if ($until && ($until+($datecurend-$datecurstart)) < $firstdaytoshow) continue;  // We discard repeatable event that end before start date to show
                    if ($datecurstart >= $lastdaytoshow) continue;                                   // We discard repeatable event that start after end date to show

                    $numofevent=0;
                    while (($datecurstart < $lastdaytoshow) && (empty($maxrepeat) || ($numofevent < $maxrepeat)))
                    {
                        if ($datecurend >= $firstdaytoshow)    // We add event
                        {
                            $newevent=$icalevent;
                            unset($newevent['RRULE']);
                            if ($icalevent['DTSTART;VALUE=DATE'])
                            {
                                $newevent['DTSTART;VALUE=DATE']=dol_print_date($datecurstart, '%Y%m%d');
                                $newevent['DTEND;VALUE=DATE']=dol_print_date($datecurend+1, '%Y%m%d');
                            }
                            else
                            {
                                $newevent['DTSTART']=$datecurstart;
                                $newevent['DTEND']=$datecurend;
                            }
                            $moreicalevents[]=$newevent;
                        }
                        // Jump on next occurence
                        $numofevent++;
                        $savdatecurstart=$datecurstart;
                        if ($icalevent['RRULE']['FREQ']=='DAILY')
                        {
                            $datecurstart=dol_time_plus_duree($datecurstart, $interval, 'd');
                            $datecurend=dol_time_plus_duree($datecurend, $interval, 'd');
                        }
                        if ($icalevent['RRULE']['FREQ']=='WEEKLY')
                        {
                            $datecurstart=dol_time_plus_duree($datecurstart, $interval, 'w');
                            $datecurend=dol_time_plus_duree($datecurend, $interval, 'w');
                        }
                        elseif ($icalevent['RRULE']['FREQ']=='MONTHLY')
                        {
                            $datecurstart=dol_time_plus_duree($datecurstart, $interval, 'm');
                            $datecurend=dol_time_plus_duree($datecurend, $interval, 'm');
                        }
                        elseif ($icalevent['RRULE']['FREQ']=='YEARLY')
                        {
                            $datecurstart=dol_time_plus_duree($datecurstart, $interval, 'y');
                            $datecurend=dol_time_plus_duree($datecurend, $interval, 'y');
                        }
                        // Test to avoid infinite loop ($datecurstart must increase)
                        if ($savdatecurstart >= $datecurstart)
                        {
                            dol_syslog("Found a rule freq ".$icalevent['RRULE']['FREQ']." not managed by dolibarr code. Assume 1 week frequency.", LOG_ERR);
                            $datecurstart+=3600*24*7;
                            $datecurend+=3600*24*7;
                        }
                    }
                }
            }
            $icalevents=array_merge($icalevents, $moreicalevents);

            // Loop on each entry into cal file to know if entry is qualified and add an ActionComm into $eventarray
            foreach($icalevents as $icalevent)
            {
            	//var_dump($icalevent);

                //print $icalevent['SUMMARY'].'->'.var_dump($icalevent).'<br>';exit;
                if (! empty($icalevent['RRULE'])) continue;    // We found a repeatable event. It was already split into unitary events, so we discard general rule.

                // Create a new object action
                $event=new ActionComm($db);
                $addevent = false;
                if (isset($icalevent['DTSTART;VALUE=DATE'])) // fullday event
                {
                    // For full day events, date are also GMT but they wont but converted using tz during output
                    $datestart=dol_stringtotime($icalevent['DTSTART;VALUE=DATE'], 1);
                    $dateend=dol_stringtotime($icalevent['DTEND;VALUE=DATE'], 1)-1;  // We remove one second to get last second of day
                    //print 'x'.$datestart.'-'.$dateend;exit;
                    //print dol_print_date($dateend,'dayhour','gmt');
                    $event->fulldayevent=1;
                    $addevent=true;
                }
                elseif (!is_array($icalevent['DTSTART'])) // not fullday event (DTSTART is not array. It is a value like '19700101T000000Z' for 00:00 in greenwitch)
                {
                    $datestart=$icalevent['DTSTART'];
                    $dateend=$icalevent['DTEND'];

                    $datestart+=+($offsettz * 3600);
                    $dateend+=+($offsettz * 3600);

                    $addevent=true;
                    //var_dump($offsettz);
                    //var_dump(dol_print_date($datestart, 'dayhour', 'gmt'));
                }
                elseif (isset($icalevent['DTSTART']['unixtime']))	// File contains a local timezone + a TZ (for example when using bluemind)
                {
                    $datestart=$icalevent['DTSTART']['unixtime'];
                    $dateend=$icalevent['DTEND']['unixtime'];

                    $datestart+=+($offsettz * 3600);
                    $dateend+=+($offsettz * 3600);

                    // $buggedfile is set to uselocalandtznodaylight if conf->global->AGENDA_EXT_BUGGEDFILEx = 'uselocalandtznodaylight'
                    if ($buggedfile === 'uselocalandtznodaylight')	// unixtime is a local date that does not take daylight into account, TZID is +1 for example for 'Europe/Paris' in summer instead of 2
                    {
                    	// TODO
                    }
                    // $buggedfile is set to uselocalandtzdaylight if conf->global->AGENDA_EXT_BUGGEDFILEx = 'uselocalandtzdaylight' (for example with bluemind)
                    if ($buggedfile === 'uselocalandtzdaylight')	// unixtime is a local date that does take daylight into account, TZID is +2 for example for 'Europe/Paris' in summer
                    {
                    	$localtzs = new DateTimeZone(preg_replace('/"/', '', $icalevent['DTSTART']['TZID']));
                    	$localtze = new DateTimeZone(preg_replace('/"/', '', $icalevent['DTEND']['TZID']));
                    	$localdts = new DateTime(dol_print_date($datestart, 'dayrfc', 'gmt'), $localtzs);
                    	$localdte = new DateTime(dol_print_date($dateend, 'dayrfc', 'gmt'), $localtze);
						$tmps=-1*$localtzs->getOffset($localdts);
						$tmpe=-1*$localtze->getOffset($localdte);
						$datestart+=$tmps;
						$dateend+=$tmpe;
						//var_dump($datestart);
                    }
                    $addevent=true;
                }

                if ($addevent)
                {
                    $event->id=$icalevent['UID'];
                    $event->icalname=$namecal;
                    $event->icalcolor=$colorcal;
                    $usertime=0;    // We dont modify date because we want to have date into memory datep and datef stored as GMT date. Compensation will be done during output.
                    $event->datep=$datestart+$usertime;
                    $event->datef=$dateend+$usertime;
                    $event->type_code="ICALEVENT";

                    if($icalevent['SUMMARY']) $event->libelle=$icalevent['SUMMARY'];
                    elseif($icalevent['DESCRIPTION']) $event->libelle=dol_nl2br($icalevent['DESCRIPTION'], 1);
                    else $event->libelle = $langs->trans("ExtSiteNoLabel");

                    $event->date_start_in_calendar=$event->datep;

                    if ($event->datef != '' && $event->datef >= $event->datep) $event->date_end_in_calendar=$event->datef;
                    else $event->date_end_in_calendar=$event->datep;

                    // Define ponctual property
                    if ($event->date_start_in_calendar == $event->date_end_in_calendar)
                    {
                        $event->ponctuel=1;
                        //print 'x'.$datestart.'-'.$dateend;exit;
                    }

                    // Add event into $eventarray if date range are ok.
                    if ($event->date_end_in_calendar < $firstdaytoshow || $event->date_start_in_calendar >= $lastdaytoshow)
                    {
                        //print 'x'.$datestart.'-'.$dateend;exit;
                        //print 'x'.$datestart.'-'.$dateend;exit;
                        //print 'x'.$datestart.'-'.$dateend;exit;
                        // This record is out of visible range
                    }
                    else
                    {
                        if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
                        if ($event->date_end_in_calendar >= $lastdaytoshow) $event->date_end_in_calendar=($lastdaytoshow - 1);

                        // Add an entry in actionarray for each day
                        $daycursor=$event->date_start_in_calendar;
                        $annee = date('Y', $daycursor);
                        $mois = date('m', $daycursor);
                        $jour = date('d', $daycursor);

                        // Loop on each day covered by action to prepare an index to show on calendar
                        $loop=true; $j=0;
                        // daykey must be date that represent day box in calendar so must be a user time
                        $daykey=dol_mktime(0, 0, 0, $mois, $jour, $annee);
                        $daykeygmt=dol_mktime(0, 0, 0, $mois, $jour, $annee, true, 0);
                        do
                        {
                            //if ($event->fulldayevent) print dol_print_date($daykeygmt,'dayhour','gmt').'-'.dol_print_date($daykey,'dayhour','gmt').'-'.dol_print_date($event->date_end_in_calendar,'dayhour','gmt').' ';
                            $eventarray[$daykey][]=$event;
                            $daykey+=60*60*24;  $daykeygmt+=60*60*24;   // Add one day
                            if (($event->fulldayevent ? $daykeygmt : $daykey) > $event->date_end_in_calendar) $loop=false;
                        }
                        while ($loop);
                    }
                }
            }
        }
    }
}



// Complete $eventarray with events coming from external module
$parameters=array(); $object=null;
$reshook=$hookmanager->executeHooks('getCalendarEvents', $parameters, $object, $action);
if (! empty($hookmanager->resArray['eventarray'])) {
    foreach ($hookmanager->resArray['eventarray'] as $keyDate => $events) {
        if (!isset($eventarray[$keyDate])) {
            $eventarray[$keyDate]=array();
        }
        $eventarray[$keyDate]=array_merge($eventarray[$keyDate], $events);
    }
}

// Sort events
foreach($eventarray as $keyDate => &$dateeventarray)
{
	usort($dateeventarray, 'sort_events_by_date');
}


$maxnbofchar=0;
$cachethirdparties=array();
$cachecontacts=array();
$cacheusers=array();

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/theme_vars.inc.php";
if (is_readable($color_file))
{
    include_once $color_file;
}
if (! is_array($theme_datacolor)) $theme_datacolor=array(array(120,130,150), array(200,160,180), array(190,190,220));


if (empty($action) || $action == 'show_month')      // View by month
{
    $newparam=$param;   // newparam is for birthday links
    $newparam=preg_replace('/showbirthday=/i', 'showbirthday_=', $newparam);	// To avoid replacement when replace day= is done
    $newparam=preg_replace('/action=show_month&?/i', '', $newparam);
    $newparam=preg_replace('/action=show_week&?/i', '', $newparam);
    $newparam=preg_replace('/day=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/month=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/year=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/viewcal=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/showbirthday_=/i', 'showbirthday=', $newparam);	// Restore correct parameter
    $newparam.='&viewcal=1';


    print '<div class="div-table-responsive-no-min">';
    print '<table width="100%" class="noborder nocellnopadd cal_pannel cal_month">';
    print ' <tr class="liste_titre">';
    $i=0;
    while ($i < 7)
    {
        print '  <td align="center">';
        $numdayinweek=(($i+(isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:1)) % 7);
        if (! empty($conf->dol_optimize_smallscreen))
        {
            $labelshort=array(0=>'SundayMin',1=>'MondayMin',2=>'TuesdayMin',3=>'WednesdayMin',4=>'ThursdayMin',5=>'FridayMin',6=>'SaturdayMin');
            print $langs->trans($labelshort[$numdayinweek]);
        }
        else print $langs->trans("Day".$numdayinweek);
        print '  </td>'."\n";
        $i++;
    }
    echo ' </tr>'."\n";

    $todayarray=dol_getdate($now, 'fast');
    $todaytms=dol_mktime(0, 0, 0, $todayarray['mon'], $todayarray['mday'], $todayarray['year']);

    // In loops, tmpday contains day nb in current month (can be zero or negative for days of previous month)
    //var_dump($eventarray);
    for ($iter_week = 0; $iter_week < 6 ; $iter_week++)
    {
        echo " <tr>\n";
        for ($iter_day = 0; $iter_day < 7; $iter_day++)
        {
        	/* Show days before the beginning of the current month (previous month)  */
            if ($tmpday <= 0)
            {
                $style='cal_other_month cal_past';
        		if ($iter_day == 6) $style.=' cal_other_month_right';
                echo '  <td class="'.$style.' nowrap" width="14%" valign="top">';
                show_day_events($db, $max_day_in_prev_month + $tmpday, $prev_month, $prev_year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam);
                echo "  </td>\n";
            }
            /* Show days of the current month */
            elseif ($tmpday <= $max_day_in_month)
            {
                $curtime = dol_mktime(0, 0, 0, $month, $tmpday, $year);
                $style='cal_current_month';
                if ($iter_day == 6) $style.=' cal_current_month_right';
                $today=0;
                if ($todayarray['mday']==$tmpday && $todayarray['mon']==$month && $todayarray['year']==$year) $today=1;
                if ($today) $style='cal_today';
                if ($curtime < $todaytms) $style.=' cal_past';
				//var_dump($todayarray['mday']."==".$tmpday." && ".$todayarray['mon']."==".$month." && ".$todayarray['year']."==".$year.' -> '.$style);
                echo '  <td class="'.$style.' nowrap" width="14%" valign="top">';
                show_day_events($db, $tmpday, $month, $year, $month, $style, $eventarray, $maxprint, $maxnbofchar, $newparam);
                echo "  </td>\n";
            }
            /* Show days after the current month (next month) */
            else
			{
                $style='cal_other_month';
                if ($iter_day == 6) $style.=' cal_other_month_right';
                echo '  <td class="'.$style.' nowrap" width="14%" valign="top">';
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
	print '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER['QUERY_STRING']).'">';
	print '<input type="hidden" name="newdate" id="newdate">' ;
}
elseif ($action == 'show_week') // View by week
{
    $newparam=$param;   // newparam is for birthday links
    $newparam=preg_replace('/showbirthday=/i', 'showbirthday_=', $newparam);	// To avoid replacement when replace day= is done
    $newparam=preg_replace('/action=show_month&?/i', '', $newparam);
    $newparam=preg_replace('/action=show_week&?/i', '', $newparam);
    $newparam=preg_replace('/day=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/month=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/year=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/viewweek=[0-9]+&?/i', '', $newparam);
    $newparam=preg_replace('/showbirthday_=/i', 'showbirthday=', $newparam);	// Restore correct parameter
    $newparam.='&viewweek=1';

    print '<div class="div-table-responsive-no-min">';
    print '<table width="100%" class="noborder nocellnopadd cal_pannel cal_month">';
    print ' <tr class="liste_titre">';
    $i=0;
    while ($i < 7)
    {
        echo '  <td align="center">'.$langs->trans("Day".(($i+(isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:1)) % 7))."</td>\n";
        $i++;
    }
    echo " </tr>\n";

    echo " <tr>\n";

    for ($iter_day = 0; $iter_day < 7; $iter_day++)
    {
        // Show days of the current week
		$curtime = dol_time_plus_duree($firstdaytoshow, $iter_day, 'd');
		$tmparray = dol_getdate($curtime, true);
		$tmpday = $tmparray['mday'];
		$tmpmonth = $tmparray['mon'];
		$tmpyear = $tmparray['year'];

        $style='cal_current_month';
        if ($iter_day == 6) $style.=' cal_other_month_right';
        $today=0;
        $todayarray=dol_getdate($now, 'fast');
        if ($todayarray['mday']==$tmpday && $todayarray['mon']==$tmpmonth && $todayarray['year']==$tmpyear) $today=1;
        if ($today) $style='cal_today';

        echo '  <td class="'.$style.'" width="14%" valign="top">';
        show_day_events($db, $tmpday, $tmpmonth, $tmpyear, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300);
        echo "  </td>\n";
    }
    echo " </tr>\n";

    print "</table>\n";
	print '</div>';

    echo '<input type="hidden" name="actionmove" value="mupdate">';
    echo '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($_SERVER['PHP_SELF']).'?'.dol_escape_htmltag($_SERVER['QUERY_STRING']).'">';
    echo '<input type="hidden" name="newdate" id="newdate">' ;
}
else    // View by day
{
    $newparam=$param;   // newparam is for birthday links
    $newparam=preg_replace('/action=show_month&?/i', '', $newparam);
    $newparam=preg_replace('/action=show_week&?/i', '', $newparam);
    $newparam=preg_replace('/viewday=[0-9]+&?/i', '', $newparam);
    $newparam.='&viewday=1';
    // Code to show just one day
    $style='cal_current_month cal_current_month_oneday';
    $today=0;
    $todayarray=dol_getdate($now, 'fast');
    if ($todayarray['mday']==$day && $todayarray['mon']==$month && $todayarray['year']==$year) $today=1;
    //if ($today) $style='cal_today';

    $timestamp=dol_mktime(12, 0, 0, $month, $day, $year);
    $arraytimestamp=dol_getdate($timestamp);

    //echo '<table class="tagtable centpercent noborder nocellnopadd cal_pannel cal_month">';
    echo '<table class="tagtable centpercent noborder nocellnopadd cal_pannel cal_month noborderbottom" style="margin-bottom: 5px !important;">';

    echo ' <tr class="tagtr liste_titre">';
    echo '  <td class="tagtd width100"></td>';
    echo '  <td class="tagtd center">'.$langs->trans("Day".$arraytimestamp['wday'])."</td>\n";
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

    /* WIP View per hour */
    $useviewhour = 0;
    if ($useviewhour)
    {
    	print '<div class="div-table-responsive-no-min borderbottom">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table

    	$maxheightwin=(isset($_SESSION["dol_screenheight"]) && $_SESSION["dol_screenheight"] > 500)?($_SESSION["dol_screenheight"]-200):660;	// Also into index.php file

	    echo '<div style="max-height: '.$maxheightwin.'px;">';
		echo '<div class="tagtable centpercent calendarviewcontainer">';

	    $maxnbofchar=80;

	    $tmp = explode('-', $conf->global->MAIN_DEFAULT_WORKING_HOURS);
	    $minhour = round($tmp[0], 0);
	    $maxhour = round($tmp[1], 0);
	    if ($minhour > 23) $minhour = 23;
	    if ($maxhour < 1)  $maxhour = 1;
	    if ($maxhour <= $minhour) { $maxhour = $minhour + 1; }

	    $i = 0;
	    $j = 0;
		while ($i < 24)
		{
		    echo ' <div class="tagtr calendarviewcontainertr">'."\n";
		    echo '  <div class="tagtd width100 tdtop">'.dol_print_date($i*3600, 'hour', 'gmt').'</div>';
		    echo '  <div class="tagtd '.$style.' tdtop">';
		    echo "  </div>\n";
		    echo " </div>\n";
		    $i++;
		    $j++;
		}

		echo '</div></div>';

		show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, 1);

		print '</div>';
    }
    else
    {
    	print '<div class="div-table-responsive-no-min">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table

    	show_day_events($db, $day, $month, $year, $month, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, 0);

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
 * @param	string	$nonew			 0=Add "new entry button", 1=No "new entry button", -1=Only "new entry button"
 * @return	void
 */
function show_day_events($db, $day, $month, $year, $monthshown, $style, &$eventarray, $maxprint = 0, $maxnbofchar = 16, $newparam = '', $showinfo = 0, $minheight = 60, $nonew = 0)
{
    global $user, $conf, $langs;
    global $action, $filter, $filtert, $status, $actioncode, $usergroup;	// Filters used into search form
    global $theme_datacolor;
    global $cachethirdparties, $cachecontacts, $cacheusers, $colorindexused;

    $dateint = sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $day);

    print "\n";

    // Line with title of day
    $curtime = dol_mktime(0, 0, 0, $month, $day, $year);
    print '<div id="dayevent_'.$dateint.'" class="dayevent tagtable centpercent nobordernopadding">'."\n";

    if ($nonew <= 0)
    {
	    print '<div class="tagtr"><div class="nowrap float">';
	    print '<a style="color: #666" href="'.DOL_URL_ROOT.'/comm/action/index.php?';
	    print 'action=show_day&day='.str_pad($day, 2, "0", STR_PAD_LEFT).'&month='.str_pad($month, 2, "0", STR_PAD_LEFT).'&year='.$year;
	    print $newparam;
	    print '">';
	    if ($showinfo) print dol_print_date($curtime, 'daytextshort');
	    else print dol_print_date($curtime, '%d');
	    print '</a>';
	    print '</div><div class="floatright nowrap">';
	    if ($user->rights->agenda->myactions->create || $user->rights->agenda->allactions->create)
	    {
	    	$newparam.='&month='.str_pad($month, 2, "0", STR_PAD_LEFT).'&year='.$year;

	        //$param='month='.$monthshown.'&year='.$year;
	        $hourminsec='100000';
	        print '<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&datep='.sprintf("%04d%02d%02d", $year, $month, $day).$hourminsec.'&backtopage='.urlencode($_SERVER["PHP_SELF"].($newparam?'?'.$newparam:'')).'">';
	        print img_picto($langs->trans("NewAction"), 'edit_add.png');
	        print '</a>';
	    }
	    print '</div></div>'."\n";
    }

    if ($nonew < 0)
    {
    	print '</div>';
    	return;
    }

    // Line with td contains all div of each events
    print '<div class="tagtr">';
    print '<div class="tagtd centpercent agendacell sortable">';

    //$curtime = dol_mktime (0, 0, 0, $month, $day, $year);
    $i=0; $numother=0; $numbirthday=0; $numical=0; $numicals=array();
    $ymd=sprintf("%04d", $year).sprintf("%02d", $month).sprintf("%02d", $day);

    $colorindexused[$user->id] = 0;			// Color index for current user (user->id) is always 0
    $nextindextouse=is_array($colorindexused)?count($colorindexused):0;	// At first run this is 0, so fist user has 0, next 1, ...
	//var_dump($colorindexused);

    foreach ($eventarray as $daykey => $notused)
    {
        $annee = date('Y', $daykey);
        $mois = date('m', $daykey);
        $jour = date('d', $daykey);
        if ($day==$jour && $month==$mois && $year==$annee)
        {
            foreach ($eventarray[$daykey] as $index => $event)
            {
                if ($i < $maxprint || $maxprint == 0 || ! empty($conf->global->MAIN_JS_SWITCH_AGENDA))
                {
                    $keysofuserassigned=array_keys($event->userassigned);
                    $ponct=($event->date_start_in_calendar == $event->date_end_in_calendar);

                    // Define $color (Hex string like '0088FF') and $cssclass of event
                    $color=-1; $cssclass=''; $colorindex=-1;
                    if (in_array($user->id, $keysofuserassigned))
                    {
	                    $cssclass='family_mytasks';

	                    if (empty($cacheusers[$event->userownerid]))
	                    {
	                    	$newuser=new User($db);
	                    	$newuser->fetch($event->userownerid);
	                    	$cacheusers[$event->userownerid]=$newuser;
	                    }
	                    //var_dump($cacheusers[$event->userownerid]->color);

	                    // We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
	                    if (! empty($cacheusers[$event->userownerid]->color)) $color=$cacheusers[$event->userownerid]->color;
                    }
                    elseif ($event->type_code == 'ICALEVENT')      // Event come from external ical file
                    {
                    	$numical++;
                    	if (! empty($event->icalname)) {
                    		if (! isset($numicals[dol_string_nospecial($event->icalname)])) {
                    			$numicals[dol_string_nospecial($event->icalname)] = 0;
                    		}
                    		$numicals[dol_string_nospecial($event->icalname)]++;
                    	}

                    	$color=($event->icalcolor?$event->icalcolor:-1);
                    	$cssclass=(! empty($event->icalname)?'family_ext'.md5($event->icalname):'family_other');
                    }
                    elseif ($event->type_code == 'BIRTHDAY')
                    {
                    	$numbirthday++; $colorindex=2; $cssclass='family_birthday unmovable'; $color=sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
                    }
                    else
               	    {
                    	$numother++;
                    	$color=($event->icalcolor?$event->icalcolor:-1);
                    	$cssclass=(! empty($event->icalname)?'family_ext'.md5($event->icalname):'family_other');

                        if (empty($cacheusers[$event->userownerid]))
                        {
                        	$newuser=new User($db);
                        	$newuser->fetch($event->userownerid);
                        	$cacheusers[$event->userownerid]=$newuser;
                        }
                        //var_dump($cacheusers[$event->userownerid]->color);

                       	// We decide to choose color of owner of event (event->userownerid is user id of owner, event->userassigned contains all users assigned to event)
                       	if (! empty($cacheusers[$event->userownerid]->color)) $color=$cacheusers[$event->userownerid]->color;
                    }

                    if ($color < 0)	// Color was not set on user card. Set color according to color index.
                    {
                    	// Define color index if not yet defined
                    	$idusertouse=($event->userownerid?$event->userownerid:0);
                    	if (isset($colorindexused[$idusertouse]))
                    	{
                    		$colorindex=$colorindexused[$idusertouse];	// Color already assigned to this user
                    	}
                    	else
                    	{
                   			$colorindex=$nextindextouse;
                   			$colorindexused[$idusertouse]=$colorindex;
                    		if (! empty($theme_datacolor[$nextindextouse+1])) $nextindextouse++;	// Prepare to use next color
                    	}
                    	//print '|'.($color).'='.($idusertouse?$idusertouse:0).'='.$colorindex.'<br>';
			            // Define color
                    	$color=sprintf("%02x%02x%02x", $theme_datacolor[$colorindex][0], $theme_datacolor[$colorindex][1], $theme_datacolor[$colorindex][2]);
                    }
                    $cssclass=$cssclass.' '.$cssclass.'_day_'.$ymd;

                    // Defined style to disable drag and drop feature
                    if ($event->type_code =='AC_OTH_AUTO')
                    {
                        $cssclass.= " unmovable";
                    }
                    elseif ($event->type_code == 'ICALEVENT')
                    {
                        $cssclass.= " unmovable";
                    } elseif ($event->date_end_in_calendar && date('Ymd', $event->date_start_in_calendar) != date('Ymd', $event->date_end_in_calendar)) {
                        $tmpyearend    = date('Y', $event->date_end_in_calendar);
                        $tmpmonthend   = date('m', $event->date_end_in_calendar);
                        $tmpdayend     = date('d', $event->date_end_in_calendar);
                        if ($tmpyearend == $annee && $tmpmonthend == $mois && $tmpdayend == $jour)
                        {
                            $cssclass.= " unmovable";
                        }
                    }
                    else{
                        if ($user->rights->agenda->allactions->create ||
                            (($event->authorid == $user->id || $event->userownerid == $user->id) && $user->rights->agenda->myactions->create))
                        {
                            $cssclass.= " movable cursormove";
                        }else{
                            $cssclass.= " unmovable";
                        }
                    }

                    $h=''; $nowrapontd=1;
                    if ($action == 'show_day')  { $h='height: 100%; '; $nowrapontd=0; }
                    if ($action == 'show_week') { $h='height: 100%; '; $nowrapontd=0; }

                    // Show rect of event
                    print "\n";
                    print '<!-- start event '.$i.' -->'."\n";
                    print '<div id="event_'.$ymd.'_'.$i.'" class="event '.$cssclass.'"';
                    //print ' style="height: 100px;';
                    //print ' position: absolute; top: 40px; width: 50%;';
                    //print '"';
                    print '>';

                    //var_dump($event->userassigned);
                    //var_dump($event->transparency);
                    print '<table class="centpercent cal_event'.(empty($event->transparency)?' cal_event_notbusy':' cal_event_busy').'" style="'.$h;
                    if (empty($event->transparency) && empty($conf->global->AGENDA_NO_TRANSPARENT_ON_NOT_BUSY))
                    {
                    	print 'border: 2px solid #'.$color.';';
                    }
                    else
                    {
                    	print 'background: #'.$color.';';
                    	print 'background: -webkit-gradient(linear, left top, left bottom, from(#'.dol_color_minus($color, -3).'), to(#'.dol_color_minus($color, -1).'));';
                    }
                    //if (! empty($event->transparency)) print 'background: #'.$color.'; background: -webkit-gradient(linear, left top, left bottom, from(#'.$color.'), to(#'.dol_color_minus($color,1).'));';
                    //else print 'background-color: transparent !important; background: none; border: 1px solid #bbb;';
                    //print ' -moz-border-radius:4px;"';
                    //print 'border: 1px solid #ccc" width="100%"';
                    print '">';
                    print '<tr>';
                    print '<td class="tdoverflow nobottom centpercent '.($nowrapontd?'nowrap ':'').'cal_event'.($event->type_code == 'BIRTHDAY'?' cal_event_birthday':'').'">';

                    $daterange='';

                    if ($event->type_code == 'BIRTHDAY') // It's a birthday
                    {
                        print $event->getNomUrl(1, $maxnbofchar, 'cal_event', 'birthday', 'contact');
                    }
                    if ($event->type_code != 'BIRTHDAY')
                    {
                        // Picto
                        if (empty($event->fulldayevent))
                        {
                            //print $event->getNomUrl(2).' ';
                        }

                        // Date
                        if (empty($event->fulldayevent))
                        {
                            // Show hours (start ... end)
                            $tmpyearstart  = date('Y', $event->date_start_in_calendar);
                            $tmpmonthstart = date('m', $event->date_start_in_calendar);
                            $tmpdaystart   = date('d', $event->date_start_in_calendar);
                            $tmpyearend    = date('Y', $event->date_end_in_calendar);
                            $tmpmonthend   = date('m', $event->date_end_in_calendar);
                            $tmpdayend     = date('d', $event->date_end_in_calendar);
                            // Hour start
                            if ($tmpyearstart == $annee && $tmpmonthstart == $mois && $tmpdaystart == $jour)
                            {
                                $daterange.=dol_print_date($event->date_start_in_calendar, '%H:%M');	// Il faudrait utiliser ici tzuser, mais si on ne peut pas car qd on rentre un date dans fiche action, en input la conversion local->gmt se base sur le TZ server et non user
                                if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar)
                                {
                                    if ($tmpyearstart == $tmpyearend && $tmpmonthstart == $tmpmonthend && $tmpdaystart == $tmpdayend)
                                    $daterange.='-';
                                    //else
                                    //print '...';
                                }
                            }
                            if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar)
                            {
                                if ($tmpyearstart != $tmpyearend || $tmpmonthstart != $tmpmonthend || $tmpdaystart != $tmpdayend)
                                {
                                    $daterange.='...';
                                }
                            }
                            // Hour end
                            if ($event->date_end_in_calendar && $event->date_start_in_calendar != $event->date_end_in_calendar)
                            {
                                if ($tmpyearend == $annee && $tmpmonthend == $mois && $tmpdayend == $jour)
                                $daterange.=dol_print_date($event->date_end_in_calendar, '%H:%M');	// Il faudrait utiliser ici tzuser, mais si on ne peut pas car qd on rentre un date dans fiche action, en input la conversion local->gmt se base sur le TZ server et non user
                            }
                        }
                        else
						{
                            if ($showinfo)
                            {
                                print $langs->trans("EventOnFullDay")."<br>\n";
                            }
                        }

                        // Show title
                        $titletoshow = $daterange;
                        $titletoshow.=($titletoshow?' ':'').$event->libelle;

                        if ($event->type_code == 'ICALEVENT') print $titletoshow;
                        else
                        {
                        	$savlabel=$event->label?$event->label:$event->libelle;
                        	$event->label=$titletoshow;
                        	$event->libelle=$titletoshow;
                        	print $event->getNomUrl(0, $maxnbofchar, 'cal_event', '', 0, 1);
                        	$event->label=$savlabel;
                        	$event->libelle=$savlabel;
                        }

                        // Loop on each assigned user
                        $listofusertoshow='';
                        $posuserassigned=0;
                        foreach($event->userassigned as $tmpid => $tmpdata)
                        {
                            if (! $posuserassigned && $titletoshow) $listofusertoshow.='<br>';
                            $posuserassigned++;
                            if (empty($cacheusers[$tmpid]))
                            {
                                $newuser=new User($db);
                                $newuser->fetch($tmpid);
                                $cacheusers[$tmpid]=$newuser;
                            }

                            $listofusertoshow.=$cacheusers[$tmpid]->getNomUrl(-3, '', 0, 0, 0, 0, '', 'paddingright valigntextbottom');
                        }
                        print $listofusertoshow;

                        if ($event->type_code == 'ICALEVENT') print '<br>('.dol_trunc($event->icalname, $maxnbofchar).')';

                        // If action related to company / contact
                        $linerelatedto='';
                        if (! empty($event->societe->id) && $event->societe->id > 0)
                        {
                            if (! isset($cachethirdparties[$event->societe->id]) || ! is_object($cachethirdparties[$event->societe->id]))
                            {
                                $thirdparty=new Societe($db);
                                $thirdparty->fetch($event->societe->id);
                                $cachethirdparties[$event->societe->id]=$thirdparty;
                            }
                            else $thirdparty=$cachethirdparties[$event->societe->id];
                            if (! empty($thirdparty->id)) $linerelatedto.=$thirdparty->getNomUrl(1, '', 0);
                        }
                        if (! empty($event->contact->id) && $event->contact->id > 0)
                        {
                            if (! is_object($cachecontacts[$event->contact->id]))
                            {
                                $contact=new Contact($db);
                                $contact->fetch($event->contact->id);
                                $cachecontacts[$event->contact->id]=$contact;
                            }
                            else $contact=$cachecontacts[$event->contact->id];
                            if ($linerelatedto) $linerelatedto.='&nbsp;';
                            if (! empty($contact->id)) $linerelatedto.=$contact->getNomUrl(1, '', 0);
                        }
                        if (! empty($event->fk_element) && $event->fk_element > 0 && ! empty($event->elementtype) && ! empty($conf->global->AGENDA_SHOW_LINKED_OBJECT))
                        {
                            include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
                            if ($linerelatedto) $linerelatedto.='<br>';
                            $linerelatedto.=dolGetElementUrl($event->fk_element, $event->elementtype, 1);
                        }
                        if ($linerelatedto) print '<br>'.$linerelatedto;
                    }

                    // Show location
                    if ($showinfo)
                    {
                        if ($event->location)
                        {
                            print '<br>';
                            print $langs->trans("Location").': '.$event->location;
                        }
                    }

                    print '</td>';
                    // Status - Percent
                    $withstatus=0;
                    if ($event->type_code != 'BIRTHDAY' && $event->type_code != 'ICALEVENT')
                    {
                        $withstatus=1;
                        if ($event->percentage >= 0) $withstatus=2;
                    }
                    print '<td class="nobottom right nowrap cal_event_right'.($withstatus >= 2 ?' cal_event_right_status':'').'">';
                    if ($withstatus) print $event->getLibStatut(3, 1);
                    else print '&nbsp;';
                    print '</td></tr></table>';
                    print '</div><!-- end event '.$i.' -->'."\n";
                    $i++;
                }
                else
                {
                	print '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action='.$action.'&maxprint=0&month='.$monthshown.'&year='.$year;
                    print ($status?'&status='.$status:'').($filter?'&filter='.$filter:'');
                    print ($filtert?'&filtert='.$filtert:'');
                    print ($usergroup?'&usergroup='.$usergroup:'');
                    print ($actioncode!=''?'&actioncode='.$actioncode:'');
                    print '">'.img_picto("all", "1downarrow_selected.png").' ...';
                    print ' +'.(count($eventarray[$daykey])-$maxprint);
                    print '</a>';
                    break;
                    //$ok=false;        // To avoid to show twice the link
                }
            }

            break;
        }
    }
    if (! $i) print '&nbsp;';

    if (! empty($conf->global->MAIN_JS_SWITCH_AGENDA) && $i > $maxprint && $maxprint)
    {
        print '<div id="more_'.$ymd.'">'.img_picto("all", "1downarrow_selected.png").' +'.$langs->trans("More").'...</div>';
        //print ' +'.(count($eventarray[$daykey])-$maxprint);
        print '<script type="text/javascript">'."\n";
        print 'jQuery(document).ready(function () {'."\n";
        print 'jQuery("#more_'.$ymd.'").click(function() { reinit_day_'.$ymd.'(); });'."\n";

        print 'function reinit_day_'.$ymd.'() {'."\n";
        print 'var nb=0;'."\n";
        // TODO Loop on each element of day $ymd and start to toggle once $maxprint has been reached
        print 'jQuery(".family_mytasks_day_'.$ymd.'").toggle();';
        print '}'."\n";

        print '});'."\n";

        print '</script>'."\n";
    }

    print '</div></div>';       // td tr

    print '</div>';             // table
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
	$newcolor=$color;
	if ($minusunit == 16)
	{
    	$newcolor[0]=dechex(max(min(hexdec($newcolor[0])-$minus, 15), 0));
    	$newcolor[2]=dechex(max(min(hexdec($newcolor[2])-$minus, 15), 0));
    	$newcolor[4]=dechex(max(min(hexdec($newcolor[4])-$minus, 15), 0));
	}
	else
	{
	    // Not yet implemented
	}
	return $newcolor;
}


/**
 * Sort events by date
 *
 * @param   object  $a      Event A
 * @param   object  $b      Event B
 * @return  int             < 0 if event A should be before event B, > 0 otherwise, 0 if they have the exact same time slot
 */
function sort_events_by_date($a, $b)
{
	if($a->datep != $b->datep)
	{
		return $a->datep - $b->datep;
	}

	// If both events have the same start time, longest first
	return $b->datef - $a->datef;
}
