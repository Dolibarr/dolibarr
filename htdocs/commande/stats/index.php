<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	    \file       htdocs/commande/stats/index.php
 *      \ingroup    commande
 *		\brief      Page with customers or suppliers orders statistics
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commandestats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$WIDTH=DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT=DolGraph::getDefaultGraphSizeForStats('height');

$mode=GETPOST("mode")?GETPOST("mode"):'customer';
if ($mode == 'customer' && ! $user->rights->commande->lire) accessforbidden();
if ($mode == 'supplier' && ! $user->rights->fournisseur->commande->lire) accessforbidden();

$object_status=GETPOST('object_status');

$userid=GETPOST('userid', 'int');
$socid=GETPOST('socid', 'int');
// Security check
if ($user->socid > 0)
{
    $action = '';
    $socid = $user->socid;
}

$nowyear=strftime("%Y", dol_now());
$year = GETPOST('year')>0?GETPOST('year'):$nowyear;
//$startyear=$year-2;
$startyear=$year-1;
$endyear=$year;

// Load translation files required by the page
$langs->loadLangs(array('orders', 'companies', 'other', 'suppliers'));


/*
 * View
 */

$form=new Form($db);
$formorder=new FormOrder($db);

if ($mode == 'customer')
{
    $title=$langs->trans("OrdersStatistics");
    $dir=$conf->commande->dir_temp;
}
if ($mode == 'supplier')
{
    $title=$langs->trans("OrdersStatisticsSuppliers").' ('.$langs->trans("SentToSuppliers").")";
    $dir=$conf->fournisseur->commande->dir_temp;
}

llxHeader('', $title);

print load_fiche_titre($title, '', 'commercial');

dol_mkdir($dir);

$stats = new CommandeStats($db, $socid, $mode, ($userid>0?$userid:0));
if ($mode == 'customer')
{
    if ($object_status != '' && $object_status >= -1) $stats->where .= ' AND c.fk_statut IN ('.$db->escape($object_status).')';
}
if ($mode == 'supplier')
{
    if ($object_status != '' && $object_status >= 0) $stats->where .= ' AND c.fk_statut IN ('.$db->escape($object_status).')';
}


// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);

//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)


