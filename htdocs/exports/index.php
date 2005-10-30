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
        \file       htdocs/exports/index.php
        \ingroup    core
        \brief      Page accueil de la zone export
        \version    $Revision$
*/
 
require("./pre.inc.php");

$langs->load("commercial");
$langs->load("orders");

$user->getrights();

if (! $user->societe_id == 0)
  accessforbidden();
	  


$dir=DOL_DOCUMENT_ROOT."/includes/modules";
$handle=opendir($dir);

// Recherche des exports disponibles
$array_export_code=array();
$var=True;
$i=0;
while (($file = readdir($handle))!==false)
{
    if (substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
    {
        if (eregi("^(mod.*)\.class\.php",$file,$reg))
        {
            $modulename=$reg[1];

            // Chargement de la classe
            $file = $dir."/".$modulename.".class.php";
            $classname = $modulename;
            require_once($file);
            $module = new $classname($db);
            
            if (is_array($module->export_code))
            {
                foreach($module->export_code as $r => $value)
                {
                    dolibarr_syslog("Exports trouvés pour le module ".$modulename);
                    $perm=$module->export_permission[$r][0];
                    if (strlen($perms[2]) > 0)
                    {
                        $bool=$user->rights->$perm[0]->$perm[1]->$perm[2];
                    }
                    else
                    {
                        $bool=$user->rights->$perm[0]->$perm[1];
                    }
                    if ($bool)
                    {
                        $array_export_module[$i]=$module;
                        $array_export_code[$i]=$module->export_code[$r];
                        $array_export_label[$i]=$module->export_label[$r];
                        $array_export_fields_code[$i]=$module->export_fields_code[$r];
                        $array_export_fields_label[$i]=$module->export_fields_label[$r];
                        $array_export_sql[$i]=$module->export_sql[$r];
                        $i++;
                    }
                }            
            }
        }
    }
}
closedir($handle);



 
llxHeader('',$langs->trans("ExportsArea"));

print_fiche_titre($langs->trans("ExportsArea"));

print '<table class="notopnoleftnoright" width="100%">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';


// Liste des formats d'exports disponibles
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryUsed").'</td>';
print '<td>'.$langs->trans("LibraryVersion").'</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>Excel</td><td>Php_WriteExcel</td>';
print '<td>&nbsp;</td>';
print '</tr>';
$var=!$var;
print '<tr '.$bc[$var].'><td>Csv</td><td>Dolibarr</td>';
print '<td>&nbsp;</td>';
print '</tr>';
print '</table>';


print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


// Affiche les modules d'exports
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("ExportableDatas").'</td>';
print '</tr>';
$val=true;
if (sizeof($array_export_code))
{
    foreach ($array_export_code as $key => $value)
    {
        $val=!$val;
        print '<tr '.$bc[$val].'><td>';
        print img_object($array_export_module[$key]->getName(),$array_export_module[$key]->picto).' ';
        print $array_export_module[$key]->getName();
        print '</td><td>';
        print $array_export_label[$key];
        print '</td></tr>';
    
    }
}
else
{
    print '<tr><td '.$bc[false].' colspan="2">'.$langs->trans("NoExportableData").'</td></tr>';
}
print '</table>';    


// Affiche les profils d'exports
$sql  = "SELECT rowid, label, public, fk_user, ".$db->pdate("datec");
$sql .= " FROM ".MAIN_DB_PREFIX."export as e";
$result=$db->query($sql);
if ($result) 
{
    print '<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td colspan="2">'.$langs->trans("ExportProfiles").'</td>';
    print '<td align="right">'.$langs->trans("Public").'</td></tr>';

    $num = $db->num_rows($result);
    if ($num > 0)
    {
        $var = true;
        $i = 0;

        while ($i < $num )
        {
            $obj = $db->fetch_object($result);
            $var=!$var;

            print "<tr $bc[$var]>";
            print '<td>'.$obj->label.'</td>';
            print '<td align="center">'.$yn($obj->public).'</td>';
            print '</tr>';
            $i++;
        }
    }

    print "</table>";
}


print '</td></tr>';
print '</table>';

$db->close();


llxFooter('$Date$ - $Revision$');

?>
