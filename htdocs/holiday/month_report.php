<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2011       François Legastelois    <flegastelois@teclib.com>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Tobias Sekan            <tobias.sekan@startmail.com>
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
 *      \file       month_report.php
 *      \ingroup    holiday
 *      \brief      Monthly report of leave requests.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('holiday', 'hrm'));

$action      = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$massaction  = GETPOST('massaction', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ');
$optioncss   = GETPOST('optioncss', 'aZ');

$id = GETPOST('id', 'int');

$search_ref         = GETPOST('search_ref', 'alphanohtml');
$search_employee    = GETPOST('search_employee', 'int');
$search_type        = GETPOST('search_type', 'int');
$search_description = GETPOST('search_description', 'alphanohtml');

$limit       = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield   = GETPOST('sortfield', 'aZ09comma');
$sortorder   = GETPOST('sortorder', 'aZ09comma');

if (!$sortfield) {
	$sortfield = "cp.rowid";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}

$hookmanager->initHooks(array('leavemovementlist'));

$arrayfields = array();
$arrayofmassactions = array();

// Security check
if ($user->socid > 0) {	// Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
$result = restrictedArea($user, 'holiday', $id);

if (!$user->hasRight('holiday', 'readall')) {
	accessforbidden();
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_ref = '';
		$search_employee = '';
		$search_type = '';
		$search_description = '';
		$toselect = array();
		$search_array_options = array();
	}

	if (GETPOST('button_removefilter_x', 'alpha')
		|| GETPOST('button_removefilter.x', 'alpha')
		|| GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha')
		|| GETPOST('button_search.x', 'alpha')
		|| GETPOST('button_search', 'alpha')) {
		$massaction = '';
	}
}

$arrayfields = array(
	'cp.ref'=>array('label' => 'Ref', 'checked'=>1, 'position'=>5),
	'cp.fk_type'=>array('label' => 'Type', 'checked'=>1, 'position'=>10),
	'cp.fk_user'=>array('label' => 'Employee', 'checked'=>1, 'position'=>20),
	'cp.date_debut'=>array('label' => 'DateDebCP', 'checked'=>-1, 'position'=>30),
	'cp.date_fin'=>array('label' => 'DateFinCP', 'checked'=>-1, 'position'=>32),
	'used_days'=>array('label' => 'NbUseDaysCPShort', 'checked'=>-1, 'position'=>34),
	'date_start_month'=>array('label' => 'DateStartInMonth', 'checked'=>1, 'position'=>50),
	'date_end_month'=>array('label' => 'DateEndInMonth', 'checked'=>1, 'position'=>52),
	'used_days_month'=>array('label' => 'NbUseDaysCPShortInMonth', 'checked'=>1, 'position'=>54),
	'cp.description'=>array('label' => 'DescCP', 'checked'=>-1, 'position'=>800),
);


/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$holidaystatic = new Holiday($db);

$listhalfday = array('morning'=>$langs->trans("Morning"), "afternoon"=>$langs->trans("Afternoon"));

$title = $langs->trans('CPTitreMenu');

llxHeader('', $title);

$search_month = GETPOST("remonth", 'int') ? GETPOST("remonth", 'int') : date("m", time());
$search_year = GETPOST("reyear", 'int') ? GETPOST("reyear", 'int') : date("Y", time());
$year_month = sprintf("%04d", $search_year).'-'.sprintf("%02d", $search_month);

$sql = "SELECT cp.rowid, cp.ref, cp.fk_user, cp.date_debut, cp.date_fin, cp.fk_type, cp.description, cp.halfday, cp.statut as status";
$sql .= " FROM ".MAIN_DB_PREFIX."holiday cp";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user u ON cp.fk_user = u.rowid";
$sql .= " WHERE cp.rowid > 0";
$sql .= " AND cp.statut = ".Holiday::STATUS_APPROVED;
$sql .= " AND (";
$sql .= " (date_format(cp.date_debut, '%Y-%m') = '".$db->escape($year_month)."' OR date_format(cp.date_fin, '%Y-%m') = '".$db->escape($year_month)."')";
$sql .= " OR";	// For leave over several months
$sql .= " (date_format(cp.date_debut, '%Y-%m') < '".$db->escape($year_month)."' AND date_format(cp.date_fin, '%Y-%m') > '".$db->escape($year_month)."') ";
$sql .= " )";
if (!empty($search_ref)) {
	$sql .= natural_search('cp.ref', $search_ref);
}
if (!empty($search_employee) && $search_employee > 0) {
	$sql .= " AND cp.fk_user = ".((int) $search_employee);
}
if (!empty($search_type) && $search_type != '-1') {
	$sql .= ' AND cp.fk_type IN ('.$db->sanitize($search_type).')';
}
if (!empty($search_description)) {
	$sql .= natural_search('cp.description', $search_description);
}

$sql .= $db->order($sortfield, $sortorder);

$resql = $db->query($sql);
if (empty($resql)) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if (!empty($search_ref)) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if (!empty($search_employee)) {
	$param .= '&search_employee='.urlencode($search_employee);
}
if (!empty($search_type)) {
	$param .= '&search_type='.urlencode($search_type);
}
if (!empty($search_description)) {
	$param .= '&search_description='.urlencode($search_description);
}

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print load_fiche_titre($langs->trans('MenuReportMonth'), '', 'title_hrm');

// Selection filter
print '<div class="tabBar">';
print $formother->select_month($search_month, 'remonth', 0, 0, 'minwidth50 maxwidth75imp valignmiddle', true);
print $formother->selectyear($search_year, 'reyear', 0, 10, 5, 0, 0, '', 'valignmiddle width75', true);
print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Search")).'" />';
print '</div>';
print '<br>';

$moreforfilter = '';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';

print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<th class="wrapcolumntitle center maxwidthsearch liste_titre">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</th>';
}

