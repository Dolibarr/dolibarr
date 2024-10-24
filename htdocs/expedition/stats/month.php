<?php
/* Copyright (C) 2001-2003  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009  Laurent Destailleur     <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/expedition/stats/month.php
 *    \ingroup    order
 *    \brief      Page des stats expeditions par mois
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expeditionstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$year = GETPOSTINT('year');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
restrictedArea($user, 'expedition');


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-expedition page-stats_month');

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$mesg = '';
$mode = '';

print load_fiche_titre($langs->trans("StatisticsOfSendings").' '.$year, $mesg);

$stats = new ExpeditionStats($db, $socid, $mode);
$data = $stats->getNbByMonth($year);

dol_mkdir($conf->expedition->dir_temp);

$filename = $conf->expedition->dir_temp."/expedition".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=expeditionstats&file=expedition'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (!$mesg) {
	$px->SetData($data);
	$px->SetMaxValue($px->GetCeilMaxValue());
	$px->SetWidth($WIDTH);
	$px->SetHeight($HEIGHT);
	$px->SetYLabel($langs->trans("NbOfSendings"));
	$px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->draw($filename, $fileurl);
}

print '<table class="border centpercent">';
print '<tr><td class="center">'.$langs->trans("NbOfSendingsByMonth").'</td>';
print '<td class="center">';
print $px->show();
print '</td></tr>';
print '</table>';

// End of page
llxFooter();
$db->close();
