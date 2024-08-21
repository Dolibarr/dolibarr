<?php
/* Copyright (C) 2013	Florian Henry	<florian.henry@open-concept.pro>
 * Copyright (C) 2015	Juanjo Menent	<jmenent@2byte.es>
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
 */

/**
 *   	\file       htdocs/admin/ecm.php
 *		\ingroup    core
 *		\brief      Page to setup ECM (GED) module
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

// Load translation files required by the page
$langs->load("admin");

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}


/*
 * Action
 */

// set
$reg = array();
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

// delete
if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans("ECMSetup"), $help_url, '', 0, 0, '', '', '', 'mod-admin page-ecm');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ECMSetup"), $linkback, 'title_setup');
print '<br>';

$head = ecm_admin_prepare_head();

print dol_get_fiche_head($head, 'ecm', '', -1, '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="100px">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Mail required for members

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ECMAutoTree").'</td>';
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('ECM_AUTO_TREE_HIDEN', null, null, 1);
} else {
	if (!getDolGlobalString('ECM_AUTO_TREE_HIDEN')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_ECM_AUTO_TREE_HIDEN&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_ECM_AUTO_TREE_HIDEN&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	}
}
print '</td></tr>';

print '</table>';

// End of page
llxFooter();
$db->close();
