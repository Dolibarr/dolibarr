<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015       Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 *		\file       htdocs/expensereport/payment/info.php
 *		\ingroup    Expense report
 *		\brief      Tab payment info
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'trips'));

$id = GETPOST('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');

/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader('', $langs->trans("Payment"));

$object = new PaymentExpenseReport($db);
$object->fetch($id);
$object->info($object->id);

$head = payment_expensereport_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("ExpenseReportPayment"), -1, 'payment');


//$linkback = '<a href="' . DOL_URL_ROOT . '/expensereport/payment/list.php">' . $langs->trans("BackToList") . '</a>';

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
