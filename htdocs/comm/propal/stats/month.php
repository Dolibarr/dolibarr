<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
	    \file       htdocs/comm/propal/stats/month.php
        \ingroup    propale
		\brief      Page des stats propositions commerciales par mois
		\version    $Revision$
*/

require("./pre.inc.php");
require("./propalestats.class.php");

llxHeader();

$year = isset($_GET["year"])?$_GET["year"]:date("Y",time());

$mesg = '<a href="month.php?year='.($year - 1).'">'.img_previous().'</a> ';
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'">'.img_next().'</a>';

/*
 *
 *
 */

print_fiche_titre('Statistiques des propositions commerciales', $mesg);

$stats = new PropaleStats($db);
$data = $stats->getNbByMonth($year);

if (! is_dir($conf->propal->dir_images)) { mkdir($conf->propal->dir_images); }

$filename = $conf->propal->dir_images."/propale$year.png";
$fileurl = $conf->propal->url_images."/propale$year.png";

$px = new BarGraph($data);
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetMaxValue($px->GetMaxValue());
    $px->SetWidth(500);
    $px->SetHeight(280);
    $px->draw($filename, $data, $year);
}

$res = $stats->getAmountByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(strftime("%b",mktime(12,12,12,$i,1,$year)), $res[$i]);
}

$filename_amount = $conf->propal->dir_images."/propaleamount$year.png";
$fileurl_amount = $conf->propal->url_images."/propaleamount$year.png";

$px = new BarGraph($data);
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->SetMaxValue($px->GetAmountMaxValue());
    $px->SetWidth(500);
    $px->SetHeight(250);
    $px->draw($filename_amount, $data, $year);
}
$res = $stats->getAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(strftime("%b",mktime(12,12,12,$i,1,$year)), $res[$i]);
}

$filename_avg = $conf->propal->dir_images."/propalaverage$year.png";
$fileurl_avg = $conf->propal->url_images."/propalaverage$year.png";

$px = new BarGraph($data);
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->SetMaxValue($px->GetAmountMaxValue());
    $px->SetWidth(500);
    $px->SetHeight(250);
    $px->draw($filename_avg, $data, $year);
}

print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
print '<tr><td align="center">Nombre par mois</td>';
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

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
