<?php
/* Copyright (C) 2026      Michael Wallis       <mh.wallis@gmail.com>
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
 * \file    htdocs/datapolicy/admin/retention.php
 * \ingroup datapolicy
 * \brief   Datapolicy setup page to define the number of years to keep business documents (legal retention).
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once DOL_DOCUMENT_ROOT.'/datapolicy/lib/datapolicy.lib.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Translations
$langs->loadLangs(array('admin', 'datapolicy'));

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Security check
if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'update') {
	if (dolibarr_set_const($db, 'DATAPOLICY_DOCUMENT_RETENTION_YEARS', max(0, GETPOSTINT('DATAPOLICY_DOCUMENT_RETENTION_YEARS')), 'chaine', 0, '', $conf->entity) > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

$page_name = "datapolicySetup";
llxHeader('', $langs->trans($page_name));

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'generic');

$head = datapolicyAdminPrepareHead();
print dol_get_fiche_head($head, 'retention', '', -1, '');

print '<span class="opacitymedium">'.$langs->trans("DocumentRetentionSetupPage").'</span>';
print '<br><br>';

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

// Number of years to keep the documents: shown as a hint, nothing is deleted or blocked
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DocumentRetentionYears").'<br>';
print '<span class="opacitymedium">'.$langs->trans("DocumentRetentionYearsDesc").'</span>';
print '</td>';
print '<td class="nowraponall">';
print '<input class="flat width75 right" name="DATAPOLICY_DOCUMENT_RETENTION_YEARS" id="DATAPOLICY_DOCUMENT_RETENTION_YEARS" type="number" min="0" spellcheck="false" value="'.getDolGlobalInt('DATAPOLICY_DOCUMENT_RETENTION_YEARS').'"> '.$langs->trans("Years");
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';

print dol_get_fiche_end();

llxFooter();
$db->close();
