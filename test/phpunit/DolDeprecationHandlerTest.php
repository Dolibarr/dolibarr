<?php
/* Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 */

/**
 * \file          test/phpunit/DeprecationHandlerTest.php
 * \ingroup       core
 * \brief         PHPUnit test class for DeprecationHandler
 * \remarks       To run this script from CLI: `phpunit test/phpunit/DeprecationHandlerTest.php`
 */

// require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/doldeprecationhandler.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';


use PHPUnit\Framework\TestCase;

/**
 * PHPUnit test class for DeprecationHandler
 */
class DolDeprecationHandlerTest extends CommonClassTest
{
	private $handler;
	private $dynHandler;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param 	string	$name		Name
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		print __METHOD__."\n";
	}

	/**
	 * Global test setup
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * Unit test setup
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();
		print __METHOD__."\n";
		$this->handler = new class () {
			use DolDeprecationHandler;

			/**
			 * @var string Private var to check that magic
			 *             is triggered.
			 */
			private $privateVarShouldTrigger;

			/**
			 * @var string Private deprecated var to check that magic
			 *             is triggered.
			 * @deprecated
			 */
			private $privateDeprecated;

			/**
			 * Define deprecated properties.
			 *
			 * @return array<string,string>
			 */
			protected function deprecatedProperties()
			{
				return [
					'oldProperty' => 'newProperty',
					'privateDeprecated' => 'newProperty',
				];
			}

			/**
			 * Define deprecated methods.
			 *
			 * @return array<string,string>
			 */
			protected function deprecatedMethods()
			{
				return [
					'oldMethod' => 'newMethod',
					'oldMethodArgs' => 'newMethodArgs',
				];
			}

			// Mocking newProperty and new Methods for testing
			/**
			 * @var string New property
			 */
			public $newProperty;
			/**
			 * New method
			 *
			 * @return string Test value
			 */
			public function newMethod()
			{
				return "New method called";
			}
			/**
			 * New method with arguments
			 *
			 * @param string $arg Test argument
			 * @return string Test value
			 */
			public function newMethodArgs($arg)
			{
				return "New method called is $arg";
			}
		};

		$this->dynHandler = new class () {
			use DolDeprecationHandler;
			protected $enableDynamicProperties = true;

			/**
			 * Define deprecated properties.
			 *
			 * @return array<string,string>
			 */
			protected function deprecatedProperties()
			{
				return [
					'oldProperty' => 'newProperty',
				];
			}

			/**
			 * Define deprecated methods.
			 *
			 * @return array<string,string>
			 */
			protected function deprecatedMethods()
			{
				return [
					'oldMethod' => 'newMethod',
					'oldMethodArgs' => 'newMethodArgs',
				];
			}

			// Mocking newProperty and new Methods for testing
			/**
			 * @var string New property
			 */
			public $newProperty;
			/**
			 * New method
			 *
			 * @return string Test value
			 */
			public function newMethod()
			{
				return "New method called";
			}
			/**
			 * New method with arguments
			 *
			 * @param string $arg Test argument
			 * @return string Test value
			 */
			public function newMethodArgs($arg)
			{
				return "New method called is $arg";
			}
		};
	}

	/**
	 * Unit test teardown
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * Global test teardown
	 *
	 * @return void
	 */
	public static function tearDownAfterClass(): void
	{
		print __METHOD__."\n";
	}


	/**
	 * Test that new property value is accessible from deprecated property
	 *
	 * @return void
	 */
	public function testDeprecatedPropertyAccess()
	{
		$this->handler->newProperty = "TestNew";
		$this->assertEquals("TestNew", $this->handler->oldProperty);
	}

	/**
	 * Test that new method is called when calling deprecated method
	 *
	 * @return void
	 */
	public function testDeprecatedMethodCall()
	{
		$this->assertEquals("New method called", $this->handler->oldMethod());
	}

	/**
	 * Test that when setting the deprecated property, the value is available in the new property
	 *
	 * @return void
	 */
	public function testDeprecatedPropertyAssignment()
	{
		$this->handler->oldProperty = "TestOld";
		$this->assertEquals("TestOld", $this->handler->newProperty);

		$this->handler->privateDeprecated = "Deprecated";
		$this->assertEquals("Deprecated", $this->handler->newProperty);
	}

	/**
	 * Test that unsetting the deprecated property unsets the new property
	 *
	 * @return void
	 */
	public function testDeprecatedPropertyUnset()
	{
		$this->handler->newProperty = "TestUnset";
		unset($this->handler->oldProperty);
		$this->assertFalse(isset($this->handler->newProperty));
	}

	/**
	 * Test that deprecated method call with arguments works
	 *
	 * @return void
	 */
	public function testDeprecatedMethodCallWithArgs()
	{
		$this->assertEquals("New method called is OK", $this->handler->oldMethodArgs("OK"));
	}

	/**
	 * Test that that access to a private property triggers the deprecated error
	 *
	 * @return void
	 */
	#[WithoutErrorHandler]
	public function testPrivatePropertyAccess()
	{
		$oldErrorReporting = error_reporting(E_ALL);
		set_error_handler(static function (int $errno, string $errstr): never {
			throw new Exception($errstr, $errno);
		}, E_ALL);

		// Enable E_USER_NOTICE in error_reporting
		$this->expectExceptionMessage("Undefined property 'privateVarShouldTrigger'");
		$this->handler->privateVarShouldTrigger;

		$this->expectExceptionMessage("Accessing deprecated property 'privateDeprecated'. Use 'newProperty' instead.");
		$this->handler->privateDeprecated;

		// Restore error_reporting
		error_reporting($oldErrorReporting);

		restore_error_handler();
	}

	/**
	 * Test that that access to an undefined property generates a notification
	 *
	 * @return void
	 */
	#[WithoutErrorHandler]
	public function testUndefinedPropertyAccessDisallowed()
	{
		$oldErrorReporting = error_reporting(E_ALL);
		set_error_handler(static function (int $errno, string $errstr): never {
			throw new Exception($errstr, $errno);
		}, E_ALL);

		$this->handler->enableDynamicProperties = false;
		// Enable E_USER_NOTICE in error_reporting
		$this->expectExceptionMessage("undefinedProperty");
		$this->handler->undefinedProperty;

		restore_error_handler();

		// Restore error_reporting
		error_reporting($oldErrorReporting);
	}


	/**
	 * Test that that access to an undefined property does not generate a notification
	 * when allowed
	 *
	 * @return void
	 */
	#[WithoutErrorHandler]
	public function testUndefinedPropertyAccess()
	{
		$oldErrorReporting = error_reporting(E_ALL);
		set_error_handler(static function (int $errno, string $errstr): never {
			throw new Exception($errstr, $errno);
		}, E_ALL);

		$this->handler->enableDynamicProperties = true;
		// This does not throw an exception
		$this->assertEquals(null, $this->handler->undefinedProperty);
		restore_error_handler();

		// Restore error_reporting
		error_reporting($oldErrorReporting);
	}

	/**
	 * Test that new dynamic property value is accessible
	 *
	 * @return void
	 */
	public function testDynamicPropertyAccess()
	{
		$this->assertEquals(null, $this->dynHandler->dynamicPropertyUndefined);

		$this->dynHandler->dynamicProperty = "TestDynamic";
		$this->assertEquals("TestDynamic", $this->dynHandler->dynamicProperty);
	}


	/**
	 * Test that unsetting the dynamic property unsets it
	 *
	 * @return void
	 */
	public function testDynamicPropertyUnset()
	{
		$this->dynHandler->unsetProperty = "TestUnset";
		unset($this->dynHandler->unsetProperty);
		$this->assertFalse(isset($this->dynHandler->unsetProperty));
	}
}
