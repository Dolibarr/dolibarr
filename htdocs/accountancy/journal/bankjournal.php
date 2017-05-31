<?php
/* Copyright (C) 2007-2010	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2007-2010	Jean Heimburger		<jean@tiaris.info>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin		<regis@dolibarr.fr>
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

// Class
require_once DOL_DOCUMENT_ROOT . '/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
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


// Langs
$langs->load("companies");
$langs->load("other");
$langs->load("compta");
$langs->load("banks");
$langs->load('bills');
$langs->load('donations');
$langs->load("accountancy");
$langs->load("trips");
$langs->load("hrm");

$id_bank_account = GETPOST('id_account', 'int');

$date_startmonth = GETPOST('date_startmonth');
$date_startday = GETPOST('date_startday');
$date_startyear = GETPOST('date_startyear');
$date_endmonth = GETPOST('date_endmonth');
$date_endday = GETPOST('date_endday');
$date_endyear = GETPOST('date_endyear');
$action = GETPOST('action');

$now = dol_now();

// Security check
if ($user->societe_id > 0 && empty($id_bank_account))
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

$p = explode(":", $conf->global->MAIN_INFO_SOCIETE_COUNTRY);
$idpays = $p[0];

$sql  = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type,";
$sql .= " ba.courant, ba.ref as baref, ba.account_number,";
$sql .= " soc.code_compta, soc.code_compta_fournisseur, soc.rowid as socid, soc.nom as name, bu1.type as typeop,";
$sql .= " u.accountancy_code, u.rowid as userid, u.lastname as name, u.firstname as firstname, bu2.type as typeop";
$sql .= " FROM " . MAIN_DB_PREFIX . "bank as b";
$sql .= " JOIN " . MAIN_DB_PREFIX . "bank_account as ba on b.fk_account=ba.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu1 ON bu1.fk_bank = b.rowid AND bu1.type='company'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "bank_url as bu2 ON bu2.fk_bank = b.rowid AND bu2.type='user'";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as soc on bu1.url_id=soc.rowid";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as u on bu2.url_id=u.rowid";
$sql .= " WHERE ba.rowid=" . $id_bank_account;
$sql .= ' AND ba.entity IN ('.getEntity('bank_account', 0).')';        // We don't share object for accountancy
if ($date_start && $date_end)
	$sql .= " AND b.dateo >= '" . $db->idate($date_start) . "' AND b.dateo <= '" . $db->idate($date_end) . "'";
$sql .= " ORDER BY b.datev";

$object = new Account($db);
$paymentstatic = new Paiement($db);
$paymentsupplierstatic = new PaiementFourn($db);
$societestatic = new Societe($db);
$userstatic = new User($db);
$chargestatic = new ChargeSociales($db);
$paymentdonstatic = new PaymentDonation($db);
$paymentvatstatic = new TVA($db);
$paymentsalstatic = new PaymentSalary($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);

// Get code of finance journal
$bank_code_journal = new Account($db);
$result = $bank_code_journal->fetch($id_bank_account);
$journal = $bank_code_journal->accountancy_journal;

dol_syslog("accountancy/journal/bankjournal.php", LOG_DEBUG);
$result = $db->query($sql);
if ($result) {

	$num = $db->num_rows($result);
	// Variables
	$account_supplier = (! empty($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) ? $conf->global->ACCOUNTING_ACCOUNT_SUPPLIER : $langs->trans("CodeNotDef"));
	$account_customer = (! empty($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) ? $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER : $langs->trans("CodeNotDef"));
	$account_employee = (! empty($conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT) ? $conf->global->SALARIES_ACCOUNTING_ACCOUNT_PAYMENT : $langs->trans("CodeNotDef"));
	$account_pay_vat = (! empty($conf->global->ACCOUNTING_VAT_PAY_ACCOUNT) ? $conf->global->ACCOUNTING_VAT_PAY_ACCOUNT : $langs->trans("CodeNotDef"));
	$account_pay_donation = (! empty($conf->global->DONATION_ACCOUNTINGACCOUNT) ? $conf->global->DONATION_ACCOUNTINGACCOUNT : $langs->trans("CodeNotDef"));
	$account_transfer = (! empty($conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH) ? $conf->global->ACCOUNTING_ACCOUNT_TRANSFER_CASH : $langs->trans("CodeNotDef"));

	$tabcompany = array();
	$tabuser = array();
	$tabpay = array ();
	$tabbq = array ();
	$tabtp = array ();
	$tabtype = array ();

	// Loop on each line into bank account. For each line, we should get:
	// on line tabpay = line into bank
	// one line for bank jounral = tabbq
	// one line for thirdparty journal = tabtp
	$i = 0;
	while ( $i < $num ) 
	{
		$obj = $db->fetch_object($result);

		// Set accountancy code (for bank and thirdparty)
		$compta_bank = $obj->account_number;
		if ($obj->label == '(SupplierInvoicePayment)')
			$compta_soc = (! empty($obj->code_compta_fournisseur) ? $obj->code_compta_fournisseur : $account_supplier);
		if ($obj->label == '(CustomerInvoicePayment)')
			$compta_soc = (! empty($obj->code_compta) ? $obj->code_compta : $account_customer);

		$tabcompany[$obj->rowid] = array (
				'id' => $obj->socid,
				'name' => $obj->name,
		        'code_compta' => $compta_soc,
		);
		
		$compta_user = (! empty($obj->accountancy_code) ? $obj->accountancy_code : $account_employee);

		$tabuser[$obj->rowid] = array (
				'id' => $obj->userid,
				'lastname' => $obj->lastname,
				'firstname' => $obj->firstname,
		        'accountancy_code' => $compta_user,
		);

		// Variable bookkeeping
		$tabpay[$obj->rowid]["date"] = $obj->do;
		$tabpay[$obj->rowid]["type_payment"] = $obj->fk_type;
		$tabpay[$obj->rowid]["ref"] = $obj->label;
		$tabpay[$obj->rowid]["fk_bank"] = $obj->rowid;
		if (preg_match('/^\((.*)\)$/i', $obj->label, $reg)) {
			$tabpay[$obj->rowid]["lib"] = $langs->trans($reg[1]);
		} else {
			$tabpay[$obj->rowid]["lib"] = dol_trunc($obj->label, 60);
		}
		$links = $object->get_url($obj->rowid);

		// get_url may return -1 which is not traversable
		if (is_array($links)) {
		    // Now loop on each link of record in bank.
			foreach ( $links as $key => $val ) {
			    
			    if (in_array($links[$key]['type'], array('sc', 'payment_sc', 'payment', 'payment_supplier', 'payment_vat', 'payment_expensereport')))     // So we excluded 'company' here
			    {
			        // We save tabtype for a future use, to remember what kind of payment it is 
			        $tabtype[$obj->rowid] = $links[$key]['type'];
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
					$tabtp[$obj->rowid][$compta_soc] += $obj->amount;
				} else if ($links[$key]['type'] == 'user') {
					$userstatic->id = $links[$key]['url_id'];
					$userstatic->name = $links[$key]['label'];
					$tabpay[$obj->rowid]["soclib"] = $userstatic->getNomUrl(1, '', 30);
					$tabtp[$obj->rowid][$compta_user] += $obj->amount;
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
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "chargesociales as chgsoc ON  chgsoc.fk_type=cchgsoc.id";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementcharge as paycharg ON  paycharg.fk_charge=chgsoc.rowid";
					$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "bank_url as bkurl ON  bkurl.url_id=paycharg.rowid";
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
					$tabpay[$obj->rowid]["lib"] .= ' ' . $langs->trans("PaymentDonation");
					$tabtp[$obj->rowid][$account_pay_donation] += $obj->amount;
				} else if ($links[$key]['type'] == 'payment_vat') {
					$paymentvatstatic->id = $links[$key]['url_id'];
					$paymentvatstatic->ref = $links[$key]['url_id'];
					$paymentvatstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $langs->trans("PaymentVat");
					$tabtp[$obj->rowid][$account_pay_vat] += $obj->amount;
				} else if ($links[$key]['type'] == 'payment_salary') {
					$paymentsalstatic->id = $links[$key]['url_id'];
					$paymentsalstatic->ref = $links[$key]['url_id'];
					$paymentsalstatic->label = $links[$key]['label'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentsalstatic->getNomUrl(2);
					// $tabtp[$obj->rowid][$account_employee] += $obj->amount;
				} else if ($links[$key]['type'] == 'payment_expensereport') {
					$paymentexpensereportstatic->id = $links[$key]['url_id'];
					$paymentexpensereportstatic->fk_expensereport = $links[$key]['url_id'];
					$tabpay[$obj->rowid]["lib"] .= ' ' . $paymentexpensereportstatic->getNomUrl(2);
					$tabpay[$obj->rowid]["fk_expensereport"] = $paymentexpensereportstatic->id;
				} else if ($links[$key]['type'] == 'banktransfert') {
					$tabpay[$obj->rowid]["lib"] .= ' ' . $langs->trans("BankTransfer");
					$tabtp[$obj->rowid][$account_transfer] += $obj->amount;
				}
			}
		}

		$tabbq[$obj->rowid][$compta_bank] += $obj->amount;

		
		// Check account number is ok
		/*if ($action == 'writebookkeeping')        // Make test now in such a case
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

		$i ++;
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
	foreach ( $tabpay as $key => $val ) {      // $key is rowid into llx_bank
		
	    $errorforline = 0;
	    
	    $db->begin();
	    
		// Bank
		if (! $errorforline)
		{
		    // Line into bank account
    		foreach ( $tabbq[$key] as $k => $mt ) 
    		{
    			if ($mt) {
        		    $bookkeeping = new BookKeeping($db);
        			$bookkeeping->doc_date = $val["date"];
        			$bookkeeping->doc_ref = $val["ref"];
        			$bookkeeping->doc_type = 'bank';
        			$bookkeeping->fk_doc = $key;
        			$bookkeeping->fk_docdet = $val["fk_bank"];
        			$bookkeeping->numero_compte = $k;
        			$bookkeeping->label_compte = $compte->label;
        			$bookkeeping->montant = ($mt < 0 ? - $mt : $mt);
        			$bookkeeping->sens = ($mt >= 0) ? 'D' : 'C';
        			$bookkeeping->debit = ($mt >= 0 ? $mt : 0);
        			$bookkeeping->credit = ($mt < 0 ? - $mt : 0);
        			$bookkeeping->code_journal = $journal;
        			$bookkeeping->fk_user_author = $user->id;
        			$bookkeeping->date_create = $now;
        
        			if ($tabtype[$key] == 'payment') {
        			    $bookkeeping->code_tiers = $tabcompany[$key]['code_compta'];
    
        				$sqlmid = 'SELECT fac.facnumber';
        				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture fac";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement_facture as payfac ON payfac.fk_facture=fac.rowid";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as pay ON  payfac.fk_paiement=pay.rowid";
        				$sqlmid .= " WHERE pay.fk_bank=" . $key;
        				dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
        				$resultmid = $db->query($sqlmid);
        				if ($resultmid) {
        					$objmid = $db->fetch_object($resultmid);
        					$bookkeeping->doc_ref = $objmid->facnumber;    // Ref of invoice
        				}
        			} else if ($tabtype[$key] == 'payment_supplier') {
        			    $bookkeeping->code_tiers = $tabcompany[$key]['code_compta'];
    
        				$sqlmid = 'SELECT facf.ref_supplier, facf.ref';
        				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture_fourn facf";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfacf ON payfacf.fk_facturefourn=facf.rowid";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn as payf ON  payfacf.fk_paiementfourn=payf.rowid";
        				$sqlmid .= " WHERE payf.fk_bank=" . $key;
        				dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
        				$resultmid = $db->query($sqlmid);
        				if ($resultmid) {
        					$objmid = $db->fetch_object($resultmid);
        					$bookkeeping->doc_ref = $objmid->ref_supplier . ' (' . $objmid->ref . ')'; // Ref on invoice
        				}
        			} else if ($tabtype[$key] == 'payment_expensereport') {
        			    $bookkeeping->code_tiers = $tabuser[$key]['accountancy_code'];
    
        				$sqlmid = 'SELECT e.ref';
        				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "expensereport as e";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "payment_expensereport as payer ON payer.fk_expensereport=e.rowid";
        				$sqlmid .= " WHERE payer.fk_expensereport=" . $val["fk_expensereport"];
        				dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
        				$resultmid = $db->query($sqlmid);
        				if ($resultmid) {
        					$objmid = $db->fetch_object($resultmid);
        					$bookkeeping->doc_ref = $objmid->ref; // Ref of expensereport
        				}
        			}
        
        			$result = $bookkeeping->create($user);
        			if ($result < 0) {
        				if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
        				{
        					$error++;
        					$errorforline++;
        					//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->doc_ref.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
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
		    // Line into thirdparty account
    		foreach ( $tabtp[$key] as $k => $mt ) {
    			if ($mt) {
        		    $bookkeeping = new BookKeeping($db);
        			$bookkeeping->doc_date = $val["date"];
        			$bookkeeping->doc_ref = $val["ref"];
        			$bookkeeping->doc_type = 'bank';
        			$bookkeeping->fk_doc = $key;
        			$bookkeeping->fk_docdet = $val["fk_bank"];
        			$bookkeeping->label_compte = $tabcompany[$key]['name'];
        			$bookkeeping->montant = ($mt < 0 ? - $mt : $mt);
        			$bookkeeping->sens = ($mt < 0) ? 'D' : 'C';
        			$bookkeeping->debit = ($mt < 0 ? - $mt : 0);
        			$bookkeeping->credit = ($mt >= 0) ? $mt : 0;
        			$bookkeeping->code_journal = $journal;
        			$bookkeeping->fk_user_author = $user->id;
        			$bookkeeping->date_create = $now;
        
        			if (in_array($tabtype[$key], array('sc', 'payment_sc'))) {   // If payment is payment of social contribution
        				$bookkeeping->code_tiers = '';
        				$bookkeeping->numero_compte = $k;
        			} else if ($tabtype[$key] == 'payment') {    // If payment is payment of customer invoice, we get ref of invoice
        				$sqlmid = 'SELECT fac.facnumber';
        				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture fac ";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement_facture as payfac ON  payfac.fk_facture=fac.rowid";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiement as pay ON  payfac.fk_paiement=pay.rowid";
        				$sqlmid .= " WHERE pay.fk_bank=" . $key;
        				dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
        				$resultmid = $db->query($sqlmid);
        				if ($resultmid) {
        					$objmid = $db->fetch_object($resultmid);
        					$bookkeeping->doc_ref = $objmid->facnumber;
        				}
        				$bookkeeping->code_tiers = $tabcompany[$key]['code_compta'];
        				$bookkeeping->numero_compte = $k;
        			} else if ($tabtype[$key] == 'payment_supplier') {           // If payment is payment of supplier invoice, we get ref of invoice
        
        				$sqlmid = 'SELECT facf.ref_supplier,facf.ref';
        				$sqlmid .= " FROM " . MAIN_DB_PREFIX . "facture_fourn facf ";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfacf ON  payfacf.fk_facturefourn=facf.rowid";
        				$sqlmid .= " INNER JOIN " . MAIN_DB_PREFIX . "paiementfourn as payf ON  payfacf.fk_paiementfourn=payf.rowid";
        				$sqlmid .= " WHERE payf.fk_bank=" . $key;
        				dol_syslog("accountancy/journal/bankjournal.php:: sqlmid=" . $sqlmid, LOG_DEBUG);
        				$resultmid = $db->query($sqlmid);
        				if ($resultmid) {
        					$objmid = $db->fetch_object($resultmid);
        					$bookkeeping->doc_ref = $objmid->ref_supplier . ' (' . $objmid->ref . ')';
        				}
                        $bookkeeping->code_tiers = $tabcompany[$key]['code_compta'];
        				$bookkeeping->numero_compte = $k;
        			} else {
        			    // FIXME Should be a temporary account ???
        				$bookkeeping->doc_ref = $k;
        				//$bookkeeping->numero_compte = $conf->global->ACCOUNTING_ACCOUNT_CUSTOMER;
        				$bookkeeping->numero_compte = 'CodeNotDef';
        			}
        
        			$result = $bookkeeping->create($user);
        			if ($result < 0) {
        				if ($bookkeeping->error == 'BookkeepingRecordAlreadyExists')	// Already exists
        				{
        					$error++;
        					$errorforline++;
        					//setEventMessages('Transaction for ('.$bookkeeping->doc_type.', '.$bookkeeping->doc_ref.', '.$bookkeeping->fk_docdet.') were already recorded', null, 'warnings');
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
		    $db->rollback();
		}
	}

	if (empty($error)) {
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
}

// Export
if ($action == 'export_csv') {
	$sep = $conf->global->ACCOUNTING_EXPORT_SEPARATORCSV;

	include DOL_DOCUMENT_ROOT . '/accountancy/tpl/export_journal.tpl.php';

	$companystatic = new Client($db);
	$userstatic = new User($db);

	// Model Cegid Expert Export
	if ($conf->global->ACCOUNTING_EXPORT_MODELCSV == 2)
	{
		$sep = ";";

		foreach ( $tabpay as $key => $val ) {
			$date = dol_print_date($db->jdate($val["date"]), '%d%m%Y');

			$reflabel = $val["ref"];
			if ($reflabel == '(SupplierInvoicePayment)') {
				$reflabel = $langs->trans('Supplier');
			}
			if ($reflabel == '(CustomerInvoicePayment)') {
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
			
			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];

			$userstatic->id = $tabuser[$key]['id'];
			$userstatic->lastname = $tabuser[$key]['lastname'];
			$userstatic->firstname = $tabuser[$key]['firstname'];

			// Bank
			foreach ( $tabbq[$key] as $k => $mt ) {
				print $date . $sep;
				print $journal . $sep;
				print length_accountg(html_entity_decode($k)) . $sep;
				print $sep;
				print ($mt < 0 ? 'C' : 'D') . $sep;
				print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
				if ($companystatic->name == '') {
					print $langs->trans('Bank')." - ". utf8_decode($val["ref"]) . $sep;
				} else {
					print $langs->trans("Bank") .' - '.utf8_decode($companystatic->name) . $sep;
				}
				print utf8_decode($reflabel) . $sep;
				print "\n";
			}

			// Third party
			if (is_array($tabtp[$key])) {
				foreach ( $tabtp[$key] as $k => $mt ) {
					if ($mt) {
						print $date . $sep;
						print $journal . $sep;
						if ($tabtype[$key] == 'payment') {
							print length_accountg($conf->global->ACCOUNTING_ACCOUNT_CUSTOMER) . $sep;
							print length_accounta(html_entity_decode($k)) . $sep;
						} else if ($tabtype[$key] == 'payment_supplier') {
							print length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUPPLIER) . $sep;
							print length_accounta(html_entity_decode($k)) . $sep;
						} else {
							print length_accountg(html_entity_decode($k)) . $sep;
							print $sep;
						}
						print ($mt < 0 ? 'D' : 'C') . $sep;
						print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
						if ($companystatic->name == '') {
							print $langs->trans('ThirdParty')." - ". utf8_decode($val["ref"]) . $sep;
						} else {
							print $langs->trans('ThirdParty')." - ". utf8_decode($companystatic->name) . $sep;
						}
						print utf8_decode($reflabel) . $sep;
						print "\n";
					}
				}
			} else {
				foreach ( $tabbq[$key] as $k => $mt ) {
					print $date . $sep;
					print $journal . $sep;
					print length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) . $sep;
					print $sep;
					print ($mt < 0 ? 'D' : 'C') . $sep;
					print ($mt <= 0 ? price(- $mt) : $mt) . $sep;
					if ($companystatic->name == '') {
						print $langs->trans('ThirdParty')." - ". utf8_decode($val["ref"]) . $sep;
					} else {
						print $langs->trans('ThirdParty')." - ". utf8_decode($companystatic->name) . $sep;
					}
					print utf8_decode($reflabel) . $sep;
					print "\n";
				}
			}
		}
	} else {
		// Model Classic Export
		foreach ( $tabpay as $key => $val ) {
			$date = dol_print_date($db->jdate($val["date"]), 'day');

			$companystatic->id = $tabcompany[$key]['id'];
			$companystatic->name = $tabcompany[$key]['name'];

			// Bank
			foreach ( $tabbq[$key] as $k => $mt ) {
				print '"' . $journal . '"' . $sep;
				print '"' . $date . '"' . $sep;
				print '"' . $val["type_payment"] . '"' . $sep;
				print '"' . length_accountg(html_entity_decode($k)) . '"' . $sep;
				if ($companystatic->name == '') {
					print '"' . $langs->trans('Bank') . " - " . utf8_decode($val["ref"]) . '"' . $sep;
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
						if ($companystatic->name == '') {
							print '"' . $langs->trans('ThirdParty') . " - " . utf8_decode($val["ref"]) . '"' . $sep;
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
					print '"' . $val["ref"] . '"' . $sep;
					print '"' . length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE) . '"' . $sep;
					if ($companystatic->name == '') {
						print '"' . $langs->trans("Bank") . ' - ' . utf8_decode($val["ref"]) . '"' . $sep;
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
}



/*
 * View
 */

