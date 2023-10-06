<?php
/* Copyright (C) 2013-2014 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2023 Alexandre Spangaro   <aspangaro@open-dsi.fr>
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

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "admin", "accountancy", "other"));

// Security access
if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');

// Parameters ACCOUNTING_* and others
$list = array(
	'ACCOUNTING_LENGTH_GACCOUNT',
	'ACCOUNTING_LENGTH_AACCOUNT',
//	'ACCOUNTING_LIMIT_LIST_VENTILATION'		   // there is already a global parameter to define the nb of records in lists, we must use it in priority. Having one parameter for nb of record for each page is deprecated.
//	'ACCOUNTING_LENGTH_DESCRIPTION',         // adjust size displayed for lines description for dol_trunc
//	'ACCOUNTING_LENGTH_DESCRIPTION_ACCOUNT', // adjust size displayed for select account description for dol_trunc
);

$list_binding = array(
	'ACCOUNTING_DATE_START_BINDING',
	'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER'
);

$error = 0;


/*
 * Actions
 */

if (in_array($action, array('setBANK_DISABLE_DIRECT_INPUT', 'setACCOUNTANCY_COMBO_FOR_AUX', 'setACCOUNTING_MANAGE_ZERO'))) {
	$constname = preg_replace('/^set/', '', $action);
	$constvalue = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, $constname, $constvalue, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'update') {
	$error = 0;

	if (!$error) {
		foreach ($list as $constname) {
			$constvalue = GETPOST($constname, 'alpha');
			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}
		if ($error) {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}

		foreach ($list_binding as $constname) {
			$constvalue = GETPOST($constname, 'alpha');

			if ($constname == 'ACCOUNTING_DATE_START_BINDING') {
				$constvalue = dol_mktime(0, 0, 0, GETPOST($constname.'month', 'int'), GETPOST($constname.'day', 'int'), GETPOST($constname.'year', 'int'));
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

if ($action == 'setenablelettering') {
	$setenablelettering = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_LETTERING", $setenablelettering, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenableautolettering') {
	$setenableautolettering = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_ENABLE_AUTOLETTERING", $setenableautolettering, 'yesno', 0, '', $conf->entity);
	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'mesgs');
	}
}

if ($action == 'setenablevatreversecharge') {
	$setenablevatreversecharge = GETPOST('value', 'int');
	$res = dolibarr_set_const($db, "ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE", $setenablevatreversecharge, 'yesno', 0, '', $conf->entity);
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
print load_fiche_titre($title, $linkback, 'accountancy');

// Show message if accountancy hidden options are activated to help to resolve some problems
if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
}
if (!empty($conf->global->FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS)) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS") . '</div>';
}
if (!empty($conf->global->ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY)) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "ACCOUNTANCY_USE_PRODUCT_ACCOUNT_ON_THIRDPARTY") . '</div>';
}
if (!empty($conf->global->MAIN_COMPANY_PERENTITY_SHARED)) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_COMPANY_PERENTITY_SHARED") . '</div>';
}
if (!empty($conf->global->MAIN_PRODUCT_PERENTITY_SHARED)) {
	print '<div class="info">' . $langs->trans("ConstantIsOn", "MAIN_PRODUCT_PERENTITY_SHARED") . '</div>';
}

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
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setBANK_DISABLE_DIRECT_INPUT&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setBANK_DISABLE_DIRECT_INPUT&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTANCY_COMBO_FOR_AUX");
print ' - <span class="opacitymedium">'.$langs->trans("NotRecommended").'</span>';
print '</td>';

