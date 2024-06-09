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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	    \file       htdocs/admin/system/web.php
 *		\brief      Page with web server system information
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}


/*
 * Action
 */

// None


/*
 * View
 */

llxHeader('', $langs->trans("InfoWebServer"), '', '', 0, 0, '', '', '', 'mod-admin page-system_web');

print load_fiche_titre($langs->trans("InfoWebServer"), '', 'title_setup');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter")."</td><td>".$langs->trans("Value")."</td></tr>\n";
print '<tr class="oddeven"><td>'.$langs->trans("Version")."</td><td>".$_SERVER["SERVER_SOFTWARE"]."</td></tr>\n";
print '<tr class="oddeven"><td>'.$langs->trans("VirtualServerName")."</td><td>".$_SERVER["SERVER_NAME"]."</td></tr>\n";
print '<tr class="oddeven"><td>'.$langs->trans("IP")."</td><td>".$_SERVER["SERVER_ADDR"]."</td></tr>\n";
print '<tr><td>'.$langs->trans("Port")."</td><td>".$_SERVER["SERVER_PORT"]."</td></tr>\n";
print '<tr><td>'.$langs->trans("DocumentRootServer")."</td><td>".$_SERVER["DOCUMENT_ROOT"]."</td></tr>\n";
print '<tr><td>'.$langs->trans("DataRootServer")."</td><td>".DOL_DATA_ROOT."</td></tr>\n";
// Web user group by default
$labeluser = dol_getwebuser('user');
$labelgroup = dol_getwebuser('group');
if ($labeluser && $labelgroup) {
	print '<tr><td>'.$langs->trans("WebUserGroup")." (env vars)</td><td>".$labeluser.':'.$labelgroup;
	if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
		$arrayofinfoofuser = posix_getpwuid(posix_geteuid());
		print ' <span class="opacitymedium">(POSIX '.$arrayofinfoofuser['name'].':'.$arrayofinfoofuser['gecos'].':'.$arrayofinfoofuser['dir'].':'.$arrayofinfoofuser['shell'].')</span>';
	}
	print "</td></tr>\n";
}
// Web user group real (detected by 'id' external command)
if (function_exists('exec')) {
	$arrayout = array();
	$varout = 0;
	exec('id', $arrayout, $varout);
	print '<tr><td>'.$langs->trans("WebUserGroup")." (real, 'id' command)</td><td>";
	if (empty($varout)) {	// Test command is ok. Work only on Linux OS.
		print implode(',', $arrayout);
	} else {
		$langs->load("errors");
		print '<span class="opacitymedium">'.$langs->trans("ErrorExecIdFailed").'</span>';
	}
	print "</td></tr>\n";
}
print '</table>';
print '</div>';

llxFooter();

$db->close();
