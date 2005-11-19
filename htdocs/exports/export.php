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
        \file       htdocs/exports/export.php
        \ingroup    core
        \brief      Page d'edition d'un export
        \version    $Revision$
*/
 
require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/exports/export.class.php");

$langs->load("exports");

$user->getrights();

if (! $user->societe_id == 0)
  accessforbidden();



$export=new Export($db);
$export->load_arrays($user,isset($datatoexport)?$datatoexport:'');


if (! isset($datatoexport))
{
    llxHeader('',$langs->trans("NewExport"));
    
    print_fiche_titre($langs->trans("NewExport"));
    
    print '<table class="notopnoleftnoright" width="100%">';

    print $langs->trans("SelectExportDataSet").'<br>';
    print '<br>';
    
    // Affiche les modules d'exports
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td width="120">'.$langs->trans("Module").'</td>';
    print '<td>'.$langs->trans("ExportableDatas").'</td>';
    print '<td>&nbsp;</td>';
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
            print $export->array_export_label[$key];
            print '</td><td>';
            print '<a href="'.DOL_URL_ROOT.'/exports/export.php?datatoexport='.$export->array_export_code[$key].'">'.img_picto($langs->trans("NewExport"),'filenew').'</a>';
            print '</td></tr>';
        }
    }
    else
    {
        print '<tr><td '.$bc[false].' colspan="2">'.$langs->trans("NoExportableData").'</td></tr>';
    }
    print '</table>';    

    print '</table>';
}

if (isset($datatoexport))
{
    llxHeader('',$langs->trans("NewExport"));
    
    print_fiche_titre($langs->trans("NewExport")." - ".$export->array_export_label[0]);
    
    print '<table class="notopnoleftnoright" width="100%">';

    print $langs->trans("SelectExportFields").'<br>';
    print '<br>';
    
    print '<table>';
    print '<tr><td>'.$langs->trans("ExportableFields").'</td>';
    print '<td>&nbsp;</td>';
    print '<td>'.$langs->trans("ExportedFields").'</td>';
    print '</tr>';

    print '<tr><td>';
    
    // Champs exportables
    $fieldscode=split(',',$export->array_export_fields_code);
    $fieldslib=split(',',$export->array_export_fields_lib);
    foreach($fieldscode as $i=>$code)
    {
                
        
    }
    
    print '</td></tr>';

    print '</table>';
}


$db->close();

llxFooter('$Date$ - $Revision$');

?>
