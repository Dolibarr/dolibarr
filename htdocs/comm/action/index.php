<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/action/index.php
        \ingroup    agenda
		\brief      Page accueil des rapports des actions
		\version    $Id$
*/

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/actioncomm.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/date.lib.php");

$filtera = isset($_REQUEST["userasked"])?$_REQUEST["userasked"]:(isset($_REQUEST["filtera"])?$_REQUEST["filtera"]:'');
$filtert = isset($_REQUEST["usertodo"])?$_REQUEST["usertodo"]:(isset($_REQUEST["filtert"])?$_REQUEST["filtert"]:'');
$filterd = isset($_REQUEST["userdone"])?$_REQUEST["userdone"]:(isset($_REQUEST["filterd"])?$_REQUEST["filterd"]:'');

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
$result = restrictedArea($user, 'societe', $socid,'');

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
 * Affichage liste
 */

llxHeader();

$form=new Form($db);

//print $langs->trans("FeatureNotYetAvailable");
$now=mktime(0,0,0);

$prev = dol_get_prev_month($month, $year);
$prev_year  = $prev['year'];
$prev_month = $prev['month'];

$next = dol_get_next_month($month, $year);
$next_year  = $next['year'];
$next_month = $next['month'];

$max_day_in_prev_month = date("t",dolibarr_mktime(0,0,0,$prev_month,1,$prev_year));
$max_day_in_month = date("t",dolibarr_mktime(0,0,0,$month,1,$year));
$day = -date("w",dolibarr_mktime(0,0,0,$month,1,$year))+2;
if ($day > 1) $day -= 7;
$firstdaytoshow=dolibarr_mktime(0,0,0,$prev_month,$max_day_in_prev_month+$day,$prev_year);
$next_day=7-($max_day_in_month+1-$day)%7;
if ($next_day < 6) $next_day+=7;
$lastdaytoshow=dolibarr_mktime(0,0,0,$next_month,$next_day,$next_year);
//print dolibarr_print_date($firstdaytoshow,'day');
//print dolibarr_print_date($lastdaytoshow,'day');

$title=$langs->trans("DoneAndToDoActions");
if ($status == 'done') $title=$langs->trans("DoneActions");
if ($status == 'todo') $title=$langs->trans("ToDoActions");

$param='';
if ($status) $param="&status=".$status;
if ($filter) $param.="&filter=".$filter;
if ($filtera) $param.="&filtera=".$filtera;
if ($filtert) $param.="&filtert=".$filtert;
if ($filterd) $param.="&filterd=".$filterd;
if ($time) $param.="&time=".$_REQUEST["time"];
if ($socid) $param.="&socid=".$_REQUEST["socid"];
if ($_GET["type"]) $param.="&type=".$_REQUEST["type"];

// Show navigation bar
$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;region=".$region.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
$nav.=" <span id=\"month_name\">".dolibarr_print_date(dolibarr_mktime(0,0,0,$month,1,$year),"%b");
$nav.=" $year";
$nav.=" </span>\n";
$nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;region=".$region.$param."\">".img_next($langs->trans("Next"))."</a>\n";

print_fiche_titre($title,$nav,"");

