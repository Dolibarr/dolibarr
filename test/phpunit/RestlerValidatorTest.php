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
 *      \brief      PHPUnit tests for the local patch applied to Restler's
 *                  Validator (see dev/dolibarr_changes.txt): a REST API
 *                  parameter documented with a union type must validate
 *                  instead of raising a fatal error.
 */

global $conf,$user,$langs,$db;
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions2.lib.php';
require_once dirname(__FILE__).'/../../htdocs/includes/restler/framework/Luracast/Restler/AutoLoader.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

spl_autoload_register(Luracast\Restler\AutoLoader::instance());

use Luracast\Restler\Data\ValidationInfo;
use Luracast\Restler\Data\Validator;


/**
 * Class RestlerValidatorTest
 *
 * Restler is vendored, so a patch applied to it is only as durable as the
 * reason it was applied is visible. These tests are that reason: if the guard
 * is lost on the next update of the library, they fail instead of the fatal
 * error coming back on a REST endpoint.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RestlerValidatorTest extends CommonClassTest
{
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
	 * The case that used to be fatal: a parameter documented "datetime|string".
	 *
	 * Restler hands the union to Validator::validate() as an array, and the
	 * type-specific prefilter lookup used it as an array key, which on PHP 8 is
	 * a TypeError raised before the endpoint's own code ever runs.
	 *
	 * @return void
	 */
	public function testUnionTypeDoesNotRaiseAFatalError()
	{
		$result = Validator::validate('2026-09-21 10:00:00', $this->info(array('datetime', 'string')));

		$this->assertSame('2026-09-21 10:00:00', $result);
	}

	/**
	 * Skipping the prefilter on the union pass costs nothing, because the
	 * union branch re-enters validate() once per type and the prefilter then
	 * runs with a scalar type: 'int|string' given "42" still yields an int.
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
