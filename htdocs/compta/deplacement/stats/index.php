<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/compta/deplacement/stats/index.php
 *  \ingroup    deplacement
 *  \brief      Page for statistics of module trips and expenses
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacementstats.class.php';

// Load translation files required by the page
$langs->loadLangs(array('trips', 'companies'));

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$userid = GETPOSTINT('userid'); if ($userid < 0) {
	$userid = 0;
}
$socid = GETPOSTINT('socid'); if ($socid < 0) {
	$socid = 0;
}
$id = GETPOSTINT('id');

// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'deplacement', $id, '');

// Other security check
$childids = $user->getAllChildIds();
$childids[] = $user->id;
if ($userid > 0) {
	if (!$user->hasRight('deplacement', 'readall') && !$user->hasRight('deplacement', 'lire_tous') && !in_array($userid, $childids)) {
		accessforbidden();
		exit;
	}
}

$nowyear = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$year = GETPOST('year') > 0 ? GETPOST('year') : $nowyear;
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

$mode = GETPOST("mode") ? GETPOST("mode") : 'customer';


/*
 * View
 */

$form = new Form($db);


llxHeader();

$title = $langs->trans("TripsAndExpensesStatistics");
$dir = $conf->deplacement->dir_temp;

print load_fiche_titre($title, $mesg);

dol_mkdir($dir);

$useridtofilter = $userid; // Filter from parameters
if (empty($useridtofilter)) {
	$useridtofilter = $childids;
	if ($user->hasRight('deplacement', 'readall') || $user->hasRight('deplacement', 'lire_tous')) {
		$useridtofilter = 0;
	}
}

$stats = new DeplacementStats($db, $socid, $useridtofilter);


// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
//print "$endyear, $startyear";
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);

$filenamenb = $dir."/tripsexpensesnbinyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=tripsexpensesstats&amp;file=tripsexpensesnbinyear-'.$year.'.png';

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$px1->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("Number"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("NumberByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir."/tripsexpensesamountinyear-".$year.".png";
$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=tripsexpensesstats&amp;file=tripsexpensesamountinyear-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (!$mesg) {
	$px2->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("Amount"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->mode = 'depth';
	$px2->SetTitle($langs->trans("AmountTotal"));

	$px2->draw($filenameamount, $fileurlamount);
}


$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

if (!$user->hasRight('societe', 'client', 'voir')) {
	$filename_avg = $dir.'/ordersaverage-'.$user->id.'-'.$year.'.png';
	if ($mode == 'customer') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$user->id.'-'.$year.'.png';
	}
	if ($mode == 'supplier') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$user->id.'-'.$year.'.png';
	}
} else {
	$filename_avg = $dir.'/ordersaverage-'.$year.'.png';
	if ($mode == 'customer') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$year.'.png';
	}
	if ($mode == 'supplier') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$year.'.png';
	}
}

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (!$mesg) {
	$px3->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px3->SetLegend($legend);
	$px3->SetYLabel($langs->trans("AmountAverage"));
	$px3->SetMaxValue($px3->GetCeilMaxValue());
	$px3->SetMinValue($px3->GetFloorMinValue());
	$px3->SetWidth($WIDTH);
	$px3->SetHeight($HEIGHT);
	$px3->SetShading(3);
	$px3->SetHorizTickIncrement(1);
	$px3->mode = 'depth';
	$px3->SetTitle($langs->trans("AmountAverage"));

	$px3->draw($filename_avg, $fileurl_avg);
}


// Show array
$data = $stats->getAllByYear();
$arrayyears = array();
foreach ($data as $val) {
	$arrayyears[$val['year']] = $val['year'];
}
if (!count($arrayyears)) {
	$arrayyears[$nowyear] = $nowyear;
}


$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/compta/deplacement/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

complete_head_from_modules($conf, $langs, null, $head, $h, 'trip_stats');

print dol_get_fiche_head($head, 'byyear', '', -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<table class="border centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
print img_picto('', 'company', 'class="pictofixedwidth"');
print $form->select_company($socid, 'socid', '', 1, 1, 0, array(), 0, 'widthcentpercentminusx maxwidth300', '');
print '</td></tr>';
// User
print '<tr><td>'.$langs->trans("User").'</td><td>';
$include = '';
if (!$user->hasRight('deplacement', 'readall') && !$user->hasRight('deplacement', 'lire_tous')) {
	$include = 'hierarchy';
}
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userid, 'userid', 1, '', 0, $include, '', 0, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
print '</td></tr>';
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
if (!in_array($year, $arrayyears)) {
	$arrayyears[$year] = $year;
}
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0, 0, 0, '', 0, 0, 0, '', 'width75');
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="border centpercent">';
print '<tr>';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="center">'.$langs->trans("Number").'</td>';
print '<td class="center">'.$langs->trans("AmountTotal").'</td>';
print '<td class="center">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear = 0;
foreach ($data as $val) {
	$year = $val['year'];
	while ($year && $oldyear > $year + 1) {	// If we have empty year
		$oldyear--;
		print '<tr>';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.'">'.$oldyear.'</a></td>';
		print '<td class="right">0</td>';
		print '<td class="right">0</td>';
		print '<td class="right">0</td>';
		print '</tr>';
	}

	// Total
	print '<tr>';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right">'.price(price2num($val['total'], 'MT'), 1).'</td>';
	print '<td class="right">'.price(price2num($val['avg'], 'MT'), 1).'</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright">';


// Show graphs
print '<table class="border centpercent"><tr class="pair nohover"><td align="center">';
if ($mesg) {
	print $mesg;
} else {
	print $px1->show();
	print "<br>\n";
	print $px2->show();
	print "<br>\n";
	print $px3->show();
}
print '</td></tr></table>';


print '</div></div>';
print '<div class="clearboth"></div>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
