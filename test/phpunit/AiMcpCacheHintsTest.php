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
 *      \file       test/phpunit/AiMcpCacheHintsTest.php
 *      \ingroup    test
 *      \brief      Caching hints of the MCP server (spec 2026-07-28, server/utilities/caching):
 *                  the results that depend on the caller must say cacheScope 'private',
 *                  so a cache shared between authorization contexts never serves them.
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcp_protocol.class.php';

if (empty($user->id) && is_object($db)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiMcpCacheHintsTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpCacheHintsTest extends CommonClassTest
{
	/**
	 * Send one request to a fresh protocol server and return its result.
	 *
	 * @param string               $method JSON-RPC method
	 * @param array<string, mixed> $params Parameters
	 * @return array<string, mixed>
	 */
	private function call($method, $params = array())
	{
		global $db, $conf, $user;

		$server = new MCPServer($db, $conf, $user);
		$res = $server->handleRequest(array('jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params));

		$this->assertIsArray($res);
		$this->assertArrayHasKey('result', $res, $method.' did not return a result');

		return $res['result'];
	}

	/**
	 * tools/list is filtered by the caller's rights, and the other list and
	 * read results carry business data: each one must carry its own hints,
	 * scoped 'private', with a ttlMs the schema accepts (integer >= 0).
	 *
	 * @return void
	 */
	public function testCallerDependentResultsArePrivate()
	{
		foreach (array(
			'tools/list' => array(),
			'resources/list' => array(),
			'resources/read' => array('uri' => 'dolibarr://user/me'),
			'prompts/list' => array(),
		) as $method => $params) {
			$r = $this->call($method, $params);

			$this->assertArrayHasKey('cacheScope', $r, $method.' lacks cacheScope (required by the 2026-07-28 schema)');
			$this->assertSame('private', $r['cacheScope'], $method.' depends on the caller: a shared cache must not serve it');
			$this->assertArrayHasKey('ttlMs', $r, $method.' lacks ttlMs (required by the 2026-07-28 schema)');
			$this->assertIsInt($r['ttlMs']);
			$this->assertGreaterThanOrEqual(0, $r['ttlMs']);
		}
	}

	/**
	 * server/discover answers the same for every caller, so it stays 'public':
	 * its hint covers its own result only, not tools/list.
	 *
	 * @return void
	 */
	public function testDiscoverStaysPublic()
	{
		$r = $this->call('server/discover');

		$this->assertSame('public', $r['cacheScope']);
		$this->assertIsInt($r['ttlMs']);
	}
}
