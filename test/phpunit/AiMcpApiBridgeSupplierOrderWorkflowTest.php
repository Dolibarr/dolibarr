<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/AiMcpApiBridgeSupplierOrderWorkflowTest.php
 * \ingroup test
 * \brief Supplier order workflow regression tests without a database.
 */

global $conf,$user,$langs,$db,$mysoc;
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once __DIR__.'/CommonClassTest.class.php';
require_once __DIR__.'/../../htdocs/ai/class/mcptool.class.php';
require_once __DIR__.'/../../htdocs/ai/tools/api_bridge.class.php';

/** Exercise workflow checks and REST delegation without a live database. */
class AiMcpApiBridgeSupplierOrderWorkflowTest extends CommonClassTest
{
	private $bridge;
	private $actor;
	private $database;
	private $previousModules;
	const MAKE = 'api_supplier_orders_make_order';
	const RECEIVE = 'api_supplier_orders_receive_order';

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		global $langs, $conf;
		$this->previousModules = $conf->modules;
		$conf->modules['stock'] = true;
		unset($conf->modules['reception']);
		$langs->setDefaultLang('en_US');
		$this->actor = new AiSupplierWorkflowUserFixture();
		$this->database = new AiSupplierWorkflowTransactionFixture();
		$this->bridge = new AiSupplierWorkflowBridgeFixture($this->database, $this->actor);
		AiSupplierWorkflowEndpointFixture::$received = array();
		AiSupplierWorkflowEndpointFixture::$fail = false;
	}

	/** @return void */
	protected function tearDown(): void
	{
		global $conf;
		$conf->modules = $this->previousModules;
		parent::tearDown();
	}

	/** @return array */
	private function receiptArgs()
	{
		return array('id' => 42, 'lineid' => 9, 'qty' => 2, 'warehouse' => 'MAIN', 'closeopenorder' => 1, 'comment' => 'Checked goods');
	}

	/** @return void */
	public function testSchemaAndRights()
	{
		$p = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		$m = new ReflectionMethod(ToolApiBridge::class, 'buildToolDefinition');
		if (PHP_VERSION_ID < 80100) {
			$p->setAccessible(true);
			$m->setAccessible(true);
		}
		foreach (array('makeOrder' => self::MAKE, 'receiveOrder' => self::RECEIVE) as $method => $tool) {
			$meta = $p->getValue($this->bridge)['supplier_orders']['methods'][$method];
			$this->assertCount(3, $this->bridge->getRequiredRights($tool));
			$def = $m->invoke($this->bridge, array('class' => AiSupplierWorkflowEndpointFixture::class, 'label' => 'supplier orders'), 'supplier_orders', $method, $tool, $meta);
			$this->assertContains('id', $def['inputSchema']['required']);
			$this->assertArrayNotHasKey('notrigger', $def['inputSchema']['properties']);
			$this->assertArrayNotHasKey('price', $def['inputSchema']['properties']);
			$this->assertArrayNotHasKey('lines', $def['inputSchema']['properties']);
		}
	}

	/** @return void */
	public function testMakeOrderPreviewAndDelegation()
	{
		$args = array('id' => 'PO-42', 'date' => '2026-11-09', 'method' => 'OrderByEMail', 'comment' => 'Agreed delivery');
		$this->assertSame('', $this->bridge->writePrecheck(self::MAKE, $args));
		$preview = $this->bridge->writeConfirmationPreview(self::MAKE, $args);
		foreach (array('PO-42', 'Email', '2026', 'Agreed delivery', 'does not send an email') as $text) {
			$this->assertStringContainsString($text, $preview);
		}
		$this->assertSame(array(), AiSupplierWorkflowEndpointFixture::$received);
		$this->assertTrue($this->bridge->execute(self::MAKE, $args)['success']);
		$this->assertSame(array('id' => 42, 'date' => dol_stringtotime('2026-11-09', 0), 'method' => 3, 'comment' => 'Agreed delivery'), AiSupplierWorkflowEndpointFixture::$received);
	}

	/** @return void */
	public function testReceivePreviewCostAndAtomicDelegation()
	{
		$this->bridge->status = 3;
		$args = $this->receiptArgs();
		$this->assertSame('', $this->bridge->writePrecheck(self::RECEIVE, $args));
		$preview = $this->bridge->writeConfirmationPreview(self::RECEIVE, $args);
		foreach (array('PO-42', 'GOODS', 'MAIN', 'Checked goods', 'Close if fully received') as $text) {
			$this->assertStringContainsString($text, $preview);
		}
		$this->assertSame(array(), AiSupplierWorkflowEndpointFixture::$received);
		$this->assertTrue($this->bridge->execute(self::RECEIVE, $args)['success']);
		$received = AiSupplierWorkflowEndpointFixture::$received;
		$this->assertSame(1, $received['closeopenorder']);
		$this->assertCount(1, $received['lines']);
		$this->assertSame(array('id' => 9, 'fk_product' => 15, 'qty' => 2.0, 'warehouse' => 7, 'price' => 45.0, 'comment' => 'Checked goods', 'eatby' => 0, 'sellby' => 0, 'batch' => '', 'notrigger' => 0), $received['lines'][0]);
		$this->assertSame(array('begin', 'commit'), $this->database->transactions);
	}

	/** @return void */
	public function testReceptionModuleUsesItsOwnPermission()
	{
		global $conf;
		$conf->modules['reception'] = true;
		$this->bridge = new AiSupplierWorkflowBridgeFixture($this->database, $this->actor);
		$this->bridge->status = 3;
		$this->assertContains(array('reception', 'creer'), $this->bridge->getRequiredRights(self::RECEIVE));
		$this->actor->deniedModule = 'reception';
		$this->assertRefused(self::RECEIVE, $this->receiptArgs());
	}

	/** @return void */
	public function testStockDisabledRefusesReceipt()
	{
		global $conf;
		unset($conf->modules['stock']);
		$this->bridge->status = 3;
		$this->assertRefused(self::RECEIVE, $this->receiptArgs());
	}

	/** @return void */
	public function testTimestampAndMethodLabelAreAccepted()
	{
		$args = array('id' => 42, 'date' => 1794182400, 'method' => 'Email');
		$this->assertSame('', $this->bridge->writePrecheck(self::MAKE, $args));
		$this->assertTrue($this->bridge->execute(self::MAKE, $args)['success']);
		$this->assertSame(1794182400, AiSupplierWorkflowEndpointFixture::$received['date']);
		$this->assertSame('', AiSupplierWorkflowEndpointFixture::$received['comment']);
	}

	/** @return void */
	public function testPartiallyReceivedOrderCanRemainOpen()
	{
		$this->bridge->status = 4;
		$args = $this->receiptArgs();
		$args['closeopenorder'] = '0';
		$this->assertSame('', $this->bridge->writePrecheck(self::RECEIVE, $args));
		$this->assertTrue($this->bridge->execute(self::RECEIVE, $args)['success']);
		$this->assertSame(0, AiSupplierWorkflowEndpointFixture::$received['closeopenorder']);
	}

	/** @return void */
	public function testReceiptFailureRollsBack()
	{
		$this->bridge->status = 4;
		AiSupplierWorkflowEndpointFixture::$fail = true;
		$this->assertArrayHasKey('error', $this->bridge->execute(self::RECEIVE, $this->receiptArgs()));
		$this->assertSame(array('begin', 'rollback'), $this->database->transactions);
	}

	/** @return void */
	public function testInvalidArgumentsAreRefusedBeforePreviewAndExecution()
	{
		foreach (array(
			array('id' => 42, 'date' => 'invalid', 'method' => 3),
			array('id' => 42, 'date' => 0, 'method' => 3),
			array('id' => 42, 'date' => '2026-11-09', 'method' => 'unknown'),
			array('id' => 42, 'date' => '2026-11-09'),
			array('id' => array(42), 'date' => '2026-11-09', 'method' => 3)
		) as $args) {
			$this->assertRefused(self::MAKE, $args);
		}
		$this->bridge->status = 3;
		foreach (array(
			array('lineid' => '9oops'), array('lineid' => 10), array('qty' => 0), array('qty' => -1),
			array('qty' => INF), array('qty' => 'bad'), array('qty' => 4), array('qty' => array(1)),
			array('warehouse' => 'unknown'), array('closeopenorder' => 2), array('notrigger' => 1),
			array('lines' => array()), array('price' => 1), array('fk_product' => 100), array('comment' => array()),
			array('id' => 43), array('id' => 42.5), array('request_data' => array())
		) as $change) {
			$this->assertRefused(self::RECEIVE, array_merge($this->receiptArgs(), $change));
		}
	}

	/** @return void */
	public function testRightsAndStateAreRecheckedAfterPreview()
	{
		foreach (array(self::MAKE, self::RECEIVE) as $tool) {
			$args = $tool === self::MAKE ? array('id' => 42, 'date' => '2026-11-09', 'method' => 3) : $this->receiptArgs();
			foreach (array('lire', 'creer', 'action', 'access', 'status', 'missing') as $change) {
				$this->actor->denied = '';
				$this->bridge->status = $tool === self::MAKE ? 2 : 3;
				$this->bridge->allowAccess = true;
				$this->bridge->missing = false;
				$this->assertSame('', $this->bridge->writePrecheck($tool, $args));
				$this->assertNotSame(McpTool::NO_WRITE, $this->bridge->writeConfirmationPreview($tool, $args));
				if ($change === 'lire' || $change === 'creer') {
					$this->actor->denied = $change;
				} elseif ($change === 'action') {
					$this->actor->denied = $tool === self::MAKE ? 'commander' : 'receptionner';
				} elseif ($change === 'access') {
					$this->bridge->allowAccess = false;
				} elseif ($change === 'missing') {
					$this->bridge->missing = true;
				} else {
					$this->bridge->status = 0;
				}
				$this->assertRefused($tool, $args);
			}
		}
	}

	/** @return void */
	public function testReceiptRechecksLineProductWarehouseAndRemainingQuantity()
	{
		$this->bridge->status = 3;
		$args = $this->receiptArgs();
		foreach (array('noLine', 'batch', 'closedWarehouse', 'alreadyReceived') as $change) {
			$this->bridge->noLine = false;
			$this->bridge->batch = false;
			$this->bridge->closedWarehouse = false;
			$this->bridge->alreadyReceived = 1;
			$this->assertSame('', $this->bridge->writePrecheck(self::RECEIVE, $args));
			$this->bridge->writeConfirmationPreview(self::RECEIVE, $args);
			$this->bridge->$change = $change === 'alreadyReceived' ? 3 : true;
			$this->assertRefused(self::RECEIVE, $args);
		}
	}

	/**
	 * @param string $tool Tool name.
	 * @param array $args Arguments.
	 * @return void
	 */
	private function assertRefused($tool, array $args)
	{
		$this->assertNotSame('', $this->bridge->writePrecheck($tool, $args));
		$this->assertArrayHasKey('error', $this->bridge->execute($tool, $args));
		$refused = false;
		try {
			$this->bridge->writeConfirmationPreview($tool, $args);
		} catch (InvalidArgumentException $e) {
			$refused = true;
		}
		$this->assertTrue($refused);
		$this->assertSame(array(), AiSupplierWorkflowEndpointFixture::$received);
	}

	/** @return void */
	public function testBothActionsRequireConfirmationInAssistantAndMcp()
	{
		global $conf, $dolibarr_main_instance_unique_id;
		require_once DOL_DOCUMENT_ROOT.'/ai/class/mcp.class.php';
		$previous = $dolibarr_main_instance_unique_id ?? null;
		$dolibarr_main_instance_unique_id = 'supplier-workflow-unit-test';
		try {
			foreach (array(McpHandler::CTX_ASSISTANT, McpHandler::CTX_MCP_SERVER) as $context) {
				foreach (array(self::MAKE, self::RECEIVE) as $tool) {
					$this->bridge->status = $tool === self::MAKE ? 2 : 3;
					$args = $tool === self::MAKE ? array('id' => 42, 'date' => '2026-11-09', 'method' => 3) : $this->receiptArgs();
					$db = new AiSupplierWorkflowConfirmationDbFixture();
					$handler = new McpHandler($db, $this->actor, $conf, $context);
					$m = new ReflectionMethod(McpHandler::class, 'checkWriteConfirmation');
					if (PHP_VERSION_ID < 80100) {
						$m->setAccessible(true);
					}
					$result = $m->invoke($handler, $this->bridge, $tool, $args);
					$this->assertSame('input_required', $result['resultType']);
					$this->assertNotEmpty($result['requestState']);
					$this->assertStringContainsString('PO-42', $result['inputRequests'][0]['prompt']);
					$this->assertSame(array(), AiSupplierWorkflowEndpointFixture::$received);
				}
			}
		} finally {
			$dolibarr_main_instance_unique_id = $previous;
		}
	}
}

