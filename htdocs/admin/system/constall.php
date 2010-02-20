<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *		\file 		htdocs/admin/system/constall.php
 *		\brief      Page d'info de toutes les constantes
 *		\version    $Id$
 */

require("./pre.inc.php");

$langs->load("admin");
$langs->load("user");
$langs->load("install");


if (!$user->admin)
  accessforbidden();


/*
 * View
 */

llxHeader();

print_fiche_titre($langs->trans("SummaryConst"),'','setup');


print_titre($langs->trans("ConfigurationFile"));
// Parameters in conf.php file
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
							'dolibarr_main_auth_ldap_debug'
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
					'dolibarr_main_auth_ldap_debug'
					);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td width="280">'.$langs->trans("Label").'</td>';
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
		if ($key == 'dolibarr_main_db_pass') print preg_replace('/./i','*',${$key});
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



// Parameters in database
print_titre($langs->trans("Database"));
print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
if (empty($conf->multicompany->enabled) || !$user->entity) print '<td>'.$langs->trans("Entity").'</td>';	// If superadmin or multicompany disabled
print "</tr>\n";

$sql = "SELECT";
$sql.= " rowid";
$sql.= ", ".$db->decrypt('name')." as name";
$sql.= ", ".$db->decrypt('value')." as value";
$sql.= ", type";
$sql.= ", note";
$sql.= ", entity";
$sql.= " FROM ".MAIN_DB_PREFIX."const";
if (empty($conf->multicompany->enabled))
{
	// If no multicompany mode, admins can see global and their constantes
	$sql.= " WHERE entity IN (0,".$conf->entity.")";
}
else
{
	// If multicompany mode, superadmin (user->entity=0) can see everything, admin are limited to their entities.
	if ($user->entity) $sql.= " WHERE entity IN (".$user->entity.",".$conf->entity.")";
}
$sql.= " ORDER BY entity, name ASC";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$var=True;

	while ($i < $num)
    {
    	$obj = $db->fetch_object($resql);
    	$var=!$var;

    	print '<tr '.$bc[$var].'>';
    	print '<td>'.$obj->name.'</td>'."\n";
    	print '<td>'.$obj->value.'</td>'."\n";
    	if (empty($conf->multicompany->enabled) || !$user->entity) print '<td>'.$obj->entity.'</td>'."\n";	// If superadmin or multicompany disabled
    	print "</tr>\n";

    	$i++;
    }
}

print '</table>';

$db->close();

llxFooter();
?>
