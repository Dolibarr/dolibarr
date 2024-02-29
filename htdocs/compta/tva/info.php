<?php
/* Copyright (C) 2016-2023  Alexandre Spangaro      <aspangaro@easya.solutions>
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
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/vat.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');

$object = new Tva($db);

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'tax', '', 'tva', 'charges');


/*
 * Actions
 */

if ($action == 'setlib' && $user->hasRight('tax', 'charges', 'creer')) {
	$object->fetch($id);
	$result = $object->setValueFrom('label', GETPOST('lib', 'alpha'), '', '', 'text', '', $user, 'TAX_MODIFY');
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}


/*
 * View
 */

$title = $langs->trans("VAT")." - ".$langs->trans("Info");
$help_url = '';
llxHeader("", $title, $help_url);

$object = new Tva($db);
$object->fetch($id);
$object->info($id);

$head = vat_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("VATPayment"), -1, 'payment');

$morehtmlref = '<div class="refidno">';
// Label of social contribution
$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->hasRight('tax', 'charges', 'creer'), 'string', '', null, null, '', 1);
$morehtmlref .= '</div>';

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/tva/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', '');

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

print dol_get_fiche_end();

llxFooter();

$db->close();
