<?php
/* Copyright (C) 2014-2015  Florian HENRY           <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Laurent Destailleur     <ldestailleur@users.sourceforge.net>
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
 *       \file       htdocs/projet/tasks/stats/index.php
 *       \ingroup    project
 *       \brief      Page for tasks statistics
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/taskstats.class.php';

// Security check
if (! $user->rights->projet->lire) {
    accessforbidden();
}


//$width=DolChartJs::getDefaultGraphSizeForStats('width');
//$height=DolChartJs::getDefaultGraphSizeForStats('height');
$width = 70;
$height = 25;

$userid = GETPOST('userid', 'int');
$socid = GETPOST('socid', 'int');
// Security check
if ($user->societe_id > 0) {
    $action = '';
    $socid = $user->societe_id;
}
$nowyear=strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
//$startyear=$year-2;
$nbyear = 3;
$startyear = $year - $nbyear + 1;
$endyear=$year;

// Load translation files required by the page
$langs->loadlangs(['companies', 'projects']);


/*
 * View
 */

$form = new Form($db);

$includeuserlist = [];


llxHeader('', $langs->trans('Tasks'));

$title = $langs->trans("TasksStatistics");

print load_fiche_titre($title, '', 'title_project.png');


$stats_tasks= new TaskStats($db);
if (!empty($userid) && $userid!=-1) {
    $stats_tasks->userid = $userid;
}
if (!empty($socid)  && $socid!=-1) {
    $stats_tasks->socid = $socid;
}
if (!empty($year)) {
    $stats_tasks->year = $year;
}



// Build graphic number of object
$px1 = new DolChartJs();
$graph_datas = $stats_tasks->getNbByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px1);

$px1->element('tasknbprevyear')
    ->setType('bar')
    ->setSwitchers(['line', 'bar'])
    ->setLabels($graph_datas['labelgroup'])
    ->setDatasets($graph_datas['dataset'])
    ->setSize(['width' => $width, 'height' => $height])
    ->setOptions([
        'responsive' => true,
        'maintainAspectRatio' => false,
        'legend' => [
            'display' => true,
            'position' => 'bottom',
        ],
        'title' => [
            'display' => true,
            'text' => $langs->transnoentitiesnoconv("ProjectNbTaskByMonth"),
        ],
        'scales' => [
            'xAxes' => [
                [
                    //'stacked' => true,
                ]
            ],
            'yAxes' => [
                [
                    'ticks' => [
                        'min' => 0,
                    ],
                    'gridLines' => [
                        'color' => 'black',
                        'borderDash' => [2, 3],
                    ],
                    'scaleLabel' => [
                        'display' => true,
                        'labelString' => $langs->transnoentitiesnoconv("ProjectNbTask"),
                        'fontColor' => 'black',
                    ],
                ]
            ],
        ],
    ]
);

// Show array
$stats_tasks->year=0;
$data_all_year = $stats_tasks->getAllByYear();

if (!empty($year)) {
    $stats_tasks->year = $year;
}
$arrayyears = [];
foreach($data_all_year as $val) {
	$arrayyears[$val['year']]=$val['year'];
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;


$h = 0;
$head = [];
$head[$h][0] = DOL_URL_ROOT . '/projet/tasks/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

complete_head_from_modules($conf,$langs,null,$head,$h,$type);

dol_fiche_head($head,'byyear',$langs->trans("Statistics"), -1, '');


print '<div class="fichecenter"><div class="fichethirdleft">';

print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
/*print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
print $form->select_company($socid, 'socid', 's.client in (1,2,3)', 1, 0, 0, [], 0, '', 'style="width: 95%"');
print '</td></tr>';
*/
// User
/*print '<tr><td>'.$langs->trans("ProjectCommercial").'</td><td>';
print $form->select_dolusers($userid, 'userid', 1, [], 0, $includeuserlist);
print '</td></tr>';*/
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
if (! in_array($year,$arrayyears)) {
    $arrayyears[$year]=$year;
}
if (! in_array($nowyear,$arrayyears)) {
    $arrayyears[$nowyear]=$nowyear;
}
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0);
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="butAction" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("NbOfTasks").'</td>';
print '</tr>';

$oldyear=0;
foreach ($data_all_year as $val) {
	$year = $val['year'];
	while ($year && $oldyear > $year+1) {
        // If we have empty year
		$oldyear--;
		print '<tr class="oddeven">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$oldyear.'</a></td>';
		print '<td align="right">0</td>';
		print '</tr>';
	}
	print '<tr class="oddeven">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$year.'</a></td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

print '<table class="border centpercent"><tr class="pair nohover"><td align="center">';
print $px1->renderChart();
print "<br>\n";
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';

// End of page
llxFooter();
$db->close();