if (!$user->rights->societe->client->voir || $user->socid)
{
    $filenamenb = $dir.'/ordersnbinyear-'.$user->id.'-'.$year.'.png';
    if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersnbinyear-'.$user->id.'-'.$year.'.png';
    if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersnbinyear-'.$user->id.'-'.$year.'.png';
}
else
{
    $filenamenb = $dir.'/ordersnbinyear-'.$year.'.png';
    if ($mode == 'customer') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersnbinyear-'.$year.'.png';
    if ($mode == 'supplier') $fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersnbinyear-'.$year.'.png';
}

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (! $mesg)
{
    $px1->SetData($data);
    $i=$startyear;$legend=array();
    while ($i <= $endyear)
    {
        $legend[]=$i;
        $i++;
    }
    $px1->SetLegend($legend);
    $px1->SetMaxValue($px1->GetCeilMaxValue());
    $px1->SetMinValue(min(0, $px1->GetFloorMinValue()));
    $px1->SetWidth($WIDTH);
    $px1->SetHeight($HEIGHT);
    $px1->SetYLabel($langs->trans("NbOfOrder"));
    $px1->SetShading(3);
    $px1->SetHorizTickIncrement(1);
    $px1->mode='depth';
    $px1->SetTitle($langs->trans("NumberOfOrdersByMonth"));

    $px1->draw($filenamenb, $fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

if (!$user->rights->societe->client->voir || $user->socid)
{
    $filenameamount = $dir.'/ordersamountinyear-'.$user->id.'-'.$year.'.png';
    if ($mode == 'customer') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamountinyear-'.$user->id.'-'.$year.'.png';
    if ($mode == 'supplier') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamountinyear-'.$user->id.'-'.$year.'.png';
}
else
{
    $filenameamount = $dir.'/ordersamountinyear-'.$year.'.png';
    if ($mode == 'customer') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamountinyear-'.$year.'.png';
    if ($mode == 'supplier') $fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamountinyear-'.$year.'.png';
}

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (! $mesg)
{
    $px2->SetData($data);
    $i=$startyear;$legend=array();
    while ($i <= $endyear)
    {
        $legend[]=$i;
        $i++;
    }
    $px2->SetLegend($legend);
    $px2->SetMaxValue($px2->GetCeilMaxValue());
    $px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
    $px2->SetWidth($WIDTH);
    $px2->SetHeight($HEIGHT);
    $px2->SetYLabel($langs->trans("AmountOfOrders"));
    $px2->SetShading(3);
    $px2->SetHorizTickIncrement(1);
    $px2->mode='depth';
    $px2->SetTitle($langs->trans("AmountOfOrdersByMonthHT"));

    $px2->draw($filenameamount, $fileurlamount);
}


$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

if (!$user->rights->societe->client->voir || $user->socid)
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

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (! $mesg)
{
    $px3->SetData($data);
    $i=$startyear;$legend=array();
    while ($i <= $endyear)
    {
        $legend[]=$i;
        $i++;
    }
    $px3->SetLegend($legend);
    $px3->SetYLabel($langs->trans("AmountAverage"));
    $px3->SetMaxValue($px3->GetCeilMaxValue());
    $px3->SetMinValue($px3->GetFloorMinValue());
    $px3->SetWidth($WIDTH);
    $px3->SetHeight($HEIGHT);
    $px3->SetShading(3);
    $px3->SetHorizTickIncrement(1);
    $px3->mode='depth';
    $px3->SetTitle($langs->trans("AmountAverage"));

    $px3->draw($filename_avg, $fileurl_avg);
}



// Show array
$data = $stats->getAllByYear();
$arrayyears=array();
foreach($data as $val) {
	if (! empty($val['year'])) {
		$arrayyears[$val['year']]=$val['year'];
	}
}
if (! count($arrayyears)) $arrayyears[$nowyear]=$nowyear;

$h=0;
$head = array();
$head[$h][0] = DOL_URL_ROOT . '/commande/stats/index.php?mode='.$mode;
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

if ($mode == 'customer') $type='order_stats';
if ($mode == 'supplier') $type='supplier_order_stats';

complete_head_from_modules($conf, $langs, null, $head, $h, $type);

dol_fiche_head($head, 'byyear', $langs->trans("Statistics"), -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
print '<tr><td class="left">'.$langs->trans("ThirdParty").'</td><td class="left">';
if ($mode == 'customer') $filter='s.client IN (1,2,3)';
if ($mode == 'supplier') $filter='s.fournisseur = 1';
print $form->select_company($socid, 'socid', $filter, 1, 0, 0, array(), 0, '', 'style="width: 95%"');
print '</td></tr>';
// User
print '<tr><td class="left">'.$langs->trans("CreatedBy").'</td><td class="left">';
print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
// Status
print '<tr><td class="left">'.$langs->trans("Status").'</td><td class="left">';
if ($mode == 'customer')
{
    $liststatus=array(
        Commande::STATUS_DRAFT=>$langs->trans("StatusOrderDraft"),
        Commande::STATUS_VALIDATED=>$langs->trans("StatusOrderValidated"),
        Commande::STATUS_SHIPMENTONPROCESS=>$langs->trans("StatusOrderSent"),
        Commande::STATUS_CLOSED=>$langs->trans("StatusOrderDelivered"),
        Commande::STATUS_CANCELED=>$langs->trans("StatusOrderCanceled")
    );
    print $form->selectarray('object_status', $liststatus, GETPOST('object_status', 'int'), -4);
}
if ($mode == 'supplier')
{
    $formorder->selectSupplierOrderStatus((strstr($object_status, ',')?-1:$object_status), 0, 'object_status');
}
print '</td></tr>';
// Year
print '<tr><td class="left">'.$langs->trans("Year").'</td><td class="left">';
if (! in_array($year, $arrayyears)) $arrayyears[$year]=$year;
if (! in_array($nowyear, $arrayyears)) $arrayyears[$nowyear]=$nowyear;
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0);
print '</td></tr>';
print '<tr><td align="center" colspan="2"><input type="submit" name="submit" class="button" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre" height="24">';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("NbOfOrders").'</td>';
print '<td class="right">%</td>';
print '<td class="right">'.$langs->trans("AmountTotal").'</td>';
print '<td class="right">%</td>';
print '<td class="right">'.$langs->trans("AmountAverage").'</td>';
print '<td class="right">%</td>';
print '</tr>';

$oldyear=0;
foreach ($data as $val)
{
	$year = $val['year'];
	while (! empty($year) && $oldyear > $year+1)
	{ // If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$oldyear.'</a></td>';
		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '</tr>';
	}


	print '<tr class="oddeven" height="24">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.($socid>0?'&socid='.$socid:'').($userid>0?'&userid='.$userid:'').'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right" style="'.(($val['nb_diff'] >= 0) ? 'color: green;':'color: red;').'">'.round($val['nb_diff']).'</td>';
	print '<td class="right">'.price(price2num($val['total'], 'MT'), 1).'</td>';
	print '<td class="right" style="'.(($val['total_diff'] >= 0) ? 'color: green;':'color: red;').'">'.round($val['total_diff']).'</td>';
	print '<td class="right">'.price(price2num($val['avg'], 'MT'), 1).'</td>';
	print '<td class="right" style="'.(($val['avg_diff'] >= 0) ? 'color: green;':'color: red;').'">'.round($val['avg_diff']).'</td>';
	print '</tr>';
	$oldyear=$year;
}

print '</table>';
print '</div>';


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Show graphs
print '<table class="border centpercent"><tr class="pair nohover"><td align="center">';
if ($mesg) { print $mesg; }
else {
    print $px1->show();
    print "<br>\n";
    print $px2->show();
    print "<br>\n";
    print $px3->show();
}
print '</td></tr></table>';


print '</div></div></div>';
print '<div style="clear:both"></div>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
