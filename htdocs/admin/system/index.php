<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/admin/system/index.php
 *  \brief      Page accueil infos systeme
 *  \version    $Id$
 */

require("../../main.inc.php");
include_once(DOL_DOCUMENT_ROOT."/lib/databases/".$conf->db->type.".lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");

$langs->load("admin");
$langs->load("user");
$langs->load("install");

if (!$user->admin)
  accessforbidden();


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("SummarySystem"),'','setup');

//print "<br>\n";
print info_admin($langs->trans("SystemInfoDesc")).'<br>';

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">Dolibarr</td></tr>\n";
$dolversion=version_dolibarr();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$dolversion."</td></tr>\n";
print '</table>';

print "<br>\n";

print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("OS")."</td></tr>\n";
$osversion=version_os();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$osversion."</td></tr>\n";
print '</table>';

print "<br>\n";

// Serveur web
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("WebServer")."</td></tr>\n";
$apacheversion=version_webserver();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$apacheversion."</td></tr>\n";
print '</table>';

print "<br>\n";

// Php
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Php")."</td></tr>\n";
$phpversion=version_php();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$phpversion."</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("PhpWebLink")."</td><td>".php_sapi_name()."</td></tr>\n";
print '</table>';

print "<br>\n";

// Database
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Database")."</td></tr>\n";
$dblabel=$db->getLabel();
$dbversion=$db->getVersion();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>" .$dblabel." ".$dbversion."</td></tr>\n";
print '</table>';
print '<br>';

llxFooter('$Date$ - $Revision$');
?>
