<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/comm/propal/stats/index.php
        \ingroup    propale
		\brief      Page des stats propositions commerciales
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/stats/propalestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

$WIDTH=500;
$HEIGHT=200;

// Sécurité accés client
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

print_fiche_titre($langs->trans("ProposalsStatistics"), $mesg);

$dir=$conf->propale->dir_temp;

create_exdir($dir);

$stats = new PropaleStats($db, $socid);

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)


if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filenamenb = $dir.'/proposalsnbinyear-'.$user->id.'-'.$year.'.png';
	$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsnbinyear-'.$user->id.'-'.$year.'.png';
}
else
{
	$filenamenb = $dir.'/proposalsnbinyear-'.$year.'.png';
	$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsnbinyear-'.$year.'.png';
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
	$px->SetYLabel($langs->trans("NbOfProposals"));
	$px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
	$px->mode='depth';
	$px->SetTitle($langs->trans("NumberOfProposalsByMonth"));

  $px->draw($filenamenb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear,$startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filenameamount = $dir.'/proposalsamountinyear-'.$user->id.'-'.$year.'.png';
	$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsamountinyear-'.$user->id.'-'.$year.'.png';
}
else
{
	$filenameamount = $dir.'/proposalsamountinyear-'.$year.'.png';
	$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsamountinyear-'.$year.'.png';
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
	$px->SetYLabel($langs->trans("AmountOfProposals"));
	$px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
	$px->mode='depth';
	$px->SetTitle($langs->trans("AmountOfProposalsByMonthHT"));

	$px->draw($filenameamount);
}

print '<table class="notopnoleftnopadd" width="100%"><tr>';
print '<td align="center" valign="top">';

// Show array
$data = $stats->getAllByYear();

print '<table class="border" width="100%">';
print '<tr height="24">';
print '<td align="center">'.$langs->trans("Year").'</td>';
print '<td align="center">'.$langs->trans("NbOfProposals").'</td>';
print '<td align="center">'.$langs->trans("AmountTotal").'</td>';
print '<td align="center">'.$langs->trans("AmountAverage").'</td>';
print '</tr>';
  
$oldyear=0;
foreach ($data as $val)
{
	$year = $val['year'];
	print $avg;
	while ($oldyear > $year+1)
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
    print '<td align="center"><a href="month.php?year='.$year.'">'.$year.'</a></td>';
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
	print '<img src="'.$fileurlnb.'" title="'.$langs->trans("NbOfProposals").'" alt="'.$langs->trans("NbOfProposals").'">';
	print "<br>\n";
	print '<img src="'.$fileurlamount.'" title="'.$langs->trans("AmountTotal").'" alt="'.$langs->trans("AmountTotal").'">';
}
print '</td></tr></table>';

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
