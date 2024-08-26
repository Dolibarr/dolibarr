<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2013      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2020      Maxime DEMAREST      <maxime@indelog.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/compta/facture/stats/index.php
 *  \ingroup    invoice
 *  \brief      Page des stats factures
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facturestats.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies', 'other'));

$mode = GETPOST("mode") ? GETPOST("mode") : 'customer';

$hookmanager->initHooks(array('invoicestats', 'globalcard'));

if ($mode == 'customer' && !$user->hasRight('facture', 'lire')) {
	accessforbidden();
}
if ($mode == 'supplier' && !$user->hasRight('fournisseur', 'facture', 'lire')) {
	accessforbidden();
}

$object_status = GETPOST('object_status', 'intcomma');
$typent_id = GETPOSTINT('typent_id');
$categ_id = GETPOSTINT('categ_id');

$userid = GETPOSTINT('userid');
$socid = GETPOSTINT('socid');
$select_categ_categ_id = GETPOST('select_categ_categ_id', 'array');
// Security check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$nowyear = dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$year = GETPOST('year') > 0 ? GETPOSTINT('year') : $nowyear;
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;


/*
 * View
 */
if (isModEnabled('category')) {
	$langs->load('categories');
}
$form = new Form($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);

llxHeader();

$picto = 'bill';
$title = $langs->trans("BillsStatistics");
$dir = $conf->facture->dir_temp;

if ($mode == 'supplier') {
	$picto = 'supplier_invoice';
	$title = $langs->trans("BillsStatisticsSuppliers");
	$dir = $conf->fournisseur->facture->dir_temp;
}


print load_fiche_titre($title, '', $picto);

dol_mkdir($dir);

$stats = new FactureStats($db, $socid, $mode, ($userid > 0 ? $userid : 0), ($typent_id > 0 ? $typent_id : 0), ($categ_id > 0 ? $categ_id : 0));
if ($mode == 'customer') {
	if ($object_status != '' && $object_status >= 0) {
		$stats->where .= ' AND f.fk_statut IN ('.$db->sanitize($object_status).')';
	}
	if (is_array($select_categ_categ_id) && !empty($select_categ_categ_id)) {
		$stats->from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_societe as cat ON (f.fk_soc = cat.fk_soc)';
		$stats->where .= ' AND cat.fk_categorie IN ('.$db->sanitize(implode(',', $select_categ_categ_id)).')';
	}
}
if ($mode == 'supplier') {
	if ($object_status != '' && $object_status >= 0) {
		$stats->where .= ' AND f.fk_statut IN ('.$db->sanitize($object_status).')';
	}
	if (is_array($select_categ_categ_id) && !empty($select_categ_categ_id)) {
		$stats->from .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_fournisseur as cat ON (f.fk_soc = cat.fk_soc)';
		$stats->where .= ' AND cat.fk_categorie IN ('.$db->sanitize(implode(',', $select_categ_categ_id)).')';
	}
}

// Build graphic number of object
// $data = array(array('Lib',val1,val2,val3),...)
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);

$filenamenb = $dir."/invoicesnbinyear-".$year.".png";
if ($mode == 'customer') {
	$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&file=invoicesnbinyear-'.$year.'.png';
}
if ($mode == 'supplier') {
	$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=billstatssupplier&file=invoicesnbinyear-'.$year.'.png';
}

