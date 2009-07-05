<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/commande/stats/index.php
 *      \ingroup    commande
 *		\brief      Page des stats commandes
 *		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/stats/commandestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

$WIDTH=500;
$HEIGHT=200;

$mode='customer';
if (isset($_GET["mode"])) $mode=$_GET["mode"];

if ($mode == 'customer' && ! $user->rights->commande->lire) accessforbidden();
if ($mode == 'supplier' && ! $user->rights->fournisseur->commande->lire) accessforbidden();

// Security check
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$year = strftime("%Y", time());
$startyear=$year-2;
$endyear=$year;



/*
 * View
 */

llxHeader();

if ($mode == 'customer')
{
	$title=$langs->trans("OrdersStatistics");
	$dir=$conf->commande->dir_temp;
}
if ($mode == 'supplier')
{
	$title=$langs->trans("OrdersStatisticsSuppliers");
	$dir=$conf->fournisseur->dir_output.'/commande/temp';
}

print_fiche_titre($title, $mesg);

create_exdir($dir);

$stats = new CommandeStats($db, $socid, $mode);

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)


if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filenamenb = $dir.'/ordersnbinyear-'.$user->id.'-'.$year.'.png';
	if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersnbinyear-'.$user->id.'-'.$year.'.png';
	if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersnbinyear-'.$user->id.'-'.$year.'.png';
}
else
{
	$filenamenb = $dir.'/ordersnbinyear-'.$year.'.png';
	if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersnbinyear-'.$year.'.png';
	if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersnbinyear-'.$year.'.png';
}

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
    $px->SetYLabel($langs->trans("NbOfOrder"));
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->mode='depth';
	$px->SetTitle($langs->trans("NumberOfOrdersByMonth"));
	$px->draw($filenamenb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filenameamount = $dir.'/ordersamountinyear-'.$user->id.'-'.$year.'.png';
	if ($mode == 'customer') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamountinyear-'.$user->id.'-'.$year.'.png';
	if ($mode == 'supplier') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamountinyear-'.$user->id.'-'.$year.'.png';
}
else
{
	$filenameamount = $dir.'/ordersamountinyear-'.$year.'.png';
	if ($mode == 'customer') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamountinyear-'.$year.'.png';
	if ($mode == 'supplier') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamountinyear-'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
	$px->SetData($data);
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
	$px->SetYLabel($langs->trans("AmountOfOrders"));
	$px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
	$px->mode='depth';
	$px->SetTitle($langs->trans("AmountOfOrdersByMonthHT"));

	$px->draw($filenameamount);
}

print '<table class="notopnoleftnopadd" width="100%"><tr>';
print '<td align="center" valign="top">';

// Show array
$data = $stats->getAllByYear();

print '<table class="border" width="100%">';
print '<tr height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="center">'.$langs->trans("NbOfOrders").'</td>';
print '<td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val)
{
	$year = $val['year'];
	while ($year && $oldyear > $year+1)
	{	// If we have empty year
		$oldyear--;
		print '<tr height="24">';
		print '<td align="center"><a href="month.php?year='.$oldyear.'&amp;mode='.$mode.'">'.$oldyear.'</a></td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '<td align="right">0</td>';
		print '</tr>';
	}
	print '<tr height="24">';
    print '<td align="center"><a href="month.php?year='.$year.'&amp;mode='.$mode.'">'.$year.'</a></td>';
	print '<td align="right">'.$val['nb'].'</td>';
	print '<td align="right">'.price(price2num($val['total'],'MT'),1).'</td>';
	print '<td align="right">'.price(price2num($val['avg'],'MT'),1).'</td>';
	print '</tr>';
	$oldyear=$year;
}

print '</table>';


print '</td>';
print '<td align="center" valign="top">';

// Show graphs
print '<table class="border" width="100%"><tr valign="top"><td align="center">';
if ($mesg) { print $mesg; }
else {
	print '<img src="'.$fileurlnb.'" title="'.$langs->trans("NbOfOrders").'" alt="'.$langs->trans("NbOfProposals").'">';
	print "<br>\n";
	print '<img src="'.$fileurlamount.'" title="'.$langs->trans("AmountTotal").'" alt="'.$langs->trans("AmountTotal").'">';
}
print '</td></tr></table>';

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
