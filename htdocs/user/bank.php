<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003       Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2015  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Peter Fontaine       <contact@peterfontaine.fr>
 * Copyright (C) 2015-2016  Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015       Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2021       Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	    \file       htdocs/user/bank.php
 *      \ingroup    HRM
 *		\brief      Tab for HR and bank
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/userbankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (isModEnabled('holiday')) {
	require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
}
if (isModEnabled('expensereport')) {
	require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
}
if (isModEnabled('salaries')) {
	require_once DOL_DOCUMENT_ROOT.'/salaries/class/salary.class.php';
	require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
}

// Load translation files required by page
$langs->loadLangs(array('companies', 'commercial', 'banks', 'bills', 'trips', 'holiday', 'salaries'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alphanohtml');
$bankid = GETPOSTINT('bankid');
$action = GETPOST("action", 'alpha');
$cancel = GETPOST('cancel', 'alpha');

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('usercardBank', 'globalcard'));

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight('user', 'self', 'creer')) ? '' : 'user');

$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->loadRights();
}

$account = new UserBankAccount($db);
if (!$bankid) {
	// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
	$account->fetch(0, '', $id);
} else {
	$account->fetch($bankid);
}
if (empty($account->userid)) {
	$account->userid = $object->id;
}

// Define value to know what current user can do on users
$selfpermission = ($user->id == $id && $user->hasRight('user', 'self', 'creer'));
$usercanadd = (!empty($user->admin) || $user->hasRight('user', 'user', 'creer') || $user->hasRight('hrm', 'write_personal_information', 'write') );
$usercanread = (!empty($user->admin) || $user->hasRight('user', 'user', 'lire') || $user->hasRight('hrm', 'read_personal_information', 'read') );
$permissiontoaddbankaccount = ($user->hasRight('salaries', 'write') || $user->hasRight('hrm', 'employee', 'write') || $user->hasRight('user', 'user', 'creer') || $selfpermission);
$permissiontoreadhr = $user->hasRight('hrm', 'read_personal_information', 'read') || $user->hasRight('hrm', 'write_personal_information', 'write');
$permissiontowritehr = $user->hasRight('hrm', 'write_personal_information', 'write');
$permissiontosimpleedit = ($selfpermission || $usercanadd);

// Ok if user->hasRight('salaries', 'readall') or user->hasRight('hrm', 'read')
//$result = restrictedArea($user, 'salaries|hrm', $object->id, 'user&user', $feature2);
$ok = false;
if ($user->id == $id) {
	$ok = true; // A user can always read its own card
}
if ($user->hasRight('salaries', 'readall')) {
	$ok = true;
}
if ($user->hasRight('hrm', 'read')) {
	$ok = true;
}
if ($user->hasRight('expensereport', 'lire') && ($user->id == $object->id || $user->hasRight('expensereport', 'readall'))) {
	$ok = true;
}
if (!$ok) {
	accessforbidden();
}


/*
 *	Actions
 */

if ($action == 'add' && !$cancel && $permissiontoaddbankaccount) {
	$account->userid          = $object->id;

	$account->bank            = GETPOST('bank', 'alpha');
	$account->label           = GETPOST('label', 'alpha');
	$account->type = GETPOSTINT('courant'); // not used
	$account->code_banque     = GETPOST('code_banque', 'alpha');
	$account->code_guichet    = GETPOST('code_guichet', 'alpha');
	$account->number          = GETPOST('number', 'alpha');
	$account->cle_rib         = GETPOST('cle_rib', 'alpha');
	$account->bic             = GETPOST('bic', 'alpha');
	$account->iban            = GETPOST('iban', 'alpha');
	$account->domiciliation   = GETPOST('address', 'alpha');
	$account->address         = GETPOST('address', 'alpha');
	$account->owner_name = GETPOST('proprio', 'alpha');
	$account->proprio = $account->owner_name;
	$account->owner_address   = GETPOST('owner_address', 'alpha');

	$account->currency_code = trim(GETPOST("account_currency_code"));
	$account->state_id = GETPOSTINT("account_state_id");
	$account->country_id = GETPOSTINT("account_country_id");

	$result = $account->create($user);

	if (!$result) {
		setEventMessages($account->error, $account->errors, 'errors');
		$action = 'edit'; // Force chargement page edition
	} else {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = '';
	}
}