$px1 = new DolGraph();
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$px1->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px1->SetLegend($legend);
	$px1->SetMaxValue($px1->GetCeilMaxValue());
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("NumberOfBills"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("NumberOfBillsByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$filenameamount = $dir."/invoicesamountinyear-".$year.".png";
if ($mode == 'customer') {
	$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=billstats&amp;file=invoicesamountinyear-'.$year.'.png';
}
if ($mode == 'supplier') {
	$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=billstatssupplier&amp;file=invoicesamountinyear-'.$year.'.png';
}

$px2 = new DolGraph();
$mesg = $px2->isGraphKo();
if (!$mesg) {
	$px2->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
		$i++;
	}
	$px2->SetLegend($legend);
	$px2->SetMaxValue($px2->GetCeilMaxValue());
	$px2->SetMinValue(min(0, $px2->GetFloorMinValue()));
	$px2->SetWidth($WIDTH);
	$px2->SetHeight($HEIGHT);
	$px2->SetYLabel($langs->trans("AmountOfBills"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->mode = 'depth';
	$px2->SetTitle($langs->trans("AmountOfBillsByMonthHT"));

	$px2->draw($filenameamount, $fileurlamount);
}


$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);

if (!$user->hasRight('societe', 'client', 'voir')) {
	$filename_avg = $dir.'/ordersaverage-'.$user->id.'-'.$year.'.png';
	if ($mode == 'customer') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$user->id.'-'.$year.'.png';
	}
	if ($mode == 'supplier') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$user->id.'-'.$year.'.png';
	}
} else {
	$filename_avg = $dir.'/ordersaverage-'.$year.'.png';
	if ($mode == 'customer') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersaverage-'.$year.'.png';
	}
	if ($mode == 'supplier') {
		$fileurl_avg = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersaverage-'.$year.'.png';
	}
}

