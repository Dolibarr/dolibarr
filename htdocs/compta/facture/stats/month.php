<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
	    \file       htdocs/compta/facture/stats/month.php
        \ingroup    facture
		\brief      Page des stats factures par mois
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/dolgraph.class.php");

$GRAPHWIDTH=500;
$GRAPHHEIGHT=200;

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


llxHeader();

$year = isset($_GET["year"])?$_GET["year"]:date("Y",time());

$mesg = '<a href="month.php?year='.($year - 1).'">'.img_previous().'</a> ';
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'">'.img_next().'</a>';

print_fiche_titre($langs->trans("BillsStatistics"), $mesg);

$stats = new FactureStats($db, $socid);
$data = $stats->getNbByMonth($year);

create_exdir($conf->facture->dir_temp);

$filename = $conf->facture->dir_temp."/facture".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=facture'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetMaxValue($px->GetCeilMaxValue());
	$px->SetPrecisionY(0);
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
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

$filename_amount = $conf->facture->dir_temp."/factureamount".$year.".png";
$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=factureamount'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->SetMaxValue($px->GetCeilMaxValue());
	$px->SetPrecisionY(0);
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

$filename_avg = $conf->facture->dir_temp."/factureaverage".$year.".png";
$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=factureaverage'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
	$px->SetPrecisionY(0);
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
    $px->SetShading(5);
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
