<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/expedition/stats/month.php
        \ingroup    commande
		\brief      Page des stats expeditions par mois
		\version    $Revision$
*/

require("./pre.inc.php");
require("../expedition.class.php");
require("./expeditionstats.class.php");

llxHeader();

$mesg = '';

print_fiche_titre('Statistiques expeditions '.$_GET["year"], $mesg);

$stats = new ExpeditionStats($db);
$data = $stats->getNbExpeditionByMonth($_GET["year"]);

if (! is_dir($conf->expedition->dir_images)) { mkdir($conf->expedition->dir_images); }

$filename = $conf->expedition->dir_images."/expedition$year.png";
$fileurl = $conf->expedition->url_images."/expedition$year.png";

$px = new BarGraph($data);
$px->SetMaxValue($px->GetMaxValue());
$px->SetWidth(600);
$px->SetHeight(280);
$px->draw($filename, $data, $_GET["year"]);

print '<table class="border" width="100%" cellspacing="0" cellpadding="2">';
print '<tr><td align="center">Nombre d\'expédition par mois</td>';
print '<td align="center">';
print '<img src="'.$fileurl.'">';
print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
