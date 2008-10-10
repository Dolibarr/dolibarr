<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       htdocs/admin/system/dolibarr.php
 *  \brief      Fichier page info systemes Dolibarr
 *  \version    $Id$
 */

require("./pre.inc.php");

$langs->load("admin");
$langs->load("install");
$langs->load("other");

if (!$user->admin)
  accessforbidden();
 
  
/*
 * View
 */
  
llxHeader();

print_fiche_titre("Dolibarr",'','setup');

print "<br>\n";

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Version").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("VersionProgram")."</td><td>".DOL_VERSION."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("VersionLastInstall")."</td><td>".$conf->global->MAIN_VERSION_LAST_INSTALL."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("VersionLastUpgrade")."</td><td>".$conf->global->MAIN_VERSION_LAST_UPGRADE."</td></tr>\n";
print '</table>';
print '<br>';

// Language
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("LocalisationDolibarrParameters").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("LanguageBrowserParameter","HTTP_ACCEPT_LANGUAGE")."</td><td>".$_SERVER["HTTP_ACCEPT_LANGUAGE"]."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("LanguageParameter","PHP LC_ALL")."</td><td>".setlocale(LC_ALL,0)."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("LanguageParameter","PHP LC_NUMERIC")."</td><td>".setlocale(LC_NUMERIC,0)."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("LanguageParameter","PHP LC_TIME")."</td><td>".setlocale(LC_TIME,0)."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("LanguageParameter","PHP LC_MONETARY")."</td><td>".setlocale(LC_MONETARY,0)."</td></tr>\n";
// Decimals
$var=!$var;
$dec=$langs->trans("SeparatorDecimal");
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentValueSeparatorDecimal")."</td><td>".$dec."</td></tr>\n";
$var=!$var;
$thousand=$langs->trans("SeparatorThousand");
if ($thousand == 'SeparatorThousand') $thousand=' ';	// ' ' does not work on trans method
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentValueSeparatorThousand")."</td><td>".$thousand."</td></tr>\n";
// Timezone
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("DolibarrTZ")."</td><td>".$langs->trans("FeatureNotYetAvailable")."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("ServerTZ")." (variable system TZ)</td><td>".$_ENV["TZ"]."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("PHPTZ")." (php.ini date.timezone)</td><td>".ini_get("date.timezone")."</td></tr>\n";	// date.timezone must be in valued defined in http://fr3.php.net/manual/en/timezones.europe.php
if (function_exists('date_default_timezone_get'))
{
	$var=!$var;
	print "<tr ".$bc[$var]."><td width=\"300\">=> ".$langs->trans("CurrentTimeZone")."</td><td>";
	print date_default_timezone_get();
	print "</td></tr>\n";	// value defined in http://fr3.php.net/manual/en/timezones.europe.php
}
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("PHPServerOffsetWithGreenwich")."</td><td>".(- dolibarr_mktime(0,0,0,1,1,1970))."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentHour")."</td><td>".dolibarr_print_date(time(),'dayhour')."</td></tr>\n";
print '</table>';
print '<br>';

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Session").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("SessionId")."</td><td>".session_id()."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentSessionTimeOut")."</td><td>".ini_get('session.gc_maxlifetime').' '.$langs->trans("seconds")."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentTheme")."</td><td>".$conf->theme."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentTopMenuHandler")."</td><td>".$conf->top_menu."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentLeftMenuHandler")."</td><td>".$conf->left_menu."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"300\">".$langs->trans("CurrentUserLanguage")."</td><td>".$langs->getDefaultLang()."</td></tr>\n";
print '</table>';
print '<br>';


// Charge les modules
$db->begin();

$dir = DOL_DOCUMENT_ROOT . "/includes/modules/";
$handle=opendir($dir);
$modules = array();
$modules_names = array();
$modules_files = array();
while (($file = readdir($handle))!==false)
{
    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, strlen($file) - 10) == '.class.php')
    {
        $modName = substr($file, 0, strlen($file) - 10);

        if ($modName)
        {
            include_once(DOL_DOCUMENT_ROOT."/includes/modules/".$file);
            $objMod = new $modName($db);

            $modules[$objMod->numero]=$objMod;
            $modules_names[$objMod->numero]=$objMod->name;
			$modules_files[$objMod->numero]=$file;
            $picto[$objMod->numero]=(isset($objMod->picto) && $objMod->picto)?$objMod->picto:'generic';
        }
    }
}
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Modules").'</td>';
print '<td>'.$langs->trans("Version").'</td>';
print '<td align="center">'.$langs->trans("Id Module").'</td>';
print '<td>'.$langs->trans("Id Permissions").'</td>';
print '</tr>';
$var=false;
$sortorder=$modules_names;
ksort($sortorder);
$rights_ids = array();
foreach($sortorder as $numero=>$name) 
{
    $idperms="";
    $var=!$var;
    // Module
    print "<tr $bc[$var]><td width=\"300\" nowrap=\"nowrap\">";
	$alt=$name.' - '.$modules_files[$numero];
	print img_object($alt,$picto[$numero]).' '.$modules[$numero]->getName();
	print "</td>";
    // Version
    print '<td>'.$modules[$numero]->getVersion().'</td>';
    // Id
    print '<td align="center">'.$numero.'</td>';
    // Permissions
    if ($modules[$numero]->rights)
    {
        foreach($modules[$numero]->rights as $rights)
        {
            $idperms.=($idperms?", ":"").$rights[0];
	    array_push($rights_ids, $rights[0]);
        }
    }
    print '<td>'.($idperms?$idperms:"&nbsp;").'</td>';
    print "</tr>\n";
}
print '</table>';
print '<br>';
sort($rights_ids);
foreach($rights_ids as $right_id)
{
  if ($old == $right_id)
    print "Attention doublon sur la permission : $right_id<br>";
  $old = $right_id;
}

llxFooter('$Date$ - $Revision$');
?>
