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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
	    \file       htdocs/comm/propal/stats/month.php
        \ingroup    propale
		\brief      Page des stats propositions commerciales par mois
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/comm/propal/stats/propalestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");


llxHeader();

$year = isset($_GET["year"])?$_GET["year"]:date("Y",time());

$mesg = '<a href="month.php?year='.($year - 1).'">'.img_previous().'</a> ';
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'">'.img_next().'</a>';

$WIDTH=500;
$HEIGHT=200;

/*
 *
 *
 */

print_fiche_titre($langs->trans("ProposalsStatistics"), $mesg);

$stats = new PropaleStats($db);
$data = $stats->getNbByMonth($year);

create_exdir($conf->propal->dir_temp);

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename = $conf->propal->dir_temp.'/propale-'.$user->id.'-'.$year.'.png';
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propale-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename = $conf->propal->dir_temp.'/propale'.$year.'.png';
	$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propale'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
	$px->SetPrecisionY(0);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename);
}

$res = $stats->getAmountByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(strftime("%b",dolibarr_mktime(12,12,12,$i,1,$year)),0,3)), $res[$i]);
}

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_amount = $conf->propal->dir_temp.'/propaleamount-'.$user->id.'-'.$year.'.png';
	$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleamount-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_amount = $conf->propal->dir_temp.'/propaleamount'.$year.'.png';
	$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleamount'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
	$px->SetPrecisionY(0);
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename_amount, $data, $year);
}
$res = $stats->getAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(strftime("%b",dolibarr_mktime(12,12,12,$i,1,$year)),0,3)), $res[$i]);
}

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_avg = $conf->propal->dir_temp.'/propaleaverage-'.$user->id.'-'.$year.'.png';
	$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleaverage-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_avg = $conf->propal->dir_temp.'/propaleaverage'.$year.'.png';
	$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=propalstats&file=propaleaverage'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
	$px->SetPrecisionY(0);
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
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

llxFooter('$Date$ - $Revision$');
?>
