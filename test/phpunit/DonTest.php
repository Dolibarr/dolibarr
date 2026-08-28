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
 *      \file       test/phpunit/DonTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/don/class/don.class.php';
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
class DonTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('don')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modDon');
			self::assertEmpty($result['errors'], 'Failed to activate module don: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('don'), 'module don must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testDonCreate
	 *
	 * @return int
	 */
	public function testDonCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Don($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testDonFetch
	 *
	 * create() does not persist $this->status (initAsSpecimen() sets it to 1 in memory, but the
	 * fk_statut column defaults to 0/draft and is never written by create()) - fetch() is needed to
	 * see the real status.
	 *
	 * @param	int		$id		Id of object
	 * @return	Don
	 *
	 * @depends	testDonCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testDonFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Don($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertEqualsWithDelta(100.90, (float) $localobject->amount, 0.00001);
		$this->assertSame('Doe', $localobject->lastname);
		$this->assertSame('The Company', $localobject->societe);
		$this->assertEquals(Don::STATUS_DRAFT, $localobject->status, 'A freshly created donation must be draft');

		return $localobject;
	}

	/**
	 * testDonUpdate
	 *
	 * @param	Don	$localobject	Donation
	 * @return	Don
	 *
	 * @depends	testDonFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testDonUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->amount = 150.50;
		$localobject->town = 'Updated town after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEqualsWithDelta(150.50, (float) $localobject->amount, 0.00001);
		$this->assertSame('Updated town after update', $localobject->town);

		return $localobject;
	}

	/**
	 * testDonValidateAndPay
	 *
	 * @param	Don	$localobject	Donation
	 * @return	Don
	 *
	 * @depends	testDonUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testDonValidateAndPay($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setValid($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$localobject->fetch($localobject->id);
		$this->assertEquals(Don::STATUS_VALIDATED, $localobject->status);

		$result = $localobject->setPaid($localobject->id);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$localobject->fetch($localobject->id);
		$this->assertEquals(Don::STATUS_PAID, $localobject->status);
		$this->assertEquals(1, $localobject->paid);

		return $localobject;
	}

	/**
	 * testDonDelete
	 *
	 * @param	Don	$localobject	Donation
	 * @return	int
	 *
	 * @depends	testDonValidateAndPay
	 * The depends says test is run only if previous is ok
	 */
	public function testDonDelete($localobject)
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

	/**
	 * testDonCancel
	 *
	 * Self-contained (not chained via @depends): set_cancel() has no status precondition, unlike
	 * setValid()/setPaid() which each require the previous status.
	 *
	 * @return void
	 */
	public function testDonCancel()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Don($db);
		$localobject->initAsSpecimen();
		$id = $localobject->create($user);
		$this->assertGreaterThan(0, $id, $localobject->errorsToString());

		$result = $localobject->set_cancel($id);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());

		$localobject->fetch($id);
		$this->assertEquals(Don::STATUS_CANCELED, $localobject->status);

		$result = $localobject->delete($user);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
	}
}
