<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2020      Maxime DEMAREST      <maxime@indelog.fr>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
 *	    \file       htdocs/modulebuilder/template/stats/myobject_index.php
 *      \ingroup    order
 *		\brief      Page with statistics
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include str_replace("..", "", $_SERVER["CONTEXT_DOCUMENT_ROOT"])."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
	$res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
dol_include_once('/mymodule/class/myobject.class.php');
dol_include_once('/mymodule/class/myobjectstats.class.php');
dol_include_once('/mymodule/lib/mymodule_myobject.lib.php');

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

$mode = GETPOSTISSET("mode") ? GETPOST("mode", 'aZ09') : 'statistics';

$userid = GETPOSTINT('userid');
$categ_id = GETPOSTINT('categ_id');

$hookmanager->initHooks(array('mymodulestats', 'myobjectstats', 'globalcard'));

$objecttype = 'myobject';
$object = new MyObject($db);

// List of object we want to manage statistics
$usercanreadstatistic = 1;
$enablepermissioncheck = getDolGlobalInt('MYMODULE_ENABLE_PERMISSION_CHECK');
if ($enablepermissioncheck) {
	$usercanreadstatistic = $user->hasRight($objecttype, 'read');
	if (getDolGlobalInt('MAIN_NEED_EXPORT_PERMISSION_TO_READ_STATISTICS')) {
		$usercanreadstatistic = $user->hasRight($objecttype, 'export');
	}
}

