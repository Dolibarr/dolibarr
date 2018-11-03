<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 *  \file       htdocs/don/stats/index.php
 *  \ingroup    donations
 *  \brief      Page with donations statistics
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/donstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';

// $width = DolChartJs::getDefaultGraphSizeForStats('width');
// $height = DolChartJs::getDefaultGraphSizeForStats('height');
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
$startyear=$year-1;
$endyear=$year;

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "donations"));


/*
 * View
 */

$form = new Form($db);

llxHeader();

print load_fiche_titre($langs->trans("StatisticsOfDonations"), $mesg);

$stats = new DonationStats($db, $socid, '', ($userid>0?$userid:0));

// Build graphic number of object
$px1 = new DolChartJs();
$graph_datas = $stats->getNbByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px1);

$px1->element('donationsnbinyear')
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
            'text' => $langs->transnoentitiesnoconv("NumberOfDonationsByMonth"),
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
                        'labelString' => $langs->transnoentitiesnoconv("NbOfDonations"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);


// Build graphic amount of object
$px2 = new DolChartJs();
$graph_datas = $stats->getAmountByMonthWithPrevYear($endyear,$startyear, 0, 0, 1, $px2);

$px2->element('ordersamountinyear')
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
            'text' => $langs->transnoentities("AmountOfDonationsByMonth"),
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
                        'labelString' => $langs->transnoentitiesnoconv("AmountOfDonations"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);


$px3 = new DolChartJs();
$graph_datas = $stats->getAverageByMonthWithPrevYear($endyear, $startyear, 1, $px3);

$px3->element('donationsaverage')
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
            'text' => $langs->transnoentities("AmountAverageOfDonationsByMonth"),
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
                        'labelString' => $langs->transnoentitiesnoconv("AmountAverageOfDonations"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);


// Show array
$data = $stats->getAllByYear();
$arrayyears = array();
foreach($data as $val) {
	if (! empty($val['year'])) {
		$arrayyears[$val['year']] = $val['year'];
	}
}
if (! count($arrayyears)) {
    $arrayyears[$nowyear] = $nowyear;
}

$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/don/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

$type = 'donation_stats';

complete_head_from_modules($conf, $langs, null, $head, $h, $type);

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="border" width="100%">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
print '<tr><td align="left">'.$langs->trans("ThirdParty").'</td><td align="left">';
if ($mode == 'customer') $filter='s.client in (1,2,3)';
print $form->select_company($socid, 'socid', $filter, 1, 0, 0, array(), 0, '', 'style="width: 95%"');
print '</td></tr>';
// User
print '<tr><td align="left">'.$langs->trans("CreatedBy").'</td><td align="left">';
print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td></tr>';
// Year
print '<tr><td align="left">'.$langs->trans("Year").'</td><td align="left">';
if (! in_array($year, $arrayyears)) $arrayyears[$year]=$year;
if (! in_array($nowyear, $arrayyears)) $arrayyears[$nowyear] = $nowyear;
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0);
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="border" width="100%">';
print '<tr height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="center">'.$langs->trans("NbOfDonations").'</td>';
print '<td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val) {
	$year = $val['year'];
	while (! empty($year) && $oldyear > $year+1) {
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
print $px1->renderChart();
print "<br>\n";
print $px2->renderChart();
print "<br>\n";
print $px3->renderChart();
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';

dol_fiche_end();



// TODO USe code similar to commande/stats/index.php instead of this one.
/*
print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("Year").'</td>';
print '<td width="40%" align="center">'.$langs->trans("NbOfSendings").'</td></tr>';

$sql = "SELECT count(*) as nb, date_format(date_expedition,'%Y') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition";
$sql.= " WHERE fk_statut > 0";
$sql.= " AND entity = ".$conf->entity;
$sql.= " GROUP BY dm DESC";

$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {
        $row = $db->fetch_row($resql);
        $nbproduct = $row[0];
        $year = $row[1];
        print "<tr>";
        print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td><td align="center">'.$nbproduct.'</td></tr>';
        $i++;
    }
}
$db->free($resql);

print '</table>';
*/

print '<br>';

llxFooter();
$db->close();
