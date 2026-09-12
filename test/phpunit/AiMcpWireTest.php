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
 *      \file       test/phpunit/AiMcpWireTest.php
 *      \ingroup    test
 *      \brief      PHPUnit tests for the MCP wire layer (ai/class/mcp_protocol.class.php):
 *                  spec 2026-07-28 alignment — server/discover, dual-stacked initialize,
 *                  per-request _meta version negotiation, transport header validation,
 *                  resultType on every result, and the error-code audit (own codes stay
 *                  out of the reserved -32020..-32099 band).
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/ai/class/mcp_protocol.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class AiMcpWireTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class AiMcpWireTest extends CommonClassTest
{
	/**
	 * Fresh protocol server per call: httpStatus is per-request state.
	 *
	 * @return MCPServer
	 */
	private function getServer()
	{
		global $db, $conf, $user;

		return new MCPServer($db, $conf, $user);
	}

	/**
	 * server/discover is mandatory in 2026-07-28 and its result must carry
	 * every schema-required field, with logging absent from capabilities.
	 *
	 * @return void
	 */
	public function testDiscoverReturnsRequiredFields()
	{
		$res = $this->getServer()->handleRequest(array('jsonrpc' => '2.0', 'id' => 1, 'method' => 'server/discover'));

		$this->assertNotNull($res);
		$this->assertArrayHasKey('result', $res);
		$r = $res['result'];
		foreach (array('supportedVersions', 'capabilities', 'cacheScope', 'ttlMs', 'resultType') as $required) {
			$this->assertArrayHasKey($required, $r, 'DiscoverResult missing required field '.$required);
		}
		$this->assertEquals('complete', $r['resultType']);
		$this->assertContains('2026-07-28', $r['supportedVersions']);
		$this->assertContains('2025-11-25', $r['supportedVersions'], 'dual-stack: legacy version must stay supported');
		$this->assertArrayNotHasKey('logging', $r['capabilities'], 'logging capability is deprecated and must not be advertised');
	}

	/**
	 * Legacy initialize keeps working byte-compatibly for 2025-11-25 clients,
	 * echoes a supported requested version, and never advertises logging.
	 *
	 * @return void
	 */
	public function testInitializeDualStack()
	{
		$legacy = $this->getServer()->handleRequest(array('jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => array('protocolVersion' => '2025-11-25')));
		$this->assertEquals('2025-11-25', $legacy['result']['protocolVersion'], 'legacy client must be answered in its own version');

		$modern = $this->getServer()->handleRequest(array('jsonrpc' => '2.0', 'id' => 2, 'method' => 'initialize', 'params' => array('protocolVersion' => '2026-07-28')));
		$this->assertEquals('2026-07-28', $modern['result']['protocolVersion']);

		$unknown = $this->getServer()->handleRequest(array('jsonrpc' => '2.0', 'id' => 3, 'method' => 'initialize', 'params' => array('protocolVersion' => '2099-01-01')));
		$this->assertEquals('2026-07-28', $unknown['result']['protocolVersion'], 'unknown requested version: answer with our newest');

		$this->assertArrayNotHasKey('logging', $modern['result']['capabilities']);
	}

	/**
	 * An unsupported per-request _meta protocol version must be refused with
	 * -32022, requested+supported data, and HTTP 400.
	 *
	 * @return void
	 */
	public function testUnsupportedMetaVersionRefused()
	{
		$server = $this->getServer();
		$res = $server->handleRequest(array('jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list', 'params' => array(
			'_meta' => array('io.modelcontextprotocol/protocolVersion' => '2091-01-01')
		)));

		$this->assertArrayHasKey('error', $res);
		$this->assertEquals(-32022, $res['error']['code']);
		$this->assertEquals('2091-01-01', $res['error']['data']['requested']);
		$this->assertContains('2026-07-28', $res['error']['data']['supported']);
		$this->assertEquals(400, $server->getHttpStatus());
	}

	/**
	 * A supported per-request _meta version passes through to normal dispatch.
	 *
	 * @return void
	 */
	public function testSupportedMetaVersionAccepted()
	{
		$server = $this->getServer();
		$res = $server->handleRequest(array('jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping', 'params' => array(
			'_meta' => array('io.modelcontextprotocol/protocolVersion' => '2026-07-28')
		)));

		$this->assertArrayHasKey('result', $res);
		$this->assertEquals(200, $server->getHttpStatus());
	}

	/**
	 * Transport headers: when present they must agree with the body
	 * (HeaderMismatchError -32020, HTTP 400); absent headers change nothing.
	 *
	 * @return void
	 */
	public function testHeaderValidation()
	{
		$req = array('jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => array('name' => 'get_company_info'));

		// Absent headers: valid.
		$this->assertNull($this->getServer()->validateTransportHeaders(array(), $req));

		// Matching headers: valid (case-insensitive lookup).
		$this->assertNull($this->getServer()->validateTransportHeaders(array('MCP-METHOD' => 'tools/call', 'Mcp-Name' => 'get_company_info'), $req));

		// Mcp-Method mismatch.
		$server = $this->getServer();
		$err = $server->validateTransportHeaders(array('Mcp-Method' => 'tools/list'), $req);
		$this->assertEquals(-32020, $err['error']['code']);
		$this->assertEquals(400, $server->getHttpStatus());

		// Mcp-Name mismatch.
		$server = $this->getServer();
		$err = $server->validateTransportHeaders(array('Mcp-Name' => 'another_tool'), $req);
		$this->assertEquals(-32020, $err['error']['code']);

		// Unsupported version in the header.
		$server = $this->getServer();
		$err = $server->validateTransportHeaders(array('MCP-Protocol-Version' => '1999-01-01'), $req);
		$this->assertEquals(-32022, $err['error']['code']);

		// Header vs _meta disagreement.
		$req2 = $req;
		$req2['params']['_meta'] = array('io.modelcontextprotocol/protocolVersion' => '2025-11-25');
		$server = $this->getServer();
		$err = $server->validateTransportHeaders(array('MCP-Protocol-Version' => '2026-07-28'), $req2);
		$this->assertEquals(-32020, $err['error']['code']);
	}

	/**
	 * Every result of every method must carry resultType (schema: required on
	 * Result), valued 'complete' until the MRTR gate introduces input_required.
	 *
	 * @return void
	 */
	public function testEveryResultCarriesResultType()
	{
		foreach (array(
			array('jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'),
			array('jsonrpc' => '2.0', 'id' => 2, 'method' => 'initialize', 'params' => array()),
			array('jsonrpc' => '2.0', 'id' => 3, 'method' => 'tools/list'),
			array('jsonrpc' => '2.0', 'id' => 4, 'method' => 'resources/list'),
			array('jsonrpc' => '2.0', 'id' => 5, 'method' => 'prompts/list'),
			array('jsonrpc' => '2.0', 'id' => 6, 'method' => 'server/discover'),
		) as $req) {
			$res = $this->getServer()->handleRequest($req);
			$this->assertArrayHasKey('result', $res, $req['method'].' did not return a result');
			$this->assertEquals('complete', $res['result']['resultType'], $req['method'].' result lacks resultType');
		}
	}

	/**
	 * Transport headers describe the HTTP request, not batch items: a header
	 * mismatching ANY item must fail the whole batch with one 400 (the server
	 * pre-validates before dispatch; asserted here at the class level, and at
	 * HTTP level in the battery). A notification item (no id) still yields a
	 * suppressed per-item response but the transport status must be 400.
	 *
	 * @return void
	 */
	public function testBatchHeaderMismatchSets400()
	{
		$server = $this->getServer();
		$err = $server->validateTransportHeaders(array('Mcp-Method' => 'ping'), array('jsonrpc' => '2.0', 'id' => 7, 'method' => 'server/discover'));
		$this->assertNotNull($err);
		$this->assertEquals(-32020, $err['error']['code']);
		$this->assertEquals(400, $server->getHttpStatus(), 'batch item mismatch must drive the HTTP status to 400');

		// Notification item: response suppressed (JSON-RPC), status still 400.
		$server = $this->getServer();
		$err = $server->validateTransportHeaders(array('Mcp-Method' => 'ping'), array('jsonrpc' => '2.0', 'method' => 'server/discover'));
		$this->assertNull($err, 'notification gets no response body');
		$this->assertEquals(400, $server->getHttpStatus(), 'but the transport status must still tell the truth');
		// The transport wraps this into a synthesized id:null error body on
		// BOTH dispatch paths (single and batch) so a 400 never goes out
		// empty - asserted at HTTP level in the battery (H2n/H6b).
	}

	/**
	 * Error-code audit: codes this server emits itself must stay outside the
	 * spec-reserved -32020..-32099 band; the two spec errors are the only
	 * legitimate occupants of it.
	 *
	 * @return void
	 */
	public function testOwnErrorCodesOutsideReservedBand()
	{
		// Unknown method: JSON-RPC standard code.
		$res = $this->getServer()->handleRequest(array('jsonrpc' => '2.0', 'id' => 1, 'method' => 'no/such/method'));
		$this->assertEquals(-32601, $res['error']['code']);

		// Malformed request: JSON-RPC 2.0 mandates -32600 with the request id
		// (null when undeterminable), never silence.
		$res = $this->getServer()->handleRequest(array('id' => 1));
		$this->assertEquals(-32600, $res['error']['code']);
		$this->assertEquals(1, $res['id']);
		$res = $this->getServer()->handleRequest(array('foo' => 'bar'));
		$this->assertEquals(-32600, $res['error']['code']);
		$this->assertNull($res['id']);

		foreach (array(-32600, -32601, -32000) as $own) {
			$this->assertFalse($own <= -32020 && $own >= -32099, 'own code '.$own.' sits in the reserved band');
		}
	}
}
