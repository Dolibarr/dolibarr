<?php
/* Copyright (C) 2010       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2026       Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/PaiementTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the customer payment (Paiement) class.
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */


//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
global $conf, $user, $langs, $db, $mysoc;
require_once dirname(__FILE__) . '/../../htdocs/master.inc.php';
/**
 * @var DoliDB $db
 * @var Conf $conf
 * @var Translate $langs
 * @var User $user
 */
require_once dirname(__FILE__) . '/../../htdocs/compta/paiement/class/paiement.class.php';
require_once dirname(__FILE__) . '/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__) . '/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__) . '/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests on the Paiement (customer payment) class.
 *
 * The tests are chained: a validated invoice is created, then a payment that
 * covers its full TTC amount is created and reconciled with the bank, fetched,
 * its date/reference updated, and finally deleted.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PaiementTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('invoice'), " module customer invoice must be enabled");
		parent::setUpBeforeClass();
	}

	/**
	 * Create and validate the invoice that the payment will settle.
	 *
	 * @return	int		Id of the validated invoice
	 */
	public function testPaiementPrepareInvoice()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Force to default setup so validation is not blocked by date controls
		$conf->global->FAC_FORCE_DATE_VALIDATION = 0;
		$conf->global->INVOICE_CHECK_POSTERIOR_DATE = 0;

		$invoice = new Facture($db);
		$invoice->initAsSpecimen();
		$result = $invoice->create($user);
		$this->assertLessThan($result, 0, 'Facture::create failed');

		$result = $invoice->validate($user);
		$this->assertLessThan($result, 0, 'Facture::validate failed');

		print __METHOD__ . " invoiceid=" . $invoice->id . " total_ttc=" . $invoice->total_ttc . "\n";
		$this->assertGreaterThan(0, $invoice->total_ttc, 'Specimen invoice should have a positive TTC amount');

		return $invoice->id;
	}

	/**
	 * Create a payment that settles the full TTC of the invoice.
	 *
	 * @param	int		$invoiceid		Id of the invoice to pay
	 * @return	array{paymentid:int,invoiceid:int,amount:float}
	 *
	 * @depends	testPaiementPrepareInvoice
	 */
	public function testPaiementCreate($invoiceid)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$invoice = new Facture($db);
		$invoice->fetch($invoiceid);
		$amount = (float) price2num($invoice->total_ttc, 'MT');

		$payment = new Paiement($db);
		$payment->datepaye     = dol_now();
		$payment->amounts      = array($invoiceid => $amount); // Dispatch the whole amount on the invoice
		$payment->paiementcode = 'LIQ'; // Cash
		$payment->paiementid   = dol_getIdFromCode($db, $payment->paiementcode, 'c_paiement', 'code', 'id', 1);
		$payment->num_payment  = '';
		$payment->note_private = 'Created by PaiementTest';

		// closepaidinvoices=0 on purpose: keep the invoice validated (status 1) so that the
		// deletion test below is allowed (a payment linked to a closed invoice cannot be deleted).
		$paymentid = $payment->create($user, 0);
		print __METHOD__ . " paymentid=" . $paymentid . "\n";
		$this->assertLessThan($paymentid, 0, 'Paiement::create failed: ' . $payment->error);

		// create() must have computed ->amount from the ->amounts array
		$this->assertEquals($amount, (float) price2num($payment->amount, 'MT'), 'Payment amount differs from dispatched amount');

		return array('paymentid' => $paymentid, 'invoiceid' => (int) $invoiceid, 'amount' => $amount);
	}

	/**
	 * Reconcile the payment with a bank account (creates the llx_bank entry).
	 *
	 * @param	array{paymentid:int,invoiceid:int,amount:float}	$data	Data from previous test
	 * @return	array{paymentid:int,invoiceid:int,amount:float}
	 *
	 * @depends	testPaiementCreate
	 */
	public function testPaiementAddToBank($data)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		if (!isModEnabled('bank')) {
			$this->assertTrue(true, 'Bank module disabled, skipping addPaymentToBank');
			return $data;
		}

		// Create a bank account to receive the payment
		$account = new Account($db);
		$account->initAsSpecimen();
		$account->date_solde = dol_now(); // Date of initial balance is required by Account::create
		$accountid = $account->create($user);
		$this->assertLessThan($accountid, 0, 'Account::create failed: ' . $account->error);

		$payment = new Paiement($db);
		$payment->fetch($data['paymentid']);
		$payment->paiementcode = $payment->type_code;
		$payment->amounts = $payment->getAmountsArray();

		$bankline = $payment->addPaymentToBank($user, 'payment', '(CustomerInvoicePayment)', $accountid, '', '');
		print __METHOD__ . " bankline=" . $bankline . "\n";
		$this->assertLessThan($bankline, 0, 'Paiement::addPaymentToBank failed: ' . $payment->error);

		return $data;
	}

	/**
	 * Reload the payment from database and check stored values.
	 *
	 * @param	array{paymentid:int,invoiceid:int,amount:float}	$data	Data from previous test
	 * @return	array{paymentid:int,invoiceid:int,amount:float}
	 *
	 * @depends	testPaiementAddToBank
	 */
	public function testPaiementFetch($data)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$payment = new Paiement($db);
		$result = $payment->fetch($data['paymentid']);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertLessThan($result, 0, 'Paiement::fetch failed');

		$this->assertEquals($data['paymentid'], $payment->id);
		$this->assertEquals($data['amount'], (float) price2num($payment->amount, 'MT'), 'Stored amount differs');

		return $data;
	}

	/**
	 * The invoice must report the payment in its paid sum (getSommePaiement).
	 *
	 * @param	array{paymentid:int,invoiceid:int,amount:float}	$data	Data from previous test
	 * @return	array{paymentid:int,invoiceid:int,amount:float}
	 *
	 * @depends	testPaiementFetch
	 */
	public function testPaiementInvoiceSommePaiement($data)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$invoice = new Facture($db);
		$invoice->fetch($data['invoiceid']);
		$sum = (float) price2num($invoice->getSommePaiement(), 'MT');
		print __METHOD__ . " sommePaiement=" . $sum . "\n";

		$this->assertEquals($data['amount'], $sum, 'getSommePaiement does not match the payment amount');

		return $data;
	}

	/**
	 * Update the payment date and reference number.
	 *
	 * @param	array{paymentid:int,invoiceid:int,amount:float}	$data	Data from previous test
	 * @return	array{paymentid:int,invoiceid:int,amount:float}
	 *
	 * @depends	testPaiementInvoiceSommePaiement
	 */
	public function testPaiementUpdateDateAndNum($data)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$payment = new Paiement($db);
		$payment->fetch($data['paymentid']);

		// Both updaters return 0 on success
		$result = $payment->update_date(dol_now() - 86400);
		print __METHOD__ . " update_date=" . $result . "\n";
		$this->assertEquals(0, $result, 'Paiement::update_date failed');

		$result = $payment->update_num('CHK-TEST-001');
		print __METHOD__ . " update_num=" . $result . "\n";
		$this->assertEquals(0, $result, 'Paiement::update_num failed');

		return $data;
	}

	/**
	 * Delete the payment and check the invoice is no longer reported as paid.
	 *
	 * @param	array{paymentid:int,invoiceid:int,amount:float}	$data	Data from previous test
	 * @return	void
	 *
	 * @depends	testPaiementUpdateDateAndNum
	 */
	public function testPaiementDelete($data)
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$payment = new Paiement($db);
		$payment->fetch($data['paymentid']);
		$result = $payment->delete($user);
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertLessThan($result, 0, 'Paiement::delete failed: ' . $payment->error);

		// After deletion the invoice must report nothing paid anymore
		$invoice = new Facture($db);
		$invoice->fetch($data['invoiceid']);
		$sum = (float) price2num($invoice->getSommePaiement(), 'MT');
		$this->assertEquals(0, $sum, 'Invoice still reports a paid amount after payment deletion');
	}

	/**
	 * A payment in the invoice currency that settles its whole remaining amount closes the invoice,
	 * even when converting that amount gives one cent less than the sum of the converted lines.
	 *
	 * @return	void
	 */
	public function testPaiementCreateInInvoiceCurrencyClosesInvoice()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->FAC_FORCE_DATE_VALIDATION = 0;
		$conf->global->INVOICE_CHECK_POSTERIOR_DATE = 0;

		// The test needs the multicurrency module and a currency USD declared
		require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
		$savmodmulticurrency = $conf->modules['multicurrency'] ?? null;
		$conf->modules['multicurrency'] = 'multicurrency';
		if (!MultiCurrency::getIdFromCode($db, 'USD')) {
			$currency = new MultiCurrency($db);
			$currency->code = 'USD';
			$currency->name = 'US Dollar';
			$this->assertGreaterThan(0, $currency->create($user), 'MultiCurrency::create failed: '.implode(',', $currency->errors));
		}

		$rate = 1.0862009;
		$unitpriceininvoicecurrency = 100.10;

		$invoice = new Facture($db);
		$invoice->initAsSpecimen('nolines');
		$invoice->multicurrency_code = 'USD';
		$invoice->multicurrency_tx = $rate;
		$this->assertLessThan($invoice->create($user), 0, 'Facture::create failed: '.$invoice->error);
		for ($i = 0; $i < 2; $i++) {
			$result = $invoice->addline('Line in invoice currency', 0, 1, 0, 0, 0, 0, 0, '', '', 0, 0, 0, 'HT', 0, Facture::TYPE_STANDARD, -1, 0, '', 0, 0, null, 0, '', array(), 100, 0, null, $unitpriceininvoicecurrency);
			$this->assertLessThan($result, 0, 'Facture::addline failed: '.$invoice->error);
		}
		$this->assertLessThan($invoice->validate($user), 0, 'Facture::validate failed: '.$invoice->error);
		$invoice->fetch($invoice->id);

		$amountininvoicecurrency = (float) price2num($invoice->multicurrency_total_ttc, 'MT');
		$this->assertNotEquals((float) price2num($amountininvoicecurrency / $rate, 'MT'), (float) price2num($invoice->total_ttc, 'MT'), 'Test needs lines whose converted sum differs from the converted total');

		$payment = new Paiement($db);
		$payment->datepaye = dol_now();
		$payment->multicurrency_amounts = array($invoice->id => $amountininvoicecurrency);
		$payment->multicurrency_code = array($invoice->id => 'USD');
		$payment->multicurrency_tx = array($invoice->id => $rate);
		$payment->paiementcode = 'LIQ';
		$payment->paiementid = dol_getIdFromCode($db, $payment->paiementcode, 'c_paiement', 'code', 'id', 1);
		$paymentid = $payment->create($user, 1);
		$this->assertLessThan($paymentid, 0, 'Paiement::create failed: '.$payment->error);

		$invoice->fetch($invoice->id);
		$this->assertEquals((float) price2num($invoice->total_ttc, 'MT'), (float) price2num($payment->amount, 'MT'), 'Payment amount in company currency differs from the invoice total');
		$this->assertEquals(Facture::STATUS_CLOSED, $invoice->status, 'Invoice fully paid in its currency is not closed');

		if ($savmodmulticurrency === null) {
			unset($conf->modules['multicurrency']);
		} else {
			$conf->modules['multicurrency'] = $savmodmulticurrency;
		}
	}
}
