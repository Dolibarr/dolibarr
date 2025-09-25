<?php
/* Copyright (C) 2005		Matthieu Valleton			<mv@seeschloss.org>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Patrick Raguin				<patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Raphaël Doursenaud			<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2021-2024	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *      \file       htdocs/categories/index.php
 *      \ingroup    category
 *      \brief      Home page of category area
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "agenda", "banks", "bills", "categories", "contracts", "interventions"));
$langs->loadLangs(array("knowledgemanagement", "members", "orders", "products", "stocks", "suppliers", "tickets", "website"));

$mode = GETPOST('mode', 'aZ09');
if (empty($mode)) {
	$mode = 'hierarchy';
}

$categstatic = new Categorie($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('categoryindex'));

$permissiontoread = $user->hasRight('categorie', 'read');
$permissiontoadd = $user->hasRight('categorie', 'write');
//$permissiontodelete = $user->hasRight('categorie', 'delete');

if (!isModEnabled("category")) {
	accessforbidden('Module Category not enabled');
}
if (!$permissiontoread) {
	accessforbidden();
}


/*
 * View
 */

$title = $langs->trans("Categories");

llxHeader('', $title, '', '', 0, 0, '', '');

// Get list of category type
$arrayofcateg = array();
foreach ($categstatic->MAP_ID as $key => $idtype) {
	$arrayofcateg[$idtype] = array();
	$arrayofcateg[$idtype]['key'] = $key;
	$arrayofcateg[$idtype]['nb'] = 0;
	$arrayofcateg[$idtype]['label'] = $langs->transnoentitiesnoconv($categstatic::$MAP_TYPE_TITLE_AREA[$key]);
	$arrayofcateg[$idtype]['labelwithoutaccent'] = dol_string_unaccent($langs->transnoentitiesnoconv($categstatic::$MAP_TYPE_TITLE_AREA[$key]));
}
$arrayofcateg = dol_sort_array($arrayofcateg, 'labelwithoutaccent', 'asc', 1, 0, 1);

// Get number of tags per category type
$sql = "SELECT type as idtype, COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."categorie GROUP BY type";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$arrayofcateg[$obj->idtype]['nb'] = $obj->nb;
	}
} else {
	dol_print_error($db);
}

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', '', '', '', -1, 0, $categstatic->picto, 0, '', '', -1, 0, 1, 1);

print '<span class="opacitymedium">';
print $langs->trans("CategorieListOfType").'...<br>';
print '</span>';

// Define $nbmodulesnotautoenabled - TODO This code is at different places
$nbmodulesnotautoenabled = count($conf->modules);
$listofmodulesautoenabled = array('agenda', 'fckeditor', 'export', 'import');
foreach ($listofmodulesautoenabled as $moduleautoenable) {
	if (in_array($moduleautoenable, $conf->modules)) {
		$nbmodulesnotautoenabled--;
	}
}

if ($user->admin && $nbmodulesnotautoenabled <= getDolGlobalInt('MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING', 1)) {	// If only minimal initial modules enabled
	$langs->load("admin");
	print info_admin($langs->trans("WarningOnlyCategoryTypesOfActivatedModules").' '.$langs->trans("YouCanEnableModulesFrom"));
}

print '<br>';

print '<div class="aaa">';

print '<table class="liste nohover centpercent noborder">';

print '<tr class="liste_titre"><td>'.$langs->trans("Type").'</td>';
print '<td class="center">'.$langs->trans("NumberOfCategories").'</td>';
print '<td></td>';
print '</tr>';


foreach ($arrayofcateg as $idtype => $val) {
	$key = $val['key'];
	$tmparray = getElementProperties($key);

	$classname = $tmparray['classname'];
	$classpath = $tmparray['classpath'];
	$classfile = $tmparray['classfile'];
	$module = $tmparray['module'];
	$fullpath = DOL_DOCUMENT_ROOT.'/'.$classpath.'/'.$classfile.'.class.php';

	if (!isModEnabled($module)) {
		continue;
	}

	print '<tr class="oddeven">';
	print '<td>';

	$tmpobject = null;
	include_once $fullpath;
	if (class_exists($classname)) {
		$tmpobject = new $classname($db);
	}
	//print "key=".$key." fullpath=".$fullpath." classname=".$classname." classpath=".$classpath." classfile=".$classfile;

	if ($tmpobject) {
		print img_picto('', $tmpobject->picto, 'class="pictofixedwidth"');
	} else {
		print img_picto('', 'generic', 'class="pictofixedwidth"');
	}
	print dolPrintHTML($arrayofcateg[$idtype]['label']);
	print '</td>';
	print '<td class="center">';
	print $arrayofcateg[$idtype]['nb'];
	print '</td>';
	print '<td class="center"><a class="editfielda" href="'.DOL_URL_ROOT.'/categories/categorie_list.php?mode=hierarchy&type='.urlencode($key).'">'.img_picto('', 'edit').'</a></td>';
	print '</tr>';
}

print "</table>";

print '</div>';

// End of page
llxFooter();
$db->close();
