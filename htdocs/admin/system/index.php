<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/admin/system/index.php
   \brief      Page accueil infos système
   \version    $Id$
*/

require("./pre.inc.php");
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
$phpversion=version_os();
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".$phpversion."</td></tr>\n";
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
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>".version_php()."</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("PhpWebLink")."</td><td>".php_sapi_name()."</td></tr>\n";
print '</table>';

print "<br>\n";

// Base de donnée
print '<table class="noborder" width="100%">';
print "<tr class=\"liste_titre\"><td colspan=\"2\">".$langs->trans("Database")."</td></tr>\n";
print "<tr $bc[0]><td width=\"280\">".$langs->trans("Version")."</td><td>" . $db->getVersion() . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DatabaseServer")."</td><td>" . $conf->db->host . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("DatabaseName")."</td><td>" . $conf->db->name . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DriverType")."</td><td>" . $conf->db->type . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("User")."</td><td>" . $conf->db->user . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("Password")."</td><td>" . eregi_replace('.','*',$dolibarr_main_db_pass) . "</td></tr>\n";
print "<tr $bc[0]><td>".$langs->trans("DBStoringCharset")."</td><td>" . $db->getDefaultCharacterSetDatabase() . "</td></tr>\n";
print "<tr $bc[1]><td>".$langs->trans("DBSortingCharset")."</td><td>" . $db->getDefaultCollationDatabase() . "</td></tr>\n";
print '</table>';
print '<br>';

// conf.php file
$configfileparameters=array(
//							'separator',
							'dolibarr_main_url_root',
							'dolibarr_main_document_root',
							'dolibarr_main_data_root',
							'separator',
							'dolibarr_main_db_host',
							'dolibarr_main_db_port',
							'dolibarr_main_db_name',
							'dolibarr_main_db_type',
							'dolibarr_main_db_user',
							'dolibarr_main_db_pass',
							'dolibarr_main_db_character_set',
							'dolibarr_main_db_collation',
							'separator',
							'dolibarr_main_authentication',
							'separator',
							'dolibarr_main_auth_ldap_login_attribute',
							'dolibarr_main_auth_ldap_host',
							'dolibarr_main_auth_ldap_port',
							'dolibarr_main_auth_ldap_version',
							'dolibarr_main_auth_ldap_dn',
							'dolibarr_main_auth_ldap_admin_login',
							'dolibarr_main_auth_ldap_admin_pass',
							'dolibarr_main_auth_ldap_debug',
							'separator',
							'dolibarr_smarty_libs_dir',
							'dolibarr_smarty_compile',
							'dolibarr_smarty_cache'
						);
$configfilelib=array(
//					'separator',
					$langs->trans("URLRoot"),
					$langs->trans("DocumentRootServer"),
					$langs->trans("DataRootServer"),
					'separator',
					$langs->trans("DatabaseServer"),
					$langs->trans("DatabasePort"),
					$langs->trans("DatabaseName"),
					$langs->trans("DriverType"),
					$langs->trans("User"),
					$langs->trans("Password"),
					$langs->trans("DBStoringCharset"),
					$langs->trans("DBSortingCharset"),
					'separator',
					$langs->trans("AuthenticationMode"),
					'separator',
					'dolibarr_main_auth_ldap_login_attribute',
					'dolibarr_main_auth_ldap_host',
					'dolibarr_main_auth_ldap_port',
					'dolibarr_main_auth_ldap_version',
					'dolibarr_main_auth_ldap_dn',
					'dolibarr_main_auth_ldap_admin_login',
					'dolibarr_main_auth_ldap_admin_pass',
					'dolibarr_main_auth_ldap_debug',
					'separator',
					$langs->trans("SmartyLibs"),
					$langs->trans("SmartyCompile"),
					$langs->trans("SmartyCache")
					);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="280">'.$langs->trans("ConfigurationFile").'</td>';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>'."\n";
$i=0;
foreach($configfileparameters as $key)
{
	$var=!$var;
	print "<tr ".$bc[$var].">";
	if ($key == 'separator')
	{
		print '<td colspan="3">&nbsp;</td>';
	}
	else
	{
		print "<td>".$configfilelib[$i].'</td><td>'.$key.'</td>';
		print "<td>";
		if ($key == 'dolibarr_main_db_pass') print eregi_replace('.','*',${$key});
		else print ${$key};
		// TODO Afficher charset effectif de base $db
		if ($key == 'dolibarr_main_db_charset')
		{


		}
		print "</td>";
	}
	print "</tr>\n";
	$i++;
}
print '</table>';
print '<br>';


llxFooter('$Date$ - $Revision$');
?>
