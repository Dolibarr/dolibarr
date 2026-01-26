<?php
/* Copyright (C) 2025 Florian Hoedl <florian@hoedl.co>
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
 * \file       htdocs/custom/earechnungat/admin/about.php
 * \ingroup    earechnungat
 * \brief      About page for EARechnungAT module
 */

// Load Dolibarr environment
require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
require_once __DIR__ . '/../lib/earechnungat.lib.php';

// Load translation files
$langs->loadLangs(array("admin", "earechnungat@earechnungat"));

// Security check
if (!$user->admin) {
	accessforbidden();
}

/*
 * View
 */

$title = $langs->trans('EARechnungATAbout');

llxHeader('', $title);

$head = earechnungatAdminPrepareHead();

print dol_get_fiche_head($head, 'about', $langs->trans('EARechnungATMenu'), -1, 'fa-file-invoice');

print '<div class="fichecenter">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td colspan="2">' . $langs->trans('EARechnungATAbout') . '</td></tr>';

print '<tr class="oddeven">';
print '<td>Module</td>';
print '<td>EARechnungAT v1.0.0</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Author</td>';
print '<td>Florian Hoedl</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td colspan="2">' . $langs->trans('AboutDescription') . '</td>';
print '</tr>';

print '</table>';

print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();
