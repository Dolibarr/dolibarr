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

$optioncss			= GETPOST('optioncss', 'alpha');
$contextpage		= GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'moduleoverview';

$search_name		= GETPOST("search_name", 'alpha');
$search_id			= GETPOST("search_id", 'alpha');
$search_version		= GETPOST("search_version", 'alpha');
$search_permission	= GETPOST("search_permission", 'alpha');
$search_text		= GETPOST("search_text", 'alpha');

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
	'permission'=>array('label'=>$langs->trans("IdPermissions"), 'checked'=>1, 'position'=>40),
	'text'=>array('label'=>$langs->trans("UntranslatedPermissionText"), 'checked'=>0, 'position'=>50)
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

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
if ($search_name)			$param .= '&search_name='.$search_name;
if ($search_version)		$param .= '&search_version='.$search_version;
if ($search_id)				$param .= '&search_id='.$search_id;
if ($search_permission)		$param .= '&search_permission='.$search_permission;
if ($search_text)			$param .= '&search_text='.$search_text;
if ($optioncss != '') 		$param .= '&optioncss='.$optioncss;

/*
 * View
 */

llxHeader();

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

print_barre_liste($langs->trans("AvailableModules"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, -1, '', 'title_setup', 0, '', '', 0, 1, 1);

print '<span class="opacitymedium">'.$langs->trans("ToActivateModule").'</span>';
print '<br>';
print '<br>';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields


// create pre-filtered list for modules
$module_list = array();

foreach ($modules as $key=>$module) {
	$newModule = new stdClass();

	$newModule->name			= $module->getName();
	$newModule->version			= $module->getVersion();
	$newModule->id				= $key;
	$newModule->picto			= GetModulePicto($module, $module->name.' - '.$modules_files[$key]);
	$newModule->permission_id	= '';
	$newModule->permission_text	= '';
	
	if(!$arrayfields['text']['checked']) {
		if ($module->rights) {
			foreach ($module->rights as $rights) {
				if (empty($rights[0])) {
					continue;
				}

				array_push($rights_ids, $rights[0]);

				if(!empty($newModule->permission_id)) $newModule->permission_id .= ",";
				$newModule->permission_id .= $rights[0];

			}
		}

		$module_list[] = $newModule;

	} else {
		if ($module->rights) {
			foreach ($module->rights as $rights) {
				if (empty($rights[0])) {
					continue;
				}
	
				$newModule->permission_id	= $rights[0];
				$newModule->permission_text	= $rights[1];
	
				array_push($rights_ids, $rights[0]);
	
				$module_list[] = $newModule;
	
				$newModule = new stdClass();
				$newModule->name	= $module->getName();
				$newModule->version	= $module->getVersion();
				$newModule->id		= $key;
				$newModule->picto	= GetModulePicto($module, $module->name.' - '.$modules_files[$key]);
			}
		} else {
			$module_list[] = $newModule;
		}
	}
}

// pre-filter list
$filtered_module_list = array();
foreach($module_list as $module) {
	if ($search_name && !stristr($module->name, $search_name))						continue;
	if ($search_version && !stristr($module->version, $search_version))				continue;
	if ($search_id && !stristr($module->id, $search_id))							continue;
	if ($search_permission && !stristr($module->permission_id, $search_permission)) continue;
	if ($search_text && !stristr($module->permission_text, $search_text))			continue;

	$filtered_module_list[] = $module;
}

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
if ($arrayfields['permission']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_permission" size="8" value="'.$search_permission.'">';
	print '</td>';
}
if ($arrayfields['text']['checked']) {
	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_text" size="8" value="'.$search_text.'">';
	print '</td>';
}
print '<td class="liste_titre center maxwidthsearch">';
$searchpicto = $form->showFilterButtons();
print $searchpicto;
print '</td>';

print '</tr>';

print '<tr class="liste_titre">';

if ($arrayfields['name']['checked']) {
	print_liste_field_titre($arrayfields['name']['label'], $_SERVER["PHP_SELF"], "name", "", $param, "", $sortfield, $sortorder);
}
if ($arrayfields['version']['checked']) {
	print_liste_field_titre($arrayfields['version']['label'], $_SERVER["PHP_SELF"], "version", "", $param, "", $sortfield, $sortorder);
}
if ($arrayfields['id']['checked']) {
	print_liste_field_titre($arrayfields['id']['label'], $_SERVER["PHP_SELF"], "id", "", $param, "", $sortfield, $sortorder);
}
if ($arrayfields['permission']['checked']) {
	print_liste_field_titre($arrayfields['permission']['label'], $_SERVER["PHP_SELF"], "permission", "", $param, "", $sortfield, $sortorder);
}
if ($arrayfields['text']['checked']) {
	print_liste_field_titre($arrayfields['text']['label'], $_SERVER["PHP_SELF"], "text", "", $param, "", $sortfield, $sortorder);
}

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print '</tr>';

// sort list
if ($sortfield == "name" && $sortorder == "asc") usort($filtered_module_list, function (stdClass $a, stdClass $b) { return strcasecmp($a->name, $b->name); });
if ($sortfield == "name" && $sortorder == "desc") usort($filtered_module_list, function (stdClass $a, stdClass $b) { return strcasecmp($b->name, $a->name); });
if ($sortfield == "version" && $sortorder == "asc") usort($filtered_module_list, function (stdClass $a, stdClass $b) { return strcasecmp($a->version, $b->version); });
if ($sortfield == "version" && $sortorder == "desc") usort($filtered_module_list, function (stdClass $a, stdClass $b) {	return strcasecmp($b->version, $a->version); });
if ($sortfield == "id" && $sortorder == "asc") usort($filtered_module_list, function (stdClass $a, stdClass $b) {	return $a->id > $b->id; });
if ($sortfield == "id" && $sortorder == "desc") usort($filtered_module_list, function (stdClass $a, stdClass $b) {	return $b->id > $a->id; });
if ($sortfield == "permission" && $sortorder == "asc") usort($filtered_module_list, "comparePermissionIdAsc");
if ($sortfield == "permission" && $sortorder == "desc") usort($filtered_module_list, "comparePermissionIdDesc");
if ($sortfield == "text" && $sortorder == "asc") usort($filtered_module_list, function (stdClass $a, stdClass $b) { return strcasecmp($a->permission_text, $b->permission_text); });
if ($sortfield == "text" && $sortorder == "desc") usort($filtered_module_list, function (stdClass $a, stdClass $b) { return strcasecmp($b->permission_text, $a->permission_text); });

foreach ($filtered_module_list as $module) {
	print '<tr class="oddeven">';

	if ($arrayfields['name']['checked']) 		print '<td width="300" class="nowrap">'.$module->picto.' '.$module->name.'</td>';
	if ($arrayfields['version']['checked']) 	print '<td>'.$module->version.'</td>';
	if ($arrayfields['id']['checked']) 			print '<td class="center">'.$module->id.'</td>';
	if ($arrayfields['permission']['checked']) 	print '<td>'.$module->permission_id.'</td>';
	if ($arrayfields['text']['checked'])		print '<td>'.$module->permission_text.'</td>';

	print '<td></td>';	// gap for the right table buttons
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
 * Return the picture of a given module
 *
 * @param object	$module		A module that should have a picture
 * @param string	$alt		A alternative name for the picture (is typical shown when the browser can't render the picture)
 * @return string				A HTML string that contains a picture
 */
function getModulePicto($module, $alt)
{
	if (empty($module->picto)) {
		return img_object($alt, 'generic', 'width="14px"');
	}

	if (preg_match('/^\//', $module->picto)) {
		return img_picto($alt, $module->picto, 'width="14px"', 1);
	}

	return img_object($alt, $module->picto, 'width="14px"');
}

/**
 * Compare the list of permissions of two objects in a ascending order
 *
 * @param object	$a		left object with a list of permissions
 * @param object	$b		right object with a list of permissions
 * @return integer			-1 = lower, 0 = same, 1 = higher
 */
function comparePermissionIdAsc($a, $b)
{
	$lista = array_map(function($stringa) { return (int)$stringa; }, explode(", ", $a->permission_id));
	$listb = array_map(function($stringb) { return (int)$stringb; }, explode(", ", $b->permission_id));

	return max($lista) > max($listb);
}

/**
 * Compare the list of permissions of two objects in a descending order
 *
 * @param object	$a		left object with a list of permissions
 * @param object	$b		right object with a list of permissions
 * @return integer			-1 = lower, 0 = same, 1 = higher
 */
function comparePermissionIdDesc($a, $b)
{
	$lista = array_map(function($stringa) { return (int)$stringa; }, explode(", ", $a->permission_id));
	$listb = array_map(function($stringb) { return (int)$stringb; }, explode(", ", $b->permission_id));

	return max($lista) < max($listb);
}
