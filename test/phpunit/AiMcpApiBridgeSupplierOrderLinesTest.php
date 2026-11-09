<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/AiMcpApiBridgeSupplierOrderLinesTest.php
 * \ingroup test
 * \brief Supplier order line bridge regression tests without a database.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once __DIR__.'/CommonClassTest.class.php';
require_once __DIR__.'/../../htdocs/ai/class/mcptool.class.php';
require_once __DIR__.'/../../htdocs/ai/tools/api_bridge.class.php';

/** Test the real bridge using an isolated order and REST endpoint. */
class AiMcpApiBridgeSupplierOrderLinesTest extends CommonClassTest
{
	private $bridge;
	private $actor;
	const TOOL = 'api_supplier_orders_update_line';

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		global $langs;
		$langs->setDefaultLang('en_US');
		$this->actor = new AiSupplierOrderLineUserFixture();
		$this->bridge = new AiSupplierOrderLineBridgeFixture(null, $this->actor);
		AiSupplierOrderLineEndpointFixture::$received = array();
	}

	/** @return void */
	public function testOnlyLineUpdateIsExposedWithBothRights()
	{
		$p = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		if (PHP_VERSION_ID < 80100) {
			$p->setAccessible(true);
		}
		$methods = $p->getValue($this->bridge)['supplier_orders']['methods'];
		$this->assertArrayHasKey('putLine', $methods);
		foreach (array('delete', 'validate', 'approve', 'makeOrder', 'receiveOrder', 'postContact', 'deleteContact') as $method) {
			$this->assertArrayNotHasKey($method, $methods);
		}
		$this->assertSame(array(array('fournisseur', 'commande', 'creer'), array('fournisseur', 'commande', 'lire')), $this->bridge->getRequiredRights(self::TOOL));
		$m = new ReflectionMethod(ToolApiBridge::class, 'buildToolDefinition');
		if (PHP_VERSION_ID < 80100) {
			$m->setAccessible(true);
		}
		$def = $m->invoke($this->bridge, array('class' => AiSupplierOrderLineEndpointFixture::class, 'label' => 'supplier orders'), 'supplier_orders', 'putLine', self::TOOL, $methods['putLine']);
		foreach (array('id', 'line', 'lineid', 'qty', 'subprice', 'remise_percent', 'tva_tx', 'desc', 'unit') as $field) {
			$this->assertArrayHasKey($field, $def['inputSchema']['properties']);
		}
		foreach (array('notrigger', 'fk_product', 'status', 'multicurrency_subprice') as $field) {
			$this->assertArrayNotHasKey($field, $def['inputSchema']['properties']);
		}
	}

	/** @return void */
	public function testPartialBodyPreservesOmittedFields()
	{
		foreach (array(
			array('id' => 42, 'lineid' => 9, 'qty' => 3),
			array('ref' => 'PO-42', 'line' => 1, 'request_data' => array('qty' => 3)),
			array('id' => 'PO-42', 'line' => 'ROOM', 'request_data' => '{"qty":3}')
		) as $args) {
			$this->assertSame('', $this->bridge->writePrecheck(self::TOOL, $args));
			$this->assertSame(array('success' => true, 'id' => 42, 'ref' => 'PO-42'), $this->bridge->execute(self::TOOL, $args));
			$this->assertSame(array('id' => 42, 'lineid' => 9, 'body' => array('qty' => 3.0)), AiSupplierOrderLineEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testExplicitZeroAndEmptyValuesAreForwarded()
	{
		$args = array('id' => 42, 'lineid' => 9, 'subprice' => 0, 'tva_tx' => 0, 'remise_percent' => 0, 'desc' => '');
		$this->assertSame('', $this->bridge->writePrecheck(self::TOOL, $args));
		$this->assertTrue($this->bridge->execute(self::TOOL, $args)['success']);
		$this->assertSame(array('subprice' => 0.0, 'tva_tx' => 0.0, 'remise_percent' => 0.0, 'desc' => ''), AiSupplierOrderLineEndpointFixture::$received['body']);
	}

	/** @return void */
	public function testPreviewDescribesOrderLineAndChanges()
	{
		$preview = $this->bridge->writeConfirmationPreview(self::TOOL, array('id' => 42, 'lineid' => 9, 'qty' => 3, 'desc' => 'Twin room'));
		foreach (array('PO-42', 'ROOM', 'Overnight stay', 'Twin room', '2', '3') as $text) {
			$this->assertStringContainsString($text, $preview);
		}
		$this->assertSame(array(), AiSupplierOrderLineEndpointFixture::$received);
	}

	/** @return void */
	public function testInvalidArgumentsNeverReachRest()
	{
		foreach (array(
			array('lineid' => 10, 'qty' => 3), array('lineid' => '9oops', 'qty' => 3),
			array('lineid' => 9.5, 'qty' => 3), array('line' => 1.5, 'qty' => 3),
			array('lineid' => 9), array('qty' => 3), array('lineid' => 9, 'qty' => 0),
			array('lineid' => 9, 'qty' => 'bad'), array('lineid' => 9, 'subprice' => INF),
			array('lineid' => 9, 'tva_tx' => null), array('lineid' => 9, 'desc' => array()),
			array('lineid' => 9, 'fk_product' => 15), array('lineid' => 9, 'notrigger' => 1),
			array('lineid' => 9, 'request_data' => '{bad'), array('lineid' => 9, 'qty' => 3, 'request_data' => null)
		) as $args) {
			$args['id'] = 42;
			$this->assertNotSame('', $this->bridge->writePrecheck(self::TOOL, $args));
			$this->assertArrayHasKey('error', $this->bridge->execute(self::TOOL, $args));			$refused = false;
			try {
				$this->bridge->writeConfirmationPreview(self::TOOL, $args);
			} catch (InvalidArgumentException $e) {
				$refused = true;
			}
			$this->assertTrue($refused);

			$this->assertSame(array(), AiSupplierOrderLineEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testRightsAccessStatusAndOwnershipAreRechecked()
	{
		$args = array('id' => 42, 'lineid' => 9, 'qty' => 3);
		foreach (array('lire', 'creer', 'access', 'status', 'line', 'missing') as $change) {
			$this->actor->denied = '';
			$this->bridge->order = null;
			$this->bridge->allowAccess = true;
			$this->assertSame('', $this->bridge->writePrecheck(self::TOOL, $args));
			$this->assertNotSame(McpTool::NO_WRITE, $this->bridge->writeConfirmationPreview(self::TOOL, $args));
			if ($change === 'lire' || $change === 'creer') {
				$this->actor->denied = $change;
			} elseif ($change === 'access') {
				$this->bridge->allowAccess = false;
			} elseif ($change === 'missing') {
				$this->bridge->order = false;
			} else {
				$this->bridge->order = $this->bridge->sampleOrder();
				if ($change === 'status') {
					$this->bridge->order->status = 1;
				} else {
					$this->bridge->order->lines = array();
				}
			}
			$this->assertArrayHasKey('error', $this->bridge->execute(self::TOOL, $args));			$refused = false;
			try {
				$this->bridge->writeConfirmationPreview(self::TOOL, $args);
			} catch (InvalidArgumentException $e) {
				$refused = true;
			}
			$this->assertTrue($refused);

			$this->assertNotSame('', $this->bridge->writePrecheck(self::TOOL, $args));
			$this->assertSame(array(), AiSupplierOrderLineEndpointFixture::$received);
		}
	}

	/** @return void */
	public function testAmbiguousProductIsRejected()
	{
		$this->bridge->order = $this->bridge->sampleOrder();
		$second = clone $this->bridge->order->lines[0];
		$second->id = 10;
		$this->bridge->order->lines[] = $second;
		$this->assertNotSame('', $this->bridge->writePrecheck(self::TOOL, array('id' => 42, 'line' => 'ROOM', 'qty' => 3)));
	}

	/** @return void */
	public function testAssistantAndMcpRequireHumanConfirmation()
	{
		global $conf, $dolibarr_main_instance_unique_id;
		require_once DOL_DOCUMENT_ROOT.'/ai/class/mcp.class.php';
		$previous = $dolibarr_main_instance_unique_id ?? null;
		$dolibarr_main_instance_unique_id = 'supplier-line-unit-test';
		try {
			foreach (array(McpHandler::CTX_ASSISTANT, McpHandler::CTX_MCP_SERVER) as $context) {
				$db = new AiSupplierOrderConfirmationDbFixture();
				$handler = new McpHandler($db, $this->actor, $conf, $context);
				$m = new ReflectionMethod(McpHandler::class, 'checkWriteConfirmation');
				if (PHP_VERSION_ID < 80100) {
					$m->setAccessible(true);
				}
				$result = $m->invoke($handler, $this->bridge, self::TOOL, array('id' => 42, 'lineid' => 9, 'qty' => 3));
				$this->assertSame('input_required', $result['resultType']);
				$this->assertNotEmpty($result['requestState']);
				$this->assertStringContainsString('PO-42', $result['inputRequests'][0]['prompt']);
				$this->assertStringContainsString('INSERT INTO', $db->sql);
				$this->assertSame(array(), AiSupplierOrderLineEndpointFixture::$received);
			}
		} finally {
			$dolibarr_main_instance_unique_id = $previous;
		}
	}
}

/** Rights fixture. */
class AiSupplierOrderLineUserFixture
{
	public $id = 1;
	public $denied = '';

	/**
	 * @param string $module Module name.
	 * @param string $object Permission group.
	 * @param string $right Permission name.
	 * @return bool
	 */
	public function hasRight($module, $object, $right)
	{
		return $right !== $this->denied;
	}
}

/** Isolate database lookup and endpoint discovery, retaining bridge logic. */
class AiSupplierOrderLineBridgeFixture extends ToolApiBridge
{
	public $order;
	public $allowAccess = true;

	/** @return object */
	public function sampleOrder()
	{
		return (object) array('id' => 42, 'ref' => 'PO-42', 'socid' => 0, 'status' => 0, 'lines' => array((object) array(
			'id' => 9, 'product_ref' => 'ROOM', 'product_label' => 'Overnight stay', 'desc' => 'Double room',
			'qty' => 2, 'subprice' => 50, 'remise_percent' => 0, 'tva_tx' => 10, 'vat_src_code' => 'KEEP',
			'multicurrency_subprice' => 45, 'fk_unit' => 0, 'total_ht' => 100
		)));
	}

	/**
	 * @param string $class Business class.
	 * @param int $id Order ID.
	 * @param string $ref Order reference.
	 * @return object|null
	 */
	protected function fetchRecord($class, $id, $ref = '')
	{
		if ($this->order === false || ($id != 42 && $ref !== 'PO-42')) {
			return null;
		}
		return $this->order ?? $this->sampleOrder();
	}

	/**
	 * @param array $access Access configuration.
	 * @param int $id Order ID.
	 * @return bool
	 */
	protected function canAccessWriteObject(array $access, $id)
	{
		return $this->allowAccess;
	}

	/** @return array */
	public function getDefinitions(): array
	{
		foreach (array('routes' => array(AiMcpApiBridgeSupplierOrderLinesTest::TOOL => array('supplier_orders', 'putLine')), 'endpoints' => array('supplier_orders' => array('path' => __FILE__, 'class' => AiSupplierOrderLineEndpointFixture::class))) as $name => $value) {
			$p = new ReflectionProperty(ToolApiBridge::class, $name);
			if (PHP_VERSION_ID < 80100) {
				$p->setAccessible(true);
			}
			$p->setValue($this, $value);
		}
		return array();
	}
}

/** Capture exactly what is delegated to REST. */
class AiSupplierOrderLineEndpointFixture
{
	public static $received = array();

	/**
	 * Update one line.
	 * @param int $id Order ID
	 * @param int $lineid Line ID
	 * @param array $request_data Partial changes
	 * @return array
	 */
	public function putLine($id, $lineid, $request_data = null)
	{
		self::$received = array('id' => $id, 'lineid' => $lineid, 'body' => $request_data);
		return array('id' => $lineid);
	}
}

/** Confirmation persistence fixture; no actual database writes. */
class AiSupplierOrderConfirmationDbFixture
{
	public $sql = '';

	/**
	 * @param string $value Value to escape.
	 * @return string
	 */
	public function escape($value)
	{
		return addslashes($value);
	}

	/**
	 * @param int $timestamp UTC timestamp.
	 * @return string
	 */
	public function idate($timestamp)
	{
		return dol_print_date($timestamp, '%Y-%m-%d %H:%M:%S', 'gmt');
	}

	/**
	 * @param string $sql Statement to capture.
	 * @return bool
	 */
	public function query($sql)
	{
		$this->sql = $sql;
		return true;
	}
}