if (!empty($conf->global->ACCOUNTANCY_COMBO_FOR_AUX)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTANCY_COMBO_FOR_AUX&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTANCY_COMBO_FOR_AUX&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_MANAGE_ZERO").'</td>';
if (!empty($conf->global->ACCOUNTING_MANAGE_ZERO)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTING_MANAGE_ZERO&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setACCOUNTING_MANAGE_ZERO&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

// Param a user $user->rights->accounting->chartofaccount can access
foreach ($list as $key) {
	print '<tr class="oddeven value">';

	if (getDolGlobalInt('ACCOUNTING_MANAGE_ZERO') && ($key == 'ACCOUNTING_LENGTH_GACCOUNT' || $key == 'ACCOUNTING_LENGTH_AACCOUNT')) {
		continue;
	}

	// Param
	$label = $langs->trans($key);
	print '<td>'.$label.'</td>';
	// Value
	print '<td class="right">';
	print '<input type="text" class="maxwidth50 right" id="'.$key.'" name="'.$key.'" value="'.getDolGlobalString($key).'">';

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

// Param a user $user->rights->accounting->chartofaccount can access
foreach ($list_binding as $key) {
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td>'.$label.'</td>';
	// Value
	print '<td class="right">';
	if ($key == 'ACCOUNTING_DATE_START_BINDING') {
		print $form->selectDate((getDolGlobalInt($key) ? (int) getDolGlobalInt($key) : -1), $key, 0, 0, 1);
	} elseif ($key == 'ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER') {
		$array = array(0=>$langs->trans("PreviousMonth"), 1=>$langs->trans("CurrentMonth"), 2=>$langs->trans("Fiscalyear"));
		print $form->selectarray($key, $array, getDolGlobalInt('ACCOUNTING_DEFAULT_PERIOD_ON_TRANSFER', 0), 0, 0, 0, '', 0, 0, 0, '', 'onrightofpage');
	} else {
		print '<input type="text" class="maxwidth100" id="'.$key.'" name="'.$key.'" value="'.getDolGlobalString($key).'">';
	}

	print '</td>';
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("ACCOUNTING_DISABLE_BINDING_ON_SALES").'</td>';
if (!empty($conf->global->ACCOUNTING_DISABLE_BINDING_ON_SALES)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonsales&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
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
	print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
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
	print img_picto($langs->trans("Activated"), 'switch_on', '', false, 0, 0, '', 'warning');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setdisablebindingonexpensereports&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '</table>';
print '<br>';


// Show advanced options
print '<br>';


// Advanced params
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">' . $langs->trans('OptionsAdvanced') . '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_LETTERING"), $langs->trans("ACCOUNTING_ENABLE_LETTERING_DESC", $langs->transnoentitiesnoconv("NumMvts")).'<br>'.$langs->trans("EnablingThisFeatureIsNotNecessary")).'</td>';
if (!empty($conf->global->ACCOUNTING_ENABLE_LETTERING)) {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setenablelettering&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?token='.newToken().'&action=setenablelettering&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

if (!empty($conf->global->ACCOUNTING_ENABLE_LETTERING)) {
	print '<tr class="oddeven">';
	print '<td>';
	print $form->textwithpicto($langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING"), $langs->trans("ACCOUNTING_ENABLE_AUTOLETTERING_DESC")) . '</td>';
	if (!empty($conf->global->ACCOUNTING_ENABLE_AUTOLETTERING)) {
		print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=0">';
		print img_picto($langs->trans("Activated"), 'switch_on');
		print '</a></td>';
	} else {
		print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenableautolettering&value=1">';
		print img_picto($langs->trans("Disabled"), 'switch_off');
		print '</a></td>';
	}
	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>';
print $form->textwithpicto($langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE"), $langs->trans("ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE_DESC", $langs->transnoentities("MenuDefaultAccounts"))).'</td>';
if (!empty($conf->global->ACCOUNTING_FORCE_ENABLE_VAT_REVERSE_CHARGE)) {
	print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=0">';
	print img_picto($langs->trans("Activated"), 'switch_on');
	print '</a></td>';
} else {
	print '<td class="right"><a class="reposition" href="' . $_SERVER['PHP_SELF'] . '?token=' . newToken() . '&action=setenablevatreversecharge&value=1">';
	print img_picto($langs->trans("Disabled"), 'switch_off');
	print '</a></td>';
}
print '</tr>';

print '</table>';


print '<div class="center"><input type="submit" class="button button-edit" name="button" value="'.$langs->trans('Modify').'"></div>';

print '</form>';

// End of page
llxFooter();
$db->close();
