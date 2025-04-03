<?php
/*
 * Copyright (C) 2024 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *
 * \file       test/phpunit/AccountancySystemTest.php
 * \ingroup    test
 * \brief      PHPUnit test
 *	\remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/accountancy/class/accountancysystem.class.php';
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
class AccountancySystemTest extends CommonClassTest
{
	/**
	 * Setup some global objects before the test.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		parent::setUpBeforeClass();
		global $conf, $user,  $mysoc;
		global $db;

		$soc = new Societe($db);
		$soc->name = "AccountancySystem Unittest";
		$socid = $soc->create($user);
		$mysoc = $soc;

		/* Errors are caught in later tests. */
		if ($socid <= 0)
			return;
	}

	/**
	 * testAccountancySystemCreate
	 *
	 * @return int		the ID of the created object
	 */
	public function testAccountancySystemCreate(): int
	{
		global $user, $db, $mysoc;

		$this->assertLessThan($mysoc->id, 0, "Cannot create Societe: " . $mysoc->errorsToString());

		$accountancySystem = new AccountancySystem($db);
		$accountancySystem->pcg_version = 'PCG99-CUSTOMTEST';
		$result = $accountancySystem->create($user);

		print __METHOD__." result=".$result." id=".$accountancySystem->id."\n";
		$this->assertLessThan($result, 0, 'Cannot create accountancySystem:' . $accountancySystem->errorsToString());

		return $accountancySystem->id;
	}

	/**
	 * testAccountancySystemFetch
	 *
	 * @param	int		$id			 Id of accountancySystem entry
	 * @return	AccountancySystem    AccountancySystem record object
	 *
	 * @depends	testAccountancySystemCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testAccountancySystemFetch($id)
	{
		global $db;

		$accountancySystem = new AccountancySystem($db);
		$result = $accountancySystem->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		$this->assertEquals($accountancySystem->pcg_version, 'PCG99-CUSTOMTEST');
		$this->assertEquals($accountancySystem->ref, $accountancySystem->pcg_version);

		return $accountancySystem;
	}
}
