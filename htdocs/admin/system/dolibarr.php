<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/admin/system/dolibarr.php
        \brief      Fichier page info systemes Dolibarr
        \version    $Revision$
*/

require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();
 

llxHeader();


print_titre("Dolibarr");

print "<br>\n";

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
print "<tr $bc[0]><td width=\"240\">".$langs->trans("Version")."</td><td>".DOL_VERSION."</td></tr>\n";
print '</table>';
print '<br>';

$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>'.$langs->trans("LanguageDolibarrParameter").'</td><td>'.$langs->trans("Value").'</td></tr>'."\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"240\">".$langs->trans("LanguageBrowserParameter","HTTP_ACCEPT_LANGUAGE")."</td><td>".$_SERVER["HTTP_ACCEPT_LANGUAGE"]."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"240\">".$langs->trans("LanguageParameter","PHP LC_ALL")."</td><td>".setlocale(LC_ALL,0)."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"240\">".$langs->trans("LanguageParameter","PHP LC_NUMERIC")."</td><td>".setlocale(LC_NUMERIC,0)."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"240\">".$langs->trans("LanguageParameter","PHP LC_TIME")."</td><td>".setlocale(LC_TIME,0)."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"240\">".$langs->trans("LanguageParameter","PHP LC_MONETARY")."</td><td>".setlocale(LC_MONETARY,0)."</td></tr>\n";
$var=!$var;
print "<tr ".$bc[$var]."><td width=\"240\">".$langs->trans("CurrentDolibarrLanguage")."</td><td>".$langs->getDefaultLang()."</td></tr>\n";
print '</table>';
print '<br>';


// Charge les modules
$db->begin();

$dir = DOL_DOCUMENT_ROOT . "/includes/modules/";
$handle=opendir($dir);
$modules = array();
while (($file = readdir($handle))!==false)
{
    if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, strlen($file) - 10) == '.class.php')
    {
        $modName = substr($file, 0, strlen($file) - 10);

        if ($modName)
        {
            include_once("../../includes/modules/$file");
            $objMod = new $modName($db);

            $modules[$objMod->numero]=$objMod;
            $modules_names[$objMod->numero]=$objMod->name;

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
foreach($sortorder as $numero=>$name) 
{
    $idperms="";
    $var=!$var;
    // Module
    print "<tr $bc[$var]><td width=\"240\">".img_object("",$picto[$numero]).' '.$modules[$numero]->getName()."</td>";
    // Version
    print '<td>'.$modules[$numero]->getVersion().'</td>';
    // Id
    print '<td align="center">'.$numero.'</td>';
    // Permissions
    if ($modules[$numero]->rights)
    {
        foreach($modules[$numero]->rights as $rights)
        {
            $idperms.=($idperms?",":"").$rights[0];
        }
    }
    print '<td>'.($idperms?$idperms:"&nbsp;").'</td>';
    print "</tr>\n";
}
print '</table>';
print '<br>';


llxFooter('$Date$ - $Revision$');

?>
