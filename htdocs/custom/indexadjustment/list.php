<?php
/* Copyright (C) 2025 Florian Hödl <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file       list.php
 * \ingroup    indexadjustment
 * \brief      List page for Index Adjustments
 */

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/class/indexadjustment.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/indexadjustment/lib/indexadjustment.lib.php';

// Load translations
$langs->loadLangs(array("indexadjustment@indexadjustment", "other"));

// Security check
if (!$user->hasRight('indexadjustment', 'indexadjustment', 'read')) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'indexadjustmentlist';

// Search parameters
$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_status = GETPOST('search_status', 'int');

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$sortfield) {
	$sortfield = "t.datec";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

$object = new IndexAdjustment($db);

// Initialize technical objects
$hookmanager->initHooks(array('indexadjustmentlist'));
$extrafields = new ExtraFields($db);

/*
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$search_ref = "";
	$search_label = "";
	$search_status = "";
	$toselect = array();
}

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

$now = dol_now();

$title = $langs->trans("IndexAdjustments");
$help_url = '';
$morejs = array();
$morecss = array();

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');

// Build SQL query
$sql = "SELECT t.rowid, t.ref, t.label, t.adjustment_date, t.adjustment_percent,";
$sql .= " t.status, t.total_contracts, t.total_lines, t.total_ht_before, t.total_ht_after,";
$sql .= " t.datec, t.date_executed, t.fk_user_creat, t.fk_soc,";
$sql .= " s.nom as socname";
$sql .= " FROM " . MAIN_DB_PREFIX . "indexadjustment as t";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON t.fk_soc = s.rowid";
$sql .= " WHERE t.entity IN (" . getEntity('indexadjustment') . ")";

if ($search_ref) {
	$sql .= natural_search("t.ref", $search_ref);
}
if ($search_label) {
	$sql .= natural_search("t.label", $search_label);
}
if ($search_status != '' && $search_status >= 0) {
	$sql .= " AND t.status = " . (int)$search_status;
}

// Count total
$sqlcount = preg_replace('/^SELECT[^FROM]*FROM/Ui', 'SELECT COUNT(*) as nbtotalofrecords FROM', $sql);
$resql = $db->query($sqlcount);
$nbtotalofrecords = 0;
if ($resql) {
	$objcount = $db->fetch_object($resql);
	$nbtotalofrecords = $objcount->nbtotalofrecords;
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// New button
$newcardbutton = '';
if ($user->hasRight('indexadjustment', 'indexadjustment', 'write')) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewIndexAdjustment'), '', 'fa fa-plus-circle', dol_buildpath('/indexadjustment/wizard.php', 1) . '?action=create');
}

print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '" name="formfilter">';
print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';
print '<input type="hidden" name="page" value="' . $page . '">';
print '<input type="hidden" name="contextpage" value="' . $contextpage . '">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'fa-percent', 0, $newcardbutton, '', $limit, 0, 0, 1);

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste">';

// Header
print '<tr class="liste_titre">';
print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "t.ref", "", "", "", $sortfield, $sortorder);
print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "t.label", "", "", "", $sortfield, $sortorder);
print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "s.nom", "", "", "", $sortfield, $sortorder);
print_liste_field_titre("AdjustmentDate", $_SERVER["PHP_SELF"], "t.adjustment_date", "", "", "", $sortfield, $sortorder, 'center ');
print_liste_field_titre("AdjustmentPercent", $_SERVER["PHP_SELF"], "t.adjustment_percent", "", "", "", $sortfield, $sortorder, 'right ');
print_liste_field_titre("TotalContracts", $_SERVER["PHP_SELF"], "t.total_contracts", "", "", "", $sortfield, $sortorder, 'right ');
print_liste_field_titre("TotalHTBefore", $_SERVER["PHP_SELF"], "t.total_ht_before", "", "", "", $sortfield, $sortorder, 'right ');
print_liste_field_titre("TotalHTAfter", $_SERVER["PHP_SELF"], "t.total_ht_after", "", "", "", $sortfield, $sortorder, 'right ');
print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "t.status", "", "", "", $sortfield, $sortorder, 'center ');
print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "t.datec", "", "", "", $sortfield, $sortorder, 'center ');
print '</tr>';

// Search row
print '<tr class="liste_titre">';
print '<td><input type="text" class="flat maxwidth100" name="search_ref" value="' . dol_escape_htmltag($search_ref) . '"></td>';
print '<td><input type="text" class="flat maxwidth200" name="search_label" value="' . dol_escape_htmltag($search_label) . '"></td>';
print '<td></td>';
print '<td></td>';
print '<td></td>';
print '<td></td>';
print '<td></td>';
print '<td></td>';
print '<td class="center">';
print $form->selectarray('search_status', array(0 => $langs->trans('StatusDraft'), 1 => $langs->trans('StatusValidated'), 2 => $langs->trans('StatusExecuted'), 9 => $langs->trans('StatusCancelled')), $search_status, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100');
print '</td>';
print '<td class="liste_titre center">';
print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
print '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("RemoveFilter"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
print '</td>';
print '</tr>';

// Data rows
$i = 0;
while ($i < min($num, $limit)) {
	$obj = $db->fetch_object($resql);

	$object->id = $obj->rowid;
	$object->ref = $obj->ref;
	$object->label = $obj->label;
	$object->status = $obj->status;

	print '<tr class="oddeven">';

	// Ref
	print '<td class="nowraponall">';
	print $object->getNomUrl(1);
	print '</td>';

	// Label
	print '<td>' . dol_escape_htmltag($obj->label) . '</td>';

	// ThirdParty
	print '<td>' . ($obj->socname ? dol_escape_htmltag($obj->socname) : $langs->trans("AllCustomers")) . '</td>';

	// Adjustment Date
	print '<td class="center">' . dol_print_date($db->jdate($obj->adjustment_date), 'day') . '</td>';

	// Percent
	print '<td class="right">' . ($obj->adjustment_percent >= 0 ? '+' : '') . number_format($obj->adjustment_percent, 2) . '%</td>';

	// Total Contracts
	print '<td class="right">' . $obj->total_contracts . '</td>';

	// Total HT Before
	print '<td class="right">' . price($obj->total_ht_before) . '</td>';

	// Total HT After
	print '<td class="right">' . price($obj->total_ht_after) . '</td>';

	// Status
	print '<td class="center">' . $object->getLibStatut(5) . '</td>';

	// Date Creation
	print '<td class="center">' . dol_print_date($db->jdate($obj->datec), 'dayhour') . '</td>';

	print '</tr>';
	$i++;
}

if ($num == 0) {
	print '<tr class="oddeven"><td colspan="10" class="opacitymedium">' . $langs->trans("NoRecordFound") . '</td></tr>';
}

print '</table>';
print '</div>';
print '</form>';

llxFooter();
$db->close();
