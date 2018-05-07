<?php
/* Copyright (C) 2017       Alexandre Spangaro  <aspangaro@zendsi.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/compta/bank/various_payment/info.php
 *  \ingroup    bank
 *  \brief      Page with info about various payment
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->loadLangs(array("compta", "banks", "bills", "users", "accountancy"));

$id=GETPOST('id','int');
$action=GETPOST('action','aZ09');

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '', '', '');

/*
 * View
 */

llxHeader("",$langs->trans("VariousPayment"));

$object = new PaymentVarious($db);
$result = $object->fetch($id);
$object->info($id);

$head = various_payment_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("VariousPayment"), 0, 'payment');


print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

llxFooter();

$db->close();