$form = new Form($db);

if (empty($action) || $action == 'view') {
	$invoicestatic = new Facture($db);
	$invoicesupplierstatic = new FactureFournisseur($db);
	$expensereportstatic = new ExpenseReport($db);
	
	llxHeader('', $langs->trans("FinanceJournal"));

	$nom = $langs->trans("FinanceJournal") . ' - ' . $bank_code_journal->getNomUrl(1);
	$builddate = time();
	//$description = $langs->trans("DescFinanceJournal") . '<br>';
	$description.= $langs->trans("DescJournalOnlyBindedVisible").'<br>';
	$period = $form->select_date($date_start, 'date_start', 0, 0, 0, '', 1, 0, 1) . ' - ' . $form->select_date($date_end, 'date_end', 0, 0, 0, '', 1, 0, 1);

	$varlink = 'id_account=' . $id_bank_account;
	
	report_header($nom, $nomlink, $period, $periodlink, $description, $builddate, $exportlink, array('action' => ''), '', $varlink);

	/*if ($conf->global->ACCOUNTING_EXPORT_MODELCSV != 1 && $conf->global->ACCOUNTING_EXPORT_MODELCSV != 2) {
		print '<input type="button" class="butActionRefused" style="float: right;" value="' . $langs->trans('Export') . '" disabled="disabled" title="' . $langs->trans('ExportNotSupported') . '"/>';
	} else {
		print '<input type="button" class="butAction" style="float: right;" value="' . $langs->trans("Export") . '" onclick="launch_export();" />';
	}*/

    print '<div class="tabsAction">';
	print '<input type="button" class="butAction" value="' . $langs->trans("WriteBookKeeping") . '" onclick="writebookkeeping();" />';
    print '</div>';
    
	// TODO Avoid using js. We can use a direct link with $param
	print '
	<script type="text/javascript">
		function launch_export() {
	        console.log("Set value into form and submit");
		    $("div.fiche div.tabBar form input[name=\"action\"]").val("export_csv");
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
	print "<td>" . $langs->trans("Piece") . ' (' . $langs->trans("InvoiceRef") . ")</td>";
	print "<td>" . $langs->trans("AccountAccounting") . "</td>";
	print "<td>" . $langs->trans("Type") . "</td>";
	print "<td>" . $langs->trans("PaymentMode") . "</td>";
	print "<td align='right'>" . $langs->trans("Debit") . "</td>";
	print "<td align='right'>" . $langs->trans("Credit") . "</td>";
	print "</tr>\n";

	$var = true;
	$r = '';

	foreach ( $tabpay as $key => $val ) {      // $key is rowid in llx_bank
		$date = dol_print_date($db->jdate($val["date"]), 'day');

		$reflabel = $val["ref"];
		if ($reflabel == '(SupplierInvoicePayment)') {
			$reflabel = $langs->trans('Supplier');
		}
		if ($reflabel == '(CustomerInvoicePayment)') {
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
		
		$ref=$reflabel;
		if ($tabtype[$key] == 'payment')
		{
		    $sqlmid = 'SELECT payfac.fk_facture as id';
		    $sqlmid .= " FROM ".MAIN_DB_PREFIX."paiement_facture as payfac";
		    $sqlmid .= " WHERE payfac.fk_paiement=" . $val["paymentid"];
		    dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		    $resultmid = $db->query($sqlmid);
		    if ($resultmid) {
		        $objmid = $db->fetch_object($resultmid);
		        $invoicestatic->fetch($objmid->id);
		        $ref=$langs->trans("Invoice").' '.$invoicestatic->getNomUrl(1);
		    }
		    else dol_print_error($db);
		}
		elseif ($tabtype[$key] == 'payment_supplier')
		{
		    $sqlmid = 'SELECT payfac.fk_facturefourn as id';
		    $sqlmid .= " FROM " . MAIN_DB_PREFIX . "paiementfourn_facturefourn as payfac";
		    $sqlmid .= " WHERE payfac.fk_paiementfourn=" . $val["paymentsupplierid"];
		    dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		    $resultmid = $db->query($sqlmid);
		    if ($resultmid) {
		        $objmid = $db->fetch_object($resultmid);
		        $invoicesupplierstatic->fetch($objmid->id);
		        $ref=$langs->trans("SupplierInvoice").' '.$invoicesupplierstatic->getNomUrl(1);
		    }
		    else dol_print_error($db);
		}
		elseif ($tabtype[$key] == 'payment_expensereport')
		{
		    $sqlmid = 'SELECT payer.fk_expensereport as id';
		    $sqlmid .= " FROM " . MAIN_DB_PREFIX . "payment_expensereport as payer";
		    $sqlmid .= " WHERE payer.fk_expensereport=" . $val["fk_expensereport"];
		    dol_syslog("accountancy/journal/bankjournal.php::sqlmid=" . $sqlmid, LOG_DEBUG);
		    $resultmid = $db->query($sqlmid);
		    if ($resultmid) {
		        $objmid = $db->fetch_object($resultmid);
		        $expensereportstatic->fetch($objmid->id);
		        $ref=$langs->trans("ExpenseReport").' '.$expensereportstatic->getNomUrl(1);
		    }
		    else dol_print_error($db);
		}


		/*$invoicestatic->id = $key;
		$invoicestatic->ref = $val["ref"];
		$invoicestatic->type = $val["type"];*/
		// Bank
		foreach ( $tabbq[$key] as $k => $mt ) 
		{
		    print "<tr " . $bc[$var] . ">";
		    print "<td><!-- Bank bank.rowid=".$key."--></td>";
		    print "<td>" . $date . "</td>";
			print "<td>" . $ref . "</td>";
			print "<td>";
			$accountoshow = length_accountg($k);
			if (empty($accountoshow) || $accountoshow == 'NotDefined')
			{
			    print '<span class="error">'.$langs->trans("BankAccountNotDefined").'</span>';
			}
			else print $accountoshow;
			print "</td>";
			if ($val['soclib'] == '') {
				print "<td>" . $bank_code_journal->label . " - " . $val["ref"] . "</td>";
			} else {
				print "<td>" . $bank_code_journal->label . " - " . $val['soclib'] . "</td>";
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
					print "<tr " . $bc[$var] . ">";
					print "<td><!-- Thirdparty bank.rowid=".$key." --></td>";
					print "<td>" . $date . "</td>";
					print "<td>" . $ref . "</td>";
					print "<td>";
        			$accountoshow = length_accounta($k);
        			if (empty($accountoshow) || $accountoshow == 'NotDefined')
        			{
        			    print '<span class="error">'.$langs->trans("ThirdpartyAccountNotDefined").'</span>';
        			}
        			else print $accountoshow;
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
				print "<tr " . $bc[$var] . ">";
				print "<td><!-- Wait bank.rowid=".$key." --></td>";
				print "<td>" . $date . "</td>";
				print "<td>" . $ref . "</td>";
				print "<td>";
			    if (empty($accountoshow) || $accountoshow == 'NotDefined')
        		{
        		    print '<span class="error">'.$langs->trans("WaitAccountNotDefined").'</span>';
        		}
				else print length_accountg($conf->global->ACCOUNTING_ACCOUNT_SUSPENSE); 
				print "</td>";
				print "<td>" . $reflabel . "</td>";
				print "<td>&nbsp;</td>";
				print "<td align='right'>" . ($mt < 0 ? price(- $mt) : '') . "</td>";
				print "<td align='right'>" . ($mt >= 0 ? price($mt) : '') . "</td>";
				print "</tr>";
			}
		}
		$var = ! $var;
	}

	print "</table>";

	llxFooter();
}

$db->close();
