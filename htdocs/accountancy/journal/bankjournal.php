<?php
<<<<<<< HEAD
/* Copyright (C) 2007-2010  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger     <jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2018  Alexandre Spangaro  <aspangaro@zendsi.com>
 * Copyright (C) 2013-2014  Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Olivier Geffroy     <jeff@jeffinfo.com>
 * Copyright (C) 2017       Frédéric France     <frederic.france@netlogic.fr>
=======
/* Copyright (C) 2007-2010  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010  Jean Heimburger         <jean@tiaris.info>
 * Copyright (C) 2011       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2013-2018  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2013-2014  Florian Henry           <florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014  Olivier Geffroy         <jeff@jeffinfo.com>
 * Copyright (C) 2017-2018  Frédéric France         <frederic.france@netlogic.fr>
 * Copyright (C) 2018		Ferran Marcet	        <fmarcet@2byte.es>
 * Copyright (C) 2018		Eric Seigne	            <eric.seigne@cap-rel.fr>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  \file       htdocs/accountancy/journal/bankjournal.php
<<<<<<< HEAD
 *  \ingroup    Advanced accountancy
=======
 *  \ingroup    Accountancy (Double entries)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *  \brief      Page with bank journal
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
<<<<<<< HEAD
=======
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
require_once DOL_DOCUMENT_ROOT . '/loan/class/loan.class.php';
require_once DOL_DOCUMENT_ROOT . '/loan/class/paymentloan.class.php';
<<<<<<< HEAD

// Load translation files required by the page
$langs->loadLangs(array("companies","other","compta","banks","bills","donations","loan","accountancy","trips","salaries","hrm"));
=======
require_once DOL_DOCUMENT_ROOT . '/adherents/class/subscription.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies","other","compta","banks","bills","donations","loan","accountancy","trips","salaries","hrm","members"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Multi journal
$id_journal = GETPOST('id_journal', 'int');

<<<<<<< HEAD
$date_startmonth = GETPOST('date_startmonth','int');
$date_startday = GETPOST('date_startday','int');
$date_startyear = GETPOST('date_startyear','int');
$date_endmonth = GETPOST('date_endmonth','int');
$date_endday = GETPOST('date_endday','int');
$date_endyear = GETPOST('date_endyear','int');
$in_bookkeeping = GETPOST('in_bookkeeping','aZ09');
=======
$date_startmonth = GETPOST('date_startmonth', 'int');
$date_startday = GETPOST('date_startday', 'int');
$date_startyear = GETPOST('date_startyear', 'int');
$date_endmonth = GETPOST('date_endmonth', 'int');
$date_endday = GETPOST('date_endday', 'int');
$date_endyear = GETPOST('date_endyear', 'int');
$in_bookkeeping = GETPOST('in_bookkeeping', 'aZ09');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if ($in_bookkeeping == '') $in_bookkeeping = 'notyet';

$now = dol_now();

<<<<<<< HEAD
$action = GETPOST('action','aZ09');
=======
$action = GETPOST('action', 'aZ09');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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

if (! GETPOSTISSET('date_startmonth') && (empty($date_start) || empty($date_end))) // We define date_start and date_end, only if we did not submit the form
{
	$date_start = dol_get_first_day($pastmonthyear, $pastmonth, false);
	$date_end = dol_get_last_day($pastmonthyear, $pastmonth, false);
}

<<<<<<< HEAD
$idpays = $mysoc->country_id;

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$sql  = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type, b.fk_account,";
$sql .= " ba.courant, ba.ref as baref, ba.account_number, ba.fk_accountancy_journal,";
$sql .= " soc.code_compta, soc.code_compta_fournisseur, soc.rowid as socid, soc.nom as name, soc.email as email, bu1.type as typeop_company,";
$sql .= " u.accountancy_code, u.rowid as userid, u.lastname as lastname, u.firstname as firstname, u.email as useremail, bu2.type as typeop_user,";
$sql .= " bu3.type as typeop_payment, bu4.type as typeop_payment_supplier";
$sql .= " FROM " . MAIN_DB_PREFIX . "bank as b";
$sql .= " JOIN " . MAIN_DB_PREFIX . "bank_account as ba on b.fk_account=ba.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu2 ON bu2.fk_bank = b.rowid AND bu2.type='user'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu3 ON bu3.fk_bank = b.rowid AND bu3.type='payment'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu4 ON bu4.fk_bank = b.rowid AND bu4.type='payment_supplier'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc on bu1.url_id=soc.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u on bu2.url_id=u.rowid";
$sql .= " WHERE ba.fk_accountancy_journal=" . $id_journal;
$sql .= ' AND b.amount != 0 AND ba.entity IN ('.getEntity('bank_account', 0).')';		// We don't share object for accountancy
if ($date_start && $date_end)
	$sql .= " AND b.dateo >= '" . $db->idate($date_start) . "' AND b.dateo <= '" . $db->idate($date_end) . "'";
// Already in bookkeeping or not
if ($in_bookkeeping == 'already')
{
	$sql .= " AND (b.rowid IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
}
if ($in_bookkeeping == 'notyet')
{
	$sql .= " AND (b.rowid NOT IN (SELECT fk_doc FROM " . MAIN_DB_PREFIX . "accounting_bookkeeping as ab  WHERE ab.doc_type='bank') )";
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
$paymentvatstatic = new TVA($db);
$paymentsalstatic = new PaymentSalary($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);
$paymentvariousstatic = new PaymentVarious($db);
$paymentloanstatic = new PaymentLoan($db);
<<<<<<< HEAD
=======
$accountLinestatic=new AccountLine($db);
$paymentsubscriptionstatic = new Subscription($db);

$tmppayment = new Paiement($db);
$tmpinvoice = new Facture($db);

$accountingaccount = new AccountingAccount($db);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Get code of finance journal
$accountingjournalstatic = new AccountingJournal($db);
$accountingjournalstatic->fetch($id_journal);
$journal = $accountingjournalstatic->code;
$journal_label = $accountingjournalstatic->label;

<<<<<<< HEAD
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
dol_syslog("accountancy/journal/bankjournal.php", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {

	$num = $db->num_rows($result);
	//print $sql;

	// Variables
<<<<<<< HEAD
	$account_supplier = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : 'NotDefined');	// NotDefined is a reserved word
	$account_customer = (! empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : 'NotDefined');	// NotDefined is a reserved word
	$account_employee = (! empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) ? $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT : 'NotDefined');	// NotDefined is a reserved word
	$account_pay_vat = (! empty($conf->global->ACCOUNTING_VAT_PAY_ACCOUNT) ? $conf->global->ACCOUNTING_VAT_PAY_ACCOUNT : 'NotDefined');	// NotDefined is a reserved word
	$account_pay_donation = (! empty($conf->global->DONATION_ACCOUNTINGACCOUNT) ? $conf->global->DONATION_ACCOUNTINGACCOUNT : 'NotDefined');	// NotDefined is a reserved word
	$account_transfer = (! empty($conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH) ? $conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH : 'NotDefined');	// NotDefined is a reserved word
=======
	$account_supplier			= (($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER != "") ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : 'NotDefined');	// NotDefined is a reserved word
	$account_customer			= (($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER != "") ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : 'NotDefined');	// NotDefined is a reserved word
	$account_employee			= (! empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) ? $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT : 'NotDefined');	// NotDefined is a reserved word
	$account_pay_vat			= (! empty($conf->global->ACCOUNTING_VAT_PAY_ACCOUNT) ? $conf->global->ACCOUNTING_VAT_PAY_ACCOUNT : 'NotDefined');	// NotDefined is a reserved word
	$account_pay_donation		= (! empty($conf->global->DONATION_ACCOUNTINGACCOUNT) ? $conf->global->DONATION_ACCOUNTINGACCOUNT : 'NotDefined');	// NotDefined is a reserved word
	$account_pay_subscription	= (! empty($conf->global->ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT) ? $conf->global->ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT : 'NotDefined');	// NotDefined is a reserved word
	$account_transfer			= (! empty($conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH) ? $conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH : 'NotDefined');	// NotDefined is a reserved word
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	$tabcompany = array();
	$tabuser = array();
	$tabpay = array ();
	$tabbq = array ();
	$tabtp = array ();
	$tabtype = array ();
<<<<<<< HEAD
=======
	$tabmoreinfo = array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	// Loop on each line into llx_bank table. For each line, we should get:
	// one line tabpay = line into bank
	// one line for bank record = tabbq
	// one line for thirdparty record = tabtp
	$i = 0;
<<<<<<< HEAD
	while ( $i < $num )
=======
	while ($i < $num)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$obj = $db->fetch_object($result);

		$lineisapurchase = -1;
		$lineisasale = -1;
		// Old method to detect if it's a sale or purchase
		if ($obj->label == '(SupplierInvoicePayment)' || $obj->label == '(SupplierInvoicePaymentBack)') $lineisapurchase=1;
		if ($obj->label == '(CustomerInvoicePayment)' || $obj->label == '(CustomerInvoicePaymentBack)') $lineisasale=1;
		// Try a more reliable method to detect if record is a supplier payment or a customer payment
		if ($lineisapurchase < 0)
		{
			if ($obj->typeop_payment_supplier == 'payment_supplier') $lineisapurchase = 1;
		}
		if ($lineisasale < 0)
		{
			if ($obj->typeop_payment == 'payment') $lineisasale = 1;
		}
		//var_dump($obj->type_payment); var_dump($obj->type_payment_supplier);
		//var_dump($lineisapurchase); //var_dump($lineisasale);

		// Set accountancy code for bank
		$compta_bank = $obj->account_number;

<<<<<<< HEAD
		// Set accountancy code for thirdparty
		$compta_soc = 'NotDefined';
		if ($lineisapurchase > 0)
			$compta_soc = (! empty($obj->code_compta_fournisseur) ? $obj->code_compta_fournisseur : $account_supplier);
=======
		// Set accountancy code for thirdparty (example: '411CU...' or '411' if no subledger account defined on customer)
		$compta_soc = 'NotDefined';
		if ($lineisapurchase > 0)
			$compta_soc = (($obj->code_compta_fournisseur != "") ? $obj->code_compta_fournisseur : $account_supplier);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if ($lineisasale > 0)
			$compta_soc = (! empty($obj->code_compta) ? $obj->code_compta : $account_customer);

		$tabcompany[$obj->rowid] = array (
				'id' => $obj->socid,
				'name' => $obj->name,
				'code_compta' => $compta_soc,
				'email' => $obj->email
		);

		// Set accountancy code for user
		$compta_user = (! empty($obj->accountancy_code) ? $obj->accountancy_code : $account_employee);

		$tabuser[$obj->rowid] = array (
				'id' => $obj->userid,
				'name' => dolGetFirstLastname($obj->firstname, $obj->lastname),
				'lastname' => $obj->lastname,
				'firstname' => $obj->firstname,
				'email' => $obj->useremail,
				'accountancy_code' => $compta_user
		);

		// Variable bookkeeping ($obj->rowid is Bank Id)
		$tabpay[$obj->rowid]["date"] = $obj->do;
		$tabpay[$obj->rowid]["type_payment"] = $obj->fk_type;		// CHQ, VIR, LIQ, CB, ...
		$tabpay[$obj->rowid]["ref"] = $obj->label;					// By default. Not unique. May be changed later
		$tabpay[$obj->rowid]["fk_bank"] = $obj->rowid;
		$tabpay[$obj->rowid]["bank_account_ref"] = $obj->baref;
		$tabpay[$obj->rowid]["fk_bank_account"] = $obj->fk_account;
		if (preg_match('/^\((.*)\)$/i', $obj->label, $reg)) {
			$tabpay[$obj->rowid]["lib"] = $langs->trans($reg[1]);
		} else {
			$tabpay[$obj->rowid]["lib"] = dol_trunc($obj->label, 60);
		}
		$links = $object->get_url($obj->rowid);		// Get an array('url'=>, 'url_id'=>, 'label'=>, 'type'=> 'fk_bank'=> )

		//var_dump($i);
		//var_dump($tabpay);
<<<<<<< HEAD
=======
		//var_dump($tabcompany);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		// By default
		$tabpay[$obj->rowid]['type'] = 'unknown';	// Can be SOLD, miscellaneous entry, payment of patient, or any old record with no links in bank_url.
		$tabtype[$obj->rowid] = 'unknown';
<<<<<<< HEAD
=======
		$tabmoreinfo[$obj->rowid] = array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

		// get_url may return -1 which is not traversable
		if (is_array($links) && count($links) > 0) {

			// Now loop on each link of record in bank.
			foreach ($links as $key => $val) {

<<<<<<< HEAD
				if (in_array($links[$key]['type'], array('sc', 'payment_sc', 'payment', 'payment_supplier', 'payment_vat', 'payment_expensereport', 'banktransfert', 'payment_donation', 'payment_loan', 'payment_salary', 'payment_various')))
=======
				if (in_array($links[$key]['type'], array('sc', 'payment_sc', 'payment', 'payment_supplier', 'payment_vat', 'payment_expensereport', 'banktransfert', 'payment_donation', 'member', 'payment_loan', 'payment_salary', 'payment_various')))
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

<<<<<<< HEAD
=======
				if ($links[$key]['type'] == 'withdraw') {
					$tabmoreinfo[$obj->rowid]['withdraw']=1;
				}

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				if ($links[$key]['type'] == 'payment') {
					$paymentstatic->id = $links[$key]['url_id'];
					$paymentstatic->ref = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentstatic->getNomUrl(2, '', '');		// TODO Do not include list of invoice in tooltip, the dol_string_nohtmltag is ko with this
					$tabpay[$obj->rowid]["paymentid"] = $paymentstatic->id;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'payment_supplier') {
=======
				} elseif ($links[$key]['type'] == 'payment_supplier') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$paymentsupplierstatic->id = $links[$key]['url_id'];
					$paymentsupplierstatic->ref = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentsupplierstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsupplierid"] = $paymentsupplierstatic->id;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'company') {
=======
				} elseif ($links[$key]['type'] == 'company') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$societestatic->id = $links[$key]['url_id'];
					$societestatic->name = $links[$key]['label'];
					$societestatic->email = $tabcompany[$obj->rowid]['email'];
					$tabpay[$obj->rowid]["soclib"] = $societestatic->getNomUrl(1, '', 30);
					if ($compta_soc) $tabtp[$obj->rowid][$compta_soc] += $obj->amount;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'user') {
=======
				} elseif ($links[$key]['type'] == 'user') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$userstatic->id = $links[$key]['url_id'];
					$userstatic->name = $links[$key]['label'];
					$userstatic->email = $tabuser[$obj->rowid]['email'];
					$userstatic->firstname = $tabuser[$obj->rowid]['firstname'];
					$userstatic->lastname = $tabuser[$obj->rowid]['lastname'];
					if ($userstatic->id > 0) $tabpay[$obj->rowid]["soclib"] = $userstatic->getNomUrl(1, '', 30);
					else $tabpay[$obj->rowid]["soclib"] = '???';	// Should not happen, but happens with old data when id of user was not saved on expense report payment.
					if ($compta_user) $tabtp[$obj->rowid][$compta_user] += $obj->amount;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'sc') {
=======
				} elseif ($links[$key]['type'] == 'sc') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
					$tabpay[$obj->rowid]["paymentscid"] = $chargestatic->id;

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
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'payment_donation') {
=======
				} elseif ($links[$key]['type'] == 'payment_donation') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$paymentdonstatic->id = $links[$key]['url_id'];
					$paymentdonstatic->ref = $links[$key]['url_id'];
					$paymentdonstatic->fk_donation = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentdonstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentdonationid"] = $paymentdonstatic->id;
					$tabtp[$obj->rowid][$account_pay_donation] += $obj->amount;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'payment_vat') {				// Payment VAT
=======
				} elseif ($links[$key]['type'] == 'member') {
					$paymentsubscriptionstatic->id = $links[$key]['url_id'];
					$paymentsubscriptionstatic->ref = $links[$key]['url_id'];
					$paymentsubscriptionstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentsubscriptionstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsubscriptionid"] = $paymentsubscriptionstatic->id;
					$paymentsubscriptionstatic->fetch($paymentsubscriptionstatic->id);
					$tabtp[$obj->rowid][$account_pay_subscription] += $obj->amount;
				} elseif ($links[$key]['type'] == 'payment_vat') {				// Payment VAT
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$paymentvatstatic->id = $links[$key]['url_id'];
					$paymentvatstatic->ref = $links[$key]['url_id'];
					$paymentvatstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentvatstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentvatid"] = $paymentvatstatic->id;
					$tabtp[$obj->rowid][$account_pay_vat] += $obj->amount;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'payment_salary') {
=======
				} elseif ($links[$key]['type'] == 'payment_salary') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$paymentsalstatic->id = $links[$key]['url_id'];
					$paymentsalstatic->ref = $links[$key]['url_id'];
					$paymentsalstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentsalstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentsalid"] = $paymentsalstatic->id;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'payment_expensereport') {
					$paymentexpensereportstatic->id = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= $paymentexpensereportstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentexpensereport"] = $paymentexpensereportstatic->id;
				} else if ($links[$key]['type'] == 'payment_various') {
=======
				} elseif ($links[$key]['type'] == 'payment_expensereport') {
					$paymentexpensereportstatic->id = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= $paymentexpensereportstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentexpensereport"] = $paymentexpensereportstatic->id;
				} elseif ($links[$key]['type'] == 'payment_various') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$paymentvariousstatic->id = $links[$key]['url_id'];
					$paymentvariousstatic->ref = $links[$key]['url_id'];
					$paymentvariousstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentvariousstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentvariousid"] = $paymentvariousstatic->id;
					$paymentvariousstatic->fetch($paymentvariousstatic->id);
					$account_various = (! empty($paymentvariousstatic->accountancy_code) ? $paymentvariousstatic->accountancy_code : 'NotDefined');	// NotDefined is a reserved word
					$tabtp[$obj->rowid][$account_various] += $obj->amount;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'payment_loan') {
=======
				} elseif ($links[$key]['type'] == 'payment_loan') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$paymentloanstatic->id = $links[$key]['url_id'];
					$paymentloanstatic->ref = $links[$key]['url_id'];
					$paymentloanstatic->fk_loan = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentloanstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["paymentloanid"] = $paymentloanstatic->id;
					//$tabtp[$obj->rowid][$account_pay_loan] += $obj->amount;
<<<<<<< HEAD
				} else if ($links[$key]['type'] == 'banktransfert') {
					$tabpay[$obj->rowid]["lib"] .= ' ' . $langs->trans("BankTransfer");
=======
					$sqlmid = 'SELECT pl.amount_capital, pl.amount_insurance, pl.amount_interest, l.accountancy_account_capital, l.accountancy_account_insurance, l.accountancy_account_interest';
					$sqlmid.= ' FROM '.MAIN_DB_PREFIX.'payment_loan as pl, '.MAIN_DB_PREFIX.'loan as l';
					$sqlmid.= ' WHERE l.rowid = pl.fk_loan AND pl.fk_bank = '.$obj->rowid;

					dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
					$resultmid = $db->query($sqlmid);
					if ($resultmid) {
						$objmid = $db->fetch_object($resultmid);
						$tabtp[$obj->rowid][$objmid->accountancy_account_capital] -= $objmid->amount_capital;
						$tabtp[$obj->rowid][$objmid->accountancy_account_insurance] -= $objmid->amount_insurance;
						$tabtp[$obj->rowid][$objmid->accountancy_account_interest] -= $objmid->amount_interest;
					}
				} elseif ($links[$key]['type'] == 'banktransfert') {
					$accountLinestatic->fetch($links[$key]['url_id']);
					$tabpay[$obj->rowid]["lib"] .= ' '.$langs->trans("BankTransfer").'- ' .$accountLinestatic ->getNomUrl(1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$tabtp[$obj->rowid][$account_transfer] += $obj->amount;
					$bankaccountstatic->fetch($tabpay[$obj->rowid]['fk_bank_account']);
					$tabpay[$obj->rowid]["soclib"] = $bankaccountstatic->getNomUrl(2);
				}
			}
		}

		$tabbq[$obj->rowid][$compta_bank] += $obj->amount;

<<<<<<< HEAD
		// If not links were found to know amount on thirdparty, we init it.
		if (empty($tabtp[$obj->rowid])) $tabtp[$obj->rowid]['NotDefined']= $tabbq[$obj->rowid][$compta_bank];
=======
		// If no links were found to know the amount on thirdparty, we try to guess it.
		// This may happens on bank entries without the links lines to 'company'.
		if (empty($tabtp[$obj->rowid]) && ! empty($tabmoreinfo[$obj->rowid]['withdraw']))	// If we dont find 'company' link because it is an old 'withdraw' record
		{
			foreach ($links as $key => $val) {
				if ($links[$key]['type'] == 'payment') {
					// Get thirdparty
					$tmppayment->fetch($links[$key]['url_id']);
					$arrayofamounts = $tmppayment->getAmountsArray();
					foreach($arrayofamounts as $invoiceid => $amount)
					{
						$tmpinvoice->fetch($invoiceid);
						$tmpinvoice->fetch_thirdparty();
						if ($tmpinvoice->thirdparty->code_compta)
						{
							$tabtp[$obj->rowid][$tmpinvoice->thirdparty->code_compta] += $amount;
						}
					}
				}
			}
		}

		// If no links were found to know the amount on thirdparty, we init it to account 'NotDefined'.
		if (empty($tabtp[$obj->rowid])) $tabtp[$obj->rowid]['NotDefined'] = $tabbq[$obj->rowid][$compta_bank];
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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

<<<<<<< HEAD
/*
var_dump($tabpay);
var_dump($tabbq);
var_dump($tabtp);
*/
=======

