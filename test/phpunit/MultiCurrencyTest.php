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
 *      \file       test/phpunit/MultiCurrencyTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/multicurrency/class/multicurrency.class.php';
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
class MultiCurrencyTest extends CommonClassTest
{
	/**
	 * ISO 4217 reserves code XTS for testing purposes, so it cannot collide with a real currency.
	 *
	 * @var string
	 */
	const TEST_CODE = 'XTS';

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('multicurrency')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modMultiCurrency');
			self::assertEmpty($result['errors'], 'Failed to activate module multicurrency: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('multicurrency'), 'module multicurrency must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testMultiCurrencyCreate
	 *
	 * MultiCurrency has no initAsSpecimen(), so the object is built by hand.
	 *
	 * @return int
	 */
	public function testMultiCurrencyCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new MultiCurrency($db);
		$localobject->code = self::TEST_CODE;
		$localobject->name = 'PHPUnit test currency';
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result."\n";

		// create() must refuse a duplicate code
		$duplicate = new MultiCurrency($db);
		$duplicate->code = self::TEST_CODE;
		$duplicate->name = 'Another name';
		$duplicateresult = $duplicate->create($user);
		$this->assertLessThan(0, $duplicateresult, 'create() must fail on a duplicate currency code');
		$this->assertTrue($localobject->checkCodeAlreadyExists(self::TEST_CODE));

		return $result;
	}

	/**
	 * testMultiCurrencyFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	MultiCurrency
	 *
	 * @depends	testMultiCurrencyCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testMultiCurrencyFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new MultiCurrency($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertSame(self::TEST_CODE, $localobject->code);
		$this->assertSame('PHPUnit test currency', $localobject->name);

		// Fetching by code must find the same record
		$bycode = new MultiCurrency($db);
		$this->assertGreaterThan(0, $bycode->fetch(0, self::TEST_CODE));
		$this->assertEquals($id, $bycode->id);

		return $localobject;
	}

	/**
	 * testMultiCurrencyAddRate
	 *
	 * addRate() (and its updateRate() alias) always append a new rate row instead of modifying an
	 * existing one: getRate() must then resolve to the most recently added one.
	 *
	 * @param	MultiCurrency	$localobject	Currency
	 * @return	MultiCurrency
	 *
	 * @depends	testMultiCurrencyFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testMultiCurrencyAddRate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = $localobject->addRate(1.25);
		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		$this->assertEqualsWithDelta(1.25, (float) $localobject->rate->rate, 0.00001);

		// updateRate() is just an alias of addRate(): it appends a new rate row instead of modifying
		// the existing one. Give it an explicit date_sync 1h in the future instead of relying on
		// updateRate()'s implicit "now" timestamp, which could collide with the first rate's
		// timestamp at second-level precision if both calls land in the same second.
		$laterrate = new CurrencyRate($db);
		$laterrate->rate = 1.30;
		$laterrate->date_sync = dol_now() + 3600;
		$result = $laterrate->create($user, $localobject->id);
		$this->assertGreaterThan(0, $result, $laterrate->errorsToString());

		$this->assertGreaterThan(0, $localobject->fetchAllCurrencyRate());
		$this->assertCount(2, $localobject->rates);

		$this->assertGreaterThan(0, $localobject->getRate());
		$this->assertEqualsWithDelta(1.30, (float) $localobject->rate->rate, 0.00001, 'getRate() must resolve to the most recently added rate');

		return $localobject;
	}

	/**
	 * testMultiCurrencyStaticHelpers
	 *
	 * @param	MultiCurrency	$localobject	Currency
	 * @return	MultiCurrency
	 *
	 * @depends	testMultiCurrencyAddRate
	 * The depends says test is run only if previous is ok
	 */
	public function testMultiCurrencyStaticHelpers($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertEquals($localobject->id, MultiCurrency::getIdFromCode($db, self::TEST_CODE));
		$this->assertEquals(0, MultiCurrency::getIdFromCode($db, 'NOTACURRENCYCODE'));

		list($id, $rate) = MultiCurrency::getIdAndTxFromCode($db, self::TEST_CODE);
		$this->assertEquals($localobject->id, $id);
		$this->assertEqualsWithDelta(1.30, (float) $rate, 0.00001);

		list($idnotfound, $ratenotfound) = MultiCurrency::getIdAndTxFromCode($db, 'NOTACURRENCYCODE');
		$this->assertEquals(0, $idnotfound);
		$this->assertEqualsWithDelta(1.0, (float) $ratenotfound, 0.00001, 'getIdAndTxFromCode() must fall back to a neutral 1:1 rate when the code is unknown');

		// getAmountConversionFromInvoiceRate() with an explicit rate does not need a real invoice row
		$this->assertEqualsWithDelta(200.0, (float) MultiCurrency::getAmountConversionFromInvoiceRate(0, 100, 'dolibarr', 'facture', 2.0), 0.00001, '100 in the foreign currency converted at rate 2 must be 200 in the Dolibarr currency');
		$this->assertEqualsWithDelta(50.0, (float) MultiCurrency::getAmountConversionFromInvoiceRate(0, 100, 'fromdolibarr', 'facture', 2.0), 0.00001, '100 in the Dolibarr currency converted at rate 2 must be 50 in the foreign currency');

		// Test with MULTICURRENCY_USE_RATE_DIRECT
		$conf->global->MULTICURRENCY_USE_RATE_DIRECT = '1';
		$this->assertEqualsWithDelta(50.0, (float) MultiCurrency::getAmountConversionFromInvoiceRate(0, 100, 'dolibarr', 'facture', 2.0), 0.00001, '100 local converted to foreign at direct rate 2 must be 50');
		$this->assertEqualsWithDelta(200.0, (float) MultiCurrency::getAmountConversionFromInvoiceRate(0, 100, 'fromdolibarr', 'facture', 2.0), 0.00001, '100 foreign converted to local at direct rate 2 must be 200');
		$conf->global->MULTICURRENCY_USE_RATE_DIRECT = '0';
		
		return $localobject;
	}

	/**
	 * testMultiCurrencyUpdate
	 *
	 * @param	MultiCurrency	$localobject	Currency
	 * @return	MultiCurrency
	 *
	 * @depends	testMultiCurrencyStaticHelpers
	 * The depends says test is run only if previous is ok
	 */
	public function testMultiCurrencyUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->name = 'Updated name after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated name after update', $localobject->name);

		return $localobject;
	}

	/**
	 * testMultiCurrencyDelete
	 *
	 * @param	MultiCurrency	$localobject	Currency
	 * @return	int
	 *
	 * @depends	testMultiCurrencyUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testMultiCurrencyDelete($localobject)
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

		$checkobject = new MultiCurrency($db);
		$this->assertSame(0, $checkobject->fetch($id), 'Currency must no longer be found after delete');

		return $result;
	}
}
