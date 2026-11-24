<?php
/* Copyright (C) 2026		John BOTELLA
 * Copyright (C) 2025-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2026		Nick Fragoulis
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

/**
 * SQL filter on memos (alias t) keeping only memos the current user can read.
 * Same rules as quickMemoInterfaceCanAccessElement() (restrictedArea()), as SQL to avoid one check per memo.
 *
 * @return string SQL condition starting with ' AND '
 */
function quickMemoGetMemoAccessSqlFilter()
{
	global $db;

	require_once __DIR__.'/../class/memo.class.php';

	// Memos not linked to an object (home, list or index page): context must be available to user
	$contexts = array();
	foreach (Memo::getAvailableMemoContext() as $context) {
		$contexts[] = "'".$db->escape($context)."'";
	}
	$conditions = array('t.status = '.Memo::STATUS_TPL);
	if (!empty($contexts)) {
		$conditions[] = "(COALESCE(t.element_type, '') = '' AND t.context_tab IN (".$db->sanitize(implode(',', $contexts), 1)."))";
	}

	// Memos linked to an object: read permission on the module, then on the object
	$sql = "SELECT DISTINCT element_type FROM ".$db->prefix()."quickmemo_memo";
	$sql .= " WHERE element_type IS NOT NULL AND element_type <> ''";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$elementType = (string) $obj->element_type;
			if (!quickMemoInterfaceCanAccessElement($elementType, 0, true)) {
				continue;
			}
			$condition = "t.element_type = '".$db->escape($elementType)."'";
			$objectCondition = quickMemoGetElementAccessSqlCondition($elementType);
			if ($objectCondition !== '') {
				// A memo shared on element is displayed on all objects of the type, module permission is enough
				$condition .= " AND (t.shared_on_element = 1 OR ".$objectCondition.")";
			}
			$conditions[] = "(".$condition.")";
		}
		$db->free($resql);
	} else {
		dol_syslog(__FUNCTION__.' '.$db->lasterror(), LOG_ERR);
	}

	return " AND (".implode(' OR ', $conditions).")";
}

/**
 * SQL condition on t.fk_element restricting memos of an element type to the objects current user can read.
 * Same rules as checkUserAccessToObject() for an internal user restricted to his third parties, an external user,
 * projects, hierarchy (holiday, expense report, evaluation), bookmarks and private contacts.
 *
 * @param string $element_type Element type stored on the memo
 * @return string              SQL condition, '' when module permission is enough
 */
