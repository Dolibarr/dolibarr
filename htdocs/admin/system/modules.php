<?php
/* Copyright (C) 2005-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 *  \file       htdocs/admin/system/modules.php
 *  \brief      File to list all Dolibarr modules
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

if (empty($user->admin)) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin"));

$optioncss = GETPOST('optioncss', 'alpha');
$contextpage		= GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'moduleoverview';

$search_name		= GETPOST("search_name", 'alpha');
$search_id = GETPOST("search_id", 'alpha');
$search_version = GETPOST("search_version", 'alpha');
$search_permission = GETPOST("search_permission", 'alpha');

$sortfield			= GETPOST('sortfield', 'aZ09comma');
$sortorder			= GETPOST('sortorder', 'aZ09comma');

if (!$sortfield) {
	$sortfield = "id";
}
if (!$sortorder) {
	$sortorder = "asc";
}

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('moduleoverview'));
$form = new Form($db);
$object = new stdClass();

// Definition of fields for lists
$arrayfields = array(
	'name' => array('label' => $langs->trans("Modules"), 'checked' => 1, 'position' => 10),
	'version' => array('label' => $langs->trans("Version"), 'checked' => 1, 'position' => 20),
	'id' => array('label' => $langs->trans("IdModule"), 'checked' => 1, 'position' => 30),
	'module_position' => array('label' => $langs->trans("Position"), 'checked' => 1, 'position' => 35),
	'permission' => array('label' => $langs->trans("IdPermissions"), 'checked' => 1, 'position' => 40)
);

$arrayfields = dol_sort_array($arrayfields, 'position');
'@phan-var-force array<string,array{label:string,checked:int<0,1>,position:int}> $arrayfields';

$param = '';
$info_admin = '';

/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';
}


// Load list of modules
$moduleList = array();
$modules = array();
$modules_files = array();
$modules_fullpath = array();
$modulesdir = dolGetModulesDirs();
$rights_ids = array();
$arrayofpermissions = array();

foreach ($modulesdir as $dir) {
	$handle = @opendir(dol_osencode($dir));
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
				$modName = substr($file, 0, dol_strlen($file) - 10);

				if ($modName) {
					//print 'xx'.$dir.$file.'<br>';
					if (in_array($file, $modules_files)) {
						// File duplicate
						print "Warning duplicate file found : ".$file." (Found ".$dir.$file.", already found ".$modules_fullpath[$file].")<br>";
					} else {
						// File to load
						$res = include_once $dir.$file;
						if (class_exists($modName)) {
							try {
								$objMod = new $modName($db);
								'@phan-var-force DolibarrModules $objMod';

								$modules[$objMod->numero] = $objMod;
								$modules_files[$objMod->numero] = $file;
								$modules_fullpath[$file] = $dir.$file;
							} catch (Exception $e) {
								dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
							}
						} else {
							$info_admin .= info_admin("Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)", 0, 0, '1', 'warning');
						}
					}
				}
			}
		}
		closedir($handle);
	}
}
'@phan-var-force array<string,DolibarrModules> $modules';

// create pre-filtered list for modules
foreach ($modules as $key => $module) {
	$newModule = new stdClass();

	$newModule->name = $module->getName();
	$newModule->version = $module->getVersion();
	$newModule->id = $key;
	$newModule->module_position = $module->getModulePosition();

	$alt = $module->name.' - '.$modules_files[$key];

	if (!empty($module->picto)) {
		if (preg_match('/^\//', $module->picto)) {
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			$newModule->picto = img_picto($alt, $module->picto, 'width="14px"', 1);
		} else {
			$newModule->picto = img_object($alt, $module->picto, 'width="14px"');
		}
	} else {
		$newModule->picto = img_object($alt, 'generic', 'width="14px"');
	}

	$permission = array();
	if ($module->rights) {
		foreach ($module->rights as $rights) {
			if (empty($rights[0])) {
				continue;
			}
			$arrayofpermissions[$rights[0]] = array('label' => 'user->hasRight(\''.$module->rights_class.'\', \''.$rights[4].'\''.(empty($rights[5]) ? '' : ', \''.$rights[5].'\'').')');
			$permission[] = $rights[0];

			array_push($rights_ids, $rights[0]);
		}
	}

	$newModule->permission = $permission;

	// pre-filter list
	if (!empty($search_name) && !stristr($newModule->name, $search_name)) {
		continue;
	}
	if (!empty($search_version) && !stristr($newModule->version, $search_version)) {
		continue;
	}
	if (!empty($search_id) && !stristr($newModule->id, $search_id)) {
		continue;
	}

	if (!empty($search_permission)) {
		$found = false;

		foreach ($newModule->permission as $permission) {
			if (stristr($permission, $search_permission)) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			continue;
		}
	}

	$moduleList[] = $newModule;
}



/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_modules');
print $info_admin;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($langs->trans("AvailableModules"), empty($page) ? 0 : $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', -1, '', 'title_setup', 0, '', '', 0, 1, 1);

print '<span class="opacitymedium">'.$langs->trans("ToActivateModule").'</span>';
print '<br>';
print '<br>';

$mode = '';
$arrayofmassactions = array();

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

$moreforfilter = '';

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Lines with input filters
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
if ($arrayfields['name']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_name" size="8" value="'.dol_escape_htmltag($search_name).'">';
	print '</td>';
}
if ($arrayfields['version']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_version" size="6" value="'.dol_escape_htmltag($search_version).'">';
	print '</td>';
}
if ($arrayfields['id']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_id" size="6" value="'.dol_escape_htmltag($search_id).'">';
	print '</td>';
}
if ($arrayfields['permission']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_permission" size="8" value="'.dol_escape_htmltag($search_permission).'">';
	print '</td>';
}
if ($arrayfields['module_position']['checked']) {
	print '<td class="liste_titre left">';
	print '</td>';
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>';

print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn');
}
if ($arrayfields['name']['checked']) {
	print_liste_field_titre($arrayfields['name']['label'], $_SERVER["PHP_SELF"], "name", "", "", "", $sortfield, $sortorder);
}
if ($arrayfields['version']['checked']) {
	print_liste_field_titre($arrayfields['version']['label'], $_SERVER["PHP_SELF"], "version", "", "", "", $sortfield, $sortorder);
}
if ($arrayfields['id']['checked']) {
	print_liste_field_titre($arrayfields['id']['label'], $_SERVER["PHP_SELF"], "id", "", "", "", $sortfield, $sortorder, 'nowraponall ');
}
if ($arrayfields['permission']['checked']) {
	print_liste_field_titre($arrayfields['permission']['label'], $_SERVER["PHP_SELF"], "permission", "", "", "", $sortfield, $sortorder);
}
if ($arrayfields['module_position']['checked']) {
	print_liste_field_titre($arrayfields['module_position']['label'], $_SERVER["PHP_SELF"], "module_position", "", "", "", $sortfield, $sortorder);
}

// Fields from hook
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
}
print '</tr>';

// sort list
if ($sortfield == "name" && $sortorder == "asc") {
	usort(
		$moduleList,
		/** @return int */
		function (stdClass $a, stdClass $b) {
			return strcasecmp($a->name, $b->name);
		}
	);
} elseif ($sortfield == "name" && $sortorder == "desc") {
	usort(
		$moduleList,
		/** @return int */
		static function (stdClass $a, stdClass $b) {
			return strcasecmp($b->name, $a->name);
		}
	);
} elseif ($sortfield == "version" && $sortorder == "asc") {
	usort(
		$moduleList,
		/** @return int */
		static function (stdClass $a, stdClass $b) {
			return strcasecmp($a->version, $b->version);
		}
	);
} elseif ($sortfield == "version" && $sortorder == "desc") {
	usort(
		$moduleList,
		/** @return int */
		static function (stdClass $a, stdClass $b) {
			return strcasecmp($b->version, $a->version);
		}
	);
} elseif ($sortfield == "id" && $sortorder == "asc") {
	usort($moduleList, "compareIdAsc");
} elseif ($sortfield == "id" && $sortorder == "desc") {
	usort($moduleList, "compareIdDesc");
} elseif ($sortfield == "permission" && $sortorder == "asc") {
	usort($moduleList, "comparePermissionIdsAsc");
} elseif ($sortfield == "permission" && $sortorder == "desc") {
	usort($moduleList, "comparePermissionIdsDesc");
} else {
	$moduleList = dol_sort_array($moduleList, 'module_position');
}

