<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	    \file       htdocs/adherents/stats/index.php
 *      \ingroup    member
 *		\brief      Page of subscription members statistics
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherentstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$userid = GETPOST('userid', 'int'); if ($userid < 0) $userid = 0;
$socid = GETPOST('socid', 'int'); if ($socid < 0) $socid = 0;

// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
$result = restrictedArea($user, 'adherent', '', '', 'cotisation');

$year = strftime("%Y", time());
$startyear = $year - 2;
$endyear = $year;

// Load translation files required by the page
$langs->loadLangs(array("companies", "members"));


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("SubscriptionsStatistics");
llxHeader('', $title);

print load_fiche_titre($title, '', 'members');

$dir = $conf->adherent->dir_temp;

dol_mkdir($dir);

$stats = new AdherentStats($db, $socid, $userid);

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)


$filenamenb = $dir.'/subscriptionsnbinyear-'.$year.'.png';
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=memberstats&file=subscriptionsnbinyear-'.$year.'.png';


$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$px1->SetData($data);
	$i = $startyear;
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetMinValue(min(0, $px1->GetFloorMinValue()));
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("NbOfSubscriptions"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("NbOfSubscriptions"));

	$px1->draw($filenamenb, $fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir.'/subscriptionsamountinyear-'.$year.'.png';
$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=memberstats&file=subscriptionsamountinyear-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (!$mesg) {
	$px2->SetData($data);
	$i = $startyear;
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("AmountOfSubscriptions"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->mode = 'depth';
	$px2->SetTitle($langs->trans("AmountOfSubscriptions"));

	$px2->draw($filenameamount, $fileurlamount);
}


$head = member_stats_prepare_head($adh);

print dol_get_fiche_head($head, 'statssubscription', $langs->trans("Statistics"), -1, 'user');


print '<div class="fichecenter"><div class="fichethirdleft">';

// Show filter box
/*print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="border centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
print '<tr><td>'.$langs->trans("Member").'</td><td>';
print $form->select_company($id,'memberid','',1);
print '</td></tr>';
print '<tr><td>'.$langs->trans("User").'</td><td>';
print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td></tr>';
print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';
*/

// Show array
$data = $stats->getAllByYear();


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder">';
print '<tr class="liste_titre" height="24">';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("NbOfSubscriptions").'</td>';
print '<td class="right">'.$langs->trans("AmountTotal").'</td>';
print '<td class="right">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear = 0;
foreach ($data as $val) {
	$year = $val['year'];
	while ($oldyear > $year + 1) {	// If we have empty year
		$oldyear--;
		print '<tr class="oddeven" height="24">';
		print '<td class="center">';
		//print '<a href="month.php?year='.$oldyear.'&amp;mode='.$mode.'">';
		print $oldyear;
		//print '</a>';
		print '</td>';
		print '<td class="right">0</td>';
		print '<td class="right">0</td>';
		print '<td class="right">0</td>';
		print '</tr>';
	}
	print '<tr class="oddeven" height="24">';
	print '<td class="center">';
	//print '<a href="month.php?year='.$year.'">';
	print $year;
	//print '</a>';
	print '</td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right">'.price(price2num($val['total'], 'MT'), 1).'</td>';
	print '<td class="right">'.price(price2num($val['avg'], 'MT'), 1).'</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
print '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
if ($mesg) { print $mesg; } else {
	print $px1->show();
	print "<br>\n";
	print $px2->show();
}
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