/** Rights fixture. */
class AiSupplierWorkflowUserFixture
{
	public $id = 1;
	public $denied = '';
	public $deniedModule = '';

	/**
	 * @param string $module Module.
	 * @param string $object Object or permission.
	 * @param string $right Permission.
	 * @return bool
	 */
	public function hasRight($module, $object, $right = '')
	{
		return $module !== $this->deniedModule && ($right !== '' ? $right : $object) !== $this->denied;
	}
}

/** Keep business checks real; isolate persistent lookups. */
class AiSupplierWorkflowBridgeFixture extends ToolApiBridge
{
	public $status = 2;
	public $allowAccess = true;
	public $missing = false;
	public $noLine = false;
	public $batch = false;
	public $closedWarehouse = false;
	public $alreadyReceived = 1;

	/**
	 * @param string $class Object class.
	 * @param int $id ID.
	 * @param string $ref Reference.
	 * @return object|null
	 */
	protected function fetchRecord($class, $id, $ref = '')
	{
		if ($this->missing || ($id != 42 && $ref !== 'PO-42')) {
			return null;
		}
		return (object) array('id' => 42, 'ref' => 'PO-42', 'socid' => 0, 'status' => $this->status, 'lines' => $this->noLine ? array() : array((object) array('id' => 9, 'fk_product' => 15, 'product_ref' => 'GOODS', 'product_label' => 'Supplies', 'qty' => 4, 'subprice' => 50, 'remise_percent' => 10)));
	}

