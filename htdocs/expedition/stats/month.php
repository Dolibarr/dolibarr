<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *    \file       htdocs/expedition/stats/month.php
 *    \ingroup    commande
 *    \brief      Page des stats expeditions par mois
 *    \version    $Id: month.php,v 1.17 2011/07/31 23:50:55 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expedition.class.php");
require_once(DOL_DOCUMENT_ROOT."/expedition/class/expeditionstats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");


llxHeader();

$WIDTH=500;
$HEIGHT=200;

$mesg = '';

print_fiche_titre('Statistiques expeditions '.$_GET["year"], $mesg);

$stats = new ExpeditionStats($db);
$data = $stats->getNbExpeditionByMonth($_GET["year"]);

create_exdir($conf->expedition->dir_temp);

$filename = $conf->expedition->dir_temp."/expedition".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=expeditionstats&file=expedition'.$year.'.png';

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("NbOfOrders"));
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename);
}

print '<table class="border" width="100%">';
print '<tr><td align="center">Nombre d expedition par mois</td>';
print '<td align="center">';
print '<img src="'.$fileurl.'">';
print '</td></tr>';
print '</table>';

$db->close();

llxFooter('$Date: 2011/07/31 23:50:55 $ - $Revision: 1.17 $');
?>
