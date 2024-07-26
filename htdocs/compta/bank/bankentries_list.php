<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2024  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
 * Copyright (C) 2014       Florian Henry        <florian.henry@open-cooncept.pro>
 * Copyright (C) 2015       Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017-2019  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2021       Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
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
 *	\file       htdocs/compta/bank/bankentries_list.php
 *	\ingroup    banque
 *	\brief      List of bank transactions
 */

// Load Dolibarr environment
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/paymentvat.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/paymentsocialcontribution.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';

// Load translation files required by the page
$langs->loadLangs(array("banks", "bills", "categories", "companies", "margins", "salaries", "loan", "donations", "trips", "members", "compta", "accountancy"));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = 'bankentrieslist';
$massaction = GETPOST('massaction', 'alpha');
$optioncss = GETPOST('optioncss', 'aZ09');
$mode = GETPOST('mode', 'aZ');

$dateop = dol_mktime(12, 0, 0, GETPOSTINT("opmonth"), GETPOSTINT("opday"), GETPOSTINT("opyear"));
$search_debit = GETPOST("search_debit", 'alpha');
$search_credit = GETPOST("search_credit", 'alpha');
$search_type = GETPOST("search_type", 'alpha');
$search_account = GETPOST("search_account", 'int') ? GETPOST("search_account", 'int') : GETPOST("account", 'int');
$search_accountancy_code = GETPOST('search_accountancy_code', 'alpha') ? GETPOST('search_accountancy_code', 'alpha') : GETPOST('accountancy_code', 'alpha');
$search_bid = GETPOST("search_bid", 'int') ? GETPOST("search_bid", 'int') : GETPOST("bid", 'int');		// Category id
$search_ref = GETPOST('search_ref', 'alpha');
$search_description = GETPOST("search_description", 'alpha');
$search_dt_start = dol_mktime(0, 0, 0, GETPOSTINT('search_start_dtmonth'), GETPOSTINT('search_start_dtday'), GETPOSTINT('search_start_dtyear'));
$search_dt_end = dol_mktime(0, 0, 0, GETPOSTINT('search_end_dtmonth'), GETPOSTINT('search_end_dtday'), GETPOSTINT('search_end_dtyear'));
$search_dv_start = dol_mktime(0, 0, 0, GETPOSTINT('search_start_dvmonth'), GETPOSTINT('search_start_dvday'), GETPOSTINT('search_start_dvyear'));
$search_dv_end = dol_mktime(0, 0, 0, GETPOSTINT('search_end_dvmonth'), GETPOSTINT('search_end_dvday'), GETPOSTINT('search_end_dvyear'));
$search_thirdparty_user = GETPOST("search_thirdparty", 'alpha') ? GETPOST("search_thirdparty", 'alpha') : GETPOST("thirdparty", 'alpha');
$search_req_nb = GETPOST("req_nb", 'alpha');
$search_num_releve = GETPOST("search_num_releve", 'alpha');
$search_conciliated = GETPOST("search_conciliated", 'int');
$search_fk_bordereau = GETPOST("search_fk_bordereau", 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$toselect = GETPOST('toselect', 'array');
$num_releve = GETPOST("num_releve", "alpha");
if (empty($dateop)) {
	$dateop = -1;
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
$pageplusone = GETPOSTINT("pageplusone");
if ($pageplusone) {
	$page = $pageplusone - 1;
}
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = 'desc,desc,desc';
}
if (!$sortfield) {
	$sortfield = 'b.datev,b.dateo,b.rowid';
}

$object = new Account($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	$search_account = $object->id; // Force the search field on id of account

	if (!($object->id > 0)) {
		$langs->load("errors");
		print($langs->trans('ErrorRecordNotFound'));
		exit;
	}
}

// redefine contextpage to depend on bank account
$contextpage = 'banktransactionlist'.(empty($object->id) ? '' : '-'.$object->id);

$mode_balance_ok = false;
//if (($sortfield == 'b.datev' || $sortfield == 'b.datev,b.dateo,b.rowid'))    // TODO Manage balance when account not selected
if (($sortfield == 'b.datev' || $sortfield == 'b.datev,b.dateo,b.rowid')) {
	$sortfield = 'b.datev,b.dateo,b.rowid';
	if ($id > 0 || !empty($ref) || $search_account > 0) {
		$mode_balance_ok = true;
	}
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('banktransactionlist', $contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label('banktransaction');
$search_array_options = $extrafields->getOptionalsFromPost('banktransaction', '', 'search_');

$arrayfields = array(
	'b.rowid' => array('label' => $langs->trans("Ref"), 'checked' => 1,'position' => 10),
	'b.label' => array('label' => $langs->trans("Description"), 'checked' => 1,'position' => 20),
	'b.dateo' => array('label' => $langs->trans("DateOperationShort"), 'checked' => -1,'position' => 30),
	'b.datev' => array('label' => $langs->trans("DateValueShort"), 'checked' => 1,'position' => 40),
	'type' => array('label' => $langs->trans("Type"), 'checked' => 1,'position' => 50),
	'b.num_chq' => array('label' => $langs->trans("Numero"), 'checked' => 1,'position' => 60),
	'bu.label' => array('label' => $langs->trans("ThirdParty").'/'.$langs->trans("User"), 'checked' => 1, 'position' => 70),
	'ba.ref' => array('label' => $langs->trans("BankAccount"), 'checked' => (($id > 0 || !empty($ref)) ? 0 : 1), 'position' => 80),
	'b.debit' => array('label' => $langs->trans("Debit"), 'checked' => 1, 'position' => 90),
	'b.credit' => array('label' => $langs->trans("Credit"), 'checked' => 1, 'position' => 100),
	'balancebefore' => array('label' => $langs->trans("BalanceBefore"), 'checked' => 0, 'position' => 110),
	'balance' => array('label' => $langs->trans("Balance"), 'checked' => 1, 'position' => 120),
	'b.num_releve' => array('label' => $langs->trans("AccountStatement"), 'checked' => 1, 'position' => 130),
	'b.conciliated' => array('label' => $langs->trans("BankLineReconciled"), 'enabled' => $object->rappro, 'checked' => ($action == 'reconcile' ? 1 : 0), 'position' => 140),
	'b.fk_bordereau' => array('label' => $langs->trans("ChequeNumber"), 'checked' => 0, 'position' => 150),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($fieldvalue) {
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'banque', $fieldvalue, 'bank_account&bank_account', '', '', $fieldtype);
} else {
	if ($user->socid) {
		$socid = $user->socid;
	}
	$result = restrictedArea($user, 'banque');
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_dt_start = '';
	$search_dt_end = '';
	$search_dv_start = '';
	$search_dv_end = '';
	$search_type = "";
	$search_debit = "";
	$search_credit = "";
	$search_bid = "";
	$search_ref = "";
	$search_req_nb = '';
	$search_description = '';
	$search_thirdparty_user = '';
	$search_num_releve = '';
	$search_conciliated = '';
	$search_fk_bordereau = '';
	$toselect = array();

	$search_account = "";
	if ($id > 0 || !empty($ref)) {
		$search_account = $object->id;
	}
}

if (empty($reshook)) {
	$objectclass = 'Account';
	$objectlabel = 'BankTransaction';
	$permissiontoread = $user->hasRight('banque', 'lire');
	$permissiontodelete = $user->hasRight('banque', 'modifier');
	$uploaddir = $conf->bank->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

$rowids = GETPOST('rowid', 'array');

// Conciliation
if ((GETPOST('confirm_savestatement', 'alpha') || GETPOST('confirm_reconcile', 'alpha'))
	&& (GETPOST("num_releve", "alpha") || !empty($rowids))
	&& $user->hasRight('banque', 'consolidate')
	&& (!GETPOSTISSET('pageplusone') || (GETPOST('pageplusone') == GETPOST('pageplusoneold')))) {
	$error = 0;

	// Definition, nettoyage parameters
	$num_releve = GETPOST("num_releve", "alpha");

	if ($num_releve) {
		$bankline = new AccountLine($db);

		$rowids = GETPOST('rowid', 'array');

		if (!empty($rowids) && is_array($rowids)) {
			foreach ($rowids as $row) {
				if ($row > 0) {
					$result = $bankline->fetch($row);
					$bankline->num_releve = $num_releve; // GETPOST("num_releve");
					$result = $bankline->update_conciliation($user, GETPOST("cat"), GETPOST('confirm_reconcile', 'alpha') ? 1 : 0); // If we confirm_reconcile, we set flag 'rappro' to 1.
					if ($result < 0) {
						setEventMessages($bankline->error, $bankline->errors, 'errors');
						$error++;
						break;
					}
				}
			}
			if (!$error && count($rowids) > 0) {
				setEventMessages($langs->trans("XNewLinesConciliated", count($rowids)), null);
			}
		} else {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("NoRecordSelected"), null, 'errors');
		}
	} else {
		$error++;
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorPleaseTypeBankTransactionReportName"), null, 'errors');
	}

	if (!$error) {
		$param = 'action=reconcile&contextpage=banktransactionlist&id='.((int) $object->id).'&search_account='.((int) $object->id);
		if ($page) {
			$param .= '&page='.urlencode((string) ($page));
		}
		if ($offset) {
			$param .= '&offset='.urlencode((string) ($offset));
		}
		if ($limit) {
			$param .= '&limit='.((int) $limit);
		}
		if ($search_conciliated != '' && $search_conciliated != '-1') {
			$param .= '&search_conciliated='.urlencode((string) ($search_conciliated));
		}
		if ($search_thirdparty_user) {
			$param .= '&search_thirdparty='.urlencode($search_thirdparty_user);
		}
		if ($search_num_releve) {
			$param .= '&search_num_releve='.urlencode($search_num_releve);
		}
		if ($search_description) {
			$param .= '&search_description='.urlencode($search_description);
		}
		if (dol_strlen($search_dt_start) > 0) {
			$param .= '&search_start_dtmonth='.GETPOSTINT('search_start_dtmonth').'&search_start_dtday='.GETPOSTINT('search_start_dtday').'&search_start_dtyear='.GETPOSTINT('search_start_dtyear');
		}
		if (dol_strlen($search_dt_end) > 0) {
			$param .= '&search_end_dtmonth='.GETPOSTINT('search_end_dtmonth').'&search_end_dtday='.GETPOSTINT('search_end_dtday').'&search_end_dtyear='.GETPOSTINT('search_end_dtyear');
		}
		if (dol_strlen($search_dv_start) > 0) {
			$param .= '&search_start_dvmonth='.GETPOSTINT('search_start_dvmonth').'&search_start_dvday='.GETPOSTINT('search_start_dvday').'&search_start_dvyear='.GETPOSTINT('search_start_dvyear');
		}
		if (dol_strlen($search_dv_end) > 0) {
			$param .= '&search_end_dvmonth='.GETPOSTINT('search_end_dvmonth').'&search_end_dvday='.GETPOSTINT('search_end_dvday').'&search_end_dvyear='.GETPOSTINT('search_end_dvyear');
		}
		if ($search_type) {
			$param .= '&search_type='.urlencode($search_type);
		}
		if ($search_debit) {
			$param .= '&search_debit='.urlencode($search_debit);
		}
		if ($search_credit) {
			$param .= '&search_credit='.urlencode($search_credit);
		}
		$param .= '&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder);
		header('Location: '.$_SERVER["PHP_SELF"].'?'.$param); // To avoid to submit twice and allow the back button
		exit;
	}
}


if (GETPOST('save') && !$cancel && $user->hasRight('banque', 'modifier')) {
	$error = 0;

	if (price2num(GETPOST("addcredit")) > 0) {
		$amount = price2num(GETPOST("addcredit"));
	} else {
		$amount = price2num(-1 * (float) price2num(GETPOST("adddebit")));
	}

	$operation = GETPOST("operation", 'alpha');
	$num_chq   = GETPOST("num_chq", 'alpha');
	$label     = GETPOST("label", 'alpha');
	$cat1      = GETPOST("cat1", 'alpha');

	$bankaccountid = $id;
	if (GETPOSTINT('add_account') > 0) {
		$bankaccountid = GETPOSTINT('add_account');
	}
	if (!$dateop) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
	}
	if (!$operation) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
	}
	if (!$label) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Label")), null, 'errors');
	}
	if (!$amount) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
	}
	if (!($bankaccountid > 0)) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
	}
	/*if (isModEnabled('accounting') && (empty($search_accountancy_code) || $search_accountancy_code == '-1'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), null, 'errors');
		$error++;
	}*/

	if (!$error && getDolGlobalString('BANK_USE_OLD_VARIOUS_PAYMENT')) {
		$objecttmp = new Account($db);
		$objecttmp->fetch($bankaccountid);
		$insertid = $objecttmp->addline($dateop, $operation, $label, $amount, $num_chq, ($cat1 > 0 ? $cat1 : 0), $user, '', '', $search_accountancy_code);
		if ($insertid > 0) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			header("Location: ".$_SERVER['PHP_SELF'].($id ? "?id=".$id : ''));
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} else {
		$action = 'addline';
	}
}

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('banque', 'modifier')) {
	$accline = new AccountLine($db);
	$result = $accline->fetch(GETPOSTINT("rowid"));
	$result = $accline->delete($user);
	if ($result <= 0) {
		setEventMessages($accline->error, $accline->errors, 'errors');
	} else {
		setEventMessages('RecordDeleted', null, 'mesgs');
	}
}

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);
$formaccounting = new FormAccounting($db);

