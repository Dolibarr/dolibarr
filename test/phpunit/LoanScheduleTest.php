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
 *      \file       test/phpunit/LoanScheduleTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/loan/class/loan.class.php';
require_once dirname(__FILE__).'/../../htdocs/loan/class/loanschedule.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class LoanScheduleTest extends CommonClassTest
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
	 * testLoanScheduleCreate
	 *
	 * @return int
	 */
	public function testLoanScheduleCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$loan = new Loan($db);
		$loan->initAsSpecimen();
		$loanid = $loan->create($user);
		$this->assertGreaterThan(0, $loanid, $loan->errorsToString());

		$typepaymentid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$this->assertGreaterThan(0, $typepaymentid, 'VIR must be a known active payment type in this environment');

		$localobject = new LoanSchedule($db);
		$localobject->fk_loan = $loanid;
		$localobject->datep = dol_now();
		$localobject->amount_capital = 100;
		$localobject->amount_insurance = 5;
		$localobject->amount_interest = 10;
		$localobject->fk_typepayment = $typepaymentid;
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." fk_loan=".$loanid."\n";
		return $result;
	}

	/**
	 * testLoanScheduleFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	LoanSchedule
	 *
	 * @depends	testLoanScheduleCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testLoanScheduleFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new LoanSchedule($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertEqualsWithDelta(100.0, (float) $localobject->amount_capital, 0.00001);
		$this->assertEqualsWithDelta(5.0, (float) $localobject->amount_insurance, 0.00001);
		$this->assertEqualsWithDelta(10.0, (float) $localobject->amount_interest, 0.00001);
		$this->assertSame('VIR', $localobject->type_code);

		return $localobject;
	}

	/**
	 * testLoanScheduleUpdate
	 *
	 * @param	LoanSchedule	$localobject	Loan schedule line
	 * @return	LoanSchedule
	 *
	 * @depends	testLoanScheduleFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testLoanScheduleUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->amount_capital = 120;
		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEqualsWithDelta(120.0, (float) $localobject->amount_capital, 0.00001);
		$this->assertSame('New note private after update', $localobject->note_private);

		return $localobject;
	}

	/**
	 * testLoanScheduleCalcMonthlyPayments
	 *
	 * Pure calculation, no database access.
	 *
	 * @return void
	 */
	public function testLoanScheduleCalcMonthlyPayments()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new LoanSchedule($db);

		// With no rate, the monthly payment is simply the capital spread evenly over the term
		$this->assertEqualsWithDelta(100.0, (float) $localobject->calcMonthlyPayments(1200, 0, 12), 0.00001);

		// With a rate, standard amortization formula: capital=1200, annual rate=12% (0.01/month), 12 terms
		$this->assertEqualsWithDelta(106.6185464140, (float) $localobject->calcMonthlyPayments(1200, 0.12, 12), 0.0001);

		// No capital or no term: nothing to calculate
		$this->assertSame('', $localobject->calcMonthlyPayments(0, 0.12, 12));
		$this->assertSame('', $localobject->calcMonthlyPayments(1200, 0.12, 0));
	}

	/**
	 * testLoanScheduleDelete
	 *
	 * @param	LoanSchedule	$localobject	Loan schedule line
	 * @return	int
	 *
	 * @depends	testLoanScheduleUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testLoanScheduleDelete($localobject)
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
