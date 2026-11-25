<?php
/* Copyright (C) 2014-2017  Alexandre Spangaro	<aspangaro@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
 * \file		htdocs/admin/loan.php
 * \ingroup		loan
 * \brief		Setup page to configure loan module
 */

// Load Dolibarr environment
require '../main.inc.php';

// Class
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'loan'));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'loan';

// Other parameters LOAN_*
$list = array(
		'LOAN_ACCOUNTING_ACCOUNT_CAPITAL',
		'LOAN_ACCOUNTING_ACCOUNT_INTEREST',
		'LOAN_ACCOUNTING_ACCOUNT_INSURANCE'
);

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'set') {
	// Activate a model
	addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	// Deactivate a model
	$ret = delDocumentModel($value, $type);
	if ($ret > 0 && getDolGlobalString('LOAN_ADDON_PDF') == "$value") {
		dolibarr_del_const($db, 'LOAN_ADDON_PDF', $conf->entity);
	}
} elseif ($action == 'setdoc') {
	// Set the default model
	if (dolibarr_set_const($db, "LOAN_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant was read before the new set, so we go through a variable to get a consistent display
		$conf->global->LOAN_ADDON_PDF = $value;
	}

	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		addDocumentModel($value, $type, $label, $scandir);
	}
} elseif ($action == 'update') {
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

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-admin page-loan');

$form = new Form($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
} else {
	$formaccounting = null;
}

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans('ConfigLoan'), $linkback, 'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" spellcheck="false">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

/*
 *  Params
 */
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans('Options').'</td>';
print "</tr>\n";

foreach ($list as $key) {
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td><label for="' . $key . '">' . $label . '</label></td>';

	// Value
	print '<td>';
	if ($formaccounting instanceof FormAccounting) {
		print $formaccounting->select_account(getDolGlobalString($key), $key, 1, array(), 1, 1);
	} else {
		print '<input type="text" size="20" id="' . $key . '" name="' . $key . '" value="' . getDolGlobalString($key) . '">';
	}
	print '</td></tr>';
}

print '</tr>';

print '</form>';
print "</table>\n";

print '<br><div class="center"><input type="submit" class="button button-edit" name="button" value="'.$langs->trans('Modify').'"></div>';

print '<br>';


/*
 *  Documents models for Loans
 */

printDocumentModelList($type, 'loan', 'LOAN_ADDON_PDF', $langs->trans("TemplatePDFLoans"), array(
	'Logo' => 'option_logo',
	'MultiLanguage' => 'option_multilang',
));

// End of page
llxFooter();
$db->close();
