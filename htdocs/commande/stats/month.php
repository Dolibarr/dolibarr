<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("../commande.class.php");
require("./commandestats.class.php");

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

llxHeader();

$year = isset($_GET["year"])?$_GET["year"]:date("Y",time());

$mesg = '<a href="month.php?year='.($year - 1).'">'.img_previous().'</a> ';
$mesg.= "Année $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'">'.img_next().'</a>';

/*
 *
 *
 */

print_fiche_titre('Statistiques commandes', $mesg);

$dir = DOL_DOCUMENT_ROOT;

$stats = new CommandeStats($db, $socidp);
$data = $stats->getNbCommandeByMonth($year);

$filev = "/document/images/commande$year.png";

$px = new BarGraph($data);
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetMaxValue($px->GetMaxValue());
    $px->SetWidth(500);
    $px->SetHeight(280);
    $px->SetYLabel("Nombre de commande");
    $px->draw($dir.$filev, $data, $year);
}

$res = $stats->getCommandeAmountByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(strftime("%b",mktime(12,12,12,$i,1,$year)), $res[$i]);
}

$file_amount = "/document/images/commandeamount.png";

$px = new BarGraph($data);
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetMaxValue($px->GetAmountMaxValue());
    $px->SetWidth(500);
    $px->SetHeight(250);
    $px->SetYLabel("Montant des commande");
    $px->draw($dir.$file_amount, $data, $year);
}
$res = $stats->getCommandeAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(strftime("%b",mktime(12,12,12,$i,1,$year)), $res[$i]);
}
$file_avg = "/document/images/commandeaverage.png";
$px = new BarGraph($data);
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetMaxValue($px->GetAmountMaxValue());
    $px->SetWidth(500);
    $px->SetHeight(250);
    $px->SetYLabel("Montant moyen des commande");
    $px->draw($dir.$file_avg, $data, $year);
}

print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
print '<tr><td align="center">Nombre de commande par mois</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.DOL_URL_ROOT.$filev.'">'; }
print '</td></tr>';
print '<tr><td align="center">Sommes des commandes</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.DOL_URL_ROOT.$file_amount.'">'; }
print '</td></tr>';
print '<tr><td align="center">Montant moyen des commande</td>';
print '<td align="center">';
if ($mesg) { print $mesg; }
else { print '<img src="'.DOL_URL_ROOT.$file_avg.'">'; }
print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
