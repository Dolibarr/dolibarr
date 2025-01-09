<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *    \file       htdocs/reception/stats/month.php
 *    \ingroup    order
 *    \brief      Page des stats receptions par mois
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/receptionstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$year = GETPOSTINT("year");
$socid = GETPOSTINT("socid");
$userid = GETPOSTINT("userid");

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'reception', 0, '');


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-reception page-stats_month');

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$mesg = '';

print load_fiche_titre($langs->trans("StatisticsOfReceptions").' '.GETPOSTINT("year"), $mesg);
$stats = new ReceptionStats($db, $socid, '', ($userid > 0 ? $userid : 0));
$data = $stats->getNbByMonth($year);

dol_mkdir($conf->reception->dir_temp);

$filename = $conf->reception->dir_temp."/reception".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=receptionstats&file=reception'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (!$mesg) {
	$px->SetData($data);
	$px->SetMaxValue($px->GetCeilMaxValue());
	$px->SetWidth($WIDTH);
	$px->SetHeight($HEIGHT);
	$px->SetYLabel($langs->trans("NbOfOrders"));
	$px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->draw($filename, $fileurl);
}

print '<table class="border centpercent">';
print '<tr><td class="center">Nombre d reception par mois</td>';
print '<td class="center">';
print $px->show();
print '</td></tr>';
print '</table>';

llxFooter();

$db->close();
