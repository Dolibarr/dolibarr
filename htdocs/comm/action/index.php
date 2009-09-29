<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/comm/action/index.php
 *	\ingroup    agenda
 *	\brief      Page accueil des rapports des actions
 *	\version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");

$filtera = isset($_REQUEST["userasked"])?$_REQUEST["userasked"]:(isset($_REQUEST["filtera"])?$_REQUEST["filtera"]:'');
$filtert = isset($_REQUEST["usertodo"])?$_REQUEST["usertodo"]:(isset($_REQUEST["filtert"])?$_REQUEST["filtert"]:'');
$filterd = isset($_REQUEST["userdone"])?$_REQUEST["userdone"]:(isset($_REQUEST["filterd"])?$_REQUEST["filterd"]:'');
$showbirthday = isset($_REQUEST["showbirthday"])?$_REQUEST["showbirthday"]:0;

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="a.datec";

$status=isset($_GET["status"])?$_GET["status"]:$_POST["status"];

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'agenda', $socid, '', 'myactions');

$canedit=1;
if (! $user->rights->agenda->myactions->read) accessforbidden();
if (! $user->rights->agenda->allactions->read) $canedit=0;
if (! $user->rights->agenda->allactions->read || $_GET["filter"]=='mine')
{
	$filtera=$user->id;
	$filtert=$user->id;
	$filterd=$user->id;
}

$year=isset($_REQUEST["year"])?$_REQUEST["year"]:date("Y");
$month=isset($_REQUEST["month"])?$_REQUEST["month"]:date("m");
$day=isset($_REQUEST["day"])?$_REQUEST["day"]:0;

$langs->load("other");

if (! isset($conf->global->AGENDA_MAX_EVENTS_DAY_VIEW)) $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW=3;


/*
 * Actions
 */
if (! empty($_POST["viewlist"]))
{
	$param='';
	foreach($_POST as $key => $val)
	{
		$param.='&'.$key.'='.urlencode($val);
	}
	//print $param;
	header("Location: ".DOL_URL_ROOT.'/comm/action/listactions.php?'.$param);
	exit;
}
if ($_GET["action"] == 'builddoc')
{
	$cat = new CommActionRapport($db, $_GET["month"], $_GET["year"]);
	$result=$cat->generate($_GET["id"]);
}

if ($action=='delete_action')
{
	$actioncomm = new ActionComm($db);
	$actioncomm->fetch($actionid);
	$result=$actioncomm->delete();
}



/*
 * View
 */

$help_url='EN:Module_Agenda_En|FR:Module_Agenda|ES:M&omodulodulo_Agenda';
llxHeader('',$langs->trans("Agenda"),$help_url);

$form=new Form($db);

//print $langs->trans("FeatureNotYetAvailable");
$now=dol_now('tzref');

$prev = dol_get_prev_month($month, $year);
$prev_year  = $prev['year'];
$prev_month = $prev['month'];

$next = dol_get_next_month($month, $year);
$next_year  = $next['year'];
$next_month = $next['month'];

$max_day_in_prev_month = date("t",dol_mktime(0,0,0,$prev_month,1,$prev_year));	// Nb of days in previous month
$max_day_in_month = date("t",dol_mktime(0,0,0,$month,1,$year));					// Nb of days in next month
// tmpday is a negative or null cursor to know how many days before the 1 to show on month view (if tmpday=0 we start on monday)
$tmpday = -date("w",dol_mktime(0,0,0,$month,1,$year))+2;
$tmpday+=((isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:1)-1);
if ($tmpday >= 1) $tmpday -= 7;
// Define firstdaytoshow and lastdaytoshow
$firstdaytoshow=dol_mktime(0,0,0,$prev_month,$max_day_in_prev_month+$tmpday,$prev_year);
$next_day=7-($max_day_in_month+1-$tmpday)%7;
if ($next_day < 6) $next_day+=7;
$lastdaytoshow=dol_mktime(0,0,0,$next_month,$next_day,$next_year);
//print dol_print_date($firstdaytoshow,'day');
//print dol_print_date($lastdaytoshow,'day');

$title=$langs->trans("Calendar");
if ($status == 'done') $title=$langs->trans("DoneActions");
if ($status == 'todo') $title=$langs->trans("ToDoActions");

