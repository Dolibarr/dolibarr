<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * 	\file		admin/bankimport.php
 * 	\ingroup	bankimport
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/bankimport.lib.php';

global $bc, $conf, $db, $langs, $user;

// Translations
$langs->load('admin');
$langs->load("bankimport@bankimport");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

/*
 * Actions
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code,"alphanohtml"), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * View
 */
$page_name = "BankImportSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback, 'tools');

// Configuration header
$head = bankimportAdminPrepareHead();
print dol_get_fiche_head(
    $head,
    'settings',
    $langs->trans("Module104020Name"),
    -1,
    "bankimport@bankimport"
);

// Setup page goes here
$form = new Form($db);
$var = true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Parameters") . '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">' . $langs->trans("Value") . '</td>';

$newToken = function_exists('newToken')?newToken():$_SESSION['newtoken'];

// Separator
$var = !$var;
print '<tr ' . $bc[$var] . '>';
print '<td>';
print $form->textwithpicto(
	'<label for="BANKIMPORT_SEPARATOR">' . $langs->trans("BankImportSeparator") . '</label>',
	$langs->trans("BankImportSeparatorHelp")
);
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="500">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $newToken . '">';
print '<input type="hidden" name="action" value="set_BANKIMPORT_SEPARATOR">';
print '<input type="text" id="BANKIMPORT_SEPARATOR" name="BANKIMPORT_SEPARATOR" value="' . getDolGlobalString('BANKIMPORT_SEPARATOR') . '" size="10" />';
print '&nbsp;<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

// Mapping
$var = !$var;
print '<tr ' . $bc[$var] . '>';
print '<td>';
print $form->textwithpicto(
	'<label for="BANKIMPORT_SEPARATOR">' . $langs->trans("BankImportMapping") . '</label>',
	$langs->trans("BankImportMappingHelp")
);
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="500">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $newToken . '">';
print '<input type="hidden" name="action" value="set_BANKIMPORT_MAPPING">';
print '<input type="text" id="BANKIMPORT_MAPPING" name="BANKIMPORT_MAPPING" value="' . getDolGlobalString('BANKIMPORT_MAPPING') . '" size="50" />';
print '&nbsp;<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

// Date format
$var = !$var;
print '<tr ' . $bc[$var] . '>';
print '<td>';
print $form->textwithpicto(
	'<label for="BANKIMPORT_DATE_FORMAT">' . $langs->trans("BankImportDateFormat") . '</label>',
	$langs->trans("BankImportDateFormatHelp")
);
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="500">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $newToken . '">';
print '<input type="hidden" name="action" value="set_BANKIMPORT_DATE_FORMAT">';
print '<input type="text" id="BANKIMPORT_DATE_FORMAT" name="BANKIMPORT_DATE_FORMAT" value="' . getDolGlobalString('BANKIMPORT_DATE_FORMAT') . '" size="10" />';
print '&nbsp;<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("FileHasHeader").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_HEADER');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("UseMacCompatibility").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_MAC_COMPATIBILITY');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportUseHistory").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_HISTORY_IMPORT');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportAllowInvoiceFromSeveralThird").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_ALLOW_INVOICE_FROM_SEVERAL_THIRD');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportAllowDraftInvoice").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_ALLOW_DRAFT_INVOICE');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportUncheckAllLines").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_UNCHECK_ALL_LINES');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportAutoCreateDiscount").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_AUTO_CREATE_DISCOUNT');
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportMatchBanklinesByAmountAndLabel").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_MATCH_BANKLINES_BY_AMOUNT_AND_LABEL');
print '</td></tr>';

// Date proximity matching
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportUseDateProximity").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_USE_DATE_PROXIMITY');
print '</td></tr>';

// Date tolerance days
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>';
print $form->textwithpicto(
	'<label for="BANKIMPORT_DATE_TOLERANCE_DAYS">' . $langs->trans("bankImportDateToleranceDays") . '</label>',
	$langs->trans("bankImportDateToleranceDaysHelp")
);
print '</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="500">';
print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '">';
print '<input type="hidden" name="token" value="' . $newToken . '">';
print '<input type="hidden" name="action" value="set_BANKIMPORT_DATE_TOLERANCE_DAYS">';
print '<input type="number" min="0" step="1" id="BANKIMPORT_DATE_TOLERANCE_DAYS" name="BANKIMPORT_DATE_TOLERANCE_DAYS" value="' . getDolGlobalString('BANKIMPORT_DATE_TOLERANCE_DAYS') . '" size="5" />';
print '&nbsp;<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("bankImportAllowFreelines").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="300">';
print ajax_constantonoff('BANKIMPORT_ALLOW_FREELINES');
print '</td></tr>';

print '</table>';

print dol_get_fiche_end(-1);

llxFooter();

$db->close();
