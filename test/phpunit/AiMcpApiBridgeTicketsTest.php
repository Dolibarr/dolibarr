<?php
/* Copyright (C) 2026 Carlos Herrera <cehojac@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/AiMcpApiBridgeTicketsTest.php
 * \ingroup test
 * \brief Tests for the ticket write tools exposed by the AI MCP API bridge.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once __DIR__.'/../../htdocs/ai/class/mcptool.class.php';
require_once __DIR__.'/../../htdocs/ai/tools/api_bridge.class.php';

if (!function_exists('dol_trunc')) {
	/**
	 * Minimal dol_trunc() stand-in for tests running without the ERP bootstrap.
	 *
	 * @param string $s String to truncate
	 * @param int    $l Max length
	 * @return string
	 */
	function dol_trunc($s, $l = 60)
	{
		return strlen((string) $s) > $l ? substr((string) $s, 0, $l) : (string) $s;
	}
}

/**
 * Minimal Translate stand-in: returns the key so previews stay assertable.
 */
class AiMcpTicketsLangsStub
{
	/**
	 * @param string $f Lang file (ignored)
	 * @return int
	 */
	public function load($f)
	{
		return 1;
	}
	/**
	 * @param string $k   Lang key
	 * @param mixed  ...$a Placeholder values (ignored)
	 * @return string
	 */
	public function trans($k, ...$a)
	{
		return $k;
	}
	/**
	 * @param string $k   Lang key
	 * @param mixed  ...$a Placeholder values (ignored)
	 * @return string
	 */
	public function transnoentitiesnoconv($k, ...$a)
	{
		return $k;
	}
}

/**
 * Reflection fixture matching the Tickets API write surface without loading the ERP.
 */
class AiMcpTicketWriteApiFixture
{
	/**
	 * Create ticket object
	 *
	 * @param array $request_data Request data
	 * @return int ID of ticket
	 */
	public function post($request_data = null)
	{
		return 1;
	}
	/**
	 * Add a new message to an existing ticket.
	 *
	 * @param array $request_data Request data
	 * @return int ID of ticket
	 */
	public function postNewMessage($request_data = null)
	{
		return 1;
	}
	/**
	 * Update ticket
	 *
	 * @param int   $id           Id of ticket to update
	 * @param array $request_data Data
	 * @return array<string,mixed> Updated ticket
	 */
	public function put($id, $request_data = null)
	{
		return array('id' => $id);
	}
}

/**
 * Ticket write metadata tests requiring neither a database nor an API credential.
 */
class AiMcpApiBridgeTicketsTest extends CommonClassTest
{
	/**
	 * The tickets endpoint now lists the write methods on top of the reads.
	 *
	 * @return void
	 */
	public function testTicketsWhitelistIncludesWriteMethods()
	{
		$bridge = new ToolApiBridge(null);
		$property = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		$property->setAccessible(true);
		$endpoints = $property->getValue($bridge);

		$this->assertArrayHasKey('tickets', $endpoints);
		$this->assertSame(
			array('post', 'postNewMessage', 'put', 'postContact', 'deleteContact', 'index', 'get', 'getByTrackId', 'getByRef'),
			array_keys($endpoints['tickets']['methods'])
		);
	}

	/**
	 * Every ticket write declares a right, a preview and a target object.
	 *
	 * @return void
	 */
	public function testTicketWritesDeclareGateMetadata()
	{
		$bridge = new ToolApiBridge(null);
		$property = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		$property->setAccessible(true);
		$methods = $property->getValue($bridge)['tickets']['methods'];

		foreach (array('post', 'postNewMessage', 'put', 'postContact', 'deleteContact') as $method) {
			$write = $methods[$method]['write'] ?? array();
			$this->assertNotEmpty($write, "tickets::$method misses its write block");
			$this->assertSame(array('ticket', 'write'), $write['right'], "tickets::$method right");
			$this->assertSame('Ticket', $write['object'], "tickets::$method object");
			$this->assertNotEmpty($write['preview'], "tickets::$method preview lang key");
			$this->assertNotEmpty($write['required'], "tickets::$method required fields");
		}

		// The API validates 'socid' while the object persists 'fk_soc', and the
		// status column reads 'status': both mismatches are bridged explicitly.
		$this->assertSame(array('socid' => 'fk_soc'), $methods['post']['write']['copy']);
		$this->assertSame(array('fk_statut' => 'status'), $methods['post']['write']['rename']);
		$this->assertSame(array('notify_tiers_at_create' => 0), $methods['post']['write']['defaults']);
		$this->assertSame(array('socid' => 'fk_soc'), $methods['put']['write']['copy']);
		$this->assertSame(array('fk_statut' => 'status'), $methods['put']['write']['rename']);
	}

