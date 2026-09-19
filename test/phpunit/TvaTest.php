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
 *      \file       test/phpunit/TvaTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/tva/class/tva.class.php';
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
 * Note: Tva::addPayment() is not covered here. It inserts its "payment" as another row of the
 * tva table itself, without setting any fk_tva link, while Tva::getSommePaiement() (and the rest
 * of the payment reporting) reads from the dedicated llx_payment_vat table via fk_tva - the two are
 * not connected. The dedicated PaymentVAT class (compta/tva/class/paymentvat.class.php), which
 * does insert into llx_payment_vat with fk_tva set, looks like the actual modern way to record a
 * payment against a Tva declaration.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class TvaTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('tax')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modTax');
			self::assertEmpty($result['errors'], 'Failed to activate module tax: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('tax'), 'module tax must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testTvaCreate
	 *
	 * @return int
	 */
	public function testTvaCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Tva($db);
		$localobject->initAsSpecimen();
		$localobject->label = 'PHPUnit VAT declaration';
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testTvaFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Tva
	 *
	 * @depends	testTvaCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testTvaFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Tva($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertSame('PHPUnit VAT declaration', $localobject->label);
		$this->assertEqualsWithDelta(100.0, (float) $localobject->amount, 0.00001);
		$this->assertEquals(Tva::STATUS_UNPAID, $localobject->paye, 'A freshly created VAT declaration must be unpaid');

		return $localobject;
	}

	/**
	 * testTvaUpdate
	 *
	 * @param	Tva	$localobject	VAT declaration
	 * @return	Tva
	 *
	 * @depends	testTvaFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testTvaUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->label = 'Updated label after update';
		$localobject->amount = 150.75;
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated label after update', $localobject->label);
		$this->assertEqualsWithDelta(150.75, (float) $localobject->amount, 0.00001);

		return $localobject;
	}

	/**
	 * testTvaSetPaidUnpaid
	 *
	 * @param	Tva	$localobject	VAT declaration
	 * @return	Tva
	 *
	 * @depends	testTvaUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testTvaSetPaidUnpaid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setPaid($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$localobject->fetch($localobject->id);
		$this->assertEquals(Tva::STATUS_PAID, $localobject->paye);

		$result = $localobject->setUnpaid($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$localobject->fetch($localobject->id);
		$this->assertEquals(Tva::STATUS_UNPAID, $localobject->paye);

		return $localobject;
	}

	/**
	 * testTvaDelete
	 *
	 * @param	Tva	$localobject	VAT declaration
	 * @return	int
	 *
	 * @depends	testTvaSetPaidUnpaid
	 * The depends says test is run only if previous is ok
	 */
	public function testTvaDelete($localobject)
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
