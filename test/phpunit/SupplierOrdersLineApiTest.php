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
// Restler unregisters existing loaders; keep PHPUnit's Composer loader available.
call_user_func(
	/** @return void */
	static function () {
		$autoloaders = spl_autoload_functions();
		try {
			require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_orders.class.php';
		} finally {
			foreach (array_reverse($autoloaders) as $autoloader) {
				if (!in_array($autoloader, spl_autoload_functions(), true)) {
					spl_autoload_register($autoloader, true, true);
				}
			}
		}
	}
);

/**
 * Exercise the REST boundary using an order whose persistence is mocked.
 */
class SupplierOrdersLineApiTest extends PHPUnit\Framework\TestCase
{
	/** @var User|null Previous API user */
	private $previousUser;

	/** @var mixed Previous extrafield metadata */
	private $previousExtrafields;

	/** @var CommandeFournisseur Mock order */
	private $order;

	/** @var CommandeFournisseurLigne Original line */
	private $line;

	/** @var SupplierOrderLineApiFixture API fixture */
	private $api;

	/** @return void */
	protected function setUp(): void
	{
		global $extrafields;
		$this->previousUser = DolibarrApiAccess::$user;
		$this->previousExtrafields = $extrafields;
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturn(true);
		DolibarrApiAccess::$user = $actingUser;
		SupplierOrderLineApiFixture::$accessAllowed = true;
		$this->order = $this->createMock(CommandeFournisseur::class);
		$this->order->method('fetch')->willReturn(1);
		$this->order->method('fetch_thirdparty')->willReturn(1);
		$this->order->id = 42;
		$this->order->status = CommandeFournisseur::STATUS_DRAFT;
		$this->line = new CommandeFournisseurLigne(null);
		foreach (array('id' => 7, 'fk_product' => 10, 'desc' => 'Existing service', 'subprice' => '85.00', 'subprice_ttc' => 0, 'qty' => 2, 'remise_percent' => 0, 'tva_tx' => 10, 'localtax1_tx' => 0, 'localtax2_tx' => 0, 'info_bits' => 0, 'product_type' => 1, 'date_start' => 1800000000, 'date_end' => 1800086400, 'array_options' => array('options_reference' => 'keep'), 'fk_unit' => 1, 'multicurrency_subprice' => '85.00', 'ref_supplier' => 'OLD') as $field => $value) {
			$this->line->$field = $value;
		}
		$this->order->lines = array($this->line);
		$this->api = new SupplierOrderLineApiFixture($this->order);
	}

	/** @return void */
	protected function tearDown(): void
	{
		global $extrafields;
		DolibarrApiAccess::$user = $this->previousUser;
		$extrafields = $this->previousExtrafields;
		SupplierOrderLineApiFixture::$accessAllowed = true;
	}

