<?php
/* Copyright (C) 2023-2024 	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2023-2024	Lionel Vessiller		<lvessiller@easya.solutions>
 * Copyright (C) 2023-2024	Patrice Andreani		<pandreani@easya.solutions>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * \file    htdocs/webportal/admin/configcss.php
 * \ingroup webportal
 * \brief   WebPortal setup page.
 */

// Load Dolibarr environment
require_once "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT . "/webportal/lib/webportal.lib.php";

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array("admin", "hrm", "other", "website"));

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('webportalsetup', 'globalsetup'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

if (empty($action)) {
	$action = 'edit';
}

// Access control
if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Convert action set_XXX and del_XXX to set var (this is used when no javascript on for ajax_constantonoff)
$regs = array();
if (preg_match('/^(set|del)_([A-Z_]+)$/', $action, $regs)) {
	if ($regs[1] == 'set') {
		dolibarr_set_const($db, $regs[2], 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_del_const($db, $regs[2], $conf->entity);
	}
}

if ($action == 'updatecss') {
	dolibarr_set_const($db, "WEBPORTAL_CUSTOM_CSS", GETPOST('WEBPORTAL_CUSTOM_CSS', 'restricthtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "WEBPORTAL_PARAMS_REV", ((int) $conf->global->WEBPORTAL_PARAMS_REV) + 1, 'chaine', 0, '', $conf->entity);
}


/*
 * View
 */

$title = "WebPortalSetup";

$wikihelp = 'EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';

llxHeader(
	'',
	$langs->trans($title),
	$wikihelp,
	'',
	0,
	0,
	array(
		'/includes/ace/src/ace.js',
		'/includes/ace/src/ext-statusbar.js',
		'/includes/ace/src/ext-language_tools.js',
	),
	array()
);

// Subheader
$linkback = '<a href="' . ($backtopage ? $backtopage : DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1') . '">' . $langs->trans("BackToModuleList") . '</a>';

print load_fiche_titre($langs->trans($title), $linkback, 'title_setup');

// Configuration header
$head = webportalAdminPrepareHead();
print dol_get_fiche_head($head, 'css', $langs->trans($title), -1, "webportal");

// Setup page goes here
echo '<span class="opacitymedium">' . $langs->trans("WebPortalCSS") . '</span><br><br>';

//WYSIWYG Editor
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

print '<form enctype="multipart/form-data" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';

print '<input type="hidden" name="token" value="' . newToken() . '">';
print '<input type="hidden" name="action" value="updatecss">';

clearstatcache();

// editeur CSS
print '<div class="div-table-responsive-no-min">';
print '<table summary="edit" class="noborder centpercent editmode tableforfield">';

print '<tr class="liste_titre">';
print '<td colspan="2">';

$customcssValue = getDolGlobalString('WEBPORTAL_CUSTOM_CSS');

$doleditor = new DolEditor('WEBPORTAL_CUSTOM_CSS', $customcssValue, '80%', 400, 'Basic', 'In', true, false, 'ace', 10, '90%');
$doleditor->Create(0, '', true, 'css', 'css');
print '</td></tr>' . "\n";

print '</table>' . "\n";
print '</div>';

print '<div class="center">';
print '<input class="button button-save reposition buttonforacesave" type="submit" name="submit" value="' . $langs->trans("Save") . '">';
//print '<input class="button button-cancel reposition" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
print '</div>';

print '</form>';

// Page end
print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
