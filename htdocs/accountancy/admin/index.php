<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2019 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2014-2015 Ari Elbaz (elarifr)  <github@accedinfo.com>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2014      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2017      Laurent Destailleur  <eldy@destailleur.fr>
 * Copyright (C) 2021      Ferran Marcet        <fmarcet@2byte.es>
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
 * \file		htdocs/accountancy/admin/index.php
 * \ingroup		Accountancy (Double entries)
 * \brief		Setup page to configure accounting expert module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "admin", "accountancy", "other"));

// Security access
if (empty($user->rights->accounting->chartofaccount))
{
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Parameters ACCOUNTING_* and others
$list = array(
	'ACCOUNTING_LENGTH_GACCOUNT',
	'ACCOUNTING_LENGTH_AACCOUNT',
//    'ACCOUNTING_LENGTH_DESCRIPTION',         // adjust size displayed for lines description for dol_trunc
//    'ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT', // adjust size displayed for select account description for dol_trunc
);

$list_binding = array(
	'ACCOUNTING_DATE_START_BINDING',
	'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER'
);

/*
 * Actions
 */

if ($action == 'update') {
	$error = 0;

	if (!$error)
	{
		foreach ($list as $constname)
		{
			$constvalue = GETPOST($constname, 'alpha');

			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}
		if ($error) {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}

		foreach ($list_binding as $constname)
		{
			$constvalue = GETPOST($constname, 'alpha');

			if ($constname == 'ACCOUNTING_DATE_START_BINDING') {
				$constvalue = dol_mktime(12, 0, 0, GETPOST($constname.'month', 'int'), GETPOST($constname.'day', 'int'), GETPOST($constname.'year', 'int'));
			}

			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}
		if ($error) {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}

if ($action == 'setlistsorttodo') {
	$setlistsorttodo = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_TODO", $setlistsorttodo, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setlistsortdone') {
	$setlistsortdone = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_LIST_SORT_VENTILATION_DONE", $setlistsortdone, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setmanagezero') {
	$setmanagezero = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_MANAGE_ZERO", $setmanagezero, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setdisabledirectinput') {
	$setdisabledirectinput = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "BANK_DISABLE_DIRECT_INPUT", $setdisabledirectinput, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenabledraftexport') {
	$setenabledraftexport = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL", $setenabledraftexport, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenablesubsidiarylist') {
	$setenablesubsidiarylist = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTANCY_COMBO_FOR_AUX", $setenablesubsidiarylist, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setdisablebindingonsales') {
	$setdisablebindingonsales = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_SALES", $setdisablebindingonsales, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setdisablebindingonpurchases') {
	$setdisablebindingonpurchases = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_PURCHASES", $setdisablebindingonpurchases, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setdisablebindingonexpensereports') {
	$setdisablebindingonexpensereports = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS", $setdisablebindingonexpensereports, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans('ConfigAccountingExpert');
llxHeader('', $title);

$linkback = '';
//$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans('ConfigAccountingExpert'), $linkback, 'accountancy');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

// Params
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans('Options').'</td>';
print "</tr>\n";

// TO DO Mutualize code for yes/no constants

/* Set this option as a hidden option but keep it for some needs.
print '<tr>';
print '<td>'.$langs->trans("ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL").'</td>';
if (!empty($conf->global->ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enabledraftexport&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enabledraftexport&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';
*/

print '<tr class="oddeven">';
print '<td>'.$langs->trans("BANK_DISABLE_DIRECT_INPUT").'</td>';
if (!empty($conf->global->BANK_DISABLE_DIRECT_INPUT)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&disabledirectinput&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&disabledirectinput&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTANCY_COMBO_FOR_AUX").'</td>';
if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enablesubsidiarylist&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&enablesubsidiarylist&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_MANAGE_ZERO").'</td>';
if (!empty($conf->global->ACCOUNTING_MANAGE_ZERO)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&managezero&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&managezero&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

// Param a user $user->rights->accounting->chartofaccount can access
foreach ($list as $key)
{
	print '<tr class="oddeven value">';

	if (!empty($conf->global->ACCOUNTING_MANAGE_ZERO) && ($key == 'ACCOUNTING_LENGTH_GACCOUNT' || $key == 'ACCOUNTING_LENGTH_AACCOUNT')) continue;

	// Param
	$label = $langs->trans($key);
	print '<td>'.$label.'</td>';
	// Value
	print '<td class="right">';
	print '<input type="text" class="maxwidth100" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';

	print '</td>';
	print '</tr>';
}
print '</table>';
print '<br>';

// Binding params
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans('BindingOptions').'</td>';
print "</tr>\n";

// TO DO Mutualize code for yes/no constants
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_LIST_SORT_VENTILATION_TODO").'</td>';
if (!empty($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_TODO)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&listsorttodo&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&listsorttodo&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_LIST_SORT_VENTILATION_DONE").'</td>';
if (!empty($conf->global->ACCOUNTING_LIST_SORT_VENTILATION_DONE)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&listsortdone&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&listsortdone&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

// Param a user $user->rights->accounting->chartofaccount can access
foreach ($list_binding as $key)
{
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td>'.$label.'</td>';
	// Value
	print '<td class="right">';
	if ($key == 'ACCOUNTING_DATE_START_BINDING') {
		print $form->selectDate(($conf->global->$key ? $db->idate($conf->global->$key) : -1), $key, 0, 0, 1);
	} elseif ($key == 'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER') {
		$array = array(0=>$langs->trans("PreviousMonth"), 1=>$langs->trans("CurrentMonth"), 2=>$langs->trans("Fiscalyear"));
		print $form->selectarray($key, $array, (isset($conf->global->ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER) ? $conf->global->ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER : 0));
	} else {
		print '<input type="text" class="maxwidth100" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';
	}

	print '</td>';
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_DISABLE_BINDING_ON_SALES").'</td>';
if (!empty($conf->global->ACCOUNTING_DISABLE_BINDING_ON_SALES)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonsales&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonsales&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_DISABLE_BINDING_ON_PURCHASES").'</td>';
if (!empty($conf->global->ACCOUNTING_DISABLE_BINDING_ON_PURCHASES)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonpurchases&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonpurchases&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS").'</td>';
if (!empty($conf->global->ACCOUNTING_DISABLE_BINDING_ON_EXPENSEREPORTS)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonexpensereports&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonexpensereports&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '</table>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans('Modify').'" name="button"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
