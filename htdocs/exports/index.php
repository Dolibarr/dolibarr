<?php
/* Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/exports/index.php
 *       \ingroup    export
 *       \brief      Home page of export wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';

// Load translation files required by the page
$langs->load("exports");

// Security check
$result=restrictedArea($user,'export');

$export=new Export($db);
$export->load_arrays($user);

/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("ExportsArea"),'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones');

print load_fiche_titre($langs->trans("ExportsArea"));

print $langs->trans("FormatedExportDesc1").'<br>';
//print $langs->trans("FormatedExportDesc2").' ';
//print $langs->trans("FormatedExportDesc3").'<br>';
print '<br>';


//print '<div class="fichecenter"><div class="fichehalfleft">';


// List export set
/*
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("ExportableDatas").'</td>';
//print '<td>&nbsp;</td>';
print '</tr>';
if (count($export->array_export_code))
{
	foreach ($export->array_export_code as $key => $value)
	{

		print '<tr class="oddeven"><td>';
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
print '<br>';
*/

print '<div class="center">';
if (count($export->array_export_code))
{
	if ($user->rights->export->creer)
	{
		print '<a class="butActionNew" href="'.DOL_URL_ROOT.'/exports/export.php?leftmenu=export">'.$langs->trans("NewExport").'<span class="fa fa-plus-circle valignmiddle"></span></a>';
	}
	else
	{
		print '<a class="butActionNewRefused" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("NewExport").'<span class="fa fa-plus-circle valignmiddle"></span></a>';
	}
	/*
	 print '<form action="'.DOL_URL_ROOT.'/exports/export.php?leftmenu=export"><input type="submit" class="button" value="'.$langs->trans("NewExport").'"';
	print ($user->rights->export->creer?'':' disabled');
	print '><div class="center"></div></form>';
	*/
}
print '</div>';
print '<br>';

//print '</div><div class="fichehalfright"><div class="ficheaddleft">';


// List of available export formats
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryShort").'</td>';
print '<td align="right">'.$langs->trans("LibraryVersion").'</td>';
print '</tr>';

include_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
$model=new ModeleExports($db);
$liste=$model->liste_modeles($db);    // This is not a static method for exports because method load non static properties

foreach($liste as $key => $val)
{
    if (preg_match('/__\(Disabled\)__/',$liste[$key]))
    {
    	$liste[$key]=preg_replace('/__\(Disabled\)__/','('.$langs->transnoentitiesnoconv("Disabled").')',$liste[$key]);
    }

	print '<tr class="oddeven">';
	print '<td width="16">'.img_picto_common($model->getDriverLabelForKey($key),$model->getPictoForKey($key)).'</td>';
	$text=$model->getDriverDescForKey($key);
	$label=$liste[$key];
	print '<td>'.$form->textwithpicto($label,$text).'</td>';
	print '<td>'.$model->getLibLabelForKey($key).'</td>';
	print '<td class="nowrap" align="right">'.$model->getLibVersionForKey($key).'</td>';
	print '</tr>';
}

print '</table>';


//print '</div></div></div>';

// End of page
llxFooter();
$db->close();
