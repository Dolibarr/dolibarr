<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013		Marcos García		<marcosgdf@gmail.com>
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
 *    \file       htdocs/fourn/paiement/info.php
 *    \ingroup    invoice, fournisseur
 *    \brief      Tab for Supplier Payment Information
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("bills", "suppliers", "companies"));

// Get Parameters
$id = GETPOSTINT('id');

// Initialize Objects
$object = new PaiementFourn($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

$result = restrictedArea($user, $object->element, $object->id, 'paiementfourn', '');

// Security check
$socid = ''; // Prevents PHP Warning:  Undefined variable $socid on line 55
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

llxHeader();

$object->info($id);

$head = payment_supplier_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("SupplierPayment"), 0, 'payment');

$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/paiement/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

dol_banner_tab($object, 'id', $linkback, -1, 'rowid', 'ref');

print dol_get_fiche_end();

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

// End of page
llxFooter();
$db->close();
