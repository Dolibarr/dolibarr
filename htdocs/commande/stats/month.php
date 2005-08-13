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

/**
	    \file       htdocs/commande/stats/month.php
        \ingroup    commande
		\brief      Page des stats commandes par mois
		\version    $Revision$
*/

require("./pre.inc.php");
require(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
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
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'">'.img_next().'</a>';

$WIDTH=500;
$HEIGHT=250;

/*
 *
 *
 */

print_fiche_titre($langs->trans("OrdersStatistics"), $mesg);

$stats = new CommandeStats($db, $socidp);
$data = $stats->getNbByMonth($year);

if (! is_dir($conf->commande->dir_images)) { mkdir($conf->commande->dir_images); }

$filename = $conf->commande->dir_images."/commande".$year.".png";
$fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commande'.$year.'.png';

$px = new BarGraph();
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetData($data);
    $px->SetMaxValue($px->GetMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel("Nombre de commande");
    $px->draw($filename);
}

$res = $stats->getAmountByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(strftime("%b",mktime(12,12,12,$i,1,$year)),0,3)), $res[$i]);
}

$filename_amount = $conf->commande->dir_images."/commandeamount".$year.".png";
$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commandeamount'.$year.'.png';

$px = new BarGraph();
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetData($data);
    $px->SetMaxValue($px->GetAmountMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->draw($filename_amount);
}
$res = $stats->getAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(strftime("%b",mktime(12,12,12,$i,1,$year)),0,3)), $res[$i]);
}

$filename_avg = $conf->commande->dir_images."/commandeaverage".$year.".png";
$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commandeaverage'.$year.'.png';

$px = new BarGraph();
$mesg = $px->isGraphKo();
if (! $mesg) {
    $px->SetData($data);
    $px->SetMaxValue($px->GetAmountMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->draw($filename_avg);
}

print '<table class="border" width="100%">';
print '<tr><td align="center">'.$langs->trans("NumberOfOrdersByMonth").'</td>';
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
