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
 *      \file       test/phpunit/SalaryTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/salaries/class/salary.class.php';
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
class SalaryTest extends CommonClassTest
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
	 * testSalaryCreate
	 *
	 * Salary has no initAsSpecimen(), so the object is built by hand. fk_user is the paid employee:
	 * reuse the test admin user, it is a real existing user.
	 *
	 * @return int
	 */
	public function testSalaryCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Salary($db);
		$localobject->fk_user = $user->id;
		$localobject->label = 'PHPUnit salary';
		$localobject->amount = 2000;
		$localobject->datesp = dol_now();
		$localobject->dateep = dol_now() + (3600 * 24 * 30);
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testSalaryFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Salary
	 *
	 * @depends	testSalaryCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testSalaryFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Salary($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertSame('PHPUnit salary', $localobject->label);
		$this->assertEqualsWithDelta(2000.0, (float) $localobject->amount, 0.00001);
		$this->assertEquals($user->id, $localobject->fk_user);
		$this->assertEquals(Salary::STATUS_UNPAID, $localobject->paye, 'A freshly created salary must be unpaid');

		return $localobject;
	}

	/**
	 * testSalaryUpdate
	 *
	 * @param	Salary	$localobject	Salary
	 * @return	Salary
	 *
	 * @depends	testSalaryFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testSalaryUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->label = 'Updated label after update';
		$localobject->amount = 2100;
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated label after update', $localobject->label);
		$this->assertEqualsWithDelta(2100.0, (float) $localobject->amount, 0.00001);

		return $localobject;
	}

	/**
	 * testSalaryDelete
	 *
	 * @param	Salary	$localobject	Salary
	 * @return	int
	 *
	 * @depends	testSalaryUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testSalaryDelete($localobject)
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
