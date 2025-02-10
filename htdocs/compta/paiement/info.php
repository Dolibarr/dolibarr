<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2013		Marcos García			<marcosgdf@gmail.com>
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
 *   	\file       htdocs/compta/paiement/info.php
 *		\ingroup    invoice
 *		\brief      Onglet info d'un paiement
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('bills', 'companies'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOSTINT('socid');

$object = new Paiement($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

$result = restrictedArea($user, $object->element, $object->id, 'paiement', '');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
// Now check also permission on thirdparty of invoices of payments. Thirdparty were loaded by the fetch_object before based on first invoice.
// It should be enough because all payments are done on invoices of the same thirdparty.
if ($socid && $socid != $object->thirdparty->id) {
	accessforbidden();
}



/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);	// Used in dol_banner_tab

llxHeader('', $langs->trans("Payment"));

$object->info($object->id);

$head = payment_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("PaymentCustomerInvoice"), -1, 'payment');


$linkback = '<a href="'.DOL_URL_ROOT.'/compta/paiement/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', '');


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
