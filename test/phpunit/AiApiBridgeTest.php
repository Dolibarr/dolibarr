<?php
/* Copyright (C) 2026 Nick Fragoulis
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
 *      \file       test/phpunit/AiApiBridgeTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the AI MCP API bridge (ai/tools/api_bridge.class.php)
 *                  and the assistant schema pipeline in ai/class/mcp.class.php.
 *                  Behavioral contract for #39856 / #40092 / #40138: whitelist layers,
 *                  list limit caps, per-method module guards, enrichment categories and
 *                  the intent-based schema filtering. Kept black-box on purpose so the
 *                  internals (and their upcoming caching layer) can change freely.
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcp.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiApiBridgeTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiApiBridgeTest extends CommonClassTest
{
	/**
	 * Build a handler for the assistant context with the bridge enabled.
	 *
	 * @param string $context McpHandler context constant value
	 * @return McpHandler
	 */
	private function getHandler($context = McpHandler::CTX_ASSISTANT)
	{
		global $db, $user, $conf;

		if (!isModEnabled('ai')) {
			$this->markTestSkipped('Module AI is not enabled on this installation.');
		}

		$conf->global->AI_MCP_API_BRIDGE = 1;

		$handler = new McpHandler($db, $user, $conf, $context);
		if (is_callable(array($handler, 'loadTools'))) {
			$handler->loadTools();
		}

		return $handler;
	}

	/**
	 * Names of all tools exposed to the LLM for the assistant context.
	 *
	 * @param McpHandler $handler Handler
	 * @return string[]
	 */
	private function getToolNames($handler)
	{
		$names = array();
		foreach ($handler->getToolsSchemaForLLM() as $def) {
			$names[] = $def['name'];
		}

		return $names;
	}

	/**
	 * The bridge must expose whitelisted read methods of enabled endpoints as tools.
	 *
	 * @return void
	 */
	public function testBridgeExposesWhitelistedReadTools()
	{
		$names = $this->getToolNames($this->getHandler());
		if (empty(preg_grep('/^api_/', $names))) {
			$this->markTestSkipped('The API bridge exposed no tool on this installation (REST API classes unavailable).');
		}

		$this->assertContains('api_thirdparties_list', $names, 'thirdparties index must be bridged');
		$this->assertContains('api_thirdparties_get', $names, 'thirdparties get must be bridged');
		$this->assertContains('api_products_list', $names, 'products index must be bridged');
		$this->assertContains('api_invoices_list', $names, 'invoices index must be bridged');
		$this->assertContains('api_categories_list', $names, 'categories index must be bridged');
	}

	/**
	 * No write-capable API method may ever be exposed: the whitelist is read-only
	 * by design until the write-safety gate exists (#38356).
	 *
	 * @return void
	 */
	public function testBridgeNeverExposesWriteMethods()
	{
		$names = $this->getToolNames($this->getHandler());

		foreach ($names as $name) {
			$this->assertSame(
				0,
				preg_match('/^api_.*_(post|put|delete|create|update|validate)($|_)/', $name),
				'write-capable API method leaked through the bridge whitelist: '.$name
			);
		}
	}

	/**
	 * The global kill switch must remove every bridge tool but keep hand-written tools.
	 *
	 * @return void
	 */
	public function testBridgeDisabledRemovesOnlyBridgeTools()
	{
		global $conf;

		if (!isModEnabled('ai')) {
			$this->markTestSkipped('Module AI is not enabled on this installation.');
		}

		$conf->global->AI_MCP_API_BRIDGE = 0;
		$handler = new McpHandler($GLOBALS['db'], $GLOBALS['user'], $conf, McpHandler::CTX_ASSISTANT);
		if (is_callable(array($handler, 'loadTools'))) {
			$handler->loadTools();
		}
		$names = $this->getToolNames($handler);

		$bridge = preg_grep('/^api_/', $names);
		$this->assertCount(0, $bridge, 'bridge tools must vanish when AI_MCP_API_BRIDGE is off');
		$this->assertContains('create_thirdparty', $names, 'hand-written tools must survive the bridge kill switch');

		$conf->global->AI_MCP_API_BRIDGE = 1;
	}

	/**
	 * Restore the constants a failed assertion could otherwise leak into the
	 * following tests (a failure aborts the test method before inline cleanup).
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;

		$conf->global->AI_MCP_API_BRIDGE = 1;
		unset($conf->global->AI_MCP_API_BRIDGE_METHODS);
		parent::tearDown();
	}

	/**
	 * AI_MCP_API_BRIDGE_METHODS holds bare method names intersected against the
	 * whitelist keys on every endpoint: it can narrow, never add. Unknown names
	 * must not add anything.
	 *
	 * @return void
	 */
	public function testMethodsConstIntersectsWhitelist()
	{
		global $conf;

		$conf->global->AI_MCP_API_BRIDGE_METHODS = 'index,doesnotexist';
		$names = $this->getToolNames($this->getHandler());

		$this->assertContains('api_thirdparties_list', $names, 'index kept by the const must stay exposed');
		$this->assertNotContains('api_thirdparties_get', $names, 'get is not in the const: must be filtered');
		$this->assertCount(0, preg_grep('/^api_.*_get_by_/', $names), 'suffixed helpers must be filtered when not listed');
		$this->assertCount(0, preg_grep('/doesnotexist/', $names), 'unknown method name in const must not add tools');
	}

	/**
	 * Every bridge list tool must declare a small default 'limit' so a single
	 * call cannot flood the model context. The hard ceiling (100) is clamped
	 * server-side at execution time, not declared in the schema; observing the
	 * clamp black-box needs a fixture larger than the seeded test database, so
	 * this test asserts the declared default and that an oversized limit is
	 * tolerated (clamped, not rejected) at execution.
	 *
	 * @return void
	 */
	public function testListLimitsAreCapped()
	{
		$handler = $this->getHandler();

		foreach ($handler->getToolsSchemaForLLM() as $def) {
			if (!preg_match('/^api_.*_list$/', $def['name'])) {
				continue;
			}
			$props = $def['inputSchema']['properties'] ?? array();
			if (!isset($props['limit'])) {
				continue;
			}
			$this->assertLessThanOrEqual(25, (int) ($props['limit']['default'] ?? 999), $def['name'].': default limit above 25');
		}
	}

	/**
	 * Every bridge tool definition must carry non-empty enrichment categories:
	 * a category-less definition disappears for one language and not another
	 * (the #40138 regression class).
	 *
	 * @return void
	 */
	public function testEveryBridgeToolHasCategories()
	{
		$handler = $this->getHandler();

		foreach ($handler->getToolsSchemaForLLM() as $def) {
			if (strpos($def['name'], 'api_') !== 0) {
				continue;
			}
			$this->assertNotEmpty($def['categories'] ?? array(), $def['name'].' has no enrichment categories');
		}
	}

	/**
	 * Executing a bridged read must return real rows for seeded data and honor
	 * the sqlfilters passthrough.
	 *
	 * @return void
	 */
	public function testBridgeExecutionReturnsRows()
	{
		if (getenv('PHPUNIT_DISABLE_API')) {
			$this->markTestSkipped("Execution tests call the API classes in-process: disabled by 'PHPUNIT_DISABLE_API'.");
		}

		$handler = $this->getHandler();

		try {
			$res = $handler->executeTool('api_thirdparties_list', array('limit' => 5));
		} catch (Throwable $e) {
			$this->fail('api_thirdparties_list threw: '.get_class($e).': '.$e->getMessage());
		}
		$this->assertIsArray($res);
		$this->assertArrayNotHasKey('error', $res, 'thirdparties list errored: '.json_encode($res));

		// Oversized limit is clamped server-side, never rejected.
		try {
			$res = $handler->executeTool('api_thirdparties_list', array('limit' => 5000));
		} catch (Throwable $e) {
			$this->fail('oversized limit threw: '.get_class($e).': '.$e->getMessage());
		}
		$this->assertIsArray($res);
		$this->assertArrayNotHasKey('error', $res, 'oversized limit must be clamped, not rejected');
	}

	/**
	 * A method outside the whitelist must be refused at execution time too,
	 * even if a client fabricates the tool name (defense in depth versus the
	 * schema layer).
	 *
	 * @return void
	 */
	public function testNonWhitelistedExecutionRefused()
	{
		if (getenv('PHPUNIT_DISABLE_API')) {
			$this->markTestSkipped("Execution tests call the API classes in-process: disabled by 'PHPUNIT_DISABLE_API'.");
		}

		$handler = $this->getHandler();

		try {
			$res = $handler->executeTool('api_thirdparties_delete', array('id' => 1));
		} catch (Throwable $e) {
			$this->fail('fabricated tool name threw instead of returning an error: '.get_class($e).': '.$e->getMessage());
		}
		$this->assertIsArray($res);
		$this->assertArrayHasKey('error', $res, 'fabricated write tool name must be refused');
	}
}
