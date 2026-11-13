<?php
/* Copyright (C) 2026       Laurent Destailleur         <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/mrp/stats/index.php
 *      \ingroup    mrp
 *		\brief      Page with Manufacturing Orders statistics
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mostats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$action = GETPOST('action', 'aZ09');
$tabfamily = GETPOST('tabfamily', 'aZ09');	// 'mostats' to show the tab with the custom reports tool

$object_status = GETPOST('object_status', 'intcomma');
$mrptype = GETPOSTISSET('mrptype') ? GETPOSTINT('mrptype') : -1;
$userid = GETPOSTINT('userid');
$productid = GETPOSTINT('productid');
$socid = GETPOSTINT('socid');

$object = new Mo($db);

$hookmanager->initHooks(array('mostats', 'globalcard'));

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
$result = restrictedArea($user, 'mrp');

$nowyear = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$year = GETPOSTINT('year') > 0 ? GETPOSTINT('year') : $nowyear;
$startyear = $year - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

// Load translation files required by the page
$langs->loadLangs(array('mrp', 'products', 'companies', 'other'));


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("ListOfManufacturingOrders");
$help_url = 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication|DE:Modul_Fertigungsauftrag';
$dir = $conf->mrp->dir_temp;

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-mrp page-stats');

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/mrp/mo_list.php?mode=common', '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', DOL_URL_ROOT.'/mrp/mo_list.php?mode=kanban', '', 1, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('Statistics'), '', 'fa fa-chart-bar imgforviewmode', DOL_URL_ROOT.'/mrp/stats/index.php', '', 2, array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/mrp/mo_card.php?action=create&backtopage='.urlencode(DOL_URL_ROOT.'/mrp/mo_list.php'), '', $user->hasRight('mrp', 'write'));

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', '', '', '', 0, $langs->trans("Statistics"), $object->picto, 0, $newcardbutton, '', 0, 0, 0, 1);	// @phan-suppress-current-line PhanPluginSuspiciousParamOrder

$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/mrp/stats/index.php';
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

// The custom reports tool is not available to external users, but external users can't access this page
$head[$h][0] = DOL_URL_ROOT.'/mrp/stats/index.php?tabfamily=mostats';
$head[$h][1] = $langs->trans("Custom");
$head[$h][2] = 'customreports';
$h++;

$type = 'mo_stats';


complete_head_from_modules($conf, $langs, null, $head, $h, $type);

// Show the tab with the custom reports tool
if ($tabfamily == 'mostats') {
	print dol_get_fiche_head($head, 'customreports', '', -1);

	define('USE_CUSTOM_REPORT_AS_TAB', 1);
	$customreportobjecttype = 'mrp';
	include DOL_DOCUMENT_ROOT.'/core/customreports.php';

	print dol_get_fiche_end();

	llxFooter();
	$db->close();
	exit;
}

dol_mkdir($dir);

$stats = new MoStats($db, ($userid > 0 ? $userid : 0), ($productid > 0 ? $productid : 0), ($socid > 0 ? $socid : 0));

if ($object_status != '' && $object_status >= -1) {
	$stats->where .= ' AND m.status IN ('.$db->sanitize($object_status).')';
}
if ($mrptype >= 0) {
	$stats->where .= ' AND m.mrptype = '.((int) $mrptype);
}

// Parameters to keep the filters on links
$paramfilter = '';
if ($userid > 0) {
	$paramfilter .= '&userid='.((int) $userid);
}
if ($productid > 0) {
	$paramfilter .= '&productid='.((int) $productid);
}
if ($socid > 0) {
	$paramfilter .= '&socid='.((int) $socid);
}
if ($mrptype >= 0) {
	$paramfilter .= '&mrptype='.((int) $mrptype);
}
if ($object_status != '') {
	$paramfilter .= '&object_status='.urlencode($object_status);
}


// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);
// $data = array(array('Lib',val1,val2,val3),...)

$filenamenb = $dir.'/mosnbinyear-'.$year.'.png';
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=mrp_temp&file=mosnbinyear-'.$year.'.png';

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
	$px1->SetMinValue(min(0, $px1->GetFloorMinValue()));
	$px1->SetWidth($WIDTH);
	$px1->SetHeight($HEIGHT);
	$px1->SetYLabel($langs->trans("NbOfMOs"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("NumberOfMOsByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}


// Show array
$data = $stats->getAllByYear();
$arrayyears = array();
foreach ($data as $val) {
	if (!empty($val['year'])) {
		$arrayyears[$val['year']] = $val['year'];
	}
}
if (!count($arrayyears)) {
	$arrayyears[$nowyear] = $nowyear;
}

print dol_get_fiche_head($head, 'byyear', '', -1);


print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Product
print '<tr><td>'.$langs->trans("Product").'</td><td>';
print img_picto('', 'product', 'class="pictofixedwidth"');
print $form->select_produits($productid, 'productid', '', 0, 0, -1, 2, '', 0, array(), 0, '1', 0, 'widthcentpercentminusx maxwidth300');
print '</td></tr>';
// Type
print '<tr><td>'.$langs->trans("Type").'</td><td>';
$listtype = array(
	0 => $langs->trans("Manufacturing"),
	1 => $langs->trans("Disassemble"),
);
print $form->selectarray('mrptype', $listtype, $mrptype, 1, 0, 0, '', 0, 0, 0, '', 'minwidth150');
print '</td></tr>';
// Thirdparty
if (isModEnabled('societe')) {
	print '<tr><td>'.$langs->trans("ThirdParty").'</td><td>';
	print img_picto('', 'company', 'class="pictofixedwidth"');
	print $form->select_company($socid, 'socid', '', 1, 0, 0, array(), 0, 'widthcentpercentminusx maxwidth300');
	print '</td></tr>';
}
// User
print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>';
print img_picto('', 'user', 'class="pictofixedwidth"');
print $form->select_dolusers($userid, 'userid', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
print '</td></tr>';
// Status
print '<tr><td>'.$langs->trans("Status").'</td><td>';
$liststatus = array(
	Mo::STATUS_DRAFT => $langs->trans("Draft"),
	Mo::STATUS_VALIDATED => $langs->trans("ValidatedToProduce"),
	Mo::STATUS_INPROGRESS => $langs->trans("InProgress"),
	Mo::STATUS_PRODUCED => $langs->trans("StatusMOProduced"),
	Mo::STATUS_CANCELED => $langs->trans("Canceled"),
);
print $form->selectarray('object_status', $liststatus, $object_status, -4);
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
print img_picto('', 'calendar', 'class="pictofixedwidth"');
print $form->selectarray('year', $arrayyears, $year, 0, 0, 0, '', 0, 0, 0, '', 'width75');
print '</td></tr>';
print '<tr><td class="center" colspan="2"><input type="submit" class="button small" name="submit" value="'.$langs->trans("Refresh").'"></td></tr>';
print '</table>';
print '</form>';
print '<br><br>';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre" height="24">';
print '<td class="center">'.$langs->trans("Year").'</td>';
print '<td class="right">'.$langs->trans("NbOfMOs").'</td>';
print '<td class="right">%</td>';
print '</tr>';

$oldyear = 0;
foreach ($data as $val) {
	$year = $val['year'];
	while (!empty($year) && $oldyear > (int) $year + 1) { // If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
		print '<td class="center"><a href="'.dolPrintHTMLForAttributeUrl($_SERVER["PHP_SELF"].'?year='.$oldyear.$paramfilter).'">'.$oldyear.'</a></td>';
		// Empty year just after a year with records: delta is -100%
		$isyearbeforeval = ($oldyear == (int) $year + 1);
		print '<td class="right">0</td>';
		print '<td class="right opacitylow growthdown">'.(($isyearbeforeval && $val['nb'] > 0) ? '-100%' : '').'</td>';
		print '</tr>';
	}

	print '<tr class="oddeven" height="24">';
	print '<td class="center"><a href="'.dolPrintHTMLForAttributeUrl($_SERVER["PHP_SELF"].'?year='.$year.$paramfilter).'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right opacitylow '.((!isset($val['nb_diff']) || $val['nb_diff'] >= 0) ? 'growthup' : 'growthdown').'">'.(isset($val['nb_diff']) ? ($val['nb_diff'] < 0 ? '' : '+').round($val['nb_diff']).'%' : '').'</td>';
	print '</tr>';
	$oldyear = $year;
}

print '</table>';
print '</div>';


print '</div><div class="fichetwothirdright">';


// Show graphs
print '<table class="border centpercent"><tr class="pair nohover"><td class="center">';
if ($mesg) {
	print $mesg;
} else {
	print $px1->show();
}
print '</td></tr></table>';


print '</div></div>';
print '<div class="clearboth"></div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
