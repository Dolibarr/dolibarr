<?php
/* Copyright (C) 2003-2006  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2012       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2013       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
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
 *  \file       htdocs/compta/facture/stats/index.php
 *  \ingroup    facture
 *  \brief      Page des stats factures
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolchartjs.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';

//$width=DolChartjs::getDefaultGraphSizeForStats('width');
//$height=DolChartjs::getDefaultGraphSizeForStats('height');
$width = 70;
$height = 25;

$mode = GETPOST("mode", 'alpha');
if (! in_array($mode, array('customer', 'supplier'))) {
    $mode = 'customer';
}
if ($mode == 'customer' && ! $user->rights->facture->lire) accessforbidden();
if ($mode == 'supplier' && ! $user->rights->fournisseur->facture->lire) accessforbidden();

$object_status = GETPOST('object_status', 'int');

$userid = GETPOST('userid','int');
$socid = GETPOST('socid','int');
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


/*
 * View
 */
// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'other'));

$form = new Form($db);

$stats = new FactureStats($db, $socid, $mode, ($userid>0?$userid:0));
if ($object_status != '' && $object_status >= 0) $stats->where .= ' AND f.fk_statut IN ('.$db->escape($object_status).')';

if ($mode == 'customer') {
    $title = $langs->trans("BillsStatistics");
} elseif ($mode == 'supplier') {
    $title = $langs->trans("BillsStatisticsSuppliers");
}

llxHeader('', $title);

print load_fiche_titre($title, $mesg, 'title_accountancy.png');

// Build graphic number of object
// $graph_datas = array(array('Lib',val1,val2,val3),...)
$px1 = new DolChartJs();
$graph_datas = $stats->getNbByMonthWithPrevYear($endyear, $startyear, 0, 0, 1, $px1);

$px1->element('invoicesnbinyear')
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
            'text' => $title,
        ),
        'scales' => array(
            'xAxes' => array(
                array(
                    //'stacked' => true,
                )
            ),
            'yAxes' => array(
                array(
                    //'stacked' => true,
                    'gridLines' => array(
                        'color' => 'black',
                        'borderDash' => array(2, 3),
                    ),
                    'scaleLabel' => array(
                        'display' => true,
                        'labelString' => $langs->transnoentitiesnoconv("NumberOfBills"),
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

$px2->element('invoicesamountinyear')
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
            'text' => $langs->transnoentities("AmountOfBillsByMonthHT"),
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
                        'labelString' => $langs->transnoentitiesnoconv("AmountOfBills"),
                        'fontColor' => 'black',
                    ),
                )
            ),
        ),
    )
);


$px3 = new DolChartJs();
$graph_datas = $stats->getAverageByMonthWithPrevYear($endyear, $startyear, 1, $px3);

$px3->element('invoicesaverage')
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
            'text' => $langs->transnoentities("AmountAverageByMonthHT"),
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
    $arrayyears[$val['year']]=$val['year'];
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;


$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/compta/facture/stats/index.php?mode='.$mode;
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

if ($mode == 'customer') $type='invoice_stats';
elseif ($mode == 'supplier') $type='supplier_invoice_stats';

complete_head_from_modules($conf,$langs,null,$head,$h,$type);

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1);

// We use select_thirdparty_list instead of select_company so we can use $filter and share same code for customer and supplier.
$tmp_companies = $form->select_thirdparty_list($socid, 'socid', $filter, 1, 0, 0, array(), '', 1);
//Array passed as an argument to Form::selectarray to build a proper select input
$companies = array();

foreach ($tmp_companies as $value) {
	$companies[$value['key']] = $value['label'];
}

print '<div class="fichecenter"><div class="fichethirdleft">';


//if (empty($socid))
//{
	// Show filter box
	print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
	// Company
	print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
	if ($mode == 'customer') $filter='s.client in (1,2,3)';
	elseif ($mode == 'supplier') $filter='s.fournisseur = 1';
	print $form->selectarray('socid', $companies, $socid, 1, 0, 0, 'style="width: 95%"', 0, 0, 0, '', '', 1);
	print '</td></tr>';
	// User
	print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>';
	print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
	print '</td></tr>';
	// Status
	print '<tr><td align="left">'.$langs->trans("Status").'</td><td align="left">';
	if ($mode == 'customer')
	{
	    $liststatus=array('0'=>$langs->trans("BillStatusDraft"), '1'=>$langs->trans("BillStatusNotPaid"), '2'=>$langs->trans("BillStatusPaid"), '3'=>$langs->trans("BillStatusCanceled"));
	    print $form->selectarray('object_status', $liststatus, $object_status, 1);
	}
	elseif ($mode == 'supplier')
	{
	    $liststatus=array('0'=>$langs->trans("BillStatusDraft"),'1'=>$langs->trans("BillStatusNotPaid"), '2'=>$langs->trans("BillStatusPaid"));
	    print $form->selectarray('object_status', $liststatus, $object_status, 1);
	}
	print '</td></tr>';
	// Year
	print '<tr><td>'.$langs->trans("Year").'</td><td>';
	if (! in_array($year, $arrayyears)) $arrayyears[$year]=$year;
	if (! in_array($nowyear, $arrayyears)) $arrayyears[$nowyear]=$nowyear;
	arsort($arrayyears);
	print $form->selectarray('year', $arrayyears, $year, 0);
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
print '<td align="right">'.$langs->trans("NumberOfBills").'</td>';
print '<td align="right">%</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">%</td>';
print '<td align="right">'.$langs->trans("AmountAverage").'</td>';
print '<td align="right">%</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val)
{
	$year = $val['year'];
	while ($year && $oldyear > $year+1)
	{	// If we have empty year
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