function quickMemoGetElementAccessSqlCondition($element_type)
{
	global $db, $user;

	$object = fetchObjectByElement(0, quickMemoInterfaceResolveElementType($element_type), '', 0);
	if (!is_object($object) || empty($object->element) || empty($object->table_element)) {
		return '0 = 1';
	}
	$module = !empty($object->module) ? $object->module : getElementProperties($element_type)['module'];
	$table = $db->prefix().$db->sanitize($object->table_element);
	$exists = "EXISTS (SELECT 1 FROM ".$table." as dbt";

	$feature = $module;
	if ($module == 'societe' && $object->element == 'contact') {
		$feature = 'contact';
	} elseif (in_array($module, array('projet', 'project')) && $object->element == 'project_task') {
		$feature = 'project_task';
	}

	$isExternal = ($user->socid > 0);
	$isRestricted = (!$isExternal && isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir'));
	$salesRepIds = array((int) $user->id);
	if ($isRestricted && getDolGlobalInt('MAIN_SEE_SUBORDINATES')) {
		$salesRepIds = array_merge($salesRepIds, (array) $user->getAllChildIds());
	}
	$salesRepIdList = implode(',', array_map('intval', $salesRepIds));

	// Same lists as checkUserAccessToObject()
	$checkTables = array('asset', 'loan', 'localtax', 'paiement', 'paiementfourn', 'pos_cash_fence', 'tva', 'workstation_workstation', 'chargesociales');
	$noCheckTables = array('hrm_job', 'hrm_job_user', 'hrm_skill');
	$check = array('adherent', 'banque', 'bom', 'don', 'mrp', 'user', 'usergroup', 'payment', 'payment_supplier', 'payment_sc', 'payment_vat', 'payment_donation', 'product', 'produit', 'service', 'produit|service', 'categorie', 'resource', 'expensereport', 'holiday', 'salaries', 'website', 'recruitment', 'chargesociales', 'knowledgemanagement', 'stock', 'stockmovement', 'workstation');
	$checkParentSoc = array('agenda', 'contact', 'contrat', 'ticket', 'stocktransfer');
	$noCheck = array('barcode', 'webhook');

	$conditions = array();
	if (in_array($object->table_element, $checkTables) || in_array($object->table_element, $noCheckTables)) {
		if ($isExternal) {
			return '0 = 1';
		}
	} elseif (in_array($feature, $check) || in_array($feature, $noCheck)) {
		// Entity only, memo entity is already filtered
	} elseif ($feature == 'societe') {
		if ($isExternal) {
			$conditions[] = "t.fk_element = ".((int) $user->socid);
		} elseif ($isRestricted) {
			$conditions[] = "EXISTS (SELECT 1 FROM ".$db->prefix()."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_element AND sc.fk_user IN (".$db->sanitize($salesRepIdList)."))";
		}
	} elseif (in_array($feature, $checkParentSoc)) {
		// Allowed when the object is not linked to a third party
		if ($isExternal) {
			$conditions[] = $exists." WHERE dbt.rowid = t.fk_element AND dbt.fk_soc = ".((int) $user->socid).")";
		} elseif ($isRestricted) {
			$conditions[] = $exists." LEFT JOIN ".$db->prefix()."societe_commerciaux as sc ON sc.fk_soc = dbt.fk_soc AND sc.fk_user = ".((int) $user->id)." WHERE dbt.rowid = t.fk_element AND (dbt.fk_soc IS NULL OR sc.fk_soc IS NOT NULL))";
		}
		if ($feature == 'contact') {
			$conditions[] = "NOT ".$exists." WHERE dbt.rowid = t.fk_element AND dbt.priv = 1 AND (dbt.fk_user_creat IS NULL OR dbt.fk_user_creat <> ".((int) $user->id)."))";
		}
	} elseif (in_array($feature, array('projet', 'project', 'project_task'))) {
		if (isModEnabled('project') && !$user->hasRight('projet', 'all', 'lire')) {
			require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
			$projectStatic = new Project($db);
			$projectIds = $projectStatic->getProjectsAuthorizedForUser($user, 0, 1, 0);
			$projectIds = ($projectIds !== '' ? $projectIds : '0');
			if ($feature == 'project_task') {
				$conditions[] = $exists." WHERE dbt.rowid = t.fk_element AND dbt.fk_projet IN (".$db->sanitize($projectIds)."))";
			} else {
				$conditions[] = "t.fk_element IN (".$db->sanitize($projectIds).")";
			}
		}
	} elseif ($isExternal || $isRestricted) {
		// Default: object must be linked to a third party of the user
		$columns = $db->DDLDescTable($table, 'fk_soc');
		if (!$columns || !$db->num_rows($columns)) {
			return '0 = 1';
		}
		if ($isExternal) {
			$conditions[] = $exists." WHERE dbt.rowid = t.fk_element AND dbt.fk_soc = ".((int) $user->socid).")";
		} else {
			$conditions[] = $exists.", ".$db->prefix()."societe_commerciaux as sc WHERE dbt.rowid = t.fk_element AND sc.fk_soc = dbt.fk_soc AND sc.fk_user IN (".$db->sanitize($salesRepIdList)."))";
		}
	}

	// Hierarchy: own objects and those of subordinates
	if (in_array($feature, array('holiday', 'expensereport', 'hrm'))) {
		$childIds = (array) $user->getAllChildIds(1);
		$childIds[] = (int) $user->id;
		$childIdList = implode(',', array_map('intval', $childIds));
		if ($feature == 'holiday' && !$user->hasRight('holiday', 'readall')) {
			$conditions[] = $exists." WHERE dbt.rowid = t.fk_element AND (dbt.fk_user IN (".$db->sanitize($childIdList).") OR dbt.fk_validator IN (".$db->sanitize($childIdList).")))";
		} elseif ($feature == 'expensereport' && !$user->hasRight('expensereport', 'readall')) {
			$approver = ($user->hasRight('expensereport', 'approve') ? " OR dbt.fk_user_validator = ".((int) $user->id) : "");
			$conditions[] = $exists." WHERE dbt.rowid = t.fk_element AND (dbt.fk_user_author IN (".$db->sanitize($childIdList).")".$approver."))";
		} elseif ($feature == 'hrm' && $object->element == 'evaluation' && !$user->hasRight('hrm', 'evaluation', 'readall')) {
			$conditions[] = $exists." WHERE dbt.rowid = t.fk_element AND dbt.fk_user IN (".$db->sanitize($childIdList)."))";
		}
	}

	// Bookmarks: public or owned by user
	if ($feature == 'bookmark' && empty($user->admin)) {
		$conditions[] = $exists." WHERE dbt.rowid = t.fk_element AND (dbt.fk_user IS NULL OR dbt.fk_user <= 0 OR dbt.fk_user = ".((int) $user->id)."))";
	}

	return implode(' AND ', $conditions);
}