	/** @return void */
	public function testPartialUpdateKeepsOmittedValues()
	{
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertCount(18, $args);
			$this->assertSame(7, $args[0]);
			$this->assertSame('New description', $args[1]);
			$this->assertSame('85.00', $args[2]);
			$this->assertSame(2, $args[3]);
			$this->assertSame(0, $args[11]);
			$this->assertSame(1800000000, $args[12]);
			$this->assertSame(1800086400, $args[13]);
			$this->assertSame(array('options_reference' => 'keep'), $args[14]);
			$this->assertSame('OLD', $args[17]);
			$this->assertSame('Existing service', $this->line->desc);
			$this->assertSame(10, $this->line->fk_product);
			return 1;
		});
		$this->api->putLine(42, 7, array('desc' => 'New description'));
	}

	/** @return void */
	public function testOmittedPriceKeepsTaxInclusiveModeWithoutForeignPriceOverridingIt()
	{
		$this->line->subprice_ttc = '93.50';
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame('93.50', $args[2]);
			$this->assertSame('TTC', $args[8]);
			$this->assertSame(0, $args[16]);
			return 1;
		});
		$this->api->putLine(42, 7, array('qty' => 3));
	}

	/** @return void */
	public function testNullDateClearsOnlyThatDate()
	{
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame(0, $args[12]);
			$this->assertSame(1800086400, $args[13]);
			return 1;
		});
		$this->api->putLine(42, 7, array('date_start' => null));
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
	public function testOmittedVatKeepsItsSourceCode()
	{
		$this->line->vat_src_code = 'VATCODE';
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame('10 (VATCODE)', $args[5]);
			return 1;
		});
		$this->api->putLine(42, 7, array('qty' => 3));
	}

	/** @return void */
	public function testExtrafieldValidationKeepsInternalMetadataAndMergesOmittedValues()
	{
		global $extrafields;
		$extrafields = (object) array('attributes' => array('commande_fournisseurdet' => array('type' => array('options_notes' => 'html'))));
		$this->order->expects($this->once())->method('updateline')->willReturnCallback(function (...$args) {
			$this->assertSame(array('options_reference' => 'keep', 'options_notes' => '<b>New note</b>'), $args[14]);
			$this->assertSame(array('options_reference' => 'keep'), $this->line->array_options);
			return 1;
		});
		$this->api->putLine(42, 7, array('array_options' => array('options_notes' => '<b>New note</b>')));
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
	public function testInvalidUpdatesAreRejectedBeforeWriting()
	{
		$this->order->expects($this->never())->method('updateline');
		$invalid = array(null, array(), array('notrigger' => 1), array('fk_product' => 20), array('qty' => 0), array('qty' => 'invalid'), array('subprice' => INF), array('subprice' => 12, 'subprice_ttc' => 15), array('date_start' => -1), array('date_end' => 1.5), array('fk_unit' => 0), array('desc' => array()), array('array_options' => 'invalid'), array('date_start' => 1800086401));
		foreach ($invalid as $patch) {
			try {
				$this->api->putLine(42, 7, $patch);
				$this->fail('An invalid patch was accepted');
			} catch (Luracast\Restler\RestException $exception) {
				$this->assertSame(400, $exception->getCode());
			}
		}
	}

	/** @return void */
	public function testWritePermissionIsRequired()
	{
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturnCallback(function ($module, $object, $permission) {
			return $permission === 'lire';
		});
		DolibarrApiAccess::$user = $actingUser;
		$this->order->expects($this->never())->method('updateline');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(403);
		$this->api->putLine(42, 7, array('qty' => 3));
	}

	/** @return void */
	public function testReadPermissionIsRequired()
	{
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturn(false);
		DolibarrApiAccess::$user = $actingUser;
		$this->order->expects($this->never())->method('fetch');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(403);
		$this->api->getLine(42, 7);
	}

	/** @return void */
	public function testOrderAccessIsRequired()
	{
		SupplierOrderLineApiFixture::$accessAllowed = false;
		$this->order->expects($this->never())->method('updateline');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(403);
		$this->api->putLine(42, 7, array('qty' => 3));
	}

	/** @return void */
	public function testMissingOrderIsRejected()
	{
		$order = $this->createMock(CommandeFournisseur::class);
		$order->method('fetch')->willReturn(0);
		$api = new SupplierOrderLineApiFixture($order);
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(404);
		$api->getLine(42, 7);
	}

	/** @return void */
	public function testReadRejectsALineFromAnotherOrder()
	{
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionCode(404);
		$this->api->getLine(42, 99);
	}

	/** @return void */
	public function testLineReadDoesNotStripLiveObjectMetadata()
	{
		$database = new stdClass();
		$this->order->db = $database;
		$this->line->db = $database;
		$result = $this->api->getLine(42, 7);
		$this->assertSame(7, $result->id);
		$this->assertSame($database, $this->order->db);
		$this->assertSame($database, $this->line->db);
		$this->assertSame('commande_fournisseurdet', $this->line->table_element);
		$this->assertFalse(isset($result->db));
		$this->assertFalse(isset($result->table_element));
	}

	/** @return void */
	public function testPersistenceErrorIsReported()
	{
		$this->order->expects($this->once())->method('updateline')->willReturn(-1);
		$this->order->method('errorsToString')->willReturn('Update failed');
		$this->expectException(Luracast\Restler\RestException::class);
		$this->expectExceptionMessage('Update failed');
		$this->api->putLine(42, 7, array('qty' => 3));
	}
}

/**
 * Replace resource authorization without bypassing line loading or validation.
 */
class SupplierOrderLineApiFixture extends SupplierOrders
{
	/** @var bool Whether the resource access check succeeds */
	public static $accessAllowed = true;

	/**
	 * @param CommandeFournisseur $order Mock order
	 */
	public function __construct($order)
	{
		$this->order = $order;
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
	 * @return bool Whether access is allowed in this fixture
	 */
	protected static function _checkAccessToResource($resource, $resource_id = 0, $dbtablename = '', $feature2 = '', $dbt_keyfield = 'fk_soc', $dbt_select = 'rowid', $parenttableforentity = '')
	{
		// phpcs:enable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
		return self::$accessAllowed;
	}
}