/*var_dump($tabpay);
var_dump($tabcompany);
var_dump($tabbq);
var_dump($tabtp);
var_dump($tabtype);*/
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Write bookkeeping
if (! $error && $action == 'writebookkeeping') {
	$now = dol_now();

	$error = 0;
<<<<<<< HEAD
	foreach ( $tabpay as $key => $val )		// $key is rowid into llx_bank
=======
	foreach ($tabpay as $key => $val)		// $key is rowid into llx_bank
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$date = dol_print_date($db->jdate($val["date"]), 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		$errorforline = 0;

		$totalcredit = 0;
		$totaldebit = 0;

		$db->begin();

		// Introduce a protection. Total of tabtp must be total of tabbq
		/*var_dump($tabpay);
		var_dump($tabtp);
		var_dump($tabbq);exit;*/

		// Bank
		if (! $errorforline && is_array($tabbq[$key]))
		{
			// Line into bank account
<<<<<<< HEAD
			foreach ( $tabbq[$key] as $k => $mt )
=======
			foreach ($tabbq[$key] as $k => $mt)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			{
				if ($mt)
				{
					$reflabel = '';
					if (! empty($val['lib'])) $reflabel .= dol_string_nohtmltag($val['lib']) . " - ";
					$reflabel.= $langs->trans("Bank").' '.dol_string_nohtmltag($val['bank_account_ref']);
					if (! empty($val['soclib'])) $reflabel .= " - " . dol_string_nohtmltag($val['soclib']);

					$bookkeeping = new BookKeeping($db);
					$bookkeeping->doc_date = $val["date"];
					$bookkeeping->doc_ref = $ref;
					$bookkeeping->doc_type = 'bank';
					$bookkeeping->fk_doc = $key;
					$bookkeeping->fk_docdet = $val["fk_bank"];
					$bookkeeping->numero_compte = $k;
<<<<<<< HEAD
					$bookkeeping->label_compte = $langs->trans("Bank");
=======

					$accountingaccount->fetch(null, $k, true);
					$bookkeeping->label_compte = $accountingaccount->label;

					$bookkeeping->label_operation = $reflabel;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$bookkeeping->montant = $mt;
					$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
					$bookkeeping->debit = ($mt >= 0 ? $mt : 0);
					$bookkeeping->credit = ($mt < 0 ? - $mt : 0);
					$bookkeeping->code_journal = $journal;
					$bookkeeping->journal_label = $journal_label;
					$bookkeeping->fk_user_author = $user->id;
					$bookkeeping->date_create = $now;

					// No subledger_account value for the bank line but add a specific label_operation
					$bookkeeping->subledger_account = '';
					$bookkeeping->label_operation = $reflabel;
					$bookkeeping->entity = $conf->entity;

					$totaldebit += $bookkeeping->debit;
					$totalcredit += $bookkeeping->credit;

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
		if (! $errorforline)
		{
			if (is_array($tabtp[$key]))
			{
				// Line into thirdparty account
<<<<<<< HEAD
				foreach ( $tabtp[$key] as $k => $mt ) {
=======
				foreach ($tabtp[$key] as $k => $mt) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					if ($mt)
					{
						$reflabel = '';
						if (! empty($val['lib'])) $reflabel .= dol_string_nohtmltag($val['lib']) . ($val['soclib']?" - ":"");
<<<<<<< HEAD
						$reflabel.= dol_string_nohtmltag($val['soclib']);
=======
						if ($tabtype[$key] == 'banktransfert')
						{
							$reflabel.= dol_string_nohtmltag($langs->transnoentitiesnoconv('TransitionalAccount').' '.$account_transfer);
						}
						else
						{
							$reflabel.= dol_string_nohtmltag($val['soclib']);
						}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $ref;
						$bookkeeping->doc_type = 'bank';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_bank"];
<<<<<<< HEAD
=======
						$bookkeeping->label_operation = $reflabel;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
						$bookkeeping->debit = ($mt < 0 ? - $mt : 0);
						$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $journal_label;
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->date_create = $now;

						if ($tabtype[$key] == 'payment') {	// If payment is payment of customer invoice, we get ref of invoice
<<<<<<< HEAD
							$bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
							$bookkeeping->subledger_label = $tabcompany[$key]['name'];
							$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
							$bookkeeping->label_compte = '';
						} else if ($tabtype[$key] == 'payment_supplier') {		   // If payment is payment of supplier invoice, we get ref of invoice
							$bookkeeping->subledger_account = $tabcompany[$key]['code_compta'];
							$bookkeeping->subledger_label = $tabcompany[$key]['name'];
							$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
							$bookkeeping->label_compte = '';
						} else if ($tabtype[$key] == 'payment_expensereport') {
							$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
							$bookkeeping->subledger_label = $tabuser[$key]['name'];
							$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
							$bookkeeping->label_compte = '';
						} else if ($tabtype[$key] == 'payment_salary') {
							$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
							$bookkeeping->subledger_label = $tabuser[$key]['name'];
							$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
							$bookkeeping->label_compte = '';
						} else if (in_array($tabtype[$key], array('sc', 'payment_sc'))) {   // If payment is payment of social contribution
=======
							$bookkeeping->subledger_account = $k;							// For payment, the subledger account is stored as $key of $tabtp
							$bookkeeping->subledger_label = $tabcompany[$key]['name'];		// $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
							$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;

							$accountingaccount->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_supplier') {	// If payment is payment of supplier invoice, we get ref of invoice
							$bookkeeping->subledger_account = $k;				   			// For payment, the subledger account is stored as $key of $tabtp
							$bookkeeping->subledger_label = $tabcompany[$key]['name'];		// $tabcompany is defined only if we are sure there is 1 thirdparty for the bank transaction
							$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;

							$accountingaccount->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_expensereport') {
							$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
							$bookkeeping->subledger_label = $tabuser[$key]['name'];
							$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;

							$accountingaccount->fetch(null, $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_salary') {
							$bookkeeping->subledger_account = $tabuser[$key]['accountancy_code'];
							$bookkeeping->subledger_label = $tabuser[$key]['name'];
							$bookkeeping->numero_compte = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;

							$accountingaccount->fetch(null, $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif (in_array($tabtype[$key], array('sc', 'payment_sc'))) {   // If payment is payment of social contribution
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = $objmid->labelc;
<<<<<<< HEAD
						} else if ($tabtype[$key] == 'payment_vat') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = '';
						} else if ($tabtype[$key] == 'payment_donation') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = '';
						} else if ($tabtype[$key] == 'payment_loan') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = '';
						} else if ($tabtype[$key] == 'payment_various') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = '';
						} else if ($tabtype[$key] == 'banktransfert') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;
							$bookkeeping->label_compte = '';
=======
						} elseif ($tabtype[$key] == 'payment_vat') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;

							$accountingaccount->fetch(null, $k, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_donation') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;

							$accountingaccount->fetch(null, $k, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'member') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;

							$accountingaccount->fetch(null, $k, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_loan') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;

							$accountingaccount->fetch(null, $k, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'payment_various') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;

							$accountingaccount->fetch(null, $k, true);
							$bookkeeping->label_compte = $accountingaccount->label;
						} elseif ($tabtype[$key] == 'banktransfert') {
							$bookkeeping->subledger_account = '';
							$bookkeeping->subledger_label = '';
							$bookkeeping->numero_compte = $k;

							$accountingaccount->fetch(null, $k, true);
							$bookkeeping->label_compte = $accountingaccount->label;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						} else {
							if ($tabtype[$key] == 'unknown')	// Unknown transaction, we will use a waiting account for thirdparty.
							{
								// Temporary account
								$bookkeeping->subledger_account = '';
								$bookkeeping->subledger_label = '';
								$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE;
<<<<<<< HEAD
								$bookkeeping->label_compte = '';
=======

								$accountingaccount->fetch(null, $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE, true);
								$bookkeeping->label_compte = $accountingaccount->label;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
							}
						}
						$bookkeeping->label_operation = $reflabel;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

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
<<<<<<< HEAD
			else {	// If thirdparty unkown, output the waiting account
				foreach ( $tabbq[$key] as $k => $mt ) {
=======
			else {	// If thirdparty unknown, output the waiting account
				foreach ($tabbq[$key] as $k => $mt) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					if ($mt)
					{
						$reflabel = '';
						if (! empty($val['lib'])) $reflabel .= dol_string_nohtmltag($val['lib']) . " - ";
						$reflabel.= dol_string_nohtmltag('WaitingAccount');

						$bookkeeping = new BookKeeping($db);
						$bookkeeping->doc_date = $val["date"];
						$bookkeeping->doc_ref = $ref;
						$bookkeeping->doc_type = 'bank';
						$bookkeeping->fk_doc = $key;
						$bookkeeping->fk_docdet = $val["fk_bank"];
						$bookkeeping->montant = $mt;
						$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
						$bookkeeping->debit = ($mt < 0 ? - $mt : 0);
						$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
						$bookkeeping->code_journal = $journal;
						$bookkeeping->journal_label = $journal_label;
						$bookkeeping->fk_user_author = $user->id;
						$bookkeeping->date_create = $now;
						$bookkeeping->label_compte = '';
						$bookkeeping->label_operation = $reflabel;
						$bookkeeping->entity = $conf->entity;

						$totaldebit += $bookkeeping->debit;
						$totalcredit += $bookkeeping->credit;

						$result = $bookkeeping->create($user);
<<<<<<< HEAD
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
		}

		if (price2num($totaldebit) != price2num($totalcredit))
		{
			$error++;
			$errorforline++;
			setEventMessages('Try to insert a non balanced transaction in book for '.$ref.'. Canceled. Surely a bug.', null, 'errors');
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
		$param.='&in_bookkeeping='.$in_bookkeeping;
		header("Location: ".$_SERVER['PHP_SELF'].($param?'?'.$param:''));
		exit;
	}
}



// Export
if ($action == 'exportcsv') {		// ISO and not UTF8 !
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	$filename = 'journal';
<<<<<<< HEAD
	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	// CSV header line
	print '"' . $langs->trans("BankId").'"' . $sep;
	print '"' . $langs->trans("Date") . '"' . $sep;
	print '"' . $langs->trans("PaymentMode") . '"' . $sep;
	print '"' . $langs->trans("AccountAccounting") . '"' . $sep;
	print '"' . $langs->trans("LedgerAccount") . '"' . $sep;
	print '"' . $langs->trans("SubledgerAccount") . '"' . $sep;
	print '"' . $langs->trans("Label"). '"' . $sep;
	print '"' . $langs->trans("Debit") . '"' . $sep;
	print '"' . $langs->trans("Credit") . '"' . $sep;
	print '"' . $langs->trans("Journal")  . '"' . $sep;
	print '"' . $langs->trans("Note")  . '"' . $sep;
	print "\n";


	foreach ( $tabpay as $key => $val )
=======
	$type_export = 'journal';
	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	// CSV header line
	print '"' . $langs->transnoentitiesnoconv("BankId").'"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("Date") . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("PaymentMode") . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("AccountAccounting") . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("LedgerAccount") . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("SubledgerAccount") . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("Label"). '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("Debit") . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("Credit") . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("Journal")  . '"' . $sep;
	print '"' . $langs->transnoentitiesnoconv("Note")  . '"' . $sep;
	print "\n";

	foreach ($tabpay as $key => $val)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$date = dol_print_date($db->jdate($val["date"]), 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		// Bank
<<<<<<< HEAD
		foreach ( $tabbq[$key] as $k => $mt ) {
=======
		foreach ($tabbq[$key] as $k => $mt) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			if ($mt)
			{
				$reflabel = '';
				if (! empty($val['lib'])) $reflabel .= dol_string_nohtmltag($val['lib']) . " - ";
				$reflabel.= $langs->trans("Bank").' '.dol_string_nohtmltag($val['bank_account_ref']);
				if (! empty($val['soclib'])) $reflabel .= " - " . dol_string_nohtmltag($val['soclib']);

				print '"' . $key . '"' . $sep;
				print '"' . $date . '"' . $sep;
				print '"' . $val["type_payment"] . '"' . $sep;
				print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
				print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
				print "  " . $sep;
				print '"' . $reflabel . '"' . $sep;
				print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
				print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
				print '"' . $journal . '"' . $sep;
				print '"' . dol_string_nohtmltag($ref) . '"' . $sep;
				print "\n";
			}
		}

		// Third party
		if (is_array($tabtp[$key])) {
<<<<<<< HEAD
			foreach ( $tabtp[$key] as $k => $mt ) {
=======
			foreach ($tabtp[$key] as $k => $mt) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				if ($mt)
				{
					$reflabel = '';
					if (! empty($val['lib'])) $reflabel .= dol_string_nohtmltag($val['lib']) . ($val['soclib']?" - ":"");
<<<<<<< HEAD
					$reflabel.= dol_string_nohtmltag($val['soclib']);
=======
					if ($tabtype[$key] == 'banktransfert')
					{
						$reflabel.= dol_string_nohtmltag($langs->transnoentitiesnoconv('TransitionalAccount').' '.$account_transfer);
					}
					else
					{
						$reflabel.= dol_string_nohtmltag($val['soclib']);
					}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

					print '"' . $key . '"' . $sep;
					print '"' . $date . '"' . $sep;
					print '"' . $val["type_payment"] . '"' . $sep;
					print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
					if ($tabtype[$key] == 'payment_supplier') {
						print '"' . $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER . '"' . $sep;
<<<<<<< HEAD
					} else if($tabtype[$key] == 'payment') {
						print '"' . $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER . '"' . $sep;
					} else if($tabtype[$key] == 'payment_expensereport') {
						print '"' . $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT . '"' . $sep;
					} else if($tabtype[$key] == 'payment_salary') {
=======
					} elseif($tabtype[$key] == 'payment') {
						print '"' . $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER . '"' . $sep;
					} elseif($tabtype[$key] == 'payment_expensereport') {
						print '"' . $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT . '"' . $sep;
					} elseif($tabtype[$key] == 'payment_salary') {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
						print '"' . $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT . '"' . $sep;
					} else {
						print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
					}
					print '"' . length_accounta(html_entity_decode($k)) . '"' . $sep;
					print '"' . $reflabel . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
					print '"' . $journal . '"' . $sep;
					print '"' . dol_string_nohtmltag($ref) . '"' . $sep;
					print "\n";
				}
			}
		} else {	// If thirdparty unkown, output the waiting account
<<<<<<< HEAD
			foreach ( $tabbq[$key] as $k => $mt ) {
=======
			foreach ($tabbq[$key] as $k => $mt) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				if ($mt)
				{
					$reflabel = '';
					if (! empty($val['lib'])) $reflabel .= dol_string_nohtmltag($val['lib']) . " - ";
					$reflabel.= dol_string_nohtmltag('WaitingAccount');

					print '"' . $key . '"' . $sep;
					print '"' . $date . '"' . $sep;
					print '"' . $val["type_payment"] . '"' . $sep;
					print '"' . length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) . '"' . $sep;
					print '"' . length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) . '"' . $sep;
					print "" . $sep;
					print '"' . $reflabel . '"' . $sep;
					print '"' . ($mt < 0 ? price(- $mt) : '') . '"' . $sep;
					print '"' . ($mt >= 0 ? price($mt) : '') . '"' . $sep;
					print '"' . $journal . '"' . $sep;
					print '"' . dol_string_nohtmltag($ref) . '"' . $sep;
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
	$salarystatic = new PaymentSalary($db);
	$variousstatic = new PaymentVarious($db);

	llxHeader('', $langs->trans("FinanceJournal"));

<<<<<<< HEAD
	$nom = $langs->trans("FinanceJournal") . ' | ' . $accountingjournalstatic->getNomUrl(0,1,1,'',1);
=======
	$nom = $langs->trans("FinanceJournal") . ' | ' . $accountingjournalstatic->getNomUrl(0, 1, 1, '', 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	$builddate=dol_now();
	//$description = $langs->trans("DescFinanceJournal") . '<br>';
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';

	$listofchoices=array('notyet'=>$langs->trans("NotYetInGeneralLedger"), 'already'=>$langs->trans("AlreadyInGeneralLedger"));
<<<<<<< HEAD
	$period = $form->select_date($date_start?$date_start:-1, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end?$date_end:-1, 'date_end', 0, 0, 0, '', 1, 0, 1). ' -  ' .$langs->trans("JournalizationInLedgerStatus").' '. $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal=' . $id_journal;

	journalHead($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);
=======
    $period = $form->selectDate($date_start?$date_start:-1, 'date_start', 0, 0, 0, '', 1, 0) . ' - ' . $form->selectDate($date_end?$date_end:-1, 'date_end', 0, 0, 0, '', 1, 0);
    $period .= ' -  ' .$langs->trans("JournalizationInLedgerStatus").' '. $form->selectarray('in_bookkeeping', $listofchoices, $in_bookkeeping, 1);

	$varlink = 'id_journal=' . $id_journal;

	journalHead($nom, '', $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


	// Test that setup is complete
	$sql = 'SELECT COUNT(rowid) as nb FROM '.MAIN_DB_PREFIX.'bank_account WHERE fk_accountancy_journal IS NULL AND clos=0';
	$resql = $db->query($sql);
	if ($resql)
	{
		$obj = $db->fetch_object($resql);
		if ($obj->nb > 0)
		{
			print '<br>'.img_warning().' '.$langs->trans("TheJournalCodeIsNotDefinedOnSomeBankAccount");
<<<<<<< HEAD
			print ' : '.$langs->trans("AccountancyAreaDescBank", 9, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("BankAccounts").'</strong>');
=======
			print ' : '.$langs->trans("AccountancyAreaDescBank", 9, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("BankAccounts").'</strong>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
	}
	else dol_print_error($db);


	// Button to write into Ledger
<<<<<<< HEAD
	if (empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) || $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER == '-1'
		|| empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) || $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER == '-1'
		|| empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) || $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT == '-1') {
		print '<br>'.img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		print ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
=======
	if (($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER == "") || $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER == '-1'
		|| ($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER == "") || $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER == '-1'
		|| empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) || $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT == '-1') {
		print '<br>'.img_warning().' '.$langs->trans("SomeMandatoryStepsOfSetupWereNotDone");
		print ' : '.$langs->trans("AccountancyAreaDescMisc", 4, '<strong>'.$langs->transnoentitiesnoconv("MenuAccountancy").'-'.$langs->transnoentitiesnoconv("Setup")."-".$langs->transnoentitiesnoconv("MenuDefaultAccounts").'</strong>');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}


	print '<div class="tabsAction tabsActionNoBottom">';
<<<<<<< HEAD
	if (! empty($conf->global->ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL)) print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';
	if ($in_bookkeeping == 'notyet') print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
	else print '<a class="butActionRefused" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
=======

	if (! empty($conf->global->ACCOUNTING_ENABLE_EXPORT_DRAFT_JOURNAL)) print '<input type="button" class="butAction" name="exportcsv" value="' . $langs->trans("ExportDraftJournal") . '" onclick="launch_export();" />';

	if (($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER == "") || $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER == '-1'
	    || ($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER == "") || $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER == '-1') {
	   print '<input type="button" class="butActionRefused classfortooltip" title="'.dol_escape_htmltag($langs->trans("SomeMandatoryStepsOfSetupWereNotDone")).'" value="' . $langs->trans("WriteBookKeeping") . '" />';
	}
	else {
	    if ($in_bookkeeping == 'notyet') print '<input type="button" class="butAction" name="writebookkeeping" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
        else print '<a class="butActionRefused classfortooltip" name="writebookkeeping">' . $langs->trans("WriteBookKeeping") . '</a>';
	}

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
	print "<table class=\"noborder\" width=\"100%\">";
	print "<tr class=\"liste_titre\">";
<<<<<<< HEAD
	print "<td></td>";
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "<td>" . $langs->trans("Date") . "</td>";
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("ObjectsRef") . ")</td>";
	print "<td>" . $langs->trans("AccountAccounting") . "</td>";
	print "<td>" . $langs->trans("SubledgerAccount") . "</td>";
	print "<td>" . $langs->trans("LabelOperation") . "</td>";
<<<<<<< HEAD
	print "<td>" . $langs->trans("PaymentMode") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
=======
	print '<td class="center">' . $langs->trans("PaymentMode") . "</td>";
	print '<td class="right">' . $langs->trans("Debit") . "</td>";
	print '<td class="right">' . $langs->trans("Credit") . "</td>";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	print "</tr>\n";

	$r = '';

<<<<<<< HEAD
	foreach ( $tabpay as $key => $val )			  // $key is rowid in llx_bank
=======
	foreach ($tabpay as $key => $val)			  // $key is rowid in llx_bank
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$date = dol_print_date($db->jdate($val["date"]), 'day');

		$ref = getSourceDocRef($val, $tabtype[$key]);

		// Bank
<<<<<<< HEAD
		foreach ( $tabbq[$key] as $k => $mt )
=======
		foreach ($tabbq[$key] as $k => $mt)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		{
			if ($mt)
			{
				$reflabel = '';
				if (! empty($val['lib'])) $reflabel .= $val['lib'] . " - ";
				$reflabel.= $langs->trans("Bank").' '.$val['bank_account_ref'];
				if (! empty($val['soclib'])) $reflabel .= " - " . $val['soclib'];

				//var_dump($tabpay[$key]);
				print '<!-- Bank bank.rowid='.$key.' type='.$tabpay[$key]['type'].' ref='.$tabpay[$key]['ref'].'-->';
				print '<tr class="oddeven">';
<<<<<<< HEAD
				print "<td></td>";
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
				print "<td>";
				print $reflabel;
				print "</td>";
<<<<<<< HEAD
				print "<td>" . $val["type_payment"] . "</td>";
				print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
=======
				print '<td class="center">' . $val["type_payment"] . "</td>";
				print '<td class="right nowraponall">' . ($mt >= 0 ? price($mt) : '') . "</td>";
				print '<td class="right nowraponall">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				print "</tr>";
			}
		}

		// Third party
		if (is_array($tabtp[$key])) {
<<<<<<< HEAD
			foreach ( $tabtp[$key] as $k => $mt ) {
=======
			foreach ($tabtp[$key] as $k => $mt) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				if ($mt)
				{
					$reflabel = '';
					if (! empty($val['lib'])) $reflabel .= $val['lib'] . ($val['soclib']?" - ":"");
<<<<<<< HEAD
					$reflabel.= $val['soclib'];

					print '<!-- Thirdparty bank.rowid='.$key.' -->';
					print '<tr class="oddeven">';
					print "<td></td>";
=======
					if ($tabtype[$key] == 'banktransfert')
					{
						$reflabel.= $langs->trans('TransitionalAccount').' '.$account_transfer;
					}
					else
					{
						$reflabel.= $val['soclib'];
					}

					print '<!-- Thirdparty bank.rowid='.$key.' -->';
					print '<tr class="oddeven">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					print "<td>" . $date . "</td>";
					print "<td>" . $ref . "</td>";
					// Ledger account
					print "<td>";
					$account_ledger = $k;
					// Try to force general ledger account depending on type
<<<<<<< HEAD
					if ($tabtype[$key] == 'payment') $account_ledger = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
					if ($tabtype[$key] == 'payment_supplier') $account_ledger = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
					if ($tabtype[$key] == 'payment_expensereport') $account_ledger = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					if ($tabtype[$key] == 'payment_salary') $account_ledger = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					if ($tabtype[$key] == 'payment_vat') $account_ledger = $conf->global->ACCOUNTING_VAT_PAY_ACCOUNT;
=======
					if ($tabtype[$key] == 'payment')				$account_ledger = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
					if ($tabtype[$key] == 'payment_supplier')		$account_ledger = $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER;
					if ($tabtype[$key] == 'payment_expensereport')	$account_ledger = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					if ($tabtype[$key] == 'payment_salary')			$account_ledger = $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT;
					if ($tabtype[$key] == 'payment_vat')			$account_ledger = $conf->global->ACCOUNTING_VAT_PAY_ACCOUNT;
					if ($tabtype[$key] == 'member')					$account_ledger = $conf->global->ADHERENT_SUBSCRIPTION_ACCOUNTINGACCOUNT;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					$accounttoshow = length_accounta($account_ledger);
					if (empty($accounttoshow) || $accounttoshow == 'NotDefined')
					{
						if ($tabtype[$key] == 'unknown')
						{
							// We will accept writing, but into a waiting account
							if (empty($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) || $conf->global->ACCOUNTING_ACCOUNT_SUSPENSE == '-1')
							{
								print '<span class="error">'.$langs->trans('UnknownAccountForThirdpartyAndWaitingAccountNotDefinedBlocking').'</span>';
							}
							else
							{
<<<<<<< HEAD
								print '<span class="warning">'.$langs->trans('UnknownAccountForThirdparty', length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE)).'</span>';	// We will a waiting account
=======
								print '<span class="warning">'.$langs->trans('UnknownAccountForThirdparty', length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE)).'</span>';	// We will use a waiting account
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
							}
						}
						else
						{
							// We will refuse writing
							$errorstring='UnknownAccountForThirdpartyBlocking';
<<<<<<< HEAD
							if ($tabtype[$key] == 'payment') $errorstring='MainAccountForCustomersNotDefined';
							if ($tabtype[$key] == 'payment_supplier') $errorstring='MainAccountForSuppliersNotDefined';
							if ($tabtype[$key] == 'payment_expensereport') $errorstring='MainAccountForUsersNotDefined';
							if ($tabtype[$key] == 'payment_salary') $errorstring='MainAccountForUsersNotDefined';
							if ($tabtype[$key] == 'payment_vat') $errorstring='MainAccountForVatPaymentNotDefined';
=======
							if ($tabtype[$key] == 'payment')				$errorstring='MainAccountForCustomersNotDefined';
							if ($tabtype[$key] == 'payment_supplier')		$errorstring='MainAccountForSuppliersNotDefined';
							if ($tabtype[$key] == 'payment_expensereport')	$errorstring='MainAccountForUsersNotDefined';
							if ($tabtype[$key] == 'payment_salary')			$errorstring='MainAccountForUsersNotDefined';
							if ($tabtype[$key] == 'payment_vat')			$errorstring='MainAccountForVatPaymentNotDefined';
							if ($tabtype[$key] == 'member')					$errorstring='MainAccountForSubscriptionPaymentNotDefined';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
							print '<span class="error">'.$langs->trans($errorstring).'</span>';
						}
					}
					else print $accounttoshow;
					print "</td>";
					// Subledger account
					print "<td>";
<<<<<<< HEAD
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					if (in_array($tabtype[$key], array('payment', 'payment_supplier', 'payment_expensereport', 'payment_salary')))	// Type of payment with subledger
					{
						$accounttoshowsubledger = length_accounta($k);
						if ($accounttoshow != $accounttoshowsubledger)
						{
							if (empty($accounttoshowsubledger) || $accounttoshowsubledger == 'NotDefined')
							{
								/*var_dump($tabpay[$key]);
								var_dump($tabtype[$key]);
								var_dump($tabbq[$key]);*/
								//print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
<<<<<<< HEAD
								print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknown").'</span>';
=======
								if (! empty($tabcompany[$key]['code_compta']))
								{
									print '<span class="warning">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknown", $tabcompany[$key]['code_compta']).'</span>';
								}
								else
								{
									print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefinedOrThirdPartyUnknownBlocking").'</span>';
								}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
							}
							else print $accounttoshowsubledger;
						}
					}
					print "</td>";
					print "<td>" . $reflabel . "</td>";
<<<<<<< HEAD
					print "<td>" . $val["type_payment"] . "</td>";
					print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
					print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
=======
					print '<td class="center">' . $val["type_payment"] . "</td>";
					print '<td class="right nowraponall">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
					print '<td class="right nowraponall">' . ($mt >= 0 ? price($mt) : '') . "</td>";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					print "</tr>";
				}
			}
		} else {	// Waiting account
<<<<<<< HEAD
			foreach ( $tabbq[$key] as $k => $mt ) {
=======
			foreach ($tabbq[$key] as $k => $mt) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				if ($mt)
				{
					$reflabel = '';
					if (! empty($val['lib'])) $reflabel .= $val['lib'] . " - ";
					$reflabel.= 'WaitingAccount';

					print '<!-- Wait bank.rowid='.$key.' -->';
					print '<tr class="oddeven">';
<<<<<<< HEAD
					print "<td></td>";
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
					print "<td>" . $val["type_payment"] . "</td>";
					print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
					print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
=======
					print '<td class="center">' . $val["type_payment"] . "</td>";
					print '<td class="right nowraponall">' . ($mt < 0 ? price(- $mt) : '') . "</td>";
					print '<td class="right nowraponall">' . ($mt >= 0 ? price($mt) : '') . "</td>";
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
					print "</tr>";
				}
			}
		}
	}

	print "</table>";
	print '</div>';

	llxFooter();
}

$db->close();



/**
 * Return source for doc_ref of a bank transaction
 *
 * @param 	string 	$val			Array of val
 * @param 	string	$typerecord		Type of record ('payment', 'payment_supplier', 'payment_expensereport', 'payment_vat', ...)
<<<<<<< HEAD
 * @return string|unknown
=======
 * @return 	string					A string label to describe a record into llx_bank_url
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */
function getSourceDocRef($val, $typerecord)
{
	global $db, $langs;

	// Defined the docref into $ref (We start with $val['ref'] by default and we complete according to other data)
	// WE MUST HAVE SAME REF FOR ALL LINES WE WILL RECORD INTO THE BOOKKEEPING
	$ref = $val['ref'];
	if ($ref == '(SupplierInvoicePayment)' || $ref == '(SupplierInvoicePaymentBack)') {
<<<<<<< HEAD
		$ref = $langs->trans('Supplier');
	}
	if ($ref == '(CustomerInvoicePayment)' || $ref == '(CustomerInvoicePaymentBack)') {
		$ref = $langs->trans('Customer');
	}
	if ($ref == '(SocialContributionPayment)') {
		$ref = $langs->trans('SocialContribution');
	}
	if ($ref == '(DonationPayment)') {
		$ref = $langs->trans('Donation');
	}
	if ($ref == '(SubscriptionPayment)') {
		$ref = $langs->trans('Subscription');
	}
	if ($ref == '(ExpenseReportPayment)') {
		$ref = $langs->trans('Employee');
	}
	if ($ref == '(LoanPayment)') {
		$ref = $langs->trans('Loan');
	}
	if ($ref == '(payment_salary)') {
		$ref = $langs->trans('Employee');
=======
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
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	$sqlmid = '';
	if ($typerecord == 'payment')
	{
<<<<<<< HEAD
		$sqlmid = 'SELECT payfac.fk_facture as id, f.facnumber as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."paiement_facture as payfac, ".MAIN_DB_PREFIX."facture as f";
		$sqlmid .= " WHERE payfac.fk_facture = f.rowid AND payfac.fk_paiement=" . $val["paymentid"];
		$ref = $langs->trans("Invoice");
=======
		$sqlmid = 'SELECT payfac.fk_facture as id, f.ref as ref';
		$sqlmid .= " FROM ".MAIN_DB_PREFIX."paiement_facture as payfac, ".MAIN_DB_PREFIX."facture as f";
		$sqlmid .= " WHERE payfac.fk_facture = f.rowid AND payfac.fk_paiement=" . $val["paymentid"];
		$ref = $langs->transnoentitiesnoconv("Invoice");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'payment_supplier')
	{
		$sqlmid = 'SELECT payfac.fk_facturefourn as id, f.ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfac, ".MAIN_DB_PREFIX."facture_fourn as f";
		$sqlmid .= " WHERE payfac.fk_facturefourn = f.rowid AND payfac.fk_paiementfourn=" . $val["paymentsupplierid"];
<<<<<<< HEAD
		$ref = $langs->trans("SupplierInvoice");
=======
		$ref = $langs->transnoentitiesnoconv("SupplierInvoice");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'payment_expensereport')
	{
		$sqlmid = 'SELECT e.rowid as id, e.ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_expensereport as pe, " . MAIN_DB_PREFIX . "expensereport as e";
		$sqlmid .= " WHERE pe.rowid=" . $val["paymentexpensereport"]." AND pe.fk_expensereport = e.rowid";
<<<<<<< HEAD
		$ref = $langs->trans("ExpenseReport");
=======
		$ref = $langs->transnoentitiesnoconv("ExpenseReport");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'payment_salary')
	{
		$sqlmid = 'SELECT s.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_salary as s";
		$sqlmid .= " WHERE s.rowid=" . $val["paymentsalid"];
<<<<<<< HEAD
		$ref = $langs->trans("SalaryPayment");
=======
		$ref = $langs->transnoentitiesnoconv("SalaryPayment");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'sc')
	{
		$sqlmid = 'SELECT sc.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "paiementcharge as sc";
		$sqlmid .= " WHERE sc.rowid=" . $val["paymentscid"];
<<<<<<< HEAD
		$ref = $langs->trans("SocialContribution");
=======
		$ref = $langs->transnoentitiesnoconv("SocialContribution");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'payment_vat')
	{
		$sqlmid = 'SELECT v.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "tva as v";
		$sqlmid .= " WHERE v.rowid=" . $val["paymentvatid"];
<<<<<<< HEAD
		$ref = $langs->trans("PaymentVat");
=======
		$ref = $langs->transnoentitiesnoconv("PaymentVat");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'payment_donation')
	{
		$sqlmid = 'SELECT payd.fk_donation as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_donation as payd";
		$sqlmid .= " WHERE payd.fk_donation=" . $val["paymentdonationid"];
<<<<<<< HEAD
		$ref = $langs->trans("Donation");
=======
		$ref = $langs->transnoentitiesnoconv("Donation");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'payment_loan')
	{
		$sqlmid = 'SELECT l.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_loan as l";
		$sqlmid .= " WHERE l.rowid=" . $val["paymentloanid"];
<<<<<<< HEAD
		$ref = $langs->trans("LoanPayment");
=======
		$ref = $langs->transnoentitiesnoconv("LoanPayment");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	elseif ($typerecord == 'payment_various')
	{
		$sqlmid = 'SELECT v.rowid as ref';
		$sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_various as v";
		$sqlmid .= " WHERE v.rowid=" . $val["paymentvariousid"];
<<<<<<< HEAD
		$ref = $langs->trans("VariousPayment");
=======
		$ref = $langs->transnoentitiesnoconv("VariousPayment");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
	// Add warning
	if (empty($sqlmid))
	{
		dol_syslog("Found a typerecord=".$typerecord." not supported", LOG_WARNING);
	}

	if ($sqlmid)
	{
		dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		$resultmid = $db->query($sqlmid);
<<<<<<< HEAD
		if ($resultmid) {
=======
		if ($resultmid)
		{
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			while ($objmid = $db->fetch_object($resultmid))
			{
				$ref.=' '.$objmid->ref;
			}
		}
		else dol_print_error($db);
	}

<<<<<<< HEAD
	$ref = dol_trunc($langs->trans("BankId").' '.$val['fk_bank'].' - '.$ref, 295);	// 295 + 3 ... i< < than max size of 300
=======
	$ref = dol_trunc($langs->transnoentitiesnoconv("BankId").' '.$val['fk_bank'].' - '.$ref, 295);	// 295 + 3 dots (...) is < than max size of 300
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	return $ref;
}
