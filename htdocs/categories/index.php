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
$langs->loadLangs(array("knowledgemanagement", "members", "orders", "products", "stocks", "suppliers", "tickets"));

$mode = GETPOST('mode', 'aZ09');
if (empty($mode)) {
	$mode = 'hierarchy';
}
$id = GETPOSTINT('id');
$type = (GETPOST('type', 'aZ09') ? GETPOST('type', 'aZ09') : Categorie::TYPE_PRODUCT);
$catname = GETPOST('catname', 'alpha');
$nosearch = GETPOSTINT('nosearch');

$categstatic = new Categorie($db);
if (is_numeric($type)) {
	$type = array_search($type, $categstatic->MAP_ID);	// For backward compatibility
}

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

$nbtotalofrecords = 99;


/*
 * View
 */

$title = $langs->trans("Categories");
$title .= ' ('.$langs->trans(empty(Categorie::$MAP_TYPE_TITLE_AREA[$type]) ? ucfirst($type) : Categorie::$MAP_TYPE_TITLE_AREA[$type]).')';


// Output page
// --------------------------------------------------------------------

llxHeader('', $title, '', '', 0, 0, '', '');


print '<div class="fichecenter">';

print '<table class="liste nohover centpercent noborder">';
print '<tr class="liste_titre"><td>'.$langs->trans("Categories").'</td><td></td><td class="right">';
if ($morethan1level && !empty($conf->use_javascript_ajax)) {
	print '<div id="iddivjstreecontrol">';
	print '<a class="notasortlink" href="#">'.img_picto('', 'folder', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("UndoExpandAll").'</span></a>';
	print ' | ';
	print '<a class="notasortlink" href="#">'.img_picto('', 'folder-open', 'class="paddingright"').'<span class="hideonsmartphone">'.$langs->trans("ExpandAll").'</span></a>';
	print '</div>';
}
print '</td></tr>';

if ($nbofentries > 0) {
	print '<tr class="oddeven nohover"><td colspan="3">';
	tree_recur($data, $data[0], 0);
	print '</td></tr>';
} else {
	print '<tr class="oddeven">';
	print '<td colspan="3"><table class="nobordernopadding"><tr class="nobordernopadding"><td>'.img_picto_common('', 'treemenu/branchbottom.gif').'</td>';
	print '<td class="valignmiddle">';
	print $langs->trans("NoCategoryYet");
	print '</td>';
	print '<td>&nbsp;</td>';
	print '</table></td>';
	print '</tr>';
}

print "</table>";

print '</div>';

// End of page
llxFooter();
$db->close();
