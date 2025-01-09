<?php
/* Copyright (C) 2015 		Alexandre Spangaro <aspangaro@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *    \file       htdocs/hrm/admin/admin_hrm.php
 *    \ingroup    HRM
 *    \brief      HRM module setup page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'hrm'));

// Get Parameters
$action = GETPOST('action', 'aZ09');

// Other parameters HRM_*
$list = array(
//		'HRM_EMAIL_EXTERNAL_SERVICE'   // To prevent your public accountant for example
);

// Permissions
$permissiontoread = $user->admin;
$permissiontoadd  = $user->admin;

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
//$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
//restrictedArea($user, $object->element, $object->id, '', '', 'fk_soc', 'rowid', 0);
if (!isModEnabled('hrm')) {
	accessforbidden();
}
if (empty($permissiontoread)) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;

	foreach ($list as $constname) {
		$constvalue = GETPOST($constname, 'alpha');

		if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */


$title = $langs->trans('Parameters');

llxHeader('', $title, '');

$form = new Form($db);

dol_htmloutput_mesg($mesg);

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("HRMSetup"), $linkback);

// Configuration header
$head = hrm_admin_prepare_head();

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print dol_get_fiche_head($head, 'parameters', $langs->trans("HRM"), -1, "user");

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans('Parameters').'</td>';
print "</tr>\n";

foreach ($list as $key) {
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td width="50%"><label for="'.$key.'">'.$label.'</label></td>';

	// Value
	print '<td>';
	print '<input type="text" size="20" id="'.$key.'" name="'.$key.'" value="'.getDolGlobalString($key).'">';
	print '</td></tr>';
}

print "</table>\n";

print dol_get_fiche_end();

print '<div class="center"><input type="submit" class="button button-edit" name="button" value="'.$langs->trans('Modify').'"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
