<?php
/* Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height');

$mode='customer';
if (! $user->rights->ficheinter->lire) accessforbidden();

$userid=GETPOST('userid','int');
$socid=GETPOST('socid','int');
// Security check
if ($user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$nowyear=strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
//$startyear=$year-2;
$startyear=$year-1;
$endyear=$year;

$object_status=GETPOST('object_status');

$langs->loadLangs(array('interventions', 'companies', 'other', 'suppliers'));


/*
 * View
 */

$form=new Form($db);
$objectstatic=new FichInter($db);

$title=$langs->trans("InterventionStatistics");
$dir=$conf->ficheinter->dir_temp;

llxHeader('', $title);

print load_fiche_titre($title,'','title_commercial.png');

dol_mkdir($dir);

$stats = new FichinterStats($db, $socid, $mode, ($userid>0?$userid:0));
if ($object_status != '' && $object_status > -1) $stats->where .= ' AND c.fk_statut IN ('.$db->escape($object_status).')';

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
// $data = array(array('Lib',val1,val2,val3),...)


if (!$user->rights->societe->client->voir || $user->societe_id)
{
    $filenamenb = $dir.'/interventionsnbinyear-'.$user->id.'-'.$year.'.png';
    $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=interventionstats&file=interventionsnbinyear-'.$user->id.'-'.$year.'.png';
}
else
{
    $filenamenb = $dir.'/interventionsnbinyear-'.$year.'.png';
    $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=interventionstats&file=interventionsnbinyear-'.$year.'.png';
}

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (! $mesg)
{
    $px1->SetData($data);
    $px1->SetPrecisionY(0);
    $i=$startyear;$legend=array();
    while ($i <= $endyear)
    {
        $legend[]=$i;
        $i++;
    }
    $px1->SetLegend($legend);
    $px1->SetMaxValue($px1->GetCeilMaxValue());
    $px1->SetMinValue(min(0,$px1->GetFloorMinValue()));
    $px1->SetWidth($WIDTH);
    $px1->SetHeight($HEIGHT);
    $px1->SetYLabel($langs->trans("NbOfIntervention"));
    $px1->SetShading(3);
    $px1->SetHorizTickIncrement(1);
    $px1->SetPrecisionY(0);
    $px1->mode='depth';
    $px1->SetTitle($langs->trans("NumberOfInterventionsByMonth"));

    $px1->draw($filenamenb,$fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
// $data = array(array('Lib',val1,val2,val3),...)

if (!$user->rights->societe->client->voir || $user->societe_id)
{
    $filenameamount = $dir.'/interventionsamountinyear-'.$user->id.'-'.$year.'.png';
    $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=interventionstats&file=interventionsamountinyear-'.$user->id.'-'.$year.'.png';
}
else
{
    $filenameamount = $dir.'/interventionsamountinyear-'.$year.'.png';
    $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=interventionstats&file=interventionsamountinyear-'.$year.'.png';
}

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
    $px2->SetMinValue(min(0,$px2->GetFloorMinValue()));
    $px2->SetWidth($WIDTH);
    $px2->SetHeight($HEIGHT);
    $px2->SetYLabel($langs->trans("AmountOfinterventions"));
    $px2->SetShading(3);
    $px2->SetHorizTickIncrement(1);
    $px2->SetPrecisionY(0);
    $px2->mode='depth';
    $px2->SetTitle($langs->trans("AmountOfinterventionsByMonthHT"));

    $px2->draw($filenameamount,$fileurlamount);
}


$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

if (!$user->rights->societe->client->voir || $user->societe_id)
{
    $filename_avg = $dir.'/interventionsaverage-'.$user->id.'-'.$year.'.png';
    $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=interventionstats&file=interventionsaverage-'.$user->id.'-'.$year.'.png';
}
else
{
    $filename_avg = $dir.'/interventionsaverage-'.$year.'.png';
    $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=interventionstats&file=interventionsaverage-'.$year.'.png';
}

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (! $mesg)
{
    $px3->SetData($data);
    $i=$startyear;$legend=array();
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
    $px3->SetPrecisionY(0);
    $px3->mode='depth';
    $px3->SetTitle($langs->trans("AmountAverage"));

    $px3->draw($filename_avg,$fileurl_avg);
}



// Show array
$data = $stats->getAllByYear();
$arrayyears=array();
foreach($data as $val) {
	if (! empty($val['year'])) {
		$arrayyears[$val['year']]=$val['year'];
	}
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;

$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/fichinter/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

$type = 'fichinter_stats';

complete_head_from_modules($conf,$langs,null,$head,$h,$type);

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


//if (empty($socid))
//{
	// Show filter box
	print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="mode" value="'.$mode.'">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
	// Company
	print '<tr><td align="left">'.$langs->trans("ThirdParty").'</td><td align="left">';
    $filter = 's.client in (1,2,3)';
	print $form->select_company($socid, 'socid', $filter, 1, 0, 0, array(), 0, '', 'style="width: 95%"');
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
foreach ($data as $val)
{
	$year = $val['year'];
	while (! empty($year) && $oldyear > $year+1)
	{
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


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
print '<table class="border" width="100%"><tr class="pair nohover"><td align="center">';
if ($mesg) { print $mesg; }
else {
    print $px1->show();
    /*print "<br>\n";
    print $px2->show();
    print "<br>\n";
    print $px3->show();*/
}
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';

dol_fiche_end();


llxFooter();

$db->close();