// Filters
if ($canedit)
{
	print '<form name="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="status" value="'.$status.'">';
	print '<input type="hidden" name="time" value="'.$_REQUEST["time"].'">';
	print '<input type="hidden" name="year" value="'.$year.'">';
	print '<input type="hidden" name="month" value="'.$month.'">';
	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td>';
	//print '<input type="checkbox" name="userasked" '.($canedit?'':'disabled="true" ').($filtera?'checked="true"':'').'> ';
	print $langs->trans("ActionsAskedBy");
	print '</td><td>';
	print $form->select_users($filtera,'userasked',1,'',!$canedit);
	print '</td>';
	print '<td rowspan="3" align="center" valign="middle">';
	print img_picto($langs->trans("ViewList"),'object_list').' <input type="submit" class="button" name="viewlist" value="'.$langs->trans("ViewList").'" '.($canedit?'':'disabled="true"') .'>';
	print '<br>';
	print '<br>';
	print img_picto($langs->trans("ViewCal"),'object_calendar').' <input type="submit" class="button" name="viewcal" value="'.$langs->trans("ViewCal").'" '.($canedit?'':'disabled="true"') .'>';
	print '</td>';
	print '</tr>';

	print '<tr>';
	print '<td>';
	//print '<input type="checkbox" name="usertodo" '.($canedit?'':'disabled="true" ').($filtert?'checked="true"':'').'> ';
	print $langs->trans("ActionsToDoBy");
	print '</td><td>';
	print $form->select_users($filtert,'usertodo',1,'',!$canedit);
	print '</td></tr>';
	
	print '<tr>';
	print '<td>';
	//print '<input type="checkbox" name="userdone" '.($canedit?'':'disabled="true" ').($filterd?'checked="true"':'').'> ';
	print $langs->trans("ActionsDoneBy");
	print '</td><td>';
	print $form->select_users($filterd,'userdone',1,'',!$canedit);
	print '</td></tr>';

	print '</table>';
	print '</form><br>';
}


// Get event in an array
$sql = 'SELECT a.id,a.label,';
$sql.= ' '.$db->pdate('a.datep').' as datep,';
$sql.= ' '.$db->pdate('a.datep2').' as datep2,';
$sql.= ' '.$db->pdate('a.datea').' as datea,';
$sql.= ' '.$db->pdate('a.datea2').' as datea2,';
$sql.= ' a.percent,';
$sql.= ' a.fk_user_author,a.fk_user_action,a.fk_user_done';
$sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';	
$sql.= ' WHERE 1=1';
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
// \TODO Add filters on dates

//echo "$sql<br>";
$actionarray=array();
$resql=$db->query($sql); 	
if ($resql)
{
	$num = $db->num_rows($resql);
	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$action=new ActionComm($db);
		$action->id=$obj->id;
		$action->datep=$obj->datep;
		$action->datef=$obj->datep2;
		//$action->date=$obj->datea;
		//$action->dateend=$obj->datea2;
		$action->libelle=$obj->label;
		$action->percentage=$obj->percent;
		$action->author->id=$obj->fk_user_author;
		$action->usertodo->id=$obj->fk_user_action;
		$action->userdone->id=$obj->fk_user_done;
		
		// Defined date_start_in_calendar and date_end_in_calendar property
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
		// Define ponctuel property
		if ($action->date_start_in_calendar == $action->date_end_in_calendar)
		{
			$action->ponctuel=1;
		}

		// Add an entry in actionarray for each day
		// \TODO
		$daycursor=$action->date_start_in_calendar;
		$annee = date('Y',$daycursor);
		$mois = date('m',$daycursor);
		$jour = date('d',$daycursor);
		$daykey=dolibarr_mktime(0,0,0,$mois,$jour,$annee);
		$loop=true;
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
	dolibarr_print_error($db);
}

// Define theme_datacolor array
$color_file = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/graph-color.php";
if (is_readable($color_file))
{
	include_once($color_file);
}
if (! is_array($theme_datacolor)) $theme_datacolor=array(array(120,130,150), array(200,160,180), array(190,190,220));

echo '<table width="100%" class="nocellnopadd">';
echo ' <tr class="liste_titre">';
echo '  <td align="center">'.$langs->trans("Monday")."</td>\n";
echo '  <td align="center">'.$langs->trans("Tuesday")."</td>\n";
echo '  <td align="center">'.$langs->trans("Wednesday")."</td>\n";
echo '  <td align="center">'.$langs->trans("Thirday")."</td>\n";
echo '  <td align="center">'.$langs->trans("Friday")."</td>\n";
echo '  <td align="center">'.$langs->trans("Saturday")."</td>\n";
echo '  <td align="center">'.$langs->trans("Sunday")."</td>\n";
echo " </tr>\n";

