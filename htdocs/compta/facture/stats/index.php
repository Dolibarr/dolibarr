<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *  \file       htdocs/compta/facture/stats/index.php
 *  \ingroup    facture
 *  \brief      Page des stats factures
 *  \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

$WIDTH=500;
$HEIGHT=200;

// Sécurité accés client
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("BillsStatistics"), $mesg);

create_exdir($conf->facture->dir_temp);

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;

$stats = new FactureStats($db, $socid);


// Build graphic number of invoices
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenamenbinvoices = $conf->facture->dir_temp."/invoicesnbinyear-".$year.".png";
$fileurlnbinvoices = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&amp;file=invoicesnbinyear-'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
	$px->SetData($data);
	$px->SetPrecisionY(0);
	$i=$startyear;
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px->SetLegend($legend);
	$px->SetMaxValue($px->GetCeilMaxValue());
	$px->SetWidth($WIDTH);
	$px->SetHeight($HEIGHT);
	$px->SetYLabel($langs->trans("NumberOfBills"));
	$px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
	$px->mode='depth';
	$px->SetTitle($langs->trans("NumberOfBillsByMonth"));

	$px->draw($filenamenbinvoices);
}

// Build graphic amount of invoices
$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamountinvoices = $conf->facture->dir_temp."/invoicesamountinyear-".$year.".png";
$fileurlamountinvoices = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&amp;file=invoicesamountinyear-'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
	$px->SetData($data);
	$px->SetPrecisionY(0);
	$i=$startyear;
	while ($i <= $endyear)
	{
		$legend[]=$i;
		$i++;
	}
	$px->SetLegend($legend);
	$px->SetMaxValue($px->GetCeilMaxValue());
	$px->SetMinValue(min(0,$px->GetFloorMinValue()));
	$px->SetWidth($WIDTH);
	$px->SetHeight($HEIGHT);
	$px->SetYLabel($langs->trans("AmountOfBills"));
	$px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
	$px->mode='depth';
	$px->SetTitle($langs->trans("AmountOfBillsByMonthHT"));

	$px->draw($filenameamountinvoices);
}


// Show array
$sql = "SELECT date_format(datef,'%Y') as dm, count(*) as nb, sum(total) as total FROM ".MAIN_DB_PREFIX."facture WHERE fk_statut > 0 ";
if ($socid)
{
	$sql .= " AND fk_soc=$socid";
}
$sql.= " GROUP BY dm DESC;";
$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	print '<table class="border" width="100%">';
	print '<tr height="24"><td align="center">'.$langs->trans("Year").'</td><td width="10%" align="center">'.$langs->trans("NumberOfBills").'</td><td align="center">'.$langs->trans("AmountTotal").'</td>';
	print '<td align="center" valign="top" rowspan="'.($num + 2).'">';
	if ($mesg) { print $mesg; }
	else {
		print '<img src="'.$fileurlnbinvoices.'" title="'.$langs->trans("NumberOfBills").'" alt="'.$langs->trans("NumberOfBills").'">';
		print "<br>\n";
		print '<img src="'.$fileurlamountinvoices.'" title="'.$langs->trans("AmountOfBills").'" alt="'.$langs->trans("AmountOfBills").'">';
	}
	print '</td></tr>';

	while ($obj = $db->fetch_object($resql))
	{
		$nbproduct = $obj->nb;
		$year = $obj->dm;
		$total = price($obj->total);
		print '<tr height="24">';
		print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td>';
		print '<td align="right">'.$nbproduct.'</td>';
		print '<td align="right">'.$total.'</td></tr>';

	}
	
	print '<tr><td colspan="3"></td></tr>';
	
	print '</table>';
	$db->free($resql);
}
else
{
	dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
