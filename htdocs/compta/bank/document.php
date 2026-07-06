<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2017 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * 	\file       htdocs/compta/bank/document.php
 * 	\ingroup    banque
 * 	\brief      Page to manage documents attached to a bank account
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/bank.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('banks', 'companies', 'other'));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('account'));
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('bankaccountdocuments', 'globalcard'));

// Security check
if ($user->socid) {
	$action = '';
	$socid = $user->socid;
}
if ($user->socid) {
	$socid = $user->socid;
}

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

$object = new Account($db);
if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
}


$result = restrictedArea($user, 'banque', $object->id, 'bank_account', '', '');

$permissiontoadd = $user->hasRight('banque', 'modifier');	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

if ($object->id > 0) {
	$object->fetch_thirdparty();
	$upload_dir = $conf->bank->dir_output."/".dol_sanitizeFileName($object->ref);
}

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$title = $object->ref.' - '.$langs->trans("Documents");
$help_url = "EN:Module_Banks_and_Cash|FR:Module_Banques_et_Caisses";

llxHeader("", $title, $help_url);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref)) {
		$upload_dir = $conf->bank->dir_output.'/'.$object->ref;

		// Onglets
		$head = bank_prepare_head($object);
		print dol_get_fiche_head($head, 'document', $langs->trans("FinancialAccount"), -1, 'account');


		// Build file list
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
		$totalsize = 0;
		foreach ($filearray as $key => $file) {
			$totalsize += $file['size'];
		}

		$morehtmlref = '';

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';
		print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
		print "</table>\n";

		print '</div>';

		print dol_get_fiche_end();


		$modulepart = 'bank';
		$permissiontoadd = $user->hasRight('banque', 'modifier');
		$permtoedit = $user->hasRight('banque', 'modifier');
		$param = '&id='.$object->id;
		include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
	} else {
		dol_print_error($db);
	}
} else {
	header('Location: index.php');
	exit;
}

// End of page
llxFooter();
$db->close();
