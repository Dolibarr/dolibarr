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
 * \file    paymentedit/admin/about.php
 * \ingroup paymentedit
 * \brief   About page for PaymentEdit module
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
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

/*
 * View
 */

$page_name = "PaymentEditAbout";

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'title_setup');

// Configuration header
$head = paymenteditAdminPrepareHead();
print dol_get_fiche_head($head, 'about', $langs->trans($page_name), -1, 'payment');

// Module info
print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ModuleName").'</td>';
print '<td>PaymentEdit</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Version").'</td>';
print '<td>1.0.0</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Author").'</td>';
print '<td>Florian Hödl &lt;florian@hoedl.co&gt;</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("Publisher").'</td>';
print '<td><a href="https://anexum.at" target="_blank" rel="noopener noreferrer">Anexum GmbH</a></td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("License").'</td>';
print '<td>GPL-3.0+</td>';
print '</tr>';

print '</table>';

print '<br>';

// Description
print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';
print '<table class="border centpercent tableforfield">';

print '<tr><td class="titlefield">'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("PaymentEditDescriptionLong").'</td></tr>';

print '</table>';
print '</div>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
