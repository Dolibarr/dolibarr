<?php
<<<<<<< HEAD
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
=======
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009  Laurent Destailleur     <eldy@users.sourceforge.net>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *    \file       htdocs/expedition/stats/month.php
 *    \ingroup    commande
 *    \brief      Page des stats expeditions par mois
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expeditionstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

<<<<<<< HEAD
=======
$year = GETPOST('year', 'int');

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

/*
 * View
 */

llxHeader();

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height');

$mesg = '';

<<<<<<< HEAD
print load_fiche_titre($langs->trans("StatisticsOfSendings").' '.$_GET["year"], $mesg);

$stats = new ExpeditionStats($db);
$data = $stats->getNbExpeditionByMonth($_GET["year"]);
=======
print load_fiche_titre($langs->trans("StatisticsOfSendings").' '.$year, $mesg);

$stats = new ExpeditionStats($db);
$data = $stats->getNbExpeditionByMonth($year);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

dol_mkdir($conf->expedition->dir_temp);

$filename = $conf->expedition->dir_temp."/expedition".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=expeditionstats&file=expedition'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
<<<<<<< HEAD
if (! $mesg)
{
=======
if (! $mesg) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    $px->SetData($data);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
<<<<<<< HEAD
    $px->SetYLabel($langs->trans("NbOfOrders"));
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename,$fileurl);
}

print '<table class="border" width="100%">';
print '<tr><td align="center">Nombre d expedition par mois</td>';
print '<td align="center">';
=======
    $px->SetYLabel($langs->trans("NbOfSendings"));
    $px->SetShading(3);
    $px->SetHorizTickIncrement(1);
    $px->draw($filename, $fileurl);
}

print '<table class="border" width="100%">';
print '<tr><td class="center">'.$langs->trans("NbOfSendingsByMonth").'</td>';
print '<td class="center">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print $px->show();
print '</td></tr>';
print '</table>';

<<<<<<< HEAD
llxFooter();

=======
// End of page
llxFooter();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$db->close();
