<?php
/* Copyright (C) 2026		Michael Wallis			<mh.wallis@gmail.com>
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
 *      \file       test/phpunit/AccountancyCategoryTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/accountancy/class/accountancycategory.class.php';

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
class AccountancyCategoryTest extends CommonClassTest
{
	/**
	 * testIsValidAccountRange
	 *
	 * @return	void
	 */
	public function testIsValidAccountRange()
	{
		$this->assertTrue(AccountancyCategory::isValidAccountRange('4'));
		$this->assertTrue(AccountancyCategory::isValidAccountRange('4,!4900'));
		$this->assertTrue(AccountancyCategory::isValidAccountRange('6000, 6020, 6050'));
		$this->assertTrue(AccountancyCategory::isValidAccountRange('7xxxxx'));
		$this->assertTrue(AccountancyCategory::isValidAccountRange('411CL'));

		$this->assertFalse(AccountancyCategory::isValidAccountRange(''));
		$this->assertFalse(AccountancyCategory::isValidAccountRange('Example: 7xxxxx'));
		$this->assertFalse(AccountancyCategory::isValidAccountRange('6000-6099'));
		$this->assertFalse(AccountancyCategory::isValidAccountRange('Wages'));
		$this->assertFalse(AccountancyCategory::isValidAccountRange('!4900'), 'A range with only left out prefixes selects nothing');
		$this->assertFalse(AccountancyCategory::isValidAccountRange('4,,5'));
	}

	/**
	 * testAccountRangeMatch
	 *
	 * @return	void
	 */
	public function testAccountRangeMatch()
	{
		$this->assertSame(1, AccountancyCategory::accountRangeMatch('4', '4010'));
		$this->assertSame(-1, AccountancyCategory::accountRangeMatch('4', '5010'));

		$this->assertSame(1, AccountancyCategory::accountRangeMatch('4,!4900', '4010'));
		$this->assertSame(-1, AccountancyCategory::accountRangeMatch('4,!4900', '4900'));
		$this->assertSame(-1, AccountancyCategory::accountRangeMatch('4,!4900', '49001'));
		$this->assertSame(-1, AccountancyCategory::accountRangeMatch('!4900,4', '4900'), 'Order of the prefixes does not matter');

		$this->assertSame(2, AccountancyCategory::accountRangeMatch('4,41', '4110'), 'Longest prefix is returned');
		$this->assertSame(4, AccountancyCategory::accountRangeMatch('6000, 6020', '6020'), 'Spaces around commas are ignored');
		$this->assertSame(1, AccountancyCategory::accountRangeMatch('7xxxxx', '706000'), 'Trailing x are ignored');
	}
}