$param='';
if ($status)  $param="&status=".$status;
if ($filter)  $param.="&filter=".$filter;
if ($filtera) $param.="&filtera=".$filtera;
if ($filtert) $param.="&filtert=".$filtert;
if ($filterd) $param.="&filterd=".$filterd;
if ($time)    $param.="&time=".$_REQUEST["time"];
if ($socid)   $param.="&socid=".$_REQUEST["socid"];
if ($showbirthday) $param.="&showbirthday=1";
if (! empty($_REQUEST["type"]))   $param.="&type=".$_REQUEST["type"];

// Show navigation bar
$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;region=".$region.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
$nav.=" <span id=\"month_name\">".dol_print_date(dol_mktime(0,0,0,$month,1,$year),"%b");
$nav.=" ".$year;
$nav.=" </span>\n";
$nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;region=".$region.$param."\">".img_next($langs->trans("Next"))."</a>\n";

// Must be after the nav definition
$param.='&year='.$year.'&month='.$month.($day?'&day='.$day:'');

print_fiche_titre($title,$nav);

// Filters
if ($canedit)
{
	print '<form name="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="status" value="'.$status.'">';
	print '<input type="hidden" name="time" value="'.$_REQUEST["time"].'">';
	print '<input type="hidden" name="year" value="'.$year.'">';
	print '<input type="hidden" name="month" value="'.$month.'">';
	print '<input type="hidden" name="day" value="'.$day.'">';
	print '<input type="hidden" name="showbirthday" value="'.$showbirthday.'">';
	print '<input type="hidden" name="action" value="'.$_REQUEST['action'].'">';
	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td nowrap="nowrap">';
	//print '<input type="checkbox" name="userasked" '.($canedit?'':'disabled="true" ').($filtera?'checked="true"':'').'> ';
	print $langs->trans("ActionsAskedBy");
	print '</td><td nowrap="nowrap">';
	print $form->select_users($filtera,'userasked',1,'',!$canedit);
	print '</td>';
	print '<td rowspan="3" align="center" valign="middle" nowrap="nowrap">';
	print img_picto($langs->trans("ViewList"),'object_list').' <input type="submit" class="button" name="viewlist" value="'.$langs->trans("ViewList").'">';
	print '<br>';
	print '<br>';
	print img_picto($langs->trans("ViewCal"),'object_calendar').' <input type="submit" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'">';
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td nowrap="nowrap">';
	//print '<input type="checkbox" name="usertodo" '.($canedit?'':'disabled="true" ').($filtert?'checked="true"':'').'> ';
	print $langs->trans("ActionsToDoBy");
	print '</td><td nowrap="nowrap">';
	print $form->select_users($filtert,'usertodo',1,'',!$canedit);
	print '</td></tr>';

	print '<tr>';
	print '<td nowrap="nowrap">';
	//print '<input type="checkbox" name="userdone" '.($canedit?'':'disabled="true" ').($filterd?'checked="true"':'').'> ';
	print $langs->trans("ActionsDoneBy");
	print '</td><td nowrap="nowrap">';
	print $form->select_users($filterd,'userdone',1,'',!$canedit);
	print '</td></tr>';

	print '</table>';
	print '</form><br>';
}


// Get event in an array
$actionarray=array();

