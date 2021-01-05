<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
 * Copyright (C) 2014       Florian Henry        <florian.henry@open-cooncept.pro>
 * Copyright (C) 2015       Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017-2019  Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2018       Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
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

// Load translation files required by the page
$langs->loadLangs(array("banks", "bills", "categories", "companies", "margins", "salaries", "loan", "donations", "trips", "members", "compta", "accountancy"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = 'banktransactionlist'.(empty($object->ref) ? '' : '-'.$object->id);

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($fielvalue)
{
	if ($user->socid) $socid = $user->socid;
	$result = restrictedArea($user, 'banque', $fieldvalue, 'bank_account&bank_account', '', '', $fieldtype);
} else {
	if ($user->socid) $socid = $user->socid;
	$result = restrictedArea($user, 'banque');
}

$dateop = dol_mktime(12, 0, 0, GETPOST("opmonth", 'int'), GETPOST("opday", 'int'), GETPOST("opyear", 'int'));
$search_debit = GETPOST("search_debit", 'alpha');
$search_credit = GETPOST("search_credit", 'alpha');
$search_type = GETPOST("search_type", 'alpha');
$search_account = GETPOST("search_account", 'int') ?GETPOST("search_account", 'int') : GETPOST("account", 'int');
$search_accountancy_code = GETPOST('search_accountancy_code', 'alpha') ?GETPOST('search_accountancy_code', 'alpha') : GETPOST('accountancy_code', 'alpha');
$search_bid = GETPOST("search_bid", "int") ?GETPOST("search_bid", "int") : GETPOST("bid", "int");
$search_ref = GETPOST('search_ref', 'alpha');
$search_description = GETPOST("search_description", 'alpha');
$search_dt_start = dol_mktime(0, 0, 0, GETPOST('search_start_dtmonth', 'int'), GETPOST('search_start_dtday', 'int'), GETPOST('search_start_dtyear', 'int'));
$search_dt_end = dol_mktime(0, 0, 0, GETPOST('search_end_dtmonth', 'int'), GETPOST('search_end_dtday', 'int'), GETPOST('search_end_dtyear', 'int'));
$search_dv_start = dol_mktime(0, 0, 0, GETPOST('search_start_dvmonth', 'int'), GETPOST('search_start_dvday', 'int'), GETPOST('search_start_dvyear', 'int'));
$search_dv_end = dol_mktime(0, 0, 0, GETPOST('search_end_dvmonth', 'int'), GETPOST('search_end_dvday', 'int'), GETPOST('search_end_dvyear', 'int'));
$search_thirdparty = GETPOST("search_thirdparty", 'alpha') ?GETPOST("search_thirdparty", 'alpha') : GETPOST("thirdparty", 'alpha');
$search_req_nb = GETPOST("req_nb", 'alpha');
$search_num_releve = GETPOST("search_num_releve", 'alpha');
$search_conciliated = GETPOST("search_conciliated", 'int');
$num_releve = GETPOST("num_releve", "alpha");
if (empty($dateop)) $dateop = -1;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
$pageplusone = GETPOST("pageplusone", 'int');
if ($pageplusone) $page = $pageplusone - 1;
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) $sortorder = 'desc,desc,desc';
if (!$sortfield) $sortfield = 'b.datev,b.dateo,b.rowid';

$object = new Account($db);
if ($id > 0 || !empty($ref))
{
	$result = $object->fetch($id, $ref);
	$search_account = $object->id; // Force the search field on id of account

	if (!($object->id > 0))
	{
		$langs->load("errors");
		print($langs->trans('ErrorRecordNotFound'));
		exit;
	}
}

$mode_balance_ok = false;
//if (($sortfield == 'b.datev' || $sortfield == 'b.datev,b.dateo,b.rowid'))    // TODO Manage balance when account not selected
if (($sortfield == 'b.datev' || $sortfield == 'b.datev,b.dateo,b.rowid'))
{
	$sortfield = 'b.datev,b.dateo,b.rowid';
	if ($id > 0 || !empty($ref) || $search_account > 0) $mode_balance_ok = true;
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('banktransactionlist', $contextpage));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label('banktransaction');
$search_array_options = $extrafields->getOptionalsFromPost('banktransaction', '', 'search_');

$arrayfields = array(
	'b.rowid'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'b.label'=>array('label'=>$langs->trans("Description"), 'checked'=>1),
	'b.dateo'=>array('label'=>$langs->trans("DateOperationShort"), 'checked'=>1),
	'b.datev'=>array('label'=>$langs->trans("DateValueShort"), 'checked'=>1),
	'type'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
	'b.num_chq'=>array('label'=>$langs->trans("Numero"), 'checked'=>1),
	'bu.label'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1, 'position'=>500),
	'ba.ref'=>array('label'=>$langs->trans("BankAccount"), 'checked'=>(($id > 0 || !empty($ref)) ? 0 : 1), 'position'=>1000),
	'b.debit'=>array('label'=>$langs->trans("Debit"), 'checked'=>1, 'position'=>600),
	'b.credit'=>array('label'=>$langs->trans("Credit"), 'checked'=>1, 'position'=>605),
	'balancebefore'=>array('label'=>$langs->trans("BalanceBefore"), 'checked'=>0, 'position'=>1000),
	'balance'=>array('label'=>$langs->trans("Balance"), 'checked'=>1, 'position'=>1001),
	'b.num_releve'=>array('label'=>$langs->trans("AccountStatement"), 'checked'=>1, 'position'=>1010),
	'b.conciliated'=>array('label'=>$langs->trans("Conciliated"), 'enabled'=> $object->rappro, 'checked'=>($action == 'reconcile' ? 1 : 0), 'position'=>1020),
);
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
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
	$search_thirdparty = '';
	$search_num_releve = '';
	$search_conciliated = '';
	$thirdparty = '';

	$search_account = "";
	if ($id > 0 || !empty($ref)) $search_account = $object->id;
}

if (empty($reshook))
{
	$objectclass = 'Account';
	$objectlabel = 'BankTransaction';
	$permissiontoread = $user->rights->banque->lire;
	$permissiontodelete = $user->rights->banque->supprimer;
	$uploaddir = $conf->bank->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}

