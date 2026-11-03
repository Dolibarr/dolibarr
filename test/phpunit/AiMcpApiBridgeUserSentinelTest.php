<?php
/* Copyright (C) 2026 Carlos Herrera <cehojac@gmail.com>
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
 *      \file       test/phpunit/AiMcpApiBridgeUserSentinelTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the 'me' argument sentinel of the MCP API
 *                  bridge: 'me' on a user-referencing field resolves to the
 *                  acting user's rowid, everywhere else it stays a literal.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once __DIR__.'/../../htdocs/ai/class/mcptool.class.php';
require_once __DIR__.'/../../htdocs/ai/tools/api_bridge.class.php';


/**
 * Class AiMcpApiBridgeUserSentinelTest
 */
class AiMcpApiBridgeUserSentinelTest extends CommonClassTest
{
	/**
	 * 'me' resolves to the acting user's rowid on user-referencing fields.
	 *
	 * @return void
	 */
	public function testMeResolvesOnUserFields()
	{
		$user = new stdClass();
		$user->id = 42;
		$bridge = new ToolApiBridge(new stdClass(), $user, new stdClass());

		$method = new ReflectionMethod('ToolApiBridge', 'resolveUserSentinels');
		$method->setAccessible(true);

		$out = $method->invoke($bridge, array(
			'fk_user_assign' => 'me',
			'fk_user_author' => 'me',
			'assigned_user_id' => 'me',
		));

		$this->assertSame(42, $out['fk_user_assign']);
		$this->assertSame(42, $out['fk_user_author']);
		$this->assertSame(42, $out['assigned_user_id']);
	}

	/**
	 * 'me' in a text field or a numeric user field stays untouched.
	 *
	 * @return void
	 */
	public function testNonUserFieldsAndLiteralsStay()
	{
		$user = new stdClass();
		$user->id = 42;
		$bridge = new ToolApiBridge(new stdClass(), $user, new stdClass());

		$method = new ReflectionMethod('ToolApiBridge', 'resolveUserSentinels');
		$method->setAccessible(true);

		$out = $method->invoke($bridge, array(
			'subject' => 'me',
			'note' => 'call me tomorrow',
			'fk_user' => 7,
		));

		$this->assertSame('me', $out['subject']);
		$this->assertSame('call me tomorrow', $out['note']);
		$this->assertSame(7, $out['fk_user']);
	}
}
