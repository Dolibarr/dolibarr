<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2013      Florian Henry          <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
 * Copyright (C) 2018      All-3kcis       		 <contact@all-3kcis.fr>
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
 *       \file       htdocs/product/stock/productlot_document.php
 *       \ingroup    product
 *       \brief      Page of attached documents for product lots
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

global $conf, $db, $langs, $user;

// Load translation files required by the page
$langs->loadLangs(array('other', 'products'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Security check
$fieldvalue = (!empty($id) ? $id : '');
$fieldtype = 'rowid';
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'produit|service');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('productlotdocuments'));

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
	$sortfield = "position_name";
}

$modulepart = 'product_batch';
$object = new Productlot($db);
if ($id || $ref) {
	if ($ref) {
		$tmp = explode('_', $ref);
		$productid = $tmp[0];
		$batch = $tmp[1];
	}
	$object->fetch($id, $productid, $batch);

	if (isModEnabled('productbatch')) {
		$upload_dir = $conf->productbatch->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 1, $object, $modulepart);
		$filearray = dol_dir_list($upload_dir, "files");
	}
}

$usercanread = $user->hasRight('produit', 'lire');
$usercancreate = $user->hasRight('produit', 'creer');
$usercandelete = $user->hasRight('produit', 'supprimer');

if (empty($upload_dir)) {
	$upload_dir = $conf->productbatch->multidir_output[$conf->entity];
}

$permissiontoread = $usercanread;
$permissiontoadd = $usercancreate;
$permtoedit = $user->hasRight('produit', 'creer');
//$permissiontodelete = $usercandelete;

// Security check
if (!isModEnabled('productbatch')) {
	accessforbidden('Module not enabled');
}
$socid = 0;
if ($user->socid > 0) { // Protection if external user
	//$socid = $user->socid;
	accessforbidden();
}
//$result = restrictedArea($user, 'productbatch');
if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Action submit/delete file/link
	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}


/*
 *	View
 */

$form = new Form($db);

llxHeader('', $langs->trans('ProductLot'), '', '', 0, 0, '', '', '', 'mod-product page-stock_productlot_document');


if ($object->id) {
	$head = productlot_prepare_head($object);
	print dol_get_fiche_head($head, 'documents', $langs->trans("Batch"), -1, 'barcode');


	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}


	$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/productlot_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$shownav = 1;
	if ($user->socid && !in_array('batch', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
		$shownav = 0;
	}

	dol_banner_tab($object, 'id', $linkback, $shownav, 'rowid', 'batch');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border tableforfield" width="100%">';

	// Product
	print '<tr><td class="titlefield">'.$langs->trans("Product").'</td><td>';
	$producttmp = new Product($db);
	$producttmp->fetch($object->fk_product);
	print $producttmp->getNomUrl(1, 'stock')." - ".$producttmp->label;
	print '</td></tr>';

	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print '</table>';

	print '</div>';
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	$param = '&id='.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}


llxFooter();
$db->close();
