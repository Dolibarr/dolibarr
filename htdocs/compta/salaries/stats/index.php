<?php
/* Copyright (C) 2018      Alexandre Spangaro <aspangaro@zendsi.com>
 * Copyright (C) 2018      Fidesio            <contact@fidesio.com>
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
 *  \file       htdocs/compta/salaries/stats/index.php
 *  \ingroup    salaries
 *  \brief      Page for statistics of module salaries
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/salariesstats.class.php';

// Load translation files required by the page
$langs->loadLangs(array("salaries", "companies"));

//$width = DolChartJs::getDefaultGraphSizeForStats('width');
//$height = DolChartJs::getDefaultGraphSizeForStats('height');
$width = 70;
$height = 25;

$userid=GETPOST('userid', 'int'); if ($userid < 0) $userid=0;
$socid=GETPOST('socid', 'int'); if ($socid < 0) $socid=0;
$id = GETPOST('id', 'int');

// Security check
$socid = GETPOST("socid", "int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'salaries', '', '', '');

$nowyear=strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
//$startyear=$year-2;
$nbyear = 3;
$startyear = $year - $nbyear + 1;
$endyear=$year;


/*
 * View
 */

$form = new Form($db);


llxHeader();

$title=$langs->trans("SalariesStatistics");

print load_fiche_titre($title, $mesg);

dol_mkdir($dir);

$useridtofilter = $userid;	// Filter from parameters

$stats = new SalariesStats($db, $socid, $useridtofilter);


// Build graphic number of object
$px1 = new DolChartJs();
$graph_datas = $stats->getNbByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px1);

$px1->element('salariesnbinyear')
    ->setType('bar')
    ->setSwitchers(array('line', 'bar'))
    ->setLabels($graph_datas['labelgroup'])
    ->setDatasets($graph_datas['dataset'])
    ->setSize(array('width' => $width, 'height' => $height))
    ->setOptions(array(
        'responsive' => true,
        'maintainAspectRatio' => false,
        'legend' => array(
            'display' => true,
            'position' => 'bottom',
        ),
        'title' => array(
            'display' => true,
            'text' => $langs->transnoentitiesnoconv("NumberOfSalariesByMonth"),
        ),
        'scales' => array(
            'yAxes' => array(
                array(
                    'gridLines' => array(
                        'color' => 'black',
                        'borderDash' => array(2, 3),
                    ),
                    'scaleLabel' => array(
                        'display' => true,
                        'labelString' => $langs->transnoentitiesnoconv("NbOfSalaries"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);

// Build graphic amount of object
$px2 = new DolChartJs();
$graph_datas = $stats->getAmountByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px2);

$px2->element('salariesamountinyear')
    ->setType('bar')
    ->setSwitchers(array('line', 'bar'))
    ->setLabels($graph_datas['labelgroup'])
    ->setDatasets($graph_datas['dataset'])
    ->setSize(array('width' => $width, 'height' => $height))
    ->setOptions(array(
        'responsive' => true,
        'maintainAspectRatio' => false,
        'legend' => array(
            'display' => true,
            'position' => 'bottom',
        ),
        'title' => array(
            'display' => true,
            'text' => $langs->transnoentities("AmountOfSalariesByMonth"),
        ),
        'scales' => array(
            'yAxes' => array(
                array(
                    'gridLines' => array(
                        'color' => 'black',
                        'borderDash' => array(2, 3),
                    ),
                    'scaleLabel' => array(
                        'display' => true,
                        'labelString' => $langs->transnoentitiesnoconv("AmountOfSalaries"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);

$px3 = new DolChartJs();
$graph_datas = $stats->getAverageByMonthWithPrevYear($endyear, $startyear, 1, $px3);

$px3->element('salariesaverageinyear')
    ->setType('bar')
    ->setSwitchers(array('line', 'bar'))
    ->setLabels($graph_datas['labelgroup'])
    ->setDatasets($graph_datas['dataset'])
    ->setSize(array('width' => $width, 'height' => $height))
    ->setOptions(array(
        'responsive' => true,
        'maintainAspectRatio' => false,
        'legend' => array(
            'display' => true,
            'position' => 'bottom',
        ),
        'title' => array(
            'display' => true,
            'text' => $langs->transnoentities("AmountAverageOfSalariesByMonthHT"),
        ),
        'scales' => array(
            'yAxes' => array(
                array(
                    'gridLines' => array(
                        'color' => 'black',
                        'borderDash' => array(2, 3),
                    ),
                    'scaleLabel' => array(
                        'display' => true,
                        'labelString' => $langs->transnoentitiesnoconv("AmountAverageOfSalaries"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);

// Show array
$data = $stats->getAllByYear();
$arrayyears=array();
foreach($data as $val) {
	$arrayyears[$val['year']]=$val['year'];
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;


$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/compta/salaries/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

complete_head_from_modules($conf,$langs,null,$head,$h,'trip_stats');

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<table class="border" width="100%">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// User
print '<tr><td>'.$langs->trans("User").'</td><td>';
print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td></tr>';
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
if (! in_array($year,$arrayyears)) $arrayyears[$year]=$year;
arsort($arrayyears);
print $form->selectarray('year',$arrayyears,$year,0);
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="border" width="100%">';
print '<tr>';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val) {
	$year = $val['year'];
	while ($year && $oldyear > $year+1) {
		// If we have empty year
		$oldyear--;
		print '<tr height="24">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'">'.$oldyear.'</a></td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '</tr>';
	}
	print '<tr height="24">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'">'.$year.'</a></td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '<td align="right">'.price(price2num($val['total'], 'MT'), 1).'</td>';
	print '<td align="right">'.price(price2num($val['avg'], 'MT'), 1).'</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
print '<table class="border" width="100%"><tr class="pair nohover"><td align="center">';
print $px1->renderChart();
print "<br>\n";
print $px2->renderChart();
print "<br>\n";
print $px3->renderChart();
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';


dol_fiche_end();

// End of page
llxFooter();
$db->close();