$sql = 'SELECT a.id,a.label,';
$sql.= ' '.$db->pdate('a.datep').' as datep,';
$sql.= ' '.$db->pdate('a.datep2').' as datep2,';
$sql.= ' '.$db->pdate('a.datea').' as datea,';
$sql.= ' '.$db->pdate('a.datea2').' as datea2,';
$sql.= ' a.percent,';
$sql.= ' a.fk_user_author,a.fk_user_action,a.fk_user_done,';
$sql.= ' ca.code';
$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
$sql.= ', '.MAIN_DB_PREFIX.'c_actioncomm as ca';
$sql.= ', '.MAIN_DB_PREFIX.'user as u';
$sql.= ' WHERE a.fk_action = ca.id';
$sql.= ' AND a.fk_user_author = u.rowid';
$sql.= ' AND u.entity in (0,'.$conf->entity.')';
if ($_GET["action"] == 'show_day')
{
	$sql.= ' AND (';
	$sql.= ' (datep BETWEEN '.$db->idate(dol_mktime(0,0,0,$month,$_GET["day"],$year));
	$sql.= ' AND '.$db->idate(dol_mktime(23,59,59,$month,$_GET["day"],$year)).')';
	$sql.= ' OR ';
	$sql.= ' (datep2 BETWEEN '.$db->idate(dol_mktime(0,0,0,$month,$_GET["day"],$year));
	$sql.= ' AND '.$db->idate(dol_mktime(23,59,59,$month,$_GET["day"],$year)).')';
	$sql.= ' OR ';
	$sql.= ' (datep < '.$db->idate(dol_mktime(0,0,0,$month,$_GET["day"],$year));
	$sql.= ' AND datep2 > '.$db->idate(dol_mktime(23,59,59,$month,$_GET["day"],$year)).')';
	$sql.= ')';
}
else
{
	// To limit array
	$sql.= ' AND (';
	$sql.= ' (datep BETWEEN '.$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7));	// Start 7 days before
    $sql.= ' AND '.$db->idate(dol_mktime(0,0,0,$month,28,$year)+(60*60*24*10)).')';			// End 7 days after + 3 to go from 28 to 31
	$sql.= ' OR ';
	$sql.= ' (datep2 BETWEEN '.$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7));
	$sql.= ' AND '.$db->idate(dol_mktime(0,0,0,$month,28,$year)+(60*60*24*10)).')';
	$sql.= ' OR ';
	$sql.= ' (datep < '.$db->idate(dol_mktime(0,0,0,$month,1,$year)-(60*60*24*7));
	$sql.= ' AND datep2 > '.$db->idate(dol_mktime(0,0,0,$month,28,$year)+(60*60*24*10)).')';
	$sql.= ')';
}
if ($filtera > 0 || $filtert > 0 || $filterd > 0)
{
	$sql.= " AND (";
	if ($filtera > 0) $sql.= " a.fk_user_author = ".$filtera;
	if ($filtert > 0) $sql.= ($filtera>0?" OR ":"")." a.fk_user_action = ".$filtert;
	if ($filterd > 0) $sql.= ($filtera>0||$filtert>0?" OR ":"")." a.fk_user_done = ".$filterd;
	$sql.= ")";
}
if ($status == 'done') { $sql.= " AND a.percent = 100"; }
if ($status == 'todo') { $sql.= " AND a.percent < 100"; }
// Sort on date
$sql.= ' ORDER BY datep';
dol_syslog("comm/action/index.php sql=".$sql, LOG_DEBUG);

//print $sql;
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i=0;
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		// Create a new object action
		$action=new ActionComm($db);
		$action->id=$obj->id;
		$action->datep=$obj->datep;
		$action->datef=$obj->datep2;
		$action->type_code=$obj->code;
		$action->libelle=$obj->label;
		$action->percentage=$obj->percent;
		$action->author->id=$obj->fk_user_author;
		$action->usertodo->id=$obj->fk_user_action;
		$action->userdone->id=$obj->fk_user_done;

		// Defined date_start_in_calendar and date_end_in_calendar property
		// They are date start and end of action but modified to not be outside
		// calendar view.
		if ($action->percentage <= 0)
		{
			$action->date_start_in_calendar=$action->datep;
			if ($action->datef != '' && $action->datef >= $action->datep) $action->date_end_in_calendar=$action->datef;
			else $action->date_end_in_calendar=$action->datep;
		}
		else
		{
			$action->date_start_in_calendar=$action->datep;
			if ($action->datef != '' && $action->datef >= $action->datep) $action->date_end_in_calendar=$action->datef;
			else $action->date_end_in_calendar=$action->datep;
		}
		// Define ponctual property
		if ($action->date_start_in_calendar == $action->date_end_in_calendar)
		{
			$action->ponctuel=1;
		}

		if ($action->date_start_in_calendar < $firstdaytoshow) $action->date_start_in_calendar=$firstdaytoshow;
		if ($action->date_end_in_calendar > $lastdaytoshow) $action->date_end_in_calendar=$lastdaytoshow;

		// Add an entry in actionarray for each day
		$daycursor=$action->date_start_in_calendar;
		$annee = date('Y',$daycursor);
		$mois = date('m',$daycursor);
		$jour = date('d',$daycursor);

		// Loop on each day covered by action to prepare an index to show on calendar
		$loop=true; $j=0;
		$daykey=dol_mktime(0,0,0,$mois,$jour,$annee);
		do
		{
			//if ($action->id==408) print 'daykey='.$daykey.' '.$action->datep.' '.$action->datef.'<br>';

			//if ($action->datef && $action->datef == $daykey && $action->datep < $action->datef)
			//{	// We discard such index. This means it's end of a range ending on last day + 1 at 00:00:00.
			//}
			//else
			//{
				$actionarray[$daykey][]=$action;
				$j++;
			//}
			$daykey+=60*60*24;
			if ($daykey > $action->date_end_in_calendar) $loop=false;
		}
		while ($loop);
		$i++;

		//print 'Event '.$i.' id='.$action->id.' (start='.dol_print_date($action->datep).'-end='.dol_print_date($action->datef).') was added in '.$j.' different index days in array<br>';
	}
}
else
{
	dol_print_error($db);
}

