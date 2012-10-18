<?php
/* Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2012	Regis Houssin			<regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/admin/system/dolibarr.php
 *  \brief      Page to show Dolibarr informations
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("admin");
$langs->load("install");
$langs->load("other");

if (! $user->admin)
	accessforbidden();


/*
 * View
 */

$form=new Form($db);

llxHeader();

print_fiche_titre($langs->trans("InfoDolibarr"),'','setup');

// Version
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Version").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("VersionLastInstall").'</td><td>'.$conf->global->MAIN_VERSION_LAST_INSTALL.'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("VersionLastUpgrade").'</td><td>'.$conf->global->MAIN_VERSION_LAST_UPGRADE.'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("VersionProgram").'</td><td>'.DOL_VERSION;
// If current version differs from last upgrade
if (empty($conf->global->MAIN_VERSION_LAST_UPGRADE))
{
	// Compare version with last install database version (upgrades never occured)
	if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_INSTALL) print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_INSTALL));
}
else
{
	// Compare version with last upgrade database version
	if (DOL_VERSION != $conf->global->MAIN_VERSION_LAST_UPGRADE) print ' '.img_warning($langs->trans("RunningUpdateProcessMayBeRequired",DOL_VERSION,$conf->global->MAIN_VERSION_LAST_UPGRADE));
}
print '</td></tr>'."\n";
print '</table>';
print '<br>';

// Session
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Session").'</td><td colspan="2">'.$langs->trans("Value").'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("SessionSavePath").'</td><td colspan="2">'.session_save_path().'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("SessionName").'</td><td colspan="2">'.session_name().'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("SessionId").'</td><td colspan="2">'.session_id().'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentSessionTimeOut").'</td><td>'.ini_get('session.gc_maxlifetime').' '.$langs->trans("seconds");
print '</td><td align="right">';
print $form->textwithpicto('',$langs->trans("SessionExplanation",ini_get("session.gc_probability"),ini_get("session.gc_divisor")));
print "</td></tr>\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentTheme").'</td><td colspan="2">'.$conf->theme.'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentMenuHandler").'</td><td colspan="2">';
if (preg_match('/^smartphone/',$conf->smart_menu) && ! empty($conf->browser->phone)) print $conf->smart_menu;
else print $conf->top_menu;
print '</td></tr>'."\n";
print '</table>';
print '<br>';


// Shmop
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x02))
{
	$shmoparray=dol_listshmop();

	$var=true;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("LanguageFilesCachedIntoShmopSharedMemory").'</td>';
	print '<td>'.$langs->trans("NbOfEntries").'</td>';
	print '<td align="right">'.$langs->trans("Address").'</td>';
	print '</tr>'."\n";

	foreach($shmoparray as $key => $val)
	{
		$var=!$var;
		print '<tr '.$bc[$var].'><td width="300">'.$key.'</td>';
		print '<td>'.count($val).'</td>';
		print '<td align="right">'.dol_getshmopaddress($key).'</td>';
		print '</tr>'."\n";
	}

	print '</table>';
	print '<br>';
}


