<?php

/* Copyright (C) 2024-2025  Florian Hödl  <florian@hoedl.co>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    paymentedit/admin/setup.php
 * \ingroup paymentedit
 * \brief   PaymentEdit setup page
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../lib/paymentedit.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 * @var User $user
 */

// Load translation files
$langs->loadLangs(array("admin", "paymentedit@paymentedit"));

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');

/*
 * Actions
 */

// Currently no configurable options - module works out of the box

/*
 * View
 */

$page_name = "PaymentEditSetup";

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = paymenteditAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, 'payment');

// Setup page content
print '<div class="info">';
print $langs->trans("PaymentEditSetupPage");
print '</div>';

print '<br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>';
print $langs->trans("PaymentEditDescriptionLong");
print '</td>';
print '</tr>';

print '</table>';

print '<br>';

// Info box about functionality
print '<div class="opacitymedium">';
print '<strong>'.$langs->trans("Usage").':</strong><br>';
print '1. '.$langs->trans("Go to").' <a href="'.DOL_URL_ROOT.'/compta/bank/various_payment/list.php">'.$langs->trans("VariousPayment").'</a><br>';
print '2. '.$langs->trans("Open a payment card").'<br>';
print '3. '.$langs->trans("Click the Modify button in the action bar").'<br>';
print '</div>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