if ($action == 'update' && !$cancel && $permissiontoaddbankaccount) {
	$account->userid = $object->id;

	$account->bank            = GETPOST('bank', 'alpha');
	$account->label           = GETPOST('label', 'alpha');
	$account->type = GETPOSTINT('courant'); // not used
	$account->code_banque     = GETPOST('code_banque', 'alpha');
	$account->code_guichet    = GETPOST('code_guichet', 'alpha');
	$account->number          = GETPOST('number', 'alpha');
	$account->cle_rib         = GETPOST('cle_rib', 'alpha');
	$account->bic             = GETPOST('bic', 'alpha');
	$account->iban            = GETPOST('iban', 'alpha');
	$account->domiciliation   = GETPOST('address', 'alpha');
	$account->address         = GETPOST('address', 'alpha');
	$account->proprio         = GETPOST('proprio', 'alpha');
	$account->owner_address   = GETPOST('owner_address', 'alpha');

	$account->currency_code = trim(GETPOST("account_currency_code"));
	$account->state_id = GETPOSTINT("account_state_id");
	$account->country_id = GETPOSTINT("account_country_id");

	$result = $account->update($user);

	if (!$result) {
		setEventMessages($account->error, $account->errors, 'errors');
		$action = 'edit'; // Force chargement page edition
	} else {
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = '';
	}
}

if ($action == 'delete_confirmed' && !$cancel && $permissiontoaddbankaccount) {
	$result = $account->delete($user);
	if ($result < 0) {
		setEventMessages($account->error, $account->errors, 'errors');
	} else {
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		header("Location: ".DOL_URL_ROOT.'/user/bank.php?id='.$object->id);
		exit;
	}
	$action = '';
}

