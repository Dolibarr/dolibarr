<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/admin/imports/index.php
 *       \ingroup    core
 *       \brief      Page accueil de la zone import
 *       \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/imports/import.class.php");

$langs->load("exports");

if (! $user->societe_id == 0)
  accessforbidden();

$import=new Import($db);
$import->load_arrays($user);


/*
 * View
 */

llxHeader('',$langs->trans("ImportArea"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

print_fiche_titre($langs->trans("ImportArea"));

print $langs->trans("FormatedImportDesc1").'<br>';
print $langs->trans("FormatedImportDesc2").'<br>';
print '<br>';

print '<table class="notopnoleftnoright" width="100%">';

print '<tr><td valign="top" width="40%" class="notopnoleft">';


// Liste des formats d'imports disponibles
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryShort").'</td>';
print '<td align="right">'.$langs->trans("LibraryVersion").'</td>';
print '</tr>';

include_once(DOL_DOCUMENT_ROOT.'/includes/modules/import/modules_import.php');
$model=new ModeleImports();
$liste=$model->liste_modeles($db);

foreach($liste as $key)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td width="16">'.img_picto_common($model->getDriverLabel($key),$model->getPicto($key)).'</td>';
    print '<td>'.dol_trunc($model->getDriverLabel($key),24).'</td>';
    print '<td>'.$model->getLibLabel($key).'</td>';
    print '<td nowrap="nowrap" align="right">'.$model->getLibVersion($key).'</td>';
    print '</tr>';
}

print '</table>';


print '</td><td valign="top" width="60%" class="notopnoleftnoright">';


// Affiche les modules d'imports
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("ImportableDatas").'</td>';
//print '<td>&nbsp;</td>';
print '</tr>';
$val=true;
if (sizeof($import->array_import_code))
{
    foreach ($import->array_import_code as $key => $value)
    {
        $val=!$val;
        print '<tr '.$bc[$val].'><td>';
        print img_object($import->array_import_module[$key]->getName(),$import->array_import_module[$key]->picto).' ';
        print $import->array_import_module[$key]->getName();
        print '</td><td>';
        $string=$langs->trans($import->array_import_label[$key]);
        print ($string!=$import->array_import_label[$key]?$string:$import->array_import_label[$key]);
        print '</td>';
//        print '<td width="24">';
//        print '<a href="'.DOL_URL_ROOT.'/imports/import.php?step=2&amp;datatoimport='.$import->array_import_code[$key].'&amp;action=cleanselect">'.img_picto($langs->trans("NewImport"),'filenew').'</a>';
//        print '</td>';
        print '</tr>';

    }
}
else
{
    print '<tr><td '.$bc[false].' colspan="2">'.$langs->trans("NoImportableData").'</td></tr>';
}
print '</table>';

if (sizeof($import->array_import_code))
{
	print '<center><form action="'.DOL_URL_ROOT.'/imports/import.php?leftmenu=import"><input type="submit" class="button" value="'.$langs->trans("NewImport").'"></form></center>';
}

/*
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
*/

print '</td></tr>';
print '</table>';

$db->close();


llxFooter('$Date$ - $Revision$');

?>