	/**
	 * The create schema asks for subject and message, keeps socid optional.
	 *
	 * @return void
	 */
	public function testTicketCreateSchema()
	{
		$bridge = new ToolApiBridge(null);
		$property = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		$property->setAccessible(true);
		$meta = $property->getValue($bridge)['tickets']['methods']['post'];
		$method = new ReflectionMethod(ToolApiBridge::class, 'buildToolDefinition');
		$method->setAccessible(true);

		$definition = $method->invoke($bridge, array('class' => AiMcpTicketWriteApiFixture::class, 'label' => 'support tickets'), 'tickets', 'post', 'api_tickets_create', $meta);

		$this->assertSame('api_tickets_create', $definition['name']);
		$this->assertSame(array('subject', 'message'), $definition['inputSchema']['required']);
		$this->assertArrayHasKey('socid', $definition['inputSchema']['properties']);
		$this->assertArrayNotHasKey('request_data', $definition['inputSchema']['properties']);
	}

	/**
	 * The update schema exposes the flat fields and requires only the ticket id.
	 *
	 * @return void
	 */
	public function testTicketUpdateSchema()
	{
		$bridge = new ToolApiBridge(null);
		$property = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		$property->setAccessible(true);
		$meta = $property->getValue($bridge)['tickets']['methods']['put'];
		$method = new ReflectionMethod(ToolApiBridge::class, 'buildToolDefinition');
		$method->setAccessible(true);

		$definition = $method->invoke($bridge, array('class' => AiMcpTicketWriteApiFixture::class, 'label' => 'support tickets'), 'tickets', 'put', 'api_tickets_update', $meta);

		$this->assertSame('api_tickets_update', $definition['name']);
		$this->assertSame(array('id'), $definition['inputSchema']['required']);
		$this->assertArrayHasKey('fk_user_assign', $definition['inputSchema']['properties']);
		$this->assertArrayHasKey('fk_statut', $definition['inputSchema']['properties']);
		// Fields the API would honour but that a confirmer must never see
		// written: they stay out of the advertised schema.
		foreach (array('track_id', 'fk_user_create', 'ref', 'datec', 'origin_email') as $hidden) {
			$this->assertArrayNotHasKey($hidden, $definition['inputSchema']['properties'], "update advertises $hidden");
		}
	}

	/**
	 * A ticket with no third party is legal: the precheck must not refuse a
	 * create that leaves the optional socid empty.
	 *
	 * @return void
	 */
	public function testTicketCreateWithoutThirdpartyPassesPrecheck()
	{
		$bridge = new ToolApiBridge(null);
		$refusal = $bridge->writePrecheck('api_tickets_create', array('subject' => 'S', 'message' => 'M'));

		$this->assertSame('', $refusal);
	}

	/**
	 * A ticket create still refuses a third party name that resolves to nothing.
	 * Here a numeric, positive socid simply passes: no lookup is needed.
	 *
	 * @return void
	 */
	public function testTicketCreateWithNumericSocidPassesPrecheck()
	{
		$bridge = new ToolApiBridge(null);
		$refusal = $bridge->writePrecheck('api_tickets_create', array('subject' => 'S', 'message' => 'M', 'socid' => 42));

		$this->assertSame('', $refusal);
	}

	/**
	 * The required third party rule is unchanged: creating an invoice with no
	 * socid still stops at the precheck instead of asking for a confirmation.
	 *
	 * @return void
	 */
	public function testInvoiceCreateStillRequiresThirdparty()
	{
		$GLOBALS['langs'] = new AiMcpTicketsLangsStub();
		$bridge = new ToolApiBridge(null);
		$refusal = $bridge->writePrecheck('api_invoices_create', array('lines' => array()));

		$this->assertSame('AIWriteNoThirdparty', $refusal);
	}
}
