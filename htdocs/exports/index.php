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
 *       \file       htdocs/exports/index.php
 *       \ingroup    core
 *       \brief      Page accueil de la zone export
 *       \version    $Id$
 */

require_once("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/exports/export.class.php");

$langs->load("exports");

if (! $user->societe_id == 0)
  accessforbidden();



$export=new Export($db);
$export->load_arrays($user);


/*
 * View
 */

llxHeader('',$langs->trans("ExportsArea"),'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones');

print_fiche_titre($langs->trans("ExportsArea"));

print $langs->trans("FormatedExportDesc1").'<br>';
print $langs->trans("FormatedExportDesc2").' ';
print $langs->trans("FormatedExportDesc3").'<br>';
print '<br>';

print '<table class="notopnoleftnoright" width="100%">';

print '<tr><td valign="top" width="40%" class="notopnoleft">';


// Liste des formats d'exports disponibles
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryShort").'</td>';
print '<td align="right">'.$langs->trans("LibraryVersion").'</td>';
print '</tr>';

include_once(DOL_DOCUMENT_ROOT.'/includes/modules/export/modules_export.php');
$model=new ModeleExports();
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
        //print img_object($export->array_export_module[$key]->getName(),$export->array_export_module[$key]->picto).' ';
        print $export->array_export_module[$key]->getName();
        print '</td><td>';
        print img_object($export->array_export_module[$key]->getName(),$export->array_export_icon[$key]).' ';
        $string=$langs->trans($export->array_export_label[$key]);
        print ($string!=$export->array_export_label[$key]?$string:$export->array_export_label[$key]);
        print '</td>';
//        print '<td width="24">';
//        print '<a href="'.DOL_URL_ROOT.'/exports/export.php?step=2&amp;datatoexport='.$export->array_export_code[$key].'&amp;action=cleanselect">'.img_picto($langs->trans("NewExport"),'filenew').'</a>';
//        print '</td>';
        print '</tr>';

    }
}
else
{
    print '<tr><td '.$bc[false].' colspan="2">'.$langs->trans("NoExportableData").'</td></tr>';
}
print '</table>';

if (sizeof($export->array_export_code))
{
   	print '<center><form action="'.DOL_URL_ROOT.'/exports/export.php?leftmenu=export"><input type="submit" class="button" value="'.$langs->trans("NewExport").'"';
   	print ($user->rights->export->creer?'':' disabled="true"');
   	print '></form></center>';
}


print '</td></tr>';
print '</table>';

$db->close();


llxFooter('$Date$ - $Revision$');
?>