for($iter_week = 0; $iter_week < 6 ; $iter_week++)
{
	echo " <tr>\n";
	for($iter_day = 0; $iter_day < 7; $iter_day++)
	{
		/* Show days before the beginning of the current month
		(previous month)  */
		if($day <= 0)
		{
			$style='cal_other_month';
			echo '  <td class="'.$style.'" width="14%" valign="top">';
			show_day_events ($db, $max_day_in_prev_month + $day, $prev_month, $prev_year, $style, $actionarray);
			echo "  </td>\n";
		}
		/* Show days of the current month */
		elseif(($day <= $max_day_in_month))
		{
			$curtime = dolibarr_mktime (0, 0, 0, $month, $day, $year);

			if ($curtime < $now)
			$style='cal_current_month';
			else if($curtime == $now)
			$style='cal_today';
			else
			$style='cal_current_month';
			
			echo '  <td class="'.$style.'" width="14%" valign="top">';
			show_day_events ($db, $day, $month, $year, $style, $actionarray);
			echo "  </td>\n";
		}
		/* Show days after the current month (next month) */
		else
		{
			$style='cal_other_month';
			echo '  <td class="'.$style.'" width="14%" valign="top">';
			show_day_events ($db, $day - $max_day_in_month, $next_month, $next_year, $style, $actionarray);
			echo "</td>\n";
		}
		$day++;
	}
	echo " </tr>\n";
}
echo "</table>\n";


$db->close();

llxFooter('$Date$ - $Revision$');



/**	\brief	Show event of a particular day
*/
function show_day_events($db, $day, $month, $year, $style, $actionarray)
{
	global $user, $conf, $langs;
	global $filtera, $filtert, $filted;
	global $theme_datacolor;
	
	$curtime = dolibarr_mktime (0, 0, 0, $month, $day, $year);

	print '<table class="nobordernopadding" width="100%">';
	print '<tr style="background: #EEEEEE"><td align="left">';
	print dolibarr_print_date($curtime,'%a %d');
	print '</td><td align="right">';
	print '<a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&datep='.sprintf("%04d%02d%02d",$year,$month,$day).'">';
	print img_picto($langs->trans("NewAction"),'edit_add.png');
	print '</a>';
	print '</td></tr>';
	print '<tr height="60"><td valign="top" colspan="2">';	// Minimum 60px height

	//$curtime = dolibarr_mktime (0, 0, 0, $month, $day, $year);
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
		   		$ponct=($action->date_start_in_calendar == $action->date_end_in_calendar);
				// Show rect of event
				$colorindex=0;
				if ($action->author->id == $user->id || $action->usertodo->id == $user->id || $action->userdone->id == $user->id) $colorindex=1;
				$color=sprintf("%02x%02x%02x",$theme_datacolor[$colorindex][0],$theme_datacolor[$colorindex][1],$theme_datacolor[$colorindex][2]);
				//print "x".$color;
				print '<table class="cal_event" style="background: #'.$color.'; -moz-border-radius:4px; " width="100%"><tr>';
				print '<td>';
				$tmpyearstart  = date('Y',$action->date_start_in_calendar);
				$tmpmonthstart = date('m',$action->date_start_in_calendar);
				$tmpdaystart   = date('d',$action->date_start_in_calendar);
				$tmpyearend    = date('Y',$action->date_end_in_calendar);
				$tmpmonthend   = date('m',$action->date_end_in_calendar);
				$tmpdayend     = date('d',$action->date_end_in_calendar);
				// Hour start
				if ($tmpyearstart == $annee && $tmpmonthstart == $mois && $tmpdaystart == $jour)
				{
					print dolibarr_print_date($action->date_start_in_calendar,'%H:%M');
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
						print dolibarr_print_date($action->date_end_in_calendar,'%H:%M');
				}
				print '<br>';
				print $action->getNomUrl(0,14,'cal_event');
				print '</td>';
				print '<td align="right">'.$action->getLibStatut(3);
				print '</td></tr></table>';
				$i++;
			}
		}
	}
	if (! $i) print '&nbsp;';
	print '</td></tr>';
	print '</table>';
}

?>
