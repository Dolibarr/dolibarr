<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2014      Cedric GROSS         <c.gross@kreiz-it.fr>
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
 *  \file       htdocs/comm/action/peruser.php
 *  \ingroup    agenda
 *  \brief      Tab of calendar events per user
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
if (! empty($conf->projet->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';


if (! isset($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW)) $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW=3;

$filter=GETPOST("filter",'',3);
$filtera = GETPOST("userasked","int",3)?GETPOST("userasked","int",3):GETPOST("filtera","int",3);
$filtert = GETPOST("usertodo","int",3)?GETPOST("usertodo","int",3):GETPOST("filtert","int",3);
$filterd = GETPOST("userdone","int",3)?GETPOST("userdone","int",3):GETPOST("filterd","int",3);
$usergroup = GETPOST("usergroup","int",3);
//if (! ($usergroup > 0) && ! ($filtert > 0)) $filtert = $user->id;
//$showbirthday = empty($conf->use_javascript_ajax)?GETPOST("showbirthday","int"):1;
$showbirthday = 0;


$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page","int");
if ($page == -1) { $page = 0; }
$limit = $conf->liste_limit;
$offset = $limit * $page;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="a.datec";

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', 0, '', 'myactions');
if ($socid < 0) $socid='';

$canedit=1;
if (! $user->rights->agenda->myactions->read) accessforbidden();
if (! $user->rights->agenda->allactions->read) $canedit=0;
if (! $user->rights->agenda->allactions->read || $filter =='mine')  // If no permission to see all, we show only affected to me
{
    $filtera=$user->id;
    $filtert=$user->id;
    $filterd=$user->id;
}

//$action=GETPOST('action','alpha');
$action='show_peruser'; //We use 'show_week' mode
//$year=GETPOST("year");
$year=GETPOST("year","int")?GETPOST("year","int"):date("Y");
$month=GETPOST("month","int")?GETPOST("month","int"):date("m");
$week=GETPOST("week","int")?GETPOST("week","int"):date("W");
$day=GETPOST("day","int")?GETPOST("day","int"):date("d");
$pid=GETPOST("projectid","int",3);
$status=GETPOST("status");
$type=GETPOST("type");
$maxprint=(isset($_GET["maxprint"])?GETPOST("maxprint"):$conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':'');

$dateselect=dol_mktime(0, 0, 0, GETPOST('dateselectmonth'), GETPOST('dateselectday'), GETPOST('dateselectyear'));
if ($dateselect > 0)
{
	$day=GETPOST('dateselectday');
	$month=GETPOST('dateselectmonth');
	$year=GETPOST('dateselectyear');
}

$tmp=empty($conf->global->MAIN_DEFAULT_WORKING_HOURS)?'9-18':$conf->global->MAIN_DEFAULT_WORKING_HOURS;
$tmparray=explode('-',$tmp);
$begin_h = GETPOST('begin_h')?GETPOST('begin_h','int'):($tmparray[0] != '' ? $tmparray[0] : 9);
$end_h   = GETPOST('end_h')?GETPOST('end_h'):($tmparray[1] != '' ? $tmparray[1] : 18);
if ($begin_h < 0 || $begin_h > 23) $begin_h = 9;
if ($end_h < 1 || $end_h > 24) $end_h = 18;
if ($end_h <= $begin_h) $end_h = $begin_h + 1;

$tmp=empty($conf->global->MAIN_DEFAULT_WORKING_DAYS)?'1-5':$conf->global->MAIN_DEFAULT_WORKING_DAYS;
$tmparray=explode('-',$tmp);
$begin_d = GETPOST('begin_d')?GETPOST('begin_d','int'):($tmparray[0] != '' ? $tmparray[0] : 1);
$end_d   = GETPOST('end_d')?GETPOST('end_d'):($tmparray[1] != '' ? $tmparray[1] : 5);
if ($begin_d < 1 || $begin_d > 7) $begin_d = 1;
if ($end_d < 1 || $end_d > 7) $end_d = 7;
if ($end_d <= $begin_d) $end_d = $begin_d + 1;

if ($actioncode == '') $actioncode=(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE);
if ($status == ''   && ! isset($_GET['status']) && ! isset($_POST['status'])) $status=(empty($conf->global->AGENDA_DEFAULT_FILTER_STATUS)?'':$conf->global->AGENDA_DEFAULT_FILTER_STATUS);
if (empty($action) && ! isset($_GET['action']) && ! isset($_POST['action'])) $action=(empty($conf->global->AGENDA_DEFAULT_VIEW)?'show_month':$conf->global->AGENDA_DEFAULT_VIEW);

if (GETPOST('viewcal') && $action != 'show_day' && $action != 'show_week' && $action != 'show_peruser')  {
    $action='show_month'; $day='';
}                                                   // View by month
if (GETPOST('viewweek') || $action == 'show_week') {
    $action='show_week'; $week=($week?$week:date("W")); $day=($day?$day:date("d"));
}  // View by week
if (GETPOST('viewday') || $action == 'show_day')  {
    $action='show_day'; $day=($day?$day:date("d"));
}                                  // View by day


$langs->load("agenda");
$langs->load("other");
$langs->load("commercial");

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('agenda'));


/*
 * Actions
 */

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
llxHeader('',$langs->trans("Agenda"),$help_url);

$form=new Form($db);
$companystatic=new Societe($db);

$now=dol_now();
$nowarray=dol_getdate($now);
$nowyear=$nowarray['year'];
$nowmonth=$nowarray['mon'];
$nowday=$nowarray['mday'];


// Define list of all external calendars (global setup)
$listofextcals=array();

$prev = dol_get_first_day_week($day, $month, $year);
$first_day   = $prev['first_day'];
$first_month = $prev['first_month'];
$first_year  = $prev['first_year'];

$week = $prev['week'];

$day = (int) $day;
$next = dol_get_next_week($day, $week, $month, $year);
$next_year  = $next['year'];
$next_month = $next['month'];
$next_day   = $next['day'];

// Define firstdaytoshow and lastdaytoshow
$firstdaytoshow=dol_mktime(0,0,0,$first_month,$first_day,$first_year);
$lastdaytoshow=dol_time_plus_duree($firstdaytoshow, 6, 'd');

$max_day_in_month = date("t",dol_mktime(0,0,0,$month,1,$year));

$tmpday = $first_day;
//print 'xx'.$prev_year.'-'.$prev_month.'-'.$prev_day;
//print 'xx'.$next_year.'-'.$next_month.'-'.$next_day;
//print dol_print_date($firstdaytoshow,'day');
//print dol_print_date($lastdaytoshow,'day');

$title=$langs->trans("DoneAndToDoActions");
if ($status == 'done') $title=$langs->trans("DoneActions");
if ($status == 'todo') $title=$langs->trans("ToDoActions");

$param='';
if ($actioncode || isset($_GET['actioncode']) || isset($_POST['actioncode'])) $param.="&actioncode=".$actioncode;
if ($status || isset($_GET['status']) || isset($_POST['status'])) $param.="&status=".$status;
if ($filter)  $param.="&filter=".$filter;
if ($filtera) $param.="&filtera=".$filtera;
if ($filtert) $param.="&filtert=".$filtert;
if ($filterd) $param.="&filterd=".$filterd;
if ($socid)   $param.="&socid=".$socid;
if ($showbirthday) $param.="&showbirthday=1";
if ($pid)     $param.="&projectid=".$pid;
if ($type)   $param.="&type=".$type;
if ($action == 'show_day' || $action == 'show_week' || $action == 'show_month' || $action != 'show_peruser') $param.='&action='.$action;
$param.="&maxprint=".$maxprint;

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

// Define firstdaytoshow and lastdaytoshow
$firstdaytoshow=dol_mktime(0,0,0,$first_month,$first_day,$first_year);
$lastdaytoshow=dol_time_plus_duree($firstdaytoshow, 6, 'd');

$max_day_in_month = date("t",dol_mktime(0,0,0,$month,1,$year));

$tmpday = $first_day;

$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;day=".$prev_day.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
$nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0,0,0,$first_month,$first_day,$first_year),"%Y").", ".$langs->trans("Week")." ".$week;
$nav.=" </span>\n";
$nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;day=".$next_day.$param."\">".img_next($langs->trans("Next"))."</a>\n";
$nav.=" &nbsp; (<a href=\"?year=".$nowyear."&amp;month=".$nowmonth."&amp;day=".$nowday.$param."\">".$langs->trans("Today")."</a>)";
$picto='calendarweek';

