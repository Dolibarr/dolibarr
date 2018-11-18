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
 *       \file       htdocs/projet/stats/index.php
 *       \ingroup    project
 *       \brief      Page for project statistics
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/projectstats.class.php';

// Security check
if (! $user->rights->projet->lire)
    accessforbidden();


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
$nowyear = strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
//$startyear=$year-2;
$nbyear = 3;
$startyear = $year - $nbyear + 1;
$endyear = $year;

// Load translation files required by the page
$langs->loadLangs(array('companies', 'projects'));


/*
 * View
 */

$form = new Form($db);

$includeuserlist = [];


llxHeader('', $langs->trans('Projects'));

$title = $langs->trans("ProjectsStatistics");

print load_fiche_titre($title, '', 'title_project.png');


$stats_project= new ProjectStats($db);
if (!empty($userid) && $userid!=-1) {
    $stats_project->userid = $userid;
}
if (!empty($socid)  && $socid!=-1) {
    $stats_project->socid = $socid;
}
if (!empty($year)) {
    $stats_project->year = $year;
}



if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
    $data1 = $stats_project->getAllProjectByStatus();
    if (!is_array($data1) && $data1<0) {
        setEventMessages($stats_project->error, null, 'errors');
    }
    if (empty($data1)) {
        $data1 = array(
            array(
                0 => $langs->trans("None"),
                1 => 1,
            ),
        );
    }

    $px = new DolChartJs();
    $i = 0;
    $labels = [];
    $dataseries = [];
    while ($i < count($data1)) {
        $dataseries[] = $data1[$i][1];
        $labels[] = $data1[$i][0];
        $i++;
    }
    $px->element('projectbystatus')
        ->setType('pie')
        ->setLabels($labels)
        ->setDatasets(
            [
                [
                    'backgroundColor' => $px->bgdatacolor,
                    'borderColor' => $px->datacolor,
                    'data' => $dataseries,
                ],
            ]
        )
        ->setSize(['width' => 70, 'height' => 25])
        ->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'title' => [
                'display' => true,
                'text' => $langs->transnoentitiesnoconv("OpportunitiesStatusForProjects"),
            ],
                'legend' => [
                'position' => 'right',
            ],
        ]
    );
}


// Build graphic number of object
$px1 = new DolChartJs();
$graph_datas = $stats_project->getNbByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px1);

$px1->element('projectnbprevyear')
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
            'text' => $langs->transnoentitiesnoconv("ProjectNbProjectByMonth"),
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
                        'labelString' => $langs->transnoentitiesnoconv("ProjectNbProject"),
                        'fontColor' => 'black',
                    ],
                ]
            ],
        ],
    ]
);


if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
    // Build graphic amount of object
    $px2 = new DolChartJs();
    $graph_datas = $stats_project->getAmountByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px2);

    //projectamountprevyear
    $px2->element('ordersamountinyear')
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
                'text' => $langs->transnoentities("ProjectOppAmountOfProjectsByMonth"),
            ],
            'scales' => [
                'yAxes' => [
                    [
                        'gridLines' => [
                            'color' => 'black',
                            'borderDash' => [2, 3],
                        ],
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => $langs->transnoentitiesnoconv("ProjectOppAmountOfProjects"),
                            'fontColor' => 'black',
                        ],
                    ]
                ],
            ],
        ]
    );
}

if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
    // Build graphic with transformation rate
    $px3 = new DolChartJs();
    $graph_datas = $stats_project->getWeightedAmountByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px3);

    $px3->element('projecttransrateprevyear')
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
                'text' => $langs->transnoentities("ProjectWeightedOppAmountOfProjectsByMonth"),
            ],
            'scales' => [
                'yAxes' => [
                    [
                        'gridLines' => [
                            'color' => 'black',
                            'borderDash' => [2, 3],
                        ],
                        'scaleLabel' => [
                            'display' => true,
                            'labelString' => $langs->transnoentitiesnoconv("ProjectWeightedOppAmountOfProjects"),
                            'fontColor' => 'black',
                        ],
                    ]
                ],
            ],
        ]
    );
}


// Show array
$stats_project->year = 0;
$data_all_year = $stats_project->getAllByYear();

if (!empty($year)) {
    $stats_project->year=$year;
}
$arrayyears = [];
foreach($data_all_year as $val) {
    $arrayyears[$val['year']]=$val['year'];
}
if (! count($arrayyears)) {
    $arrayyears[$nowyear]=$nowyear;
}


$h = 0;
$head = [];
$head[$h][0] = DOL_URL_ROOT . '/projet/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

complete_head_from_modules($conf, $langs, null, $head, $h, $type);

dol_fiche_head($head,'byyear',$langs->trans("Statistics"), -1, '');


print '<div class="fichecenter"><div class="fichethirdleft">';

print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
print $form->select_company($socid, 'socid', 's.client in (1,2,3)', 1, 0, 0, [], 0, '', 'style="width: 95%"');
print '</td></tr>';
// User
/*print '<tr><td>'.$langs->trans("ProjectCommercial").'</td><td>';
print $form->select_dolusers($userid, 'userid', 1, [],0,$includeuserlist);
print '</td></tr>';*/
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
if (! in_array($year,$arrayyears)) {
    $arrayyears[$year] = $year;
}
if (! in_array($nowyear,$arrayyears)) {
    $arrayyears[$nowyear] = $nowyear;
}
arsort($arrayyears);
print $form->selectarray('year',$arrayyears,$year,0);
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="butAction" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("NbOfProjects").'</td>';
if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
    print '<td align="right">'.$langs->trans("OpportunityAmountShort").'</td>';
    print '<td align="right">'.$langs->trans("OpportunityAmountAverageShort").'</td>';
    print '<td align="right">'.$langs->trans("OpportunityAmountWeigthedShort").'</td>';
}
print '</tr>';

$oldyear=0;
foreach ($data_all_year as $val) {
    $year = $val['year'];
    while ($year && $oldyear > $year+1) {
        // If we have empty year
        $oldyear--;

        print '<tr class="oddeven">';
        print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$oldyear.'</a></td>';
        if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
            print '<td align="right">0</td>';
            print '<td align="right">0</td>';
            print '<td align="right">0</td>';
        }
        print '<td align="right">0</td>';
        print '</tr>';
    }

    print '<tr class="oddeven">';
    print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$year.'</a></td>';
    print '<td align="right">'.$val['nb'].'</td>';
    if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
        print '<td align="right">'.($val['total']?price(price2num($val['total'],'MT'),1):'0').'</td>';
        print '<td align="right">'.($val['avg']?price(price2num($val['avg'],'MT'),1):'0').'</td>';
        print '<td align="right">'.($val['weighted']?price(price2num($val['weighted'],'MT'),1):'0').'</td>';
    }
    print '</tr>';
    $oldyear = $year;
}

print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

print '<table class="border" width="100%"><tr class="pair nohover"><td align="center">';
print $px1->renderChart();
print "<br>\n";
if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
    print $px->renderChart();
    print "<br>\n";
    print $px2->renderChart();
    print "<br>\n";
    print $px3->renderChart();
}
print '</td></tr></table>';

print '</div></div></div>';
print '<div style="clear:both"></div>';

// End of page
llxFooter();
$db->close();
