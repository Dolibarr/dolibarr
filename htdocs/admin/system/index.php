<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/admin/system/index.php
 *  \brief      Home page of system information
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "user", "install"));

if (!$user->admin) accessforbidden();


/*
 * Actions
 */

// None


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("SummarySystem"), '', 'title_setup');


print '<table class="noborder centpercent">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">Dolibarr</td></tr>\n";
$dolversion = version_dolibarr();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$dolversion."</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder centpercent">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("OS")."</td></tr>\n";
$osversion = version_os();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$osversion."</td></tr>\n";
print '</table>';

print "<br>\n";

// Serveur web
print '<table class="noborder centpercent">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("WebServer")."</td></tr>\n";
$apacheversion = version_webserver();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$apacheversion."</td></tr>\n";
print '</table>';

print "<br>\n";

// Php
print '<table class="noborder centpercent">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("PHP")."</td></tr>\n";
$phpversion = version_php();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$phpversion."</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("PhpWebLink")."</td><td>".php_sapi_name()."</td></tr>\n";
print '</table>';

print "<br>\n";

// Database
print '<table class="noborder centpercent">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Database")."</td></tr>\n";
$dblabel = $db::LABEL;
$dbversion = $db->getVersion();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$dblabel." ".$dbversion."</td></tr>\n";
print '</table>';
// Add checks on database options
if ($db->type == 'pgsql')
{
	// Check option standard_conforming_strings is on
	$paramarray = $db->getServerParametersValues('standard_conforming_strings');
	//	if ($paramarray['standard_conforming_strings'] != 'on' && $paramarray['standard_conforming_strings'] != 1)
	//	{
	//		$langs->load("errors");
	//	}
}
print '<br>';

// Browser
print '<table class="noborder centpercent">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Browser")."</td></tr>\n";
print "<tr $bc[0]><td width=\"280\">".$langs->trans("UserAgent")."</td><td>".$_SERVER["HTTP_USER_AGENT"]."</td></tr>\n";
print "<tr $bc[1]><td width=\"280\">".$langs->trans("Smartphone")."</td><td>".(($conf->browser->layout != 'phone') ? $langs->trans("No") : $langs->trans("Yes"))."</td></tr>\n";
print '</table>';
print '<br>';


//print "<br>\n";
print info_admin($langs->trans("SystemInfoDesc")).'<br>';

// End of page
llxFooter();
$db->close();
