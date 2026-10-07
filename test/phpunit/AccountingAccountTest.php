<?php
/* Copyright (C) 2018 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/AccountingAccountTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/accountancy/class/accountingaccount.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/accounting.lib.php';
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
class AccountingAccountTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('accounting'), " module double party accounting must be enabled");
		parent::setUpBeforeClass();
	}

	/**
	 * testAccountingAccountCreate
	 *
	 * @return  int		Id of created object
	 */
	public function testAccountingAccountCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new AccountingAccount($db);
		$localobject->fk_pcg_version = 'PCG99-ABREGE';
		$localobject->account_category = 0;
		$localobject->pcg_type = 'XXXXX';
		$localobject->pcg_subtype = 'XXXXX';
		$localobject->account_number = '411123456';
		$localobject->account_parent = 0;
		$localobject->label = 'Account specimen';
		$localobject->active = 0;
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testAccountingAccountFetch
	 *
	 * @param   int $id     Id accounting account
	 * @return  AccountingAccount
	 *
	 * @depends	testAccountingAccountCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testAccountingAccountFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new AccountingAccount($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testAccountingAccountUpdate
	 *
	 * @param	AccountingAccount		$localobject	AccountingAccount
	 * @return	int							ID accounting account
	 *
	 * @depends	testAccountingAccountFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testAccountingAccountUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->label = 'New label';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject->id;
	}

	/**
	 * testAccountingAccountDelete
	 *
	 * @param   int $id         Id of accounting account
	 * @return  int				Result of delete
	 *
	 * @depends testAccountingAccountUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testAccountingAccountDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new AccountingAccount($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testGetCurrentPeriodOfFiscalYear
	 *
	 * @return  int				Result of delete
	 *
	 * @depends testAccountingAccountDelete
	 * The depends says test is run only if previous is ok
	 */
	public function testGetCurrentPeriodOfFiscalYear()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;


		$result = getCurrentPeriodOfFiscalYear($db, $conf, null, 'tzserver');
		var_dump($result);

		print __METHOD__." date_start = ".dol_print_date($result['date_start'], 'dayhour', 'gmt')."\n";
		print __METHOD__." date_end   = ".dol_print_date($result['date_end'], 'dayhour', 'gmt')."\n";

		$this->assertArrayHasKey('date_start', $result);
		$this->assertArrayHasKey('date_end', $result);


		$result = getCurrentPeriodOfFiscalYear($db, $conf, null, 'gmt');
		var_dump($result);

		print __METHOD__." date_start = ".dol_print_date($result['date_start'], 'dayhour', 'gmt')."\n";
		print __METHOD__." date_end   = ".dol_print_date($result['date_end'], 'dayhour', 'gmt')."\n";

		$this->assertArrayHasKey('date_start', $result);
		$this->assertArrayHasKey('date_end', $result);

		return $result;
	}

	/**
	 * testAccountingSplitAmountPerYear
	 *
	 * @return	void
	 */
	public function testAccountingSplitAmountPerYear()
	{
		$this->assertSame(array(), accountingSplitAmountPerYear(1200, '2026-02-01', '2026-11-30 12:00:00', 2026));

		$result = accountingSplitAmountPerYear(1200, '2026-07-01', '2027-06-30', 2026);
		$this->assertSame(array(
			2026 => array('days' => 184, 'percent' => 50.41, 'amount' => 604.93),
			2027 => array('days' => 181, 'percent' => 49.59, 'amount' => 595.07),
		), $result);

		$result = accountingSplitAmountPerYear(1000, '2025-07-01', '2027-06-30', 2026);
		$this->assertSame(array(
			2025 => array('days' => 184, 'percent' => 25.21, 'amount' => 252.05),
			2026 => array('days' => 365, 'percent' => 50.0, 'amount' => 500.0),
			2027 => array('days' => 181, 'percent' => 24.79, 'amount' => 247.95),
		), $result);

		$result = accountingSplitAmountPerYear(100, '2026-12-31', '2028-01-01', 2026);
		$this->assertSame(array(2026, 2027, 2028), array_keys($result));
		$this->assertEquals(100, array_sum(array_column($result, 'amount')));
	}

	/**
	 * testAccountingAccountIsExcludedFromSplit
	 *
	 * @return	void
	 */
	public function testAccountingAccountIsExcludedFromSplit()
	{
		global $conf;
		$conf = $this->savconf;

		$conf->global->ACCOUNTING_SPLIT_AMOUNT_EXCLUDED_ACCOUNTS = '';
		$this->assertFalse(accountingAccountIsExcludedFromSplit('1500'));

		$conf->global->ACCOUNTING_SPLIT_AMOUNT_EXCLUDED_ACCOUNTS = '1,20';
		$this->assertTrue(accountingAccountIsExcludedFromSplit('1'));
		$this->assertTrue(accountingAccountIsExcludedFromSplit('1500'));
		$this->assertTrue(accountingAccountIsExcludedFromSplit('2010'));
		$this->assertFalse(accountingAccountIsExcludedFromSplit('2300'));
		$this->assertFalse(accountingAccountIsExcludedFromSplit('3400'));
	}
}
