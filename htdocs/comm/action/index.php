<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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

$page = $_GET["page"];
$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="a.datea";

// Sécurité accés client
if ($user->societe_id > 0) 
{
	$action = '';
	$socid = $user->societe_id;
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
$nav ="<a href=\"?year=".$prev_year."&amp;month=".$prev_month."&amp;region=".$region."\">".img_previous($langs->trans("Previous"))."</a>\n";
$nav.=" <span id=\"month_name\">".dolibarr_print_date(dolibarr_mktime(0,0,0,$month,1,$year),"%b");
$nav.=" <a href=\"?year=".$year."&amp;region=".$region."\">".$year."</a>"."</span>\n";
$nav.=" <a href=\"?year=".$next_year."&amp;month=".$next_month."&amp;region=".$region."\">".img_next($langs->trans("Next"))."</a>\n";
print_fiche_titre($langs->trans("Calendar"),$nav,'');

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
			echo '  <td class="'.$style.'" width="14%">';
			show_day_events ($db, $max_day_in_prev_month + $day, $prev_month, $prev_year, $style);
			echo "  </td>\n";
		}
		/* Show days of the current month */
		elseif(($day <= $max_day_in_month))
		{
			$curtime = dolibarr_mktime (0, 0, 0, $month, $day, $year);

			if ($curtime < $now)
			$style='cal_past_month';
			else if($curtime == $now)
			$style='cal_today';
			else
			$style='cal_current_month';
			
			echo '  <td class="'.$style.'" width="14%">';
			show_day_events ($db, $day, $month, $year, $style);
			echo "  </td>\n";
		}
		/* Show days after the current month (next month) */
		else
		{
			$style='cal_other_month';
			echo '  <td class="'.$style.'" width="14%">';
			show_day_events ($db, $day - $max_day_in_month, $next_month, $next_year, $style);
			echo "</td>\n";
		}
		$day++;
	}
	echo " </tr>\n";
}
echo "</table>\n";


$db->close();

llxFooter('$Date$ - $Revision$');

function show_day_events($db, $day, $month, $year, $style)
{
	print '<table class="border" width="100%">';
	print '<tr><td align="left">'.$day.'</td></tr>';
	print '<tr height="60"><td>&nbsp;</td></tr>';
	print '</table>';
}

?>