// update birth
if ($action == 'setbirth' && $usercanadd && !$cancel) {
	$object->birth = dol_mktime(0, 0, 0, GETPOSTINT('birthmonth'), GETPOSTINT('birthday'), GETPOSTINT('birthyear'));
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// update personal email
if ($action == 'setpersonal_email' && $permissiontosimpleedit && !$cancel) {
	$object->personal_email = (string) GETPOST('personal_email', 'alphanohtml');
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// update personal mobile
if ($action == 'setpersonal_mobile' && $permissiontosimpleedit && !$cancel) {
	$object->personal_mobile = (string) GETPOST('personal_mobile', 'alphanohtml');
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// update accountancy_code
if ($action == 'setaccountancy_code' && $usercanadd && !$cancel) {
	$object->accountancy_code = (string) GETPOST('accountancy_code', 'alphanohtml');
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// update ref_employee
if ($action == 'setref_employee' && $usercanadd && !$cancel) {
	$object->ref_employee = (string) GETPOST('ref_employee', 'alphanohtml');
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

// update national_registration_number
if ($action == 'setnational_registration_number' && $usercanadd && !$cancel) {
	$object->national_registration_number = (string) GETPOST('national_registration_number', 'alphanohtml');
	$result = $object->update($user);
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
}

if (getDolGlobalString('MAIN_USE_EXPENSE_IK')) {
	// update default_c_exp_tax_cat
	if ($action == 'setdefault_c_exp_tax_cat' && $usercanadd) {
		$object->default_c_exp_tax_cat = GETPOSTINT('default_c_exp_tax_cat');
		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// update default range
	if ($action == 'setdefault_range' && $usercanadd) {
		$object->default_range = GETPOSTINT('default_range');
		$result = $object->update($user);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


/*
 *	View
 */

$form = new Form($db);
$formcompany = new FormCompany($db);

$childids = $user->getAllChildIds(1);

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('BankAccounts');
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-bank');

$head = user_prepare_head($object);

if ($id && $bankid && $action == 'edit' && !$cancel && $permissiontoaddbankaccount) {
	if ($conf->use_javascript_ajax) {
		print "\n<script>";
		print 'jQuery(document).ready(function () {
					jQuery("#type").change(function() {
						document.formbank.action.value="edit";
						document.formbank.submit();
					});
					jQuery("#selectaccount_country_id").change(function() {
						document.formbank.action.value="edit";
						document.formbank.submit();
					});
				})';
		print "</script>\n";
	}
	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" name="formbank" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.GETPOSTINT("id").'">';
	print '<input type="hidden" name="bankid" value="'.$bankid.'">';
}
if ($id && $action == 'create' && !$cancel && $permissiontoaddbankaccount) {
	if ($conf->use_javascript_ajax) {
		print "\n<script>";
		print 'jQuery(document).ready(function () {
					jQuery("#type").change(function() {
						document.formbank.action.value="create";
						document.formbank.submit();
					});
					jQuery("#selectaccount_country_id").change(function() {
						document.formbank.action.value="create";
						document.formbank.submit();
					});
				})';
		print "</script>\n";
	}
	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" name="formbank" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="bankid" value="'.$bankid.'">';
}


// View
if ($action != 'edit' && $action != 'create') {		// If not bank account yet, $account may be empty
	$title = $langs->trans("User");
	print dol_get_fiche_head($head, 'bank', $title, -1, 'user');

	$linkback = '';

	if ($user->hasRight('user', 'user', 'lire') || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'&output=file&file='.urlencode(dol_sanitizeFileName($object->getFullName($langs).'.vcf')).'" class="refid" rel="noopener">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	$urltovirtualcard = '/user/virtualcard.php?id='.((int) $object->id);
	$morehtmlref .= dolButtonToOpenUrlInDialogPopup('publicvirtualcard', $langs->transnoentitiesnoconv("PublicVirtualCardUrl").' - '.$object->getFullName($langs), img_picto($langs->trans("PublicVirtualCardUrl"), 'card', 'class="valignmiddle marginleftonly paddingrightonly"'), $urltovirtualcard, '', 'nohover');

	dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin, 'rowid', 'ref', $morehtmlref);

	print '<div class="fichecenter"><div class="fichehalfleft">';

	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	print '<tr><td class="titlefieldmiddle">'.$langs->trans("Login").'</td>';
	if (!empty($object->ldap_sid) && $object->statut == 0) {
		print '<td class="error">';
		print $langs->trans("LoginAccountDisableInDolibarr");
		print '</td>';
	} else {
		print '<td>';
		$addadmin = '';
		if (property_exists($object, 'admin')) {
			if (isModEnabled('multicompany') && !empty($object->admin) && empty($object->entity)) {
				$addadmin .= img_picto($langs->trans("SuperAdministratorDesc"), "redstar", 'class="paddingleft"');
			} elseif (!empty($object->admin)) {
				$addadmin .= img_picto($langs->trans("AdministratorDesc"), "star", 'class="paddingleft"');
			}
		}
		print showValueWithClipboardCPButton($object->login).$addadmin;
		print '</td>';
	}
	print '</tr>';


	// Hierarchy
	print '<tr><td>'.$langs->trans("HierarchicalResponsible").'</td>';
	print '<td>';
	if (empty($object->fk_user)) {
		print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
	} else {
		$huser = new User($db);
		if ($object->fk_user > 0) {
			$huser->fetch($object->fk_user);
			print $huser->getNomUrl(1);
		} else {
			print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
		}
	}
	print '</td>';
	print "</tr>\n";

	// Expense report validator
	if (isModEnabled('expensereport')) {
		print '<tr><td>';
		$text = $langs->trans("ForceUserExpenseValidator");
		print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
		print '</td>';
		print '<td>';
		if (!empty($object->fk_user_expense_validator)) {
			$evuser = new User($db);
			$evuser->fetch($object->fk_user_expense_validator);
			print $evuser->getNomUrl(1);
		}
		print '</td>';
		print "</tr>\n";
	}

	// Holiday request validator
	if (isModEnabled('holiday')) {
		print '<tr><td>';
		$text = $langs->trans("ForceUserHolidayValidator");
		print $form->textwithpicto($text, $langs->trans("ValidatorIsSupervisorByDefault"), 1, 'help');
		print '</td>';
		print '<td>';
		if (!empty($object->fk_user_holiday_validator)) {
			$hvuser = new User($db);
			$hvuser->fetch($object->fk_user_holiday_validator);
			print $hvuser->getNomUrl(1);
		}
		print '</td>';
		print "</tr>\n";
	}

	// Position/Job
	print '<tr><td>'.$langs->trans("PostOrFunction").'</td>';
	print '<td>'.dol_escape_htmltag($object->job).'</td>';
	print '</tr>'."\n";

	// Weeklyhours
	print '<tr><td>'.$langs->trans("WeeklyHours").'</td>';
	print '<td>';
	print price2num($object->weeklyhours);
	print '</td>';
	print "</tr>\n";

	// Sensitive salary/value information
	if ((empty($user->socid) && in_array($id, $childids))	// A user can always see salary/value information for its subordinates
		|| (isModEnabled('salaries') && $user->hasRight('salaries', 'readall'))
		|| (isModEnabled('hrm') && $user->hasRight('hrm', 'employee', 'read'))) {
		$langs->load("salaries");

		// Salary
		print '<tr><td>'.$langs->trans("Salary").'</td>';
		print '<td>';
		print($object->salary != '' ? img_picto('', 'salary', 'class="pictofixedwidth paddingright"').'<span class="amount">'.price($object->salary, 0, $langs, 1, -1, -1, $conf->currency) : '').'</span>';
		print '</td>';
		print "</tr>\n";

		// THM
		print '<tr><td>';
		$text = $langs->trans("THM");
		print $form->textwithpicto($text, $langs->trans("THMDescription"), 1, 'help', 'classthm');
		print '</td>';
		print '<td>';
		print($object->thm != '' ? price($object->thm, 0, $langs, 1, -1, -1, $conf->currency) : '');
		print '</td>';
		print "</tr>\n";

		// TJM
		print '<tr><td>';
		$text = $langs->trans("TJM");
		print $form->textwithpicto($text, $langs->trans("TJMDescription"), 1, 'help', 'classtjm');
		print '</td>';
		print '<td>';
		print($object->tjm != '' ? price($object->tjm, 0, $langs, 1, -1, -1, $conf->currency) : '');
		print '</td>';
		print "</tr>\n";
	}

	// Date employment
	print '<tr><td>'.$langs->trans("DateOfEmployment").'</td>';
	print '<td>';
	if ($object->dateemployment) {
		print '<span class="opacitymedium">'.$langs->trans("FromDate").'</span> ';
		print dol_print_date($object->dateemployment, 'day');
	}
	if ($object->dateemploymentend) {
		print '<span class="opacitymedium"> - '.$langs->trans("To").'</span> ';
		print dol_print_date($object->dateemploymentend, 'day');
	}
	print '</td>';
	print "</tr>\n";

	// Date of birth
	if ($user->hasRight('hrm', 'read_personal_information', 'read') || $user->hasRight('hrm', 'write_personal_information', 'write')) {
		print '<tr>';
		print '<td>';
		print $form->editfieldkey("DateOfBirth", 'birth', $object->birth, $object, $user->hasRight('user', 'user', 'creer'));
		print '</td><td>';
		print $form->editfieldval("DateOfBirth", 'birth', $object->birth, $object, $user->hasRight('user', 'user', 'creer'), 'day', $object->birth);
		print '</td>';
		print "</tr>\n";
	}

	// Personal email
	if ($user->hasRight('hrm', 'read_personal_information', 'read') || $user->hasRight('hrm', 'write_personal_information', 'write') || $permissiontosimpleedit) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("UserPersonalEmail", 'personal_email', $object->personal_email, $object, $user->hasRight('user', 'user', 'creer') || $user->hasRight('hrm', 'write_personal_information', 'write'));
		print '</td><td>';
		print $form->editfieldval("UserPersonalEmail", 'personal_email', $object->personal_email, $object, $user->hasRight('user', 'user', 'creer') || $user->hasRight('hrm', 'write_personal_information', 'write'), 'email', '', null, null, '', 0, '');
		print '</td>';
		print '</tr>';
	}

	// Personal phone
	if ($user->hasRight('hrm', 'read_personal_information', 'read') || $user->hasRight('hrm', 'write_personal_information', 'write') || $permissiontosimpleedit) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("UserPersonalMobile", 'personal_mobile', $object->personal_mobile, $object, $user->hasRight('user', 'user', 'creer') || $user->hasRight('hrm', 'write_personal_information', 'write'));
		print '</td><td>';
		print $form->editfieldval("UserPersonalMobile", 'personal_mobile', $object->personal_mobile, $object, $user->hasRight('user', 'user', 'creer') || $user->hasRight('hrm', 'write_personal_information', 'write'), 'phone', '', null, null, '', 0, '');
		print '</td>';
		print '</tr>';
	}

	if (getDolGlobalString('MAIN_USE_EXPENSE_IK')) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("DefaultCategoryCar", 'default_c_exp_tax_cat', $object->default_c_exp_tax_cat, $object, $user->hasRight('user', 'user', 'creer'));
		print '</td><td>';
		if ($action == 'editdefault_c_exp_tax_cat') {
			$ret = '<form method="post" action="'.$_SERVER["PHP_SELF"].($moreparam ? '?'.$moreparam : '').'">';
			$ret .= '<input type="hidden" name="action" value="setdefault_c_exp_tax_cat">';
			$ret .= '<input type="hidden" name="token" value="'.newToken().'">';
			$ret .= '<input type="hidden" name="id" value="'.$object->id.'">';
			$ret .= $form->selectExpenseCategories($object->default_c_exp_tax_cat, 'default_c_exp_tax_cat', 1);
			$ret .= '<input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'"> ';
			$ret .= '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			$ret .= '</form>';
			print $ret;
		} else {
			$label_exp_tax_cat = dol_getIdFromCode($db, $object->default_c_exp_tax_cat, 'c_exp_tax_cat', 'rowid', 'label');
			print $langs->trans($label_exp_tax_cat);
			//print $form->editfieldval("DefaultCategoryCar", 'default_c_exp_tax_cat', $object->default_c_exp_tax_cat, $object, $user->hasRight('user', 'user', 'creer'), 'string', ($object->default_c_exp_tax_cat != '' ? $object->default_c_exp_tax_cat : ''));
		}
		print '</td>';
		print '</tr>';

		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("DefaultRangeNumber", 'default_range', $object->default_range, $object, $user->hasRight('user', 'user', 'creer'));
		print '</td><td>';
		if ($action == 'editdefault_range') {
			$ret = '<form method="post" action="'.$_SERVER["PHP_SELF"].($moreparam ? '?'.$moreparam : '').'">';
			$ret .= '<input type="hidden" name="action" value="setdefault_range">';
			$ret .= '<input type="hidden" name="token" value="'.newToken().'">';
			$ret .= '<input type="hidden" name="id" value="'.$object->id.'">';

			$expensereportik = new ExpenseReportIk($db);
			$maxRangeNum = $expensereportik->getMaxRangeNumber($object->default_c_exp_tax_cat);

			$ret .= $form->selectarray('default_range', range(0, $maxRangeNum), $object->default_range);
			$ret .= '<input type="submit" class="button" name="modify" value="'.$langs->trans("Modify").'"> ';
			$ret .= '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			$ret .= '</form>';
			print $ret;
		} else {
			print $object->default_range;
		}
		print '</td>';
		print '</tr>';
	}

	// Accountancy code
	if (isModEnabled('accounting')) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("AccountancyCode", 'accountancy_code', $object->accountancy_code, $object, $user->hasRight('user', 'user', 'creer'));
		print '</td><td>';
		print $form->editfieldval("AccountancyCode", 'accountancy_code', $object->accountancy_code, $object, $user->hasRight('user', 'user', 'creer'), 'string', '', null, null, '', 0, '');
		print '</td>';
		print '</tr>';
	}

	// Employee Number
	if ($permissiontoreadhr) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("RefEmployee", 'ref_employee', $object->ref_employee, $object, $permissiontowritehr);
		print '</td><td>';
		print $form->editfieldval("RefEmployee", 'ref_employee', $object->ref_employee, $object, $permissiontowritehr, 'string', $object->ref_employee);
		print '</td>';
		print '</tr>';
	}

	// National registration number
	if ($permissiontoreadhr) {
		print '<tr class="nowrap">';
		print '<td>';
		print $form->editfieldkey("NationalRegistrationNumber", 'national_registration_number', $object->national_registration_number, $object, $permissiontowritehr);
		print '</td><td>';
		print $form->editfieldval("NationalRegistrationNumber", 'national_registration_number', $object->national_registration_number, $object, $permissiontowritehr, 'string', $object->national_registration_number);
		print '</td>';
		print '</tr>';
	}

	print '</table>';

	print '</div><div class="fichehalfright">';

	// Max number of elements in small lists
	$MAXLIST = getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT');

	// Latest payments of salaries
	if (isModEnabled('salaries') &&
		(($user->hasRight('salaries', 'read') && (in_array($object->id, $childids) || $object->id == $user->id)) || ($user->hasRight('salaries', 'readall')))
	) {
		$payment_salary = new PaymentSalary($db);
		$salary = new Salary($db);

		$sql = "SELECT s.rowid as sid, s.ref as sref, s.label, s.datesp, s.dateep, s.paye, s.amount, SUM(ps.amount) as alreadypaid";
		$sql .= " FROM ".MAIN_DB_PREFIX."salary as s";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_salary as ps ON (s.rowid = ps.fk_salary)";
		$sql .= " WHERE s.fk_user = ".((int) $object->id);
		$sql .= " AND s.entity IN (".getEntity('salary').")";
		$sql .= " GROUP BY s.rowid, s.ref, s.label, s.datesp, s.dateep, s.paye, s.amount";
		$sql .= " ORDER BY s.dateep DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td colspan="5"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastSalaries", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/salaries/list.php?search_user='.$object->login.'">'.$langs->trans("AllSalaries").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
			print '</tr></table></td>';
			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$salary->id = $objp->sid;
				$salary->ref = $objp->sref ? $objp->sref : $objp->sid;
				$salary->label = $objp->label;
				$salary->datesp = $db->jdate($objp->datesp);
				$salary->dateep = $db->jdate($objp->dateep);
				$salary->paye = $objp->paye;
				$salary->amount = $objp->amount;

				$payment_salary->id = !empty($objp->rowid) ? $objp->rowid : 0;
				$payment_salary->ref = !empty($objp->ref) ? $objp->ref : "";
				$payment_salary->datep = $db->jdate(!empty($objp->datep) ? $objp->datep : "");

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $salary->getNomUrl(1);
				print '</td>';
				print '<td class="right nowraponall">'.dol_print_date($db->jdate($objp->datesp), 'day')."</td>\n";
				print '<td class="right nowraponall">'.dol_print_date($db->jdate($objp->dateep), 'day')."</td>\n";
				print '<td class="right nowraponall"><span class="amount">'.price($objp->amount).'</span></td>';
				print '<td class="right nowraponall">'.$salary->getLibStatut(5, $objp->alreadypaid).'</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) {
				print '<td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></a>';
			}
			print "</table>";
			print "</div>";
		} else {
			dol_print_error($db);
		}
	}

	// Latest leave requests
	if (isModEnabled('holiday') && ($user->hasRight('holiday', 'readall') || ($user->hasRight('holiday', 'read') && $object->id == $user->id))) {
		$holiday = new Holiday($db);

		$sql = "SELECT h.rowid, h.statut as status, h.fk_type, h.date_debut, h.date_fin, h.halfday";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.fk_user = ".((int) $object->id);
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		$sql .= " ORDER BY h.date_debut DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td colspan="4"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastHolidays", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/holiday/list.php?id='.$object->id.'">'.$langs->trans("AllHolidays").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
			print '</tr></table></td>';
			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$holiday->id = $objp->rowid;
				$holiday->ref = $objp->rowid;

				$holiday->fk_type = $objp->fk_type;
				$holiday->statut = $objp->status;
				$holiday->status = $objp->status;

				$nbopenedday = num_open_day($db->jdate($objp->date_debut, 'gmt'), $db->jdate($objp->date_fin, 'gmt'), 0, 1, $objp->halfday);

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $holiday->getNomUrl(1);
				print '</td><td class="right nowraponall">'.dol_print_date($db->jdate($objp->date_debut), 'day')."</td>\n";
				print '<td class="right nowraponall">'.$nbopenedday.' '.$langs->trans('DurationDays').'</td>';
				print '<td class="right nowraponall">'.$holiday->LibStatut($objp->status, 5).'</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) {
				print '<td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></a>';
			}
			print "</table>";
			print "</div>";
		} else {
			dol_print_error($db);
		}
	}

	// Latest expense report
	if (isModEnabled('expensereport') &&
		($user->hasRight('expensereport', 'readall') || ($user->hasRight('expensereport', 'lire') && $object->id == $user->id))
	) {
		$exp = new ExpenseReport($db);

		$sql = "SELECT e.rowid, e.ref, e.fk_statut as status, e.date_debut, e.total_ttc";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e";
		$sql .= " WHERE e.fk_user_author = ".((int) $object->id);
		$sql .= " AND e.entity = ".((int) $conf->entity);
		$sql .= " ORDER BY e.date_debut DESC";

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);

			print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
			print '<table class="noborder centpercent">';

			print '<tr class="liste_titre">';
			print '<td colspan="4"><table class="nobordernopadding centpercent"><tr><td>'.$langs->trans("LastExpenseReports", ($num <= $MAXLIST ? "" : $MAXLIST)).'</td><td class="right"><a class="notasortlink" href="'.DOL_URL_ROOT.'/expensereport/list.php?id='.$object->id.'">'.$langs->trans("AllExpenseReports").'<span class="badge marginleftonlyshort">'.$num.'</span></a></td>';
			print '</tr></table></td>';
			print '</tr>';

			$i = 0;
			while ($i < $num && $i < $MAXLIST) {
				$objp = $db->fetch_object($resql);

				$exp->id = $objp->rowid;
				$exp->ref = $objp->ref;
				$exp->status = $objp->status;

				print '<tr class="oddeven">';
				print '<td class="nowraponall">';
				print $exp->getNomUrl(1);
				print '</td><td class="right nowraponall">'.dol_print_date($db->jdate($objp->date_debut), 'day')."</td>\n";
				print '<td class="right nowraponall"><span class="amount">'.price($objp->total_ttc).'</span></td>';
				print '<td class="right nowraponall">'.$exp->LibStatut($objp->status, 5).'</td>';
				print '</tr>';
				$i++;
			}
			$db->free($resql);

			if ($num <= 0) {
				print '<td colspan="4"><span class="opacitymedium">'.$langs->trans("None").'</span></a>';
			}
			print "</table>";
			print "</div>";
		} else {
			dol_print_error($db);
		}
	}

	print '</div></div>';
	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	// List of bank accounts (Currently only one bank account possible for each employee)

	$morehtmlright = '';
	if ($account->id == 0) {
		if ($permissiontoaddbankaccount) {
			$morehtmlright = dolGetButtonTitle($langs->trans('Add'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create');
		} else {
			$morehtmlright = dolGetButtonTitle($langs->trans('Add'), $langs->trans('NotEnoughPermissions'), 'fa fa-plus-circle', '', '', -2);
		}
	} else {
		$morehtmlright = dolGetButtonTitle($langs->trans('Add'), $langs->trans('AlreadyOneBankAccount'), 'fa fa-plus-circle', '', '', -2);
	}

	print load_fiche_titre($langs->trans("BankAccounts"), $morehtmlright, 'bank_account');

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="liste centpercent">';

	print '<tr class="liste_titre">';
	print_liste_field_titre("LabelRIB");
	print_liste_field_titre("Bank");
	print_liste_field_titre("RIB");
	print_liste_field_titre("IBAN");
	print_liste_field_titre("BIC");
	print_liste_field_titre("Currency");
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', '', '', 'maxwidthsearch ');
	print "</tr>\n";

	if ($account->id > 0) {
		print '<tr class="oddeven">';
		// Label
		print '<td>'.dol_escape_htmltag($account->label).'</td>';
		// Bank name
		print '<td>'.dol_escape_htmltag($account->bank).'</td>';
		// Account number
		print '<td>';
		$stringescaped = '';
		foreach ($account->getFieldsToShow() as $val) {
			if ($val == 'BankCode') {
				$stringescaped .= dol_escape_htmltag($account->code_banque).' ';
			} elseif ($val == 'BankAccountNumber') {
				$stringescaped .= dol_escape_htmltag($account->number).' ';
			} elseif ($val == 'DeskCode') {
				$stringescaped .= dol_escape_htmltag($account->code_guichet).' ';
			} elseif ($val == 'BankAccountNumberKey') {
				$stringescaped .= dol_escape_htmltag($account->cle_rib).' ';
			}
		}
		if (!empty($account->label) && $account->number) {
			if (!checkBanForAccount($account)) {
				$stringescaped .= ' '.img_picto($langs->trans("ValueIsNotValid"), 'warning');
			} else {
				$stringescaped .= ' '.img_picto($langs->trans("ValueIsValid"), 'info');
			}
		}

		print $stringescaped;
		print '</td>';
		// IBAN
		print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag(getIbanHumanReadable($account)).'">';
		if (!empty($account->iban)) {
			if (!checkIbanForAccount($account)) {
				print ' '.img_picto($langs->trans("IbanNotValid"), 'warning');
			}
		}
		print getIbanHumanReadable($account);
		print '</td>';
		// BIC
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($account->bic).'">';
		if (!empty($account->bic)) {
			if (!checkSwiftForAccount($account)) {
				print ' '.img_picto($langs->trans("SwiftNotValid"), 'warning');
			}
		}
		print dol_escape_htmltag($account->bic);
		print '</td>';

		// Currency
		print '<td>'.$account->currency_code.'</td>';

		// Edit/Delete
		print '<td class="right nowraponall">';
		if ($permissiontoaddbankaccount) {
			print '<a class="editfielda marginleftonly marginrightonly" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&bankid='.$account->id.'&action=edit&token='.newToken().'">';
			print img_picto($langs->trans("Modify"), 'edit');
			print '</a>';

			print '<a class="editfielda marginleftonly marginrightonly reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&bankid='.$account->id.'&action=delete_confirmed&token='.newToken().'">';
			print img_picto($langs->trans("Delete"), 'delete');
			print '</a>';
		}
		print '</td>';

		print '</tr>';
	}


	if ($account->id == 0) {
		$colspan = 7;
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoBANRecord").'</span></td></tr>';
	}



	print '</table>';
	print '</div>';

	// Add hook in fields
	$parameters = array('colspan' => ' colspan="2"');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
}

// Edit
if ($id && ($action == 'edit' || $action == 'create') && $permissiontoaddbankaccount) {
	$title = $langs->trans("User");
	print dol_get_fiche_head($head, 'bank', $title, 0, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'id', $linkback, $user->hasRight('user', 'user', 'lire') || $user->admin);

	print '<div class="underbanner clearboth"></div>';
	print '<br>';

	print '<table class="border centpercent">';

	print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Label").'</td>';
	print '<td><input size="30" type="text" name="label" value="'.$account->label.'" autofocus></td></tr>';

	print '<tr><td class="">'.$langs->trans("BankName").'</td>';
	print '<td><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';

	// Currency
	print '<tr><td class="fieldrequired">'.$langs->trans("Currency");
	print '<input type="hidden" value="'.$account->currency_code.'">';
	print '</td>';
	print '<td class="maxwidth200onsmartphone">';
	$selectedcode = $account->currency_code;
	if (!$selectedcode) {
		$selectedcode = $conf->currency;
	}
	print img_picto('', 'multicurrency', 'class="pictofixedwidth"');
	print $form->selectCurrency((GETPOSTISSET("account_currency_code") ? GETPOST("account_currency_code") : $selectedcode), 'account_currency_code');
	print '</td></tr>';

	// Country
	$account->country_id = $account->country_id ? $account->country_id : $mysoc->country_id;
	$selectedcode = $account->country_code;
	if (GETPOSTISSET("account_country_id")) {
		$selectedcode = GETPOST("account_country_id");
	} elseif (empty($selectedcode)) {
		$selectedcode = $mysoc->country_code;
	}
	$account->country_code = getCountry($selectedcode, '2'); // Force country code on account to have following field on bank fields matching country rules

	print '<tr><td class="fieldrequired">'.$langs->trans("Country").'</td>';
	print '<td class="maxwidth200onsmartphone">';
	print img_picto('', 'country', 'class="pictofixedwidth"').$form->select_country($selectedcode, 'account_country_id');
	if ($user->admin) {
		print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	}
	print '</td></tr>';

	// State
	print '<tr><td>'.$langs->trans('State').'</td><td class="maxwidth200onsmartphone">';
	if ($selectedcode) {
		print img_picto('', 'state', 'class="pictofixedwidth"');
		print $formcompany->select_state(GETPOSTISSET("account_state_id") ? GETPOST("account_state_id") : $account->state_id, $selectedcode, 'account_state_id');
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';


	// Show fields of bank account
	$bankaccount = $account;

	// Code here is similar as in paymentmodes.php for third-parties
	foreach ($bankaccount->getFieldsToShow(1) as $val) {
		$require = false;
		$tooltip = '';
		if ($val == 'BankCode') {
			$name = 'code_banque';
			$size = 8;
			$content = $bankaccount->code_banque;
		} elseif ($val == 'DeskCode') {
			$name = 'code_guichet';
			$size = 8;
			$content = $bankaccount->code_guichet;
		} elseif ($val == 'BankAccountNumber') {
			$name = 'number';
			$size = 18;
			$content = $bankaccount->number;
		} elseif ($val == 'BankAccountNumberKey') {
			$name = 'cle_rib';
			$size = 3;
			$content = $bankaccount->cle_rib;
		} elseif ($val == 'IBAN') {
			$name = 'iban';
			$size = 30;
			$content = $bankaccount->iban;
			if ($bankaccount->needIBAN()) {
				$require = true;
			}
			$tooltip = $langs->trans("Example").':<br>CH93 0076 2011 6238 5295 7<br>LT12 1000 0111 0100 1000<br>FR14 2004 1010 0505 0001 3M02 606<br>LU28 0019 4006 4475 0000<br>DE89 3704 0044 0532 0130 00';
		} elseif ($val == 'BIC') {
			$name = 'bic';
			$size = 12;
			$content = $bankaccount->bic;
			if ($bankaccount->needIBAN()) {
				$require = true;
			}
			$tooltip = $langs->trans("Example").': LIABLT2XXXX';
		}
		print '<tr>';
		print '<td'.($require ? ' class="fieldrequired" ' : '').'>';
		if ($tooltip) {
			print $form->textwithpicto($langs->trans($val), $tooltip, 4, 'help', '', 0, 3, $name);
		} else {
			print $langs->trans($val);
		}
		print '</td>';
		print '<td><input size="'.$size.'" type="text" class="flat" name="'.$name.'" value="'.$content.'"></td>';
		print '</tr>';
	}

	print '<tr><td class="tdtop">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
	print '<textarea name="address" rows="4" class="quatrevingtpercent">';
	print dol_escape_htmltag($account->address);
	print "</textarea></td></tr>";

	print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
	print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
	print "</td></tr>\n";

	print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
	print '<textarea name="owner_address" rows="4" class="quatrevingtpercent">';
	print dol_escape_htmltag($account->owner_address);
	print "</textarea></td></tr>";

	print '</table>';

	//print '</div>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel($action == 'create' ? "Create" : "Modify");
}

if ($id && $action == 'edit' && $permissiontoaddbankaccount) {
	print '</form>';
}

if ($id && $action == 'create' && $permissiontoaddbankaccount) {
	print '</form>';
}

// End of page
llxFooter();
$db->close();
