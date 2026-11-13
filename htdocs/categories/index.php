<?php
/* Copyright (C) 2005		Matthieu Valleton			<mv@seeschloss.org>
 * Copyright (C) 2005		Eric Seigne					<eric.seigne@ryxeo.com>
 * Copyright (C) 2006-2016	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Patrick Raguin				<patrick.raguin@gmail.com>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Raphaël Doursenaud			<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2021-2025  Frédéric France				<frederic.france@free.fr>
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
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("accountancy", "agenda", "banks", "bills", "categories", "contracts", "interventions", "mrp", "propal", "supplier_proposal"));
$langs->loadLangs(array("knowledgemanagement", "members", "orders", "products", "stocks", "suppliers", "tickets", "website"));

$mode = GETPOST('mode', 'aZ09');
if (empty($mode)) {
	$mode = 'hierarchy';
}
$search_type = GETPOST('search_type', 'alphanohtml');

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
 * Actions
 */

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_type = '';
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Categories");

llxHeader('', $title, '', '', 0, 0, '', '');

// Get number of tags per category type
$countobjects = [];
$sql = "SELECT type as idtype, COUNT(rowid) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."categorie";
$sql .= " WHERE entity IN (".getEntity('category').")";
$sql .= " GROUP BY type";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$countobjects[$obj->idtype] = $obj->nb;
	}
} else {
	dol_print_error($db);
}

// Get list of category type
$arrayofcateg = array();
foreach ($categstatic->MAP_ID as $key => $idtype) {
	if ($key == 'product' && !isModEnabled("product")) {
		continue;
	}
	if ($key == 'service' && !isModEnabled("service")) {
		continue;
	}

	$arrayofcateg[$idtype] = array();
	$arrayofcateg[$idtype]['key'] = $key;
	$arrayofcateg[$idtype]['nb'] = $countobjects[$idtype] ?? 0;
	$arrayofcateg[$idtype]['label'] = $langs->transnoentitiesnoconv($categstatic::$MAP_TYPE_TITLE_AREA[$key]);
	$arrayofcateg[$idtype]['labelwithoutaccent'] = dol_string_unaccent($langs->transnoentitiesnoconv($categstatic::$MAP_TYPE_TITLE_AREA[$key]));
	if ($key == 'product' || $key == 'service') {
		$arrayofcateg[$idtype]['label'] = $langs->transnoentitiesnoconv("ProductsAndServices");
		$arrayofcateg[$idtype]['labelwithoutaccent'] = dol_string_unaccent($langs->transnoentitiesnoconv("ProductsAndServices"));
	}

	// Filter on the label of the type (case and accent insensitive)
	if ($search_type !== '' && strpos(dol_strtolower($arrayofcateg[$idtype]['labelwithoutaccent']), dol_strtolower(dol_string_unaccent($search_type))) === false) {
		unset($arrayofcateg[$idtype]);
	}
}
$arrayofcateg = dol_sort_array($arrayofcateg, 'labelwithoutaccent', 'asc', 1, 0, 1);

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', '', '', '', count($arrayofcateg), count($arrayofcateg), $categstatic->picto, 0, '', '', -1, 0, 1, 1);

print '<div class="info">';
print $langs->trans("CategorieListOfType").'...';
print '</div>';

// Define $nbmodulesnotautoenabled - TODO This code is at different places
$nbmodulesnotautoenabled = count($conf->modules);
$listofmodulesautoenabled = array('user', 'agenda', 'fckeditor', 'export', 'import');
foreach ($listofmodulesautoenabled as $moduleautoenable) {
	if (in_array($moduleautoenable, $conf->modules)) {
		$nbmodulesnotautoenabled--;
	}
}

if ($user->admin && $nbmodulesnotautoenabled < getDolGlobalInt('MAIN_MIN_NB_ENABLED_MODULE_FOR_WARNING', 1)) {	// If only minimal initial modules enabled
	$langs->load("admin");
	print info_admin($langs->trans("WarningOnlyCategoryTypesOfActivatedModules").' '.$langs->trans("YouCanEnableModulesFrom"));
}

print '<br>';

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div class="aaa">';

print '<table class="liste nohover centpercent noborder">';

// Line with the search filters
print '<tr class="liste_titre_filter">';
print '<td class="liste_titre"><input type="text" class="flat maxwidth200" name="search_type" value="'.dolPrintHTMLForAttribute($search_type).'"></td>';
print '<td class="liste_titre"></td>';
print '<td class="liste_titre center maxwidthsearch">'.$form->showFilterButtons().'</td>';
print '</tr>';

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
	if (empty($arrayofcateg[$idtype]['nb'])) {
		print '<span class="opacitymedium">'.$arrayofcateg[$idtype]['nb'].'</span>';
	} else {
		print $arrayofcateg[$idtype]['nb'];
	}
	print '</td><td class="center">';
	print '<a class="editfielda" href="'.dolBuildUrl(DOL_URL_ROOT.'/categories/categorie_list.php', ['mode' => 'hierarchy', 'type' => $key]).'">'.img_picto('', 'edit').'</a>';
	print '</td>';

	print '</tr>';
}

print "</table>\n";

print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
