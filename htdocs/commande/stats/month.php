<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	    \file       htdocs/commande/stats/month.php
 *      \ingroup    commande
 *		\brief      Page des stats commandes par mois
 *		\version    $Id: month.php,v 1.34 2011/08/03 00:46:39 eldy Exp $
 */
require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/class/commandestats.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");

$GRAPHWIDTH=500;
$GRAPHHEIGHT=200;

// Check security access
if ($user->societe_id > 0)
{
  $action = '';
  $socid = $user->societe_id;
}

$year = isset($_GET["year"])?$_GET["year"]:date("Y",time());

$mode='customer';
if (isset($_GET["mode"])) $mode=$_GET["mode"];



/*
 * View
 */

llxHeader();

if ($mode == 'customer')
{
	$title=$langs->trans("OrdersStatistics");
	$dir=$conf->commande->dir_temp;
}
if ($mode == 'supplier')
{
	$title=$langs->trans("OrdersStatisticsSuppliers");
	$dir=$conf->fournisseur->dir_output.'/commande/temp';
}

$mesg = '<a href="month.php?year='.($year - 1).'&amp;mode='.$mode.'">'.img_previous().'</a> ';
$mesg.= $langs->trans("Year")." $year";
$mesg.= ' <a href="month.php?year='.($year + 1).'&amp;mode='.$mode.'">'.img_next().'</a>';
print_fiche_titre($title, $mesg);

create_exdir($dir);

$stats = new CommandeStats($db, $socid, $mode);


$data = $stats->getNbByMonth($year);

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename = $dir.'/ordersnb-'.$user->id.'-'.$year.'.png';
  	if ($mode == 'customer') $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersnb-'.$user->id.'-'.$year.'.png';
  	if ($mode == 'supplier') $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersnb-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename = $dir.'/ordersnb-'.$year.'.png';
  	if ($mode == 'customer') $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersnb-'.$year.'.png';
  	if ($mode == 'supplier') $fileurl = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersnb-'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
    $px->SetYLabel($langs->trans("NbOfOrders"));
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename);
}


$data = $stats->getAmountByMonth($year);

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_amount = $dir.'/ordersamount-'.$user->id.'-'.$year.'.png';
	if ($mode == 'customer') $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamount-'.$user->id.'-'.$year.'.png';
	if ($mode == 'supplier') $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamount-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_amount = $dir.'/ordersamount-'.$year.'.png';
	if ($mode == 'customer') $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamount-'.$year.'.png';
	if ($mode == 'supplier') $fileurl_amount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamount-'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetYLabel($langs->trans("AmountTotal"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
    $px->SetShading(3);
	$px->SetHorizTickIncrement(1);
	$px->SetPrecisionY(0);
    $px->draw($filename_amount);
}
$res = $stats->getAverageByMonth($year);

$data = array();

for ($i = 1 ; $i < 13 ; $i++)
{
  $data[$i-1] = array(ucfirst(substr(dol_print_date(dol_mktime(12,0,0,$i,1,$year),"%b"),0,3)), $res[$i]);
}

if (!$user->rights->societe->client->voir || $user->societe_id)
{
	$filename_avg = $dir.'/ordersaverage-'.$user->id.'-'.$year.'.png';
	if ($mode == 'customer') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$user->id.'-'.$year.'.png';
	if ($mode == 'supplier') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$user->id.'-'.$year.'.png';
}
else
{
	$filename_avg = $dir.'/ordersaverage-'.$year.'.png';
	if ($mode == 'customer') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$year.'.png';
	if ($mode == 'supplier') $fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$year.'.png';
}

$px = new DolGraph();
$mesg = $px->isGraphKo();
if (! $mesg)
{
    $px->SetData($data);
    $px->SetYLabel($langs->trans("AmountAverage"));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetWidth($GRAPHWIDTH);
    $px->SetHeight($GRAPHHEIGHT);
    $px->SetShading(3);
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

llxFooter('$Date: 2011/08/03 00:46:39 $ - $Revision: 1.34 $');
?>