$nav.=' &nbsp; <form name="dateselect" action="'.$_SERVER["PHP_SELF"].'?action=show_peruser'.$param.'">';
$nav.=$form->select_date($dateselect, 'dateselect', 0, 0, 1, '', 1, 0, 1);
$nav.=' <input type="submit" name="submitdateselect" class="button" value="'.$langs->trans("Refresh").'">';
$nav.='</form>';

// Must be after the nav definition
$param.='&year='.$year.'&month='.$month.($day?'&day='.$day:'');
//print 'x'.$param;




$tabactive='';
if ($action == 'show_month') $tabactive='cardmonth';
if ($action == 'show_week') $tabactive='cardweek';
if ($action == 'show_day')  $tabactive='cardday';
if ($action == 'show_list') $tabactive='cardlist';
if ($action == 'show_peruser') $tabactive='cardperuser';

$paramnoaction=preg_replace('/action=[a-z_]+/','',$param);

$head = calendars_prepare_head($paramnoaction);

dol_fiche_head($head, $tabactive, $langs->trans('Agenda'), 0, 'action');
print_actions_filter($form, $canedit, $status, $year, $month, $day, $showbirthday, $filtera, $filtert, $filterd, $pid, $socid, $action, $listofextcals, $actioncode, $usergroup);
dol_fiche_end();

