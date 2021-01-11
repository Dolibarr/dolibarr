<?php
/* Copyright (C) 2005-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin"));

$optioncss = GETPOST('optioncss', 'alpha');
$contextpage		= GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'moduleoverview';

$search_name		= GETPOST("search_name", 'alpha');
$search_id = GETPOST("search_id", 'alpha');
$search_version = GETPOST("search_version", 'alpha');
$search_permission = GETPOST("search_permission", 'alpha');

$sortfield			= GETPOST("sortfield", 'alpha');
$sortorder			= GETPOST("sortorder", 'alpha');

if (!$sortfield) $sortfield = "id";
if (!$sortorder) $sortorder = "asc";

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$hookmanager->initHooks(array('moduleoverview'));
$form = new Form($db);
$object = new stdClass();

// Definition of fields for lists
$arrayfields = array(
	'name'=>array('label'=>$langs->trans("Modules"), 'checked'=>1, 'position'=>10),
	'version'=>array('label'=>$langs->trans("Version"), 'checked'=>1, 'position'=>20),
	'id'=>array('label'=>$langs->trans("IdModule"), 'checked'=>1, 'position'=>30),
	'module_position'=>array('label'=>$langs->trans("Position"), 'checked'=>1, 'position'=>35),
	'permission'=>array('label'=>$langs->trans("IdPermissions"), 'checked'=>1, 'position'=>40)
);

$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

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
					}
					else {
						// File to load
						$res = include_once $dir.$file;
						if (class_exists($modName)) {
							try {
								$objMod = new $modName($db);

								$modules[$objMod->numero] = $objMod;
								$modules_files[$objMod->numero] = $file;
								$modules_fullpath[$file] = $dir.$file;
							}
							catch (Exception $e) {
								dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
							}
						}
						else {
							print "Warning bad descriptor file : ".$dir.$file." (Class ".$modName." not found into file)<br>";
						}
					}
				}
			}
		}
		closedir($handle);
	}
}

// create pre-filtered list for modules
foreach ($modules as $key=>$module) {
	$newModule = new stdClass();

	$newModule->name = $module->getName();
	$newModule->version = $module->getVersion();
	$newModule->id = $key;
	$newModule->module_position = $module->module_position;

	$alt = $module->name.' - '.$modules_files[$key];

	if (!empty($module->picto)) {
		if (preg_match('/^\//', $module->picto)) $newModule->picto = img_picto($alt, $module->picto, 'width="14px"', 1);
		else $newModule->picto = img_object($alt, $module->picto, 'width="14px"');
	}
	else {
		$newModule->picto = img_object($alt, 'generic', 'width="14px"');
	}

	$permission = array();
	if ($module->rights) {
		foreach ($module->rights as $rights) {
			if (empty($rights[0])) {
				continue;
			}

			$permission[] = $rights[0];

			array_push($rights_ids, $rights[0]);
		}
	}

	$newModule->permission = $permission;

	// pre-filter list
	if ($search_name && !stristr($newModule->name, $search_name))			continue;
	if ($search_version && !stristr($newModule->version, $search_version))	continue;
	if ($search_id && !stristr($newModule->id, $search_id))					continue;

	if ($search_permission) {
		$found = false;

		foreach ($newModule->permission as $permission) {
			if (stristr($permission, $search_permission)) {
				$found = true;
				break;
			}
		}

		if (!$found) continue;
	}

	$moduleList[] = $newModule;
}



/*
 * View
 */

llxHeader();

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($langs->trans("AvailableModules"), $page, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, $massactionbutton, -1, '', 'title_setup', 0, '', '', 0, 1, 1);

print '<span class="opacitymedium">'.$langs->trans("ToActivateModule").'</span>';
print '<br>';
print '<br>';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Lines with input filters
print '<tr class="liste_titre_filter">';

if ($arrayfields['name']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_name" size="8" value="'.$search_name.'">';
	print '</td>';
}
if ($arrayfields['version']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_version" size="8" value="'.$search_version.'">';
	print '</td>';
}
if ($arrayfields['id']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_id" size="8" value="'.$search_id.'">';
	print '</td>';
}
if ($arrayfields['module_position']['checked']) {
	print '<td class="liste_titre left">';
	print '</td>';
}
if ($arrayfields['permission']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_permission" size="8" value="'.$search_permission.'">';
	print '</td>';
}

print '<td class="liste_titre center maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';

print '</tr>';