$companystatic = new Societe($db);
$bankaccountstatic = new Account($db);
$userstatic = new User($db);

$banktransferstatic = new BonPrelevement($db);
$societestatic = new Societe($db);
$userstatic = new User($db);
$chargestatic = new ChargeSociales($db);
$loanstatic = new Loan($db);
$memberstatic = new Adherent($db);
$donstatic = new Don($db);
$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$paymentscstatic = new PaymentSocialContribution($db);
$paymentvatstatic = new PaymentVAT($db);
$paymentsalstatic = new PaymentSalary($db);
$paymentdonationstatic = new PaymentDonation($db);
$paymentvariousstatic = new PaymentVarious($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);
$bankstatic = new Account($db);
$banklinestatic = new AccountLine($db);
$bordereaustatic = new RemiseCheque($db);

$now = dol_now();

// Must be before button action
$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($id > 0) {
	$param .= '&id='.urlencode((string) ($id));
}
if (!empty($ref)) {
	$param .= '&ref='.urlencode($ref);
}
if (!empty($search_ref)) {
	$param .= '&search_ref='.urlencode($search_ref);
}
if (!empty($search_description)) {
	$param .= '&search_description='.urlencode($search_description);
}
if (!empty($search_type)) {
	$param .= '&type='.urlencode($search_type);
}
if (!empty($search_thirdparty_user)) {
	$param .= '&search_thirdparty='.urlencode($search_thirdparty_user);
}
if (!empty($search_debit)) {
	$param .= '&search_debit='.urlencode($search_debit);
}
if (!empty($search_credit)) {
	$param .= '&search_credit='.urlencode($search_credit);
}
if ($search_account > 0) {
	$param .= '&search_account='.((int) $search_account);
}
if (!empty($search_num_releve)) {
	$param .= '&search_num_releve='.urlencode($search_num_releve);
}
if ($search_conciliated != '' && $search_conciliated != '-1') {
	$param .= '&search_conciliated='.urlencode((string) ($search_conciliated));
}
if ($search_fk_bordereau > 0) {
	$param .= '$&search_fk_bordereau='.urlencode((string) ($search_fk_bordereau));
}
if ($search_bid > 0) {	// Category id
	$param .= '&search_bid='.((int) $search_bid);
}
if (dol_strlen($search_dt_start) > 0) {
	$param .= '&search_start_dtmonth='.GETPOSTINT('search_start_dtmonth').'&search_start_dtday='.GETPOSTINT('search_start_dtday').'&search_start_dtyear='.GETPOSTINT('search_start_dtyear');
}
if (dol_strlen($search_dt_end) > 0) {
	$param .= '&search_end_dtmonth='.GETPOSTINT('search_end_dtmonth').'&search_end_dtday='.GETPOSTINT('search_end_dtday').'&search_end_dtyear='.GETPOSTINT('search_end_dtyear');
}
if (dol_strlen($search_dv_start) > 0) {
	$param .= '&search_start_dvmonth='.GETPOSTINT('search_start_dvmonth').'&search_start_dvday='.GETPOSTINT('search_start_dvday').'&search_start_dvyear='.GETPOSTINT('search_start_dvyear');
}
if (dol_strlen($search_dv_end) > 0) {
	$param .= '&search_end_dvmonth='.GETPOSTINT('search_end_dvmonth').'&search_end_dvday='.GETPOSTINT('search_end_dvday').'&search_end_dvyear='.GETPOSTINT('search_end_dvyear');
}
if ($search_req_nb) {
	$param .= '&req_nb='.urlencode($search_req_nb);
}
if (GETPOSTINT("search_thirdparty")) {
	$param .= '&thirdparty='.urlencode((string) (GETPOSTINT("search_thirdparty")));
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($action == 'reconcile') {
	$param .= '&action=reconcile';
}
$totalarray = array(
	'nbfield' => 0,
	'totalcred' => 0,
	'totaldeb' => 0,
);
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$options = array();

$buttonreconcile = '';
$morehtmlref = '';

if ($id > 0 || !empty($ref)) {
	$title = $object->ref.' - '.$langs->trans("Transactions");
} else {
	$title = $langs->trans("BankTransactions");
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, array(), array(), $param);


if ($id > 0 || !empty($ref)) {
	// Load bank groups
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
	$bankcateg = new BankCateg($db);

	$arrayofbankcateg = $bankcateg->fetchAll();
	foreach ($arrayofbankcateg as $bankcategory) {
		$options[$bankcategory->id] = $bankcategory->label;
	}

	// Bank card
	$head = bank_prepare_head($object);
	print dol_get_fiche_head($head, 'journal', $langs->trans("FinancialAccount"), 0, 'account');

	$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

	print dol_get_fiche_end();


	/*
	 * Buttons actions
	 */

	if ($action != 'reconcile') {
		if ($object->canBeConciliated() > 0) {
			$allowautomaticconciliation = false; // TODO
			$titletoconciliatemanual = $langs->trans("Conciliate");
			$titletoconciliateauto = $langs->trans("Conciliate");
			if ($allowautomaticconciliation) {
				$titletoconciliatemanual .= ' ('.$langs->trans("Manual").')';
				$titletoconciliateauto .= ' ('.$langs->trans("Auto").')';
			}

			// If not cash account and can be reconciliate
			if ($user->hasRight('banque', 'consolidate')) {
				$newparam = $param;
				$newparam = preg_replace('/search_conciliated=\d+/i', '', $newparam);
				$buttonreconcile = '<a class="butAction" style="margin-bottom: 5px !important; margin-top: 5px !important" href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?action=reconcile&sortfield=b.datev,b.dateo,b.rowid&sortorder=asc,asc,asc&search_conciliated=0'.$newparam.'">'.$titletoconciliatemanual.'</a>';
			} else {
				$buttonreconcile = '<a class="butActionRefused" style="margin-bottom: 5px !important; margin-top: 5px !important" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$titletoconciliatemanual.'</a>';
			}

			if ($allowautomaticconciliation) {
				// If not cash account and can be reconciliate
				if ($user->hasRight('banque', 'consolidate')) {
					$newparam = $param;
					$newparam = preg_replace('/search_conciliated=\d+/i', '', $newparam);
					$buttonreconcile .= ' <a class="butAction" style="margin-bottom: 5px !important; margin-top: 5px !important" href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?action=reconcile&sortfield=b.datev,b.dateo,b.rowid&sortorder=asc,asc,asc&search_conciliated=0'.$newparam.'">'.$titletoconciliateauto.'</a>';
				} else {
					$buttonreconcile .= ' <a class="butActionRefused" style="margin-bottom: 5px !important; margin-top: 5px !important" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$titletoconciliateauto.'</a>';
				}
			}
		}
	}
}

$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro as conciliated, b.num_releve, b.num_chq,";
$sql .= " b.fk_account, b.fk_type, b.fk_bordereau,";
$sql .= " ba.rowid as bankid, ba.ref as bankref";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
	}
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ";
if ($search_bid > 0) {
	$sql .= MAIN_DB_PREFIX."category_bankline as l,";
}
$sql .= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql .= " ".MAIN_DB_PREFIX."bank as b";
if (!empty($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (b.rowid = ef.fk_object)";
}

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListJoin', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " WHERE b.fk_account = ba.rowid";
$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
if ($search_account > 0) {
	$sql .= " AND b.fk_account = ".((int) $search_account);
}
// Search period criteria
if (dol_strlen($search_dt_start) > 0) {
	$sql .= " AND b.dateo >= '".$db->idate($search_dt_start)."'";
}
if (dol_strlen($search_dt_end) > 0) {
	$sql .= " AND b.dateo <= '".$db->idate($search_dt_end)."'";
}
// Search period criteria
if (dol_strlen($search_dv_start) > 0) {
	$sql .= " AND b.datev >= '".$db->idate($search_dv_start)."'";
}
if (dol_strlen($search_dv_end) > 0) {
	$sql .= " AND b.datev <= '".$db->idate($search_dv_end)."'";
}
if ($search_ref) {
	$sql .= natural_search("b.rowid", $search_ref, 1);
}
if ($search_req_nb) {
	$sql .= natural_search("b.num_chq", $search_req_nb);
}
if ($search_num_releve) {
	$sql .= natural_search("b.num_releve", $search_num_releve);
}
if ($search_conciliated != '' && $search_conciliated != '-1') {
	$sql .= " AND b.rappro = ".((int) $search_conciliated);
}
if ($search_fk_bordereau > 0) {
	$sql .= " AND b.fk_bordereau = " . ((int) $search_fk_bordereau);
}
if ($search_thirdparty_user) {
	$sql .= " AND (b.rowid IN ";
	$sql .= " 	( SELECT bu.fk_bank FROM ".MAIN_DB_PREFIX."bank_url AS bu";
	$sql .= "	 JOIN ".MAIN_DB_PREFIX."bank AS b2 ON b2.rowid = bu.fk_bank";
	$sql .= "	 JOIN ".MAIN_DB_PREFIX."user AS subUser ON (bu.type = 'user' AND bu.url_id = subUser.rowid)";
	$sql .= "	  WHERE ". natural_search(array("subUser.firstname", "subUser.lastname"), $search_thirdparty_user, 0, 1).")";

	$sql .= " OR b.rowid IN ";
	$sql .= " 	( SELECT bu.fk_bank FROM ".MAIN_DB_PREFIX."bank_url AS bu";
	$sql .= "	 JOIN ".MAIN_DB_PREFIX."bank AS b2 ON b2.rowid = bu.fk_bank";
	$sql .= "	 JOIN ".MAIN_DB_PREFIX."societe AS subSoc ON (bu.type = 'company' AND bu.url_id = subSoc.rowid)";
	$sql .= "	  WHERE ". natural_search(array("subSoc.nom"), $search_thirdparty_user, 0, 1);
	$sql .= "))";
}
if ($search_description) {
	$search_description_to_use = $search_description;
	$arrayoffixedlabels = array(
		'payment_salary',
		'CustomerInvoicePayment',
		'CustomerInvoicePaymentBack',
		'SupplierInvoicePayment',
		'SupplierInvoicePaymentBack',
		'DonationPayment',
		'ExpenseReportPayment',
		'SocialContributionPayment',
		'SubscriptionPayment',
		'WithdrawalPayment'
	);
	foreach ($arrayoffixedlabels as $keyforlabel) {
		$translatedlabel = $langs->transnoentitiesnoconv($keyforlabel);
		if (preg_match('/'.$search_description.'/i', $translatedlabel)) {
			$search_description_to_use .= "|".$keyforlabel;
		}
	}
	$sql .= natural_search("b.label", $search_description_to_use); // Warning some text are just translation keys, not translated strings
}

if ($search_bid > 0) {
	$sql .= " AND b.rowid = l.lineid AND l.fk_categ = ".((int) $search_bid);
}
if (!empty($search_type)) {
	$sql .= " AND b.fk_type = '".$db->escape($search_type)."'";
}
// Search criteria amount
if ($search_debit) {
	$sql .= natural_search('ABS(b.amount)', $search_debit, 1);
	$sql .= ' AND b.amount <= 0';
}
if ($search_credit) {
	$sql .= natural_search('b.amount', $search_credit, 1);
	$sql .= ' AND b.amount >= 0';
}
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
$nbtotalofpages = 0;
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	$nbtotalofpages = ceil($nbtotalofrecords / $limit);
}

