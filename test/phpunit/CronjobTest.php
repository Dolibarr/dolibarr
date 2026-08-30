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
 *      \file       test/phpunit/CronjobTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/cron/class/cronjob.class.php';
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
 * This only covers the CRUD of the job definition itself. run_jobs()/reprogram_jobs() actually
 * execute the configured command/method, which is out of scope for a unit test: the job created
 * here is left disabled (STATUS_DISABLED) so nothing in the environment could pick it up and run it.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CronjobTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('cron')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modCron');
			self::assertEmpty($result['errors'], 'Failed to activate module cron: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('cron'), 'module cron must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testCronjobCreate
	 *
	 * @return int
	 */
	public function testCronjobCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Cronjob($db);
		$localobject->initAsSpecimen();
		// initAsSpecimen() leaves label/jobtype/datenextrun empty, all required by create()
		$localobject->label = 'PHPUnit cron job';
		$localobject->jobtype = 'method';
		$localobject->classesname = '/user/class/user.class.php';
		$localobject->objectname = 'User';
		$localobject->methodename = 'fetch';
		$localobject->params = '1';
		$localobject->datenextrun = dol_now();
		$localobject->status = Cronjob::STATUS_DISABLED; // never executed by anything in this environment
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testCronjobFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Cronjob
	 *
	 * @depends	testCronjobCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testCronjobFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Cronjob($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertSame('PHPUnit cron job', $localobject->label);
		$this->assertSame('method', $localobject->jobtype);
		$this->assertSame('User', $localobject->objectname);
		$this->assertSame('fetch', $localobject->methodename);
		$this->assertEquals(Cronjob::STATUS_DISABLED, $localobject->status);

		return $localobject;
	}

	/**
	 * testCronjobUpdate
	 *
	 * @param	Cronjob	$localobject	Cron job
	 * @return	Cronjob
	 *
	 * @depends	testCronjobFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testCronjobUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->label = 'Updated label after update';
		$localobject->priority = 5;
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated label after update', $localobject->label);
		$this->assertEquals(5, $localobject->priority);

		return $localobject;
	}

	/**
	 * testCronjobDelete
	 *
	 * @param	Cronjob	$localobject	Cron job
	 * @return	int
	 *
	 * @depends	testCronjobUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testCronjobDelete($localobject)
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
