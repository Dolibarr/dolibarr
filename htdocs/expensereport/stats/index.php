<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/expensereport/stats/index.php
 *  \ingroup    expensereport
 *  \brief      Page for statistics of module trips and expenses
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereportstats.class.php';

// Load translation files required by the page
$langs->loadLangs(array('trips', 'companies'));

//$width=DolChartjs::getDefaultGraphSizeForStats('width');
//$height=DolChartjs::getDefaultGraphSizeForStats('height');
$width = 70;
$height = 25;

$mode = GETPOST("mode")?GETPOST("mode"):'customer';
$object_status = GETPOST('object_status');

$userid=GETPOST('userid', 'int');
$socid=GETPOST('socid', 'int'); if ($socid < 0) $socid=0;
$id = GETPOST('id', 'int');

// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport', $id,'');

$nowyear=strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
$nbyear = 3;
//$startyear=$year-2;
$startyear = $year - $nbyear + 1;
$endyear = $year;



/*
 * View
 */

$form = new Form($db);
$tmpexpensereport = new ExpenseReport($db);

$title = $langs->trans("TripsAndExpensesStatistics");

llxHeader('', $title);

print load_fiche_titre($title, $mesg);

dol_mkdir($dir);

$stats = new ExpenseReportStats($db, $socid, $userid);
if ($object_status != '' && $object_status >= -1) $stats->where .= ' AND e.fk_statut IN ('.$db->escape($object_status).')';

// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
//print "$endyear, $startyear";
$datas = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);

$labels = array();
$datatmp = array();
$datacolor = array();
$bgdatacolor = array();
$dataset = array();
$px1 = new DolChartJs();
foreach ($datas as $data) {
    $labels[] = $data[0];
    for ($i=0; $i<$nbyear; $i++) {
        $datacolor[$i][] = $px1->datacolor[$i];
        $bgdatacolor[$i][] = $px1->bgdatacolor[$i];
        $datatmp[$i][] = $data[$i+1];
    }
}
for ($i=0; $i<$nbyear; $i++) {
    $dataset[] = array(
        //'label' => $langs->trans("NbOfSubscriptions").' '.($startyear+$i),
        'label' => $startyear + $i,
        'backgroundColor' => $datacolor[$i],
        'borderColor' => $bgdatacolor[$i],
        'data' => $datatmp[$i],
    );
}
$px1->element('tripsexpensesnbinyear')
    ->setType('bar')
    ->setLabels($labels)
    ->setDatasets($dataset)
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
            'text' => $langs->transnoentitiesnoconv("NumberByMonth"),
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
                        'labelString' => $langs->transnoentitiesnoconv("Number"),
                        'fontColor' => 'green',
                    ),
                )
            ),
        ),
    )
);

// Build graphic amount of object
$datas = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)
$labels1 = array();
$datatmp = array();
$datacolor = array();
$bgdatacolor = array();
$dataset = array();
$px2 = new DolChartJs();
foreach ($datas as $data) {
    $labels1[] = $data[0];
    for ($i=0; $i<$nbyear; $i++) {
        $datacolor[$i][] = $px2->datacolor[$i];
        $bgdatacolor[$i][] = $px2->bgdatacolor[$i];
        $datatmp[$i][] = $data[$i+1];
    }
}
for ($i=0; $i<$nbyear; $i++) {
    $dataset[] = array(
        //'label' => $langs->trans("NbOfSubscriptions").' '.($startyear+$i),
        'label' => $startyear + $i,
        'backgroundColor' => $datacolor[$i],
        'borderColor' => $bgdatacolor[$i],
        'data' => $datatmp[$i],
    );
}
$px2->element('tripsexpensesamountinyear')
    ->setType('bar')
    ->setLabels($labels1)
    ->setDatasets($dataset)
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
            'text' => $langs->transnoentitiesnoconv("AmountTotal"),
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
                        'labelString' => $langs->transnoentitiesnoconv("Amount"),
                        'fontColor' => 'green',
                    ),
                )
            ),
        ),
    )
);


$datas = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

$labels = array();
$datatmp = array();
$datacolor = array();
$bgdatacolor = array();
$dataset = array();
$px3 = new DolChartJs();
foreach ($datas as $data) {
    $labels[] = $data[0];
    for ($i=0; $i<$nbyear; $i++) {
        $datacolor[$i][] = $px3->datacolor[$i];
        $bgdatacolor[$i][] = $px3->bgdatacolor[$i];
        $datatmp[$i][] = $data[$i+1];
    }
}
for ($i=0; $i<$nbyear; $i++) {
    $dataset[] = array(
        //'label' => $langs->trans("NbOfSubscriptions").' '.($startyear+$i),
        'label' => $startyear + $i,
        'backgroundColor' => $datacolor[$i],
        'borderColor' => $bgdatacolor[$i],
        'data' => $datatmp[$i],
    );
}
$px3->element('tripsexpensesamountavginyear')
    ->setType('bar')
    ->setLabels($labels)
    ->setDatasets($dataset)
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
            'text' => $langs->transnoentitiesnoconv("AmountAverageByMonth"),
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
                        'labelString' => $langs->transnoentitiesnoconv("AmountAverage"),
                        'fontColor' => 'green',
                    ),
                )
            ),
        ),
    )
);

// Show array
$datas = $stats->getAllByYear();
$arrayyears = array();
foreach($datas as $val) {
    $arrayyears[$val['year']]=$val['year'];
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;


$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/expensereport/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

complete_head_from_modules($conf,$langs,null,$head,$h,'trip_stats');

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
/*
print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
$filter='';
print $form->select_company($socid,'socid',$filter,1,1,0,array(),0,'','style="width: 95%"');
print '</td></tr>';
*/
// User
print '<tr><td>'.$langs->trans("User").'</td><td>';
$include='';
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)) $include='hierarchy';
print $form->select_dolusers($userid, 'userid', 1, '', 0, $include, '', 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td></tr>';
// Status
print '<tr><td align="left">'.$langs->trans("Status").'</td><td align="left">';
$liststatus=$tmpexpensereport->statuts;
print $form->selectarray('object_status', $liststatus, GETPOST('object_status'), -4, 0, 0, '', 1);
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
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre" height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear=0;
foreach ($datas as $val)
{
	$year = $val['year'];
	while ($year && $oldyear > $year+1)
	{	// If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.'">'.$oldyear.'</a></td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '</tr>';
	}

	print '<tr class="oddeven" height="24">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.'">'.$year.'</a></td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '<td align="right">'.price(price2num($val['total'],'MT'),1).'</td>';
	print '<td align="right">'.price(price2num($val['avg'],'MT'),1).'</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
print '<table class="border" width="100%"><tr class="pair nohover"><td align="center">';
if ($mesg) {
    print $mesg;
} else {
    print $px1->renderChart();
    print "<br>\n";
    print $px2->renderChart();
    print "<br>\n";
    print $px3->renderChart();
}
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';


dol_fiche_end();

// End of page
llxFooter();
$db->close();
