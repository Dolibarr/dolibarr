<?php
/* Copyright (C) 2007-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    test/unit/BillOfMaterialsTest.php
 * \ingroup billofmaterials
 * \brief   PHPUnit test for BillOfMaterials class.
 */

namespace test\unit;

/**
 * Class BillOfMaterialsTest
 * @package Testbillofmaterials
 */
class BOMTest extends \PHPUnit\Framework\TestCase
{
	/**
	 * Global test setup
     * @return void
	 */
	public static function setUpBeforeClass()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Unit test setup
     * @return void
	 */
	protected function setUp()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Verify pre conditions
     * @return void
	 */
	protected function assertPreConditions()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * A sample test
     * @return bool
	 */
	public function testSomething()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		// TODO: test something
		$this->assertTrue(true);
	}

	/**
	 * Verify post conditions
     * @return void
	 */
	protected function assertPostConditions()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Unit test teardown
     * @return void
	 */
	protected function tearDown()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Global test teardown
     * @return void
	 */
	public static function tearDownAfterClass()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Unsuccessful test
	 *
	 * @param  Exception $e    Exception
     * @return void
	 * @throws Exception
	 */
	protected function onNotSuccessfulTest(Exception $e)
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		throw $e;
	}
}
