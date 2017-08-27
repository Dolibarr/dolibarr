<?php
use Stripe\BankAccount;

/* Copyright (C) 2007-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2017	Alexandre Spangaro	<aspangaro@zendsi.com>
 * Copyright (C) 2013-2014	Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014	Olivier Geffroy		<jeff@jeffinfo.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 		htdocs/accountancy/journal/bankjournal.php
 * \ingroup 	Advanced accountancy
 * \brief 		Page with bank journal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT . '/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';

$langs->loadLangs(array("companies","other","compta","banks",'bills','donations',"accountancy","trips","salaries","hrm"));

// Multi journal
$id_journal = GETPOST('id_journal', 'int');

$date_startmonth = GETPOST('date_startmonth','int');
$date_startday = GETPOST('date_startday','int');
$date_startyear = GETPOST('date_startyear','int');
$date_endmonth = GETPOST('date_endmonth','int');
$date_endday = GETPOST('date_endday','int');
$date_endyear = GETPOST('date_endyear','int');
$in_bookkeeping = GETPOST('in_bookkeeping','aZ09');
if ($in_bookkeeping == '') $in_bookkeeping = 'notyet';

$now = dol_now();
$action = GETPOST('action','aZ09');

// Security check
if ($user->societe_id > 0 && empty($id_journal))
	accessforbidden();


/*
 * Actions
 */

$error = 0;

