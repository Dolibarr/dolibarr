<?php
/* Copyright (C) 2005-2023	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2024	Regis Houssin			<regis.houssin@inodbox.com>
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
 *  \file       htdocs/admin/system/browser.php
 *  \brief      Page to show Dolibarr information
 */

// Load Dolibarr environment
require '../../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array("install", "other", "admin"));

if (empty($user->admin)) {
	accessforbidden();
}


/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-system_browser');

print load_fiche_titre($langs->trans("InfoBrowser"), '', 'title_setup');

// Browser
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td colspan="2">'.$langs->trans("Value").'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("UserAgent").'</td><td colspan="2">'.dol_escape_htmltag($conf->browser->ua).'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("BrowserName").'</td><td colspan="2">'.$conf->browser->name.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("BrowserOS").'</td><td colspan="2">'.$conf->browser->os.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("Version").'</td><td colspan="2">'.$conf->browser->version.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("Layout").' (phone/tablet/classic)</td><td colspan="2">'.$conf->browser->layout.'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("IPAddress").'</td><td colspan="2">'.dol_escape_htmltag($_SERVER['REMOTE_ADDR']);
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
	print ' (HTTP_CLIENT_IP='.dol_escape_htmltag($_SERVER['HTTP_CLIENT_IP']).')';
}
if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	print ' (HTTP_X_FORWARDED_FOR='.dol_escape_htmltag($_SERVER['HTTP_X_FORWARDED_FOR']).')';
}
print '</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("SessionName").'</td><td colspan="2">'.session_name().'</td></tr>'."\n";
print '<tr class="oddeven"><td width="300">'.$langs->trans("SessionId").'</td><td colspan="2">********</td></tr>'."\n";

print '<tr class="oddeven"><td width="300">'.$langs->trans("Screen").'</td><td colspan="2">';
print $_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight'];
print '</td></tr>'."\n";
print '</table>';
print '</div>';
print '<br>';

// End of page
llxFooter();
$db->close();
