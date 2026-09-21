<?php
/* Copyright (C) 2026	Jose MARTINEZ	<jose.martinez@pichinov.com>
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
 *  \file       htdocs/product/inventory/inventory_document.php
 *  \ingroup    stock
 *  \brief      Tab for documents linked to an inventory
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var ExtraFields $extrafields
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/inventory/class/inventory.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/inventory/lib/inventory.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("stocks", "other", "mails"));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm');
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

// Initialize a technical objects
$object = new Inventory($db);
$hookmanager->initHooks(array('inventorydocument', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'. Include fetch and fetch_thirdparty but not fetch_optionals

$upload_dir = null;
if ($id > 0 || !empty($ref)) {
	$upload_dir = $conf->stock->multidir_output[$object->entity ? $object->entity : $conf->entity].'/inventory/'.dol_sanitizeFileName((string) $object->ref);
}

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
$result = restrictedArea($user, 'stock', $object->id, 'inventory&stock');

$permissiontoadd = $user->hasRight('stock', 'creer'); // Used by the include of actions_linkedfiles.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("Inventory").' - '.$langs->trans("Files");
$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|DE:Modul_Lager';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-product page-inventory_document');

if ($object->id && $upload_dir !== null) {
	/*
	 * Show tabs
	 */
	$head = inventoryPrepareHead($object);

	print dol_get_fiche_head($head, 'document', $langs->trans("Inventory"), -1, 'stock');

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/product/inventory/list.php', 1).'?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';

	// Number of files
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';

	// Total size
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print '</table>';

	print '</div>';

	print dol_get_fiche_end();

	$modulepart = 'inventory';
	$permtoedit = $user->hasRight('stock', 'creer');
	$param = '&id='.$object->id;

	$relativepathwithnofile = dol_sanitizeFileName((string) $object->ref).'/';

	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	accessforbidden('', 0, 1);
}

// End of page
llxFooter();
$db->close();