$showextcals=$listofextcals;
// Legend
if ($conf->use_javascript_ajax)
{
	$s='';
	$s.='<script type="text/javascript">' . "\n";
	$s.='jQuery(document).ready(function () {' . "\n";
	$s.='jQuery("#check_mytasks").click(function() { jQuery(".family_mytasks").toggle(); jQuery(".family_other").toggle(); });' . "\n";
	$s.='jQuery("#check_birthday").click(function() { jQuery(".family_birthday").toggle(); });' . "\n";
	$s.='jQuery(".family_birthday").toggle();' . "\n";
	if ($action=="show_week" || $action=="show_month" || empty($action))
	{
    	$s.='jQuery( "td.sortable" ).sortable({connectWith: ".sortable",placeholder: "ui-state-highlight",items: "div:not(.unsortable)", receive: function( event, ui ) {';
    	$s.='var frm=jQuery("#move_event");frm.attr("action",ui.item.find("a.cal_event").attr("href")).children("#newdate").val(jQuery(event.target).closest("div").attr("id"));frm.submit();}});'."\n";
	}
  	$s.='});' . "\n";
	$s.='</script>' . "\n";
	if (! empty($conf->use_javascript_ajax))
	{
		$s.='<div class="nowrap clear float"><input type="checkbox" id="check_mytasks" name="check_mytasks" checked="true" disabled="disabled"> ' . $langs->trans("LocalAgenda").' &nbsp; </div>';
		if (is_array($showextcals) && count($showextcals) > 0)
		{
			foreach ($showextcals as $val)
			{
				$htmlname = dol_string_nospecial($val['name']);
				$s.='<script type="text/javascript">' . "\n";
				$s.='jQuery(document).ready(function () {' . "\n";
				$s.='		jQuery("#check_' . $htmlname . '").click(function() {';
				$s.=' 		/* alert("'.$htmlname.'"); */';
				$s.=' 		jQuery(".family_' . $htmlname . '").toggle();';
				$s.='		});' . "\n";
				$s.='});' . "\n";
				$s.='</script>' . "\n";
				$s.='<div class="nowrap float"><input type="checkbox" id="check_' . $htmlname . '" name="check_' . $htmlname . '" checked="true"> ' . $val ['name'] . ' &nbsp; </div>';
			}
		}
	}
	//$s.='<div class="nowrap float"><input type="checkbox" id="check_birthday" name="check_birthday"> '.$langs->trans("AgendaShowBirthdayEvents").' &nbsp; </div>';
}



$link='';
print_fiche_titre($s,$link.' &nbsp; &nbsp; '.$nav, '');


// Get event in an array
$eventarray=array();

