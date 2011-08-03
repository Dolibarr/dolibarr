<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/comm/propal/stats/month.php
        \ingroup    propale
		\brief      Page des stats propositions commerciales par mois
		\version    $Id: month.php,v 1.27 2011/08/03 00:46:38 eldy Exp $
*/

require("../../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propalestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");

$GRAPHWIDTH=500;
$GRAPHHEIGHT=200;

// Check security access
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$year = isset($_GET["year"])?$_GET["year"]:date("Y",time());


/*
 * View
 */

llxHeader();

$dir=$conf->propale->dir_temp;

$mesg = '<a href="month.php?year='.($year - 1).'">'.img_previous().'</a> ';
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'">'.img_next().'</a>';
print_fiche_titre($langs->trans("ProposalsStatistics"), $mesg);

create_exdir($dir);

$stats = new PropaleStats($db, $socid);


$data = $stats->getNbByMonth($year);

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename = $dir.'/proposalsnb-'.$user->id.'-'.$year.'.png';
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsnb-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename = $dir.'/proposalsnb-'.$year.'.png';
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsnb-'.$year.'.png';
}

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

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_amount = $dir.'/proposalsamount-'.$user->id.'-'.$year.'.png';
	$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsamount-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_amount = $dir.'/proposalsamount-'.$year.'.png';
	$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsamount-'.$year.'.png';
}

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
  $data[$i-1] = array(ucfirst(substr(dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b"),0,3)), $res[$i]);
}

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_avg = $dir.'/proposalsaverage-'.$user->id.'-'.$year.'.png';
	$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsaverage-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_avg = $dir.'/proposalsaverage-'.$year.'.png';
	$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=proposalsaverage-'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
	$px->SetPrecisionY(0);
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
print '<tr><td align="center">'.$langs->trans("NumberOfProposalsByMonth").'</td>';
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

llxFooter('$Date: 2011/08/03 00:46:38 $ - $Revision: 1.27 $');
?>
