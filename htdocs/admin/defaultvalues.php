<?php
/* Copyright (C) 2017-2020	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2017-2018	Regis Houssin		<regis.houssin@inodbox.com>
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
 *       \file      htdocs/admin/defaultvalues.php
 *       \brief     Page to set default values used used in a create form
 *       			Default values are stored into $user->default_values[url]['createform']['querystring'|'_noquery_'][paramkey]=paramvalue
 *       			Default filters are stored into $user->default_values[url]['filters']['querystring'|'_noquery_'][paramkey]=paramvalue
 *       			Default sort order are stored into $user->default_values[url]['sortorder']['querystring'|'_noquery_'][paramkey]=paramvalue
 *       			Default focus are stored into $user->default_values[url]['focus']['querystring'|'_noquery_'][paramkey]=paramvalue
 *       			Mandatory fields are stored into $user->default_values[url]['mandatory']['querystring'|'_noquery_'][paramkey]=paramvalue
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/defaultvalues.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'admin', 'sms', 'other', 'errors'));

if (!$user->admin) {
	accessforbidden();
}

$id = GETPOST('rowid', 'int');
$action = GETPOST('action', 'aZ09');
$optioncss = GETPOST('optionscss', 'alphanohtml');

$mode = GETPOST('mode', 'aZ09') ? GETPOST('mode', 'aZ09') : 'createform'; // 'createform', 'filters', 'sortorder', 'focus'

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'page,param';
}
if (!$sortorder) {
	$sortorder = 'ASC';
}

$defaulturl = GETPOST('defaulturl', 'alphanohtml');
$defaultkey = GETPOST('defaultkey', 'alphanohtml');
$defaultvalue = GETPOST('defaultvalue', 'restricthtml');

$defaulturl = preg_replace('/^\//', '', $defaulturl);

$urlpage = GETPOST('urlpage', 'alphanohtml');
$key = GETPOST('key', 'alphanohtml');
$value = GETPOST('value', 'restricthtml');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admindefaultvalues', 'globaladmin'));


$object = new DefaultValues($db);
/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && !empty($massaction) && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$defaulturl = '';
	$defaultkey = '';
	$defaultvalue = '';
	$toselect = array();
	$search_array_options = array();
}

if ($action == 'setMAIN_ENABLE_DEFAULT_VALUES') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'MAIN_ENABLE_DEFAULT_VALUES', 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_set_const($db, 'MAIN_ENABLE_DEFAULT_VALUES', 0, 'chaine', 0, '', $conf->entity);
	}
}

if (($action == 'add' || (GETPOST('add') && $action != 'update')) || GETPOST('actionmodify')) {
	$error = 0;

	if (($action == 'add' || (GETPOST('add') && $action != 'update'))) {
		if (empty($defaulturl)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Url")), null, 'errors');
			$error++;
		}
		if (empty($defaultkey)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Field")), null, 'errors');
			$error++;
		}
	}
	if (GETPOST('actionmodify')) {
		if (empty($urlpage)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Url")), null, 'errors');
			$error++;
		}
		if (empty($key)) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Field")), null, 'errors');
			$error++;
		}
	}

	if (!$error) {
		if ($action == 'add' || (GETPOST('add') && $action != 'update')) {
			$object->type=$mode;
			$object->user_id=0;
			$object->page=$defaulturl;
			$object->param=$defaultkey;
			$object->value=$defaultvalue;
			$object->entity=$conf->entity;
			$result=$object->create($user);
			if ($result<0) {
				$action = '';
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				$action = "";
				$defaulturl = '';
				$defaultkey = '';
				$defaultvalue = '';
			}
		}
		if (GETPOST('actionmodify')) {
			$object->id=$id;
			$object->type=$mode;
			$object->page=$urlpage;
			$object->param=$key;
			$object->value=$value;
			$object->entity=$conf->entity;
			$result=$object->update($user);
			if ($result<0) {
				$action = '';
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				$action = "";
				$defaulturl = '';
				$defaultkey = '';
				$defaultvalue = '';
			}
		}
	}
}

// Delete line from delete picto
if ($action == 'delete') {
	$object->id=$id;
	$result=$object->delete($user);
	if ($result<0) {
		$action = '';
		setEventMessages($object->error, $object->errors, 'errors');
	}
}



/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

$wikihelp = 'EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

$param = '&mode='.$mode;

$enabledisablehtml = $langs->trans("EnableDefaultValues").' ';
if (empty($conf->global->MAIN_ENABLE_DEFAULT_VALUES)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_DEFAULT_VALUES&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setMAIN_ENABLE_DEFAULT_VALUES&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}

print load_fiche_titre($langs->trans("DefaultValues"), $enabledisablehtml, 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("DefaultValuesDesc")."</span><br>\n";
print "<br>\n";

if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.urlencode($limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($defaulturl) {
	$param .= '&defaulturl='.urlencode($defaulturl);
}
if ($defaultkey) {
	$param .= '&defaultkey='.urlencode($defaultkey);
}
if ($defaultvalue) {
	$param .= '&defaultvalue='.urlencode($defaultvalue);
}


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';

$head = defaultvalues_prepare_head();

print dol_get_fiche_head($head, $mode, '', -1, '');

if ($mode == 'sortorder') {
	print info_admin($langs->trans("WarningSettingSortOrder")).'<br>';
}
if ($mode == 'mandatory') {
	print info_admin($langs->trans("FeatureSupportedOnTextFieldsOnly")).'<br>';
}

print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" id="action" name="action" value="">';
print '<input type="hidden" id="mode" name="mode" value="'.dol_escape_htmltag($mode).'">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
// Page
$texthelp = $langs->trans("PageUrlForDefaultValues");
if ($mode == 'createform') {
	$texthelp .= $langs->trans("PageUrlForDefaultValuesCreate", 'societe/card.php', 'societe/card.php?abc=val1&def=val2');
} else {
	$texthelp .= $langs->trans("PageUrlForDefaultValuesList", 'societe/list.php', 'societe/list.php?abc=val1&def=val2');
}
$texthelp .= '<br><br>'.$langs->trans("AlsoDefaultValuesAreEffectiveForActionCreate");
$texturl = $form->textwithpicto($langs->trans("RelativeURL"), $texthelp);
print_liste_field_titre($texturl, $_SERVER["PHP_SELF"], 'page,param', '', $param, '', $sortfield, $sortorder);
// Field
$texthelp = $langs->trans("TheKeyIsTheNameOfHtmlField");
if ($mode != 'sortorder') {
	$textkey = $form->textwithpicto($langs->trans("Field"), $texthelp);
} else {
	$texthelp = 'field or alias.field';
	$textkey = $form->textwithpicto($langs->trans("Field"), $texthelp);
}
print_liste_field_titre($textkey, $_SERVER["PHP_SELF"], 'param', '', $param, '', $sortfield, $sortorder);
// Value
if ($mode != 'focus' && $mode != 'mandatory') {
	if ($mode != 'sortorder') {
		$substitutionarray = getCommonSubstitutionArray($langs, 2, array('object', 'objectamount')); // Must match list into GETPOST
		unset($substitutionarray['__USER_SIGNATURE__']);
		$texthelp = $langs->trans("FollowingConstantsWillBeSubstituted").'<br>';
		foreach ($substitutionarray as $key => $val) {
			$texthelp .= $key.' -> '.$val.'<br>';
		}
		$textvalue = $form->textwithpicto($langs->trans("Value"), $texthelp, 1, 'help', '', 0, 2, 'subsitutiontooltip');
	} else {
		$texthelp = 'ASC or DESC';
		$textvalue = $form->textwithpicto($langs->trans("SortOrder"), $texthelp);
	}
	print_liste_field_titre($textvalue, $_SERVER["PHP_SELF"], 'value', '', $param, '', $sortfield, $sortorder);
}
// Entity
if (!empty($conf->multicompany->enabled) && !$user->entity) {
	print_liste_field_titre("Entity", $_SERVER["PHP_SELF"], 'entity,page', '', $param, '', $sortfield, $sortorder);
} else {
	print_liste_field_titre("", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
}
// Actions
print_liste_field_titre("", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
print "</tr>\n";


// Line to add new record
print "\n";

print '<tr class="oddeven">';
// Page
print '<td>';
print '<input type="text" class="flat minwidth200 maxwidthonsmartphone" name="defaulturl" value="'.dol_escape_htmltag(GETPOST('defaulturl', 'alphanohtml')).'">';
print '</td>'."\n";
// Field
print '<td>';
print '<input type="text" class="flat maxwidth100onsmartphone" name="defaultkey" value="'.dol_escape_htmltag(GETPOST('defaultkey', 'alphanohtml')).'">';
print '</td>';
// Value
if ($mode != 'focus' && $mode != 'mandatory') {
	print '<td>';
	print '<input type="text" class="flat maxwidth100onsmartphone" name="defaultvalue" value="">';
	print '</td>';
}
// Limit to superadmin
if (!empty($conf->multicompany->enabled) && !$user->entity) {
	print '<td>';
	print '<input type="text" class="flat" size="1" disabled name="entity" value="'.$conf->entity.'">'; // We see environment, but to change it we must switch on other entity
	print '</td>';
} else {
	print '<td class="center">';
	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
	print '</td>';
}
print '<td class="center">';
$disabled = '';
if (empty($conf->global->MAIN_ENABLE_DEFAULT_VALUES)) {
	$disabled = ' disabled="disabled"';
}
print '<input type="submit" class="button"'.$disabled.' value="'.$langs->trans("Add").'" name="add">';
print '</td>'."\n";
print '</tr>'."\n";

$result = $object->fetchAll($sortorder, $sortfield, 0, 0, array('t.type'=>$mode,'t.entity'=>array($user->entity,$conf->entity)));

if (!is_array($result) && $result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
} elseif (is_array($result) && count($result) > 0) {
	foreach ($result as $key => $defaultvalue) {
		print '<tr class="oddeven">';

		// Page
		print '<td>';
		if ($action != 'edit' || GETPOST('rowid', 'int') != $defaultvalue->id) print $defaultvalue->page;
		else print '<input type="text" name="urlpage" value="'.dol_escape_htmltag($defaultvalue->page).'">';
		print '</td>'."\n";

		// Field
		print '<td>';
		if ($action != 'edit' || GETPOST('rowid') != $defaultvalue->id) print $defaultvalue->param;
		else print '<input type="text" name="key" value="'.dol_escape_htmltag($defaultvalue->param).'">';
		print '</td>'."\n";

		// Value
		if ($mode != 'focus' && $mode != 'mandatory') {
			print '<td>';
			if ($action != 'edit' || GETPOST('rowid') != $defaultvalue->id) print dol_escape_htmltag($defaultvalue->value);
			else print '<input type="text" name="value" value="'.dol_escape_htmltag($defaultvalue->value).'">';
			print '</td>';
		}

		print '<td></td>';

		// Actions
		print '<td class="center">';
		if ($action != 'edit' || GETPOST('rowid') != $defaultvalue->id)	{
			print '<a class="editfielda marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$defaultvalue->id.'&entity='.$defaultvalue->entity.'&mode='.$mode.'&action=edit&token='.newToken().'">'.img_edit().'</a>';
			print '<a class="marginleftonly marginrightonly" href="'.$_SERVER['PHP_SELF'].'?rowid='.$defaultvalue->id.'&entity='.$defaultvalue->entity.'&mode='.$mode.'&action=delete&token='.newToken().'">'.img_delete().'</a>';
		} else {
			print '<input type="hidden" name="page" value="'.$page.'">';
			print '<input type="hidden" name="rowid" value="'.$id.'">';
			print '<div name="'.(!empty($defaultvalue->id) ? $defaultvalue->id : 'none').'"></div>';
			print '<input type="submit" class="button button-edit" name="actionmodify" value="'.$langs->trans("Modify").'">';
			print '<input type="submit" class="button button-cancel" name="actioncancel" value="'.$langs->trans("Cancel").'">';
		}
		print '</td>';

		print "</tr>\n";
	}
}

print '</table>';
print '</div>';

print dol_get_fiche_end();

print "</form>\n";

// End of page
llxFooter();
$db->close();
