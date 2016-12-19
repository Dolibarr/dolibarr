<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013		Marcos García		<marcosgdf@gmail.com>
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
 *   	\file       htdocs/compta/paiement/info.php
 *		\ingroup    facture
 *		\brief      Onglet info d'un paiement
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

$langs->load("bills");
$langs->load("companies");

$id=GETPOST('id');


/*
 * View
 */

llxHeader();

$object = new Paiement($db);
$object->fetch($id);
$object->info($id);

$head = payment_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("PaymentCustomerInvoice"), 0, 'payment');

print '<table class="border" width="100%">';

$linkback = '<a href="' . DOL_URL_ROOT . '/compta/paiement/list.php">' . $langs->trans("BackToList") . '</a>';


// Ref
print '<tr><td class="titlefield">'.$langs->trans('Ref').'</td><td colspan="3">';
print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
print '</td></tr>';

print '</table>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

llxFooter();
$db->close();
