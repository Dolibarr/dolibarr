<?php
/* Copyright (C) 2026		John BOTELLA
 * Copyright (C) 2025-2026  Frédéric France         <frederic.france@free.fr>
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
 * \file    quickmemo/lib/quickmemo.lib.php
 * \ingroup quickmemo
 * \brief   Library files with common functions for QuickMemo
 */

/**
 * Prepare admin pages header
 *
 * @return array<array{string,string,string}>
 */
function quickmemoAdminPrepareHead()
{
	global $langs, $conf;

	// global $db;
	// $extrafields = new ExtraFields($db);
	// $extrafields->fetch_name_optionals_label('myobject');

	$langs->load("quickmemo");

	$h = 0;
	$head = array();

	$head[$h][0] = dolBuildUrl(DOL_URL_ROOT."/quickmemo/admin/setup.php");
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;


	//  $head[$h][0] = dolBuildUrl(DOL_URL_ROOT.'/admin/extrafields.php', array('elementtype' => 'quickmemo_memo'));
	//  $head[$h][1] = $langs->trans("ExtraFields");
	//  $nbExtrafields = (isset($extrafields->attributes['memo']['label']) && is_countable($extrafields->attributes['memo']['label'])) ? count($extrafields->attributes['memo']['label']) : 0;
	//  if ($nbExtrafields > 0) {
	//      $head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	//  }
	//  $head[$h][2] = 'memo_extrafields';
	//  $h++;

	/*
	$head[$h][0] = dolBuildUrl(dol_buildpath("/quickmemo/admin/myobjectline_extrafields.php", 1));
	$head[$h][1] = $langs->trans("ExtraFieldsLines");
	$nbExtrafields = (isset($extrafields->attributes['myobjectline']['label']) && is_countable($extrafields->attributes['myobjectline']['label'])) ? count($extrafields->attributes['myobject']['label']) : 0;
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbExtrafields . '</span>';
	}
	$head[$h][2] = 'myobject_extrafieldsline';
	$h++;
	*/


	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@quickmemo:/quickmemo/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@quickmemo:/quickmemo/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'quickmemo@quickmemo');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'quickmemo@quickmemo', 'remove');

	return $head;
}

/**
 * Check that current user can read the object a memo is attached to.
 * Writing a memo does not change the object, so read access on the object is enough.
 *
 * @param string $element_type    Element type of the object ('' for global contexts like home page)
 * @param int    $element_id      Id of the object
 * @param bool   $moduleLevelOnly True to check only the permission on the module (memo shared on all objects of this type)
 * @return bool
 */
function quickMemoInterfaceCanAccessElement($element_type, $element_id, $moduleLevelOnly = false)
{
	global $user, $hookmanager;

	if (empty($element_type)) {
		return true;
	}

	$parameters = array('element_type' => $element_type, 'element_id' => $element_id, 'moduleLevelOnly' => $moduleLevelOnly);
	$hookobject = null;
	$action = '';
	$reshook = $hookmanager->executeHooks('quickMemoCheckElementAccess', $parameters, $hookobject, $action);
	if ($reshook > 0) {
		return true;
	} elseif ($reshook < 0) {
		return false;
	}

	$element_type = quickMemoInterfaceResolveElementType($element_type);
	$object = fetchObjectByElement($moduleLevelOnly ? 0 : (int) $element_id, $element_type, '', $moduleLevelOnly ? 0 : 1);
	if (!is_object($object) || empty($object->element)) {
		return false;
	}
	if (!$moduleLevelOnly && empty($object->id)) {
		return false;
	}

	// Property module is set by fetchObjectByElement() only when object is fetched
	$module = !empty($object->module) ? $object->module : getElementProperties($element_type)['module'];
	if (empty($module)) {
		return false;
	}

	$feature2 = ($module != $object->element ? $object->element : '');
	if ($feature2 && !$user->hasRight($module, $object->element, 'read')) {
		$feature2 = '';
	}

	// Force mode 'read', else restrictedArea() guesses the mode from our 'action' (create, delete...) and checks write permission on the object
	return (bool) restrictedArea($user, $module, $moduleLevelOnly ? 0 : $object, $object->table_element, $feature2, 'fk_soc', 'rowid', 0, 1, 'read');
}

/**
 * Element type usable by fetchObjectByElement() for the element type stored on memos ($object->element).
 * For an object of an external module (or some core objects like hrm evaluation), the element alone does
 * not lead to its class: the module is then searched among the enabled modules having the class file
 * <module>/class/<element>.class.php, and 'element@module' is returned. The stored element type is not
 * changed, so existing memos keep matching their object.
 *
 * @param string $element_type Element type stored on the memo (ex: 'propal', 'emailaccount')
 * @return string              Ex: 'propal', 'emailaccount@crmclientconnector'
 */
function quickMemoInterfaceResolveElementType($element_type)
{
	global $conf;
	static $cache = array();

	$element_type = (string) $element_type;
	if ($element_type === '' || strpos($element_type, '@') !== false || !preg_match('/^[a-z0-9_]+$/i', $element_type)) {
		return $element_type;
	}
	if (isset($cache[$element_type])) {
		return $cache[$element_type];
	}

	$cache[$element_type] = $element_type;
	$properties = getElementProperties($element_type);
	if (file_exists(dol_buildpath('/'.$properties['classpath'].'/'.$properties['classfile'].'.class.php', 0))) {
		return $element_type;
	}
	foreach (array_keys($conf->modules) as $module) {
		$module = strtolower((string) $module);
		if (preg_match('/^[a-z0-9_]+$/', $module) && file_exists(dol_buildpath('/'.$module.'/class/'.strtolower($element_type).'.class.php', 0))) {
			$cache[$element_type] = $element_type.'@'.$module;
			break;
		}
	}

	return $cache[$element_type];
}
