<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
	    \file       htdocs/commande/stats/month.php
        \ingroup    commande
		\brief      Page des stats commandes par mois
		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/stats/commandestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/dolgraph.class.php");

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

$WIDTH=500;
$HEIGHT=200;

/*
 *
 *
 */

print_fiche_titre($langs->trans("OrdersStatistics"), $mesg);

$stats = new CommandeStats($db, $socid);
$data = $stats->getNbByMonth($year);

create_exdir($conf->commande->dir_temp);


if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename = $conf->commande->dir_temp.'/commande-'.$user->id.'-'.$year.'.png';
  $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commande-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename = $conf->commande->dir_temp.'/commande'.$year.'.png';
  $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commande'.$year.'.png';
}

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

$res = $stats->getAmountByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(strftime("%b",dolibarr_mktime(12,12,12,$i,1,$year)),0,3)), $res[$i]);
}

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_amount = $conf->commande->dir_temp.'/commandeamount-'.$user->id.'-'.$year.'.png';
	$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commandeamount-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_amount = $conf->commande->dir_temp.'/commandeamount'.$year.'.png';
	$fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commandeamount'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->SetShading(5);
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

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_avg = $conf->commande->dir_temp.'/commandeaverage-'.$user->id.'-'.$year.'.png';
	$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commandeaverage-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_avg = $conf->commande->dir_temp.'/commandeaverage'.$year.'.png';
	$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=commandeaverage'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetWidth($WIDTH);
    $px->SetHeight($HEIGHT);
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->SetShading(5);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
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
