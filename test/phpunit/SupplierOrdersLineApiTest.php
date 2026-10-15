<?php
/* Copyright (C) 2026 Braito <braito4@hotmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

/**
 * \file test/phpunit/SupplierOrdersLineApiTest.php
 * \ingroup test
 * \brief Supplier order partial-update tests without database writes.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', __DIR__.'/../../htdocs');
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_orders.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/mcptool.class.php';
require_once DOL_DOCUMENT_ROOT.'/ai/tools/api_bridge.class.php';

/**
 * Tests of the REST update boundary and the AI declaration using mock objects.
 */
class SupplierOrdersLineApiTest extends PHPUnit\Framework\TestCase
{
	/** @var User|null Previous API user */
	private $previousUser;

	/** @var CommandeFournisseur Mock order */
	private $order;

	/** @var CommandeFournisseurLigne Original line */
	private $line;

	/** @var SupplierOrderLineApiFixture API fixture */
	private $api;

	/** @return void */
	protected function setUp(): void
	{
		$this->previousUser = DolibarrApiAccess::$user;
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturn(true);
		DolibarrApiAccess::$user = $actingUser;
		$this->order = $this->createMock(CommandeFournisseur::class);
		$this->order->method('fetch_thirdparty')->willReturn(1);
		$this->order->id = 42;
		$this->order->status = 0;
		$this->line = new CommandeFournisseurLigne(null);
		foreach (array('id' => 7, 'fk_product' => 10, 'desc' => 'Existing service', 'subprice' => '85.00', 'subprice_ttc' => 0, 'qty' => 2, 'remise_percent' => 0, 'tva_tx' => 10, 'localtax1_tx' => 0, 'localtax2_tx' => 0, 'info_bits' => 0, 'product_type' => 1, 'date_start' => 1800000000, 'date_end' => 1800086400, 'array_options' => array('options_reference' => 'keep'), 'fk_unit' => 1, 'multicurrency_subprice' => '85.00', 'ref_supplier' => 'OLD') as $field => $value) {
			$this->line->$field = $value;
		}
		$this->api = new SupplierOrderLineApiFixture($this->order, $this->line);
	}

	/** @return void */
	protected function tearDown(): void
	{
		DolibarrApiAccess::$user = $this->previousUser;
	}

