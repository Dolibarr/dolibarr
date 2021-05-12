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
 * \file    test/unit/MyObjectTest.php
 * \ingroup mymodule
 * \brief   PHPUnit test for MyObject class.
 */

namespace test\unit;

/**
 * Class MyObjectTest
 * @package Testmymodule
 */
class MyObjectTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * Global test setup
<<<<<<< HEAD
=======
     * @return void
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 */
	public static function setUpBeforeClass()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Unit test setup
<<<<<<< HEAD
=======
     * @return void
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 */
	protected function setUp()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Verify pre conditions
<<<<<<< HEAD
=======
     * @return void
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 */
	protected function assertPreConditions()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * A sample test
<<<<<<< HEAD
=======
     * @return bool
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 */
	public function testSomething()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		// TODO: test something
		$this->assertTrue(true);
	}

	/**
	 * Verify post conditions
<<<<<<< HEAD
=======
     * @return void
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 */
	protected function assertPostConditions()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Unit test teardown
<<<<<<< HEAD
=======
     * @return void
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 */
	protected function tearDown()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Global test teardown
<<<<<<< HEAD
=======
     * @return void
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 */
	public static function tearDownAfterClass()
	{
		fwrite(STDOUT, __METHOD__ . "\n");
	}

	/**
	 * Unsuccessful test
	 *
	 * @param  Exception $e    Exception
<<<<<<< HEAD
=======
     * @return void
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 * @throws Exception
	 */
	protected function onNotSuccessfulTest(Exception $e)
	{
		fwrite(STDOUT, __METHOD__ . "\n");
		throw $e;
	}
}