$sql = 'SELECT a.id,a.label,';
$sql.= ' a.datep,';
$sql.= ' a.datep2,';
$sql.= ' a.datea,';
$sql.= ' a.datea2,';
$sql.= ' a.percent,';
$sql.= ' a.fk_user_author,a.fk_user_action,a.fk_user_done,';
$sql.= ' a.transparency, a.priority, a.fulldayevent, a.location,';
$sql.= ' a.fk_soc, a.fk_contact,';
$sql.= ' ca.code';
$sql.= ' FROM '.MAIN_DB_PREFIX.'c_actioncomm as ca, '.MAIN_DB_PREFIX."actioncomm as a";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
if ($usergroup > 0) $sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ugu";
$sql.= ' WHERE a.fk_action = ca.id';
$sql.= ' AND a.entity IN ('.getEntity('agenda', 1).')';
if ($actioncode) $sql.=" AND ca.code='".$db->escape($actioncode)."'";
if ($pid) $sql.=" AND a.fk_project=".$db->escape($pid);
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
if ($socid > 0) $sql.= ' AND a.fk_soc = '.$socid;
if ($usergroup > 0) $sql.= " AND ugu.fk_user = a.fk_user_action";
if ($action == 'show_day')
{
    $sql.= " AND (";
    $sql.= " (a.datep BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,$day,$year))."'";
    $sql.= " AND '".$db->idate(dol_mktime(23,59,59,$month,$day,$year))."')";
    $sql.= " OR ";
    $sql.= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,$day,$year))."'";
    $sql.= " AND '".$db->idate(dol_mktime(23,59,59,$month,$day,$year))."')";
    $sql.= " OR ";
    $sql.= " (a.datep < '".$db->idate(dol_mktime(0,0,0,$month,$day,$year))."'";
    $sql.= " AND a.datep2 > '".$db->idate(dol_mktime(23,59,59,$month,$day,$year))."')";
    $sql.= ')';
}
else
{
    // To limit array
    $sql.= " AND (";
    $sql.= " (a.datep BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7))."'";   // Start 7 days before
    $sql.= " AND '".$db->idate(dol_mktime(23,59,59,$month,28,$year)+(60*60*24*10))."')";            // End 7 days after + 3 to go from 28 to 31
    $sql.= " OR ";
    $sql.= " (a.datep2 BETWEEN '".$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7))."'";
    $sql.= " AND '".$db->idate(dol_mktime(23,59,59,$month,28,$year)+(60*60*24*10))."')";
    $sql.= " OR ";
    $sql.= " (a.datep < '".$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7))."'";
    $sql.= " AND a.datep2 > '".$db->idate(dol_mktime(23,59,59,$month,28,$year)+(60*60*24*10))."')";
    $sql.= ')';
}
if ($type) $sql.= " AND ca.id = ".$type;
if ($status == '0') { $sql.= " AND a.percent = 0"; }
if ($status == '-1') { $sql.= " AND a.percent = -1"; }	// Not applicable
if ($status == '50') { $sql.= " AND (a.percent > 0 AND a.percent < 100)"; }	// Running already started
if ($status == 'done' || $status == '100') { $sql.= " AND (a.percent = 100 OR (a.percent = -1 AND a.datep2 <= '".$db->idate($now)."'))"; }
if ($status == 'todo') { $sql.= " AND ((a.percent >= 0 AND a.percent < 100) OR (a.percent = -1 AND a.datep2 > '".$db->idate($now)."'))"; }
if ($filtera > 0 || $filtert > 0 || $filterd > 0 || $usergroup > 0)
{
    $sql.= " AND (";
    if ($filtera > 0) $sql.= " a.fk_user_author = ".$filtera;
    if ($filtert > 0) $sql.= ($filtera>0?" OR ":"")." a.fk_user_action = ".$filtert;
    if ($filterd > 0) $sql.= ($filtera>0||$filtert>0?" OR ":"")." a.fk_user_done = ".$filterd;
	if ($usergroup > 0) $sql.= ($filtera>0||$filtert>0||$filterd>0?" OR ":"")." ugu.fk_usergroup = ".$usergroup;
    $sql.= ")";
}
// Sort on date
$sql.= ' ORDER BY fk_user_action, datep'; //fk_user_action
//print $sql;