	/** @return void */
	public function testPartialUpdateKeepsOmittedValues()
	{
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame(7, $args[0]);
			$this->assertSame('New description', $args[1]);
			$this->assertSame('85.00', $args[2]);
			$this->assertSame(2, $args[3]);
			$this->assertSame(1800000000, $args[12]);
			$this->assertSame(1800086400, $args[13]);
			$this->assertSame(array('options_reference' => 'keep'), $args[14]);
			$this->assertSame(null, $args[18]);
			return 1;
		});
		$this->api->putLine(42, 7, array('desc' => 'New description'));
	}

	/** @return void */
	public function testOmittedPriceKeepsTaxInclusiveMode()
	{
		$this->line->subprice_ttc = '93.50';
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame('93.50', $args[2]);
			$this->assertSame('TTC', $args[8]);
			return 1;
		});
		$this->api->putLine(42, 7, array('qty' => 3));
	}

	/** @return void */
	public function testNullDateClearsOnlyThatDate()
	{
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame(null, $args[12]);
			$this->assertSame(1800086400, $args[13]);
			return 1;
		});
		$this->api->putLine(42, 7, array('date_start' => null));
	}

	/** @return void */
	public function testReplacementKeepsLineIdentityAndClearsObsoleteSupplierReference()
	{
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame(7, $args[0]);
			$this->assertSame('95.00', $args[2]);
			$this->assertSame('', $args[17]);
			$this->assertSame(20, $args[18]);
			return 1;
		});
		$this->api->putLine(42, 7, array('fk_product' => 20, 'desc' => 'Replacement service', 'subprice' => '95.00', 'tva_tx' => 10));
	}

	/** @return void */
	public function testReplacementRequiresExplicitCommercialValues()
	{
		$this->order->expects($this->never())->method('updateline');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionMessage('explicit unit price');
		$this->api->putLine(42, 7, array('fk_product' => 20));
	}

	/** @return void */
	public function testCompanyCurrencyPriceRecalculatesForeignCurrencyInsteadOfKeepingOldPrice()
	{
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame('95.00', $args[2]);
			$this->assertSame(0, $args[16]);
			return 1;
		});
		$this->api->putLine(42, 7, array('subprice' => '95.00'));
	}

	/** @return void */
	public function testForeignCurrencyPriceIsTheOnlyPriceSource()
	{
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame(0, $args[2]);
			$this->assertSame('95.00', $args[16]);
			$this->assertSame('HT', $args[8]);
			return 1;
		});
		$this->api->putLine(42, 7, array('multicurrency_subprice' => '95.00'));
	}

	/** @return void */
	public function testNonDraftCannotBeChangedImplicitly()
	{
		$this->order->status = 3;
		$this->order->expects($this->never())->method('updateline');
		$this->order->expects($this->never())->method('setReopen');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(409);
		$this->api->putLine(42, 7, array('qty' => 3));
	}

	/** @return void */
	public function testUnknownFieldsAreRejected()
	{
		$this->order->expects($this->never())->method('updateline');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionMessage('Unsupported line field');
		$this->api->putLine(42, 7, array('notrigger' => 1));
	}

	/** @return void */
	public function testZeroQuantityCannotSilentlyBecomeOne()
	{
		$this->order->expects($this->never())->method('updateline');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionMessage('Quantity must not be zero');
		$this->api->putLine(42, 7, array('qty' => 0));
	}

	/** @return void */
	public function testWritePermissionIsRequired()
	{
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturn(false);
		DolibarrApiAccess::$user = $actingUser;
		$this->order->expects($this->never())->method('updateline');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(403);
		$this->api->putLine(42, 7, array('qty' => 3));
	}

	/** @return void */
	public function testReopeningRejectsAStaleStatus()
	{
		$this->order->status = 3;
		$this->order->expects($this->never())->method('setReopen');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(409);
		$this->api->reopen(42, 2);
	}

	/** @return void */
	public function testReadRejectsALineFromAnotherOrder()
	{
		$api = $this->getMockBuilder(SupplierOrders::class)->disableOriginalConstructor()->onlyMethods(array('loadOrderForOperation'))->getMock();
		$api->order = $this->order;
		$this->order->lines = array($this->line);
		$api->expects($this->once())->method('loadOrderForOperation')->with(42);
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(404);
		$api->getLine(42, 99);
	}

	/** @return void */
	public function testLineReadDoesNotStripTheLiveOrdersDatabaseHandle()
	{
		$api = $this->getMockBuilder(SupplierOrders::class)->disableOriginalConstructor()->onlyMethods(array('loadOrderForOperation'))->getMock();
		$api->order = $this->order;
		$database = new SupplierOrderLineSqlFixture();
		$this->order->db = $database;
		$this->line->db = $database;
		$this->order->lines = array($this->line);
		$result = $api->getLine(42, 7);
		$this->assertSame(7, $result->id);
		$this->assertSame($database, $this->order->db);
		$this->assertSame($database, $this->line->db);
		$this->assertFalse(isset($result->db));
	}

	/** @return void */
	public function testBridgePreservesPatchBodyWithoutAddingPricesOrQuantity()
	{
		$bridge = new ToolApiBridge(null);
		$metadata = new ReflectionProperty(ToolApiBridge::class, 'enrichments');
		$metadata->setAccessible(true);
		$endpoints = $metadata->getValue($bridge);
		$patch = $endpoints['supplier_orders']['methods']['putLine'];
		$normalise = new ReflectionMethod(ToolApiBridge::class, 'normaliseWriteArgs');
		$normalise->setAccessible(true);
		$args = array('id' => 42, 'lineid' => 7, 'request_data' => array('desc' => str_repeat('complete ', 100)));

		$this->assertSame($args, $normalise->invoke($bridge, $args, $patch['write']));
		$this->assertFalse($patch['input_schema']['properties']['request_data']['additionalProperties']);
		$this->assertArrayHasKey('fk_product', $patch['input_schema']['properties']['request_data']['properties']);
		$this->assertArrayNotHasKey('status', $patch['input_schema']['properties']['request_data']['properties']);
		$this->assertSame(array('fournisseur', 'commande', 'creer'), $patch['write']['right']);
		$this->assertSame(array(array('fournisseur', 'commande', 'creer'), array('fournisseur', 'commande', 'lire')), $bridge->getRequiredRights('api_supplier_orders_update_line'));
	}

	/** @return void */
	public function testLinePersistenceWritesReplacementProductAndKeepsModifyTrigger()
	{
		global $user;
		$previousUser = $user;
		$user = DolibarrApiAccess::$user;
		try {
			$line = $this->getMockBuilder(CommandeFournisseurLigne::class)->disableOriginalConstructor()->onlyMethods(array('insertExtraFields', 'call_trigger'))->getMock();
			foreach (get_object_vars($this->line) as $field => $value) {
				$line->$field = $value;
			}
			$database = new SupplierOrderLineSqlFixture();
			$line->db = $database;
			$line->fk_product = 20;
			$line->expects($this->once())->method('insertExtraFields')->willReturn(1);
			$line->expects($this->once())->method('call_trigger')->with('LINEORDER_SUPPLIER_MODIFY', $user)->willReturn(1);
			$this->assertSame(1, $line->update());
			$this->assertStringContainsString('fk_product = 20', $database->queryText);
			$this->assertStringContainsString('WHERE rowid = 7', $database->queryText);
		} finally {
			$user = $previousUser;
		}
	}
}

