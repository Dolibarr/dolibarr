<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("./pre.inc.php");
require("../expedition.class.php");
require("./expeditionstats.class.php");

llxHeader();

$mesg = '';

/*
 *
 *
 */

print_fiche_titre('Statistiques expeditions '.$_GET["year"], $mesg);

$stats = new ExpeditionStats($db);

$dir = DOL_DOCUMENT_ROOT;

$data = $stats->getNbExpeditionByMonth($_GET["year"]);

$filev = "/document/images/expedition-".$_GET["year"].".png";

$px = new BarGraph($data);
$px->SetMaxValue($px->GetMaxValue());
$px->SetWidth(600);
$px->SetHeight(280);
$px->draw($dir.$filev, $data, $_GET["year"]);

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr><td align="center">Nombre d\'expédition par mois</td>';
print '<td align="center">';
print '<img src="'.DOL_URL_ROOT.$filev.'">';
print '</td></tr>';
print '</table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
