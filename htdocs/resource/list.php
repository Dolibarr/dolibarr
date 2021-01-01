<?php
/* Copyright (C) 2013-2014  Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2018-2019  Frédéric France         <frederic.france@netlogic.fr>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';

// Load translation files required by the page
$langs->loadLangs(array("resource", "companies", "other"));

// Get parameters
$id             = GETPOST('id', 'int');
$action         = GETPOST('action', 'alpha');

$lineid         = GETPOST('lineid', 'int');
$element        = GETPOST('element', 'alpha');
$element_id     = GETPOST('element_id', 'int');
$resource_id    = GETPOST('resource_id', 'int');

$sortorder      = GETPOST('sortorder', 'alpha');
$sortfield      = GETPOST('sortfield', 'aZ09comma');

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'resourcelist';

// Initialize technical objects
$object = new Dolresource($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
if (!is_array($search_array_options)) $search_array_options = array();
$search_ref = GETPOST("search_ref", 'alpha');
$search_type = GETPOST("search_type", 'alpha');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;


$filter = array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&amp;contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&amp;limit='.urlencode($limit);

if ($search_ref != '') {
	$param .= '&search_ref='.urlencode($search_ref);
	$filter['t.ref'] = $search_ref;
}
if ($search_type != '') {
	$param .= '&search_type='.urlencode($search_type);
	$filter['ty.label'] = $search_type;
}

// Add $param from extra fields
foreach ($search_array_options as $key => $val)
{
	$crit = $val;
	$tmpkey = preg_replace('/search_options_/', '', $key);
	$typ = $extrafields->attributes[$object->table_element]['type'][$tmpkey];
	if ($val != '') {
		$param .= '&search_options_'.$tmpkey.'='.urlencode($val);
	}
	$mode_search = 0;
	if (in_array($typ, array('int', 'double', 'real'))) $mode_search = 1; // Search on a numeric
	if (in_array($typ, array('sellist', 'link')) && $crit != '0' && $crit != '-1') $mode_search = 2; // Search on a foreign key int
	if ($crit != '' && (!in_array($typ, array('select', 'sellist')) || $crit != '0') && (!in_array($typ, array('link')) || $crit != '-1'))
	{
		$filter['ef.'.$tmpkey] = natural_search('ef.'.$tmpkey, $crit, $mode_search);
	}
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);


$hookmanager->initHooks(array('resourcelist'));

if (empty($sortorder)) $sortorder = "ASC";
if (empty($sortfield)) $sortfield = "t.ref";
if (empty($arch)) $arch = 0;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (!$user->rights->resource->read) {
		accessforbidden();
}
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
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // Both test are required to be compatible with all browsers
{
	$search_ref = "";
	$search_type = "";
	$search_array_options = array();
	$filter = array();
}


/*
 * Action
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


/*
 * View
 */

$form = new Form($db);

$pagetitle = $langs->trans('ResourcePageIndex');
llxHeader('', $pagetitle, '');

// Confirmation suppression resource line
if ($action == 'delete_resource')
{
	print $form->formconfirm($_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id."&lineid=".$lineid, $langs->trans("DeleteResource"), $langs->trans("ConfirmDeleteResourceElement"), "confirm_delete_resource", '', '', 1);
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$ret = $object->fetch_all('', '', 0, 0, $filter);
	if ($ret == -1) {
		dol_print_error($db, $object->error);
		exit;
	} else {
		$nbtotalofrecords = $ret;
	}
}

// Load object list
$ret = $object->fetch_all($sortorder, $sortfield, $limit, $offset, $filter);
if ($ret == -1) {
	dol_print_error($db, $object->error);
	exit;
} else {
	$newcardbutton = '';
	if ($user->rights->resource->write)
	{
		$newcardbutton .= dolGetButtonTitle($langs->trans('MenuResourceAdd'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/resource/card.php?action=create');
	}

	print_barre_liste($pagetitle, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $ret + 1, $nbtotalofrecords, 'object_resource', 0, $newcardbutton, '', $limit, 0, 0, 1);
}

$moreforfilter = '';

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre_filter">';
if (!empty($arrayfields['t.ref']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_ref" value="'.$search_ref.'" size="6">';
	print '</td>';
}
if (!empty($arrayfields['ty.label']['checked']))
{
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="search_type" value="'.$search_type.'" size="6">';
	print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Action column
print '<td class="liste_titre maxwidthsearch">';
$searchpicto = $form->showFilterAndCheckAddButtons(0);
print $searchpicto;
print '</td>';
print "</tr>\n";

print '<tr class="liste_titre">';
if (!empty($arrayfields['t.ref']['checked'])) print_liste_field_titre($arrayfields['t.ref']['label'], $_SERVER["PHP_SELF"], "t.ref", "", $param, "", $sortfield, $sortorder);
if (!empty($arrayfields['ty.label']['checked'])) print_liste_field_titre($arrayfields['ty.label']['label'], $_SERVER["PHP_SELF"], "ty.label", "", $param, "", $sortfield, $sortorder);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";


if ($ret)
{
	foreach ($object->lines as $resource)
	{
		print '<tr class="oddeven">';

		if (!empty($arrayfields['t.ref']['checked']))
		{
			print '<td>';
			print $resource->getNomUrl(5);
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		if (!empty($arrayfields['ty.label']['checked']))
		{
			print '<td>';
			print $resource->type_label;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Extra fields
		$obj = (Object) $resource->array_options;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';

		print '<td class="center">';
		print '<a class="editfielda" href="./card.php?action=edit&token='.newToken().'&id='.$resource->id.'">';
		print img_edit();
		print '</a>';
		print '&nbsp;';
		print '<a href="./card.php?action=delete&token='.newToken().'&id='.$resource->id.'">';
		print img_delete('', 'class="marginleftonly"');
		print '</a>';
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print '</tr>';
	}
} else {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
	print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

print '</table>';
print "</form>\n";

// End of page
llxFooter();
$db->close();
