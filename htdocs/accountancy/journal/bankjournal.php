<?php
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2022  Open-DSI                <support@open-dsi.fr>
 * Copyright (C) 2013-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2017-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Eric Seigne             <eric.seigne@cap-rel.fr>
 * Copyright (C) 2021       Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024       MDW                     <mdeweerd@users.noreply.github.com>
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
 *  \file       htdocs/accountancy/journal/bankjournal.php
 *  \ingroup    Accountancy (Double entries)
 *  \brief      Page with bank journal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/paymentdonation.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/client.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT.'/loan/class/paymentloan.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "compta", "banks", "bills", "donations", "loan", "accountancy", "trips", "salaries", "hrm", "members"));

// Multi journal&search_status=-1
$id_journal = GETPOSTINT('id_journal');

$date_startmonth = GETPOSTINT('date_startmonth');
$date_startday = GETPOSTINT('date_startday');
$date_startyear = GETPOSTINT('date_startyear');
$date_endmonth = GETPOSTINT('date_endmonth');
$date_endday = GETPOSTINT('date_endday');
$date_endyear = GETPOSTINT('date_endyear');
$in_bookkeeping = GETPOST('in_bookkeeping', 'aZ09');

$only_rappro = GETPOSTINT('only_rappro');
if ($only_rappro == 0) {
	//GET page for the first time, use default settings
	$only_rappro = getDolGlobalInt('ACCOUNTING_BANK_CONCILIATED');
}

$now = dol_now();

$action = GETPOST('action', 'aZ09');

if ($in_bookkeeping == '') {
	$in_bookkeeping = 'notyet';
}


// Security check
if (!isModEnabled('accounting')) {
	accessforbidden();
}
if ($user->socid > 0) {
	accessforbidden();
}
if (!$user->hasRight('accounting', 'bind', 'write')) {
	accessforbidden();
}


/*
 * Actions
 */

$error = 0;

$date_start = dol_mktime(0, 0, 0, $date_startmonth, $date_startday, $date_startyear);
$date_end = dol_mktime(23, 59, 59, $date_endmonth, $date_endday, $date_endyear);

if (empty($date_startmonth)) {
	// Period by default on transfer
	$dates = getDefaultDatesForTransfer();
	$date_start = $dates['date_start'];
	$pastmonthyear = $dates['pastmonthyear'];
	$pastmonth = $dates['pastmonth'];
}
if (empty($date_endmonth)) {
	// Period by default on transfer
	$dates = getDefaultDatesForTransfer();
	$date_end = $dates['date_end'];
	$pastmonthyear = $dates['pastmonthyear'];
	$pastmonth = $dates['pastmonth'];
}

