<?php
/* Copyright (C) 2014-2015 Florian HENRY       <florian.henry@open-concept.pro>
 * Copyright (C) 2015-2021 Laurent Destailleur <ldestailleur@users.sourceforge.net>
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
 *       \file       htdocs/projet/stats/index.php
 *       \ingroup    project
 *       \brief      Page for project statistics
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/projectstats.class.php';

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$search_opp_status = GETPOST("search_opp_status", 'alpha');

$userid = GETPOST('userid', 'int');
$socid = GETPOST('socid', 'int');
// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
$nowyear = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$year = GETPOST('year', 'int') > 0 ? GETPOST('year', 'int') : $nowyear;
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

// Load translation files required by the page
$langs->loadLangs(array('companies', 'projects'));

// Security check
if (!$user->hasRight('projet', 'lire')) {
	accessforbidden();
}


/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);

$includeuserlist = array();


llxHeader('', $langs->trans('Projects'));

$title = $langs->trans("ProjectsStatistics");
$dir = $conf->project->dir_output.'/temp';

print load_fiche_titre($title, '', 'project');

dol_mkdir($dir);


$stats_project = new ProjectStats($db);
if (!empty($userid) && $userid != -1) {
	$stats_project->userid = $userid;
}
if (!empty($socid) && $socid != -1) {
	$stats_project->socid = $socid;
}
if (!empty($year)) {
	$stats_project->year = $year;
}

if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
	if ($search_opp_status) {
		$stats_project->opp_status = $search_opp_status;
	}
}


// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats_project->getNbByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);

$filenamenb = $conf->project->dir_output."/stats/projectnbprevyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=projectstats&amp;file=projectnbprevyear-'.$year.'.png';

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
	$px1->SetYLabel($langs->trans("ProjectNbProject"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("ProjectNbProjectByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}


if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
	// Build graphic amount of object
	$data = $stats_project->getAmountByMonthWithPrevYear($endyear, $startyear);
	//var_dump($data);
	// $data = array(array('Lib',val1,val2,val3),...)

	$filenamenb = $conf->project->dir_output."/stats/projectamountprevyear-".$year.".png";
	$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=projectstats&amp;file=projectamountprevyear-'.$year.'.png';

	$px2 = new DolGraph();
	$mesg = $px2->isGraphKo();
	if (!$mesg) {
		$i = $startyear;
		$legend = array();
		while ($i <= $endyear) {
			$legend[] = $i;
			$i++;
		}

		$px2->SetData($data);
		$px2->SetLegend($legend);
		$px2->SetMaxValue($px2->GetCeilMaxValue());
		$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
		$px2->SetWidth($WIDTH);
		$px2->SetHeight($HEIGHT);
		$px2->SetYLabel($langs->trans("ProjectOppAmountOfProjectsByMonth"));
		$px2->SetShading(3);
		$px2->SetHorizTickIncrement(1);
		$px2->SetType(array('bars', 'bars'));
		$px2->mode = 'depth';
		$px2->SetTitle($langs->trans("ProjectOppAmountOfProjectsByMonth"));

		$px2->draw($filenamenb, $fileurlnb);
	}
}

if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
	// Build graphic with transformation rate
	$data = $stats_project->getWeightedAmountByMonthWithPrevYear($endyear, $startyear, 0, 0);
	//var_dump($data);
	// $data = array(array('Lib',val1,val2,val3),...)

	$filenamenb = $conf->project->dir_output."/stats/projecttransrateprevyear-".$year.".png";
	$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=projectstats&amp;file=projecttransrateprevyear-'.$year.'.png';

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
		$px3->SetMaxValue($px3->GetCeilMaxValue());
		$px3->SetMinValue(min(0, $px3->GetFloorMinValue()));
		$px3->SetWidth($WIDTH);
		$px3->SetHeight($HEIGHT);
		$px3->SetYLabel($langs->trans("ProjectWeightedOppAmountOfProjectsByMonth"));
		$px3->SetShading(3);
		$px3->SetHorizTickIncrement(1);
		$px3->mode = 'depth';
		$px3->SetTitle($langs->trans("ProjectWeightedOppAmountOfProjectsByMonth"));

		$px3->draw($filenamenb, $fileurlnb);
	}
}


// Show array
$stats_project->year = 0;
$data_all_year = $stats_project->getAllByYear();

if (!empty($year)) {
	$stats_project->year = $year;
}
$arrayyears = array();
foreach ($data_all_year as $val) {
	$arrayyears[$val['year']] = $val['year'];
}
if (!count($arrayyears)) {
	$arrayyears[$nowyear] = $nowyear;
}


$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/projet/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

complete_head_from_modules($conf, $langs, null, $head, $h, 'project_stats');

print dol_get_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1, '');


print '<div class="fichecenter"><div class="fichethirdleft">';

print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
print img_picto('', 'company', 'class="pictofixedwidth"');
print $form->select_company($socid, 'socid', '', 1, 0, 0, array(), 0, 'widthcentpercentminusx maxwidth300', '');
print '</td></tr>';
// Opportunity status
if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
	print '<tr><td>'.$langs->trans("OpportunityStatusShort").'</td><td>';
	print $formproject->selectOpportunityStatus('search_opp_status', $search_opp_status, 1, 0, 1, 0, 'maxwidth300', 1, 1);
	print '</td></tr>';
}

// User
/*print '<tr><td>'.$langs->trans("ProjectCommercial").'</td><td>';
print $form->select_dolusers($userid, 'userid', 1, array(),0,$includeuserlist);
print '</td></tr>';*/
// Year
print '<tr><td>'.$langs->trans("Year").' <span class="opacitymedium">('.$langs->trans("DateCreation").')</span></td><td>';
if (!in_array($year, $arrayyears)) {
	$arrayyears[$year] = $year;
}
if (!in_array($nowyear, $arrayyears)) {
	$arrayyears[$nowyear] = $nowyear;
}
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0, 0, 0, '', 0, 0, 0, '', 'width75');
print '</td></tr>';
print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';

