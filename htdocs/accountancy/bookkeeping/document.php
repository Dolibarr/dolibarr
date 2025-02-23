<?php
/* Copyright (C) 2025		Alexandre Spangaro      <alexandre@inovea-conseil.com>
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
 *       \file       htdocs/compta/tva/document.php
 *       \ingroup    tax
 *       \brief      Page with attached files on social contributions
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('other', 'companies', 'compta', 'bills'));

$id = GETPOSTINT('id');
$mode = GETPOST('mode', 'aZ09'); // '' or '_tmp'
$piece_num = GETPOSTINT("piece_num") ? GETPOSTINT("piece_num") : GETPOST('ref'); 	// id of transaction (several lines share the same transaction id)

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
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

$object = new BookKeeping($db);

$result = $object->fetchPerMvt($piece_num, $mode);
if ($result < 0) {
    setEventMessages($object->error, $object->errors, 'errors');
}
$object->ref = (string) $object->piece_num;

$upload_dir = $conf->accounting->dir_output.dol_sanitizeFileName($object->piece_num);
$modulepart = 'accounting';

// Security check
if (!isModEnabled('accounting')) {
    accessforbidden();
}
if ($user->socid > 0) {
    accessforbidden();
}
if (!$user->hasRight('accounting', 'mouvements', 'lire')) {
    accessforbidden();
}

$permissiontoadd = $user->hasRight('accounting', 'mouvements', 'creer');	// Used by the include of actions_dellink.inc.php
$permtoedit = $user->hasRight('accounting', 'mouvements', 'creer');
$permissiontodelete = $user->hasRight('accounting', 'mouvements', 'supprimer');

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans('Transaction').' - '.$langs->trans("Documents");
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';

llxHeader("", $title, $help_url);

if (!empty($object->piece_num)) {
	$backlink = '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?restore_lastsearch_values=1">'.$langs->trans('BackToList').'</a>';

	$head = accountingtransaction_prepare_head($object);

	print dol_get_fiche_head($head, 'documents', $langs->trans('Transaction'), -1, 'accounting_account');

	$morehtmlref = '<div style="clear: both;"></div>';
	$morehtmlref .= '<div class="refidno opacitymedium">';
	$morehtmlref .= $object->doc_ref;
	$morehtmlref .= '</div>';

	$morehtmlstatus = '';
	dol_banner_tab($object, 'ref', $backlink, 1, 'piece_num', 'piece_num', $morehtmlref);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	// Build file list
	$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
	$totalsize = 0;
	foreach ($filearray as $key => $file) {
		$totalsize += $file['size'];
	}

	print '<table class="border tableforfield centpercent">';

	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
	print '</table>';

	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	$param = '&piece_num='.$object->piece_num;
	include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
} else {
	print $langs->trans("ErrorUnknown");
}


llxFooter();

$db->close();
