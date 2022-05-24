<?php
/* Copyright (C) 2016  Florian HENRY	<florian.henry@atm-consulting.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * \file		htdocs/admin/resource.php
 * \ingroup		resource
 * \brief		Setup page to configure resource module
 */

require '../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "resource"));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'updateoptions') {
	if (GETPOST('activate_RESOURCE_USE_SEARCH_TO_SELECT') != '') {
		if (dolibarr_set_const($db, "RESOURCE_USE_SEARCH_TO_SELECT", GETPOST('activate_RESOURCE_USE_SEARCH_TO_SELECT'), 'chaine', 0, '', $conf->entity)) {
			setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}
}

/*
 * View
 */

llxHeader('', $langs->trans('ResourceSetup'));

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ResourceSetup'), $linkback, 'title_setup');

$head = resource_admin_prepare_head();

print dol_get_fiche_head($head, 'general', $langs->trans("ResourceSingular"), -1, 'action');

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateoptions">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td class="right">'.$langs->trans("Value").'</td>'."\n";
print '<td></td>';


// Utilisation formulaire Ajax sur choix produit
print '<tr class="oddeven">';
print '<td width="80%">'.$langs->trans("UseSearchToSelectResource").'</td>';
if (empty($conf->use_javascript_ajax)) {
	print '<td class="nowrap right" colspan="2">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
} else {
	print '<td width="60" class="right">';
	$arrval = array(
			'0'=>$langs->trans("No"),
			'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 1).')',
			'2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 2).')',
			'3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 3).')',
	);
	print $form->selectarray("activate_RESOURCE_USE_SEARCH_TO_SELECT", $arrval, $conf->global->RESOURCE_USE_SEARCH_TO_SELECT);
	print '</td>';
	print '<td class="right">';
	print '<input type="submit" class="button small" name="RESOURCE_USE_SEARCH_TO_SELECT" value="'.$langs->trans("Modify").'">';
	print '</td>';
}
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans('EnableResourceUsedInEventCheck').'</td>';
print '<td class="right">';
echo ajax_constantonoff('RESOURCE_USED_IN_EVENT_CHECK');
print '</td>';
print '<td></td>';
print '</tr>';

/*
print '<tr class="oddeven">';
print '<td>'.$langs->trans('DisabledResourceLinkUser').'</td>';
print '<td class="right">';
echo ajax_constantonoff('RESOURCE_HIDE_ADD_CONTACT_USER');
print '</td>';
print '<td></td>';
print '</tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->trans('DisabledResourceLinkContact').'</td>';
print '<td class="right">';
echo ajax_constantonoff('RESOURCE_HIDE_ADD_CONTACT_THIPARTY');
print '</td>';
print '<td></td>';
print '</tr>';
*/

print '</table>';
print '</div>';

print '</form>';


//RESOURCE_HIDE_ADD_CONTACT_USER
//RESOURCE_HIDE_ADD_CONTACT_THIPARTY

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
