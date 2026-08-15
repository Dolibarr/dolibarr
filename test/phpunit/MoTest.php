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
 *      \file       test/phpunit/MoTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/mrp/class/mo.class.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
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
class MoTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf, $dolibarr_main_db_pass, $mysoc, $user;

		if (!isModEnabled('mrp')) {
			// Defensive: in the full test suite (all classes run in one continuous process,
			// processIsolation=false), $db can become stale/closed by the time this class runs, for
			// reasons outside this test's control. modMrp depends on modBom, which itself depends on
			// modProduct, whose constructor queries the DB via Societe::useNPR() - and would crash on
			// a dead connection (see https://github.com/Dolibarr/dolibarr/issues/38068 and
			// https://github.com/Dolibarr/dolibarr/pull/39010 for related "mysqli object is already
			// closed" reports elsewhere in this codebase, on stricter PHP versions). Reconnect first.
			$stillconnected = false;
			try {
				$stillconnected = (bool) $db->query('SELECT 1');
			} catch (Throwable $e) {
				$stillconnected = false;
			}
			if (!$stillconnected) {
				// $conf->db->pass is deliberately unset by master.inc.php right after the initial
				// connection (to avoid the password lingering in memory) - $dolibarr_main_db_pass is
				// the one global left available for this exact kind of reconnect.
				$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $dolibarr_main_db_pass, $conf->db->name, (int) $conf->db->port);
			}
			// Always resync, even if $db itself did not need reconnecting above: $mysoc and $user were
			// built once at bootstrap (master.inc.php) and each stashed their own reference to $db in
			// their ->db property, which can go stale independently of the global $db variable (for
			// example if an earlier test class in the same continuous run already reconnected $db
			// without going through this same code) - Societe::useNPR(), called from
			// modProduct::__construct() during the dependency activation below, uses $mysoc->db, not the
			// global $db, and would still crash on a stale reference otherwise.
			$mysoc->db = $db;
			$user->db = $db;

			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modMrp');
			self::assertEmpty($result['errors'], 'Failed to activate module mrp: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('mrp'), 'module mrp must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testMoCreate
	 *
	 * Mo::create() needs a real product (fk_product): a random real catalog product cannot be used
	 * here, a kit/BOM product would be rejected by create() unless ALLOW_USE_KITS_INTO_BOM_AND_MO is
	 * set (see Mo::create()) - use a freshly created, plain (non-kit) specimen product instead.
	 *
	 * @return int
	 */
	public function testMoCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$product = new Product($db);
		$product->initAsSpecimen();
		$productid = $product->create($user);
		$this->assertGreaterThan(0, $productid, $product->errorsToString());

		$localobject = new Mo($db);
		$localobject->initAsSpecimen();
		// initAsSpecimen() sets a fixed ref ('ABCD1234'), but a real Mo is created with the '(PROV)'
		// placeholder so createCommon() assigns it a real provisional ref ("(PROVid)") - use that here
		// so the ref-renumbering on validate() tested below reflects real usage.
		$localobject->ref = '(PROV)';
		$localobject->fk_product = $productid;
		$localobject->qty = 5;
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." fk_product=".$productid."\n";

		// create() must have auto-created the "to produce" line for the finished product itself
		// (no BOM is set here, so there is nothing to consume)
		$localobject->fetch($result);
		$toproduce = $localobject->fetchLinesLinked('toproduce');
		$this->assertCount(1, $toproduce);
		$this->assertEquals($productid, $toproduce[0]['fk_product']);
		$this->assertEqualsWithDelta(5.0, (float) $toproduce[0]['qty'], 0.00001);
		$this->assertCount(0, $localobject->fetchLinesLinked('toconsume'));

		return $result;
	}

	/**
	 * testMoFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	Mo
	 *
	 * @depends	testMoCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testMoFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Mo($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertEqualsWithDelta(5.0, (float) $localobject->qty, 0.00001);
		$this->assertEquals(Mo::STATUS_DRAFT, $localobject->status);
		$this->assertMatchesRegularExpression('/^\(?PROV/i', (string) $localobject->ref, 'A not yet validated Mo must have a provisional ref');

		return $localobject;
	}

	/**
	 * testMoUpdate
	 *
	 * @param	Mo	$localobject	Mo
	 * @return	Mo
	 *
	 * @depends	testMoFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testMoUpdate($localobject)
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
	 * testMoValidate
	 *
	 * @param	Mo	$localobject	Mo
	 * @return	Mo
	 *
	 * @depends	testMoUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testMoValidate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$oldref = $localobject->ref;
		$result = $localobject->validate($user);

		$this->assertEquals(1, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result." ref=".$localobject->ref."\n";

		$this->assertEquals(Mo::STATUS_VALIDATED, $localobject->status);
		$this->assertNotEquals($oldref, $localobject->ref, 'validate() must replace the provisional ref with a definitive one');
		$this->assertNotRegExp('/^\(?PROV/i', $localobject->ref);

		return $localobject;
	}

	/**
	 * testMoCancel
	 *
	 * @param	Mo	$localobject	Mo
	 * @return	Mo
	 *
	 * @depends	testMoValidate
	 * The depends says test is run only if previous is ok
	 */
	public function testMoCancel($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->cancel($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEquals(Mo::STATUS_CANCELED, $localobject->status);

		return $localobject;
	}

	/**
	 * testMoReopen
	 *
	 * @param	Mo	$localobject	Mo
	 * @return	int
	 *
	 * @depends	testMoCancel
	 * The depends says test is run only if previous is ok
	 */
	public function testMoReopen($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->reopen($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEquals(Mo::STATUS_VALIDATED, $localobject->status);

		return $localobject->id;
	}

	/**
	 * testMoDelete
	 *
	 * @param	int		$id		Id of object
	 * @return	int
	 *
	 * @depends	testMoReopen
	 * The depends says test is run only if previous is ok
	 */
	public function testMoDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Mo($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $result;
	}
}
