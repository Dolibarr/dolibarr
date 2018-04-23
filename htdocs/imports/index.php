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
 *       \file       htdocs/imports/index.php
 *       \ingroup    import
 *       \brief      Home page of import wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/imports/class/import.class.php';

$langs->load("exports");

if (! $user->societe_id == 0)
  accessforbidden();

$import=new Import($db);
$import->load_arrays($user);


/*
 * View
 */

$form=new Form($db);

llxHeader('',$langs->trans("ImportArea"),'EN:Module_Imports_En|FR:Module_Imports|ES:M&oacute;dulo_Importaciones');

print load_fiche_titre($langs->trans("ImportArea"));

print $langs->trans("FormatedImportDesc1").'<br>';
//print $langs->trans("FormatedImportDesc2").'<br>';
print '<br>';


//print '<div class="fichecenter"><div class="fichehalfleft">';


// List of import set
/*
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Module").'</td>';
print '<td>'.$langs->trans("ImportableDatas").'</td>';
//print '<td>&nbsp;</td>';
print '</tr>';
$val=true;
if (count($import->array_import_code))
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
print '<br>';
*/

print '<div class="center">';
if (count($import->array_import_code))
{
	//if ($user->rights->import->run)
	//{
	print '<a class="butAction" href="'.DOL_URL_ROOT.'/imports/import.php?leftmenu=import">'.$langs->trans("NewImport").'</a>';
	//}
	//else
	//{
	//	print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans("NewImport").'</a>';
	//}
}
print '</div>';
print '<br>';


//print '</div><div class="fichehalfright"><div class="ficheaddleft">';


// List of available import format
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("AvailableFormats").'</td>';
print '<td>'.$langs->trans("LibraryShort").'</td>';
print '<td align="right">'.$langs->trans("LibraryVersion").'</td>';
print '</tr>';

include_once DOL_DOCUMENT_ROOT.'/core/modules/import/modules_import.php';
$model=new ModeleImports();
$liste=$model->liste_modeles($db);

foreach($liste as $key)
{

	print '<tr class="oddeven">';
	print '<td width="16">'.img_picto_common($model->getDriverLabelForKey($key),$model->getPictoForKey($key)).'</td>';
	$text=$model->getDriverDescForKey($key);
	print '<td>'.$form->textwithpicto($model->getDriverLabelForKey($key),$text).'</td>';
	print '<td>'.$model->getLibLabelForKey($key).'</td>';
	print '<td class="nowrap" align="right">'.$model->getLibVersionForKey($key).'</td>';
	print '</tr>';
}

print '</table>';


//print '</div></div></div>';


llxFooter();

$db->close();