/**
 * Capture SQL without connecting to a database.
 */
class SupplierOrderLineSqlFixture
{
	/** @var string Captured statement */
	public $queryText = '';

	/** @return int Success */
	public function begin()
	{
		return 1;
	}
	/** @return int Success */
	public function commit()
	{
		return 1;
	}
	/** @return int Success */
	public function rollback()
	{
		return 1;
	}
	/** @return string Prefix */
	public function prefix()
	{
		return 'llx_';
	}
	/** @param int $value Timestamp
	 * @return string Fixture date
	 */
	public function idate($value)
	{
		return gmdate('Y-m-d H:i:s', $value);
	}
	/** @param mixed $value Value
	 * @return string Escaped text
	 */
	public function escape($value)
	{
		return str_replace("'", "''", (string) $value);
	}
	/** @param string $sql Statement
	 * @return bool Success
	 */
	public function query($sql)
	{
		$this->queryText = $sql;
		return true;
	}
}

/**
 * Fixture replacing persistence while exercising the actual update method.
 */
class SupplierOrderLineApiFixture extends SupplierOrders
{
	/** @var CommandeFournisseurLigne Existing line */
	private $existingLine;

	/**
	 * @param CommandeFournisseur $order Mock order
	 * @param CommandeFournisseurLigne $line Existing line
	 */
	public function __construct($order, $line)
	{
		$this->order = $order;
		$this->existingLine = $line;
	}

	/** @param int $id Order ID
	 * @return Object Order
	 */
	public function get($id)
	{
		return $this->order;
	}

	/** @param int $id Order ID
	 * @param int $lineid Line ID
	 * @return Object Line
	 */
	public function getLine($id, $lineid)
	{
		return clone $this->existingLine;
	}

	/** @param int $id Order ID
	 * @return void
	 */
	protected function loadOrderForOperation($id)
	{
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * @param string $resource Resource
	 * @param mixed $resource_id ID
	 * @param string $dbtablename Table
	 * @param string $feature2 Permission
	 * @param string $dbt_keyfield Foreign key
	 * @param string $dbt_select ID field
	 * @param string $parenttableforentity Parent entity
	 * @return bool Access allowed in this fixture
	 */
	protected static function _checkAccessToResource($resource, $resource_id = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $parenttableforentity = '')
	{
		// phpcs:enable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		return true;
	}
}
