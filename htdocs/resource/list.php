<?php
/* Copyright (C) 2013-2014  Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2021  Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/resource/list.php
 *      \ingroup    resource
 *      \brief      Page to manage resource objects
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';

// Load translation files required by the page
$langs->loadLangs(array("resource", "companies", "other"));

// Get parameters
$id             = GETPOSTINT('id');
$action         = GETPOST('action', 'alpha');
$massaction     = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)

$lineid         = GETPOSTINT('lineid');
$element        = GETPOST('element', 'alpha');
$element_id     = GETPOSTINT('element_id');
$resource_id    = GETPOSTINT('resource_id');

$sortorder      = GETPOST('sortorder', 'aZ09comma');
$sortfield      = GETPOST('sortfield', 'aZ09comma');
$optioncss = GETPOST('optioncss', 'alpha');

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'resourcelist';

// Initialize technical objects
$object = new Dolresource($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
if (!is_array($search_array_options)) {
	$search_array_options = array();
}
$search_ref = GETPOST("search_ref", 'alpha');
$search_type = GETPOST("search_type", 'alpha');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;

$filter = array();

$hookmanager->initHooks(array('resourcelist'));

if (empty($sortorder)) {
	$sortorder = "ASC";
}
if (empty($sortfield)) {
	$sortfield = "t.ref";
}
if (empty($arch)) {
	$arch = 0;
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$arrayfields = array(
		't.ref' => array(
				'label' => $langs->trans("Ref"),
				'checked' => 1
		),
		'ty.label' => array(
				'label' => $langs->trans("ResourceType"),
				'checked' => 1
		),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$search_ref = "";
	$search_type = "";
	$search_array_options = array();
	$filter = array();
}

if (!$user->hasRight('resource', 'read')) {
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


/*
 * View
 */

$form = new Form($db);
$objectstatic = new Dolresource($db);

//$help_url="EN:Module_MyObject|FR:Module_MyObject_FR|ES:Módulo_MyObject";
$help_url = '';
$title = $langs->trans('Resources');
$morejs = array();
$morecss = array();


$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

$atleastonefieldinlines = 0;
foreach ($arrayfields as $tmpkey => $tmpval) {
	if (preg_match('/^fd\./', $tmpkey) && !empty($arrayfields[$tmpkey]['checked'])) {
		$atleastonefieldinlines++;
		break;
	}
}

$sql = "SELECT";
$sql .= " t.rowid,";
$sql .= " t.entity,";
$sql .= " t.ref,";
$sql .= " t.address,";
$sql .= " t.zip,";
$sql .= " t.town,";
$sql .= " t.fk_country,";
$sql .= " t.fk_state,";
$sql .= " t.description,";
$sql .= " t.phone,";
$sql .= " t.email,";
$sql .= " t.max_users,";
$sql .= " t.url,";
$sql .= " t.fk_code_type_resource,";
$sql .= " t.tms as date_modification,";
$sql .= " t.datec as date_creation";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."resource as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (t.rowid = ef.fk_object)";
}

// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " WHERE t.entity IN (".getEntity('resource').")";
if ($search_ref) {
	$sql .= natural_search('t.ref', $search_ref);
}
if ($search_type) {
	$sql .= natural_search('ty.label', $search_type);
}

// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);


// Direct jump if only one record found
if ($num == 1 && getDolGlobalString('MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE') && !$page) {
	$obj = $db->fetch_object($resql);
	$id = $obj->rowid;
	header("Location: ".dol_buildpath('/resource/card.php', 1).'?id='.$id);
	exit;
}

// Output page

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'bodyforlist');	// Can use also classforhorizontalscrolloftabs instead of bodyforlist for no horizontal scroll

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_ref != '') {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_type != '') {
	$param .= '&search_type='.urlencode($search_type);
}

// Including the previous script generate the correct SQL filter for all the extrafields
// we are playing with the behaviour of the Dolresource::fetchAll() by generating a fake
// extrafields filter key to make it works
$filter['ef.resource'] = $sql;

if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}

// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';


// Confirmation suppression resource line
if ($action == 'delete_resource') {
	print $form->formconfirm($_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id."&lineid=".$lineid, $langs->trans("DeleteResource"), $langs->trans("ConfirmDeleteResourceElement"), "confirm_delete_resource", '', '', 1);
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));


print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	//$ret = $object->fetchAll('', '', 0, 0, $filter);
	if ($ret == -1) {
		dol_print_error($db, $object->error);
		exit;
	} else {
		$nbtotalofrecords = $ret;
	}
}

// Load object list
//$ret = $object->fetchAll($sortorder, $sortfield, $limit, $offset, $filter);
if ($ret == -1) {
	dol_print_error($db, $object->error);
	exit;
} else {
	$newcardbutton = '';
	if ($user->hasRight('resource', 'write')) {
		$newcardbutton .= dolGetButtonTitle($langs->trans('MenuResourceAdd'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/resource/card.php?action=create');
	}

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $ret + 1, $nbtotalofrecords, 'object_resource', 0, $newcardbutton, '', $limit, 0, 0, 1);
}

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
}
if (!empty($arrayfields['t.ref']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
	print '</td>';
}
if (!empty($arrayfields['ty.label']['checked'])) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_type" value="'.$search_type.'" size="6">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';
}
print "</tr>\n";

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
if (!empty($arrayfields['t.ref']['checked'])) {
	print_liste_field_titre($arrayfields['t.ref']['label'], $_SERVER["PHP_SELF"], "t.ref", "", $param, "", $sortfield, $sortorder);
}
if (!empty($arrayfields['ty.label']['checked'])) {
	print_liste_field_titre($arrayfields['ty.label']['label'], $_SERVER["PHP_SELF"], "ty.label", "", $param, "", $sortfield, $sortorder);
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print "</tr>\n";

$i = 0;
$totalarray = array();
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	$objectstatic->id = $obj->rowid;
	$objectstatic->ref = $obj->ref;
	$objectstatic->type_label= $obj->label;
	print '<tr class="oddeven">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="center">';
		print '<a class="editfielda" href="./card.php?action=edit&token='.newToken().'&id='.$resource->id.'">';
		print img_edit();
		print '</a>';
		print '&nbsp;';
		print '<a href="./card.php?action=delete&token='.newToken().'&id='.$resource->id.'">';
		print img_delete('', 'class="marginleftonly"');
		print '</a>';
		print '</td>';
	}

	if (!empty($arrayfields['t.ref']['checked'])) {
		print '<td>';
		print $objectstatic->getNomUrl(5);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	if (!empty($arrayfields['ty.label']['checked'])) {
		print '<td>';
		print $objectstatic->type_label;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	// Extra fields
	//$obj = (object) $resource->array_options;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="center">';
		print '<a class="editfielda" href="./card.php?action=edit&token='.newToken().'&id='.$resource->id.'">';
		print img_edit();
		print '</a>';
		print '&nbsp;';
		print '<a href="./card.php?action=delete&token='.newToken().'&id='.$resource->id.'">';
		print img_delete('', 'class="marginleftonly"');
		print '</a>';
		print '</td>';
	}
	if (!$i) {
		$totalarray['nbfield']++;
	}

	print '</tr>';
	$i++;
}
/*if ($ret) {

} else {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}*/

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
