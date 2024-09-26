<?php
/* Copyright (C) 2019-2024  Alexandre Spangaro      <aspangaro@easya.solutions>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file		htdocs/accountancy/admin/closure.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Setup page to configure accounting expert module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "admin", "accountancy"));

// Security check
if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


$list_account_main = array(
	'ACCOUNTING_RESULT_PROFIT',
	'ACCOUNTING_RESULT_LOSS'
);

/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;

	$defaultjournal = GETPOST('ACCOUNTING_CLOSURE_DEFAULT_JOURNAL', 'alpha');

	if (!empty($defaultjournal)) {
		if (!dolibarr_set_const($db, 'ACCOUNTING_CLOSURE_DEFAULT_JOURNAL', $defaultjournal, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	} else {
		$error++;
	}

	$accountinggroupsusedforbalancesheetaccount = GETPOST('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT', 'alphanohtml');
	if (!empty($accountinggroupsusedforbalancesheetaccount)) {
		if (!dolibarr_set_const($db, 'ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT', $accountinggroupsusedforbalancesheetaccount, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	} else {
		$error++;
	}

	$accountinggroupsusedforincomestatement = GETPOST('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT', 'alpha');
	if (!empty($accountinggroupsusedforincomestatement)) {
		if (!dolibarr_set_const($db, 'ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT', $accountinggroupsusedforincomestatement, 'chaine', 0, '', $conf->entity)) {
			$error++;
		}
	} else {
		$error++;
	}

	foreach ($list_account_main as $constname) {
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

$form = new Form($db);
$formaccounting = new FormAccounting($db);

$title = $langs->trans('Closure');

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-accountancy page-admin_closure');

$linkback = '';
print load_fiche_titre($langs->trans('MenuClosureAccounts'), $linkback, 'title_accountancy');

print '<span class="opacitymedium">'.$langs->trans("DefaultClosureDesc").'</span><br>';
print '<br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// Define main accounts for closure
print '<table class="noborder centpercent">';

foreach ($list_account_main as $key) {
	print '<tr class="oddeven value">';
	// Param
	$label = $langs->trans($key);
	$keydesc = $key.'_Desc';

	$htmltext = $langs->trans($keydesc);
	print '<td class="fieldrequired" width="50%">';
	print $form->textwithpicto($label, $htmltext);
	print '</td>';
	// Value
	print '<td>'; // Do not force class=right, or it align also the content of the select box
	print $formaccounting->select_account(getDolGlobalString($key), $key, 1, array(), 1, 1);
	print '</td>';
	print '</tr>';
}

// Journal
print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("ACCOUNTING_CLOSURE_DEFAULT_JOURNAL").'</td>';
print '<td>';
$defaultjournal = getDolGlobalString('ACCOUNTING_CLOSURE_DEFAULT_JOURNAL');
print $formaccounting->select_journal($defaultjournal, "ACCOUNTING_CLOSURE_DEFAULT_JOURNAL", 9, 1, 0, 0);
print '</td></tr>';

// Accounting groups used for the balance sheet account
print '<tr class="oddeven">';
print '<td class="fieldrequired">';
print $form->textwithpicto($langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT"), $langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNTHelp"));
print '</td>';
print '<td>';
print '<input type="text" class="quatrevingtpercent" id="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT" name="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT" value="' . dol_escape_htmltag(getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_BALANCE_SHEET_ACCOUNT')). '">';
print '</td></tr>';

// Accounting groups used for the income statement
print '<tr class="oddeven">';
print '<td class="fieldrequired">';
print $form->textwithpicto($langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT"), $langs->trans("ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENTHelp"));
print '</td>';
print '<td>';
print '<input type="text" class="quatrevingtpercent" id="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT" name="ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT" value="' . dol_escape_htmltag(getDolGlobalString('ACCOUNTING_CLOSURE_ACCOUNTING_GROUPS_USED_FOR_INCOME_STATEMENT')). '">';
print '</td></tr>';

print "</table>\n";

print '<div class="center"><input type="submit" class="button button-edit" name="button" value="'.$langs->trans('Save').'"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
