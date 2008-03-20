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

$filtera = isset($_REQUEST["userasked"])?$_REQUEST["userasked"]:'';
$filtert = isset($_REQUEST["usertodo"])?$_REQUEST["usertodo"]:'';
$filterd = isset($_REQUEST["userdone"])?$_REQUEST["userdone"]:'';

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datea";

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid,'');

$canedit=1;
if (! $user->rights->agenda->myactions->read) access_forbidden();
if (! $user->rights->agenda->allactions->read) $canedit=0;
if (! $user->rights->agenda->allactions->read || $_GET["filter"]=='mine')
{
	$filtera=$user->id;
	$filtert=$user->id;
	$filterd=$user->id;
}

$year=isset($_GET["year"])?$_GET["year"]:date("Y");
$month=isset($_GET["month"])?$_GET["month"]:date("m");


/*
 * Actions
 */
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

$max_day_in_month = date("t",dolibarr_mktime(0,0,0,$month,1,$year));
$max_day_in_prev_month = date("t",dolibarr_mktime(0,0,0,$prev_month,1,$prev_year));

$day = -date("w",dolibarr_mktime(0,0,0,$month,1,$year))+2;
if ($day > 1) $day -= 7;

// Show navigation bar
$param='&amp;userasked='.$fitlera.'&amp;usertodo='.$filtert.'&amp;userdone='.$filterd;
$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;region=".$region.$param."\">".img_previous($langs->trans("Previous"))."</a>\n";
$nav.=" <span id=\"month_name\">".dolibarr_print_date(dolibarr_mktime(0,0,0,$month,1,$year),"%b");
$nav.=" $year";
$nav.=" </span>\n";
$nav.="<a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;region=".$region.$param."\">".img_next($langs->trans("Next"))."</a>\n";
print_fiche_titre($langs->trans("Calendar"),$nav,'');

// Filters
if ($canedit)
{
	print '<form name="listactionsfilter" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="status" value="'.$status.'">';
	print '<input type="hidden" name="time" value="'.$_REQUEST["time"].'">';
	print '<table class="border" width="100%">';
	print '<tr>';
	print '<td>';
	//print '<input type="checkbox" name="userasked" '.($canedit?'':'disabled="true" ').($filtera?'checked="true"':'').'> ';
	print $langs->trans("ActionsAskedBy");
	print '</td><td>';
	print $form->select_users($filtera,'userasked',1,'',!$canedit);
	print '</td>';
	print '<td rowspan="3" align="center" valign="middle">';
	print '<input type="submit" class="button" value="'.$langs->trans("Search").'" '.($canedit?'':'disabled="true"') .'>';
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
$sql.= ' a.percent,a.fk_user_author,a.fk_user_action,a.fk_user_done';
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
		$action->date=$obj->datea;
		$action->dateend=$obj->datea2;
		$action->libelle=$obj->label;
		$action->percentage=$obj->percent;

		if ($action->percentage <= 0)
		{
			$action->date_to_show_in_calendar=$action->datep;
			// Add days until datep2
		}
		else if ($action->percentage > 0)
		{
			$action->date_to_show_in_calendar=$action->date;
			// Add days until dateend

		}

		//var_dump($action);
		$actionarray[]=$action;
		$i++;
	}
	//echo $num;
}
else
{
	dolibarr_print_error($db);
}

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
	global $filtera, $filtert, $filted;
	
	$curtime = dolibarr_mktime (0, 0, 0, $month, $day, $year);

	print '<table class="noborder" width="100%">';
	print '<tr style="border-bottom: solid 1px #AAAAAA;"><td align="left">'.dolibarr_print_date($curtime,'%a %d').'</td></tr>';
	print '<tr height="60"><td valign="top">';

	//$curtime = dolibarr_mktime (0, 0, 0, $month, $day, $year);
	$i=0;
	foreach ($actionarray as $action)
	{
		$annee = date('Y',$action->date_to_show_in_calendar);
		$mois = date('m',$action->date_to_show_in_calendar);
		$jour = date('d',$action->date_to_show_in_calendar);
		if ($day==$jour && $month==$mois && $year==$annee)
		{
			if ($i) print "<br>";
	   		print $action->getNomUrl(1,10)." ".$action->getLibStatut(3);
			$i++;
		}
	}
	print '&nbsp;</td></tr>';
	print '</table>';
}

?>