// Filter: Ref
if (!empty($arrayfields['cp.ref']['checked'])) {
	print '<th class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</th>';
}

// Filter: Type
if (!empty($arrayfields['cp.fk_type']['checked'])) {
	$typeleaves = $holidaystatic->getTypes(1, -1);
	$arraytypeleaves = array();
	foreach ($typeleaves as $key => $val) {
		$labeltoshow = ($langs->trans($val['code']) != $val['code'] ? $langs->trans($val['code']) : $val['label']);
		$arraytypeleaves[$val['rowid']] = $labeltoshow;
	}

	print '<th class="liste_titre">';
	print $form->selectarray('search_type', $arraytypeleaves, $search_type, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100', 1);
	print '</th>';
}

// Filter: Employee
if (!empty($arrayfields['cp.fk_user']['checked'])) {
	print '<th class="liste_titre">';
	print $form->select_dolusers($search_employee, "search_employee", 1, null, 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth100');
	print '</th>';
}

if (!empty($arrayfields['cp.date_debut']['checked'])) {
	print '<th class="liste_titre"></th>';
}
if (!empty($arrayfields['cp.date_fin']['checked'])) {
	print '<th class="liste_titre"></th>';
}
if (!empty($arrayfields['used_days']['checked'])) {
	print '<th class="liste_titre"></th>';
}
if (!empty($arrayfields['date_start_month']['checked'])) {
	print '<th class="liste_titre"></th>';
}
if (!empty($arrayfields['date_end_month']['checked'])) {
	print '<th class="liste_titre"></th>';
}
if (!empty($arrayfields['used_days_month']['checked'])) {
	print '<th class="liste_titre"></th>';
}

// Filter: Description
if (!empty($arrayfields['cp.description']['checked'])) {
	print '<th class="liste_titre">';
	print '<input type="text" class="maxwidth100" name="search_description" value="'.$search_description.'">';
	print '</th>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<th class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</th>';
}
print '</tr>';

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
}
if (!empty($arrayfields['cp.ref']['checked'])) {
	print_liste_field_titre($arrayfields['cp.ref']['label'], $_SERVER["PHP_SELF"], 'cp.ref', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cp.fk_type']['checked'])) {
	print_liste_field_titre($arrayfields['cp.fk_type']['label'], $_SERVER["PHP_SELF"], 'cp.fk_type', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cp.fk_user']['checked'])) {
	print_liste_field_titre($arrayfields['cp.fk_user']['label'], $_SERVER["PHP_SELF"], 'u.lastname', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['ct.label']['checked'])) {
	print_liste_field_titre($arrayfields['ct.label']['label'], $_SERVER["PHP_SELF"], 'ct.label', '', '', '', $sortfield, $sortorder);
}
if (!empty($arrayfields['cp.date_debut']['checked'])) {
	print_liste_field_titre($arrayfields['cp.date_debut']['label'], $_SERVER["PHP_SELF"], 'cp.date_debut', '', '', '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['cp.date_fin']['checked'])) {
	print_liste_field_titre($arrayfields['cp.date_fin']['label'], $_SERVER["PHP_SELF"], 'cp.date_fin', '', '', '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['used_days']['checked'])) {
	print_liste_field_titre($arrayfields['used_days']['label'], $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'maxwidth125 right ');
}
if (!empty($arrayfields['date_start_month']['checked'])) {
	print_liste_field_titre($arrayfields['date_start_month']['label'], $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['date_end_month']['checked'])) {
	print_liste_field_titre($arrayfields['date_end_month']['label'], $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['used_days_month']['checked'])) {
	print_liste_field_titre($arrayfields['used_days_month']['label'], $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'maxwidth125 right ');
}
if (!empty($arrayfields['cp.description']['checked'])) {
	print_liste_field_titre($arrayfields['cp.description']['label'], $_SERVER["PHP_SELF"], 'cp.description', '', '', '', $sortfield, $sortorder);
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
}
print '</tr>';

if ($num == 0) {
	print '<tr><td colspan="11"><span class="opacitymedium">'.$langs->trans('None').'</span></td></tr>';
} else {
	$tmpuser = new User($db);
	while ($obj = $db->fetch_object($resql)) {
		$tmpuser->fetch($obj->fk_user);

		$date_start = $db->jdate($obj->date_debut, true);
		$date_end = $db->jdate($obj->date_fin, true);

		$tmpstart = dol_getdate($date_start);
		$tmpend = dol_getdate($date_end);

		$starthalfday = ($obj->halfday == -1 || $obj->halfday == 2) ? 'afternoon' : 'morning';
		$endhalfday = ($obj->halfday == 1 || $obj->halfday == 2) ? 'morning' : 'afternoon';

		$halfdayinmonth = $obj->halfday;
		$starthalfdayinmonth = $starthalfday;
		$endhalfdayinmonth = $endhalfday;

		//0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning

		// Set date_start_gmt and date_end_gmt that are date to show for the selected month
		$date_start_inmonth = $db->jdate($obj->date_debut, true);
		$date_end_inmonth = $db->jdate($obj->date_fin, true);
		if ($tmpstart['year'] < $search_year || $tmpstart['mon'] < $search_month) {
			$date_start_inmonth = dol_get_first_day($search_year, $search_month, true);
			$starthalfdayinmonth = 'morning';
			if ($halfdayinmonth == 2) {
				$halfdayinmonth = 1;
			}
			if ($halfdayinmonth == -1) {
				$halfdayinmonth = 0;
			}
		}
		if ($tmpend['year'] > $search_year || $tmpend['mon'] > $search_month) {
			$date_end_inmonth = dol_get_last_day($search_year, $search_month, true) - ((24 * 3600) - 1);
			$endhalfdayinmonth = 'afternoon';
			if ($halfdayinmonth == 2) {
				$halfdayinmonth = -1;
			}
			if ($halfdayinmonth == 1) {
				$halfdayinmonth = 0;
			}
		}

		// Leave request
		$holidaystatic->id = $obj->rowid;
		$holidaystatic->ref = $obj->ref;
		$holidaystatic->statut = $obj->status;
		$holidaystatic->status = $obj->status;
		$holidaystatic->fk_user = $obj->fk_user;
		$holidaystatic->fk_type = $obj->fk_type;
		$holidaystatic->description = $obj->description;
		$holidaystatic->halfday = $obj->halfday;
		$holidaystatic->date_debut = $db->jdate($obj->date_debut);
		$holidaystatic->date_fin = $db->jdate($obj->date_fin);


		print '<tr class="oddeven">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
		}

		if (!empty($arrayfields['cp.ref']['checked'])) {
			print '<td class="nowraponall">'.$holidaystatic->getNomUrl(1, 1).'</td>';
		}
		if (!empty($arrayfields['cp.fk_type']['checked'])) {
			print '<td>'.$arraytypeleaves[$obj->fk_type].'</td>';
		}
		if (!empty($arrayfields['cp.fk_user']['checked'])) {
			print '<td class="tdoverflowmax150">'.$tmpuser->getNomUrl(-1).'</td>';
		}

		if (!empty($arrayfields['cp.date_debut']['checked'])) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_debut), 'day');
			print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$starthalfday]).')</span>';
			print '</td>';
		}

		if (!empty($arrayfields['cp.date_fin']['checked'])) {
			print '<td class="center">'.dol_print_date($db->jdate($obj->date_fin), 'day');
			print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$endhalfday]).')</span>';
			print '</td>';
		}

		if (!empty($arrayfields['used_days']['checked'])) {
			print '<td class="right">'.num_open_day($date_start, $date_end, 0, 1, $obj->halfday).'</td>';
		}

		if (!empty($arrayfields['date_start_month']['checked'])) {
			print '<td class="center">'.dol_print_date($date_start_inmonth, 'day');
			print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$starthalfdayinmonth]).')</span>';
			print '</td>';
		}

		if (!empty($arrayfields['date_end_month']['checked'])) {
			print '<td class="center">'.dol_print_date($date_end_inmonth, 'day');
			print ' <span class="opacitymedium">('.$langs->trans($listhalfday[$endhalfdayinmonth]).')</span>';
			print '</td>';
		}

		if (!empty($arrayfields['used_days_month']['checked'])) {
			print '<td class="right">'.num_open_day($date_start_inmonth, $date_end_inmonth, 0, 1, $halfdayinmonth).'</td>';
		}
		if (!empty($arrayfields['cp.description']['checked'])) {
			print '<td class="maxwidth300 small">';
			print '<div class="twolinesmax">';
			print dolGetFirstLineOfText(dol_string_nohtmltag($obj->description, 1));
			print '</div>';
			print '</td>';
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td></td>';
		}
		print '</tr>';
	}
}
print '</table>';
print '</div>';
print '</form>';

// End of page
llxFooter();
$db->close();
