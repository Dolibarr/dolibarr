<?php
/* Copyright (C) 2014-2015 Florian HENRY       <florian.henry@open-concept.pro>
 * Copyright (C) 2015      Laurent Destailleur <ldestailleur@users.sourceforge.net>
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
 *       \file       htdocs/projet/tasks/stats/index.php
 *       \ingroup    project
 *       \brief      Page for tasks statistics
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/taskstats.class.php';

// Security check
if (!$user->hasRight('projet', 'lire')) {
	accessforbidden();
}


$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$userid = GETPOSTINT('userid');
$socid = GETPOSTINT('socid');
// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
$nowyear = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$year = GETPOSTINT('year') > 0 ? GETPOSTINT('year') : $nowyear;
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

// Load translation files required by the page
$langs->loadlangs(array('companies', 'projects'));


/*
 * View
 */

$form = new Form($db);

$includeuserlist = array();


llxHeader('', $langs->trans('Tasks'),'', '', 0, 0, '', '', '', 'mod-project project-tasks page-stats');

$title = $langs->trans("TasksStatistics");
$dir = $conf->project->dir_output.'/temp';

print load_fiche_titre($title, '', 'projecttask');

dol_mkdir($dir);


$stats_tasks = new TaskStats($db);
if (!empty($userid) && $userid != -1) {
	$stats_tasks->userid = $userid;
}
if (!empty($socid) && $socid != -1) {
	$stats_tasks->socid = $socid;
}
if (!empty($year)) {
	$stats_tasks->year = $year;
}



// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats_tasks->getNbByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);

$filenamenb = $conf->project->dir_output."/stats/tasknbprevyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=taskstats&amp;file=tasknbprevyear-'.$year.'.png';

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
	$px1->SetYLabel($langs->trans("ProjectNbTask"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("ProjectNbTaskByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}


// Show array
$stats_tasks->year = 0;
$data_all_year = $stats_tasks->getAllByYear();

if (!empty($year)) {
	$stats_tasks->year = $year;
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
$head[$h][0] = DOL_URL_ROOT.'/projet/tasks/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

complete_head_from_modules($conf, $langs, null, $head, $h, 'project_tasks_stats');

print dol_get_fiche_head($head, 'byyear', '', -1, '');


print '<div class="fichecenter"><div class="fichethirdleft">';

print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
/*print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
print $form->select_company($socid,'socid','',1,0,0,array(),0,'','style="width: 95%"');
print '</td></tr>';
*/
// User
/*print '<tr><td>'.$langs->trans("ProjectCommercial").'</td><td>';
print $form->select_dolusers($userid, 'userid', 1, array(),0,$includeuserlist);
print '</td></tr>';*/
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
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
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("NbOfTasks").'</td>';
print '</tr>';

$oldyear = 0;
foreach ($data_all_year as $val) {
	$year = $val['year'];
	while ($year && $oldyear > $year + 1) {	// If we have empty year
		$oldyear--;

		print '<tr class="oddeven">';
		print '<td><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$oldyear.'</a></td>';
		print '<td class="right">0</td>';
		print '</tr>';
	}

	print '<tr class="oddeven">';
	print '<td><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
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
}
$stringtoshow .= '</td></tr></table>';

print $stringtoshow;


print '</div></div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
