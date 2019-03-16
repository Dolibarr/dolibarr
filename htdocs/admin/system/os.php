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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/admin/system/os.php
 *		\brief      Page des infos systeme de l'OS
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("InfoOS"), '', 'title_setup');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
print "\n";

// Recupere l'OS au sens PHP
print "<tr $bc[0]><td width=\"240\">".$langs->trans("PHP_OS")."</td><td>".PHP_OS."</td></tr>\n";

// Recupere la version de l'OS
$osversion=version_os();
print "<tr $bc[1]><td width=\"240\">".$langs->trans("Version")."</td><td>".$osversion."</td></tr>\n";
print '</table>';

// End of page
llxFooter();
$db->close();
