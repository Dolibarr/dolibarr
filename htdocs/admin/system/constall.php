<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis.houssin@capnetworks.com>
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
 *		\file 		htdocs/admin/system/constall.php
 *		\brief      Page to show all Dolibarr setup (config file and database constants)
 */

require '../../main.inc.php';

$langs->load("admin");
$langs->load("user");
$langs->load("install");


if (!$user->admin)
  accessforbidden();


/*
 * View
 */

llxHeader();

print load_fiche_titre($langs->trans("SummaryConst"),'','title_setup');


print load_fiche_titre($langs->trans("ConfigurationFile").' ('.$conffiletoshowshort.')');
// Parameters in conf.php file (when a parameter start with ?, it is shown only if defined)
$configfileparameters=array(
							'dolibarr_main_url_root',
							'dolibarr_main_url_root_alt',
							'dolibarr_main_document_root',
							'dolibarr_main_document_root_alt',
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
							'?dolibarr_main_db_prefix',
							'separator',
							'dolibarr_main_authentication',
							'separator',
							'?dolibarr_main_auth_ldap_login_attribute',
							'?dolibarr_main_auth_ldap_host',
							'?dolibarr_main_auth_ldap_port',
							'?dolibarr_main_auth_ldap_version',
							'?dolibarr_main_auth_ldap_dn',
							'?dolibarr_main_auth_ldap_admin_login',
							'?dolibarr_main_auth_ldap_admin_pass',
							'?dolibarr_main_auth_ldap_debug',
                            'separator',
                            '?dolibarr_lib_ADODB_PATH',
							'?dolibarr_lib_FPDF_PATH',
	                        '?dolibarr_lib_TCPDF_PATH',
							'?dolibarr_lib_FPDI_PATH',
                            '?dolibarr_lib_TCPDI_PATH',
							'?dolibarr_lib_NUSOAP_PATH',
                            '?dolibarr_lib_PHPEXCEL_PATH',
                            '?dolibarr_lib_GEOIP_PATH',
							'?dolibarr_lib_ODTPHP_PATH',
                            '?dolibarr_lib_ODTPHP_PATHTOPCLZIP',
						    '?dolibarr_js_CKEDITOR',
						    '?dolibarr_js_JQUERY',
						    '?dolibarr_js_JQUERY_UI',
						    '?dolibarr_js_JQUERY_FLOT',
							'?dolibarr_font_DOL_DEFAULT_TTF',
                            '?dolibarr_font_DOL_DEFAULT_TTF_BOLD',
							'separator',
							'?dolibarr_mailing_limit_sendbyweb',
							'?dolibarr_mailing_limit_sendbycli',
                            '?dolibarr_strict_mode'
						);
$configfilelib=array(
//					'separator',
					$langs->trans("URLRoot"),
					$langs->trans("URLRoot").' (alt)',
					$langs->trans("DocumentRootServer"),
					$langs->trans("DocumentRootServer").' (alt)',
					$langs->trans("DataRootServer"),
					'separator',
					$langs->trans("DatabaseServer"),
					$langs->trans("DatabasePort"),
					$langs->trans("DatabaseName"),
					$langs->trans("DriverType"),
					$langs->trans("DatabaseUser"),
					$langs->trans("DatabasePassword"),
					$langs->trans("DBStoringCharset"),
					$langs->trans("DBSortingCharset"),
					$langs->trans("Prefix"),
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
                    'dolibarr_lib_ADODB_PATH',
                    'dolibarr_lib_TCPDF_PATH',
                    'dolibarr_lib_FPDI_PATH',
					'dolibarr_lib_NUSOAP_PATH',
                    'dolibarr_lib_PHPEXCEL_PATH',
                    'dolibarr_lib_GEOIP_PATH',
					'dolibarr_lib_ODTPHP_PATH',
                    'dolibarr_lib_ODTPHP_PATHTOPCLZIP',
                    'dolibarr_js_CKEDITOR',
                    'dolibarr_js_JQUERY',
                    'dolibarr_js_JQUERY_UI',
                    'dolibarr_js_JQUERY_FLOT',
					'dolibarr_font_DOL_DEFAULT_TTF',
                    'dolibarr_font_DOL_DEFAULT_TTF_BOLD',
					'separator',
					'Limit nb of email sent by page',
					'Strict mode is on/off'
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
	$ignore=0;

	if ($key == 'dolibarr_main_url_root_alt' && empty(${$key})) $ignore=1;
	if ($key == 'dolibarr_main_document_root_alt' && empty(${$key})) $ignore=1;

	if (empty($ignore))
	{
        $newkey = preg_replace('/^\?/','',$key);

        if (preg_match('/^\?/',$key) && empty(${$newkey}))
        {
            $i++;
            continue;    // We discard parametes starting with ?
        }

        if ($newkey == 'separator' && $lastkeyshown == 'separator')
        {
            $i++;
            continue;
        }

		print '<tr class="oddeven">';
		if ($newkey == 'separator')
		{
			print '<td colspan="3">&nbsp;</td>';
		}
		else
		{
			// Label
			print "<td>".$configfilelib[$i].'</td>';
			// Key
			print '<td>'.$newkey.'</td>';
			// Value
			print "<td>";
			if ($newkey == 'dolibarr_main_db_pass') print preg_replace('/./i','*',${$newkey});
			else if ($newkey == 'dolibarr_main_url_root' && preg_match('/__auto__/',${$newkey})) print ${$newkey}.' => '.constant('DOL_MAIN_URL_ROOT');
			else print ${$newkey};
			if ($newkey == 'dolibarr_main_url_root' && ${$newkey} != DOL_MAIN_URL_ROOT) print ' (currently overwritten by autodetected value: '.DOL_MAIN_URL_ROOT.')';
			print "</td>";
		}
		print "</tr>\n";
		$lastkeyshown=$newkey;
	}
	$i++;
}
print '</table>';
print '<br>';



// Parameters in database
print load_fiche_titre($langs->trans("Database"));
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

	while ($i < $num)
    {
    	$obj = $db->fetch_object($resql);

    	print '<tr class="oddeven">';
    	print '<td>'.$obj->name.'</td>'."\n";
    	print '<td>'.$obj->value.'</td>'."\n";
    	if (empty($conf->multicompany->enabled) || !$user->entity) print '<td>'.$obj->entity.'</td>'."\n";	// If superadmin or multicompany disabled
    	print "</tr>\n";

    	$i++;
    }
}

print '</table>';


llxFooter();

$db->close();
