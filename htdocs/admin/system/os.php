<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file      htdocs/admin/system/os.php
 *    \brief     Page about System OS (Operating System)
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_os');

print load_fiche_titre($langs->trans("InfoOS"), '', 'title_setup');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
print "\n";

// Recovers the OS in the PHP sense
print '<tr class="oddeven"><td>'.$langs->trans("PHP_OS")."</td><td>".PHP_OS."</td></tr>\n";

// Recovers the OS version
$osversion = version_os();
print '<tr class="oddeven"><td>'.$langs->trans("Version")."</td><td>".$osversion."</td></tr>\n";
print '</table>';

// End of page
llxFooter();
$db->close();