$px3 = new DolGraph();
$mesg = $px3->isGraphKo();
if (!$mesg) {
	$px3->SetData($data);
	$i = $startyear;
	$legend = array();
	while ($i <= $endyear) {
		$legend[] = $i;
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
	$px3->mode = 'depth';
	$px3->SetTitle($langs->trans("AmountAverage"));

	$px3->draw($filename_avg, $fileurl_avg);
}


// Show array
$data = $stats->getAllByYear();
$arrayyears = array();
foreach ($data as $val) {
	$arrayyears[$val['year']] = $val['year'];
}
if (!count($arrayyears)) {
	$arrayyears[$nowyear] = $nowyear;
}


$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/compta/facture/stats/index.php?mode='.urlencode($mode);
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

if ($mode == 'customer') {
	$type = 'invoice_stats';
}
if ($mode == 'supplier') {
	$type = 'supplier_invoice_stats';
}

complete_head_from_modules($conf, $langs, null, $head, $h, $type);

print dol_get_fiche_head($head, 'byyear', '', -1);

print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
$filter = '';
if ($mode == 'customer') {
	$filter = '(s.client:IN:1,2,3)';
}
if ($mode == 'supplier') {
	$filter = '(s.fournisseur:=:1)';
}
print img_picto('', 'company', 'class="pictofixedwidth"');
print $form->select_company($socid, 'socid', $filter, 1, 0, 0, array(), 0, 'widthcentpercentminusx maxwidth300');
print '</td></tr>';

// ThirdParty Type
print '<tr><td>'.$langs->trans("ThirdPartyType").'</td><td>';
$sortparam_typent = (!getDolGlobalString('SOCIETE_SORT_ON_TYPEENT') ? 'ASC' : $conf->global->SOCIETE_SORT_ON_TYPEENT); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
print $form->selectarray("typent_id", $formcompany->typent_array(0), $typent_id, 1, 0, 0, '', 0, 0, 0, $sortparam_typent, '', 1);
if ($user->admin) {
	print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
}
print '</td></tr>';

// Category
if (isModEnabled('category')) {
	if ($mode == 'customer') {
		$cat_type = Categorie::TYPE_CUSTOMER;
		$cat_label = $langs->trans("Category").' '.lcfirst($langs->trans("Customer"));
	}
	if ($mode == 'supplier') {
		$cat_type = Categorie::TYPE_SUPPLIER;
		$cat_label = $langs->trans("Category").' '.lcfirst($langs->trans("Supplier"));
	}
	print '<tr><td>'.$cat_label.'</td><td>';
	$cate_arbo = $form->select_all_categories($cat_type, null, 'parent', null, null, 1);
	print img_picto('', 'category', 'class="pictofixedwidth"');
	print $form->multiselectarray('select_categ_categ_id', $cate_arbo, GETPOST('select_categ_categ_id', 'array'), 0, 0, 'widthcentpercentminusx maxwidth300');
	//print $formother->select_categories($cat_type, $categ_id, 'categ_id', true);
	print '</td></tr>';
}

// User
print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userid, 'userid', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
print '</td></tr>';
// Status
print '<tr><td>'.$langs->trans("Status").'</td><td>';
if ($mode == 'customer') {
	$liststatus = array('0' => $langs->trans("BillStatusDraft"), '1' => $langs->trans("BillStatusNotPaid"), '2' => $langs->trans("BillStatusPaid"), '1,2' => $langs->trans("BillStatusNotPaid").' / '.$langs->trans("BillStatusPaid"), '3' => $langs->trans("BillStatusCanceled"));
	print $form->selectarray('object_status', $liststatus, $object_status, 1);
}
if ($mode == 'supplier') {
	$liststatus = array('0' => $langs->trans("BillStatusDraft"), '1' => $langs->trans("BillStatusNotPaid"), '2' => $langs->trans("BillStatusPaid"));
	print $form->selectarray('object_status', $liststatus, $object_status, 1);
}
print '</td></tr>';
// Year
print '<tr><td>'.$langs->trans("Year").'</td><td>';
if (!in_array($year, $arrayyears)) {
	$arrayyears[$year] = $year;
}
if (!in_array($nowyear, $arrayyears)) {
	$arrayyears[$nowyear] = $nowyear;
}
arsort($arrayyears);
print $form->selectarray('year', $arrayyears, $year, 0, 0, 0, '', 0, 0, 0, '', 'width75');
print '</td></tr>';
print '<tr><td class="center" colspan="2"><input type="submit" name="submit" class="button small" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre" height="24">';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("NumberOfBills").'</td>';
print '<td class="right">%</td>';
print '<td class="right">'.$langs->trans("AmountTotal").'</td>';
print '<td class="right">%</td>';
print '<td class="right">'.$langs->trans("AmountAverage").'</td>';
print '<td class="right">%</td>';
print '</tr>';

$oldyear = 0;
foreach ($data as $val) {
	$year = (int) $val['year'];
	while ($year && $oldyear > $year + 1) {	// If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$oldyear.'</a></td>';
		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '<td class="right amount">0</td>';
		print '<td class="right"></td>';
		print '<td class="right amount">0</td>';
		print '<td class="right"></td>';
		print '</tr>';
	}

	if ($mode == 'supplier') {
		$greennb = (empty($val['nb_diff']) || $val['nb_diff'] <= 0);
		$greentotal = (empty($val['total_diff']) || $val['total_diff'] <= 0);
		$greenavg = (empty($val['avg_diff']) || $val['avg_diff'] <= 0);
	} else {
		$greennb = (empty($val['nb_diff']) || $val['nb_diff'] >= 0);
		$greentotal = (empty($val['total_diff']) || $val['total_diff'] >= 0);
		$greenavg = (empty($val['avg_diff']) || $val['avg_diff'] >= 0);
	}

	print '<tr class="oddeven" height="24">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right opacitylow" style="'.($greennb ? 'color: green;' : 'color: red;').'">'.(!empty($val['nb_diff']) && $val['nb_diff'] < 0 ? '' : '+').round(!empty($val['nb_diff']) ? $val['nb_diff'] : 0).'%</td>';
	print '<td class="right"><span class="amount">'.price(price2num($val['total'], 'MT'), 1).'</span></td>';
	print '<td class="right opacitylow" style="'.($greentotal ? 'color: green;' : 'color: red;').'">'.(!empty($val['total_diff']) && $val['total_diff'] < 0 ? '' : '+').round(!empty($val['total_diff']) ? $val['total_diff'] : 0).'%</td>';
	print '<td class="right"><span class="amount">'.price(price2num($val['avg'], 'MT'), 1).'</span></td>';
	print '<td class="right opacitylow" style="'.($greenavg ? 'color: green;' : 'color: red;').'">'.(!empty($val['avg_diff']) && $val['avg_diff'] < 0 ? '' : '+').round(!empty($val['avg_diff']) ? $val['avg_diff'] : 0).'%</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';

print '</div><div class="fichetwothirdright">';


// Show graphs
print '<table class="border centpercent"><tr class="pair nohover"><td align="center">';
if ($mesg) {
	print $mesg;
} else {
	print $px1->show();
	print "<br>\n";
	print $px2->show();
	print "<br>\n";
	print $px3->show();
}
print '</td></tr></table>';


print '</div></div>';
print '<div class="clearboth"></div>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
