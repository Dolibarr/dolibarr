<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Maxime Kohlhaas         <maxime@atm-consulting.fr>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    bom/admin/setup.php
 * \ingroup bom
 * \brief   Bom setup page.
 */

// Load Dolibarr environment
require '../../main.inc.php';
// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/bom.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "mrp"));

// Access control
if (! $user->admin) accessforbidden();

// Parameters
$action = GETPOST('action', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters=array(
	'BILLOFMATERIALS_MYPARAM1'=>array('css'=>'minwidth200','enabled'=>1),
	'BILLOFMATERIALS_MYPARAM2'=>array('css'=>'minwidth500','enabled'=>1)
);


/*
 * Actions
 */
include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

/*
 * View
 */

$page_name = "BomSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="'.($backtopage?$backtopage:DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_bom');

// Configuration header
$head = bomAdminPrepareHead();
dol_fiche_head($head, 'settings', '', -1, "bom");

// Setup page goes here
echo $langs->trans("BomSetupPage").'<br><br>';


if ($action == 'edit')
{
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach($arrayofparameters as $key => $val)
	{
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
		print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css'])?'minwidth200':$val['css']).'" value="' . $conf->global->$key . '"></td></tr>';
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
}
else
{
	if (! empty($arrayofparameters))
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach($arrayofparameters as $key => $val)
		{
			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
			print '</td><td>' . $conf->global->$key . '</td></tr>';
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
		print '</div>';
	}
	else
	{
		print '<br>'.$langs->trans("NothingToSetup");
	}
}


// Page end
dol_fiche_end();

llxFooter();
$db->close();
