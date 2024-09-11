<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *    \file       htdocs/compta/bank/info.php
 *    \ingroup    compta/bank
 *    \brief      Info tab of bank statement
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';


// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'companies'));


// Get Parameters
$id = GETPOSTINT("rowid");
$accountid = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('account'));
$ref = GETPOST('ref', 'alpha');


// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));

$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

$result = restrictedArea($user, 'banque', $accountid, 'bank_account');
if (!$user->hasRight('banque', 'lire') && !$user->hasRight('banque', 'consolidate')) {
	accessforbidden();
}


/*
 * View
 */

llxHeader();

$object = new AccountLine($db);
$object->fetch($id);
$object->info($id);


$h = 0;

$head = array();
$head[$h][0] = DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$id;
$head[$h][1] = $langs->trans("BankTransaction");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/bank/info.php?rowid='.$id;
$head[$h][1] = $langs->trans("Info");
$hselected = (string) $h;
$h++;


print dol_get_fiche_head($head, $hselected, $langs->trans("LineRecord"), -1, 'accountline');

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';


dol_banner_tab($object, 'rowid', $linkback);

print '<div class="underbanner clearboth"></div>';
print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

// End of page
llxFooter();
$db->close();