if ($showbirthday)
{
	// Add events in array
	$sql = 'SELECT sp.rowid, sp.name, sp.firstname, sp.birthday';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'socpeople as sp';
	$sql.= ' WHERE (priv=0 OR (priv=1 AND fk_user_creat='.$user->id.'))';
	$sql.= ' AND sp.entity = '.$conf->entity;
	if ($_GET["action"] == 'show_day')
	{
		$sql.= ' AND MONTH(birthday) = '.$month;
		$sql.= ' AND DAY(birthday) = '.$_GET["day"];
	}
	else
	{
		$sql.= ' AND MONTH(birthday) = '.$month;
	}
	// Sort on date
	$sql.= ' ORDER BY birthday';
	//print $sql;

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i=0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$action=new ActionComm($db);
			$action->id=$obj->rowid;	// We put contact id in action id for birthdays events
			$datebirth=dol_stringtotime($obj->birthday);
			//print 'ee'.$obj->birthday.'-'.$datebirth;
			$datearray=dol_getdate($datebirth,true);
			$action->datep=dol_mktime(0,0,0,$datearray['mon'],$datearray['mday'],$year);
			$action->datef=$action->datep;
			$action->type_code='BIRTHDAY';
			$action->libelle=$langs->trans("Birthday").' '.$obj->firstname.' '.$obj->name;
			$action->percentage=100;

			$action->date_start_in_calendar=$action->datep;
			$action->date_end_in_calendar=$action->datef;
			$action->ponctuel=0;

			// Add an entry in actionarray for each day
			$daycursor=$action->date_start_in_calendar;
			$annee = date('Y',$daycursor);
			$mois = date('m',$daycursor);
			$jour = date('d',$daycursor);

			$loop=true;
			$daykey=dol_mktime(0,0,0,$mois,$jour,$annee);
			do
			{
				$actionarray[$daykey][]=$action;
				$daykey+=60*60*24;
				if ($daykey > $action->date_end_in_calendar) $loop=false;
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

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
if (is_readable($color_file))
{
	include_once($color_file);
}
if (! is_array($theme_datacolor)) $theme_datacolor=array(array(120,130,150), array(200,160,180), array(190,190,220));

// Add link to show birthdays
$link='<a href="'.$_SERVER['PHP_SELF'];
$newparam=eregi_replace('showbirthday=[0-1]','showbirthday='.(empty($showbirthday)?1:0),$param);
if (! eregi('showbirthday=',$newparam)) $newparam.='&showbirthday=1';
if ($_REQUEST['action']) $newparam.='&action='.$_REQUEST['action'];
$link.='?'.$newparam;
$link.='">';
if (empty($showbirthday)) $link.=$langs->trans("AgendaShowBirthdayEvents");
else $link.=$langs->trans("AgendaHideBirthdayEvents");
$link.='</a>';
print_fiche_titre('',$link);

if ($_GET["action"] != 'show_day')		// View by month
{
	echo '<table width="100%" class="nocellnopadd">';
	echo ' <tr class="liste_titre">';
	$i=0;
	while ($i < 7)
	{
		echo '  <td align="center">'.$langs->trans("Day".(($i+$conf->global->MAIN_START_WEEK) % 7))."</td>\n";
		$i++;
	}
	echo " </tr>\n";

	// In loops, tmpday contains day nb in current month (can be negative for days of previous month)
	for($iter_week = 0; $iter_week < 6 ; $iter_week++)
	{
		echo " <tr>\n";
		for($iter_day = 0; $iter_day < 7; $iter_day++)
		{
			/* Show days before the beginning of the current month
			 (previous month)  */
			if($tmpday <= 0)
			{
				$style='cal_other_month';
				echo '  <td class="'.$style.'" width="14%" valign="top"  nowrap="nowrap">';
				show_day_events ($db, $max_day_in_prev_month + $tmpday, $prev_month, $prev_year, $month, $style, $actionarray, $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
				echo "  </td>\n";
			}
			/* Show days of the current month */
			elseif(($tmpday <= $max_day_in_month))
			{
				$curtime = dol_mktime (0, 0, 0, $month, $tmpday, $year);

				if ($curtime < $now)
				$style='cal_current_month';
				else if($curtime == $now)
				$style='cal_today';
				else
				$style='cal_current_month';

				echo '  <td class="'.$style.'" width="14%" valign="top"  nowrap="nowrap">';
				show_day_events($db, $tmpday, $month, $year, $month, $style, $actionarray, $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
				echo "  </td>\n";
			}
			/* Show days after the current month (next month) */
			else
			{
				$style='cal_other_month';
				echo '  <td class="'.$style.'" width="14%" valign="top"  nowrap="nowrap">';
				show_day_events($db, $tmpday - $max_day_in_month, $next_month, $next_year, $month, $style, $actionarray, $conf->global->AGENDA_MAX_EVENTS_DAY_VIEW);
				echo "</td>\n";
			}
			$tmpday++;
		}
		echo " </tr>\n";
	}
	echo "</table>\n";
}
else	// View by day
{
	// Code to show just one day
	$style='cal_current_month';
	$timestamp=dol_mktime(12,0,0,$month,$_GET["day"],$year);
	$arraytimestamp=adodb_getdate(dol_mktime(12,0,0,$month,$_GET["day"],$year));
	echo '<table width="100%" class="nocellnopadd">';
	echo ' <tr class="liste_titre">';
	echo '  <td align="center">'.$langs->trans("Day".$arraytimestamp['wday'])."</td>\n";
	echo " </tr>\n";
	echo " <tr>\n";
	echo '  <td class="'.$style.'" width="14%" valign="top"  nowrap="nowrap">';
	show_day_events ($db, $_GET["day"], $month, $year, $month, $style, $actionarray, 0, 0);
	echo "</td>\n";
	echo " </tr>\n";
	echo '</table>';
}


$db->close();


llxFooter('$Date$ - $Revision$');


/**
 * \brief	Show event of a particular day
 *
 * @param unknown_type $db				Database handler
 * @param unknown_type $day				Day
 * @param unknown_type $month			Month
 * @param unknown_type $year			Year
 * @param unknown_type $monthshown      Month shown in calendar view
 * @param unknown_type $style			Style to use for this day
 * @param unknown_type $actionarray		Array of actions
 * @param unknown_type $maxPrint		Nb of actions to show each day on month view (0 means non limit)
 * @param unknown_type nbofchartoshow	Nb of characters to show for event line
 */
function show_day_events($db, $day, $month, $year, $monthshown, $style, &$actionarray, $maxPrint=0, $nbofchartoshow=14)
{
	global $user, $conf, $langs;
	global $filtera, $filtert, $filted;
	global $theme_datacolor;
	if ($_GET["action"] == 'maxPrint')
	{
		$maxPrint=0;
	}
	$curtime = dol_mktime (0, 0, 0, $month, $day, $year);

	print '<table class="nobordernopadding" width="100%">';
	print '<tr style="background: #EEEEEE"><td align="left" nowrap="nowrap">';
	print '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&day='.str_pad($day, 2, "0", STR_PAD_LEFT).'&month='.$month.'&year='.$year.'">'.dol_print_date($curtime,'%a %d').'</a>';
	print '</td><td align="right" nowrap="nowrap">';
	if ($user->rights->agenda->myactions->create || $user->rights->agenda->allactions->create)
	{
		print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&datep='.sprintf("%04d%02d%02d",$year,$month,$day).'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">';
		print img_picto($langs->trans("NewAction"),'edit_add.png');
		print '</a>';
	}
	print '</td></tr>';
	print '<tr height="60"><td valign="top" colspan="2" nowrap="nowrap">';	// Minimum 60px height

	//$curtime = dol_mktime (0, 0, 0, $month, $day, $year);
	$i=0;

	foreach ($actionarray as $daykey => $notused)
	{
		$annee = date('Y',$daykey);
		$mois = date('m',$daykey);
		$jour = date('d',$daykey);
		if ($day==$jour && $month==$mois && $year==$annee)
		{
			foreach ($actionarray[$daykey] as $index => $action)
			{
				if ($i < $maxPrint || $maxPrint == 0)
				{
					$ponct=($action->date_start_in_calendar == $action->date_end_in_calendar);
					// Show rect of event
					$colorindex=0;
					if ($action->author->id == $user->id || $action->usertodo->id == $user->id || $action->userdone->id == $user->id) $colorindex=1;
					if ($action->type_code == 'BIRTHDAY') $colorindex=2;
					$color=sprintf("%02x%02x%02x",$theme_datacolor[$colorindex][0],$theme_datacolor[$colorindex][1],$theme_datacolor[$colorindex][2]);
					//print "x".$color;
					print '<table class="cal_event" style="background: #'.$color.'; -moz-border-radius:4px; " width="100%"><tr>';
					print '<td nowrap="nowrap">';
					if ($action->type_code != 'BIRTHDAY')
					{
						$tmpyearstart  = date('Y',$action->date_start_in_calendar);
						$tmpmonthstart = date('m',$action->date_start_in_calendar);
						$tmpdaystart   = date('d',$action->date_start_in_calendar);
						$tmpyearend    = date('Y',$action->date_end_in_calendar);
						$tmpmonthend   = date('m',$action->date_end_in_calendar);
						$tmpdayend     = date('d',$action->date_end_in_calendar);
						// Hour start
						if ($tmpyearstart == $annee && $tmpmonthstart == $mois && $tmpdaystart == $jour)
						{
							print dol_print_date($action->date_start_in_calendar,'%H:%M');
							if ($action->date_end_in_calendar && $action->date_start_in_calendar != $action->date_end_in_calendar)
							{
								if ($tmpyearstart == $tmpyearend && $tmpmonthstart == $tmpmonthend && $tmpdaystart == $tmpdayend)
								print '-';
								//else
								//print '...';
							}
						}
						if ($action->date_end_in_calendar && $action->date_start_in_calendar != $action->date_end_in_calendar)
						{
							if ($tmpyearstart != $tmpyearend || $tmpmonthstart != $tmpmonthend || $tmpdaystart != $tmpdayend)
							{
								print '...';
							}
						}
						// Hour end
						if ($action->date_end_in_calendar && $action->date_start_in_calendar != $action->date_end_in_calendar)
						{
							if ($tmpyearend == $annee && $tmpmonthend == $mois && $tmpdayend == $jour)
							print dol_print_date($action->date_end_in_calendar,'%H:%M');
						}
						print '<br>';
						print $action->getNomUrl(0,$nbofchartoshow,'cal_event');
					}
					else	// It's a birthday
					{
						print $action->getNomUrl(0,$nbofchartoshow,'cal_event','birthday');
					}
					print '</td>';
					print '<td align="right" nowrap="nowrap">';
					if ($action->type_code != 'BIRTHDAY') print $action->getLibStatut(3);
					else print '&nbsp;';
					print '</td></tr></table>';
					$i++;
				}
				else
				{
					print '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=maxPrint&month='.$monthshown.'&year='.$year.'">'.img_picto("all","1downarrow_selected.png").' ...';
					print ' +'.(sizeof($actionarray[$daykey])-$maxPrint);
					print '</a>';
					break;
					//$ok=false;		// To avoid to show twice the link
				}
			}
			break;
		}
	}
	if (! $i) print '&nbsp;';
	print '</td></tr>';
	print '</table>';
}

?>