$year_current = strftime("%Y", dol_now());
$pastmonth = strftime("%m", dol_now()) - 1;
$pastmonthyear = $year_current;
if ($pastmonth == 0) {
	$pastmonth = 12;
	$pastmonthyear --;
}

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_start) || empty($date_end)) // We define date_start and date_end
{
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

$idpays = $mysoc->country_id;

$sql  = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_account,";
$sql .= " ba.courant, ba.ref as baref, ba.account_number, ba.fk_accountancy_journal,";
$sql .= " soc.code_compta, soc.code_compta_fournisseur, soc.rowid as socid, soc.nom as name, bu1.type as typeop,";
$sql .= " u.accountancy_code, u.rowid as userid, u.lastname as lastname, u.firstname as firstname, bu2.type as typeop";
$sql .= " FROM " . MAIN_DB_PREFIX . "bank as b";
$sql .= " JOIN " . MAIN_DB_PREFIX . "bank_account as ba on b.fk_account=ba.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu2 ON bu2.fk_bank = b.rowid AND bu2.type='user'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc on bu1.url_id=soc.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u on bu2.url_id=u.rowid";
$sql .= " WHERE ba.fk_accountancy_journal=" . $id_journal;
$sql .= ' AND b.amount != 0 AND ba.entity IN ('.getEntity('bank_account', 0).')';		// We don't share object for accountancy
if ($date_start && $date_end)
	$sql .= " AND b.dateo >= '" . $db->idate($date_start) . "' AND b.dateo <= '" . $db->idate($date_end) . "'";
if ($in_bookkeeping == 'already')
	$sql .= " AND (b.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
if ($in_bookkeeping == 'notyet')
	$sql .= " AND (b.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
$sql .= " ORDER BY b.datev";
//print $sql;

$object = new Account($db);
$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$societestatic = new Societe($db);
$userstatic = new User($db);
$bankaccountstatic = new Account($db);
$chargestatic = new ChargeSociales($db);
$paymentdonstatic = new PaymentDonation($db);
$paymentvatstatic = new TVA($db);
$paymentsalstatic = new PaymentSalary($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);
$paymentvariousstatic = new PaymentVarious($db);

// Get code of finance journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $accountingjournalstatic->label;

dol_syslog("accountancy/journal/bankjournal.php", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {

	$num = $db->num_rows($result);

	// Variables
	$account_supplier = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : 'NotDefined');	// NotDefined is a reserved word
	$account_customer = (! empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : 'NotDefined');	// NotDefined is a reserved word
	$account_employee = (! empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) ? $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT : 'NotDefined');	// NotDefined is a reserved word
	$account_pay_vat = (! empty($conf->global->ACCOUNTING_VAT_PAY_ACCOUNT) ? $conf->global->ACCOUNTING_VAT_PAY_ACCOUNT : 'NotDefined');	// NotDefined is a reserved word
	$account_pay_donation = (! empty($conf->global->DONATION_ACCOUNTINGACCOUNT) ? $conf->global->DONATION_ACCOUNTINGACCOUNT : 'NotDefined');	// NotDefined is a reserved word
	$account_transfer = (! empty($conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH) ? $conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH : 'NotDefined');	// NotDefined is a reserved word

	$tabcompany = array();
	$tabuser = array();
	$tabpay = array ();
	$tabbq = array ();
	$tabtp = array ();
	$tabtype = array ();

	// Loop on each line into llx_bank table. For each line, we should get:
	// one line tabpay = line into bank
	// one line for bank record = tabbq
	// one line for thirdparty record = tabtp
	$i = 0;
	while ( $i < $num )
	{
		$obj = $db->fetch_object($result);

		// Set accountancy code (for bank and thirdparty)
		$compta_bank = $obj->account_number;

		$compta_soc = 'NotDefined';
		if ($obj->label == '(SupplierInvoicePayment)' || $obj->label == '(SupplierInvoicePaymentBack)')
			$compta_soc = (! empty($obj->code_compta_fournisseur) ? $obj->code_compta_fournisseur : $account_supplier);
		if ($obj->label == '(CustomerInvoicePayment)' || $obj->label == '(CustomerInvoicePaymentBack)')
			$compta_soc = (! empty($obj->code_compta) ? $obj->code_compta : $account_customer);

		$tabcompany[$obj->rowid] = array (
				'id' => $obj->socid,
				'name' => $obj->name,
				'code_compta' => $compta_soc,
		);

		$compta_user = (! empty($obj->accountancy_code) ? $obj->accountancy_code : $account_employee);

		$tabuser[$obj->rowid] = array (
				'id' => $obj->userid,
				'name' => dolGetFirstLastname($obj->firstname, $obj->lastname),
				'lastname' => $obj->lastname,
				'firstname' => $obj->firstname,
				'accountancy_code' => $compta_user,
		);

		// Variable bookkeeping
		$tabpay[$obj->rowid]["date"] = $obj->do;
		$tabpay[$obj->rowid]["type_payment"] = $obj->fk_type;		// CHQ, VIR, LIQ, CB, ...
		$tabpay[$obj->rowid]["ref"] = $obj->label;					// By default. Not unique. May be changed later
		$tabpay[$obj->rowid]["fk_bank"] = $obj->rowid;
		$tabpay[$obj->rowid]["fk_bank_account"] = $obj->fk_account;
		if (preg_match('/^\((.*)\)$/i', $obj->label, $reg)) {
			$tabpay[$obj->rowid]["lib"] = $langs->trans($reg[1]);
		} else {
			$tabpay[$obj->rowid]["lib"] = dol_trunc($obj->label, 60);
		}
		$links = $object->get_url($obj->rowid);

		//var_dump($i);
		//var_dump($tabpay);

		// By default
		$tabpay[$obj->rowid]['type'] = 'unknown';	// Can be SOLD, miscellaneous entry, payment of patient, or old record with no links in bank_url.
		$tabtype[$obj->rowid] = 'unknown';

		// get_url may return -1 which is not traversable
		if (is_array($links) && count($links) > 0) {

			// Now loop on each link of record in bank.
			foreach ($links as $key => $val) {

				if (in_array($links[$key]['type'], array('sc', 'payment_sc', 'payment', 'payment_supplier', 'payment_vat', 'payment_expensereport', 'banktransfert', 'payment_donation', 'payment_salary', 'payment_various')))
				{
					// So we excluded 'company' and 'user' here. We want only payment lines

					// We save tabtype for a future use, to remember what kind of payment it is
					$tabpay[$obj->rowid]['type'] = $links[$key]['type'];
					$tabtype[$obj->rowid] = $links[$key]['type'];
				}
				elseif (in_array($links[$key]['type'], array('company', 'user')))
				{
					if ($tabpay[$obj->rowid]['type'] == 'unknown')
					{
						// We can guess here it is a bank record for a thirdparty company or a user.
						// But we won't be able to record somewhere else than into a waiting account, because there is no other journal to record the contreparty.
					}
				}

				if ($links[$key]['type'] == 'payment') {
					$paymentstatic->id = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentid"] = $paymentstatic->id;
				} else if ($links[$key]['type'] == 'payment_supplier') {
					$paymentsupplierstatic->id = $links[$key]['url_id'];
					$paymentsupplierstatic->ref = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentsupplierstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsupplierid"] = $paymentsupplierstatic->id;
				} else if ($links[$key]['type'] == 'company') {
					$societestatic->id = $links[$key]['url_id'];
					$societestatic->name = $links[$key]['label'];
					$tabpay[$obj->rowid]["soclib"] = $societestatic->getNomUrl(1, '', 30);
					if ($compta_soc) $tabtp[$obj->rowid][$compta_soc] += $obj->amount;
				} else if ($links[$key]['type'] == 'user') {
					$userstatic->id = $links[$key]['url_id'];
					$userstatic->name = $links[$key]['label'];
					if ($userstatic->id > 0) $tabpay[$obj->rowid]["soclib"] = $userstatic->getNomUrl(1, '', 30);
					else $tabpay[$obj->rowid]["soclib"] = '???';	// Should not happen, but happens with old data when id of user was not saved on expense report payment.
					if ($compta_user) $tabtp[$obj->rowid][$compta_user] += $obj->amount;
				} else if ($links[$key]['type'] == 'sc') {
					$chargestatic->id = $links[$key]['url_id'];
					$chargestatic->ref = $links[$key]['url_id'];

					$tabpay[$obj->rowid]["lib"] .= ' ' . $chargestatic->getNomUrl(2);
					if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
						if ($reg[1] == 'socialcontribution')
							$reg[1] = 'SocialContribution';
						$chargestatic->lib = $langs->trans($reg[1]);
					} else {
						$chargestatic->lib = $links[$key]['label'];
					}
					$chargestatic->ref = $chargestatic->lib;
					$tabpay[$obj->rowid]["soclib"] = $chargestatic->getNomUrl(1, 30);

					$sqlmid = 'SELECT cchgsoc.accountancy_code';
					$sqlmid .= " FROM " . MAIN_DB_PREFIX . "c_chargesociales cchgsoc ";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "chargesociales as chgsoc ON chgsoc.fk_type=cchgsoc.id";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementcharge as paycharg ON paycharg.fk_charge=chgsoc.rowid";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "bank_url as bkurl ON bkurl.url_id=paycharg.rowid";
					$sqlmid .= " WHERE bkurl.fk_bank=" . $obj->rowid;

					dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
					$resultmid = $db->query($sqlmid);
					if ($resultmid) {
						$objmid = $db->fetch_object($resultmid);
						$tabtp[$obj->rowid][$objmid->accountancy_code] += $obj->amount;
					}
				} else if ($links[$key]['type'] == 'payment_donation') {
					$paymentdonstatic->id = $links[$key]['url_id'];
					$paymentdonstatic->fk_donation = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentdonstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentdonationid"] = $paymentdonstatic->id;
					$tabtp[$obj->rowid][$account_pay_donation] += $obj->amount;
				} else if ($links[$key]['type'] == 'payment_vat') {
					$paymentvatstatic->id = $links[$key]['url_id'];
					$paymentvatstatic->ref = $links[$key]['url_id'];
					$paymentvatstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentvatstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentvatid"] = $paymentvatstatic->id;
					$tabtp[$obj->rowid][$account_pay_vat] += $obj->amount;
				} else if ($links[$key]['type'] == 'payment_salary') {
					$paymentsalstatic->id = $links[$key]['url_id'];
					$paymentsalstatic->ref = $links[$key]['url_id'];
					$paymentsalstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentsalstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsalid"] = $paymentsalstatic->id;
				} else if ($links[$key]['type'] == 'payment_expensereport') {
					$paymentexpensereportstatic->id = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentexpensereportstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentexpensereport"] = $paymentexpensereportstatic->id;
				} else if ($links[$key]['type'] == 'payment_various') {
					$paymentvariousstatic->id = $links[$key]['url_id'];
					$paymentvariousstatic->ref = $links[$key]['url_id'];
					$paymentvariousstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentvariousstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentvariousid"] = $paymentvariousstatic->id;
					$paymentvariousstatic->fetch($paymentvariousstatic->id);
					$account_various = (! empty($paymentvariousstatic->accountancy_code) ? $paymentvariousstatic->accountancy_code : 'NotDefined');	// NotDefined is a reserved word
					$tabtp[$obj->rowid][$account_various] += $obj->amount;
				} else if ($links[$key]['type'] == 'banktransfert') {
					$tabpay[$obj->rowid]["lib"] .= ' ' . $langs->trans("BankTransfer");
					$tabtp[$obj->rowid][$account_transfer] += $obj->amount;
					$bankaccountstatic->fetch($tabpay[$obj->rowid]['fk_bank_account']);
					$tabpay[$obj->rowid]["soclib"] = $bankaccountstatic->getNomUrl(2);
				}
			}
		}

		$tabbq[$obj->rowid][$compta_bank] += $obj->amount;

		// If not links were found to know amount on thirdparty, we init it.
		if (empty($tabtp[$obj->rowid])) $tabtp[$obj->rowid]['NotDefined']= $tabbq[$obj->rowid][$compta_bank];

		// Check account number is ok
		/*if ($action == 'writebookkeeping')		// Make test now in such a case
		{
			reset($tabbq[$obj->rowid]);
			$first_key_tabbq = key($tabbq[$obj->rowid]);
			if (empty($first_key_tabbq))
			{
				$error++;
				setEventMessages($langs->trans('ErrorAccountancyCodeOnBankAccountNotDefined', $obj->baref), null, 'errors');
			}
			reset($tabtp[$obj->rowid]);
			$first_key_tabtp = key($tabtp[$obj->rowid]);
			if (empty($first_key_tabtp))
			{
				$error++;
				setEventMessages($langs->trans('ErrorAccountancyCodeOnThirdPartyNotDefined'), null, 'errors');
			}
		}*/

		// if($obj->socid)$tabtp[$obj->rowid][$compta_soc] += $obj->amount;

		$i++;
	}
} else {
	dol_print_error($db);
}

