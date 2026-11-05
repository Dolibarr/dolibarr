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
 *      \file       test/phpunit/AiMcpApiBridgeGetStockTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the per-method module/right declarations of
 *                  the MCP API bridge (products getStock): a read method may
 *                  declare 'module' (tool absent when the module is off) and
 *                  'right' (named refusal instead of the API's bare 403).
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcptool.class.php';
require_once dirname(__FILE__).'/../../htdocs/ai/tools/api_bridge.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiMcpApiBridgeGetStockTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpApiBridgeGetStockTest extends CommonClassTest
{
	/**
	 * getStock declares the rights it needs on top of produit/lire, so a
	 * caller lacking stock/lire neither sees api_products_stock in
	 * tools/list nor reaches the API method's bare 403.
	 *
	 * @return void
	 */
	public function testGetStockDeclaresItsRights()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);

		$rights = $bridge->getRequiredRights('api_products_stock');
		$this->assertSame(
			array(array('produit', 'lire'), array('stock', 'lire')),
			$rights,
			'api_products_stock must declare produit/lire and stock/lire'
		);
	}

	/**
	 * The read-rights declaration must not leak onto other tools: writes
	 * keep their write['right'], plain reads keep downstream enforcement.
	 *
	 * @return void
	 */
	public function testOtherToolsKeepTheirRightsShape()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);

		$this->assertSame(
			array(array('produit', 'creer')),
			$bridge->getRequiredRights('api_products_create'),
			'Write right must still come from the write block'
		);
		$this->assertSame(
			McpTool::RIGHTS_ENFORCED_DOWNSTREAM,
			$bridge->getRequiredRights('api_products_get'),
			'Plain reads must keep downstream enforcement'
		);
	}

	/**
	 * A method-level 'right' accepts both shapes: a single triple
	 * ['module','perm'] or a list of triples. The single-triple form is
	 * normalised into the list shape checkToolRights() iterates.
	 *
	 * @return void
	 */
	public function testSingleTripleRightIsNormalised()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);

		$prop = new ReflectionProperty('ToolApiBridge', 'enrichments');
		$prop->setAccessible(true);
		$enrichments = $prop->getValue($bridge);
		$enrichments['products']['methods']['fakeStockRead'] = array(
			'suffix' => 'fake_stock_read',
			'right' => array('stock', 'lire'),
		);
		$prop->setValue($bridge, $enrichments);

		$this->assertSame(
			array(array('stock', 'lire')),
			$bridge->getRequiredRights('api_products_fake_stock_read'),
			'Single-triple right must be normalised into a list of triples'
		);
	}

	/**
	 * The module gate: api_products_stock must be offered only while the
	 * Stock module is enabled — when it is off the tool simply does not
	 * exist, instead of answering the API method's bare 403.
	 *
	 * @return void
	 */
	public function testGetStockToolExistsOnlyWithStockModule()
	{
		global $db, $conf, $user;

		$bridge = new ToolApiBridge($db, $user, $conf);
		$defs = $bridge->getDefinitions();
		$names = array();
		foreach ($defs as $def) {
			$names[] = isset($def['name']) ? $def['name'] : (isset($def['function']['name']) ? $def['function']['name'] : '');
		}

		if (isModEnabled('stock')) {
			$this->assertContains('api_products_stock', $names, 'Stock enabled: the tool must be exposed');
		} else {
			$this->assertNotContains('api_products_stock', $names, 'Stock disabled: the tool must not exist');
		}
	}
}