if (!GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) { // We define date_start and date_end, only if we did not submit the form
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

$sql  = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.amount_main_currency, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_account,";
$sql .= " ba.courant, ba.ref as baref, ba.account_number, ba.fk_accountancy_journal,";
$sql .= " soc.rowid as socid, soc.nom as name, soc.email as email, bu1.type as typeop_company,";
if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
	$sql .= " spe.accountancy_code_customer as code_compta,";
	$sql .= " spe.accountancy_code_supplier as code_compta_fournisseur,";
} else {
	$sql .= " soc.code_compta,";
	$sql .= " soc.code_compta_fournisseur,";
}
$sql .= " u.accountancy_code, u.rowid as userid, u.lastname as lastname, u.firstname as firstname, u.email as useremail, u.statut as userstatus,";
$sql .= " bu2.type as typeop_user,";
$sql .= " bu3.type as typeop_payment, bu4.type as typeop_payment_supplier";
$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql .= " JOIN ".MAIN_DB_PREFIX."bank_account as ba on b.fk_account=ba.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu2 ON bu2.fk_bank = b.rowid AND bu2.type='user'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu3 ON bu3.fk_bank = b.rowid AND bu3.type='payment'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu4 ON bu4.fk_bank = b.rowid AND bu4.type='payment_supplier'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as soc on bu1.url_id=soc.rowid";
if (getDolGlobalString('MAIN_COMPANY_PERENTITY_SHARED')) {
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_perentity as spe ON spe.fk_soc = soc.rowid AND spe.entity = " . ((int) $conf->entity);
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on bu2.url_id=u.rowid";
$sql .= " WHERE ba.fk_accountancy_journal=".((int) $id_journal);
$sql .= ' AND b.amount <> 0 AND ba.entity IN ('.getEntity('bank_account', 0).')'; // We don't share object for accountancy
if ($date_start && $date_end) {
	$sql .= " AND b.dateo >= '".$db->idate($date_start)."' AND b.dateo <= '".$db->idate($date_end)."'";
}
// Define begin binding date
if (getDolGlobalInt('ACCOUNTING_DATE_START_BINDING')) {
	$sql .= " AND b.dateo >= '".$db->idate(getDolGlobalInt('ACCOUNTING_DATE_START_BINDING'))."'";
}
// Already in bookkeeping or not
if ($in_bookkeeping == 'already') {
	$sql .= " AND (b.rowid IN (SELECT fk_doc FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
}
if ($in_bookkeeping == 'notyet') {
	$sql .= " AND (b.rowid NOT IN (SELECT fk_doc FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
}
if ($only_rappro == 2) {
	$sql .= " AND (b.rappro = '1')";
}
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
$paymentvatstatic = new Tva($db);
$paymentsalstatic = new PaymentSalary($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);
$paymentvariousstatic = new PaymentVarious($db);
$paymentloanstatic = new PaymentLoan($db);
$accountLinestatic = new AccountLine($db);
$paymentsubscriptionstatic = new Subscription($db);

$tmppayment = new Paiement($db);
$tmpinvoice = new Facture($db);

$accountingaccount = new AccountingAccount($db);

// Get code of finance journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $accountingjournalstatic->label;

$tabcompany = array();
$tabuser = array();
$tabpay = array();
$tabbq = array();
$tabtp = array();
$tabtype = array();
$tabmoreinfo = array();

'
@phan-var-force array<array{id:mixed,name:mixed,code_compta:string,email:string}> $tabcompany
@phan-var-force array<array{id:int,name:string,lastname:string,firstname:string,email:string,accountancy_code:string,status:int> $tabuser
@phan-var-force array<int,array{date:string,type_payment:string,ref:string,fk_bank:int,ban_account_ref:string,fk_bank_account:int,lib:string,type:string}> $tabpay
@phan-var-force array<array{lib:string,date?:int|string,type_payment?:string,ref?:string,fk_bank?:int,ban_account_ref?:string,fk_bank_account?:int,type?:string,bank_account_ref?:string,paymentid?:int,paymentsupplierid?:int,soclib?:string,paymentscid?:int,paymentdonationid?:int,paymentsubscriptionid?:int,paymentvatid?:int,paymentsalid?:int,paymentexpensereport?:int,paymentvariousid?:int,account_various?:string,paymentloanid?:int}> $tabtp
';

//print $sql;
dol_syslog("accountancy/journal/bankjournal.php", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	//print $sql;

	// Variables
	$account_supplier = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER', 'NotDefined'); // NotDefined is a reserved word
	$account_customer = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER', 'NotDefined'); // NotDefined is a reserved word
	$account_employee = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT', 'NotDefined'); // NotDefined is a reserved word
	$account_expensereport = getDolGlobalString('ACCOUNTING_ACCOUNT_EXPENSEREPORT', 'NotDefined'); // NotDefined is a reserved word
	$account_pay_vat = getDolGlobalString('ACCOUNTING_VAT_PAY_ACCOUNT', 'NotDefined'); // NotDefined is a reserved word
	$account_pay_donation = getDolGlobalString('DONATION_ACCOUNTINGACCOUNT', 'NotDefined'); // NotDefined is a reserved word
	$account_pay_subscription = getDolGlobalString('ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT', 'NotDefined'); // NotDefined is a reserved word
	$account_transfer = getDolGlobalString('ACCOUNTING_ACCOUNT_TRANSFER_CASH', 'NotDefined'); // NotDefined is a reserved word

	// Loop on each line into the llx_bank table. For each line, we should get:
	// one line tabpay = line into bank
	// one line for bank record = tabbq
	// one line for thirdparty record = tabtp
	// Note: tabcompany is used to store the subledger account
	$i = 0;
	while ($i < $num) {
		$obj = $db->fetch_object($result);

		$lineisapurchase = -1;
		$lineisasale = -1;
		// Old method to detect if it's a sale or purchase
		if ($obj->label == '(SupplierInvoicePayment)' || $obj->label == '(SupplierInvoicePaymentBack)') {
			$lineisapurchase = 1;
		}
		if ($obj->label == '(CustomerInvoicePayment)' || $obj->label == '(CustomerInvoicePaymentBack)') {
			$lineisasale = 1;
		}
		// Try a more reliable method to detect if record is a supplier payment or a customer payment
		if ($lineisapurchase < 0) {
			if ($obj->typeop_payment_supplier == 'payment_supplier') {
				$lineisapurchase = 1;
			}
		}
		if ($lineisasale < 0) {
			if ($obj->typeop_payment == 'payment') {
				$lineisasale = 1;
			}
		}
		//var_dump($obj->type_payment); //var_dump($obj->type_payment_supplier);
		//var_dump($lineisapurchase); //var_dump($lineisasale);

		// Set accountancy code for bank
		$compta_bank = $obj->account_number;

		// Set accountancy code for thirdparty (example: '411CU...' or '411' if no subledger account defined on customer)
		$compta_soc = 'NotDefined';
		if ($lineisapurchase > 0) {
			$compta_soc = (($obj->code_compta_fournisseur != "") ? $obj->code_compta_fournisseur : $account_supplier);
		}
		if ($lineisasale > 0) {
			$compta_soc = (!empty($obj->code_compta) ? $obj->code_compta : $account_customer);
		}

		$tabcompany[$obj->rowid] = array(
			'id' => $obj->socid,
			'name' => $obj->name,
			'code_compta' => $compta_soc,
			'email' => $obj->email
		);

		// Set accountancy code for user
		// $obj->accountancy_code is the accountancy_code of table u=user (but it is defined only if
		// a link with type 'user' exists and user as a subledger account)
		$compta_user = (!empty($obj->accountancy_code) ? $obj->accountancy_code : '');

		$tabuser[$obj->rowid] = array(
			'id' => $obj->userid,
			'name' => dolGetFirstLastname($obj->firstname, $obj->lastname),
			'lastname' => $obj->lastname,
			'firstname' => $obj->firstname,
			'email' => $obj->useremail,
			'accountancy_code' => $compta_user,
			'status' => $obj->userstatus
		);

		// Variable bookkeeping ($obj->rowid is Bank Id)
		$tabpay[$obj->rowid]["date"] = $db->jdate($obj->do);
		$tabpay[$obj->rowid]["type_payment"] = $obj->fk_type; // CHQ, VIR, LIQ, CB, ...
		$tabpay[$obj->rowid]["ref"] = $obj->label; // By default. Not unique. May be changed later
		$tabpay[$obj->rowid]["fk_bank"] = $obj->rowid;
		$tabpay[$obj->rowid]["bank_account_ref"] = $obj->baref;
		$tabpay[$obj->rowid]["fk_bank_account"] = $obj->fk_account;
		$reg = array();
		if (preg_match('/^\((.*)\)$/i', $obj->label, $reg)) {
			$tabpay[$obj->rowid]["lib"] = $langs->trans($reg[1]);
		} else {
			$tabpay[$obj->rowid]["lib"] = dol_trunc($obj->label, 60);
		}

		// Load of url links to the line into llx_bank (so load llx_bank_url)
		$links = $object->get_url($obj->rowid); // Get an array('url'=>, 'url_id'=>, 'label'=>, 'type'=> 'fk_bank'=> )
		// print '<p>' . json_encode($object) . "</p>";//exit;
		// print '<p>' . json_encode($links) . "</p>";//exit;

		// By default
		$tabpay[$obj->rowid]['type'] = 'unknown'; // Can be SOLD, miscellaneous entry, payment of patient, or any old record with no links in bank_url.
		$tabtype[$obj->rowid] = 'unknown';
		$tabmoreinfo[$obj->rowid] = array();

		$amounttouse = $obj->amount;
		if (!empty($obj->amount_main_currency)) {
			// If $obj->amount_main_currency is set, it means that $obj->amount is not in same currency, we must use $obj->amount_main_currency
			$amounttouse = $obj->amount_main_currency;
		}

		// get_url may return -1 which is not traversable
		if (is_array($links) && count($links) > 0) {
			// Test if entry is for a social contribution, salary or expense report.
			// In such a case, we will ignore the bank url line for user
			$is_sc = false;
			$is_salary = false;
			$is_expensereport = false;
			foreach ($links as $v) {
				if ($v['type'] == 'sc') {
					$is_sc = true;
					break;
				}
				if ($v['type'] == 'payment_salary') {
					$is_salary = true;
					break;
				}
				if ($v['type'] == 'payment_expensereport') {
					$is_expensereport = true;
					break;
				}
			}

			// Now loop on each link of record in bank (code similar to bankentries_list.php)
			foreach ($links as $key => $val) {
				if ($links[$key]['type'] == 'user' && !$is_sc && !$is_salary && !$is_expensereport) {
					// We must avoid as much as possible this "continue". If we want to jump to next loop, it means we don't want to process
					// the case the link is user (often because managed by hard coded code into another link), and we must avoid this.
					continue;
				}
				if (in_array($links[$key]['type'], array('sc', 'payment_sc', 'payment', 'payment_supplier', 'payment_vat', 'payment_expensereport', 'banktransfert', 'payment_donation', 'member', 'payment_loan', 'payment_salary', 'payment_various'))) {
					// So we excluded 'company' and 'user' here. We want only payment lines

					// We save tabtype for a future use, to remember what kind of payment it is
					$tabpay[$obj->rowid]['type'] = $links[$key]['type'];
					$tabtype[$obj->rowid] = $links[$key]['type'];
					/* phpcs:disable -- Code does nothing at this moment -> commented
					} elseif (in_array($links[$key]['type'], array('company', 'user'))) {
						if ($tabpay[$obj->rowid]['type'] == 'unknown') {
							// We can guess here it is a bank record for a thirdparty company or a user.
							// But we won't be able to record somewhere else than into a waiting account, because there is no other journal to record the contreparty.
						}
					*/ // phpcs::enable
				}

				// Special case to ask later to add more request to get information for old links without company link.
				if ($links[$key]['type'] == 'withdraw') {
					$tabmoreinfo[$obj->rowid]['withdraw'] = 1;
				}

				if ($links[$key]['type'] == 'payment') {
					$paymentstatic->id = $links[$key]['url_id'];
					$paymentstatic->ref = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentstatic->getNomUrl(2, '', ''); // TODO Do not include list of invoice in tooltip, the dol_string_nohtmltag is ko with this
					$tabpay[$obj->rowid]["paymentid"] = $paymentstatic->id;
				} elseif ($links[$key]['type'] == 'payment_supplier') {
					$paymentsupplierstatic->id = $links[$key]['url_id'];
					$paymentsupplierstatic->ref = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentsupplierstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsupplierid"] = $paymentsupplierstatic->id;
				} elseif ($links[$key]['type'] == 'company') {
					$societestatic->id = $links[$key]['url_id'];
					$societestatic->name = $links[$key]['label'];
					$societestatic->email = $tabcompany[$obj->rowid]['email'];
					$tabpay[$obj->rowid]["soclib"] = $societestatic->getNomUrl(1, '', 30);
					if ($compta_soc) {
						if (empty($tabtp[$obj->rowid][$compta_soc])) {
							$tabtp[$obj->rowid][$compta_soc] = $amounttouse;
						} else {
							$tabtp[$obj->rowid][$compta_soc] += $amounttouse;
						}
					}
				} elseif ($links[$key]['type'] == 'user') {
					$userstatic->id = $links[$key]['url_id'];
					$userstatic->name = $links[$key]['label'];
					$userstatic->email = $tabuser[$obj->rowid]['email'];
					$userstatic->firstname = $tabuser[$obj->rowid]['firstname'];
					$userstatic->lastname = $tabuser[$obj->rowid]['lastname'];
					$userstatic->status = $tabuser[$obj->rowid]['status'];
					$userstatic->accountancy_code = $tabuser[$obj->rowid]['accountancy_code'];
					// For a payment of social contribution, we have a link sc + user.
					// but we already fill the $tabpay[$obj->rowid]["soclib"] in the line 'sc'.
					// If we fill it here to, we must concat
					if ($userstatic->id > 0) {
						if ($is_sc) {
							$tabpay[$obj->rowid]["soclib"] .= ' '.$userstatic->getNomUrl(1, 'accountancy', 0);
						} else {
							$tabpay[$obj->rowid]["soclib"] = $userstatic->getNomUrl(1, 'accountancy', 0);
						}
					} else {
						$tabpay[$obj->rowid]["soclib"] = '???'; // Should not happen, but happens with old data when id of user was not saved on expense report payment.
					}

					if ($compta_user) {
						if ($is_sc) {
							//$tabcompany[$obj->rowid][$compta_user] += $amounttouse;
						} else {
							$tabtp[$obj->rowid][$compta_user] += $amounttouse;
						}
					}
				} elseif ($links[$key]['type'] == 'sc') {
					$chargestatic->id = $links[$key]['url_id'];
					$chargestatic->ref = $links[$key]['url_id'];

					$tabpay[$obj->rowid]["lib"] .= ' '.$chargestatic->getNomUrl(2);
					$reg = array();
					if (preg_match('/^\((.*)\)$/i', $links[$key]['label'], $reg)) {
						if ($reg[1] == 'socialcontribution') {
							$reg[1] = 'SocialContribution';
						}
						$chargestatic->label = $langs->trans($reg[1]);
					} else {
						$chargestatic->label = $links[$key]['label'];
					}
					$chargestatic->ref = $chargestatic->label;
					$tabpay[$obj->rowid]["soclib"] = $chargestatic->getNomUrl(1, 30);
					$tabpay[$obj->rowid]["paymentscid"] = $chargestatic->id;

					// Retrieve the accounting code of the social contribution of the payment from link of payment.
					// Note: We have the social contribution id, it can be faster to get accounting code from social contribution id.
					$sqlmid = "SELECT cchgsoc.accountancy_code";
					$sqlmid .= " FROM ".MAIN_DB_PREFIX."c_chargesociales cchgsoc";
					$sqlmid .= " INNER JOIN ".MAIN_DB_PREFIX."chargesociales as chgsoc ON chgsoc.fk_type = cchgsoc.id";
					$sqlmid .= " INNER JOIN ".MAIN_DB_PREFIX."paiementcharge as paycharg ON paycharg.fk_charge = chgsoc.rowid";
					$sqlmid .= " INNER JOIN ".MAIN_DB_PREFIX."bank_url as bkurl ON bkurl.url_id=paycharg.rowid AND bkurl.type = 'payment_sc'";
					$sqlmid .= " WHERE bkurl.fk_bank = ".((int) $obj->rowid);

					dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=".$sqlmid, LOG_DEBUG);
					$resultmid = $db->query($sqlmid);
					if ($resultmid) {
						$objmid = $db->fetch_object($resultmid);
						$tabtp[$obj->rowid][$objmid->accountancy_code] = isset($tabtp[$obj->rowid][$objmid->accountancy_code]) ? $tabtp[$obj->rowid][$objmid->accountancy_code] + $amounttouse : $amounttouse;
					}
				} elseif ($links[$key]['type'] == 'payment_donation') {
					$paymentdonstatic->id = $links[$key]['url_id'];
					$paymentdonstatic->ref = $links[$key]['url_id'];
					$paymentdonstatic->fk_donation = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentdonstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentdonationid"] = $paymentdonstatic->id;
					$tabtp[$obj->rowid][$account_pay_donation] = isset($tabtp[$obj->rowid][$account_pay_donation]) ? $tabtp[$obj->rowid][$account_pay_donation] + $amounttouse : $amounttouse;
				} elseif ($links[$key]['type'] == 'member') {
					$paymentsubscriptionstatic->id = $links[$key]['url_id'];
					$paymentsubscriptionstatic->ref = $links[$key]['url_id'];
					$paymentsubscriptionstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentsubscriptionstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsubscriptionid"] = $paymentsubscriptionstatic->id;
					$paymentsubscriptionstatic->fetch($paymentsubscriptionstatic->id);
					$tabtp[$obj->rowid][$account_pay_subscription] = isset($tabtp[$obj->rowid][$account_pay_subscription]) ? $tabtp[$obj->rowid][$account_pay_subscription] + $amounttouse : $amounttouse;
				} elseif ($links[$key]['type'] == 'payment_vat') {				// Payment VAT
					$paymentvatstatic->id = $links[$key]['url_id'];
					$paymentvatstatic->ref = $links[$key]['url_id'];
					$paymentvatstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentvatstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentvatid"] = $paymentvatstatic->id;
					$tabtp[$obj->rowid][$account_pay_vat] = isset($tabtp[$obj->rowid][$account_pay_vat]) ? $tabtp[$obj->rowid][$account_pay_vat] + $amounttouse : $amounttouse;
				} elseif ($links[$key]['type'] == 'payment_salary') {
					$paymentsalstatic->id = $links[$key]['url_id'];
					$paymentsalstatic->ref = $links[$key]['url_id'];
					$paymentsalstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentsalstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsalid"] = $paymentsalstatic->id;

					// This part of code is no more required. it is here to solve case where a link were missing (with v14.0.0) and keep writing in accountancy complete.
					// Note: A better way to fix this is to delete payment of salary and recreate it, or to fix the bookkeeping table manually after.
					if (getDolGlobalString('ACCOUNTANCY_AUTOFIX_MISSING_LINK_TO_USER_ON_SALARY_BANK_PAYMENT')) {
						$tmpsalary = new Salary($db);
						$tmpsalary->fetch($paymentsalstatic->id);
						$tmpsalary->fetch_user($tmpsalary->fk_user);

						$userstatic->id = $tmpsalary->user->id;
						$userstatic->name = $tmpsalary->user->name;
						$userstatic->email = $tmpsalary->user->email;
						$userstatic->firstname = $tmpsalary->user->firstname;
						$userstatic->lastname = $tmpsalary->user->lastname;
						$userstatic->status = $tmpsalary->user->status;
						$userstatic->accountancy_code = $tmpsalary->user->accountancy_code;

						if ($userstatic->id > 0) {
							$tabpay[$obj->rowid]["soclib"] = $userstatic->getNomUrl(1, 'accountancy', 0);
						} else {
							$tabpay[$obj->rowid]["soclib"] = '???'; // Should not happen
						}

						if (empty($obj->typeop_user)) {	// Add test to avoid to add amount twice if a link already exists also on user.
							$compta_user = $userstatic->accountancy_code;
							if ($compta_user) {
								$tabtp[$obj->rowid][$compta_user] += $amounttouse;
								$tabuser[$obj->rowid] = array(
								'id' => $userstatic->id,
								'name' => dolGetFirstLastname($userstatic->firstname, $userstatic->lastname),
								'lastname' => $userstatic->lastname,
								'firstname' => $userstatic->firstname,
								'email' => $userstatic->email,
								'accountancy_code' => $compta_user,
								'status' => $userstatic->status
								);
							}
						}
					}
				} elseif ($links[$key]['type'] == 'payment_expensereport') {
					$paymentexpensereportstatic->id = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= $paymentexpensereportstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentexpensereport"] = $paymentexpensereportstatic->id;
				} elseif ($links[$key]['type'] == 'payment_various') {
					$paymentvariousstatic->id = $links[$key]['url_id'];
					$paymentvariousstatic->ref = $links[$key]['url_id'];
					$paymentvariousstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentvariousstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentvariousid"] = $paymentvariousstatic->id;
					$paymentvariousstatic->fetch($paymentvariousstatic->id);
					$account_various = (!empty($paymentvariousstatic->accountancy_code) ? $paymentvariousstatic->accountancy_code : 'NotDefined'); // NotDefined is a reserved word
					$account_subledger = (!empty($paymentvariousstatic->subledger_account) ? $paymentvariousstatic->subledger_account : ''); // NotDefined is a reserved word
					$tabpay[$obj->rowid]["account_various"] = $account_various;
					$tabtp[$obj->rowid][$account_subledger] = isset($tabtp[$obj->rowid][$account_subledger]) ? $tabtp[$obj->rowid][$account_subledger] + $amounttouse : $amounttouse;
				} elseif ($links[$key]['type'] == 'payment_loan') {
					$paymentloanstatic->id = $links[$key]['url_id'];
					$paymentloanstatic->ref = $links[$key]['url_id'];
					$paymentloanstatic->fk_loan = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' '.$paymentloanstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentloanid"] = $paymentloanstatic->id;
					//$tabtp[$obj->rowid][$account_pay_loan] += $amounttouse;
					$sqlmid = 'SELECT pl.amount_capital, pl.amount_insurance, pl.amount_interest, l.accountancy_account_capital, l.accountancy_account_insurance, l.accountancy_account_interest';
					$sqlmid .= ' FROM '.MAIN_DB_PREFIX.'payment_loan as pl, '.MAIN_DB_PREFIX.'loan as l';
					$sqlmid .= ' WHERE l.rowid = pl.fk_loan AND pl.fk_bank = '.((int) $obj->rowid);

					dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=".$sqlmid, LOG_DEBUG);
					$resultmid = $db->query($sqlmid);
					if ($resultmid) {
						$objmid = $db->fetch_object($resultmid);
						$tabtp[$obj->rowid][$objmid->accountancy_account_capital] = isset($tabtp[$obj->rowid][$objmid->accountancy_account_capital]) ? $tabtp[$obj->rowid][$objmid->accountancy_account_capital] - $objmid->amount_capital : $amounttouse;
						$tabtp[$obj->rowid][$objmid->accountancy_account_insurance] = isset($tabtp[$obj->rowid][$objmid->accountancy_account_insurance]) ? $tabtp[$obj->rowid][$objmid->accountancy_account_insurance] - $objmid->amount_insurance : $amounttouse;
						$tabtp[$obj->rowid][$objmid->accountancy_account_interest] = isset($tabtp[$obj->rowid][$objmid->accountancy_account_interest]) ? $tabtp[$obj->rowid][$objmid->accountancy_account_interest] - $objmid->amount_interes : $amounttouse;
					}
				} elseif ($links[$key]['type'] == 'banktransfert') {
					$accountLinestatic->fetch($links[$key]['url_id']);
					$tabpay[$obj->rowid]["lib"] .= ' '.$langs->trans("BankTransfer").'- '.$accountLinestatic ->getNomUrl(1);
					$tabtp[$obj->rowid][$account_transfer] = isset($tabtp[$obj->rowid][$account_transfer]) ? $tabtp[$obj->rowid][$account_transfer] + $amounttouse : $amounttouse;
					$bankaccountstatic->fetch($tabpay[$obj->rowid]['fk_bank_account']);
					$tabpay[$obj->rowid]["soclib"] = $bankaccountstatic->getNomUrl(2);
				}
			}
		}

		if (empty($tabbq[$obj->rowid][$compta_bank])) {
			$tabbq[$obj->rowid][$compta_bank] = $amounttouse;
		} else {
			$tabbq[$obj->rowid][$compta_bank] += $amounttouse;
		}

		// If no links were found to know the amount on thirdparty, we try to guess it.
		// This may happens on bank entries without the links lines to 'company'.
		if (empty($tabtp[$obj->rowid]) && !empty($tabmoreinfo[$obj->rowid]['withdraw'])) {	// If we don't find 'company' link because it is an old 'withdraw' record
			foreach ($links as $key => $val) {
				if ($links[$key]['type'] == 'payment') {
					// Get thirdparty
					$tmppayment->fetch($links[$key]['url_id']);
					$arrayofamounts = $tmppayment->getAmountsArray();
					if (is_array($arrayofamounts)) {
						foreach ($arrayofamounts as $invoiceid => $amount) {
							$tmpinvoice->fetch($invoiceid);
							$tmpinvoice->fetch_thirdparty();
							if ($tmpinvoice->thirdparty->code_compta_client) {
								$tabtp[$obj->rowid][$tmpinvoice->thirdparty->code_compta_client] += $amount;
							}
						}
					}
				}
			}
		}

		// If no links were found to know the amount on thirdparty/user, we init it to account 'NotDefined'.
		if (empty($tabtp[$obj->rowid])) {
			$tabtp[$obj->rowid]['NotDefined'] = $tabbq[$obj->rowid][$compta_bank];
		}

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

		// if($obj->socid)$tabtp[$obj->rowid][$compta_soc] += $amounttouse;

		$i++;
	}
} else {
	dol_print_error($db);
}


//var_dump($tabpay);
//var_dump($tabcompany);
//var_dump($tabbq);
//var_dump($tabtp);
//var_dump($tabtype);

// Write bookkeeping
if (!$error && $action == 'writebookkeeping') {
	$now = dol_now();

	$accountingaccountcustomer = new AccountingAccount($db);
	$accountingaccountcustomer->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER'), true);

	$accountingaccountsupplier = new AccountingAccount($db);
	$accountingaccountsupplier->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER'), true);

	$accountingaccountpayment = new AccountingAccount($db);
	$accountingaccountpayment->fetch(null, getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT'), true);

	$accountingaccountexpensereport = new AccountingAccount($db);
	$accountingaccountexpensereport->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_EXPENSEREPORT, true);

	$accountingaccountsuspense = new AccountingAccount($db);
	$accountingaccountsuspense->fetch(null, getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'), true);

	$error = 0;
	foreach ($tabpay as $key => $val) {		// $key is rowid into llx_bank
		$date = dol_print_date($val["date"], 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		$errorforline = 0;

		$totalcredit = 0;
		$totaldebit = 0;

		$db->begin();

		// Introduce a protection. Total of tabtp must be total of tabbq
		//var_dump($tabpay);
		//var_dump($tabtp);
		//var_dump($tabbq);exit;

		// Bank
		if (!$errorforline && is_array($tabbq[$key])) {
			// Line into bank account
			foreach ($tabbq[$key] as $k => $mt) {
				if ($mt) {
					if (empty($conf->cache['accountingaccountincurrententity'][$k])) {
						$accountingaccount = new AccountingAccount($db);
						$accountingaccount->fetch(0, $k, true);	// $k is accounting account of the bank.
						$conf->cache['accountingaccountincurrententity'][$k] = $accountingaccount;
					} else {
						$accountingaccount = $conf->cache['accountingaccountincurrententity'][$k];
					}

					$account_label = $accountingaccount->label;

					$reflabel = '';
					if (!empty($val['lib'])) {
						$reflabel .= dol_string_nohtmltag($val['lib'])." - ";
					}
					$reflabel .= $langs->trans("Bank").' '.dol_string_nohtmltag($val['bank_account_ref']);
					if (!empty($val['soclib'])) {
						$reflabel .= " - ".dol_string_nohtmltag($val['soclib']);
					}

					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $ref;
					$bookkeeping->doc_type = 'bank';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_bank"];

					$bookkeeping->numero_compte = $k;
					$bookkeeping->label_compte = $account_label;

					$bookkeeping->label_operation = $reflabel;
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
					$bookkeeping->debit = ($mt >= 0 ? $mt : 0);
					$bookkeeping->credit = ($mt < 0 ? -$mt : 0);
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $langs->transnoentities($journal_label);
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->date_creation = $now;

					// No subledger_account value for the bank line but add a specific label_operation
					$bookkeeping->subledger_account = '';
					$bookkeeping->label_operation = $reflabel;
					$bookkeeping->entity = $conf->entity;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

					$result = $bookkeeping->create($user);
					if ($result < 0) {
						if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
							$error++;
							$errorforline++;
							setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
						} else {
							$error++;
							$errorforline++;
							setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
						}
					}
				}
			}
		}

		// Third party
		if (!$errorforline) {
			if (is_array($tabtp[$key])) {
				// Line into thirdparty account
				foreach ($tabtp[$key] as $k => $mt) {
					if ($mt) {
						$lettering = false;

						$reflabel = '';
						if (!empty($val['lib'])) {
							$reflabel .= dol_string_nohtmltag($val['lib']).($val['soclib'] ? " - " : "");
						}
						if ($tabtype[$key] == 'banktransfert') {
							$reflabel .= dol_string_nohtmltag($langs->transnoentitiesnoconv('TransitionalAccount').' '.$account_transfer);
						} else {
							$reflabel .= dol_string_nohtmltag($val['soclib']);
						}

						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $ref;
						$bookkeeping->doc_type = 'bank';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_bank"];

						$bookkeeping->label_operation = $reflabel;
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
						$bookkeeping->debit = ($mt < 0 ? -$mt : 0);
						$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $langs->transnoentities($journal_label);
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->date_creation = $now;

						if ($tabtype[$key] == 'payment') {	// If payment is payment of customer invoice, we get ref of invoice
							$lettering = true;
							$bookkeeping->subledger_account = $k; // For payment, the subledger account is stored as $key of $tabtp
							$bookkeeping->subledger_label = $tabcompany[$key]['name']; // $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
							$bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
							$bookkeeping->label_compte = $accountingaccountcustomer->label;
						} elseif ($tabtype[$key] == 'payment_supplier') {	// If payment is payment of supplier invoice, we get ref of invoice
							$lettering = true;
							$bookkeeping->subledger_account = $k; // For payment, the subledger account is stored as $key of $tabtp
							$bookkeeping->subledger_label = $tabcompany[$key]['name']; // $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
							$bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER');
							$bookkeeping->label_compte = $accountingaccountsupplier->label;
						} elseif ($tabtype[$key] == 'payment_expensereport') {
							$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
							$bookkeeping->subledger_label = $tabuser[$key]['name'];
							$bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_EXPENSEREPORT');
							$bookkeeping->label_compte = $accountingaccountexpensereport->label;
						} elseif ($tabtype[$key] == 'payment_salary') {
							$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
							$bookkeeping->subledger_label = $tabuser[$key]['name'];
							$bookkeeping->numero_compte = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
							$bookkeeping->label_compte = $accountingaccountpayment->label;
						} elseif (in_array($tabtype[$key], array('sc', 'payment_sc'))) {   // If payment is payment of social contribution
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$accountingaccount->fetch(null, $k, true);	// TODO Use a cache
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_vat') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_donation') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'member') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_loan') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_various') {
							$bookkeeping->subledger_account = $k;
							$bookkeeping->subledger_label = $tabcompany[$key]['name'];
							$accountingaccount->fetch(null, $tabpay[$key]["account_various"], true);	// TODO Use a cache
							$bookkeeping->numero_compte = $tabpay[$key]["account_various"];
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'banktransfert') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$accountingaccount->fetch(null, $k, true);		// TODO Use a cache
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = $accountingaccount->label;
						} else {
							if ($tabtype[$key] == 'unknown') {	// Unknown transaction, we will use a waiting account for thirdparty.
								// Temporary account
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$bookkeeping->numero_compte = getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE');
								$bookkeeping->label_compte = $accountingaccountsuspense->label;
							}
						}
						$bookkeeping->label_operation = $reflabel;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);
						if ($result < 0) {
							if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
								$error++;
								$errorforline++;
								setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
							} else {
								$error++;
								$errorforline++;
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						} else {
							if ($lettering && getDolGlobalInt('ACCOUNTING_ENABLE_LETTERING') && getDolGlobalInt('ACCOUNTING_ENABLE_AUTOLETTERING')) {
								require_once DOL_DOCUMENT_ROOT . '/accountancy/class/lettering.class.php';
								$lettering_static = new Lettering($db);
								$nb_lettering = $lettering_static->bookkeepingLetteringAll(array($bookkeeping->id));
							}
						}
					}
				}
			} else {	// If thirdparty unknown, output the waiting account
				foreach ($tabbq[$key] as $k => $mt) {
					if ($mt) {
						$reflabel = '';
						if (!empty($val['lib'])) {
							$reflabel .= dol_string_nohtmltag($val['lib'])." - ";
						}
						$reflabel .= dol_string_nohtmltag('WaitingAccount');

						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $ref;
						$bookkeeping->doc_type = 'bank';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_bank"];
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
						$bookkeeping->debit = ($mt < 0 ? -$mt : 0);
						$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $langs->transnoentities($journal_label);
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->date_creation = $now;
						$bookkeeping->label_compte = '';
						$bookkeeping->label_operation = $reflabel;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);

						if ($result < 0) {
							if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists') {	// Already exists
								$error++;
								$errorforline++;
								setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->fk_doc.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
							} else {
								$error++;
								$errorforline++;
								setEventMessages($bookkeeping->error, $bookkeeping->errors, 'errors');
							}
						}
					}
				}
			}
		}

		if (price2num($totaldebit, 'MT') != price2num($totalcredit, 'MT')) {
			$error++;
			$errorforline++;
			setEventMessages('We tried to insert a non balanced transaction in book for '.$ref.'. Canceled. Surely a bug.', null, 'errors');
		}

		if (!$errorforline) {
			$db->commit();
		} else {
			//print 'KO for line '.$key.' '.$error.'<br>';
			$db->rollback();

			$MAXNBERRORS = 5;
			if ($error >= $MAXNBERRORS) {
				setEventMessages($langs->trans("ErrorTooManyErrorsProcessStopped").' (>'.$MAXNBERRORS.')', null, 'errors');
				break; // Break in the foreach
			}
		}
	}

	if (empty($error) && count($tabpay) > 0) {
		setEventMessages($langs->trans("GeneralLedgerIsWritten"), null, 'mesgs');
	} elseif (count($tabpay) == $error) {
		setEventMessages($langs->trans("NoNewRecordSaved"), null, 'warnings');
	} else {
		setEventMessages($langs->trans("GeneralLedgerSomeRecordWasNotRecorded"), null, 'warnings');
	}

	$action = '';

	// Must reload data, so we make a redirect
	if (count($tabpay) != $error) {
		$param = 'id_journal='.$id_journal;
		$param .= '&date_startday='.$date_startday;
		$param .= '&date_startmonth='.$date_startmonth;
		$param .= '&date_startyear='.$date_startyear;
		$param .= '&date_endday='.$date_endday;
		$param .= '&date_endmonth='.$date_endmonth;
		$param .= '&date_endyear='.$date_endyear;
		$param .= '&in_bookkeeping='.$in_bookkeeping;
		header("Location: ".$_SERVER['PHP_SELF'].($param ? '?'.$param : ''));
		exit;
	}
}



