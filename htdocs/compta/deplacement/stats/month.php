<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	    \file       htdocs/compta/deplacement/stats/month.php
 *      \ingroup    facture
 *		\brief      Page des stats notes de frais par mois
 */

require("../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/deplacement/class/deplacementstats.class.php");

$langs->load("trips");

$GRAPHWIDTH=500;
$GRAPHHEIGHT=200;

// Check security access
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$year = isset($_GET["year"])?$_GET["year"]:date("Y",time());

$mode='customer';
if (isset($_GET["mode"])) $mode=$_GET["mode"];



/*
 * View
 */

llxHeader();

$title=$langs->trans("TripsAndExpensesStatistics");
$dir=$conf->deplacement->dir_temp;

$mesg = '<a href="month.php?year='.($year - 1).'&amp;mode='.$mode.'">'.img_previous().'</a> ';
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'&amp;mode='.$mode.'">'.img_next().'</a>';
print_fiche_titre($title, $mesg);

create_exdir($dir);

$stats = new DeplacementStats($db, $socid);


$data = $stats->getNbByMonth($year);

$filename = $dir."/tripsexpensesnb-".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=tripsexpensesstats&file=tripsexpensesnb-'.$year.'.png';

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (! $mesg)
{
    $px1->SetData($data);
    $px1->SetMaxValue($px1->GetCeilMaxValue());
    $px1->SetMinValue($px1->GetFloorMinValue());
    $px1->SetWidth($GRAPHWIDTH);
    $px1->SetHeight($GRAPHHEIGHT);
    $px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->SetPrecisionY(0);
    $px1->draw($filename,$fileurl);
}



$data = $stats->getAmountByMonth($year);

$filename_amount = $dir."/tripsexpensesamount-".$year.".png";
$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=tripsexpensesstats&file=tripsexpensesamount-'.$year.'.png';

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (! $mesg)
{
    $px2->SetData($data);
    $px2->SetYLabel($langs->trans("AmountTotal"));
    $px2->SetMaxValue($px2->GetCeilMaxValue());
    $px2->SetMinValue($px2->GetFloorMinValue());
    $px2->SetWidth($GRAPHWIDTH);
    $px2->SetHeight($GRAPHHEIGHT);
    $px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->SetPrecisionY(0);
    $px2->draw($filename_amount,$fileurl_amount);
}



$res = $stats->getAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b"),0,3)), $res[$i]);
}

$filename_avg = $dir."/tripsexpensesaverage-".$year.".png";
$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=tripsexpensesstats&file=tripsexpensesaverage-'.$year.'.png';

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (! $mesg)
{
    $px3->SetData($data);
    $px3->SetYLabel($langs->trans("AmountAverage"));
    $px3->SetMaxValue($px3->GetCeilMaxValue());
    $px3->SetMinValue($px3->GetFloorMinValue());
    $px3->SetWidth($GRAPHWIDTH);
    $px3->SetHeight($GRAPHHEIGHT);
    $px3->SetShading(3);
	$px3->SetHorizTickIncrement(1);
	$px3->SetPrecisionY(0);
    $px3->draw($filename_avg,$fileurl_avg);
}

print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("NumberByMonth").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print $px1->show(); }
print '</td></tr>';
print '<tr><td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print $px2->show(); }
print '</td></tr>';
print '<tr><td align="center">'.$langs->trans("AmountAverage").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print $px3->show(); }
print '</td></tr></table>';

$db->close();

llxFooter();
?>
