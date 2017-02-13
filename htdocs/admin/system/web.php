<?php
/* Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/admin/system/web.php
 *		\brief      Page with web server system information
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

$langs->load("admin");

if (!$user->admin) accessforbidden();


/*
 * Action
 */

// None


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("InfoWebServer"),'','title_setup');

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td>".$langs->trans("Parameter")."</td><td>".$langs->trans("Value")."</td></tr>\n";
print "<tr $bc[0]><td width=\"240\">".$langs->trans("Version")."</td><td>".$_SERVER["SERVER_SOFTWARE"]."</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("VirtualServerName")."</td><td>" . $_SERVER["SERVER_NAME"] . "</td></tr>\n";
print "<tr $bc[0]><td width=\"240\">".$langs->trans("IP")."</td><td>".$_SERVER["SERVER_ADDR"]."</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("Port")."</td><td>" . $_SERVER["SERVER_PORT"] . "</td></tr>\n";
print "<tr $bc[0]><td width=\"240\">".$langs->trans("DocumentRootServer")."</td><td>".$_SERVER["DOCUMENT_ROOT"]."</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DataRootServer")."</td><td>" . DOL_DATA_ROOT . "</td></tr>\n";
$labeluser=dol_getwebuser('user');
$labelgroup=dol_getwebuser('group');
if ($labeluser && $labelgroup)
{
	print "<tr $bc[0]><td>".$langs->trans("WebUserGroup")."</td><td>".$labeluser.'/'.$labelgroup."</td></tr>\n";
}
print '</table>';


llxFooter();

$db->close();