// Conciliation
if ((GETPOST('confirm_savestatement', 'alpha') || GETPOST('confirm_reconcile', 'alpha')) && $user->rights->banque->consolidate
	&& (!GETPOSTISSET('pageplusone') || (GETPOST('pageplusone') == GETPOST('pageplusoneold'))))
{
	$error = 0;

	// Definition, nettoyage parametres
	$num_releve = GETPOST("num_releve", "alpha");

	if ($num_releve)
	{
		$bankline = new AccountLine($db);

		$rowids = GETPOST('rowid', 'array');

		if (!empty($rowids) && is_array($rowids)) {
			foreach ($rowids as $row) {
				if ($row > 0) {
					$result = $bankline->fetch($row);
					$bankline->num_releve = $num_releve; //$_POST["num_releve"];
					$result = $bankline->update_conciliation($user, GETPOST("cat"), GETPOST('confirm_reconcile', 'alpha') ? 1 : 0); // If we confirm_reconcile, we set flag 'rappro' to 1.
					if ($result < 0) {
						setEventMessages($bankline->error, $bankline->errors, 'errors');
						$error++;
						break;
					}
				}
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

	if (!$error)
	{
		$param = 'action=reconcile&contextpage=banktransactionlist&id='.$id.'&search_account='.$id;
		$param .= '&search_conciliated='.urlencode($search_conciliated);
		if ($page) $param .= '&page='.urlencode($page);
		if ($offset) $param .= '&offset='.urlencode($offset);
		if ($search_thirdparty) $param .= '&search_thirdparty='.urlencode($search_thirdparty);
		if ($search_num_releve) $param .= '&search_num_releve='.urlencode($search_num_releve);
		if ($search_description) $param .= '&search_description='.urlencode($search_description);
		if ($search_start_dt) $param .= '&search_start_dt='.urlencode($search_start_dt);
		if ($search_end_dt) $param .= '&search_end_dt='.urlencode($search_end_dt);
		if ($search_start_dv) $param .= '&search_start_dv='.urlencode($search_start_dv);
		if ($search_end_dv) $param .= '&search_end_dv='.urlencode($search_end_dv);
		if ($search_type) $param .= '&search_type='.urlencode($search_type);
		if ($search_debit) $param .= '&search_debit='.urlencode($search_debit);
		if ($search_credit) $param .= '&search_credit='.urlencode($search_credit);
		$param .= '&sortfield='.urlencode($sortfield).'&sortorder='.urlencode($sortorder);
		header('Location: '.$_SERVER["PHP_SELF"].'?'.$param); // To avoid to submit twice and allow the back button
		exit;
	}
}


if (GETPOST('save') && !$cancel && $user->rights->banque->modifier)
{
	$error = 0;

	if (price2num(GETPOST("addcredit")) > 0)
	{
		$amount = price2num(GETPOST("addcredit"));
	} else {
		$amount = - price2num(GETPOST("adddebit"));
	}

	$operation = GETPOST("operation", 'alpha');
	$num_chq   = GETPOST("num_chq", 'alpha');
	$label     = GETPOST("label", 'alpha');
	$cat1      = GETPOST("cat1", 'alpha');

	$bankaccountid = $id;
	if (GETPOST('add_account', 'int') > 0) $bankaccountid = GETPOST('add_account', 'int');

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
	if (!$bankaccountid > 0)
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BankAccount")), null, 'errors');
	}
	/*if (! empty($conf->accounting->enabled) && (empty($search_accountancy_code) || $search_accountancy_code == '-1'))
    {
    	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("AccountAccounting")), null, 'errors');
    	$error++;
    }*/

	if (!$error && !empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT))
	{
		$objecttmp = new Account($db);
		$objecttmp->fetch($bankaccountid);
		$insertid = $objecttmp->addline($dateop, $operation, $label, $amount, $num_chq, ($cat1 > 0 ? $cat1 : 0), $user, '', '', $search_accountancy_code);
		if ($insertid > 0)
		{
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

if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->banque->modifier)
{
	$accline = new AccountLine($db);
	$result = $accline->fetch(GETPOST("rowid", "int"));
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
$paymentvatstatic = new TVA($db);
$paymentsalstatic = new PaymentSalary($db);
$paymentdonationstatic = new PaymentDonation($db);
$paymentvariousstatic = new PaymentVarious($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);
$bankstatic = new Account($db);
$banklinestatic = new AccountLine($db);

$now = dol_now();


// Must be before button action
$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
if ($id > 0) $param .= '&id='.urlencode($id);
if (!empty($ref)) $param .= '&ref='.urlencode($ref);
if (!empty($search_ref)) $param .= '&search_ref='.urlencode($search_ref);
if (!empty($search_description)) $param .= '&search_description='.urlencode($search_description);
if (!empty($search_type)) $param .= '&type='.urlencode($search_type);
if (!empty($search_thirdparty)) $param .= '&search_thirdparty='.urlencode($search_thirdparty);
if (!empty($search_debit)) $param .= '&search_debit='.urlencode($search_debit);
if (!empty($search_credit)) $param .= '&search_credit='.urlencode($search_credit);
if (!empty($search_account)) $param .= '&search_account='.urlencode($search_account);
if (!empty($search_num_releve)) $param .= '&search_num_releve='.urlencode($search_num_releve);
if ($search_conciliated != '' && $search_conciliated != '-1')  $param .= '&search_conciliated='.urlencode($search_conciliated);
if ($search_bid > 0)  $param .= '&search_bid='.urlencode($search_bid);
if (dol_strlen($search_dt_start) > 0) $param .= '&search_start_dtmonth='.GETPOST('search_start_dtmonth', 'int').'&search_start_dtday='.GETPOST('search_start_dtday', 'int').'&search_start_dtyear='.GETPOST('search_start_dtyear', 'int');
if (dol_strlen($search_dt_end) > 0)   $param .= '&search_end_dtmonth='.GETPOST('search_end_dtmonth', 'int').'&search_end_dtday='.GETPOST('search_end_dtday', 'int').'&search_end_dtyear='.GETPOST('search_end_dtyear', 'int');
if (dol_strlen($search_dv_start) > 0) $param .= '&search_start_dvmonth='.GETPOST('search_start_dvmonth', 'int').'&search_start_dvday='.GETPOST('search_start_dvday', 'int').'&search_start_dvyear='.GETPOST('search_start_dvyear', 'int');
if (dol_strlen($search_dv_end) > 0)   $param .= '&search_end_dvmonth='.GETPOST('search_end_dvmonth', 'int').'&search_end_dvday='.GETPOST('search_end_dvday', 'int').'&search_end_dvyear='.GETPOST('search_end_dvyear', 'int');
if ($search_req_nb) $param .= '&req_nb='.urlencode($search_req_nb);
if (GETPOST("search_thirdparty", 'int')) $param .= '&thirdparty='.urlencode(GETPOST("search_thirdparty", 'int'));
if ($optioncss != '')       $param .= '&optioncss='.urlencode($optioncss);
if ($action == 'reconcile') $param .= '&action=reconcile';
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

$options = array();

$buttonreconcile = '';
$morehtmlref = '';

if ($id > 0 || !empty($ref))
{
	$title = $langs->trans("FinancialAccount").' - '.$langs->trans("Transactions");
	$helpurl = "";
	llxHeader('', $title, $helpurl);

	// Load bank groups
	require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';
	$bankcateg = new BankCateg($db);

	foreach ($bankcateg->fetchAll() as $bankcategory) {
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

	if ($action != 'reconcile')
	{
		if ($object->canBeConciliated() > 0)
		{
			// If not cash account and can be reconciliate
			if ($user->rights->banque->consolidate) {
				$newparam = $param;
				$newparam = preg_replace('/search_conciliated=\d+/i', '', $newparam);
				$buttonreconcile = '<a class="butAction" style="margin-bottom: 5px !important; margin-top: 5px !important" href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?action=reconcile&sortfield=b.datev,b.dateo,b.rowid&amp;sortorder=asc,asc,asc&search_conciliated=0'.$newparam.'">'.$langs->trans("Conciliate").'</a>';
			} else {
				$buttonreconcile = '<a class="butActionRefused" style="margin-bottom: 5px !important; margin-top: 5px !important" title="'.$langs->trans("NotEnoughPermissions").'" href="#">'.$langs->trans("Conciliate").'</a>';
			}
		}
	}
} else {
	llxHeader('', $langs->trans("BankTransactions"), '', '', 0, 0, array(), array(), $param);
}

$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro as conciliated, b.num_releve, b.num_chq,";
$sql .= " b.fk_account, b.fk_type,";
$sql .= " ba.rowid as bankid, ba.ref as bankref,";
$sql .= " bu.url_id,";
$sql .= " s.nom, s.name_alias, s.client, s.fournisseur, s.email, s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ";
if ($search_bid > 0) $sql .= MAIN_DB_PREFIX."bank_class as l,";
$sql .= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql .= " ".MAIN_DB_PREFIX."bank as b";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (b.rowid = ef.fk_object)";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND type = 'company'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
$sql .= " WHERE b.fk_account = ba.rowid";
$sql .= " AND ba.entity IN (".getEntity('bank_account').")";
if ($search_account > 0) $sql .= " AND b.fk_account = ".$search_account;
// Search period criteria
if (dol_strlen($search_dt_start) > 0) $sql .= " AND b.dateo >= '".$db->idate($search_dt_start)."'";
if (dol_strlen($search_dt_end) > 0) $sql .= " AND b.dateo <= '".$db->idate($search_dt_end)."'";
// Search period criteria
if (dol_strlen($search_dv_start) > 0) $sql .= " AND b.datev >= '".$db->idate($search_dv_start)."'";
if (dol_strlen($search_dv_end) > 0) $sql .= " AND b.datev <= '".$db->idate($search_dv_end)."'";
if ($search_ref) $sql .= natural_search("b.rowid", $search_ref, 1);
if ($search_req_nb) $sql .= natural_search("b.num_chq", $search_req_nb);
if ($search_num_releve) $sql .= natural_search("b.num_releve", $search_num_releve);
if ($search_conciliated != '' && $search_conciliated != '-1') $sql .= " AND b.rappro = ".urlencode($search_conciliated);
if ($search_thirdparty) $sql .= natural_search("s.nom", $search_thirdparty);
if ($search_description)
{
	$search_description_to_use = $search_description;
	$arrayoffixedlabels = array(
		'payment_salary',
		'CustomerInvoicePayment', 'CustomerInvoicePaymentBack',
		'SupplierInvoicePayment', 'SupplierInvoicePaymentBack',
		'DonationPayment',
		'ExpenseReportPayment',
		'SocialContributionPayment',
		'SubscriptionPayment',
		'WithdrawalPayment'
	);
	foreach ($arrayoffixedlabels as $keyforlabel)
	{
		$translatedlabel = $langs->transnoentitiesnoconv($keyforlabel);
		if (preg_match('/'.$search_description.'/i', $translatedlabel))
		{
			$search_description_to_use .= "|".$keyforlabel;
		}
	}
	$sql .= natural_search("b.label", $search_description_to_use); // Warning some text are just translation keys, not translated strings
}
if ($search_bid > 0) $sql .= " AND b.rowid=l.lineid AND l.fk_categ=".$search_bid;
if (!empty($search_type)) $sql .= " AND b.fk_type = '".$db->escape($search_type)."' ";
// Search criteria amount
$search_debit = price2num(str_replace('-', '', $search_debit));
$search_credit = price2num(str_replace('-', '', $search_credit));
if ($search_debit) $sql .= natural_search('- b.amount', $search_debit, 1);
if ($search_credit) $sql .= natural_search('b.amount', $search_credit, 1);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
$nbtotalofpages = 0;
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	$nbtotalofpages = ceil($nbtotalofrecords / $limit);
}

if (($id > 0 || !empty($ref)) && ((string) $page == ''))
{
	// We open a list of transaction of a dedicated account and no page was set by defaut
	// We force on last page.
	$page = ($nbtotalofpages - 1);
	$offset = $limit * $page;
	if ($page < 0) $page = 0;
}
if ($page >= $nbtotalofpages)
{
	// If we made a search and result has low page than the page number we were on
	$page = ($nbtotalofpages - 1);
	$offset = $limit * $page;
	if ($page < 0) $page = 0;
}


// If not account defined $mode_balance_ok=false
if (empty($search_account)) $mode_balance_ok = false;
// If a search is done $mode_balance_ok=false
if (!empty($search_ref)) $mode_balance_ok = false;
if (!empty($search_description)) $mode_balance_ok = false;
if (!empty($search_type)) $mode_balance_ok = false;
if (!empty($search_debit)) $mode_balance_ok = false;
if (!empty($search_credit)) $mode_balance_ok = false;
if (!empty($search_thirdparty)) $mode_balance_ok = false;
if ($search_conciliated != '' && $search_conciliated != '-1') $mode_balance_ok = false;
if (!empty($search_num_releve)) $mode_balance_ok = false;

$sql .= $db->plimit($limit + 1, $offset);
//print $sql;
dol_syslog('compta/bank/bankentries_list.php', LOG_DEBUG);
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	// List of mass actions available
	$arrayofmassactions = array(
		//'presend'=>$langs->trans("SendByMail"),
		//'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if ($user->rights->bank->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	// Confirmation delete
	if ($action == 'delete')
	{
		$text = $langs->trans('ConfirmDeleteTransaction');
		print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id.'&rowid='.GETPOST("rowid"), $langs->trans('DeleteTransaction'), $text, 'confirm_delete', null, '', 1);
	}

	// Lines of title fields
	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'" name="search_form">'."\n";
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="'.($action ? $action : 'search').'">';
	print '<input type="hidden" name="view" value="'.dol_escape_htmltag($view).'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="ref" value="'.$ref.'">';
	if (GETPOST('bid')) print '<input type="hidden" name="bid" value="'.GETPOST("bid").'">';

	// Form to reconcile
	if ($user->rights->banque->consolidate && $action == 'reconcile')
	{
		print '<div class="valignmiddle inline-block" style="padding-right: 20px;">';
		print '<strong>'.$langs->trans("InputReceiptNumber").'</strong>: ';
		print '<input class="flat" id="num_releve" name="num_releve" type="text" value="'.(GETPOST('num_releve') ?GETPOST('num_releve') : '').'" size="10">'; // The only default value is value we just entered
		print '</div>';
		if (is_array($options) && count($options))
		{
			print $langs->trans("EventualyAddCategory").': ';
			print Form::selectarray('cat', $options, GETPOST('cat'), 1);
		}
		print '<br><div style="margin-top: 5px;"><span class="opacitymedium">'.$langs->trans("ThenCheckLinesAndConciliate").'</span> ';
		print '<input class="button" name="confirm_savestatement" type="submit" value="'.$langs->trans("SaveStatementOnly").'">';
		print ' '.$langs->trans("or").' ';
		print '<input class="button" name="confirm_reconcile" type="submit" value="'.$langs->trans("Conciliate").'">';
		print ' '.$langs->trans("or").' ';
		print '<input type="submit" name="cancel" class="button button-cancel" value="'.$langs->trans("Cancel").'">';
		print '</div>';

		// Show last bank statements
		$nbmax = 12; // We show last 12 receipts (so we can have more than one year)
		$liste = "";
		$sql = "SELECT DISTINCT num_releve FROM ".MAIN_DB_PREFIX."bank";
		$sql .= " WHERE fk_account=".$object->id." AND num_releve IS NOT NULL";
		$sql .= $db->order("num_releve", "DESC");
		$sql .= $db->plimit($nbmax + 1);
		print '<br>';
		print $langs->trans("LastAccountStatements").' : ';
		$resqlr = $db->query($sql);
		if ($resqlr)
		{
			$numr = $db->num_rows($resqlr);
			$i = 0;
			$last_ok = 0;
			while (($i < $numr) && ($i < $nbmax))
			{
				$objr = $db->fetch_object($resqlr);
				if (!$last_ok) {
					$last_releve = $objr->num_releve;
					$last_ok = 1;
				}
				$i++;
				$liste = '<a href="'.DOL_URL_ROOT.'/compta/bank/releve.php?account='.$id.'&amp;num='.$objr->num_releve.'">'.$objr->num_releve.'</a> &nbsp; '.$liste;
			}
			if ($numr >= $nbmax) $liste = "... &nbsp; ".$liste;
			print $liste;
			if ($numr <= 0) print '<b>'.$langs->trans("None").'</b>';
		} else {
			dol_print_error($db);
		}

		// Using BANK_REPORT_LAST_NUM_RELEVE to automatically report last num (or not)
		if (!empty($conf->global->BANK_REPORT_LAST_NUM_RELEVE))
		{
			print '
			    <script type="text/javascript">
			    	$("#num_releve").val("' . $last_releve.'");
			    </script>
			';
		}
		print '<br><br>';
	}

	// Form to add a transaction with no invoice
	if ($user->rights->banque->modifier && $action == 'addline' && !empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT))
	{
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
		/*if (! empty($conf->accounting->enabled))
		{
			print '<td class="center">';
			print $langs->trans("AccountAccounting");
			print '</td>';
		}*/
		print '<td align="center">&nbsp;</td>';
		print '</tr>';

		print '<tr>';
		print '<td>';
		print '<input name="label" class="flat minwidth200" type="text" value="'.GETPOST("label", "alpha").'">';
		if (is_array($options) && count($options))
		{
			print '<br>'.$langs->trans("Rubrique").': ';
			print Form::selectarray('cat1', $options, GETPOST('cat1'), 1);
		}
		print '</td>';
		print '<td class="nowrap">';
		print $form->selectDate(empty($dateop) ?-1 : $dateop, 'op', 0, 0, 0, 'transaction');
		print '</td>';
		print '<td>&nbsp;</td>';
		print '<td class="nowrap">';
		$form->select_types_paiements((GETPOST('operation') ?GETPOST('operation') : ($object->courant == Account::TYPE_CASH ? 'LIQ' : '')), 'operation', '1,2', 2, 1);
		print '</td>';
		print '<td>';
		print '<input name="num_chq" class="flat" type="text" size="4" value="'.GETPOST("num_chq", "alpha").'">';
		print '</td>';
		//if (! $search_account > 0)
		//{
			print '<td class=right>';
			$form->select_comptes(GETPOST('add_account', 'int') ?GETPOST('add_account', 'int') : $search_account, 'add_account', 0, '', 1, ($id > 0 || !empty($ref) ? ' disabled="disabled"' : ''));
			print '</td>';
		//}
		print '<td class="right"><input name="adddebit" class="flat" type="text" size="4" value="'.GETPOST("adddebit", "alpha").'"></td>';
		print '<td class="right"><input name="addcredit" class="flat" type="text" size="4" value="'.GETPOST("addcredit", "alpha").'"></td>';
		/*if (! empty($conf->accounting->enabled))
		{
			print '<td class="center">';
			print $formaccounting->select_account($search_accountancy_code, 'search_accountancy_code', 1, null, 1, 1, '');
			print '</td>';
		}*/
		print '<td class="center">';
		print '<input type="submit" name="save" class="button buttongen marginbottomonly" value="'.$langs->trans("Add").'"><br>';
		print '<input type="submit" name="cancel" class="button buttongen marginbottomonly button-cancel" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';

		print '</table>';
		print '<br>';
	}

	/// ajax to adjust value date with plus and less picto
	print '
    <script type="text/javascript">
    $(function() {
    	$("a.ajax").each(function(){
    		var current = $(this);
    		current.click(function()
    		{
    			$.get("'.DOL_URL_ROOT.'/core/ajax/bankconciliate.php?"+current.attr("href").split("?")[1], function(data)
    			{
    			    console.log(data)
    				current.parent().prev().replaceWith(data);
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
	if ($action != 'addline' && $action != 'reconcile')
	{
		if (empty($conf->global->BANK_DISABLE_DIRECT_INPUT))
		{
			if (empty($conf->global->BANK_USE_OLD_VARIOUS_PAYMENT))	// Default is to record miscellaneous direct entries using miscellaneous payments
			{
				$newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create&accountid='.$search_account.'&backtopage='.urlencode($_SERVER['PHP_SELF'].'?id='.urlencode($search_account)), '', $user->rights->banque->modifier);
			} else // If direct entries is not done using miscellaneous payments
			{
				$newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=addline&page='.$page.$param, '', $user->rights->banque->modifier);
			}
		} else {
			$newcardbutton = dolGetButtonTitle($langs->trans('AddBankRecord'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?action=addline&page='.$page.$param, '', -1);
		}
	}

	/*$morehtml = '<div class="inline-block '.(($buttonreconcile || $newcardbutton) ? 'marginrightonly' : '').'">';
	$morehtml .= '<label for="pageplusone">'.$langs->trans("Page")."</label> "; // ' Page ';
	$morehtml .= '<input type="text" name="pageplusone" id="pageplusone" class="flat right width25 pageplusone" value="'.($page + 1).'">';
	$morehtml .= '/'.$nbtotalofpages.' ';
	$morehtml .= '</div>';
	*/

	if ($action != 'addline' && $action != 'reconcile')
	{
		$morehtml .= $buttonreconcile;
	}

	$morehtml .= '<!-- Add New button -->'.$newcardbutton;

	$picto = 'bank_account';
	if ($id > 0 || !empty($ref)) $picto = '';

	print_barre_liste($langs->trans("BankTransactions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, $morehtml, '', $limit, 0, 0, 1);

	// We can add page now to param
	if ($page != '') $param .= '&page='.urlencode($page);

	$moreforfilter = '';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateOperationShort').' :';
	$moreforfilter .= ($conf->browser->layout == 'phone' ? '<br>' : ' ');
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $form->selectDate($search_dt_start, 'search_start_dt', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From')).'</div>';
	//$moreforfilter .= ' - ';
	$moreforfilter .= '<div class="nowrap inline-block">'.$form->selectDate($search_dt_end, 'search_end_dt', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to')).'</div>';
	$moreforfilter .= '</div>';

	$moreforfilter .= '<div class="divsearchfield">';
	$moreforfilter .= $langs->trans('DateValueShort').' : ';
	$moreforfilter .= ($conf->browser->layout == 'phone' ? '<br>' : ' ');
	$moreforfilter .= '<div class="nowrap inline-block">';
	$moreforfilter .= $form->selectDate($search_dv_start, 'search_start_dv', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From')).'</div>';
	//$moreforfilter .= ' - ';
	$moreforfilter .= '<div class="nowrap inline-block">'.$form->selectDate($search_dv_end, 'search_end_dv', 0, 0, 1, "search_form", 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to')).'</div>';
	$moreforfilter .= '</div>';

	if (!empty($conf->categorie->enabled))
	{
		// Categories
		if (!empty($conf->categorie->enabled) && !empty($user->rights->categorie->lire))
		{
			$langs->load('categories');

			// Bank line
			$moreforfilter .= '<div class="divsearchfield">';
			$moreforfilter .= $langs->trans('RubriquesTransactions').' : ';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_BANK_LINE, $search_bid, 'parent', null, null, 1);
			$moreforfilter .= $form->selectarray('search_bid', $cate_arbo, $search_bid, 1, 0, 0, '', 0, 0, 0, '', '', 1);
			$moreforfilter .= '</div>';
		}
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
	else $moreforfilter = $hookmanager->resPrint;

	if ($moreforfilter)
	{
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>'."\n";
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	// When action is 'reconcile', we force to have the column num_releve always enabled (otherwise we can't make reconciliation).
	if ($action == 'reconcile') {
		$arrayfields['b.num_releve']['checked'] = 1;
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";


	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['b.rowid']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat" name="search_ref" size="2" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['b.label']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input type="text" class="flat maxwidth100" name="search_description" value="'.dol_escape_htmltag($search_description).'">';
		print '</td>';
	}
	if (!empty($arrayfields['b.dateo']['checked']))
	{
		print '<td class="liste_titre">&nbsp;</td>';
	}
	if (!empty($arrayfields['b.datev']['checked']))
	{
		print '<td class="liste_titre">&nbsp;</td>';
	}
	if (!empty($arrayfields['type']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		$form->select_types_paiements(empty($search_type) ? '' : $search_type, 'search_type', '', 2, 1, 1, 0, 1, 'maxwidth100');
		print '</td>';
	}
	if (!empty($arrayfields['b.num_chq']['checked']))
	{
		// Numero
		print '<td class="liste_titre" align="center"><input type="text" class="flat" name="req_nb" value="'.dol_escape_htmltag($search_req_nb).'" size="2"></td>';
	}
	if (!empty($arrayfields['bu.label']['checked']))
	{
		print '<td class="liste_titre"><input type="text" class="flat maxwidth75" name="search_thirdparty" value="'.dol_escape_htmltag($search_thirdparty).'"></td>';
	}
	if (!empty($arrayfields['ba.ref']['checked']))
	{
		print '<td class="liste_titre">';
		$form->select_comptes($search_account, 'search_account', 0, '', 1, ($id > 0 || !empty($ref) ? ' disabled="disabled"' : ''), 0, 'maxwidth100');
		print '</td>';
	}
	if (!empty($arrayfields['b.debit']['checked']))
	{
		print '<td class="liste_titre right">';
		print '<input type="text" class="flat width50" name="search_debit" value="'.dol_escape_htmltag($search_debit).'">';
		print '</td>';
	}
	if (!empty($arrayfields['b.credit']['checked']))
	{
		print '<td class="liste_titre right">';
		print '<input type="text" class="flat width50" name="search_credit" value="'.dol_escape_htmltag($search_credit).'">';
		print '</td>';
	}
	if (!empty($arrayfields['balancebefore']['checked']))
	{
		print '<td class="liste_titre right">';
		$htmltext = $langs->trans("BalanceVisibilityDependsOnSortAndFilters", $langs->transnoentitiesnoconv("DateValue"));
		print $form->textwithpicto('', $htmltext, 1);
		print '</td>';
	}
	if (!empty($arrayfields['balance']['checked']))
	{
		print '<td class="liste_titre right">';
		$htmltext = $langs->trans("BalanceVisibilityDependsOnSortAndFilters", $langs->transnoentitiesnoconv("DateValue"));
		print $form->textwithpicto('', $htmltext, 1);
		print '</td>';
	}
	// Numero statement
	if (!empty($arrayfields['b.num_releve']['checked']))
	{
		print '<td class="liste_titre" align="center"><input type="text" class="flat" name="search_num_releve" value="'.dol_escape_htmltag($search_num_releve).'" size="3"></td>';
	}
	// Conciliated
	if (!empty($arrayfields['b.conciliated']['checked']))
	{
		print '<td class="liste_titre" align="center">';
		print $form->selectyesno('search_conciliated', $search_conciliated, 1, false, 1, 1);
		print '</td>';
	}
	print '<td class="liste_titre" align="middle">';
	print '</td>';
	print '<td class="liste_titre" align="middle">';
	$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print "</tr>\n";

	// Fields title
	print '<tr class="liste_titre">';
	if (!empty($arrayfields['b.rowid']['checked']))            print_liste_field_titre($arrayfields['b.rowid']['label'], $_SERVER['PHP_SELF'], 'b.rowid', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['b.label']['checked']))            print_liste_field_titre($arrayfields['b.label']['label'], $_SERVER['PHP_SELF'], 'b.label', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['b.dateo']['checked']))            print_liste_field_titre($arrayfields['b.dateo']['label'], $_SERVER['PHP_SELF'], 'b.dateo', '', $param, '', $sortfield, $sortorder, "center ");
	if (!empty($arrayfields['b.datev']['checked']))            print_liste_field_titre($arrayfields['b.datev']['label'], $_SERVER['PHP_SELF'], 'b.datev,b.dateo,b.rowid', '', $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['type']['checked']))               print_liste_field_titre($arrayfields['type']['label'], $_SERVER['PHP_SELF'], '', '', $param, 'align="center"', $sortfield, $sortorder);
	if (!empty($arrayfields['b.num_chq']['checked']))          print_liste_field_titre($arrayfields['b.num_chq']['label'], $_SERVER['PHP_SELF'], 'b.num_chq', '', $param, '', $sortfield, $sortorder, "center ");
	if (!empty($arrayfields['bu.label']['checked']))           print_liste_field_titre($arrayfields['bu.label']['label'], $_SERVER['PHP_SELF'], 'bu.label', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['ba.ref']['checked']))             print_liste_field_titre($arrayfields['ba.ref']['label'], $_SERVER['PHP_SELF'], 'ba.ref', '', $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['b.debit']['checked']))            print_liste_field_titre($arrayfields['b.debit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, "right ");
	if (!empty($arrayfields['b.credit']['checked']))           print_liste_field_titre($arrayfields['b.credit']['label'], $_SERVER['PHP_SELF'], 'b.amount', '', $param, '', $sortfield, $sortorder, "right ");
	if (!empty($arrayfields['balancebefore']['checked']))      print_liste_field_titre($arrayfields['balancebefore']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, "right ");
	if (!empty($arrayfields['balance']['checked']))            print_liste_field_titre($arrayfields['balance']['label'], $_SERVER['PHP_SELF'], '', '', $param, '', $sortfield, $sortorder, "right ");
	if (!empty($arrayfields['b.num_releve']['checked']))       print_liste_field_titre($arrayfields['b.num_releve']['label'], $_SERVER['PHP_SELF'], 'b.num_releve', '', $param, '', $sortfield, $sortorder, "center ");
	if (!empty($arrayfields['b.conciliated']['checked']))      print_liste_field_titre($arrayfields['b.conciliated']['label'], $_SERVER['PHP_SELF'], 'b.rappro', '', $param, '', $sortfield, $sortorder, "center ");
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', 'class="right"', $sortfield, $sortorder, 'maxwidthsearch ');
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', 'align="center"', $sortfield, $sortorder, 'maxwidthsearch ');
	print "</tr>\n";

	$balance = 0; // For balance
	$balancebefore = 0; // For balance
	$balancecalculated = false;
	$posconciliatecol = 0;
	$cachebankaccount = array();

	// Loop on each record
	$sign = 1;

	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$objp = $db->fetch_object($resql);
		// If we are in a situation where we need/can show balance, we calculate the start of balance
		if (!$balancecalculated && (!empty($arrayfields['balancebefore']['checked']) || !empty($arrayfields['balance']['checked'])) && ($mode_balance_ok || $search_conciliated === '0'))
		{
			if (!$search_account)
			{
				dol_print_error('', 'account is not defined but $mode_balance_ok is true');
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
			$sqlforbalance .= " AND b.fk_account = ".$search_account;
			$sqlforbalance .= " AND (b.datev < '".$db->idate($db->jdate($objp->dv))."' OR (b.datev = '".$db->idate($db->jdate($objp->dv))."' AND (b.dateo < '".$db->idate($db->jdate($objp->do))."' OR (b.dateo = '".$db->idate($db->jdate($objp->do))."' AND b.rowid < ".$objp->rowid."))))";
			$resqlforbalance = $db->query($sqlforbalance);
			//print $sqlforbalance;
			if ($resqlforbalance) {
				$objforbalance = $db->fetch_object($resqlforbalance);
				if ($objforbalance) {
					// If sort is desc,desc,desc then total of previous date + amount is the balancebefore of the previous line before the line to show
					if ($sortfield == 'b.datev,b.dateo,b.rowid' && $sortorder == 'desc,desc,desc')
					{
						$balancebefore = $objforbalance->previoustotal + ($sign * $objp->amount);
					} // If sort is asc,asc,asc then total of previous date is balance of line before the next line to show
					else {
						$balance = $objforbalance->previoustotal;
					}
				}
			} else dol_print_error($db);

			$balancecalculated = true;

			// Output a line with start balance
			if ($user->rights->banque->consolidate && $action == 'reconcile')
			{
				$tmpnbfieldbeforebalance = 0;
				$tmpnbfieldafterbalance = 0;
				$balancefieldfound = 0;
				foreach ($arrayfields as $key => $val)
				{
					if ($key == 'balancebefore' || $key == 'balance')
					{
						$balancefieldfound++;
						continue;
					}
		   			if (!empty($arrayfields[$key]['checked']))
		   			{
		   				if (!$balancefieldfound) $tmpnbfieldbeforebalance++;
		   				else $tmpnbfieldafterbalance++;
		   			}
				}
				// Extra fields
				$element = 'banktransaction';
				if (is_array($extrafields->attributes[$element]['label']) && count($extrafields->attributes[$element]['label']))
				{
					foreach ($extrafields->attributes[$element]['label'] as $key => $val)
					{
						if (!empty($arrayfields["ef.".$key]['checked']))
						{
				   			if (!empty($arrayfields[$key]['checked']))
				   			{
				   				if (!$balancefieldfound) $tmpnbfieldbeforebalance++;
				   				else $tmpnbfieldafterbalance++;
				   			}
						}
					}
				}

				print '<tr class="oddeven trforbreak">';
				if ($tmpnbfieldbeforebalance)
				{
					print '<td colspan="'.$tmpnbfieldbeforebalance.'">';
					print '&nbsp;';
					print '</td>';
				}

				if (!empty($arrayfields['balancebefore']['checked']))
				{
					print '<td class="right">';
					if ($search_conciliated !== '0') {
						print price(price2num($balance, 'MT'), 1, $langs);
					}
					print '</td>';
				}
				if (!empty($arrayfields['balance']['checked']))
				{
					print '<td class="right">';
					if ($search_conciliated !== '0') {
						print price(price2num($balance, 'MT'), 1, $langs);
					}
					print '</td>';
				}
				if (!empty($arrayfields['b.num_releve']['checked']))
				{
					print '<td class="center">';
					print '<input type="checkbox" id="selectAll" title="'.dol_escape_htmltag($langs->trans("SelectAll")).'" />';
					print ' <script type="text/javascript">
							$("input#selectAll").change(function() {
								$("input[type=checkbox][name^=rowid]").prop("checked", $(this).is(":checked"));
							});
							</script>';
					print '</td>';
				}
				print '<td colspan="'.($tmpnbfieldafterbalance + 2).'">';
				print '&nbsp;';
				print '</td>';
				print '</tr>';
			}
		}


		if ($sortfield == 'b.datev,b.dateo,b.rowid' && $sortorder == 'desc,desc,desc')
		{
			$balance = price2num($balancebefore, 'MT'); // balance = balancebefore of previous line (sort is desc)
			$balancebefore = price2num($balancebefore - ($sign * $objp->amount), 'MT');
		} else {
			$balancebefore = price2num($balance, 'MT'); // balancebefore = balance of previous line (sort is asc)
			$balance = price2num($balance + ($sign * $objp->amount), 'MT');
		}

		if (empty($cachebankaccount[$objp->bankid]))
		{
			$bankaccounttmp = new Account($db);
			$bankaccounttmp->fetch($objp->bankid);
			$cachebankaccount[$objp->bankid] = $bankaccounttmp;
			$bankaccount = $bankaccounttmp;
		} else {
			$bankaccount = $cachebankaccount[$objp->bankid];
		}

		if (empty($conf->global->BANK_COLORIZE_MOVEMENT)) {
			$backgroundcolor = "class='oddeven'";
		} else {
			if ($objp->amount < 0)
			{
				if (empty($conf->global->BANK_COLORIZE_MOVEMENT_COLOR1)) {
					$color = '#fca955';
				} else {
					$color = '#'.$conf->global->BANK_COLORIZE_MOVEMENT_COLOR1;
				}
				$backgroundcolor = 'style="background: '.$color.';"';
			} else {
				if (empty($conf->global->BANK_COLORIZE_MOVEMENT_COLOR2)) {
					$color = '#7fdb86';
				} else {
					$color = '#'.$conf->global->BANK_COLORIZE_MOVEMENT_COLOR2;
				}
				$backgroundcolor = 'style="background: '.$color.';"';
			}
		}

		$banklinestatic->id = $objp->rowid;
		$banklinestatic->ref = $objp->rowid;

		print '<tr class="oddeven" '.$backgroundcolor.'>';

		// Ref
		if (!empty($arrayfields['b.rowid']['checked']))
		{
				print '<td class="nowrap left">';
				print $banklinestatic->getNomUrl(1);
				print '</td>';
				if (!$i) $totalarray['nbfield']++;
		}

		// Description
		if (!empty($arrayfields['b.label']['checked']))
		{
			print "<td>";

			//print "<a href=\"line.php?rowid=".$objp->rowid."&amp;account=".$objp->fk_account."\">";
			$reg = array();
			preg_match('/\((.+)\)/i', $objp->label, $reg); // Si texte entoure de parenthee on tente recherche de traduction
			if ($reg[1] && $langs->trans($reg[1]) != $reg[1]) print $langs->trans($reg[1]);
			else {
				if ($objp->label == '(payment_salary)') {
					print dol_trunc($langs->trans("SalaryPayment", 40));
				} else {
					print dol_trunc($objp->label, 40);
				}
			}
			//print "</a>&nbsp;";

			// Add links after description
			$links = $bankaccountstatic->get_url($objp->rowid);
			$cachebankaccount = array();
			foreach ($links as $key=>$val)
			{
				print '<!-- '.$links[$key]['type'].' -->';
				if ($links[$key]['type'] == 'withdraw')
				{
					$banktransferstatic->id = $links[$key]['url_id'];
					$banktransferstatic->ref = $links[$key]['label'];
					print ' '.$banktransferstatic->getNomUrl(0);
				} elseif ($links[$key]['type'] == 'payment')
				{
					$paymentstatic->id = $links[$key]['url_id'];
					$paymentstatic->ref = $links[$key]['url_id']; // FIXME This is id, not ref of payment
					print ' '.$paymentstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'payment_supplier')
				{
					$paymentsupplierstatic->id = $links[$key]['url_id'];
					$paymentsupplierstatic->ref = $links[$key]['url_id']; // FIXME This is id, not ref of payment
					print ' '.$paymentsupplierstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'payment_sc')
				{
					$paymentscstatic->id = $links[$key]['url_id'];
					$paymentscstatic->ref = $links[$key]['url_id'];
					$paymentscstatic->label = $links[$key]['label'];
					print ' '.$paymentscstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'payment_vat')
				{
					$paymentvatstatic->id = $links[$key]['url_id'];
					$paymentvatstatic->ref = $links[$key]['url_id'];
					print ' '.$paymentvatstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'payment_salary')
				{
					$paymentsalstatic->id = $links[$key]['url_id'];
					$paymentsalstatic->ref = $links[$key]['url_id'];
					$paymentsalstatic->label = $links[$key]['label'];
					print ' '.$paymentsalstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'payment_loan')
				{
					print '<a href="'.DOL_URL_ROOT.'/loan/payment/card.php?id='.$links[$key]['url_id'].'">';
					print ' '.img_object($langs->trans('ShowPayment'), 'payment').' ';
					print '</a>';
				} elseif ($links[$key]['type'] == 'payment_donation')
				{
					$paymentdonationstatic->id = $links[$key]['url_id'];
					$paymentdonationstatic->ref = $links[$key]['url_id'];
					print ' '.$paymentdonationstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'payment_expensereport')
				{
					$paymentexpensereportstatic->id = $links[$key]['url_id'];
					$paymentexpensereportstatic->ref = $links[$key]['url_id'];
					print ' '.$paymentexpensereportstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'payment_various')
				{
					$paymentvariousstatic->id = $links[$key]['url_id'];
					$paymentvariousstatic->ref = $links[$key]['url_id'];
					print ' '.$paymentvariousstatic->getNomUrl(2);
				} elseif ($links[$key]['type'] == 'banktransfert')
				{
					// Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
					if ($objp->amount > 0)
					{
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id = $banklinestatic->fk_account;
						$bankstatic->label = $banklinestatic->bank_account_ref;
						print ' ('.$langs->trans("TransferFrom").' ';
						print $bankstatic->getNomUrl(1, 'transactions');
						print ' '.$langs->trans("toward").' ';
						$bankstatic->id = $objp->bankid;
						$bankstatic->label = $objp->bankref;
						print $bankstatic->getNomUrl(1, '');
						print ')';
					} else {
						$bankstatic->id = $objp->bankid;
						$bankstatic->label = $objp->bankref;
						print ' ('.$langs->trans("TransferFrom").' ';
						print $bankstatic->getNomUrl(1, '');
						print ' '.$langs->trans("toward").' ';
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id = $banklinestatic->fk_account;
						$bankstatic->label = $banklinestatic->bank_account_ref;
						print $bankstatic->getNomUrl(1, 'transactions');
						print ')';
					}
					//var_dump($links);
				} elseif ($links[$key]['type'] == 'company')
				{
				} elseif ($links[$key]['type'] == 'user')
				{
				} elseif ($links[$key]['type'] == 'member')
				{
				} elseif ($links[$key]['type'] == 'sc')
				{
				} else {
					// Show link with label $links[$key]['label']
					if (!empty($objp->label) && !empty($links[$key]['label'])) print ' - ';
					print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
					if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg))
					{
						// Label generique car entre parentheses. On l'affiche en le traduisant
						if ($reg[1] == 'paiement') $reg[1] = 'Payment';
						print ' '.$langs->trans($reg[1]);
					} else {
						print ' '.$links[$key]['label'];
					}
					print '</a>';
				}
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Date ope
		if (!empty($arrayfields['b.dateo']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print '<span id="dateoperation_'.$objp->rowid.'">'.dol_print_date($db->jdate($objp->do), "day")."</span>";
			print '&nbsp;';
			print '<span class="inline-block">';
			print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=doprev&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_remove()."</a> ";
			print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=donext&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_add()."</a>";
			print '</span>';
			print "</td>\n";
				if (!$i) $totalarray['nbfield']++;
		}

		// Date value
		if (!empty($arrayfields['b.datev']['checked']))
		{
			print '<td align="center" class="nowrap">';
			print '<span id="datevalue_'.$objp->rowid.'">'.dol_print_date($db->jdate($objp->dv), "day")."</span>";
			print '&nbsp;';
			print '<span class="inline-block">';
			print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=dvprev&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_remove()."</a> ";
			print '<a class="ajax" href="'.$_SERVER['PHP_SELF'].'?action=dvnext&amp;account='.$objp->bankid.'&amp;rowid='.$objp->rowid.'">';
			print img_edit_add()."</a>";
			print '</span>';
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Payment type
		if (!empty($arrayfields['type']['checked']))
		{
			print '<td align="center" class="nowrap">';
			$labeltype = ($langs->trans("PaymentTypeShort".$objp->fk_type) != "PaymentTypeShort".$objp->fk_type) ? $langs->trans("PaymentTypeShort".$objp->fk_type) : $langs->getLabelFromKey($db, $objp->fk_type, 'c_paiement', 'code', 'libelle', '', 1);
			if ($labeltype == 'SOLD') print '&nbsp;'; //$langs->trans("InitialBankBalance");
			else print $labeltype;
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Num cheque
		if (!empty($arrayfields['b.num_chq']['checked']))
		{
			print '<td class="nowrap" align="center">'.($objp->num_chq ? $objp->num_chq : "")."</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Third party
		if (!empty($arrayfields['bu.label']['checked']))
		{
			print '<td class="tdoverflowmax150">';
			if ($objp->url_id)
			{
				$companystatic->id = $objp->url_id;
				$companystatic->name = $objp->nom;
				$companystatic->name_alias = $objp->name_alias;
				$companystatic->client = $objp->client;
				$companystatic->email = $objp->email;
				$companystatic->fournisseur = $objp->fournisseur;
				$companystatic->code_client = $objp->code_client;
				$companystatic->code_fournisseur = $objp->code_fournisseur;
				$companystatic->code_compta = $objp->code_compta;
				$companystatic->code_compta_fournisseur = $objp->code_compta_fournisseur;
				print $companystatic->getNomUrl(1);
			} else {
				print '&nbsp;';
			}
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Bank account
		if (!empty($arrayfields['ba.ref']['checked']))
		{
			print '<td class="nowrap">';
			print $bankaccount->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Debit
		if (!empty($arrayfields['b.debit']['checked']))
		{
			print '<td class="nowrap right">';
			if ($objp->amount < 0)
			{
				print price($objp->amount * -1);
				$totalarray['totaldeb'] += $objp->amount;
			}
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['totaldebfield'] = $totalarray['nbfield'];
		}

		// Credit
		if (!empty($arrayfields['b.credit']['checked']))
		{
			print '<td class="nowrap right">';
			if ($objp->amount > 0)
			{
				print price($objp->amount);
				$totalarray['totalcred'] += $objp->amount;
			}
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
			if (!$i) $totalarray['totalcredfield'] = $totalarray['nbfield'];
		}

		// Balance before
		if (!empty($arrayfields['balancebefore']['checked']))
		{
			if ($mode_balance_ok)
			{
				if ($balancebefore >= 0)
				{
					print '<td class="nowrap right">&nbsp;'.price($balancebefore).'</td>';
				} else {
					print '<td class="error nowrap right">&nbsp;'.price($balancebefore).'</td>';
				}
			} else {
				print '<td class="right">-</td>';
			}
			if (!$i) $totalarray['nbfield']++;
		}
		// Balance
		if (!empty($arrayfields['balance']['checked']))
		{
			if ($mode_balance_ok)
			{
				if ($balance >= 0)
				{
					print '<td class="nowrap right">&nbsp;'.price($balance).'</td>';
				} else {
					print '<td class="error nowrap right">&nbsp;'.price($balance).'</td>';
				}
			} else {
				print '<td class="right">-</td>';
			}
			if (!$i) $totalarray['nbfield']++;
		}

		if (!empty($arrayfields['b.num_releve']['checked']))
		{
			print '<td class="nowraponall" align="center">';
			// Transaction reconciliated or edit link
			if ($bankaccount->canBeConciliated() > 0)
			{
				if ($objp->num_releve)
				{
					print '<a href="releve.php?num='.$objp->num_releve.'&account='.$objp->bankid.'&save_lastsearch_values=1">'.$objp->num_releve.'</a>';
				}
				if (!$objp->conciliated && $action == 'reconcile')
				{
					if ($objp->num_releve) print '&nbsp;';
					print '<input class="flat" name="rowid['.$objp->rowid.']" type="checkbox" value="'.$objp->rowid.'" size="1"'.(!empty($_POST['rowid'][$objp->rowid]) ? ' checked' : '').'>';
				}
			}
			print '</td>';
			if (!$i)
			{
				$totalarray['nbfield']++;
				$posconciliatecol = $totalarray['nbfield'];
			}
		}

		if (!empty($arrayfields['b.conciliated']['checked']))
		{
			print '<td class="nowraponall" align="center">';
			print $objp->conciliated ? $langs->trans("Yes") : $langs->trans("No");
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action edit/delete
		print '<td class="nowraponall" align="center">';
		// Transaction reconciliated or edit link
		if ($objp->conciliated && $bankaccount->canBeConciliated() > 0)  // If line not conciliated and account can be conciliated
		{
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/compta/bank/line.php?save_lastsearch_values=1&amp;rowid='.$objp->rowid.'&amp;account='.$objp->bankid.'&amp;page='.$page.'">';
			print img_edit();
			print '</a>';
		} else {
			if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
			{
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/compta/bank/line.php?save_lastsearch_values=1&amp;rowid='.$objp->rowid.'&amp;account='.$objp->bankid.'&amp;page='.$page.'">';
				print img_edit();
				print '</a>';
			} else {
				print '<a class="editfielda" href="'.DOL_URL_ROOT.'/compta/bank/line.php?save_lastsearch_values=1&amp;rowid='.$objp->rowid.'&amp;account='.$objp->bankid.'&amp;page='.$page.'">';
				print img_view();
				print '</a>';
			}
			if ($bankaccount->canBeConciliated() > 0 && empty($objp->conciliated))
			{
				if ($db->jdate($objp->dv) < ($now - $conf->bank->rappro->warning_delay))
				{
					print ' '.img_warning($langs->trans("ReconciliationLate"));
				}
			}
			if ($user->rights->banque->modifier)
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;token='.newToken().'&amp;rowid='.$objp->rowid.'&amp;id='.$objp->bankid.'&amp;page='.$page.'">';
				print img_delete('', 'class="marginleftonly"');
				print '</a>';
			}
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		// Action column
		print '<td class="nowrap" align="center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>";

		$i++;
	}

	// Show total line
	if (isset($totalarray['totaldebfield']) || isset($totalarray['totalcredfield']))
	{
		print '<tr class="liste_total">';
		$i = 0;
		while ($i < $totalarray['nbfield'])
		{
			$i++;
			if ($i == 1)
			{
				if ($num < $limit && empty($offset)) print '<td class="left">'.$langs->trans("Total").'</td>';
				else print '<td class="left tdoverflowmax50" title="'.$langs->trans("Totalforthispage").'">'.$langs->trans("Totalforthispage").'</td>';
			} elseif ($totalarray['totaldebfield'] == $i) print '<td class="right">'.price(-1 * $totalarray['totaldeb']).'</td>';
			elseif ($totalarray['totalcredfield'] == $i) print '<td class="right">'.price($totalarray['totalcred']).'</td>';
			elseif ($i == $posconciliatecol)
			{
				print '<td class="center">';
				if ($user->rights->banque->consolidate && $action == 'reconcile') print '<input class="button" name="confirm_reconcile" type="submit" value="'.$langs->trans("Conciliate").'">';
				print '</td>';
			} else print '<td></td>';
		}
		print '</tr>';
	}

	// If no record found
	if ($num == 0)
	{
		$colspan = 1;
		foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
		print '<tr><td colspan="'.($colspan + 1).'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
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
