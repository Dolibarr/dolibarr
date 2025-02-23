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
 *	\file       htdocs/compta/tva/info.php
 *	\ingroup    tax
 *	\brief      Page with info about vat
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

$mode = GETPOST('mode', 'aZ09'); // '' or '_tmp'
$piece_num = GETPOSTINT("piece_num") ? GETPOSTINT("piece_num") : GETPOST('ref'); 	// id of transaction (several lines share the same transaction id)

$object = new BookKeeping($db);

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', '', 'tva', 'charges');

/*
 * Actions
 */



/*
 * View
 */

$title = $langs->trans("Transaction")." - ".$langs->trans("Info");
$help_url = 'EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double';
llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-consultation page-info');

$result = $object->fetchPerMvt($piece_num, $mode);
if ($result < 0) {
	setEventMessages($object->error, $object->errors, 'errors');
}

$head = accountingtransaction_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans('Transaction'), -1, 'accounting_account');

$object->ref = (string) $object->piece_num;

$morehtmlref = '<div class="refidno">';
$morehtmlref .= '<div class="refidno opacitymedium">';
$morehtmlref .= $object->doc_ref;
$morehtmlref .= '</div>';
$morehtmlref .= '</div>';

$linkback = '<a href="'.DOL_URL_ROOT.'/accountancy/bookkeeping/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'piece_num', $linkback, 0, 'piece_num', 'piece_num', $morehtmlref, '', 0, '', '');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table class="centpercent"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

print dol_get_fiche_end();

llxFooter();

$db->close();