// Export
if ($action == 'exportcsv') {		// ISO and not UTF8 !
	$sep = getDolGlobalString('ACCOUNTING_EXPORT_SEPARATORCSV');

	$filename = 'journal';
	$type_export = 'journal';
	include DOL_DOCUMENT_ROOT.'/accountancy/tpl/export_journal.tpl.php';

	// CSV header line
	print '"'.$langs->transnoentitiesnoconv("BankId").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("Date").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("PaymentMode").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("AccountAccounting").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("LedgerAccount").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("SubledgerAccount").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("Label").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("AccountingDebit").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("AccountingCredit").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("Journal").'"'.$sep;
	print '"'.$langs->transnoentitiesnoconv("Note").'"'.$sep;
	print "\n";

	foreach ($tabpay as $key => $val) {
		$date = dol_print_date($val["date"], 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		// Bank
		foreach ($tabbq[$key] as $k => $mt) {
			if ($mt) {
				$reflabel = '';
				if (!empty($val['lib'])) {
					$reflabel .= dol_string_nohtmltag($val['lib'])." - ";
				}
				$reflabel .= $langs->trans("Bank").' '.dol_string_nohtmltag($val['bank_account_ref']);
				if (!empty($val['soclib'])) {
					$reflabel .= " - ".dol_string_nohtmltag($val['soclib']);
				}

				print '"'.$key.'"'.$sep;
				print '"'.$date.'"'.$sep;
				print '"'.$val["type_payment"].'"'.$sep;
				print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
				print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
				print "  ".$sep;
				print '"'.$reflabel.'"'.$sep;
				print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
				print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
				print '"'.$journal.'"'.$sep;
				print '"'.dol_string_nohtmltag($ref).'"'.$sep;
				print "\n";
			}
		}

		// Third party
		if (is_array($tabtp[$key])) {
			foreach ($tabtp[$key] as $k => $mt) {
				if ($mt) {
					$reflabel = '';
					if (!empty($val['lib'])) {
						$reflabel .= dol_string_nohtmltag($val['lib']).($val['soclib'] ? " - " : "");
					}
					if ($tabtype[$key] == 'banktransfert') {
						$reflabel .= dol_string_nohtmltag($langs->transnoentitiesnoconv('TransitionalAccount').' '.$account_transfer);
					} else {
						$reflabel .= dol_string_nohtmltag($val['soclib']);
					}

					print '"'.$key.'"'.$sep;
					print '"'.$date.'"'.$sep;
					print '"'.$val["type_payment"].'"'.$sep;
					print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
					if ($tabtype[$key] == 'payment_supplier') {
						print '"'.getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER').'"'.$sep;
					} elseif ($tabtype[$key] == 'payment') {
						print '"'.getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER').'"'.$sep;
					} elseif ($tabtype[$key] == 'payment_expensereport') {
						print '"'.getDolGlobalString('ACCOUNTING_ACCOUNT_EXPENSEREPORT').'"'.$sep;
					} elseif ($tabtype[$key] == 'payment_salary') {
						print '"'.getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT').'"'.$sep;
					} else {
						print '"'.length_accountg(html_entity_decode($k)).'"'.$sep;
					}
					print '"'.length_accounta(html_entity_decode($k)).'"'.$sep;
					print '"'.$reflabel.'"'.$sep;
					print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
					print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
					print '"'.$journal.'"'.$sep;
					print '"'.dol_string_nohtmltag($ref).'"'.$sep;
					print "\n";
				}
			}
		} else {	// If thirdparty unknown, output the waiting account
			foreach ($tabbq[$key] as $k => $mt) {
				if ($mt) {
					$reflabel = '';
					if (!empty($val['lib'])) {
						$reflabel .= dol_string_nohtmltag($val['lib'])." - ";
					}
					$reflabel .= dol_string_nohtmltag('WaitingAccount');

					print '"'.$key.'"'.$sep;
					print '"'.$date.'"'.$sep;
					print '"'.$val["type_payment"].'"'.$sep;
					print '"'.length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE')).'"'.$sep;
					print '"'.length_accounta(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE')).'"'.$sep;
					print $sep;
					print '"'.$reflabel.'"'.$sep;
					print '"'.($mt < 0 ? price(-$mt) : '').'"'.$sep;
					print '"'.($mt >= 0 ? price($mt) : '').'"'.$sep;
					print '"'.$journal.'"'.$sep;
					print '"'.dol_string_nohtmltag($ref).'"'.$sep;
					print "\n";
				}
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
	$loanstatic = new Loan($db);
	$salarystatic = new Salary($db);
	$variousstatic = new PaymentVarious($db);

	$title = $langs->trans("GenerationOfAccountingEntries").' - '.$accountingjournalstatic->getNomUrl(0, 2, 1, '', 1);
	$help_url ='EN:Module_Double_Entry_Accounting|FR:Module_Comptabilit&eacute;_en_Partie_Double#G&eacute;n&eacute;ration_des_&eacute;critures_en_comptabilit&eacute;';
	llxHeader('', dol_string_nohtmltag($title), $help_url, '', 0, 0, '', '', '', 'mod-accountancy accountancy-generation page-bankjournal');

	$nom = $title;
	$builddate = dol_now();
	//$description = $langs->trans("DescFinanceJournal") . '<br>';
	$description = $langs->trans("DescJournalOnlyBindedVisible").'<br>';

	$listofchoices = array(
		'notyet' => $langs->trans("NotYetInGeneralLedger"),
		'already' => $langs->trans("AlreadyInGeneralLedger")
	);
	$period = $form->selectDate($date_start ? $date_start : -1, 'date_start', 0, 0, 0, '', 1, 0).' - '.$form->selectDate($date_end ? $date_end : -1, 'date_end', 0, 0, 0, '', 1, 0);
	$period .= ' -  '.$langs->trans("JournalizationInLedgerStatus").' '.$form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal='.$id_journal;
	$periodlink = '';
	$exportlink = '';

	$listofchoices = array(
		1 => $langs->trans("TransfertAllBankLines"),
		2 => $langs->trans("TransfertOnlyConciliatedBankLine")
	);
	$moreoptions = [ "BankLineConciliated" => $form->selectarray('only_rappro', $listofchoices, $only_rappro)];

	journalHead($nom, '', $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink, $moreoptions);

	$desc = '';

	if (getDolGlobalString('ACCOUNTANCY_FISCAL_PERIOD_MODE') != 'blockedonclosed') {
		// Test that setup is complete (we are in accounting, so test on entity is always on $conf->entity only, no sharing allowed)
		// Fiscal period test
		$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."accounting_fiscalyear WHERE entity = ".((int) $conf->entity);
		$resql = $db->query($sql);
		if ($resql) {
			$obj = $db->fetch_object($resql);
			if ($obj->nb == 0) {
				print '<br><div class="warning">'.img_warning().' '.$langs->trans("TheFiscalPeriodIsNotDefined");
				$desc = ' : '.$langs->trans("AccountancyAreaDescFiscalPeriod", 4, '{link}');
				$desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("FiscalPeriod").'</strong>', $desc);
				print $desc;
				print '</div>';
			}
		} else {
			dol_print_error($db);
		}
	}

	// Bank test
	$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX."bank_account WHERE entity = ".((int) $conf->entity)." AND fk_accountancy_journal IS NULL AND clos=0";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj->nb > 0) {
			print '<br><div class="warning">'.img_warning().' '.$langs->trans("TheJournalCodeIsNotDefinedOnSomeBankAccount");
			$desc = ' : '.$langs->trans("AccountancyAreaDescBank", 6, '{link}');
			$desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("BankAccounts").'</strong>', $desc);
			print $desc;
			print '</div>';
		}
	} else {
		dol_print_error($db);
	}


	// Button to write into Ledger
	if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
		|| getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1'
		|| (isModEnabled("salaries") && (getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == "" || getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT') == '-1'))
		|| (isModEnabled("expensereport") && (getDolGlobalString('ACCOUNTING_ACCOUNT_EXPENSEREPORT') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_EXPENSEREPORT') == '-1'))) {


		print($desc ? '' : '<br>').'<div class="warning">'.img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		$desc = ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '{link}');
		$desc = str_replace('{link}', '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>', $desc);
		print $desc;
		print '</div>';
	}


	print '<br><div class="tabsAction tabsActionNoBottom centerimp">';

	if (getDolGlobalString('ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL') && $in_bookkeeping == 'notyet') {
		print '<input type="button" class="butAction" name="exportcsv" value="'.$langs->trans("ExportDraftJournal").'" onclick="launch_export();" />';
	}

	if (getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER') == '-1'
		|| getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == "" || getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER') == '-1') {
		print '<input type="button" class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")).'" value="'.$langs->trans("WriteBookKeeping").'" />';
	} else {
		if ($in_bookkeeping == 'notyet') {
			print '<input type="button" class="butAction" name="writebookkeeping" value="'.$langs->trans("WriteBookKeeping").'" onclick="writebookkeeping();" />';
		} else {
			print '<a class="butActionRefused classfortooltip" name="writebookkeeping">'.$langs->trans("WriteBookKeeping").'</a>';
		}
	}

	print '</div>';

	// TODO Avoid using js. We can use a direct link with $param
	print '
	<script type="text/javascript">
		function launch_export() {
			console.log("Set value into form and submit");
			$("div.fiche form input[name=\"action\"]").val("exportcsv");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
		function writebookkeeping() {
			console.log("Set value into form and submit");
			$("div.fiche form input[name=\"action\"]").val("writebookkeeping");
			$("div.fiche form input[type=\"submit\"]").click();
			$("div.fiche form input[name=\"action\"]").val("");
		}
	</script>';

	/*
	 * Show result array
	 */
	print '<br>';

	$i = 0;
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print "<td>".$langs->trans("Date")."</td>";
	print "<td>".$langs->trans("Piece").' ('.$langs->trans("ObjectsRef").")</td>";
	print "<td>".$langs->trans("AccountAccounting")."</td>";
	print "<td>".$langs->trans("SubledgerAccount")."</td>";
	print "<td>".$langs->trans("LabelOperation")."</td>";
	print '<td class="center">'.$langs->trans("PaymentMode")."</td>";
	print '<td class="right">'.$langs->trans("AccountingDebit")."</td>";
	print '<td class="right">'.$langs->trans("AccountingCredit")."</td>";
	print "</tr>\n";

	$r = '';

	foreach ($tabpay as $key => $val) {			  // $key is rowid in llx_bank
		$date = dol_print_date($val["date"], 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		// Bank
		foreach ($tabbq[$key] as $k => $mt) {
			if ($mt) {
				$reflabel = '';
				if (!empty($val['lib'])) {
					$reflabel .= $val['lib']." - ";
				}
				$reflabel .= $langs->trans("Bank").' '.$val['bank_account_ref'];
				if (!empty($val['soclib'])) {
					$reflabel .= " - ".$val['soclib'];
				}

				//var_dump($tabpay[$key]);
				print '<!-- Bank bank.rowid='.$key.' type='.$tabpay[$key]['type'].' ref='.$tabpay[$key]['ref'].'-->';
				print '<tr class="oddeven">';

				// Date
				print "<td>".$date."</td>";

				// Ref
				print "<td>".dol_escape_htmltag($ref)."</td>";

				// Ledger account
				$accounttoshow = length_accountg($k);
				if (empty($accounttoshow) || $accounttoshow == 'NotDefined') {
					$accounttoshow = '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
				}
				print '<td class="maxwidth300" title="'.dol_escape_htmltag(dol_string_nohtmltag($accounttoshow)).'">';
				print $accounttoshow;
				print "</td>";

				// Subledger account
				print '<td class="maxwidth300">';
				/*$accounttoshow = length_accountg($k);
				if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
				{
					print '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
				}
				else print $accounttoshow;*/
				print "</td>";

				// Label operation
				print '<td>';
				print $reflabel;	// This is already html escaped content
				print "</td>";

				print '<td class="center">'.$val["type_payment"]."</td>";
				print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
				print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
				print "</tr>";

				$i++;
			}
		}

		// Third party
		if (is_array($tabtp[$key])) {
			foreach ($tabtp[$key] as $k => $mt) {
				if ($mt) {
					$reflabel = '';
					if (!empty($val['lib'])) {
						$reflabel .= $val['lib'].(isset($val['soclib']) ? " - " : "");
					}
					if ($tabtype[$key] == 'banktransfert') {
						$reflabel .= $langs->trans('TransitionalAccount').' '.$account_transfer;
					} else {
						$reflabel .= isset($val['soclib']) ? $val['soclib'] : "";
					}

					print '<!-- Thirdparty bank.rowid='.$key.' -->';
					print '<tr class="oddeven">';

					// Date
					print "<td>".$date."</td>";

					// Ref
					print "<td>".dol_escape_htmltag($ref)."</td>";

					// Ledger account
					$account_ledger = $k;
					// Try to force general ledger account depending on type
					if ($tabtype[$key] == 'payment') {
						$account_ledger = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
					}
					if ($tabtype[$key] == 'payment_supplier') {
						$account_ledger = getDolGlobalString('ACCOUNTING_ACCOUNT_SUPPLIER');
					}
					if ($tabtype[$key] == 'payment_expensereport') {
						$account_ledger = getDolGlobalString('ACCOUNTING_ACCOUNT_EXPENSEREPORT');
					}
					if ($tabtype[$key] == 'payment_salary') {
						$account_ledger = getDolGlobalString('SALARIES_ACCOUNTING_ACCOUNT_PAYMENT');
					}
					if ($tabtype[$key] == 'payment_vat') {
						$account_ledger = getDolGlobalString('ACCOUNTING_VAT_PAY_ACCOUNT');
					}
					if ($tabtype[$key] == 'member') {
						$account_ledger = getDolGlobalString('ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT');
					}
					if ($tabtype[$key] == 'payment_various') {
						$account_ledger = $tabpay[$key]["account_various"];
					}
					$accounttoshow = length_accountg($account_ledger);
					if (empty($accounttoshow) || $accounttoshow == 'NotDefined') {
						if ($tabtype[$key] == 'unknown') {
							// We will accept writing, but into a waiting account
							if (!getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE') || getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE') == '-1') {
								$accounttoshow = '<span class="error small">'.$langs->trans('UnknownAccountForThirdpartyAndWaitingAccountNotDefinedBlocking').'</span>';
							} else {
								$accounttoshow = '<span class="warning small">'.$langs->trans('UnknownAccountForThirdparty', length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'))).'</span>'; // We will use a waiting account
							}
						} else {
							// We will refuse writing
							$errorstring = 'UnknownAccountForThirdpartyBlocking';
							if ($tabtype[$key] == 'payment') {
								$errorstring = 'MainAccountForCustomersNotDefined';
							}
							if ($tabtype[$key] == 'payment_supplier') {
								$errorstring = 'MainAccountForSuppliersNotDefined';
							}
							if ($tabtype[$key] == 'payment_expensereport') {
								$errorstring = 'MainAccountForUsersNotDefined';
							}
							if ($tabtype[$key] == 'payment_salary') {
								$errorstring = 'MainAccountForUsersNotDefined';
							}
							if ($tabtype[$key] == 'payment_vat') {
								$errorstring = 'MainAccountForVatPaymentNotDefined';
							}
							if ($tabtype[$key] == 'member') {
								$errorstring = 'MainAccountForSubscriptionPaymentNotDefined';
							}
							$accounttoshow = '<span class="error small">'.$langs->trans($errorstring).'</span>';
						}
					}
					print '<td class="maxwidth300" title="'.dol_escape_htmltag(dol_string_nohtmltag($accounttoshow)).'">';
					print $accounttoshow;	// This is a HTML string
					print "</td>";

					// Subledger account
					$accounttoshowsubledger = '';
					if (in_array($tabtype[$key], array('payment', 'payment_supplier', 'payment_expensereport', 'payment_salary', 'payment_various'))) {	// Type of payments that uses a subledger
						$accounttoshowsubledger = length_accounta($k);
						if ($accounttoshow != $accounttoshowsubledger) {
							if (empty($accounttoshowsubledger) || $accounttoshowsubledger == 'NotDefined') {
								//var_dump($tabpay[$key]);
								//var_dump($tabtype[$key]);
								//var_dump($tabbq[$key]);
								//print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
								if (!empty($tabcompany[$key]['code_compta'])) {
									if (in_array($tabtype[$key], array('payment_various', 'payment_salary'))) {
										// For such case, if subledger is not defined, we won't use subledger accounts.
										$accounttoshowsubledger = '<span class="warning small">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknownSubledgerIgnored").'</span>';
									} else {
										$accounttoshowsubledger = '<span class="warning small">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknown", $tabcompany[$key]['code_compta']).'</span>';
									}
								} else {
									$accounttoshowsubledger = '<span class="error small">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknownBlocking").'</span>';
								}
							}
						} else {
							$accounttoshowsubledger = '';
						}
					}
					print '<td class="maxwidth300">';
					print $accounttoshowsubledger;	// This is a html string
					print "</td>";

					print "<td>".$reflabel."</td>";

					print '<td class="center">'.$val["type_payment"]."</td>";

					print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";

					print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";

					print "</tr>";

					$i++;
				}
			}
		} else {	// Waiting account
			foreach ($tabbq[$key] as $k => $mt) {
				if ($mt) {
					$reflabel = '';
					if (!empty($val['lib'])) {
						$reflabel .= $val['lib']." - ";
					}
					$reflabel .= 'WaitingAccount';

					print '<!-- Wait bank.rowid='.$key.' -->';
					print '<tr class="oddeven">';
					print "<td>".$date."</td>";
					print "<td>".$ref."</td>";
					// Ledger account
					print "<td>";
					/*if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
					{
						print '<span class="error">'.$langs->trans("WaitAccountNotDefined").'</span>';
					}
					else */
					print length_accountg(getDolGlobalString('ACCOUNTING_ACCOUNT_SUSPENSE'));
					print "</td>";
					// Subledger account
					print "<td>";
					print "</td>";
					print "<td>".dol_escape_htmltag($reflabel)."</td>";
					print '<td class="center">'.$val["type_payment"]."</td>";
					print '<td class="right nowraponall amount">'.($mt < 0 ? price(-$mt) : '')."</td>";
					print '<td class="right nowraponall amount">'.($mt >= 0 ? price($mt) : '')."</td>";
					print "</tr>";

					$i++;
				}
			}
		}
	}

	if (!$i) {
		$colspan = 8;
		print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	print "</table>";
	print '</div>';

	llxFooter();
}

$db->close();



/**
 * Return source for doc_ref of a bank transaction
 *
 * @param 	array 	$val			Array of val
 * @param 	string	$typerecord		Type of record ('payment', 'payment_supplier', 'payment_expensereport', 'payment_vat', ...)
 * @return 	string					A string label to describe a record into llx_bank_url
 */
function getSourceDocRef($val, $typerecord)
{
	global $db, $langs;

	// Defined the docref into $ref (We start with $val['ref'] by default and we complete according to other data)
	// WE MUST HAVE SAME REF FOR ALL LINES WE WILL RECORD INTO THE BOOKKEEPING
	$ref = $val['ref'];
	if ($ref == '(SupplierInvoicePayment)' || $ref == '(SupplierInvoicePaymentBack)') {
		$ref = $langs->transnoentitiesnoconv('Supplier');
	}
	if ($ref == '(CustomerInvoicePayment)' || $ref == '(CustomerInvoicePaymentBack)') {
		$ref = $langs->transnoentitiesnoconv('Customer');
	}
	if ($ref == '(SocialContributionPayment)') {
		$ref = $langs->transnoentitiesnoconv('SocialContribution');
	}
	if ($ref == '(DonationPayment)') {
		$ref = $langs->transnoentitiesnoconv('Donation');
	}
	if ($ref == '(SubscriptionPayment)') {
		$ref = $langs->transnoentitiesnoconv('Subscription');
	}
	if ($ref == '(ExpenseReportPayment)') {
		$ref = $langs->transnoentitiesnoconv('Employee');
	}
	if ($ref == '(LoanPayment)') {
		$ref = $langs->transnoentitiesnoconv('Loan');
	}
	if ($ref == '(payment_salary)') {
		$ref = $langs->transnoentitiesnoconv('Employee');
	}

	$sqlmid = '';
	if ($typerecord == 'payment') {
		if (getDolGlobalInt('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$sqlmid = "SELECT payfac.fk_facture as id, ".$db->ifsql('f1.rowid IS NULL', 'f.ref', 'f1.ref')." as ref";
			$sqlmid .= " FROM ".$db->prefix()."paiement_facture as payfac";
			$sqlmid .= " LEFT JOIN ".$db->prefix()."facture as f ON f.rowid = payfac.fk_facture";
			$sqlmid .= " LEFT JOIN ".$db->prefix()."societe_remise_except as sre ON sre.fk_facture_source = payfac.fk_facture";
			$sqlmid .= " LEFT JOIN ".$db->prefix()."facture as f1 ON f1.rowid = sre.fk_facture";
			$sqlmid .= " WHERE payfac.fk_paiement=".((int) $val['paymentid']);
		} else {
			$sqlmid = "SELECT payfac.fk_facture as id, f.ref as ref";
			$sqlmid .= " FROM ".$db->prefix()."paiement_facture as payfac";
			$sqlmid .= " INNER JOIN ".$db->prefix()."facture as f ON f.rowid = payfac.fk_facture";
			$sqlmid .= " WHERE payfac.fk_paiement=".((int) $val['paymentid']);
		}
		$ref = $langs->transnoentitiesnoconv("Invoice");
	} elseif ($typerecord == 'payment_supplier') {
		$sqlmid = 'SELECT payfac.fk_facturefourn as id, f.ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."paiementfourn_facturefourn as payfac, ".MAIN_DB_PREFIX."facture_fourn as f";
		$sqlmid .= " WHERE payfac.fk_facturefourn = f.rowid AND payfac.fk_paiementfourn=".((int) $val["paymentsupplierid"]);
		$ref = $langs->transnoentitiesnoconv("SupplierInvoice");
	} elseif ($typerecord == 'payment_expensereport') {
		$sqlmid = 'SELECT e.rowid as id, e.ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_expensereport as pe, ".MAIN_DB_PREFIX."expensereport as e";
		$sqlmid .= " WHERE pe.rowid=".((int) $val["paymentexpensereport"])." AND pe.fk_expensereport = e.rowid";
		$ref = $langs->transnoentitiesnoconv("ExpenseReport");
	} elseif ($typerecord == 'payment_salary') {
		$sqlmid = 'SELECT s.rowid as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
		$sqlmid .= " WHERE s.rowid=".((int) $val["paymentsalid"]);
		$ref = $langs->transnoentitiesnoconv("SalaryPayment");
	} elseif ($typerecord == 'sc') {
		$sqlmid = 'SELECT sc.rowid as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."paiementcharge as sc";
		$sqlmid .= " WHERE sc.rowid=".((int) $val["paymentscid"]);
		$ref = $langs->transnoentitiesnoconv("SocialContribution");
	} elseif ($typerecord == 'payment_vat') {
		$sqlmid = 'SELECT v.rowid as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."tva as v";
		$sqlmid .= " WHERE v.rowid=".((int) $val["paymentvatid"]);
		$ref = $langs->transnoentitiesnoconv("PaymentVat");
	} elseif ($typerecord == 'payment_donation') {
		$sqlmid = 'SELECT payd.fk_donation as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_donation as payd";
		$sqlmid .= " WHERE payd.fk_donation=".((int) $val["paymentdonationid"]);
		$ref = $langs->transnoentitiesnoconv("Donation");
	} elseif ($typerecord == 'payment_loan') {
		$sqlmid = 'SELECT l.rowid as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_loan as l";
		$sqlmid .= " WHERE l.rowid=".((int) $val["paymentloanid"]);
		$ref = $langs->transnoentitiesnoconv("LoanPayment");
	} elseif ($typerecord == 'payment_various') {
		$sqlmid = 'SELECT v.rowid as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."payment_various as v";
		$sqlmid .= " WHERE v.rowid=".((int) $val["paymentvariousid"]);
		$ref = $langs->transnoentitiesnoconv("VariousPayment");
	}
	// Add warning
	if (empty($sqlmid)) {
		dol_syslog("Found a typerecord=".$typerecord." not supported", LOG_WARNING);
	}

	if ($sqlmid) {
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=".$sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
		if ($resultmid) {
			while ($objmid = $db->fetch_object($resultmid)) {
				$ref .= ' '.$objmid->ref;
			}
		} else {
			dol_print_error($db);
		}
	}

	$ref = dol_trunc($langs->transnoentitiesnoconv("BankId").' '.$val['fk_bank'].' - '.$ref, 295); // 295 + 3 dots (...) is < than max size of 300
	return $ref;
}