if (($id > 0 || !empty($ref)) && ((string) $page == '')) {
	// We open a list of transaction of a dedicated account and no page was set by default
	// We force on last page.
	$page = ($nbtotalofpages - 1);
	$offset = $limit * $page;
	if ($page < 0) {
		$page = 0;
	}
}
if ($page >= $nbtotalofpages) {
	// If we made a search and result has low page than the page number we were on
	$page = ($nbtotalofpages - 1);
	$offset = $limit * $page;
	if ($page < 0) {
		$page = 0;
	}
}


// If not account defined $mode_balance_ok=false
if (empty($search_account)) {
	$mode_balance_ok = false;
}
// If a search is done $mode_balance_ok=false
if (!empty($search_ref)) {
	$mode_balance_ok = false;
}
if (!empty($search_description)) {
	$mode_balance_ok = false;
}
if (!empty($search_type)) {
	$mode_balance_ok = false;
}
if (!empty($search_debit)) {
	$mode_balance_ok = false;
}
if (!empty($search_credit)) {
	$mode_balance_ok = false;
}
if (!empty($search_thirdparty_user)) {
	$mode_balance_ok = false;
}
if ($search_conciliated != '' && $search_conciliated != '-1') {
	$mode_balance_ok = false;
}
if (!empty($search_num_releve)) {
	$mode_balance_ok = false;
}
if (!empty($search_fk_bordereau)) {
	$mode_balance_ok = false;
}

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;
dol_syslog('compta/bank/bankentries_list.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$arrayofselected = (!empty($toselect) && is_array($toselect)) ? $toselect : array();

	// List of mass actions available
	$arrayofmassactions = array(
		//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
		//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	);
	if (in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	// Confirmation delete
	if ($action == 'delete') {
		$text = $langs->trans('ConfirmDeleteTransaction');
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&rowid='.GETPOSTINT("rowid"), $langs->trans('DeleteTransaction'), $text, 'confirm_delete', null, '', 1);
	}

	// Lines of title fields
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="search_form">'."\n";
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="'.($action != 'delete' ? $action : 'search').'">';
	if (!empty($view)) {
		print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
	}
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="ref" value="'.$ref.'">';
	if (GETPOSTINT('bid')) {
		print '<input type="hidden" name="bid" value="'.GETPOSTINT("bid").'">';
	}

	// Form to add a transaction with no invoice
	if ($user->hasRight('banque', 'modifier') && $action == 'addline' && getDolGlobalString('BANK_USE_OLD_VARIOUS_PAYMENT')) {
		print load_fiche_titre($langs->trans("AddBankRecordLong"), '', '');

		print '<table class="noborder centpercent">';

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Description").'</td>';
		print '<td>'.$langs->trans("Date").'</td>';
		print '<td>&nbsp;</td>';
		print '<td>'.$langs->trans("Type").'</td>';
		print '<td>'.$langs->trans("Numero").'</td>';
		print '<td class=right>'.$langs->trans("BankAccount").'</td>';
		print '<td class=right>'.$langs->trans("Debit").'</td>';
		print '<td class=right>'.$langs->trans("Credit").'</td>';
		/*if (isModEnabled('accounting'))
		{
			print '<td class="center">';
			print $langs->trans("AccountAccounting");
			print '</td>';
		}*/
		print '<td class="center">&nbsp;</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print '<input name="label" class="flat minwidth200" type="text" value="'.GETPOST("label", "alpha").'">';
		if (is_array($options) && count($options)) {
			print '<br>'.$langs->trans("Rubrique").': ';
			print Form::selectarray('cat1', $options, GETPOST('cat1'), 1);
		}
		print '</td>';
		print '<td class="nowrap">';
		print $form->selectDate(empty($dateop) ? -1 : $dateop, 'op', 0, 0, 0, 'transaction');
		print '</td>';
		print '<td>&nbsp;</td>';
		print '<td class="nowrap">';
		$form->select_types_paiements((GETPOST('operation') ? GETPOST('operation') : ($object->type == Account::TYPE_CASH ? 'LIQ' : '')), 'operation', '1,2', 2, 1);
		print '</td>';
		print '<td>';
		print '<input name="num_chq" class="flat" type="text" size="4" value="'.GETPOST("num_chq", "alpha").'">';
		print '</td>';
		//if (! $search_account > 0)
		//{
		print '<td class=right>';
		$form->select_comptes(GETPOSTINT('add_account') ? GETPOSTINT('add_account') : $search_account, 'add_account', 0, '', 1, ($id > 0 || !empty($ref) ? ' disabled="disabled"' : ''));
		print '</td>';
		//}
		print '<td class="right"><input name="adddebit" class="flat" type="text" size="4" value="'.GETPOST("adddebit", "alpha").'"></td>';
		print '<td class="right"><input name="addcredit" class="flat" type="text" size="4" value="'.GETPOST("addcredit", "alpha").'"></td>';
		/*if (isModEnabled('accounting'))
		{
			print '<td class="center">';
			print $formaccounting->select_account($search_accountancy_code, 'search_accountancy_code', 1, null, 1, 1, '');
			print '</td>';
		}*/
		print '<td class="center">';
		print '<input type="submit" name="save" class="button buttongen marginbottomonly button-add" value="'.$langs->trans("Add").'"><br>';
		print '<input type="submit" name="cancel" class="button buttongen marginbottomonly button-cancel" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';

		print '</table>';
		print '<br>';
	}

	// Code to adjust value date with plus and less picto using an Ajax call instead of a full reload of page
	$urlajax = DOL_URL_ROOT.'/core/ajax/bankconciliate.php?token='.currentToken();
	print '
    <script type="text/javascript">
    $(function() {
    	$("a.ajaxforbankoperationchange").each(function(){
    		var current = $(this);
    		current.click(function()
    		{
				var url = "'.$urlajax.'&"+current.attr("href").split("?")[1];
    			$.get(url, function(data)
    			{
    			    console.log(url)
					console.log(data)
					current.parent().parent().find(".spanforajaxedit").replaceWith(data);
    			});
    			return false;
    		});
    	});
    });
    </script>
    ';

	$i = 0;

	// Title
	$bankcateg = new BankCateg($db);

	$newcardbutton = '';
	if ($action != 'addline') {
		if (!getDolGlobalString('BANK_DISABLE_DIRECT_INPUT')) {
			if (!getDolGlobalString('BANK_USE_OLD_VARIOUS_PAYMENT')) {	// Default is to record miscellaneous direct entries using miscellaneous payments
				$newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create&accountid='.urlencode($search_account).'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.urlencode($search_account)), '', $user->rights->banque->modifier);
			} else { // If direct entries is not done using miscellaneous payments
				$newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=addline&token='.newToken().'&page='.$page.$param, '', $user->rights->banque->modifier);
			}
		} else {
			$newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=addline&token='.newToken().'&page='.$page.$param, '', -1);
		}
	}

	$morehtml = '';
	/*$morehtml = '<div class="inline-block '.(($buttonreconcile || $newcardbutton) ? 'marginrightonly' : '').'">';
	$morehtml .= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.($page + 1).'">';
	$morehtml .= '/'.$nbtotalofpages.' ';
	$morehtml .= '</div>';
	*/

	if ($action != 'addline' && $action != 'reconcile') {
		$morehtml .= $buttonreconcile;
	}

	$morehtmlright = '<!-- Add New button -->'.$newcardbutton;

	$picto = 'bank_account';
	if ($id > 0 || !empty($ref)) {
		$picto = '';
	}

	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("BankTransactions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton.$morehtml, $num, $nbtotalofrecords, $picto, 0, $morehtmlright, '', $limit, 0, 0, 1);

	// Form to reconcile
	if ($user->hasRight('banque', 'consolidate') && $action == 'reconcile') {
		print '<!-- form with reconciliation input -->'."\n";
		print '<div class="valignmiddle inline-block" style="padding-right: 20px;">';
		if (getDolGlobalInt('NW_RECEIPTNUMBERFORMAT')) {
			print '<strong>'.$langs->trans("InputReceiptNumber").'</strong>: ';
			print '<input class="flat width175" id="num_releve" name="num_releve" type="text" value="'.(GETPOST('num_releve') ? GETPOST('num_releve') : '').'">';
		} else {
			$texttoshow = $langs->trans("InputReceiptNumber").': ';
			$yyyy = dol_substr($langs->transnoentitiesnoconv("Year"), 0, 1).substr($langs->transnoentitiesnoconv("Year"), 0, 1).substr($langs->transnoentitiesnoconv("Year"), 0, 1).substr($langs->transnoentitiesnoconv("Year"), 0, 1);
			$mm = dol_substr($langs->transnoentitiesnoconv("Month"), 0, 1).substr($langs->transnoentitiesnoconv("Month"), 0, 1);
			$dd = dol_substr($langs->transnoentitiesnoconv("Day"), 0, 1).substr($langs->transnoentitiesnoconv("Day"), 0, 1);
			$placeholder = $yyyy.$mm;
			$placeholder .= ' '.$langs->trans("or").' ';
			$placeholder .= $yyyy.$mm.$dd;
			if (!$placeholder) {
				$texttoshow .= $langs->trans("InputReceiptNumberBis");
			}
			print $texttoshow;
			print '<input class="flat width175" pattern="[0-9]+" title="'.dol_escape_htmltag($texttoshow.($placeholder ? ': '.$placeholder : '')).'" id="num_releve" name="num_releve" placeholder="'.dol_escape_htmltag($placeholder).'" type="text" value="'.(GETPOSTINT('num_releve') ? GETPOSTINT('num_releve') : '').'">'; // The only default value is value we just entered
		}
		print '</div>';
		if (is_array($options) && count($options)) {
			print $langs->trans("EventualyAddCategory").': ';
			print Form::selectarray('cat', $options, GETPOST('cat'), 1);
		}
		print '<br><div style="margin-top: 5px;"><span class="opacitymedium">'.$langs->trans("ThenCheckLinesAndConciliate").'</span> ';
		print '<input type="submit" class="button" name="confirm_reconcile" value="'.$langs->trans("Conciliate").'">';
		print ' <span class="opacitymedium">'.$langs->trans("otherwise").'</span> ';
		print '<input type="submit" class="button small" name="confirm_savestatement" value="'.$langs->trans("SaveStatementOnly").'">';
		print ' <span class="opacitymedium">'.$langs->trans("or").'</span> ';
		print '<input type="submit" name="cancel" class="button button-cancel small" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		// Show last bank statements
		$nbmax = 12; // We show last 12 receipts (so we can have more than one year)
		$listoflastreceipts = '';
		$sql = "SELECT DISTINCT num_releve FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE fk_account = ".((int) $object->id)." AND num_releve IS NOT NULL";
		$sql .= $db->order("num_releve", "DESC");
		$sql .= $db->plimit($nbmax + 1);

		print '<br>';
		print $langs->trans("LastAccountStatements").' : ';
		$resqlr = $db->query($sql);
		if ($resqlr) {
			$numr = $db->num_rows($resqlr);
			$i = 0;
			$last_ok = 0;
			while (($i < $numr) && ($i < $nbmax)) {
				$objr = $db->fetch_object($resqlr);
				if (!$last_ok) {
					$last_releve = $objr->num_releve;
					$last_ok = 1;
				}
				$i++;
				$newentreyinlist = '<a target="_blank" href="'.DOL_URL_ROOT.'/compta/bank/releve.php?account='.((int) $id).'&num='.urlencode($objr->num_releve).'">';
				$newentreyinlist .= img_picto($objr->num_releve, 'generic', 'class="paddingright"');
				$newentreyinlist .= dol_escape_htmltag($objr->num_releve).'</a> &nbsp; ';
				$listoflastreceipts = $newentreyinlist.$listoflastreceipts;
			}
			if ($numr >= $nbmax) {
				$listoflastreceipts = "... &nbsp; ".$listoflastreceipts;
			}
			print $listoflastreceipts;
			if ($numr <= 0) {
				print '<b>'.$langs->trans("None").'</b>';
			}
		} else {
			dol_print_error($db);
		}

		// Using BANK_REPORT_LAST_NUM_RELEVE to automatically report last num (or not)
		if (getDolGlobalString('BANK_REPORT_LAST_NUM_RELEVE')) {
			print '
			    <script type="text/javascript">
			    	$("#num_releve").val("' . $last_releve.'");
			    </script>
			';
		}
		print '<br><br>';
	}

	// We can add page now to param
	if ($page != '') {
		$param .= '&page='.urlencode((string) ($page));
	}

	$moreforfilter = '';
	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateOperationShort');
	$moreforfilter .= ($conf->browser->layout == 'phone' ? '<br>' : ' ');
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $form->selectDate($search_dt_start, 'search_start_dt', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	$moreforfilter .= '</div>';
	$moreforfilter .= ($conf->browser->layout == 'phone' ? '' : ' ');
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $form->selectDate($search_dt_end, 'search_end_dt', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	$moreforfilter .= '</div>';
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateValueShort');
	$moreforfilter .= ($conf->browser->layout == 'phone' ? '<br>' : ' ');
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $form->selectDate($search_dv_start, 'search_start_dv', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	$moreforfilter .= '</div>';
	$moreforfilter .= ($conf->browser->layout == 'phone' ? '' : ' ');
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $form->selectDate($search_dv_end, 'search_end_dv', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	$moreforfilter .= '</div>';
	$moreforfilter .= '</div>';

	if (isModEnabled('category')) {
		// Categories
		if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
			$langs->load('categories');

			// Bank line
			$moreforfilter .= '<div class="divsearchfield">';
			$tmptitle = $langs->trans('RubriquesTransactions');
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_BANK_LINE, $search_bid, 'parent', null, null, 1);
			$moreforfilter .= img_picto($tmptitle, 'category', 'class="pictofixedwidth"').$form->selectarray('search_bid', $cate_arbo, $search_bid, $tmptitle, 0, 0, '', 0, 0, 0, '', '', 1);
			$moreforfilter .= '</div>';
		}
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$moreforfilter .= $hookmanager->resPrint;
	} else {
		$moreforfilter = $hookmanager->resPrint;
	}

	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>'."\n";
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
	$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
	$selectedfields .= ($action == 'reconcile' ? $form->showCheckAddButtons('checkforselect', 1) : '');

	// When action is 'reconcile', we force to have the column num_releve always enabled (otherwise we can't make reconciliation).
	if ($action == 'reconcile') {
		$arrayfields['b.num_releve']['checked'] = 1;
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	// --------------------------------------------------------------------
	print '<tr class="liste_titre_filter">';
	// Actions and select
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre valignmiddle center">';
		$searchpicto = $form->showFilterButtons('left');
		print $searchpicto;
		//$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
		//print $searchpicto;
		print '</td>';
	}
	if (!empty($arrayfields['b.rowid']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_ref" size="2" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['b.label']['checked'])) {
		print '<td class="liste_titre">';
		print '<input type="text" class="flat maxwidth100" name="search_description" value="'.dol_escape_htmltag($search_description).'">';
		print '</td>';
	}
	if (!empty($arrayfields['b.dateo']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	if (!empty($arrayfields['b.datev']['checked'])) {
		print '<td class="liste_titre">&nbsp;</td>';
	}
	if (!empty($arrayfields['type']['checked'])) {
		print '<td class="liste_titre center">';
		print $form->select_types_paiements(empty($search_type) ? '' : $search_type, 'search_type', '', 2, 1, 1, 0, 1, 'maxwidth100', 1);
		print '</td>';
	}
	// Numero
	if (!empty($arrayfields['b.num_chq']['checked'])) {
		print '<td class="liste_titre center"><input type="text" class="flat" name="req_nb" value="'.dol_escape_htmltag($search_req_nb).'" size="2"></td>';
	}
	// Checked
	if (!empty($arrayfields['bu.label']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_thirdparty" value="'.dol_escape_htmltag($search_thirdparty_user).'"></td>';
	}
	// Ref
	if (!empty($arrayfields['ba.ref']['checked'])) {
		print '<td class="liste_titre">';
		$form->select_comptes($search_account, 'search_account', 0, '', 1, ($id > 0 || !empty($ref) ? ' disabled="disabled"' : ''), 0, 'maxwidth100');
		print '</td>';
	}
	// Debit
	if (!empty($arrayfields['b.debit']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input type="text" class="flat width50" name="search_debit" value="'.dol_escape_htmltag($search_debit).'">';
		print '</td>';
	}
	// Credit
	if (!empty($arrayfields['b.credit']['checked'])) {
		print '<td class="liste_titre right">';
		print '<input type="text" class="flat width50" name="search_credit" value="'.dol_escape_htmltag($search_credit).'">';
		print '</td>';
	}
	// Balance before
	if (!empty($arrayfields['balancebefore']['checked'])) {
		print '<td class="liste_titre right">';
		$htmltext = $langs->trans("BalanceVisibilityDependsOnSortAndFilters", $langs->transnoentitiesnoconv("DateValue"));
		print $form->textwithpicto('', $htmltext, 1);
		print '</td>';
	}
	// Balance
	if (!empty($arrayfields['balance']['checked'])) {
		print '<td class="liste_titre right">';
		$htmltext = $langs->trans("BalanceVisibilityDependsOnSortAndFilters", $langs->transnoentitiesnoconv("DateValue"));
		print $form->textwithpicto('', $htmltext, 1);
		print '</td>';
	}
	// Numero statement
	if (!empty($arrayfields['b.num_releve']['checked'])) {
		print '<td class="liste_titre center"><input type="text" class="flat width50" name="search_num_releve" value="'.dol_escape_htmltag($search_num_releve).'"></td>';
	}
	// Conciliated
	if (!empty($arrayfields['b.conciliated']['checked'])) {
		print '<td class="liste_titre center parentonrightofpage">';
		print $form->selectyesno('search_conciliated', $search_conciliated, 1, false, 1, 1, 'search_status onrightofpage width75');
		print '</td>';
	}
	// Bordereau
	if (!empty($arrayfields['b.fk_bordereau']['checked'])) {
		print '<td class="liste_titre center"><input type="text" class="flat width50" name="search_fk_bordereau" value="'.dol_escape_htmltag($search_fk_bordereau).'"></td>';
	}
	// Action edit/delete and select
	print '<td class="nowraponall center"></td>';

	// Actions and select
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre valignmiddle center">';
		//$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
		//print $searchpicto;
		$searchpicto = $form->showFilterButtons();
		print $searchpicto;
		print '</td>';
	}
	print "</tr>\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	// Fields title
	print '<tr class="liste_titre">';
	// Actions and select
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.rowid']['checked'])) {
		print_liste_field_titre($arrayfields['b.rowid']['label'], $_SERVER['PHP_SELF'], 'b.rowid', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.label']['checked'])) {
		print_liste_field_titre($arrayfields['b.label']['label'], $_SERVER['PHP_SELF'], 'b.label', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.dateo']['checked'])) {
		print_liste_field_titre($arrayfields['b.dateo']['label'], $_SERVER['PHP_SELF'], 'b.dateo', '', $param, '', $sortfield, $sortorder, "center ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.datev']['checked'])) {
		print_liste_field_titre($arrayfields['b.datev']['label'], $_SERVER['PHP_SELF'], 'b.datev,b.dateo,b.rowid', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['type']['checked'])) {
		print_liste_field_titre($arrayfields['type']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.num_chq']['checked'])) {
		print_liste_field_titre($arrayfields['b.num_chq']['label'], $_SERVER['PHP_SELF'], 'b.num_chq', '', $param, '', $sortfield, $sortorder, "center ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['bu.label']['checked'])) {
		print_liste_field_titre($arrayfields['bu.label']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['ba.ref']['checked'])) {
		print_liste_field_titre($arrayfields['ba.ref']['label'], $_SERVER['PHP_SELF'], 'ba.ref', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.debit']['checked'])) {
		print_liste_field_titre($arrayfields['b.debit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, "right ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.credit']['checked'])) {
		print_liste_field_titre($arrayfields['b.credit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, "right ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['balancebefore']['checked'])) {
		print_liste_field_titre($arrayfields['balancebefore']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, "right ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['balance']['checked'])) {
		print_liste_field_titre($arrayfields['balance']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, "right ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.num_releve']['checked'])) {
		print_liste_field_titre($arrayfields['b.num_releve']['label'], $_SERVER['PHP_SELF'], 'b.num_releve', '', $param, '', $sortfield, $sortorder, "center ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.conciliated']['checked'])) {
		print_liste_field_titre($arrayfields['b.conciliated']['label'], $_SERVER['PHP_SELF'], 'b.rappro', '', $param, '', $sortfield, $sortorder, "center ");
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['b.fk_bordereau']['checked'])) {
		print_liste_field_titre($arrayfields['b.fk_bordereau']['label'], $_SERVER['PHP_SELF'], 'b.fk_bordereau', '', $param, '', $sortfield, $sortorder, "center ");
		$totalarray['nbfield']++;
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Action edit/delete and select
	print '<td class="nowraponall center"></td>';
	$totalarray['nbfield']++;
	// Actions and select
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		//print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
		$totalarray['nbfield']++;
	}
	print "</tr>\n";

	$balance = 0; // For balance
	$balancebefore = 0; // For balance
	$balancecalculated = false;
	$posconciliatecol = 0;
	$cachebankaccount = array();

	$sign = 1;

	// Loop on each record
	$i = 0;
	$savnbfield = $totalarray['nbfield'];
	$totalarray = array();
	$totalarray['nbfield'] = 0;
	$totalarray['totaldeb'] = 0;
	$totalarray['totalcred'] = 0;

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$objp = $db->fetch_object($resql);
		$links = $bankaccountstatic->get_url($objp->rowid);
		// If we are in a situation where we need/can show balance, we calculate the start of balance
		if (!$balancecalculated && (!empty($arrayfields['balancebefore']['checked']) || !empty($arrayfields['balance']['checked'])) && ($mode_balance_ok || $search_conciliated === '0')) {
			if (!$search_account) {
				dol_print_error(null, 'account is not defined but $mode_balance_ok is true');
				exit;
			}

			// Loop on each record before
			$sign = 1;
			$i = 0;
			$sqlforbalance = 'SELECT SUM(b.amount) as previoustotal';
			$sqlforbalance .= " FROM ";
			$sqlforbalance .= " ".MAIN_DB_PREFIX."bank_account as ba,";
			$sqlforbalance .= " ".MAIN_DB_PREFIX."bank as b";
			$sqlforbalance .= " WHERE b.fk_account = ba.rowid";
			$sqlforbalance .= " AND ba.entity IN (".getEntity('bank_account').")";
			$sqlforbalance .= " AND b.fk_account = ".((int) $search_account);
			// To limit record on the page
			$sqlforbalance .= " AND (b.datev < '".$db->idate($db->jdate($objp->dv))."' OR (b.datev = '".$db->idate($db->jdate($objp->dv))."' AND (b.dateo < '".$db->idate($db->jdate($objp->do))."' OR (b.dateo = '".$db->idate($db->jdate($objp->do))."' AND b.rowid < ".$objp->rowid."))))";
			$resqlforbalance = $db->query($sqlforbalance);

			//print $sqlforbalance;
			if ($resqlforbalance) {
				$objforbalance = $db->fetch_object($resqlforbalance);
				if ($objforbalance) {
					// If sort is desc,desc,desc then total of previous date + amount is the balancebefore of the previous line before the line to show
					if ($sortfield == 'b.datev,b.dateo,b.rowid' && ($sortorder == 'desc' || $sortorder == 'desc,desc' || $sortorder == 'desc,desc,desc')) {
						$balancebefore = $objforbalance->previoustotal + ($sign * $objp->amount);
					} else {
						// If sort is asc,asc,asc then total of previous date is balance of line before the next line to show
						$balance = $objforbalance->previoustotal;
					}
				}
			} else {
				dol_print_error($db);
			}

			$balancecalculated = true;

			// Output a line with start balance
			if ($user->hasRight('banque', 'consolidate') && $action == 'reconcile') {
				$tmpnbfieldbeforebalance = 0;
				$tmpnbfieldafterbalance = 0;
				$balancefieldfound = 0;
				foreach ($arrayfields as $key => $val) {
					if ($key == 'balancebefore' || $key == 'balance') {
						$balancefieldfound++;
						continue;
					}
					if (!empty($arrayfields[$key]['checked'])) {
						if (!$balancefieldfound) {
							$tmpnbfieldbeforebalance++;
						} else {
							$tmpnbfieldafterbalance++;
						}
					}
				}
				// Extra fields
				$element = 'banktransaction';
				if (!empty($extrafields->attributes[$element]['label']) && is_array($extrafields->attributes[$element]['label']) && count($extrafields->attributes[$element]['label'])) {
					foreach ($extrafields->attributes[$element]['label'] as $key => $val) {
						if (!empty($arrayfields["ef.".$key]['checked'])) {
							if (!empty($arrayfields[$key]['checked'])) {
								if (!$balancefieldfound) {
									$tmpnbfieldbeforebalance++;
								} else {
									$tmpnbfieldafterbalance++;
								}
							}
						}
					}
				}

				print '<tr class="oddeven trforbreak">';
				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}
				if ($tmpnbfieldbeforebalance) {
					print '<td colspan="'.$tmpnbfieldbeforebalance.'">';
					print '&nbsp;';
					print '</td>';
				}

				if (!empty($arrayfields['balancebefore']['checked'])) {
					print '<td class="right">';
					if ($search_conciliated !== '0') {
						if ($sortfield == 'b.datev,b.dateo,b.rowid' && ($sortorder == 'desc' || $sortorder == 'desc,desc' || $sortorder == 'desc,desc,desc')) {
							print price(price2num($balancebefore, 'MT'), 1, $langs);
						} else {
							print price(price2num($balance, 'MT'), 1, $langs);
						}
					}
					print '</td>';
				}
				if (!empty($arrayfields['balance']['checked'])) {
					print '<td class="right">';
					if ($search_conciliated !== '0') {	// If not filter of filter on "conciliated"
						if ($sortfield == 'b.datev,b.dateo,b.rowid' && ($sortorder == 'desc' || $sortorder == 'desc,desc' || $sortorder == 'desc,desc,desc')) {
							print price(price2num($balancebefore, 'MT'), 1, $langs);
						} else {
							print price(price2num($balance, 'MT'), 1, $langs);
						}
					}
					print '</td>';
				}
				if (!empty($arrayfields['b.num_releve']['checked'])) {
					print '<td></td>';
				}

				// conciliate
				print '<td colspan="'.($tmpnbfieldafterbalance).'">';
				print '&nbsp;';
				print '</td>';

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}

				print '</tr>';
			}
		}

		if ($sortfield == 'b.datev,b.dateo,b.rowid' && ($sortorder == 'desc' || $sortorder == 'desc,desc' || $sortorder == 'desc,desc,desc')) {
			$balance = price2num($balancebefore, 'MT'); // balance = balancebefore of previous line (sort is desc)
			$balancebefore = price2num($balancebefore - ($sign * $objp->amount), 'MT');
		} else {
			$balancebefore = price2num($balance, 'MT'); // balancebefore = balance of previous line (sort is asc)
			$balance = price2num($balance + ($sign * $objp->amount), 'MT');
		}

		if (empty($cachebankaccount[$objp->bankid])) {
			$bankaccounttmp = new Account($db);
			$bankaccounttmp->fetch($objp->bankid);
			$cachebankaccount[$objp->bankid] = $bankaccounttmp;
			$bankaccount = $bankaccounttmp;
		} else {
			$bankaccount = $cachebankaccount[$objp->bankid];
		}

		if (!getDolGlobalString('BANK_COLORIZE_MOVEMENT')) {
			$backgroundcolor = "class='oddeven'";
		} else {
			if ($objp->amount < 0) {
				$color = '#' . getDolGlobalString('BANK_COLORIZE_MOVEMENT_COLOR1', 'fca955');
				$backgroundcolor = 'style="background: '.$color.';"';
			} else {
				$color = '#' . getDolGlobalString('BANK_COLORIZE_MOVEMENT_COLOR2', '7fdb86');
				$backgroundcolor = 'style="background: '.$color.';"';
			}
		}

		$banklinestatic->id = $objp->rowid;
		$banklinestatic->ref = $objp->rowid;

		print '<tr class="oddeven" '.$backgroundcolor.'>';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			if (!$objp->conciliated && $action == 'reconcile') {
				print '<input class="flat checkforselect" name="rowid['.$objp->rowid.']" type="checkbox" name="toselect[]" value="'.$objp->rowid.'" size="1"'.(!empty($tmparray[$objp->rowid]) ? ' checked' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Ref
		if (!empty($arrayfields['b.rowid']['checked'])) {
			print '<td class="nowrap left">';
			print $banklinestatic->getNomUrl(1);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Description
		if (!empty($arrayfields['b.label']['checked'])) {
			$labeltoshow = '';
			$titletoshow = '';
			$reg = array();
			preg_match('/\((.+)\)/i', $objp->label, $reg); // Si texte entoure de parenthee on tente recherche de traduction
			if (!empty($reg[1]) && $langs->trans($reg[1]) != $reg[1]) {
				// Example: $reg[1] = 'CustomerInvoicePayment', 'SupplierInvoicePayment', ... (or on old version: 'WithdrawalPayment', 'BankTransferPayment')
				$labeltoshow = $langs->trans($reg[1]);
			} else {
				if ($objp->label == '(payment_salary)') {
					$labeltoshow = $langs->trans("SalaryPayment");
				} else {
					$labeltoshow = dol_escape_htmltag($objp->label);
					$titletoshow = $objp->label;
				}
			}


			print '<td class="tdoverflowmax250"'.($titletoshow ? ' title="'.dol_escape_htmltag($titletoshow).'"' : '').'>';

			// Add info about links after description
			$cachebankaccount = array();
			foreach ($links as $key => $val) {
				print '<!-- '.$links[$key]['type'].' -->';
				if ($links[$key]['type'] == 'withdraw') {
					$banktransferstatic->id = $links[$key]['url_id'];
					$banktransferstatic->ref = $links[$key]['label'];
					print $banktransferstatic->getNomUrl(0).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment') {
					$paymentstatic->id = $links[$key]['url_id'];
					$paymentstatic->ref = $links[$key]['url_id']; // FIXME This is id, not ref of payment
					$paymentstatic->date = $db->jdate($objp->do);
					print $paymentstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_supplier') {
					$paymentsupplierstatic->id = $links[$key]['url_id'];
					$paymentsupplierstatic->ref = $links[$key]['url_id']; // FIXME This is id, not ref of payment
					print $paymentsupplierstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_sc') {
					$paymentscstatic->id = $links[$key]['url_id'];
					$paymentscstatic->ref = $links[$key]['url_id'];
					$paymentscstatic->label = $links[$key]['label'];
					print $paymentscstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_vat') {
					$paymentvatstatic->id = $links[$key]['url_id'];
					$paymentvatstatic->ref = $links[$key]['url_id'];
					print $paymentvatstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_salary') {
					$paymentsalstatic->id = $links[$key]['url_id'];
					$paymentsalstatic->ref = $links[$key]['url_id'];
					$paymentsalstatic->label = $links[$key]['label'];
					print $paymentsalstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_loan') {
					print '<a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$links[$key]['url_id'].'">';
					print ' '.img_object($langs->trans('ShowPayment'), 'payment').' ';
					print '</a>'.($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_donation') {
					$paymentdonationstatic->id = $links[$key]['url_id'];
					$paymentdonationstatic->ref = $links[$key]['url_id'];
					print $paymentdonationstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_expensereport') {
					$paymentexpensereportstatic->id = $links[$key]['url_id'];
					$paymentexpensereportstatic->ref = $links[$key]['url_id'];
					print $paymentexpensereportstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'payment_various') {
					$paymentvariousstatic->id = $links[$key]['url_id'];
					$paymentvariousstatic->ref = $links[$key]['url_id'];
					print $paymentvariousstatic->getNomUrl(2).($labeltoshow ? ' ' : '');
				} elseif ($links[$key]['type'] == 'banktransfert') {
					// Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
					if ($objp->amount > 0) {
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id = $banklinestatic->fk_account;
						$bankstatic->label = $banklinestatic->bank_account_ref;
						print $langs->trans("TransferFrom").' ';
						print $bankstatic->getNomUrl(1, 'transactions');
						print ' '.$langs->trans("toward").' ';
						$bankstatic->id = $objp->bankid;
						$bankstatic->label = $objp->bankref;
						print $bankstatic->getNomUrl(1, '');
						print($labeltoshow ? ' - ' : '');
					} else {
						$bankstatic->id = $objp->bankid;
						$bankstatic->label = $objp->bankref;
						print $langs->trans("TransferFrom").' ';
						print $bankstatic->getNomUrl(1, '');
						print ' '.$langs->trans("toward").' ';
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id = $banklinestatic->fk_account;
						$bankstatic->label = $banklinestatic->bank_account_ref;
						print $bankstatic->getNomUrl(1, 'transactions');
						print($labeltoshow ? ' - ' : '');
					}
					//var_dump($links);
				} elseif ($links[$key]['type'] == 'company') {
				} elseif ($links[$key]['type'] == 'user') {
				} elseif ($links[$key]['type'] == 'member') {
				} elseif ($links[$key]['type'] == 'sc') {
				} elseif ($links[$key]['type'] == 'vat') {
				} elseif ($links[$key]['type'] == 'salary') {
					// Information is already shown using the payment_salary link. No need of this link.
				} else {
					// Show link with label $links[$key]['label']
					print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
					if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
						// Label generique car entre parentheses. On l'affiche en le traduisant
						if ($reg[1] == 'paiement') {
							$reg[1] = 'Payment';
						}
						print $langs->trans($reg[1]);
					} else {
						print $links[$key]['label'];
					}
					print '</a>'.($labeltoshow ? ' - ' : '');
				}
			}

			print $labeltoshow;	// Already escaped

			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date ope
		if (!empty($arrayfields['b.dateo']['checked'])) {
			print '<td class="nowrap center">';
			print '<span class="spanforajaxedit" id="dateoperation_'.$objp->rowid.'" title="'.dol_print_date($db->jdate($objp->do), "day").'">'.dol_print_date($db->jdate($objp->do), "dayreduceformat")."</span>";
			print '&nbsp;';
			print '<span class="inline-block">';
			print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=doprev&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_remove()."</a> ";
			print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=donext&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_add()."</a>";
			print '</span>';
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Date value
		if (!empty($arrayfields['b.datev']['checked'])) {
			print '<td class="nowrap center">';
			print '<span class="spanforajaxedit" id="datevalue_'.$objp->rowid.'" title="'.dol_print_date($db->jdate($objp->dv), "day").'">'.dol_print_date($db->jdate($objp->dv), "dayreduceformat")."</span>";
			print '&nbsp;';
			print '<span class="inline-block">';
			print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=dvprev&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_remove()."</a> ";
			print '<a class="ajaxforbankoperationchange" href="'.$_SERVER['PHP_SELF'].'?action=dvnext&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_add()."</a>";
			print '</span>';
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Payment type
		if (!empty($arrayfields['type']['checked'])) {
			print '<td class="tdoverflowmax100">';
			$labeltype = ($langs->trans("PaymentTypeShort".$objp->fk_type) != "PaymentTypeShort".$objp->fk_type) ? $langs->trans("PaymentTypeShort".$objp->fk_type) : $langs->getLabelFromKey($db, $objp->fk_type, 'c_paiement', 'code', 'libelle', '', 1);
			if ($labeltype == 'SOLD') {
				print '&nbsp;'; //$langs->trans("InitialBankBalance");
			} else {
				print $labeltype;
			}
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Num cheque
		if (!empty($arrayfields['b.num_chq']['checked'])) {
			print '<td class="nowrap center">'.($objp->num_chq ? dol_escape_htmltag($objp->num_chq) : "")."</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Third party
		if (!empty($arrayfields['bu.label']['checked'])) {
			print '<td class="tdoverflowmax125">';

			$companylinked_id = 0;
			$userlinked_id = 0;
			$type_link = "";
			$thirdstr = "";

			//payment line type to define user display and user or company linked
			foreach ($links as $key => $value) {
				if ($links[$key]['type'] == 'payment_sc') {
					$type_link = 'payment_sc';
				}
				if ($links[$key]['type'] == 'payment_salary') {
					$type_link = 'payment_salary';
				}
				if ($links[$key]['type'] == 'payment_donation') {
					$paymentdonationstatic->fetch($links[$key]['url_id']);
					$donstatic->fetch($paymentdonationstatic->fk_donation);
					$companylinked_id = $donstatic->socid;
					if (!$companylinked_id) {
						$thirdstr = ($donstatic->societe !== "" ?
									$donstatic->societe :
									$donstatic->firstname." ".$donstatic->lastname);
					}
				}
				if ($links[$key]['type'] == 'payment_expensereport') {
					$type_link = 'payment_expensereport';
				}

				if ($links[$key]['type'] == 'company') {
					$companylinked_id = $links[$key]['url_id'];
				}
				if ($links[$key]['type'] == 'user') {
					$userlinked_id = $links[$key]['url_id'];
				}
			}

			// Show more information in the column thirdparty.
			if ($companylinked_id) {
				// TODO Add a cache of loaded companies here ?
				$companystatic->fetch($companylinked_id);
				print $companystatic->getNomUrl(1);
			} elseif ($userlinked_id &&
					(($type_link == 'payment_salary' && $user->hasRight('salaries', 'read'))
						|| ($type_link == 'payment_sc' && $user->hasRight('tax', 'charges', 'lire'))
						|| ($type_link == 'payment_expensereport' && $user->hasRight('expensereport', 'lire')))) {
				// Get object user from cache or load it
				if (!empty($conf->cache['user'][$userlinked_id])) {
					$tmpuser = $conf->cache['user'][$userlinked_id];
				} else {
					$tmpuser = new User($db);
					$tmpuser->fetch($userlinked_id);
					$conf->cache['user'][$userlinked_id] = $tmpuser;
				}
				print $tmpuser->getNomUrl(-1);
			} elseif ($thirdstr) {
				print $thirdstr;
			} else {
				print '&nbsp;';
			}

			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Bank account
		if (!empty($arrayfields['ba.ref']['checked'])) {
			print '<td class="nowrap">';
			print $bankaccount->getNomUrl(1);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Debit
		if (!empty($arrayfields['b.debit']['checked'])) {
			print '<td class="nowrap right"><span class="amount">';
			if ($objp->amount < 0) {
				print price($objp->amount * -1);
				$totalarray['totaldeb'] += $objp->amount;
			}
			print "</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['totaldebfield'] = $totalarray['nbfield'];
			}
		}

		// Credit
		if (!empty($arrayfields['b.credit']['checked'])) {
			print '<td class="nowrap right"><span class="amount">';
			if ($objp->amount > 0) {
				print price($objp->amount);
				$totalarray['totalcred'] += $objp->amount;
			}
			print "</span></td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
			if (!$i) {
				$totalarray['totalcredfield'] = $totalarray['nbfield'];
			}
		}

		// Balance before
		if (!empty($arrayfields['balancebefore']['checked'])) {
			if ($mode_balance_ok) {
				if ($balancebefore >= 0) {
					print '<td class="nowrap right">&nbsp;'.price($balancebefore).'</td>';
				} else {
					print '<td class="error nowrap right">&nbsp;'.price($balancebefore).'</td>';
				}
			} else {
				print '<td class="right">-</td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Balance after
		if (!empty($arrayfields['balance']['checked'])) {
			if ($mode_balance_ok) {
				if ($balance >= 0) {
					print '<td class="nowrap right">&nbsp;'.price($balance).'</td>';
				} else {
					print '<td class="error nowrap right">&nbsp;'.price($balance).'</td>';
				}
			} else {
				print '<td class="right">-</td>';
			}
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['b.num_releve']['checked'])) {
			print '<td class="nowraponall center">';
			// Transaction reconciliated or edit link
			if ($bankaccount->canBeConciliated() > 0) {
				if ($objp->num_releve) {
					print '<a href="releve.php?num='.urlencode($objp->num_releve).'&account='.urlencode($objp->bankid).'&save_lastsearch_values=1">'.dol_escape_htmltag($objp->num_releve).'</a>';
				}
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
				$posconciliatecol = $totalarray['nbfield'];
			}
		}

		if (!empty($arrayfields['b.conciliated']['checked'])) {
			print '<td class="nowraponall center">';
			print yn($objp->conciliated);
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if (!empty($arrayfields['b.fk_bordereau']['checked'])) {
			$bordereaustatic->fetch($objp->fk_bordereau);
			print '<td class="nowraponall center">';
			print $bordereaustatic->getNomUrl();
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Fields from hook
		$parameters = array('arrayfields' => $arrayfields, 'obj' => $objp, 'i' => $i, 'totalarray' => &$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objecttmp);    // Note that $action and $objecttmpect may have been modified by hook
		print $hookmanager->resPrint;

		// Action edit/delete and select
		print '<td class="nowraponall center">';
		// Transaction reconciliated or edit link
		if ($objp->conciliated && $bankaccount->canBeConciliated() > 0) {  // If line not conciliated and account can be conciliated
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/compta/bank/line.php?save_lastsearch_values=1&rowid='.$objp->rowid.($object->id > 0 ? '&account='.$object->id : '').'&page='.$page.'">';
			print img_edit();
			print '</a>';
		} else {
			if ($user->hasRight('banque', 'modifier') || $user->hasRight('banque', 'consolidate')) {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/compta/bank/line.php?save_lastsearch_values=1&rowid='.$objp->rowid.($object->id > 0 ? '&account='.$object->id : '').'&page='.$page.'">';
				print img_edit();
				print '</a>';
			} else {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/compta/bank/line.php?save_lastsearch_values=1&rowid='.$objp->rowid.($object->id > 0 ? '&account='.$object->id : '').'&page='.$page.'">';
				print img_view();
				print '</a>';
			}
			if ($bankaccount->canBeConciliated() > 0 && empty($objp->conciliated)) {
				if ($db->jdate($objp->dv) < ($now - $conf->bank->rappro->warning_delay)) {
					print ' '.img_warning($langs->trans("ReconciliationLate"));
				}
			}
			if ($user->hasRight('banque', 'modifier')) {
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&rowid='.$objp->rowid.'&page='.$page.$param.($sortfield ? '&sortfield='.$sortfield : '').($sortorder ? '&sortorder='.$sortorder : '').'">';
				print img_delete('', 'class="marginleftonly"');
				print '</a>';
			}
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			if (!$objp->conciliated && $action == 'reconcile') {
				print '<input class="flat checkforselect" name="rowid['.$objp->rowid.']" type="checkbox" value="'.$objp->rowid.'" size="1"'.(!empty($tmparray[$objp->rowid]) ? ' checked' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print "</tr>\n";

		$i++;
	}

	// Show total line
	if (isset($totalarray['totaldebfield']) || isset($totalarray['totalcredfield'])) {
		print '<tr class="liste_total">';
		$i = 0;
		while ($i < $totalarray['nbfield']) {
			$i++;
			if ($i == 1) {
				if ($num < $limit && empty($offset)) {
					print '<td class="left">'.$langs->trans("Total").'</td>';
				} else {
					print '<td class="left tdoverflowmax50" title="'.$langs->trans("Totalforthispage").'">'.$langs->trans("Totalforthispage").'</td>';
				}
			} elseif ($totalarray['totaldebfield'] == $i) {
				print '<td class="right"><span class="amount">'.price(-1 * $totalarray['totaldeb']).'</span></td>';
			} elseif ($totalarray['totalcredfield'] == $i) {
				print '<td class="right"><span class="amount">'.price($totalarray['totalcred']).'</span></td>';
			} elseif ($i == $posconciliatecol) {
				print '<td class="center">';
				if ($user->hasRight('banque', 'consolidate') && $action == 'reconcile') {
					print '<input class="button smallpaddingimp" name="confirm_reconcile" type="submit" value="'.$langs->trans("Conciliate").'">';
				}
				print '</td>';
			} else {
				print '<td></td>';
			}
		}
		print '</tr>';
	}

	// If no record found
	if ($num == 0) {
		$colspan = 1;
		foreach ($arrayfields as $key => $val) {
			if (!empty($val['checked'])) {
				$colspan++;
			}
		}
		print '<tr><td colspan="'.($colspan + 1).'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print "</table>";
	print "</div>";

	print '</form>';
	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