	/**
	 * @param string $class Object class.
	 * @param int $id ID.
	 * @param string $ref Reference.
	 * @return object|null
	 */
	protected function fetchBusinessObject($class, $id, $ref = '')
	{
		return (object) array('id' => 15, 'type' => 0, 'status_batch' => $this->batch ? 1 : 0);
	}

	/**
	 * @param array $access Access descriptor.
	 * @param int $id ID.
	 * @return bool
	 */
	protected function canAccessWriteObject(array $access, $id)
	{
		return $this->allowAccess;
	}

	/** @return array */
	protected function supplierOrderInputMethods()
	{
		return array(array('id' => 3, 'code' => 'OrderByEMail', 'label' => 'Email'));
	}

	/**
	 * @param int $orderid Order ID.
	 * @param int $lineid Line ID.
	 * @return float
	 */
	protected function supplierOrderReceivedQuantity($orderid, $lineid)
	{
		return (float) $this->alreadyReceived;
	}

	/**
	 * @param mixed $value Warehouse selector.
	 * @return object|null
	 */
	protected function supplierOrderWarehouse($value)
	{
		return $value === 'MAIN' ? (object) array('id' => 7, 'ref' => 'MAIN', 'status' => $this->closedWarehouse ? 0 : 1) : null;
	}

