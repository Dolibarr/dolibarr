<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
<<<<<<< HEAD
=======
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereportstats.class.php';

// Load translation files required by the page
$langs->loadLangs(array('trips', 'companies'));

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height');

$mode=GETPOST("mode")?GETPOST("mode"):'customer';
$object_status=GETPOST('object_status');

<<<<<<< HEAD
$userid=GETPOST('userid','int');
$socid=GETPOST('socid','int'); if ($socid < 0) $socid=0;
$id = GETPOST('id','int');
=======
$userid=GETPOST('userid', 'int');
$socid=GETPOST('socid', 'int'); if ($socid < 0) $socid=0;
$id = GETPOST('id', 'int');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Security check
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}
if ($user->societe_id) $socid=$user->societe_id;
<<<<<<< HEAD
$result = restrictedArea($user, 'expensereport', $id,'');
=======
$result = restrictedArea($user, 'expensereport', $id, '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

$nowyear=strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
//$startyear=$year-2;
$startyear=$year-1;
$endyear=$year;



/*
 * View
 */

$form=new Form($db);
$tmpexpensereport=new ExpenseReport($db);

$title=$langs->trans("TripsAndExpensesStatistics");
$dir=$conf->expensereport->dir_temp;

llxHeader('', $title);

print load_fiche_titre($title, $mesg);

dol_mkdir($dir);

$stats = new ExpenseReportStats($db, $socid, $userid);
if ($object_status != '' && $object_status >= -1) $stats->where .= ' AND e.fk_statut IN ('.$db->escape($object_status).')';

// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
//print "$endyear, $startyear";
<<<<<<< HEAD
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
=======
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
//var_dump($data);

$filenamenb = $dir."/tripsexpensesnbinyear-".$year.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=tripsexpensesstats&amp;file=tripsexpensesnbinyear-'.$year.'.png';

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (! $mesg)
{
	$px1->SetData($data);
<<<<<<< HEAD
	$px1->SetPrecisionY(0);
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$i=$startyear;$legend=array();
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("Number"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
<<<<<<< HEAD
	$px1->SetPrecisionY(0);
	$px1->mode='depth';
	$px1->SetTitle($langs->trans("NumberByMonth"));

	$px1->draw($filenamenb,$fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
=======
	$px1->mode='depth';
	$px1->SetTitle($langs->trans("NumberByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir."/tripsexpensesamountinyear-".$year.".png";
$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=tripsexpensesstats&amp;file=tripsexpensesamountinyear-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (! $mesg)
{
	$px2->SetData($data);
	$i=$startyear;$legend=array();
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
<<<<<<< HEAD
	$px2->SetMinValue(min(0,$px2->GetFloorMinValue()));
=======
	$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("Amount"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
<<<<<<< HEAD
	$px2->SetPrecisionY(0);
	$px2->mode='depth';
	$px2->SetTitle($langs->trans("AmountTotal"));

	$px2->draw($filenameamount,$fileurlamount);
=======
	$px2->mode='depth';
	$px2->SetTitle($langs->trans("AmountTotal"));

	$px2->draw($filenameamount, $fileurlamount);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}


$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

if (!$user->rights->societe->client->voir || $user->societe_id)
{
    $filename_avg = $dir.'/ordersaverage-'.$user->id.'-'.$year.'.png';
    if ($mode == 'customer') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$user->id.'-'.$year.'.png';
    if ($mode == 'supplier') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$user->id.'-'.$year.'.png';
}
else
{
    $filename_avg = $dir.'/ordersaverage-'.$year.'.png';
    if ($mode == 'customer') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$year.'.png';
    if ($mode == 'supplier') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$year.'.png';
}

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (! $mesg)
{
    $px3->SetData($data);
    $i = $startyear;$legend=array();
    while ($i <= $endyear)
    {
        $legend[]=$i;
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
<<<<<<< HEAD
    $px3->SetPrecisionY(0);
    $px3->mode='depth';
    $px3->SetTitle($langs->trans("AmountAverage"));

    $px3->draw($filename_avg,$fileurl_avg);
=======
    $px3->mode='depth';
    $px3->SetTitle($langs->trans("AmountAverage"));

    $px3->draw($filename_avg, $fileurl_avg);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}


// Show array
$data = $stats->getAllByYear();
$arrayyears=array();
foreach($data as $val) {
    $arrayyears[$val['year']]=$val['year'];
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;


$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/expensereport/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

<<<<<<< HEAD
complete_head_from_modules($conf,$langs,null,$head,$h,'trip_stats');
=======
complete_head_from_modules($conf, $langs, null, $head, $h, 'trip_stats');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
$filter='';
print $form->select_company($socid,'socid',$filter,1,1,0,array(),0,'','style="width: 95%"');
=======
print $form->select_company($socid,'socid','',1,1,0,array(),0,'','style="width: 95%"');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '</td></tr>';
*/
// User
print '<tr><td>'.$langs->trans("User").'</td><td>';
$include='';
if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)) $include='hierarchy';
print $form->select_dolusers($userid, 'userid', 1, '', 0, $include, '', 0, 0, 0, '', 0, '', 'maxwidth300');
print '</td></tr>';
// Status
<<<<<<< HEAD
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
=======
print '<tr><td class="left">'.$langs->trans("Status").'</td><td class="left">';
$liststatus=$tmpexpensereport->statuts;
print $form->selectarray('object_status', $liststatus, GETPOST('object_status', 'int'), -4, 0, 0, '', 1);
print '</td></tr>';
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
if (! in_array($year, $arrayyears)) $arrayyears[$year]=$year;
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0);
print '</td></tr>';
print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '</table>';
print '</form>';
print '<br><br>';

<<<<<<< HEAD
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre" height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("AmountTotal").'</td>';
print '<td align="right">'.$langs->trans("AmountAverage").'</td>';
=======
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre" height="24">';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("Number").'</td>';
print '<td class="right">'.$langs->trans("AmountTotal").'</td>';
print '<td class="right">'.$langs->trans("AmountAverage").'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '</tr>';

$oldyear=0;
foreach ($data as $val)
{
	$year = $val['year'];
	while ($year && $oldyear > $year+1)
	{	// If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
<<<<<<< HEAD
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.'">'.$oldyear.'</a></td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
=======
		print '<td class="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.'">'.$oldyear.'</a></td>';
		print '<td class="right">0</td>';
		print '<td class="right">0</td>';
		print '<td class="right">0</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		print '</tr>';
	}


	print '<tr class="oddeven" height="24">';
<<<<<<< HEAD
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.'">'.$year.'</a></td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '<td align="right">'.price(price2num($val['total'],'MT'),1).'</td>';
	print '<td align="right">'.price(price2num($val['avg'],'MT'),1).'</td>';
=======
	print '<td class="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right">'.price(price2num($val['total'], 'MT'), 1).'</td>';
	print '<td class="right">'.price(price2num($val['avg'], 'MT'), 1).'</td>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print '</tr>';
	$oldyear=$year;
}

print '</table>';
<<<<<<< HEAD

=======
print '</div>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
<<<<<<< HEAD
print '<table class="border" width="100%"><tr class="pair nohover"><td align="center">';
=======
print '<table class="border" width="100%"><tr class="pair nohover"><td class="center">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if ($mesg) { print $mesg; }
else {
    print $px1->show();
	print "<br>\n";
	print $px2->show();
    print "<br>\n";
    print $px3->show();
}
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';


dol_fiche_end();

<<<<<<< HEAD

llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