// Localisation
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("LocalisationDolibarrParameters").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("LanguageBrowserParameter","HTTP_ACCEPT_LANGUAGE").'</td><td>'.$_SERVER["HTTP_ACCEPT_LANGUAGE"].'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentUserLanguage").'</td><td>'.$langs->getDefaultLang().'</td></tr>'."\n";
// Thousands
$var=!$var;
$thousand=$langs->trans("SeparatorThousand");
if ($thousand == 'SeparatorThousand') $thousand=' ';	// ' ' does not work on trans method
if ($thousand == 'None') $thousand='';
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentValueSeparatorThousand").'</td><td>'.($thousand==' '?$langs->trans("Space"):$thousand).'</td></tr>'."\n";
// Decimals
$var=!$var;
$dec=$langs->trans("SeparatorDecimal");
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentValueSeparatorDecimal").'</td><td>'.$dec.'</td></tr>'."\n";
// Show results of functions to see if everything works
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => price2num(1233.56+1)</td><td>'.price2num(1233.56+1,'2').'</td></tr>';
$var=!$var;
print "<tr ".$bc[$var].'><td width=\"300\">&nbsp; => price2num('."'1".$thousand."234".$dec."56')</td><td>".price2num("1".$thousand."234".$dec."56",'2')."</td>";
if (($thousand != ',' && $thousand != '.') || ($thousand != ' '))
{
	$var=!$var;
	print "<tr ".$bc[$var].'><td width=\"300\">&nbsp; => price2num('."'1 234.56')</td><td>".price2num("1 234.56",'2')."</td>";
	print "</tr>\n";
}
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => price(1234.56)</td><td>'.price(1234.56).'</td>';
// Timezone
$txt =$langs->trans("OSTZ").' (variable system TZ): '.(! empty($_ENV["TZ"])?$_ENV["TZ"]:$langs->trans("NotDefined")).'<br>'."\n";
$txt.=$langs->trans("PHPTZ").' (php.ini date.timezone): '.(ini_get("date.timezone")?ini_get("date.timezone"):$langs->trans("NotDefined")).''."\n"; // date.timezone must be in valued defined in http://fr3.php.net/manual/en/timezones.europe.php
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentTimeZone").'</td><td>';	// Timezone server PHP
$a=getServerTimeZoneInt('now');
$b=getServerTimeZoneInt('winter');
$c=getServerTimeZoneInt('summer');
$daylight=(is_numeric($c) && is_numeric($b))?round($c-$b):'unknown';
//print $a." ".$b." ".$c." ".$daylight;
$val=($a>=0?'+':'').$a;
$val.=' ('.($a==='unknown'?'unknown':($a>=0?'+':'').($a*3600)).')';
$val.=' &nbsp; &nbsp; &nbsp; '.getServerTimeZoneString();
$val.=' &nbsp; &nbsp; &nbsp; '.$langs->trans("DaylingSavingTime").': '.($daylight==='unknown'?'unknown':yn($daylight));
print $form->textwithtooltip($val,$txt,2,1,img_info(''));
print '</td></tr>'."\n";	// value defined in http://fr3.php.net/manual/en/timezones.europe.php
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => '.$langs->trans("CurrentHour").'</td><td>'.dol_print_date(dol_now(),'dayhour','tzserver').'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => dol_print_date(0,"dayhourtext")</td><td>'.dol_print_date(0,"dayhourtext").'</td>';
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => dol_get_first_day(1970,1,false)</td><td>'.dol_get_first_day(1970,1,false).' &nbsp; &nbsp; (=> dol_print_date() or idate() of this value = '.dol_print_date(dol_get_first_day(1970,1,false),'dayhour').')</td>';
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => dol_get_first_day(1970,1,true)</td><td>'.dol_get_first_day(1970,1,true).' &nbsp; &nbsp; (=> dol_print_date() or idate() of this value = '.dol_print_date(dol_get_first_day(1970,1,true),'dayhour').')</td>';
// Parent company
/*
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CompanyTZ").'</td><td>'.$langs->trans("FeatureNotYetAvailable").'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => '.$langs->trans("CompanyHour").'</td><td>'.$langs->trans("FeatureNotYetAvailable").'</td></tr>'."\n";
*/
// Client
$var=!$var;
$tz=(int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst'];
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("ClientTZ").'</td><td>'.($tz?($tz>=0?'+':'').$tz:'').' ('.($tz>=0?'+':'').($tz*60*60).')';
print ' &nbsp; &nbsp; &nbsp; '.$_SESSION['dol_tz_string'];
print ' &nbsp; &nbsp; &nbsp; '.$langs->trans("DaylingSavingTime").': ';
if ($_SESSION['dol_dst']>0) print yn(1);
else print yn(0);
if (! empty($_SESSION['dol_dst_first'])) print ' &nbsp; &nbsp; ('.dol_print_date(dol_stringtotime($_SESSION['dol_dst_first']),'dayhour','gmt').' - '.dol_print_date(dol_stringtotime($_SESSION['dol_dst_second']),'dayhour','gmt').')';
print '</td></tr>'."\n";
print '</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => '.$langs->trans("ClientHour").'</td><td>'.dol_print_date(dol_now(),'dayhour','tzuser').'</td></tr>'."\n";

$var=!$var;
$filesystemencoding=ini_get("unicode.filesystem_encoding");	// Disponible avec PHP 6.0
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("File encoding").' (php.ini unicode.filesystem_encoding)</td><td>'.$filesystemencoding.'</td></tr>'."\n";	// date.timezone must be in valued defined in http://fr3.php.net/manual/en/timezones.europe.php

$var=!$var;
$tmp=ini_get("unicode.filesystem_encoding");						// Disponible avec PHP 6.0
if (empty($tmp) && ! empty($_SERVER["WINDIR"])) $tmp='iso-8859-1';	// By default for windows
if (empty($tmp)) $tmp='utf-8';										// By default for other
if (! empty($conf->global->MAIN_FILESYSTEM_ENCODING)) $tmp=$conf->global->MAIN_FILESYSTEM_ENCODING;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => '.$langs->trans("File encoding").'</td><td>'.$tmp.'</td></tr>'."\n";	// date.timezone must be in valued defined in http://fr3.php.net/manual/en/timezones.europe.php

print '</table>';
print '<br>';



// Parameters in conf.php file (when a parameter start with ?, it is shown only if defined)
$configfileparameters=array(
		'dolibarr_main_url_root' => $langs->trans("URLRoot"),
		'dolibarr_main_url_root_alt' => $langs->trans("URLRoot").' (alt)',
		'dolibarr_main_document_root'=> $langs->trans("DocumentRootServer"),
		'dolibarr_main_document_root_alt' => $langs->trans("DocumentRootServer").' (alt)',
		'dolibarr_main_data_root' => $langs->trans("DataRootServer"),
		'separator' => '',
		'dolibarr_main_db_host' => $langs->trans("DatabaseServer"),
		'dolibarr_main_db_port' => $langs->trans("DatabasePort"),
		'dolibarr_main_db_name' => $langs->trans("DatabaseName"),
		'dolibarr_main_db_type' => $langs->trans("DriverType"),
		'dolibarr_main_db_user' => $langs->trans("DatabaseUser"),
		'dolibarr_main_db_pass' => $langs->trans("DatabasePassword"),
		'dolibarr_main_db_character_set' => $langs->trans("DBStoringCharset"),
		'dolibarr_main_db_collation' => $langs->trans("DBSortingCollation"),
		'?dolibarr_main_db_prefix' => $langs->trans("Prefix"),
		'separator' => '',
		'dolibarr_main_authentication' => $langs->trans("AuthenticationMode"),
		'separator'=> '',
		'?dolibarr_main_auth_ldap_login_attribute' => 'dolibarr_main_auth_ldap_login_attribute',
		'?dolibarr_main_auth_ldap_host' => 'dolibarr_main_auth_ldap_host',
		'?dolibarr_main_auth_ldap_port' => 'dolibarr_main_auth_ldap_port',
		'?dolibarr_main_auth_ldap_version' => 'dolibarr_main_auth_ldap_version',
		'?dolibarr_main_auth_ldap_dn' => 'dolibarr_main_auth_ldap_dn',
		'?dolibarr_main_auth_ldap_admin_login' => 'dolibarr_main_auth_ldap_admin_login',
		'?dolibarr_main_auth_ldap_admin_pass' => 'dolibarr_main_auth_ldap_admin_pass',
		'?dolibarr_main_auth_ldap_debug' => 'dolibarr_main_auth_ldap_debug',
		'separator' => '',
		'?dolibarr_lib_ADODB_PATH' => 'dolibarr_lib_ADODB_PATH',
		'?dolibarr_lib_TCPDF_PATH' => 'dolibarr_lib_TCPDF_PATH',
		'?dolibarr_lib_FPDI_PATH' => 'dolibarr_lib_FPDI_PATH',
		'?dolibarr_lib_NUSOAP_PATH' => 'dolibarr_lib_NUSOAP_PATH',
		'?dolibarr_lib_PHPEXCEL_PATH' => 'dolibarr_lib_PHPEXCEL_PATH',
		'?dolibarr_lib_GEOIP_PATH' => 'dolibarr_lib_GEOIP_PATH',
		'?dolibarr_lib_ODTPHP_PATH' => 'dolibarr_lib_ODTPHP_PATH',
		'?dolibarr_lib_ODTPHP_PATHTOPCLZIP' => 'dolibarr_lib_ODTPHP_PATHTOPCLZIP',
		'?dolibarr_js_CKEDITOR' => 'dolibarr_js_CKEDITOR',
		'?dolibarr_js_JQUERY' => 'dolibarr_js_JQUERY',
		'?dolibarr_js_JQUERY_UI' => 'dolibarr_js_JQUERY_UI',
		'?dolibarr_js_JQUERY_FLOT' => 'dolibarr_js_JQUERY_FLOT',
		'?dolibarr_font_DOL_DEFAULT_TTF' => 'dolibarr_font_DOL_DEFAULT_TTF',
		'?dolibarr_font_DOL_DEFAULT_TTF_BOLD' => 'dolibarr_font_DOL_DEFAULT_TTF_BOLD',
		'separator' => '',
		'?dolibarr_mailing_limit_sendbyweb' => 'Limit nb of email sent by page',
		'?dolibarr_strict_mode' => 'Strict mode is on/off'
);

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td width="300">'.$langs->trans("Parameters").' ';
print $langs->trans("ConfigurationFile").' ('.$conffiletoshowshort.')';
print '</td>';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>'."\n";

foreach($configfileparameters as $key => $value)
{
	$ignore=0;

	if ($key == 'dolibarr_main_url_root_alt' && empty(${$key})) $ignore=1;
	if ($key == 'dolibarr_main_document_root_alt' && empty(${$key})) $ignore=1;

	if (empty($ignore))
	{
		$newkey = preg_replace('/^\?/','',$key);

		if (preg_match('/^\?/',$key) && empty(${$newkey})) continue;    // We discard parametes starting with ?
		if ($newkey == 'separator' && $lastkeyshown == 'separator') continue;

		$var=!$var;
		print "<tr ".$bc[$var].">";
		if ($newkey == 'separator')
		{
			print '<td colspan="3">&nbsp;</td>';
		}
		else
		{
			// Label
			print "<td>".$value.'</td>';
			// Key
			print '<td>'.$newkey.'</td>';
			// Value
			print "<td>";
			if ($newkey == 'dolibarr_main_db_pass') print preg_replace('/./i','*',${$newkey});
			else if ($newkey == 'dolibarr_main_url_root' && preg_match('/__auto__/',${$newkey})) print ${$newkey}.' => '.constant('DOL_MAIN_URL_ROOT');
			else if ($newkey == 'dolibarr_main_url_root_alt' && preg_match('/__auto__/',${$newkey})) print ${$newkey}.' => '.constant('DOL_MAIN_URL_ROOT_ALT');
			else print ${$newkey};
			if ($newkey == 'dolibarr_main_url_root' && $newkey != DOL_MAIN_URL_ROOT) print ' (currently overwritten by autodetected value: '.DOL_MAIN_URL_ROOT.')';
			print "</td>";
		}
		print "</tr>\n";
		$lastkeyshown=$newkey;
	}
}
print '</table>';
print '<br>';



// Parameters in database
print '<table class="noborder">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").' '.$langs->trans("Database").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
if (empty($conf->multicompany->enabled) || !$user->entity) print '<td align="center">'.$langs->trans("Entity").'</td>';	// If superadmin or multicompany disabled
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
		if (empty($conf->multicompany->enabled) || !$user->entity) print '<td align="center">'.$obj->entity.'</td>'."\n";	// If superadmin or multicompany disabled
		print "</tr>\n";

		$i++;
	}
}

print '</table>';



llxFooter();

$db->close();
?>