print '</form>';

print '<br><br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre" height="24">';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("NbOfProjects").'</td>';
if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
	print '<td class="right">'.$langs->trans("OpportunityAmountShort").'</td>';
	print '<td class="right">'.$langs->trans("OpportunityAmountAverageShort").'</td>';
	print '<td class="right">'.$langs->trans("OpportunityAmountWeigthedShort").'</td>';
}
print '</tr>';

$oldyear = 0;
foreach ($data_all_year as $val) {
	$year = $val['year'];
	while ($year && $oldyear > $year + 1) {	// If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
		print '<td class="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$oldyear.'</a></td>';
		print '<td class="right">0</td>';
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			print '<td class="right amount nowraponall">0</td>';
			print '<td class="right amount nowraponall">0</td>';
			print '<td class="right amount nowraponall">0</td>';
		}
		print '</tr>';
	}

	print '<tr class="oddeven" height="24">';
	print '<td class="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
		print '<td class="right amount nowraponall">'.($val['total'] ? price(price2num($val['total'], 'MT'), 1) : '0').'</td>';
		print '<td class="right amount nowraponall">'.($val['avg'] ? price(price2num($val['avg'], 'MT'), 1) : '0').'</td>';
		print '<td class="right amount nowraponall">'.(isset($val['weighted']) ? price(price2num($val['weighted'], 'MT'), 1) : '0').'</td>';
	}
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright">';

$stringtoshow = '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
if ($mesg) {
	print $mesg;
} else {
	$stringtoshow .= $px1->show();
	$stringtoshow .= "<br>\n";
	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
		//$stringtoshow .= $px->show();
		//$stringtoshow .= "<br>\n";
		$stringtoshow .= $px2->show();
		$stringtoshow .= "<br>\n";
		$stringtoshow .= $px3->show();
	}
}
$stringtoshow .= '</td></tr></table>';

print $stringtoshow;

print '</div></div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