foreach ($moduleList as $module) {
	print '<tr class="oddeven">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}

	if ($arrayfields['name']['checked']) {
		print '<td width="300" class="nowrap">';
		print $module->picto;
		print ' '.$module->name;
		print "</td>";
	}

	if ($arrayfields['version']['checked']) {
		print '<td class="nowraponall">'.$module->version.'</td>';
	}

	if ($arrayfields['id']['checked']) {
		print '<td class="center">'.$module->id.'</td>';
	}

	if ($arrayfields['permission']['checked']) {
		$idperms = '';

		foreach ($module->permission as $permission) {
			$translationKey = "Permission".$permission;
			$labelpermission = $langs->trans($translationKey);
			$labelpermission .= ' : '.$arrayofpermissions[$permission]['label'];
			$idperms .= ($idperms ? ", " : "").'<span title="'.$labelpermission.'">'.$permission.'</a>';

			if (getDolGlobalString('MAIN_SHOW_PERMISSION')) {
				if (empty($langs->tab_translate[$translationKey])) {
					$tooltip = 'Missing translation (key '.$translationKey.' not found in admin.lang)';
					$idperms .= ' <img src="../../theme/eldy/img/warning.png" alt="Warning" title="'.$tooltip.'">';
				}
			}
		}

		print '<td><span class="opacitymedium">'.($idperms ? $idperms : "&nbsp;").'</span></td>';
	}

	if ($arrayfields['module_position']['checked']) {
		print '<td class="center">'.$module->module_position.'</td>';
	}

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}
	print '</tr>';
}

