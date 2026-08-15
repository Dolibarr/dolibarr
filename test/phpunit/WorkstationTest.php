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
 *      \file       test/phpunit/WorkstationTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/workstation/class/workstation.class.php';
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
class WorkstationTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('workstation')) {
			// activateModule() below transitively instantiates modProduct (modWorkstation depends on modMrp,
			// which depends on modBom, which depends on modProduct), whose constructor queries the DB via
			// Societe::useNPR() - make sure $db/$mysoc/$user are not stale before that (see
			// CommonClassTest::ensureDbIsConnected() for why this is needed).
			self::ensureDbIsConnected();

			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modWorkstation');
			self::assertEmpty($result['errors'], 'Failed to activate module workstation: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('workstation'), 'module workstation must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testWorkstationCreate
	 *
	 * @return int
	 */
	public function testWorkstationCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Workstation($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testWorkstationFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Workstation
	 *
	 * @depends	testWorkstationCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testWorkstationFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Workstation($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";

		// Values set by Workstation::initAsSpecimen() (-> initAsSpecimenCommon())
		$this->assertSame('This is label', $localobject->label);
		$this->assertSame('Public note', $localobject->note_public);
		$this->assertSame('Private note', $localobject->note_private);
		$this->assertEquals(Workstation::STATUS_ENABLED, $localobject->status);

		return $localobject;
	}

	/**
	 * testWorkstationUpdate
	 *
	 * @param	Workstation		$localobject	Workstation
	 * @return	Workstation
	 *
	 * @depends	testWorkstationFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testWorkstationUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->label = 'Updated label after update';
		$localobject->note_private = 'New note private after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated label after update', $localobject->label);
		$this->assertSame('New note private after update', $localobject->note_private);

		return $localobject;
	}

	/**
	 * testWorkstationSetStatus
	 *
	 * @param	Workstation		$localobject	Workstation
	 * @return	int								Id of object
	 *
	 * @depends	testWorkstationUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testWorkstationSetStatus($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->setStatusCommon($user, Workstation::STATUS_DISABLED);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEquals(Workstation::STATUS_DISABLED, $localobject->status);

		return $localobject->id;
	}

	/**
	 * testWorkstationDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	int
	 *
	 * @depends	testWorkstationSetStatus
	 * The depends says test is run only if previous is ok
	 */
	public function testWorkstationDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Workstation($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $result;
	}
}
