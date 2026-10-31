<?php
/* Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/PaymentLoanTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/loan/class/loan.class.php';
require_once dirname(__FILE__).'/../../htdocs/loan/class/paymentloan.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/loan.lib.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * Note: PaymentLoan::create() is inconsistent with fetch()/update(): it declares (and even cleans)
 * $fk_loan and $fk_typepayment properties like fetch()/update() use, but its actual INSERT statement
 * reads $chid and $paymenttype instead (both left uncleaned). This matches the real caller
 * (loan/payment/payment.php, which sets ->chid and ->paymenttype), so create() below does the same -
 * setting fk_loan/fk_typepayment instead would silently insert 0 for both columns.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PaymentLoanTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('loan')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modLoan');
			self::assertEmpty($result['errors'], 'Failed to activate module loan: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('loan'), 'module loan must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testPaymentLoanCreate
	 *
	 * @return array{0:int,1:int} Id of the payment and id of the loan it was made for
	 */
	public function testPaymentLoanCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$loan = new Loan($db);
		$loan->initAsSpecimen();
		$loan->charge_tva_tx = 10;
		$loan->charge_vat_src_code = 'TEST-VAT';
		$loanid = $loan->create($user);
		$this->assertGreaterThan(0, $loanid, $loan->errorsToString());

		// VAT rate of the loan's charge
		$loan = new Loan($db);
		$loan->fetch($loanid);
		$this->assertEqualsWithDelta(10.0, $loan->charge_tva_tx, 0.00001);
		$this->assertSame('TEST-VAT', $loan->charge_vat_src_code);

		$typepaymentid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$this->assertGreaterThan(0, $typepaymentid, 'VIR must be a known active payment type in this environment');

		$localobject = new PaymentLoan($db);
		$localobject->chid = $loanid; // see class docblock: create() uses chid, not fk_loan
		$localobject->paymenttype = $typepaymentid; // see class docblock: create() uses paymenttype, not fk_typepayment
		$localobject->datep = dol_now();
		$localobject->amount_capital = 100;
		$localobject->amount_insurance = 5;
		$localobject->amount_interest = 10;
		$localobject->tva_tx = 10;
		$localobject->vat_src_code = 'TEST-VAT';
		$localobject->total_tva = loanVatIncluded(5, 10);	// 0.45, included in amount_insurance
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." fk_loan=".$loanid."\n";
		return array($result, $loanid);
	}

	/**
	 * testPaymentLoanFetch
	 *
	 * @param	array{0:int,1:int}	$data	Id of the payment and id of the loan it was made for
	 * @return	PaymentLoan
	 *
	 * @depends	testPaymentLoanCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentLoanFetch($data)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($id, $loanid) = $data;

		$localobject = new PaymentLoan($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		// fetch() (unlike create()) does populate fk_loan/fk_typepayment from the DB columns
		$this->assertEquals($loanid, $localobject->fk_loan);
		$this->assertEqualsWithDelta(100.0, (float) $localobject->amount_capital, 0.00001);
		$this->assertEqualsWithDelta(5.0, (float) $localobject->amount_insurance, 0.00001);
		$this->assertEqualsWithDelta(10.0, (float) $localobject->amount_interest, 0.00001);
		$this->assertSame('VIR', $localobject->type_code);
		$this->assertEqualsWithDelta(10.0, $localobject->tva_tx, 0.00001);
		$this->assertSame('TEST-VAT', $localobject->vat_src_code);
		$this->assertEqualsWithDelta(0.45, $localobject->total_tva, 0.00001);

		return $localobject;
	}

	/**
	 * testPaymentLoanUpdate
	 *
	 * @param	PaymentLoan	$localobject	Loan payment
	 * @return	PaymentLoan
	 *
	 * @depends	testPaymentLoanFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentLoanUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// update() (unlike create()) does use fk_loan/fk_typepayment, both already set by fetch() above
		$localobject->amount_capital = 120;
		$localobject->note_private = 'New note private after update';
		$localobject->tva_tx = 0;
		$localobject->vat_src_code = '';
		$localobject->total_tva = 0;
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEqualsWithDelta(120.0, (float) $localobject->amount_capital, 0.00001);
		$this->assertSame('New note private after update', $localobject->note_private);
		$this->assertEqualsWithDelta(0.0, $localobject->tva_tx, 0.00001);
		$this->assertSame('', $localobject->vat_src_code);
		$this->assertEqualsWithDelta(0.0, $localobject->total_tva, 0.00001);

		return $localobject;
	}

	/**
	 * testLoanVatHelpers: VAT included in the charge, and the "rate (code)" keys of the VAT lists
	 *
	 * @return	void
	 */
	public function testLoanVatHelpers()
	{
		// The VAT is part of the charge amount, not added on top
		$this->assertEqualsWithDelta(0.17, loanVatIncluded(1.84, 10), 0.00001);
		$this->assertEqualsWithDelta(10.0, loanVatIncluded(110, 10), 0.00001);
		$this->assertEqualsWithDelta(0.0, loanVatIncluded(5, 0), 0.00001);

		// Same keys as Form::load_tva() with $mode = 1; no VAT is "0"
		$this->assertSame('10 (TEST-VAT)', loanVatKey(10, 'TEST-VAT'));
		$this->assertSame('8.5', loanVatKey(8.5, ''));
		$this->assertSame('0', loanVatKey(0, ''));
		$this->assertSame(array(10.0, 'TEST-VAT'), loanParseVatKey(loanVatKey(10, 'TEST-VAT')));
		$this->assertSame(array(8.5, ''), loanParseVatKey('8.5'));
		$this->assertSame(array(0.0, ''), loanParseVatKey(''));
	}

	/**
	 * testPaymentLoanDelete
	 *
	 * @param	PaymentLoan	$localobject	Loan payment
	 * @return	int
	 *
	 * @depends	testPaymentLoanUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentLoanDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$id = $localobject->id;
		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $result;
	}
}
