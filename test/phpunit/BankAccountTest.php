<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/BankAccounrTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/bank.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class BankAccountTest extends CommonClassTest
{
	/**
	 * testBankAccountCreate
	 *
	 * @return  int
	 */
	public function testBankAccountCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Account($db);
		$localobject->initAsSpecimen();
		$localobject->date_solde = dol_now();
		$result = $localobject->create($user);

		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $result;
	}

	/**
	 * testBankAccountFetch
	 *
	 * @param   int $id     Id of contract
	 * @return  int
	 *
	 * @depends testBankAccountCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testBankAccountFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Account($db);
		$result = $localobject->fetch($id);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject;
	}

	/**
	 * testBankAccountOther
	 *
	 * @param   Account  $localobject    Account
	 * @return  int
	 *
	 * @depends testBankAccountFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testBankAccountOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		$localobject->info($localobject->id);

		$result = $localobject->needIBAN();
		//print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertEquals(1, $result);

		// Test checkIbanForAccount for FR account
		$result = checkIbanForAccount($localobject);
		print __METHOD__." checkIbanForAccount(".$localobject->iban.") = ".$result."\n";
		$this->assertTrue($result);

		// Test checkIbanForAccount for CI account
		$localobject2 = new Account($db);
		$localobject2->country = 'CI';
		$localobject2->iban = 'CI77A12312341234123412341234';
		$result = checkIbanForAccount($localobject2);
		print __METHOD__." checkIbanForAccount(".$localobject2->iban.") = ".$result."\n";
		$this->assertTrue($result);

		return $localobject;
	}

	/**
	 * testCheckSwiftForAccount
	 *
	 * @param   Account  $localobject    Account
	 * @return  int
	 *
	 * @depends testBankAccountOther
	 * The depends says test is run only if previous is ok
	 */
	public function testCheckSwiftForAccount($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->bic = 'PSSTFRPPMARBIDON';

		$result = checkSwiftForAccount($localobject);
		print __METHOD__." checkSwiftForAccount ".$localobject->bic." = ".$result."\n";
		$this->assertFalse($result);


		$localobject->bic = 'PSSTFRPPMAR';

		$result = checkSwiftForAccount($localobject);
		print __METHOD__." checkSwiftForAccount ".$localobject->bic." = ".$result."\n";
		$this->assertTrue($result);

		return $localobject->id;
	}

	/**
	 * testBankAccountDelete
	 *
	 * @param   int $id     Id of contract
	 * @return  int
	 *
	 * @depends testCheckSwiftForAccount
	 * The depends says test is run only if previous is ok
	 */
	public function testBankAccountDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Account($db);
		$result = $localobject->fetch($id);
		$result = $localobject->delete($user);

		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}
}
