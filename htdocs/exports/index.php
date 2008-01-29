<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/exports/export.class.php");

$langs->load("exports");

if (! $user->societe_id == 0)
  accessforbidden();

	  

$export=new Export($db);
$export->load_arrays($user);

 
llxHeader('',$langs->trans("ExportsArea"));

print_fiche_titre($langs->trans("ExportsArea"));

print $langs->trans("FormatedExportDesc1").'<br>';
print $langs->trans("FormatedExportDesc2").' ';
print $langs->trans("FormatedExportDesc3").'<br>';
print '<br>';

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

include_once(DOL_DOCUMENT_ROOT.'/includes/modules/export/modules_export.php');
$model=new ModeleExports();
$liste=$model->liste_modeles($db);

foreach($liste as $key)
{
    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$model->getDriverLabel($key).'</td>';
    print '<td>'.$model->getLibLabel($key).'</td>';
    print '<td nowrap="nowrap" align="center">'.$model->getLibVersion($key).'</td>';
    print '</tr>';
}

print '</table>';


print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


// Affiche les modules d'exports
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("ExportableDatas").'</td>';
//print '<td>&nbsp;</td>';
print '</tr>';
$val=true;
if (sizeof($export->array_export_code))
{
    foreach ($export->array_export_code as $key => $value)
    {
        $val=!$val;
        print '<tr '.$bc[$val].'><td>';
        print img_object($export->array_export_module[$key]->getName(),$export->array_export_module[$key]->picto).' ';
        print $export->array_export_module[$key]->getName();
        print '</td><td>';
        $string=$langs->trans($export->array_export_label[$key]);
        print ($string!=$export->array_export_label[$key]?$string:$export->array_export_label[$key]);
        print '</td>';
//        print '<td width="24">';
//        print '<a href="'.DOL_URL_ROOT.'/exports/export.php?step=2&amp;datatoexport='.$export->array_export_code[$key].'&amp;action=cleanselect">'.img_picto($langs->trans("NewExport"),'filenew').'</a>';
//        print '</td>';
        print '</tr>';

    }

    print '<tr class="total"><td class="total" colspan="2" align="center"><form action="'.DOL_URL_ROOT.'/exports/export.php?leftmenu=export"><input type="submit" class="button" value="'.$langs->trans("NewExport").'"></form></td></tr>';
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
