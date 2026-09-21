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
 *      \file       test/phpunit/AiMcpApiBridgeTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the MCP API bridge (ai/tools/api_bridge.class.php):
 *                  translation of Restler's inline validation tags ({@min}, {@max},
 *                  {@choice}, {@pattern}) into JSON Schema constraints, and their
 *                  removal from the parameter descriptions sent to the model.
 */

global $conf,$user,$langs,$db;
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcptool.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/tools/api_bridge.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiMcpApiBridgeTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpApiBridgeTest extends CommonClassTest
{
	/**
	 * Call the private tag lifter.
	 *
	 * The method has no public caller yet: every whitelisted parameter carrying
	 * a tag also carries a hand-written description that replaces the docblock,
	 * so the translation only starts running when an endpoint without hand
	 * written parameter docs is whitelisted. Reflection is what lets the guard
	 * be verified before that day rather than after.
	 *
	 * @param  string                $desc        Description as written in the docblock
	 * @param  string                $ptype       JSON Schema type of the parameter
	 * @param  array<string, mixed>  $constraints Filled with the constraints found
	 * @return string                             Description with the tags removed
	 */
	private function lift($desc, $ptype, &$constraints)
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);

		$method = new ReflectionMethod('ToolApiBridge', 'liftInlineTags');
		$method->setAccessible(true);

		$args = array($desc, $ptype, &$constraints);

		return $method->invokeArgs($bridge, $args);
	}

	/**
	 * A description holding no tag must come back untouched.
	 *
	 * @return void
	 */
	public function testDescriptionWithoutTagIsUnchanged()
	{
		$constraints = array();
		$desc = "Sort field";

		$this->assertSame($desc, $this->lift($desc, 'string', $constraints));
		$this->assertSame(array(), $constraints);
	}

	/**
	 * The real case in the API today: {@pattern} on thirdparty_ids.
	 *
	 * @return void
	 */
	public function testPatternBecomesAConstraintAndLeavesTheText()
	{
		$constraints = array();
		$desc = $this->lift(
			"Thirdparty ids to filter orders of (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}",
			'string',
			$constraints
		);

		$this->assertSame("Thirdparty ids to filter orders of (example '1' or '1,2,3')", $desc);
		$this->assertSame('^[0-9,]*$', $constraints['pattern']);
	}

	/**
	 * A flag that changes what the regexp accepts cannot be carried to JSON
	 * Schema. Dropping the constraint is right; tightening it silently is not.
	 *
	 * @return void
	 */
	public function testPatternWithASemanticFlagIsDropped()
	{
		$constraints = array();
		$desc = $this->lift("Reference {@pattern /^ref-[a-z]+$/i}", 'string', $constraints);

		$this->assertSame("Reference", $desc);
		$this->assertArrayNotHasKey('pattern', $constraints);
	}

	/**
	 * {@min} and {@max} become numbers, not strings.
	 *
	 * @return void
	 */
	public function testMinAndMaxBecomeNumbers()
	{
		$constraints = array();
		$desc = $this->lift("Page number {@min 0} {@max 100}", 'integer', $constraints);

		$this->assertSame("Page number", $desc);
		$this->assertSame(0, $constraints['minimum']);
		$this->assertSame(100, $constraints['maximum']);
	}

	/**
	 * A numeric choice list on an integer parameter must not reach the model as
	 * strings: an enum of "0","1" would invite the wrong JSON type back.
	 *
	 * @return void
	 */
	public function testChoiceFollowsTheParameterType()
	{
		$constraints = array();
		$this->lift("Mode {@choice 0,1}", 'integer', $constraints);
		$this->assertSame(array(0, 1), $constraints['enum']);

		$constraints = array();
		$this->lift("Answer {@choice yes,no}", 'string', $constraints);
		$this->assertSame(array('yes', 'no'), $constraints['enum']);
	}

	/**
	 * Tags JSON Schema cannot express are still removed from the sentence:
	 * {@type} names a PHP class and {@from} names the HTTP source, neither of
	 * which means anything to a model calling the tool.
	 *
	 * @return void
	 */
	public function testUntranslatableTagsAreStrippedWithoutConstraint()
	{
		$constraints = array();
		$desc = $this->lift("Category object {@type Categorie} {@from body}", 'string', $constraints);

		$this->assertSame("Category object", $desc);
		$this->assertSame(array(), $constraints);
	}
}