/*
var_dump($tabpay);
var_dump($tabbq);
var_dump($tabtp);
*/

// Write bookkeeping
if (! $error && $action == 'writebookkeeping') {
	$now = dol_now();

	$error = 0;
	foreach ( $tabpay as $key => $val ) {	  // $key is rowid into llx_bank

		$ref = getSourceDocRef($val, $tabtype[$key]);

		$errorforline = 0;

		$db->begin();

		// Introduce a protection. Total of tabtp must be total of tabbq
		/*var_dump($tabpay);
		var_dump($tabtp);
		var_dump($tabbq);exit;*/

		// Bank
		if (! $errorforline && is_array($tabbq[$key]))
		{
			// Line into bank account
			foreach ( $tabbq[$key] as $k => $mt )
			{
				if ($mt) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $ref;
					$bookkeeping->doc_type = 'bank';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_bank"];
					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_operation = $val["label"];
					$bookkeeping->label_compte = $langs->trans("Bank");
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
					$bookkeeping->debit = ($mt >= 0 ? $mt : 0);
					$bookkeeping->credit = ($mt < 0 ? - $mt : 0);
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $journal_label;
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->date_create = $now;

					// No subledger_account value for the bank line
					if ($tabtype[$key] == 'payment') {
						$bookkeeping->subledger_account = '';
					} else if ($tabtype[$key] == 'payment_supplier') {
						$bookkeeping->subledger_account = '';
					} else if ($tabtype[$key] == 'payment_expensereport') {
						$bookkeeping->subledger_account = '';
					} else if ($tabtype[$key] == 'payment_salary') {
						$bookkeeping->subledger_account = '';
					} else if ($tabtype[$key] == 'payment_vat') {
						$bookkeeping->subledger_account = '';
					} else if ($tabtype[$key] == 'payment_donation') {
						$bookkeeping->subledger_account = '';
					} else if ($tabtype[$key] == 'payment_various') {
						$bookkeeping->subledger_account = '';
					} else if ($tabtype[$key] == 'unknown') {
						// ???
						$bookkeeping->subledger_account = '';
					}

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
						{
							$error++;
							$errorforline++;
							setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						}
						else
						{
							$error++;
							$errorforline++;
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		// Third party
		if (! $errorforline && is_array($tabtp[$key]))
		{
			// Line into thirdparty account
			foreach ( $tabtp[$key] as $k => $mt ) {
				if ($mt) {
					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $ref;
					$bookkeeping->doc_type = 'bank';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_bank"];
					$bookkeeping->label_operation = $tabcompany[$key]['name'];
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
					$bookkeeping->debit = ($mt < 0 ? - $mt : 0);
					$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $journal_label;
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->date_create = $now;

					if ($tabtype[$key] == 'payment') {	// If payment is payment of customer invoice, we get ref of invoice
						$bookkeeping->label_operation = '';
						$bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
						$bookkeeping->subledger_label = $tabcompany[$key]['name'];
						$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
						$bookkeeping->label_compte = '';
					} else if ($tabtype[$key] == 'payment_supplier') {		   // If payment is payment of supplier invoice, we get ref of invoice
						$bookkeeping->label_operation = '';
						$bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
						$bookkeeping->subledger_label = $tabcompany[$key]['name'];
						$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
						$bookkeeping->label_compte = '';
					} else if ($tabtype[$key] == 'payment_expensereport') {
						$bookkeeping->label_operation = $tabuser[$key]['name'];
						$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
						$bookkeeping->subledger_label = $tabuser[$key]['name'];
						$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
						$bookkeeping->label_compte = '';
					} else if ($tabtype[$key] == 'payment_salary') {
						$bookkeeping->label_operation = $tabuser[$key]['name'];
						$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
						$bookkeeping->subledger_label = $tabuser[$key]['name'];
						$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
						$bookkeeping->label_compte = '';
					} else if (in_array($tabtype[$key], array('sc', 'payment_sc'))) {   // If payment is payment of social contribution
						$bookkeeping->label_operation = '';
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = $objmid->labelc;
					} else if ($tabtype[$key] == 'payment_vat') {
						$bookkeeping->label_operation = '';
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = '';
					} else if ($tabtype[$key] == 'payment_donation') {
						$bookkeeping->label_operation = '';
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = '';
					} else if ($tabtype[$key] == 'payment_various') {
						$bookkeeping->label_operation = '';
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = '';
					} else if ($tabtype[$key] == 'banktransfert') {
						$bookkeeping->label_operation = '';
						$bookkeeping->subledger_account = '';
						$bookkeeping->subledger_label = '';
						$bookkeeping->numero_compte = $k;
						$bookkeeping->label_compte = '';
					} else {
						if ($tabtype[$key] == 'unknown')	// Unknown transaction, we will use a waiting account for thirdparty.
						{
							// Temporary account
							$bookkeeping->label_operation = '';
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE;
							$bookkeeping->label_compte = '';
						}
					}

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
						{
							$error++;
							$errorforline++;
							setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						}
						else
						{
							$error++;
							$errorforline++;
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		if (! $errorforline)
		{
			$db->commit();
		}
		else
		{
			//print 'KO for line '.$key.' '.$error.'<br>';
			$db->rollback();

			$MAXNBERRORS=5;
			if ($error >= $MAXNBERRORS)
			{
			    setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped").' (>'.$MAXNBERRORS.')', null, 'errors');
			    break;  // Break in the foreach
			}
		}
	}

	if (empty($error) && count($tabpay) > 0) {
		setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
	}
	elseif (count($tabpay) == $error)
	{
		setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
	}
	else
	{
		setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
	}

	$action = '';

	// Must reload data, so we make a redirect
	if (count($tabpay) != $error)
	{
		$param='id_journal='.$id_journal;
		$param.='&date_startday='.$date_startday;
		$param.='&date_startmonth='.$date_startmonth;
		$param.='&date_startyear='.$date_startyear;
		$param.='&date_endday='.$date_endday;
		$param.='&date_endmonth='.$date_endmonth;
		$param.='&date_endyear='.$date_endyear;
		$param.='&in_bookeeping='.$in_bookeeping;
		header("Location: ".$_SERVER['PHP_SELF'].($param?'?'.$param:''));
		exit;
	}
}

// Export
if ($action == 'exportcsv') {		// ISO and not UTF8 !
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	$companystatic = new Client($db);
	$userstatic = new User($db);

	foreach ( $tabpay as $key => $val ) {
		$date = dol_print_date($db->jdate($val["date"]), 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		//
		if (! empty($tabcompany[$key]['id']))
		{
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];
		}
		else
		{
			$companystatic->id = 0;
			$companystatic->name = '';
		}
		if (! empty($tabuser[$key]['id']))
		{
			$userstatic->id = $tabuser[$key]['id'];
			$userstatic->lastname = $tabuser[$key]['lastname'];
			$userstatic->firstname = $tabuser[$key]['firstname'];
		}
		else
		{
			$userstatic->id = 0;
			$userstatic->lastname = '';
			$userstatic->firstname = '';
		}

		// Bank
		foreach ( $tabbq[$key] as $k => $mt ) {
			print '"' . $journal . '"' . $sep;
			print '"' . $date . '"' . $sep;
			print '"' . $val["type_payment"] . '"' . $sep;
			print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
			print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
			print "  " . $sep;
			if ($companystatic->name == '') {
				print '"' . $langs->trans('Bank') . " - " . utf8_decode($reflabel) . '"' . $sep;
			} else {
				print '"' . $langs->trans("Bank") . ' - ' . utf8_decode($companystatic->name) . '"' . $sep;
			}
			print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
			print '"' . ($mt < 0 ? price(- $mt) : '') . '"';
			print "\n";
		}

		// Third party
		if (is_array($tabtp[$key])) {
			foreach ( $tabtp[$key] as $k => $mt ) {
				if ($mt) {
					print '"' . $journal . '"' . $sep;
					print '"' . $date . '"' . $sep;
					print '"' . $val["type_payment"] . '"' . $sep;
					print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
					if ($tabtype[$key] == 'payment_supplier') {
					print '"' . $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER . '"' . $sep;
					} else if($tabtype[$key] == 'payment') {
					print '"' . $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER . '"' . $sep;
					} else {
					print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
					}
					print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
					if ($companystatic->name == '') {
						print '"' . $langs->trans('ThirdParty') . " - " . utf8_decode($reflabel) . '"' . $sep;
					} else {
						print '"' . $langs->trans('ThirdParty') . " - " . utf8_decode($companystatic->name) . '"' . $sep;
					}
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"';
					print "\n";
				}
			}
		} else {
			foreach ( $tabbq[$key] as $k => $mt ) {
				print '"' . $journal . '"' . $sep;
				print '"' . $date . '"' . $sep;
				print '"' . $val["type_payment"] . '"' . $sep;
				print '"' . length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) . '"' . $sep;
				print '"' . length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) . '"' . $sep;
				print "  " . $sep;
				if ($companystatic->name == '') {
					print '"' . $langs->trans("Bank") . ' - ' . utf8_decode($reflabel) . '"' . $sep;
				} else {
					print '"' . $langs->trans("Bank") . ' - ' . utf8_decode($companystatic->name) . '"' . $sep;
				}
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"';
				print "\n";
			}
		}
	}
}


/*
 * View
 */

$form = new Form($db);

if (empty($action) || $action == 'view') {
	$invoicestatic = new Facture($db);
	$invoicesupplierstatic = new FactureFournisseur($db);
	$expensereportstatic = new ExpenseReport($db);
	$vatstatic = new Tva($db);
	$donationstatic = new Don($db);
	$salarystatic = new PaymentSalary($db);
	$variousstatic = new PaymentVarious($db);

	llxHeader('', $langs->trans("FinanceJournal"));

	$nom = $langs->trans("FinanceJournal") . ' - ' . $accountingjournalstatic->getNomUrl(1);
	$builddate = time();
	//$description = $langs->trans("DescFinanceJournal") . '<br>';
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';

	$listofchoices=array('already'=>$langs->trans("AlreadyInGeneralLedger"), 'notyet'=>$langs->trans("NotYetInGeneralLedger"));
	$period = $form->select_date($date_start, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end, 'date_end', 0, 0, 0, '', 1, 0, 1). ' -  ' .$langs->trans("JournalizationInLedgerStatus").' '. $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal=' . $id_journal;

	journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

	// Button to write into Ledger
	if (empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) || $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER == '-1'
		|| empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) || $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER == '-1'
		|| empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) || $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT == '-1') {
		print img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		print ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '<strong>'.$langs->transnoentitiesnoconv("MenuFinancial").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
	}


	print '<div class="tabsAction tabsActionNoBottom">';
	print '<input type="button" class="butAction" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
	print '<input type="button" class="butAction" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
	print '</div>';

	// TODO Avoid using js. We can use a direct link with $param
	print '
	<script type="text/javascript">
		function launch_export() {
			console.log("Set value into form and submit");
			$("div.fiche div.tabBar form input[name=\"action\"]").val("exportcsv");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
			$("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("Set value into form and submit");
			$("div.fiche div.tabBar form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche div.tabBar form input[type=\"submit\"]").click();
			$("div.fiche div.tabBar form input[name=\"action\"]").val("");
		}
	</script>';

	/*
	 * Show result array
	 */
	print '<br>';

	$i = 0;
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
	print "<td></td>";
	print "<td>" . $langs->trans("Date") . "</td>";
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("ObjectsRef") . ")</td>";
	print "<td>" . $langs->trans("AccountAccounting") . "</td>";
	print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
	print "<td>" . $langs->trans("Label") . "</td>";
	print "<td>" . $langs->trans("PaymentMode") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$r = '';

	foreach ( $tabpay as $key => $val ) {	  // $key is rowid in llx_bank
		$date = dol_print_date($db->jdate($val["date"]), 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		// Bank
		foreach ( $tabbq[$key] as $k => $mt )
		{
			print '<tr class="oddeven">';
			print "<td><!-- Bank bank.rowid=".$key."--></td>";
			print "<td>" . $date . "</td>";
			print "<td>" . $ref . "</td>";
			// Ledger account
			print "<td>";
			$accounttoshow = length_accountg($k);
			if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
			{
				print '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
			}
			else print $accounttoshow;
			print "</td>";
			// Subledger account
			print "<td>";
			/*$accounttoshow = length_accountg($k);
			if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
			{
				print '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
			}
			else print $accounttoshow;*/
			print "</td>";
			if ($val['soclib'] == '') {
				print "<td>" . $langs->trans("Bank") . " - " . $reflabel . "</td>";
			} else {
				print "<td>" . $langs->trans("Bank") . " - " . $val['soclib'] . "</td>";
			}
			print "<td>" . $val["type_payment"] . "</td>";
			print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
			print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
			print "</tr>";
		}

		// Third party
		if (is_array($tabtp[$key])) {
			foreach ( $tabtp[$key] as $k => $mt ) {
				if ($k != 'type') {
					print '<tr class="oddeven">';
					print "<td><!-- Thirdparty bank.rowid=".$key." --></td>";
					print "<td>" . $date . "</td>";
					print "<td>" . $ref . "</td>";
					// Ledger account
					print "<td>";
					$account_ledger = $k;

					if ($tabtype[$key] == 'payment') $account_ledger = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
					if ($tabtype[$key] == 'payment_supplier') $account_ledger = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
					if ($tabtype[$key] == 'payment_expensereport') $account_ledger = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					if ($tabtype[$key] == 'payment_salary') $account_ledger = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					if ($tabtype[$key] == 'payment_vat') $account_ledger = $conf->global->ACCOUNTING_VAT_PAY_ACCOUNT;
					$accounttoshow = length_accounta($account_ledger);
					if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
					{
						if ($tabtype[$key] == 'unknown')
						{
							// We will accept writing, but into a waiting account
							print '<span class="warning">'.$langs->trans('UnknownAccountForThirdparty', length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE)).'</span>';	// We will a waiting account
						}
						else
						{
							// We will refuse writing
							$errorstring='UnknownAccountForThirdpartyBlocking';
							if ($tabtype[$key] == 'payment') $errorstring='MainAccountForCustomersNotDefined';
							if ($tabtype[$key] == 'payment_supplier') $errorstring='MainAccountForSuppliersNotDefined';
							if ($tabtype[$key] == 'payment_expensereport') $errorstring='MainAccountForUsersNotDefined';
							if ($tabtype[$key] == 'payment_salary') $errorstring='MainAccountForUsersNotDefined';
							if ($tabtype[$key] == 'payment_vat') $errorstring='MainAccountForVatPaymentNotDefined';
							print '<span class="error">'.$langs->trans($errorstring).'</span>';
						}
					}
					else print $accounttoshow;
					print "</td>";
					// Subledger account
					print "<td>";
					if (in_array($tabtype[$key], array('payment', 'payment_supplier', 'payment_expensereport', 'payment_salary')))	// Type of payment with subledger
					{
						$accounttoshowsubledger = length_accounta($k);
						if ($accounttoshow != $accounttoshowsubledger)
						{
							if (empty($accounttoshowsubledger) || $accounttoshowsubledger == 'NotDefined')
							{
								print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
							}
							else print $accounttoshowsubledger;
						}
					}
					print "</td>";
					print "<td>" . $reflabel . ' ' . $val['soclib'] . "</td>";
					print "<td>" . $val["type_payment"] . "</td>";
					print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
					print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
					print "</tr>";
				}
			}
		} else {
			foreach ( $tabbq[$key] as $k => $mt ) {
				print '<tr class="oddeven">';
				print "<td><!-- Wait bank.rowid=".$key." --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $ref . "</td>";
				// Ledger account
				print "<td>";
				/*if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("WaitAccountNotDefined").'</span>';
				}
				else */ print length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE);
				print "</td>";
				// Subledger account
				print "<td>";
				/*if (empty($accounttoshowsubledger) || $accounttoshowsubledger == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("WaitAccountNotDefined").'</span>';
				}
				else print length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE);
				*/
				print "</td>";
				print "<td>" . $reflabel . "</td>";
				print "<td>" . $val["type_payment"] . "</td>";
				print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "</tr>";
			}
		}
	}

	print "</table>";

	llxFooter();
}

$db->close();



/**
 * Return source for doc_ref of a bank transaction
 *
 * @param 	string 	$val			Array of val
 * @param 	string	$typerecord		Type of record
 * @return string|unknown
 */
function getSourceDocRef($val, $typerecord)
{
	global $db, $langs;

	// Defined the docref into $ref (We start with $val['ref'] by default and we complete according to other data)
	// WE MUST HAVE SAME REF FOR ALL LINES WE WILL RECORD INTO THE BOOKKEEPING
	$reflabel = $val['ref'];
	if ($reflabel == '(SupplierInvoicePayment)' || $reflabel == '(SupplierInvoicePaymentBack)') {
		$reflabel = $langs->trans('Supplier');
	}
	if ($reflabel == '(CustomerInvoicePayment)' || $reflabel == '(CustomerInvoicePaymentBack)') {
		$reflabel = $langs->trans('Customer');
	}
	if ($reflabel == '(SocialContributionPayment)') {
		$reflabel = $langs->trans('SocialContribution');
	}
	if ($reflabel == '(DonationPayment)') {
		$reflabel = $langs->trans('Donation');
	}
	if ($reflabel == '(SubscriptionPayment)') {
		$reflabel = $langs->trans('Subscription');
	}
	if ($reflabel == '(ExpenseReportPayment)') {
		$reflabel = $langs->trans('Employee');
	}
	if ($reflabel == '(payment_salary)') {
		$reflabel = $langs->trans('Employee');
	}
	$ref=$reflabel;
	if ($typerecord == 'payment')
	{
		$sqlmid = 'SELECT payfac.fk_facture as id, f.facnumber as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."paiement_facture as payfac, ".MAIN_DB_PREFIX."facture as f";
		$sqlmid .= " WHERE payfac.fk_facture = f.rowid AND payfac.fk_paiement=" . $val["paymentid"];
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$ref=$langs->trans("Invoice");
			while ($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}
	elseif ($typerecord == 'payment_supplier')
	{
		$sqlmid = 'SELECT payfac.fk_facturefourn as id, f.ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfac, ".MAIN_DB_PREFIX."facture_fourn as f";
		$sqlmid .= " WHERE payfac.fk_facturefourn = f.rowid AND payfac.fk_paiementfourn=" . $val["paymentsupplierid"];
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$ref=$langs->trans("SupplierInvoice");
			while($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}
	elseif ($typerecord == 'payment_expensereport')
	{
		$sqlmid = 'SELECT e.rowid as id, e.ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_expensereport as pe, " . MAIN_DB_PREFIX . "expensereport as e";
		$sqlmid .= " WHERE pe.rowid=" . $val["paymentexpensereport"]." AND pe.fk_expensereport = e.rowid";
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$ref=$langs->trans("ExpenseReport");
			while($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}
	elseif ($typerecord == 'payment_salary')
	{
		$sqlmid = 'SELECT s.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_salary as s";
		$sqlmid .= " WHERE s.rowid=" . $val["paymentsalid"];
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$ref=$langs->trans("SalaryPayment");
			while ($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}
	elseif ($typerecord == 'payment_vat')
	{
		$sqlmid = 'SELECT v.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "tva as v";
		$sqlmid .= " WHERE v.rowid=" . $val["paymentvatid"];
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$ref=$langs->trans("PaymentVat");
			while ($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}
	elseif ($typerecord == 'payment_donation')
	{
		$sqlmid = 'SELECT payd.fk_donation as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_donation as payd";
		$sqlmid .= " WHERE payd.fk_donation=" . $val["paymentdonationid"];
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$ref=$langs->trans("Donation").' ';
			while ($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}
	elseif ($typerecord == 'payment_various')
	{
		$sqlmid = 'SELECT v.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_various as v";
		$sqlmid .= " WHERE v.rowid=" . $val["paymentvariousid"];
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			$ref=$langs->trans("VariousPayment");
			while ($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}

	return $ref;
}
