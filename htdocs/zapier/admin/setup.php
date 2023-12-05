<?php
/* Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2019       Frédéric FRANCE         <frederic.france@free.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    zapier/admin/setup.php
 * \ingroup zapier
 * \brief   Zapier setup page.
 */

// Load Dolibarr environment
require '../../main.inc.php';

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/zapier/lib/zapier.lib.php';

// Translations
$langs->loadLangs(array('admin', 'zapier'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

$arrayofparameters = array(
//	'ZAPIERFORDOLIBARR_MYPARAM1'=>array('css'=>'minwidth200', 'enabled'=>1),
//	'ZAPIERFORDOLIBARR_MYPARAM2'=>array('css'=>'minwidth500', 'enabled'=>1)
);

if (!isModEnabled('zapier')) {
	accessforbidden();
}
if (empty($user->admin)) {
	accessforbidden();
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

/*
 * View
 */

$page_name = 'ZapierForDolibarrSetup';
$help_url = 'EN:Module_Zapier';
llxHeader('', $langs->trans($page_name), $help_url);

// Subheader
$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans($page_name), $linkback, 'object_zapier');

// Configuration header
$head = zapierAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', '', -1, "zapier");


if ($action == 'edit') {
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	foreach ($arrayofparameters as $key => $val) {
		print '<tr class="oddeven"><td>';
		print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
		print '</td><td><input name="'.$key.'"  class="flat '.(empty($val['css']) ? 'minwidth200' : $val['css']).'" value="'.getDolGlobalString($key).'"></td></tr>';
	}
	print '</table>';

	print '<br><div class="center">';
	print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
	print '</div>';

	print '</form>';
	print '<br>';
} else {
	if (!empty($arrayofparameters)) {
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

		foreach ($arrayofparameters as $key => $val) {
			print '<tr class="oddeven"><td>';
			print $form->textwithpicto($langs->trans($key), $langs->trans($key.'Tooltip'));
			print '</td><td>'.getDolGlobalString($key).'</td></tr>';
		}

		print '</table>';

		print '<div class="tabsAction">';
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
		print '</div>';
	} else {
		// Setup page goes here
		echo '<br><br><span class="opacitymediumdisabled">'.$langs->trans("ZapierSetupPage").'</span><br><br>';
		//print '<br>'.$langs->trans("NothingToSetup");
	}
}


// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();
