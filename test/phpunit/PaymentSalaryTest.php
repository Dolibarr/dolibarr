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
 *      \file       test/phpunit/PaymentSalaryTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/salaries/class/salary.class.php';
require_once dirname(__FILE__).'/../../htdocs/salaries/class/paymentsalary.class.php';
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
 * Unlike Tva::addPayment()/getSommePaiement() (see TvaTest.php), PaymentSalary::create() and
 * Salary::getSommePaiement() do agree on the same table/link (payment_salary.fk_salary), so a
 * payment that fully covers a salary really is reflected back on the Salary once fetched.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PaymentSalaryTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('salaries')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modSalaries');
			self::assertEmpty($result['errors'], 'Failed to activate module salaries: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('salaries'), 'module salaries must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testPaymentSalaryCreate
	 *
	 * Pay a freshly created salary in full, with closepaidcontrib=1: the salary must come back paid.
	 *
	 * @return int Id of the payment
	 */
	public function testPaymentSalaryCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$salary = new Salary($db);
		$salary->fk_user = $user->id;
		$salary->label = 'PHPUnit salary to pay';
		$salary->amount = 100;
		$salary->datesp = dol_now();
		$salary->dateep = dol_now() + (3600 * 24 * 30);
		$salaryid = $salary->create($user);
		$this->assertGreaterThan(0, $salaryid, $salary->errorsToString());

		$typepaymentid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$this->assertGreaterThan(0, $typepaymentid, 'VIR must be a known active payment type in this environment');

		$localobject = new PaymentSalary($db);
		$localobject->datep = dol_now();
		$localobject->fk_typepayment = $typepaymentid;
		$localobject->amounts = array($salaryid => 100); // keyed by fk_salary, like Paiement::amounts for invoices
		$result = $localobject->create($user, 1); // closepaidcontrib=1: close the salary if fully paid

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." fk_salary=".$salaryid."\n";

		$salary->fetch($salaryid);
		$this->assertEquals(Salary::STATUS_PAID, $salary->paye, 'A salary paid in full with closepaidcontrib=1 must be marked paid');
		$this->assertEqualsWithDelta(100.0, (float) $salary->getSommePaiement(), 0.00001);

		return $result;
	}

	/**
	 * testPaymentSalaryFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	PaymentSalary
	 *
	 * @depends	testPaymentSalaryCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentSalaryFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new PaymentSalary($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertEqualsWithDelta(100.0, (float) $localobject->amount, 0.00001);
		$this->assertSame('VIR', $localobject->type_code);

		return $localobject;
	}

	/**
	 * testPaymentSalaryUpdate
	 *
	 * @param	PaymentSalary	$localobject	Salary payment
	 * @return	PaymentSalary
	 *
	 * @depends	testPaymentSalaryFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentSalaryUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Note: update() writes datep from $this->datepaye (a deprecated alias), not from $this->datep,
		// and the note column from $this->note, not $this->note_private (even though fetch() populates
		// both note and note_private from that same column) - this intentionally only touches
		// amount/note here to avoid those traps.
		$localobject->amount = 120;
		$localobject->note = 'New note after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEqualsWithDelta(120.0, (float) $localobject->amount, 0.00001);
		$this->assertSame('New note after update', $localobject->note_private);

		return $localobject;
	}

	/**
	 * testPaymentSalaryDelete
	 *
	 * @param	PaymentSalary	$localobject	Salary payment
	 * @return	int
	 *
	 * @depends	testPaymentSalaryUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentSalaryDelete($localobject)
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