print '</table>';
print '</div>';
print '</form>';
print '<br>';

sort($rights_ids);
$old = '';

foreach ($rights_ids as $right_id) {
	if ($old == $right_id) {
		print "Warning duplicate id on permission : ".$right_id."<br>";
	}

	$old = $right_id;
}

// End of page
llxFooter();
$db->close();


/**
 * Compare two modules by their ID for a ascending order
 *
 * @param	stdClass 	$a		First module
 * @param	stdClass 	$b		Second module
 * @return	int					Compare result (-1, 0, 1)
 */
function compareIdAsc(stdClass $a, stdClass $b)
{
	if ((int) $a->id == (int) $b->id) {
		return 0;
	}

	return ((int) $a->id < (int) $b->id) ? -1 : 1;
}

/**
 * Compare two modules by their ID for a descending order
 *
 * @param	stdClass 	$a		First module
 * @param	stdClass 	$b		Second module
 * @return	int					Compare result (-1, 0, 1)
 */
function compareIdDesc(stdClass $a, stdClass $b)
{
	if ((int) $a->id == (int) $b->id) {
		return 0;
	}

	return ((int) $b->id < (int) $a->id) ? -1 : 1;
}

/**
 * Compare two modules by their ID for a ascending order
 *
 * @param	stdClass 	$a		First module
 * @param	stdClass 	$b		Second module
 * @return	int					Compare result (-1, 0, 1)
 */
function comparePermissionIdsAsc(stdClass $a, stdClass $b)
{
	if (empty($a->permission) && empty($b->permission)) {
		return compareIdAsc($a, $b);
	}

	if (empty($a->permission)) {
		return 1;
	}
	if (empty($b->permission)) {
		return -1;
	}

	if ($a->permission[0] == $b->permission[0]) {
		return 0;
	}

	return $a->permission[0] < $b->permission[0] ? -1 : 1;
}

/**
 * Compare two modules by their permissions for a descending order
 *
 * @param	stdClass 	$a		First module
 * @param	stdClass 	$b		Second module
 * @return	int					Compare result (-1, 0, 1)
 */
function comparePermissionIdsDesc(stdClass $a, stdClass $b)
{
	if (empty($a->permission) && empty($b->permission)) {
		return compareIdDesc($a, $b);
	}

	if (empty($a->permission)) {
		return -1;
	}
	if (empty($b->permission)) {
		return 1;
	}

	if ($a->permission[0] == $b->permission[0]) {
		return 0;
	}

	return $b->permission[0] < $a->permission[0] ? -1 : 1;
}