print '<tr class="liste_titre">';

if ($arrayfields['name']['checked']) {
	print_liste_field_titre($arrayfields['name']['label'], $_SERVER["PHP_SELF"], "name", "", "", "", $sortfield, $sortorder);
}
if ($arrayfields['version']['checked']) {
	print_liste_field_titre($arrayfields['version']['label'], $_SERVER["PHP_SELF"], "version", "", "", "", $sortfield, $sortorder);
}
if ($arrayfields['id']['checked']) {
	print_liste_field_titre($arrayfields['id']['label'], $_SERVER["PHP_SELF"], "id", "", "", "", $sortfield, $sortorder);
}
if ($arrayfields['module_position']['checked']) {
	print_liste_field_titre($arrayfields['module_position']['label'], $_SERVER["PHP_SELF"], "module_position", "", "", "", $sortfield, $sortorder);
}
if ($arrayfields['permission']['checked']) {
	print_liste_field_titre($arrayfields['permission']['label'], $_SERVER["PHP_SELF"], "permission", "", "", "", $sortfield, $sortorder);
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print '</tr>';

// sort list
if ($sortfield == "name" && $sortorder == "asc") usort($moduleList, function (stdClass $a, stdClass $b) {
	return strcasecmp($a->name, $b->name); });
if ($sortfield == "name" && $sortorder == "desc") usort($moduleList, function (stdClass $a, stdClass $b) {
	return strcasecmp($b->name, $a->name); });
if ($sortfield == "version" && $sortorder == "asc") usort($moduleList, function (stdClass $a, stdClass $b) {
	return strcasecmp($a->version, $b->version); });
if ($sortfield == "version" && $sortorder == "desc") usort($moduleList, function (stdClass $a, stdClass $b) {
	return strcasecmp($b->version, $a->version); });
if ($sortfield == "id" && $sortorder == "asc") usort($moduleList, "compareIdAsc");
if ($sortfield == "id" && $sortorder == "desc") usort($moduleList, "compareIdDesc");
if ($sortfield == "permission" && $sortorder == "asc") usort($moduleList, "comparePermissionIdsAsc");
if ($sortfield == "permission" && $sortorder == "desc") usort($moduleList, "comparePermissionIdsDesc");

$moduleList = dol_sort_array($moduleList, 'module_position');

foreach ($moduleList as $module) {
	print '<tr class="oddeven">';

	if ($arrayfields['name']['checked']) {
		print '<td width="300" class="nowrap">';
		print $module->picto;
		print ' '.$module->name;
		print "</td>";
	}

	if ($arrayfields['version']['checked']) {
		print '<td>'.$module->version.'</td>';
	}

	if ($arrayfields['id']['checked']) {
		print '<td class="center">'.$module->id.'</td>';
	}

	if ($arrayfields['module_position']['checked']) {
		print '<td class="center">'.$module->module_position.'</td>';
	}

	if ($arrayfields['permission']['checked']) {
		$idperms = '';

		foreach ($module->permission as $permission) {
			$idperms .= ($idperms ? ", " : "").$permission;
			$translationKey = "Permission".$permission;

			if (!empty($conf->global->MAIN_SHOW_PERMISSION)) {
				if (empty($langs->tab_translate[$translationKey])) {
					$tooltip = 'Missing translation (key '.$translationkey.' not found in admin.lang)';
					$idperms .= ' <img src="../../theme/eldy/img/warning.png" alt="Warning" title="'.$tooltip.'">';
				}
			}
		}

		print '<td>'.($idperms ? $idperms : "&nbsp;").'</td>';
	}

	print '<td></td>';
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
	if ($a->id == $b->id) return 0;

	return $a->id > $b->id ? -1 : 1;
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
	if ($a->id == $b->id) return 0;

	return $b->id > $a->id ? -1 : 1;
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
	if (empty($a->permission) && empty($b->permission)) return compareIdAsc($a, $b);

	if (empty($a->permission)) return 1;
	if (empty($b->permission)) return -1;

	if ($a->permission[0] == $b->permission[0]) return 0;

	return $a->permission[0] > $b->permission[0] ? -1 : 1;
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
	if (empty($a->permission) && empty($b->permission)) return compareIdDesc($a, $b);

	if (empty($a->permission)) return -1;
	if (empty($b->permission)) return 1;

	if ($a->permission[0] == $b->permission[0]) return 0;

	return $a->permission[0] > $b->permission[0] ? 1 : -1;
}
