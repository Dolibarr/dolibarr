<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2014-2017	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Charlene Benke          <charlene@patas-monkey.com>
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
 */

/**
 *	    \file       htdocs/compta/bank/card.php
 *      \ingroup    bank
 *		\brief      Page to create/view a bank account
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbank.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
}
if (isModEnabled('accounting')) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("banks", "bills", "categories", "companies", "compta", "withdrawals"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

$object = new Account($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('bankcard', 'globalcard'));

// Security check
$id = GETPOSTINT("id") ? GETPOSTINT("id") : GETPOST('ref');
$fieldid = GETPOSTINT("id") ? 'rowid' : 'ref';

if (GETPOSTINT("id") || GETPOST("ref")) {
	if (GETPOSTINT("id")) {
		$object->fetch(GETPOSTINT("id"));
	}
	if (GETPOST("ref")) {
		$object->fetch(0, GETPOST("ref"));
	}
}

$result = restrictedArea($user, 'banque', $id, 'bank_account&bank_account', '', '', $fieldid);


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/compta/bank/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/compta/bank/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	if ($action == 'add') {
		$error = 0;

		$db->begin();

		// Create account
		$object = new Account($db);

		$object->ref = dol_string_nospecial(trim(GETPOST('ref', 'alpha')));
		$object->label = trim(GETPOST("label", 'alphanohtml'));
		$object->type = GETPOSTINT("type");
		$object->courant = $object->type;	// deprecated
		$object->status = GETPOSTINT("clos");
		$object->clos = $object->status;	// deprecated
		$object->rappro = (GETPOST("norappro", 'alpha') ? 0 : 1);
		$object->url = trim(GETPOST("url", 'alpha'));

		$object->bank = trim(GETPOST("bank"));
		$object->code_banque = trim(GETPOST("code_banque"));
		$object->code_guichet = trim(GETPOST("code_guichet"));
		$object->number = trim(GETPOST("number"));
		$object->cle_rib = trim(GETPOST("cle_rib"));
		$object->bic = trim(GETPOST("bic"));
		$object->iban = trim(GETPOST("iban"));
		$object->pti_in_ctti = empty(GETPOST("pti_in_ctti")) ? 0 : 1;

		$object->address = trim(GETPOST("account_address", "alphanohtml"));
		$object->domiciliation = $object->address;	// deprecated

		$object->proprio = trim(GETPOST("proprio", 'alphanohtml'));
		$object->owner_address = trim(GETPOST("owner_address", 'alphanohtml'));
		$object->owner_zip = trim(GETPOST("owner_zip", 'alphanohtml'));
		$object->owner_town = trim(GETPOST("owner_town", 'alphanohtml'));
		$object->owner_country_id = GETPOSTINT("owner_country_id");

		$object->ics = trim(GETPOST("ics", 'alpha'));
		$object->ics_transfer = trim(GETPOST("ics_transfer", 'alpha'));

		$account_number = GETPOST('account_number', 'alphanohtml');
		if (empty($account_number) || $account_number == '-1') {
			$object->account_number = '';
		} else {
			$object->account_number = $account_number;
		}
		$fk_accountancy_journal = GETPOSTINT('fk_accountancy_journal');
		if ($fk_accountancy_journal <= 0) {
			$object->fk_accountancy_journal = 0;
		} else {
			$object->fk_accountancy_journal = $fk_accountancy_journal;
		}

		$object->balance = GETPOSTFLOAT("solde");
		$object->solde = $object->balance;	// deprecated
		$object->date_solde = dol_mktime(12, 0, 0, GETPOSTINT("remonth"), GETPOSTINT('reday'), GETPOSTINT("reyear"));

		$object->currency_code = trim(GETPOST("account_currency_code"));

		$object->state_id = GETPOSTINT("account_state_id");
		$object->country_id = GETPOSTINT("account_country_id");

		$object->min_allowed = GETPOSTFLOAT("account_min_allowed");
		$object->min_desired = GETPOSTFLOAT("account_min_desired");
		$object->comment = trim(GETPOST("account_comment", 'restricthtml'));

		$object->fk_user_author = $user->id;

		if (getDolGlobalInt('MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED') && empty($object->account_number)) {
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountancyCode")), null, 'errors');
			$action = 'create'; // Force chargement page en mode creation
			$error++;
		}
		if (empty($object->ref)) {
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref")), null, 'errors');
			$action = 'create'; // Force chargement page en mode creation
			$error++;
		}
		if (empty($object->label)) {
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("LabelBankCashAccount")), null, 'errors');
			$action = 'create'; // Force chargement page en mode creation
			$error++;
		}

		// Fill array 'array_options' with data from add form
		$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');

		if (!$error) {
			$id = $object->create($user);
			if ($id > 0) {
				// Category association
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);

				$action = '';
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');

				$action = 'create'; // Force chargement page en mode creation
			}
		}

		if (!$error) {
			$noback = 0;

			$db->commit();

			$urltogo = $backtopage ? str_replace('__ID__', (string) $object->id, $backtopage) : $backurlforlist;
			$urltogo = preg_replace('/--IDFORBACKTOPAGE--/', (string) $object->id, $urltogo); // New method to autoselect project after a New on another form object creation

			if (empty($noback)) {
				header("Location: " . $urltogo);
				exit;
			}
		} else {
			$db->rollback();
		}
	}

	if ($action == 'update') {
		$error = 0;

		// Update account
		$object = new Account($db);
		$object->fetch(GETPOSTINT("id"));

		$object->ref = dol_string_nospecial(trim(GETPOST('ref', 'alpha')));
		$object->label = trim(GETPOST("label", 'alphanohtml'));
		$object->type = GETPOSTINT("type");
		$object->courant = $object->type; // deprecated
		$object->status = GETPOSTINT("clos");
		$object->clos = $object->status; // deprecated
		$object->rappro = (GETPOST("norappro", 'alpha') ? 0 : 1);
		$object->url = trim(GETPOST("url", 'alpha'));

		$object->bank = trim(GETPOST("bank"));
		$object->code_banque = trim(GETPOST("code_banque"));
		$object->code_guichet = trim(GETPOST("code_guichet"));
		$object->number = trim(GETPOST("number"));
		$object->cle_rib = trim(GETPOST("cle_rib"));
		$object->bic = trim(GETPOST("bic"));
		$object->iban = trim(GETPOST("iban"));
		$object->pti_in_ctti = empty(GETPOST("pti_in_ctti")) ? 0 : 1;

		$object->proprio = trim(GETPOST("proprio", 'alphanohtml'));
		$object->owner_address = trim(GETPOST("owner_address", 'alphanohtml'));
		$object->owner_zip = trim(GETPOST("owner_zip", 'alphanohtml'));
		$object->owner_town = trim(GETPOST("owner_town", 'alphanohtml'));
		$object->owner_country_id = GETPOSTINT("owner_country_id");

		$object->ics = trim(GETPOST("ics", 'alpha'));
		$object->ics_transfer = trim(GETPOST("ics_transfer", 'alpha'));

		$account_number = GETPOST('account_number', 'alphanohtml');
		if (empty($account_number) || $account_number == '-1') {
			$object->account_number = '';
		} else {
			$object->account_number = $account_number;
		}
		$fk_accountancy_journal = GETPOSTINT('fk_accountancy_journal');
		if ($fk_accountancy_journal <= 0) {
			$object->fk_accountancy_journal = 0;
		} else {
			$object->fk_accountancy_journal = $fk_accountancy_journal;
		}

		$object->currency_code = trim(GETPOST("account_currency_code"));

		$object->address = trim(GETPOST("account_address", "alphanohtml"));
		$object->state_id = GETPOSTINT("account_state_id");
		$object->country_id = GETPOSTINT("account_country_id");

		$object->min_allowed = GETPOSTFLOAT("account_min_allowed");
		$object->min_desired = GETPOSTFLOAT("account_min_desired");
		$object->comment = trim(GETPOST("account_comment", 'restricthtml'));

		if (getDolGlobalInt('MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED') && empty($object->account_number)) {
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountancyCode")), null, 'errors');
			$action = 'edit'; // Force chargement page en mode creation
			$error++;
		}
		if (empty($object->ref)) {
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("Ref")), null, 'errors');
			$action = 'edit'; // Force chargement page en mode creation
			$error++;
		}
		if (empty($object->label)) {
			setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired", $langs->transnoentitiesnoconv("LabelBankCashAccount")), null, 'errors');
			$action = 'edit'; // Force chargement page en mode creation
			$error++;
		}

		$db->begin();

		if (!$error) {
			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
		}

		if (!$error) {
			$result = $object->update($user);
			if ($result >= 0) {
				// Category association
				$categories = GETPOST('categories', 'array');
				$object->setCategories($categories);

				$id = GETPOSTINT("id"); // Force load of this page
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'edit'; // Force load of page in edit mode
			}
		}

		if (!$error) {
			$db->commit();
		} else {
			$db->rollback();
		}
	}

	if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $user->hasRight('banque', 'configurer')) {
		// Delete
		$object = new Account($db);
		$object->fetch(GETPOSTINT("id"));
		$result = $object->delete($user);

		if ($result > 0) {
			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
			header("Location: " . DOL_URL_ROOT . "/compta/bank/list.php");
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = '';
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formbank = new FormBank($db);
$formcompany = new FormCompany($db);
if (isModEnabled('accounting')) {
	$formaccounting = new FormAccounting($db);
}

$countrynotdefined = $langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

$help_url = 'EN:Module_Banks_and_Cash|FR:Module_Banques_et_Caisses|ES:Módulo_Bancos_y_Cajas|DE:Modul_Banken_und_Barbestände';
if ($action == 'create') {
	$title = $langs->trans("NewFinancialAccount");
} elseif (!empty($object->ref)) {
	$title = $object->ref." - ".$langs->trans("Card");
}

llxHeader("", $title, $help_url);

// Creation
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewFinancialAccount"), '', 'bank_account');

	if ($conf->use_javascript_ajax) {
		print "\n".'<script type="text/javascript">';
		print 'jQuery(document).ready(function () {
                    jQuery("#type").change(function() {
                        document.formsoc.action.value="create";
                        document.formsoc.submit();
                    });
                    jQuery("#selectaccount_country_id").change(function() {
                        document.formsoc.action.value="create";
                        document.formsoc.submit();
                    });
               })';
		print '</script>'."\n";
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" name="formsoc" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="clos" value="0">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head('');

	print '<table class="border centpercent tableforfieldcreate">';

	// Ref
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Ref").'</td>';
	print '<td><input type="text" class="flat width100" name="ref" value="'.dol_escape_htmltag(GETPOSTISSET('ref') ? GETPOST("ref", 'alpha') : $object->ref).'" maxlength="12" autofocus></td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("LabelBankCashAccount").'</td>';
	print '<td><input type="text" class="flat maxwidth150onsmartphone" name="label" value="'.dol_escape_htmltag(GETPOST('label', 'alpha')).'"></td></tr>';

	// Type
	print '<tr><td class="fieldrequired">'.$langs->trans("AccountType").'</td>';
	print '<td>';
	$formbank->selectTypeOfBankAccount(GETPOSTISSET("type") ? GETPOSTINT('type') : Account::TYPE_CURRENT, 'type');
	print '</td></tr>';

	// Currency
	print '<tr><td class="fieldrequired">'.$langs->trans("Currency").'</td>';
	print '<td>';
	$selectedcode = $object->currency_code;
	if (!$selectedcode) {
		$selectedcode = $conf->currency;
	}
	print $form->selectCurrency((GETPOSTISSET("account_currency_code") ? GETPOST("account_currency_code") : $selectedcode), 'account_currency_code');
	//print $langs->trans("Currency".$conf->currency);
	//print '<input type="hidden" name="account_currency_code" value="'.$conf->currency.'">';
	print '</td></tr>';

	// Status
	print '<tr><td class="fieldrequired">'.$langs->trans("Status").'</td>';
	print '<td>';
	print $form->selectarray("clos", $object->labelStatus, (GETPOSTINT('clos') != '' ? GETPOSTINT('clos') : $object->status), 0, 0, 0, '', 0, 0, 0, '', 'minwidth100 maxwidth150onsmartphone');
	print '</td></tr>';

	// Country
	$selectedcode = '';
	if (GETPOSTISSET("account_country_id")) {
		$selectedcode = GETPOST("account_country_id") ? GETPOST("account_country_id") : $object->country_code;
	} elseif (empty($selectedcode)) {
		$selectedcode = $mysoc->country_code;
	}
	$object->country_code = getCountry($selectedcode, 2); // Force country code on account to have following field on bank fields matching country rules

	print '<tr><td class="fieldrequired">'.$langs->trans("BankAccountCountry").'</td>';
	print '<td>';
	print img_picto('', 'country', 'class="pictofixedwidth"');
	print $form->select_country($selectedcode, 'account_country_id');
	if ($user->admin) {
		print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
	}
	print '</td></tr>';

	// State
	print '<tr><td>'.$langs->trans('State').'</td><td>';
	if ($selectedcode) {
		print img_picto('', 'state', 'class="pictofixedwidth"');
		print $formcompany->select_state(GETPOSTISSET("account_state_id") ? GETPOST("account_state_id") : '', $selectedcode, 'account_state_id');
	} else {
		print $countrynotdefined;
	}
	print '</td></tr>';

	$type = (GETPOSTISSET("type") ? GETPOSTINT('type') : Account::TYPE_CURRENT); // add default value
	if ($type == Account::TYPE_SAVINGS || $type == Account::TYPE_CURRENT) {
		print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td>';
		print '<textarea class="flat quatrevingtpercent" name="account_address" rows="'.ROWS_2.'">';
		print(GETPOSTISSET('account_address') ? GETPOST('account_address') : $object->address);
		print "</textarea></td></tr>";
	}

	// Web
	print '<tr><td>'.$langs->trans("Web").'</td>';
	print '<td>';
	print img_picto('', 'globe', 'class="pictofixedwidth"');
	print '<input class="minwidth300 widthcentpercentminusx maxwidth500" type="text" class="flat" name="url" value="'.GETPOST("url").'">';
	print '</td></tr>';

	// Tags-Categories
	if (isModEnabled('category')) {
		print '<tr><td>'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_ACCOUNT, '', 'parent', 64, 0, 3);

		$arrayselected = array();
		$c = new Categorie($db);
		$cats = $c->containing($object->id, Categorie::TYPE_ACCOUNT);
		if (is_array($cats)) {
			foreach ($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
		}
		print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
		print "</td></tr>";
	}

	// Comment
	print '<tr><td>'.$langs->trans("Comment").'</td>';
	print '<td>';
	// Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('account_comment', (GETPOST("account_comment") ? GETPOST("account_comment") : $object->comment), '', 90, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor') && getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_4, '90%');
	$doleditor->Create();
	print '</td></tr>';

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create', $parameters);
	}

	print '</table>';

	print '<br>';

	print '<table class="border centpercent">';

	// Sold
	print '<tr><td class="titlefieldcreate">'.$langs->trans("InitialBankBalance").'</td>';
	print '<td><input size="12" type="text" class="flat" name="solde" value="'.(GETPOST("solde") ? GETPOST("solde") : price2num($object->solde)).'"></td></tr>';

	print '<tr><td>'.$langs->trans("Date").'</td>';
	print '<td>';
	print $form->selectDate('', 're', 0, 0, 0, 'formsoc');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
	print '<td><input size="12" type="text" class="flat" name="account_min_allowed" value="'.(GETPOST("account_min_allowed") ? GETPOST("account_min_allowed") : $object->min_allowed).'"></td></tr>';

	print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
	print '<td><input size="12" type="text" class="flat" name="account_min_desired" value="'.(GETPOST("account_min_desired") ? GETPOST("account_min_desired") : $object->min_desired).'"></td></tr>';

	print '</table>';
	print '<br>';

	$type = (GETPOSTISSET("type") ? GETPOSTINT('type') : Account::TYPE_CURRENT); // add default value
	if ($type == Account::TYPE_SAVINGS || $type == Account::TYPE_CURRENT) {
		print '<table class="border centpercent">';

		// If bank account
		print '<tr><td class="titlefieldcreate">'.$langs->trans("BankName").'</td>';
		print '<td><input type="text" class="flat minwidth150" name="bank" value="'.(GETPOST('bank') ? GETPOST('bank', 'alpha') : $object->bank).'"></td>';
		print '</tr>';

		$ibankey = FormBank::getIBANLabel($object);
		$bickey = "BICNumber";
		if ($object->getCountryCode() == 'IN') {
			$bickey = "SWIFT";
		}

		// IBAN
		print '<tr><td>'.$langs->trans($ibankey).'</td>';
		print '<td><input maxlength="34" type="text" class="flat minwidth300" name="iban" value="'.(GETPOSTISSET('iban') ? GETPOST('iban', 'alpha') : $object->iban).'"></td></tr>';

		// BIC
		print '<tr><td>'.$langs->trans($bickey).'</td>';
		print '<td><input maxlength="11" type="text" class="flat minwidth150" name="bic" value="'.(GETPOSTISSET('bic') ? GETPOST('bic', 'alpha') : $object->bic).'"></td></tr>';

		// Show fields of bank account
		$sizecss = '';
		foreach ($object->getFieldsToShow() as $val) {
			$content = '';
			if ($val == 'BankCode') {
				$name = 'code_banque';
				$sizecss = 'minwidth100';
				$content = $object->code_banque;
			} elseif ($val == 'DeskCode') {
				$name = 'code_guichet';
				$sizecss = 'minwidth100';
				$content = $object->code_guichet;
			} elseif ($val == 'BankAccountNumber') {
				$name = 'number';
				$sizecss = 'minwidth200';
				$content = $object->number;
			} elseif ($val == 'BankAccountNumberKey') {
				$name = 'cle_rib';
				$sizecss = 'minwidth50';
				$content = $object->cle_rib;
			}

			print '<td>'.$langs->trans($val).'</td>';
			print '<td><input type="text" class="flat '.$sizecss.'" name="'.$name.'" value="'.(GETPOSTISSET($name) ? GETPOST($name, 'alpha') : $content).'"></td>';
			print '</tr>';
		}

		if (isModEnabled('paymentbybanktransfer')) {
			if ($mysoc->isInEEC()) {
				print '<tr><td>'.$form->textwithpicto($langs->trans("SEPAXMLPlacePaymentTypeInformationInCreditTransfertransactionInformation"), $langs->trans("SEPAXMLPlacePaymentTypeInformationInCreditTransfertransactionInformationHelp")).'</td>';
				print '<td><input type="checkbox" class="flat" name="pti_in_ctti"'. (empty(GETPOST('pti_in_ctti')) ? '' : ' checked ') . '>';
				print '</td></tr>';
			}
		}
		print '</table>';
		print '<hr>';

		print '<table class="border centpercent">';
		print '<tr><td class="titlefieldcreate">'.$langs->trans("BankAccountOwner").'</td>';
		print '<td><input type="text" class="flat minwidth300" name="proprio" value="'.(GETPOST('proprio') ? GETPOST('proprio', 'alpha') : $object->proprio).'">';
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
		print '<textarea class="flat quatrevingtpercent" name="owner_address" rows="'.ROWS_2.'">';
		print(GETPOST('owner_address') ? GETPOST('owner_address', 'alpha') : $object->owner_address);
		print "</textarea></td></tr>";

		print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerZip").'</td>';
		print '<td><input type="text" class="flat maxwidth100" name="owner_zip" value="'.(GETPOST('owner_zip') ? GETPOST('owner_zip', 'alpha') : $object->owner_zip).'">';
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerTown").'</td>';
		print '<td><input type="text" class="flat maxwidth200" name="owner_town" value="'.(GETPOST('owner_town') ? GETPOST('owner_town', 'alpha') : $object->owner_town).'">';
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerCountry").'</td>';
		print '<td>';
		print img_picto('', 'country', 'class="pictofixedwidth"');
		print $form->select_country(GETPOST('owner_country_id') ? GETPOST('owner_country_id', 'alpha') : $object->owner_country_id, 'owner_country_id');
		print '</td></tr>';

		print '</table>';
		print '<hr>';
	}

	print '<table class="border centpercent">';
	// Accountancy code
	$fieldrequired = '';
	if (getDolGlobalString('MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED')) {
		$fieldrequired = 'fieldrequired ';
	}

	if (isModEnabled('accounting')) {
		print '<tr><td class="'.$fieldrequired.'titlefieldcreate">'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		print img_picto('', 'accounting_account', 'class="pictofixedwidth"');
		print $formaccounting->select_account($object->account_number, 'account_number', 1, '', 1, 1);
		if ($formaccounting->nbaccounts == 0) {
			$langs->load("errors");
			$htmltext = $langs->transnoentitiesnoconv("WarningGoOnAccountancySetupToAddAccounts", $langs->transnoentitiesnoconv("MenuAccountancy"), $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Chartofaccounts"));
			print $form->textwithpicto('', $htmltext);
		}
		print '</td></tr>';
	} else {
		print '<tr><td class="'.$fieldrequired.'titlefieldcreate">'.$langs->trans("AccountancyCode").'</td>';
		print '<td><input type="text" name="account_number" value="'.(GETPOST("account_number") ? GETPOST('account_number', 'alpha') : $object->account_number).'"></td></tr>';
	}

	// Accountancy journal
	if (isModEnabled('accounting')) {
		print '<tr><td>'.$langs->trans("AccountancyJournal").'</td>';
		print '<td>';
		print $formaccounting->select_journal($object->fk_accountancy_journal, 'fk_accountancy_journal', 4, 1, 0, 0);
		print '</td></tr>';
	}

	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("CreateAccount");

	print '</form>';
} else {
	// View and edit mode
	if ((GETPOSTINT("id") || GETPOST("ref")) && $action != 'edit') {
		// Show tabs
		$head = bank_prepare_head($object);

		print dol_get_fiche_head($head, 'bankname', $langs->trans("FinancialAccount"), -1, 'account', 0, '', '', 0, '', 1);

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("DeleteAccount"), $langs->trans("ConfirmDeleteAccount"), "confirm_delete");
		}

		// Print form confirm
		print $formconfirm;

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref = '';
		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		// Type
		print '<tr><td class="titlefield">'.$langs->trans("AccountType").'</td>';
		print '<td>'.$object->type_lib[$object->type].'</td></tr>';

		// Currency
		print '<tr><td>'.$langs->trans("Currency").'</td>';
		print '<td>';
		$selectedcode = $object->currency_code;
		if (!$selectedcode) {
			$selectedcode = $conf->currency;
		}
		print $langs->trans("Currency".$selectedcode);
		print '</td></tr>';

		// Conciliate
		print '<tr><td>'.$langs->trans("Conciliable").'</td>';
		print '<td>';
		$conciliate = $object->canBeConciliated();
		if ($conciliate == -2) {
			print $langs->trans("No").' <span class="opacitymedium">('.$langs->trans("CashAccount").')</span>';
		} elseif ($conciliate == -3) {
			print $langs->trans("No").' <span class="opacitymedium">('.$langs->trans("Closed").')</span>';
		} else {
			print($object->rappro == 1 ? $langs->trans("Yes") : ($langs->trans("No").' <span class="opacitymedium">('.$langs->trans("ConciliationDisabled").')</span>'));
		}
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td>'.$object->min_allowed.'</td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td>'.$object->min_desired.'</td></tr>';

		// Accountancy code
		print '<tr class="liste_titre_add"><td class="titlefield">'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		if (isModEnabled('accounting')) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch('', $object->account_number, 1);

			print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
		} else {
			print $object->account_number;
		}
		print '</td></tr>';

		// Accountancy journal
		if (isModEnabled('accounting')) {
			print '<tr><td>'.$langs->trans("AccountancyJournal").'</td>';
			print '<td>';

			if ($object->fk_accountancy_journal > 0) {
				$accountingjournal = new AccountingJournal($db);
				$accountingjournal->fetch($object->fk_accountancy_journal);

				print $accountingjournal->getNomUrl(0, 1, 1, '', 1);
			}
			print '</td></tr>';
		}

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';

		// Categories
		if (isModEnabled('category')) {
			print '<tr><td class="titlefield">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($object->id, Categorie::TYPE_ACCOUNT, 1);
			print "</td></tr>";
		}

		print '<tr><td class="tdtop titlefield">'.$langs->trans("Comment").'</td>';
		print '<td>'.dol_htmlentitiesbr($object->comment).'</td></tr>';

		print '</table>';

		if ($object->type == Account::TYPE_SAVINGS || $object->type == Account::TYPE_CURRENT) {
			print '<table class="border tableforfield centpercent">';

			print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("BankName").'</td>';
			print '<td>'.$object->bank.'</td></tr>';

			$ibankey = FormBank::getIBANLabel($object);
			$bickey = "BICNumber";
			if ($object->getCountryCode() == 'IN') {
				$bickey = "SWIFT";
			}

			// IBAN
			print '<tr><td>'.$langs->trans($ibankey).'</td>';
			print '<td>'.getIbanHumanReadable($object).'&nbsp;';
			if (!empty($object->iban)) {
				if (!checkIbanForAccount($object)) {
					print img_picto($langs->trans("IbanNotValid"), 'warning');
				} else {
					print img_picto($langs->trans("IbanValid"), 'tick');
				}
			}
			print '</td></tr>';

			// BIC
			print '<tr><td>'.$langs->trans($bickey).'</td>';
			print '<td>'.$object->bic.'&nbsp;';
			if (!empty($object->bic)) {
				if (!checkSwiftForAccount($object)) {
					print img_picto($langs->trans("SwiftNotValid"), 'warning');
				} else {
					print img_picto($langs->trans("SwiftValid"), 'tick');
				}
			}
			print '</td></tr>';

			// TODO Add a link "Show more..." for all other information.

			// Show fields of bank account
			foreach ($object->getFieldsToShow() as $val) {
				$content = '';
				if ($val == 'BankCode') {
					$content = $object->code_banque;
				} elseif ($val == 'DeskCode') {
					$content = $object->code_guichet;
				} elseif ($val == 'BankAccountNumber') {
					$content = $object->number;
				} elseif ($val == 'BankAccountNumberKey') {
					$content = $object->cle_rib;
				}

				print '<tr><td>'.$langs->trans($val).'</td>';
				print '<td>'.$content.'</td>';
				print '</tr>';
			}

			if (isModEnabled('prelevement')) {
				print '<tr><td>'.$form->textwithpicto($langs->trans("ICS"), $langs->trans("ICS").' ('.$langs->trans("UsedFor", $langs->transnoentitiesnoconv("StandingOrder")).')').'</td>';
				print '<td>'.$object->ics.'</td>';
				print '</tr>';
			}

			if (isModEnabled('paymentbybanktransfer')) {
				if (getDolGlobalString("SEPA_USE_IDS")) {	// Use another ICS for bank transfer
					print '<tr><td>'.$form->textwithpicto($langs->trans("IDS"), $langs->trans("IDS").' ('.$langs->trans("UsedFor", $langs->transnoentitiesnoconv("BankTransfer")).')').'</td>';
					print '<td>'.$object->ics_transfer.'</td>';
					print '</tr>';
				}
				if ($mysoc->isInEEC()) {
					print '<tr><td>'.$form->textwithpicto($langs->trans("SEPAXMLPlacePaymentTypeInformationInCreditTransfertransactionInformation"), $langs->trans("SEPAXMLPlacePaymentTypeInformationInCreditTransfertransactionInformationHelp")).'</td><td>';
					print(empty($object->pti_in_ctti) ? $langs->trans("No") : $langs->trans("Yes"));
					print "</td></tr>\n";
				}
			}

			print '<tr><td>'.$langs->trans("BankAccountOwner").'</td><td>';
			print dol_escape_htmltag($object->owner_name);
			print "</td></tr>\n";

			print '<tr><td>'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
			print nl2br($object->owner_address);
			print "</td></tr>\n";

			print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerZip").'</td>';
			print '<td>'.dol_escape_htmltag($object->owner_zip);
			print '</td></tr>';

			print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerTown").'</td>';
			print '<td>'.dol_escape_htmltag($object->owner_town);
			print '</td></tr>';

			print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerCountry").'</td>';
			print '<td>';
			$object->owner_country_code = dol_getIdFromCode($db, $object->owner_country_id, 'c_country', 'rowid', 'code');
			$langs->load("dict");
			print(empty($object->owner_country_code) ? '' : $langs->convToOutputCharset($langs->transnoentitiesnoconv("Country".$object->owner_country_code)));

			print '</td></tr>';

			print '</table>';
		}

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		print dol_get_fiche_end();

		/*
		 * Action bar
		 */
		print '<div class="tabsAction">';

		if ($user->hasRight('banque', 'configurer')) {
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Modify").'</a>';
		}

		$canbedeleted = $object->can_be_deleted(); // Return true if account without movements
		if ($user->hasRight('banque', 'configurer') && $canbedeleted) {
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Delete").'</a>';
		}

		print '</div>';
	}

	/* ************************************************************************** */
	/*                                                                            */
	/* Edition                                                                    */
	/*                                                                            */
	/* ************************************************************************** */

	if (GETPOSTINT('id') && $action == 'edit' && $user->hasRight('banque', 'configurer')) {
		print load_fiche_titre($langs->trans("EditFinancialAccount"), '', 'bank_account');

		if ($conf->use_javascript_ajax) {
			print "\n".'<script type="text/javascript">';
			print 'jQuery(document).ready(function () {
                        jQuery("#type").change(function() {
                            document.formsoc.action.value="edit";
                            document.formsoc.submit();
                        });
                   })'."\n";

			print 'jQuery(document).ready(function () {
                        jQuery("#selectaccount_country_id").change(function() {
                            document.formsoc.action.value="edit";
                            document.formsoc.submit();
                        });
                   })';
			print '</script>'."\n";
		}

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post" name="formsoc">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.GETPOSTINT("id").'">'."\n\n";
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

		print dol_get_fiche_head(array(), 0, '', 0);

		//print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfieldcreate">';

		// Ref
		print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Ref").'</td>';
		print '<td><input type="text" class="flat maxwidth200" name="ref" value="'.dol_escape_htmltag(GETPOSTISSET('ref') ? GETPOST('ref', 'alpha') : $object->ref).'"></td></tr>';

		// Label
		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
		print '<td><input type="text" class="flat minwidth300" name="label" value="'.dol_escape_htmltag(GETPOSTISSET('label') ? GETPOST('label', 'alpha') : $object->label).'"></td></tr>';

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("AccountType").'</td>';
		print '<td class="maxwidth200onsmartphone">';
		$formbank->selectTypeOfBankAccount((GETPOSTISSET('type') ? GETPOSTINT('type') : $object->type), 'type');
		print '</td></tr>';

		// Currency
		print '<tr><td class="fieldrequired">'.$langs->trans("Currency");
		print '<input type="hidden" value="'.$object->currency_code.'">';
		print '</td>';
		print '<td class="maxwidth200onsmartphone">';
		$selectedcode = $object->currency_code;
		if (!$selectedcode) {
			$selectedcode = $conf->currency;
		}
		print img_picto('', 'multicurrency', 'class="pictofixedwidth"');
		print $form->selectCurrency((GETPOSTISSET("account_currency_code") ? GETPOST("account_currency_code") : $selectedcode), 'account_currency_code');
		//print $langs->trans("Currency".$conf->currency);
		//print '<input type="hidden" name="account_currency_code" value="'.$conf->currency.'">';
		print '</td></tr>';

		// Status
		print '<tr><td class="fieldrequired">'.$langs->trans("Status").'</td>';
		print '<td class="maxwidth200onsmartphone">';
		print $form->selectarray("clos", $object->status, (GETPOSTISSET("clos") ? GETPOSTINT("clos") : $object->clos));
		print '</td></tr>';

		// Country
		$object->country_id = $object->country_id ? $object->country_id : $mysoc->country_id;
		$selectedcode = $object->country_code;
		if (GETPOSTISSET("account_country_id")) {
			$selectedcode = GETPOST("account_country_id");
		} elseif (empty($selectedcode)) {
			$selectedcode = $mysoc->country_code;
		}
		$object->country_code = getCountry($selectedcode, 2); // Force country code on account to have following field on bank fields matching country rules

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
			print $formcompany->select_state(GETPOSTISSET("account_state_id") ? GETPOST("account_state_id") : $object->state_id, $selectedcode, 'account_state_id');
		} else {
			print $countrynotdefined;
		}
		print '</td></tr>';

		$type = (GETPOSTISSET('type') ? GETPOSTINT('type') : $object->type); // add default current value
		if ($type == Account::TYPE_SAVINGS || $type == Account::TYPE_CURRENT) {
			print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td>';
			print '<textarea class="flat quatrevingtpercent" name="account_address" rows="'.ROWS_2.'">';
			print $object->address;
			print "</textarea></td></tr>";
		}

		// Conciliable
		print '<tr><td>'.$langs->trans("Conciliable").'</td>';
		print '<td>';
		$conciliate = $object->canBeConciliated();
		if ($conciliate == -2) {
			print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
		} elseif ($conciliate == -3) {
			print $langs->trans("No").' ('.$langs->trans("Closed").')';
		} else {
			print '<input type="checkbox" class="flat" id="norappro" name="norappro"'.(($conciliate > 0) ? '' : ' checked="checked"').'"> <label for="norappro" class="opacitymedium">'.$langs->trans("DisableConciliation").'</label>';
		}
		print '</td></tr>';

		// Balance
		print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td><input size="12" type="text" class="flat" name="account_min_allowed" value="'.(GETPOSTISSET("account_min_allowed") ? GETPOST("account_min_allowed") : $object->min_allowed).'"></td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td><input size="12" type="text" class="flat" name="account_min_desired" value="'.(GETPOSTISSET("account_min_desired") ? GETPOST("account_min_desired") : $object->min_desired).'"></td></tr>';

		// Web
		print '<tr><td>'.$langs->trans("Web").'</td>';
		print '<td>';
		print img_picto('', 'globe', 'class="pictofixedwidth"');
		print '<input class="maxwidth200onsmartphone" type="text" class="flat" name="url" value="'.(GETPOSTISSET("url") ? GETPOST("url") : $object->url).'">';
		print '</td></tr>';

		// Tags-Categories
		if (isModEnabled('category')) {
			print '<tr><td>'.$langs->trans("Categories").'</td><td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_ACCOUNT, '', 'parent', 64, 0, 3);

			$arrayselected = array();
			$c = new Categorie($db);
			$cats = $c->containing($object->id, Categorie::TYPE_ACCOUNT);
			if (is_array($cats)) {
				foreach ($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
			}
			print img_picto('', 'category').$form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, 'quatrevingtpercent widthcentpercentminusx', 0, 0);
			print "</td></tr>";
		}

		// Comment
		print '<tr><td class="tdtop">'.$langs->trans("Comment").'</td>';
		print '<td>';
		// Editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('account_comment', (GETPOST("account_comment") ? GETPOST("account_comment") : $object->comment), '', 90, 'dolibarr_notes', '', false, true, isModEnabled('fckeditor') && getDolGlobalInt('FCKEDITOR_ENABLE_SOCIETE'), ROWS_4, '95%');
		$doleditor->Create();
		print '</td></tr>';

		// Other attributes
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook)) {
			print $object->showOptionals($extrafields, 'edit', $parameters);
		}

		print '</table>';
		print '<hr>';


		//print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		// Accountancy code
		$tdextra = ' class="titlefieldcreate"';

		if (getDolGlobalString('MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED')) {
			$tdextra = ' class="fieldrequired titlefieldcreate"';
		}

		print '<tr><td'.$tdextra.'>'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		if (isModEnabled('accounting')) {
			print img_picto('', 'accounting_account', 'class="pictofixedwidth"');
			print $formaccounting->select_account($object->account_number, 'account_number', 1, '', 1, 1);
			if ($formaccounting->nbaccounts == 0) {
				$langs->load("errors");
				$htmltext = $langs->transnoentitiesnoconv("WarningGoOnAccountancySetupToAddAccounts", $langs->transnoentitiesnoconv("MenuAccountancy"), $langs->transnoentitiesnoconv("Setup"), $langs->transnoentitiesnoconv("Chartofaccounts"));
				print $form->textwithpicto('', $htmltext);
			}
		} else {
			print '<input type="text" name="account_number" value="'.(GETPOST("account_number") ? GETPOST("account_number") : $object->account_number).'">';
		}
		print '</td></tr>';

		// Accountancy journal
		if (isModEnabled('accounting')) {
			print '<tr><td class="fieldrequired">'.$langs->trans("AccountancyJournal").'</td>';
			print '<td>';
			print $formaccounting->select_journal($object->fk_accountancy_journal, 'fk_accountancy_journal', 4, 1, 0, 0);
			print '</td></tr>';
		}

		print '</table>';

		$type = (GETPOSTISSET('type') ? GETPOSTINT('type') : $object->type); // add default current value
		if ($type == Account::TYPE_SAVINGS || $type == Account::TYPE_CURRENT) {
			print '<br>';

			print '<table class="border centpercent">';

			// If bank account
			print '<tr class="liste_titre_add"><td class="titlefieldcreate">'.$langs->trans("BankName").'</td>';
			print '<td><input type="text" class="flat width300" name="bank" value="'.$object->bank.'"></td>';
			print '</tr>';

			$ibankey = FormBank::getIBANLabel($object);
			$bickey = "BICNumber";
			if ($object->getCountryCode() == 'IN') {
				$bickey = "SWIFT";
			}

			// IBAN
			print '<tr><td>';
			$tooltip = $langs->trans("Example").':<br>CH93 0076 2011 6238 5295 7<br>LT12 1000 0111 0100 1000<br>FR14 2004 1010 0505 0001 3M02 606<br>LU28 0019 4006 4475 0000<br>DE89 3704 0044 0532 0130 00';
			print $form->textwithpicto($langs->trans($ibankey), $tooltip, 1, 'help', '', 0, 3, 'iban');
			print '</td>';
			print '<td><input class="minwidth300 maxwidth200onsmartphone" maxlength="34" type="text" class="flat" name="iban" value="'.(GETPOSTISSET('iban') ? GETPOST('iban', 'alphanohtml') : $object->iban).'"></td></tr>';

			// BIC
			print '<tr><td>';
			$tooltip = $langs->trans("Example").': LIABLT2XXXX';
			print $form->textwithpicto($langs->trans($bickey), $tooltip, 1, 'help', '', 0, 3, 'bic');
			print '</td>';
			print '<td><input class="minwidth150 maxwidth200onsmartphone" maxlength="11" type="text" class="flat" name="bic" value="'.(GETPOSTISSET('bic') ? GETPOST('bic', 'alphanohtml') : $object->bic).'"></td></tr>';

			// Show fields of bank account
			foreach ($object->getFieldsToShow() as $val) {
				$content = '';
				if ($val == 'BankCode') {
					$name = 'code_banque';
					$css = 'width100';
					$content = $object->code_banque;
				} elseif ($val == 'DeskCode') {
					$name = 'code_guichet';
					$css = 'width100';
					$content = $object->code_guichet;
				} elseif ($val == 'BankAccountNumber') {
					$name = 'number';
					$css = 'width200';
					$content = $object->number;
				} elseif ($val == 'BankAccountNumberKey') {
					$name = 'cle_rib';
					$css = 'width50';
					$content = $object->cle_rib;
				}

				print '<tr><td>'.$langs->trans($val).'</td>';
				print '<td><input type="text" class="flat '.$css.'" name="'.$name.'" value="'.dol_escape_htmltag($content).'"></td>';
				print '</tr>';
			}

			if (isModEnabled('prelevement')) {
				print '<tr><td>'.$form->textwithpicto($langs->trans("ICS"), $langs->trans("ICS").' ('.$langs->trans("UsedFor", $langs->transnoentitiesnoconv("StandingOrder")).')').'</td>';
				print '<td><input class="minwidth150 maxwidth200onsmartphone" maxlength="32" type="text" class="flat" name="ics" value="'.(GETPOSTISSET('ics') ? GETPOST('ics', 'alphanohtml') : $object->ics).'"></td></tr>';
			}

			if (isModEnabled('paymentbybanktransfer')) {
				if (getDolGlobalString("SEPA_USE_IDS")) {	// ICS is not used with bank transfer !
					print '<tr><td>'.$form->textwithpicto($langs->trans("IDS"), $langs->trans("IDS").' ('.$langs->trans("UsedFor", $langs->transnoentitiesnoconv("BankTransfer")).')').'</td>';
					print '<td><input class="minwidth150 maxwidth200onsmartphone" maxlength="32" type="text" class="flat" name="ics_transfer" value="'.(GETPOSTISSET('ics_transfer') ? GETPOST('ics_transfer', 'alphanohtml') : $object->ics_transfer).'"></td></tr>';
				}
				if ($mysoc->isInEEC()) {
					print '<tr><td>'.$form->textwithpicto($langs->trans("SEPAXMLPlacePaymentTypeInformationInCreditTransfertransactionInformation"), $langs->trans("SEPAXMLPlacePaymentTypeInformationInCreditTransfertransactionInformationHelp")).'</td>';
					print '<td><input type="checkbox" class="flat" name="pti_in_ctti"'. ($object->pti_in_ctti ? ' checked ' : '') . '>';
					print '</td></tr>';
				}
			}

			print '</table>';

			print '<hr>';

			print '<table class="border centpercent">';

			print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
			print '<td><input class="maxwidth200onsmartphone" type="text" class="flat" name="proprio" value="'.$object->proprio.'"></td>';
			print '</tr>';

			print '<tr><td class="titlefieldcreate tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
			print '<textarea class="flat quatrevingtpercent" name="owner_address" rows="'.ROWS_2.'">';
			print $object->owner_address;
			print "</textarea></td></tr>";

			print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerZip").'</td>';
			print '<td><input type="text" class="flat maxwidth100" name="owner_zip" value="'.(GETPOST('owner_zip') ? GETPOST('owner_zip', 'alpha') : $object->owner_zip).'">';
			print '</td></tr>';

			print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerTown").'</td>';
			print '<td><input type="text" class="flat maxwidth200" name="owner_town" value="'.(GETPOST('owner_town') ? GETPOST('owner_town', 'alpha') : $object->owner_town).'">';
			print '</td></tr>';

			print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerCountry").'</td>';
			print '<td>';
			print img_picto('', 'country', 'class="pictofixedwidth"');
			print $form->select_country(GETPOST('owner_country_id') ? GETPOST('owner_country_id', 'alpha') : $object->owner_country_id, 'owner_country_id');
			print '</td></tr>';

			print '</table>';
		}

		print dol_get_fiche_end();

		print $form->buttonsSaveCancel("Modify");

		print '</form>';
	}
}

// End of page
llxFooter();
$db->close();
