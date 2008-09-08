<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	    \file       htdocs/compta/facture/stats/month.php
 *      \ingroup    facture
 *		\brief      Page des stats factures par mois
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

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

if ($mode == 'customer') 
{
	$title=$langs->trans("BillsStatistics");
	$dir=$conf->facture->dir_temp;
}
if ($mode == 'supplier') 
{
	$title=$langs->trans("BillsStatisticsSuppliers");
	$dir=$conf->fournisseur->facture->dir_temp;
}

$mesg = '<a href="month.php?year='.($year - 1).'&amp;mode='.$mode.'">'.img_previous().'</a> ';
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'&amp;mode='.$mode.'">'.img_next().'</a>';
print_fiche_titre($title, $mesg);

create_exdir($dir);

$stats = new FactureStats($db, $socid, $mode);


$data = $stats->getNbByMonth($year);

$filename = $dir."/invoicesnb-".$year.".png";
if ($mode == 'customer') $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=invoicesnb-'.$year.'.png';
if ($mode == 'supplier') $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=billstatssupplier&file=invoicesnb-'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename);
}



$data = $stats->getAmountByMonth($year);

$filename_amount = $dir."/invoicesamount-".$year.".png";
if ($mode == 'customer') $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=invoicesamount-'.$year.'.png';
if ($mode == 'supplier') $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=billstatssupplier&file=invoicesamount-'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename_amount);
}



$res = $stats->getAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(strftime("%b",dolibarr_mktime(12,12,12,$i,1,$year)),0,3)), $res[$i]);
}

$filename_avg = $dir."/invoicesaverage-".$year.".png";
if ($mode == 'customer') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=invoicesaverage-'.$year.'.png';
if ($mode == 'supplier') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=billstatssupplier&file=invoicesaverage-'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename_avg);
}

print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("NumberOfBillsByMonth").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.$fileurl.'">'; }
print '</td></tr>';
print '<tr><td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.$fileurl_amount.'">'; }
print '</td></tr>';
print '<tr><td align="center">'.$langs->trans("AmountAverage").'</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.$fileurl_avg.'">'; }
print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
