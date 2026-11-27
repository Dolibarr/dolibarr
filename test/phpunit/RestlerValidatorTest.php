<?php
/* Copyright (C) 2026 Morgan Demoulin <morgan.demoulin@gmail.com>
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
 *      \file       test/phpunit/RestlerValidatorTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the Restler Validator patch (union types, see dev/dolibarr_changes.txt)
 */

global $conf,$user,$langs,$db;
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions2.lib.php';
require_once dirname(__FILE__).'/../../htdocs/includes/restler/framework/Luracast/Restler/AutoLoader.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

use Luracast\Restler\Data\ValidationInfo;
use Luracast\Restler\Data\Validator;


/**
 * Class RestlerValidatorTest
 *
 * Fails if the patch is lost on the next update of the vendored library.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RestlerValidatorTest extends CommonClassTest
{
	/**
	 * @var callable|null Restler's autoloader while a test is running
	 */
	private $restlerloader = null;

	/**
	 * Register Restler's autoloader during a test only: it would break PHPUnit's own class loading.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->restlerloader = Luracast\Restler\AutoLoader::instance();
		spl_autoload_register($this->restlerloader);
	}

	/**
	 * Put the autoloader back where it was found.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		if ($this->restlerloader !== null) {
			spl_autoload_unregister($this->restlerloader);
			$this->restlerloader = null;
		}

		parent::tearDown();
	}

	/**
	 * Build the validation info Restler derives from a PHPDoc @param line.
	 *
	 * @param  string|array<int,string> $type Documented type, an array for a union
	 * @return ValidationInfo
	 */
	private function info($type)
	{
		return new ValidationInfo(array(
			'name' => 'param',
			'label' => 'param',
			'type' => $type,
			'required' => true,
		));
	}

	/**
	 * A union type ("datetime|string", an array for Restler) no longer raises a TypeError.
	 *
	 * @return void
	 */
	public function testUnionTypeDoesNotRaiseAFatalError()
	{
		$result = Validator::validate('2026-09-21 10:00:00', $this->info(array('datetime', 'string')));

		$this->assertSame('2026-09-21 10:00:00', $result);
	}

	/**
	 * The union branch still runs the per-type prefilter: 'int|string' given "42" yields an int.
	 *
	 * @return void
	 */
	public function testUnionTypeStillAppliesThePerTypeFilter()
	{
		$result = Validator::validate('42', $this->info(array('int', 'string')));

		$this->assertSame(42, $result, 'The int branch should have cast the value');
	}

	/**
	 * A union none of whose types accept the value is still refused.
	 *
	 * @return void
	 */
	public function testUnionTypeStillRejectsAnInvalidValue()
	{
		$this->expectException('Luracast\Restler\RestException');

		Validator::validate('not-a-number', $this->info(array('int', 'float')));
	}

	/**
	 * The ordinary scalar path is untouched: the prefilter still runs.
	 *
	 * @return void
	 */
	public function testScalarTypeIsUnaffected()
	{
		$this->assertSame(42, Validator::validate('42', $this->info('int')));
		$this->assertSame('abc', Validator::validate('abc', $this->info('string')));
	}
}
