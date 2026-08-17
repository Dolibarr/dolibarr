<?php
/* Copyright (C) 2026		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
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
 *      \file       test/phpunit/MultiCurrencyRateDirectTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test: MultiCurrency::addRate() pre-fills the inverse rate_direct (issue #32379)
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
 * Class for PHPUnit tests of the rate_direct auto-fill
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 *
 * @phan-file-suppress PhanUndeclaredExtendedClass
 * @phan-file-suppress PhanUndeclaredMethod
 */
class MultiCurrencyRateDirectTest extends CommonClassTest
{
	/**
	 * addRate() stores the inverse rate (1 / rate) in rate_direct so the direct-rate display is meaningful.
	 *
	 * @return void
	 */
	public function testAddRateFillsInverseRateDirect()
	{
		global $db, $user;

		// Throwaway currency with an unlikely code (rolled back by the test transaction)
		$code = 'Z'.sprintf('%02d', mt_rand(0, 99));
		$mc = new MultiCurrency($db);
		$mc->code = $code;
		$mc->name = 'Rate direct test '.$code;
		$id = $mc->create($user);
		$this->assertGreaterThan(0, $id, 'MultiCurrency creation failed: '.implode(',', $mc->errors));

		$res = $mc->addRate(2.0);
		$this->assertSame(1, $res, 'addRate must succeed');

		// Re-fetch the latest rate from the database to assert the stored value
		$reloaded = new MultiCurrency($db);
		$reloaded->fetch($id);
		$this->assertSame(1, $reloaded->getRate(), 'getRate must find the rate');
		$this->assertEquals(2.0, (float) $reloaded->rate->rate, 'Stored rate must be 2.0');
		$this->assertEquals(0.5, (float) $reloaded->rate->rate_direct, 'rate_direct must be 1 / rate = 0.5');
	}
}
