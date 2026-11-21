<?php
/* Copyright (C) 2026 Umar Farooq <umarpak995@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file       test/phpunit/CashControlReportTest.php
 * \ingroup    test
 * \brief      PHPUnit test for CashControl local tax display logic (Issue #36300).
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';

/**
 * Class CashControlReportTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class CashControlReportTest extends CommonClassTest
{
	/**
	 * Test that useLocalTax distinguishes between local tax 1 and local tax 2.
	 *
	 * @return void
	 */
	public function testUseLocalTaxDistinction()
	{
		global $db;

		$soc = new Societe($db);
		$soc->country_code = 'ES';

		// Enable LT1 only
		$soc->localtax1_assuj = '1';
		$soc->localtax2_assuj = '0';
		$this->assertEquals(1, $soc->useLocalTax(1));
		$this->assertEquals(0, $soc->useLocalTax(2));

		// Enable LT2 only
		$soc->localtax1_assuj = '0';
		$soc->localtax2_assuj = '1';
		$this->assertEquals(0, $soc->useLocalTax(1));
		$this->assertEquals(1, $soc->useLocalTax(2));

		// Enable both
		$soc->localtax1_assuj = '1';
		$soc->localtax2_assuj = '1';
		$this->assertEquals(1, $soc->useLocalTax(1));
		$this->assertEquals(1, $soc->useLocalTax(2));
	}
}
