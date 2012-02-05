<?php
/* Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/memory.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/date.lib.php");

$langs->load("admin");
$langs->load("install");
$langs->load("other");

if (!$user->admin)
  accessforbidden();


/*
 * View
 */

$form=new Form($db);

llxHeader();

print_fiche_titre("Dolibarr",'','setup');

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
if (preg_match('/^smartphone/',$conf->smart_menu) && isset($conf->browser->phone)) print $conf->smart_menu;
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
$txt =$langs->trans("OSTZ").' (variable system TZ): '.($_ENV["TZ"]?$_ENV["TZ"]:$langs->trans("NotDefined")).'<br>'."\n";
$txt.=$langs->trans("PHPTZ").' (php.ini date.timezone): '.(ini_get("date.timezone")?ini_get("date.timezone"):$langs->trans("NotDefined")).''."\n"; // date.timezone must be in valued defined in http://fr3.php.net/manual/en/timezones.europe.php
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CurrentTimeZone").'</td><td>';	// Timezone server PHP
$a=getCurrentTimeZoneString();
$a.=' '.getCurrentTimeZoneInt();
$a.=' ('.(-dol_mktime(0,0,0,1,1,1970)>0?'+':'').(-dol_mktime(0,0,0,1,1,1970)).')';
print $form->textwithtooltip($a,$txt,2,1,img_info(''));
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
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("CompanyTZ").'</td><td>'.$langs->trans("FeatureNotYetAvailable").'</td></tr>'."\n";
$var=!$var;
print '<tr '.$bc[$var].'><td width="300">&nbsp; => '.$langs->trans("CompanyHour").'</td><td>'.$langs->trans("FeatureNotYetAvailable").'</td></tr>'."\n";
// Client
$var=!$var;
$tz=(int) $_SESSION['dol_tz'] + (int) $_SESSION['dol_dst'];
print '<tr '.$bc[$var].'><td width="300">'.$langs->trans("ClientTZ").'</td><td>'.($tz?($tz>=0?'+':'').$tz:'').' ('.($tz>=0?'+':'').($tz*60*60).')';
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

llxFooter();
?>