	/** @return array */
	public function getDefinitions(): array
	{
		$routes = array(AiMcpApiBridgeSupplierOrderWorkflowTest::MAKE => array('supplier_orders', 'makeOrder'), AiMcpApiBridgeSupplierOrderWorkflowTest::RECEIVE => array('supplier_orders', 'receiveOrder'));
		foreach (array('routes' => $routes, 'endpoints' => array('supplier_orders' => array('path' => __FILE__, 'class' => AiSupplierWorkflowEndpointFixture::class))) as $name => $value) {
			$p = new ReflectionProperty(ToolApiBridge::class, $name);
			if (PHP_VERSION_ID < 80100) {
				$p->setAccessible(true);
			}
			$p->setValue($this, $value);
		}
		return array();
	}
}

/** Capture REST arguments and simulate a failure after dispatch. */
class AiSupplierWorkflowEndpointFixture
{
	public static $received = array();
	public static $fail = false;

	/**
	 * @param int $id Order ID.
	 * @param int $date Date.
	 * @param int $method Ordering method.
	 * @param string $comment Comment.
	 * @return array
	 */
	public function makeOrder($id, $date, $method, $comment = '')
	{
		self::$received = array('id' => $id, 'date' => $date, 'method' => $method, 'comment' => $comment);
		return array('success' => true);
	}

	/**
	 * @param int $id Order ID.
	 * @param int $closeopenorder Close when fully received.
	 * @param string $comment Comment.
	 * @param array $lines Dispatches.
	 * @return array
	 */
	public function receiveOrder($id, $closeopenorder, $comment, $lines)
	{
		if (self::$fail) {
			throw new RuntimeException('Status update failed after dispatch');
		}
		self::$received = array('id' => $id, 'closeopenorder' => $closeopenorder, 'comment' => $comment, 'lines' => $lines);
		return array('success' => true);
	}
}

/** Observe the outer receipt transaction. */
class AiSupplierWorkflowTransactionFixture
{
	public $transactions = array();

	/** @return int */
	public function begin()
	{
		$this->transactions[] = 'begin';
		return 1;
	}

	/** @return int */
	public function commit()
	{
		$this->transactions[] = 'commit';
		return 1;
	}

	/** @return int */
	public function rollback()
	{
		$this->transactions[] = 'rollback';
		return 1;
	}
}
/** Confirmation persistence fixture; no actual database writes. */
class AiSupplierWorkflowConfirmationDbFixture
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
	 * @param mixed $result Query result.
	 * @return int
	 */
	public function affected_rows($result = null)
	{
		return 0;
	}

	/**
	 * @param string $sql Statement to capture.
	 * @return bool
	 */
	public function query($sql)
	{
		if (strpos($sql, 'INSERT INTO') === 0) {
			$this->sql = $sql;
		}
		return true;
	}
}