if (!$usercanreadstatistic) {
	accessforbidden();
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

$nowyear = (int) dol_print_date(dol_now('gmt'), "%Y", 'gmt');
$year = GETPOSTINT('year') > 0 ? GETPOSTINT('year') : $nowyear;
$startyear = $year - (!getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalInt('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'mymodule@mymodule'));


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Statistics");
$dir = getMultidirTemp($object);

llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-order page-stats');

$permissiontoadd = 1;
$param = '';
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', dol_buildpath('/mymodule/myobject_list.php', 1).'?mode=common'.preg_replace('/(&|\?)*(mode|groupby)=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', dol_buildpath('/mymodule/myobject_list.php', 1).'?mode=kanban'.preg_replace('/(&|\?)*(mode|groupby)=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
//$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanbanGroupBy'), '', 'fa fa-grip-vertical imgforviewmode', dol_buildpath('/mymodule/aaa_index.php', 1).'?mode=kanbangroupby&groupby=p.fk_opp_status'.preg_replace('/(&|\?)*(mode|groupby)=[^&]+/', '', $param), '', ($mode == 'kanbangroupby' ? 2 : 1), array('morecss' => 'reposition'));
//$newcardbutton .= dolGetButtonTitle($langs->trans('HierarchicView'), '', 'fa fa-stream paddingleft imgforviewmode', dol_buildpath('/mymodule/aaa_index.php', 1).'?mode=hierarchy'.preg_replace('/(&|\?)*(mode|groupby)=[^&]+/', '', $param), '', (($mode == 'hierarchy') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('Statistics'), '', 'fa fa-chart-bar imgforviewmode', dol_buildpath('/mymodule/stats/mymodule_index.php', 1).'?mode=statistics&objecttype=myobject@mymodule'.preg_replace('/(&|\?)*(mode|groupby)=[^&]+/', '', $param), '', ($mode == 'statistics' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', dol_buildpath('/mymodule/myobject_card.php', 1).'?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

$param = '';
$page = 0;
$sortfield = '';
$sortorder = '';
$massactionbutton = '';
$num = 0;
$nbtotalofrecords = 0;
$limit = 0;

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $object->picto, 0, $newcardbutton, '', $limit, 0, 0, 1);


//print load_fiche_titre($title, '', $picto);

dol_mkdir($dir);

$stats = new MyObjectStats($db, $socid, $mode, ($userid > 0 ? $userid : 0), ($categ_id > 0 ? $categ_id : 0));


// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);


$filenamenb = $dir.'/myobjectnbinyear-'.$user->id.'-'.$year.'.png';
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersnbinyear-'.$user->id.'-'.$year.'.png';


$px1 = new DolGraph();
$displaypx1 = false;
$mesg = $px1->isGraphKo();
if (!$mesg) {
	$displaypx1 = true;
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
	$px1->SetYLabel($langs->trans("Nb"));
	$px1->SetShading(3);
	$px1->SetHorizTickIncrement(1);
	$px1->mode = 'depth';
	$px1->SetTitle($langs->trans("ByMonth"));

	$px1->draw($filenamenb, $fileurlnb);
}


/*

// Build graphic amount of object
$data = $stats->getAmountByMonthWithPrevYear($endyear, $startyear);
//var_dump($data);
// $data = array(array('Lib',val1,val2,val3),...)

$fileurlamount = '';
if (!$user->hasRight('societe', 'client', 'voir')) {
	$filenameamount = $dir.'/ordersamountinyear-'.$user->id.'-'.$year.'.png';
	if ($mode == 'customer') {
		$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamountinyear-'.$user->id.'-'.$year.'.png';
	}
	if ($mode == 'supplier') {
		$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamountinyear-'.$user->id.'-'.$year.'.png';
	}
} else {
	$filenameamount = $dir.'/ordersamountinyear-'.$year.'.png';
	if ($mode == 'customer') {
		$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstats&file=ordersamountinyear-'.$year.'.png';
	}
	if ($mode == 'supplier') {
		$fileurlamount = DOL_URL_ROOT.'/viewimage.php?modulepart=orderstatssupplier&file=ordersamountinyear-'.$year.'.png';
	}
}

$px2 = new DolGraph();
$displaypx2 = false;
$mesg = $px2->isGraphKo();
if (!$mesg) {
	$displaypx2 = true;
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
	$px2->SetYLabel($langs->trans("AmountOfOrders"));
	$px2->SetShading(3);
	$px2->SetHorizTickIncrement(1);
	$px2->mode = 'depth';
	$px2->SetTitle($langs->trans("AmountOfOrdersByMonthHT"));

	$px2->draw($filenameamount, $fileurlamount);
}



$data = $stats->getAverageByMonthWithPrevYear($endyear, $startyear);


$fileurl_avg = '';
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
$displaypx3 = false;
$mesg = $px3->isGraphKo();
if (!$mesg) {
	$displaypx3 = true;
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
	$px3->SetMinValue((int) $px3->GetFloorMinValue());
	$px3->SetWidth($WIDTH);
	$px3->SetHeight($HEIGHT);
	$px3->SetShading(3);
	$px3->SetHorizTickIncrement(1);
	$px3->mode = 'depth';
	$px3->SetTitle($langs->trans("AmountAverage"));

	$px3->draw($filename_avg, $fileurl_avg);
}

*/


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



$h = 0;
$head = array();
$head[$h][0] = $_SERVER["PHP_SELF"].'?mode='.$mode;
$head[$h][1] = $langs->trans("ByMonthYear");
$head[$h][2] = 'byyear';
$h++;

$type = 'myobject_stats';

complete_head_from_modules($conf, $langs, null, $head, $h, $type);

print dol_get_fiche_head($head, 'byyear', '', -1);

print '<div class="fichecenter"><div class="fichethirdleft">';


// Show filter box
print '<form name="stats" method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td class="liste_titre" colspan="2">'.$langs->trans("Filter").'</td></tr>';
// Company
/*
print '<tr><td class="left">'.$langs->trans("ThirdParty").'</td><td class="left">';
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
*/
// User
if (array_key_exists('fk_user_creat', $object->fields)) {
	print '<tr><td>'.$langs->trans("CreatedBy").'</td><td>';
	print img_picto('', 'user', 'class="pictofixedwidth"');
	print $form->select_dolusers($userid, 'userid', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'widthcentpercentminusx maxwidth300');
}
// Status
if (array_key_exists('status', $object->fields)) {
	print '<tr><td>'.$langs->trans("Status").'</td><td>';
	$liststatus = $object->fields['status']['arrayofkeyvalue'];
	print $form->selectarray('object_status', $liststatus, GETPOST('object_status', 'intcomma'), -4);
	print '</td></tr>';
}
// Year
print '<tr><td class="left">'.$langs->trans("Year").'</td><td class="left">';
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
print '<tr><td align="center" colspan="2"><input type="submit" class="button small" name="submit" value="'.$langs->trans("Refresh").'"></td></tr>';
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

$oldyear = 0;
foreach ($data as $val) {
	$year = $val['year'];
	while (!empty($year) && $oldyear > (int) $year + 1) { // If we have empty year
		$oldyear--;

		print '<tr class="oddeven" height="24">';
		print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$oldyear.'&amp;mode='.$mode.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$oldyear.'</a></td>';
		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '<td class="right">0</td>';
		print '<td class="right"></td>';
		print '</tr>';
	}


	print '<tr class="oddeven" height="24">';
	print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?year='.$year.'&amp;mode='.$mode.($socid > 0 ? '&socid='.$socid : '').($userid > 0 ? '&userid='.$userid : '').'">'.$year.'</a></td>';
	print '<td class="right">'.$val['nb'].'</td>';
	print '<td class="right opacitylow" style="'.((!isset($val['nb_diff']) || $val['nb_diff'] >= 0) ? 'color: green;' : 'color: red;').'">'.(isset($val['nb_diff']) ? round($val['nb_diff']) : "0").'%</td>';
	print '<td class="right">'.price(price2num($val['total'], 'MT'), 1).'</td>';
	print '<td class="right opacitylow" style="'.((!isset($val['total_diff']) || $val['total_diff'] >= 0) ? 'color: green;' : 'color: red;').'">'.(isset($val['total_diff']) ? round($val['total_diff']) : "0").'%</td>';
	print '<td class="right">'.price(price2num($val['avg'], 'MT'), 1).'</td>';
	print '<td class="right opacitylow" style="'.((!isset($val['avg_diff']) || $val['avg_diff'] >= 0) ? 'color: green;' : 'color: red;').'">'.(isset($val['avg_diff']) ? round($val['avg_diff']) : "0").'%</td>';
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
	if ($displaypx1) {
		print $px1->show();
		print "<br>\n";
	}
	/*
	if ($displaypx2) {
		print $px2->show();
		print "<br>\n";
	}
	if ($displaypx3) {
		print $px3->show();
		print "<br>\n";
	}
	*/
}
print '</td></tr></table>';


print '</div></div>';
print '<div class="clearboth"></div>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