dol_syslog("comm/action/index.php", LOG_DEBUG);
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i=0;
    while ($i < $num)
    {
        $obj = $db->fetch_object($resql);

        // Create a new object action
        $event=new ActionComm($db);
        $event->id=$obj->id;
        $event->datep=$db->jdate($obj->datep);      // datep and datef are GMT date
        $event->datef=$db->jdate($obj->datep2);
        $event->type_code=$obj->code;
        $event->libelle=$obj->label;				// deprecated
        $event->label=$obj->label;
        $event->percentage=$obj->percent;
        $event->author->id=$obj->fk_user_author;	// user id of creator
        $event->usertodo->id=$obj->fk_user_action;	// user id of owner
        $event->userdone->id=$obj->fk_user_done;	// deprecated
		// $event->userstodo=... with s after user, in future version, will be an array with all id of user assigned to event
        $event->priority=$obj->priority;
        $event->fulldayevent=$obj->fulldayevent;
        $event->location=$obj->location;
        $event->transparency=$obj->transparency;

        $event->socid=$obj->fk_soc;
        $event->contactid=$obj->fk_contact;
        $event->societe->id=$obj->fk_soc;
        $event->contact->id=$obj->fk_contact;

        // Defined date_start_in_calendar and date_end_in_calendar property
        // They are date start and end of action but modified to not be outside calendar view.
        if ($event->percentage <= 0)
        {
            $event->date_start_in_calendar=$event->datep;
            if ($event->datef != '' && $event->datef >= $event->datep) $event->date_end_in_calendar=$event->datef;
            else $event->date_end_in_calendar=$event->datep;
        }
        else
        {
            $event->date_start_in_calendar=$event->datep;
            if ($event->datef != '' && $event->datef >= $event->datep) $event->date_end_in_calendar=$event->datef;
            else $event->date_end_in_calendar=$event->datep;
        }
        // Define ponctual property
        if ($event->date_start_in_calendar == $event->date_end_in_calendar)
        {
            $event->ponctuel=1;
        }

        // Check values
        if ($event->date_end_in_calendar < $firstdaytoshow ||
        $event->date_start_in_calendar > $lastdaytoshow)
        {
            // This record is out of visible range
        }
        else
        {
            if ($event->date_start_in_calendar < $firstdaytoshow) $event->date_start_in_calendar=$firstdaytoshow;
            if ($event->date_end_in_calendar > $lastdaytoshow) $event->date_end_in_calendar=$lastdaytoshow;

            // Add an entry in actionarray for each day
            $daycursor=$event->date_start_in_calendar;
            $annee = date('Y',$daycursor);
            $mois = date('m',$daycursor);
            $jour = date('d',$daycursor);

            // Loop on each day covered by action to prepare an index to show on calendar
            $loop=true; $j=0;
            $daykey=dol_mktime(0,0,0,$mois,$jour,$annee);
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


$maxnbofchar=18;
$cachethirdparties=array();
$cachecontacts=array();

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
if (is_readable($color_file))
{
    include_once $color_file;
}
if (! is_array($theme_datacolor)) $theme_datacolor=array(array(120,130,150), array(200,160,180), array(190,190,220));


$newparam=$param;   // newparam is for birthday links
$newparam=preg_replace('/showbirthday=/i','showbirthday_=',$newparam);	// To avoid replacement when replace day= is done
$newparam=preg_replace('/action=show_month&?/i','',$newparam);
$newparam=preg_replace('/action=show_week&?/i','',$newparam);
$newparam=preg_replace('/day=[0-9]+&?/i','',$newparam);
$newparam=preg_replace('/month=[0-9]+&?/i','',$newparam);
$newparam=preg_replace('/year=[0-9]+&?/i','',$newparam);
$newparam=preg_replace('/viewweek=[0-9]+&?/i','',$newparam);
$newparam=preg_replace('/showbirthday_=/i','showbirthday=',$newparam);	// Restore correct parameter
$newparam.='&viewweek=1';

echo '<form id="move_event" action="" method="POST"><input type="hidden" name="action" value="mupdate">';
echo '<input type="hidden" name="backtopage" value="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'">';
echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
echo '<input type="hidden" name="newdate" id="newdate">' ;
echo '</form>';


// Table :
echo '<table width="100%" class="nocellnopadd cal_month">';

echo '<tr class="liste_titre">';
echo '<td></td>';
$i=0;
while ($i < 7)
{
	if (($i + 1) < $begin_d || ($i + 1) > $end_d)
	{
		$i++;
		continue;
	}
	echo '<td align="center" colspan="'.($end_h - $begin_h).'">';
	echo $langs->trans("Day".(($i+(isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:1)) % 7));
	print "<br>";
	if ($i) print dol_print_date(dol_time_plus_duree($firstdaytoshow, $i, 'd'),'day');
	else print dol_print_date($firstdaytoshow,'day');
	echo "</td>\n";
	$i++;
}
echo "</tr>\n";

echo '<tr class="liste_titre">';
echo '<td></td>';
$i=0;
while ($i < 7)
{
	if (($i + 1) < $begin_d || ($i + 1) > $end_d)
	{
		$i++;
		continue;
	}
	for ($h = $begin_h; $h < $end_h; $h++)
	{
		echo '<td align="center">';
		print '<small style="font-family: courier">'.sprintf("%02d",$h).'</small>';
		print "</td>";
	}
	echo "</td>\n";
	$i++;
}
echo "</tr>\n";

// Define $usernames
$usernames = array(); //init
/* Use this to have list of users only if users have events
foreach ($eventarray as $daykey => $notused)
{
   $annee = date('Y',$daykey);
   $mois = date('m',$daykey);
   $jour = date('d',$daykey);
   //if ($day==$jour && $month==$mois && $year==$annee)
   //{
      //Tout les events à la même date :
      foreach ($eventarray[$daykey] as $index => $event)
      {
         $myuser = new User($db);
         $user_id = $event->usertodo->id;
         $myuser->fetch($user_id);
         $username = $myuser->getFullName($langs);
         if (! in_array($username, $usernames))
         {
            $usernames[] = $username;
         }
      }
   //}
}*/
if ($filtert > 0)
{
	$tmpuser = new User($db);
	$tmpuser->fetch($filtert);
	$usernames[] = $tmpuser;
}
else if ($usergroup)
{
	$tmpgroup = new UserGroup($db);
	$tmpgroup->fetch($usergroup);
	$usernames = $tmpgroup->listUsersForGroup();
}
else
{
	$tmpgroup = new UserGroup($db);
	//$tmpgroup->fetch($usergroup); No fetch, we want all users for all groups
	$usernames = $tmpgroup->listUsersForGroup();
}


// Loop on each user to show calendar
$sav = $tmpday;
$showheader = true;
foreach ($usernames as $username)
{
	echo "<tr>";
	echo '<td class="cal_current_month">' . $username->getNomUrl(1). '</td>';
	$tmpday = $sav;

	$i = 0;
	for ($iter_day = 0; $iter_day < 7; $iter_day++)
	{
		if (($i + 1) < $begin_d || ($i + 1) > $end_d)
		{
			$i++;
			continue;
		}

        // Show days of the current week
		$curtime = dol_time_plus_duree($firstdaytoshow, $iter_day, 'd');
		$tmparray = dol_getdate($curtime,'fast');
		$tmpday = $tmparray['mday'];
		$tmpmonth = $tmparray['mon'];
		$tmpyear = $tmparray['year'];

		$style='cal_current_month';
		if ($iter_day == 6) $style.=' cal_other_month';
		$today=0;
		$todayarray=dol_getdate($now,'fast');
		if ($todayarray['mday']==$tmpday && $todayarray['mon']==$month && $todayarray['year']==$year) $today=1;
		if ($today) $style='cal_today_peruser';

		show_day_events2($username, $tmpday, $month, $year, $monthshown, $style, $eventarray, 0, $maxnbofchar, $newparam, 1, 300, $showheader);

		$i++;
	}
	echo "</tr>\n";
	$showheader = false;
}

echo "</table>\n";


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
			url = "'.DOL_URL_ROOT.'/comm/action/listactions.php?usertodo="+userid
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



llxFooter();

$db->close();




/**
 * Show event of a particular day
 *
 * @param	string	$username		Login
 * @param   int		$day            Day
 * @param   int		$month          Month
 * @param   int		$year           Year
 * @param   int		$monthshown     Current month shown in calendar view
 * @param   string	$style          Style to use for this day
 * @param   array	&$eventarray    Array of events
 * @param   int		$maxprint       Nb of actions to show each day on month view (0 means no limit)
 * @param   int		$maxnbofchar    Nb of characters to show for event line
 * @param   string	$newparam       Parameters on current URL
 * @param   int		$showinfo       Add extended information (used by day view)
 * @param   int		$minheight      Minimum height for each event. 60px by default.
 * @param	boolean	$showheader		Show header
 * @return	void
 */
function show_day_events2($username, $day, $month, $year, $monthshown, $style, &$eventarray, $maxprint=0, $maxnbofchar=16, $newparam='', $showinfo=0, $minheight=60, $showheader=false)
{
	global $db;
	global $user, $conf, $langs;
	global $filter, $filtera, $filtert, $filterd, $status, $actioncode;	// Filters used into search form
	global $theme_datacolor;
	global $cachethirdparties, $cachecontacts, $colorindexused;
	global $begin_h, $end_h;

	$cases1 = array();	// Color first half hour
	$cases2 = array(); // Color second half hour

	$curtime = dol_mktime(0, 0, 0, $month, $day, $year);

	$i=0; $nummytasks=0; $numother=0; $numbirthday=0; $numical=0; $numicals=array();
	$ymd=sprintf("%04d",$year).sprintf("%02d",$month).sprintf("%02d",$day);

	$nextindextouse=count($colorindexused);	// At first run this is 0, so fist user has 0, next 1, ...

	foreach ($eventarray as $daykey => $notused)
	{
		$annee = date('Y',$daykey);
		$mois = date('m',$daykey);
		$jour = date('d',$daykey);
		if ($day==$jour && $month==$mois && $year==$annee)
		{
			//Tout les events à la même date :
			foreach ($eventarray[$daykey] as $index => $event)
			{
				if ($username->id != $event->usertodo->id) continue;	// We discard record if event is from another user than user we want to show

				$ponct=($event->date_start_in_calendar == $event->date_end_in_calendar);

				// Define $color and $cssclass of event
				$color=-1; $cssclass=''; $colorindex=-1;
				if ((! empty($event->author->id) && $event->author->id == $user->id)
					|| (! empty($event->usertodo->id) && $event->usertodo->id == $user->id)
					|| (! empty($event->userdone->id) && $event->userdone->id == $user->id))
				{
					$nummytasks++; $cssclass='family_mytasks';
				}
				else if ($event->type_code == 'ICALEVENT')
				{
					$numical++;
					if (! empty($event->icalname)) {
						if (! isset($numicals[dol_string_nospecial($event->icalname)])) {
							$numicals[dol_string_nospecial($event->icalname)] = 0;
						}
						$numicals[dol_string_nospecial($event->icalname)]++;
					}
					$color=$event->icalcolor;
					$cssclass=(! empty($event->icalname)?'family_'.dol_string_nospecial($event->icalname):'family_other unsortable');
				}
				else if ($event->type_code == 'BIRTHDAY')  {
					$numbirthday++; $colorindex=2; $cssclass='family_birthday unsortable'; $color=sprintf("%02x%02x%02x",$theme_datacolor[$colorindex][0],$theme_datacolor[$colorindex][1],$theme_datacolor[$colorindex][2]);
				}
				else { $numother++; $cssclass='family_other';
				}
				if ($color == -1)	// Color was not forced. Set color according to color index.
				{
					// Define color index if not yet defined
					$idusertouse=($event->usertodo->id?$event->usertodo->id:0);
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
					// Define color
					$color=sprintf("%02x%02x%02x",$theme_datacolor[$colorindex][0],$theme_datacolor[$colorindex][1],$theme_datacolor[$colorindex][2]);
				}
				//$cssclass=$cssclass.' '.$cssclass.'_day_'.$ymd;

				// Define all rects with event (cases1 is first half hour, cases2 is second half hour)
				for ($h = $begin_h; $h < $end_h; $h++)
				{
					$color = ''; //init
					if (empty($event->fulldayevent))
					{
						$a = dol_mktime((int) $h,0,0,$month,$day,$year,false,false);
						$b = dol_mktime((int) $h,30,0,$month,$day,$year,false,false);
						$c = dol_mktime((int) $h+1,0,0,$month,$day,$year,false,false);

						$dateendtouse=$event->date_end_in_calendar;
						if ($dateendtouse==$event->date_start_in_calendar) $dateendtouse++;

						if ($event->date_start_in_calendar < $b && $dateendtouse > $a)
						{
							$busy=$event->transparency;
							$cases1[$h][$event->id]['busy']=$busy;
							$cases1[$h][$event->id]['string']=dol_print_date($event->date_start_in_calendar,'dayhour').' - '.dol_print_date($event->date_end_in_calendar,'dayhour').' - '.$event->label;
							$cases1[$h][$event->id]['typecode']=$event->type_code;
							if ($event->socid)
							{
								$cases1[$h][$event->id]['string'].='xxx';
							}
						}
						if ($event->date_start_in_calendar < $c && $dateendtouse > $b)
						{
							$busy=$event->transparency;
							$cases2[$h][$event->id]['busy']=$busy;
							$cases2[$h][$event->id]['string']=dol_print_date($event->date_start_in_calendar,'dayhour').' - '.dol_print_date($event->date_end_in_calendar,'dayhour').' - '.$event->label;
							$cases1[$h][$event->id]['typecode']=$event->type_code;
							if ($event->socid)
							{
								$cases2[$h][$event->id]['string'].='xxx';
							}
						}
					}
					else
					{
						$busy=$event->transparency;
						$cases1[$h][$event->id]['busy']=$busy;
						$cases2[$h][$event->id]['busy']=$busy;
						$cases1[$h][$event->id]['string']=$event->label;
						$cases2[$h][$event->id]['string']=$event->label;
						$cases1[$h][$event->id]['typecode']=$event->type_code;
						$cases2[$h][$event->id]['typecode']=$event->type_code;
						break;
					}
				}
				$i++;
			}

			break;
		}
	}

	for ($h = $begin_h; $h < $end_h; $h++)
	{
		$style1='';$style2='';
		$string1='&nbsp;';$string2='&nbsp;';
		$title1='';$title2='';
		if (isset($cases1[$h]) && $cases1[$h] != '')
		{
			$title1=count($cases1[$h]).' '.(count($cases1[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			$string1='&nbsp;';
			$style1='peruser_notbusy';
			foreach($cases1[$h] as $id => $ev)
			{
				if ($ev['busy']) $style1='peruser_busy';
			}
		}
		if (isset($cases2[$h]) && $cases2[$h] != '')
		{
			$title2=count($cases2[$h]).' '.(count($cases2[$h])==1?$langs->trans("Event"):$langs->trans("Events"));
			$string2='&nbsp;';
			$style2='peruser_notbusy';
			foreach($cases2[$h] as $id => $ev)
			{
				if ($ev['busy']) $style2='peruser_busy';
			}
		}


		if ($h == $begin_h) echo '<td class="'.$style.'_peruserleft cal_peruser">';
		else echo '<td class="'.$style.' cal_peruser">';
		if (count($cases1[$h]) == 1)	// 1 seul evenement
		{
			$ids=array_keys($cases1[$h]);
			$output = array_slice($cases1[$h], 0, 1);
			if ($output[0]['string']) $title1.=' - '.$output[0]['string'];
		}
		if (count($cases2[$h]) == 1)	// 1 seul evenement
		{
			$ids=array_keys($cases2[$h]);
			$output = array_slice($cases2[$h], 0, 1);
			if ($output[0]['string']) $title2.=' - '.$output[0]['string'];
		}
		$ids1=join(',',array_keys($cases1[$h]));
		$ids2=join(',',array_keys($cases2[$h]));
		//var_dump($cases1[$h]);
		print '<table class="nobordernopadding" width="100%">';
		print '<tr><td class="'.($style1?$style1.' ':'').'onclickopenref'.($title1?' cursorpointer':'').'" ref="ref_'.$username->id.'_'.sprintf("%04d",$year).'_'.sprintf("%02d",$month).'_'.sprintf("%02d",$day).'_'.sprintf("%02d",$h).'_00_'.($ids1?$ids1:'none').'"'.($title1?' title="'.$title1.'"':'').'>';
		print $string1;
		print '</td><td class="'.($style2?$style2.' ':'').'onclickopenref'.($title1?' cursorpointer':'').'" ref="ref_'.$username->id.'_'.sprintf("%04d",$year).'_'.sprintf("%02d",$month).'_'.sprintf("%02d",$day).'_'.sprintf("%02d",$h).'_30_'.($ids2?$ids2:'none').'"'.($title2?' title="'.$title2.'"':'').'>';
		print $string2;
		print '</td></tr>';
		print '</table>';
		print '</td>';
	}
}
