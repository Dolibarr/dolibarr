<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/fichinter/stats/index.php
 *      \ingroup    fichinter
 *		\brief      Page with interventions statistics
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinterstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';

// $width = DolChartJs::getDefaultGraphSizeForStats('width');
// $height = DolChartJs::getDefaultGraphSizeForStats('height');
$width = 70;
$height = 25;

$mode = 'customer';
if (! $user->rights->ficheinter->lire) accessforbidden();

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

$object_status = GETPOST('object_status');

// Load translation files required by the page
$langs->loadLangs(array("interventions","suppliers","companies","other"));


/*
 * View
 */

$form = new Form($db);
$objectstatic = new FichInter($db);

$title=$langs->trans("InterventionStatistics");

llxHeader('', $title);

print load_fiche_titre($title, '', 'title_commercial.png');

$stats = new FichinterStats($db, $socid, $mode, ($userid>0?$userid:0));
if ($object_status != '' && $object_status > -1) $stats->where .= ' AND c.fk_statut IN ('.$db->escape($object_status).')';

// Build graphic number of object
$px1 = new DolChartJs();
$graph_datas = $stats->getNbByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px1);

$px1->element('interventionsnbinyear')
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
            'text' => $langs->transnoentitiesnoconv("NumberOfInterventionsByMonth"),
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
                        'labelString' => $langs->transnoentitiesnoconv("NbOfIntervention"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);

// Build graphic amount of object
//$px2 = new DolChartJs();
//$graph_datas = $stats->getAmountByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px2);

//interventionsamountinyear

//     $px2->SetYLabel($langs->trans("AmountOfinterventions"));
//     $px2->SetTitle($langs->trans("AmountOfinterventionsByMonthHT"));

//$px3 = new DolChartJs();
//$graph_datas = $stats->getAverageByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px3);

//interventionsaverage

//     $px3->SetYLabel($langs->trans("AmountAverage"));
//     $px3->SetTitle($langs->trans("AmountAverage"));

// Show array
$data = $stats->getAllByYear();
$arrayyears = array();
foreach($data as $val) {
	if (! empty($val['year'])) {
		$arrayyears[$val['year']]=$val['year'];
	}
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;

$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/fichinter/stats/index.php?mode='.$mode;
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

if ($mode == 'customer') $type = 'fichinter_stats';

complete_head_from_modules($conf,$langs,null,$head,$h,$type);

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


//if (empty($socid))
//{
	// Show filter box
	print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
	// Company
	print '<tr><td align="left">'.$langs->trans("ThirdParty").'</td><td align="left">';
	if ($mode == 'customer') $filter='s.client in (1,2,3)';
	//if ($mode == 'supplier') $filter='s.fournisseur = 1';
	print $form->select_company($socid,'socid',$filter,1,0,0,array(),0,'','style="width: 95%"');
	print '</td></tr>';
	// User
	print '<tr><td align="left">'.$langs->trans("CreatedBy").'</td><td align="left">';
	print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	// Status
	print '<tr><td align="left">'.$langs->trans("Status").'</td><td align="left">';
	$tmp = $objectstatic->LibStatut(0);		// To load $this->statuts_short
	$liststatus=$objectstatic->statuts_short;
	if (empty($conf->global->FICHINTER_CLASSIFY_BILLED)) unset($liststatus[2]);   // Option deprecated. In a future, billed must be managed with a dedicated field to 0 or 1
	print $form->selectarray('object_status', $liststatus, $object_status, 1, 0, 0, '', 1);
	print '</td></tr>';
	// Year
	print '<tr><td align="left">'.$langs->trans("Year").'</td><td align="left">';
	if (! in_array($year,$arrayyears)) $arrayyears[$year]=$year;
	if (! in_array($nowyear,$arrayyears)) $arrayyears[$nowyear]=$nowyear;
	arsort($arrayyears);
	print $form->selectarray('year',$arrayyears,$year,0);
	print '</td></tr>';
	print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
	print '</table>';
	print '</form>';
	print '<br><br>';
//}

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre" height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("NbOfinterventions").'</td>';
print '<td align="right">%</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">%</td>';
print '<td align="right">'.$langs->trans("AmountAverage").'</td>';
print '<td align="right">%</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val) {
	$year = $val['year'];
	while (! empty($year) && $oldyear > $year+1) {
        // If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$oldyear.'</a></td>';

		print '<td align="right">0</td>';
		print '<td align="right"></td>';
		print '<td align="right">0</td>';
		print '<td align="right"></td>';
		print '<td align="right">0</td>';
		print '<td align="right"></td>';
		print '</tr>';
	}


	print '<tr class="oddeven" height="24">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$year.'</a></td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '<td align="right" style="'.(($val['nb_diff'] >= 0) ? 'color: green;':'color: red;').'">'.round($val['nb_diff']).'</td>';
	print '<td align="right">'.price(price2num($val['total'],'MT'),1).'</td>';
	print '<td align="right" style="'.(($val['total_diff'] >= 0) ? 'color: green;':'color: red;').'">'.round($val['total_diff']).'</td>';
	print '<td align="right">'.price(price2num($val['avg'],'MT'),1).'</td>';
	print '<td align="right" style="'.(($val['avg_diff'] >= 0) ? 'color: green;':'color: red;').'">'.round($val['avg_diff']).'</td>';
	print '</tr>';
	$oldyear=$year;
}

print '</table>';
print '</div>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
print '<table class="border" width="100%"><tr class="pair nohover"><td align="center">';
print $px1->renderChart();
// print "<br>\n";
// print $px2->renderChart();
// print "<br>\n";
// print $px3->renderChart();
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';

dol_fiche_end();


llxFooter();

$db->close();
